# Mastodon API

* [Home](help)
  * [Using the APIs](help/api)

## Overview

Friendica provides the following endpoints defined in [the official Mastodon API reference](https://docs.joinmastodon.org/api/).

Authentication is the same as described in [Using the APIs](help/api#Authentication).

## Clients

### Supported apps

For supported apps please have a look at the [FAQ](help/FAQ#clients)

### Unsupported apps

#### Android

- [Fedilab](https://framagit.org/tom79/fedilab) Automatically uses the legacy API, see issue: https://framagit.org/tom79/fedilab/-/issues/520
- [Mammut](https://github.com/jamiesanson/Mammut) There are problems with the token request, see issue https://github.com/jamiesanson/Mammut/issues/19

#### iOS

- [Mast](https://github.com/Beesitech/Mast) Doesn't accept the entered instance name. Claims that it is invalid (Message is: "Not a valid instance (may be closed or dead)")
- [Toot!](https://apps.apple.com/app/toot/id1229021451)

## Entities

These endpoints use the [Mastodon API entities](https://docs.joinmastodon.org/entities/).

## Implemented endpoints

- [`GET /api/v1/accounts/:id`](https://docs.joinmastodon.org/methods/accounts/#retrieve-information)
- [`POST /api/v1/accounts/:id/block`](https://docs.joinmastodon.org/methods/accounts/)
- [`POST /api/v1/accounts/:id/follow`](https://docs.joinmastodon.org/methods/accounts/)
- [`GET /api/v1/accounts/:id/followers`](https://docs.joinmastodon.org/methods/accounts/)
- [`GET /api/v1/accounts/:id/following`](https://docs.joinmastodon.org/methods/accounts/)
- [`GET /api/v1/accounts/:id/lists`](https://docs.joinmastodon.org/methods/accounts/)
- [`POST /api/v1/accounts/:id/mute`](https://docs.joinmastodon.org/methods/accounts/)
- [`POST /api/v1/accounts/:id/note`](https://docs.joinmastodon.org/methods/accounts/)
- [`GET /api/v1/accounts/:id/statuses`](https://docs.joinmastodon.org/methods/accounts/)
- [`POST /api/v1/accounts/:id/unfollow`](https://docs.joinmastodon.org/methods/accounts/)
- [`POST /api/v1/accounts/:id/unblock`](https://docs.joinmastodon.org/methods/accounts/)
- [`POST /api/v1/accounts/:id/unmute`](https://docs.joinmastodon.org/methods/accounts/)
- [`GET /api/v1/accounts/relationships`](https://docs.joinmastodon.org/methods/accounts/)
- [`GET /api/v1/accounts/search`](https://docs.joinmastodon.org/methods/accounts)
- [`PATCH /api/v1/accounts/update_credentials`](https://docs.joinmastodon.org/methods/accounts/#update_credentials)
- [`GET /api/v1/accounts/verify_credentials`](https://docs.joinmastodon.org/methods/accounts)
- [`POST /api/v1/apps`](https://docs.joinmastodon.org/methods/apps/)
- [`GET /api/v1/apps/verify_credentials`](https://docs.joinmastodon.org/methods/apps/)
- [`GET /api/v1/blocks`](https://docs.joinmastodon.org/methods/accounts/blocks/)
- [`GET /api/v1/bookmarks`](https://docs.joinmastodon.org/methods/accounts/bookmarks/)
- [`GET /api/v1/conversations`](https://docs.joinmastodon.org/methods/timelines/conversations/)
- [`DELETE /api/v1/conversations/:id`](https://docs.joinmastodon.org/methods/timelines/conversations/)
- [`POST /api/v1/conversations/:id/read`](https://docs.joinmastodon.org/methods/timelines/conversations/)
- [`GET /api/v1/custom_emojis`](https://docs.joinmastodon.org/methods/instance/custom_emojis/)
    - Doesn't return unicode emojis since they aren't using an image URL


- [`GET /api/v1/directory`](https://docs.joinmastodon.org/methods/instance/directory/)
- [`GET /api/v1/favourites`](https://docs.joinmastodon.org/methods/accounts/favourites/)
- [`GET /api/v1/follow_requests`](https://docs.joinmastodon.org/methods/accounts/follow_requests#pending-follows)
    - Returned IDs are specific to follow requests
- [`POST /api/v1/follow_requests/:id/authorize`](https://docs.joinmastodon.org/methods/accounts/follow_requests#accept-follow)
    - `:id` is a follow request ID, not a regular account id
- [`POST /api/v1/follow_requests/:id/reject`](https://docs.joinmastodon.org/methods/accounts/follow_requests#reject-follow)
    - `:id` is a follow request ID, not a regular account id
- `POST /api/v1/follow_requests/:id/ignore`
    - Friendica-specific, hides the follow request from the list and prevents the remote contact from retrying.
    - `:id` is a follow request ID, not a regular account id
    - Returns a [Relationship](https://docs.joinmastodon.org/entities/relationship) object.

- [`GET /api/v1/followed_tags'](https://docs.joinmastodon.org/methods/followed_tags/)
- [`GET /api/v1/instance`](https://docs.joinmastodon.org/methods/instance#fetch-instance)
- `GET /api/v1/instance/rules` Undocumented, returns Terms of Service
- [`GET /api/v1/instance/peers`](https://docs.joinmastodon.org/methods/instance#list-of-connected-domains)
- [`GET /api/v1/lists`](https://docs.joinmastodon.org/methods/timelines/lists/)
- [`POST /api/v1/lists`](https://docs.joinmastodon.org/methods/timelines/lists/)
- [`GET /api/v1/lists/:id`](https://docs.joinmastodon.org/methods/timelines/lists/)
- [`PUT /api/v1/lists/:id`](https://docs.joinmastodon.org/methods/timelines/lists/)
- [`DELETE /api/v1/lists/:id`](https://docs.joinmastodon.org/methods/timelines/lists/)
- [`GET /api/v1/lists/:id/accounts`](https://docs.joinmastodon.org/methods/timelines/lists/)
- [`POST /api/v1/lists/:id/accounts`](https://docs.joinmastodon.org/methods/timelines/lists/)
- [`DELETE /api/v1/lists/:id/accounts`](https://docs.joinmastodon.org/methods/timelines/lists/)
- [`POST /api/v1/markers`](https://docs.joinmastodon.org/methods/timelines/markers/)
- [`GET /api/v1/markers`](https://docs.joinmastodon.org/methods/timelines/markers/)
- [`POST /api/v1/media`](https://docs.joinmastodon.org/methods/statuses/media/)
- [`GET /api/v1/media/:id`](https://docs.joinmastodon.org/methods/statuses/media/)
- [`PUT /api/v1/media/:id`](https://docs.joinmastodon.org/methods/statuses/media/)
- [`GET /api/v1/mutes`](https://docs.joinmastodon.org/methods/accounts/mutes/)
- [`GET /api/v1/notifications`](https://docs.joinmastodon.org/methods/notifications/)
- [`GET /api/v1/notifications/:id`](https://docs.joinmastodon.org/methods/notifications/)
- [`POST /api/v1/notifications/clear`](https://docs.joinmastodon.org/methods/notifications/)
- [`POST /api/v1/notifications/:id/dismiss`](https://docs.joinmastodon.org/methods/notifications/)
- [`GET /api/v1/polls/:id`](https://docs.joinmastodon.org/methods/statuses/polls/)
- [`GET /api/v1/preferences`](https://docs.joinmastodon.org/methods/accounts/preferences/)
- [`DELETE /api/v1/push/subscription`](https://docs.joinmastodon.org/methods/notifications/push/)
- [`GET /api/v1/push/subscription`](https://docs.joinmastodon.org/methods/notifications/push/)
- [`PUSH /api/v1/push/subscription`](https://docs.joinmastodon.org/methods/notifications/push/)
- [`PUT /api/v1/push/subscription`](https://docs.joinmastodon.org/methods/notifications/push/)
- [`POST /api/v1/reports`](https://docs.joinmastodon.org/methods/accounts/reports/)
- [`GET /api/v1/scheduled_statuses`](https://docs.joinmastodon.org/methods/statuses/scheduled_statuses/)
- [`DELETE /api/v1/scheduled_statuses/:id`](https://docs.joinmastodon.org/methods/statuses/scheduled_statuses/)
- [`GET /api/v1/scheduled_statuses/:id`](https://docs.joinmastodon.org/methods/statuses/scheduled_statuses/)
- [`GET /api/v1/search`](https://docs.joinmastodon.org/methods/search/)
- [`POST /api/v1/statuses`](https://docs.joinmastodon.org/methods/statuses/#create)
    - Additionally to the static values `public`, `unlisted` and `private`, the `visibility` parameter can contain a numeric value with a group id.
- [`GET /api/v1/statuses/:id`](https://docs.joinmastodon.org/methods/statuses/#get)
- [`DELETE /api/v1/statuses/:id`](https://docs.joinmastodon.org/methods/statuses/#delete)
- [`GET /api/v1/statuses/:id/context`](https://docs.joinmastodon.org/methods/statuses/#context)
- [`GET /api/v1/statuses/:id/reblogged_by`](https://docs.joinmastodon.org/methods/statuses/#reblogged_by)
- [`GET /api/v1/statuses/:id/favourited_by`](https://docs.joinmastodon.org/methods/statuses/#favourited_by)
- [`POST /api/v1/statuses/:id/favourite`](https://docs.joinmastodon.org/methods/statuses/#favourite)
- [`POST /api/v1/statuses/:id/unfavourite`](https://docs.joinmastodon.org/methods/statuses/#unfavourite)
- [`POST /api/v1/statuses/:id/reblog`](https://docs.joinmastodon.org/methods/statuses/#boost)
- [`POST /api/v1/statuses/:id/unreblog`](https://docs.joinmastodon.org/methods/statuses/#unreblog)
- [`POST /api/v1/statuses/:id/bookmark`](https://docs.joinmastodon.org/methods/statuses/#bookmark)
- [`POST /api/v1/statuses/:id/unbookmark`](https://docs.joinmastodon.org/methods/statuses/#unbookmark)
- [`POST /api/v1/statuses/:id/mute`](https://docs.joinmastodon.org/methods/statuses/#mute)
- [`POST /api/v1/statuses/:id/unmute`](https://docs.joinmastodon.org/methods/statuses/#unmute)
- [`POST /api/v1/statuses/:id/pin`](https://docs.joinmastodon.org/methods/statuses/#pin)
- [`POST /api/v1/statuses/:id/unpin`](https://docs.joinmastodon.org/methods/statuses/#unpin)
- [`POST /api/v1/statuses/:id`](https://docs.joinmastodon.org/methods/statuses/#edit)
- [`GET /api/v1/statuses/:id/source`](https://docs.joinmastodon.org/methods/statuses/#source)
- [`GET /api/v1/statuses/:id/card`](https://docs.joinmastodon.org/methods/statuses/#card)
- [`GET /api/v1/suggestions`](https://docs.joinmastodon.org/methods/accounts/suggestions/)
- [`GET /api/v1/tags/:id`](https://docs.joinmastodon.org/methods/tags/#get)
- [`GET /api/v1/tags/:id/follow`](https://docs.joinmastodon.org/methods/tags/#follow)
- [`GET /api/v1/tags/:id/unfollow`](https://docs.joinmastodon.org/methods/tags/#unfollow)
- [`GET /api/v1/timelines/direct`](https://docs.joinmastodon.org/methods/timelines/)
- [`GET /api/v1/timelines/home`](https://docs.joinmastodon.org/methods/timelines/)
- [`GET /api/v1/timelines/list/:id`](https://docs.joinmastodon.org/methods/timelines/)
- [`GET /api/v1/timelines/public`](https://docs.joinmastodon.org/methods/timelines/)
- [`GET /api/v1/timelines/tag/:hashtag`](https://docs.joinmastodon.org/methods/timelines/)
- [`GET /api/v1/trends`](https://docs.joinmastodon.org/methods/instance/trends/)
- [`GET /api/v1/trends/links`](https://github.com/mastodon/mastodon/pull/16917)
- [`GET /api/v1/trends/statuses`](https://docs.joinmastodon.org/methods/trends/#statuses)
- [`GET /api/v1/trends/tags`](https://docs.joinmastodon.org/methods/trends/#tags)
- [`GET /api/v2/search`](https://docs.joinmastodon.org/methods/search/)


## Currently unimplemented endpoints

These emdpoints are planned to be implemented somewhere in the future.

- [`POST /api/v1/accounts/:id/remove_from_followers`](https://github.com/mastodon/mastodon/pull/16864)
- [`GET /api/v1/accounts/familiar_followers`](https://github.com/mastodon/mastodon/pull/17700)
- [`GET /api/v1/accounts/lookup`](https://github.com/mastodon/mastodon/pull/15740)
- [`POST /api/v1/polls/:id/votes`](https://docs.joinmastodon.org/methods/statuses/polls/)
- [`GET /api/v1/featured_tags`](https://docs.joinmastodon.org/methods/accounts/featured_tags/)
- [`POST /api/v1/featured_tags`](https://docs.joinmastodon.org/methods/accounts/featured_tags/)
- [`DELETE /api/v1/featured_tags/:id`](https://docs.joinmastodon.org/methods/accounts/featured_tags/)

## Dummy endpoints

These endpoints are returning empty data to avoid error messages when using third party clients.
They refer to features that don't exist in Friendica yet.

- [`GET /api/v1/accounts/:id/identity_proofs`](https://docs.joinmastodon.org/methods/accounts/)
- [`GET /api/v1/announcements`](https://docs.joinmastodon.org/methods/announcements/)
- [`GET /api/v1/endorsements`](https://docs.joinmastodon.org/methods/accounts/endorsements/)
- [`GET /api/v1/filters`](https://docs.joinmastodon.org/methods/accounts/filters/)

## Non supportable endpoints

These endpoints won't be implemented at the moment.
They refer to features or data that don't exist in Friendica yet.

- `POST /api/meta` Misskey API endpoint.
- [`POST /api/v1/accounts`](https://docs.joinmastodon.org/methods/accounts/)
- [`GET /api/v1/accounts/:id/featured_tags`](https://docs.joinmastodon.org/methods/accounts/)
- [`POST /api/v1/accounts/:id/pin`](https://docs.joinmastodon.org/methods/accounts/)
- [`POST /api/v1/accounts/:id/unpin`](https://docs.joinmastodon.org/methods/accounts/)
- [`GET /api/v1/admin/accounts`](https://docs.joinmastodon.org/methods/admin/)
- [`GET /api/v1/admin/accounts/:id`](https://docs.joinmastodon.org/methods/admin/)
- [`POST /api/v1/admin/accounts/:id/{action}`](https://docs.joinmastodon.org/methods/admin/)
- [`GET /api/v1/admin/reports`](https://docs.joinmastodon.org/methods/admin/)
- [`GET /api/v1/admin/reports/:id`](https://docs.joinmastodon.org/methods/admin/)
- [`POST /api/v1/admin/reports/:id/{action}`](https://docs.joinmastodon.org/methods/admin/)
- [`POST /api/v1/announcements/:id/dismiss`](https://docs.joinmastodon.org/methods/announcements/)
- [`PUT /api/v1/announcements/:id/reactions/{name}`](https://docs.joinmastodon.org/methods/announcements/)
- [`DELETE /api/v1/announcements/:id/reactions/{name}`](https://docs.joinmastodon.org/methods/announcements/)
- [`GET /api/v1/domain_blocks`](https://docs.joinmastodon.org/methods/accounts/domain_blocks/)
- [`POST /api/v1/domain_blocks`](https://docs.joinmastodon.org/methods/accounts/domain_blocks/)
- [`DELETE /api/v1/domain_blocks`](https://docs.joinmastodon.org/methods/accounts/domain_blocks/)
- [`DELETE /api/v1/emails/confirmations`](https://github.com/mastodon/mastodon/pull/15816)
- [`GET /api/v1/featured_tags/suggestions`](https://docs.joinmastodon.org/methods/accounts/featured_tags/)
- [`GET /api/v1/filters/:id`](https://docs.joinmastodon.org/methods/accounts/filters/)
- [`POST /api/v1/filters/:id`](https://docs.joinmastodon.org/methods/accounts/filters/)
- [`PUT /api/v1/filters/:id`](https://docs.joinmastodon.org/methods/accounts/filters/)
- [`DELETE /api/v1/filters/:id`](https://docs.joinmastodon.org/methods/accounts/filters/)
- [`GET /api/v1/instance/activity`](https://docs.joinmastodon.org/methods/instance#weekly-activity)
- [`POST /api/v1/markers`](https://docs.joinmastodon.org/methods/timelines/markers/)
- [`PUT /api/v1/scheduled_statuses/:id`](https://docs.joinmastodon.org/methods/statuses/scheduled_statuses/)
- [`GET /api/v1/statuses/{id:\d+}/history`](https://github.com/mastodon/mastodon/pull/16697)
- [`GET /api/v1/streaming`](https://docs.joinmastodon.org/methods/timelines/streaming/)
- [`DELETE /api/v1/suggestions/:id`](https://docs.joinmastodon.org/methods/accounts/suggestions/)
