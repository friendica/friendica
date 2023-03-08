
{{* template incomming contact request and suggested contacts *}}

<div class="intro-wrapper media" id="intro-{{$intro_id}}">

	{{* Contact Photo *}}
	<div class="intro-photo-wrapper dropdown pull-left">
		<img id="photo-{{$intro_id}}" class="intro-photo media-object" src="{{$photo}}" title="{{$fullname}}" alt="{{$fullname}}" />
	</div>

	<div class="media-body">
		<div class='intro-enty-name'><h4 class="media-heading"><a href="{{$zrl}}">{{$fullname}}</a></h4></div>
		<div class="intro-desc"><span class="intro-desc-label">{{$str_notification_type}}</span>&nbsp;{{$str_type}}</div>
		{{* if the contact was suggestested by another contact, the contact who made the suggestion is displayed*}}
		{{if $madeby}}<div class="intro-madeby"><span class="intro-madeby-label">{{$lbl_madeby}}</span>&nbsp;<a href="{{$madeby_zrl}}">{{$madeby}}</a></div>{{/if}}

		{{* Additional information of the contact *}}
		<div class="intro-contact-info hidden-xs">
			<div class="intro-url"><span class="intro-url-label">{{$lbl_url}}:&nbsp;</span><a href="{{$zrl}}">{{$url}}</a></div>
			{{if $network}}<div class="intro-network"><span class="intro-network-label">{{$lbl_network}}</span>&nbsp;{{$network}}</div>{{/if}}
			{{if $location}}<div class="intro-location"><span class="intro-location-label">{{$lbl_location}}</span>&nbsp;{{$location}}</div>{{/if}}
			{{if $keywords}}<div class="intro-keywords"><span class="intro-keywords-label">{{$lbl_keywords}}</span>&nbsp;{{$keywords}}</div>{{/if}}
			{{if $about}}<div class="intro-about"><span class="intro-about-label">{{$lbl_about}}</span>&nbsp;{{$about nofilter}}</div>{{/if}}
			<div class="intro-knowyou"><span class="intro-knowyou-label">{{$lbl_knowyou}}</span>{{$knowyou}}</div>
			<div class="intro-note intro-note-{{$intro_id}}">{{$note}}</div>
		</div>

		{{* Additional information of the contact for mobile view *}}
		<div class="intro-contact-info xs hidden-lg hidden-md hidden-sm">
			<div class="intro-url"><span class="intro-url-label">{{$lbl_url}}:</span><a href="{{$zrl}}">{{$url}}</a></div>
			{{if $network}}<div class="intro-network"><span class="intro-network-label">{{$lbl_network}}</span>{{$network}}</div>{{/if}}
			{{if $location}}<div class="intro-location"><span class="intro-location-label">{{$lbl_location}}</span>{{$location}}</div>{{/if}}
			{{if $keywords}}<div class="intro-keywords"><span class="intro-keywords-label">{{$lbl_keywords}}</span>{{$keywords}}</div>{{/if}}
			{{if $about}}<div class="intro-about"><span class="intro-about-label">{{$lbl_about}}</span>{{$about nofilter}}</div>{{/if}}
			<div class="intro-knowyou"><span class="intro-knowyou-label">{{$lbl_knowyou}}</span>{{$knowyou}}</div>
			<div class="intro-note intro-note-{{$intro_id}}">{{$note}}</div>
		</div>

		{{* On mobile touch devices we use buttons for approve, ingnore && discard to have a better UX *}}
		{{if $is_mobile}}
		<form class="intro-form" action="notification/{{$intro_id}}" method="post">
			<button class="btn btn-small btn-primary" type="button" onclick="addElmToModal('#intro-approve-wrapper-{{$intro_id}}');"><i class="fa fa-check" aria-hidden="true"></i> {{$approve}}</button>
			{{if $discard}}
				<button class="btn btn-small btn-warning intro-submit-discard" type="submit" name="submit" value="{{$discard}}"><i class="fa fa-trash-o" aria-hidden="true"></i> {{$discard}}</button>
			{{/if}}
			<button class="btn btn-small btn-danger intro-submit-ignore" type="submit" name="submit" value="{{$ignore}}"><i class="fa fa-ban" aria-hidden="true"></i> {{$ignore}}</button>
		</form>
		{{else}}
		{{* The intro actions like approve, ignore, discard intro*}}
		<div class="intro-actions pull-right nav-pills preferences">
			<button class="btn-link intro-action-link" onclick="addElmToModal('#intro-approve-wrapper-{{$intro_id}}');" aria-label="{{$approve}}" title="{{$approve}}" data-toggle="tooltip"><i class="fa fa-check" aria-hidden="true"></i></button>

			<form class="intro-form" action="notification/{{$intro_id}}" method="post">
				<button class="btn-link intro-submit-ignore intro-action-link" type="submit" name="submit" value="{{$ignore}}" aria-label="{{$ignore}}" title="{{$ignore}}" data-toggle="tooltip"><i class="fa fa-ban" aria-hidden="true"></i></button>
				{{if $discard}}<button class="btn-link intro-submit-discard intro-action-link" type="submit" name="submit" value="{{$discard}}" aria-label="{{$discard}}" title="{{$discard}}" data-toggle="tooltip"><i class="fa fa-trash-o" aria-hidden="true"></i></button>{{/if}}
			</form>
		</div>
		{{/if}}

		{{* This sections contains special settings for contact approval. We hide it by default and load this section in
		a bootstrap modal in the case of approval *}}
		<template id="intro-approve-wrapper-{{$intro_id}}" style="display: none;">
			<h3 class="heading">{{$fullname}}{{if $addr}}&nbsp;({{$addr}}){{/if}}</h3>
			<form class="intro-approve-form" {{if $request}}action="{{$request}}" method="get"{{else}}action="{{$action}}" method="post"{{/if}}>
				{{if $type != "friend_suggestion"}}
				{{include file="field_checkbox.tpl" field=$hidden}}
				<div role="radiogroup" aria-labelledby="connection_type">
					<label id="connection_type">{{$lbl_connection_type}}</label>
					{{include file="field_radio.tpl" field=$friend}}
					{{include file="field_radio.tpl" field=$follower}}
				</div>
				<input type="hidden" name="dfrn_id" value="{{$dfrn_id}}">
				<input type="hidden" name="intro_id" value="{{$intro_id}}">
				<input type="hidden" name="contact_id" value="{{$contact_id}}">
				{{else}}
				{{if $note}}<div>{{$note}}</div>{{/if}}
				<input type="hidden" name="url" value="{{$url}}">
				<input type="hidden" name="dfrn-url" value="{{$dfrn_url}}">
				{{/if}}
				<div class="pull-right">
					<button class="btn btn-primary intro-submit-approve" type="submit" name="submit" value="{{$approve}}">{{$approve}}</button>
				</div>
				<div class="clear"></div>
			</form>
		</template>
	</div>
</div>
<div class="intro-end"></div>
