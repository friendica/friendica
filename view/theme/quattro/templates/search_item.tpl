<div class="wall-item-decor">
	{{if $item.star}}<span class="icon s22 star {{$item.isstarred}}" id="starred-{{$item.id}}" title="{{$item.star.starred}}">{{$item.star.starred}}</span>{{/if}}
	{{if $item.lock}}<span class="icon s22 lock fakelink" onclick="lockview(event,{{$item.id}});" title="{{$item.lock}}">{{$item.lock}}</span>{{/if}}
	<img id="like-rotator-{{$item.id}}" class="like-rotator" src="images/rotator.gif" alt="{{$item.wait}}" title="{{$item.wait}}" style="display: none;" />
</div>

<div class="wall-item-container {{$item.indent}}">
	<div class="wall-item-item">
		<div class="wall-item-info">
			<div class="contact-photo-wrapper"
				onmouseover="if (typeof t{{$item.id}} != 'undefined') clearTimeout(t{{$item.id}}); openMenu('wall-item-photo-menu-button-{{$item.id}}')"
				onmouseout="t{{$item.id}}=setTimeout('closeMenu(\'wall-item-photo-menu-button-{{$item.id}}\'); closeMenu(\'wall-item-photo-menu-{{$item.id}}\');',200)">
				<a href="{{$item.profile_url}}" target="redir" title="{{$item.linktitle}}" class="wall-item-photo-link" id="wall-item-photo-link-{{$item.id}}">
					<img src="{{$item.thumb}}" class="contact-photo{{$item.sparkle}}" id="wall-item-photo-{{$item.id}}" alt="{{$item.name}}" />
				</a>
				<a href="#" rel="#wall-item-photo-menu-{{$item.id}}" class="contact-photo-menu-button icon s16 menu" id="wall-item-photo-menu-button-{{$item.id}}">menu</a>
				<ul class="wall-item-menu menu-popup" id="wall-item-photo-menu-{{$item.id}}">
				{{$item.item_photo_menu}}
				</ul>

			</div>
			<div class="wall-item-location">{{$item.location}}</div>
		</div>
		<div class="wall-item-content">
			{{if $item.title}}<h2><a href="{{$item.plink.href}}">{{$item.title}}</a></h2>{{/if}}
			{{$item.body}}
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="wall-item-links">
		</div>
		<div class="wall-item-tags">
			{{foreach $item.tags as $tag}}
				<span class='tag'>{{$tag}}</span>
			{{/foreach}}
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="">
			{{if $item.plink}}<a class="icon s16 link" title="{{$item.plink.title}}" href="{{$item.plink.href}}">{{$item.plink.title}}</a>{{/if}}
		</div>
		<div class="wall-item-actions">
			<div class="wall-item-actions-author">
				<a href="{{$item.profile_url}}" target="redir" title="{{$item.linktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.sparkle}}">{{$item.name}}</span></a> <span class="wall-item-ago" title="{{$item.localtime}}">{{$item.ago}}</span>
			</div>

			<div class="wall-item-actions-social">
			{{if $item.star}}
				<a href="#" id="star-{{$item.id}}" onclick="dostar({{$item.id}}); return false;"  class="{{$item.star.classdo}}"  title="{{$item.star.do}}">{{$item.star.do}}</a>
				<a href="#" id="unstar-{{$item.id}}" onclick="dostar({{$item.id}}); return false;"  class="{{$item.star.classundo}}"  title="{{$item.star.undo}}">{{$item.star.undo}}</a>
				<a href="#" id="tagger-{{$item.id}}" onclick="itemTag({{$item.id}}); return false;" class="{{$item.star.classtagger}}" title="{{$item.star.tagger}}">{{$item.star.tagger}}</a>
			{{/if}}

			{{if $item.vote}}
				<a href="#" id="like-{{$item.id}}"{{if $item.responses.like.self}} class="active{{/if}}" title="{{$item.vote.like.0}}" onclick="dolike({{$item.id}},'like'); return false">{{$item.vote.like.1}}</a>
				<a href="#" id="dislike-{{$item.id}}"{{if $item.responses.dislike.self}} class="active{{/if}}" title="{{$item.vote.dislike.0}}" onclick="dolike({{$item.id}},'dislike'); return false">{{$item.vote.dislike.1}}</a>
			{{/if}}

			{{if $item.vote.share}}
				<a href="#" id="share-{{$item.id}}" title="{{$item.vote.share.0}}" onclick="jotShare({{$item.id}}); return false">{{$item.vote.share.1}}</a>
			{{/if}}
			</div>

			<div class="wall-item-actions-tools">

				{{if $item.drop.pagedrop}}
					<input type="checkbox" title="{{$item.drop.select}}" name="itemselected[]" class="item-select" value="{{$item.id}}" />
				{{/if}}
				{{if $item.drop.dropping}}
					<a href="item/drop/{{$item.id}}" onclick="return confirmDelete();" class="icon delete s16" title="{{$item.drop.delete}}">{{$item.drop.delete}}</a>
				{{/if}}
				{{if $item.edpost}}
					<a class="icon edit s16" href="{{$item.edpost.0}}" title="{{$item.edpost.1}}"></a>
				{{/if}}
			</div>

		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="wall-item-links"></div>
		<div class="wall-item-like" id="wall-item-like-{{$item.id}}">{{$item.like}}</div>
		<div class="wall-item-dislike" id="wall-item-dislike-{{$item.id}}">{{$item.dislike}}</div>
		{{if $item.conv}}
		<div class="wall-item-conv" id="wall-item-conv-{{$item.id}}" >
			<a href='{{$item.conv.href}}' id='context-{{$item.id}}' title='{{$item.conv.title}}'>{{$item.conv.title}}</a>
		</div>
		{{/if}}
	</div>


</div>

