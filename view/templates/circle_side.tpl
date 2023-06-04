<span id="circle-sidebar-inflated" class="widget fakelink" onclick="openCloseWidget('circle-sidebar', 'circle-sidebar-inflated');">
	<h3>{{$title}}</h3>
</span>
<div class="widget" id="circle-sidebar">
	<span class="fakelink" onclick="openCloseWidget('circle-sidebar', 'circle-sidebar-inflated');">
		<h3>{{$title}}</h3>
	</span>

	<div id="sidebar-circle-list">
		<ul role="menu" id="sidebar-circle-ul">
			{{foreach $circles as $circle}}
				<li role="menuitem" class="sidebar-circle-li circle-{{$circle.id}}">
					{{if ! $new_circle}}<span class="notify badge pull-right"></span>{{/if}}
					{{if $circle.cid}}
						<input type="checkbox"
							class="{{if $circle.selected}}ticked{{else}}unticked {{/if}} action"
							onclick="return contactCircleChangeMember(this, '{{$circle.id}}','{{$circle.cid}}');"
							{{if $circle.ismember}}checked="checked"{{/if}}
						/>
					{{/if}}
					{{if $circle.edit}}
						<a class="circlesideedit" href="{{$circle.edit.href}}" title="{{$edittext}}">
							<span id="edit-sidebar-circle-element-{{$circle.id}}" class="circle-edit-icon iconspacer small-pencil"><span class="sr-only">{{$edittext}}</span></span>
						</a>
					{{/if}}
					<a id="sidebar-circle-element-{{$circle.id}}" class="sidebar-circle-element {{if $circle.selected}}circle-selected{{/if}}" href="{{$circle.href}}">{{$circle.text}}</a>
				</li>
			{{/foreach}}
		</ul>
	</div>

	{{if $new_circle}}
	<div id="sidebar-new-circle">
		<a onclick="javascript:$('#circle-new-form').fadeIn('fast');return false;">{{$createtext}}</a>
		<form id="circle-new-form" action="circle/new" method="post" style="display:none;">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			<input name="circle_name" id="id_circle_name" placeholder="{{$create_circle}}">
		</form>
	</div>
	{{else}}
	<div id="sidebar-edit-circles"><a href="{{$circle_page}}">{{$edit_circles_text}}</a></div>
	{{/if}}

	{{if $uncircled}}<div id="sidebar-uncircled"><a class="{{if $uncircled_selected}}circle-selected{{/if}}" href="nocircle">{{$uncircled}}</a></div>{{/if}}
</div>
<script>
initWidget('circle-sidebar', 'circle-sidebar-inflated');
</script>
