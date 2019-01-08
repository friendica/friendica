<?php

/* Local settings; not checked into git. */
$localSettings = [];
if (\is_readable(__DIR__ . '/../config/local.config.php')) {
	/** @var array $localSettings */
	$localSettings = include __DIR__ . '/../config/local.config.php';
}

if ($localSettings == false && \is_readable(__DIR__ . '/../config/local.ini.php')) {
	$settingsFile = include __DIR__ . '/../config/local.ini.php';
	if (\is_string($settingsFile)) {
		/** @var array $localSettings */
		$localSettings = \parse_ini_string($settingsFile, true, INI_SCANNER_TYPED);
	}
}

$settings = include __DIR__ . '/../config/settings.config.php';

function settings_merge_recursive($defaults, $local) {
	$return = [];
	foreach ($defaults as $key => $value) {
		if (isset($local[$key])) {
			if (is_array($value)) {
				$return[$key] = settings_merge_recursive($value, $local[$key]);
			} else {
				$return[$key] = $local[$key];
			}
		} else {
			$return[$key] = $value;
		}
	}
	return $return;
}
return [
	'settings' => settings_merge_recursive($settings, $localSettings)
];
