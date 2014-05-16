<div id="message-sidebar" class="widget">
	<div id="message-new" class="{{if $new.sel}}selected{{/if}}"><a href="{{$new.url}}">{{$new.label}}</a> </div>
	
	<ul class="message-ul">
		{{foreach $tabs as $t}}
			<li class="tool {{if $t.sel}}selected{{/if}}"><a href="{{$t.url}}" class="message-link">{{$t.label}}</a></li>
		{{/foreach}}
	</ul>
	
</div>
