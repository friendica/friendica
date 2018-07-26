<?php

namespace Friendica\Object;

/**
 * OEmbed data object
 *
 * @see https://oembed.com/#section2.3
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class OEmbed
{
	public $embed_url        = '';

	public $type             = '';
	public $title            = '';
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
			if (property_exists(__CLASS__, $key)) {
				$this->{$key} = $value;
			}
		}
	}
}
