<center>
<form action="$confirm_url" id="confirm-form" method="$method">

	<span id="confirm-message">$message</span>
	{{ for $extra_inputs as $input }}
	<input type="hidden" name="$input.name" value="$input.value" />
	{{ endfor }}

	<input class="confirm-button" id="confirm-submit-button" type="submit" name="$confirm_name" value="$confirm" />
	<input class="confirm-button" id="confirm-cancel-button" type="submit" name="canceled" value="$cancel" />

</form>
</center>

