<h3 style="margin-top: 0px; padding-left: 0px; text-align: center;">Settings Menu</h3>
<ul class="rs_tabs">
	{{ for $tabs as $tab }}
		<li><a href="$tab.url" class="rs_tab button $tab.sel">$tab.label</a></li>
	{{ endfor }}
</ul>
