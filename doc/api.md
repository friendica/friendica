Friendica API
===
The Friendica API aims to be compatible to the [GNU Social API](http://skilledtests.com/wiki/Twitter-compatible_API) and the [Twitter API](https://dev.twitter.com/rest/public).

Please refer to the linked documentation for further information.

## Implemented API calls

### General
#### Unsupported parameters
* cursor: Not implemented in GNU Social
* trim_user: Not implemented in GNU Social
* contributor_details: Not implemented in GNU Social
* place_id: Not implemented in GNU Social
* display_coordinates: Not implemented in GNU Social
* include_rts: To-Do
* include_my_retweet: Retweets in Friendica are implemented in a different way

#### Different behaviour
* screen_name: The nick name in friendica is only unique in each network but not for all networks. The users are searched in the following priority: Friendica, StatusNet/GNU Social, Diaspora, pump.io, Twitter. If no contact was found by this way, then the first contact is taken.
* include_entities: Default is "false". If set to "true" then the plain text is formatted so that links are having descriptions.

#### Return values
* cid: Contact id of the user (important for "contact_allow" and "contact_deny")
* network: network of the user

#### Errors
When an error occour in API call, an HTTP error code is returned, with an error message
Usually:
- 400 Bad Request: if parameter are missing or items can't be found
- 403 Forbidden: if authenticated user is missing
- 405 Method Not Allowed: if API was called with invalid method, eg. GET when API require POST
- 501 Not Implemented: if requested API doesn't exists
- 500 Internal Server Error: on other error contitions

Error body is

json:
```
	{
	  "error": "Specific error message",
	  "request": "API path requested",
	  "code": "HTTP error code"
	}
```

xml:
```
	<status>
		<error>Specific error message</error>
		<request>API path requested</request>
		<code>HTTP error code</code>
	</status>
```

---
### account/rate_limit_status

---
### account/verify_credentials
#### Parameters
* skip_status: Don't show the "status" field. (Default: false)
* include_entities: "true" shows entities for pictures and links (Default: false)

---
### conversation/show
Unofficial Twitter command. It shows all direct answers (excluding the original post) to a given id.

#### Parameters
* id: id of the post
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts
* trim_user
* contributor_details

---
### direct_messages
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* skip_status

---
### direct_messages/all
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"

---
### direct_messages/conversation
Shows all direct messages of a conversation
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"
* uri: URI of the conversation

---
### direct_messages/new
#### Parameters
* user_id: id of the user
* screen_name: screen name (for technical reasons, this value is not unique!)
* text: The message
* replyto: ID of the replied direct message
* title: Title of the direct message

---
### direct_messages/sent
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"
* include_entities: "true" shows entities for pictures and links (Default: false)

---
### favorites
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* user_id
* screen_name

Favorites aren't displayed to other users, so "user_id" and "screen_name". So setting this value will result in an empty array.

---
### favorites/create
#### Parameters
* id
* include_entities: "true" shows entities for pictures and links (Default: false)

---
### favorites/destroy
#### Parameters
* id
* include_entities: "true" shows entities for pictures and links (Default: false)

---
### followers/ids
#### Parameters
* stringify_ids: Should the id numbers be sent as text (true) or number (false)? (default: false)

#### Unsupported parameters
* user_id
* screen_name
* cursor

Friendica doesn't allow showing followers of other users.

---
### friendica/activity/<verb>
#### parameters
* id: item id

Add or remove an activity from an item.
'verb' can be one of:
- like
- dislike
- attendyes
- attendno
- attendmaybe

To remove an activity, prepend the verb with "un", eg. "unlike" or "undislike"
Attend verbs disable eachother: that means that if "attendyes" was added to an item,
adding "attendno" remove previous "attendyes".
Attend verbs should be used only with event-related items (there is no check at the moment)

#### Return values

On success:
json
```"ok"```

xml
```<ok>true</ok>```

On error:
HTTP 400 BadRequest

---
### friendica/photo
#### Parameters
* photo_id: Resource id of a photo.
* scale: (optional) scale value of the photo

Returns data of a picture with the given resource.
If 'scale' isn't provided, returned data include full url to each scale of the photo.
If 'scale' is set, returned data include image data base64 encoded.

possibile scale value are:
0: original or max size by server settings
1: image with or height at <= 640
2: image with or height at <= 320
3: thumbnail 160x160

4: Profile image at 175x175
5: Profile image at 80x80
6: Profile image at 48x48

An image used as profile image has only scale 4-6, other images only 0-3

#### Return values

json
```
	{
	  "id": "photo id"
	  "created": "date(YYYY-MM-GG HH:MM:SS)",
	  "edited": "date(YYYY-MM-GG HH:MM:SS)",
	  "title": "photo title",
	  "desc": "photo description",
	  "album": "album name",
	  "filename": "original file name",
	  "type": "mime type",
	  "height": "number",
	  "width": "number",
	  "profile": "1 if is profile photo",
	  "link": {
		"<scale>": "url to image"
		...
	  },
	  // if 'scale' is set
	  "datasize": "size in byte",
	  "data": "base64 encoded image data"
	}
```

xml
```
	<photo>
		<id>photo id</id>
		<created>date(YYYY-MM-GG HH:MM:SS)</created>
		<edited>date(YYYY-MM-GG HH:MM:SS)</edited>
		<title>photo title</title>
		<desc>photo description</desc>
		<album>album name</album>
		<filename>original file name</filename>
		<type>mime type</type>
		<height>number</height>
		<width>number</width>
		<profile>1 if is profile photo</profile>
		<links type="array">
			<link type="mime type" scale="scale number" href="image url"/>
			...
		</links>
	</photo>
```

---
### friendica/photos/list

Returns a list of all photo resources of the logged in user.

#### Return values

json
```
	[
		{
			id: "resource_id",
			album: "album name",
			filename: "original file name",
			type: "image mime type",
			thumb: "url to thumb sized image"
		},
		...
	]
```

xml
```
	<photos type="array">
		<photo id="resource_id"
			album="album name"
			filename="original file name"
			type="image mime type">
				"url to thumb sized image"
		</photo>
		...
	</photos>
```

---
### friends/ids
#### Parameters
* stringify_ids: Should the id numbers be sent as text (true) or number (false)? (default: false)

#### Unsupported parameters
* user_id
* screen_name
* cursor

Friendica doesn't allow showing friends of other users.

---
### help/test

---
### media/upload
#### Parameters
* media: image data

---
### oauth/request_token
#### Parameters
* oauth_callback

#### Unsupported parameters
* x_auth_access_type

---
### oauth/access_token
#### Parameters
* oauth_verifier

#### Unsupported parameters
* x_auth_password
* x_auth_username
* x_auth_mode

---
### statuses/destroy
#### Parameters
* id: message number
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* trim_user

---
### statuses/followers
* include_entities: "true" shows entities for pictures and links (Default: false)

---
### statuses/friends
* include_entities: "true" shows entities for pictures and links (Default: false)

---
### statuses/friends_timeline
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts
* trim_user
* contributor_details

---
### statuses/home_timeline
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts
* trim_user
* contributor_details

---
### statuses/mentions
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts
* trim_user
* contributor_details

---
### statuses/public_timeline
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* trim_user

---
### statuses/replies
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts
* trim_user
* contributor_details

---
### statuses/retweet
#### Parameters
* id: message number
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* trim_user

---
### statuses/show
#### Parameters
* id: message number
* conversation: if set to "1" show all messages of the conversation with the given id
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_my_retweet
* trim_user

---
### statuses/update, statuses/update_with_media
#### Parameters
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
* media_ids: (By now only a single value, no array)

#### Unsupported parameters
* trim_user
* place_id
* display_coordinates

---
### statuses/user_timeline
#### Parameters
* user_id: id of the user
* screen_name: screen name (for technical reasons, this value is not unique!)
* count: Items per page (default: 20)
* page: page number
* since_id: minimal id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts
* trim_user
* contributor_details

---
### statusnet/config

---
### statusnet/version

#### Unsupported parameters
* user_id
* screen_name
* cursor

Friendica doesn't allow showing followers of other users.

---
### users/search
#### Parameters
* q: name of the user

#### Unsupported parameters
* page
* count
* include_entities

---
### users/show
#### Parameters
* user_id: id of the user
* screen_name: screen name (for technical reasons, this value is not unique!)
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* user_id
* screen_name
* cursor

Friendica doesn't allow showing friends of other users.


## Implemented API calls (not compatible with other APIs)

---
### friendica/group_show
Return all or a specified group of the user with the containing contacts as array.

#### Parameters
* gid: optional, if not given, API returns all groups of the user

#### Return values
Array of:
* name: name of the group
* gid: id of the group
* user: array of group members (return from api_get_user() function for each member)


---
### friendica/group_delete
delete the specified group of contacts; API call need to include the correct gid AND name of the group to be deleted.

---
### Parameters
* gid: id of the group to be deleted
* name: name of the group to be deleted

#### Return values
Array of:
* success: true if successfully deleted
* gid: gid of the deleted group
* name: name of the deleted group
* status: „deleted“ if successfully deleted
* wrong users: empty array


---
### friendica/group_create
Create the group with the posted array of contacts as members.
#### Parameters
* name: name of the group to be created

#### POST data
JSON data as Array like the result of „users/group_show“:
* gid
* name
* array of users

#### Return values
Array of:
* success: true if successfully created or reactivated
* gid: gid of the created group
* name: name of the created group
* status: „missing user“ | „reactivated“ | „ok“
* wrong users: array of users, which were not available in the contact table


---
### friendica/group_update
Update the group with the posted array of contacts as members (post all members of the group to the call; function will remove members not posted).
#### Parameters
* gid: id of the group to be changed
* name: name of the group to be changed

#### POST data
JSON data as array like the result of „users/group_show“:
* gid
* name
* array of users

#### Return values
Array of:
* success: true if successfully updated
* gid: gid of the changed group
* name: name of the changed group
* status: „missing user“ | „ok“
* wrong users: array of users, which were not available in the contact table

---
## Not Implemented API calls
The following API calls are implemented in GNU Social but not in Friendica: (incomplete)

* statuses/retweets_of_me
* friendships/create
* friendships/destroy
* friendships/exists
* friendships/show
* account/update_profile_background_image
* account/update_profile_image
* blocks/create
* blocks/destroy

The following API calls from the Twitter API aren't implemented neither in Friendica nor in GNU Social:

* statuses/mentions_timeline
* statuses/retweets/:id
* statuses/oembed
* statuses/retweeters/ids
* statuses/lookup
* direct_messages/show
* search/tweets
* direct_messages/destroy
* friendships/no_retweets/ids
* friendships/incoming
* friendships/outgoing
* friendships/update
* friends/list
* friendships/lookup
* account/settings
* account/update_delivery_device
* account/update_profile
* account/update_profile_background_image
* account/update_profile_image
* blocks/list
* blocks/ids
* users/lookup
* users/show
* users/search
* account/remove_profile_banner
* account/update_profile_banner
* users/profile_banner
* mutes/users/create
* mutes/users/destroy
* mutes/users/ids
* mutes/users/list
* users/suggestions/:slug
* users/suggestions
* users/suggestions/:slug/members
* favorites/list
* lists/list
* lists/statuses
* lists/members/destroy
* lists/memberships
* lists/subscribers
* lists/subscribers/create
* lists/subscribers/show
* lists/subscribers/destroy
* lists/members/create_all
* lists/members/show
* lists/members
* lists/members/create
* lists/destroy
* lists/update
* lists/create
* lists/show
* lists/subscriptions
* lists/members/destroy_all
* lists/ownerships
* saved_searches/list
* saved_searches/show/:id
* saved_searches/create
* saved_searches/destroy/:id
* geo/id/:place_id
* geo/reverse_geocode
* geo/search
* geo/place
* trends/place
* trends/available
* help/configuration
* help/languages
* help/privacy
* help/tos
* trends/closest
* users/report_spam

---

---

## Usage Examples
### BASH / cURL
Betamax has documentated some example API usage from a [bash script](https://en.wikipedia.org/wiki/Bash_(Unix_shell) employing [curl](https://en.wikipedia.org/wiki/CURL) (see [his posting](https://betamax65.de/display/betamax65/43539)).

    /usr/bin/curl -u USER:PASS https://YOUR.FRIENDICA.TLD/api/statuses/update.xml -d source="some source id" -d status="the status you want to post"

### Python
The [RSStoFriedika](https://github.com/pafcu/RSStoFriendika) code can be used as an example of how to use the API with python. The lines for posting are located at [line 21](https://github.com/pafcu/RSStoFriendika/blob/master/RSStoFriendika.py#L21) and following.

    def tweet(server, message, group_allow=None):
        url = server + '/api/statuses/update'
        urllib2.urlopen(url, urllib.urlencode({'status': message,'group_allow[]':group_allow}, doseq=True))

There is also a [module for python 3](https://bitbucket.org/tobiasd/python-friendica) for using the API.
