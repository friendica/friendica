{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id="photo-album-edit-wrapper">
<form name="photo-album-edit-form" id="photo-album-edit-form" action="photos/{{$nickname}}/album/{{$hexalbum}}" method="post">


<label id="photo-album-edit-name-label" for="photo-album-edit-name">{{$nametext}}</label>
<input type="text" name="albumname" value="{{$album}}">

<div id="photo-album-edit-name-end"></div>

<input id="photo-album-edit-submit" type="submit" name="submit" value="{{$submit}}" />

</form>
</div>
<div id="photo-album-edit-end"></div>
