<div class="widget" id="group-sidebar">
<h3>$title</h3>

<div id="sidebar-group-list">
	<ul id="sidebar-group-ul">
		{{ for $groups as $group }}
			<li class="sidebar-group-li">
				<a href="$group.href" class="sidebar-group-element {{ if $group.selected }}group-selected{{ endif }}">$group.text</a>
				{{ if $group.edit }}
					<a 
                        class="groupsideedit"
                        href="$group.edit.href" title="$edittext"><span class="icon small-pencil"></span></a>
				{{ endif }}
				{{ if $group.cid }}
					<input type="checkbox" 
						class="{{ if $group.selected }}ticked{{ else }}unticked {{ endif }} action" 
						onclick="contactgroupChangeMember('$group.id','$group.cid');return true;"
						{{ if $group.ismember }}checked="checked"{{ endif }}
					/>
				{{ endif }}
			</li>
		{{ endfor }}
	</ul>
	</div>
  <div id="sidebar-new-group">
  <a href="group/new" title="$createtext"><span class="action text add">$createtext</span></a>
  </div>
</div>


