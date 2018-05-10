{{if count($reasons) > 1}}
<ul class="content-filter-reasons">
	{{foreach $reasons as $reason}}
	<li>{{$reason|escape:html}}</li>
	{{/foreach}}
</ul>
<p>
	<button type="button" id="content-filter-wrap-{{$rnd}}" class="btn btn-default btn-small content-filter-button" onclick="openClose('content-filter-{{$rnd}}');">
		<i class="glyphicon glyphicon-eye-open"></i> {{$openclose}}
	</button>
</p>
{{else}}
<p>
	{{$reasons.0|escape:html}}
	<button type="button" id="content-filter-wrap-{{$rnd}}" class="btn btn-default btn-xs content-filter-button" onclick="openClose('content-filter-{{$rnd}}');">
		<i class="glyphicon glyphicon-eye-open"></i> {{$openclose}}
	</button>
</p>
{{/if}}
<div id="content-filter-{{$rnd}}" class="content-filter-content" style="display: none;">
	{{$html}}
</div>
