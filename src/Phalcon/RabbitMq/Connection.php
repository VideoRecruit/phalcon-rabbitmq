<?php

namespace VideoRecruit\Phalcon\RabbitMq;

use Kdyby\RabbitMq\BaseConsumer;
use Kdyby\RabbitMq\Connection as KdybyConnection;
use Kdyby\RabbitMq\Producer;
use Kdyby\RabbitMq\RpcClient;
use Kdyby\RabbitMq\RpcServer;
use Phalcon\DiInterface;
use VideoRecruit\Phalcon\RabbitMq\DI\RabbitMqExtension;

/**
 * Class Connection
 *
 * @package Ihub\RabbitMq
 */
class Connection extends KdybyConnection
{

	/**
	 * @var DiInterface
	 */
	private $di;

	/**
	 * @param DiInterface $di
	 * @return $this
	 */
	public function setDi(DiInterface $di)
	{
		$this->di = $di;

		return $this;
	}

	/**
	 * @param string $name
	 * @return BaseConsumer
	 * @throws InvalidArgumentException
	 */
	public function getConsumer($name)
	{
		$service = RabbitMqExtension::PREFIX_CONSUMER . $name;

		if (!$this->di->has($service)) {
			throw new InvalidArgumentException("Unknown consumer {$name}");
		}

		return $this->di->get($service);
	}

	/**
	 * @param string $name
	 * @return Producer
	 * @throws InvalidArgumentException
	 */
	public function getProducer($name)
	{
		$service = RabbitMqExtension::PREFIX_PRODUCER . $name;

		if (!$this->di->has($service)) {
			throw new InvalidArgumentException("Unknown producer {$name}");
		}

		return $this->di->get($service);
	}

	/**
	 * @param string $name
	 * @return RpcClient
	 * @throws InvalidArgumentException
	 */
	public function getRpcClient($name)
	{
		$service = RabbitMqExtension::PREFIX_RPC_CLIENT . $name;

		if (!$this->di->has($service)) {
			throw new InvalidArgumentException("Unknown RPC client {$name}");
		}

		return $this->di->get($service);
	}

	/**
	 * @param string $name
	 * @return RpcServer
	 * @throws InvalidArgumentException
	 */
	public function getRpcServer($name)
	{
		$service = RabbitMqExtension::PREFIX_RPC_SERVER . $name;

		if (!$this->di->has($service)) {
			throw new InvalidArgumentException("Unknown RPC server {$name}");
		}

		return $this->di->get($service);
	}
}
