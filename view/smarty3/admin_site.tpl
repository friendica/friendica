<script>
	$(function(){
		
		$("#cnftheme").fancybox({
			width: 800,
			autoDimensions: false,
			onStart: function(){
				var theme = $("#id_theme :selected").val();
				var theme_mobile = $("#id_theme_mobile :selected").val();
				$("#cnftheme").attr('href',"{{$baseurl}}/admin/themes/"+theme);
			}, 
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
						$.fancybox.close();
					})
					
					return false;
				});
				
			}
		});
	});
</script>
<div id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>
	
	<form action="{{$baseurl}}/admin/site" method="post">
    <input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

	{{include file="file:{{$field_input}}" field=$sitename}}
	{{include file="file:{{$field_textarea}}" field=$banner}}
	{{include file="file:{{$field_select}}" field=$language}}
	{{include file="file:{{$field_select}}" field=$theme}}
	{{include file="file:{{$field_select}}" field=$theme_mobile}}
	{{include file="file:{{$field_select}}" field=$ssl_policy}}
	{{include file="file:{{$field_checkbox}}" field=$new_share}}

	
	<div class="submit"><input type="submit" name="page_site" value="{{$submit}}" /></div>
	
	<h3>{{$registration}}</h3>
	{{include file="file:{{$field_input}}" field=$register_text}}
	{{include file="file:{{$field_select}}" field=$register_policy}}
	{{include file="file:{{$field_input}}" field=$daily_registrations}}
	{{include file="file:{{$field_checkbox}}" field=$no_multi_reg}}
	{{include file="file:{{$field_checkbox}}" field=$no_openid}}
	{{include file="file:{{$field_checkbox}}" field=$no_regfullname}}
	
	<div class="submit"><input type="submit" name="page_site" value="{{$submit}}" /></div>

	<h3>{{$upload}}</h3>
	{{include file="file:{{$field_input}}" field=$maximagesize}}
	{{include file="file:{{$field_input}}" field=$maximagelength}}
	{{include file="file:{{$field_input}}" field=$jpegimagequality}}
	
	<h3>{{$corporate}}</h3>
	{{include file="file:{{$field_input}}" field=$allowed_sites}}
	{{include file="file:{{$field_input}}" field=$allowed_email}}
	{{include file="file:{{$field_checkbox}}" field=$block_public}}
	{{include file="file:{{$field_checkbox}}" field=$force_publish}}
	{{include file="file:{{$field_checkbox}}" field=$no_community_page}}
	{{include file="file:{{$field_checkbox}}" field=$ostatus_disabled}}
	{{include file="file:{{$field_checkbox}}" field=$diaspora_enabled}}
	{{include file="file:{{$field_checkbox}}" field=$dfrn_only}}
	{{include file="file:{{$field_input}}" field=$global_directory}}
	{{include file="file:{{$field_checkbox}}" field=$thread_allow}}
	{{include file="file:{{$field_checkbox}}" field=$newuser_private}}
	
	<div class="submit"><input type="submit" name="page_site" value="{{$submit}}" /></div>
	
	<h3>{{$advanced}}</h3>
	{{include file="file:{{$field_checkbox}}" field=$no_utf}}
	{{include file="file:{{$field_checkbox}}" field=$verifyssl}}
	{{include file="file:{{$field_input}}" field=$proxy}}
	{{include file="file:{{$field_input}}" field=$proxyuser}}
	{{include file="file:{{$field_input}}" field=$timeout}}
	{{include file="file:{{$field_input}}" field=$delivery_interval}}
	{{include file="file:{{$field_input}}" field=$poll_interval}}
	{{include file="file:{{$field_input}}" field=$maxloadavg}}
	{{include file="file:{{$field_input}}" field=$abandon_days}}
	{{include file="file:{{$field_input}}" field=$lockpath}}
	{{include file="file:{{$field_input}}" field=$temppath}}
	{{include file="file:{{$field_input}}" field=$basepath}}

	<h3>{{$performance}}</h3>
	{{include file="file:{{$field_checkbox}}" field=$use_fulltext_engine}}
	{{include file="file:{{$field_input}}" field=$itemcache}}
	{{include file="file:{{$field_input}}" field=$itemcache_duration}}

	
	<div class="submit"><input type="submit" name="page_site" value="{{$submit}}" /></div>
	
	</form>
</div>
