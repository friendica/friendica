
<photo>
	<id>{{$photo.id}}</id>
	<created>{{$photo.created}}</created>
	<edited>{{$photo.edited}}</edited>
	<title>{{$photo.title}}</title>
	<desc>{{$photo.desc}}</desc>
	<album>{{$photo.album}}</album>
	<filename>{{$photo.filename}}</filename>
	<type>{{$photo.type}}</type>
	<height>{{$photo.height}}</height>
	<width>{{$photo.width}}</width>
	<datasize>{{$photo.datasize}}</datasize>
	<profile>1</profile>
	<links type="array">{{foreach $photo.link as $scale => $url}}
		<link type="{{$photo.type}}" scale="{{$scale}}" href="{{$url}}" />
	{{/foreach}}</links>
	{{if $photo.data}}
	<data encode="base64">{{$photo.data}}</data>
	{{/if}}
</photo>
