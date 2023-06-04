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
With some additional extensions listed below.

### Instance (Version 2) Entities
Extensions to the [Mastodon Instance::V2 Entities](https://docs.joinmastodon.org/entities/Instance/)
* `friendica`: Friendica specific properties of the V2 Instance including:
    * `version`: The Friendica version string
    * `codename`: The Friendica version code name
    * `db_version`: The database schema version number

Example:
```json
{
  "domain": "friendicadevtest1.myportal.social",
  "title": "Friendica Social Network",
  "version": "2.8.0 (compatible; Friendica 2023.03-dev)",
  ...
  "friendica": {
    "version": "2023.03-dev",
    "codename": "Giant Rhubarb",
    "db_version": 1516
  }
}
```

### Notification Entities
Extensions to the [Mastodon Notification Entities](https://docs.joinmastodon.org/entities/Notification/)
* `dismissed`: whether the object has been dismissed or not

### Status Entities
Extensions to the [Mastodon Status Entities](https://docs.joinmastodon.org/entities/Status/)
* `in_reply_to_status`: A fully populated Mastodon Status entity for the replied to status or null it is a post rather than a response
* `friendica`: Friendica specific properties of a status including:
  * `title`: The Friendica title for a post, or empty if the status is a comment
  * `delivery_data`: Information about the state of federating a message from the server
    * `delivery_queue_count`: Total number of remote servers that the status needs to be federated to.
    * `delivery_queue_done`: Total number of remote servers that have successfully been federated to so far.
    * `delivery_queue_failed`: Total number of remote servers that have we failed to federate to so far.
  * `dislikes_count`: The number of dislikes that a status has accumulated according to the server.
  * `disliked`: Whether the API user disliked the status.

Example:
```json
{
  "id": "358",
  "created_at": "2023-02-23T02:45:46.000Z",
  "in_reply_to_id": "356",
  "in_reply_to_status": {
    "id": "356",
    "created_at": "2023-02-23T02:45:35.000Z",
    "in_reply_to_id": null,
    "in_reply_to_status": null,
    "in_reply_to_account_id": null,
    ...
    "content": "A post from testuser1",
    ...
    "account": {
      "id": "6",
      "username": "testuser1",
      "acct": "testuser1",
      "display_name": "testuser1",
      ...
    },
    ...
    "friendica": {
      "title": "",
      "dislikes_count": 0
    }
  },
  "in_reply_to_account_id": "6",
  ...
  "replies_count": 0,
  "reblogs_count": 0,
  "favourites_count": 0,
  ...
  "content": "A reply from testuser2",
  ...
  "account": {
    "id": "8",
    "username": "testuser2",
    "acct": "testuser2",
    "display_name": "testuser2",
    ...
  },
  ...
  "friendica": {
    "title": "",
    "delivery_data": {
      "delivery_queue_count": 10,
      "delivery_queue_done": 3,
      "delivery_queue_failed": 0
    },
    "dislikes_count": 0
  }
}
```

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

- [`GET /api/v1/followed_tags`](https://docs.joinmastodon.org/methods/followed_tags/)
- [`GET /api/v1/instance`](https://docs.joinmastodon.org/methods/instance/#v1)
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
    - Additional field `include_all` to return read and unread statuses, defaults to `false`
    - Additional field `summary` returns a count of all of the statuses that match the type filter
    - Additional field `with_muted` Pleroma extension to return notifications from muted users, defaults to `false`
    - Does not support the `type` field, which is the mirror image of the supported `exclude_types` field
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
- [`PUT /api/v1/statuses`](https://docs.joinmastodon.org/methods/statuses/#edit)
    - Does not support `polls` argument as Friendica does not have polls
    - Additional fields `friendica` for Friendica specific parameters:
        - `title`: Explicitly sets the title for a post status, ignored if used on a comment status. For post statuses the legacy behavior is to use any "spoiler text" as the title if it is provided. If both the title and spoiler text are provided for a post status then they will each be used for their respective roles. If no title is provided then the legacy behavior will persist. If you want to create a post with no title but spoiler text then explicitly set the title but set it to an empty string `""`.
- [`POST /api/v1/statuses`](https://docs.joinmastodon.org/methods/statuses/#create)
    - Does not support `polls` argument as Friendica does not have polls
    - Additionally to the static values `public`, `unlisted` and `private`, the `visibility` parameter can contain a numeric value with a circle id.
    - Additional field `quote_id` for the post that is being quote reshared
    - Additional fields `friendica` for Friendica specific parameters:
       - `title`: Explicitly sets the title for a post status, ignored if used on a comment status. For post statuses the legacy behavior is to use any "spoiler text" as the title if it is provided. If both the title and spoiler text are provided for a post status then they will each be used for their respective roles. If no title is provided then the legacy behavior will persist. If you want to create a post with no title but spoiler text then explicitly set the title but set it to an empty string `""`.
- [`GET /api/v1/statuses/:id`](https://docs.joinmastodon.org/methods/statuses/#get)
- [`DELETE /api/v1/statuses/:id`](https://docs.joinmastodon.org/methods/statuses/#delete)
- [`GET /api/v1/statuses/:id/context`](https://docs.joinmastodon.org/methods/statuses/#context)
    - Additional support for paging using `min_id`, `max_id`, `since_id` parameters
    - Additional support for previous/next Link Headers to support paging
    - Additional flag `show_all` to allow including posts from blocked and ignored/muted users, defaults to `false`
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
    - Additional field `with_muted` Pleroma extension to return notifications from muted users, defaults to `false`
    - Additional field `exclude_replies` to only return post statuses not replies/comments, defaults to `false`
- [`GET /api/v1/timelines/list/:id`](https://docs.joinmastodon.org/methods/timelines/)
    - Additional field `with_muted` Pleroma extension to return notifications from muted users, defaults to `false`
    - Additional field `exclude_replies` to only return post statuses not replies/comments, defaults to `false`
- [`GET /api/v1/timelines/public`](https://docs.joinmastodon.org/methods/timelines/)
    - Additional field `with_muted` Pleroma extension to return notifications from muted users, defaults to `false`
    - Additional field `exclude_replies` to only return post statuses not replies/comments, defaults to `false`
- [`GET /api/v1/timelines/tag/:hashtag`](https://docs.joinmastodon.org/methods/timelines/)
    - Additional field `with_muted` Pleroma extension to return notifications from muted users, defaults to `false`
    - Additional field `exclude_replies` to only return post statuses not replies/comments, defaults to `false`
    - Does not support the `any[]`, `all[]`, or `none[]` query parameters
- [`GET /api/v1/trends`](https://docs.joinmastodon.org/methods/instance/trends/)
- [`GET /api/v1/trends/links`](https://github.com/mastodon/mastodon/pull/16917)
- [`GET /api/v1/trends/statuses`](https://docs.joinmastodon.org/methods/trends/#statuses)
- [`GET /api/v1/trends/tags`](https://docs.joinmastodon.org/methods/trends/#tags)
    - Additional field `friendica_local` to return local trending tags instead of global tags, defaults to `false`
- [`GET /api/v2/instance`](https://docs.joinmastodon.org/methods/instance/#v2)
- [`GET /api/v2/search`](https://docs.joinmastodon.org/methods/search/)


## Currently unimplemented endpoints

These endpoints are planned to be implemented somewhere in the future.

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
