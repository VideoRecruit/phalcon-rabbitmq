<?php

namespace VideoRecruit\Phalcon\RabbitMq\DI;

use Nette\DI\Config\Helpers as ConfigHelpers;
use Phalcon\Config;
use Phalcon\DiInterface;
use VideoRecruit\Phalcon\RabbitMq\Commands\SetupFabricCommand;
use VideoRecruit\Phalcon\RabbitMq\Connection;
use VideoRecruit\Phalcon\RabbitMq\InvalidArgumentException;

/**
 * Class RabbitMqExtension
 *
 * @package VideoRecruit\Phalcon\RabbitMq\DI
 */
class RabbitMqExtension
{
	const PREFIX_CONNECTION = 'rabbitmq.connection.';
	const PREFIX_PRODUCER = 'rabbitmq.producer.';
	const PREFIX_CONSUMER = 'rabbitmq.consumer.';
	const PREFIX_RPC_CLIENT = 'rabbitmq.rpc.client.';
	const PREFIX_RPC_SERVER = 'rabbitmq.rpc.server.';
	const PREFIX_CONSOLE_COMMAND = 'rabbitmq.command';

	const PRODUCERS = 'rabbitmq.producers';
	const CONSUMERS = 'rabbitmq.consumers';
	const RPC_CLIENTS = 'rabbitmq.rpc.clients';
	const RPC_SERVERS = 'rabbitmq.rpc.servers';
	const CONSOLE_COMMANDS = 'rabbitmq.commands';

	/**
	 * @var DiInterface
	 */
	private $di;

	/**
	 * @var array
	 */
	public $defaults = [
		'connection' => [],
		'producers' => [],
		'consumers' => [],
		'rpcClients' => [],
		'rpcServers' => [],
		'autoSetupFabric' => FALSE,
	];

	/**
	 * @var array
	 */
	public $connectionDefaults = [
		'host' => '127.0.0.1',
		'port' => 5672,
		'user' => NULL,
		'password' => NULL,
		'vhost' => '/',
		'insist' => FALSE,
		'login_method' => 'AMQPLAIN',
		'login_response' => NULL,
		'locale' => 'en_US',
		'connection_timeout' => 3,
		'read_write_timeout' => 3,
		'context' => NULL,
		'keepalive' => FALSE,
		'heartbeat' => 0,
	];

	/**
	 * @var array
	 */
	public $producerDefaults = [
		'connection' => 'default',
		'class' => 'Kdyby\RabbitMq\Producer',
		'exchange' => [],
		'queue' => [],
		'contentType' => 'application/json',
		'deliveryMode' => 2, 'routingKey' => '',
		'autoSetupFabric' => NULL, // inherits from `rabbitmq: autoSetupFabric:`
	];

	/**
	 * @var array
	 */
	public $consumersDefaults = [
		'connection' => 'default',
		'exchange' => [],
		'queues' => [], // for multiple consumers // @todo: not implemented yet
		'queue' => [], // for single consumer
		'callback' => NULL,
		'qos' => [],
		'idleTimeout' => NULL,
		'autoSetupFabric' => NULL, // inherits from `rabbitmq: autoSetupFabric:`
	];

	/**
	 * @var array
	 */
	public $exchangeDefaults = [
		'passive' => FALSE,
		'durable' => TRUE,
		'autoDelete' => FALSE,
		'internal' => FALSE,
		'nowait' => FALSE,
		'arguments' => NULL,
		'ticket' => NULL,
		'declare' => TRUE,
	];

	/**
	 * @var array
	 */
	public $queueDefaults = [
		'name' => '',
		'passive' => FALSE,
		'durable' => TRUE,
		'noLocal' => FALSE,
		'noAck' => FALSE,
		'exclusive' => FALSE,
		'autoDelete' => FALSE,
		'nowait' => FALSE,
		'arguments' => NULL,
		'ticket' => NULL,
		'routing_keys' => [],
	];

	/**
	 * @var array
	 */
	public $qosDefaults = [
		'prefetchSize' => 0,
		'prefetchCount' => 0,
		'global' => FALSE,
	];

	/**
	 * RabbitMqExtension constructor.
	 *
	 * @param DiInterface $di
	 * @param array|Config $config
	 * @throws InvalidArgumentException
	 */
	public function __construct(DiInterface $di, $config)
	{
		$this->di = $di;

		if ($config instanceof Config) {
			$config = $config->toArray();
		} elseif (!is_array($config)) {
			throw new InvalidArgumentException('Config has to be either an array or ' .
				'a configuration service name within the DI container.');
		}

		$config = $this->mergeConfigs($config, $this->defaults);

		$this->loadConnections($config['connection']);
		$this->loadProducers($config['producers']);
		$this->loadConsumers($config['consumers']);
		$this->loadRpcClients($config['rpcClients']);
		$this->loadRpcServers($config['rpcServers']);
		$this->loadConsole();
	}

	/**
	 * Register producer/consumer/RPCClient/RPCServer services into the DI container.
	 *
	 * @param DiInterface $di
	 * @param array|Config $config
	 * @return self
	 * @throws InvalidArgumentException
	 */
	public static function register(DiInterface $di, $config)
	{
		return new self($di, $config);
	}

	/**
	 * Register connections to the DI container.
	 *
	 * @param array $connections
	 */
	private function loadConnections(array $connections)
	{
		foreach ($connections as $name => $config) {
			$config = $this->mergeConfigs($config, $this->connectionDefaults);

			$di = $this->di;
			$di->setShared(self::PREFIX_CONNECTION . $name, function () use ($config, $di) {
				return (new Connection(
					$config['host'],
					$config['port'],
					$config['user'],
					$config['password'],
					$config['vhost'],
					$config['insist'],
					$config['login_method'],
					$config['login_response'],
					$config['locale'],
					$config['connection_timeout'],
					$config['read_write_timeout'],
					$config['context'],
					$config['keepalive'],
					$config['heartbeat']
				))->setDi($di);
			});
		}
	}

	/**
	 * Register producers to the DI container.
	 *
	 * @param array $producers
	 */
	private function loadProducers(array $producers)
	{
		$producerServices = [];

		foreach ($producers as $name => $config) {
			$autoSetup = ['autoSetupFabric' => $this->defaults['autoSetupFabric']];

			$config = $this->mergeConfigs($config, $autoSetup + $this->producerDefaults);
			$config['queue'] = $this->mergeConfigs($config['queue'], $this->queueDefaults);

			$calls = [
				[
					'method' => 'setContentType',
					'arguments' => [
						$this->createParameter($config['contentType']),
					],
				],
				[
					'method' => 'setDeliveryMode',
					'arguments' => [
						$this->createParameter($config['deliveryMode']),
					],
				],
				[
					'method' => 'setRoutingKey',
					'arguments' => [
						$this->createParameter($config['routingKey']),
					],
				],
				[
					'method' => 'setQueueOptions',
					'arguments' => [
						$this->createParameter($config['queue']),
					],
				],
			];

			if (!empty($config['exchange'])) {
				$config['exchange'] = $this->mergeConfigs($config['exchange'], $this->exchangeDefaults);
				$calls[] = [
					'method' => 'setExchangeOptions',
					'arguments' => [
						$this->createParameter($config['exchange']),
					],
				];
			}

			if ($config['autoSetupFabric'] === FALSE) {
				$calls[] = [
					'method' => 'disableAutoSetupFabric',
				];
			}

			$serviceName = self::PREFIX_PRODUCER . $name;
			$this->di->setShared($serviceName, [
				'className' => $config['class'],
				'arguments' => [
					$this->createParameter($this->di->get(self::PREFIX_CONNECTION . $config['connection'])),
				],
				'calls' => $calls,
			]);

			$producerServices[$name] = $serviceName;
		}

		// list of all registered producers
		$this->di->setShared(self::PRODUCERS, new Config($producerServices));
	}

	/**
	 * Register consumers to the DI container.
	 *
	 * @param array $consumers
	 */
	private function loadConsumers(array $consumers)
	{
		$consumerServices = [];

		foreach ($consumers as $name => $config) {
			$autoSetup = ['autoSetupFabric' => $this->defaults['autoSetupFabric']];

			$config = $this->mergeConfigs($config, $autoSetup + $this->consumersDefaults);
			$config['queue'] = $this->mergeConfigs($config['queue'], $this->queueDefaults);
			$config['callback'] = $this->fixCallback($this->di, $config['callback']);

			$calls = [
				[
					'method' => 'setQueueOptions',
					'arguments' => [
						$this->createParameter($config['queue']),
					],
				],
				[
					'method' => 'setCallback',
					'arguments' => [
						$this->createParameter($config['callback']),
					],
				]
			];

			if (!empty($config['exchange'])) {
				$config['exchange'] = $this->mergeConfigs($config['exchange'], $this->exchangeDefaults);
				$calls[] = [
					'method' => 'setExchangeOptions',
					'arguments' => [
						$this->createParameter($config['exchange']),
					],
				];
			}

			if (array_filter($config['qos'])) { // has values
				$config['qos'] = $this->mergeConfigs($config['qos'], $this->qosDefaults);
				$calls[] = [
					'method' => 'setQosOptions',
					'arguments' => [
						$this->createParameter($config['qos']['prefetchSize']),
						$this->createParameter($config['qos']['prefetchCount']),
						$this->createParameter($config['qos']['global']),
					],
				];
			}

			if ($config['idleTimeout']) {
				$calls[] = [
					'method' => 'setIdleTimeout',
					'arguments' => [
						$this->createParameter($config['idleTimeout']),
					],
				];
			}

			if ($config['autoSetupFabric'] === FALSE) {
				$calls[] = [
					'method' => 'disableAutoSetupFabric',
				];
			}

			$serviceName = self::PREFIX_CONSUMER . $name;
			$this->di->setShared($serviceName, [
				'className' => 'Kdyby\RabbitMq\Consumer',
				'arguments' => [
					$this->createParameter($this->di->get(self::PREFIX_CONNECTION . $config['connection'])),
				],
				'calls' => $calls,
			]);

			$consumerServices[$name] = $serviceName;
		}

		// list of all registered consumers
		$this->di->setShared(self::CONSUMERS, new Config($consumerServices));
	}

	/**
	 * Register RPC clients to the DI container.
	 *
	 * @param array $rpcClients
	 */
	private function loadRpcClients(array $rpcClients)
	{
		// todo
	}

	/**
	 * Register RPC servers to the DI container.
	 *
	 * @param array $rpcServers
	 */
	private function loadRpcServers(array $rpcServers)
	{
		// todo
	}

	/**
	 * Register console commands to the DI container.
	 */
	private function loadConsole()
	{
		$commandServices = [];

		// register commands
		foreach ([
			'Kdyby\RabbitMq\Command\ConsumerCommand',
			'Kdyby\RabbitMq\Command\PurgeConsumerCommand',
			'Kdyby\RabbitMq\Command\RpcServerCommand',
			'Kdyby\RabbitMq\Command\StdInProducerCommand',
		] as $i => $class) {
			$command = lcfirst(str_replace(['Kdyby\RabbitMq\Command\\', 'Command'], '', $class));
			$serviceName = self::PREFIX_CONSOLE_COMMAND . $command;
			$this->di->setShared($serviceName, [
				'className' => $class,
				'properties' => [
					[
						'name' => 'connection',
						'value' => [
							'type' => 'service',
							'name' => self::PREFIX_CONNECTION . 'default'
						],
					]
				],
			]);

			$commandServices[$class] = $serviceName;
		}

		// setup fabric command
		$fabricCommandName = self::PREFIX_CONSOLE_COMMAND . 'setupFabric';
		$this->di->setShared($fabricCommandName, [
			'className' => SetupFabricCommand::class,
			'properties' => [
				[
					'name' => 'di',
					'value' => [
						'type' => 'parameter',
						'value' => $this->di,
					],
				],
			],
		]);

		$commandServices[SetupFabricCommand::class] = $fabricCommandName;

		// list of all registered consumers
		$this->di->setShared(self::CONSOLE_COMMANDS, new Config($commandServices));
	}

	/**
	 * Merge two configs.
	 *
	 * @param array $config
	 * @param array $defaults
	 * @return array
	 */
	private function mergeConfigs(array $config, array $defaults)
	{
		return ConfigHelpers::merge($config, $defaults);
	}

	/**
	 * Prepare argument for di service
	 *
	 * @param mixed $value
	 * @return array
	 */
	private function createParameter($value)
	{
		return [
			'type' => 'parameter',
			'value' => $value,
		];
	}

	/**
	 * Fix callback - transform from string to service
	 *
	 * @param DiInterface $di
	 * @param string $callback
	 * @return array
	 */
	private function fixCallback(DiInterface $di, $callback)
	{
		if (is_string($callback)) {
			list ($service, $method) = explode('::', $callback);

			return [$di->get($service), $method];
		}

		return $callback;
	}
}
