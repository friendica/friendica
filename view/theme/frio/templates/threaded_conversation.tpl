{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{if $mode == display}}
<p><a href="#" class="back-link" title="{{$back_link}}"><i class="ri ri-arrow-left-long-line"></i> {{$back_link}}</a></p>
{{/if}}
{{if !$update}}<script type="text/javascript" src="view/theme/frio/frameworks/jquery-color/jquery.color.js?v={{$VERSION}}"></script>{{/if}}
{{if $mode == display}}
<script type="text/javascript">
// Display module: Scroll to item by GUID
window.itemGuid = window.location.pathname.split("/").pop();
window.scrollToDisplayGuid = () => scrollToItem("item-" + window.itemGuid);

// 1. Try immediately (works for SPA after content replacement)
window.scrollToDisplayGuid();

// 2. Try on window.load (works for non-SPA)
$(window).load(window.scrollToDisplayGuid);

// 3. Listen for SPA navigation
window.addEventListener('spa:navigate', (e) => {
    if (e.detail?.path?.includes('/display/')) {
        const newGuid = e.detail.path.split("/").pop();
        scrollToItem("item-" + newGuid);
    }
});
</script>
{{/if}}
{{$live_update nofilter}}
{{foreach $threads as $thread}}
<div id="tread-wrapper-{{$thread.uriid}}" class="tread-wrapper {{if $thread.threaded}}threaded{{/if}} {{$thread.toplevel}} {{$thread.network}} {{if $thread.thread_level==1}}panel-default panel{{/if}} {{if $thread.thread_level!=1}}comment-wrapper{{/if}} {{if $hide_comments && $mode != display}}hide-feed-comments{{/if}}" style="{{if $item.thread_level>2}}margin-left: -15px; margin-right:-16px; margin-bottom:-16px;{{/if}}"><!-- panel -->

		{{* {{if $thread.type == tag}}
			{{include file="wall_item_tag.tpl" item=$thread}}
		{{else}}
			{{include file="{{$thread.template}}" item=$thread}}
		{{/if}} *}} {{include file="{{$thread.template}}" item=$thread}}

</div><!--./tread-wrapper-->
{{/foreach}}
{{if !$update}}
  <div id="conversation-end"></div>

  {{if $dropping}}
    <button type="button" id="item-delete-selected" class="btn btn-primary" onclick="deleteCheckedItems();">
      <i class="ri ri-delete-bin-line" aria-hidden="true"></i>
      <span>{{$dropping}}</span>
    </button>
  {{/if}}
{{/if}}
{{if $mode == display}}
<script>
	$(function(){
		// plinks to source are rel="noreferer noopener" in new tab
		if (!window.opener && !document.referrer){
			$('a.back-link').hide();
		}
	});
    $('a.back-link').on('click',function(e){
    	e.preventDefault();
    		if (window.history.length === 1){
    			if (document.referrer){
    				window.location = document.referrer;
    			} else if (window.opener) {
    				window.location = window.opener;
    			} else {
    				// no idea where to go
    			}
    		} else {
    			window.history.back();
    		}
    });    
</script>
{{/if}}
