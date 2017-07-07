<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>
	<p>{{$intro1}}</p>
	<p>{{$intro2}}</p>
</div>

<form action="{{$baseurl}}/admin/deleteitem" method="post">
	<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
	{{include file="field_input.tpl" field=$deleteitemguid}}
	<div class="submit"><input type="submit" name="page_deleteitem_submit" value="{{$submit}}" /></div>
</form>
