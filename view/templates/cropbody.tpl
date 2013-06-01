<h1>{{$title}}</h1>
<p id="cropimage-desc">
{{$desc}}
</p>
<div id="cropimage-wrapper">
<img src="{{$image_url}}" id="croppa" class="imgCrop" alt="{{$title}}" />
</div>
<div id="cropimage-preview-wrapper" >
<div id="previewWrap" ></div>
</div>

<script type="text/javascript" language="javascript">

	function onEndCrop( coords, dimensions ) {
		$PR( 'x1' ).value = coords.x1;
		$PR( 'y1' ).value = coords.y1;
		$PR( 'x2' ).value = coords.x2;
		$PR( 'y2' ).value = coords.y2;
		$PR( 'width' ).value = dimensions.width;
		$PR( 'height' ).value = dimensions.height;
	}

	Event.observe( window, 'load', function() {
		new Cropper.ImgWithPreview(
		'croppa',
		{
			previewWrap: 'previewWrap',
			minWidth: 175,
			minHeight: 175,
			maxWidth: 640,
			maxHeight: 640,
			ratioDim: { x: 100, y:100 },
			displayOnInit: true,
			onEndCrop: onEndCrop
		}
		);
	}
	);

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
<input type="submit" name="submit" value="{{$done}}" />
</div>

</form>
