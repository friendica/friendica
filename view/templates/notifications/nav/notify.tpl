<li class="notification-{{if !$notify.seen}}un{{/if}}seen">
	<a href="{{$notify.href}}" title="{{$notify.localdate}}"><img data-src="{{$notify.contact.photo}}" height="24" width="24" alt="" />{{$notify.richtext nofilter}} <span class="notif-when">{{$notify.ago}}</span></a>
</li>
