
<link rel='stylesheet' type='text/css' href='{{$baseurl}}/library/fullcalendar/fullcalendar.css' />
<link rel='stylesheet' type='text/css' href='view/theme/frio/css/mod_events.css' />
<script type="text/javascript" src="{{$baseurl}}/library/moment/moment.min.js"></script>
<script type="text/javascript" src="{{$baseurl}}/library/moment/locales.min.js"></script>
<script type="text/javascript" src="{{$baseurl}}/library/fullcalendar/fullcalendar.min.js"></script>
<script type="text/javascript" src="view/theme/frio/js/mod_events.js"></script>

<script language="javascript" type="text/javascript">
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

	var moduleUrl = '{{$module_url}}';
	var modparams = {{$modparams}}

</script>

{{if $editselect != 'none'}}
<script language="javascript" type="text/javascript"
	  src="{{$baseurl}}/library/tinymce/jscripts/tiny_mce/tiny_mce_src.js"></script>
<script language="javascript" type="text/javascript">

	tinyMCE.init({
		theme : "advanced",
		mode : "textareas",
		plugins : "bbcode,paste",
		theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor,formatselect,code",
		theme_advanced_buttons2 : "",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "center",
		theme_advanced_blockformats : "blockquote,code",
		theme_advanced_resizing : true,
		gecko_spellcheck : true,
		paste_text_sticky : true,
		entity_encoding : "raw",
		add_unload_trigger : false,
		remove_linebreaks : false,
		forced_root_block : 'div',
		content_css: "{{$baseurl}}/view/custom_tinymce.css",
		theme_advanced_path : false,
		setup : function(ed) {
			ed.onInit.add(function(ed) {
				ed.pasteAsPlainText = true;
			});
		}

	});

	$(document).ready(function() {
		$('.comment-edit-bb').hide();
	});
{{else}}
	<script language="javascript" type="text/javascript">
{{/if}}

	$(document).ready(function() {
{{if $editselect = 'none'}}
		$("#comment-edit-text-desc").bbco_autocomplete('bbcode');
{{/if}}

	});

</script>
