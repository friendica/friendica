<h1>{{$title}}</h1>

<div class='oauthapp'>
	<img src='{{$app.icon}}'>
	<h4>{{$app.name}}</h4>
</div>
<h3>{{$authorize}}</h3>
<form method="POST">
<div class="settings-submit-wrapper"><input  class="settings-submit"  type="submit" name="oauth_yes" value="{{$yes}}" /></div>
</form>
