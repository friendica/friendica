<li class="notification-{{if !$notify.seen}}un{{/if}}seen notif-entry">
	<div class="notif-entry-wrapper media">
		<div class="notif-photo-wrapper media-object pull-left" aria-hidden="true">
			<a href="{{$notify.contact.url}}" class="userinfo click-card" tabIndex="-1"><img data-src="{{$notify.contact.photo}}" alt=""></a>
		</div>
		<a href="{{$notify.href}}" class="notif-desc-wrapper media-body">
            {{$notify.richtext nofilter}}
			<div><time class="notif-when time" data-toggle="tooltip" title="{{$notify.localdate}}">{{$notify.ago}}</time></div>
		</a>
	</div>
</li>
