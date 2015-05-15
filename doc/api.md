Implemented API calls
===
The friendica API aims to be compatible to the [StatusNet API](http://status.net/wiki/Twitter-compatible_API) which aims to be compatible to the [Twitter API 1.0](https://dev.twitter.com/docs/api/1). 

Please refer to the linked documentation for further information.

General
---

### Unsupported parameters
* cursor: Not implemented in StatusNet
* trim_user: Not implemented in StatusNet
* contributor_details: Not implemented in StatusNet
* place_id: Not implemented in StatusNet
* display_coordinates: Not implemented in StatusNet
* include_rts: To-Do
* include_my_retweet: Retweets in friendica are implemented in a different way

### Different behaviour
* screen_name: The nick name in friendica is only unique in each network but not for all networks. The users are searched in the following priority: Friendica, StatusNet/GNU Social, Diaspora, pump.io, Twitter. If no contact was found by this way, then the first contact is taken.
* include_entities: Default is "false". If set to "true" then the plain text is formatted so that links are having descriptions.

### Return values
* cid: Contact id of the user (important for "contact_allow" and "contact_deny")
* network: network of the user

account/verify_credentials
---

### Parameters
* skip_status: Don't show the "status" field. (Default: false)
* include_entities: "true" shows entities for pictures and links (Default: false)

statuses/update, statuses/update_with_media
---

### Parameters
* title: Title of the status
* status: Status in text format
* htmlstatus: Status in HTML format
* in_reply_to_status_id
* lat: latitude
* long: longitude
* media: image data
* source: Application name
* group_allow
* contact_allow
* group_deny
* contact_deny
* network
* include_entities: "true" shows entities for pictures and links (Default: false)

### Unsupported parameters
* trim_user
* place_id
* display_coordinates

users/search
---

### Parameters
* q: name of the user 

### Unsupported parameters
* page
* count
* include_entities

users/show
---

### Parameters
* user_id: id of the user 
* screen_name: screen name (for technical reasons, this value is not unique!)
* include_entities: "true" shows entities for pictures and links (Default: false)

statuses/home_timeline
---

### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

### Unsupported parameters
* include_rts 
* trim_user 
* contributor_details 

statuses/friends_timeline
---

### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

### Unsupported parameters
* include_rts 
* trim_user 
* contributor_details 

statuses/public_timeline
---

### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

### Unsupported parameters
* trim_user 

statuses/show
---

### Parameters
* id: message number
* conversation: if set to "1" show all messages of the conversation with the given id
* include_entities: "true" shows entities for pictures and links (Default: false)

### Unsupported parameters
* include_my_retweet 
* trim_user 

statuses/retweet
---

### Parameters
* id: message number
* include_entities: "true" shows entities for pictures and links (Default: false)

### Unsupported parameters
* trim_user 

statuses/destroy
---

### Parameters
* id: message number
* include_entities: "true" shows entities for pictures and links (Default: false)

### Unsupported parameters
* trim_user 

statuses/mentions
---

### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

### Unsupported parameters
* include_rts 
* trim_user 
* contributor_details 

statuses/replies
---

### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

### Unsupported parameters
* include_rts 
* trim_user 
* contributor_details 

statuses/user_timeline
---

### Parameters
* user_id: id of the user 
* screen_name: screen name (for technical reasons, this value is not unique!)
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

### Unsupported parameters
* include_rts 
* trim_user 
* contributor_details 

conversation/show
---

Unofficial Twitter command. It shows all direct answers (excluding the original post) to a given id.

### Parameters
* id: id of the post
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

### Unsupported parameters
* include_rts 
* trim_user 
* contributor_details 

favorites
---

### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

### Unsupported parameters
* user_id
* screen_name

Favorites aren't displayed to other users, so "user_id" and "screen_name". So setting this value will result in an empty array.

account/rate_limit_status
---

help/test
---

statuses/friends
---
* include_entities: "true" shows entities for pictures and links (Default: false)

### Unsupported parameters
* user_id
* screen_name
* cursor 

Friendica doesn't allow showing friends of other users.

statuses/followers
---

* include_entities: "true" shows entities for pictures and links (Default: false)

### Unsupported parameters
* user_id
* screen_name
* cursor 

Friendica doesn't allow showing followers of other users.

statusnet/config
---

statusnet/version
---

friends/ids
---

### Parameters
* stringify_ids: Should the id numbers be sent as text (true) or number (false)? (default: false)

### Unsupported parameters
* user_id
* screen_name
* cursor 

Friendica doesn't allow showing friends of other users.

followers/ids
---

Parameters
---

* stringify_ids: Should the id numbers be sent as text (true) or number (false)? (default: false)

### Unsupported parameters
* user_id
* screen_name
* cursor 

Friendica doesn't allow showing followers of other users.

direct_messages/new
---

### Parameters
* user_id: id of the user 
* screen_name: screen name (for technical reasons, this value is not unique!)
* text: The message
* replyto: ID of the replied direct message
* title: Title of the direct message

direct_messages/conversation
---

Shows all direct messages of a conversation
### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"
* uri: URI of the conversation

direct_messages/all
---

### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"

direct_messages/sent
---

### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"
* include_entities: "true" shows entities for pictures and links (Default: false)

direct_messages
---

### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"
* include_entities: "true" shows entities for pictures and links (Default: false)

### Unsupported parameters
* skip_status 

oauth/request_token
---

### Parameters
* oauth_callback 

### Unsupported parameters
* x_auth_access_type 

oauth/access_token
---

### Parameters
* oauth_verifier 

### Unsupported parameters
* x_auth_password 
* x_auth_username 
* x_auth_mode 

Not Implemented API calls
===

The following list is extracted from the [API source file](https://github.com/friendica/friendica/blob/master/include/api.php) (at the very bottom):
* favorites/create
* favorites/destroy
* statuses/retweets_of_me
* friendships/create
* friendships/destroy
* friendships/exists
* friendships/show
* account/update_location
* account/update_profile_background_image
* account/update_profile_image
* blocks/create
* blocks/destroy

The following are things from the Twitter API also not implemented in StatusNet:
* statuses/retweeted_to_me
* statuses/retweeted_by_me
* direct_messages/destroy
* account/end_session
* account/update_delivery_device
* notifications/follow
* notifications/leave
* blocks/exists
* blocks/blocking
* lists

Usage Examples
===

BASH / cURL
---

Betamax has documentated some example API usage from a [bash script](https://en.wikipedia.org/wiki/Bash_(Unix_shell) employing [curl](https://en.wikipedia.org/wiki/CURL) (see [his posting](https://betamax65.de/display/betamax65/43539)).

    /usr/bin/curl -u USER:PASS https://YOUR.FRIENDICA.TLD/api/statuses/update.xml -d source="some source id" -d status="the status you want to post"

Python
---

The [RSStoFriedika](https://github.com/pafcu/RSStoFriendika) code can be used as an example of how to use the API with python.
The lines for posting are located at [line 21](https://github.com/pafcu/RSStoFriendika/blob/master/RSStoFriendika.py#L21) and following.

    def tweet(server, message, group_allow=None):
        url = server + '/api/statuses/update'
        urllib2.urlopen(url, urllib.urlencode({'status': message,'group_allow[]':group_allow}, doseq=True))

There is also a [module for python 3](https://bitbucket.org/tobiasd/python-friendica) for using the API.
