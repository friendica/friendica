<script>
function showHideForumlist() {
	if( $("li[id^='forum-widget-entry-extended-']").is(':visible')) {
		$("li[id^='forum-widget-entry-extended-']").hide();
		$("li#forum-widget-collapse").html(window.showMore);

	}
	else {
		$("li[id^='forum-widget-entry-extended-']").show();
		$("li#forum-widget-collapse").html(window.showFewer);
	}
}
</script>
<span id="forumlist-sidebar-frame">
<span id="forumlist-sidebar-inflated" class="widget fakelink" onclick="openCloseWidget('forumlist-sidebar', 'forumlist-sidebar-inflated');">
	<h3>{{$title}}</h3>
</span>
<div id="forumlist-sidebar" class="widget">
	<span class="fakelink" onclick="openCloseWidget('forumlist-sidebar', 'forumlist-sidebar-inflated');">
		<h3 id="forumlist">{{$title}}</h3>
	</span>
	<ul id="forumlist-sidbar-ul" role="menu">
		{{foreach $forums as $forum}}
		{{if $forum.id <= $visible_forums}}
		<li class="forum-widget-entry forum-{{$forum.cid}}" id="forum-widget-entry-{{$forum.id}}" role="menuitem">
			<span class="notify badge pull-right"></span>
			<a href="{{$forum.external_url}}" title="{{$forum.link_desc}}" class="label sparkle">
				<img class="forumlist-img" src="{{$forum.micro}}" alt="{{$forum.link_desc}}" />
			</a>
			<a class="forum-widget-link {{if $forum.selected}}forum-selected{{/if}}" id="forum-widget-link-{{$forum.id}}" href="{{$forum.url}}" >{{$forum.name}}</a>
		</li>
		{{/if}}
	
		{{if $forum.id > $visible_forums}}
		<li class="forum-widget-entry forum-{{$forum.cid}}" id="forum-widget-entry-extended-{{$forum.id}}" role="menuitem" style="display: none;">
			<span class="notify badge pull-right"></span>
			<a href="{{$forum.external_url}}" title="{{$forum.link_desc}}" class="label sparkle">
				<img class="forumlist-img" src="{{$forum.micro}}" alt="{{$forum.link_desc}}" />
			</a>
			<a class="forum-widget-link {{if $forum.selected}}forum-selected{{/if}}" id="forum-widget-link-{{$forum.id}}" href="{{$forum.url}}" >{{$forum.name}}</a>
		</li>
		{{/if}}
		{{/foreach}}

		{{if $total > $visible_forums }}
		<li onclick="showHideForumlist(); return false;" id="forum-widget-collapse" class="forum-widget-link fakelink tool">{{$showmore}}</li>
		{{/if}}
	</ul>
</div>
</span>
<script>
initWidget('forumlist-sidebar', 'forumlist-sidebar-inflated');
</script>
