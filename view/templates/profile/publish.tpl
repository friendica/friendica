{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id="profile-publish-wrapper-{{$instance}}" class="checkbox">
	<input type="checkbox" name="profile_publish_{{$instance}}" id="profile-publish-{{$instance}}" checked value="1" />
	<label id="profile-publish-label-{{$instance}}" for="profile-publish-{{$instance}}">
		{{$pubdesc}}
	</label>
	<div id="profile-publish-end-{{$instance}}"></div>
</div>
