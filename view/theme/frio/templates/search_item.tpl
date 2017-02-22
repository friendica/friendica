<!-- TODO => Unknow block -->
<div class="wall-item-decor" style="display:none;">
	<span class="icon s22 star {{$item.isstarred}}" id="starred-{{$item.id}}" title="{{$item.star.starred}}">{{$item.star.starred}}</span>
	{{if $item.lock}}<span class="navicon lock fakelink" onclick="lockview(event,{{$item.id}});" title="{{$item.lock}}"></span><span class="fa fa-lock"></span>{{/if}}
	<img id="like-rotator-{{$item.id}}" class="like-rotator" src="images/rotator.gif" alt="{{$item.wait}}" title="{{$item.wait}}" style="display: none;" />
</div>
<!-- ./TODO => Unknow block -->


<div class="panel">
	<div class="wall-item-container panel-body{{$item.indent}} {{$item.shiny}} {{$item.previewing}}" >
		<div class="media">
			{{* Put additional actions in a top-right dropdown menu *}}

			<ul class="nav nav-pills preferences">
				<li><span class="wall-item-network" title="{{$item.app}}">{{$item.network_name}}</span></li>

				{{if $item.plink || $item.star || $item.drop.dropping || $item.edpost}}
				<li class="dropdown">
					<a class="dropdown-toggle" data-toggle="dropdown"  href="#" id="dropdownMenuTools-{{$item.id}}" role="button" aria-haspopup="true" aria-expanded="false"><i class="fa fa-angle-down"></i></a>

					<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="dropdownMenuTools-{{$item.id}}">
						{{if $item.plink}}      {{*link to the original source of the item *}}
	                                        <li role="menuitem">
	                                                <a title="{{$item.plink.title}}" href="{{$item.plink.href}}" class="navicon plink"><i class="fa fa-external-link"></i> {{$item.plink.title}}</a>
	                                        </li>
	                                        {{/if}}

						{{if $item.edpost}} {{* edit the posting *}}
						<li role="menuitem">
							<a href="#" href="{{$item.edpost.0}}" title="{{$item.edpost.1}}" class="navicon pencil"><i class="fa fa-pencil"></i> {{$item.edpost.1}}</a>
						</li>
						{{/if}}

						{{if $item.tagger}} {{* tag the post *}}
						<li role="menuitem">
							<a href="#" id="tagger-{{$item.id}}" onclick="itemTag({{$item.id}}); return false;" class="{{$item.tagger.class}}" title="{{$item.tagger.add}}"><i class="fa fa-tag"></i> {{$item.tagger.add}}</a>
						</li>
						{{/if}}

						{{if $item.filer}}
						<li role="menuitem">
							<a href="#" id="filer-{{$item.id}}" onclick="itemFiler({{$item.id}}); return false;" class="filer-item filer-icon" title="{{$item.filer}}"><i class="fa fa-folder"></i>&nbsp;{{$item.filer}}</a>
						</li>
						{{/if}}

						{{if $item.star}}
						<li role="menuitem">
							<a href="#" id="star-{{$item.id}}" onclick="dostar({{$item.id}}); return false;" class="{{$item.star.classdo}}" title="{{$item.star.do}}"><i class="fa fa-star-o"></i>&nbsp;{{$item.star.do}}</a>
							<a href="#" id="unstar-{{$item.id}}" onclick="dostar({{$item.id}}); return false;" class="{{$item.star.classundo}}" title="{{$item.star.undo}}"><i class="fa fa-star"></i>&nbsp;{{$item.star.undo}}</a>
						</li>
						{{/if}}

						{{if $item.drop.dropping}}
						<li role="separator" class="divider"></li>
						<li role="menuitem">
							<a class="navicon delete" onclick="dropItem('item/drop/{{$item.id}}', '#item-{{$item.guid}}'); return false;" title="{{$item.drop.delete}}"><i class="fa fa-trash"></i> {{$item.drop.delete}}</a>
						</li>
						{{/if}}
					</ul>
				</li>
				{{/if}}
			</ul>

			{{* The avatar picture and the photo-menu *}}
			<div class="dropdown pull-left"><!-- Dropdown -->
				<div class="hidden-sm hidden-xs contact-photo-wrapper mframe{{if $item.owner_url}} wwfrom{{/if}}">
					<a href="{{$item.profile_url}}" class="userinfo" id="wall-item-photo-menu-{{$item.id}}">
						<div class="contact-photo-image-wrapper">
							<img src="{{$item.thumb}}" class="contact-photo media-object {{$item.sparkle}}" id="wall-item-photo-{{$item.id}}" alt="{{$item.name}}" />
						</div>
					</a>
				</div>
				<div class="hidden-lg hidden-md contact-photo-wrapper mframe{{if $item.owner_url}} wwfrom{{/if}}">
					<a href="{{$item.profile_url}}" class="userinfo" id="wall-item-photo-menu-{{$item.id}}">
						<div class="contact-photo-image-wrapper">
							<img src="{{$item.thumb}}" class="contact-photo-xs media-object {{$item.sparkle}}" id="wall-item-photo-{{$item.id}}" alt="{{$item.name}}" />
						</div>
					</a>
				</div>
			</div><!-- ./Dropdown -->


			{{* contact info header*}}
			<div role="heading " class="contact-info hidden-sm hidden-xs media-body"><!-- <= For computer -->
				<h4 class="media-heading"><a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link userinfo"><span class="wall-item-name btn-link {{$item.sparkle}}">{{$item.name}}</span></a>
					{{if $item.owner_url}}{{$item.via}} <a href="{{$item.owner_url}}" target="redir" title="{{$item.olinktitle}}" class="wall-item-name-link userinfo"><span class="wall-item-name{{$item.osparkle}} btn-link" id="wall-item-ownername-{{$item.id}}">{{$item.owner_name}}</span></a>{{/if}}
					{{if $item.lock}}<span class="navicon lock fakelink" onClick="lockview(event,{{$item.id}});" title="{{$item.lock}}">&nbsp;<small><i class="fa fa-lock"></i></small></span>{{/if}}

					<div class="additional-info text-muted">
						<div id="wall-item-ago-{{$item.id}}" class="wall-item-ago">
							<small><a href="{{$item.plink.orig}}"><span class="time" title="{{$item.localtime}}" data-toggle="tooltip">{{$item.ago}}</span></a></small>
						</div>

						{{if $item.location}}
						<div id="wall-item-location-{{$item.id}}" class="wall-item-location">
							<small><span class="location">({{$item.location}})</span></small>
						</div>
						{{/if}}
					</div>
				{{* @todo $item.created have to be inserted *}}
				</h4>
			</div>

			{{* contact info header for smartphones *}}
			<div role="heading " class="contact-info-xs hidden-lg hidden-md">
				<h5 class="media-heading">
					<a href="{{$item.profile_url}}" title="{{$item.linktitle}}" class="wall-item-name-link userinfo"><span>{{$item.name}}</span></a>
					<p class="text-muted"><small>
						<span class="wall-item-ago">{{$item.ago}}</span> {{if $item.location}}&nbsp;&mdash;&nbsp;({{$item.location}}){{/if}}</small>
					</p>
				</h5>
			</div>

			<div class="clearfix"></div>

			<hr />


			{{* item content *}}
			<div itemprop="description" class="wall-item-content {{$item.type}}" id="wall-item-content-{{$item.id}}">
				{{* insert some space if it's an top-level post *}}
				{{if $item.thread_level==1}}
				<div style="height:10px;">&nbsp;</div> <!-- use padding/margin instead-->
				{{/if}}

				{{if $item.title}}
				<span class="wall-item-title" id="wall-item-title-{{$item.id}}"><h4 class="media-heading"><a href="{{$item.plink.href}}" class="{{$item.sparkle}}">{{$item.title}}</a></h4><br /></span>
				{{/if}}

				<div class="wall-item-body" id="wall-item-body-{{$item.id}}">{{$item.body}}</div>
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
						<span class='folder label btn-danger sm'>{{$cat.name}}</a>{{if $cat.removeurl}} (<a href="{{$cat.removeurl}}" title="{{$remove}}">x</a>) {{/if}} </span>
					{{/foreach}}

					{{foreach $item.categories as $cat}}
						<span class='category label btn-success sm'>{{$cat.name}}</a>{{if $cat.removeurl}} (<a href="{{$cat.removeurl}}" title="{{$remove}}">x</a>) {{/if}} </span>
					{{/foreach}}
				</div>
					{{if $item.edited}}<div class="itemedited text-muted">{{$item.edited['label']}} (<span title="{{$item.edited['date']}}">{{$item.edited['relative']}}</span>)</div>{{/if}}
			</div>
			<!-- ./TODO -->

			<div class="wall-item-actions">
				{{* Action buttons to interact with the item (like: like, dislike, share and so on *}}
				<div class="wall-item-actions-left pull-left">
					<!--comment this out to try something different {{if $item.threaded}}{{if $item.comment}}
					<div id="button-reply" class="pull-left">
						<span class="btn-link" id="comment-{{$item.id}}" onclick="openClose('item-comments-{{$item.id}}'); commentExpand({{$item.id}});"><i class="fa fa-reply" title="{{$item.switchcomment}}"></i> </span>
					</div>
					{{/if}}{{/if}}-->

					{{if $item.threaded}}{{/if}}

					{{* Buttons for like and dislike *}}
					{{if $item.vote}}
						{{if $item.vote.like}}
						<a role="button" href="#" class="button-likes{{if $item.responses.like.self}} active{{/if}}" id="like-{{$item.id}}" title="{{$item.vote.like.0}}" onclick="dolike({{$item.id}},'like'); return false;">{{$item.vote.like.0}}</a>
						{{/if}}

						{{if $item.vote.like AND $item.vote.dislike}}
						<span role="presentation" class="separator">&nbsp;•&nbsp;</span>
						{{/if}}

						{{if $item.vote.dislike}}
						<a role="button" href="#" class="button-likes{{if $item.responses.like.self}} active{{/if}}" id="dislike-{{$item.id}}" title="{{$item.vote.dislike.0}}" onclick="dolike({{$item.id}},'dislike'); return false;">{{$item.vote.dislike.0}}</a>
						{{/if}}

						{{if ($item.vote.like OR $item.vote.dislike) AND $item.comment}}
						<span role="presentation" class="separator">&nbsp;•&nbsp;</span>
						{{/if}}
					{{/if}}

					{{* Butten to open the comment text field *}}
					{{if $item.comment}}
					<a role="button" class="" id="comment-{{$item.id}}" title="{{$item.switchcomment}}" onclick="openClose('item-comments-{{$item.id}}'); commentExpand({{$item.id}});">{{$item.switchcomment}} </a>
					{{/if}}

					{{* Button for sharing the item *}}
					{{if $item.vote}}
						{{if ($item.vote.like OR $item.vote.dislike OR $item.comment) AND $item.vote.share}}
						<span role="presentation" class="separator">&nbsp;•&nbsp;</span>
						{{/if}}
						{{if $item.vote.share}}
						<a role="button" href="#" class="" id="share-{{$item.id}}" title="{{$item.vote.share.0}}" onclick="jotShare({{$item.id}}); return false;"><i class="fa fa-retweet"></i>&nbsp;{{$item.vote.share.0}}</a>
						{{/if}}
					{{/if}}
				</div>


				<div class="wall-item-actions-right pull-right">
					{{* Event attendance buttons *}}
					{{if $item.isevent}}
					<div class="vote-event">
						<a role="button" href="#" class="button-event{{if $item.responses.attendyes.self}} active{{/if}}" id="attendyes-{{$item.id}}" title="{{$item.attend.0}}" onclick="dolike({{$item.id}},'attendyes'); return false;"><i class="fa fa-check"><span class="sr-only">{{$item.attend.0}}</span></i></a>
						<a role="button" href="#" class="button-event{{if $item.responses.attendno.self}} active{{/if}}" id="attendno-{{$item.id}}" title="{{$item.attend.1}}" onclick="dolike({{$item.id}},'attendno'); return false;"><i class="fa fa-times"><span class="sr-only">{{$item.attend.1}}</span></i></a>
						<a role="button" href="#" class="button-event{{if $item.responses.attendmaybe.self}} active{{/if}}" id="attendmaybe-{{$item.id}}" title="{{$item.attend.2}}" onclick="dolike({{$item.id}},'attendmaybe'); return false;"><i class="fa fa-question"><span class="sr-only">{{$item.attend.2}}</span></i></a>
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

					{{* Display likes, dislike and attendance stats *}}
			{{if $item.responses}}
				<div class="wall-item-responses">
					{{foreach $item.responses as $verb=>$response}}
					<div class="wall-item-{{$verb}}" id="wall-item-{{$verb}}-{{$item.id}}">{{$response.output}}</div>
					{{/foreach}}
				</div>
			{{/if}}

			<div class="wall-item-conv" id="wall-item-conv-{{$item.id}}" >
			{{if $item.conv}}
					<a href='{{$item.conv.href}}' id='context-{{$item.id}}' title='{{$item.conv.title}}'>{{$item.conv.title}}</a>
			{{/if}}
			</div>
		</div><!--./media>-->
	</div><!-- ./panel-body -->
</div><!--./panel-->
