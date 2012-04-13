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
		
		{{ if $item.type == tag }}
			{{ inc wall_item_tag.tpl }}{{ endinc }}
		{{ else }}
			{{ inc $item.template }}{{ endinc }}
		{{ endif }}
		
	{{ endfor }}
</div>
{{ endfor }}

{{ if $dropping }}
<a href="#" onclick="deleteCheckedItems();return false;">
	<span class="icon s22 delete text">$dropping</span>
</a>
{{ endif }}
