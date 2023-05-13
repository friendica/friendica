<?php

namespace Friendica\Database\Capabilities;

interface IDatabaseError
{
	public function getError(): string;
	public function getErrorNumber(): int;
}
