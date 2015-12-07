{{if $page}}
<div id="right_pages" class="widget">
<div>{{$page}}</div>
</div>
{{/if}}

{{if $comunity_profiles_title}}
<div id="right_profiles" class="widget">
<h3>{{$comunity_profiles_title}}</h3>
<div id='lastusers-wrapper' class='items-wrapper'>
{{foreach $comunity_profiles_items as $i}}
	{{$i}}
{{/foreach}}
</div>
</div>
{{/if}}

{{if $helpers}}
<div id="right_helpers" class="widget">
<h3>{{$helpers.title.1}}</h3>
<ul role="menu">
{{foreach $helpers_items as $i}}
	{{$i}}
{{/foreach}}
</ul>
</div>
{{/if}}

{{if $con_services}}
<div id="right_services" class="widget">
<h3>{{$con_services.title.1}}</h3>
<div id="right_services_icons">
{{foreach $connector_items as $i}}
	{{$i}}
{{/foreach}}
</div>
</div>
{{/if}}

{{if $nv}}
<div id="right_friends" class="widget">
<h3>{{$nv.title.1}}</h3>
<ul role="menu">
<li class="tool" role="menuitem"><a class="{{$nv.directory.2}}" href="{{$nv.directory.0}}" title="{{$nv.directory.3}}" >{{$nv.directory.1}}</a></li>
<li class="tool" role="menuitem"><a class="{{$nv.global_directory.2}}" href="{{$nv.global_directory.0}}" target="blank" title="{{$nv.global_directory.3}}" >{{$nv.global_directory.1}}</a></li>
<li class="tool" role="menuitem"><a class="{{$nv.match.2}}" href="{{$nv.match.0}}" title="{{$nv.match.3}}" >{{$nv.match.1}}</a></li>
<li class="tool" role="menuitem"><a class="{{$nv.suggest.2}}" href="{{$nv.suggest.0}}" title="{{$nv.suggest.3}}" >{{$nv.suggest.1}}</a></li>
<li class="tool" role="menuitem"><a class="{{$nv.invite.2}}" href="{{$nv.invite.0}}" title="{{$nv.invite.3}}" >{{$nv.invite.1}}</a></li>
</ul>
{{$nv.search}}
</div>
{{/if}}

{{if $lastusers_title}}
<div id="right_lastusers" class="widget">
<h3>{{$lastusers_title}}</h3>
<div id='lastusers-wrapper' class='items-wrapper'>
{{foreach $lastusers_items as $i}}
	{{$i}}
{{/foreach}}
</div>
</div>
{{/if}}

{{if $activeusers_title}}
<h3>{{$activeusers_title}}</h3>
<div class='items-wrapper'>
{{foreach $activeusers_items as $i}}
	{{$i}}
{{/foreach}}
</div>
{{/if}}
