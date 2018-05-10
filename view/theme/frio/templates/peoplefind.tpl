<div id="peoplefind-sidebar" class="widget">
	<h3>{{$nv.findpeople}}</h3>

	<form action="dirfind" method="get">
		{{* The search field *}}
		<label for="side-peoplefind-url" id="peoplefind-desc">{{$nv.desc}}</label>
		<div class="form-group form-group-search">
			<input id="side-peoplefind-url" class="search-input form-control form-search" type="text" name="search" data-toggle="tooltip" title="{{$nv.hint|escape:'html'}}" />
			<button id="side-peoplefind-submit" class="btn btn-default btn-sm form-button-search" type="submit">{{$nv.findthem|escape: 'html'}}</button>
		</div>
	</form>

	{{* Directory links *}}
	<div class="side-link" id="side-directory-link"><a href="directory" >{{$nv.local_directory}}</a></div>
	<div class="side-link" id="side-directory-link"><a href="{{$nv.global_dir}}" target="extlink" >{{$nv.directory}}</a></div>
	{{* Additional links *}}
	<div class="side-link" id="side-match-link"><a href="match" >{{$nv.similar}}</a></div>
	<div class="side-link" id="side-suggest-link"><a href="suggest" >{{$nv.suggest}}</a></div>

	{{if $nv.inv}} 
	<div class="side-link" id="side-invite-link" ><button type="button" class="btn-link" onclick="addToModal('invite'); return false;">{{$nv.inv}}</button></div>
	{{/if}}
</div>
