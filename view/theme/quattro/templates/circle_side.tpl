<div id="circle-sidebar" class="widget">
	<div class="title tool">
		<h3 class="label">{{$title}}</h3>
		<a href="circle/new" title="{{$createtext}}" class="action"><span class="icon text s16 add"> {{$add}}</span></a>
	</div>

	<div id="sidebar-circle-list">
		<ul>
			{{foreach $circles as $circle}}
			<li class="tool  {{if $circle.selected}}selected{{/if}} circle-{{$circle.id}}">
				<a href="{{$circle.href}}" class="label">
					{{$circle.text}}
				</a>
				{{if $circle.edit}}
					<a href="{{$circle.edit.href}}" class="action"><span class="icon text s10 edit">{{$circle.edit.title}}</span></a>
				{{/if}}
				{{if $circle.cid}}
					<input type="checkbox"
						class="{{if $circle.selected}}ticked{{else}}unticked {{/if}} action"
						onclick="return contactCircleChangeMember(this, '{{$circle.id}}','{{$circle.cid}}');"
						{{if $circle.ismember}}checked="checked"{{/if}}
					/>
				{{/if}}
				<span class="notify"></span>
			</li>
			{{/foreach}}
		</ul>
	</div>
</div>

