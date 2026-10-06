{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="deck-host" data-config="{{$config}}">
	<div id="deck-toolbar">
		<button type="button" class="btn btn-primary" id="deck-new-post" data-compose-url="{{$compose_url}}" title="{{$new_post}}" aria-label="{{$new_post}}">
			<i class="ri-edit-line" aria-hidden="true"></i>
		</button>
		<div class="dropdown">
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{$add}}" aria-label="{{$add}}">
				<i class="ri-add-line" aria-hidden="true"></i>
			</button>
			<ul class="dropdown-menu" id="deck-add-menu"></ul>
		</div>
	</div>
	<div id="deck-scroller"></div>
</div>

<div class="modal fade" id="deck-compose-modal" tabindex="-1" role="dialog" aria-label="{{$new_post}}">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="{{$close}}"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">{{$new_post}}</h4>
			</div>
			<div class="modal-body">
				<iframe id="deck-compose-frame" title="{{$new_post}}"></iframe>
			</div>
		</div>
	</div>
</div>
