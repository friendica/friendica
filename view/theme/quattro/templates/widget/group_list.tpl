<script>

function showHideGroupList() {
	if( $("li[id^='group-widget-entry-extended-']").is(':visible')) {
		$("li[id^='group-widget-entry-extended-']").hide();
		$("li#group-widget-collapse").html('{{$showmore}}');

	}
	else {
		$("li[id^='group-widget-entry-extended-']").show();
		$("li#group-widget-collapse").html('{{$showless}}');
	}
}
</script>

<div id="group-list-sidebar" class="widget">
	<h3 id="group-list">{{$title}}</h3>

	<ul id="group-list-sidebar-ul" role="menu">
		{{foreach $groups as $group}}
		{{if $group.id <= $visible_groups}}
		<li class="group-widget-entry group-{{$group.cid}} tool {{if $group.selected}}selected{{/if}}" id="group-widget-entry-{{$group.id}}" role="menuitem">
			<span class="notify badge pull-right"></span>
			<a href="{{$group.external_url}}" title="{{$group.link_desc}}" class="label sparkle" target="_blank" rel="noopener noreferrer">
				<img class="group-list-img" src="{{$group.micro}}" alt="{{$group.link_desc}}" />
			</a>
			<a class="group-widget-link" id="group-widget-link-{{$group.id}}" href="{{$group.url}}">{{$group.name}}</a>
		</li>
		{{/if}}

		{{if $group.id > $visible_groups}}
		<li class="group-widget-entry group-{{$group.cid}} tool {{if $group.selected}}selected{{/if}}" id="group-widget-entry-extended-{{$group.id}}" role="menuitem" style="display: none;">
			<span class="notify badge pull-right"></span>
			<a href="{{$group.external_url}}" title="{{$group.link_desc}}" class="label sparkle" target="_blank" rel="noopener noreferrer">
				<img class="group-list-img" src="{{$group.micro}}" alt="{{$group.link_desc}}" />
			</a>
			<a class="group-widget-link" id="group-widget-link-{{$group.id}}" href="{{$group.url}}">{{$group.name}}</a>
		</li>
		{{/if}}
		{{/foreach}}

		{{if $total > $visible_groups }}
		<li onclick="showHideGroupList(); return false;" id="group-widget-collapse" class="group-widget-link fakelink tool">{{$showmore}}</li>
		{{/if}}
	</ul>
</div>
