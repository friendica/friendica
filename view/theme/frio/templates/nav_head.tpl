{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<script type="text/javascript">
window.onDocumentReady('body', function() {
	$("#nav-search-input-field").search_autocomplete(baseurl + '/search/acl');
});
</script>
