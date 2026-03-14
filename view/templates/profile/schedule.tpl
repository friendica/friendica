{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper scheduled-posts-wrapper">
	<h1>{{$title}}</h1>
{{foreach $schedule as $row}}
<div id="tread-wapper-{{$row.item['uri-id']}}" class="tread-wrapper h-entry {{$row.item.network}}">
	<div class="wall-item-container {{$row.item.network}} thread_level_0" id="item-{{$row.item.guid}}">
		<div class="wall-item-item">
			<div class="wall-item-info">
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
			<div class="wall-item-actions-author">
				<span class="author-wrapper">
					<a aria-haspopup="dialog" href="{{$row.item['author-link']}}" target="redir" title="{{$row.item['author-name']}}" class="userinfo click-card u-url wall-item-name-link" {{if $row.item.guid}}data-guid="true"{{/if}}><span class="wall-item-name sparkle">{{$row.item['author-name']}}</span></a>
				</span>
				<span class="owner-wrapper">
					{{if $row.item['owner-link'] && $row.item['owner-link'] != $row.item['author-link']}}{{$row.via}}<a aria-haspopup="dialog" href="{{$row.item['owner-link']}}" target="redir" title="{{$row.item['owner-name']}}" class="userinfo click-card wall-item-name-link"><span class="wall-item-name sparkle">{{$row.item['owner-name']}}</span></a>{{/if}}
				</span>
				<div class="wall-postinfo">
					<span class="wall-item-ago" style="margin-left:0;"> 
						<span class="icon icon-time"></span><time class="dt-scheduled" datetime="">{{$scheduled_at}} <span class="datetime">{{$row.scheduled_at}}</span></time>
					</span>
					{{** lockview() function will not work because post gets ID after going live **}}
					{{if $row.item.private}}<span class="icon s10 lock" title="{{$row.item.lock}}">{{$row.item.lock}}</span>{{/if}}
					{{if $row.item.location}}
					<div id="wall-item-location-{{$row.item['uri-id']}}" class="wall-item-location">
						<small><span class="location">{{$row.item.location}}</span></small>
					</div>
					{{/if}}
				</div>
				<div class="wall-item-network-end"></div>
			</div>
			<div class="clearfix"></div>
			<div itemprop="description" class="wall-item-content" id="wall-item-content-{{$row.item['uri-id']}}">
				{{if $row.item.title}}<h2 dir="auto">{{$row.item.title}}</h2>{{/if}}
				<div class="wall-item-body e-content {{if !$row.item.title}}p-name{{/if}}" dir="auto">{{$row.item['rendered-html'] nofilter}}</div>
				<div class="wall-item-bottom">
					<div class="wall-item-links"></div>
					<div class="wall-item-tags">
						{{foreach $row.item.hashtags as $tag}}
							<span class="tag hashtag">{{$tag nofilter}}</span>
						{{/foreach}}
						{{foreach $row.item.mentions as $tag}}
							<span class="tag mention">{{$tag nofilter}}</span>
						{{/foreach}}
						{{foreach $row.item.implicit_mentions as $tag}}
							<span class="tag mention">{{$tag nofilter}}</span>
						{{/foreach}}
					</div>
				</div>
				<div class="wall-item-bottom" style="display:block;width:100%;margin-top:6px;">
					<div class="wall-item-actions" style="display:block;width:100%;">
						<div class="wall-item-actions-tools">
							<span class="wall-item-actions-right">
								<form action="{{$basefurl}}/profile/{{$nickname}}/schedule" method="post">
									<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
									<button type="submit" name="delete" value="{{$row.id}}" title="{{$delete}}" class="icon-trash icon-large" style="float:right;border:none;background:transparent;"></button>
								</form>
							</span>						
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
{{/foreach}}

<script>
	function closeAside(){
		$("aside").toggleClass("show");
	}
	// build sidebar table of contents of scheduled posts
	var posts = document.querySelectorAll('.tread-wrapper');
	var toc_html  = '<div class="widget"><h3>{{$title}}</h3><ul role="menu">';
	for (var p=0; p < posts.length; p++){
		toc_html += '<li class="menuitem"><a href="{{$baseurl}}/profile/{{$nickname}}/schedule#'+posts[p].id+'" onclick="closeAside();">'+posts[p].querySelector('.datetime').innerText+'</a></li>';
	}
	toc_html += '</ul></div>';
	var aside = document.getElementsByTagName('aside');
	if (aside.length > 0){
		aside[0].innerHTML = toc_html;
	}
</script>
