<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Privacy;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Model;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Privacy\Entity;
use Friendica\Security\PermissionSet\Repository\PermissionSet;
use Friendica\Util\ACLFormatter;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Outputs the permission tooltip HTML content for the provided item, photo or event id.
 */
class PermissionTooltip extends BaseModule
{
	private Database $dba;
	private ACLFormatter $aclFormatter;
	private IHandleUserSessions $session;
	private IManageConfigValues $config;
	private PermissionSet $permissionSet;
	private EventDispatcherInterface $eventDispatcher;

	public function __construct(
		PermissionSet $permissionSet,
		IManageConfigValues $config,
		IHandleUserSessions $session,
		ACLFormatter $aclFormatter,
		Database $dba,
		EventDispatcherInterface $eventDispatcher,
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = []
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->dba             = $dba;
		$this->aclFormatter    = $aclFormatter;
		$this->session         = $session;
		$this->config          = $config;
		$this->permissionSet   = $permissionSet;
		$this->eventDispatcher = $eventDispatcher;
	}

	protected function rawContent(array $request = [])
	{
		$type        = $this->parameters['type'];
		$referenceId = $this->parameters['id'];

		$expectedTypes = ['item', 'photo', 'event'];
		if (!in_array($type, $expectedTypes)) {
			throw new HTTPException\BadRequestException($this->t('Wrong type "%s", expected one of: %s', $type, implode(', ', $expectedTypes)));
		}

		$condition = ['id' => $referenceId, 'uid' => [0, $this->session->getLocalUserId()]];
		if ($type == 'item') {
			$fields = ['uid', 'psid', 'private', 'uri-id', 'origin', 'network'];
			$model  = Model\Post::selectFirst($fields, $condition, ['order' => ['uid' => true]]);

			if ($model['origin'] || ($model['network'] != Protocol::ACTIVITYPUB)) {
				$permissionSet      = $this->permissionSet->selectOneById($model['psid'], $model['uid']);
				$model['allow_cid'] = $permissionSet->allow_cid;
				$model['allow_gid'] = $permissionSet->allow_gid;
				$model['deny_cid']  = $permissionSet->deny_cid;
				$model['deny_gid']  = $permissionSet->deny_gid;
			} else {
				$model['allow_cid'] = [];
				$model['allow_gid'] = [];
				$model['deny_cid']  = [];
				$model['deny_gid']  = [];
			}
		} else {
			$fields             = ['uid', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid'];
			$model              = $this->dba->selectFirst($type, $fields, $condition);
			$model['allow_cid'] = $this->aclFormatter->expand($model['allow_cid']);
			$model['allow_gid'] = $this->aclFormatter->expand($model['allow_gid']);
			$model['deny_cid']  = $this->aclFormatter->expand($model['deny_cid']);
			$model['deny_gid']  = $this->aclFormatter->expand($model['deny_gid']);
		}

		if (!$this->dba->isResult($model)) {
			throw new HttpException\NotFoundException($this->t('Model not found'));
		}

		$hook_data = [
			'model' => $model,
		];

		$hook_data = $this->eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::PERMISSION_TOOLTIP_CONTENT, $hook_data),
		)->getArray();

		$model = $hook_data['model'] ?? $model;

		$aclReceivers       = new Entity\AclReceivers();
		$addressedReceivers = new Entity\AddressedReceivers();
		if (!empty($model['allow_cid']) || !empty($model['allow_gid']) || !empty($model['deny_cid']) || !empty($model['deny_gid'])) {
			$aclReceivers = $this->fetchReceiversFromACL($model);
		} elseif ($type == 'item') {
			$addressedReceivers = $this->fetchAddressedReceivers($model['uri-id']);
		}

		$privacy = '';
		switch ($model['private'] ?? null) {
			case Model\Item::PUBLIC:
				$privacy = $this->t('Public');
				break;
			case Model\Item::UNLISTED:
				$privacy = $this->t('Unlisted');
				break;
			case Model\Item::PRIVATE:
				$privacy = $this->t('Limited/Private');
				break;
		}

		if ($aclReceivers->isEmpty() && $addressedReceivers->isEmpty() && empty($privacy)) {
			echo $this->t('Remote privacy information not available.');
			exit;
		}

		$tpl    = Renderer::getMarkupTemplate('privacy/permission_tooltip.tpl');
		$output = Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'visible_to' => $this->t('Visible to:'),
				'to'         => $this->t('To:'),
				'cc'         => $this->t('CC:'),
				'bcc'        => $this->t('BCC:'),
				'audience'   => $this->t('Audience:'),
				'attributed' => $this->t('Attributed To:'),
			],
			'$aclReceivers'       => $aclReceivers,
			'$addressedReceivers' => $addressedReceivers,
			'$privacy'            => $privacy,
		]);

		$this->httpExit($output);
	}

	/**
	 * @throws \Exception
	 */
	private function fetchReceiversFromACL(array $model): Entity\AclReceivers
	{
		$allow_cid = $model['allow_cid'];
		$allow_gid = $model['allow_gid'];
		$deny_cid  = $model['deny_cid'];
		$deny_gid  = $model['deny_gid'];

		$allowContacts = [];
		$allowCircles  = [];
		$denyContacts  = [];
		$denyCircles   = [];

		if (count($allow_gid)) {
			$key = array_search(Model\Circle::FOLLOWERS, $allow_gid);
			if ($key !== false) {
				$allowCircles[] = $this->t('Followers');
				unset($allow_gid[$key]);
			}

			$key = array_search(Model\Circle::MUTUALS, $allow_gid);
			if ($key !== false) {
				$allowCircles[] = $this->t('Mutuals');
				unset($allow_gid[$key]);
			}

			foreach ($this->dba->selectToArray('group', ['name'], ['id' => $allow_gid]) as $circle) {
				$allowCircles[] = $circle['name'];
			}
		}

		foreach ($this->dba->selectToArray('contact', ['name'], ['id' => $allow_cid]) as $contact) {
			$allowContacts[] = $contact['name'];
		}

		if (count($deny_gid)) {
			$key = array_search(Model\Circle::FOLLOWERS, $deny_gid);
			if ($key !== false) {
				$denyCircles[] = $this->t('Followers');
				unset($deny_gid[$key]);
			}

			$key = array_search(Model\Circle::MUTUALS, $deny_gid);
			if ($key !== false) {
				$denyCircles[] = $this->t('Mutuals');
				unset($deny_gid[$key]);
			}

			foreach ($this->dba->selectToArray('group', ['name'], ['id' => $allow_gid]) as $circle) {
				$denyCircles[] = $circle['name'];
			}
		}

		foreach ($this->dba->selectToArray('contact', ['name'], ['id' => $deny_cid]) as $contact) {
			$denyContacts[] = $contact['name'];
		}

		return new Entity\AclReceivers($allowContacts, $allowCircles, $denyContacts, $denyCircles);
	}

	/**
	 * @throws InternalServerErrorException
	 */
	private function fetchAddressedReceivers(int $uriId): Entity\AddressedReceivers
	{
		$own_url = '';
		$uid     = $this->session->getLocalUserId();
		if ($uid) {
			$owner = Model\User::getOwnerDataById($uid);
			if (!empty($owner['url'])) {
				$own_url = $owner['url'];
			}
		}

		$receivers = [];
		foreach (Model\Tag::getByURIId($uriId, [Model\Tag::TO, Model\Tag::CC, Model\Tag::BCC, Model\Tag::AUDIENCE, Model\Tag::ATTRIBUTED]) as $receiver) {
			// We only display BCC when it contains the current user
			if (($receiver['type'] == Model\Tag::BCC) && ($receiver['url'] != $own_url)) {
				continue;
			}

			switch (Model\Tag::getTargetType($receiver['url'], false)) {
				case Model\Tag::PUBLIC_COLLECTION:
					$receivers[$receiver['type']][] = $this->t('Public');
					break;
				case Model\Tag::GENERAL_COLLECTION:
					$receivers[$receiver['type']][] = $this->t('Collection (%s)', $receiver['name']);
					break;
				case Model\Tag::FOLLOWER_COLLECTION:
					$apcontact                      = $this->dba->selectFirst('apcontact', ['name'], ['followers' => $receiver['url']]);
					$receivers[$receiver['type']][] = $this->t('Followers (%s)', $apcontact['name'] ?? $receiver['name']);
					break;
				case Model\Tag::ACCOUNT:
					$apcontact                      = Model\APContact::getByURL($receiver['url'], false);
					$receivers[$receiver['type']][] = $apcontact['name'] ?? $receiver['name'];
					break;
				default:
					$receivers[$receiver['type']][] = $receiver['name'];
					break;
			}
		}

		foreach ($receivers as $type => $receiver) {
			$max   = $this->config->get('system', 'max_receivers');
			$total = count($receiver);
			if ($total > $max) {
				$receivers[$type]   = array_slice($receiver, 0, $max);
				$receivers[$type][] = $this->t('%d more', $total - $max);
			}
		}

		return new Entity\AddressedReceivers(
			$receivers[Model\Tag::TO]         ?? [],
			$receivers[Model\Tag::CC]         ?? [],
			$receivers[Model\Tag::BCC]        ?? [],
			$receivers[Model\Tag::AUDIENCE]   ?? [],
			$receivers[Model\Tag::ATTRIBUTED] ?? [],
		);
	}
}
