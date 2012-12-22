<div id="contact-block">
<h4 class="contact-block-h4">{{$contacts}}</h4>
{{if $micropro}}
		<a class="allcontact-link" href="viewcontacts/{{$nickname}}">{{$viewcontacts}}</a>
		<div class='contact-block-content'>
		{{foreach $micropro as $m}}
			{{$m}}
		{{/foreach}}
		</div>
{{/if}}
</div>
<div class="clear"></div>
