
<div id="peoplefind-sidebar" class="widget">
	<h3>{{$findpeople}}</h3>
	<div id="peoplefind-desc">{{$desc}}</div>
	<form action="dirfind" method="get" />
		<input id="side-peoplefind-url" type="text" name="search" size="24" title="{{$hint|escape:'html'}}" /><input id="side-peoplefind-submit" type="submit" name="submit" value="{{$findthem|escape:'html'}}" />
	</form>
	<div class="side-link" id="side-match-link"><a href="match" >{{$similar}}</a></div>
	<div class="side-link" id="side-suggest-link"><a href="suggest" >{{$suggest}}</a></div>
	<div class="side-link" id="side-directory-link"><a href="{{$global_dir}}" target="extlink" >{{$directory}}</a></div>
	<div class="side-link" id="side-random-profile-link" ><a href="randprof" target="extlink" >{{$random}}</a></div>
	{{if $inv}} 
	<div class="side-link" id="side-invite-link" ><a href="invite" >{{$inv}}</a></div>
	{{/if}}
</div>

