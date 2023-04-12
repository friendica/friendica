# Friendica API

* [主页](help)
  * [使用API](help/api)

## 概述

Friendica提供了以下特定的终点。

身份验证与[使用API](help/api#Authentication)中描述的相同。

## 实体

这些终点使用[Friendica API实体](help/API-Entities)。

## 终点

### GET api/friendica/events

返回当前登录用户的[事件](help/API-Entities#Event)实体列表。

#### 参数

- `since_id`：（可选）用于分页的最小事件ID
- `count`：返回的最大项目数，默认为20

### POST api/friendica/event_create

为当前登录用户创建一个新事件。

#### 参数

- `id`：（可选）事件ID，如果提供，则会修改事件
- `name`：事件名称（必填）
- `start_time`：事件开始时间（ISO），必填
- `end_time`：（可选）事件结束时间，如果未提供，则事件在开放状态
- `desc`：（可选）事件说明
- `place`：（可选）事件地点
- `publish`：（可选）创建事件消息
- `allow_cid`：（可选）允许私有事件的联系人ID的ACL格式化列表
- `allow_gid`：（可选）不允许私有事件的联系人ID的ACL格式化列表
- `deny_cid`：（可选）允许私有事件的组ID的ACL格式化列表
- `deny_gid`：（可选）不允许私有事件的组ID的ACL格式化列表

### POST api/friendica/event_delete

从日历中删除事件（不是消息）

#### 参数

- `id`：要删除的事件ID

### GET api/externalprofile/show

返回所提供配置文件URL的[联系人](help/API-Entities#Contact)实体。

#### 参数

- `profileurl`：配置文件URL

### GET api/statuses/public_timeline

返回此节点上发布的公共[项目](help/API-Entities#Item)列表。
相当于本地社区页面。

#### 参数

* `count`：每页的条目数（默认值：20）
* `page`：页码
* `since_id`：最小ID
* `max_id`：最大ID
* `exclude_replies`：不显示回复（默认值：false）
* `conversation_id`：显示给定对话的所有状态。
* `include_entities`：如果设置为“true”，则会显示图片和链接的实体（默认为false）。

#### 不支持的参数

* `trim_user`

### GET api/statuses/networkpublic_timeline

返回此节点知道的[Item](help/API-Entities#Item)列表。
相当于全球社区页面。

#### 参数

* `count`：每页的条目数（默认值：20）
* `page`：页码
* `since_id`：最小ID
* `max_id`：最大ID
* `exclude_replies`：不显示回复（默认值：false）
* `conversation_id`：显示给定对话的所有状态。
* `include_entities`：如果设置为“true”，则会显示图片和链接的实体（默认为false）。

### GET api/statuses/replies

#### 参数

* `count`：每页的条目数（默认值：20）
* `page`：页码
* `since_id`：最小ID
* `max_id`：最大ID
* `include_entities`：如果设置为“true”，则会显示图片和链接的实体（默认为false）。

#### 不支持的参数

* `include_rts`
* `trim_user`
* `contributor_details`

---

### GET api/conversation/show

非官方Twitter命令。显示与给定ID的所有直接回复（不包括原始帖子）。

#### 参数

* `id`：帖子的id
* `count`：每页的条目数（默认值：20）
* `page`：页码
* `since_id`：最小ID
* `max_id`：最大ID
* `include_entities`：如果设置为“true”，则会显示图片和链接的实体（默认为false）。

#### 不支持的参数

* `include_rts`
* `trim_user`
* `contributor_details`

### GET api/statusnet/conversation

[`api/conversation/show`](#GET+api%2Fconversation%2Fshow)的别名。

### GET api/statusnet/config

返回公共Friendica节点配置。

### GET api/gnusocial/config

[`api/statusnet/config`](#GET+api%2Fstatusnet%2Fconfig)的别名。

### GET api/statusnet/version

返回一个静态的 StatusNet 协议版本。

### GET api/gnusocial/version

[`api/statusnet/version`](#GET+api%2Fstatusnet%2Fversion)的别名。

---

### POST api/friendica/activity/[verb]

添加或删除项目的活动。
'verb'可以是以下之一：

* `like`
* `dislike`
* `attendyes`
* `attendno`
* `attendmaybe`

要删除一个活动，请在动词前面加上 "un"，例如："unlike" 或 "undislike"
Attend 动词会彼此禁用：这意味着如果将 "attendyes" 添加到一个项目中，则添加 "attendno" 将删除以前的 "attendyes"。
Attend 动词只应与与事件相关的项目一起使用（目前没有检查）。

## 参数

* `id`：项目ID

### 返回值

成功时：

json：
```"ok"```

xml：
```<ok>true</ok>```

失败时：

HTTP 400 BadRequest

---

### GET api/direct_messages

Twitter已废弃的接收私信列表端点。

#### 参数

* `count`：每页项目数（默认：20）
* `page`：页码
* `since_id`：最小ID
* `max_id`：最大ID
* `getText`：定义状态字段的格式。可以是“html”或“plain”
* `include_entities`：“true”显示图片和链接的实体（默认值：false）
* `friendica_verbose`：“true”启用不同的错误返回（默认值：“false”）

#### 不支持的参数

* `skip_status`

### GET api/direct_messages/all

返回所有[私信](help/API-Entities#Private+message)。

#### 参数

* `count`：每页项目数（默认：20）
* `page`：页码
* `since_id`：最小ID
* `max_id`：最大ID
* `getText`：定义状态字段的格式。可以是“html”或“plain”
* `friendica_verbose`：“true”启用不同的错误返回（默认值：“false”）

### GET api/direct_messages/conversation

返回单个私人消息会话的所有回复。返回[私信](help/API-Entities#Private+message)

#### 参数

* `count`：每页项目数（默认：20）
* `page`：页码
* `since_id`：最小ID
* `max_id`：最大ID
* `getText`：定义状态字段的格式。可以是“html”或“plain”
* `uri`：会话的URI
* `friendica_verbose`：“true”启用不同的错误返回（默认值：“false”）

### GET api/direct_messages/sent

Twitter已废弃的发送私信列表端点。返回[私信](help/API-Entities#Private+message)。

#### 参数

* `count`：每页项目数（默认：20）
* `page`：页码
* `since_id`：最小ID
* `max_id`：最大ID
* `getText`：定义状态字段的格式。可以是“html”或“plain”
* `include_entities`：“true”显示图片和链接的实体（默认值：false）
* `friendica_verbose`：“true”启用不同的错误返回（默认值：“false”）


### POST api/direct_messages/new

Twitter已废弃的私人消息提交端点。

#### 参数

* `user_id`：用户ID
* `screen_name`：屏幕名称（由于技术原因，此值不是唯一的！）
* `text`：消息
* `replyto`：被回复的私人消息ID
* `title`：私人消息的标题

### POST api/direct_messages/destroy

Twitter已废弃的删除直接消息端点。

#### 参数

* `id`：要删除的消息的ID
* `include_entities`：可选，目前尚未实现
* `friendica_parenturi`：可选，可用于增加安全性以仅删除预期的消息
* `friendica_verbose`："true"启用不同的错误返回（默认值："false"）

#### 返回值

成功时：

* 与Twitter API定义的JSON返回尚未实现
* 当friendica_verbose=true时：JSON返回{"result":"ok","message":"message deleted"}

出错时：

HTTP 400 BadRequest

* 当friendica_verbose=true时：不同的JSON返回 {"result":"error","message":"xyz"}

### GET api/friendica/direct_messages_setseen

#### 参数

* `id`：要设置为已读的消息的ID

#### 返回值

成功时：

* JSON返回 `{"result": "ok", "message": "message set to seen"}`

出错时：

* 不同的JSON返回 `{"result": "error", "message": "xyz"}`


### GET api/friendica/direct_messages_search (GET; AUTH)

返回匹配提供的搜索字符串的[私信](help/API-Entities#Private+message)。

#### 参数

* `searchstring`：API调用应在验证用户的所有消息的“body”字段中搜索“%searchstring%”（标题被忽略）的字符串
* `getText` （可选）： `plain`|`html`如果省略，标题将被加到私人消息对象的`text`属性的纯文本正文前面。
* `getUserObjects`（可选）： `true`|`false` 如果为“false”，私人消息对象的“sender”和“recipient”属性不存在。

#### 返回值

仅使用JSON进行测试，XML可能也可以。

成功时：

* JSON返回 `{"success":"true", "search_results": array of found messages}`
* JSOn返回 `{"success":"false", "search_results": "nothing found"}`

出错时：

* 不同的JSON返回 `{"result": "error", "message": "searchstring not specified"}`

---

### GET api/friendica/group_show

返回包含联系人的用户的所有或指定组作为数组。

#### 参数

* `gid`：可选，如果没有给出，API将返回用户的所有组

#### 返回值

数组：

* `name`：组的名称
* `gid`：组的ID
* `user`：[联系人](help/API-Entities#Contact)的数组

### POST api/friendica/group_create

创建以发布的联系人数组作为成员的组。

#### 参数

* `name`：要创建的组的名称

#### POST数据

JSON数据作为类似于[GET api/friendica/group_show](#GET+api%2Ffriendica%2Fgroup_show)结果的数组：

* `gid`
* `name`
* [联系人](help/API-Entities#Contact)列表

#### 返回值

数组：

* `success`：如果成功创建或重新激活则为true
* `gid`：已创建组的gid
* `name`：已创建组的名称
* `status`： “missing user”|“reactivated”|“ok”
* `wrong users`：未在联系人表中可用的用户数组

### POST api/friendica/group_update

使用发布的联系人数组作为成员更新群组（将所有群组成员发布到调用中；该函数将删除未发布的成员）。

### Parameters

* `gid`: 要更改的群组的id
* `name`: 要更改的群组的名称

#### POST 数据

JSON 数据，格式为数组（与 [GET api/friendica/group_show](#GET+api%2Ffriendica%2Fgroup_show) 的返回结果相同）：

* `gid`
* `name`
* [联系人](help/API-Entities#Contact)列表

#### 返回值

数组，包含以下信息：

* `success`：如果成功更新则为 true
* `gid`：更改后的群组 ID
* `name`：更改后的群组名称
* `status`：状态信息，可能为 "missing user" 或 "ok"
* `wrong users`：未在联系人表中找到的用户列表

### POST api/friendica/group_delete

删除指定的联系人群组；API 调用需要包括要删除的群组的正确 gid 和名称。

#### 参数

* `gid`：要删除的群组的id
* `name`：要删除的群组的名称

#### 返回值

数组，包含以下信息：

* `success`：如果成功删除则为 true
* `gid`：已删除群组的 ID
* `name`：已删除群组的名称
* `status`：状态信息，为 "deleted" 表示删除成功
* `wrong users`：空数组

---

### GET api/friendica/notifications

按时间顺序返回当前用户最新的 50 条 [通知](help/API-Entities#Notification)，其中未读的在顶部。

#### 参数

无

### POST api/friendica/notifications/seen

将通知标记为已读。

#### 参数

- `id`：要标记已读的通知的id

#### 返回值

如果该通知与某个项目有关联，则返回一个 [Item](help/API-Entities#Item)。

否则，返回一条成功状态信息：

* `success`（JSON）| `<status>success</status>`（XML）

---

### GET api/friendica/photo

返回一张 [照片](help/API-Entities#Photo)。

#### 参数

* `photo_id`：照片的资源id。
* `scale`：（可选）照片的缩放比例

返回给定资源的图片数据。
如果未提供 'scale'，则返回的数据包括每个比例的完整URL。
如果设置了 'scale'，则返回的数据包含基于 Base64 编码的图像数据。

可用的缩放比例值为：

* 0：原始大小或服务器设置的最大大小
* 1：宽度或高度小于等于 640 的图像
* 2：宽度或高度小于等于 320 的图像
* 3：缩略图 160x160
* 4：300x300的个人资料图片
* 5：80x80的个人资料图片
* 6：48x48的个人资料图片

用作个人资料图片的图像仅具有比例 4-6，其他图像只有 0-3。

#### 返回值

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

返回API用户的[照片列表项](help/API-Entities#Photo+List+Item)。

#### 返回值

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

别名为 [`api/friendica/photo/update`](#POST+api%2Ffriendica%2Fphoto%2Fupdate)

### POST api/friendica/photo/update

将0-2级别的数据保存到数据库中（有关规模说明，请参见上文）。调用将非公共条目添加到items表中，以启用经过身份验证的联系人评论 / 点赞照片。客户端应注意，更新的访问权限不会转移到联系人。即，如果在将可见性设置回受限组之前已经对公共照片进行了评论 / 点赞，则这些照片仍将公开可见。目前，最好通知用户更新权利并不是正确的方法，并提供添加具有新权利的新照片的解决方案。

#### 参数

* `photo_id`（可选）：如果指定，则将更新此ID的照片
* `media`（可选）：图像数据作为Base64，仅在指定了photo_id时才是可选的（新上传必须有媒体）
* `desc`（可选）：照片说明，在指定photo_id时更新
* `album`：要删除的相册名称（始终必要）
* `album_new`（可选）：如果指定了photo_id，则可以将其用于更改单个照片的相册
* `allow_cid` / `allow_gid` / `deny_cid` / `deny_gid`（可选）：
    - 在创建时：空字符串或省略=公共照片，以格式```<x><y><z>```指定私人照片
	- 在更新时：需要存在键并带有空值，以将私人照片更改为公共照片

#### 返回值

成功时：

* 上传新照片：JSON返回带有照片数据（请参见[GET api/friendica/photo](#GET+api%2Ffriendica%2Fphoto)）
* 照片已更新-更改照片数据：JSON返回带有照片数据（请参见[GET api/friendica/photo](#GET+api%2Ffriendica%2Fphoto)）
* 照片已更新-更改信息：JSON返回 `{"result": "updated", "message":"Image id 'xyz' has been updated."}`
* 照片已更新-没有更改：JSON返回 `{"result": "cancelled","message": "Nothing to update for image id 'xyz'."}`

出现错误时：

* 403 FORBIDDEN：未经身份验证
* 400 BADREQUEST：“未指定相册名称”，“未提交媒体数据”，“照片不可用”，“acl数据无效”
* 500 INTERNALSERVERERROR：“图像大小超过PHP配置设置，文件被服务器拒绝”，
			“图像大小超过Friendica Config设置（上传大小：x）”，
			“无法处理图像数据”,
			“图像上传失败”,
			“未知错误-上传照片失败，请查看Friendica的日志以获取更多信息”,
			“未知错误-在数据库中更新照片条目失败”,
			“未知错误-上传或更新照片时出现此错误，不应该发生”

### POST api/friendica/photo/delete

删除具有指定ID的单个图像，不可逆->确保客户端要求用户确认此操作
将此照片的item表条目设置为deleted = 1。

#### 参数

* `photo_id`：要删除的照片的ID

#### 返回值

成功时：

* JSON返回 

```json
{
    "result": "deleted",
    "message": "photo with id 'xyz' has been deleted from server."
}
```

出现错误时：

* 403 FORBIDDEN：未经身份验证
* 400 BADREQUEST：“未指定photo_id”，“照片不可用”
* 500 INTERNALSERVERERROR：“删除照片时发生未知错误”，“删除项目时出现问题”

---

### POST api/friendica/photoalbum/delete

删除具有指定相册名称的所有图像，不可逆->确保客户端要求用户确认此操作。

#### 参数

* `album`：要删除的相册名称

#### 返回值

成功时：

* JSON返回:

```json
{
    "result": "deleted",
    "message": "album 'xyz' with all containing photos has been deleted."
}
```

出错时：

* 403禁止访问：如果未经身份验证。
* 400错误请求：“未指定相册名称”，“相册不可用”
* 500服务器内部错误：“删除项发生问题”，“未知错误-从数据库中删除失败”

### POST api/friendica/photoalbum/update

更改相册中所有照片的相册名称为 album_new。

#### 参数

* `album`：要更新的相册的名称
* `album_new`：相册的新名称

#### 返回值

成功时：

* JSON返回

```json
{
    "result": "updated",
    "message":"album 'abc' with all containing photos has been renamed to 'xyz'."
}
```

出错时：

* 403禁止访问：如果未经身份验证。
* 400错误请求：“未指定相册名称”，“未指定新相册名称”，“相册不可用”
* 500服务器内部错误：“未知错误-在数据库更新失败”

---

### GET api/friendica/profile/show

返回经过身份验证的用户的[Profile]（help / API-Entities＃Profile）数据。

#### 返回值

成功时：数组包括：

* `global_dir`：服务器设置中设置的全局目录的URL
* `friendica_owner`：经过身份验证的用户数据
* `profiles`：配置文件数据的数组

出错时：

HTTP 403禁止访问：当未提供身份验证时
HTTP 400错误请求：如果给定的profile_id不在数据库中或未分配给经过身份验证的用户

API返回数据中配置文件数据的一般说明：
- hide_friends：如果隐藏了好友，则为true
- profile_photo
- profile_thumb
- publish：如果发布到服务器的本地目录上，则为true
- net_publish：如果发布到global_dir，则为true
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
- custom_fields：公共自定义字段列表

---

## 废弃的终点

- POST api / statuses / mediap