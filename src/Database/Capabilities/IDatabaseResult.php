<?php

namespace Friendica\Database\Capabilities;

interface IDatabaseResult
{
	public function getAffectedRows(): int;
	public function toArray(bool $doClose = true, int $count = 0): array;

	/**
	 * @return array|bool
	 */
	public function fetch();
	public function close(): bool;
	public function getColumnCount(): int;
}
