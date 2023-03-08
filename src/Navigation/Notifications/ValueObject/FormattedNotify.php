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
 */

namespace Friendica\Navigation\Notifications\ValueObject;

use Friendica\BaseDataTransferObject;

/**
 * A view-only object for printing item notifications to the frontend
 *
 * @deprecated since 2022.05 Use \Friendica\Navigation\Notifications\ValueObject\FormattedNotification instead
 */
class FormattedNotify extends BaseDataTransferObject
{
	const SYSTEM   = 'system';
	const PERSONAL = 'personal';
	const NETWORK  = 'network';
	const INTRO    = 'intro';
	const HOME     = 'home';

	/** @var string */
	protected $label = '';
	/** @var string */
	protected $link = '';
	/** @var string */
	protected $image = '';
	/** @var string */
	protected $url = '';
	/** @var string */
	protected $text = '';
	/** @var string */
	protected $when = '';
	/** @var string */
	protected $ago = '';
	/** @var boolean */
	protected $seen = false;

	public function __construct(string $label, string $link, string $image, string $url, string $text, string $when, string $ago, bool $seen)
	{
		$this->label = $label ?? '';
		$this->link  = $link  ?? '';
		$this->image = $image ?? '';
		$this->url   = $url   ?? '';
		$this->text  = $text  ?? '';
		$this->when  = $when  ?? '';
		$this->ago   = $ago   ?? '';
		$this->seen  = $seen  ?? false;
	}
}
