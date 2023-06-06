{{if $page}}
<div id="right_pages" class="widget">
<div>{{$page nofilter}}</div>
</div>
{{/if}}

{{if $community_profiles_title}}
<div id="right_profiles" class="widget">
<h3>{{$community_profiles_title}}</h3>
<div id='lastusers-wrapper' class='items-wrapper'>
{{foreach $community_profiles_items as $i}}
	{{$i nofilter}}
{{/foreach}}
</div>
<div class="clear"></div>
</div>
{{/if}}

{{if $helpers}}
<div id="right_helpers" class="widget">
<h3>{{$helpers.title.1}}</h3>
<ul role="menu">
{{foreach $helpers_items as $i}}
	{{$i nofilter}}
{{/foreach}}
</ul>
</div>
{{/if}}

{{if $con_services}}
<div id="right_services" class="widget">
<h3>{{$con_services.title.1}}</h3>
<div id="right_services_icons">
{{foreach $connector_items as $i}}
	{{$i nofilter}}
{{/foreach}}
</div>
</div>
{{/if}}

{{if $nv}}
{{include file='widget/peoplefind.tpl' nv=$nv}}
{{/if}}

{{if $lastusers_title}}
<div id="right_lastusers" class="widget">
<h3>{{$lastusers_title}}</h3>
<div id='lastusers-wrapper' class='items-wrapper'>
{{foreach $lastusers_items as $i}}
	{{$i nofilter}}
{{/foreach}}
</div>
<div class="clear"></div>
</div>
{{/if}}

{{if $activeusers_title}}
<h3>{{$activeusers_title}}</h3>
<div class='items-wrapper'>
{{foreach $activeusers_items as $i}}
	{{$i nofilter}}
{{/foreach}}
</div>
{{/if}}
