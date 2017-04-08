
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<base href="{{$baseurl}}/" />
<meta name="generator" content="{{$generator}}" />
<link rel="stylesheet" href="view/global.css" type="text/css" media="all" />
<link rel="stylesheet" href="library/colorbox/colorbox.css" type="text/css" media="screen" />
<link rel="stylesheet" href="library/jgrowl/jquery.jgrowl.css" type="text/css" media="screen" />
<link rel="stylesheet" href="library/datetimepicker/jquery.datetimepicker.css" type="text/css" media="screen" />
<link rel="stylesheet" href="library/perfect-scrollbar/perfect-scrollbar.min.css" type="text/css" media="screen" />
<link rel="stylesheet" href="vendor/pear-pear.php.net/Text_Highlighter/data/Text_Highlighter/Text/sample.css" type="text/css" media="screen" />

<link rel="stylesheet" type="text/css" href="{{$stylesheet}}" media="all" />

<!--
<link rel="shortcut icon" href="images/friendica-32.png" />
<link rel="apple-touch-icon" href="images/friendica-128.png"/>
-->
<link rel="shortcut icon" href="{{$shortcut_icon}}" />
<link rel="apple-touch-icon" href="{{$touch_icon}}"/>

<meta name="apple-mobile-web-app-capable" content="yes" />
<script>
// Prevents links to switch to Safari in a home screen app - see https://gist.github.com/irae/1042167
(function(a,b,c){if(c in b&&b[c]){var d,e=a.location,f=/^(a|html)$/i;a.addEventListener("click",function(a){d=a.target;while(!f.test(d.nodeName))d=d.parentNode;"href"in d&&(chref=d.href).replace(e.href,"").indexOf("#")&&(!/^[a-z\+\.\-]+:/i.test(chref)||chref.indexOf(e.protocol+"//"+e.host)===0)&&(a.preventDefault(),e.href=d.href)},!1)}})(document,window.navigator,"standalone");
</script>

<link rel="search"
         href="{{$baseurl}}/opensearch"
         type="application/opensearchdescription+xml"
         title="Search in Friendica" />

<!--[if IE]>
<script type="text/javascript" src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript" src="js/modernizr.js" ></script>
<script type="text/javascript" src="js/jquery.js" ></script>
<!-- <script type="text/javascript" src="js/jquery-migrate.js" ></script>-->
<script type="text/javascript" src="js/jquery-migrate.js" ></script>
<script type="text/javascript" src="js/jquery.textinputs.js" ></script>
<script type="text/javascript" src="library/jquery-textcomplete/jquery.textcomplete.min.js" ></script>
<script type="text/javascript" src="js/autocomplete.js" ></script>
<script type="text/javascript" src="library/colorbox/jquery.colorbox-min.js"></script>
<script type="text/javascript" src="library/jgrowl/jquery.jgrowl_minimized.js"></script>
<script type="text/javascript" src="library/datetimepicker/jquery.datetimepicker.js"></script>
<script type="text/javascript" src="library/perfect-scrollbar/perfect-scrollbar.jquery.min.js" ></script>
<script type="text/javascript" src="js/acl.js" ></script>
<script type="text/javascript" src="js/webtoolkit.base64.js" ></script>
<script type="text/javascript" src="js/main.js" ></script>
<script>

	var updateInterval = {{$update_interval}};
	var localUser = {{if $local_user}}{{$local_user}}{{else}}false{{/if}};

	{{* Create an object with the data which is needed for infinite scroll.
	For the relevant js part look at function loadContent() in main.js. *}}
	{{if $infinite_scroll}}
	var infinite_scroll = {
		"pageno"	: {{$infinite_scroll.pageno}},
		"reload_uri"	: "{{$infinite_scroll.reload_uri}}"
	}
	{{/if}}

	function confirmDelete() { return confirm("{{$delitem}}"); }
	function commentExpand(id) {
		$("#comment-edit-text-" + id).value = "";
		$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
		$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
		$("#comment-edit-text-" + id).focus();
		$("#mod-cmnt-wrap-" + id).show();
		openMenu("comment-edit-submit-wrapper-" + id);
		return true;
	}
	function commentOpen(obj,id) {
		if (obj.value == "") {
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			$("#mod-cmnt-wrap-" + id).show();
			openMenu("comment-edit-submit-wrapper-" + id);
			return true;
		}
		return false;
	}
	function commentClose(obj,id) {
		if (obj.value == "") {
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).addClass("comment-edit-text-empty");
			$("#mod-cmnt-wrap-" + id).hide();
			closeMenu("comment-edit-submit-wrapper-" + id);
			return true;
		}
		return false;
	}


	function commentInsert(obj,id) {
		var tmpStr = $("#comment-edit-text-" + id).val();
		if (tmpStr == "") {
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-edit-submit-wrapper-" + id);
		}
		var ins = $(obj).html();
		ins = ins.replace("&lt;","<");
		ins = ins.replace("&gt;",">");
		ins = ins.replace("&amp;","&");
		ins = ins.replace("&quot;","\"");
		$("#comment-edit-text-" + id).val(tmpStr + ins);
	}

	function qCommentInsert(obj,id) {
		var tmpStr = $("#comment-edit-text-" + id).val();
		if (tmpStr == "") {
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-edit-submit-wrapper-" + id);
		}
		var ins = $(obj).val();
		ins = ins.replace("&lt;","<");
		ins = ins.replace("&gt;",">");
		ins = ins.replace("&amp;","&");
		ins = ins.replace("&quot;","\"");
		$("#comment-edit-text-" + id).val(tmpStr + ins);
		$(obj).val("");
	}

	window.showMore = "{{$showmore}}";
	window.showFewer = "{{$showfewer}}";

	function showHideCommentBox(id) {
		if ($("#comment-edit-form-" + id).is(":visible")) {
			$("#comment-edit-form-" + id).hide();
		} else {
			$("#comment-edit-form-" + id).show();
		}
	}


</script>


