
<div class="intro-approve-as-friend-desc">
  <p>{{$approve_as1}}</p>
  <p>{{$approve_as2}}</p>
  <p>{{$approve_as3}}</p>
</div>

<div class="intro-approve-as-friend-wrapper">
	<label class="intro-approve-as-friend-label" for="intro-approve-as-friend-{{$intro_id}}">{{$as_friend}}</label>
	<input type="radio" name="duplex" id="intro-approve-as-friend-{{$intro_id}}" class="intro-approve-as-friend" {{$friend_selected}} value="1" />
	<div class="intro-approve-friend-break" ></div>	
</div>
<div class="intro-approve-as-friend-end"></div>
<div class="intro-approve-as-fan-wrapper">
	<label class="intro-approve-as-fan-label" for="intro-approve-as-fan-{{$intro_id}}">{{$as_fan}}</label>
	<input type="radio" name="duplex" id="intro-approve-as-fan-{{$intro_id}}" class="intro-approve-as-fan" {{$fan_selected}} value="0"  />
	<div class="intro-approve-fan-break"></div>
</div>
<div class="intro-approve-as-end"></div>
