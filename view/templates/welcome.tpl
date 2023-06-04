<div id="welcome" class="generic-page-wrapper">
	<h1>{{$welcome nofilter}}</h1>
	<h3>{{$checklist nofilter}}</h3>
	<div style="font-size: 120%;">
		{{$description nofilter}}
		<h4>{{$started nofilter}}</h4>
		<ul>
			<li>
				<a target="newmember" href="help/Quick-Start-guide">{{$quickstart_link}}</a><br />
				{{$quickstart_txt nofilter}}
			</li>
		</ul>
		<h4>{{$settings nofilter}}</h4>
		<ul>
			<li>
				<a target="newmember" href="settings">{{$settings_link}}</a><br />
				{{$settings_txt nofilter}}
			</li>
			<li>
				{{$settings_other nofilter}}
			</ul>
		<h4>{{$profile nofilter}}</h4>
		<ul>
			<li>
				<a target="newmember" href="settings/profile/photo">{{$profile_photo_link}}</a><br />
				{{$profile_photo_txt nofilter}}
			</li>
			<li>
				<a target="newmember" href="settings/profile">{{$profiles_link}}</a><br />
				{{$profiles_txt nofilter}}
			</li>
			<li>
				<a target="newmember" href="settings/profile">{{$profiles_keywords_link}}</a><br />
				{{$profiles_keywords_txt nofilter}}
			</li>
		</ul>
		<h4>{{$connecting nofilter}}</h4>
		<ul>
			{{if $mail_disabled}}
			<li>
				<a target="newmember" href="settings/connectors">{{$import_mail_link}}</a><br />
				{{$import_mail_txt nofilter}}
			</li>
			{{/if}}
			<li>
				<a target="newmember" href="contact">{{$contact_link}}</a><br />
				{{$contact_txt nofilter}}
			</li>
			<li>
				<a target="newmember" href="directory">{{$directory_link}}</a><br />
				{{$directory_txt nofilter}}
			</li>
			<li>
				<a target="newmember" href="contact">{{$finding_link}}</a><br />
				{{$finding_txt nofilter}}
			</li>
		</ul>
		<h4>{{$circles nofilter}}</h4>
		<ul>
			<li>
				<a target="newmember" href="contact">{{$circle_contact_link}}</a><br />
				{{$circle_contact_txt nofilter}}
			</li>

			{{if $newuser_private}}
			<li>
				<a target="newmember" href="help/Circles-and-Privacy">{{$private_link}}</a><br />
				{{$private_txt nofilter}}
			</li>
			{{/if}}
		</ul>
		<h4>{{$help}}</h4>
		<ul>
			<li>
				<a target="newmember" href="help">{{$help_link}}</a><br />
				{{$help_txt nofilter}}
			</li>
		</ul>
	</div>
</div>
