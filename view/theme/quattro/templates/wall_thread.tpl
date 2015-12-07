{{if $mode == display}}
{{else}}
{{if $item.comment_firstcollapsed}}
	<div class="hide-comments-outer">
		<span id="hide-comments-total-{{$item.id}}" 
			class="hide-comments-total">{{$item.num_comments}}</span>
			<span id="hide-comments-{{$item.id}}" 
				class="hide-comments fakelink" 
				onclick="showHideComments({{$item.id}});">{{$item.hide_text}}</span>
			{{if $item.thread_level==3}} - 
			<span id="hide-thread-{{$item}}-id"
				class="fakelink"
				onclick="showThread({{$item.id}});">expand</span> /
			<span id="hide-thread-{{$item}}-id"
				class="fakelink"
				onclick="hideThread({{$item.id}});">collapse</span> thread{{/if}}
	</div>
	<div id="collapsed-comments-{{$item.id}}" class="collapsed-comments" style="display: none;">
{{/if}}
{{/if}}

{{if $item.thread_level!=1}}<div class="children">{{/if}}

<div class="wall-item-decor">
	{{if $item.star}}<span class="icon s22 star {{$item.isstarred}}" id="starred-{{$item.id}}" title="{{$item.star.starred}}">{{$item.star.starred}}</span>{{/if}}
	{{if $item.lock}}<span class="icon s22 lock fakelink" onclick="lockview(event,{{$item.id}});" title="{{$item.lock}}">{{$item.lock}}</span>{{/if}}	
	<img id="like-rotator-{{$item.id}}" class="like-rotator" src="images/rotator.gif" alt="{{$item.wait}}" title="{{$item.wait}}" style="display: none;" />
</div>

<div class="wall-item-container {{$item.indent}} {{$item.shiny}} {{$item.network}}" id="item-{{$item.guid}}">
	<div class="wall-item-item">
		<div class="wall-item-info">
			<div class="contact-photo-wrapper mframe{{if $item.owner_url}} wwfrom{{/if}}"
				onmouseover="if (typeof t{{$item.id}} != 'undefined') clearTimeout(t{{$item.id}}); openMenu('wall-item-photo-menu-button-{{$item.id}}')" 
				onmouseout="t{{$item.id}}=setTimeout('closeMenu(\'wall-item-photo-menu-button-{{$item.id}}\'); closeMenu(\'wall-item-photo-menu-{{$item.id}}\');',200)">
				<a href="{{$item.profile_url}}" target="redir" title="{{$item.linktitle}}" class="contact-photo-link" id="wall-item-photo-link-{{$item.id}}">
					<img src="{{$item.thumb}}" class="contact-photo {{$item.sparkle}}" id="wall-item-photo-{{$item.id}}" alt="{{$item.name}}" />
				</a>
				<a href="#" rel="#wall-item-photo-menu-{{$item.id}}" class="contact-photo-menu-button icon s16 menu" id="wall-item-photo-menu-button-{{$item.id}}">menu</a>
				<ul class="contact-menu menu-popup" id="wall-item-photo-menu-{{$item.id}}">
				{{$item.item_photo_menu}}
				</ul>
				
			</div>	
			{{if $item.owner_url}}
			<div class="contact-photo-wrapper mframe wwto" id="wall-item-ownerphoto-wrapper-{{$item.id}}" >
				<a href="{{$item.owner_url}}" target="redir" title="{{$item.olinktitle}}" class="contact-photo-link" id="wall-item-ownerphoto-link-{{$item.id}}">
					<img src="{{$item.owner_photo}}" class="contact-photo {{$item.osparkle}}" id="wall-item-ownerphoto-{{$item.id}}" alt="{{$item.owner_name}}" />
				</a>
			</div>
			{{/if}}			
			<div class="wall-item-location">{{$item.location}}</div>	
		</div>
		<div class="wall-item-content">
			{{if $item.title}}<h2><a href="{{$item.plink.href}}" class="{{$item.sparkle}}">{{$item.title}}</a></h2>{{/if}}
			{{$item.body}}
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="wall-item-links">
		</div>
		<div class="wall-item-tags">
			{{foreach $item.hashtags as $tag}}
				<span class='tag'>{{$tag}}</span>
			{{/foreach}}
  			{{foreach $item.mentions as $tag}}
				<span class='mention'>{{$tag}}</span>
			{{/foreach}}
               {{foreach $item.folders as $cat}}
                    <span class='folder'>{{$cat.name}}</a>{{if $cat.removeurl}} (<a href="{{$cat.removeurl}}" title="{{$remove}}">x</a>) {{/if}} </span>
               {{/foreach}}
                {{foreach $item.categories as $cat}}
                    <span class='category'>{{$cat.name}}</a>{{if $cat.removeurl}} (<a href="{{$cat.removeurl}}" title="{{$remove}}">x</a>) {{/if}} </span>
                {{/foreach}}
		</div>
	</div>	
	<div class="wall-item-bottom">
		<div class="wall-item-links">
			{{if $item.plink}}<a class="icon s16 link{{$item.sparkle}}" title="{{$item.plink.title}}" href="{{$item.plink.href}}">{{$item.plink.title}}</a>{{/if}}
		</div>
		<div class="wall-item-actions">
			<div class="wall-item-actions-author">
				<a href="{{$item.profile_url}}" target="redir"
                                title="{{$item.linktitle}}"
                                class="wall-item-name-link"><span
                                class="wall-item-name{{$item.sparkle}}">{{$item.name}}</span></a>
                                <span class="wall-item-ago" title="{{$item.localtime}}">{{$item.ago}}</span>
				 {{if $item.owner_url}}<br/>{{$item.to}} <a href="{{$item.owner_url}}" target="redir" title="{{$item.olinktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.osparkle}}" id="wall-item-ownername-{{$item.id}}">{{$item.owner_name}}</span></a> {{$item.vwall}}
				 {{/if}}
			</div>
			
			<div class="wall-item-actions-social">
			{{if $item.star}}
				<a href="#" id="star-{{$item.id}}" onclick="dostar({{$item.id}}); return false;"  class="{{$item.star.classdo}}"  title="{{$item.star.do}}">{{$item.star.do}}</a>
				<a href="#" id="unstar-{{$item.id}}" onclick="dostar({{$item.id}}); return false;"  class="{{$item.star.classundo}}"  title="{{$item.star.undo}}">{{$item.star.undo}}</a>
			{{/if}}
			{{if $item.ignore}}
			    <a href="#" id="ignore-{{$item.id}}" onclick="doignore({{$item.id}}); return false;" class="{{$item.ignore.classdo}}" title="{{$item.ignore.do}}">{{$item.ignore.do}}</a>
			    <a href="#" id="unignore-{{$item.id}}" onclick="doignore({{$item.id}}); return false;" class="{{$item.ignore.classundo}}" title="{{$item.ignore.undo}}">{{$item.ignore.undo}}</a>
			{{/if}}
			{{if $item.tagger}}
				<a href="#" id="tagger-{{$item.id}}" onclick="itemTag({{$item.id}}); return false;" class="{{$item.tagger.class}}" title="{{$item.tagger.add}}">{{$item.tagger.add}}</a>
			{{/if}}
			{{if $item.filer}}
                                <a href="#" id="filer-{{$item.id}}" onclick="itemFiler({{$item.id}}); return false;" class="filer-item filer-icon" title="{{$item.filer}}">{{$item.filer}}</a>
			{{/if}}			
			
			{{if $item.vote}}
				<a href="#" id="like-{{$item.id}}" title="{{$item.vote.like.0}}" onclick="dolike({{$item.id}},'like'); return false">{{$item.vote.like.1}}</a>
				{{if $item.vote.dislike}}
				<a href="#" id="dislike-{{$item.id}}" title="{{$item.vote.dislike.0}}" onclick="dolike({{$item.id}},'dislike'); return false">{{$item.vote.dislike.1}}</a>
				{{/if}}
			    {{if $item.vote.share}}
				    <a href="#" id="share-{{$item.id}}" title="{{$item.vote.share.0}}" onclick="jotShare({{$item.id}}); return false">{{$item.vote.share.1}}</a>
			    {{/if}}			
			{{/if}}
			{{if $item.isevent}}
			<div class="clear"></div>
			<div class="wall-item-actions-isevent">
				<a href="#" id="attendyes-{{$item.id}}" title="{{$item.attend.0}}" onclick="dolike({{$item.id}},'attendyes'); return false;">{{$item.attend.0}}</a>
				<a href="#" id="attendno-{{$item.id}}" title="{{$item.attend.1}}" onclick="dolike({{$item.id}},'attendno'); return false;">{{$item.attend.1}}</a>
				<a href="#" id="attendmaybe-{{$item.id}}" title="{{$item.attend.2}}" onclick="dolike({{$item.id}},'attendmaybe'); return false;">{{$item.attend.2}}</a>
			</div>
			{{/if}}
						
			</div>
			
			<div class="wall-item-actions-tools">

				{{if $item.drop.pagedrop}}
					<input type="checkbox" title="{{$item.drop.select}}" name="itemselected[]" class="item-select" value="{{$item.id}}" />
				{{/if}}
				{{if $item.drop.dropping}}
					<a href="item/drop/{{$item.id}}" onclick="return confirmDelete();" class="icon delete s16" title="{{$item.drop.delete}}">{{$item.drop.delete}}</a>
				{{/if}}
				{{if $item.edpost}}
					<a class="icon edit s16" href="{{$item.edpost.0}}" title="{{$item.edpost.1}}"></a>
				{{/if}}
			</div>
			
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="wall-item-links"></div>
		{{if $item.responses}}
			{{foreach $item.responses as $verb=>$response}}
				<div class="wall-item-{{$verb}}" id="wall-item-{{$verb}}-{{$item.id}}">{{$response.output}}</div>
			{{/foreach}}
		{{/if}}
	</div>
	
	{{if $item.threaded}}{{if $item.comment}}{{if $item.indent==comment}}
	<div class="wall-item-bottom commentbox">
		<div class="wall-item-links"></div>
		<div class="wall-item-comment-wrapper">
					{{$item.comment}}
		</div>
	</div>
	{{/if}}{{/if}}{{/if}}
</div>


{{foreach $item.children as $child}}
	{{if $child.type == tag}}
		{{include file="wall_item_tag.tpl" item=$child}}
	{{else}}
		{{include file="{{$item.template}}" item=$child}}
	{{/if}}
{{/foreach}}

{{if $item.thread_level!=1}}</div>{{/if}}


{{if $mode == display}}
{{else}}
{{if $item.comment_lastcollapsed}}</div>{{/if}}
{{/if}}

{{* top thread comment box *}}
{{if $item.threaded}}{{if $item.comment}}{{if $item.thread_level==1}}
<div class="wall-item-comment-wrapper" >{{$item.comment}}</div>
{{/if}}{{/if}}{{/if}}


{{if $item.flatten}}
<div class="wall-item-comment-wrapper" >{{$item.comment}}</div>
{{/if}}
