<h1>{{$title}}</h1>

{{$tostext nofilter}}

{{if $rules}}
    <h2>{{$rulestitle}}</h2>
    {{$rules nofilter}}
{{/if}}

{{if $displayprivstatement}}
<h2>{{$privstatementtitle nofilter}}</h2>
<p>{{$privacy_operate nofilter}}</p>
<p>{{$privacy_distribute nofilter}}</p>
<p>{{$privacy_delete nofilter}}</p>
{{/if}}

