<users type="array">
	{{for $users as $user }}
	{{inc api_user_xml.tpl }}{{endinc}}
	{{endfor}}
</users>
