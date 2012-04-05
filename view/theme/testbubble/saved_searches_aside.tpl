<div class="widget" id="saved-search-list">
	<h3 id="search">$title</h3>
	$searchbox
	
	<ul id="saved-search-ul">
		{{ for $saved as $search }}
		<li class="saved-search-li clear">
			<a onmouseout="imgdull(this);" onmouseover="imgbright(this);" onclick="return confirmDelete();" class="icon savedsearchdrop drophide" href="network/?f=&amp;remove=1&amp;search=$search.encodedterm"></a>
			<a class="savedsearchterm" href="network/?f=&amp;search=$search.encodedterm">$search.term</a>
		</li>
		{{ endfor }}
	</ul>
	<div class="clear"></div>
</div>
