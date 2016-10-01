<?php
require_once('include/diaspora.php');

function profile_change()
{
    diaspora::send_profile(local_user());
}
