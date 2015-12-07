
{{include file="section_title.tpl"}}

{{if $gdirpath}}
	<ul>
		<li><div id="global-directory-link"><a href="{{$gdirpath}}">{{$globaldir}}</a></div></li>
	</ul>
{{/if}}


<div id="directory-search-wrapper">
	<form id="directory-search-form" action="directory" method="get" >
		<span class="dirsearch-desc">{{$desc}}</span>
		<input type="text" name="search" id="directory-search" class="search-input" onfocus="this.select();" value="{{$search|escape:'html'}}" />
		<input type="submit" name="submit" id="directory-search-submit" value="{{$submit|escape:'html'}}" class="button" />
	</form>
</div>

{{if $findterm}}
	<h4>{{$finding}} '{{$findterm}}'</h4>
{{/if}}

<div id="directory-search-end"></div>

{{foreach $contacts as $contact}}
	{{include file="contact_template.tpl"}}
{{/foreach}}

<div class="directory-end" ></div>

{{$paginate}}
