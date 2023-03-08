<span id="group-sidebar-inflated" class="widget fakelink" onclick="openCloseWidget('group-sidebar', 'group-sidebar-inflated');">
        <h3>{{$title}}</h3>
</span>
<div class="widget" id="group-sidebar">
	<div id="sidebar-group-header" class="sidebar-widget-header">
		<span class="fakelink" onclick="openCloseWidget('group-sidebar', 'group-sidebar-inflated');">
			<h3>{{$title}}</h3>
		</span>
		{{if ! $newgroup}}
		<a class="group-edit-tool pull-right widget-action faded-icon" id="sidebar-edit-group" href="{{$grouppage}}" data-toggle="tooltip" title="{{$editgroupstext}}">
			<i class="fa fa-pencil" aria-hidden="true"></i>
		</a>
		{{else}}
		<a class="group-edit-tool pull-right widget-action faded-icon" id="sidebar-new-group" onclick="javascript:$('#group-new-form').fadeIn('fast');" data-toggle="tooltip" title="{{$createtext}}">
			<i class="fa fa-plus" aria-hidden="true"></i>
		</a>
		<form id="group-new-form" action="group/new" method="post" style="display:none;">
			<div class="form-group">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<input name="groupname" id="id_groupname" class="form-control input-sm" placeholder="{{$creategroup}}">
			</div>
		</form>
		{{/if}}
	</div>
	<div id="sidebar-group-list" class="sidebar-widget-list">
		{{* The list of available groups *}}
		<ul role="menu" id="sidebar-group-ul">
			{{foreach $groups as $group}}
				<li role="menuitem" class="sidebar-group-li group-{{$group.id}} {{if $group.selected}}selected{{/if}}">
					{{if ! $newgroup}}<span class="notify badge pull-right"></span>{{/if}}
					{{if $group.cid}}
						<div class="checkbox pull-right group-checkbox ">
							<input type="checkbox"
								id="sidebar-group-checkbox-{{$group.id}}"
								class="{{if $group.selected}}ticked{{else}}unticked {{/if}} action"
								onclick="return contactgroupChangeMember(this, '{{$group.id}}','{{$group.cid}}');"
								{{if $group.ismember}}checked="checked"{{/if}}
								aria-checked="{{if $group.ismember}}true{{else}}false{{/if}}"
							/>
							<label for="sidebar-group-checkbox-{{$group.id}}"></label>
							<div class="clearfix"></div>
						</div>
					{{/if}}
					{{if $group.edit}}
						{{* if the group is editable show a little pencil for editing *}}
						<a id="edit-sidebar-group-element-{{$group.id}}" class="group-edit-tool pull-right faded-icon" href="{{$group.edit.href}}" data-toggle="tooltip" title="{{$edittext}}">
							<i class="fa fa-pencil" aria-hidden="true"></i>
						</a>
					{{/if}}
					<a id="sidebar-group-element-{{$group.id}}" class="sidebar-group-element" href="{{$group.href}}">{{$group.text}}</a>
				</li>
			{{/foreach}}

			{{if $ungrouped}}<li class="{{if $ungrouped_selected}}selected{{/if}} sidebar-group-li" id="sidebar-ungrouped"><a href="nogroup">{{$ungrouped}}</a></li>{{/if}}
		</ul>
	</div>
</div>
<script>
initWidget('group-sidebar', 'group-sidebar-inflated');
</script>
