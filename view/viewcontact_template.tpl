<h3>$title</h3>

{{ for $contacts as $c }}
	{{ inc $contact_template with $contact=$c }}{{ endinc }}
{{ endfor }}

<div id="view-contact-end"></div>

$paginate
