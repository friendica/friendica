<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<!--<meta content='width=device-width, minimum-scale=1 maximum-scale=1' name='viewport'>
<meta content='True' name='HandheldFriendly'>
<meta content='320' name='MobileOptimized'>-->
<meta name="viewport" content="width=device-width; initial-scale = 1.0; maximum-scale=1.0; user-scalable=no" />
<!--<meta name="viewport" content="width=100%;  initial-scale=1; maximum-scale=1; minimum-scale=1; user-scalable=no;" />-->

<base href="$baseurl/" />
<meta name="generator" content="$generator" />
<!--<link rel="stylesheet" href="$baseurl/library/fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />
<link rel="stylesheet" href="$baseurl/library/tiptip/tipTip.css" type="text/css" media="screen" />-->
<link rel="stylesheet" href="$baseurl/library/jgrowl/jquery.jgrowl.css" type="text/css" media="screen" />

<link rel="stylesheet" type="text/css" href="$stylesheet" media="all" />

<link rel="shortcut icon" href="$baseurl/images/friendica-32.png" />
<link rel="search"
         href="$baseurl/opensearch" 
         type="application/opensearchdescription+xml" 
         title="Search in Friendica" />

<script>
	window.delItem = "$delitem";
	window.commentEmptyText = "$comment";
	window.showMore = "$showmore";
	window.showFewer = "$showfewer";
	var updateInterval = $update_interval;
	var localUser = {{ if $local_user }}$local_user{{ else }}false{{ endif }};
</script>
<script type="text/javascript" src="$baseurl/js/jquery.js" ></script>
<script type="text/javascript">var $j = jQuery.noConflict();</script>
<script type="text/javascript" src="$baseurl/view/theme/frost-mobile/js/main.min.js" ></script>

