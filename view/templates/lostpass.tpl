{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<h3>{{$title}}</h3>

<p id="lostpass-desc">
{{$desc nofilter}}
</p>

<form action="lostpass" method="post">
<div id="login-name-wrapper">
   <input type="text" name="login-name" id="login-name" placeholder="{{$name}}" value="" class="form-control" autofocus />
</div>
<div id="login-extra-end"></div>
<div id="login-submit-wrapper">
   <input type="submit" name="submit" id="lostpass-submit-button" class="btn btn-primary" value="{{$submit}}" />
</div>
<div id="login-submit-end"></div>
</form>

