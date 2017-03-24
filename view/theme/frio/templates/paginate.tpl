
{{* Pager template, uses output of paginate_data() in include/text.php *}}

{{if $pager}}
<ul class="{{$pager.class}} pagination-sm">
	{{if $pager.first}}<li class="pager_first {{$pager.first.class}}"><a href="{{$pager.first.url}}" title="{{$pager.first.text}}">&#8739;&lt;</a></li>{{/if}}

	{{if $pager.prev}}<li class="pager_prev {{$pager.prev.class}}"><a href="{{$pager.prev.url}}" title="{{$pager.prev.text}}">&lt;</a></li>{{/if}}

	{{foreach $pager.pages as $p}}<li class="pager_{{$p.class}} hidden-xs hidden-sm"><a href="{{$p.url}}">{{$p.text}}</a></li>{{/foreach}}

	{{if $pager.next}}<li class="pager_next {{$pager.next.class}}"><a href="{{$pager.next.url}}" title="{{$pager.next.text}}">&gt;</a></li>{{/if}}

	{{if $pager.last}}<li class="pager_last {{$pager.last.class}}"><a href="{{$pager.last.url}}" title="{{$pager.last.text}}">&gt;&#8739;</a></li>{{/if}}
</ul>
{{/if}}
