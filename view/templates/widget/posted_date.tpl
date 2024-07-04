<script>
function showHideDates() {
	if( $('#posted-date-selector-drop').is(':visible')) {
		$('#posted-date-selector-drop').hide();
		$('#posted-date-collapse').html('{{$showmore}}');

	} else {
		$('#posted-date-selector-drop').show();
		$('#posted-date-collapse').html('{{$showless}}');
	}
}
</script>

<span id="datebrowse-sidebar-inflated" class="widget inflated fakelink" onclick="openCloseWidget('datebrowse-sidebar', 'datebrowse-sidebar-inflated');">
	<h3>{{$title}}</h3>
</span>
<div id="datebrowse-sidebar" class="widget">
	<span class="fakelink" onclick="openCloseWidget('datebrowse-sidebar', 'datebrowse-sidebar-inflated');">
		<h3>{{$title}}</h3>
	</span>
	<ul id="posted-date-selector" class="datebrowse-ul">
		{{foreach $dates as $y => $arr}}

		{{if $y == $cutoff_year}}
		</ul>
		<ul id="posted-date-selector-drop" class="datebrowse-ul" style="display: none;">
		{{/if}}

		<li id="posted-date-selector-year-{{$y}}" class="tool">
			<a class="datebrowse-link" href="#" onclick="openClose('posted-date-selector-{{$y}}'); return false;">{{$y}}</a>
		</li>
		<li id="posted-date-selector-{{$y}}" class="tool posted-date-selector-months" style="display: none;">
			<ul class="datebrowse-ul">
				{{if $y|cat:$thisday >= $cutoffday}}
				<li class="tool">
					<a class="datebrowse-link" href="{{$url}}/{{$y|cat:$nextday}}/{{$y|cat:$thisday}}">{{$onthisdate}}</a>
				</li>
				{{/if}}
				{{foreach $arr as $d}}
				<li class="tool">
					<a class="datebrowse-link" href="{{$url}}/{{$d.1}}/{{$d.2}}">{{$d.0}}</a>
				</li>

				{{/foreach}}
			</ul>
		</li>
		{{/foreach}}
	</ul>
	{{if $cutoff}}
	<ul class="datebrowse-ul">
		<li onclick="showHideDates(); return false;" id="posted-date-collapse" class="fakelink tool">{{$showmore}}</li>
	</ul>
	{{/if}}
</div>
<script>
initWidget('datebrowse-sidebar', 'datebrowse-sidebar-inflated');
</script>
