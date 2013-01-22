<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<base href="$baseurl/" />
<meta name="generator" content="$generator" />
<link rel="stylesheet" href="$baseurl/library/fancybox/jquery.fancybox.css" type="text/css" media="screen" />
<link rel="stylesheet" href="$baseurl/library/jgrowl/jquery.jgrowl.css" type="text/css" media="screen" />

<link rel="stylesheet" type="text/css" href="$stylesheet" media="all" />

<link rel="shortcut icon" href="$baseurl/images/friendica-32.png" />

<link rel="apple-touch-icon" href="$baseurl/images/friendica-128.png"/>
<meta name="apple-mobile-web-app-capable" content="yes" /> 


<link rel="search"
         href="$baseurl/opensearch" 
         type="application/opensearchdescription+xml" 
         title="Search in Friendica" />

<!--[if IE]>
<script type="text/javascript" src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript" src="$baseurl/js/jquery.js" ></script>
<script type="text/javascript" src="$baseurl/js/jquery.textinputs.js" ></script>
<script type="text/javascript" src="$baseurl/js/fk.autocomplete.js" ></script>
<script type="text/javascript" src="$baseurl/library/fancybox/jquery.fancybox.pack.js"></script>
<script type="text/javascript" src="$baseurl/library/jgrowl/jquery.jgrowl_minimized.js"></script>
<script type="text/javascript" src="$baseurl/library/tinymce/jscripts/tiny_mce/tiny_mce_src.js" ></script>
<script type="text/javascript" src="$baseurl/js/acl.js" ></script>
<script type="text/javascript" src="$baseurl/js/webtoolkit.base64.js" ></script>
<script type="text/javascript" src="$baseurl/js/main.js" ></script>
<script>

	var updateInterval = $update_interval;
	var localUser = {{ if $local_user }}$local_user{{ else }}false{{ endif }};

	function confirmDelete() { return confirm("$delitem"); }
	function commentOpen(obj,id) {
		if(obj.value == '$comment') {
			obj.value = '';
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			$("#mod-cmnt-wrap-" + id).show();
			openMenu("comment-edit-submit-wrapper-" + id);
			return true;
		}
		return false;
	}
	function commentClose(obj,id) {
		if(obj.value == '') {
			obj.value = '$comment';
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
		if(tmpStr == '$comment') {
			tmpStr = '';
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-edit-submit-wrapper-" + id);
		}
		var ins = $(obj).html();
		ins = ins.replace('&lt;','<');
		ins = ins.replace('&gt;','>');
		ins = ins.replace('&amp;','&');
		ins = ins.replace('&quot;','"');
		$("#comment-edit-text-" + id).val(tmpStr + ins);
	}

	function qCommentInsert(obj,id) {
		var tmpStr = $("#comment-edit-text-" + id).val();
		if(tmpStr == '$comment') {
			tmpStr = '';
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-edit-submit-wrapper-" + id);
		}
		var ins = $(obj).val();
		ins = ins.replace('&lt;','<');
		ins = ins.replace('&gt;','>');
		ins = ins.replace('&amp;','&');
		ins = ins.replace('&quot;','"');
		$("#comment-edit-text-" + id).val(tmpStr + ins);
		$(obj).val('');
	}

	window.showMore = "$showmore";
	window.showFewer = "$showfewer";

	function showHideCommentBox(id) {
		if( $('#comment-edit-form-' + id).is(':visible')) {
			$('#comment-edit-form-' + id).hide();
		}
		else {
			$('#comment-edit-form-' + id).show();
		}
	}


</script>


