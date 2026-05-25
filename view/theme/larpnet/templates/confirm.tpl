{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<form action="{{$confirm_url}}" id="confirm-form" method="{{$method}}" class="generic-page-wrapper">
	<div id="confirm-message">{{$l10n.message}}</div>

	<div class="form-group pull-right settings-submit-wrapper">
		<button type="submit" name="{{$confirm_name}}" id="confirm-submit-button" class="btn btn-primary confirm-button" value="{{$confirm_value}}">{{$l10n.confirm}}</button>
		<button type="submit" name="canceled" value="{{$l10n.cancel}}" id="confirm-cancel-button" class="btn confirm-button" data-dismiss="modal">{{$l10n.cancel}}</button>
	</div>
	<div class="clear"></div>
</form>
