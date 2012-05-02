<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<base href="$baseurl/" />
<meta name="generator" content="$generator" />
<link rel="stylesheet" href="$baseurl/library/fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />
<link rel="stylesheet" href="$baseurl/library/tiptip/tipTip.css" type="text/css" media="screen" />
<link rel="stylesheet" href="$baseurl/library/jgrowl/jquery.jgrowl.css" type="text/css" media="screen" />

<link rel="stylesheet" type="text/css" href="$stylesheet" media="all" />

<link rel="shortcut icon" href="$baseurl/images/friendica-32.png" />
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
<script type="text/javascript" src="$baseurl/library/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<script type="text/javascript" src="$baseurl/library/tiptip/jquery.tipTip.minified.js"></script>
<script type="text/javascript" src="$baseurl/library/jgrowl/jquery.jgrowl_minimized.js"></script>
<script type="text/javascript" src="$baseurl/library/tinymce/jscripts/tiny_mce/tiny_mce_src.js" ></script>
<script type="text/javascript" src="$baseurl/js/acl.js" ></script>
<script type="text/javascript" src="$baseurl/js/webtoolkit.base64.js" ></script>
<script type="text/javascript" src="$baseurl/js/main.js" ></script>
<script>

	var updateInterval = $update_interval;

	function confirmDelete() { return confirm("$delitem"); }
	function commentOpen(obj,id) {
		if(obj.value == '$comment') {
			obj.value = '';
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-edit-submit-wrapper-" + id);
		}
	}
	function commentClose(obj,id) {
		if(obj.value == '') {
			obj.value = '$comment';
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).addClass("comment-edit-text-empty");
			closeMenu("comment-edit-submit-wrapper-" + id);
		}
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

	function showHideComments(id) {
		if( $('#collapsed-comments-' + id).is(':visible')) {
			$('#collapsed-comments-' + id).hide();
			$('#hide-comments-' + id).html('$showmore');
		}
		else {
			$('#collapsed-comments-' + id).show();
			$('#hide-comments-' + id).html('$showfewer');
		}
	}


</script>


