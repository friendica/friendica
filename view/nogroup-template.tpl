<h1>$header</h1>

{{ for $contacts as $c }}
	{{ inc contact_template.tpl with $contact=$c }}{{ endinc }}
{{ endfor }}
<div id="contact-edit-end"></div>

$paginate




