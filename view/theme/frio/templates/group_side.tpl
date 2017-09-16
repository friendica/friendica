<div class="widget" id="group-sidebar">
	<h3>{{$title}}</h3>

	<div id="sidebar-group-list">
		{{* The list of available groups *}}
		<ul role="menu" id="sidebar-group-ul">
			{{foreach $groups as $group}}
				<li role="menuitem" class="sidebar-group-li group-{{$group.id}} {{if $group.selected}}selected{{/if}}">
					{{if ! $newgroup}}<span class="notify badge pull-right"></span>{{/if}}
					{{if $group.cid}}
						<div class="checkbox pull-right group-checkbox ">
							<input type="checkbox"
								class="{{if $group.selected}}ticked{{else}}unticked {{/if}} action"
								onclick="contactgroupChangeMember('{{$group.id}}','{{$group.cid}}');return true;"
								{{if $group.ismember}}checked="checked"{{/if}}
								aria-checked="{{if $group.ismember}}true{{else}}false{{/if}}"
							/>
							<label for="group-{{$group.id}}"></label>
							<div class="clearfix"></div>
						</div>
					{{/if}}
					{{if $group.edit}}
						{{* if the group is editable show a little pencil for editing *}}
						<a id="edit-sidebar-group-element-{{$group.id}}" class="group-edit-tool pull-right" href="{{$group.edit.href}}" title="{{$edittext}}">
							<i class="faded-icon fa fa-pencil" aria-hidden="true"></i><span class="sr-only">{{$edittext}}</span>
						</a>
					{{/if}}
					<a id="sidebar-group-element-{{$group.id}}" class="sidebar-group-element" href="{{$group.href}}">{{$group.text}}</a>
				</li>
			{{/foreach}}
		</ul>
	</div>

	{{if $newgroup}}
	<div id="sidebar-new-group">
		{{* show the input field by clicking "new group" *}}
		<button type="button" class="btn-link" onclick="javascript:$('#group-new-form').fadeIn('fast');">{{$createtext}}</button>
		<form id="group-new-form" action="group/new" method="post" style="display:none;">
			<div class="form-group">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<input name="groupname" id="id_groupname" class="form-control input-sm" placeholder="{{$creategroup}}">
			</div>
		</form>
	</div>
	{{else}}
	<div id="sidebar-edit-groups"><a href="{{$grouppage}}">{{$editgroupstext}}</a></div>
	{{/if}}

	{{if $ungrouped}}<div id="sidebar-ungrouped"><a href="nogroup">{{$ungrouped}}</a></div>{{/if}}
</div>
