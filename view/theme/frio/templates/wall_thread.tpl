
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
	{{assign var="top_child_total" count($item.children)}}
	{{assign var="top_child_nr" 0}}
{{/if}}
{{if $item.thread_level==2}}
	{{assign var="top_child_nr" value=$top_child_nr+1 scope=parent}}
{{/if}}

{{if $item.thread_level==2 && $top_child_nr==1}}
<div class="comment-container well well-sm"> <!--top-child-begin-->
{{/if}}
{{* end of hacky part to count childrens *}}


{{if $mode == display}}
{{else}}
{{if $item.comment_firstcollapsed}}
	{{*
	<div align="center" style="margin-top:-34px;">
		<div class="hide-comments-outer btn btn-default" onclick="showHideComments({{$item.id}});">
			<span id="hide-comments-total-{{$item.id}}" class="hide-comments-total">{{$item.num_comments}}</span>
			<span id="hide-comments-{{$item.id}}" class="hide-comments fakelink">{{$item.hide_text}}</span>
		</div>
	</div>
	<div id="collapsed-comments-{{$item.id}}" class="collapsed-comments" style="display: none;">
	*}}

	{{if $item.thread_level<3}}
		<div class="hide-comments-outer-wrapper">
			<div class="hide-comments-outer fakelink" onclick="showHideComments({{$item.id}});">
				<span id="hide-comments-total-{{$item.id}}"
					class="hide-comments-total">{{$item.num_comments}}</span>
				<span id="hide-comments-{{$item.id}}"
					class="hide-comments">{{$item.hide_text}}</span>
			</div>
			<hr />
		</div>
		<div id="collapsed-comments-{{$item.id}}" class="collapsed-comments" style="display: none;">
	{{else}}
		<div id="collapsed-comments-{{$item.id}}" class="collapsed-comments" style="display: block;">
	{{/if}}
{{/if}}
{{/if}}

<!-- TODO => Unknow block -->
<div class="wall-item-decor" style="display:none;">
	{{if $item.star}}
	<span class="icon s22 star {{$item.isstarred}}" id="starred-{{$item.id}}" title="{{$item.star.starred}}">{{$item.star.starred}}</span>
	{{/if}}
	{{if $item.lock}}<span class="navicon lock fakelink" onclick="lockview(event,{{$item.id}});" title="{{$item.lock}}"></span><span class="fa fa-lock"></span>{{/if}}
</div>
<!-- ./TODO => Unknow block -->



{{* Use a different div container in dependence max thread-level = 7 *}}
{{if $item.thread_level<7}}
<div class="item-{{$item.id}} wall-item-container {{$item.indent}} {{$item.shiny}} {{$item.network}} thread_level_{{$item.thread_level}} {{if $item.thread_level==1}}panel-body h-entry{{else}}u-comment h-cite{{/if}}" id="item-{{$item.guid}}"><!-- wall-item-container -->
{{else}}
<div class="item-{{$item.id}} wall-item-container {{$item.indent}} {{$item.shiny}} {{$item.network}} thread_level_7 u-comment h-cite" id="item-{{$item.guid}}">
 {{/if}}
	<div class="media">
		{{* Put addional actions in a top-right dropdown menu *}}

		<ul class="nav nav-pills preferences">
			<li><span class="wall-item-network" title="{{$item.app}}">{{$item.network_name}}</span></li>

			{{if $item.plink || $item.drop.dropping || $item.edpost || $item.ignore || $item.tagger || $item.star || $item.filer}}
			<li class="dropdown">
				<button type="button" class="btn-link dropdown-toggle" data-toggle="dropdown" id="dropdownMenuTools-{{$item.id}}" aria-haspopup="true" aria-expanded="false"><i class="fa fa-angle-down"></i></button>

				<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="dropdownMenuTools-{{$item.id}}">
					{{if $item.plink}}	{{*link to the original source of the item *}}
					<li role="menuitem">
						<a title="{{$item.plink.title}}" href="{{$item.plink.href}}" class="navicon plink u-url"><i class="fa fa-external-link"></i> {{$item.plink.title}}</a>
					</li>
					{{/if}}

					{{if $item.edpost}} {{* edit the posting *}}
					<li role="menuitem">
						<button type="button" onclick="editpost('{{$item.edpost.0}}?mode=none');" title="{{$item.edpost.1}}" class="btn-link navicon pencil"><i class="fa fa-pencil"></i> {{$item.edpost.1}}</button>
					</li>
					{{/if}}

					{{if $item.tagger}} {{* tag the post *}}
					<li role="menuitem">
						<button type="button" id="tagger-{{$item.id}}" onclick="itemTag({{$item.id}});" class="btn-link {{$item.tagger.class}}" title="{{$item.tagger.add}}"><i class="fa fa-tag"></i> {{$item.tagger.add}}</button>
					</li>
					{{/if}}

					{{if $item.filer}}
					<li role="menuitem">
						<button type="button" id="filer-{{$item.id}}" onclick="itemFiler({{$item.id}});" class="btn-link filer-item filer-icon" title="{{$item.filer}}"><i class="fa fa-folder"></i>&nbsp;{{$item.filer}}</button>
					</li>
					{{/if}}

					{{if $item.star}}
					<li role="menuitem">
						<button type="button" id="star-{{$item.id}}" onclick="dostar({{$item.id}});" class="btn-link {{$item.star.classdo}}" title="{{$item.star.do}}"><i class="fa fa-star-o"></i>&nbsp;{{$item.star.do}}</button>
						<button type="button" id="unstar-{{$item.id}}" onclick="dostar({{$item.id}});" class="btn-link {{$item.star.classundo}}" title="{{$item.star.undo}}"><i class="fa fa-star"></i>&nbsp;{{$item.star.undo}}</button>
					</li>
					{{/if}}

					{{if $item.ignore || $item.drop.dropping}}
					<li role="separator" class="divider"></li>
					{{/if}}

					{{if $item.ignore}}
						<li role="menuitem">
							<button type="button" id="ignore-{{$item.id}}" onclick="doignore({{$item.id}});" class="btn-link {{$item.ignore.classdo}}" title="{{$item.ignore.do}}"><i class="fa fa-eye-slash"></i> {{$item.ignore.do}}</button>
						</li>
						<li role="menuitem">
							<button type="button" id="unignore-{{$item.id}}" onclick="doignore({{$item.id}});" class="btn-link {{$item.ignore.classundo}}"  title="{{$item.ignore.undo}}"><i class="fa fa-eye"></i> {{$item.ignore.undo}}</button>
						</li>
					{{/if}}

					{{if $item.drop.dropping}}
					<li role="menuitem">
						<button type="button" class="btn-link navicon delete" onclick="dropItem('item/drop/{{$item.id}}', 'item-{{$item.guid}}');" title="{{$item.drop.delete}}"><i class="fa fa-trash"></i> {{$item.drop.delete}}</button>
					</li>
					{{/if}}
				</ul>
			</li>
			{{/if}}
		</ul>


		{{* The avatar picture and the photo-menu *}}
		<div class="dropdown pull-left"><!-- Dropdown -->
			{{if $item.thread_level==1}}
			<div class="hidden-sm hidden-xs contact-photo-wrapper mframe{{if $item.owner_url}} wwfrom{{/if}} p-author h-card">
				<a class="userinfo" id="wall-item-photo-menu-{{$item.id}} u-url" href="{{$item.profile_url}}">
					<div class="contact-photo-image-wrapper">
						<img src="{{$item.thumb}}" class="contact-photo media-object {{$item.sparkle}} p-name u-photo" id="wall-item-photo-{{$item.id}}" alt="{{$item.name}}" />
					</div>
				</a>
			</div>
			<div class="hidden-lg hidden-md contact-photo-wrapper mframe{{if $item.owner_url}} wwfrom{{/if}}">
				<a class="userinfo" id="wall-item-photo-menu-{{$item.id}}" href="{{$item.profile_url}}">
					<div class="contact-photo-image-wrapper">
						<img src="{{$item.thumb}}" class="contact-photo-xs media-object {{$item.sparkle}}" id="wall-item-photo-{{$item.id}}" alt="{{$item.name}}" />
					</div>
				</a>
			</div>

			{{* The litle overlay avatar picture if someone is posting directly to a wall or a forum *}}
			{{if $item.owner_url}}
			<div aria-hidden="true" class="contact-photo-wrapper mframe wwto" id="wall-item-ownerphoto-wrapper-{{$item.id}}" >
				<a href="{{$item.owner_url}}" target="redir" title="{{$item.olinktitle}}" class="contact-photo-link" id="wall-item-ownerphoto-link-{{$item.id}}">
					<img src="{{$item.owner_photo}}" class="contact-photo {{$item.osparkle}}" id="wall-item-ownerphoto-{{$item.id}}" alt="{{$item.owner_name}}" />
				</a>
			</div>
			{{/if}}

			{{/if}} {{*End if $item.thread_level==1}}

			{{* The avatar picture for comments *}}
			{{if $item.thread_level!=1}}
			<div class="contact-photo-wrapper mframe{{if $item.owner_url}} wwfrom{{/if}} p-author h-card">
				<a class="userinfo" id="wall-item-photo-menu-{{$item.id}} u-url" href="{{$item.profile_url}}">
					<div class="contact-photo-image-wrapper">
						<img src="{{$item.thumb}}" class="contact-photo-xs media-object {{$item.sparkle}} p-name u-photo" id="wall-item-photo-{{$item.id}}" alt="{{$item.name}}" />
					</div>
				</a>
			</div>
			{{/if}}
		</div><!-- ./Dropdown -->



		{{* contact info header*}}
		{{if $item.thread_level==1}}
		<div role="heading " aria-level="{{$item.thread_level}}" class="contact-info hidden-sm hidden-xs media-body"><!-- <= For computer -->
			<h4 class="media-heading"><a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link userinfo"><span class="wall-item-name {{$item.sparkle}}">{{$item.name}}</span></a>
			{{if $item.owner_url}}{{$item.via}} <a href="{{$item.owner_url}}" target="redir" title="{{$item.olinktitle}}" class="wall-item-name-link userinfo"><span class="wall-item-name {{$item.osparkle}}" id="wall-item-ownername-{{$item.id}}">{{$item.owner_name}}</span></a>{{/if}}
			{{if $item.lock}}<span class="navicon lock fakelink" onClick="lockview(event,{{$item.id}});" title="{{$item.lock}}" data-toggle="tooltip">&nbsp;<small><i class="fa fa-lock"></i></small></span>{{/if}}
			</h4>

			<div class="additional-info text-muted">
				<div id="wall-item-ago-{{$item.id}}" class="wall-item-ago">
					<small><a href="{{$item.plink.orig}}"><span class="time" title="{{$item.localtime}}" data-toggle="tooltip"><time class="dt-published" datetime="{{$item.localtime}}">{{$item.ago}}</time></span></a></small>
				</div>

				{{if $item.location}}
				<div id="wall-item-location-{{$item.id}}" class="wall-item-location">
					<small><span class="location">({{$item.location}})</span></small>
				</div>
				{{/if}}
			</div>
			{{* @todo $item.created have to be inserted *}}
		</div>

		{{* contact info header for smartphones *}}
		<div role="heading " aria-level="{{$item.thread_level}}" class="contact-info-xs hidden-lg hidden-md"><!-- <= For smartphone (responsive) -->
			<h5 class="media-heading">
				<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link userinfo"><span>{{$item.name}}</span></a>
				<p class="text-muted">
					<small><a class="time" href="{{$item.plink.orig}}"><span class="wall-item-ago">{{$item.ago}}</span></a> {{if $item.location}}&nbsp;&mdash;&nbsp;({{$item.location}}){{/if}}</small>
				</p>
			</h5>
		</div>
		{{/if}} {{* End of if $item.thread_level==1 *}}

		{{* contact info header for comments *}}
		{{if $item.thread_level!=1}}
		<div class="media-body">{{*this is the media body for comments - this div must be closed at the end of the file *}}
		<div role="heading " aria-level="{{$item.thread_level}}" class="contact-info-comment">
			<h5 class="media-heading">
				<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link userinfo"><span class="fakelink">{{$item.name}}</span></a>
				<span class="text-muted">
					<small><span title="{{$item.localtime}}" data-toggle="tooltip">{{$item.ago}}</span> {{if $item.location}}&nbsp;&mdash;&nbsp;({{$item.location}}){{/if}}</small>
				</span>
			</h5>
		</div>
		{{/if}}

		<div class="clearfix"></div>

		{{* Insert Line to seperate item header and item content visually *}}
		{{if $item.thread_level==1}}<hr />{{/if}}

		{{* item content *}}
		<div itemprop="description" class="wall-item-content {{$item.type}}" id="wall-item-content-{{$item.id}}">
			{{* insert some space if it's an top-level post *}}
			{{if $item.thread_level==1}}
			<div style="height:10px;">&nbsp;</div> <!-- use padding/margin instead-->
			{{/if}}

			{{if $item.title}}
			<span class="wall-item-title" id="wall-item-title-{{$item.id}}"><h4 class="media-heading"><a href="{{$item.plink.href}}" class="{{$item.sparkle}} p-name">{{$item.title}}</a></h4><br /></span>
			{{/if}}

			<div class="wall-item-body e-content {{if !$item.title}}p-name{{/if}}" id="wall-item-body-{{$item.id}}">{{$item.body}}</div>
		</div>

		<!-- TODO -->
		<div class="wall-item-bottom">
			<div class="wall-item-links">
			</div>
			<div class="wall-item-tags">
				{{foreach $item.hashtags as $tag}}
					<span class='tag label btn-info sm'>{{$tag}} <i class="fa fa-bolt"></i></span>
				{{/foreach}}

				{{foreach $item.mentions as $tag}}
					<span class='mention label btn-warning sm'>{{$tag}} <i class="fa fa-user"></i></span>
				{{/foreach}}

				{{foreach $item.folders as $cat}}
					<span class='folder label btn-danger sm'><span class="p-category">{{$cat.name}}</span></a>{{if $cat.removeurl}} (<a href="{{$cat.removeurl}}" title="{{$remove}}">x</a>) {{/if}} </span>
				{{/foreach}}

				{{foreach $item.categories as $cat}}
					<span class='category label btn-success sm'><span class="p-category">{{$cat.name}}</span></a>{{if $cat.removeurl}} (<a href="{{$cat.removeurl}}" title="{{$remove}}">x</a>) {{/if}} </span>
				{{/foreach}}
			</div>
				{{if $item.edited}}<div class="itemedited text-muted">{{$item.edited['label']}} (<span title="{{$item.edited['date']}}">{{$item.edited['relative']}}</span>)</div>{{/if}}
		</div>
		<!-- ./TODO -->

		<!-- <hr /> -->
		<div class="wall-item-actions">
			{{* Action buttons to interact with the item (like: like, dislike, share and so on *}}
			<div class="wall-item-actions-left pull-left">
				<!--comment this out to try something different {{if $item.threaded}}{{if $item.comment}}
				<div id="button-reply" class="pull-left">
					<button type="button" class="btn-link" id="comment-{{$item.id}}" onclick="openClose('item-comments-{{$item.id}}'); commentExpand({{$item.id}});"><i class="fa fa-reply" title="{{$item.switchcomment}}"></i> </span>
				</div>
				{{/if}}{{/if}}-->

				{{if $item.threaded}}{{/if}}

				{{* Buttons for like and dislike *}}
				{{if $item.vote}}
					{{if $item.vote.like}}
					<button type="button" class="btn-link button-likes{{if $item.responses.like.self}} active" aria-pressed="true{{/if}}" id="like-{{$item.id}}" title="{{$item.vote.like.0}}" onclick="doLikeAction({{$item.id}},'like');" data-toggle="button"><i class="fa fa-thumbs-up"></i>&nbsp;{{$item.vote.like.1}}</button>
					{{/if}}
					{{if $item.vote.like AND $item.vote.dislike}}
					<span role="presentation" class="separator">•</span>
					{{/if}}
					{{if $item.vote.dislike}}
					<button type="button" class="btn-link button-likes{{if $item.responses.dislike.self}} active" aria-pressed="true{{/if}}" id="dislike-{{$item.id}}" title="{{$item.vote.dislike.0}}" onclick="doLikeAction({{$item.id}},'dislike');" data-toggle="button"><i class="fa fa-thumbs-down"></i>&nbsp;{{$item.vote.dislike.1}}</button>
					{{/if}}

					{{if ($item.vote.like OR $item.vote.dislike) AND $item.comment}}
					<span role="presentation" class="separator">•</span>
					{{/if}}
				{{/if}}

				{{* Button to open the comment text field *}}
				{{if $item.comment}}
				<button type="button" class="btn-link button-comments" id="comment-{{$item.id}}" title="{{$item.switchcomment}}" {{if $item.thread_level != 1}}onclick="openClose('item-comments-{{$item.id}}'); commentExpand({{$item.id}});" {{else}} onclick="showHide('item-comments-{{$item.id}}'); commentExpand({{$item.id}});"{{/if}}><i class="fa fa-commenting"></i>&nbsp;{{$item.switchcomment}}</button>
				{{/if}}

				{{* Button for sharing the item *}}
				{{if $item.vote}}
					{{if $item.vote.share}}
						{{if $item.vote.like OR $item.vote.dislike OR $item.comment}}
					<span role="presentation" class="separator">•</span>
						{{/if}}
					<button type="button" class="btn-link button-votes" id="share-{{$item.id}}" title="{{$item.vote.share.0}}" onclick="jotShare({{$item.id}});"><i class="fa fa-retweet"></i>&nbsp;{{$item.vote.share.1}}</a>
					{{/if}}
				{{/if}}
				<img id="like-rotator-{{$item.id}}" class="like-rotator" src="images/rotator.gif" alt="{{$item.wait}}" title="{{$item.wait}}" style="display: none;" />
			</div>

			<div class="wall-item-actions-right pull-right">
				{{* Event attendance buttons *}}
				{{if $item.isevent}}
				<div class="vote-event">
					<button type="button" class="btn btn-xs btn-default button-event{{if $item.responses.attendyes.self}} active" aria-pressed="true{{/if}}" id="attendyes-{{$item.id}}" title="{{$item.attend.0}}" onclick="doLikeAction({{$item.id}},'attendyes');"><i class="fa fa-check"><span class="sr-only">{{$item.attend.0}}</span></i></button>
					<button type="button" class="btn btn-xs btn-default button-event{{if $item.responses.attendno.self}} active" aria-pressed="true{{/if}}" id="attendno-{{$item.id}}" title="{{$item.attend.1}}" onclick="doLikeAction({{$item.id}},'attendno');"><i class="fa fa-times"><span class="sr-only">{{$item.attend.1}}</span></i></button>
					<button type="button" class="btn btn-xs btn-default button-event{{if $item.responses.attendmaybe.self}} active" aria-pressed="true{{/if}}" id="attendmaybe-{{$item.id}}" title="{{$item.attend.2}}" onclick="doLikeAction({{$item.id}},'attendmaybe');"><i class="fa fa-question"><span class="sr-only">{{$item.attend.2}}</span></i></button>
				</div>
				{{/if}}

				<div class="pull-right checkbox">
					{{if $item.drop.pagedrop}}
					<input type="checkbox" title="{{$item.drop.select}}" name="itemselected[]" id="checkbox-{{$item.id}}" class="item-select" value="{{$item.id}}" />
					<label for="checkbox-{{$item.id}}"></label>
				{{/if}}
				</div>
			</div>
			<div class="clearfix"></div>
		</div><!--./wall-item-actions-->

		<div class="wall-item-links"></div>

		{{* Display likes, dislike and attendance stats *}}
		{{if $item.responses}}
			<div class="wall-item-responses">
				{{foreach $item.responses as $verb=>$response}}
				<div class="wall-item-{{$verb}}" id="wall-item-{{$verb}}-{{$item.id}}">{{$response.output}}</div>
				{{/foreach}}
			</div>
		{{/if}}

		{{if $item.thread_level!=1}}
		</div><!--./media-body from for comments-->
		<hr />
		{{/if}}


		{{* Insert comment box of threaded children *}}
		{{if $item.threaded}}{{if $item.comment}}{{if $item.indent==comment}}
		<div class="wall-item-comment-wrapper" id="item-comments-{{$item.id}}" style="display: none;">
			{{$item.comment}}
		</div>
		{{/if}}{{/if}}{{/if}}


		{{foreach $item.children as $child}}
			{{*
			{{if $child.type == tag}}
				{{include file="wall_item_tag.tpl" item=$child}}
			{{else}}
				{{include file="{{$item.template}}" item=$child}}
			{{/if}}
			*}}
			{{include file="{{$item.template}}" item=$child}}
		{{/foreach}}

		{{* Insert the comment box of the top level post at the bottom of the thread.
			Display this comment box if there are any comments. If not hide it. In this
			case it could be opend with the "comment" button *}}
		{{if $item.total_comments_num}}
			{{if $item.threaded}}{{if $item.comment}}{{if $item.thread_level==1}}
				<div class="wall-item-comment-wrapper well well-small" id="item-comments-{{$item.id}}">{{$item.comment}}</div>
			{{/if}}{{/if}}{{/if}}

			{{if $item.flatten}}
				<div class="wall-item-comment-wrapper well well-small" id="item-comments-{{$item.id}}">{{$item.comment}}</div>
			{{/if}}
		{{else}}
			{{if $item.threaded}}{{if $item.comment}}{{if $item.thread_level==1}}
				<div class="wall-item-comment-wrapper well well-small" id="item-comments-{{$item.id}}" style="display: none;">{{$item.comment}}</div>
			{{/if}}{{/if}}{{/if}}

			{{if $item.flatten}}
				<div class="wall-item-comment-wrapper well well-small" id="item-comments-{{$item.id}}" style="display: none;">{{$item.comment}}</div>
			{{/if}}
		{{/if}}
	</div><!-- /media -->
</div><!-- ./panel-body or ./wall-item-container -->


{{if $mode == display}}
{{else}}
{{if $item.comment_lastcollapsed}}</div>{{/if}}
{{/if}}


{{* close the comment-container div if no more thread_level = 2 children are left *}}
{{if $item.thread_level==2 && $top_child_nr==$top_child_total}}
</div><!--./comment-container-->
{{/if}}
