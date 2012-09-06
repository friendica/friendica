<h2>$title</h2>


<div id="group-edit-wrapper" >
	<form action="group/$gid" id="group-edit-form" method="post" >
		<div id="group-edit-name-wrapper" >
			<label id="group-edit-name-label" for="group-edit-name" >$gname</label>
			<input type="text" id="group-edit-name" name="groupname" value="$name" />
			<input type="submit" name="submit" value="$submit">
			$drop
		</div>
		<div id="group-edit-name-end"></div>
		<div id="group-edit-desc">$desc</div>
		<div id="group-edit-select-end" ></div>
	</form>
</div>
