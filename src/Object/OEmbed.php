<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object;

/**
 * OEmbed data object
 *
 * @see https://oembed.com/#section2.3
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class OEmbed
{
	public $embed_url = '';

	public $type             = '';
	public $title            = '';
	public $description      = '';
	public $author_name      = '';
	public $author_url       = '';
	public $provider_name    = '';
	public $provider_url     = '';
	public $cache_age        = '';
	public $thumbnail_url    = '';
	public $thumbnail_width  = '';
	public $thumbnail_height = '';
	public $html             = '';
	public $url              = '';
	public $width            = '';
	public $height           = '';

	public function __construct($embed_url)
	{
		$this->embed_url = $embed_url;
	}

	public function parseJSON($json_string)
	{
		$properties = json_decode($json_string, true);

		if (empty($properties)) {
			return;
		}

		foreach ($properties as $key => $value) {
			if (in_array($key, ['thumbnail_width', 'thumbnail_height', 'width', 'height'])) {
				// These values should be numbers, so ensure that they really are numbers.
				$value = (int)$value;
			} elseif (is_array($value)) {
				// Ignoring arrays.
			} elseif ($key != 'html') {
				// Avoid being able to inject some ugly stuff through these fields.
				$value = htmlentities($value);
			} else {
				/// @todo Add a way to sanitize the html as well, possibly with an <iframe>?
				$value = mb_convert_encoding($value, 'HTML-ENTITIES', mb_detect_encoding($value));
			}

			if (property_exists(__CLASS__, $key)) {
				$this->{$key} = $value;
			}
		}
	}
}
