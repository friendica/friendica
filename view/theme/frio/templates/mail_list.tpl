
<li>
	<div class="mail-list-outside-wrapper">

		<div class="media">
			<div class="pull-left contact-photo-wrapper">
				<a href="{{$from_url}}" title="{{$from_addr}}">
					<img class="media-object" src="{{$from_photo}}" alt="{{$from_name}}" title="{{$from_addr}}" />
				</a>
			</div>
			<div class="media-body">
				<div class="text-muted time ago pull-right" title="{{$date}}">{{$ago}}</div>

				<h4 class="media-heading">
					{{if !$seen}}
						<strong>
					{{/if}}
						<a href="message/{{$id}}">{{$from_name}}</a>
					{{if !$seen}}
						</strong>
					{{/if}}
				</h4>
				<div class="mail-list-subject">
					<a href="message/{{$id}}">
					{{if !$seen}}
						<strong>
					{{/if}}
						{{$subject}}
					{{if !$seen}}
						</strong>
					{{/if}}
					</a>
				</div>
				<a href="message/dropconv/{{$id}}" onclick="return confirmDelete();"  title="{{$delete}}" class="pull-right" onmouseover="imgbright(this);" onmouseout="imgdull(this);">
				<i class="faded-icon fa fa-trash"></i>
				</a>
				<p class="text-muted">{{$count}}</p>
			</div>

		</div>



	</div>
	<div class="mail-list-delete-end"></div>

	<div class="mail-list-outside-wrapper-end"></div>

</li>
