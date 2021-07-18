<div id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>

	<form action="{{$baseurl}}/admin/storage" method="post">
		<input type='hidden' name='form_security_token' value="{{$form_security_token}}">

		{{include file="field_select.tpl" field=$storagebackend}}
		{{foreach from=$storageform item=$field}}
		{{include file=$field.field field=$field}}
		{{/foreach}}

		<div class="submit"><input type="submit" name="page_logs" value="{{$submit}}" /></div>

	</form>
</div>
