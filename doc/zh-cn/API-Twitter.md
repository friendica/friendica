# Twitter API

* [Home](help)
  * [Using the APIs](help/api)

## Overview

Friendica provides the following endpoints defined in the [official Twitter API reference](https://developer.twitter.com/en/docs/api-reference-index).

Authentication is the same as described in [Using the APIs](help/api#Authentication).

## Entities

These endpoints use the [Friendica API entities](help/API-Entities).

## Different behaviour

* `screen_name`: The nick name in Friendica is only unique in each network but not for all networks. The users are searched in the following priority: Friendica, StatusNet/GNU Social, Diaspora, pump.io, Twitter. If no contact was found by this way, then the first contact is taken.
* `include_entities`: Default is "false". If set to "true" then the plain text is formatted so that links are having descriptions.

## Friendica-specific return values

* `cid`: Contact id of the user (important for "contact_allow" and "contact_deny")
* `network`: network of the user

## Unsupported parameters

* `cursor`
* `trim_user`
* `contributor_details` 
* `place_id`
* `display_coordinates`
* `include_rts`: To-Do
* `include_my_retweet`: Retweets in Friendica are implemented in a different way

## Implemented endpoints

- [POST api/oauth/access_token](https://developer.twitter.com/en/docs/basics/authentication/api-reference/access_token)
    - Unsupported parameters:
        - `x_auth_password`
        - `x_auth_username`
        - `x_auth_mode`
- [POST api/oauth/request_token](https://developer.twitter.com/en/docs/basics/authentication/api-reference/request_token)
    - Unsupported parameter:
        - `x_auth_access_type`


- [GET api/account/verify_credentials](https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/get-account-verify_credentials)
    - Unsupported parameter:
        - `include_email`
- [POST api/account/update_profile](https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/post-account-update_profile)
    - Unsupported parameters:
        - `url`
        - `location`
        - `profile_link_color`
        - `include_entities`
        - `skip_status`
- [POST api/account/update_profile_image](https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/post-account-update_profile_image)
    - Additional parameter:
        - `profile_id` (optional):  Numerical id of the profile for which the image should be used, default is changing the default profile. 


- [POST api/statuses/update](https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-update)
    - Unsupported parameter:
        - `display_coordinates`
- [POST api/statuses/update_with_media (deprecated)](https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-update_with_media)


- [POST api/media/upload](https://developer.twitter.com/en/docs/media/upload-media/api-reference/post-media-upload)
    - Additional return value:
        - `image.friendica_preview_url`: image preview url
- [POST api/media/metadata/create](https://developer.twitter.com/en/docs/media/upload-media/api-reference/post-media-metadata-create)


- [GET api/users/show](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-users-show)
- [GET api/users/search](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-users-search)
    - Unsupported parameters:
        - `page`
        - `count`
        - `include_entities`
- [GET api/users/lookup](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-users-lookup)
    - Unsupported parameters:
        - `screen_name`
        - `include_entities`


- [GET api/search/tweets](https://developer.twitter.com/en/docs/tweets/search/api-reference/get-search-tweets)
    - Unsupported parameters:
        - `geocode`
        - `lang`
        - `locale`
        - `result_type`
        - `until`
        - `include_entities`


- [GET api/saved_searches/list](https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/get-saved_searches-list)


- [GET api/statuses/home_timeline](https://developer.twitter.com/en/docs/tweets/timelines/api-reference/get-statuses-home_timeline)
    - Alias: `GET api/statuses/friends_timeline`
- [GET api/statuses/user_timeline](https://developer.twitter.com/en/docs/tweets/timelines/api-reference/get-statuses-user_timeline)
- [GET api/statuses/mentions (deprecated)](https://developer.twitter.com/en/docs/tweets/timelines/api-reference/get-statuses-mentions_timeline)
- [GET api/statuses/show/:id](https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/get-statuses-show-id)
- [POST api/statuses/retweet/:id](https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-retweet-id)
- [POST api/statuses/destroy/:id](https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-destroy-id)
- [GET api/statuses/followers (deprecated)](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-followers-list)
    - Alias: `GET api/statuses/friends`


- [GET api/favorites (deprecated)](https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/get-favorites-list)
    - Unsupported parameters:
        - `user_id`: Favorites aren't returned for other users than self
        - `screen_name`: Favorites aren't returned for other users than self
- [POST api/favorites/create](https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-favorites-create)
- [POST api/favorites/destroy](https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-favorites-destroy)


- [GET api/lists/list](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-list)
- [GET api/lists/ownerships](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-ownerships)
    - Unsupported parameters:
        - `slug`
        - `owner_screen_name`
        - `owner_id`
        - `include_entities`
- [GET api/lists/statuses](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-statuses)
    - Unsupported parameters:
        - `screen_name`
        - `count`
- [GET api/lists/subscriptions](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-subscriptions)
- [POST api/lists/update](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-update)
    - Unsupported parameters:
        - `slug`
        - `name`
        - `mode`
        - `description`
        - `owner_screen_name`
        - `owner_id`
- [POST api/lists/create](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-create)
    - Unsupported parameters:
        - `mode`
        - `description`
- [POST api/lists/destroy](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-destroy)
    - Unsupported parameters:
        - `owner_screen_name`
        - `owner_id`
        - `slug`

- [GET api/blocks/list](https://developer.twitter.com/en/docs/accounts-and-users/mute-block-report-users/api-reference/get-blocks-list)


- [GET api/friendships/incoming](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-friendships-incoming)
    - Unsupported parameters
        - `stringify_ids`

- - [GET api/followers/ids](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-followers-ids)
  - [GET api/followers/list](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-followers-list)
  - [GET api/friends/ids](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-friends-ids)
  - [GET api/friends/list](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-friends-list)
    - Additional parameters:
        - `since_id`: You can use the `next_cursor` value to load the next page.
        - `max_id`: You can use the inverse of the `previous_cursor` value to load the previous page.
    - Unsupported parameter:
        - `skip_status`: No status is returned even if it isn't set to true.
    - Caveats:
        - `cursor` trumps `since_id` trumps `max_id` if any combination is provided. 
        - `user_id` must be the ID of a contact associated with a local user account.
        - `screen_name` must be associated with a local user account.
        - `screen_name` trumps `user_id` if both are provided (undocumented Twitter behavior).
        - Will succeed but return an empty array for users hiding their contact lists.


- [POST api/friendships/destroy](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/post-friendships-destroy)




## Non-implemented endpoints

- [GET oauth/authenticate](https://developer.twitter.com/en/docs/basics/authentication/api-reference/authenticate)
- [GET oauth/authorize](https://developer.twitter.com/en/docs/basics/authentication/api-reference/authorize)
- [POST oauth/invalidate_token](https://developer.twitter.com/en/docs/basics/authentication/api-reference/invalidate_access_token)
- [POST oauth2/invalidate_token](https://developer.twitter.com/en/docs/basics/authentication/api-reference/invalidate_bearer_token)
- [POST oauth2/token](https://developer.twitter.com/en/docs/basics/authentication/api-reference/token)


- [GET lists/members](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-members)
- [GET lists/members/show](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-members-show)
- [GET lists/memberships](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-memberships)
- [GET lists/show](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-show)
- [GET lists/subscribers](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-subscribers)
- [GET lists/subscribers/show](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-subscribers-show)
- [POST lists/members/create](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-members-create)
- [POST lists/members/create_all](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-members-create_all)
- [POST lists/members/destroy](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-members-destroy)
- [POST lists/members/destroy_all](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-members-destroy_all)
- [POST lists/subscribers/create](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-subscribers-create)
- [POST lists/subscribers/destroy](https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-subscribers-destroy)


- [GET friendships/lookup](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-friendships-lookup)
- [GET friendships/no_retweets/ids](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-friendships-no_retweets-ids)
- [GET friendships/outgoing](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-friendships-outgoing)
- [GET friendships/show](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-friendships-show)
- [GET users/suggestions (deprecated)](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-users-suggestions)
- [GET users/suggestions/:slug (deprecated)](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-users-suggestions-slug)
- [GET users/suggestions/:slug/members (deprecated)](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-users-suggestions-slug-members)
- [POST friendships/create](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/post-friendships-create)
- [POST friendships/update](https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/post-friendships-update)


- [GET account/settings](https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/get-account-settings)
- [GET saved_searches/show/:id](https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/get-saved_searches-show-id)
- [GET users/profile_banner](https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/get-users-profile_banner)
- [POST account/remove_profile_banner](https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/post-account-remove_profile_banner)
- [POST account/settings](https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/post-account-settings)
- [POST account/update_profile_background_image (deprecated)](https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/post-account-update_profile_background_image)
- [POST account/update_profile_banner](https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/post-account-update_profile_banner)
- [POST saved_searches/create](https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/post-saved_searches-create)
- [POST saved_searches/destroy/:id](https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/post-saved_searches-destroy-id)


- [GET blocks/ids](https://developer.twitter.com/en/docs/accounts-and-users/mute-block-report-users/api-reference/get-blocks-ids)
- [GET mutes/users/ids](https://developer.twitter.com/en/docs/accounts-and-users/mute-block-report-users/api-reference/get-mutes-users-ids)
- [GET mutes/users/list](https://developer.twitter.com/en/docs/accounts-and-users/mute-block-report-users/api-reference/get-mutes-users-list)
- [POST blocks/create](https://developer.twitter.com/en/docs/accounts-and-users/mute-block-report-users/api-reference/post-blocks-create)
- [POST blocks/destroy](https://developer.twitter.com/en/docs/accounts-and-users/mute-block-report-users/api-reference/post-blocks-destroy)
- [POST mutes/users/create](https://developer.twitter.com/en/docs/accounts-and-users/mute-block-report-users/api-reference/post-mutes-users-create)
- [POST mutes/users/destroy](https://developer.twitter.com/en/docs/accounts-and-users/mute-block-report-users/api-reference/post-mutes-users-destroy)
- [POST users/report_spam](https://developer.twitter.com/en/docs/accounts-and-users/mute-block-report-users/api-reference/post-users-report_spam)


- [GET collections/entries](https://developer.twitter.com/en/docs/tweets/curate-a-collection/api-reference/get-collections-entries)
- [GET collections/list](https://developer.twitter.com/en/docs/tweets/curate-a-collection/api-reference/get-collections-list)
- [GET collections/show](https://developer.twitter.com/en/docs/tweets/curate-a-collection/api-reference/get-collections-show)
- [POST collections/create](https://developer.twitter.com/en/docs/tweets/curate-a-collection/api-reference/post-collections-create)
- [POST collections/destroy](https://developer.twitter.com/en/docs/tweets/curate-a-collection/api-reference/post-collections-destroy)
- [POST collections/entries/add](https://developer.twitter.com/en/docs/tweets/curate-a-collection/api-reference/post-collections-entries-add)
- [POST collections/entries/curate](https://developer.twitter.com/en/docs/tweets/curate-a-collection/api-reference/post-collections-entries-curate)
- [POST collections/entries/move](https://developer.twitter.com/en/docs/tweets/curate-a-collection/api-reference/post-collections-entries-move)
- [POST collections/entries/remove](https://developer.twitter.com/en/docs/tweets/curate-a-collection/api-reference/post-collections-entries-remove)
- [POST collections/update](https://developer.twitter.com/en/docs/tweets/curate-a-collection/api-reference/post-collections-update)


- [POST statuses/filter](https://developer.twitter.com/en/docs/tweets/filter-realtime/api-reference/post-statuses-filter)
  
  
- [GET statuses/mentions_timeline](https://developer.twitter.com/en/docs/tweets/timelines/api-reference/get-statuses-mentions_timeline)


- [GET favorites/list](https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/get-favorites-list)
- [GET statuses/lookup](https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/get-statuses-lookup)
- [GET statuses/oembed](https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/get-statuses-oembed)
- [GET statuses/retweeters/ids](https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/get-statuses-retweeters-ids)
- [GET statuses/retweets/:id](https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/get-statuses-retweets-id)
- [GET statuses/retweets_of_me](https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/get-statuses-retweets_of_me)
- [POST statuses/unretweet/:id](https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-unretweet-id)


- [GET statuses/sample](https://developer.twitter.com/en/docs/tweets/sample-realtime/api-reference/get-statuses-sample)
  

- [GET compliance/firehose](https://developer.twitter.com/en/docs/tweets/compliance/api-reference/compliance-firehose)
 

- [DELETE custom_profiles/destroy.json](https://developer.twitter.com/en/docs/direct-messages/custom-profiles/api-reference/delete-profile)
- [GET custom_profiles/:id](https://developer.twitter.com/en/docs/direct-messages/custom-profiles/api-reference/get-profile)
- [GET custom_profiles/list](https://developer.twitter.com/en/docs/direct-messages/custom-profiles/api-reference/get-profile-list)
- [POST custom_profiles/new.json](https://developer.twitter.com/en/docs/direct-messages/custom-profiles/api-reference/new-profile)


- [DELETE direct_messages/events/destroy](https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/delete-message-event)
- [GET direct_messages/events/list](https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/list-events)
- [GET direct_messages/events/show](https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/get-event)
- [POST direct_messages/events/new (message_create)](https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/new-event)
- [POST direct_messages/indicate_typing](https://developer.twitter.com/en/docs/direct-messages/typing-indicator-and-read-receipts/api-reference/new-typing-indicator)
- [POST direct_messages/mark_read](https://developer.twitter.com/en/docs/direct-messages/typing-indicator-and-read-receipts/api-reference/new-read-receipt)
  
  
- [DELETE direct_messages/welcome_messages/destroy](https://developer.twitter.com/en/docs/direct-messages/welcome-messages/api-reference/delete-welcome-message)
- [DELETE direct_messages/welcome_messages/rules/destroy](https://developer.twitter.com/en/docs/direct-messages/welcome-messages/api-reference/delete-welcome-message-rule)
- [PUT direct_messages/welcome_messages/update](https://developer.twitter.com/en/docs/direct-messages/welcome-messages/api-reference/update-welcome-message)
- [GET direct_messages/welcome_messages/list](https://developer.twitter.com/en/docs/direct-messages/welcome-messages/api-reference/list-welcome-messages)
- [GET direct_messages/welcome_messages/rules/list](https://developer.twitter.com/en/docs/direct-messages/welcome-messages/api-reference/list-welcome-message-rules)
- [GET direct_messages/welcome_messages/rules/show](https://developer.twitter.com/en/docs/direct-messages/welcome-messages/api-reference/get-welcome-message-rule)
- [GET direct_messages/welcome_messages/show](https://developer.twitter.com/en/docs/direct-messages/welcome-messages/api-reference/get-welcome-message)
- [POST direct_messages/welcome_messages/new](https://developer.twitter.com/en/docs/direct-messages/welcome-messages/api-reference/new-welcome-message)
- [POST direct_messages/welcome_messages/rules/new](https://developer.twitter.com/en/docs/direct-messages/welcome-messages/api-reference/new-welcome-message-rule)


- [GET media/upload (STATUS)](https://developer.twitter.com/en/docs/media/upload-media/api-reference/get-media-upload-status)
- [POST media/subtitles/create](https://developer.twitter.com/en/docs/media/upload-media/api-reference/post-media-subtitles-create)
- [POST media/subtitles/delete](https://developer.twitter.com/en/docs/media/upload-media/api-reference/post-media-subtitles-delete)
- [POST media/upload (APPEND)](https://developer.twitter.com/en/docs/media/upload-media/api-reference/post-media-upload-append)
- [POST media/upload (FINALIZE)](https://developer.twitter.com/en/docs/media/upload-media/api-reference/post-media-upload-finalize)
- [POST media/upload (INIT)](https://developer.twitter.com/en/docs/media/upload-media/api-reference/post-media-upload-init)


- [GET trends/available](https://developer.twitter.com/en/docs/trends/locations-with-trending-topics/api-reference/get-trends-available)
- [GET trends/closest](https://developer.twitter.com/en/docs/trends/locations-with-trending-topics/api-reference/get-trends-closest)
- [GET trends/place](https://developer.twitter.com/en/docs/trends/trends-for-location/api-reference/get-trends-place)
 
 
- [GET geo/id/:place_id](https://developer.twitter.com/en/docs/geo/place-information/api-reference/get-geo-id-place_id)
- [GET geo/reverse_geocode](https://developer.twitter.com/en/docs/geo/places-near-location/api-reference/get-geo-reverse_geocode)
- [GET geo/search](https://developer.twitter.com/en/docs/geo/places-near-location/api-reference/get-geo-search)
