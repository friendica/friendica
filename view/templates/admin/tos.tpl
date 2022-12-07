<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>
  <p>{{$intro}}</p>
	<form action="{{$baseurl}}/admin/tos" method="post">
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
		{{include file="field_checkbox.tpl" field=$displaytos}}
		{{include file="field_checkbox.tpl" field=$displayprivstatement}}
		{{include file="field_textarea.tpl" field=$tostext}}
		{{include file="field_textarea.tpl" field=$tosrules}}
		<div class="submit"><input type="submit" name="page_tos" value="{{$submit}}" /></div>
	</form>
	<h2>{{$preview}}</h2>
	{{for $i=1 to 3}}
	<p>{{$privtext[$i] nofilter}}</p>
	{{/for}}
</div>

