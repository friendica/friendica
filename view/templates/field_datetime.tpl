{{include file='field_input.tpl' field=$field}}

<script type="text/javascript">
	$(function () {
		$('#id_{{$field.0}}').datetimepicker({
			step: 5,
			format: '{{$datetimepicker.dateformat}}',
{{if $datetimepicker.minDate}}
			minDate: new Date({{$datetimepicker.minDate->getTimestamp()}} * 1000),
			yearStart: {{$datetimepicker.minDate->format('Y')}},
{{/if}}
{{if $datetimepicker.maxDate}}
			maxDate: new Date({{$datetimepicker.maxDate->getTimestamp()}} * 1000),
			yearEnd: {{$datetimepicker.maxDate->format('Y')}},
{{/if}}
{{if $datetimepicker.defaultDate}}
			defaultDate: new Date({{$datetimepicker.defaultDate->getTimestamp()}} * 1000),
{{/if}}
			dayOfWeekStart: {{$datetimepicker.firstDay}},
			lang: '{{$datetimepicker.lang}}'
		});
{{if $datetimepicker.lang}}
		jQuery.datetimepicker.setLocale('{{$datetimepicker.lang}}');
{{/if}}

{{if $datetimepicker.minfrom }}
		$('#id_{{$datetimepicker.minfrom}}').data('xdsoft_datetimepicker').setOptions({
			onChangeDateTime: function (currentDateTime) {
				$('#id_{{$field.0}}').data('xdsoft_datetimepicker').setOptions({minDate: currentDateTime});
			}
		});
{{/if}}
{{if $datetimepicker.maxfrom }}
		$('#id_{{$datetimepicker.maxfrom}}').data('xdsoft_datetimepicker').setOptions({
			onChangeDateTime: function (currentDateTime) {
				$('#id_{{$field.0}}').data('xdsoft_datetimepicker').setOptions({maxDate: currentDateTime});
			}
		});
{{/if}}
	})
</script>
