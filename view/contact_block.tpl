<div id="contact-block">
<h4 class="contact-block-h4">$contacts</h4>
{{ if $micropro }}
		<a class="allcontact-link" href="contacts/$nickname">$viewcontacts</a>
		<div class='contact-block-content'>
		{{ for $micropro as $m }}
			$m
		{{ endfor }}
		</div>
{{ endif }}
</div>
