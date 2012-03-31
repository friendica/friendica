<h3 style="border-bottom: 1px solid #D2D2D2;">Settings Menu</h3>
<ul class="rs_tabs">
	{{ for $tabs as $tab }}
		<li><a href="$tab.url" class="rs_tab button $tab.sel">$tab.label</a></li>
	{{ endfor }}
</ul>
