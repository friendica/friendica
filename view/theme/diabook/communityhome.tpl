{{ if $lastusers_title }}
<h3 style="margin-top:0px;">Help or #Newhere?</h3>
<a href="https://helpers.pyxis.uberspace.de/profile/helpers" style="margin-left: 10px; color:#000;" title="Friendica Support" target="blank">Friendica Support</a><br>
<a href="https://letstalk.pyxis.uberspace.de/profile/letstalk" style="margin-left: 10px; color:#000;" title="Let's talk" target="blank">Let's talk</a><br>
<a href="http://kakste.com/profile/newhere" title="#NewHere" style="margin-left: 10px; color:#000;" target="blank">NewHere</a>
{{ endif }}
{{ if $lastusers_title }}
<h3>$lastusers_title</h3>
<div class='items-wrapper'>
{{ for $lastusers_items as $i }}
	$i
{{ endfor }}
</div>
{{ endif }}

{{ if $activeusers_title }}
<h3>$activeusers_title</h3>
<div class='items-wrapper'>
{{ for $activeusers_items as $i }}
	$i
{{ endfor }}
</div>
{{ endif }}

{{ if $photos_title }}
<h3>$photos_title</h3>
<div class='items-wrapper'>
{{ for $photos_items as $i }}
	$i
{{ endfor }}
</div>
{{ endif }}


{{ if $like_title }}
<h3>$like_title</h3>
<ul id='likes'>
{{ for $like_items as $i }}
	<li>$i</li>
{{ endfor }}
</ul>
{{ endif }}
