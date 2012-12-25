<!-- TEMPLATE APPEARS UNUSED -->

<users type="array">
	{{foreach $users as $u}}
	{{include file="api_user_xml.tpl" user=$u}}
	{{/foreach}}
</users>
