{{if $delivery.queue_count >= -1 && $delivery.queue_count !== '' && $delivery.queue_count !== null}}
<span class="delivery">
	&bull;
	{{if $delivery.queue_count == 0}}
		<i class="icon-spinner" aria-hidden="true" title="{{$delivery.notifier_pending}}"></i>
		<span class="sr-only">{{$delivery.notifier_pending}}</span>
	{{elseif $delivery.queue_done == 0}}
		<i class="icon-spinner" aria-hidden="true" title="{{$delivery.delivery_pending}} {{$item.delivery.queue_done}}/{{$item.delivery.queue_count}}"></i>
		<span class="sr-only">{{$delivery.delivery_pending}}</span>
	{{elseif $delivery.queue_done / $delivery.queue_count < 0.75}}
		<i class="icon-plane" aria-hidden="true" title="{{$delivery.delivery_underway}} {{$item.delivery.queue_done}}/{{$item.delivery.queue_count}}"></i>
		<span class="sr-only">{{$delivery.delivery_underway}}</span>
	{{else}}
		<i class="icon-plane" aria-hidden="true" title="{{$delivery.delivery_almost}} {{$item.delivery.queue_done}}/{{$item.delivery.queue_count}}"></i>
		<span class="sr-only">{{$delivery.delivery_almost}}</span>
	{{/if}}
</span>
{{/if}}
