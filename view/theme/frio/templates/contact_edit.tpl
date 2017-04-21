
<div class="generic-page-wrapper">
	{{if $header}}<h3>{{$header}}:&nbsp;{{$name}}{{if $account_type}}&nbsp;<small>({{$account_type}})</small>{{/if}}</h3>{{/if}}

	<div id="contact-edit-wrapper" >

		{{* Insert Tab-Nav *}}
		{{$tab_str}}


		<div id="contact-edit-content-wrapper">
			<form action="contacts/{{$contact_id}}" method="post" >

				{{* This is the Action menu where contact related actions like 'ignore', 'hide' can be performed *}}
				<ul id="contact-edit-actions" class="nav nav-pills preferences">
					<li class="dropdown pull-right">
						<button type="button" class="btn btn-link btn-sm dropdown-toggle" id="contact-edit-actions-button" data-toggle="dropdown" aria-expanded="true">
							<i class="fa fa-angle-down"></i>&nbsp;{{$contact_action_button}}
						</button>

						<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="contact-edit-actions-button" aria-haspopup="true" id="contact-actions-menu" >
							{{if $lblsuggest}}<li role="menuitem"><a href="{{$contact_actions.suggest.url}}" title="{{$contact_actions.suggest.title}}">{{$contact_actions.suggest.label}}</a></li>{{/if}}
							{{if $poll_enabled}}<li role="menuitem"><a href="{{$contact_actions.update.url}}" title="{{$contact_actions.update.title}}">{{$contact_actions.update.label}}</a></li>{{/if}}
							{{if $lblsuggest || $poll_enabled}}
							<li class="divider"></li>
							{{/if}}
							<li role="menuitem"><a href="{{$contact_actions.block.url}}" title="{{$contact_actions.block.title}}">{{$contact_actions.block.label}}</a></li>
							<li role="menuitem"><a href="{{$contact_actions.ignore.url}}" title="{{$contact_actions.ignore.title}}">{{$contact_actions.ignore.label}}</a></li>
							<li role="menuitem"><a href="{{$contact_actions.archive.url}}" title="{{$contact_actions.archive.title}}">{{$contact_actions.archive.label}}</a></li>
							<li role="menuitem"><button type="button" class="btn-link" title="{{$contact_actions.delete.title}}" onclick="addToModal('{{$contact_actions.delete.url}}?confirm=1');">{{$contact_actions.delete.label}}</button></li>
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
								<span id="contact-edit-poll-text">{{$updpub}}</span> {{$poll_interval}}
							{{/if}}
							</li>
						{{/if}}

						{{if $lost_contact}}<li><div id="lost-contact-message">{{$lost_contact}}</div></li>{{/if}}
						{{if $insecure}}<li><div id="insecure-message">{{$insecure}}</div></li>	{{/if}}
						{{if $blocked}}<li><div id="block-message">{{$blocked}}</div></li>{{/if}}
						{{if $ignored}}<li><div id="ignore-message">{{$ignored}}</div></li>{{/if}}
						{{if $archived}}<li><div id="archive-message">{{$archived}}</div></li>{{/if}}
					</ul>

					<ul>
						<!-- <li><a href="network/0?nets=all&cid={{$contact_id}}" id="contact-edit-view-recent">{{$lblrecent}}</a></li> -->
						{{if $follow}}<li><div id="contact-edit-follow"><a href="{{$follow}}">{{$follow_text}}</a></div></li>{{/if}}
					</ul>
				</div> {{* End of contact-edit-status-wrapper *}}

				<div id="contact-edit-links-end"></div>

				<div class="panel-group" id="contact-edit-tools" role="tablist" aria-multiselectable="true">

					{{* Some information about the contact from the profile *}}
					<div class="panel">
						<div class="section-subtitle-wrapper" role="tab" id="contact-edit-profile">
							<h4>
								<a class="accordion-toggle" data-toggle="collapse" data-parent="#contact-edit-tools" href="#contact-edit-profile-collapse" aria-expanded="true" aria-controls="contact-edit-profile-collapse">
									{{$contact_profile_label}}
								</a>
							</h4>
						</div>
						<div id="contact-edit-profile-collapse" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="contact-edit-profile">
							<div class="section-content-tools-wrapper">
								<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
									<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 text-muted">{{$profileurllabel}}</div><a target="blank" href="{{$url}}">{{$profileurl}}</a></dd></dl>
								</div>

								{{if $location}}
								<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
									<hr class="profile-separator">
									<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 text-muted">{{$location_label}}</div>
									<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">{{$location}}</div>
								</div>
								{{/if}}

								{{if $xmpp}}
								<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
									<hr class="profile-separator">
									<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 text-muted">{{$xmpp_label}}</div>
									<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">{{$xmpp}}</div>
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
									<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">{{$about}}</div>
								</div>
								{{/if}}
							</div>
						</div>
						<div class="clear"></div>
					</div>

					<div class="panel">
						<div class="section-subtitle-wrapper" role="tab" id="contact-edit-settings">
							<h4>
								<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#contact-edit-tools" href="#contact-edit-settings-collapse" aria-expanded="true" aria-controls="contact-edit-settings-collapse">
									{{$contact_settings_label}}
								</a>
							</h4>
						</div>
						<div id="contact-edit-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="contact-edit-settings">
							<div class="section-content-tools-wrapper">

								<input type="hidden" name="contact_id" value="{{$contact_id}}">

								{{include file="field_checkbox.tpl" field=$notify}}
								{{if $fetch_further_information}}
									{{include file="field_select.tpl" field=$fetch_further_information}}
									{{if $fetch_further_information.2 == 2 }} {{include file="field_textarea.tpl" field=$ffi_keyword_blacklist}} {{/if}}
								{{/if}}
								{{include file="field_checkbox.tpl" field=$hidden}}

								<div class="form-group pull-right settings-submit-wrapper" >
									<button type="submit" name="submit" class="btn btn-primary" value="{{$submit|escape:'html'}}">{{$submit}}</button>
								</div>
								<div class="clear"></div>
							</div>
						</div>
					</div>

					<div class="panel">
						<div class="section-subtitle-wrapper" role="tab" id="contact-edit-info">
							<h4>
								<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#contact-edit-tools" href="#contact-edit-info-collapse" aria-expanded="true" aria-controls="contact-edit-info-collapse">
									{{$lbl_info1}}
								</a>
							</h4>
						</div>
						<div id="contact-edit-info-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="contact-edit-info">
							<div class="section-content-tools-wrapper">

								{{include file="field_textarea.tpl" field=$cinfo}}

								<div class="form-group pull-right settings-submit-wrapper" >
									<button type="submit" name="submit" class="btn btn-primary" value="{{$submit|escape:'html'}}">{{$submit}}</button>
								</div>
								<div class="clear"></div>
							</div>
						</div>
					</div>

					<div class="panel">
						<div class="section-subtitle-wrapper" role="tab" id="contact-edit-profile-select">
							<h4>
								<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#contact-edit-tools" href="#contact-edit-profile-select-collapse" aria-expanded="true" aria-controls="contact-edit-profile-select-collapse">
									{{$lbl_vis1}}
								</a>
							</h4>
						</div>
						<div id="contact-edit-profile-select-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="contact-edit-profile-select">
								{{if $profile_select}}
									<div id="contact-edit-profile-select-text">
										<p>{{$lbl_vis2}}</p>
									</div>
									<div class="form-group">
									{{$profile_select}}
									</div>
									<div class="clear"></div>
								{{/if}}

								<div class="form-group pull-right settings-submit-wrapper" >
									<button type="submit" name="submit" class="btn btn-primary" value="{{$submit|escape:'html'}}">{{$submit}}</button>
								</div>
								<div class="clear"></div>
							</div>
						</div>
					</div>

				</div>

			</form>{{* End of the form *}}
		</div>{{* End of contact-edit-content-wrapper *}}
	</div>
</div>
