			<!-- Modal  -->
			<div id="modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
				<div class="modal-dialog modal-full-screen">
					<div class="modal-content">
						<div id="modal-header" class="modal-header">
							<button id="modal-cloase" type="button" class="close" data-dismiss="modal" aria-hidden="true">
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
