<script type="text/javascript">
	function updateLocationButtonDisplay(location_button, location_input)
	{
		location_button.classList.remove('btn-primary');
		if (location_input.value) {
			location_button.disabled = false;
			location_button.classList.add('btn-primary');
			location_button.title = location_button.dataset.titleClear;
		} else if (!"geolocation" in navigator) {
			location_button.disabled = true;
			location_button.title = location_button.dataset.titleUnavailable;
		} else if (location_button.disabled) {
			location_button.title = location_button.dataset.titleDisabled;
		} else {
			location_button.title = location_button.dataset.titleSet;
		}
	}

	$(function() {
		// Jot attachment live preview.
		let $textarea = $('#comment-edit-text-0');
		$textarea.linkPreview();
		$textarea.keyup(function(){
			var textlen = $(this).val().length;
			$('#character-counter').text(textlen);
		});
		$textarea.editor_autocomplete(baseurl+"/acl");
		$textarea.bbco_autocomplete('bbcode');

		let $acl_allow_input = $('#acl_allow');
		let $group_allow_input = $('[name=group_allow]');
		let $contact_allow_input = $('[name=contact_allow]');
		let $acl_deny_input = $('#acl_deny');
		let $group_deny_input = $('[name=group_deny]');
		let $contact_deny_input = $('[name=contact_deny]');

		// Visibility accordion

		// Prevents open panel to collapse
		// @see https://stackoverflow.com/a/43593116
		$('[data-toggle="collapse"]').click(function(e) {
			target = $(this).attr('href');
			if ($(target).hasClass('in')) {
				e.preventDefault(); // to stop the page jump to the anchor target.
				e.stopPropagation()
			}
		});

		$('#visibility-public-panel').on('show.bs.collapse', function() {
			$('#visibility-public').prop('checked', true);
			$group_allow_input.prop('disabled', true);
			$contact_allow_input.prop('disabled', true);
			$group_deny_input.prop('disabled', true);
			$contact_deny_input.prop('disabled', true);
		});

		$('#visibility-custom-panel').on('show.bs.collapse', function() {
			$('#visibility-custom').prop('checked', true);
			$group_allow_input.prop('disabled', false);
			$contact_allow_input.prop('disabled', false);
			$group_deny_input.prop('disabled', false);
			$contact_deny_input.prop('disabled', false);
		});

		if (document.querySelector('input[name="visibility"]:checked').value === 'custom') {
			$('#visibility-custom-panel').collapse({parent: '#visibility-accordion'});
		}

		// Custom visibility tags inputs

		let acl_groups = new Bloodhound({
			local: {{$acl_groups|@json_encode nofilter}},
			identify: function(obj) { return obj.id; },
			datumTokenizer: Bloodhound.tokenizers.obj.whitespace(['name']),
			queryTokenizer: Bloodhound.tokenizers.whitespace,
		});
		let acl_contacts = new Bloodhound({
			local: {{$acl_contacts|@json_encode nofilter}},
			identify: function(obj) { return obj.id; },
			datumTokenizer: Bloodhound.tokenizers.obj.whitespace(['name', 'addr']),
			queryTokenizer: Bloodhound.tokenizers.whitespace,
		});
		let acl = new Bloodhound({
			local: {{$acl|@json_encode nofilter}},
			identify: function(obj) { return obj.id; },
			datumTokenizer: Bloodhound.tokenizers.obj.whitespace(['name', 'addr']),
			queryTokenizer: Bloodhound.tokenizers.whitespace,
		});
		acl.initialize();

		let suggestionTemplate = function (item) {
			return '<div><img src="' + item.micro + '" alt="" style="float: left; width: auto; height: 2.8em; margin-right: 0.5em;"> <strong>' + item.name + '</strong><br /><em>' + item.addr + '</em></div>';
		};

		$acl_allow_input.tagsinput({
			confirmKeys: [13, 44],
			cancelConfirmKeysOnEmpty: true,
			freeInput: false,
			tagClass: function(item) {
				switch (item.type) {
					case 'group'   : return 'label label-primary';
					case 'contact'  :
					default:
						return 'label label-info';
				}
			},
			itemValue: 'id',
			itemText: 'name',
			itemThumb: 'micro',
			itemTitle: function(item) {
				return item.addr;
			},
			typeaheadjs: {
				name: 'contacts',
				displayKey: 'name',
				templates: {
					suggestion: suggestionTemplate
				},
				source: acl.ttAdapter()
			}
		});

		$acl_deny_input
		.tagsinput({
			confirmKeys: [13, 44],
			freeInput: false,
			tagClass: function(item) {
				switch (item.type) {
					case 'group'   : return 'label label-primary';
					case 'contact'  :
					default:
						return 'label label-info';
				}
			},
			itemValue: 'id',
			itemText: 'name',
			itemThumb: 'micro',
			itemTitle: function(item) {
				return item.addr;
			},
			typeaheadjs: {
				name: 'contacts',
				displayKey: 'name',
				templates: {
					suggestion: suggestionTemplate
				},
				source: acl.ttAdapter()
			}
		});

		// Import existing ACL into the tags input fields.

		$group_allow_input.val().split(',').forEach(function (val) {
			$acl_allow_input.tagsinput('add', acl_groups.get(val)[0]);
		});
		$contact_allow_input.val().split(',').forEach(function (val) {
			$acl_allow_input.tagsinput('add', acl_contacts.get(val)[0]);
		});
		$group_deny_input.val().split(',').forEach(function (val) {
			$acl_deny_input.tagsinput('add', acl_groups.get(val)[0]);
		});
		$contact_deny_input.val().split(',').forEach(function (val) {
			$acl_deny_input.tagsinput('add', acl_contacts.get(val)[0]);
		});

		// Anti-duplicate callback + acl fields value generation

		$acl_allow_input.on('itemAdded', function (event) {
			// Removes duplicate in the opposite acl box
			$acl_deny_input.tagsinput('remove', event.item);

			// Update the real acl field
			$group_allow_input.val('');
			$contact_allow_input.val('');
			[].forEach.call($acl_allow_input.tagsinput('items'), function (item) {
				if (item.type === 'group') {
					$group_allow_input.val($group_allow_input.val() + '<' + item.id + '>');
				} else {
					$contact_allow_input.val($contact_allow_input.val() + '<' + item.id + '>');
				}
			});
		});

		$acl_deny_input.on('itemAdded', function (event) {
			// Removes duplicate in the opposite acl box
			$acl_allow_input.tagsinput('remove', event.item);

			// Update the real acl field
			$group_deny_input.val('');
			$contact_deny_input.val('');
			[].forEach.call($acl_deny_input.tagsinput('items'), function (item) {
				if (item.type === 'group') {
					$group_deny_input.val($group_allow_input.val() + '<' + item.id + '>');
				} else {
					$contact_deny_input.val($contact_allow_input.val() + '<' + item.id + '>');
				}
			});
		});

		let location_button = document.getElementById('profile-location');
		let location_input = document.getElementById('jot-location');

		updateLocationButtonDisplay(location_button, location_input);

		location_input.addEventListener('change', function () {
			updateLocationButtonDisplay(location_button, location_input);
		});
		location_input.addEventListener('keyup', function () {
			updateLocationButtonDisplay(location_button, location_input);
		});

		location_button.addEventListener('click', function() {
			if (location_input.value) {
				location_input.value = '';
				updateLocationButtonDisplay(location_button, location_input);
			} else if ("geolocation" in navigator) {
				navigator.geolocation.getCurrentPosition(function(position) {
					location_input.value = position.coords.latitude + ', ' + position.coords.longitude;
					updateLocationButtonDisplay(location_button, location_input);
				}, function (error) {
					location_button.disabled = true;
					updateLocationButtonDisplay(location_button, location_input);
				});
			}
		});
	})
</script>
