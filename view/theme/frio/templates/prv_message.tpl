
<div id="prvmail-wrapper">
<form id="prvmail-form" action="message" method="post" >

	{{* Disable the header. We will see if we will need it
	<h3>{{$header}}</h3>
	*}}

	{{$parent}}

	{{* The To: form-group which contains the message recipient *}}
	<div id="prvmail-to-label" class="form-group">
		<label for="message-to-select">{{$to}}</label><br>
		{{if $showinputs}}
		<input type="text" id="recip" class="form-control" name="messagerecip" value="{{$prefill}}" tabindex="10" {{if $prefill}}disabled{{else}}aria-required="true"{{/if}} />
		<input type="hidden" id="recip-complete" name="messageto" value="{{$preid}}">
		{{else}}
		{{$select}}
		{{/if}}
	</div>

	{{* The subject input field *}}
	<div id="prvmail-subject-label" class="form-group">
		<label for="prvmail-subject">{{$subject}}</label>
		<input type="text" id="prvmail-subject" class="form-control" name="subject" value="{{$subjtxt}}" {{$readonly}} tabindex="11" />
	</div>

	{{* The message input field which contains the message text *}}
	<div id="prvmail-message-label" class="form-group">
		<label for="prvmail-text">{{$yourmessage}}</label>
		<textarea rows="8" cols="72" class="prvmail-text form-control text-autosize" id="comment-edit-text-input" name="body" tabindex="12">{{$text}}</textarea>
	</div>

	<ul id="prvmail-text-edit-bb" class="comment-edit-bb comment-icon-list nav nav-pills hidden-xs pull-left">
				<li>
					<button type="button" class="btn-link icon" style="cursor: pointer;" title="{{$edimg|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="img" data-id="input">
						<i class="fa fa-picture-o"></i>
					</button>
				</li>
				<li>
					<button type="button" class="btn-link icon bb-url" style="cursor: pointer;" title="{{$edurl|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="url" data-id="input">
						<i class="fa fa-link"></i>
					</button>
				</li>
				<li>
					<button type="button" class="btn-link icon bb-video" style="cursor: pointer;" title="{{$edvideo|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="video" data-id="input">
						<i class="fa fa-video-camera"></i>
					</button>
				</li>

				<li>
					<button type="button" class="btn-link icon underline" style="cursor: pointer;" title="{{$eduline|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="u" data-id="input">
						<i class="fa fa-underline"></i>
					</button>
				</li>
				<li>
					<button type="button" class="btn-link icon italic" style="cursor: pointer;" title="{{$editalic|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="i" data-id="input">
						<i class="fa fa-italic"></i>
					</button>
				</li>
				<li>
					<button type="button" class="btn-link icon bold" style="cursor: pointer;"  title="{{$edbold|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="b" data-id="input">
						<i class="fa fa-bold"></i>
					</button>
				</li>
				<li>
					<button type="button" class="btn-link icon quote" style="cursor: pointer;" title="{{$edquote|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="quote" data-id="input">
						<i class="fa fa-quote-left"></i>
					</button>
				</li>
			</ul>

	<div id="prvmail-text-bb-end"></div>

	{{* The submit button *}}
	<div id="prvmail-submit-wrapper">
		<button type="submit" id="prvmail-submit" name="submit" value="{{$submit|escape:'html'}}" class="btn btn-primary pull-right"  tabindex="13">
			<i class="fa fa-slideshare fa-fw"></i>
			{{$submit|escape:'html'}}
		</button>
	</div>

	<div id="prvmail-end"></div>

</form>
</div>

<script language="javascript" type="text/javascript">
	$(document).ready( function() {
		// initiale autosize for the textareas
		autosize($("textarea.text-autosize"));
	});
</script>
