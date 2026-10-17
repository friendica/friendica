{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>

	<form enctype="multipart/form-data" action="settings/attachments" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<div id="attachment-upload-wrapper" class="form-group field input">
			<input class="form-control" name="userfile" type="file" id="attachment-upload">
		</div>
		<div id="attachment-upload-submit-wrapper" class="pull-right settings-submit-wrapper">
			<button type="submit" class="btn btn-primary" value="{{$upload}}">{{$upload}}</button>
		</div>
		<div class="clear"></div>
	</form>

	{{if !$attachments}}
		<p>{{$no_attachments}}</p>
	{{else}}
		<form action="settings/attachments" method="post" autocomplete="off">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			<table id='attachment-block' class='table table-condensed table-striped'>
				<thead>
					<tr>
						<th>{{$name}}</th>
						<th>{{$size}}</th>
						<th>{{$created}}</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					{{foreach $attachments as $attachment}}
					<tr>
						<td><a href="attach/{{$attachment.id}}">{{$attachment.filename}}</a></td>
						<td>{{$attachment.filesize}}</td>
						<td>{{$attachment.created}}</td>
						<td>
							<button type="submit" class="btn" title="{{$delete}}" name="delete" value="{{$attachment.id}}">
								<i class="icon s22 delete" aria-hidden="true"></i>
							</button>
						</td>
					</tr>
					{{/foreach}}
				</tbody>
			</table>
		</form>
		{{$paginate nofilter}}
	{{/if}}
</div>
