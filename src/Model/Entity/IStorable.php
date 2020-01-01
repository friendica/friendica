<?php

namespace Friendica\Model\Entity;

/**
 * @property int id
 */
interface IStorable
{
	public function getChanged();
	public function isStored();
	public function isChanged();
	public function asArray();
}
