{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="credits" class="generic-page-wrapper">
        {{include file="section_title.tpl"}}
        <p>{{$thanks}}</p>

        <ul class="credits">
                {{foreach $names as $name}}
                 <li>{{$name}}</li>
                {{/foreach}}
        </ul>
        <div class="clear"></div>
</div>
