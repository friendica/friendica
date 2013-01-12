<h1>$header</h1>

{{ for $contacts as $contact }}
	{{ inc contact_template.tpl }}{{ endinc }}
{{ endfor }}
<div id="contact-edit-end"></div>

$paginate




