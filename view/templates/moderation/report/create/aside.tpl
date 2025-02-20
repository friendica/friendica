{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<nav class="widget">
	<p><strong>{{$l10n.contact_title}}</strong></p>
	{{if $contact}}
		{{include file="contact/entry.tpl"}}
	{{/if}}
	<p><strong>{{$l10n.category_title}}</strong></p>
	{{if $category}}
		<p>{{$category}}</p>
	{{/if}}
	{{if $rules}}
		<p><strong>{{$l10n.rules_title}}</strong></p>
		<ol>
			{{foreach $rules as $rule_id => $rule_text}}
				<li value="{{$rule_id}}">{{$rule_text}}</li>
			{{/foreach}}
		</ol>
	{{/if}}
	{{if $comment}}
		<p><strong>{{$l10n.comment_title}}</strong></p>
		<p>{{$comment nofilter}}</p>
	{{/if}}
	{{if $posts}}
		<p><strong>{{$l10n.posts_title}} ({{$posts}})</strong></p>
	{{/if}}
</nav>