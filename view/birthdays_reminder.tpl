{{ if $count }}
<div id="birthday-notice" class="birthday-notice fakelink $classtoday" onclick="openClose('birthday-wrapper');">$event_reminders ($count)</div>
<div id="birthday-wrapper" style="display: none;" ><div id="birthday-title">$event_title</div>
<div id="birthday-title-end"></div>
{{ for $events as $event }}
<div class="birthday-list" id="birthday-$event.id"></a> <a href="$event.link">$event.title</a> $event.date </div>
{{ endfor }}
</div>
{{ endif }}

