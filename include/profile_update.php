<?php
require_once('include/diaspora.php');

function profile_change() {
	Diaspora::send_profile(local_user());
}
