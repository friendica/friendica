<script>
	$(function(){

		$("#cnftheme").click(function(){
			$.colorbox({
				width: 800,
				height: '90%',
				/*onOpen: function(){
					var theme = $("#id_theme :selected").val();
					$("#cnftheme").attr('href',"{{$baseurl}}/admin/themes/"+theme);
				},*/
				href: "{{$baseurl}}/admin/themes/" + $("#id_theme :selected").val(),
				onComplete: function(){
					$("div#fancybox-content form").submit(function(e){
						var url = $(this).attr('action');
						// can't get .serialize() to work...
						var data={};
						$(this).find("input").each(function(){
							data[$(this).attr('name')] = $(this).val();
						});
						$(this).find("select").each(function(){
							data[$(this).attr('name')] = $(this).children(":selected").val();
						});
						console.log(":)", url, data);

						$.post(url, data, function(data) {
							if(timer) clearTimeout(timer);
							NavUpdate();
							$.colorbox.close();
						})

						return false;
					});

				}
			});
			return false;
		});
	});
</script>
<div id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>

	<form action="{{$baseurl}}/admin/site" method="post">
    <input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

	{{include file="field_input.tpl" field=$sitename}}
	{{include file="field_input.tpl" field=$hostname}}
	{{include file="field_input.tpl" field=$sender_email}}
	{{include file="field_textarea.tpl" field=$banner}}
	{{include file="field_input.tpl" field=$shortcut_icon}}
	{{include file="field_input.tpl" field=$touch_icon}}
	{{include file="field_textarea.tpl" field=$info}}
	{{include file="field_select.tpl" field=$language}}
	{{include file="field_select.tpl" field=$theme}}
	{{include file="field_select.tpl" field=$theme_mobile}}
	{{include file="field_select.tpl" field=$ssl_policy}}
	{{if $ssl_policy.2 == 1}}{{include file="field_checkbox.tpl" field=$force_ssl}}{{/if}}
	{{include file="field_checkbox.tpl" field=$hide_help}}
	{{include file="field_select.tpl" field=$singleuser}}
	<div class="submit"><input type="submit" name="page_site" value="{{$submit|escape:'html'}}" /></div>

	<h3>{{$registration}}</h3>
	{{include file="field_input.tpl" field=$register_text}}
	{{include file="field_select.tpl" field=$register_policy}}
	{{include file="field_input.tpl" field=$daily_registrations}}
	{{include file="field_checkbox.tpl" field=$no_multi_reg}}
	{{include file="field_checkbox.tpl" field=$no_openid}}
	{{include file="field_checkbox.tpl" field=$no_regfullname}}
	<div class="submit"><input type="submit" name="page_site" value="{{$submit|escape:'html'}}" /></div>

	<h3>{{$upload}}</h3>
	{{include file="field_input.tpl" field=$maximagesize}}
	{{include file="field_input.tpl" field=$maximagelength}}
	{{include file="field_input.tpl" field=$jpegimagequality}}
	<div class="submit"><input type="submit" name="page_site" value="{{$submit|escape:'html'}}" /></div>

	<h3>{{$corporate}}</h3>
	{{include file="field_input.tpl" field=$allowed_sites}}
	{{include file="field_input.tpl" field=$allowed_email}}
	{{include file="field_checkbox.tpl" field=$block_public}}
	{{include file="field_checkbox.tpl" field=$force_publish}}
	{{include file="field_select.tpl" field=$community_page_style}}
	{{include file="field_input.tpl" field=$max_author_posts_community_page}}

	{{if $thread_allow.2}}
		{{include file="field_checkbox.tpl" field=$ostatus_disabled}}
		{{include file="field_select.tpl" field=$ostatus_poll_interval}}
		{{include file="field_checkbox.tpl" field=$ostatus_full_threads}}
	{{else}}
		<div class='field checkbox' id='div_id_{{$ostatus_disabled.0}}'>
			<label for='id_{{$ostatus_disabled.0}}'>{{$ostatus_disabled.1}}</label>
			<span id='id_{{$ostatus_disabled.0}}'>{{$ostatus_not_able}}</span>
		</div>
	{{/if}}

	{{if $diaspora_able}}
		{{include file="field_checkbox.tpl" field=$diaspora_enabled}}
	{{else}}
		<div class='field checkbox' id='div_id_{{$diaspora_enabled.0}}'>
			<label for='id_{{$diaspora_enabled.0}}'>{{$diaspora_enabled.1}}</label>
			<span id='id_{{$diaspora_enabled.0}}'>{{$diaspora_not_able}}</span>
		</div>
	{{/if}}
	{{include file="field_checkbox.tpl" field=$dfrn_only}}
	{{include file="field_input.tpl" field=$global_directory}}
	{{include file="field_checkbox.tpl" field=$thread_allow}}
	{{include file="field_checkbox.tpl" field=$newuser_private}}
	{{include file="field_checkbox.tpl" field=$enotify_no_content}}
	{{include file="field_checkbox.tpl" field=$private_addons}}
	{{include file="field_checkbox.tpl" field=$disable_embedded}}
	{{include file="field_checkbox.tpl" field=$allow_users_remote_self}}
	<div class="submit"><input type="submit" name="page_site" value="{{$submit|escape:'html'}}" /></div>

	<h3>{{$advanced}}</h3>
	{{include file="field_select.tpl" field=$rino}}
	{{include file="field_checkbox.tpl" field=$verifyssl}}
	{{include file="field_input.tpl" field=$proxy}}
	{{include file="field_input.tpl" field=$proxyuser}}
	{{include file="field_input.tpl" field=$timeout}}
	{{include file="field_input.tpl" field=$maxloadavg}}
	{{include file="field_input.tpl" field=$maxloadavg_frontend}}
	{{include file="field_input.tpl" field=$optimize_max_tablesize}}
	{{include file="field_input.tpl" field=$optimize_fragmentation}}
	{{include file="field_input.tpl" field=$abandon_days}}
	{{include file="field_input.tpl" field=$temppath}}
	{{include file="field_input.tpl" field=$basepath}}
	{{include file="field_checkbox.tpl" field=$suppress_tags}}
	{{include file="field_checkbox.tpl" field=$nodeinfo}}
	<div class="submit"><input type="submit" name="page_site" value="{{$submit|escape:'html'}}" /></div>

	<h3>{{$portable_contacts}}</h3>
	{{include file="field_checkbox.tpl" field=$poco_completion}}
	{{include file="field_input.tpl" field=$poco_requery_days}}
	{{include file="field_select.tpl" field=$poco_discovery}}
	{{include file="field_select.tpl" field=$poco_discovery_since}}
	{{include file="field_checkbox.tpl" field=$poco_local_search}}
	<div class="submit"><input type="submit" name="page_site" value="{{$submit|escape:'html'}}" /></div>

	<h3>{{$performance}}</h3>
	{{include file="field_checkbox.tpl" field=$only_tag_search}}
	{{include file="field_input.tpl" field=$itemcache}}
	{{include file="field_input.tpl" field=$itemcache_duration}}
	{{include file="field_input.tpl" field=$max_comments}}
	{{include file="field_checkbox.tpl" field=$proxy_disabled}}
	<div class="submit"><input type="submit" name="page_site" value="{{$submit|escape:'html'}}" /></div>

	<h3>{{$worker_title}}</h3>
	{{include file="field_input.tpl" field=$worker_queues}}
	{{include file="field_checkbox.tpl" field=$worker_dont_fork}}
	{{include file="field_checkbox.tpl" field=$worker_fastlane}}
	{{include file="field_checkbox.tpl" field=$worker_frontend}}
	<div class="submit"><input type="submit" name="page_site" value="{{$submit|escape:'html'}}" /></div>

	</form>

	{{* separate form for relocate... *}}
	<form action="{{$baseurl}}/admin/site" method="post">
	<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
	<h3>{{$relocate}}</h3>
	{{include file="field_input.tpl" field=$relocate_url}}
	<input type="hidden" name="page_site" value="{{$submit|escape:'html'}}">
	<div class="submit"><input type="submit" name="relocate" value="{{$submit|escape:'html'}}" /></div>
	</form>

</div>
