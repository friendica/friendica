<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Admin;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Moderation\Entity\Report as ReportEntity;
use Friendica\Moderation\Repository\Report as ReportRepository;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/methods/admin/accounts/
 */
class Accounts extends BaseApi
{
	public function __construct(
		private readonly Database $database,
		private readonly ReportRepository $reportRepository,
		\Friendica\Factory\Api\Mastodon\Error $errorFactory,
		AppHelper $appHelper,
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		ApiResponse $response,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	/**
	 * Perform an action against an account.
	 *
	 * @see POST /api/v1/admin/accounts/:id/action
	 *
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$this->checkModeratorAccess();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$uid      = self::getCurrentUserID();
		$action   = $this->parameters['action'] ?? '';
		$targetId = (int) $this->parameters['id'];

		$request = $this->getRequest([
			'type'                    => '',
			'report_id'               => '',
			'warning_preset_id'       => '',
			'text'                    => '',
			'send_email_notification' => false,
		], $request);

		$targetId = Contact::getPublicContactId($targetId, 0);
		if (empty($targetId)) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		$localUserId = User::getIdForContactId($targetId);

		switch ($action) {
			case 'action':
				$this->performAccountAction(
					$request['type'],
					$targetId,
					$localUserId,
					$request['report_id'],
					$uid,
				);
				break;
			case 'approve':
				if (!$localUserId) {
					$this->logAndJsonError(403, $this->errorFactory->Forbidden());
				}
				$this->approveAccount($localUserId);
				break;
			case 'reject':
				if (!$localUserId) {
					$this->logAndJsonError(403, $this->errorFactory->Forbidden());
				}
				$this->rejectAccount($localUserId);
				break;
			case 'enable':
				if (!$localUserId) {
					$this->logAndJsonError(403, $this->errorFactory->Forbidden());
				}
				User::block($localUserId, false);
				break;
			case 'unsilence':
				Contact::unhide($targetId);
				break;
			case 'unsuspend':
				// Unsuspend: unblock the contact; for local users also re-enable the account.
				// Note: User::remove() is irreversible, so unsuspend only works on blocked accounts.
				Contact::unblock($targetId);
				if ($localUserId) {
					User::block($localUserId, false);
				}
				break;
			case 'unsensitive':
				$this->database->update('contact', ['sensitive' => false], ['id' => $targetId]);
				break;
			default:
				$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$this->jsonExit($this->buildAdminAccountResponse($targetId, $localUserId));
	}

	/**
	 * Perform a typed moderation action against an account.
	 *
	 * Types: none, sensitive, disable, silence, suspend
	 */
	private function performAccountAction(string $type, int $publicContactId, int $localUserId, string $reportId, int $moderatorUid): void
	{
		switch ($type) {
			case 'none':
				// Warning: no system-level action, just note via report
				break;
			case 'sensitive':
				// Mark all future posts from this account as sensitive
				// @todo This most likely will not work at the moment
				$this->database->update('contact', ['sensitive' => true], ['id' => $publicContactId]);
				break;
			case 'disable':
				// Disable login for local users only
				if (!$localUserId) {
					$this->logAndJsonError(403, $this->errorFactory->Forbidden());
				}
				User::block($localUserId);
				break;
			case 'silence':
				// Silence contact globally (Posts will not appear in federated timelines, but followers will still see them in their home timeline)
				Contact::hide($publicContactId);
				break;
			case 'suspend':
				// For local users: schedule removal; for remote: block globally
				Contact::block($publicContactId);
				if ($localUserId) {
					try {
						User::remove($localUserId);
					} catch (\Throwable $e) {
						$this->logger->warning('Could not remove local user during suspension', [
							'uid'   => $localUserId,
							'error' => $e->getMessage(),
						]);
					}
				}
				break;
			default:
				$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		// If a report_id was provided, resolve the associated report
		if (!empty($reportId) && ctype_digit($reportId)) {
			try {
				$report = $this->reportRepository->selectOneById((int) $reportId);
				if ($report->status === ReportEntity::STATUS_OPEN) {
					$this->reportRepository->updateModerationState((int) $reportId, [
						'resolution'      => ReportEntity::RESOLUTION_ACCEPTED,
						'status'          => ReportEntity::STATUS_CLOSED,
						'last-editor-uid' => $moderatorUid,
					]);
				}
			} catch (\Throwable $e) {
				$this->logger->warning('Could not resolve report after account action', [
					'report_id' => $reportId,
					'error'     => $e->getMessage(),
				]);
			}
		}
	}

	/**
	 * Approve a pending local account registration.
	 */
	private function approveAccount(int $localUserId): void
	{
		$user = User::getById($localUserId, ['verified', 'blocked', 'account_removed']);
		if (empty($user)) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}
		// Mark as verified/approved
		$this->database->update('user', ['verified' => true, 'blocked' => false], ['uid' => $localUserId]);
		// Remove any pending register entry
		$this->database->delete('register', ['uid' => $localUserId]);
	}

	/**
	 * Reject a pending local account registration.
	 */
	private function rejectAccount(int $localUserId): void
	{
		$user = User::getById($localUserId, ['verified', 'blocked', 'account_removed']);
		if (empty($user)) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}
		try {
			User::remove($localUserId);
		} catch (\Throwable $e) {
			$this->logger->warning('Could not remove rejected account', [
				'uid'   => $localUserId,
				'error' => $e->getMessage(),
			]);
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}
	}

	/**
	 * Build a minimal Admin::Account-compatible response array.
	 *
	 * @see https://docs.joinmastodon.org/entities/Admin_Account/
	 */
	private function buildAdminAccountResponse(int $publicContactId, int $localUserId): array
	{
		$contact = DBA::selectFirst('contact', ['id', 'name', 'nick', 'url', 'baseurl', 'network', 'created', 'blocked', 'sensitive'], ['id' => $publicContactId, 'uid' => 0]);
		if (empty($contact)) {
			return ['id' => (string) $publicContactId];
		}

		$domain = null;
		if (!empty($contact['baseurl'])) {
			$parsed = parse_url((string) $contact['baseurl']);
			$domain = $parsed['host'] ?? null;
		}

		$response = [
			'id'             => (string) $publicContactId,
			'username'       => $contact['nick'] ?? '',
			'domain'         => $domain,
			'created_at'     => DateTimeFormat::utc($contact['created'], DateTimeFormat::JSON),
			'ip'             => null,
			'role'           => null,
			'confirmed'      => true,
			'suspended'      => false,
			'silenced'       => (bool) ($contact['blocked'] ?? false),
			'sensitized'     => (bool) ($contact['sensitive'] ?? false),
			'disabled'       => false,
			'approved'       => true,
			'locale'         => null,
			'invite_request' => null,
			'ips'            => [],
		];

		if ($localUserId) {
			$user = User::getById($localUserId, ['email', 'language', 'blocked', 'verified', 'account_removed', 'register_date']);
			if (!empty($user)) {
				$response['email']     = $user['email'] ?? '';
				$response['confirmed'] = (bool) ($user['verified'] ?? false);
				$response['disabled']  = (bool) ($user['blocked'] ?? false);
				$response['suspended'] = (bool) ($user['account_removed'] ?? false);
				$response['locale']    = $user['language'] ?? null;
			}
		}

		return $response;
	}
}
