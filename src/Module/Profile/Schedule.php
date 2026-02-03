<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Profile;

use Friendica\BaseModule;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\BaseProfile;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;
use Friendica\Model\Item;
use Friendica\Model\Tag;

class Schedule extends BaseProfile
{
	protected function post(array $request = [])
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		if (empty($_REQUEST['delete'])) {
			throw new HTTPException\BadRequestException();
		}

		if (!DBA::exists('delayed-post', ['id' => $_REQUEST['delete'], 'uid' => DI::userSession()->getLocalUserId()])) {
			throw new HTTPException\NotFoundException();
		}

		Post\Delayed::deleteById($_REQUEST['delete']);
	}

	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		$o = self::getTabsHTML('schedule', true, DI::userSession()->getLocalUserNickname(), false);

		$schedule = [];
		$delayed  = DBA::select('delayed-post', [], ['uid' => DI::userSession()->getLocalUserId()]);
		while ($row = DBA::fetch($delayed)) {
			$parameter = Post\Delayed::getParametersForid($row['id']);
			if (empty($parameter)) {
				continue;
			}
			$parameter['item']['uri-id'] = $parameter['parameters']['wid'];
			// scheduling strips [url] BBcode from hashtags so we need to add them back:
			$parameter['item']['body'] = Item::setHashtags($parameter['item']['body']);
			// now we need to extract them into an array:
			$extracted_tags = Tag::getFromBody($parameter['item']['body']);
			// then we prep the body to get rendered HTML back:
			Item::prepareBody($parameter['item'], true);
			// Item:prepareBody creates the tag arrays but they will be empty...
			$tags = [
				'tags'              => [],
				'hashtags'          => [],
				'mentions'          => [],
				'implicit_mentions' => [],
			];
			// populate our $tags placeholder array:
			if (!empty($extracted_tags)) {
				foreach ($extracted_tags as $tag) {
					$html = '<bdi>' . $tag[1] . '<a href="' . $tag[2] . '" target="_blank" rel="noopener noreferrer">' . $tag[3] . '</a></bdi>';
					if ($tag[1] == "#") {
						$tags['hashtags'][] = $html;
					} elseif ($tag[1] == "@") {
						$tags['mentions'][] = $html;
					} elseif ($tag[1] == "1") {
						$tags['implicit_mentions'][] = $html;
					} else { // no idea what it isset
					}
					// tags is catch-all for all of them
					$tags['tags'][] = $html;
				}
			}
			// populate the ['item'] tag arrays...
			$parameter['item']['tags']              = $tags['tags'];
			$parameter['item']['hashtags']          = $tags['hashtags'];
			$parameter['item']['mentions']          = $tags['mentions'];
			$parameter['item']['implicit_mentions'] = $tags['implicit_mentions'];
			// fetch privacy
			if ($parameter['item']['private'] == 1) {
				$parameter['item']['lock'] = DI::l10n()->t('Private Message');
			}
			// we no return to our scheduled program...
			$schedule[] = [
				'id'           => $row['id'],
				'scheduled_at' => DateTimeFormat::local($row['delayed'], "D, d M Y H:i:s e"),
				'content'      => BBCode::toPlaintext($parameter['item']['body'], false),
				'item'         => $parameter['item'],
				'via'          => DI::l10n()->t('via'),
			];
		}
		DBA::close($delayed);

		$tpl = Renderer::getMarkupTemplate('profile/schedule.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseModule::getFormSecurityToken("profile_schedule"),
			'$title'               => DI::l10n()->t('Scheduled Posts'),
			'$nickname'            => $this->parameters['nickname'] ?? '',
			'$scheduled_at'        => DI::l10n()->t('Scheduled'),
			'$content'             => DI::l10n()->t('Content'),
			'$delete'              => DI::l10n()->t('Remove post'),
			'$schedule'            => $schedule,
		]);

		return $o;
	}
}
