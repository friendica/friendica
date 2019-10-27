<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>
	<p id="cropimage-desc">
		{{$desc nofilter}}
	</p>

	<div id="cropimage-wrapper">
		<p><img src="{{$image_url}}" id="croppa" class="imgCrop" alt="{{$title}}"></p>
	</div>

	<div id="cropimage-preview-wrapper" >
		<div id="previewWrap" class="crop-preview"></div>
	</div>

	<form action="settings/profile/photo/crop/{{$resource}}" id="crop-image-form" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<input type="hidden" name="xstart" id="x1" />
		<input type="hidden" name="ystart" id="y1" />
		<input type="hidden" name="height" id="height" />
		<input type="hidden" name="width"  id="width" />

		<div id="settings-profile-photo-crop-submit-wrapper" class="pull-right settings-submit-wrapper">
		{{if $skip}}
			<button type="submit" name="action" id="settings-profile-photo-crop-skip" class="btn" value="skip">{{$skip}}</button>
        {{/if}}
			<button type="submit" name="action" id="settings-profile-photo-crop-submit" class="btn btn-primary" value="crop">{{$crop}}</button>
		</div>

		<div class="clear"></div>
	</form>

	<script type="text/javascript" language="javascript">

		var image = document.getElementById('croppa');
		var cropper = new Cropper(image, {
			aspectRatio: 1,
			viewMode: 1,
			preview: '#profile-photo-wrapper, .crop-preview',
			crop: function(e) {
				$('#x1').val(e.detail.x);
				$('#y1').val(e.detail.y);
				$('#width').val(e.detail.width);
				$('#height').val(e.detail.height);
			},
		});

		var skip_button = document.getElementById('settings-profile-photo-crop-skip');

		skip_button.addEventListener('click', function() {
			let image_data = cropper.getImageData();
			cropper.setData({x: 0, y: 0, width: image_data.width, height: image_data.height});
		})
	</script>
</div>
