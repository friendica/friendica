<?php

namespace Friendica\Database\Capabilities;

interface IDatabaseLock
{
	public function lock(string $table): bool;
	public function unlock(): bool;
}
