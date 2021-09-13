<div id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>

		<h2>{{$label_current}}: <b>{{$storagebackend}}</b></h2>

		<h2>{{$label_config}}</h2>

		{{foreach from=$availablestorageforms item=$storage}}
	<form action="{{$baseurl}}/admin/storage/{{$storage.prefix}}" method="post">
		<input type='hidden' name='form_security_token' value="{{$form_security_token}}">
		<h3>{{$storage.name}}</h3>
		{{if $storage.form}}
			{{foreach from=$storage.form item=$field}}
				{{include file=$field.field field=$field}}
			{{/foreach}}
		{{else}}
			{{$noconfig}}
		{{/if}}

		{{if $storage.form}}
		<input type="submit" name="submit_save" value="{{$save}}"/>
		{{if $storage.active}}
		<input type="submit" name="submit_save_set" value="{{$save_reload}}"/>
		{{else}}
		<input type="submit" name="submit_save_set" value="{{$save_use}}"/>
		{{/if}}
		{{else}}
		<br /><input type="submit" name="submit_save_set" {{if $storage.active}}disabled="disabled"{{/if}} value="{{$use}}"/>
		{{/if}}
	</form>
		{{/foreach}}

</div>
