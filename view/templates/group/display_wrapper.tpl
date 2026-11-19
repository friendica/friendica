{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper group-display-wrapper">
	{{if $header}}{{include file="group/header.tpl" header=$header}}{{/if}}
	{{$content nofilter}}
</div>
