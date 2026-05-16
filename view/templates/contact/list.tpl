{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

{{include file="section_title.tpl"}}

{{$tab_str nofilter}}

{{$additional_text}}

<div id="viewcontact_wrapper-{{$id}}">
{{foreach $contacts as $contact}}
	{{include file="contact/entry.tpl"}}
{{/foreach}}
</div>
<div class="clear"></div>
<div id="view-contact-end"></div>

{{$paginate nofilter}}

{{if $filtered}}
	<p>{{$filtered nofilter}}</p>
{{/if}}
