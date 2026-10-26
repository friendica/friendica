<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Mod;

use Dice\Dice;
use Friendica\App\Arguments;
use Friendica\App\Router;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Friendica\Test\ApiTestCase;
use Friendica\Util\DateTimeFormat;
use phpmock\phpunit\PHPMock;

require_once __DIR__ . '/../../../mod/item.php';

/**
 * Characterization tests for the legacy mod/item.php global functions ahead of their planned migration into the Module/Service/Repository architecture.
 *
 * These tests lock in *current* behavior; they are not a statement of desired behavior.
 *
 * Known gap: branches that terminate via a raw System::jsonExit()/System::exit() call (as opposed to a redirect, which throws a catchable HTTPException\FoundException) cannot be exercised here
 */
class ItemTest extends ApiTestCase
{
	use PHPMock;

	protected function setUp(): void
	{
		parent::setUp();

		DI::session()->set('authenticated', true);
		DI::session()->set('uid', 42);

		DBA::update('apcontact', ['updated' => DateTimeFormat::utcNow()], ['url' => 'https://friendica.local/profile/selfcontact']);
	}

	private function mockRedirectHeader()
	{
		return $this->getFunctionMock('Friendica\Core', 'header');
	}

	/**
	 * Whether the logged in user (uid 42) can still see the given `post-user` row.
	 *
	 * Deletion in Friendica only sets a flag, so "gone" means "no longer returned by the user facing
	 * query" - Post::selectFirstForUser() adds the `NOT deleted` condition (Post::exists() doesn't) and
	 * is also what drop_item() itself uses to locate an item.
	 */
	private function isVisibleForUser(int $id): bool
	{
		return DBA::isResult(Post::selectFirstForUser(42, ['id'], ['id' => $id]));
	}

	private function setArgs(string $pagename): void
	{
		$server                   = $_SERVER;
		$server['REQUEST_METHOD'] = Router::GET;

		$this->dice = $this->dice->addRule(Arguments::class, [
			'instanceOf' => Arguments::class,
			'call'       => [
				['determine', [$server, ['pagename' => $pagename]], Dice::CHAIN_CALL],
			],
		]);
		DI::init($this->dice);
	}

	public function testItemPostRequiresLogin(): void
	{
		DI::session()->set('authenticated', false);
		try {
			$this->expectException(HTTPException\ForbiddenException::class);

			item_post();
		} finally {
			DI::session()->set('authenticated', true);
		}
	}

	public function testItemPostDuplicateSubmissionShortCircuitsBeforeDispatch(): void
	{
		DI::session()->set('post-random', 'same-token');

		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		$_REQUEST = [
			'post_id_random' => 'same-token',
			'body'           => 'This must never be stored',
			'return'         => 'network',
		];

		try {
			item_post();
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		} finally {
			$_REQUEST = [];
		}

		$stored = Post::selectFirst(['id'], ['uid' => 42, 'body' => 'This must never be stored']);
		self::assertFalse(DBA::isResult($stored));
	}

	public function testItemPostDispatchesToInsertWhenNoPostIdGiven(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with(self::stringStartsWith('Location: https://friendica.local/network'));

		$_REQUEST = ['body' => 'Posted via item_post() dispatch', 'return' => 'network'];

		try {
			item_post();
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		} finally {
			$_REQUEST = [];
		}

		$stored = Post::selectFirst(['gravity'], ['uid' => 42, 'body' => 'Posted via item_post() dispatch']);
		self::assertTrue(DBA::isResult($stored));
		self::assertEquals(Item::GRAVITY_PARENT, $stored['gravity']);
	}

	public function testItemPostDispatchesToEditWhenPostIdGiven(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		$_REQUEST = ['post_id' => 1, 'body' => 'Edited via item_post() dispatch', 'return' => 'network'];

		try {
			item_post();
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		} finally {
			$_REQUEST = [];
		}

		$post = Post::selectFirst(['body'], ['id' => 1]);
		self::assertSame('Edited via item_post() dispatch', $post['body']);
	}

	public function testItemPostDropitemsDelegationIsNotAutomaticallyTestable(): void
	{
		self::markTestIncomplete(
			'item_post() with $_REQUEST[\'dropitems\'] set delegates to item_drop(), which always '
			. 'ends in System::jsonExit({"success":1}) - there is no return_path branch to catch, '
			. 'unlike the other jsonExit gaps in this class. Verify manually: posting dropitems=<id> '
			. 'deletes the item(s) and returns {"success":1} as JSON.',
		);
	}

	public function testItemEditPostNotFoundWithoutReturnPathThrowsNotFound(): void
	{
		$this->expectException(HTTPException\NotFoundException::class);

		item_edit(42, ['post_id' => 999999], false, '');
	}

	public function testItemEditPostNotFoundWithReturnPathRedirectsAndAddsNotice(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		try {
			item_edit(42, ['post_id' => 999999], false, 'network');
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}

		self::assertContains('Unable to locate original post.', DI::sysmsg()->getNotices());
	}

	public function testItemEditUpdatesExistingPostAndRedirects(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		$request = [
			'post_id' => 1,
			'body'    => 'Edited body content',
			'title'   => 'Edited title',
		];

		try {
			item_edit(42, $request, false, 'network');
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}

		$post = Post::selectFirst(['body', 'title'], ['id' => 1]);
		self::assertSame('Edited body content', $post['body']);
		self::assertSame('Edited title', $post['title']);
	}

	public function testItemEditKeepsSensitiveStateWhenRequestHasNoSensitiveField(): void
	{
		Post::update(['sensitive' => true], ['id' => 1]);

		$header = $this->mockRedirectHeader();
		$header->expects(self::once());

		try {
			item_edit(42, ['post_id' => 1, 'body' => 'Edited sensitive post'], false, 'network');
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}

		$post = Post::selectFirst(['sensitive'], ['id' => 1]);
		self::assertTrue((bool) $post['sensitive']);
	}

	public function testItemEditWithoutReturnPathThrowsOkException(): void
	{
		$this->expectException(HTTPException\OKException::class);

		item_edit(42, ['post_id' => 1, 'body' => 'Another edit'], false, '');
	}

	public function testItemInsertCommentOnMissingParentWithoutReturnPathThrowsNotFound(): void
	{
		$this->expectException(HTTPException\NotFoundException::class);

		item_insert(42, ['parent' => 999999, 'body' => 'a comment'], false, '');
	}

	public function testItemInsertCommentOnMissingParentWithReturnPathRedirects(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		try {
			item_insert(42, ['parent' => 999999, 'body' => 'a comment'], false, 'network');
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}

		self::assertContains('Unable to locate original post.', DI::sysmsg()->getNotices());
	}

	public function testItemInsertTopLevelPostIsStoredAndRedirects(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with(self::stringStartsWith('Location: https://friendica.local/network'));

		try {
			item_insert(42, ['body' => 'A brand new top level post', 'return' => 'network'], false, 'network');
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}

		$stored = Post::selectFirst(['id', 'body', 'uid', 'gravity', 'parent'], [
			'uid'  => 42,
			'body' => 'A brand new top level post',
		]);
		self::assertTrue(DBA::isResult($stored));
		self::assertEquals(Item::GRAVITY_PARENT, $stored['gravity']);
		// A top-level post's `parent` column self-references its own id (not 0/null).
		self::assertSame($stored['id'], $stored['parent']);
	}

	/**
	 * Note: like the top-level post case above, this goes through Item::insert()'s synchronous ActivityPub\Transmitter/APContact resolution - can be noticeably slower than other tests here.
	 */
	public function testItemInsertCommentIsStoredWithParentThreading(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once());

		try {
			item_insert(42, ['parent' => 3, 'body' => 'A reply to post 3'], false, 'network');
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}

		$parent = Post::selectFirst(['id'], ['id' => 3]);

		$stored = Post::selectFirst(['body', 'gravity', 'parent'], [
			'uid'  => 42,
			'body' => 'A reply to post 3',
		]);
		self::assertTrue(DBA::isResult($stored));
		self::assertEquals(Item::GRAVITY_COMMENT, $stored['gravity']);
		self::assertSame($parent['id'], $stored['parent']);
	}

	public function testItemInsertCommentOnSensitivePostIsSensitive(): void
	{
		Post::update(['sensitive' => true], ['id' => 3]);

		$header = $this->mockRedirectHeader();
		$header->expects(self::once());

		try {
			item_insert(42, ['parent' => 3, 'body' => 'A reply to a sensitive post'], false, 'network');
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}

		$stored = Post::selectFirst(['sensitive'], ['uid' => 42, 'body' => 'A reply to a sensitive post']);
		self::assertTrue(DBA::isResult($stored));
		self::assertTrue((bool) $stored['sensitive']);
	}

	public function testItemInsertCommentOnNonSensitivePostIsNotSensitive(): void
	{
		Post::update(['sensitive' => false], ['id' => 3]);

		$header = $this->mockRedirectHeader();
		$header->expects(self::once());

		try {
			item_insert(42, ['parent' => 3, 'body' => 'A reply to a normal post'], false, 'network');
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}

		$stored = Post::selectFirst(['sensitive'], ['uid' => 42, 'body' => 'A reply to a normal post']);
		self::assertTrue(DBA::isResult($stored));
		self::assertFalse((bool) $stored['sensitive']);
	}

	public function testItemInsertWithFutureScheduledAtQueuesDelayedPostInsteadOfInserting(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		$request = [
			'body'         => 'A post scheduled for the future',
			'scheduled_at' => '2099-01-01 00:00:00',
			'return'       => 'network',
		];

		try {
			item_insert(42, $request, false, 'network');
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}

		$stored = Post::selectFirst(['id'], ['uid' => 42, 'body' => 'A post scheduled for the future']);
		self::assertFalse(DBA::isResult($stored), 'The post must not be inserted immediately when scheduled for the future.');

		$delayed = DBA::selectFirst('delayed-post', ['uid'], ['uid' => 42]);
		self::assertTrue(DBA::isResult($delayed), 'A delayed-post row must be queued instead.');
	}

	public function testItemInsertCommentingOnPublicPostStoresItForCurrentUser(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once());

		try {
			item_insert(42, ['parent' => 7, 'body' => 'A reply to the public copy'], false, 'network');
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}

		$stored = Post::selectFirst(['gravity', 'parent'], ['uid' => 42, 'body' => 'A reply to the public copy']);
		self::assertTrue(DBA::isResult($stored));
		self::assertEquals(Item::GRAVITY_COMMENT, $stored['gravity']);
	}

	public function testItemProcessEmptyBodyWithoutReturnPathThrowsBadRequest(): void
	{
		$this->expectException(HTTPException\BadRequestException::class);

		item_process(['uid' => 42, 'gravity' => Item::GRAVITY_PARENT, 'network' => Protocol::DFRN], ['body' => ''], false, '');
	}

	public function testItemProcessEmptyBodyWithReturnPathRedirectsAndAddsNotice(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		try {
			item_process(['uid' => 42, 'gravity' => Item::GRAVITY_PARENT, 'network' => Protocol::DFRN], ['body' => ''], false, 'network');
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}

		self::assertContains('Empty post discarded.', DI::sysmsg()->getNotices());
	}

	/**
	 * A post cancelled by an addon (via the `$post['cancel']` field set through the
	 * INSERT_POST_LOCAL ArrayFilterEvent hook data) redirects when a return_path is given -
	 * unlike the no-return_path case, which falls through to the System::jsonExit() gap.
	 */
	public function testItemProcessCancelledPostWithReturnPathRedirectsWithoutStoring(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		$post = ['uid' => 42, 'gravity' => Item::GRAVITY_PARENT, 'network' => Protocol::DFRN, 'cancel' => true];

		try {
			item_process($post, ['body' => 'This should be cancelled'], false, 'network');
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}

		$stored = Post::selectFirst(['id'], ['uid' => 42, 'body' => 'This should be cancelled']);
		self::assertFalse(DBA::isResult($stored));
	}

	/**
	 * Known gap: preview mode always terminates via System::jsonExit(), which cannot be intercepted from PHPUnit. Needs manual/browser verification instead.
	 */
	public function testItemProcessPreviewModeIsNotAutomaticallyTestable(): void
	{
		self::markTestIncomplete(
			'item_process() in preview mode always calls System::jsonExit(), a raw exit() call '
			. 'that cannot be intercepted by PHPUnit. Verify manually: posting with preview=1 returns '
			. '{"preview": "<rendered html>"} as JSON.',
		);
	}

	public function testDropItemNotFoundRedirectsToNetworkWithNotice(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		try {
			drop_item(999999);
			self::fail('Expected a redirect (FoundException)');
			/** @phpstan-ignore catch.neverThrown (PHPStan can't trace the throw through DI::baseUrl()->redirect() -> System::externalRedirect(); empirically verified by this passing test) */
		} catch (HTTPException\FoundException) {
			// expected
		}

		/** @phpstan-ignore deadCode.unreachable (see the catch.neverThrown suppression above) */
		self::assertContains('Item not found.', DI::sysmsg()->getNotices());
	}

	/**
	 * Finding: drop_item()'s `if ($item['deleted']) { return ''; }` branch (mod/item.php) is
	 * currently dead code. Post::selectFirstForUser() adds `NOT deleted` to every query against
	 * `post-user-view` - so a deleted item is never found in the first place, and drop_item() takes
	 * the "item not found" path instead of the early-return-empty-string path. Characterizing the
	 * *actual* current behavior here rather than the behavior the dead code suggests.
	 */
	public function testDropItemAlreadyDeletedIsTreatedAsNotFound(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		self::assertTrue(DBA::update('post-user', ['deleted' => 1], ['id' => 2]));

		try {
			drop_item(2);
			self::fail('Expected a redirect (FoundException)');
			/** @phpstan-ignore catch.neverThrown (PHPStan can't trace the throw through DI::baseUrl()->redirect() -> System::externalRedirect(); empirically verified by this passing test) */
		} catch (HTTPException\FoundException) {
			// expected
		}

		/** @phpstan-ignore deadCode.unreachable (see the catch.neverThrown suppression above) */
		self::assertContains('Item not found.', DI::sysmsg()->getNotices());
	}

	/**
	 * Dropping a single comment must not touch the rest of the thread: only that comment disappears,
	 * the parent (post-user id 1) and its sibling comments (ids 4 and 5) stay readable.
	 */
	public function testDropItemOwnerDeletesCommentAndRedirectsToParentDisplay(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/display/1');

		self::assertTrue($this->isVisibleForUser(2), 'The comment must exist before the drop.');

		try {
			drop_item(2);
			self::fail('Expected a redirect (FoundException)');
			/** @phpstan-ignore catch.neverThrown (PHPStan can't trace the throw through DI::baseUrl()->redirect() -> System::externalRedirect(); empirically verified by this passing test) */
		} catch (HTTPException\FoundException) {
			// expected
		}

		/** @phpstan-ignore deadCode.unreachable (see the catch.neverThrown suppression above) */
		$item = DBA::selectFirst('post-user', ['deleted', 'hidden'], ['id' => 2]);
		self::assertEquals(1, $item['hidden']);
		self::assertEquals(1, $item['deleted']);

		// The comment is gone from the user's point of view ...
		self::assertFalse($this->isVisibleForUser(2), 'The dropped comment must be gone.');

		// ... while the rest of the thread is untouched.
		self::assertTrue($this->isVisibleForUser(1), 'The parent must survive dropping one of its comments.');
		self::assertTrue($this->isVisibleForUser(4), 'A sibling comment must survive dropping another comment.');
		self::assertTrue($this->isVisibleForUser(5), 'A sibling comment must survive dropping another comment.');
	}

	/**
	 * Dropping a top-level post takes the whole thread with it: Item::markForDeletionById() ends in a
	 * markForDeletion() call over the parent's children, so the comments must disappear along with it.
	 */
	public function testDropItemDeletesTopLevelPostWithItsCommentsAndRedirectsToNetwork(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		// Fixture thread: post-user id 1 is the parent of the comments 2, 4 and 5 (all uid 42).
		$thread = [1, 2, 4, 5];

		foreach ($thread as $id) {
			self::assertTrue($this->isVisibleForUser($id), sprintf('Item %d must exist before the drop.', $id));
		}

		try {
			drop_item(1);
			self::fail('Expected a redirect (FoundException)');
			/** @phpstan-ignore catch.neverThrown (PHPStan can't trace the throw through DI::baseUrl()->redirect() -> System::externalRedirect(); empirically verified by this passing test) */
		} catch (HTTPException\FoundException) {
			// expected
		}

		/** @phpstan-ignore deadCode.unreachable (see the catch.neverThrown suppression above) */
		foreach ($thread as $id) {
			$item = DBA::selectFirst('post-user', ['deleted'], ['id' => $id]);
			self::assertEquals(1, $item['deleted'], sprintf('Item %d must be marked as deleted.', $id));

			// Neither the parent nor any of its comments are readable anymore.
			self::assertFalse($this->isVisibleForUser($id), sprintf('Item %d must be gone after dropping the parent.', $id));
		}
	}

	/**
	 * Post-user id 7 is the uid-0 (public/global) copy of uri-id 1, owned by no local user. Logged
	 * in as uid 42 - who neither owns it nor has a matching remote-contact relation to it - drop_item()
	 * takes the permission-denied branch instead of deleting: notice added, redirect to the item's
	 * own display page (guid "1", shared with post-user id 1 since both reference uri-id 1), and
	 * critically the item is left untouched.
	 */
	public function testDropItemPermissionDeniedLeavesItemUntouched(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/display/1');

		try {
			drop_item(7);
			self::fail('Expected a redirect (FoundException)');
			/** @phpstan-ignore catch.neverThrown (PHPStan can't trace the throw through DI::baseUrl()->redirect() -> System::externalRedirect(); empirically verified by this passing test) */
		} catch (HTTPException\FoundException) {
			// expected
		}

		/** @phpstan-ignore deadCode.unreachable (see the catch.neverThrown suppression above) */
		self::assertContains('Permission denied.', DI::sysmsg()->getNotices());

		$item = DBA::selectFirst('post-user', ['deleted'], ['id' => 7]);
		self::assertEquals(0, $item['deleted']);
	}

	public function testItemRedirectAfterActionForCommentWithUnresolvableParentGoesToNetwork(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		try {
			item_redirect_after_action(['gravity' => Item::GRAVITY_COMMENT, 'parent' => 999999], '');
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}
	}

	public function testItemRedirectAfterActionForTopLevelPostGoesToNetworkOnEmptyReturn(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		try {
			item_redirect_after_action(['gravity' => Item::GRAVITY_PARENT], '');
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}
	}

	public function testItemRedirectAfterActionForTopLevelPostHonoursGivenReturnUrl(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/profile/selfcontact');

		try {
			item_redirect_after_action(['gravity' => Item::GRAVITY_PARENT], bin2hex('profile/selfcontact'));
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}
	}

	public function testItemRedirectAfterActionStripsAjaxUpdatePrefixFromReturnUrl(): void
	{
		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		try {
			// "update_network" is what the ajax auto-refresh sends back; "update_" must be stripped
			// and, since it contains no "display", it falls through to the network default.
			item_redirect_after_action(['gravity' => Item::GRAVITY_PARENT], bin2hex('update_network'));
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}
	}

	public function testItemContentRequiresAuthentication(): void
	{
		DI::session()->set('authenticated', false);
		try {
			$this->expectException(HTTPException\UnauthorizedException::class);

			item_content();
		} finally {
			DI::session()->set('authenticated', true);
		}
	}

	public function testItemContentRequiresAtLeastTwoArguments(): void
	{
		$this->setArgs('item');

		$this->expectException(HTTPException\BadRequestException::class);

		item_content();
	}

	public function testItemContentBlockUnknownItemThrowsNotFound(): void
	{
		$this->setArgs('item/block/999999');

		$this->expectException(HTTPException\NotFoundException::class);

		item_content();
	}

	public function testItemContentBlockNonAjaxRedirectsAfterAction(): void
	{
		$this->setArgs('item/block/1');

		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		try {
			item_content();
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}
	}

	public function testItemContentIgnoreNonAjaxRedirectsAfterAction(): void
	{
		$this->setArgs('item/ignore/1');

		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		try {
			item_content();
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}
	}

	public function testItemContentCollapseNonAjaxRedirectsAfterAction(): void
	{
		$this->setArgs('item/collapse/1');

		$header = $this->mockRedirectHeader();
		$header->expects(self::once())->with('Location: https://friendica.local/network');

		try {
			item_content();
			self::fail('Expected a redirect (FoundException)');
		} catch (HTTPException\FoundException) {
			// expected
		}
	}

	/**
	 * Known gap: the "drop" ajax branch of item_content() calls System::jsonExit(), which cannot be intercepted by PHPUnit.
	 *
	 * Needs manual/browser verification instead.
	 */
	public function testItemContentAjaxBranchesAreNotAutomaticallyTestable(): void
	{
		self::markTestIncomplete(
			'item_content() ajax branches (drop/block/ignore/collapse with DI::mode()->isAjax()) '
			. 'call System::jsonExit(), a raw exit() call that cannot be intercepted by PHPUnit. '
			. 'Verify manually: ajax drop/block/ignore/collapse return a JSON array [item id, owner id].',
		);
	}
}
