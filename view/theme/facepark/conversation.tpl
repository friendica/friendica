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
<div id="item-delete-selected" class="fakelink" onclick="deleteCheckedItems();">
  <div id="item-delete-selected-icon" class="icon drophide" title="$dropping" onmouseover="imgbright(this);" onmouseout="imgdull(this);" ></div>
  <div id="item-delete-selected-desc" >$dropping</div>
</div>
<div id="item-delete-selected-end"></div>
{{ endif }}
