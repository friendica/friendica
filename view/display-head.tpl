<script>
$(document).ready(function() {
	$(".comment-edit-wrapper textarea").contact_autocomplete(baseurl+"/acl");
	// make auto-complete work in more places
	$(".wall-item-comment-wrapper textarea").contact_autocomplete(baseurl+"/acl");
});
</script>

