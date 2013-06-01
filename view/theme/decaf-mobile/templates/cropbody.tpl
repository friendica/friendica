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

<form action="profile_photo/{{$resource}}" id="crop-image-form" method="post" />
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

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
