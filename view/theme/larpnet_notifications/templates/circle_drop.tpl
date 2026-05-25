{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

{{* Link for deleting contact circles *}}
<a href="circle/drop/{{$id}}?t={{$form_security_token}}" onclick="return confirmDelete();" id="circle-delete-icon-{{$id}}" class="btn btn-clear" title="{{$delete}}" data-toggle="tooltip">
	<i class="fa fa-trash" aria-hidden="true"></i>
</a>
