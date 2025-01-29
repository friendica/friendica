{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="item-{{$item.guid}}" class="item-{{$item.id}}">
	<span class="commented" style="display: none;">{{$item.commented}}</span>
	<span class="received" style="display: none;">{{$item.received}}</span>
	<span class="created" style="display: none;">{{$item.created_date}}</span>
	<span class="uriid" style="display: none;">{{$item.uriid}}</span>
	<div class="wall-item-container panel-body{{$item.indent}} {{$item.shiny}} {{$item.previewing}}">
		<div class="media">
			{{* Put additional actions in a top-right dropdown menu *}}

			{{* The avatar picture and the photo-menu *}}
			<div class="dropdown pull-left"><!-- Dropdown -->
				<div class="hidden-sm hidden-xs contact-photo-wrapper mframe{{if $item.owner_url}} wwfrom{{/if}}">
					<a href="{{$item.profile_url}}" class="userinfo click-card u-url" id="wall-item-photo-menu-{{$item.id}}">
						<div class="contact-photo-image-wrapper">
							<img src="{{$item.thumb}}" class="contact-photo media-object {{$item.sparkle}}" id="wall-item-photo-{{$item.id}}" alt="{{$item.name}}" loading="lazy"/>
						</div>
					</a>
				</div>
				<div class="hidden-lg hidden-md contact-photo-wrapper mframe{{if $item.owner_url}} wwfrom{{/if}}">
					<a href="{{$item.profile_url}}" class="userinfo click-card u-url" id="wall-item-photo-menu-xs-{{$item.id}}">
						<div class="contact-photo-image-wrapper">
							<img src="{{$item.thumb}}" class="contact-photo-xs media-object {{$item.sparkle}}" id="wall-item-photo-xs-{{$item.id}}" alt="{{$item.name}}" loading="lazy"/>
						</div>
					</a>
				</div>
			</div><!-- ./Dropdown -->


			{{* contact info header*}}
			<div class="contact-info hidden-sm hidden-xs media-body"><!-- Desktop -->
				<div class="preferences">
					{{if $item.network_svg && $item.plink}}
						<span class="wall-item-network"><a href="{{$item.plink.href}}" class="plink u-url" target="_blank"><img class="network-svg" src="{{$item.network_svg}}" alt="{{$item.plink.title}}" title="{{$item.network_name}} - {{$item.plink.title}}" loading="lazy"/></a></span>
					{{elseif $item.plink}}
						<a href="{{$item.plink.href}}" class="plink u-url" aria-label="{{$item.plink.title}}" title="{{$item.network_name}} - {{$item.plink.title}}" target="_blank">{{$item.network_name}}</a>
					{{elseif $item.network_svg}}
						<span class="wall-item-network"><img class="network-svg" src="{{$item.network_svg}}" title="{{$item.network_name}}" loading="lazy" aria-hidden="true"/></span>
					{{else}}
						<span class="wall-item-network" title="{{$item.app}}">{{$item.network_name}}</span>
					{{/if}}
				</div>
				<h2 class="media-heading">
					<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link userinfo hover-card">
						<span class="wall-item-name {{$item.sparkle}}">{{$item.name}}</span>
					</a>
				{{if $item.owner_url}}
					{{$item.via}}
					<a href="{{$item.owner_url}}" target="redir" title="{{$item.olinktitle}}" class="wall-item-name-link userinfo hover-card">
						<span class="wall-item-name {{$item.osparkle}}" id="wall-item-ownername-{{$item.id}}">{{$item.owner_name}}</span>
					</a>
				{{/if}}
				{{if $item.lock}}
					<span class="navicon lock fakelink" onClick="lockview(event, 'item', {{$item.id}});" title="{{$item.lock}}">
						&nbsp;<small><i class="fa fa-lock" aria-hidden="true"></i></small>
					</span>
				{{elseif $item.connector}}
					<span class="fa fa-lock" title="{{$item.connector}}"></span>
				{{/if}}
					<div class="additional-info text-muted">
						<div id="wall-item-ago-{{$item.id}}" class="wall-item-ago">
							<small>
								<a href="{{$item.plink.orig}}">
									<time class="time" title="{{$item.localtime}}" data-toggle="tooltip" datetime="{{$item.utc}}">{{$item.ago}}</time>
								</a>
								{{if $item.pinned}}
									<span aria-hidden="true">&bull;</span> <i class="fa fa-thumb-tack" aria-hidden="true" title="{{$item.pinned}}"></i>
									<span class="sr-only">{{$item.pinned}}</span>
								{{/if}}
							</small>
						</div>

						{{if $item.location_html}}
						<div id="wall-item-location-{{$item.id}}" class="wall-item-location">
							<small><span class="location">({{$item.location_html nofilter}})</span></small>
						</div>
						{{/if}}
					</div>
				{{* @todo $item.created have to be inserted *}}
				</h2>
			</div>

			{{* contact info header for smartphones *}}
			<div class="contact-info contact-info-xs hidden-lg hidden-md">
				<div class="preferences">
					{{if $item.network_svg && $item.plink}}
						<span class="wall-item-network"><a href="{{$item.plink.href}}" class="plink u-url" target="_blank"><img class="network-svg" src="{{$item.network_svg}}" alt="{{$item.plink.title}}" title="{{$item.network_name}} - {{$item.plink.title}}" loading="lazy"/></a></span>
					{{elseif $item.plink}}
						<a href="{{$item.plink.href}}" class="plink u-url" aria-label="{{$item.plink.title}}" title="{{$item.network_name}} - {{$item.plink.title}}" target="_blank">{{$item.network_name}}</a>
					{{elseif $item.network_svg}}
						<span class="wall-item-network"><img class="network-svg" src="{{$item.network_svg}}" alt="{{$item.plink.title}}" title="{{$item.network_name}} - {{$item.plink.title}}" loading="lazy"/></span>
					{{else}}
						<span class="wall-item-network" title="{{$item.app}}">{{$item.network_name}}</span>
					{{/if}}
				</div>
				<h5 class="media-heading">
					<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link userinfo hover-card"><span>{{$item.name}}</span></a>
					<p class="text-muted"><small>
						<span class="wall-item-ago">{{$item.ago}}</span> {{if $item.location_html}}&nbsp;&mdash;&nbsp;({{$item.location_html nofilter}}){{/if}}</small>
					</p>
				</h5>
			</div>

			<div class="clearfix"></div>

			<hr />

			{{* item content *}}
			<div class="wall-item-content {{$item.type}}" id="wall-item-content-{{$item.id}}">
				{{if $item.title}}
				<span class="wall-item-title" id="wall-item-title-{{$item.id}}"><h2 class="media-heading" dir="auto"><a href="{{$item.plink.href}}" class="{{$item.sparkle}}">{{$item.title}}</a></h2><br /></span>
				{{/if}}

				<div class="wall-item-body" id="wall-item-body-{{$item.id}}" dir="auto">{{$item.body_html nofilter}}</div>
			</div>

			<!-- TODO -->
			<div class="wall-item-bottom">
				<div class="wall-item-links"></div>
				<div class="wall-item-tags">
			{{if !$item.suppress_tags}}
				{{foreach $item.hashtags as $tag}}
					<span class="tag label btn-info sm">{{$tag nofilter}} <i class="fa fa-bolt" aria-hidden="true"></i></span>
				{{/foreach}}

				{{foreach $item.mentions as $tag}}
					<span class="mention label btn-warning sm">{{$tag nofilter}} <i class="fa fa-user" aria-hidden="true"></i></span>
				{{/foreach}}
			{{/if}}

				{{foreach $item.folders as $cat}}
					<span class="folder label btn-danger sm">{{$cat.name}}{{if $cat.removeurl}} (<a href="{{$cat.removeurl}}" title="{{$remove}}">x</a>) {{/if}} </span>
				{{/foreach}}

				{{foreach $item.categories as $cat}}
					<span class="category label btn-success sm">{{$cat.name}}{{if $cat.removeurl}} (<a href="{{$cat.removeurl}}" title="{{$remove}}">x</a>) {{/if}} </span>
				{{/foreach}}
				</div>
				{{if $item.edited}}<div class="itemedited text-muted">{{$item.edited['label']}} (<span title="{{$item.edited['date']}}">{{$item.edited['relative']}}</span>)</div>{{/if}}
			</div>
			<!-- ./TODO -->

			<p class="wall-item-actions">
				{{* Action buttons to interact with the item (like: like, dislike, share and so on *}}
					{{if $item.threaded}}{{/if}}

					{{* Buttons for like and dislike *}}
					{{if $item.vote}}
						{{if $item.vote.like}}
					<button type="button" class="btn btn-defaultbutton-likes{{if $item.responses.like.self}} active" aria-pressed="true{{/if}}" id="like-{{$item.id}}" title="{{$item.vote.like.0}}" onclick="doActivityItemAction({{$item.id}}, 'like'{{if $item.responses.like.self}}, true{{/if}});"></button>
						{{/if}}
						{{if $item.vote.like AND $item.vote.dislike}}
					<span role="presentation" class="separator">•</span>
						{{/if}}

						{{if $item.vote.dislike}}
					<button type="button" class="btn btn-defaultbutton-likes{{if $item.responses.like.self}} active" aria-pressed="true{{/if}}" id="dislike-{{$item.id}}" title="{{$item.vote.dislike.0}}" onclick="doActivityItemAction({{$item.id}}, 'dislike'{{if $item.responses.dislike.self}}, true{{/if}});"></button>
						{{/if}}
						{{if ($item.vote.like OR $item.vote.dislike) AND $item.comment_html}}
					<span role="presentation" class="separator">•</span>
						{{/if}}
					{{/if}}

					{{* Button to open the comment text field *}}
					{{if $item.comment_html}}
						<button type="button" class="btn btn-default" id="comment-{{$item.id}}" title="{{$item.switchcomment}}" onclick="openClose('item-comments-{{$item.id}}'); commentExpand({{$item.id}});"></button>
					{{/if}}

					{{* Button for sharing the item *}}
					{{if $item.vote}}
						{{if $item.vote.share}}
							{{if $item.vote.like OR $item.vote.dislike OR $item.comment_html}}
					<span role="presentation" class="separator">•</span>
							{{/if}}
					<button type="button" class="btn btn-default" id="share-{{$item.id}}" title="{{$item.vote.share.0}}" onclick="jotShare({{$item.id}});"><i class="fa fa-retweet" aria-hidden="true"></i></button>
						{{/if}}
					{{/if}}

				{{* Put additional actions in a dropdown menu *}}
				{{if $item.menu && ($item.edpost || $item.tagger || $item.filer || $item.pin || $item.star || $item.follow_thread || $item.ignore || $item.drop.dropping || $item.browsershare)}}
					<span role="presentation" class="separator"></span>
					<span class="more-links btn-group{{if $item.thread_level> 1}} dropup{{/if}}">
						<button type="button" class="btn-link dropdown-toggle" data-toggle="dropdown" id="dropdownMenuOptions-{{$item.id}}" aria-haspopup="true" aria-expanded="false" title="{{$item.menu}}"><i class="fa fa-ellipsis-h" aria-hidden="true"></i>&nbsp;{{$item.menu}}</button>
						<ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="dropdownMenuOptions-{{$item.id}}">
						{{if $item.edpost}} {{* edit the posting *}}
							<li role="menuitem">
								<a href="javascript:editpost('{{$item.edpost.0}}?mode=none');" title="{{$item.edpost.1}}" class="btn-link navicon pencil"><i class="fa fa-pencil" aria-hidden="true"></i> {{$item.edpost.1}}</a>
							</li>
						{{/if}}

						{{if $item.tagger}} {{* tag the post *}}
							<li role="menuitem">
								<a id="tagger-{{$item.id}}" href="javascript:itemTag({{$item.id}});" class="btn-link {{$item.tagger.class}}" title="{{$item.tagger.add}}"><i class="fa fa-tag" aria-hidden="true"></i> {{$item.tagger.add}}</a>
							</li>
						{{/if}}

						{{if $item.filer}}
							<li role="menuitem">
								<a id="filer-{{$item.id}}" href="javascript:itemFiler({{$item.id}});" class="btn-link filer-item filer-icon" title="{{$item.filer}}"><i class="fa fa-folder" aria-hidden="true"></i>&nbsp;{{$item.filer}}</a>
							</li>
						{{/if}}

						{{if $item.pin}}
							<li role="menuitem">
								<a id="pin-{{$item.id}}" href="javascript:doPin({{$item.id}});" class="btn-link {{$item.pin.classdo}}" title="{{$item.pin.do}}"><i class="fa fa-circle-o" aria-hidden="true"></i>&nbsp;{{$item.pin.do}}</a>
								<a id="unpin-{{$item.id}}" href="javascript:doPin({{$item.id}});" class="btn-link {{$item.pin.classundo}}" title="{{$item.pin.undo}}"><i class="fa fa-dot-circle-o" aria-hidden="true"></i>&nbsp;{{$item.pin.undo}}</a>
							</li>
						{{/if}}

						{{if $item.star}}
							<li role="menuitem">
								<a id="star-{{$item.id}}" href="javascript:doStar({{$item.id}});" class="btn-link {{$item.star.classdo}}" title="{{$item.star.do}}"><i class="fa fa-star-o" aria-hidden="true"></i>&nbsp;{{$item.star.do}}</a>
								<a id="unstar-{{$item.id}}" href="javascript:doStar({{$item.id}});" class="btn-link {{$item.star.classundo}}" title="{{$item.star.undo}}"><i class="fa fa-star" aria-hidden="true"></i>&nbsp;{{$item.star.undo}}</a>
							</li>
						{{/if}}

						{{if $item.follow_thread}}
							<li role="menuitem">
								<a id="follow_thread-{{$item.id}}" href="javascript:{{$item.follow_thread.action}}" class="btn-link" title="{{$item.follow_thread.title}}"><i class="fa fa-plus" aria-hidden="true"></i>&nbsp;{{$item.follow_thread.title}}</a>
							</li>
						{{/if}}

						{{if $item.language}}
						<li role="menuitem">
							<a id="language-{{$item.id}}" href="javascript:displayLanguage({{$item.uriid}});" class="btn-link filer-item language-icon" title="{{$item.language}}"><i class="fa fa-language" aria-hidden="true"></i>&nbsp;{{$item.language}}</a>
						</li>
						{{/if}}

						<li role="menuitem">
							<a id="searchtext-{{$item.id}}" href="javascript:displaySearchText({{$item.uriid}});" class="btn-link filer-item search-icon" title="{{$item.searchtext}}"><i class="fa fa-search" aria-hidden="true"></i>&nbsp;{{$item.searchtext}}</a>
						</li>

						{{if $item.browsershare}}
							<li role="menuitem" class="button-browser-share">
								<a id="browser-share-{{$item.id}}" href="javascript:navigator.share({url: '{{$item.plink.orig}}'});" class="btn-link button-browser-share" title="{{$item.browsershare.1}}"><i class="fa fa-share-alt" aria-hidden="true"></i>&nbsp;{{$item.browsershare.0}}</a>
							</li>
						{{/if}}

						{{if ($item.edpost || $item.tagger || $item.filer || $item.pin || $item.star || $item.follow_thread) && ($item.ignore || $item.drop.dropping)}}
							<li role="separator" class="divider"></li>
						{{/if}}

						{{if $item.ignore}}
							<li role="menuitem">
								<a id="ignore-{{$item.id}}" href="javascript:doIgnoreThread({{$item.id}});" class="btn-link {{$item.ignore.classdo}}" title="{{$item.ignore.do}}"><i class="fa fa-bell-slash" aria-hidden="true"></i> {{$item.ignore.do}}</a>
							</li>
							<li role="menuitem">
								<a id="unignore-{{$item.id}}" href="javascript:doIgnoreThread({{$item.id}});" class="btn-link {{$item.ignore.classundo}}"  title="{{$item.ignore.undo}}"><i class="fa fa-bell" aria-hidden="true"></i> {{$item.ignore.undo}}</a>
							</li>
						{{/if}}

						{{if $item.drop && $item.drop.dropping}}
							<li role="menuitem">
								<a class="btn-link navicon delete" href="javascript:dropItem('item/drop/{{$item.id}}', 'item-{{$item.guid}}');" title="{{$item.drop.label}}"><i class="fa fa-trash" aria-hidden="true"></i> {{$item.drop.label}}</a>
							</li>
							{{/if}}
						</ul>
						<img id="like-rotator-{{$item.id}}" class="like-rotator" src="images/rotator.gif" alt="{{$item.wait}}" title="{{$item.wait}}" style="display: none;" />
					</span>
				{{else}}
					<img id="like-rotator-{{$item.id}}" class="like-rotator" src="images/rotator.gif" alt="{{$item.wait}}" title="{{$item.wait}}" style="display: none;" />
				{{/if}}


				<span class="wall-item-actions-right">
					{{* Event attendance buttons *}}
				{{if $item.isevent}}
					<span class="vote-event">
						<button type="button" class="btn btn-defaultbutton-event{{if $item.responses.attendyes.self}} active" aria-pressed="true{{/if}}" id="attendyes-{{$item.id}}" title="{{$item.attend.0}}" onclick="doActivityItemAction({{$item.id}}, 'attendyes'{{if $item.responses.attendyes.self}}, true{{/if}});"><i class="fa fa-check" aria-hidden="true"><span class="sr-only">{{$item.attend.0}}</span></i></button>
						<button type="button" class="btn btn-defaultbutton-event{{if $item.responses.attendno.self}} active" aria-pressed="true{{/if}}" id="attendno-{{$item.id}}" title="{{$item.attend.1}}" onclick="doActivityItemAction({{$item.id}}, 'attendno'{{if $item.responses.attendno.self}}, true{{/if}});"><i class="fa fa-times" aria-hidden="true"><span class="sr-only">{{$item.attend.1}}</span></i></button>
						<button type="button" class="btn btn-defaultbutton-event{{if $item.responses.attendmaybe.self}} active" aria-pressed="true{{/if}}" id="attendmaybe-{{$item.id}}" title="{{$item.attend.2}}" onclick="doActivityItemAction({{$item.id}}, 'attendmaybe'{{if $item.responses.attendmaybe.self}}, true{{/if}});"><i class="fa fa-question" aria-hidden="true"><span class="sr-only">{{$item.attend.2}}</span></i></button>
					</span>
				{{/if}}

					<span class="pull-right checkbox">
				{{if $item.drop && $item.drop.pagedrop}}
						<input type="checkbox" title="{{$item.drop.select}}" name="itemselected[]" id="checkbox-{{$item.id}}" class="item-select" value="{{$item.id}}" />
						<label for="checkbox-{{$item.id}}"></label>
				{{/if}}
					</span>
				</span>
			</p><!--./wall-item-actions-->

			{{* Display likes, dislike and attendance stats *}}
			{{if $item.emojis}}
				{{foreach $item.emojis as $emoji}}
					{{if $emoji.icon.fa}}
						<span class="wall-item-emoji" title="{{$emoji.title}}"><i class="fa {{$emoji.icon.fa}}" aria-hidden="true"></i> {{$emoji.total}}</span>
					{{else}}
						<span class="wall-item-emoji" title="{{$emoji.title}}">{{$emoji.emoji}} {{$emoji.total}}</span>
					{{/if}}
				{{/foreach}}
			{{elseif $item.responses}}
				<div class="wall-item-responses">
				{{foreach $item.responses as $verb=>$response}}
					<div class="wall-item-{{$verb}}" id="wall-item-{{$verb}}-{{$item.id}}">{{$response.output nofilter}}</div>
				{{/foreach}}
				</div>
			{{/if}}

			<div class="wall-item-conv" id="wall-item-conv-{{$item.id}}" dir="auto">
			{{if $item.conv}}
				<a href="{{$item.conv.href}}" id="context-{{$item.id}}" title="{{$item.conv.title}}">{{$item.conv.title}}</a>
			{{/if}}
			</div>
		</div><!--./media>-->
	</div><!--./scrollable-->
</div><!-- ./panel-body -->
