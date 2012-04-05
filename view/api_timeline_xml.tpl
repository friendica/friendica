<statuses type="array">
  {{ for $statuses as $status }}
  <status>
    <created_at>$status.created_at</created_at>
    <id>$status.id</id>
    <text>$status.text</text>
	<statusnet_html>$status.statusnet_html</statusnet_html>
    <source>$status.source</source>
    <truncated>$status.truncated</truncated>
    <url>$status.url</url>
    <in_reply_to_status_id>$status.in_reply_to_status_id</in_reply_to_status_id>
    <in_reply_to_user_id>$status.in_reply_to_user_id</in_reply_to_user_id>
    <favorited>$status.favorited</favorited>
    <in_reply_to_screen_name>$status.in_reply_to_screen_name</in_reply_to_screen_name>
    <geo>$status.geo</geo>
    <coordinates>$status.coordinates</coordinates>
    <place>$status.place</place>
    <contributors>$status.contributors</contributors>
  	{{ inc api_user_xml.tpl with $user=$status.user }}{{ endinc }}
  </status>
  {{ endfor }}
</statuses>