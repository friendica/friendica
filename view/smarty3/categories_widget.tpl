<div id="categories-sidebar" class="widget">
	<h3>{{$title}}</h3>
	<div id="nets-desc">{{$desc}}</div>
	
	<ul class="categories-ul">
		<li class="tool"><a href="{{$base}}" class="categories-link categories-all{{if $sel_all}} categories-selected{{/if}}">{{$all}}</a></li>
		{{foreach $terms as $term}}
			<li class="tool"><a href="{{$base}}?f=&category={{$term.name}}" class="categories-link{{if $term.selected}} categories-selected{{/if}}">{{$term.name}}</a></li>
		{{/foreach}}
	</ul>
	
</div>
