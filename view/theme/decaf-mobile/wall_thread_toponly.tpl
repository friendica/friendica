<!--{{if $item.comment_firstcollapsed}}
	<div class="hide-comments-outer">
	<span id="hide-comments-total-$item.id" class="hide-comments-total">$item.num_comments</span> <span id="hide-comments-$item.id" class="hide-comments fakelink" onclick="showHideComments($item.id);">$item.hide_text</span>
	</div>
	<div id="collapsed-comments-$item.id" class="collapsed-comments" style="display: none;">
{{endif}}-->
<div id="tread-wrapper-$item.id" class="tread-wrapper $item.toplevel">
<a name="$item.id" ></a>
	<div class="wall-item-content-wrapper $item.indent" id="wall-item-content-wrapper-$item.id" >
		<div class="wall-item-info{{ if $item.owner_url }} wallwall{{ endif }}" id="wall-item-info-$item.id">
			{{ if $item.owner_url }}
			<div class="wall-item-photo-wrapper wwto" id="wall-item-ownerphoto-wrapper-$item.id" >
				<a href="$item.owner_url" target="redir" title="$item.olinktitle" class="wall-item-photo-link" id="wall-item-ownerphoto-link-$item.id">
				<img src="$item.owner_photo" class="wall-item-photo$item.osparkle" id="wall-item-ownerphoto-$item.id" style="height: 80px; width: 80px;" alt="$item.owner_name" onError="this.src='../../../images/person-48.jpg';" />
				</a>
			</div>
			<div class="wall-item-arrowphoto-wrapper" ><img src="images/larrow.gif" alt="$item.wall" /></div>
			{{ endif }}
				<a href="$item.profile_url" target="redir" title="$item.linktitle" class="wall-item-photo-link" id="wall-item-photo-link-$item.id">
				<img src="$item.thumb" class="wall-item-photo$item.sparkle" id="wall-item-photo-$item.id" style="height: 80px; width: 80px;" alt="$item.name" onError="this.src='../../../images/person-48.jpg';" />
				</a>

			<div class="wall-item-wrapper" id="wall-item-wrapper-$item.id" >
				{{ if $item.lock }}<img src="images/lock_icon.gif" class="wall-item-lock lockview" alt="$item.lock" {#onclick="lockview(event,$item.id);"#} />
				{{ else }}<div class="wall-item-lock"></div>{{ endif }}	
				<div class="wall-item-location" id="wall-item-location-$item.id">$item.location</div>
			</div>
		</div>
				<a href="$item.profile_url" target="redir" title="$item.linktitle" class="wall-item-name-link"><span class="wall-item-name$item.sparkle" id="wall-item-name-$item.id" >$item.name</span></a>{{ if $item.owner_url }} $item.to <a href="$item.owner_url" target="redir" title="$item.olinktitle" class="wall-item-name-link"><span class="wall-item-name$item.osparkle" id="wall-item-ownername-$item.id">$item.owner_name</span></a> $item.vwall{{ endif }}<br />
				<div class="wall-item-ago"  id="wall-item-ago-$item.id">$item.ago</div>				
		<div class="wall-item-content" id="wall-item-content-$item.id" >
			<div class="wall-item-title" id="wall-item-title-$item.id">$item.title</div>
			<div class="wall-item-body" id="wall-item-body-$item.id" >$item.body
						{{ for $item.tags as $tag }}
							<span class='body-tag tag'>$tag</span>
						{{ endfor }}
			{{ if $item.has_cats }}
			<div class="categorytags">$item.txt_cats {{ for $item.categories as $cat }}$cat.name <a href="$cat.removeurl" title="$remove">[$remove]</a> {{ if $cat.last }}{{ else }}, {{ endif }}{{ endfor }}
			</div>
			{{ endif }}

			{{ if $item.has_folders }}
			<div class="filesavetags">$item.txt_folders {{ for $item.folders as $cat }}$cat.name <a href="$cat.removeurl" title="$remove">[$remove]</a> {{ if $cat.last }}{{ else }}, {{ endif }}{{ endfor }}
			</div>
			{{ endif }}
			</div>
		</div>
		<div class="wall-item-tools" id="wall-item-tools-$item.id">
			{{ if $item.vote }}
			<div class="wall-item-like-buttons" id="wall-item-like-buttons-$item.id">
				<a href="like/$item.id?verb=like&return=$return_path#$item.id" class="icon like" title="$item.vote.like.0" ></a>
				{{ if $item.vote.dislike }}
				<a href="like/$item.id?verb=dislike&return=$return_path#$item.id" class="icon dislike" title="$item.vote.dislike.0" ></a>
				{{ endif }}
				{#<!--{{ if $item.vote.share }}<a href="#" class="icon recycle wall-item-share-buttons" title="$item.vote.share.0" onclick="jotShare($item.id); return false"></a>{{ endif }}-->#}
				<img id="like-rotator-$item.id" class="like-rotator" src="images/rotator.gif" alt="$item.wait" title="$item.wait" style="display: none;" />
			</div>
			{{ endif }}
			{{ if $item.plink }}
				<a href="$item.plink.href" title="$item.plink.title" target="external-link" class="wall-item-links-wrapper icon remote-link$item.sparkle"></a>
			{{ endif }}
			{{ if $item.edpost }}
				<a class="editpost icon pencil" href="$item.edpost.0" title="$item.edpost.1"></a>
			{{ endif }}
			 
			{{ if $item.star }}
			<a href="starred/$item.id?return=$return_path#$item.id" id="starred-$item.id" class="star-item icon $item.isstarred" title="$item.star.toggle"></a>
			{{ endif }}
			{#<!--{{ if $item.tagger }}
			<a href="#" id="tagger-$item.id" onclick="itemTag($item.id); return false;" class="tag-item icon tagged" title="$item.tagger.add"></a>
			{{ endif }}
			{{ if $item.filer }}
			<a href="#" id="filer-$item.id" onclick="itemFiler($item.id); return false;" class="filer-item filer-icon" title="$item.filer"></a>
			{{ endif }}			-->#}
			
				{{ if $item.drop.dropping }}<a href="item/drop/$item.id?confirm=1" onclick="id=this.id;return confirmDelete(function(){changeHref(id, 'item/drop/$item.id')});" class="wall-item-delete-wrapper icon drophide" title="$item.drop.delete" id="wall-item-delete-wrapper-$item.id" {#onmouseover="imgbright(this);" onmouseout="imgdull(this);"#} ></a>{{ endif }}
				{#<!--{{ if $item.drop.pagedrop }}<input type="checkbox" onclick="checkboxhighlight(this);" title="$item.drop.select" class="item-select" name="itemselected[]" value="$item.id" />{{ endif }}-->#}
		</div>
	</div>	
	<div class="wall-item-like $item.indent" id="wall-item-like-$item.id">$item.like</div>
	<div class="wall-item-dislike $item.indent" id="wall-item-dislike-$item.id">$item.dislike</div>

	<div class="hide-comments-outer">
	<a href="display/$user.nickname/$item.id"><span id="hide-comments-total-$item.id" class="hide-comments-total">$item.total_comments_num $item.total_comments_text</span></a>
	</div>
<!--	{{ if $item.threaded }}
	{{ if $item.comment }}
		$item.comment
	{{ endif }}
	{{ endif }}

{{ for $item.children as $child }}
	{{ inc $child.template with $item=$child }}{{ endinc }}
{{ endfor }}

{{ if $item.flatten }}
	$item.comment
{{ endif }}-->
</div>
<!--{{if $item.comment_lastcollapsed}}</div>{{endif}}-->

