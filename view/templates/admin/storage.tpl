<div id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>

		<h2>Current Storage Backend: <b>{{$storagebackend}}</b></h2>

		<h2>Storage Configuration</h2>

		{{foreach from=$availablestorageforms item=$storage}}
	<form action="{{$baseurl}}/admin/storage/{{$storage.prefix}}" method="post">
		<input type='hidden' name='form_security_token' value="{{$form_security_token}}">
		<h3>{{$storage.name}}</h3>
		{{foreach from=$storage.form item=$field}}
		{{include file=$field.field field=$field}}
		{{/foreach}}

		{{if $storage.form}}
		<div class="submit"><input type="submit" name="submit_save" value="{{$save}}" /></div>
		{{/if}}
		<div class="submit"><input type="submit" name="submit_save_set" value="{{$save_set}}" /></div>
	</form>
		{{/foreach}}

</div>
