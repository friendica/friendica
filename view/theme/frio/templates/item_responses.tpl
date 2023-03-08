{{* 
This is a first try to update responses with jquery and show changed users too
author: jakob
*}}
{{if $item.responses}}
	<div class="wall-item-responses">
		{{foreach $item.responses as $verb=>$response}}
		<div class="wall-item-{{$verb}}" id="wall-item-{{$verb}}-{{$item.id}}">{{$response.output nofilter}}</div>
		{{/foreach}}
	</div>
{{/if}}

