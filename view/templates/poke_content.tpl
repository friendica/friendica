
<h3>{{$title}}</h3>

<div id="poke-desc">{{$desc}}</div>


<div id="poke-wrapper">
	<form action="poke" method="get">

	<div id="poke-recipient">
		<div id="poke-recip-label">{{$clabel}}</div>
		<input id="poke-recip" type="text" size="64" maxlength="255" value="{{$name|escape:'html'}}" name="pokename" autocomplete="off" />
		<input id="poke-recip-complete" type="hidden" value="{{$id}}" name="cid" />
		<input id="poke-parent" type="hidden" value="{{$parent}}" name="parent" />
	</div>

	<div id="poke-action">
		<div id="poke-action-label">{{$choice}}</div>
		<select name="verb" id="poke-verb-select" >
		{{foreach $verbs as $v}}
		<option value="{{$v.0}}">{{$v.1}}</option>
		{{/foreach}}
		</select>
	</div>

	<div id="poke-privacy-settings">
		<div id="poke-private-desc">{{$prv_desc}}</div>
		<input type="checkbox" name="private" {{if $parent}}disabled="disabled"{{/if}} value="1" />
	</div>

	<input type="submit" name="submit" value="{{$submit|escape:'html'}}" />

	</form>
</div>

