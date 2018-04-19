
<div id="peoplefind-sidebar" class="widget">
	<h3>{{$nv.findpeople}}</h3>
	<div id="peoplefind-desc">{{$nv.desc}}</div>
	<form action="dirfind" method="get" />
		<input id="side-peoplefind-url" type="text" name="search" size="24" title="{{$nv.hint|escape:'html'}}" /><input id="side-peoplefind-submit" type="submit" name="submit" value="{{$nv.findthem|escape:'html'}}" />
	</form>
	<div class="side-link" id="side-match-link"><a href="match" >{{$nv.similar}}</a></div>
	<div class="side-link" id="side-suggest-link"><a href="suggest" >{{$nv.suggest}}</a></div>
	<div class="side-link" id="side-directory-link"><a href="directory" >{{$nv.local_directory}}</a></div>
	<div class="side-link" id="side-directory-link"><a href="{{$nv.global_dir}}" target="extlink" >{{$nv.directory}}</a></div>
	<div class="side-link" id="side-random-profile-link" ><a href="randprof" target="extlink" >{{$nv.random}}</a></div>
	{{if $nv.inv}} 
	<div class="side-link" id="side-invite-link" ><a href="invite" >{{$nv.inv}}</a></div>
	{{/if}}
</div>

