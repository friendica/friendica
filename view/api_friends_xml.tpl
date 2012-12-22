<!-- TEMPLATE APPEARS UNUSED -->

<users type="array">
	{{for $users as $u }}
	{{inc $api_user_xml with $user=$u }}{{endinc}}
	{{endfor}}
</users>
