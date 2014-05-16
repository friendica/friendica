<div id="saved-search-list" class="widget">
	<h3 class="title">{{$title}}</h3>

	<ul id="saved-search-ul">
		{{foreach $saved as $search}}
			<li class="tool {{if $search.selected}}selected{{/if}}">
					<a href="network/?f=&search={{$search.encodedterm}}" class="label" >{{$search.term}}</a>
					<a href="network/?f=&remove=1&search={{$search.encodedterm}}" class="action icon s10 delete" title="{{$search.delete}}" onclick="return confirmDelete();"></a>
			</li>
		{{/foreach}}
	</ul>
	
	{{$searchbox}}
	
</div>
