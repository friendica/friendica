

<div id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>
	
	<form action="{{$baseurl}}/admin/site" method="post">
    <input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

	{{include file="field_input.tpl" field=$sitename}}
	{{include file="field_textarea.tpl" field=$banner}}
	{{include file="field_select.tpl" field=$language}}
	{{include file="field_select.tpl" field=$theme}}
	{{include file="field_select.tpl" field=$theme_mobile}}
	{{include file="field_select.tpl" field=$ssl_policy}}
	{{include file="field_checkbox.tpl" field=$old_share}}
	{{include file="field_checkbox.tpl" field=$hide_help}} 
	{{include file="field_select.tpl" field=$singleuser}}
	
	<div class="submit"><input type="submit" name="page_site" value="{{$submit}}" /></div>
	
	<h3>{{$registration}}</h3>
	{{include file="field_input.tpl" field=$register_text}}
	{{include file="field_select.tpl" field=$register_policy}}
	{{include file="field_input.tpl" field=$daily_registrations}}
	{{include file="field_checkbox.tpl" field=$no_multi_reg}}
	{{include file="field_checkbox.tpl" field=$no_openid}}
	{{include file="field_checkbox.tpl" field=$no_regfullname}}
	
	<div class="submit"><input type="submit" name="page_site" value="{{$submit}}" /></div>

	<h3>{{$upload}}</h3>
	{{include file="field_input.tpl" field=$maximagesize}}
	{{include file="field_input.tpl" field=$maximagelength}}
	{{include file="field_input.tpl" field=$jpegimagequality}}
	
	<h3>{{$corporate}}</h3>
	{{include file="field_input.tpl" field=$allowed_sites}}
	{{include file="field_input.tpl" field=$allowed_email}}
	{{include file="field_checkbox.tpl" field=$block_public}}
	{{include file="field_checkbox.tpl" field=$force_publish}}
	{{include file="field_checkbox.tpl" field=$no_community_page}}
	{{include file="field_checkbox.tpl" field=$ostatus_disabled}}
	{{include file="field_select.tpl" field=$ostatus_poll_interval}} 
	{{include file="field_checkbox.tpl" field=$diaspora_enabled}}
	{{include file="field_checkbox.tpl" field=$dfrn_only}}
	{{include file="field_input.tpl" field=$global_directory}}
	{{include file="field_checkbox.tpl" field=$thread_allow}}
	{{include file="field_checkbox.tpl" field=$newuser_private}}
	{{include file="field_checkbox.tpl" field=$enotify_no_content}}
	{{include file="field_checkbox.tpl" field=$private_addons}}	
	{{include file="field_checkbox.tpl" field=$disable_embedded}}	
	<div class="submit"><input type="submit" name="page_site" value="{{$submit}}" /></div>
	
	<h3>{{$advanced}}</h3>
	{{include file="field_checkbox.tpl" field=$verifyssl}}
	{{include file="field_input.tpl" field=$proxy}}
	{{include file="field_input.tpl" field=$proxyuser}}
	{{include file="field_input.tpl" field=$timeout}}
	{{include file="field_input.tpl" field=$delivery_interval}}
	{{include file="field_input.tpl" field=$poll_interval}}
	{{include file="field_input.tpl" field=$maxloadavg}}
	{{include file="field_input.tpl" field=$abandon_days}}
	{{include file="field_input.tpl" field=$lockpath}}
	{{include file="field_input.tpl" field=$temppath}}
	{{include file="field_input.tpl" field=$basepath}}

	<h3>{{$performance}}</h3>
	{{include file="field_input.tpl" field=$itemcache}}
	{{include file="field_input.tpl" field=$itemcache_duration}}

	
	<div class="submit"><input type="submit" name="page_site" value="{{$submit}}" /></div>
	
	</form>
</div>
