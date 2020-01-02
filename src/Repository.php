<?php

namespace Friendica;

use Psr\Log\LoggerInterface;

/**
 * Repositories contain the business logic of each domanis. They directly act on Models and Collections.
 *
 * @see Model
 * @see Collection
 */
abstract class Repository
{
	/** @var LoggerInterface */
	protected $logger;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}
}
