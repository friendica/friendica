
<script language="javascript" type="text/javascript">
	window.nickname = "{{$nickname}}";
	window.linkURL = "{{$linkurl}}";
	var none = "none";	// ugly hack: {{$editselect}} shouldn't be a string if TinyMCE is enabled, but should if it isn't
	window.editSelect = {{$editselect}};
	window.ajaxType = 'msg-header';
	window.autocompleteType = 'msg-header';
</script>

