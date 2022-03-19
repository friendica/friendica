<li class="notification-{{if !$notify.seen}}un{{/if}}seen" onclick="location.href='{{$notify.href}}';">
	<div class="notif-entry-wrapper">
		<div class="notif-photo-wrapper"><a href="{{$notify.contact.url}}"><img data-src="{{$notify.contact.photo}}"></a></div>
		<div class="notif-desc-wrapper">
            {{$notify.richtext nofilter}}
			<div><time class="notif-when" title="{{$notify.localdate}}">{{$notify.ago}}</time></div>
		</div>
	</div>
</li>
