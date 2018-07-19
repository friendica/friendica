
<div class="pull-left">
<h3 class="heading">{{$messages}}</h3>
</div>

<div id="message-new" class="pull-right">
	{{if $button.sel == "new"}}
	<a href="{{$button.url}}" accesskey="m" class="newmessage-selected" title="{{$button.label}}" data-toggle="tooltip">
	<i class="faded-icon fa fa-plus"></i>
	</a>
	{{else}}
	<a href="{{$button.url}}" title="{{$button.label}}" data-toggle="tooltip">
	<i class="faded-icon fa fa-close"></i>
	</a>
	{{/if}}
</div>

<div class="clear"></div>
{{$tab_content}}
