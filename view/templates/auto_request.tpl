<h1>{{$header}}</h1>

{{if $myaddr == ""}}
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
{{/if}}

{{if $request}}
<form action="{{$request}}" method="post" />
{{else}}
<form action="dfrn_request/{{$nickname}}" method="post" />
{{/if}}

{{if $photo}}
<img src="{{$photo}}" alt="" id="dfrn-request-photo">
{{/if}}

{{if $url}}<dl><dt>{{$url_label}}</dt><dd><a target="blank" href="{{$zrl}}">{{$url}}</a></dd></dl>{{/if}}
{{if $location}}<dl><dt>{{$location_label}}</dt><dd>{{$location}}</dd></dl>{{/if}}
{{if $keywords}}<dl><dt>{{$keywords_label}}</dt><dd>{{$keywords}}</dd></dl>{{/if}}
{{if $about}}<dl><dt>{{$about_label}}</dt><dd>{{$about}}</dd></dl>{{/if}}

<div id="dfrn-request-url-wrapper" >
	<label id="dfrn-url-label" for="dfrn-url" >{{$your_address}}</label>
        {{if $myaddr}}
                {{$myaddr}}
                <input type="hidden" name="dfrn_url" id="dfrn-url" value="{{$myaddr|escape:'html'}}" />
        {{else}}
        <input type="text" name="dfrn_url" id="dfrn-url" size="32" value="{{$myaddr|escape:'html'}}" />
        {{/if}}
        {{if $url}}
                <input type="hidden" name="url" id="url" value="{{$url|escape:'html'}}" />
        {{/if}}
	<div id="dfrn-request-url-end"></div>
</div>


<div id="dfrn-request-info-wrapper" >

</div>

	<div id="dfrn-request-submit-wrapper">
		{{if $submit}}
			<input type="submit" name="submit" id="dfrn-request-submit-button" value="{{$submit|escape:'html'}}" />
		{{/if}}
		<input type="submit" name="cancel" id="dfrn-request-cancel-button" value="{{$cancel|escape:'html'}}" />
	</div>
</form>
