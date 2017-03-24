<div class="pager">
	{{if $pager}}
	{{if $pager.prev}}<span class="pager_prev {{$pager.prev.class}}"><a href="{{$pager.prev.url}}">{{$pager.prev.text}}</a></span>{{/if}}

	{{if $pager.first}}<span class="pager_first {{$pager.first.class}}"><a href="{{$pager.first.url}}">{{$pager.first.text}}</a></span>{{/if}}

	{{foreach $pager.pages as $p}}<span class="pager_{{$p.class}}"><a href="{{$p.url}}">{{$p.text}}</a></span>{{/foreach}}

	{{if $pager.last}}&nbsp;<span class="pager_last {{$pager.last.class}}"><a href="{{$pager.last.url}}">{{$pager.last.text}}</a></span>{{/if}}

	{{if $pager.next}}<span class="pager_next {{$pager.next.class}}"><a href="{{$pager.next.url}}">{{$pager.next.text}}</a></span>{{/if}}
	{{/if}}
</div>
