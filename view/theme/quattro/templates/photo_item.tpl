<div class="wall-item-container {{$indent}}">
	<div class="wall-item-item">
		<div class="wall-item-info">
			<div class="contact-photo-wrapper">
				<a href="{{$profile_url}}" target="redir" title="{{$linktitle}}" class="wall-item-photo-link" id="wall-item-photo-link-{{$id}}">
					<img src="{{$thumb}}" class="contact-photo{{$sparkle}}" id="wall-item-photo-{{$id}}" alt="{{$name}}" />
				</a>
			</div>
			<div class="wall-item-location">{{$location}}</div>
		</div>
		<div class="wall-item-content">
			{{if $title}}<h2><a href="{{$plink.href}}">{{$title}}</a></h2>{{/if}}
			{{$body}}
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="wall-item-links">
		</div>
		<div class="wall-item-tags">
			{{foreach $tags as $tag}}
				<span class='tag'>{{$tag}}</span>
			{{/foreach}}
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="">
			{{if $plink}}<a class="icon s16 link" title="{{$plink.title}}" href="{{$plink.href}}">{{$plink.title}}</a>{{/if}}
		</div>
		<div class="wall-item-actions">
			<div class="wall-item-actions-author">
				<a href="{{$profile_url}}" target="redir" title="{{$linktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$sparkle}}">{{$name}}</span></a> <span class="wall-item-ago" title="{{$localtime}}">{{$ago}}</span>
			</div>

			<div class="wall-item-actions-social">
			{{if $star}}
				<a href="#" id="star-{{$id}}" onclick="dostar({{$id}}); return false;"  class="{{$star.classdo}}"  title="{{$star.do}}">{{$star.do}}</a>
				<a href="#" id="unstar-{{$id}}" onclick="dostar({{$id}}); return false;"  class="{{$star.classundo}}"  title="{{$star.undo}}">{{$star.undo}}</a>
				<a href="#" id="tagger-{{$id}}" onclick="itemTag({{$id}}); return false;" class="{{$star.classtagger}}" title="{{$star.tagger}}">{{$star.tagger}}</a>
			{{/if}}

			{{if $vote}}
				<a href="#" id="like-{{$id}}"{{if $item.responses.like.self}} class="active{{/if}}" title="{{$vote.like.0}}" onclick="dolike({{$id}},'like'); return false">{{$vote.like.1}}</a>
				<a href="#" id="dislike-{{$id}}"{{if $item.responses.dislike.self}} class="active{{/if}}" title="{{$vote.dislike.0}}" onclick="dolike({{$id}},'dislike'); return false">{{$vote.dislike.1}}</a>
			{{/if}}

			{{if $vote.share}}
				<a href="#" id="share-{{$id}}" title="{{$vote.share.0}}" onclick="jotShare({{$id}}); return false">{{$vote.share.1}}</a>
			{{/if}}
			</div>

			<div class="wall-item-actions-tools">

				{{if $drop.pagedrop}}
					<input type="checkbox" title="{{$drop.select}}" name="itemselected[]" class="item-select" value="{{$id}}" />
				{{/if}}
				{{if $drop.dropping}}
					<a href="item/drop/{{$id}}" onclick="return confirmDelete();" class="icon delete s16" title="{{$drop.delete}}">{{$drop.delete}}</a>
				{{/if}}
				{{if $edpost}}
					<a class="icon edit s16" href="{{$edpost.0}}" title="{{$edpost.1}}"></a>
				{{/if}}
			</div>

		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="wall-item-links"></div>
		<div class="wall-item-like" id="wall-item-like-{{$id}}">{{$like}}</div>
		<div class="wall-item-dislike" id="wall-item-dislike-{{$id}}">{{$dislike}}</div>
		{{if $conv}}
		<div class="wall-item-conv" id="wall-item-conv-{{$id}}" >
			<a href='{{$conv.href}}' id='context-{{$id}}' title='{{$conv.title}}'>{{$conv.title}}</a>
		</div>
		{{/if}}
	</div>


</div>

