<?php

namespace Friendica\Core\Logger;

use Friendica\Core\Config;

trait LogConfigTrait
{
	protected $config_category = 'log_channel';
	protected $name     = '';

	protected function getConfig($fieldName, $default_value = null)
	{
		return Config::get($this->config_category, sprintf("%s.%s", $this->name, $fieldName), $default_value);
	}

	protected function setConfig($fieldname, $value)
	{
		Config::set($this->config_category, sprintf('%s.%s', $this->name, $fieldname), $value);
	}
}
