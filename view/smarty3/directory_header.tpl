<h1>{{$sitedir}}</h1>

{{$globaldir}}
{{$admin}}

{{$finding}}

<div id="directory-search-wrapper">
<form id="directory-search-form" action="directory" method="get" >
<span class="dirsearch-desc">{{$desc}}</span>
<input type="text" name="search" id="directory-search" class="search-input" onfocus="this.select();" value="{{$search}}" />
<input type="submit" name="submit" id="directory-search-submit" value="{{$submit}}" class="button" />
</form>
</div>
<div id="directory-search-end"></div>

