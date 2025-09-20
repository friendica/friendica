{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="photo-album-edit-wrapper">
	<form name="photo-album-edit-form" id="photo-album-edit-form" action="photos/{{$nickname}}/album/{{$hexalbum}}" method="post">
		<label id="photo-album-edit-name-label" for="photo-album-edit-name">{{$nametext}}</label>
		<div class="photo-album-edit-name">
			<input class="form-control" type="text" size="64" name="albumname" value="{{$album}}" id="photo-album-edit-name">
		</div>

		<div id="photo-album-edit-submit">
			<input class="btn-primary btn btn-small" id="photo-album-edit-submit" type="submit" name="submit" value="{{$submit}}" />
		</div>
	</form>
</div>
