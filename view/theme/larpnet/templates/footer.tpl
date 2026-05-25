{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
			<!-- Modal  -->
			<div id="modal" class="modal fade" tabindex="-1" role="dialog">
				<div class="modal-dialog modal-full-screen">
					<div class="modal-content">
						<div id="modal-header" class="modal-header">
							<button id="modal-close" type="button" class="close" data-dismiss="modal" title="{{$close}}">
								&times;
							</button>
							<h4 id="modal-title" class="modal-title"></h4>
						</div>
						<div id="modal-body" class="modal-body">
							<!-- /# content goes here -->
						</div>
					</div>
				</div>
			</div>

			<!-- Dummy div to append other div's when needed (e.g. used for js function editpost() -->
			<div id="cache-container"></div>

{{foreach $footerScripts as $scriptUrl}}
			<script type="text/javascript" src="{{$scriptUrl}}"></script>
{{/foreach}}
