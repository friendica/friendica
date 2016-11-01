
<h4>{{$account_types}}</h4>
{{include file="field_radio.tpl" field=$account_person}}
<div id="account-type-sub-0" class="pageflags">
	<h5>{{$user}}</h5>
	{{include file="field_radio.tpl" field=$page_normal}}
	{{include file="field_radio.tpl" field=$page_soapbox}}
	{{include file="field_radio.tpl" field=$page_freelove}}
</div>

{{include file="field_radio.tpl" field=$account_organisation}}
{{include file="field_radio.tpl" field=$account_news}}

{{include file="field_radio.tpl" field=$account_community}}
<div id="account-type-sub-3" class="pageflags">
	<h5>{{$community}}</h5>
	{{include file="field_radio.tpl" field=$page_community}}
	{{include file="field_radio.tpl" field=$page_prvgroup}}
</div>

<script language="javascript" type="text/javascript">
	// This js part changes the state of page-flags radio buttons according
	// to the selected account type. For a translation of the different
	// account-types and page-flags have a look in the define section in boot.php
	var accountType = {{$account_type}};

	$(document).ready(function(){
		// Hide all DIV for page-flags expet the one which belongs to the present
		// account-type
		showPageFlags(accountType);

		// Save the ID of the active page-flage
		var activeFlag = $('[id^=id_page-flags_]:checked');

		$("[id^=id_account-type_]").change(function(){
			// Since the ID of the radio buttons containing the type of
			// the account-type we catch the last character of the ID to
			// know for what account-type the radio button stands for.
			var type = this.id.substr(this.id.length - 1);

			// Hide all DIV with page-flags and show only the one which belongs
			// to the selected radio button
			showPageFlags(type);

			// Uncheck all page-flags radio buttons
			$('input:radio[name="page-flags"]').prop("checked", false);

			// If the selected account type is the active one mark the page-flag
			// radio button as checked which is already by database state
			if (accountType == type) {
				$(activeFlag).prop("checked", true);
			} else if (type == 1 || type == 2) {
				// For account-type 1 or 2 the page-flags are always set to 1
				$('#id_page-flags_1').prop("checked", true);
			} else {
				// Mark the first available page-flags radio button of the selected
				// account-type as checked
				$('#account-type-sub-' + type + ' input:radio[name="page-flags"]').first().prop("checked", true);
			}
		});
	});

	// Show/Hide the page-flags according to the selected account-type
	function showPageFlags(type) {
		$(".pageflags").hide();

		if (type == 0 || type == 3) {
			$("#account-type-sub-" + type).show();
		}
	}
</script>
