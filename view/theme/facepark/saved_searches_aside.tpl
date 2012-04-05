<div class="widget" id="saved-search-list">
	<h3 id="search">$title</h3>
	$searchbox
	
	<ul id="saved-search-ul">
		{{ for $saved as $search }}
		<li class="saved-search-li clear">
			<a title="$search.delete" onclick="return confirmDelete();" id="drop-saved-search-term-$search.id" class="iconspacer savedsearchdrop " href="network/?f=&amp;remove=1&amp;search=$search.encodedterm"></a>
			<a id="saved-search-term-$search.id" class="savedsearchterm" href="network/?f=&amp;search=$search.encodedterm">$search.term</a>
		</li>
		{{ endfor }}
	</ul>
	<div class="clear"></div>
</div>
