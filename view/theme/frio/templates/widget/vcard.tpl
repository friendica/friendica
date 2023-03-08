<div class="vcard h-card widget">

	<div id="profile-photo-wrapper">
		{{if $url}}
		<a href="{{$url}}"><img class="photo u-photo" src="{{$photo}}" alt="{{$contact.name}}" /></a>
		{{else}}
		<img class="photo u-photo" src="{{$photo}}" alt="{{$contact.name}}" />
		{{/if}}
	</div>

	{{* The short information which will appended to the second navbar by scrollspy *}}
	<div id="vcard-short-info-wrapper" style="display: none;">
		<div id="vcard-short-info" class="media" style="display: none">
			<div id="vcard-short-photo-wrapper" class="pull-left">
				<img class="media-object" src="{{$photo}}" alt="{{$contact.name}}" />
			</div>

			<div id="vcard-short-desc" class="media-body">
				<h4 class="media-heading" dir="auto">{{$contact.name}}</h4>
				{{if $contact.addr}}<div class="vcard-short-addr">{{$contact.addr}}</div>{{/if}}
			</div>
		</div>
	</div>

	<div class="panel-body">
		<div class="profile-header">
			<h3 class="fn p-name" dir="auto">{{$contact.name}}</h3>

			{{if $contact.addr}}<div class="p-addr">{{$contact.addr}}</div>{{/if}}

			{{if $account_type}}<div class="account-type">({{$account_type}})</div>{{/if}}

			{{if $about}}<div class="title" dir="auto">{{$about nofilter}}</div>{{/if}}
		</div>

		<div id="profile-extra-links">
			<div id="dfrn-request-link-button">
				{{if $follow_link}}
					<a id="dfrn-request-link" class="btn btn-labeled btn-primary" href="{{$follow_link}}"">
						<span class=""><i class="fa fa-user-plus"></i></span>
						<span class="">{{$follow}}</span>
					</a>
				{{/if}}
				{{if $unfollow_link}}
					<a id="dfrn-request-link" class="btn btn-labeled btn-primary" href="{{$unfollow_link}}">
						<span class=""><i class="fa fa-user-times"></i></span>
						<span class="">{{$unfollow}}</span>
					</a>
				{{/if}}
			</div>
			{{if $wallmessage_link}}
				<div id="wallmessage-link-botton">
					<button type="button" id="wallmessage-link" class="btn btn-labeled btn-primary" onclick="openWallMessage('{{$wallmessage_link}}')">
						<span class=""><i class="fa fa-envelope"></i></span>
						<span class="">{{$wallmessage}}</span>
					</button>
				</div>
			{{/if}}
		</div>

		<div class="clear"></div>

		{{if $contact.location}}
		<div class="location detail">
			<span class="location-label icon"><i class="fa fa-map-marker"></i></span>
			<span class="adr p-location">{{$contact.location}}</span>
		</div>
		{{/if}}

		{{if $contact.xmpp}}
		<div class="xmpp detail">
			<span class="xmpp-label icon"><i class="fa fa-xmpp"></i></span>
			<span class="xmpp-data"><a href="xmpp:{{$contact.xmpp}}" rel="me" target="_blank" rel="noopener noreferrer">{{include file="sub/punct_wrap.tpl" text=$contact.xmpp}}</a></span>
		</div>
		{{/if}}

		{{if $contact.matrix}}
		<div class="matrix detail">
			<span class="matrix-label icon"><i class="fa fa-matrix-org"></i></span>
			<span class="matrix-data"><a href="matrix:{{$contact.matrix}}" rel="me" target="_blank" rel="noopener noreferrer">{{include file="sub/punct_wrap.tpl" text=$contact.matrix}}</a></span>
		</div>
		{{/if}}

		{{if $network_link}}
		<div class="network detail">
			<span class="network-label icon"><i class="fa fa-{{$network_avatar}}"></i></span>
			<span class="x-network">{{$network_link nofilter}}</span>
		</div>
		{{/if}}
	</div>
</div>
