# Friendica API entities

* [Home](help)
  * [Using the APIs](help/api)


## Activities

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th align="center">Nullable</th>
</tr>
</thead>
<tbody>

<tr>
<td><code>like</code></td>
<td>List of <a href="help/API-Entities#Contact">Contacts</a></td>
<td align="center">No</td>
</tr>

<tr>
<td><code>dislike</code></td>
<td>List of <a href="help/API-Entities#Contact">Contacts</a></td>
<td align="center">No</td>
</tr>

<tr>
<td><code>attendyes</code></td>
<td>List of <a href="help/API-Entities#Contact">Contacts</a></td>
<td align="center">No</td>
</tr>

<tr>
<td><code>attendno</code></td>
<td>List of <a href="help/API-Entities#Contact">Contacts</a></td>
<td align="center">No</td>
</tr>

<tr>
<td><code>attendmaybe</code></td>
<td>List of <a href="help/API-Entities#Contact">Contacts</a></td>
<td align="center">No</td>
</tr>

</tbody>
</table>

## Attachment

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th align="center">Nullable</th>
</tr>
</thead>
<tbody>

<tr>
<td><code>url</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>mimetype</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>size</code></td>
<td>Integer (bytes)</td>
<td align="center">No</td>
</tr>

</tbody>
</table>

## Contact

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th align="center">Nullable</th>
</tr>
</thead>

<tbody>
<tr>
<td><code>id</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>id_str</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>name</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>screen_name</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>location</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>description</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>profile_image_url</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>profile_image_url_https</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>profile_image_url_profile_size</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>profile_image_url_large</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>url</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>protected</code></td>
<td>Boolean</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>followers_count</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>friends_count</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>listed_count</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>favourites_count</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>statuses_count</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>created_at</code></td>
<td>String (Date)<br/>
Ex: Wed May 23 06:01:13 +0000 2007
</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>utc_offset</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>time_zone</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>geo_enabled</code></td>
<td>Boolean</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>verified</code></td>
<td>Boolean</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>lang</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>contributors_enabled</code></td>
<td>Boolean</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>is_translator</code></td>
<td>Boolean</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>is_translation_enabled</code></td>
<td>Boolean</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>following</code></td>
<td>Boolean</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>follow_request_sent</code></td>
<td>Boolean</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>statusnet_blocking</code></td>
<td>Boolean</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>notifications</code></td>
<td>Boolean</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>statusnet_profile_url</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>uid</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>cid</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>pid</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>self</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>network</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

</tbody>
</table>


## Entities

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th align="center">Nullable</th>
</tr>
</thead>
<tbody>

<tr>
<td><code>hashtags</code></td>
<td>List of <a href="help/API-Entities#Hashtag">Hashtags</a></td>
<td align="center">No</td>
</tr>

<tr>
<td><code>symbols</code></td>
<td>List of <a href="help/API-Entities#Symbol">Symbols</a></td>
<td align="center">No</td>
</tr>

<tr>
<td><code>urls</code></td>
<td>List of <a href="help/API-Entities#URL">URLs</a></td>
<td align="center">No</td>
</tr>

<tr>
<td><code>user_mentions</code></td>
<td>List of <a href="help/API-Entities#User+Mention">User mentions</a></td>
<td align="center">No</td>
</tr>

<tr>
<td><code>media</code></td>
<td>List of <a href="help/API-Entities#Media">Medias</a></td>
<td align="center">No</td>
</tr>

</tbody>
</table>

## Event

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th>Description</th>
</tr>
</thead>

<tbody>
<tr>
<td><code>id</code></td>
<td>Integer</td>
<td></td>
</tr>

<tr>
<td><code>uid</code></td>
<td>Integer</td>
<td>Owner User Id</td>
</tr>

<tr>
<td><code>cid</code></td>
<td>Integer</td>
<td>Target Contact Id</td>
</tr>

<tr>
<td><code>uri</code></td>
<td>String</td>
<td>Item unique URI string</td>
</tr>

<tr>
<td><code>name</code></td>
<td>String (Plaintext)</td>
<td>Title</td>
</tr>

<tr>
<td><code>desc</code></td>
<td>String (HTML)</td>
<td>Description</td>
</tr>

<tr>
<td><code>start_time</code></td>
<td>String (UTC <code>YYYY-MM-DD HH:II:SS)</code>)</td>
<td></td>
</tr>

<tr>
<td><code>end_time</code></td>
<td>String (UTC <code>YYYY-MM-DD HH:II:SS)</code>)</td>
<td>Optional (null date is <code>0001-01-01 00:00:00</code></td>
</tr>

<tr>
<td><code>type</code></td>
<td>String (<code>event</code> or <code>birthday</code>)</td>
<td></td>
</tr>

<tr>
<td><code>nofinish</code></td>
<td>Boolean</td>
<td>Ongoing event</td>
</tr>

<tr>
<td><code>place</code></td>
<td>String</td>
<td>Optional. Location.</td>
</tr>

<tr>
<td><code>ignore</code></td>
<td>Boolean</td>
<td>???</td>
</tr>

<tr>
<td><code>allow_cid</code></td>
<td>String (angle-brackets escaped integers)</td>
<td>Optional. List of allowed contact ids</td>
</tr>

<tr>
<td><code>allow_gid</code></td>
<td>String (angle-brackets escaped integers)</td>
<td>Optional. List of allowed circle ids</td>
</tr>

<tr>
<td><code>deny_cid</code></td>
<td>String (angle-brackets escaped integers)</td>
<td>Optional. List of disallowed contact ids</td>
</tr>

<tr>
<td><code>deny_gid</code></td>
<td>String (angle-brackets escaped integers)</td>
<td>Optional. List of disallowed circle ids</td>
</tr>

</tbody>
</table>

## Hashtag

Unused

## Item

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th align="center">Nullable</th>
</tr>
</thead>

<tbody>
<tr>
<td><code>text</code></td>
<td>String (Plaintext)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>truncated</code></td>
<td>Boolean</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>created_at</code></td>
<td>String (Date)<br/>
Ex: Wed May 23 06:01:13 +0000 2007
</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>in_reply_to_status_id</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>in_reply_to_status_id_str</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>source</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>id</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>id_str</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>in_reply_to_user_id</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>in_reply_to_user_id_str</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>in_reply_to_screen_name</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>geo</code></td>
<td>String</td>
<td align="center">Yes</td>
</tr>

<tr>
<td><code>favorited</code></td>
<td>Boolean</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>user</code></td>
<td><a href="help/API-Entities#Contact">Contact</a></td>
<td align="center">No</td>
</tr>

<tr>
<td><code>friendica_author</code></td>
<td><a href="help/API-Entities#Contact">Contact</a></td>
<td align="center">No</td>
</tr>

<tr>
<td><code>friendica_owner</code></td>
<td>

<a href="help/API-Entities#Contact">Contact</a></td>
<td align="center">No</td>
</tr>

<tr>
<td><code>friendica_private</code></td>
<td>Boolean</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>statusnet_html</code></td>
<td>String (HTML)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>statusnet_conversation_id</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>external_url</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>friendica_activities</code></td>
<td><a href="help/API-Entities#Activities">Activities</a></td>
<td align="center">No</td>
</tr>

<tr>
<td><code>friendica_title</code></td>
<td>String (Plaintext)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>friendica_html</code></td>
<td>String (HTML)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>attachments</code></td>
<td>List of <a href="help/API-Entities#Attachment">Attachments</a></td>
<td align="center">Yes</td>
</tr>

<tr>
<td><code>entities</code></td>
<td><a href="help/API-Entities#Entities">Entities</a></td>
<td align="center">Yes</td>
</tr>

</tbody>
</table>

## Media

Identical to [the Twitter Media Object](https://developer.twitter.com/en/docs/tweets/data-dictionary/overview/entities-object#media).

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th align="center">Nullable</th>
</tr>
</thead>
<tbody>

<tr>
<td><code>id</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>id_str</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>indices</code></td>
<td>List of Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>media_url</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>media_url_https</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>url</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>display_url</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>expanded_url</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>ext_alt_text</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>type</code></td>
<td>String</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>sizes</code></td>
<td><a href="help/API-Entities#Sizes">Sizes</a></td>
<td align="center">No</td>
</tr>

</tbody>
</table>

## Notification

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th>Description</th>
</tr>
</thead>
<tbody>

<tr>
<td><code>id</code></td>
<td>Integer</td>
<td></td>
</tr>

<tr>
<td><code>hash</code></td>
<td>String</td>
<td></td>
</tr>

<tr>
<td><code>type</code></td>
<td>Integer</td>
<td><ul>
<li>1: Inbound follow request</li>
<li>2: Outbound follow request confirmation</li>
<li>4: Wall-to-wall post</li>
<li>8: Reply</li>
<li>16: Private message</li>
<li>32: Friend suggestion</li>
<li>64: Unused</li>
<li>128: Mention</li>
<li>256: Tag added to a post</li>
<li>512: Poke (Obsolete)</li>
<li>1024: New post</li>
<li>16384: System email</li>
<li>32768: System event</li>
</ul></td>
</tr>

<tr>
<td><code>name</code></td>
<td>String</td>
<td>Full name of the contact subject</td>
</tr>

<tr>
<td><code>url</code></td>
<td>String (URL)</td>
<td>Profile page URL of the contact subject</td>
</tr>

<tr>
<td><code>photo</code></td>
<td>String (URL)</td>
<td>Profile photo URL of the contact subject</td>
</tr>

<tr>
<td><code>date</code></td>
<td>String (Date)</td>
<td><code>YYYY-MM-DD hh:mm:ss</code> local server time</td>
</tr>

<tr>
<td><code>msg</code></td>
<td>String (BBCode)</td>
<td></td>
</tr>

<tr>
<td><code>uid</code></td>
<td>Integer</td>
<td>Owner User Id</td>
</tr>

<tr>
<td><code>link</code></td>
<td>String (URL)</td>
<td>Notification URL</td>
</tr>

<tr>
<td><code>iid</code></td>
<td>Integer</td>
<td>Item Id</td>
</tr>

<tr>
<td><code>parent</code></td>
<td>Integer</td>
<td>Parent Item Id</td>
</tr>

<tr>
<td><code>seen</code></td>
<td>Integer (Boolean)</td>
<td>Whether the notification was read or not.</td>
</tr>

<tr>
<td><code>verb</code></td>
<td>String (URL)</td>
<td>[Activity Streams](http://activitystrea.ms) Verb URL</td>
</tr>

<tr>
<td><code>seen</code></td>
<td>Integer (Boolean)</td>
<td>Whether the notification was read or not.</td>
</tr>

<tr>
<td><code>otype</code></td>
<td>Enum</td>
<td>Subject type (`item`, `intro` or `mail`)</td>
</tr>

<tr>
<td><code>name_cache</code></td>
<td>String (HTML)</td>
<td>Full name of the contact subject</td>
</tr>

<tr>
<td><code>msg_cache</code></td>
<td>String (Plaintext)</td>
<td>Plaintext version of the notification text with a placeholder (`{0}`) for the subject contact's name.</td>
</tr>

<tr>
<td><code>timestamp</code></td>
<td>Integer</td>
<td>Unix timestamp</td>
</tr>

<tr>
<td><code>date_rel</code></td>
<td>String</td>
<td>Time since the note was posted, eg "1 hour ago"</td>
</tr>


<tr>
<td><code>msg_html</code></td>
<td>String (HTML)</td>
<td></td>
</tr>

<tr>
<td><code>msg_plain</code></td>
<td>String (Plaintext)</td>
<td></td>
</tr>

</tbody>
</table>

## Photo

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th>Description</th>
</tr>
</thead>
<tbody>

<tr>
<td><code>id</code></td>
<td>String</td>
<td>Resource ID (32 hex chars)</td>
</tr>

<tr>
<td><code>media-id</code></td>
<td>String (Integer) </td>
<td>ID used for attaching images to a Mastodon Post Status</td>
</tr>


<tr>
<td><code>created</code></td>
<td>String (Date)</td>
<td>Format <code>YYYY-MM-DD HH:MM:SS</code></td>
</tr>

<tr>
<td><code>edited</code></td>
<td>String (Date)</td>
<td>Format <code>YYYY-MM-DD HH:MM:SS</code></td>
</tr>

<tr>
<td><code>title</code></td>
<td>String</td>
<td></td>
</tr>

<tr>
<td><code>desc</code></td>
<td>String (Plaintext)</td>
<td>Picture caption</td>
</tr>

<tr>
<td><code>album</code></td>
<td>String</td>
<td>Album name</td>
</tr>

<tr>
<td><code>filename</code></td>
<td>String</td>
<td>Original image filename</td>
</tr>

<tr>
<td><code>type</code></td>
<td>String</td>
<td>MIME Type</td>
</tr>

<tr>
<td><code>height</code></td>
<td>Integer</td>
<td>Image height in pixels</td>
</tr>

<tr>
<td><code>width</code></td>
<td>Integer</td>
<td>Image width in pixels</td>
</tr>

<tr>
<td><code>profile</code></td>
<td>Integer</td>
<td>1 if it is a profile photo</td>
</tr>

<tr>
<td><code>allow_cid</code></td>
<td>String (ACL)</td>
<td>List of contact ids wrapped in angle brackets allowed to access the photo.</td>
</tr>

<tr>
<td><code>allow_gid</code></td>
<td>String (ACL)</td>
<td>List of contact circle ids wrapped in angle brackets allowed to access the photo.</td>
</tr>

<tr>
<td><code>deny_cid</code></td>
<td>String (ACL)</td>
<td>List of contact ids wrapped in angle brackets forbidden to access the photo.</td>
</tr>

<tr>
<td><code>deny_gid</code></td>
<td>String (ACL)</td>
<td>List of contact circle ids wrapped in angle brackets forbidden to access the photo.</td>
</tr>

<tr>
<td><code>link</code></td>
<td>Array of Strings (URL)</td>
<td>
URLs to the different scales indexed by scale number if no specific scale was requested.
Mutually exclusive with <code>data</code> <code>datasize</code>.
</td>
</tr>

<tr>
<td><code>scales</code></td>
<td>Array of Photo Scales</td>
<td>
List of the various resized versions of the Photo
</td>
</tr>

<tr>
<td><code>datasize</code></td>
<td>Integer</td>
<td>
Picture size in bytes if a single scale was requested.
Mutually exclusive with <code>link</code>.
</td>
</tr>

<tr>
<td><code>data</code></td>
<td>String</td>
<td>
Base64-encoded image data if a single scale was requested.
Mutually exclusive with <code>link</code>.
</td>
</tr>

<tr>
<td><code>friendica_activities</code></td>
<td><a href="help/API-Entities#Activities">Activities</a></td>
<td></td>
</tr>

<tr>
<td><code>friendica_comments</code></td>
<td>List of <a href="help/API-Entities#Item">Items</a></td>
<td></td>
</tr>

<tr>
<td><code>rights_mismatch</code></td>
<td>Boolean</td>
<td>True if the ACL differs between the picture and the associated item.</td>
</tr>

</tbody>
</table>

## Photo Scale

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th align="center">Nullable</th>
</tr>
</thead>
<tbody>

<tr>
<td><code>id</code></td>
<td>String (Integer)</td>
<td>Row ID of this photo scale</td>
</tr>

<tr>
<td><code>scale</code></td>
<td>Integer</td>
<td>Scale number</td>
</tr>

<tr>
<td><code>link</code></td>
<td>String (URL)</td>
<td>URL to this scale's image</td>
</tr>

<tr>
<td><code>height</code></td>
<td>Integer</td>
<td>Image height in pixels</td>
</tr>

<tr>
<td><code>width</code></td>
<td>Integer</td>
<td>Image width in pixels</td>
</tr>

<tr>
<td><code>size</code></td>
<td>Integer</td>
<td>Image size in bytes</td>
</tr>

</tbody>
</table>


## Photo List Item

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th>Description</th>
</tr>
</thead>
<tbody>

<tr>
<td><code>id</code></td>
<td>String</td>
<td>Resource ID (32 hex chars)</td>
</tr>

<tr>
<td><code>album</code></td>
<td>String</td>
<td>Album name</td>
</tr>

<tr>
<td><code>filename</code></td>
<td>String</td>
<td>Original image filename</td>
</tr>

<tr>
<td><code>type</code></td>
<td>String</td>
<td>MIME Type</td>
</tr>

<tr>
<td><code>created</code></td>
<td>String (Date)</td>
<td>Format <code>YYYY-MM-DD HH:MM:SS</code></td>
</tr>

<tr>
<td><code>edited</code></td>
<td>String (Date)</td>
<td>Format <code>YYYY-MM-DD HH:MM:SS</code></td>
</tr>

<tr>
<td><code>desc</code></td>
<td>String (Plaintext)</td>
<td>Picture caption</td>
</tr>

<tr>
<td><code>thumb</code></td>
<td>String (URL)</td>
<td>URL of the smallest scale version of the picture.</td>
</tr>

</tbody>
</table>


## Photo Album

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th>Description</th>
</tr>
</thead>
<tbody>

<tr>
<td><code>name</code></td>
<td>String</td>
<td>The name of the photo album</td>
</tr>

<tr>
<td><code>created</code></td>
<td>String (Date)</td>
<td>The creation date of the album. Format <code>YYYY-MM-DD HH:MM:SS</code></td>
</tr>

<tr>
<td><code>count</code></td>
<td>Integer</td>
<td>The number of images in the album</td>
</tr>

</tbody>
</table>

## Private message

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th>Description</th>
</tr>
</thead>
<tbody>

<tr>
<td><code>id</code></td>
<td>Integer</td>
<td></td>
</tr>

<tr>
<td><code>sender_id</code></td>
<td>Integer</td>
<td>Sender Contact Id</td>
</tr>

<tr>
<td><code>text</code></td>
<td>String</td>
<td>Can be HTML or plaintext depending on the API call parameter `getText`.</td>
</tr>

<tr>
<td><code>recipient_id</code></td>
<td>Integer</td>
<td>Recipient Contact Id</td>
</tr>

<tr>
<td><code>created_at</code></td>
<td>String (Date)</td>
<td>Ex: Wed May 23 06:01:13 +0000 2007</td>
</tr>

<tr>
<td><code>sender_screen_name</code></td>
<td>String</td>
<td></td>
</tr>

<tr>
<td><code>recipient_screen_name</code></td>
<td>String</td>
<td></td>
</tr>

<tr>
<td><code>sender</code></td>
<td><a href="help/API-Entities#Contact">Contact</a></td>
<td></td>
</tr>

<tr>
<td><code>recipient</code></td>
<td><a href="help/API-Entities#Contact">Contact</a></td>
<td></td>
</tr>

<tr>
<td><code>title</code></td>
<td>String</td>
<td>Empty if the API call parameter `getText` is empty or absent.</td>
</tr>

<tr>
<td><code>friendica_seen</code></td>
<td>Integer (Boolean)</td>
<td>Whether the private message has been read or not.</td>
</tr>

<tr>
<td><code>friendica_parent_uri</code></td>
<td>String</td>
<td></td>
</tr>

</tbody>
</table>

## Profile

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th>Description</th>
</tr>
</thead>
<tbody>

<tr>
<td><code>profile_id</code></td>
<td>Integer</td>
<td></td>
</tr>

<tr>
<td><code>profile_name</code></td>
<td>String</td>
<td></td>
</tr>

<tr>
<td><code>is_default</code></td>
<td>Boolean</td>
<td></td>
</tr>

<tr>
<td><code>hide_friends</code></td>
<td>Boolean</td>
<td>Whether the user chose to hide their contact list on their profile.</td>
</tr>

<tr>
<td><code>profile_photo</code></td>
<td>String (URL)</td>
<td>Largest size profile picture URL.</td>
</tr>

<tr>
<td><code>profile_thumb</code></td>
<td>String (URL)</td>
<td>Smallest size profile picture URL.</td>
</tr>

<tr>
<td><code>publish</code></td>
<td>Boolean</td>
<td>Whether the user chose to publish their profile in the local directory.</td>
</tr>

<tr>
<td><code>net_publish</code></td>
<td>Boolean</td>
<td>Whether the user chose to publish their profile in the global directory.</td>
</tr>

<tr>
<td><code>description</code></td>
<td>String</td>
<td></td>
</tr>

<tr>
<td><code>date_of_birth</code></td>
<td>String</td>
<td></td>
</tr>

<tr>
<td><code>address</code></td>
<td>String</td>
<td></td>
</tr>

<tr>
<td><code>city</code></td>
<td>String</td>
<td></td>
</tr>

<tr>
<td><code>region</code></td>
<td>String</td>
<td></td>
</tr>

<tr>
<td><code>postal_code</code></td>
<td>String</td>
<td></td>
</tr>

<tr>
<td><code>country</code></td>
<td>String</td>
<td></td>
</tr>

<tr>
<td><code>public_keywords</code></td>
<td>String</td>
<td>Comma-separated list of words meant to be displayed as hashtags.</td>
</tr>

<tr>
<td><code>private_keywords</code></td>
<td>String</td>
<td>Comma-separated list of words meant to be used for search only.</td>
</tr>

<tr>
<td><code>homepage</code></td>
<td>String (URL)</td>
<td></td>
</tr>

</tbody>
</table>

## Size

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th align="center">Nullable</th>
</tr>
</thead>
<tbody>
<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th align="center">Nullable</th>
</tr>
</thead>
<tbody>

<tr>
<td><code>w</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>h</code></td>
<td>Integer</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>resize</code></td>
<td>Enum (fit, crop)</td>
<td align="center">Yes</td>
</tr>

</tbody>
</table>


## Sizes

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th align="center">Nullable</th>
</tr>
</thead>
<tbody>

<tr>
<td><code>medium</code></td>
<td><a href="help/API-Entities#Size">Size</a></td>
<td align="center">No</td>
</tr>

<tr>
<td><code>large</code></td>
<td><a href="help/API-Entities#Size">Size</a></td>
<td align="center">Yes</td>
</tr>

<tr>
<td><code>thumb</code></td>
<td><a href="help/API-Entities#Size">Size</a></td>
<td align="center">Yes</td>
</tr>

<tr>
<td><code>small</code></td>
<td><a href="help/API-Entities#Size">Size</a></td>
<td align="center">Yes</td>
</tr>

</tbody>
</table>

## Symbol

Unused

## URL

<table class="table table-condensed table-striped table-bordered">
<thead>
<tr>
<th>Attribute</th>
<th>Type</th>
<th align="center">Nullable</th>
</tr>
</thead>
<tbody>

<tr>
<td><code>url</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>expanded_url</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>display_url</code></td>
<td>String (URL)</td>
<td align="center">No</td>
</tr>

<tr>
<td><code>indices</code></td>
<td>List of Integer</td>
<td align="center">No</td>
</tr>

</tbody>
</table>

## User Mention

Unused
