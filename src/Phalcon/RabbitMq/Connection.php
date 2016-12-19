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
 * @package VideoRecruit\Phalcon\RabbitMq
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
		return RabbitMqExtension::getConsumer($this->di, $name);
	}

	/**
	 * @param string $name
	 * @return Producer
	 * @throws InvalidArgumentException
	 */
	public function getProducer($name)
	{
		return RabbitMqExtension::getProducer($this->di, $name);
	}

	/**
	 * @param string $name
	 * @return RpcClient
	 * @throws InvalidArgumentException
	 */
	public function getRpcClient($name)
	{
		return RabbitMqExtension::getRpcClient($this->di, $name);
	}

	/**
	 * @param string $name
	 * @return RpcServer
	 * @throws InvalidArgumentException
	 */
	public function getRpcServer($name)
	{
		return RabbitMqExtension::getRpcServer($this->di, $name);
	}
}
