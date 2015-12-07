<div id="message-sidebar" class="widget">
	<div id="message-new"><a href="{{$new.url}}" accesskey="m" class="{{if $new.sel}}newmessage-selected{{/if}}">{{$new.label}}</a> </div>

  {{if $tabs}}
	<div id="message-preview">
		{{$tabs}}
	</div>
  {{/if}}

</div>
