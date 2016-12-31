
{{if $item.comment_firstcollapsed}}
	<div class="hide-comments-outer">
	<span id="hide-comments-total-{{$item.id}}" class="hide-comments-total">{{$item.num_comments}}</span> <span id="hide-comments-{{$item.id}}" class="hide-comments fakelink" onclick="showHideComments({{$item.id}});">{{$item.hide_text}}</span>
	</div>
	<div id="collapsed-comments-{{$item.id}}" class="collapsed-comments" style="display: none;">
{{/if}}
<div id="tread-wrapper-{{$item.id}}" class="tread-wrapper {{$item.toplevel}} {{if $item.toplevel}} h-entry {{else}} u-comment h-cite {{/if}}">
<a name="{{$item.id}}" ></a>
{{*<!--<div class="wall-item-outside-wrapper {{$item.indent}}{{$item.previewing}} wallwall" id="wall-item-outside-wrapper-{{$item.id}}" >-->*}}
	<div class="wall-item-content-wrapper {{$item.indent}} {{$item.previewing}}{{if $item.owner_url}} wallwall{{/if}}" id="wall-item-content-wrapper-{{$item.id}}" >
		<div class="wall-item-info{{if $item.owner_url}} wallwall{{/if}}" id="wall-item-info-{{$item.id}}">
			{{if $item.owner_url}}
			<div class="wall-item-photo-wrapper wwto" id="wall-item-ownerphoto-wrapper-{{$item.id}}" >
				<a href="{{$item.owner_url}}" target="redir" title="{{$item.olinktitle}}" class="wall-item-photo-link" id="wall-item-ownerphoto-link-{{$item.id}}">
				<img src="{{$item.owner_photo}}" class="wall-item-photo{{$item.osparkle}}" id="wall-item-ownerphoto-{{$item.id}}" style="height: 80px; width: 80px;" alt="{{$item.owner_name}}" onError="this.src='../../../images/person-48.jpg';" />
				</a>
			</div>
			<div class="wall-item-arrowphoto-wrapper" ><img src="images/larrow.gif" alt="{{$item.wall}}" /></div>
			{{/if}}
			<div class="wall-item-photo-wrapper wwfrom p-author h-card" id="wall-item-photo-wrapper-{{$item.id}}"
				onmouseover="if (typeof t{{$item.id}} != 'undefined') clearTimeout(t{{$item.id}}); openMenu('wall-item-photo-menu-button-{{$item.id}}')"
                onmouseout="t{{$item.id}}=setTimeout('closeMenu(\'wall-item-photo-menu-button-{{$item.id}}\'); closeMenu(\'wall-item-photo-menu-{{$item.id}}\');',200)">
				<a href="{{$item.profile_url}}" target="redir" title="{{$item.linktitle}}" class="wall-item-photo-link u-url" id="wall-item-photo-link-{{$item.id}}">
				<img src="{{$item.thumb}}" class="wall-item-photo{{$item.sparkle}} p-name u-photo" id="wall-item-photo-{{$item.id}}" style="height: 80px; width: 80px;" alt="{{$item.name}}" onError="this.src='../../../images/person-48.jpg';" />
				</a>
				<span onclick="openClose('wall-item-photo-menu-{{$item.id}}');" class="fakelink wall-item-photo-menu-button" id="wall-item-photo-menu-button-{{$item.id}}">menu</span>
{{*<!--                <div class="wall-item-photo-menu" id="wall-item-photo-menu-{{$item.id}}">-->*}}
                    <ul class="wall-item-photo-menu" id="wall-item-photo-menu-{{$item.id}}">
                        {{$item.item_photo_menu}}
                    </ul>
{{*<!--                </div>-->*}}

			</div>
			{{*<!--<div class="wall-item-photo-end"></div>-->*}}
			<div class="wall-item-wrapper" id="wall-item-wrapper-{{$item.id}}" >
				{{if $item.lock}}{{*<!--<div class="wall-item-lock">-->*}}<img src="images/lock_icon.gif" class="wall-item-lock lockview" alt="{{$item.lock}}" onclick="lockview(event,{{$item.id}});" />{{*<!--</div>-->*}}
				{{else}}<div class="wall-item-lock"></div>{{/if}}
				<div class="wall-item-location" id="wall-item-location-{{$item.id}}">{{$item.location}}</div>
			</div>
		</div>
		{{*<!--<div class="wall-item-author">-->*}}
				<a href="{{$item.profile_url}}" target="redir" title="{{$item.linktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.sparkle}}" id="wall-item-name-{{$item.id}}" >{{$item.name}}</span></a>{{if $item.owner_url}} {{$item.to}} <a href="{{$item.owner_url}}" target="redir" title="{{$item.olinktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.osparkle}}" id="wall-item-ownername-{{$item.id}}">{{$item.owner_name}}</span></a> {{$item.vwall}}{{/if}}<br />
				<div class="wall-item-ago"  id="wall-item-ago-{{$item.id}}" title="{{$item.localtime}}" ><a href="display/{{$user.nickname}}/{{$item.id}}"><time class="dt-published" datetime="{{$item.localtime}}">{{$item.ago}}</time></a></div>
		{{*<!--</div>-->*}}
		<div class="wall-item-content" id="wall-item-content-{{$item.id}}" >
			<div class="wall-item-title p-name" id="wall-item-title-{{$item.id}}">{{$item.title}}</div>
			{{*<!--<div class="wall-item-title-end"></div>-->*}}
			<div class="wall-item-body" id="wall-item-body-{{$item.id}}" ><span class="e-content">{{$item.body}}</span>
					{{*<!--<div class="body-tag">-->*}}
						{{foreach $item.tags as $tag}}
							<span class='body-tag tag'>{{$tag}}</span>
						{{/foreach}}
					{{*<!--</div>-->*}}
			{{if $item.has_cats}}
			<div class="categorytags">{{$item.txt_cats}} {{foreach $item.categories as $cat}}<span class="p-category">{{$cat.name}}</span> <a href="{{$cat.removeurl}}" title="{{$remove}}">[{{$remove}}]</a> {{if $cat.last}}{{else}}, {{/if}}{{/foreach}}
			</div>
			{{/if}}

			{{if $item.has_folders}}
			<div class="filesavetags">{{$item.txt_folders}} {{foreach $item.folders as $cat}}<span class="p-category">{{$cat.name}}</span> <a href="{{$cat.removeurl}}" title="{{$remove}}">[{{$remove}}]</a> {{if $cat.last}}{{else}}, {{/if}}{{/foreach}}
			</div>
			{{/if}}
			</div>
		</div>
		<div class="wall-item-tools" id="wall-item-tools-{{$item.id}}">
			{{if $item.vote}}
			<div class="wall-item-like-buttons" id="wall-item-like-buttons-{{$item.id}}">
				<a href="#" class="tool like{{if $item.responses.like.self}} active{{/if}}" title="{{$item.vote.like.0}}" onclick="dolike({{$item.id}},'like'); return false"></a>
				{{if $item.vote.dislike}}
				<a href="#" class="tool dislike{{if $item.responses.dislike.self}} active{{/if}}" title="{{$item.vote.dislike.0}}" onclick="dolike({{$item.id}},'dislike'); return false"></a>
				{{/if}}
				{{if $item.vote.share}}<a href="#" class="tool recycle wall-item-share-buttons" title="{{$item.vote.share.0}}" onclick="jotShare({{$item.id}}); return false"></a>{{/if}}
				<img id="like-rotator-{{$item.id}}" class="like-rotator" src="images/rotator.gif" alt="{{$item.wait}}" title="{{$item.wait}}" style="display: none;" />
			</div>
			{{/if}}
			{{if $item.plink}}
				{{*<!--<div class="wall-item-links-wrapper">-->*}}<a href="{{$item.plink.href}}" title="{{$item.plink.title}}" target="external-link" class="wall-item-links-wrapper icon remote-link{{$item.sparkle}} u-url"></a>{{*<!--</div>-->*}}
			{{/if}}
			{{if $item.edpost}}
				<a class="editpost tool pencil" href="{{$item.edpost.0}}" title="{{$item.edpost.1}}"></a>
			{{/if}}

			{{if $item.star}}
			<a href="#" id="starred-{{$item.id}}" onclick="dostar({{$item.id}}); return false;" class="star-item tool {{$item.isstarred}}" title="{{$item.star.toggle}}"></a>
			{{/if}}
			{{if $item.tagger}}
			<a href="#" id="tagger-{{$item.id}}" onclick="itemTag({{$item.id}}); return false;" class="tag-item tool tagged" title="{{$item.tagger.add}}"></a>
			{{/if}}
			{{if $item.filer}}
			<a href="#" id="filer-{{$item.id}}" onclick="itemFiler({{$item.id}}); return false;" class="filer-item filer-icon" title="{{$item.filer}}"></a>
			{{/if}}

			{{if $item.isevent}}
				<a href="#" id="attendyes-{{$item.id}}" title="{{$item.attend.0}}" onclick="dolike({{$item.id}},'attendyes'); return false;" class="event-item event-icon event-attend-icon{{if $item.responses.attendyes.self}} active{{/if}}"></a>
				<a href="#" id="attendno-{{$item.id}}" title="{{$item.attend.1}}" onclick="dolike({{$item.id}},'attendno'); return false;"  class="event-item event-icon event-dontattend-icon{{if $item.responses.attendno.self}} active{{/if}}"></a>
				<a href="#" id="attendmaybe-{{$item.id}}" title="{{$item.attend.2}}" onclick="dolike({{$item.id}},'attendmaybe'); return false;"  class="event-item event-icon event-maybeattend-icon{{if $item.responses.attendmaybe.self}} active{{/if}}"></a>
			{{/if}}


			{{*<!--<div class="wall-item-delete-wrapper" id="wall-item-delete-wrapper-{{$item.id}}" >-->*}}
				{{if $item.drop.dropping}}<a href="item/drop/{{$item.id}}" onclick="return confirmDelete();" class="wall-item-delete-wrapper icon drophide" title="{{$item.drop.delete}}" id="wall-item-delete-wrapper-{{$item.id}}" {{*onmouseover="imgbright(this);" onmouseout="imgdull(this);" *}}></a>{{/if}}
			{{*<!--</div>-->*}}
				{{if $item.drop.pagedrop}}<input type="checkbox" onclick="checkboxhighlight(this);" title="{{$item.drop.select}}" class="item-select" name="itemselected[]" value="{{$item.id}}" />{{/if}}
			{{*<!--<div class="wall-item-delete-end"></div>-->*}}


		</div>
	</div>
	{{*<!--<div class="wall-item-wrapper-end"></div>-->*}}
	{{if $item.responses}}
		{{foreach $item.responses as $verb=>$response}}
			<div class="wall-item-response {{$item.indent}}" id="wall-item-{{$verb}}-{{$item.id}}">{{$response.output}}</div>
		{{/foreach}}
	{{/if}}

	{{if $item.threaded}}
	{{if $item.comment}}
	{{*<!--<div class="wall-item-comment-wrapper {{$item.indent}}" >-->*}}
		{{$item.comment}}
	{{*<!--</div>-->*}}
	{{/if}}
	{{/if}}

{{*<!--<div class="wall-item-outside-wrapper-end {{$item.indent}}" ></div>-->*}}
{{*<!--</div>-->*}}
{{foreach $item.children as $child}}
	{{include file="{{$child.template}}" item=$child}}
{{/foreach}}

{{if $item.flatten}}
{{*<!--<div class="wall-item-comment-wrapper" >-->*}}
	{{$item.comment}}
{{*<!--</div>-->*}}
{{/if}}
</div>
{{if $item.comment_lastcollapsed}}</div>{{/if}}

