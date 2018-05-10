<div class="generic-page-wrapper">
	{{* include the title template for the settings title *}}
	{{include file="section_title.tpl" title=$title }}


	<form action="settings/oauth" method="post" autocomplete="off">
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

		<div id="profile-edit-links">
			<ul>
				{{*
				I commented this out. Initially I wanted to to load the oauth/add into a modal dialog but settings.php
				does need $a->argv[2] === 'add' to work and argv[2] isn't available if you load a modal
				I leave it at this place as reminder that we need an other solution in settings.php 

				<li role="menuitem">
					<a id="profile-edit-view-link" onclick="addToModal('{{$baseurl}}/settings/oauth/add')">{{$add}}</a>
				</li>
				*}}

				<li role="menuitem">
					<a id="profile-edit-view-link" href="{{$baseurl}}/settings/oauth/add">{{$add}}</a>
				</li>
			</ul>
		</div>

		{{foreach $apps as $app}}
		<div class='oauthapp'>
			<img src='{{$app.icon}}' class="{{if $app.icon}} {{else}}noicon{{/if}}">
			{{if $app.name}}<h4>{{$app.name}}</h4>{{else}}<h4>{{$noname}}</h4>{{/if}}
			{{if $app.my}}
				{{if $app.oauth_token}}
				<div class="settings-submit-wrapper" ><button class="settings-submit"  type="submit" name="remove" value="{{$app.oauth_token}}">{{$remove}}</button></div>
				{{/if}}
			{{/if}}
			{{if $app.my}}
			<a href="{{$baseurl}}/settings/oauth/edit/{{$app.client_id}}" class="btn" title="{{$edit|escape:'html'}}"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>&nbsp;</a>
			<a href="{{$baseurl}}/settings/oauth/delete/{{$app.client_id}}?t={{$form_security_token}}" class="btn" title="{{$delete|escape:'html'}}"><i class="fa fa-trash" aria-hidden="true"></i></a>
			{{/if}}
		</div>
		{{/foreach}}

	</form>
</div>
