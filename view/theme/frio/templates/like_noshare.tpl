
<div class="wall-item-actions" id="wall-item-like-buttons-{{$id}}">
	<button type="button"
	        class="btn-link button-likes{{if $responses.like.self}} active" aria-pressed="true{{/if}}" id="like-{{$id}}"
	        title="{{$like_title}}"
	        onclick="doActivityItemAction({{$id}}, 'like'{{if $responses.like.self}}, true{{/if}});">
		<i class="fa fa-thumbs-up" aria-hidden="true"></i>&nbsp;{{$like}}
	</button>
	{{if !$hide_dislike}}
		<span class="icon-padding"> </span>
	<button type="button"
	        class="btn-link button-likes{{if $responses.dislike.self}} active" aria-pressed="true{{/if}}"
	        id="dislike-{{$id}}"
	        title="{{$dislike_title}}"
	        onclick="doActivityItemAction({{$id}}, 'dislike'{{if $responses.dislike.self}}, true{{/if}});">
                <i class="fa fa-thumbs-down" aria-hidden="true"></i>&nbsp;{{$dislike}}
	</button>
	{{/if}}
	<img id="like-rotator-{{$id}}" class="like-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait}}" style="display: none;" />
</div>
