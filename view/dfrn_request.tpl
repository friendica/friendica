
<h1>$header</h1>

<p id="dfrn-request-intro">
$page_desc<br />
<ul id="dfrn-request-networks">
<li><a href="http://friendica.com" title="$friendica">$friendica</a></li>
<li><a href="http://joindiaspora.com" title="$diaspora">$diaspora</a> $diasnote</li>
<li><a href="http://ostatus.org" title="$public_net" >$statusnet</a></li>
<li>$emailnet</li>
</ul>
$invite_desc
</p>
<p>
$desc
</p>

<form action="dfrn_request/$nickname" method="post" />

<div id="dfrn-request-url-wrapper" >
	<label id="dfrn-url-label" for="dfrn-url" >$your_address</label>
	<input type="text" name="dfrn_url" id="dfrn-url" size="32" value="$myaddr" />
	<div id="dfrn-request-url-end"></div>
</div>

<p id="dfrn-request-options">
$pls_answer
</p>

<div id="dfrn-request-info-wrapper" >


<p id="doiknowyou">
$does_know
</p>

		<div id="dfrn-request-know-yes-wrapper">
		<label id="dfrn-request-knowyou-yes-label" for="dfrn-request-knowyouyes">$yes</label>
		<input type="radio" name="knowyou" id="knowyouyes" value="1" />

		<div id="dfrn-request-knowyou-break" ></div>	
		</div>
		<div id="dfrn-request-know-no-wrapper">
		<label id="dfrn-request-knowyou-no-label" for="dfrn-request-knowyouno">$no</label>
		<input type="radio" name="knowyou" id="knowyouno" value="0" checked="checked" />

		<div id="dfrn-request-knowyou-end"></div>
		</div>


<p id="dfrn-request-message-desc">
$add_note
</p>
	<div id="dfrn-request-message-wrapper">
	<textarea name="dfrn-request-message" rows="4" cols="64" ></textarea>
	</div>


</div>

	<div id="dfrn-request-submit-wrapper">
		<input type="submit" name="submit" id="dfrn-request-submit-button" value="$submit" />
		<input type="submit" name="cancel" id="dfrn-request-cancel-button" value="$cancel" />
	</div>
</form>
