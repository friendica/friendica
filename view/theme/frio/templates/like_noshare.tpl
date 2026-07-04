{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id="wall-item-like-buttons-{{$id}}">
	<button type="button"
	        class="btn btn-default button-likes{{if $responses.like.self}} active" aria-pressed="true{{/if}}" id="like-{{$id}}"
	        title="{{$like_title}}"
	        onclick="doActivityItemAction({{$id}}, 'like'{{if $responses.like.self}}, true{{/if}});">
		<i class="ri ri-thumb-up-line" aria-hidden="true"></i>&nbsp;{{$like}}
	</button>
	{{if !$hide_dislike}}
	<button type="button"
	        class="btn btn-default button-likes{{if $responses.dislike.self}} active" aria-pressed="true{{/if}}"
	        id="dislike-{{$id}}"
	        title="{{$dislike_title}}"
	        onclick="doActivityItemAction({{$id}}, 'dislike'{{if $responses.dislike.self}}, true{{/if}});">
                <i class="ri ri-thumb-down-line" aria-hidden="true"></i>&nbsp;{{$dislike}}
	</button>
	{{/if}}
</div>
