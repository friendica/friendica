<?php

function login_content(&$a) {
	if(x($_SESSION,'theme'))
		unset($_SESSION['theme']);
	if(local_user())
		goaway(z_root());
	return login(($a->config['register_policy'] == REGISTER_CLOSED) ? false : true);

}