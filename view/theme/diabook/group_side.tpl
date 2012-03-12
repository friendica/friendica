<div id="group-sidebar" class="widget">
	<div class="title tool">
		<h3 class="label">$title</h3>
		<a href="group/new" title="$createtext" class="action"><span class="icon text s16 add"></span></a>
	</div>

	<div id="sidebar-group-list">
		<ul>
			{{ for $groups as $group }}
			<li class="tool  {{ if $group.selected }}selected{{ endif }}">
				<a href="$group.href" class="label">
					$group.text
				</a>
				{{ if $group.edit }}
					<a href="$group.edit.href" class="action"><span class="icon text s10 edit"></span></a>
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
</div>	

