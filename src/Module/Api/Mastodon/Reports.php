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

namespace Friendica\Module\Api\Mastodon;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Model\Contact;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/reports/
 */
class Reports extends BaseApi
{
	/** @var \Friendica\Moderation\Factory\Report */
	private $reportFactory;
	/** @var \Friendica\Moderation\Repository\Report */
	private $reportRepo;

	public function __construct(\Friendica\Moderation\Repository\Report $reportRepo, \Friendica\Moderation\Factory\Report $reportFactory, App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->reportFactory = $reportFactory;
		$this->reportRepo    = $reportRepo;
	}

	public function post(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);

		$request = $this->getRequest([
			'account_id' => '',      // ID of the account to report
			'status_ids' => [],      // Array of Statuses to attach to the report, for context
			'comment'    => '',      // Reason for the report (default max 1000 characters)
			'category'   => 'other', // Specify if the report is due to spam, violation of enumerated instance rules, or some other reason.
			'rule_ids'   => [],      // For violation category reports, specify the ID of the exact rules broken.
			'forward'    => false,   // If the account is remote, should the report be forwarded to the remote admin?
		], $request);

		$contact = Contact::getById($request['account_id'], ['id']);
		if (empty($contact)) {
			throw new HTTPException\NotFoundException('Account ' . $request['account_id'] . ' not found');
		}

		$violation = '';
		$rules     = System::getRules(true);

		foreach ($request['rule_ids'] as $key) {
			if (!empty($rules[$key])) {
				$violation .= $rules[$key] . "\n";
			}
		}

		$report = $this->reportFactory->createFromReportsRequest(Contact::getPublicIdByUserId(self::getCurrentUserID()), $request['account_id'], $request['comment'], $request['category'], trim($violation), $request['forward'], $request['status_ids'], self::getCurrentUserID());

		$this->reportRepo->save($report);

		System::jsonExit([]);
	}
}
