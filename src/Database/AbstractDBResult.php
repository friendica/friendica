<?php

namespace Friendica\Database;

use Friendica\Database\Capabilities\IDatabaseResult;

abstract class AbstractDBResult implements IDatabaseResult
{
	public function toArray(bool $doClose = true, int $count = 0): array
	{
		$data = [];
		while ($row = $this->fetch()) {
			$data[] = $row;
			if (($count != 0) && count($data) == $count) {
				return $data;
			}
		}

		if ($doClose) {
			$this->close();
		}

		return $data;
	}
}
