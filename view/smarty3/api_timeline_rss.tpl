<rss xmlns:atom="http://www.w3.org/2005/Atom" version="2.0" xmlns:georss="http://www.georss.org/georss" xmlns:twitter="http://api.twitter.com">
  <channel>
    <title>Friendica</title>
    <link>{{$rss.alternate}}</link>
    <atom:link type="application/rss+xml" rel="self" href="{{$rss.self}}"/>
    <description>Friendica timeline</description>
    <language>{{$rss.language}}</language>
    <ttl>40</ttl>
	<image>
		<link>{{$user.link}}</link>
		<title>{{$user.name}}'s items</title>
		<url>{{$user.profile_image_url}}</url>
	</image>
	
{{foreach $statuses as $status}}
  <item>
    <title>{{$status.user.name}}: {{$status.text}}</title>
    <description>{{$status.text}}</description>
    <pubDate>{{$status.created_at}}</pubDate>
    <guid>{{$status.url}}</guid>
    <link>{{$status.url}}</link>
    <twitter:source>{{$status.source}}</twitter:source>
  </item>
{{/foreach}}
  </channel>
</rss>
