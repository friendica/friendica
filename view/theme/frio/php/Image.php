<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * contain methods to deal with images
 */

use Friendica\DI;

/**
 * This class contains methods to deal with images
 */
class Image
{
	/**
	 * Give all available options for the background image
	 *
	 * @param array $arr Array with the present user settings
	 * @return array Array with the immage options
	 */
	public static function get_options(array $arr): array
	{
		$bg_image_options = [
			'stretch' => ['frio_bg_image_option', DI::l10n()->t('Top Banner'), 'stretch', DI::l10n()->t('Resize image to the width of the screen and show background color below on long pages.'), ($arr['bg_image_option'] == 'stretch')],
			'cover'   => ['frio_bg_image_option', DI::l10n()->t('Full screen'), 'cover', DI::l10n()->t('Resize image to fill entire screen, clipping either the right or the bottom.'), ($arr['bg_image_option'] == 'cover')],
			'contain' => ['frio_bg_image_option', DI::l10n()->t('Single row mosaic'), 'contain', DI::l10n()->t('Resize image to repeat it on a single row, either vertical or horizontal.'), ($arr['bg_image_option'] == 'contain')],
			'repeat'  => ['frio_bg_image_option', DI::l10n()->t('Mosaic'), 'repeat', DI::l10n()->t('Repeat image to fill the screen.'), ($arr['bg_image_option'] == 'repeat')],
		];

		return $bg_image_options;
	}
}
