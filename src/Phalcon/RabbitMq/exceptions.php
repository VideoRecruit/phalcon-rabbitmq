<?php

namespace VideoRecruit\Phalcon\RabbitMq;

/**
 * Common exception interface.
 */
interface Exception
{
}

/**
 * Class InvalidArgumentException
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{
}
