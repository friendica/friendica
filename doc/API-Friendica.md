# Friendica API

* [Home](help)
  * [Using the APIs](help/api)

## Overview

Friendica provides the following specific endpoints.

Authentication is the same as described in [Using the APIs](help/api#Authentication).

## Entities

These endpoints uses the [Friendica API entities](help/API-Entities).

## Endpoints

### GET api/friendica/events

Returns a list of [Event](help/API-Entities#Event) entities for the current logged in user.

#### Parameters

- `since_id`: (optional) minimum event id for pagination
- `count`: maximum number of items returned, default 20

### POST api/friendica/event_create

Create a new event for the current logged in user.

#### Parameters

- `id` : (optional) id of event, event will be amended if supplied
- `name` : name of the event (required)
- `start_time` : start of the event (ISO), required
- `end_time` : (optional) end of the event, event is open end, if not supplied
- `desc` : (optional) description of the event
- `place` : (optional) location of the event
- `publish` : (optional) create message for event
- `allow_cid` : (optional) ACL-formatted list of allowed contact ids if private event
- `allow_gid` : (optional) ACL-formatted list of disallowed contact ids if private event
- `deny_cid` : (optional) ACL-formatted list of allowed circle ids if private event
- `deny_gid` : (optional) ACL-formatted list of disallowed circle ids if private event

### POST api/friendica/event_delete

Delete event from calendar (not the message)

#### Parameters

- `id` : id of event to be deleted

### GET api/externalprofile/show

Returns a [Contact](help/API-Entities#Contact) entity for the provided profile URL.

#### Parameters

- `profileurl`: Profile URL

### GET api/statuses/public_timeline

Returns a list of public [Items](help/API-Entities#Item) posted on this node.
Equivalent of the local community page.

#### Parameters

* `count`: Items per page (default: 20)
* `page`: page number
* `since_id`: minimum id
* `max_id`: maximum id
* `exclude_replies`: don't show replies (default: false)
* `conversation_id`: Shows all statuses of a given conversation.
* `include_entities`: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters

* `trim_user`

### GET api/statuses/networkpublic_timeline

Returns a list of public [Items](help/API-Entities#Item) this node is aware of.
Equivalent of the global community page.

#### Parameters

* `count`: Items per page (default: 20)
* `page`: page number
* `since_id`: minimum id
* `max_id`: maximum id
* `exclude_replies`: don't show replies (default: false)
* `conversation_id`: Shows all statuses of a given conversation.
* `include_entities`: "true" shows entities for pictures and links (Default: false)

### GET api/statuses/replies

#### Parameters

* `count`: Items per page (default: 20)
* `page`: page number
* `since_id`: minimum id
* `max_id`: maximum id
* `include_entities`: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters

* `include_rts`
* `trim_user`
* `contributor_details`

---

### GET api/conversation/show

Unofficial Twitter command. It shows all direct answers (excluding the original post) to a given id.

#### Parameters

* `id`: id of the post
* `count`: Items per page (default: 20)
* `page`: page number
* `since_id`: minimum id
* `max_id`: maximum id
* `include_entities`: "true" shows entities for pictures and links (Default: false)

#### Unsupported parameters

* `include_rts`
* `trim_user`
* `contributor_details`

### GET api/statusnet/conversation

Alias of [`api/conversation/show`](#GET+api%2Fconversation%2Fshow).

### GET api/statusnet/config

Returns the public Friendica node configuration.

### GET api/gnusocial/config

Alias of [`api/statusnet/config`](#GET+api%2Fstatusnet%2Fconfig).

### GET api/statusnet/version

Returns a fake static StatusNet protocol version.

### GET api/gnusocial/version

Alias of [`api/statusnet/version`](#GET+api%2Fstatusnet%2Fversion).

---

### POST api/friendica/activity/[verb]

Add or remove an activity from an item.
'verb' can be one of:

* `like`
* `dislike`
* `attendyes`
* `attendno`
* `attendmaybe`

To remove an activity, prepend the verb with "un", eg. "unlike" or "undislike"
Attend verbs disable each other: that means that if "attendyes" was added to an item, adding "attendno" remove previous "attendyes".
Attend verbs should be used only with event-related items (there is no check at the moment).

#### Parameters

* `id`: item id

#### Return values

On success:
json:

```"ok"```

xml:

```<ok>true</ok>```

On error:
HTTP 400 BadRequest

---

### GET api/direct_messages

Deprecated Twitter received direct message list endpoint.

#### Parameters

* `count`: Items per page (default: 20)
* `page`: page number
* `since_id`: minimum id
* `max_id`: maximum id
* `getText`: Defines the format of the status field. Can be "html" or "plain"
* `include_entities`: "true" shows entities for pictures and links (Default: false)
* `friendica_verbose`: "true" enables different error returns (default: "false")

#### Unsupported parameters

* `skip_status`

### GET api/direct_messages/all

Returns all [Private Messages](help/API-Entities#Private+message).

#### Parameters

* `count`: Items per page (default: 20)
* `page`: page number
* `since_id`: minimum id
* `max_id`: maximum id
* `getText`: Defines the format of the status field. Can be "html" or "plain"
* `friendica_verbose`: "true" enables different error returns (default: "false")

### GET api/direct_messages/conversation

Returns all replies of a single private message conversation. Returns [Private Messages](help/API-Entities#Private+message)

#### Parameters

* `count`: Items per page (default: 20)
* `page`: page number
* `since_id`: minimum id
* `max_id`: maximum id
* `getText`: Defines the format of the status field. Can be "html" or "plain"
* `uri`: URI of the conversation
* `friendica_verbose`: "true" enables different error returns (default: "false")

### GET api/direct_messages/sent

Deprecated Twitter sent direct message list endpoint. Returns [Private Messages](help/API-Entities#Private+message).

#### Parameters

* `count`: Items per page (default: 20)
* `page`: page number
* `since_id`: minimum id
* `max_id`: maximum id
* `getText`: Defines the format of the status field. Can be "html" or "plain"
* `include_entities`: "true" shows entities for pictures and links (Default: false)
* `friendica_verbose`: "true" enables different error returns (default: "false")


### POST api/direct_messages/new

Deprecated Twitter direct message submission endpoint.

#### Parameters

* `user_id`: id of the user
* `screen_name`: screen name (for technical reasons, this value is not unique!)
* `text`: The message
* `replyto`: ID of the replied direct message
* `title`: Title of the direct message

### POST api/direct_messages/destroy

Deprecated Twitter direct message deletion endpoint.

#### Parameters

* `id`: id of the message to be deleted
* `include_entities`: optional, currently not yet implemented
* `friendica_parenturi`: optional, can be used for increased safety to delete only intended messages
* `friendica_verbose`: "true" enables different error returns (default: "false")

#### Return values

On success:

* JSON return as defined for Twitter API not yet implemented
* on friendica_verbose=true: JSON return {"result":"ok","message":"message deleted"}

On error:
HTTP 400 BadRequest

* on friendica_verbose=true: different JSON returns {"result":"error","message":"xyz"}

### GET api/friendica/direct_messages_setseen

#### Parameters

* `id`: id of the message to be updated as seen

#### Return values

On success:

* JSON return `{"result": "ok", "message": "message set to seen"}`

On error:

* different JSON returns `{"result": "error", "message": "xyz"}`


### GET api/friendica/direct_messages_search (GET; AUTH)

Returns [Private Messages](help/API-Entities#Private+message) matching the provided search string.

#### Parameters

* `searchstring`: string for which the API call should search as '%searchstring%' in field 'body' of all messages of the authenticated user (caption ignored)
* `getText` (optional): `plain`|`html` If omitted, the title is prepended to the plaintext body in the `text` attribute of the private message objects.
* `getUserObjects` (optional): `true`|`false` If `false`, the `sender` and `recipient` attributes of the private message object are absent.

#### Return values

Returns only tested with JSON, XML might work as well.

On success:

* JSON return `{"success":"true", "search_results": array of found messages}`
* JSOn return `{"success":"false", "search_results": "nothing found"}`

On error:

* different JSON returns `{"result": "error", "message": "searchstring not specified"}`

---

### GET api/friendica/circle_show

Alternatively: GET api/friendica/group_show (Backward compatibility)

Return all or a specified circle of the user with the containing contacts as array.

#### Parameters

* `gid`: optional, if not given, API returns all circles of the user

#### Return values

Array of:

* `name`: name of the circle
* `gid`: id of the circle
* `user`: array of [Contacts](help/API-Entities#Contact)

### POST api/friendica/circle_create

Alternatively: POST api/friendica/group_create

Create the circle with the posted array of contacts as members.

#### Parameters

* `name`: name of the circle to be created

#### POST data

JSON data as Array like the result of [GET api/friendica/circle_show](#GET+api%2Ffriendica%2Fcircle_show):

* `gid`
* `name`
* List of [Contacts](help/API-Entities#Contact)

#### Return values

Array of:

* `success`: true if successfully created or reactivated
* `gid`: gid of the created circle
* `name`: name of the created circle
* `status`: "missing user" | "reactivated" | "ok"
* `wrong users`: array of users, which were not available in the contact table

### POST api/friendica/circle_update

Alternatively: POST api/friendica/group_update

Update the circle with the posted array of contacts as members (post all members of the circle to the call; function will remove members not posted).

#### Parameters

* `gid`: id of the circle to be changed
* `name`: name of the circle to be changed

#### POST data

JSON data as array like the result of [GET api/friendica/circle_show](#GET+api%2Ffriendica%2Fcircle_show):

* `gid`
* `name`
* List of [Contacts](help/API-Entities#Contact)

#### Return values

Array of:

* `success`: true if successfully updated
* `gid`: gid of the changed circle
* `name`: name of the changed circle
* `status`: "missing user" | "ok"
* `wrong users`: array of users, which were not available in the contact table

### POST api/friendica/circle_delete

Alternatively: POST api/friendica/group_delete

Delete the specified circle of contacts; API call need to include the correct gid AND name of the circle to be deleted.

#### Parameters

* `gid`: id of the circle to be deleted
* `name`: name of the circle to be deleted

#### Return values

Array of:

* `success`: true if successfully deleted
* `gid`: gid of the deleted circle
* `name`: name of the deleted circle
* `status`: "deleted" if successfully deleted
* `wrong users`: empty array

---

### GET api/friendica/notifications

Return last 50 [Notifications](help/API-Entities#Notification) for the current user, ordered by date with unseen item on top.

#### Parameters

none

### POST api/friendica/notifications/seen

Set notification as seen.

#### Parameters

- `id`: id of the notification to set seen

#### Return values

If the note is linked to an item, returns an [Item](help/API-Entities#Item).

Otherwise, a success status is returned:

* `success` (json) | `<status>success</status>` (xml)

---

### GET api/friendica/photo

Returns a [Photo](help/API-Entities#Photo).

#### Parameters

* `photo_id`: Resource id of a photo.
* `scale`: (optional) scale value of the photo

Returns data of a picture with the given resource.
If 'scale' isn't provided, returned data include full url to each scale of the photo.
If 'scale' is set, returned data include image data base64 encoded.

possibile scale value are:

* 0: original or max size by server settings
* 1: image with or height at <= 640
* 2: image with or height at <= 320
* 3: thumbnail 160x160
* 4: Profile image at 300x300
* 5: Profile image at 80x80
* 6: Profile image at 48x48

An image used as profile image has only scale 4-6, other images only 0-3

#### Return values

json:

```json
	{
		"id": "photo id",
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
			"<scale>": "url to image",
			...
		},
		// if 'scale' is set
		"datasize": "size in byte",
		"data": "base64 encoded image data"
	}
```

xml:

```xml
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

### GET api/friendica/photos/list

Returns the API user's [Photo List Items](help/API-Entities#Photo+List+Item).

#### Return values

json:

```json
	[
		{
			"id": "resource_id",
			"album": "album name",
			"filename": "original file name",
			"type": "image mime type",
			"thumb": "url to thumb sized image"
		},
		...
	]
```

xml:

```xml
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

### POST api/friendica/photo/create

Alias of [`api/friendica/photo/update`](#POST+api%2Ffriendica%2Fphoto%2Fupdate)

### POST api/friendica/photo/update

Saves data for the scales 0-2 to database (see above for scale description).
Call adds non-public entries to items table to enable authenticated contacts to comment/like the photo.
Client should pay attention to the fact that updated access rights are not transferred to the contacts. i.e. public photos remain publicly visible if they have been commented/liked before setting visibility back to a limited circle.
Currently it is best to inform user that updating rights is not the right way to do this, and offer a solution to add photo as a new photo with the new rights instead.

#### Parameters

* `photo_id` (optional): if specified the photo with this id will be updated
* `media` (optional): image data as base64, only optional if photo_id is specified (new upload must have media)
* `desc` (optional): description for the photo, updated when photo_id is specified
* `album`: name of the album to be deleted (always necessary)
* `album_new` (optional): can be used to change the album of a single photo if photo_id is specified
* `allow_cid`/`allow_gid`/`deny_cid`/`deny_gid` (optional):
    - on create: empty string or omitting = public photo, specify in format ```<x><y><z>``` for private photo
	- on update: keys need to be present with empty values for changing a private photo to public

#### Return values

On success:

* new photo uploaded: JSON return with photo data (see [GET api/friendica/photo](#GET+api%2Ffriendica%2Fphoto))
* photo updated - changed photo data: JSON return with photo data (see [GET api/friendica/photo](#GET+api%2Ffriendica%2Fphoto))
* photo updated - changed info: JSON return `{"result": "updated", "message":"Image id 'xyz' has been updated."}`
* photo updated - nothing changed: JSON return `{"result": "cancelled","message": "Nothing to update for image id 'xyz'."}`

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

### POST api/friendica/photo/delete

Deletes a single image with the specified id, is not reversible -> ensure that client is asking user for being sure to do this
Sets item table entries for this photo to deleted = 1.

#### Parameters

* `photo_id`: id of the photo to be deleted

#### Return values

On success:

* JSON return

```json
{
    "result": "deleted",
    "message": "photo with id 'xyz' has been deleted from server."
}
```

On error:

* 403 FORBIDDEN: if not authenticated
* 400 BADREQUEST: "no photo_id specified", "photo not available"
* 500 INTERNALSERVERERROR: "unknown error on deleting photo", "problem with deleting items occurred"

---

### POST api/friendica/photoalbum/delete

Deletes all images with the specified album name, is not reversible -> ensure that client is asking user for being sure to do this.

#### Parameters

* `album`: name of the album to be deleted

#### Return values

On success:

* JSON return

```json
{
    "result": "deleted",
    "message": "album 'xyz' with all containing photos has been deleted."
}
```

On error:

* 403 FORBIDDEN: if not authenticated
* 400 BADREQUEST: "no albumname specified", "album not available"
* 500 INTERNALSERVERERROR: "problem with deleting item occurred", "unknown error - deleting from database failed"

### POST api/friendica/photoalbum/update

Changes the album name to album_new for all photos in album.

#### Parameters

* `album`: name of the album to be updated
* `album_new`: new name of the album

#### Return values

On success:

* JSON return

```json
{
  "result": "updated",
  "message":"album 'abc' with all containing photos has been renamed to 'xyz'."
}
```

On error:

* 403 FORBIDDEN: if not authenticated
* 400 BADREQUEST: "no albumname specified", "no new albumname specified", "album not available"
* 500 INTERNALSERVERERROR: "unknown error - updating in database failed"

### GET api/friendica/photoalbums

Get a list of photo albums for the user

#### Parameters

None
#### Return values

On success a list of photo album objects:

```json
[
  {
    "name": "Wall Photos",
    "created": "2023-01-22 02:03:19",
    "count": 4
  },
  {
    "name": "Profile photos",
    "created": "2022-11-20 14:40:06",
    "count": 1
  }
]
```

### GET api/friendica/photoalbum

Get a list of images in a photo album
#### Parameters

* `album` (Required): name of the album to be deleted
* `limit` (Optional): Maximum number of items to get, defaults to 50, max 500
* `offset`(Optional): Offset in results to page through total items, defaults to 0
* `latest_first` (Optional): Reverse the order so the most recent images are first, defaults to false

#### Return values

On success:

* JSON return with the list of Photo items

**Example:**
`https://<server>/api/friendica/photoalbum?album=Wall Photos&limit=10&offset=2`

```json
[
  {
    "created": "2023-02-14 14:31:06",
    "edited": "2023-02-14 14:31:14",
    "title": "",
    "desc": "",
    "album": "Wall Photos",
    "filename": "image.png",
    "type": "image/png",
    "height": 835,
    "width": 693,
    "datasize": 119523,
    "profile": 0,
    "allow_cid": "",
    "deny_cid": "",
    "allow_gid": "",
    "deny_gid": "",
    "id": "899184972463eb9b2ae3dc2580502826",
    "scale": 0,
    "media-id": 52,
    "scales": [
      {
        "id": 52,
        "scale": 0,
        "link": "https://<server>/photo/899184972463eb9b2ae3dc2580502826-0.png",
        "width": 693,
        "height": 835,
        "size": 119523
      },
      ...
    ],
    "thumb": "https://<server>/photo/899184972463eb9b2ae3dc2580502826-2.png"
  },
  ...
]
```

---


### GET api/friendica/profile/show

Returns the [Profile](help/API-Entities#Profile) data of the authenticated user.

#### Return values

On success: Array of:

* `global_dir`: URL of the global directory set in server settings
* `friendica_owner`: user data of the authenticated user
* `profiles`: array of the profile data

On error:
HTTP 403 Forbidden: when no authentication was provided
HTTP 400 Bad Request: if given profile_id is not in the database or is not assigned to the authenticated user

General description of profile data in API returns:
- hide_friends: true if friends are hidden
- profile_photo
- profile_thumb
- publish: true if published on the server's local directory
- net_publish: true if published to global_dir
- fullname
- date_of_birth
- description
- xmpp
- homepage
- address
- locality
- region
- postal_code
- country
- pub_keywords
- custom_fields: list of public custom fields

---

### POST api/friendica/statuses/:id/dislike

Marks the given status as disliked by this user

#### Path Parameter

* `id`: the status ID that is being marked

#### Return values

A Mastodon [Status Entity](https://docs.joinmastodon.org/entities/Status/)

#### Example:
`https://<server_name>/api/friendica/statuses/341/dislike`

```json
{
  "id": "341",
  "created_at": "2023-02-23T01:50:00.000Z",
  "in_reply_to_id": null,
  "in_reply_to_status": null,
  "in_reply_to_account_id": null,
  "sensitive": false,
  "spoiler_text": "",
  "visibility": "public",
  "language": "en",
  ...
  "account": {
    "id": "8",
    "username": "testuser2",
    ...
  },
  "media_attachments": [],
  "mentions": [],
  "tags": [],
  "emojis": [],
  "card": null,
  "poll": null,
  "friendica": {
    "title": "",
    "dislikes_count": 1,
    "disliked": true
  }
}
```


### GET api/friendica/statuses/:id/disliked_by

Returns the list of accounts that have disliked the status as known by the current server

#### Path Parameter

* `id`: the status ID that is being marked

#### Return values

A list of [Mastodon Account](https://docs.joinmastodon.org/entities/Account/) objects
in the body and next/previous link headers in the header

#### Example:
`https://<server_name>/api/friendica/statuses/341/disliked_by`

```json
[
  {
    "id": "6",
    "username": "testuser1",
    ...
  }
]
```



### POST api/friendica/statuses/:id/undislike

Removes the dislike mark (if it exists) on this status for this user

#### Path Parameter

* `id`: the status ID that is being marked

#### Return values

A Mastodon [Status Entity](https://docs.joinmastodon.org/entities/Status/)

#### Example:
`https://<server_name>/api/friendica/statuses/341/undislike`

```json
{
  "id": "341",
  "created_at": "2023-02-23T01:50:00.000Z",
  "in_reply_to_id": null,
  "in_reply_to_status": null,
  "in_reply_to_account_id": null,
  "sensitive": false,
  "spoiler_text": "",
  "visibility": "public",
  "language": "en",
  ...
  "account": {
    "id": "8",
    "username": "testuser2",
    ...
  },
  "media_attachments": [],
  "mentions": [],
  "tags": [],
  "emojis": [],
  "card": null,
  "poll": null,
  "friendica": {
    "title": "",
    "dislikes_count": 0,
    "disliked": false
  }
}
```

---

## Deprecated endpoints

- POST api/statuses/mediap
