<?php
require_once("hostxrd.php");

function _well_known_init(&$a){
    if ($a->argc > 1) {
        switch($a->argv[1]) {
            case "host-meta":
                hostxrd_init($a);
                break;
        }
    }
    http_status_exit(404);
    killme();
}