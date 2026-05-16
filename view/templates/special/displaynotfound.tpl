{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="exception" class="generic-page-wrapper">
	<img class="hare" src="images/friendica-404_svg_flaxy-o-hare.png" title="Flaxy O'Hare"/>
	<h1>{{$l10n.title}}</h1>
	<p>{{$l10n.desc1}}</p>
	<p>{{$l10n.desc2}}</p>
	<ul>
{{foreach $l10n.reasons as $reason}}
		<li>{{$reason}}</li>
{{/foreach}}
	</ul>
</div>
