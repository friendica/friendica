<?php

/**
 * @file view/theme/frio/php/Image.php
 * @brief contain methods to deal with images
 */

/**
 * @brief This class contains methods to deal with images
 */
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
        $bg_image_options = array(
                    'repeat' => array(
                        'frio_bg_image_option',    t("Repeat the image"),    "repeat",    t("Will repeat your image to fill the background."), ($arr["bg_image_option"] == "repeat")),
                    'stretch' => array(
                        'frio_bg_image_option',    t("Stretch"),        "stretch",    t("Will stretch to width/height of the image."), ($arr["bg_image_option"] == "stretch")),
                    'cover' => array(
                        'frio_bg_image_option',    t("Resize fill and-clip"), "cover",    t("Resize to fill and retain aspect ratio."),    ($arr["bg_image_option"] == "cover")),
                    'contain' => array(
                        'frio_bg_image_option',    t("Resize best fit"),    "contain",    t("Resize to best fit and retain aspect ratio."), ($arr["bg_image_option"] == "contain")),
        );

        return $bg_image_options;
    }
}
