<div class="widget" id="group-sidebar">
<h3>{{$title}}</h3>

<div id="sidebar-group-list">
	<ul role="menu" id="sidebar-group-ul">
		{{foreach $groups as $group}}
			<li role="menuitem" class="sidebar-group-li group-{{$group.id}}">
				{{if $group.cid}}
					<input type="checkbox"
						class="{{if $group.selected}}ticked{{else}}unticked {{/if}} action"
						onclick="contactgroupChangeMember('{{$group.id}}','{{$group.cid}}');return true;"
						{{if $group.ismember}}checked="checked"{{/if}}
					/>
				{{/if}}
				{{if $group.edit}}
					<a class="groupsideedit" href="{{$group.edit.href}}" title="{{$edittext}}"><span id="edit-sidebar-group-element-{{$group.id}}" class="group-edit-icon iconspacer small-pencil"><span class="sr-only">{{$edittext}}</span></span></a>
				{{/if}}
				<a id="sidebar-group-element-{{$group.id}}" class="sidebar-group-element {{if $group.selected}}group-selected{{/if}}" href="{{$group.href}}">{{$group.text}}</a>
				<span class="notify"></span>
			</li>
		{{/foreach}}
	</ul>
	</div>
  <div id="sidebar-new-group">
  <a onclick="javascript:$('#group-new-form').fadeIn('fast');return false;">{{$createtext}}</a>
  <form id="group-new-form" action="group/new" method="post" style="display:none;">
   <input type="hidden" name="form_security_token" value="{{$form_security_token}}">
   <input name="groupname" id="id_groupname" placeholder="{{$creategroup}}">
  </form>
  </div>
  {{if $ungrouped}}
  <div id="sidebar-ungrouped">
  <a href="nogroup">{{$ungrouped}}</a>
  </div>
  {{/if}}
</div>


