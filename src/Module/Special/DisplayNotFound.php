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

namespace Friendica\Module\Special;

use Friendica\Core\Renderer;

/**
 * This is a special case of the HTTPException module where the message is intended to be HTML.
 * This module should be called directly from the Display module and shouldn't be routed to.
 */
class DisplayNotFound extends \Friendica\BaseModule
{
	protected function content(array $request = []): string
	{
		$tpl = Renderer::getMarkupTemplate('special/displaynotfound.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title'   => $this->t('Not Found'),
				'message' => $this->t("<p>Unfortunately, the requested conversation isn't available to you.</p>
<p>Possible reasons include:</p>
<ul>
	<li>The top-level post isn't visible.</li>
	<li>The top-level post was deleted.</li>
	<li>The node has blocked the top-level author or the author of the shared post.</li>
	<li>You have ignored or blocked the top-level author or the author of the shared post.</li>
</ul>"),
			]
		]);
	}
}
