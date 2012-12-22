<!-- TEMPLATE APPEARS UNUSED -->

<users type="array">
	{{foreach $users as $u}}
	{{include file="file:{{$api_user_xml}}" user=$u}}
	{{/foreach}}
</users>
