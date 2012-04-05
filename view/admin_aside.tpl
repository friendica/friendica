<script>
	// update pending count //
	$(function(){

		$("nav").bind('nav-update',  function(e,data){
			var elm = $('#pending-update');
			var register = $(data).find('register').text();
			if (register=="0") { reigster=""; elm.hide();} else { elm.show(); }
			elm.html(register);
		});
	});
</script>
<h4><a href="$admurl">Admin</a></h4>
<ul class='admin linklist'>
	<li class='admin link button $admin.site.2'><a href='$admin.site.0'>$admin.site.1</a></li>
	<li class='admin link button $admin.users.2'><a href='$admin.users.0'>$admin.users.1</a><span id='pending-update' title='$h_pending'></span></li>
	<li class='admin link button $admin.plugins.2'><a href='$admin.plugins.0'>$admin.plugins.1</a></li>
	<li class='admin link button $admin.themes.2'><a href='$admin.themes.0'>$admin.themes.1</a></li>
</ul>

<ul class='admin linklist'>
	<li class='admin link button $admin.update.2'><a href='$admin.update.0'>$admin.update.1</a></li>
</ul>


{{ if $admin.plugins_admin }}<h4>Plugins</h4>{{ endif }}
<ul class='admin linklist'>
	{{ for $admin.plugins_admin as $l }}
	<li class='admin link button $l.2'><a href='$l.0'>$l.1</a></li>
	{{ endfor }}
</ul>
	
	
<h4>Logs</h4>
<ul class='admin linklist'>
	<li class='admin link button $admin.logs.2'><a href='$admin.logs.0'>$admin.logs.1</a></li>
</ul>

