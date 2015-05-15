
<div id="follow-sidebar" class="widget">
	<h3>{{$connect}}</h3>
	<div id="connect-desc">{{$desc}}</div>
	<form action="follow" method="get" >
		<input id="side-follow-url" type="text" name="url" value="{{$value|escape:'html'}}" size="24" placeholder="{{$hint|escape:'html'}}" title="{{$hint|escape:'html'}}" /><input id="side-follow-submit" type="submit" name="submit" value="{{$follow|escape:'html'}}" />
	</form>
</div>

