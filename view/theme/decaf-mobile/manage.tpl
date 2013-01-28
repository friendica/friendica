<h3>$title</h3>
<div id="identity-manage-desc">$desc</div>
<div id="identity-manage-choose">$choose</div>
<div id="identity-selector-wrapper">
	<form action="manage" method="post" >
	<select name="identity" size="4" onchange="this.form.submit();" >

	{{ for $identities as $id }}
		<option $id.selected value="$id.uid">$id.username ($id.nickname)</option>
	{{ endfor }}

	</select>
	<div id="identity-select-break"></div>

	{# name="submit" interferes with this.form.submit() #}
	<input id="identity-submit" type="submit" {#name="submit"#} value="$submit" />
</div></form>

