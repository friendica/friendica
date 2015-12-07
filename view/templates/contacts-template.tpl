
<h2>{{$header}}{{if $total}} ({{$total}}){{/if}}</h2>

{{if $finding}}<h4>{{$finding}}</h4>{{/if}}

<div id="contacts-search-wrapper">
<form id="contacts-search-form" action="{{$cmd}}" method="get" >
<span class="contacts-search-desc">{{$desc}}</span>
<input type="text" name="search" id="contacts-search" class="search-input" onfocus="this.select();" value="{{$search|escape:'html'}}" />
<input type="submit" name="submit" id="contacts-search-submit" value="{{$submit|escape:'html'}}" />
</form>
</div>
<div id="contacts-search-end"></div>

{{$tabs}}

<form action="{{$baseurl}}/contacts/batch/" method="POST">
{{foreach $contacts as $contact}}
	{{include file="contact_template.tpl"}}
{{/foreach}}
<div id="contact-edit-end"></div>
<div id="contacts-actions">
{{foreach $batch_actions as $n=>$l}}
 <input class="batch-action" name="{{$n}}" value="{{$l|escape:'html'}}" type="submit">
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
 
  // add javascript confirm dialog to "drop" links. Plain html url have "?confirm=1" to show confirmation form, we need to remove it
  $(".drop").each(function() {
   $(this).attr('href', $(this).attr('href').replace("confirm=1","") );
   $(this).click(function(e){
    if (confirm("{{$contact_drop_confirm}}")) {
     return true;
    } else {
     e.preventDefault();
     return false;
    }
   });
   
  });
 });
 </script>

{{$paginate}}




