<html>
	<head>
		<title>{{$title}}</title>
	</head>
	<body>
		<h1>{{$title}}</h1>
		<p>{{$message nofilter}}</p>
	{{if $trace}}
		<pre>{{$trace nofilter}}</pre>
	{{/if}}
	</body>
</html>
