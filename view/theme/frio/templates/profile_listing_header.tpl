
<div class="generic-page-wrapper">
	<div class="section-title-wrapper pull-left">
		<h2>{{$header}}</h2>
	</div>
	<div id="profile-listing-new-link-wrapper" class="pull-right" >
		<a href="{{$cr_new_link}}" id="profile-listing-new-link" class="page-action faded-icon" title="{{$cr_new}}" data-toggle="tooltip">
		<i class="fa fa-plus"></i></a>
	</div>

	<div class="section-content-wrapper">
		<div id="profile-listing-profiles" class="profile-listing-table">
			{{$profiles}}
		</div>
	</div>
</div>
