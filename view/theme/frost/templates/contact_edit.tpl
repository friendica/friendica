

<h2>{{$header}}</h2>

<div id="contact-edit-wrapper" >

	{{$tab_str}}

	<div id="contact-edit-nav-wrapper" >
		<div id="contact-edit-links">
			<div id="contact-edit-status-wrapper">
				<span id="contact-edit-contact-status">{{$contact_status}}</span>

				<div id="contact-edit-actions">
					<a class="btn" rel="#contact-actions-menu" href="#" id="contact-edit-actions-button">{{$contact_action_button}}</a>

					<ul role="menu" aria-haspopup="true" id="contact-actions-menu" class="menu-popup" >
						{{if $lblsuggest}}<li role="menuitem"><a  href="#" title="{{$contact_actions.suggest.title}}" onclick="window.location.href='{{$contact_actions.suggest.url}}'; return false;">{{$contact_actions.suggest.label}}</a></li>{{/if}}
						{{if $poll_enabled}}<li role="menuitem"><a  href="#" title="{{$contact_actions.update.title}}" onclick="window.location.href='{{$contact_actions.update.url}}'; return false;">{{$contact_actions.update.label}}</a></li>{{/if}}
						<li class="divider"></li>
						<li role="menuitem"><a  href="#" title="{{$contact_actions.block.title}}" onclick="window.location.href='{{$contact_actions.block.url}}'; return false;">{{$contact_actions.block.label}}</a></li>
						<li role="menuitem"><a  href="#" title="{{$contact_actions.ignore.title}}" onclick="window.location.href='{{$contact_actions.ignore.url}}'; return false;">{{$contact_actions.ignore.label}}</a></li>
						<li role="menuitem"><a  href="#" title="{{$contact_actions.archive.title}}" onclick="window.location.href='{{$contact_actions.archive.url}}'; return false;">{{$contact_actions.archive.label}}</a></li>
						<li role="menuitem"><a  href="{{$contact_actions.delete.url}}" title="{{$contact_actions.delete.title}}" onclick="return confirmDelete();">{{$contact_actions.delete.label}}</a></li>
					</ul>
				</div>

				<ul>
					<li><div id="contact-edit-rel">{{$relation_text}}</div></li>
					<li><div id="contact-edit-nettype">{{$nettype}}</div></li>
					{{if $poll_enabled}}
						<div id="contact-edit-poll-wrapper">
							<div id="contact-edit-last-update-text">{{$lastupdtext}} <span id="contact-edit-last-updated">{{$last_update}}</span></div>
						</div>
					{{/if}}
					{{if $lost_contact}}
						<li><div id="lost-contact-message">{{$lost_contact}}</div></li>
					{{/if}}
					{{if $insecure}}
						<li><div id="insecure-message">{{$insecure}}</div></li>
					{{/if}}
					{{if $blocked}}
						<li><div id="block-message">{{$blocked}}</div></li>
					{{/if}}
					{{if $ignored}}
						<li><div id="ignore-message">{{$ignored}}</div></li>
					{{/if}}
					{{if $archived}}
						<li><div id="archive-message">{{$archived}}</div></li>
					{{/if}}

				</ul>
			</div>
		</div>
	</div>
	<div id="contact-edit-nav-end"></div>


<form action="contacts/{{$contact_id}}" method="post" >
<input type="hidden" name="contact_id" value="{{$contact_id}}">

	<div id="contact-edit-end" ></div>

	{{include file="field_checkbox.tpl" field=$hidden}}

<div id="contact-edit-info-wrapper">
<h4>{{$lbl_info1}}</h4>
	<textarea id="contact-edit-info" rows="8" cols="60" name="info">{{$info}}</textarea>
	<input class="contact-edit-submit" type="submit" name="submit" value="{{$submit}}" />
</div>
<div id="contact-edit-info-end"></div>


<div id="contact-edit-profile-select-text">
<h4>{{$lbl_vis1}}</h4>
<p>{{$lbl_vis2}}</p> 
</div>
{{$profile_select}}
<div id="contact-edit-profile-select-end"></div>

<input class="contact-edit-submit" type="submit" name="submit" value="{{$submit}}" />

</form>
</div>
