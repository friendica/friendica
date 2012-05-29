{{ for $threads as $thread }}
<div id="tread-wrapper-$thread.id" class="tread-wrapper">
	{{ for $thread.items as $item }}
		{{if $item.comment_firstcollapsed}}
			<div class="hide-comments-outer">
			<span id="hide-comments-total-$thread.id" class="hide-comments-total">$thread.num_comments</span> <span id="hide-comments-$thread.id" class="hide-comments fakelink" onclick="showHideComments($thread.id);">$thread.hide_text</span>
			</div>
			<div id="collapsed-comments-$thread.id" class="collapsed-comments" style="display: none;">
		{{endif}}
		{{if $item.comment_lastcollapsed}}</div>{{endif}}
		
		{{ inc $item.template }}{{ endinc }}
		
		
	{{ endfor }}
</div>
{{ endfor }}

{{ if $dropping }}
<div class="delete-checked">
<a href="#" onclick="deleteCheckedItems();return false;"><span class="icon delete"></span><span class="s22 text" onmouseover="imgbright(this);" onmouseout="imgdull(this);">$dropping</span></a>
</div>
<div id="item-delete-selected-end"></div>
{{ endif }}
