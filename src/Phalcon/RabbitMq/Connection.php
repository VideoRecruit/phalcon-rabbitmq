<?php

namespace VideoRecruit\Phalcon\RabbitMq;

use Kdyby\RabbitMq\BaseConsumer;
use Kdyby\RabbitMq\Connection as KdybyConnection;
use Kdyby\RabbitMq\Producer;
use Kdyby\RabbitMq\RpcClient;
use Kdyby\RabbitMq\RpcServer;
use Phalcon\DiInterface;

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
		$service = 'rabbitmq.consumer.' . $name;

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
		$service = 'rabbitmq.producer.' . $name;

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
		$service = 'rabbitmq.rpcClient.' . $name;

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
		$service = 'rabbitmq.rpcServer.' . $name;

		if (!$this->di->has($service)) {
			throw new InvalidArgumentException("Unknown RPC server {$name}");
		}

		return $this->di->get($service);
	}
}
