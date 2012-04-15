{{ if $page }}
<div>$page</div>
{{ endif }}

{{ if $lastusers_title }}
<h3 id="extra-help-header">Help or '@NewHere'?</h3>
<div id="extra-help">
<a href="https://helpers.pyxis.uberspace.de/profile/helpers"
	title="Friendica Support" target="_blank">Friendica Support</a><br />
<a href="https://letstalk.pyxis.uberspace.de/profile/letstalk"
	title="Let's talk" target="_blank">Let's talk</a><br />
<a href="http://newzot.hydra.uberspace.de/profile/newzot"
	title="Local Friendica" target="_blank">Local Friendica</a><br />
<a href="http://kakste.com/profile/newhere" title="@NewHere" target="_blank">NewHere</a>
</div>
{{ endif }}

{{ if $lastusers_title }}
<h3 id="connect-services-header">Connectable Services</h3>
<div id="connect-services">
<a href="$url/facebook"><img alt="Facebook"
	src="view/theme/dispy/icons/facebook.png" title="Facebook" /></a>
<a href="$url/settings/connectors"><img
	alt="StatusNet" src="view/theme/dispy/icons/StatusNet.png?" title="StatusNet" /></a>
<a href="$url/settings/connectors"><img
	alt="LiveJournal" src="view/theme/dispy/icons/livejournal.png?" title="LiveJournal" /></a>
<a href="$url/settings/connectors"><img
	alt="Posterous" src="view/theme/dispy/icons/posterous.png?" title="Posterous" /></a><br />
<a href="$url/settings/connectors"><img
	alt="Tumblr" src="view/theme/dispy/icons/tumblr.png?" title="Tumblr" /></a>
<a href="$url/settings/connectors"><img
	alt="Twitter" src="view/theme/dispy/icons/twitter.png?" title="Twitter" /></a>
<a href="$url/settings/connectors"><img
	alt="WordPress" src="view/theme/dispy/icons/wordpress.png?" title="WordPress" /></a>
<a href="$url/settings/connectors"><img
	alt="E-Mail" src="view/theme/dispy/icons/email.png?" title="E-Mail" /></a>
</div>
{{ endif }}

<h3 id="postit-header">'PostIt' to Friendica</h3>
<div id="postit">
<a href="$fpostitJS" title="PostIt">Post to Friendica</a> from anywhere by bookmarking this link.
</div>

