<?php

namespace VideoRecruit\Phalcon\RabbitMq\Commands;

use Kdyby\RabbitMq;
use Phalcon\DiInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VideoRecruit\Phalcon\RabbitMq\DI\RabbitMqExtension;

/**
 * Class SetupFabricCommand
 *
 * @package VideoRecruit\Phalcon\RabbitMq\Commands
 */
class SetupFabricCommand extends RabbitMq\Command\SetupFabricCommand
{
	/**
	 * @var DiInterface
	 */
	public $di;

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return null|int null or 0 if everything went fine, or an error code
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (defined('AMQP_DEBUG') === false) {
			define('AMQP_DEBUG', (bool) $input->getOption('debug'));
		}

		$output->writeln('Setting up the Rabbit MQ fabric');

		$services = array_merge(
			array_values($this->di->get(RabbitMqExtension::PRODUCERS)->toArray()),
			array_values($this->di->get(RabbitMqExtension::CONSUMERS)->toArray()),
			array_values($this->di->get(RabbitMqExtension::RPC_CLIENTS)->toArray()),
			array_values($this->di->get(RabbitMqExtension::RPC_SERVERS)->toArray())
		);

		foreach ($services as $name) {
			$service = $this->di->get($name);
			$service->setupFabric();
		}
	}
}
