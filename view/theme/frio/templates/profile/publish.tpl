
<div id="profile-publish-wrapper">
	<h5 id="profile-publish-desc-{{$instance}}">
	{{$pubdesc}}
	</h5>

	<div id="profile-publish-yes-wrapper-{{$instance}}" class="field radio">
		<div class="radio">
			<input type="radio" name="profile_publish_{{$instance}}" id="profile-publish-yes-{{$instance}}" {{$yes_selected}} value="1"/>
			<label id="profile-publish-yes-label-{{$instance}}" for="profile-publish-yes-{{$instance}}">{{$str_yes}}</label>
		</div>
		<div id="profile-publish-break-{{$instance}}"></div>
	</div>

	<div id="profile-publish-no-wrapper-{{$instance}}" class="field radio">
		<div class="radio">
			<input type="radio" name="profile_publish_{{$instance}}" id="profile-publish-no-{{$instance}}" {{$no_selected}} value="0"/>
			<label id="profile-publish-no-label-{{$instance}}" for="profile-publish-no-{{$instance}}">{{$str_no}}</label>
		</div>

		<div id="profile-publish-end-{{$instance}}"></div>
	</div>
</div>
