
<p id="dfrn-request-homecoming" >
{{$welcome}}
<br />
{{$please}}

</p>
<form id="dfrn-request-homecoming-form" action="dfrn_request/{{$nickname}}" method="post"> 
<input type="hidden" name="dfrn_url" value="{{$dfrn_url}}" />
<input type="hidden" name="confirm_key" value="{{$confirm_key}}" />
<input type="hidden" name="localconfirm" value="1" />
{{$aes_allow}}

<label id="dfrn-request-homecoming-hide-label" for="dfrn-request-homecoming-hide">{{$hidethem}}</label>
<input type="checkbox" name="hidden-contact" value="1" {{if $hidechecked}}checked="checked" {{/if}} />


<div id="dfrn-request-homecoming-submit-wrapper" >
<input id="dfrn-request-homecoming-submit" type="submit" name="submit" value="{{$submit}}" />
</div>
</form>