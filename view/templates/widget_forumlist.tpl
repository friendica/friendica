<script>

function showHideForumlist() {
	if( $("div[id^='forum-widget-entry-extended-']").is(':visible')) {
		$("div[id^='forum-widget-entry-extended-']").hide();
		$('#forum-widget-collapse').html(window.showMore);

	}
	else {
		$("div[id^='forum-widget-entry-extended-']").show();
		$('#forum-widget-collapse').html(window.showFewer);
	}
	}
</script>

<div id="forumlist-sidebar" class="widget">
	<h3 id="forumlist">{{$title}}</h3>

	{{foreach $forums as $forum}}
		{{if $forum.id <= $visible_forums}}
		<div class="forum-widget-entry" id="forum-widget-entry-{{$forum.id}}" role="menuitem">
			<a href="{{$forum.external_url}}" title="{{$forum.link_desc}}" class="label sparkle" target="_blank">
				<img class="forumlist-img" src="{{$forum.micro}}" alt="{{$forum.link_desc}}" />
			</a>
			<a class="forum-widget-link" id="forum-widget-link-{{$forum.id}}" href="{{$forum.url}}" >{{$forum.name}}</a>
		</div>
		{{/if}}
	
		{{if $forum.id > $visible_forums}}
		<div class="forum-widget-entry" id="forum-widget-entry-extended-{{$forum.id}}" role="menuitem" style="display: none;">
			<a href="{{$forum.external_url}}" title="{{$forum.link_desc}}" class="label sparkle" target="_blank">
				<img class="forumlist-img" src="{{$forum.micro}}" alt="{{$forum.link_desc}}" />
			</a>
			<a class="forum-widget-link" id="forum-widget-link-{{$forum.id}}" href="{{$forum.url}}" >{{$forum.name}}</a>
		</div>
		{{/if}}
	{{/foreach}}

	{{if $total > $visible_forums }}
	<div id="forum-widget-showmore" class="forum-widget-entry" role="menuitem">
		<a onclick="showHideForumlist(); return false;" id="forum-widget-collapse" class="forum-widget-link">{{$showmore}}</a>
	</div>
	{{/if}}

</div>
