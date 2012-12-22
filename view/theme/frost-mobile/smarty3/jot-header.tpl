
<script>
	var none = "none"; // ugly hack: {{$editselect}} shouldn't be a string if TinyMCE is enabled, but should if it isn't
	window.editSelect = {{$editselect}};
	window.isPublic = "{{$ispublic}}";
	window.nickname = "{{$nickname}}";
	window.linkURL = "{{$linkurl}}";
	window.vidURL = "{{$vidurl}}";
	window.audURL = "{{$audurl}}";
	window.whereAreU = "{{$whereareu}}";
	window.term = "{{$term}}";
	window.baseURL = "{{$baseurl}}";
	window.geoTag = function () { {{$geotag}} }
	window.ajaxType = 'jot-header';
</script>


