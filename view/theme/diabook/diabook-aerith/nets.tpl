<div id="profile_side">
	<h3 style="margin-left: 2px;">$title</h3>
	<div id="nets-desc">$desc</div>
   
	<ul class="menu-profile-side">
	<li class="menu-profile-list">
	<span class="menu-profile-icon {{ if $sel_all }}group_selected{{else}}group_unselected{{ endif }}"></span>	
	<a style="text-decoration: none;" href="$base" class="menu-profile-list-item">$all</a></li>
	{{ for $nets as $net }}
	<li class="menu-profile-list">
	<span class="menu-profile-icon {{ if $net.selected }}group_selected{{else}}group_unselected{{ endif }}"></span>	
	<a href="$base?nets=$net.ref" class="menu-profile-list-item">$net.name</a></li>
	{{ endfor }}
	</ul>
</div>
