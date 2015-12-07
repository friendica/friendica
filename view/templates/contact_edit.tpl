

<h2>{{$header}}</h2>

<div id="contact-edit-wrapper" >

	{{$tab_str}}

	<div id="contact-edit-drop-link" >
		<a href="contacts/{{$contact_id}}/drop" class="icon drophide" id="contact-edit-drop-link" onclick="return confirmDelete();"  title="{{$delete}}" onmouseover="imgbright(this);" onmouseout="imgdull(this);"></a>
	</div>

	<div id="contact-edit-drop-link-end"></div>


	<div id="contact-edit-nav-wrapper" >
		<div id="contact-edit-links">
			<ul>
				{{if $relation_text}}
					<li><div id="contact-edit-rel">{{$relation_text}}</div></li>
				{{/if}}
				<li><div id="contact-edit-nettype">{{$nettype}}</div></li>
				<li><div id="contact-edit-profileurl">{{$profileurllabel}}: <a href="{{$url}}">{{$profileurl}}</a></div></li>
				{{if $lost_contact}}
					<li><div id="lost-contact-message">{{$lost_contact}}</div></li>
				{{/if}}
				{{if $insecure}}
					<li><div id="insecure-message">{{$insecure}}</div></li>
				{{/if}}
				{{if $blocked}}
					<li><div id="block-message">{{$blocked}}</div></li>
				{{/if}}
				{{if $ignored}}
					<li><div id="ignore-message">{{$ignored}}</div></li>
				{{/if}}
				{{if $archived}}
					<li><div id="archive-message">{{$archived}}</div></li>
				{{/if}}
			</ul>
			<ul>

				{{if $common_text}}
					<li><div id="contact-edit-common"><a href="{{$common_link}}">{{$common_text}}</a></div></li>
				{{/if}}
				{{if $all_friends}}
					<li><div id="contact-edit-allfriends"><a href="allfriends/{{$contact_id}}">{{$all_friends}}</a></div></li>
				{{/if}}


				<li><a href="network/0?nets=all&cid={{$contact_id}}" id="contact-edit-view-recent">{{$lblrecent}}</a></li>
				{{if $lblsuggest}}
					<li><a href="fsuggest/{{$contact_id}}" id="contact-edit-suggest">{{$lblsuggest}}</a></li>
				{{/if}}
				{{if $follow}}
					<li><div id="contact-edit-follow"><a href="{{$follow}}">{{$follow_text}}</a></div></li>
				{{/if}}

			</ul>
		</div>
	</div>
	<div id="contact-edit-nav-end"></div>


<form action="contacts/{{$contact_id}}" method="post" >
<input type="hidden" name="contact_id" value="{{$contact_id}}">

	<div id="contact-edit-poll-wrapper">
		{{if $poll_enabled}}
			<div id="contact-edit-last-update-text">{{$lastupdtext}} <span id="contact-edit-last-updated">{{$last_update}}</span></div>
			{{if $poll_interval}}
				<span id="contact-edit-poll-text">{{$updpub}}</span> {{$poll_interval}}
			{{/if}}
			<span id="contact-edit-update-now" class="button"><a href="contacts/{{$contact_id}}/update" >{{$udnow}}</a></span>
		{{/if}}
	</div>
	<div id="contact-edit-end" ></div>
	{{include file="field_checkbox.tpl" field=$notify}}
	{{if $fetch_further_information}}
		{{include file="field_select.tpl" field=$fetch_further_information}}
		{{if $fetch_further_information.2 == 2 }} {{include file="field_textarea.tpl" field=$ffi_keyword_blacklist}} {{/if}}
	{{/if}}
	{{include file="field_checkbox.tpl" field=$hidden}}

<div id="contact-edit-info-wrapper">
<h4>{{$lbl_info1}}</h4>
	<textarea id="contact-edit-info" rows="8" cols="60" name="info">{{$info}}</textarea>
	<input class="contact-edit-submit" type="submit" name="submit" value="{{$submit|escape:'html'}}" />
</div>
<div id="contact-edit-info-end"></div>

{{if $profile_select}}
	<div id="contact-edit-profile-select-text">
	<h4>{{$lbl_vis1}}</h4>
	<p>{{$lbl_vis2}}</p> 
	</div>
	{{$profile_select}}
	<div id="contact-edit-profile-select-end"></div>
	<input class="contact-edit-submit" type="submit" name="submit" value="{{$submit|escape:'html'}}" />
{{/if}}
</form>
</div>
