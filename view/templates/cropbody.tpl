<h1>{{$title}}</h1>
<p id="cropimage-desc">
	{{$desc}}
</p>

<div id="cropimage-wrapper">
	<img src="{{$image_url}}" id="croppa" class="imgCrop" alt="{{$title}}" />
</div>

<div id="cropimage-preview-wrapper" >
	<div id="previewWrap" class="crop-preview"></div>
</div>

<script type="text/javascript" language="javascript">

	var image = document.getElementById('croppa');
	var cropper = new Cropper(image, {
		aspectRatio: 1 / 1,
		viewMode: 1,
		preview: '#profile-photo-wrapper, .crop-preview',
		crop: function(e) {
			$( '#x1' ).val(e.detail.x);
			$( '#y1' ).val(e.detail.y);
			$( '#x2' ).val(e.detail.x + e.detail.width);
			$( '#y2' ).val(e.detail.y + e.detail.height);
			$( '#width' ).val(e.detail.scaleX);
			$( '#height' ).val(e.detail.scaleY);
		},
		ready: function() {
			// Add the "crop-preview" class to the preview element ("profile-photo-wrapper").
			var cwrapper = document.getElementById("profile-photo-wrapper");
			cwrapper.classList.add("crop-preview");
		}
	});

</script>

<form action="profile_photo/{{$resource}}" id="crop-image-form" method="post" />
	<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

	<input type='hidden' name='profile' value='{{$profile}}'>
	<input type="hidden" name="cropfinal" value="1" />
	<input type="hidden" name="xstart" id="x1" />
	<input type="hidden" name="ystart" id="y1" />
	<input type="hidden" name="xfinal" id="x2" />
	<input type="hidden" name="yfinal" id="y2" />
	<input type="hidden" name="height" id="height" />
	<input type="hidden" name="width"  id="width" />

	<div id="crop-image-submit-wrapper" >
		<input type="submit" name="submit" value="{{$done|escape:'html'}}" />
	</div>

</form>
