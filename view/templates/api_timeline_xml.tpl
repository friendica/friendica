
<statuses type="array"
	xmlns:statusnet="http://status.net/schema/api/1/"
	xmlns:friendica="http://friendi.ca/schema/api/1/">
{{foreach $statuses as $status}} <status>
  <text>{{$status.text}}</text>
  <truncated>{{$status.truncated}}</truncated>
  <created_at>{{$status.created_at}}</created_at>
  <in_reply_to_status_id>{{$status.in_reply_to_status_id}}</in_reply_to_status_id>
  <source>{{$status.source}}</source>
  <id>{{$status.id}}</id>
  <in_reply_to_user_id>{{$status.in_reply_to_user_id}}</in_reply_to_user_id>
  <in_reply_to_screen_name>{{$status.in_reply_to_screen_name}}</in_reply_to_screen_name>
  <geo>{{$status.geo}}</geo>
  <favorited>{{$status.favorited}}</favorited>
{{include file="api_user_xml.tpl" user=$status.user}}  <statusnet:html>{{$status.statusnet_html}}</statusnet:html>
  <statusnet:conversation_id>{{$status.statusnet_conversation_id}}</statusnet:conversation_id>
  <url>{{$status.url}}</url>
  <coordinates>{{$status.coordinates}}</coordinates>
  <place>{{$status.place}}</place>
  <contributors>{{$status.contributors}}</contributors>
  <friendica:activities>{{foreach $status.friendica_activities as $k=>$v}}
    <friendica:{{$k}}>{{$v}}</friendica:{{$k}}>
  {{/foreach}}</friendica:activities>
 </status>
{{/foreach}}</statuses>
