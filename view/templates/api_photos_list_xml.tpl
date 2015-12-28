
<photos type="array">
{{foreach $photos as $photo}}
	<photo id="{{$photo.id}}" album="{{$photo.album}}" filename="{{$photo.filename}}" type="{{$photo.type}}">{{$photo.thumb}}</photo>
{{/foreach}}</photos>
