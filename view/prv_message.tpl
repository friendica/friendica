
<h3>$header</h3>

<div id="prvmail-wrapper" >
<form id="prvmail-form" action="message" method="post" >

$parent

<div id="prvmail-to-label">$to</div>
$select

<div id="prvmail-subject-label">$subject</div>
<input type="text" size="64" maxlength="255" id="prvmail-subject" name="subject" value="$subjtxt" $readonly tabindex="11" />

<div id="prvmail-message-label">$yourmessage</div>
<textarea rows="8" cols="72" class="prvmail-text" id="prvmail-text" name="body" tabindex="12">$text</textarea>


<div id="prvmail-submit-wrapper" >
	<input type="submit" id="prvmail-submit" name="submit" value="$submit" tabindex="13" />
	<div id="prvmail-upload-wrapper" >
		<div id="prvmail-upload" class="icon border camera" title="$upload" ></div>
	</div> 
	<div id="prvmail-link-wrapper" >
		<div id="prvmail-link" class="icon border link" title="$insert" onclick="jotGetLink();" ></div>
	</div> 
	<div id="prvmail-rotator-wrapper" >
		<img id="prvmail-rotator" src="images/rotator.gif" alt="$wait" title="$wait" style="display: none;" />
	</div> 
</div>
<div id="prvmail-end"></div>
</form>
</div>
