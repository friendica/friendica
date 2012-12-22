
<h1>{{$header}}</h1>

<p id="dfrn-request-intro">
{{$page_desc}}<br />
<ul id="dfrn-request-networks">
<li><a href="http://friendica.com" title="{{$friendica}}">{{$friendica}}</a></li>
<li><a href="http://joindiaspora.com" title="{{$diaspora}}">{{$diaspora}}</a> {{$diasnote}}</li>
<li><a href="http://ostatus.org" title="{{$public_net}}" >{{$statusnet}}</a></li>
{{if $emailnet}}<li>{{$emailnet}}</li>{{/if}}
</ul>
</p>
<p>
{{$invite_desc}}
</p>
<p>
{{$desc}}
</p>

<form action="dfrn_request/{{$nickname}}" method="post" />

<div id="dfrn-request-url-wrapper" >
	<label id="dfrn-url-label" for="dfrn-url" >{{$your_address}}</label>
	<input type="text" name="dfrn_url" id="dfrn-url" size="32" value="{{$myaddr}}" />
	<div id="dfrn-request-url-end"></div>
</div>


<div id="dfrn-request-info-wrapper" >

</div>

	<div id="dfrn-request-submit-wrapper">
		<input type="submit" name="submit" id="dfrn-request-submit-button" value="{{$submit}}" />
		<input type="submit" name="cancel" id="dfrn-request-cancel-button" value="{{$cancel}}" />
	</div>
</form>
