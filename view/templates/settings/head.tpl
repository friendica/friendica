

<script>
	var ispublic = "{{$ispublic nofilter}}";


	$(document).ready(function() {

		$('#contact_allow, #contact_deny, #circle_allow, #circle_deny').change(function() {
			var selstr;
			$('#contact_allow option:selected, #contact_deny option:selected, #circle_allow option:selected, #circle_deny option:selected').each( function() {
				selstr = $(this).html();
				$('#jot-perms-icon').removeClass('unlock').addClass('lock');
				$('#jot-public').hide();
			});
			if(selstr == null) {
				$('#jot-perms-icon').removeClass('lock').addClass('unlock');
				$('#jot-public').show();
			}

		}).trigger('change');

		$('.settings-content-block').hide();
		$('.settings-heading').click(function(){
			$('.settings-content-block').hide();
			$(this).next('.settings-content-block').toggle();
		});

	});

</script>

