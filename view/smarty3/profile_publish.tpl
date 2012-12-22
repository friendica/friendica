<p id="profile-publish-desc-{{$instance}}">
{{$pubdesc}}
</p>

		<div id="profile-publish-yes-wrapper-{{$instance}}">
		<label id="profile-publish-yes-label-{{$instance}}" for="profile-publish-yes-{{$instance}}">{{$str_yes}}</label>
		<input type="radio" name="profile_publish_{{$instance}}" id="profile-publish-yes-{{$instance}}" {{$yes_selected}} value="1" />

		<div id="profile-publish-break-{{$instance}}" ></div>	
		</div>
		<div id="profile-publish-no-wrapper-{{$instance}}">
		<label id="profile-publish-no-label-{{$instance}}" for="profile-publish-no-{{$instance}}">{{$str_no}}</label>
		<input type="radio" name="profile_publish_{{$instance}}" id="profile-publish-no-{{$instance}}" {{$no_selected}} value="0"  />

		<div id="profile-publish-end-{{$instance}}"></div>
		</div>
