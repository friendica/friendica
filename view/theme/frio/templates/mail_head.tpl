
<div class="pull-left">
	<h3 class="heading">{{$messages}}</h3>
</div>

<div id="message-new" class="pull-right">
	{{if $button.sel == "new"}}
	<a href="{{$button.url}}" accesskey="m" class="newmessage-selected faded-icon page-action" title="{{$button.label}}" data-toggle="tooltip">
		<i class="fa fa-plus"></i>
	</a>
	{{else}}
	<a href="{{$button.url}}" title="{{$button.label}}" class="faded-icon page-action" data-toggle="tooltip">
		<i class="fa fa-close"></i>
	</a>
	{{/if}}
</div>

<div class="clear"></div>
{{$tab_content}}
