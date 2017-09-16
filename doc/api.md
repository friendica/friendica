Friendica API
===

* [Home](help)

The Friendica API aims to be compatible with the [GNU Social API](http://wiki.gnusocial.de/gnusocial:api) and the [Twitter API](https://dev.twitter.com/rest/public).

Please refer to the linked documentation for further information.

## Implemented API calls

### General
#### HTTP Method

API endpoints can restrict the method used to request them.
Using an invalid method results in HTTP error 405 "Method Not Allowed".

In this document, the required method is listed after the endpoint name. "*" means every method can be used.

#### Auth

Friendica supports basic http auth and OAuth 1 to authenticate the user to the api.

OAuth settings can be added by the user in web UI under /settings/oauth/

In this document, endpoints which requires auth are marked with "AUTH" after endpoint name

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
When an error occurs in API call, an HTTP error code is returned, with an error message
Usually:
- 400 Bad Request: if parameters are missing or items can't be found
- 403 Forbidden: if the authenticated user is missing
- 405 Method Not Allowed: if API was called with an invalid method, eg. GET when API require POST
- 501 Not Implemented: if the requested API doesn't exist
- 500 Internal Server Error: on other error conditions

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
### account/rate_limit_status (*; AUTH)

---
### account/verify_credentials (*; AUTH)
#### Parameters

* skip_status: Don't show the "status" field. (Default: false)
* include_entities: "true" shows entities for pictures and links (Default: false)

---
### conversation/show (*; AUTH)
Unofficial Twitter command. It shows all direct answers (excluding the original post) to a given id.

#### Parameter
* id: id of the post
* count: Items per page (default: 20)
* page: page number
* since_id: minimum id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts
* trim_user
* contributor_details

---
### direct_messages (*; AUTH)
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimum id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"
* include_entities: "true" shows entities for pictures and links (Default: false)
* friendica_verbose: "true" enables different error returns (default: "false")

#### Unsupported parameters
* skip_status

---
### direct_messages/all (*; AUTH)
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimum id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"
* friendica_verbose: "true" enables different error returns (default: "false")

---
### direct_messages/conversation (*; AUTH)
Shows all direct messages of a conversation
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimum id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"
* uri: URI of the conversation
* friendica_verbose: "true" enables different error returns (default: "false")

---
### direct_messages/sent (*; AUTH)
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimum id
* max_id: maximum id
* getText: Defines the format of the status field. Can be "html" or "plain"
* include_entities: "true" shows entities for pictures and links (Default: false)
* friendica_verbose: "true" enables different error returns (default: "false")

---
### direct_messages/new (POST,PUT; AUTH)
#### Parameters
* user_id: id of the user
* screen_name: screen name (for technical reasons, this value is not unique!)
* text: The message
* replyto: ID of the replied direct message
* title: Title of the direct message

---
### direct_messages/destroy (POST,DELETE; AUTH)
#### Parameters
* id: id of the message to be deleted
* include_entities: optional, currently not yet implemented
* friendica_parenturi: optional, can be used for increased safety to delete only intended messages
* friendica_verbose: "true" enables different error returns (default: "false")

#### Return values

On success:
* JSON return as defined for Twitter API not yet implemented
* on friendica_verbose=true: JSON return {"result":"ok","message":"message deleted"}

On error:
HTTP 400 BadRequest
* on friendica_verbose=true: different JSON returns {"result":"error","message":"xyz"}

---
### externalprofile/show (*)
#### Parameters
* profileurl: profile url

---
### favorites (*; AUTH)
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimum id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* user_id
* screen_name

Favorites aren't displayed to other users, so "user_id" and "screen_name" are unsupported.
Set this values will result in an empty array.

---
### favorites/create (POST,PUT; AUTH)
#### Parameters
* id
* include_entities: "true" shows entities for pictures and links (Default: false)

---
### favorites/destroy (POST,DELETE; AUTH)
#### Parameters
* id
* include_entities: "true" shows entities for pictures and links (Default: false)

---
### followers/ids (*; AUTH)
#### Parameters
* stringify_ids: Send id numbers as text (true) or integers (false)? (default: false)

#### Unsupported parameters
* user_id
* screen_name
* cursor

Friendica doesn't allow showing the followers of other users.

---
### friends/ids (*; AUTH)
#### Parameters
* stringify_ids: Send the id numbers as text (true) or integers (false)? (default: false)

#### Unsupported parameters
* user_id
* screen_name
* cursor

Friendica doesn't allow showing the friends of other users.

---
### help/test (*)

---
### media/upload (POST,PUT; AUTH)
#### Parameters
* media: image data

---
### oauth/request_token (*)
#### Parameters
* oauth_callback

#### Unsupported parameters
* x_auth_access_type

---
### oauth/access_token (*)
#### Parameters
* oauth_verifier

#### Unsupported parameters
* x_auth_password
* x_auth_username
* x_auth_mode

---
### statuses/destroy (POST,DELETE; AUTH)
#### Parameters
* id: message number
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* trim_user

---
### statuses/followers (*; AUTH)

#### Parameters

* include_entities: "true" shows entities for pictures and links (Default: false)

---
### statuses/friends (*; AUTH)

#### Parameters

* include_entities: "true" shows entities for pictures and links (Default: false)

---
### statuses/friends_timeline (*; AUTH)
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimum id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts
* trim_user
* contributor_details

---
### statuses/home_timeline (*; AUTH)
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimum id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts
* trim_user
* contributor_details

---
### statuses/mentions (*; AUTH)
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimum id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts
* trim_user
* contributor_details

---
### statuses/public_timeline (*; AUTH)
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimum id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* trim_user

---
### statuses/replies (*; AUTH)
#### Parameters
* count: Items per page (default: 20)
* page: page number
* since_id: minimum id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* include_rts
* trim_user
* contributor_details

---
### statuses/retweet (POST,PUT; AUTH)
#### Parameters
* id: message number
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* trim_user

---
### statuses/show (*; AUTH)
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
### statuses/user_timeline (*; AUTH)
#### Parameters
* user_id: id of the user
* screen_name: screen name (for technical reasons, this value is not unique!)
* count: Items per page (default: 20)
* page: page number
* since_id: minimum id
* max_id: maximum id
* exclude_replies: don't show replies (default: false)
* conversation_id: Shows all statuses of a given conversation.
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters

* include_rts
* trim_user
* contributor_details

---
### statusnet/config (*)

---
### statusnet/conversation (*; AUTH)
It shows all direct answers (excluding the original post) to a given id.

#### Parameter
* id: id of the post
* count: Items per page (default: 20)
* page: page number
* since_id: minimum id
* max_id: maximum id
* include_entities: "true" shows entities for pictures and links (Default: false)

---
### statusnet/version (*)

#### Unsupported parameters
* user_id
* screen_name
* cursor

Friendica doesn't allow showing followers of other users.

---
### users/search (*)
#### Parameters
* q: name of the user

#### Unsupported parameters
* page
* count
* include_entities

---
### users/show (*)
#### Parameters
* user_id: id of the user
* screen_name: screen name (for technical reasons, this value is not unique!)
* include_entities: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters
* user_id
* screen_name
* cursor

Friendica doesn't allow showing friends of other users.


---
### account/update_profile_image (POST; AUTH)
#### Parameters
* image: image data as base64 (Twitter has a limit of 700kb, Friendica allows more)
* profile_id (optional): id of the profile for which the image should be used, default is changing the default profile

uploads a new profile image (scales 4-6) to database, changes default or specified profile to the new photo

#### Return values

On success:
* JSON return: returns the updated user details (see account/verify_credentials)

On error:
* 403 FORBIDDEN: if not authenticated
* 400 BADREQUEST: "no media data submitted", "profile_id not available"
* 500 INTERNALSERVERERROR: "image size exceeds PHP config settings, file was rejected by server",
			"image size exceeds Friendica Config setting (uploaded size: x)",
			"unable to process image data",
			"image upload failed"


## Implemented API calls (not compatible with other APIs)


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
Attend verbs disable eachother: that means that if "attendyes" was added to an item, adding "attendno" remove previous "attendyes".
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
### friendica/group_show (*; AUTH)
Return all or a specified group of the user with the containing contacts as array.

#### Parameters
* gid: optional, if not given, API returns all groups of the user

#### Return values
Array of:

* name: name of the group
* gid: id of the group
* user: array of group members (return from api_get_user() function for each member)


---
### friendica/group_delete (POST,DELETE; AUTH)
delete the specified group of contacts; API call need to include the correct gid AND name of the group to be deleted.

#### Parameters
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
### friendica/group_create (POST,PUT; AUTH)
Create the group with the posted array of contacts as members.

#### Parameters
* name: name of the group to be created

#### POST data
JSON data as Array like the result of "users/group_show":

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
### friendica/group_update (POST)
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
### friendica/notifications (GET)
Return last 50 notification for current user, ordered by date with unseen item on top

#### Parameters
none

#### Return values
Array of:

* id: id of the note
* type: type of notification as int (see NOTIFY_* constants in boot.php)
* name: full name of the contact subject of the note
* url: contact's profile url
* photo: contact's profile photo
* date: datetime string of the note
* timestamp: timestamp of the node
* date_rel: relative date of the note (eg. "1 hour ago")
* msg: note message in bbcode
* msg_html: note message in html
* msg_plain: note message in plain text
* link: link to note
* seen: seen state: 0 or 1


---
### friendica/notifications/seen (POST)
Set note as seen, returns item object if possible

#### Parameters
id: id of the note to set seen

#### Return values
If the note is linked to an item, the item is returned, just like one of the "statuses/*_timeline" api.

If the note is not linked to an item, a success status is returned:

* "success" (json) | <status>success</status>;" (xml)


---
### friendica/photo (*; AUTH)
#### Parameters
* photo_id: Resource id of a photo.
* scale: (optional) scale value of the photo

Returns data of a picture with the given resource.
If 'scale' isn't provided, returned data include full url to each scale of the photo.
If 'scale' is set, returned data include image data base64 encoded.

possibile scale value are:

* 0: original or max size by server settings
* 1: image with or height at <= 640
* 2: image with or height at <= 320
* 3: thumbnail 160x160
* 4: Profile image at 175x175
* 5: Profile image at 80x80
* 6: Profile image at 48x48

An image used as profile image has only scale 4-6, other images only 0-3

#### Return values

json
```
	{
		"id": "photo id"
		"created": "date(YYYY-MM-DD HH:MM:SS)",
		"edited": "date(YYYY-MM-DD HH:MM:SS)",
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
		<created>date(YYYY-MM-DD HH:MM:SS)</created>
		<edited>date(YYYY-MM-DD HH:MM:SS)</edited>
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
### friendica/photos/list (*; AUTH)

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
### friendica/photoalbum/delete (POST,DELETE; AUTH)
#### Parameters
* album: name of the album to be deleted

deletes all images with the specified album name, is not reversible -> ensure that client is asking user for being sure to do this

#### Return values

On success:
* JSON return {"result":"deleted","message":"album 'xyz' with all containing photos has been deleted."}

On error:
* 403 FORBIDDEN: if not authenticated
* 400 BADREQUEST: "no albumname specified", "album not available"
* 500 INTERNALSERVERERROR: "problem with deleting item occured", "unknown error - deleting from database failed"


---
### friendica/photoalbum/update (POST,PUT; AUTH)
#### Parameters
* album: name of the album to be updated
* album_new: new name of the album

changes the album name to album_new for all photos in album

#### Return values

On success:
* JSON return {"result":"updated","message":"album 'abc' with all containing photos has been renamed to 'xyz'."}

On error:
* 403 FORBIDDEN: if not authenticated
* 400 BADREQUEST: "no albumname specified", "no new albumname specified", "album not available"
* 500 INTERNALSERVERERROR: "unknown error - updating in database failed"


---
### friendica/photo/create (POST; AUTH)
### friendica/photo/update (POST; AUTH)
#### Parameters
* photo_id (optional): if specified the photo with this id will be updated
* media (optional): image data as base64, only optional if photo_id is specified (new upload must have media)
* desc (optional): description for the photo, updated when photo_id is specified
* album: name of the album to be deleted (always necessary)
* album_new (optional): can be used to change the album of a single photo if photo_id is specified
* allow_cid/allow_gid/deny_cid/deny_gid (optional): on create: empty string or omitting = public photo, specify in format '```<x><y><z>```' for private photo;
			on update: keys need to be present with empty values for changing a private photo to public

both calls point to one function for creating AND updating photos.
Saves data for the scales 0-2 to database (see above for scale description).
Call adds non-visible entries to items table to enable authenticated contacts to comment/like the photo.
Client should pay attention to the fact that updated access rights are not transferred to the contacts. i.e. public photos remain publicly visible if they have been commented/liked before setting visibility back to a limited group.
Currently it is best to inform user that updating rights is not the right way to do this, and offer a solution to add photo as a new photo with the new rights instead.

#### Return values

On success:
* new photo uploaded: JSON return with photo data (see friendica/photo)
* photo updated - changed photo data: JSON return with photo data (see friendica/photo)
* photo updated - changed info: JSON return {"result":"updated","message":"Image id 'xyz' has been updated."}
* photo updated - nothing changed: JSON return {"result":"cancelled","message":"Nothing to update for image id 'xyz'."}

On error:
* 403 FORBIDDEN: if not authenticated
* 400 BADREQUEST: "no albumname specified", "no media data submitted", "photo not available", "acl data invalid"
* 500 INTERNALSERVERERROR: "image size exceeds PHP config settings, file was rejected by server",
			"image size exceeds Friendica Config setting (uploaded size: x)",
			"unable to process image data",
			"image upload failed",
			"unknown error - uploading photo failed, see Friendica log for more information",
			"unknown error - update photo entry in database failed",
			"unknown error - this error on uploading or updating a photo should never happen"


---
### friendica/photo/delete (DELETE; AUTH)
#### Parameters
* photo_id: id of the photo to be deleted

deletes a single image with the specified id, is not reversible -> ensure that client is asking user for being sure to do this
Sets item table entries for this photo to deleted = 1

#### Return values

On success:
* JSON return {"result":"deleted","message":"photo with id 'xyz' has been deleted from server."}

On error:
* 403 FORBIDDEN: if not authenticated
* 400 BADREQUEST: "no photo_id specified", "photo not available"
* 500 INTERNALSERVERERROR: "unknown error on deleting photo", "problem with deleting items occurred"


---
### friendica/direct_messages_setseen (GET; AUTH)
#### Parameters
* id: id of the message to be updated as seen

#### Return values

On success:
* JSON return {"result":"ok","message":"message set to seen"}

On error:
* different JSON returns {"result":"error","message":"xyz"}

---
### friendica/direct_messages_search (GET; AUTH)
#### Parameters
* searchstring: string for which the API call should search as '%searchstring%' in field 'body' of all messages of the authenticated user (caption ignored)

#### Return values
Returns only tested with JSON, XML might work as well.

On success:
* JSON return {"success":"true","search_results": array of found messages}
* JSOn return {"success":"false","search_results":"nothing found"}

On error:
* different JSON returns {"result":"error","message":"searchstring not specified"}

---
### friendica/profile/show (GET; AUTH)
show data of all profiles or a single profile of the authenticated user

#### Parameters
* profile_id: id of the profile to be returned (optional, if omitted all profiles are returned by default)

#### Return values
On success: Array of:

* multi_profiles: true if user has activated multi_profiles
* global_dir: URL of the global directory set in server settings
* friendica_owner: user data of the authenticated user
* profiles: array of the profile data

On error:
HTTP 403 Forbidden: when no authentication was provided
HTTP 400 Bad Request: if given profile_id is not in the database or is not assigned to the authenticated user

General description of profile data in API returns:
* profile_id
* profile_name
* is_default: true if this is the public profile
* hide_friends: true if friends are hidden
* profile_photo
* profile_thumb
* publish: true if published on the server's local directory
* net_publish: true if published to global_dir
* description ... homepage: different data fields from 'profile' table in database
* users: array with the users allowed to view this profile (empty if is_default=true)


---
## Not Implemented API calls
The following API calls are implemented in GNU Social but not in Friendica: (incomplete)

* statuses/retweets_of_me
* friendships/create
* friendships/destroy
* friendships/exists
* friendships/show
* account/update_profile_background_image
* blocks/create
* blocks/destroy

The following API calls from the Twitter API are not implemented in either Friendica or GNU Social:

* statuses/mentions_timeline
* statuses/retweets/:id
* statuses/oembed
* statuses/retweeters/ids
* statuses/lookup
* direct_messages/show
* search/tweets
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

/usr/bin/curl -u USER:PASS https://YOUR.FRIENDICA.TLD/api/statuses/update.xml -d source="some source id" -d status="the status you want to post"

### Python
The [RSStoFriedika](https://github.com/pafcu/RSStoFriendika) code can be used as an example of how to use the API with python. The lines for posting are located at [line 21](https://github.com/pafcu/RSStoFriendika/blob/master/RSStoFriendika.py#L21) and following.

def tweet(server, message, group_allow=None):
url = server + '/api/statuses/update'
urllib2.urlopen(url, urllib.urlencode({'status': message,'group_allow[]':group_allow}, doseq=True))

There is also a [module for python 3](https://bitbucket.org/tobiasd/python-friendica) for using the API.
