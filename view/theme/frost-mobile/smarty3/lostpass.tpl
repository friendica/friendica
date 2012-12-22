<div class="lostpass-form">
<h2>{{$title}}</h2>
<br /><br /><br />

<form action="lostpass" method="post" >
<div id="login-name-wrapper" class="field input">
        <label for="login-name" id="label-login-name">{{$name}}</label><br />
        <input type="text" maxlength="60" name="login-name" id="login-name" value="" />
</div>
<div id="login-extra-end"></div>
<p id="lostpass-desc">
{{$desc}}
</p>
<br />

<div id="login-submit-wrapper" >
        <input type="submit" name="submit" id="lostpass-submit-button" value="{{$submit}}" />
</div>
<div id="login-submit-end"></div>
</form>
</div>
