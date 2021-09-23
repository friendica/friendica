
<h2>{{$header}}{{if $total}} ({{$total}}){{/if}}</h2>

{{if $finding}}<h4>{{$finding}}</h4>{{/if}}

<div id="contacts-search-wrapper">
<form id="contacts-search-form" action="{{$cmd}}" method="get">
<span class="contacts-search-desc">{{$desc nofilter}}</span>
<input type="text" name="search" id="contacts-search" class="search-input" onfocus="this.select();" value="{{$search}}" />
<input type="submit" name="submit" id="contacts-search-submit" value="{{$submit}}" />
</form>
</div>
<div id="contacts-search-end"></div>

{{$tabs nofilter}}

<form action="{{$baseurl}}/contact/batch/" method="POST">
	<input type="hidden" name="redirect_url" value="{{$cmd}}" />
	<input type="hidden" name="form_security_token" value="{{$form_security_token}}" />
{{foreach $contacts as $contact}}
	{{include file="contact_template.tpl"}}
{{/foreach}}
<div id="contact-edit-end"></div>
<div id="contacts-actions">
{{foreach $batch_actions as $n=>$l}}
 <input class="batch-action" name="{{$n}}" value="{{$l}}" type="submit">
 {{/foreach}}
 </div>
</form>
<script>
 $(document).ready(function() {
  // javascript dialog to batch actions
  $(".batch-action").click(function(e){
    if (confirm($(this).attr('value')+" ?")) {
     return true;
    } else {
     e.preventDefault();
     return false;
    }
  });
 });
 </script>

{{$paginate nofilter}}




