
<script type="text/javascript" src="view/theme/frio/js/mod_events.js?v={{$smarty.const.FRIENDICA_VERSION}}"></script>

<script type="text/javascript">
	// pass php translation strings to js variables/arrays so we can make use of it in js files
	aStr.monthNames = [
		'{{$i18n.January|escape:'quotes'}}',
		'{{$i18n.February|escape:'quotes'}}',
		'{{$i18n.March|escape:'quotes'}}',
		'{{$i18n.April|escape:'quotes'}}',
		'{{$i18n.May|escape:'quotes'}}',
		'{{$i18n.June|escape:'quotes'}}',
		'{{$i18n.July|escape:'quotes'}}',
		'{{$i18n.August|escape:'quotes'}}',
		'{{$i18n.September|escape:'quotes'}}',
		'{{$i18n.October|escape:'quotes'}}',
		'{{$i18n.November|escape:'quotes'}}',
		'{{$i18n.December|escape:'quotes'}}'
	];
	aStr.monthNamesShort = [
		'{{$i18n.Jan|escape:'quotes'}}',
		'{{$i18n.Feb|escape:'quotes'}}',
		'{{$i18n.Mar|escape:'quotes'}}',
		'{{$i18n.Apr|escape:'quotes'}}',
		'{{$i18n.May|escape:'quotes'}}',
		'{{$i18n.Jun|escape:'quotes'}}',
		'{{$i18n.Jul|escape:'quotes'}}',
		'{{$i18n.Aug|escape:'quotes'}}',
		'{{$i18n.Sep|escape:'quotes'}}',
		'{{$i18n.Oct|escape:'quotes'}}',
		'{{$i18n.Nov|escape:'quotes'}}',
		'{{$i18n.Dec|escape:'quotes'}}'
	];
	aStr.dayNames = [
		'{{$i18n.Sunday|escape:'quotes'}}',
		'{{$i18n.Monday|escape:'quotes'}}',
		'{{$i18n.Tuesday|escape:'quotes'}}',
		'{{$i18n.Wednesday|escape:'quotes'}}',
		'{{$i18n.Thursday|escape:'quotes'}}',
		'{{$i18n.Friday|escape:'quotes'}}',
		'{{$i18n.Saturday|escape:'quotes'}}'
	];
	aStr.dayNamesShort = [
		'{{$i18n.Sun|escape:'quotes'}}',
		'{{$i18n.Mon|escape:'quotes'}}',
		'{{$i18n.Tue|escape:'quotes'}}',
		'{{$i18n.Wed|escape:'quotes'}}',
		'{{$i18n.Thu|escape:'quotes'}}',
		'{{$i18n.Fri|escape:'quotes'}}',
		'{{$i18n.Sat|escape:'quotes'}}'
	];

	aStr.firstDay = '{{$i18n.firstDay|escape:'quotes'}}';
	aStr.today = '{{$i18n.today|escape:'quotes'}}';
	aStr.month = '{{$i18n.month|escape:'quotes'}}';
	aStr.week = '{{$i18n.week|escape:'quotes'}}';
	aStr.day = '{{$i18n.day|escape:'quotes'}}';

	aStr.allday = '{{$i18n.allday|escape:'quotes'}}';
	aStr.noevent = '{{$i18n.noevent|escape:'quotes'}}';

	aStr.dtstartLabel = '{{$i18n.dtstart_label|escape:'quotes'}}';
	aStr.dtendLabel = '{{$i18n.dtend_label|escape:'quotes'}}';
	aStr.locationLabel = '{{$i18n.location_label|escape:'quotes'}}';

	aStr.defaultView = '{{$i18n.defaultView|escape:'quotes'}}';
	var calendar_api = '{{$calendar_api}}';
	var event_api = '{{$event_api}}';

	$(document).ready(function() {
		$("#comment-edit-text-desc").bbco_autocomplete('bbcode');
	});
</script>
