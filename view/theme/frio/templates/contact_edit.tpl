{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div class="generic-page-wrapper">
	{{if $header}}<h3>{{$header}}:&nbsp;{{$name}}{{if $account_type}}&nbsp;<small>({{$account_type}})</small>{{/if}}</h3>{{/if}}

	<div id="contact-edit-wrapper">

		{{* Insert Tab-Nav *}}
		{{$tab_str nofilter}}


		<div id="contact-edit-content-wrapper">
			<form action="contact/{{$contact_id}}" method="post">

				{{* This is the Action menu where contact related actions like 'ignore', 'hide' can be performed *}}
				<ul id="contact-edit-actions" class="nav nav-pills preferences">
					<li class="dropdown pull-right">
						<button type="button" class="btn btn-link dropdown-toggle" id="contact-edit-actions-button" data-toggle="dropdown" aria-expanded="false">
							<i class="fa fa-angle-down" aria-hidden="true"></i>&nbsp;{{$contact_action_button}}
						</button>

						<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="contact-edit-actions-button" aria-haspopup="true" id="contact-actions-menu">
							{{if $contact_actions.follow}}<li><a role="menuitem" href="{{$contact_actions.follow.url}}" title="{{$contact_actions.follow.title}}">{{$contact_actions.follow.label}}</a></li>{{/if}}
							{{if $contact_actions.unfollow}}<li><a role="menuitem" href="{{$contact_actions.unfollow.url}}" title="{{$contact_actions.unfollow.title}}">{{$contact_actions.unfollow.label}}</a></li>{{/if}}
							{{if $lblsuggest}}<li><a role="menuitem" href="{{$contact_actions.suggest.url}}" title="{{$contact_actions.suggest.title}}">{{$contact_actions.suggest.label}}</a></li>{{/if}}
							{{if $poll_enabled}}<li><a role="menuitem" href="{{$contact_actions.update.url}}" title="{{$contact_actions.update.title}}">{{$contact_actions.update.label}}</a></li>{{/if}}
							{{if $contact_actions.updateprofile}}<li><a role="menuitem" href="{{$contact_actions.updateprofile.url}}" title="{{$contact_actions.updateprofile.title}}">{{$contact_actions.updateprofile.label}}</a></li>{{/if}}
							{{if $lblsuggest || $poll_enabled || $contact_actions.updateprofile}}
							<li class="divider"></li>
							{{/if}}
							<li><a role="menuitem" href="{{$contact_actions.block.url}}" title="{{$contact_actions.block.title}}">{{$contact_actions.block.label}}</a></li>
							<li><a role="menuitem" href="{{$contact_actions.ignore.url}}" title="{{$contact_actions.ignore.title}}">{{$contact_actions.ignore.label}}</a></li>
							<li><a role="menuitem" href="{{$contact_actions.collapse.url}}" title="{{$contact_actions.collapse.title}}">{{$contact_actions.collapse.label}}</a></li>
							{{if $contact_actions.revoke_follow.url}}<li><button role="menuitem" type="button" class="btn-link" title="{{$contact_actions.revoke_follow.title}}" onclick="addToModal('{{$contact_actions.revoke_follow.url}}');">{{$contact_actions.revoke_follow.label}}</button></li>{{/if}}
						</ul>
					</li>
				</ul>
				<div class="clear"></div>


				<div id="contact-edit-status-wrapper">
					<span id="contact-edit-contact-status">{{$contact_status}}</span>

					{{* Block with status information about the contact *}}
					<ul>
						{{if $relation_text}}<li><div id="contact-edit-rel">{{$relation_text}}</div></li>{{/if}}
						{{if $nettype}}<li><div id="contact-edit-nettype">{{$nettype}}</div></li>{{/if}}

						{{if $poll_enabled}}
							<li><div id="contact-edit-last-update-text">{{$lastupdtext}} <span id="contact-edit-last-updated">{{$last_update}}</span></div>
							{{if $poll_interval}}
								<span id="contact-edit-poll-text">{{$updpub}}</span> {{$poll_interval nofilter}}
								<input class="btn btn-primary" type="submit" name="submit" value="{{$submit}}" />
							{{/if}}
							</li>
						{{/if}}

						{{if $lost_contact}}<li><div id="lost-contact-message">{{$lost_contact}}</div></li>{{/if}}
						{{if $insecure}}<li><div id="insecure-message">{{$insecure}}</div></li>	{{/if}}
						{{if $blocked && !$pending}}<li><div id="block-message">{{$blocked}}</div></li>{{/if}}
						{{if $pending}}<li><div id="pending-message">{{$pending}}</div></li>{{/if}}
						{{if $ignored}}<li><div id="ignore-message">{{$ignored}}</div></li>{{/if}}
						{{if $collapsed}}<li><div id="collapse-message">{{$collapsed}}</div></li>{{/if}}
						{{if $archived}}<li><div id="archive-message">{{$archived}}</div></li>{{/if}}
						{{if $serverIgnored}}<li><div id="serverIgnored-message">{{$serverIgnored}} <a href="settings/server">{{$manageServers}}</a></div></li>{{/if}}
					</ul>
				</div> {{* End of contact-edit-status-wrapper *}}

				<div id="contact-edit-links-end"></div>

				<div class="panel-group" id="contact-edit-tools" role="tablist" aria-multiselectable="true">

					{{* Some information about the contact from the profile *}}
					<div class="panel">
						<div class="section-subtitle-wrapper panel-heading" role="tab" id="contact-edit-profile">
							<h4>
								<button class="btn-link accordion-toggle" data-toggle="collapse" data-parent="#contact-edit-tools" href="#contact-edit-profile-collapse" aria-expanded="true" aria-controls="contact-edit-profile-collapse">
									{{$contact_profile_label}}
								</button>
							</h4>
						</div>
						<div id="contact-edit-profile-collapse" class="panel-body panel-collapse collapse in" role="tabpanel" aria-labelledby="contact-edit-profile">
							<div class="section-content-tools-wrapper">
								<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
									<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 text-muted">{{$profileurllabel}}</div><a target="blank" href="{{$profileurl}}">{{$profileurl}}</a>
								</div>

								{{if $location}}
								<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
									<hr class="profile-separator">
									<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 text-muted">{{$location_label}}</div>
									<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">{{$location nofilter}}</div>
								</div>
								{{/if}}

								{{if $xmpp}}
								<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
									<hr class="profile-separator">
									<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 text-muted">{{$xmpp_label}}</div>
									<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">{{$xmpp}}</div>
								</div>
								{{/if}}

								{{if $matrix}}
								<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
									<hr class="profile-separator">
									<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 text-muted">{{$matrix_label}}</div>
									<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">{{$matrix}}</div>
								</div>
								{{/if}}

								{{if $keywords}}
								<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
									<hr class="profile-separator">
									<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 text-muted">{{$keywords_label}}</div>
									<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">{{$keywords}}</div>
								</div>
								{{/if}}

								{{if $about}}
								<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
									<hr class="profile-separator">
									<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 text-muted">{{$about_label}}</div>
									<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">{{$about nofilter}}</div>
								</div>
								{{/if}}
							</div>
						</div>
						<div class="clear"></div>
					</div>

					{{if $contact_settings_label}}
					<div class="panel">
						<div class="section-subtitle-wrapper panel-heading" role="tab" id="contact-edit-settings">
							<h4>
								<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#contact-edit-tools" href="#contact-edit-settings-collapse" aria-expanded="false" aria-controls="contact-edit-settings-collapse">
									{{$contact_settings_label}}
								</button>
							</h4>
						</div>
						<div id="contact-edit-settings-collapse" class="panel-body panel-collapse collapse" role="tabpanel" aria-labelledby="contact-edit-settings">
							<div class="section-content-tools-wrapper">

								<input type="hidden" name="contact_id" value="{{$contact_id}}">

								{{include file="field_checkbox.tpl" field=$notify_new_posts}}
								{{if $fetch_further_information}}
									{{include file="field_select.tpl" field=$fetch_further_information}}
									{{if $fetch_further_information.2 == 2 || $fetch_further_information.2 == 3}} {{include file="field_textarea.tpl" field=$ffi_keyword_denylist}} {{/if}}
								{{/if}}
								{{if $allow_remote_self}}
									{{include file="field_select.tpl" field=$remote_self}}
								{{/if}}

								{{include file="field_checkbox.tpl" field=$hidden}}

								<div class="pull-right settings-submit-wrapper">
									<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
								</div>
								<div class="clear"></div>
							</div>
						</div>
					</div>
					{{/if}}

					{{if $lbl_info1}}
					<div class="panel">
						<div class="section-subtitle-wrapper panel-heading" role="tab" id="contact-edit-info">
							<h4>
								<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#contact-edit-tools" href="#contact-edit-info-collapse" aria-expanded="false" aria-controls="contact-edit-info-collapse">
									{{$lbl_info1}}
								</button>
							</h4>
						</div>
						<div id="contact-edit-info-collapse" class="panel-body panel-collapse collapse" role="tabpanel" aria-labelledby="contact-edit-info">
							<div class="section-content-tools-wrapper">

								{{include file="field_textarea.tpl" field=$cinfo}}

								<div class="pull-right settings-submit-wrapper">
									<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
								</div>
								<div class="clear"></div>
								{{if $reason}}
								<h4>{{$lbl_info2}}</h4>
								<p>{{$reason}}</p>
								<div class="clear"></div>
								{{/if}}
							</div>
						</div>
					</div>
					{{/if}}
					{{if $channel_settings_label}}
						<div class="panel">
							<div class="section-subtitle-wrapper panel-heading" role="tab" id="contact-edit-channel">
								<h4>
									<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#contact-edit-tools" href="#contact-edit-channel-collapse" aria-expanded="false" aria-controls="contact-edit-channel-collapse">
										{{$channel_settings_label}}
									</button>
								</h4>
							</div>
							<div id="contact-edit-channel-collapse" class="panel-body panel-collapse collapse" role="tabpanel" aria-labelledby="contact-edit-channel">
								<div class="section-content-tools-wrapper">
									{{include file="field_checkbox.tpl" field=$channel_only}}
									<label>{{$frequency_label}}</label>
									{{include file="field_radio.tpl" field=$frequency_default}}
									{{include file="field_radio.tpl" field=$frequency_always}}
									{{include file="field_radio.tpl" field=$frequency_reduced}}
									{{include file="field_radio.tpl" field=$frequency_never}}
									<p>{{$frequency_description}}</p>

									<div class="pull-right settings-submit-wrapper">
										<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
									</div>
									<div class="clear"></div>
								</div>
							</div>
						</div>
					{{/if}}
				</div>

			</form>{{* End of the form *}}
		</div>{{* End of contact-edit-content-wrapper *}}
	</div>
</div>
