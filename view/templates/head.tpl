{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<base href="{{$baseurl}}/" />
<meta name="generator" content="{{$generator}}" />
<link rel="stylesheet" href="view/global.css?v={{$VERSION}}" type="text/css" media="all" />
<link rel="stylesheet" href="view/asset/jquery-colorbox/example5/colorbox.css?v={{$VERSION}}" type="text/css" media="screen" />
<link rel="stylesheet" href="view/asset/jgrowl/jquery.jgrowl.min.css?v={{$VERSION}}" type="text/css" media="screen" />
<link rel="stylesheet" href="view/asset/jquery-datetimepicker/build/jquery.datetimepicker.min.css?v={{$VERSION}}" type="text/css" media="screen" />
<link rel="stylesheet" href="view/asset/perfect-scrollbar/dist/css/perfect-scrollbar.min.css?v={{$VERSION}}" type="text/css" media="screen" />
<link rel="stylesheet" href="view/js/fancybox/jquery.fancybox.min.css?v={{$VERSION}}" type="text/css" media="screen" />

{{foreach $stylesheets as $stylesheetUrl => $media}}
	<link rel="stylesheet" href="{{$stylesheetUrl}}" type="text/css" media="{{$media}}" />
{{/foreach}}

<link rel="icon" href="{{$shortcut_icon}}" />
<link rel="apple-touch-icon" href="{{$touch_icon}}"/>

<meta name="apple-mobile-web-app-capable" content="yes" />
<link rel="manifest" href="{{$baseurl}}/manifest" />
<script>
// @license magnet:?xt=urn:btih:d3d9a9a6595521f9666a5e94cc830dab83b65699&dn=expat.txt Expat
// Prevents links to switch to Safari in a home screen app - see https://gist.github.com/irae/1042167
(function(a,b,c){if(c in b&&b[c]){var d,e=a.location,f=/^(a|html)$/i;a.addEventListener("click",function(a){d=a.target;while(!f.test(d.nodeName))d=d.parentNode;"href"in d&&(chref=d.href).replace("{{$baseurl}}/", "").replace(e.href,"").indexOf("#")&&(!/^[a-z\+\.\-]+:/i.test(chref)||chref.indexOf(e.protocol+"//"+e.host)===0)&&(a.preventDefault(),e.href=d.href)},!1)}})(document,window.navigator,"standalone");
// |license-end
</script>

<link rel="search"
         href="{{$baseurl}}/opensearch"
         type="application/opensearchdescription+xml"
         title="Search in Friendica" />

<!--[if IE]>
<script type="text/javascript" src="https://html5shiv.googlecode.com/svn/trunk/html5.js?v={{$VERSION}}"></script>
<![endif]-->
<script type="text/javascript" src="view/js/modernizr.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="view/asset/jquery/dist/jquery.min.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="view/js/jquery.textinputs.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="view/asset/textcomplete/dist/textcomplete.min.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="view/js/autocomplete.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="view/asset/jquery-colorbox/jquery.colorbox-min.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="view/asset/jgrowl/jquery.jgrowl.min.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="view/asset/jquery-datetimepicker/build/jquery.datetimepicker.full.min.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="view/asset/perfect-scrollbar/dist/js/perfect-scrollbar.jquery.min.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="view/asset/imagesloaded/imagesloaded.pkgd.min.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="view/asset/base64/base64.min.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="view/asset/dompurify/dist/purify.min.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="view/js/fancybox/jquery.fancybox.min.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="view/js/fancybox/fancybox.config.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="view/js/vanillaEmojiPicker/vanillaEmojiPicker.min.js?v={{$VERSION}}"></script>
<script>
window.onload = function(){
	new EmojiPicker({
		trigger: [
			{
				selector: '.emojis-post',
				insertInto: ['#comment-edit-text-0', '#profile-jot-text']
			},
			{
				selector: '.emojis',
				insertInto: ['.comment-edit-text-full']
			}
		],
		closeButton: true
	});
};
</script>
<script type="text/javascript">
	const updateContent = {{$update_content}};
	const localUser = {{if $local_user}}{{$local_user}}{{else}}false{{/if}};
</script>
<script type="text/javascript" src="view/js/main.js?v={{$VERSION}}"></script>
<script>
	// Lifted from https://css-tricks.com/snippets/jquery/move-cursor-to-end-of-textarea-or-input/
    jQuery.fn.putCursorAtEnd = function() {
        return this.each(function() {
            // Cache references
            var $el = $(this),
                el = this;

            // Only focus if input isn't already
            if (!$el.is(":focus")) {
                $el.focus();
            }

            // If this function exists... (IE 9+)
            if (el.setSelectionRange) {
                // Double the length because Opera is inconsistent about whether a carriage return is one character or two.
                var len = $el.val().length * 2;

                // Timeout seems to be required for Blink
                setTimeout(function() {
                    el.setSelectionRange(len, len);
                }, 1);
            } else {
                // As a fallback, replace the contents with itself
                // Doesn't work in Chrome, but Chrome supports setSelectionRange
                $el.val($el.val());
            }

            // Scroll to the bottom, in case we're in a tall textarea
            // (Necessary for Firefox and Chrome)
            this.scrollTop = 999999;
        });
    };

	function confirmDelete() { return confirm("{{$delitem}}"); }
	function commentExpand(id) {
		$("#comment-edit-text-" + id).putCursorAtEnd();
		$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
		$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
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

	function showHideCommentBox(id) {
		if ($("#comment-edit-form-" + id).is(":visible")) {
			$("#comment-edit-form-" + id).hide();
		} else {
			$("#comment-edit-form-" + id).show();
		}
	}
</script>
