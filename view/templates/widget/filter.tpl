
<div id="{{$type}}-sidebar" class="widget">
	<h3>{{$title}}</h3>
	<div id="{{$type}}-desc">{{$desc nofilter}}</div>
	<ul role="menu" class="{{$type}}-ul">
		<li role="menuitem" {{if !$selected}}class="selected"{{/if}}><a href="{{$base}}" class="{{$type}}-link{{if !$selected}} {{$type}}-selected{{/if}} {{$type}}-all">{{$all_label}}</a></li>
		{{foreach $options as $option}}
			<li role="menuitem" {{if $selected == $option.ref}}class="selected"{{/if}}><a href="{{$base}}{{$type}}={{$option.ref}}" class="{{$type}}-link{{if $selected == $option.ref}} {{$type}}-selected{{/if}}">{{$option.name}}</a></li>
		{{/foreach}}
	</ul>
</div>
