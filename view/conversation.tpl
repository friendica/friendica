{{ for $threads as $thread }}
<div class="tread-wrapper">
	$thread
</div>
{{ endfor }}

{{ if $dropping }}
<a href="#" onclick="deleteCheckedItems();return false;">
	<span class="icon s22 delete text">$dropping</span>
</a>
{{ endif }}
