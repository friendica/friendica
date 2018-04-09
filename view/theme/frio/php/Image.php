<?php
/**
 * @file view/theme/frio/php/Image.php
 * @brief contain methods to deal with images
 */

/**
 * @brief This class contains methods to deal with images
 */
use Friendica\Core\L10n;

class Image
{
	/**
	 * @brief Give all available options for the background image
	 *
	 * @param array $arr Array with the present user settings
	 *
	 * @return array Array with the immage options
	 */
	public static function get_options($arr)
	{
		$bg_image_options = [
					'repeat' => [
						'frio_bg_image_option',	L10n::t("Repeat the image"),	"repeat",	L10n::t("Will repeat your image to fill the background."), ($arr["bg_image_option"] == "repeat")],
					'stretch' => [
						'frio_bg_image_option',	L10n::t("Stretch"),		"stretch",	L10n::t("Will stretch to width/height of the image."), ($arr["bg_image_option"] == "stretch")],
					'cover' => [
						'frio_bg_image_option',	L10n::t("Resize fill and-clip"), "cover",	L10n::t("Resize to fill and retain aspect ratio."),	($arr["bg_image_option"] == "cover")],
					'contain' => [
						'frio_bg_image_option',	L10n::t("Resize best fit"),	"contain",	L10n::t("Resize to best fit and retain aspect ratio."), ($arr["bg_image_option"] == "contain")],
		];

		return $bg_image_options;
	}
}
