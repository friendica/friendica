{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper scheduled-posts-wrapper" style="background-color:transparent !important;box-shadow:none !important;border:none !important;">
	<h1>{{$title}}</h1>
{{foreach $schedule as $row}}
<div id="tread-wapper-{{$row.item['uri-id']}}" class="tread-wrapper toplevel_item {{$row.item.network}} panel-default panel">
	<div class="item-{{$row.item['uri-id']}} wall-item-container {{$row.item.network}} thread_level_1 panel-body h-entry" id="item-{{$row.item.guid}}">
		<div class="media">
			<div class="dropdown pull-left">
				<div class="contact-photo-wrapper mframe{{if $row.item['owner-link']}} wwfrom{{/if}} p-author h-card">
					<a aria-haspopup="dialog" href="{{$row.item['author-link']}}" target="redir" title="{{$row.item['author-name']}}" class="userinfo click-card contact-photo-link u-url" id="wall-item-photo-link-{{$row.item.uid}}" {{if $row.item.guid}}data-guid="true"{{/if}}>
						<img src="{{$row.item['author-avatar']}}" class="contact-photo p-name u-photo" id="wall-item-photo-{{$row.item['author-id']}}" alt="{{$row.item['author-name']}}"/>
					</a>
				</div>
				{{if $row.item['owner-link'] && $row.item['owner-link'] != $row.item['author-link']}}
					<div aria-haspopup="dialog" class="contact-photo-wrapper maframe wwto" id="wall-item-ownerphoto-wrapper-{{$row.item['owner-id']}}">
						<a href="{{$row.item['owner-link']}}" target="redir" title="{{$row.item['author-name']}}" class="userinfo click-card contact-photo-link ur-url" id="wall-item-ownerphoto-link-{{$row.item['owner-id']}}">
							<img src="{{$row.item['owner-avatar']}}" class="contact-photo p-name u-photo" id="wall-item-ownerphoto-{{$row.item['owner-id']}}" alt="{{$row.item['owner-name']}}"/>
						</a>
					</div>
				{{/if}}
			</div>
			<div class="contact-info">
				<div class="preferences"></div>
				<div class="media-body">
					<h4 class="media-heading">
						<a aria-haspopup="dialog" href="{{$row.item['author-link']}}" target="redir" title="{{$row.item['author-name']}}" class="userinfo click-card u-url wall-item-name-link" {{if $row.item.guid}}data-guid="true"{{/if}}><span class="wall-item-name sparkle">{{$row.item['author-name']}}</span></a>
						{{if $row.item['owner-link'] && $row.item['owner-link'] != $row.item['author-link']}}{{$row.via}}<a aria-haspopup="dialog" href="{{$row.item['owner-link']}}" target="redir" title="{{$row.item['owner-name']}}" class="userinfo click-card wall-item-name-link"><span class="wall-item-name sparkle">{{$row.item['owner-name']}}</span></a>{{/if}}
					</h4>
					<div class="additional-info text-muted">
						<div class="wall-item-ago" style="margin-left:0;">
							<small> 
							<span class="fa fa-clock"></span><time class="dt-scheduled" datetime="">{{$scheduled_at}} <span class="datetime">{{$row.scheduled_at}}</span></time>
						{{** lockview() function will not work because post gets ID after going live **}}
						{{if $row.item.private}}
							<span class="navicon lock" title="{{$row.item.lock}}" data-toggle="tooltip">
								<small>
									<i class="fa fa-lock"></i>
								</small>
							</span>
						{{/if}}
							</small>
						</div>
						{{if $row.item.location}}
						<div id="wall-item-location-{{$row.item['uri-id']}}" class="wall-item-location">
							<small><span class="location">{{$row.item.location}}</span></small>
						</div>
						{{/if}}
					</div>
				</div>
			</div>
			<div class="clearfix"></div>
			<hr>
			<div class="wall-item-content post" id="wall-item-content-{{$row.item['uri-id']}}">
				{{if $row.item.title}}
				<span id="wall-item-title-{{$row.item['uri-id']}}" class="wall-item-title">
					<h4 class="media-heading" dir="auto">{{$row.item.title}}</h4><br>
				</span>
				{{/if}}
				<div class="wall-item-body e-content {{if !$row.item.title}}p-name{{/if}}" dir="auto">{{$row.item['rendered-html'] nofilter}}</div>
			</div>
			<div class="wall-item-bottom">
				<div class="wall-item-links"></div>
				<div class="wall-item-tags">
					{{foreach $row.item.hashtags as $tag}}
						<span class="tag label btn-info sm">{{$tag nofilter}} <i class="fa fa-bolt" aria-hidden="true"></i></span>
					{{/foreach}}
					{{foreach $row.item.mentions as $tag}}
						<span class="mention label btn-warning sm">{{$tag nofilter}} <i class="fa fa-user" aria-hidden="true"></i></span>
					{{/foreach}}
					{{foreach $row.item.implicit_mentions as $tag}}
						<span class="mention label label-default sm">{{$tag nofilter}} <i class="fa fa-eye-slash" aria-hidden="true"></i></span>
					{{/foreach}}
				</div>
			</div>
			<div class="wall-item-actions" style="display:block;width:100%;">
						<div class="wall-item-actions-tools">
							<span class="wall-item-actions-right">
								<form action="{{$basefurl}}/profile/{{$nickname}}/schedule" method="post" style="width:100%;">
									<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
									<button type="submit" name="delete" value="{{$row.id}}" title="{{$delete}}" class="btn-link navicon fa fa-trash" style="float:right;border:none!important;background:transparent!important;box-shadow:none;"></button>
								</form>
							</span>						
						</div>
					</div>
		</div>
	</div>
</div>
{{/foreach}}

<script>
	function closeAside(e,el){
		var $this = $(el), href;
		var target = $this.attr('data-aside');
		var $canvas = $(target);
		// only toggle aside if in mobile UI
		if ( $(window).width() < 990){ 
			var data = $canvas.data('bs.offcanvas');
			var option = data ? 'close' : $this.data();
			e.stopPropagation();
			if (data) {
				data.toggle();
			} else {
				$canvas.offcanvas(option);
			}
		}
	}
	// build sidebar table of contents of scheduled posts
	var posts = document.querySelectorAll('.tread-wrapper');
	var toc_html  = '<div class="widget"><h3>{{$title}}</h3><ul role="menu">';
	for (var p=0; p < posts.length; p++){
		toc_html += '<li class="menuitem"><a href="{{$baseurl}}/profile/{{$nickname}}/schedule#'+posts[p].id+'" onclick="closeAside(event,this);" data-close="offcanvas" data-aside="aside">'+posts[p].querySelector('.datetime').innerText+'</a></li>';
	}
	toc_html += '</ul></div>';
	var aside = document.getElementsByTagName('aside');
	if (aside.length > 0){
		aside[0].innerHTML = toc_html;
	}
</script>
