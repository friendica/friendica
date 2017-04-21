
<script language="javascript" type="text/javascript">
$(document).ready(function() {
	// Add contact_filter autocompletion to the search field
	$("#contacts-search").contact_filter(baseurl + '/acl', 'r', true);

	// Hide the viewcontact_wrapper if there is an input in the search field
	// We are doing this to let the the contact_filter replace the original 
	// shown contacts
	$("#contacts-search").keyup(function(){
		var elText = $(this).val();
		if(elText.length !== 0) {
			$("#viewcontact_wrapper").hide();
			$("ul.textcomplete-dropdown").addClass("show media-list");
		} else {
			$("#viewcontact_wrapper").show();
			$("ul.textcomplete-dropdown").removeClass("show");
		}
	});
	// initiale autosize for the textareas
	autosize($("textarea.text-autosize"));

});
</script>

