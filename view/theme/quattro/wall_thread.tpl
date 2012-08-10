{{if $mode == display}}
{{ else }}
{{if $item.comment_firstcollapsed}}
	<div class="hide-comments-outer">
		<span id="hide-comments-total-$item.id" 
			class="hide-comments-total">$item.num_comments</span>
			<span id="hide-comments-$item.id" 
				class="hide-comments fakelink" 
				onclick="showHideComments($item.id);">$item.hide_text</span>
			{{ if $item.thread_level==3 }} - 
			<span id="hide-thread-$item-id"
				class="fakelink"
				onclick="showThread($item.id);">expand</span> /
			<span id="hide-thread-$item-id"
				class="fakelink"
				onclick="hideThread($item.id);">collapse</span> thread{{ endif }}
	</div>
	<div id="collapsed-comments-$item.id" class="collapsed-comments" style="display: none;">
{{endif}}
{{ endif }}

{{ if $item.thread_level!=1 }}<div class="children">{{ endif }}

<div class="wall-item-decor">
	<span class="icon s22 star $item.isstarred" id="starred-$item.id" title="$item.star.starred">$item.star.starred</span>
	{{ if $item.lock }}<span class="icon s22 lock fakelink" onclick="lockview(event,$item.id);" title="$item.lock">$item.lock</span>{{ endif }}	
	<img id="like-rotator-$item.id" class="like-rotator" src="images/rotator.gif" alt="$item.wait" title="$item.wait" style="display: none;" />
</div>

<div class="wall-item-container $item.indent" id="item-$item.id">
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
			<div class="wall-item-location">$item.location</div>	
		</div>
		<div class="wall-item-content">
			{{ if $item.title }}<h2><a href="$item.plink.href" class="$item.sparkle">$item.title</a></h2>{{ endif }}
			$item.body
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
			{{ if $item.plink }}<a class="icon s16 link$item.sparkle" title="$item.plink.title" href="$item.plink.href">$item.plink.title</a>{{ endif }}
		</div>
		<div class="wall-item-actions">
			<div class="wall-item-actions-author">
				<a href="$item.profile_url" target="redir" title="$item.linktitle" class="wall-item-name-link"><span class="wall-item-name$item.sparkle">$item.name</span></a> <span class="wall-item-ago">$item.ago</span>
			</div>
			
			<div class="wall-item-actions-social">
			{{ if $item.star }}
				<a href="#" id="star-$item.id" onclick="dostar($item.id); return false;"  class="$item.star.classdo"  title="$item.star.do">$item.star.do</a>
				<a href="#" id="unstar-$item.id" onclick="dostar($item.id); return false;"  class="$item.star.classundo"  title="$item.star.undo">$item.star.undo</a>
				<a href="#" id="tagger-$item.id" onclick="itemTag($item.id); return false;" class="$item.star.classtagger" title="$item.star.tagger">$item.star.tagger</a>
			{{ endif }}
			{{ if $item.filer }}
                                <a href="#" id="filer-$item.id" onclick="itemFiler($item.id); return false;" class="filer-item filer-icon" title="$item.filer">$item.filer</a>
			{{ endif }}			
			
			{{ if $item.vote }}
				<a href="#" id="like-$item.id" title="$item.vote.like.0" onclick="dolike($item.id,'like'); return false">$item.vote.like.1</a>
				<a href="#" id="dislike-$item.id" title="$item.vote.dislike.0" onclick="dolike($item.id,'dislike'); return false">$item.vote.dislike.1</a>
			{{ endif }}
						
			{{ if $item.vote.share }}
				<a href="#" id="share-$item.id" title="$item.vote.share.0" onclick="jotShare($item.id); return false">$item.vote.share.1</a>
			{{ endif }}			
			</div>
			
			<div class="wall-item-actions-tools">

				{{ if $item.drop.dropping }}
					<input type="checkbox" title="$item.drop.select" name="itemselected[]" class="item-select" value="$item.id" />
					<a href="item/drop/$item.id" onclick="return confirmDelete();" class="icon delete s16" title="$item.drop.delete">$item.drop.delete</a>
				{{ endif }}
				{{ if $item.edpost }}
					<a class="icon edit s16" href="$item.edpost.0" title="$item.edpost.1"></a>
				{{ endif }}
			</div>
			
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="wall-item-links"></div>
		<div class="wall-item-like" id="wall-item-like-$item.id">$item.like</div>
		<div class="wall-item-dislike" id="wall-item-dislike-$item.id">$item.dislike</div>	
	</div>
	{{ if $item.threaded }}{{ if $item.comment }}{{ if $item.thread_level!=1 }}
	<div class="wall-item-bottom">
		<div class="wall-item-links"></div>
		<div class="wall-item-comment-wrapper">
			$item.comment
		</div>
	</div>
	{{ endif }}{{ endif }}{{ endif }}

</div>



{{ for $item.children as $child }}
	{{ if $item.type == tag }}
		{{ inc wall_item_tag.tpl with $item=$child }}{{ endinc }}
	{{ else }}
		{{ inc $item.template with $item=$child }}{{ endinc }}
	{{ endif }}
{{ endfor }}

{{ if $item.thread_level!=1 }}</div>{{ endif }}


{{if $mode == display}}
{{ else }}
{{if $item.comment_lastcollapsed}}</div>{{endif}}
{{ endif }}

{{if $item.threaded}}{{if $item.comment}}{{if $item.thread_level==1}}
<div class="wall-item-comment-wrapper" >$item.comment</div>
{{ endif }}{{ endif }}{{ endif }}


{{ if $item.flatten }}
<div class="wall-item-comment-wrapper" >$item.comment</div>
{{ endif }}
