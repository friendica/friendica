<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The top-level buckets `src/Object/`, `src/Factory/` and `src/Collection/` are frozen: they may shrink, never grow.
 * New factories, collections and typed objects belong next to their feature (`src/<Feature>/Factory/`, ...).
 *
 * @see https://github.com/friendica/friendica/issues/15981
 */
class FrozenNamespaceTest extends TestCase
{
	/**
	 * The files each bucket had when it was frozen.
	 * Remove an entry when you fold its file into a feature package, never add one.
	 */
	private const FROZEN = [
		'src/Collection' => [
			'src/Collection/Api/Mastodon/Emojis.php',
			'src/Collection/Api/Mastodon/Fields.php',
			'src/Collection/Api/Mastodon/Mentions.php',
			'src/Collection/Api/Notifications.php',
		],
		'src/Factory' => [
			'src/Factory/Api/Friendica/Activities.php',
			'src/Factory/Api/Friendica/Circle.php',
			'src/Factory/Api/Friendica/Photo.php',
			'src/Factory/Api/Mastodon/Account.php',
			'src/Factory/Api/Mastodon/Application.php',
			'src/Factory/Api/Mastodon/Attachment.php',
			'src/Factory/Api/Mastodon/Card.php',
			'src/Factory/Api/Mastodon/Conversation.php',
			'src/Factory/Api/Mastodon/Emoji.php',
			'src/Factory/Api/Mastodon/Error.php',
			'src/Factory/Api/Mastodon/Field.php',
			'src/Factory/Api/Mastodon/ListEntity.php',
			'src/Factory/Api/Mastodon/Mention.php',
			'src/Factory/Api/Mastodon/Notification.php',
			'src/Factory/Api/Mastodon/Poll.php',
			'src/Factory/Api/Mastodon/Relationship.php',
			'src/Factory/Api/Mastodon/Report.php',
			'src/Factory/Api/Mastodon/ScheduledStatus.php',
			'src/Factory/Api/Mastodon/Status.php',
			'src/Factory/Api/Mastodon/StatusSource.php',
			'src/Factory/Api/Mastodon/Subscription.php',
			'src/Factory/Api/Mastodon/Tag.php',
			'src/Factory/Api/Twitter/Attachment.php',
			'src/Factory/Api/Twitter/DirectMessage.php',
			'src/Factory/Api/Twitter/Hashtag.php',
			'src/Factory/Api/Twitter/Media.php',
			'src/Factory/Api/Twitter/Mention.php',
			'src/Factory/Api/Twitter/Status.php',
			'src/Factory/Api/Twitter/Url.php',
			'src/Factory/Api/Twitter/User.php',
		],
		'src/Object' => [
			'src/Object/Api/Friendica/Circle.php',
			'src/Object/Api/Friendica/Notification.php',
			'src/Object/Api/Mastodon/Account.php',
			'src/Object/Api/Mastodon/Activity.php',
			'src/Object/Api/Mastodon/Application.php',
			'src/Object/Api/Mastodon/Attachment.php',
			'src/Object/Api/Mastodon/Card.php',
			'src/Object/Api/Mastodon/Conversation.php',
			'src/Object/Api/Mastodon/Emoji.php',
			'src/Object/Api/Mastodon/Error.php',
			'src/Object/Api/Mastodon/ExtendedDescription.php',
			'src/Object/Api/Mastodon/Field.php',
			'src/Object/Api/Mastodon/Instance.php',
			'src/Object/Api/Mastodon/InstanceV2.php',
			'src/Object/Api/Mastodon/InstanceV2/Accounts.php',
			'src/Object/Api/Mastodon/InstanceV2/Configuration.php',
			'src/Object/Api/Mastodon/InstanceV2/Contact.php',
			'src/Object/Api/Mastodon/InstanceV2/FriendicaExtensions.php',
			'src/Object/Api/Mastodon/InstanceV2/MediaAttachmentsConfig.php',
			'src/Object/Api/Mastodon/InstanceV2/Polls.php',
			'src/Object/Api/Mastodon/InstanceV2/Registrations.php',
			'src/Object/Api/Mastodon/InstanceV2/StatusesConfig.php',
			'src/Object/Api/Mastodon/InstanceV2/Thumbnail.php',
			'src/Object/Api/Mastodon/InstanceV2/Usage.php',
			'src/Object/Api/Mastodon/InstanceV2/UserStats.php',
			'src/Object/Api/Mastodon/ListEntity.php',
			'src/Object/Api/Mastodon/Mention.php',
			'src/Object/Api/Mastodon/Notification.php',
			'src/Object/Api/Mastodon/Poll.php',
			'src/Object/Api/Mastodon/Preferences.php',
			'src/Object/Api/Mastodon/Relationship.php',
			'src/Object/Api/Mastodon/Report.php',
			'src/Object/Api/Mastodon/Role.php',
			'src/Object/Api/Mastodon/ScheduledStatus.php',
			'src/Object/Api/Mastodon/Source.php',
			'src/Object/Api/Mastodon/Stats.php',
			'src/Object/Api/Mastodon/Status.php',
			'src/Object/Api/Mastodon/Status/Counts.php',
			'src/Object/Api/Mastodon/Status/FriendicaDeliveryData.php',
			'src/Object/Api/Mastodon/Status/FriendicaExtension.php',
			'src/Object/Api/Mastodon/Status/FriendicaVisibility.php',
			'src/Object/Api/Mastodon/Status/UserAttributes.php',
			'src/Object/Api/Mastodon/StatusSource.php',
			'src/Object/Api/Mastodon/Subscription.php',
			'src/Object/Api/Mastodon/Tag.php',
			'src/Object/Api/Mastodon/TimelineOrderByTypes.php',
			'src/Object/Api/Mastodon/Token.php',
			'src/Object/Api/Twitter/Attachment.php',
			'src/Object/Api/Twitter/DirectMessage.php',
			'src/Object/Api/Twitter/Hashtag.php',
			'src/Object/Api/Twitter/Media.php',
			'src/Object/Api/Twitter/Mention.php',
			'src/Object/Api/Twitter/SavedSearch.php',
			'src/Object/Api/Twitter/Status.php',
			'src/Object/Api/Twitter/Url.php',
			'src/Object/Api/Twitter/User.php',
			'src/Object/EMail/IEmail.php',
			'src/Object/EMail/ItemCCEMail.php',
			'src/Object/Email.php',
			'src/Object/Image.php',
			'src/Object/Log/ParsedLogLine.php',
			'src/Object/Search/ContactResult.php',
			'src/Object/Search/IResult.php',
			'src/Object/Search/ResultList.php',
		],
	];

	/**
	 * @return array<string, array{string, string[]}>
	 */
	public static function provideFrozenBuckets(): array
	{
		$cases = [];

		foreach (self::FROZEN as $bucket => $files) {
			$cases[$bucket] = [$bucket, $files];
		}

		return $cases;
	}

	/**
	 * @param string[] $frozenFiles
	 */
	#[DataProvider('provideFrozenBuckets')]
	public function testFrozenBucketDoesNotGrow(string $bucket, array $frozenFiles): void
	{
		$actualFiles = $this->phpFilesIn($bucket);

		$this->assertSame(
			[],
			array_values(array_diff($actualFiles, $frozenFiles)),
			sprintf('%s is frozen, put new classes into the feature package they belong to (src/<Feature>/Factory/, ...).', $bucket),
		);

		$this->assertSame(
			[],
			array_values(array_diff($frozenFiles, $actualFiles)),
			sprintf('These files left %s, remove them from %s::FROZEN.', $bucket, self::class),
		);
	}

	/**
	 * @return string[] paths relative to the project root
	 */
	private function phpFilesIn(string $bucket): array
	{
		$root  = dirname(__DIR__, 3) . '/';
		$files = [];

		/** @var \SplFileInfo $file */
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . $bucket, \FilesystemIterator::SKIP_DOTS)) as $file) {
			if ($file->isFile() && $file->getExtension() === 'php') {
				$files[] = substr($file->getPathname(), strlen($root));
			}
		}

		return $files;
	}
}
