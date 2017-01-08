
<div id="poke-wrapper">

	<h3 class="heading">{{$title}}</h3>
	<div id="poke-desc">{{$desc}}</div>

	<form id="poke-form" action="poke" method="get">

		<div id="poke-content-wrapper">

			{{* The input field with the recipient name*}}
			<div id="poke-recip-wrapper" class="form-group">
				<label for="poke-recip">{{$clabel}}</label>
				<input id="poke-recip" class="form-control" type="text" size="64" maxlength="255" value="{{$name|escape:'html'}}" name="pokename" autocomplete="off" />
				<input id="poke-recip-complete" type="hidden" value="{{$id}}" name="cid" />
				<input id="poke-parent" type="hidden" value="{{$parent}}" name="parent" />
			</div>

			{{* The drop-down list with different actions *}}
			<div id="poke-action-wrapper" class="form-group">
				<label for="poke-verb-select">{{$choice}}</label>
				<select name="verb" id="poke-verb-select" class="form-control">
				{{foreach $verbs as $v}}
				<option value="{{$v.0}}">{{$v.1}}</option>
				{{/foreach}}
				</select>
			</div>

			{{* The checkbox to select if the "poke message" should be private *}}
			<div id="poke-private-desc" class="checkbox">
				<input type="checkbox" id=poke-private-desc-checkbox" name="private" {{if $parent}}disabled="disabled"{{/if}} value="1" />
				<label for="poke-private-desc-checkbox">{{$prv_desc}}</label>
			</div>

		</div>

		<div id="poke-content-wrapper-end"></div>

		<div id="poke-submit-wrapper">
			<button class="btn btn-primary pull-right" type="submit" name="submit" value="{{$submit|escape:'html'}}"><i class="fa fa-slideshare"></i>&nbsp;{{$submit|escape:'html'}}</button>
		</div>

		<div id="poke-submit-wrapper-end"></div>

	</form>
	<div id="poke-wrapper-end"></div>

</div>
