{{if $title}}
<div class="section-title-wrapper{{if isset($pullright)}} pull-left{{/if}}">
	<h2>{{$title}}</h2>
	{{if ! isset($pullright)}}
	<div class="clear"></div>
	{{/if}}
</div>
{{/if}}
