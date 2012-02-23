<h3>$title</h3>

{{ for $contacts as $contact }}
	{{ inc contact_template.tpl }}{{ endinc }}
{{ endfor }}

<div id="view-contact-end"></div>

$paginate
