
<div id="contact-block">
	<h3 class="contact-block-h4 pull-left">{{$contacts}}</h3>
{{if $micropro}}
	<a class="pull-right" href="viewcontacts/{{$nickname}}">
	<i class="faded-icon fa fa-eye" aria-hidden="true"></i><span class="sr-only">{{$viewcontacts}}</span>
	</a>
	<div class='contact-block-content'>
	{{foreach $micropro as $m}}
		{{$m}}
	{{/foreach}}
	</div>
{{/if}}
</div>
<div class="clear"></div>
