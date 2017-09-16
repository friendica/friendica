<?php

/**
 *
 * File: testargs.php
 *
 * Purpose:
 * During installation we need to check if register_argc_argv is
 * enabled for the command line PHP processor, because otherwise
 * deliveries will fail. So we will do a shell exec of php and
 * execute this file with a command line argument, and see if it
 * echoes the argument back to us. Otherwise notify the person
 * that their installation doesn't meet the system requirements.
 *
 */


if (($_SERVER["argc"] > 1) && isset($_SERVER["argv"][1])) {
	echo $_SERVER["argv"][1];
} else {
	echo '';
}
