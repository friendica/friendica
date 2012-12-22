{{if $item.comment_firstcollapsed}}
	<div class="hide-comments-outer">
	<span id="hide-comments-total-$item.id" class="hide-comments-total">$item.num_comments</span> <span id="hide-comments-$item.id" class="hide-comments fakelink" onclick="showHideComments($item.id);">$item.hide_text</span>
	</div>
	<div id="collapsed-comments-$item.id" class="collapsed-comments" style="display: none;">
{{endif}}
<div id="tread-wrapper-$item.id" class="tread-wrapper $item.toplevel">
{{ if $item.indent }}{{ else }}
<div class="wall-item-decor">
	<img id="like-rotator-$item.id" class="like-rotator" src="images/rotator.gif" alt="$item.wait" title="$item.wait" style="display: none;" />
</div>
{{ endif }}
<div class="wall-item-container $item.indent">
	<div class="wall-item-item">
		<div class="wall-item-info">
			<div class="contact-photo-wrapper"
				onmouseover="if (typeof t$item.id != 'undefined') clearTimeout(t$item.id); openMenu('wall-item-photo-menu-button-$item.id')" 
				onmouseout="t$item.id=setTimeout('closeMenu(\'wall-item-photo-menu-button-$item.id\'); closeMenu(\'wall-item-photo-menu-$item.id\');',200)">
				<a href="$item.profile_url" target="redir" title="$item.linktitle" class="contact-photo-link" id="wall-item-photo-link-$item.id">
					<img src="$item.thumb" class="contact-photo$item.sparkle" id="wall-item-photo-$item.id" alt="$item.name" />
				</a>
				<a href="#" rel="#wall-item-photo-menu-$item.id" class="contact-photo-menu-button icon s16 menu" id="wall-item-photo-menu-button-$item.id">menu</a>
				<ul class="contact-menu menu-popup" id="wall-item-photo-menu-$item.id">
				$item.item_photo_menu
				</ul>
				
			</div>
		</div>
			<div class="wall-item-actions-author">
				<a href="$item.profile_url" target="redir" title="$item.linktitle" class="wall-item-name-link"><span class="wall-item-name$item.sparkle">$item.name</span></a> 
			<span class="wall-item-ago">-
			{{ if $item.plink }}<a class="link$item.sparkle" title="$item.plink.title" href="$item.plink.href" style="color: #999">$item.ago</a>{{ else }} $item.ago {{ endif }}
			{{ if $item.lock }} - <span class="fakelink" style="color: #999" onclick="lockview(event,$item.id);">$item.lock</span> {{ endif }}
			</span>
			</div>
		<div class="wall-item-content">
			{{ if $item.title }}<h2><a href="$item.plink.href">$item.title</a></h2>{{ endif }}
			$item.body
			{{ if $item.has_cats }}
			<div class="categorytags"><span>$item.txt_cats {{ for $item.categories as $cat }}$cat.name <a href="$cat.removeurl" title="$remove">[$remove]</a> {{ if $cat.last }}{{ else }}, {{ endif }}{{ endfor }}
			</div>
			{{ endif }}

			{{ if $item.has_folders }}
			<div class="filesavetags"><span>$item.txt_folders {{ for $item.folders as $cat }}$cat.name <a href="$cat.removeurl" title="$remove">[$remove]</a> {{ if $cat.last }}{{ else }}, {{ endif }}{{ endfor }}
			</div>
			{{ endif }}
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="wall-item-links">
		</div>
		<div class="wall-item-tags">
			{{ for $item.tags as $tag }}
				<span class='tag'>$tag</span>
			{{ endfor }}
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="">

		</div>
		<div class="wall-item-actions">

			<div class="wall-item-actions-social">
			
			
			{{ if $item.vote }}
				<a href="#" id="like-$item.id" class="icon like" title="$item.vote.like.0" onclick="dolike($item.id,'like'); return false">$item.vote.like.1</a>
				{{ if $item.vote.dislike }}
				<a href="#" id="dislike-$item.id" class="icon dislike" title="$item.vote.dislike.0" onclick="dolike($item.id,'dislike'); return false"></a>
				{{ endif }}
			{{ endif }}
						
			{{ if $item.vote.share }}
				<a href="#" id="share-$item.id" class="icon recycle" title="$item.vote.share.0" onclick="jotShare($item.id); return false"></a>
			{{ endif }}	


			{{ if $item.star }}
				<a href="#" id="starred-$item.id" onclick="dostar($item.id); return false;" class="star-item icon $item.isstarred" title="$item.star.toggle">
				<img src="images/star_dummy.png" class="icon star" alt="$item.star.do" /> </a>
			{{ endif }}

			{{ if $item.tagger }}
				<a href="#" id="tagger-$item.id" onclick="itemTag($item.id); return false;" class="tag-item icon tagged" title="$item.tagger.add"></a>					  
			{{ endif }}	
			
			{{ if $item.filer }}
			<a href="#" id="filer-$item.id" onclick="itemFiler($item.id); return false;" class="filer-item icon file-as" title="$item.star.filer"></a>
			{{ endif }}				
			
			{{ if $item.plink }}<a class="icon link" title="$item.plink.title" href="$item.plink.href">$item.plink.title</a>{{ endif }}
			
					
					
			</div>
			
			<div class="wall-item-actions-tools">

				{{ if $item.drop.pagedrop }}
					<input type="checkbox" title="$item.drop.select" name="itemselected[]" class="item-select" value="$item.id" />
				{{ endif }}
				{{ if $item.drop.dropping }}
					<a href="item/drop/$item.id" onclick="return confirmDelete();" class="icon drop" title="$item.drop.delete">$item.drop.delete</a>
				{{ endif }}
				{{ if $item.edpost }}
					<a class="icon pencil" href="$item.edpost.0" title="$item.edpost.1"></a>
				{{ endif }}
			</div>
			<div class="wall-item-location">$item.location&nbsp;</div>
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="wall-item-links"></div>
		<div class="wall-item-like" id="wall-item-like-$item.id">$item.like</div>
		<div class="wall-item-dislike" id="wall-item-dislike-$item.id">$item.dislike</div>	
	</div>
</div>

{{ if $item.threaded }}
{{ if $item.comment }}
<div class="wall-item-comment-wrapper $item.indent" >
	$item.comment
</div>
{{ endif }}
{{ endif }}

{{ if $item.flatten }}
<div class="wall-item-comment-wrapper" >
	$item.comment
</div>
{{ endif }}


{{ for $item.children as $child_item }}
	{{ inc $child_item.template with $item=$child_item }}{{ endinc }}
{{ endfor }}

</div>
{{if $item.comment_lastcollapsed}}</div>{{endif}}
