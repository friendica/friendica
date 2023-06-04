<script>
function showHideForumlist() {
	if( $("li[id^='forum-widget-entry-extended-']").is(':visible')) {
		$("li[id^='forum-widget-entry-extended-']").hide();
		$("li#forum-widget-collapse").html('{{$showmore}}');

	}
	else {
		$("li[id^='forum-widget-entry-extended-']").show();
		$("li#forum-widget-collapse").html('{{$showless}}');
	}
}
</script>
<span id="forumlist-sidebar-frame">
<span id="forumlist-sidebar-inflated" class="widget fakelink" onclick="openCloseWidget('forumlist-sidebar', 'forumlist-sidebar-inflated');">
	<h3>{{$title}}</h3>
</span>
<div id="forumlist-sidebar" class="widget">
<div id="sidebar-forum-header" class="sidebar-widget-header">
	<span class="fakelink" onclick="openCloseWidget('forumlist-sidebar', 'forumlist-sidebar-inflated');">
		<h3>{{$title}}</h3>
	</span>
		<a class="forum-new-tool pull-right widget-action faded-icon" id="sidebar-new-forum" href="{{$forums_page}}" data-toggle="tooltip" title="{{$create_new_forum}}">
			<i class="fa fa-plus" aria-hidden="true"></i>
		</a>
		
	</div>
	<div id="sidebar-forum-list" class="sidebar-widget-list">
		{{* The list of available forums *}}
	<ul id="forumlist-sidebar-ul" role="menu">
		{{foreach $forums as $forum}}
		{{if $forum.id <= $visible_forums}}
		<li class="forum-widget-entry forum-{{$forum.cid}}" id="forum-widget-entry-{{$forum.id}}" role="menuitem">
			<span class="notify badge pull-right"></span>
			<a href="{{$forum.external_url}}" title="{{$forum.link_desc}}" class="label sparkle" target="_blank" rel="noopener noreferrer">
				<img class="forumlist-img" src="{{$forum.micro}}" alt="{{$forum.link_desc}}" />
			</a>
			<a class="forum-widget-link {{if $forum.selected}}forum-selected{{/if}}" id="forum-widget-link-{{$forum.id}}" href="{{$forum.url}}">{{$forum.name}}</a>
		</li>
		{{/if}}
	
		{{if $forum.id > $visible_forums}}
		<li class="forum-widget-entry forum-{{$forum.cid}}" id="forum-widget-entry-extended-{{$forum.id}}" role="menuitem" style="display: none;">
			<span class="notify badge pull-right"></span>
			<a href="{{$forum.external_url}}" title="{{$forum.link_desc}}" class="label sparkle" target="_blank" rel="noopener noreferrer">
				<img class="forumlist-img" src="{{$forum.micro}}" alt="{{$forum.link_desc}}" />
			</a>
			<a class="forum-widget-link {{if $forum.selected}}forum-selected{{/if}}" id="forum-widget-link-{{$forum.id}}" href="{{$forum.url}}">{{$forum.name}}</a>
		</li>
		{{/if}}
		{{/foreach}}

		{{if $total > $visible_forums }}
		<li onclick="showHideForumlist(); return false;" id="forum-widget-collapse" class="forum-widget-link fakelink tool">{{$showmore}}</li>
		{{/if}}
	</ul>
	</div>
</div>
</span>
<script>
initWidget('forumlist-sidebar', 'forumlist-sidebar-inflated');
</script>
