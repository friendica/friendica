<h3>{{$header}}</h3>

<div id="delegate-desc" class="delegate-desc">{{$desc}}</div>

{{if $managers}}
<h3>{{$head_managers}}</h3>

{{foreach $managers as $x}}

<div class="contact-block-div">
<a class="contact-block-link" href="#" >
<img class="contact-block-img" src="{{$base}}/photo/thumb/{{$x.uid}}" title="{{$x.username}} ({{$x.nickname}})" />
</a>
</div>

{{/foreach}}
<div class="clear"></div>
<hr />
{{/if}}


<h3>{{$head_delegates}}</h3>

{{if $delegates}}
{{foreach $delegates as $x}}

<div class="contact-block-div">
<a class="contact-block-link" href="{{$base}}/delegate/remove/{{$x.uid}}" >
<img class="contact-block-img" src="{{$base}}/photo/thumb/{{$x.uid}}" title="{{$x.username}} ({{$x.nickname}})" />
</a>
</div>

{{/foreach}}
<div class="clear"></div>
{{else}}
{{$none}}
{{/if}}
<hr />


<h3>{{$head_potentials}}</h3>
{{if $potentials}}
{{foreach $potentials as $x}}

<div class="contact-block-div">
<a class="contact-block-link" href="{{$base}}/delegate/add/{{$x.uid}}" >
<img class="contact-block-img" src="{{$base}}/photo/thumb/{{$x.uid}}" title="{{$x.username}} ({{$x.nickname}})" />
</a>
</div>

{{/foreach}}
<div class="clear"></div>
{{else}}
{{$none}}
{{/if}}
<hr />

