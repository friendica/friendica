<div id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>

	<form action="{{$baseurl}}/admin/storage" method="post">
		<input type='hidden' name='form_security_token' value="{{$form_security_token}}">

		<h2>Storage Backend</h2>

		{{include file="field_select.tpl" field=$storagebackend}}
		<div class="submit"><input type="submit" name="page_storage" value="{{$submit}}" /></div>

		<h2>Storage Configuration</h2>

		{{foreach from=$availablestorageforms item=$storage}}
		<h3>{{$storage.name}}</h3>
		{{foreach from=$storage.form item=$field}}
		{{include file=$field.field field=$field}}
		{{/foreach}}
		{{/foreach}}

		<div class="submit"><input type="submit" name="page_storage" value="{{$submit}}" /></div>

	</form>
</div>
