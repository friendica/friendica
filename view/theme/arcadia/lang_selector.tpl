<div id="lang-select-icon" class="icon s22 language button" title="$title" onclick="toggle('language-selector');"></div>
<div id="language-selector" class="menu" onBlur="hide('language-selector');" tabindex="-1">
	<ul>
		{{ for $langs.0 as $v=>$l }}
		<li{{if $v==$langs.1}} class="selected"{{ endif }} onclick="set_language('$v');">$l</li>
		{{ endfor }}
	</ul>
	<form action="#" method="post" id="language-selector-form">
		<input type="hidden" name="system_language" value="$langs.1" id="language-selector-form-value" />
	</form>
</div>
