
<div id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>

	<p><span class='toggleaddon icon {{$status}}'></span> {{$info.name}} - {{$info.version}} : <a href="{{$baseurl}}/admin/{{$function}}/{{$addon}}/?a=t&amp;t={{$form_security_token}}">{{$action}}</a></p>
	<p>{{$info.description}}</p>

	<p class="author">{{$str_author}}
	{{foreach $info.author as $a name=authors}}
		{{if $a.link}}<a href="{{$a.link}}">{{$a.name}}</a>{{else}}{{$a.name}}{{/if}}{{if $smarty.foreach.authors.last}}{{else}}, {{/if}}
	{{/foreach}}
	</p>

	<p class="maintainer">{{$str_maintainer}}
	{{foreach $info.maintainer as $a name=maintainers}}
		{{if $a.link}}<a href="{{$a.link}}">{{$a.name}}</a>{{else}}{{$a.name}}{{/if}}{{if $smarty.foreach.maintainers.last}}{{else}}, {{/if}}
	{{/foreach}}
	</p>

	{{if $screenshot}}
	<a href="{{$screenshot.0}}" class='screenshot'><img src="{{$screenshot.0}}" alt="{{$screenshot.1}}" /></a>
	{{/if}}

	{{if $admin_form}}
	<h3>{{$settings}}</h3>
	<form method="post" action="{{$baseurl}}/admin/{{$function}}/{{$addon}}/">
		{{$admin_form}}
	</form>
	{{/if}}

	{{if $readme}}
	<h3>Readme</h3>
	<div id="addon_readme">
		{{$readme}}
	</div>
	{{/if}}
</div>
