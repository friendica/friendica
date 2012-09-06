<?php

function login_content(&$a) {
	if(x($_SESSION,'theme'))
		unset($_SESSION['theme']);
	if(x($_SESSION,'mobile-theme'))
		unset($_SESSION['mobile-theme']);

	if(local_user())
		goaway(z_root());
	return login(($a->config['register_policy'] == REGISTER_CLOSED) ? false : true);

}
