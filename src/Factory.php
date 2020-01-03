<?php

namespace Friendica;

use Psr\Log\LoggerInterface;

/**
 * Factories act as an intermediary to avoid direct Entitiy instanciation.
 *
 * @see Model
 * @see Collection
 */
abstract class Factory
{
	/** @var LoggerInterface */
	protected $logger;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}
}
