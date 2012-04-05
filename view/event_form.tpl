<h3>$title</h3>

<p>
$desc
</p>

<form action="$post" method="post" >

<input type="hidden" name="event_id" value="$eid" />
<input type="hidden" name="cid" value="$cid" />
<input type="hidden" name="uri" value="$uri" />

<div id="event-start-text">$s_text</div>
$s_dsel $s_tsel

<div id="event-finish-text">$f_text</div>
$f_dsel $f_tsel

<div id="event-datetime-break"></div>

<input type="checkbox" name="nofinish" value="1" id="event-nofinish-checkbox" $n_checked /> <div id="event-nofinish-text">$n_text</div>

<div id="event-nofinish-break"></div>

<input type="checkbox" name="adjust" value="1" id="event-adjust-checkbox" $a_checked /> <div id="event-adjust-text">$a_text</div>

<div id="event-adjust-break"></div>

<div id="event-desc-text">$d_text</div>
<textarea id="event-desc-textarea" name="desc">$d_orig</textarea>


<div id="event-location-text">$l_text</div>
<textarea id="event-location-textarea" name="location">$l_orig</textarea>

<input type="checkbox" name="share" value="1" id="event-share-checkbox" $sh_checked /> <div id="event-share-text">$sh_text</div>
<div id="event-share-break"></div>

$acl

<div class="clear"></div>
<input id="event-submit" type="submit" name="submit" value="$submit" />
</form>


