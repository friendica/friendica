<h1>$header{{ if $total }} ($total){{ endif }}</h1>

{{ if $finding }}<h4>$finding</h4>{{ endif }}

<div id="contacts-search-wrapper">
<form id="contacts-search-form" action="$cmd" method="get" >
<span class="contacts-search-desc">$desc</span>
<input type="text" name="search" id="contacts-search" class="search-input" onfocus="this.select();" value="$search" />
<input type="submit" name="submit" id="contacts-search-submit" value="$submit" />
</form>
</div>
<div id="contacts-search-end"></div>

$tabs


{{ for $contacts as $contact }}
	{{ inc contact_template.tpl }}{{ endinc }}
{{ endfor }}
<div id="contact-edit-end"></div>

$paginate




