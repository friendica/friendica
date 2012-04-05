<div class="widget" id="group-sidebar">
<h3>$title</h3>

<div id="sidebar-group-list">
	<ul id="sidebar-group-ul">
		{{ for $groups as $group }}
			<li class="sidebar-group-li">
				{{ if $group.cid }}
					<input type="checkbox" 
						class="{{ if $group.selected }}ticked{{ else }}unticked {{ endif }} action" 
						onclick="contactgroupChangeMember('$group.id','$group.cid');return true;"
						{{ if $group.ismember }}checked="checked"{{ endif }}
					/>
				{{ endif }}			
				{{ if $group.edit }}
					<a class="groupsideedit" href="$group.edit.href" title="$edittext"><span id="edit-sidebar-group-element-$group.id" class="group-edit-icon iconspacer small-pencil"></span></a>
				{{ endif }}
				<a id="sidebar-group-element-$group.id" class="sidebar-group-element {{ if $group.selected }}group-selected{{ endif }}" href="$group.href">$group.text</a>
			</li>
		{{ endfor }}
	</ul>
	</div>
  <div id="sidebar-new-group">
  <a href="group/new">$createtext</a>
  </div>
</div>


