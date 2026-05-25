{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

{{* Strings which are needed for some js functions (e.g. translation or the interval for page update)
They are loaded into the html <head> so that js functions can use them *}}
<script type="text/javascript">
	const aStr = {
		delitem          : "{{$l10n.delitem|escape:'javascript' nofilter}}",
		blockAuthor      : "{{$l10n.blockAuthor|escape:'javascript' nofilter}}",
		ignoreAuthor     : "{{$l10n.ignoreAuthor|escape:'javascript' nofilter}}",
		collapseAuthor   : "{{$l10n.collapseAuthor|escape:'javascript' nofilter}}",
		ignoreServer     : "{{$l10n.ignoreServer|escape:'javascript' nofilter}}",
		ignoreServerDesc : "{{$l10n.ignoreServerDesc|escape:'javascript' nofilter}}",
	};
	const aActErr = {
		like       : "{{$l10n.likeError|escape:'javascript' nofilter}}",
		dislike    : "{{$l10n.dislikeError|escape:'javascript' nofilter}}",
		announce   : "{{$l10n.announceError|escape:'javascript' nofilter}}",
		attendyes  : "{{$l10n.attendError|escape:'javascript' nofilter}}",
		attendno   : "{{$l10n.attendError|escape:'javascript' nofilter}}",
		attendmaybe: "{{$l10n.attendError|escape:'javascript' nofilter}}",
	};
	const aErrType = {
		srvErr: "{{$l10n.srvError|escape:'javascript' nofilter}}",
		netErr: "{{$l10n.netError|escape:'javascript' nofilter}}",
	};
	const dzStrings = {
		dictDefaultMessage          : "{{$l10n.dictDefaultMessage|escape:'javascript' nofilter}}",
		dictFallbackMessage         : "{{$l10n.dictFallbackMessage|escape:'javascript' nofilter}}",
		dictFallbackText            : "{{$l10n.dictFallbackText|escape:'javascript' nofilter}}",
		dictFileTooBig              : "{{$l10n.dictFileTooBig|escape:'javascript' nofilter}}",
		dictInvalidFileType         : "{{$l10n.dictInvalidFileType|escape:'javascript' nofilter}}",
		dictResponseError           : "{{$l10n.dictResponseError|escape:'javascript' nofilter}}",
		dictCancelUpload            : "{{$l10n.dictCancelUpload|escape:'javascript' nofilter}}",
		dictUploadCanceled          : "{{$l10n.dictUploadCanceled|escape:'javascript' nofilter}}",
		dictCancelUploadConfirmation: "{{$l10n.dictCancelUploadConfirmation|escape:'javascript' nofilter}}",
		dictRemoveFile              : "{{$l10n.dictRemoveFile|escape:'javascript' nofilter}}",
		dictMaxFilesExceeded        : "{{$l10n.dictMaxFilesExceeded|escape:'javascript' nofilter}}",
	};
</script>
