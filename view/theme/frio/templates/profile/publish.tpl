{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id="profile-publish-wrapper">
	<div id="profile-publish-wrapper-{{$instance}}" class="field">
		<div class="checkbox">
				<input type="checkbox" id="profile-publish-{{$instance}}"  name="profile_publish_{{$instance}}" value="1" />
				<label id="profile-publish-label-{{$instance}}" for="profile-publish-{{$instance}}">
					{{$pubdesc}}
				</label>
		</div>
		<div id="profile-publish-end-{{$instance}}"></div>
	</div>
</div>
