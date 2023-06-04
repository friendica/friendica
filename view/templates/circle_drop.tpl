
<div class="circle-delete-wrapper button" id="circle-delete-wrapper-{{$id}}">
	<a href="circle/drop/{{$id}}?t={{$form_security_token}}"
		onclick="return confirmDelete();"
		id="circle-delete-icon-{{$id}}"
		class="icon drophide circle-delete-icon"
		onmouseover="imgbright(this);"
		onmouseout="imgdull(this);"
		title="{{$delete}}">
	</a>
</div>
<div class="circle-delete-end"></div>
