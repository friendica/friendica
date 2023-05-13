<?php

namespace Friendica\Database;

use Friendica\Database\Capabilities\IDatabaseError;

class DBError implements IDatabaseError
{
	/** @var string */
	protected $error;
	/** @var int */
	protected $errorNumber;

	public function getError(): string
	{
		return $this->error;
	}

	public function getErrorNumber(): int
	{
		return $this->errorNumber;
	}

	public function __construct(string $error = null, int $errorNumber = null)
	{
		$this->checkError($error, $errorNumber);
	}

	protected function checkError(string $error = null, int $errorNumber = null)
	{
		// See issue https://github.com/friendica/friendica/issues/8572
		// Ensure that we always get an error message on an error.
		if (empty($errorNumber)) {
			$this->errorNumber = -1;
		}

		if (empty($error)) {
			$this->error = 'Unknown database error';
		}
	}
}
