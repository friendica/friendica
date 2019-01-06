<?php

namespace Friendica\Core\Config;

interface IConfigurable
{
	function loadConfig();
	function saveConfig();
	function toArray();
}
