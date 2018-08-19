
{{if $saved}}
<div class="widget" id="saved-search-list">
	<h3 id="search">{{$title}}</h3>

	<ul role="menu" id="saved-search-ul">
		{{foreach $saved as $search}}
		<li role="menuitem" class="saved-search-li clear">
			<a title="{{$search.delete}}" onclick="return confirmDelete();" id="drop-saved-search-term-{{$search.id}}" class="savedsearchdrop pull-right widget-action faded-icon" href="network/?f=&amp;remove=1&amp;search={{$search.encodedterm}}">
				<i class="fa fa-trash" aria-hidden="true"></i>
			</a>
			<a id="saved-search-term-{{$search.id}}" class="savedsearchterm" href="search?search={{$search.encodedterm}}">{{$search.term}}</a>
		</li>
		{{/foreach}}
	</ul>
	<div class="clearfix"></div>
</div>
{{/if}}
