
<div id='adminpage'>
	<h1>$title - $page</h1>
	
	<form action="$baseurl/admin/site" method="post">
    <input type='hidden' name='form_security_token' value='$form_security_token'>

	{{ inc $field_input with $field=$sitename }}{{ endinc }}
	{{ inc $field_textarea with $field=$banner }}{{ endinc }}
	{{ inc $field_select with $field=$language }}{{ endinc }}
	{{ inc $field_select with $field=$theme }}{{ endinc }}
	{{ inc $field_select with $field=$theme_mobile }}{{ endinc }}
	{{ inc $field_select with $field=$ssl_policy }}{{ endinc }}
	
	<div class="submit"><input type="submit" name="page_site" value="$submit" /></div>
	
	<h3>$registration</h3>
	{{ inc $field_input with $field=$register_text }}{{ endinc }}
	{{ inc $field_select with $field=$register_policy }}{{ endinc }}
	{{ inc $field_input with $field=$daily_registrations }}{{ endinc }}	
	{{ inc $field_checkbox with $field=$no_multi_reg }}{{ endinc }}
	{{ inc $field_checkbox with $field=$no_openid }}{{ endinc }}
	{{ inc $field_checkbox with $field=$no_regfullname }}{{ endinc }}
	
	<div class="submit"><input type="submit" name="page_site" value="$submit" /></div>

	<h3>$upload</h3>
	{{ inc $field_input with $field=$maximagesize }}{{ endinc }}
	{{ inc $field_input with $field=$maximagelength }}{{ endinc }}
	{{ inc $field_input with $field=$jpegimagequality }}{{ endinc }}
	
	<h3>$corporate</h3>
	{{ inc $field_input with $field=$allowed_sites }}{{ endinc }}
	{{ inc $field_input with $field=$allowed_email }}{{ endinc }}
	{{ inc $field_checkbox with $field=$block_public }}{{ endinc }}
	{{ inc $field_checkbox with $field=$force_publish }}{{ endinc }}
	{{ inc $field_checkbox with $field=$no_community_page }}{{ endinc }}
	{{ inc $field_checkbox with $field=$ostatus_disabled }}{{ endinc }}
	{{ inc $field_checkbox with $field=$diaspora_enabled }}{{ endinc }}
	{{ inc $field_checkbox with $field=$dfrn_only }}{{ endinc }}
	{{ inc $field_input with $field=$global_directory }}{{ endinc }}
	{{ inc $field_checkbox with $field=$thread_allow }}{{ endinc }}
	{{ inc $field_checkbox with $field=$newuser_private }}{{ endinc }}
	
	<div class="submit"><input type="submit" name="page_site" value="$submit" /></div>
	
	<h3>$advanced</h3>
	{{ inc $field_checkbox with $field=$no_utf }}{{ endinc }}
	{{ inc $field_checkbox with $field=$verifyssl }}{{ endinc }}
	{{ inc $field_input with $field=$proxy }}{{ endinc }}
	{{ inc $field_input with $field=$proxyuser }}{{ endinc }}
	{{ inc $field_input with $field=$timeout }}{{ endinc }}
	{{ inc $field_input with $field=$delivery_interval }}{{ endinc }}
	{{ inc $field_input with $field=$poll_interval }}{{ endinc }}
	{{ inc $field_input with $field=$maxloadavg }}{{ endinc }}
	{{ inc $field_input with $field=$abandon_days }}{{ endinc }}
	
	<div class="submit"><input type="submit" name="page_site" value="$submit" /></div>
	
	</form>
</div>
