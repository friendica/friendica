
{{* This is a little bit hacky. This is needed to have some sort comments container.
It would be better if it would be done in friendica core but since core lacks this functionality
it is done in the theme

In short: the piece of code counts the total number of children of the toplevelpost
- this are usaly all posts with thread_level = 2 - and stores it in variable $top_children_total.
The first time a children which hits thread_level = 2 and $top_child = 1 opens the div.

Everytime when a children with top_level = 2 comes up $top_child_nr rises with 1.
The div get's closed if thread_level = 2 and the value of $top_child_nr is the same
as the value of $top_child_total (this is done at the end of this file)
*}}
{{if $item.thread_level==1}}
	{{assign var="top_child_total" value=count($item.children)}}
	{{assign var="top_child_nr" value=0}}
{{/if}}
{{if $item.thread_level==2}}
	{{assign var="top_child_nr" value=$top_child_nr+1 scope=parent}}
{{/if}}

{{if $item.thread_level==2 && $top_child_nr==1}}
<div class="comment-container"> <!--top-child-begin-->
{{/if}}
{{* end of hacky part to count childrens *}}


{{if $mode == display}}
{{else}}
{{if $item.comment_firstcollapsed}}
	{{if $item.thread_level<3}}
		<button type="button" class="hide-comments-outer fakelink" onclick="showHideComments({{$item.id}});">
			<span id="hide-comments-total-{{$item.id}}" class="hide-comments-total">
				<i class="fa fa-caret-right" aria-hidden="true"></i>
				{{$item.num_comments}} - {{$item.show_text}}
			</span>
			<span id="hide-comments-{{$item.id}}" class="hide-comments" style="display: none">
				<i class="fa fa-caret-down" aria-hidden="true"></i>
				{{$item.num_comments}} - {{$item.hide_text}}
			</span>
		</button>
		<div id="collapsed-comments-{{$item.id}}" class="collapsed-comments" style="display: none;">
	{{else}}
		<div id="collapsed-comments-{{$item.id}}" class="collapsed-comments" style="display: block;">
	{{/if}}
{{/if}}
{{/if}}

{{* TODO => Unknown block *}}
<div class="wall-item-decor" style="display:none;">
	{{if $item.star}}
	<span class="icon s22 star {{$item.isstarred}}" id="starred-{{$item.id}}" title="{{$item.star.starred}}">{{$item.star.starred}}</span>
	{{/if}}
	{{if $item.lock}}<span class="navicon lock fakelink" onclick="lockview(event, 'item', {{$item.id}});" title="{{$item.lock}}"></span><span class="fa fa-lock"></span>
	{{elseif $item.connector}}<span class="fa fa-lock" title="{{$item.connector}}"></span>{{/if}}
</div>
{{* /TODO => Unknown block *}}


{{* Use a different div container in dependence max thread-level = 7 *}}
{{if $item.thread_level<7}}
<div class="item-{{$item.id}} wall-item-container {{$item.indent}} {{$item.network}} thread_level_{{$item.thread_level}} {{if $item.thread_level==1}}panel-body h-entry{{else}}u-comment h-cite{{/if}}" id="item-{{$item.guid}}"><!-- wall-item-container -->
{{else}}
<div class="item-{{$item.id}} wall-item-container {{$item.indent}} {{$item.network}} thread_level_7 u-comment h-cite" id="item-{{$item.guid}}">
{{/if}}
{{if $item.thread_level==1}}
<span class="commented" style="display: none;">{{$item.commented}}</span>
<span class="received" style="display: none;">{{$item.received}}</span>
<span class="created" style="display: none;">{{$item.created_date}}</span>
<span class="uriid" style="display: none;">{{$item.uriid}}</span>
{{/if}}
	<div class="media {{$item.shiny}}">
	{{if $item.reshared}}
		<p class="wall-item-announce wall-item-responses" id="wall-item-announce-{{$item.id}}"><i class="fa fa-retweet" aria-hidden="true"></i> {{$item.reshared nofilter}}</p>
	{{/if}}
		{{* The avatar picture and the photo-menu *}}
		<div class="dropdown pull-left"><!-- Dropdown -->
			{{if $item.thread_level==1}}
			<div class="hidden-sm hidden-xs contact-photo-wrapper mframe{{if $item.owner_url}} wwfrom{{/if}} p-author h-card">
				<a class="userinfo click-card u-url" id="wall-item-photo-menu-{{$item.id}}" href="{{$item.profile_url}}">
					<div class="contact-photo-image-wrapper">
						<img src="{{$item.thumb}}" class="contact-photo media-object {{$item.sparkle}} p-name u-photo" id="wall-item-photo-{{$item.id}}" alt="{{$item.name}}" />
					</div>
				</a>
			</div>
			<div class="hidden-lg hidden-md contact-photo-wrapper mframe{{if $item.owner_url}} wwfrom{{/if}}">
				<a class="userinfo click-card u-url" id="wall-item-photo-menu-xs-{{$item.id}}" href="{{$item.profile_url}}">
					<div class="contact-photo-image-wrapper">
						<img src="{{$item.thumb}}" class="contact-photo-xs media-object {{$item.sparkle}}" id="wall-item-photo-xs-{{$item.id}}" alt="{{$item.name}}" />
					</div>
				</a>
			</div>

			{{* The litle overlay avatar picture if someone is posting directly to a wall or a forum *}}
			{{if $item.owner_url}}
			<div aria-hidden="true" class="contact-photo-wrapper mframe wwto" id="wall-item-ownerphoto-wrapper-{{$item.id}}">
				<a href="{{$item.owner_url}}" target="redir" title="{{$item.olinktitle}}" class="contact-photo-link" id="wall-item-ownerphoto-link-{{$item.id}}">
					<img src="{{$item.owner_photo}}" class="contact-photo {{$item.osparkle}}" id="wall-item-ownerphoto-{{$item.id}}" alt="{{$item.owner_name}}" />
				</a>
			</div>
			{{/if}}

			{{/if}} {{*End if $item.thread_level==1}}

			{{* The avatar picture for comments *}}
			{{if $item.thread_level!=1}}
			<div class="contact-photo-wrapper mframe{{if $item.owner_url}} wwfrom{{/if}} p-author h-card">
				<a class="userinfo click-card u-url" id="wall-item-photo-menu-{{$item.id}}" href="{{$item.profile_url}}">
					<div class="contact-photo-image-wrapper">
						<img src="{{$item.thumb}}" class="contact-photo-xs media-object {{$item.sparkle}} p-name u-photo" id="wall-item-photo-comment-{{$item.id}}" alt="{{$item.name}}" />
					</div>
				</a>
			</div>
			{{/if}}
		</div><!-- ./Dropdown -->


	{{if $item.thread_level!=1}}
		<div class="media-body">{{*this is the media body for comments - this div must be closed at the end of the file *}}
	{{/if}}

			{{* contact info header*}}
		<div class="contact-info">
			<div class="preferences">
				{{if $item.network_icon != ""}}
					<span class="wall-item-network"><i class="fa fa-{{$item.network_icon}}" title="{{$item.network_name}}" aria-hidden="true"></i></span>
				{{else}}
					<span class="wall-item-network" title="{{$item.app}}">{{$item.network_name}}</span>
				{{/if}}
				{{if $item.plink}}	{{*link to the original source of the item *}}
					<a href="{{$item.plink.href}}" class="plink u-url" aria-label="{{$item.plink.title}}" title="{{$item.plink.title}}">
						<i class="fa fa-external-link"></i>
					</a>
				{{/if}}
			</div>
		{{if $item.thread_level==1}}
			<div class="hidden-sm hidden-xs media-body"><!-- <= For computer -->
				<h4 class="media-heading">
					<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link userinfo hover-card">
						<span class="wall-item-name {{$item.sparkle}}">{{$item.name}}</span>
					</a>
				{{if $item.owner_url}}
					{{$item.via}}
					<a href="{{$item.owner_url}}" target="redir" title="{{$item.olinktitle}}" class="wall-item-name-link userinfo hover-card">
						<span class="wall-item-name {{$item.osparkle}}" id="wall-item-ownername-{{$item.id}}">{{$item.owner_name}}</span>
					</a>
				{{/if}}
				{{if $item.connector}}
					<small><i class="fa fa-plug" title="{{$item.connector}}" aria-hidden="true"></i></small>
				{{else}}
					<span class="navicon lock fakelink" onClick="lockview(event, 'item', {{$item.id}});" title="{{$item.privacy}}" data-toggle="tooltip">
						&nbsp;<small><i class="fa {{if $item.private == 1}}fa-lock{{elseif $item.private == 0}}fa-globe{{else}}fa-low-vision{{/if}}" aria-hidden="true"></i></small>
					</span>
				{{/if}}
				</h4>

				<div class="additional-info text-muted">
					<div id="wall-item-ago-{{$item.id}}" class="wall-item-ago">
						<small>
							<a href="{{$item.plink.orig}}">
								<time class="time dt-published" title="{{$item.localtime}}" data-toggle="tooltip" datetime="{{$item.utc}}">{{$item.ago}}</time>
							</a>
							{{if $item.owner_self}}
								{{include file="sub/delivery_count.tpl" delivery=$item.delivery}}
							{{/if}}
							{{if $item.direction}}
								{{include file="sub/direction.tpl" direction=$item.direction}}
							{{/if}}
							{{if $item.pinned}}
								&bull; <i class="fa fa-thumb-tack" aria-hidden="true" title="{{$item.pinned}}"></i>
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
			</div>

			{{* contact info header for smartphones *}}
			<div class="contact-info-xs hidden-lg hidden-md"><!-- <= For smartphone (responsive) -->
				<h5 class="media-heading">
					<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link userinfo hover-card"><span>{{$item.name}}</span></a>
					<p class="text-muted">
						<small>
							<a href="{{$item.plink.orig}}">
								<time class="time" class="wall-item-ago" datetime="{{$item.utc}}">{{$item.ago}}</time>
							</a>
							{{if $item.location_html}}&nbsp;&mdash;&nbsp;({{$item.location_html nofilter}}){{/if}}
							{{if $item.owner_self}}
								{{include file="sub/delivery_count.tpl" delivery=$item.delivery}}
							{{/if}}
							{{if $item.direction}}
								{{include file="sub/direction.tpl" direction=$item.direction}}
							{{/if}}
						</small>
					</p>
				</h5>
			</div>
		{{else}} {{* End of if $item.thread_level == 1 *}}
			{{* contact info header for comments *}}
			<div class="contact-info-comment">
				<h5 class="media-heading">
					<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link userinfo hover-card"><span class="fakelink">{{$item.name}}</span></a>
					{{if $item.connector}}
						<small><i class="fa fa-plug" title="{{$item.connector}}" aria-hidden="true"></i></small>
					{{else}}
						<span class="navicon lock fakelink" onClick="lockview(event, 'item', {{$item.id}});" title="{{$item.privacy}}" data-toggle="tooltip">
							&nbsp;<small><i class="fa {{if $item.private == 1}}fa-lock{{elseif $item.private == 0}}fa-globe{{else}}fa-low-vision{{/if}}" aria-hidden="true"></i></small>
						</span>
					{{/if}}
					<span class="text-muted">
				</h5>
				<small>
					<a href="{{$item.plink.orig}}">
						<time class="time" title="{{$item.localtime}}" data-toggle="tooltip" datetime="{{$item.utc}}">{{$item.ago}}</time>
					</a>
					{{if $item.location_html}}&nbsp;&mdash;&nbsp;({{$item.location_html nofilter}}){{/if}}
					{{if $item.owner_self}}
						{{include file="sub/delivery_count.tpl" delivery=$item.delivery}}
					{{/if}}
					{{if $item.direction}}
						{{include file="sub/direction.tpl" direction=$item.direction}}
					{{/if}}
				</small>
			</span>
			</div>
		{{/if}} {{* End of if $item.thread_level != 1 *}}
		</div>

		<div class="clearfix"></div>

		{{* Insert Line to seperate item header and item content visually *}}
		{{if $item.thread_level==1}}<hr />{{/if}}

		{{* item content *}}
		<div class="wall-item-content {{$item.type}}" id="wall-item-content-{{$item.id}}">
			{{if $item.title}}
			<span class="wall-item-title" id="wall-item-title-{{$item.id}}"><h4 class="media-heading" dir="auto"><a href="{{$item.plink.href}}" class="{{$item.sparkle}} p-name">{{$item.title}}</a></h4><br /></span>
			{{/if}}

			<div class="wall-item-body e-content {{if !$item.title}}p-name{{/if}}" id="wall-item-body-{{$item.id}}" dir="auto">{{$item.body_html nofilter}}</div>
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

			{{*foreach $item.implicit_mentions as $tag}}
				<span class="mention label label-default sm">{{$tag nofilter}} <i class="fa fa-eye-slash" aria-hidden="true"></i></span>
			{{/foreach*}}
		{{/if}}
			{{foreach $item.folders as $folder}}
				<span class="folder label btn-danger sm p-category">{{$folder.name}}{{if $folder.removeurl}} (<a href="{{$folder.removeurl}}" class="filerm" title="{{$remove}}">x</a>){{/if}}</span>
			{{/foreach}}

			{{foreach $item.categories as $cat}}
				<span class="category label btn-success sm p-category"><a href="{{$cat.url}}">{{$cat.name}}</a>{{if $cat.removeurl}} (<a href="{{$cat.removeurl}}" class="filerm" title="{{$remove}}">x</a>){{/if}}</span>
			{{/foreach}}
			</div>
			{{if $item.edited}}<div class="itemedited text-muted">{{$item.edited['label']}} (<span title="{{$item.edited['date']}}">{{$item.edited['relative']}}</span>)</div>{{/if}}
		</div>
		<!-- ./TODO -->

		<!-- <hr /> -->
		<div class="wall-item-actions">
			{{* Action buttons to interact with the item (like: like, dislike, share and so on *}}
			<span class="wall-item-actions-left hidden-xs">

			{{* Buttons for like and dislike *}}
			{{if $item.vote}}
				{{if $item.vote.like}}
				<button type="button" class="btn-link button-likes{{if $item.responses.like.self}} active" aria-pressed="true{{/if}}" id="like-{{$item.id}}" title="{{$item.vote.like.0}}" onclick="doActivityItemAction({{$item.id}}, 'like'{{if $item.responses.like.self}}, true{{/if}});" ><i class="fa fa-thumbs-up" aria-hidden="true"></i>&nbsp;{{$item.vote.like.1}}</button>
				{{/if}}
				{{if $item.vote.like AND $item.vote.dislike}}
				<span role="presentation" class="separator"></span>
				{{/if}}
				{{if $item.vote.dislike}}
				<button type="button" class="btn-link button-likes{{if $item.responses.dislike.self}} active" aria-pressed="true{{/if}}" id="dislike-{{$item.id}}" title="{{$item.vote.dislike.0}}" onclick="doActivityItemAction({{$item.id}}, 'dislike'{{if $item.responses.dislike.self}}, true{{/if}});" ><i class="fa fa-thumbs-down" aria-hidden="true"></i>&nbsp;{{$item.vote.dislike.1}}</button>
				{{/if}}

				{{if ($item.vote.like OR $item.vote.dislike) AND $item.comment_html}}
				<span role="presentation" class="separator"></span>
				{{/if}}
			{{/if}}

			{{if $item.remote_comment}}
				<a href="{{$item.remote_comment.2}}" class="btn-link button-comments" title="{{$item.remote_comment.0}}"><i class="fa fa-commenting" aria-hidden="true"></i>&nbsp;{{$item.remote_comment.1}}</a>
			{{/if}}

			{{* Button to open the comment text field *}}
			{{if $item.comment_html}}
				<button type="button" class="btn-link button-comments" id="comment-{{$item.id}}" title="{{$item.switchcomment}}" {{if $item.thread_level != 1}}onclick="openClose('item-comments-{{$item.id}}'); commentExpand({{$item.id}});" {{else}} onclick="openClose('item-comments-{{$item.id}}'); commentExpand({{$item.id}});"{{/if}}><i class="fa fa-commenting" aria-hidden="true"></i>&nbsp;{{$item.switchcomment}}</button>
			{{/if}}

			{{* Button for sharing the item *}}
			{{if $item.vote}}
				{{if $item.vote.share OR $item.vote.announce}}
					{{if $item.vote.like OR $item.vote.dislike OR $item.comment_html}}
				<span role="presentation" class="separator"></span>
					{{/if}}
				{{/if}}
				{{if $item.vote.announce}}
				<button type="button" class="btn-link button-announces{{if $item.responses.announce.self}} active" aria-pressed="true{{/if}}" id="announce-{{$item.id}}" title="{{$item.vote.announce.0}}" onclick="doActivityItemAction({{$item.id}}, 'announce'{{if $item.responses.announce.self}}, true{{/if}});" ><i class="fa fa-retweet" aria-hidden="true"></i>&nbsp;{{$item.vote.announce.1}}</button>
				<span role="presentation" class="separator"></span>
				{{/if}}
				{{if $item.vote.share}}
				<button type="button" class="btn-link button-votes" id="share-{{$item.id}}" title="{{$item.vote.share.0}}" onclick="jotShare({{$item.id}});"><i class="fa fa-share" aria-hidden="true"></i>&nbsp;{{$item.vote.share.1}}</button>
				{{/if}}
			{{/if}}

			{{* Put additional actions in a dropdown menu *}}
			{{if $item.menu && ($item.edpost || $item.tagger || $item.filer || $item.pin || $item.star || $item.follow_thread || $item.ignore || ($item.drop && $item.drop.dropping) || $item.browsershare)}}
				<span role="presentation" class="separator"></span>
				<span class="more-links btn-group{{if $item.thread_level > 1}} dropup{{/if}}">
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
							<a id="language-{{$item.id}}" href="javascript:alert('{{$item.language.1}}');" class="btn-link filer-item language-icon" title="{{$item.language.0}}"><i class="fa fa-language" aria-hidden="true"></i>&nbsp;{{$item.language.0}}</a>
						</li>
						{{/if}}

						{{if $item.browsershare}}
							<li role="menuitem" class="button-browser-share">
								<a id="browser-share-{{$item.id}}" href="javascript:navigator.share({url: '{{$item.plink.orig}}'})" class="btn-link button-browser-share" title="{{$item.browsershare.1}}"><i class="fa fa-share-alt" aria-hidden="true"></i>&nbsp;{{$item.browsershare.0}}</a>
							</li>
						{{/if}}

						{{if ($item.edpost || $item.tagger || $item.filer || $item.pin || $item.star || $item.follow_thread) && ($item.ignore || ($item.drop && $item.drop.dropping))}}
						<li role="separator" class="divider"></li>
						{{/if}}

						{{if $item.ignore}}
							<li role="menuitem">
								<a id="ignore-{{$item.id}}" href="javascript:doIgnoreThread({{$item.id}});" class="btn-link {{$item.ignore.classdo}}" title="{{$item.ignore.do}}"><i class="fa fa-eye-slash" aria-hidden="true"></i> {{$item.ignore.do}}</a>
							</li>
							<li role="menuitem">
								<a id="unignore-{{$item.id}}" href="javascript:doIgnoreThread({{$item.id}});" class="btn-link {{$item.ignore.classundo}}"  title="{{$item.ignore.undo}}"><i class="fa fa-eye" aria-hidden="true"></i> {{$item.ignore.undo}}</a>
							</li>
						{{/if}}

						{{if $item.drop && $item.drop.dropping}}
						<li role="menuitem">
							<a class="btn-link navicon delete" href="javascript:dropItem('item/drop/{{$item.id}}/{{$item.return}}', 'item-{{$item.guid}}');" title="{{$item.drop.delete}}"><i class="fa fa-trash" aria-hidden="true"></i> {{$item.drop.delete}}</a>
						</li>
						{{/if}}

						{{if $item.block}}
						<li role="menuitem">
							<a class="btn-link navicon block" href="javascript:blockAuthor('item/block/{{$item.id}}/{{$item.return}}', 'item-{{$item.guid}}');" title="{{$item.block.block}}"><i class="fa fa-ban" aria-hidden="true"></i> {{$item.block.block}}</a>
						</li>
						{{/if}}
						{{if $item.ignore_author}}
							<li role="menuitem">
								<a class="btn-link navicon ignore" href="javascript:ignoreAuthor('item/ignore/{{$item.id}}/{{$item.return}}', 'item-{{$item.guid}}');" title="{{$item.ignore_author.ignore}}"><i class="fa fa-ban" aria-hidden="true"></i> {{$item.ignore_author.ignore}}</a>
							</li>
							{{/if}}
						</ul>
					<img id="like-rotator-{{$item.id}}" class="like-rotator" src="images/rotator.gif" alt="{{$item.wait}}" title="{{$item.wait}}" style="display: none;" />
				</span>
			{{else}}
				<img id="like-rotator-{{$item.id}}" class="like-rotator" src="images/rotator.gif" alt="{{$item.wait}}" title="{{$item.wait}}" style="display: none;" />
			{{/if}}

			</span>

			<span class="wall-item-actions-right hidden-xs">
				{{* Event attendance buttons *}}
			{{if $item.isevent}}
				<span class="vote-event">
					<button type="button" class="btn btn-xs btn-default button-event{{if $item.responses.attendyes.self}} active" aria-pressed="true{{/if}}" id="attendyes-{{$item.id}}" title="{{$item.attend.0}}" onclick="doActivityItemAction({{$item.id}}, 'attendyes'{{if $item.responses.attendyes.self}}, true{{/if}});"><i class="fa fa-check" aria-hidden="true"><span class="sr-only">{{$item.attend.0}}</span></i></button>
					<button type="button" class="btn btn-xs btn-default button-event{{if $item.responses.attendno.self}} active" aria-pressed="true{{/if}}" id="attendno-{{$item.id}}" title="{{$item.attend.1}}" onclick="doActivityItemAction({{$item.id}}, 'attendno'{{if $item.responses.attendno.self}}, true{{/if}});"><i class="fa fa-times" aria-hidden="true"><span class="sr-only">{{$item.attend.1}}</span></i></button>
					<button type="button" class="btn btn-xs btn-default button-event{{if $item.responses.attendmaybe.self}} active" aria-pressed="true{{/if}}" id="attendmaybe-{{$item.id}}" title="{{$item.attend.2}}" onclick="doActivityItemAction({{$item.id}}, 'attendmaybe'{{if $item.responses.attendmaybe.self}}, true{{/if}});"><i class="fa fa-question" aria-hidden="true"><span class="sr-only">{{$item.attend.2}}</span></i></button>
				</span>
			{{/if}}

				<span class="pull-right checkbox">
			{{if $item.drop && $item.drop.pagedrop}}
					<input type="checkbox" title="{{$item.drop.select}}" name="itemselected[]" id="checkbox-{{$item.id}}" class="item-select" value="{{$item.id}}" />
					<label for="checkbox-{{$item.id}}"></label>
			{{/if}}
				</span>
			</span>

			<div class="wall-item-actions-items btn-toolbar btn-group visible-xs" role="group">
				<div class="wall-item-actions-row">
					{{* Buttons for like and dislike *}}
					{{if $item.vote}}
						{{if $item.vote.like}}
							<button type="button" class="btn button-likes{{if $item.responses.like.self}} active" aria-pressed="true{{/if}}" id="like-{{$item.id}}" title="{{$item.vote.like.0}}" onclick="doActivityItemAction({{$item.id}}, 'like'{{if $item.responses.like.self}}, true{{/if}});" ><i class="fa fa-thumbs-up" aria-hidden="true"></i></button>
						{{/if}}
						{{if $item.vote.dislike}}
							<button type="button" class="btn button-likes{{if $item.responses.dislike.self}} active" aria-pressed="true{{/if}}" id="dislike-{{$item.id}}" title="{{$item.vote.dislike.0}}" onclick="doActivityItemAction({{$item.id}}, 'dislike'{{if $item.responses.dislike.self}}, true{{/if}});" ><i class="fa fa-thumbs-down" aria-hidden="true"></i></button>
						{{/if}}
					{{/if}}

					{{* Button to open the comment text field *}}
					{{if $item.comment_html}}
						<button type="button" class="btn button-comments" id="comment-{{$item.id}}" title="{{$item.switchcomment}}" {{if $item.thread_level != 1}}onclick="openClose('item-comments-{{$item.id}}'); commentExpand({{$item.id}});" {{else}} onclick="openClose('item-comments-{{$item.id}}'); commentExpand({{$item.id}});"{{/if}}><i class="fa fa-commenting" aria-hidden="true"></i></button>
					{{/if}}

					{{if $item.vote.announce OR $item.vote.share}}
						<div class="share-links btn-group{{if $item.thread_level > 1}} dropup{{/if}}" role="group">
							<button type="button" class="btn dropdown-toggle{{if $item.responses.announce.self}} active{{/if}}" data-toggle="dropdown" id="shareMenuOptions-{{$item.id}}" aria-haspopup="true" aria-expanded="false" title="{{$item.menu}}">
								<i class="fa fa-share" aria-hidden="true"></i>
							</button>
							<ul class="dropdown-menu dropdown-menu-left" role="menu" aria-labelledby="shareMenuOptions-{{$item.id}}">
								{{if $item.vote.announce}} {{* edit the posting *}}
								<li role="menuitem">
									{{if $item.responses.announce.self}}
									<a class="btn-link" id="announce-{{$item.id}}" href="javascript:doActivityItemAction({{$item.id}}, 'announce', true);" title="{{$item.vote.unannounce.0}}">
										<i class="fa fa-ban" aria-hidden="true"></i> {{$item.vote.unannounce.1}}
									</a>
									{{else}}
									<a class="btn-link" id="announce-{{$item.id}}" href="javascript:doActivityItemAction({{$item.id}}, 'announce');" title="{{$item.vote.announce.0}}">
										<i class="fa fa-retweet" aria-hidden="true"></i> {{$item.vote.announce.1}}
									</a>
									{{/if}}
								</li>
								{{/if}}
								{{if $item.vote.share}}
								<li role="menuitem">
									<a class="btn-link" id="share-{{$item.id}}" href="javascript:jotShare({{$item.id}});" title="{{$item.vote.share.0}}">
										<i class="fa fa-share" aria-hidden="true"></i> {{$item.vote.share.1}}
									</a>
								</li>
								{{/if}}
								{{if $item.browsershare}}
								<li role="menuitem">
									<button type="button" class="btn-link button-browser-share" onclick="navigator.share({url: '{{$item.plink.orig}}'})" title="{{$item.browsershare.1}}">
										<i class="fa fa-share-alt" aria-hidden="true"></i> {{$item.browsershare.0}}
									</button>
								</li>
								{{/if}}
							</ul>
						</div>
					{{/if}}

					{{* Put additional actions in a dropdown menu *}}
					<img id="like-rotator-{{$item.id}}" class="like-rotator" src="images/rotator.gif" alt="{{$item.wait}}" title="{{$item.wait}}" style="display: none;" />

				{{* Event attendance buttons *}}
				{{if $item.isevent}}
				<div class="btn-group btn-group-event" role="group">
					<button type="button" class="btn btn-default button-event{{if $item.responses.attendyes.self}} active" aria-pressed="true{{/if}}" id="attendyes-{{$item.id}}" title="{{$item.attend.0}}" onclick="doActivityItemAction({{$item.id}}, 'attendyes'{{if $item.responses.attendyes.self}}, true{{/if}});"><i class="fa fa-check" aria-hidden="true"><span class="sr-only">{{$item.attend.0}}</span></i></button>
					<button type="button" class="btn btn-default button-event{{if $item.responses.attendno.self}} active" aria-pressed="true{{/if}}" id="attendno-{{$item.id}}" title="{{$item.attend.1}}" onclick="doActivityItemAction({{$item.id}}, 'attendno'{{if $item.responses.attendno.self}}, true{{/if}});"><i class="fa fa-times" aria-hidden="true"><span class="sr-only">{{$item.attend.1}}</span></i></button>
					<button type="button" class="btn btn-default button-event{{if $item.responses.attendmaybe.self}} active" aria-pressed="true{{/if}}" id="attendmaybe-{{$item.id}}" title="{{$item.attend.2}}" onclick="doActivityItemAction({{$item.id}}, 'attendmaybe'{{if $item.responses.attendmaybe.self}}, true{{/if}});"><i class="fa fa-question" aria-hidden="true"><span class="sr-only">{{$item.attend.2}}</span></i></button>
				</div>
				{{/if}}

				{{if $item.edpost || $item.tagger || $item.filer || $item.pin || $item.star || $item.follow_thread || $item.ignore || ($item.drop && $item.drop.dropping)}}
					<div class="more-links btn-group{{if $item.thread_level > 1}} dropup{{/if}}">
						<button type="button" class="btn dropdown-toggle" data-toggle="dropdown" id="dropdownMenuOptions-{{$item.id}}" aria-haspopup="true" aria-expanded="false" title="{{$item.menu}}"><i class="fa fa-ellipsis-h" aria-hidden="true"></i></button>
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
								<a id="language-{{$item.id}}" href="javascript:alert('{{$item.language.1}}');" class="btn-link filer-item language-icon" title="{{$item.language.0}}"><i class="fa fa-language" aria-hidden="true"></i>&nbsp;{{$item.language.0}}</a>
							</li>
							{{/if}}

							{{if $item.ignore || ($item.drop && $item.drop.dropping)}}
								<li role="separator" class="divider"></li>
							{{/if}}

							{{if $item.ignore}}
								<li role="menuitem">
								<a id="ignore-{{$item.id}}" href="javascript:doIgnoreThread({{$item.id}});" class="btn-link {{$item.ignore.classdo}}" title="{{$item.ignore.do}}"><i class="fa fa-eye-slash" aria-hidden="true"></i> {{$item.ignore.do}}</a>
							</li>
								<li role="menuitem">
								<a id="unignore-{{$item.id}}" href="javascript:doIgnoreThread({{$item.id}});" class="btn-link {{$item.ignore.classundo}}"  title="{{$item.ignore.undo}}"><i class="fa fa-eye" aria-hidden="true"></i> {{$item.ignore.undo}}</a>
							</li>
							{{/if}}

							{{if $item.drop && $item.drop.dropping}}
								<li role="menuitem">
								<a class="btn-link navicon delete" href="javascript:dropItem('item/drop/{{$item.id}}/{{$item.return}}', 'item-{{$item.guid}}');" title="{{$item.drop.delete}}"><i class="fa fa-trash" aria-hidden="true"></i> {{$item.drop.delete}}</a>
							</li>
							{{/if}}
						</ul>
						<img id="like-rotator-{{$item.id}}" class="like-rotator" src="images/rotator.gif" alt="{{$item.wait}}" title="{{$item.wait}}" style="display: none;" />
					</div>
				{{/if}}
				<span class="pull-right checkbox">
					{{if $item.drop && $item.drop.pagedrop}}
						<input type="checkbox" title="{{$item.drop.select}}" name="itemselected[]" id="checkbox-{{$item.id}}" class="item-select" value="{{$item.id}}" />
						<label for="checkbox-{{$item.id}}"></label>
					{{/if}}
				</span>
				</div>
			</div>
		</div><!--./wall-item-actions-->

		<div class="wall-item-links"></div>

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

		{{* Insert comment box of threaded children *}}
		{{if $item.threaded && $item.comment_html && $item.indent==comment}}
			<div class="wall-item-comment-wrapper" id="item-comments-{{$item.id}}" data-display="block" style="display: none;">
				{{$item.comment_html nofilter}}
			</div>
		{{/if}}

		{{if $item.thread_level!=1}}
		</div><!--./media-body from for comments-->
		{{/if}}
	</div>
	{{foreach $item.children as $child}}
		{{include file="{{$item.template}}" item=$child}}
	{{/foreach}}

	{{* Insert the comment box of the top level post at the bottom of the thread.
		Display this comment box if there are any comments. If not hide it. In this
		case it could be opend with the "comment" button *}}
	{{if $item.comment_html && $item.thread_level==1}}
		{{if $item.total_comments_num}}
		<div class="comment-fake-form" id="comment-fake-form-{{$item.id}}">
			<textarea id="comment-fake-text-{{$item.id}}" class="comment-fake-text-empty form-control" placeholder="{{$item.reply_label}}" onFocus="commentOpenUI(this, {{$item.id}});"  rows="1"></textarea>
		</div>
		{{/if}}
		<div class="wall-item-comment-wrapper well well-small" id="item-comments-{{$item.id}}" data-display="block" style="display: none">{{$item.comment_html nofilter}}</div>
	{{/if}}
</div><!-- ./panel-body or ./wall-item-container -->

{{if $mode == display}}
{{else}}
{{if $item.comment_lastcollapsed}}</div>{{/if}}
{{/if}}

{{* close the comment-container div if no more thread_level = 2 children are left *}}
{{if $item.thread_level==2 && $top_child_nr==$top_child_total}}
</div><!--./comment-container-->
{{/if}}
