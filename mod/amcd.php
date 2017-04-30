<?php

use Friendica\App;

function amcd_content(App $a) {
	echo <<< JSON
{
  "version":1,
  "sessionstatus":{
    "method":"GET",
    "path":"/session"
  },
  "auth-methods": {
    "username-password-form": {
      "connect": {
        "method":"POST",
        "path":"/login",
        "params": {
          "username":"login-name",
          "password":"password"
        },
        "onsuccess": { "action":"reload" }
      },
      "disconnect": {
        "method":"GET",
        "path":"\/logout"
      }
    }
  }
  "methods": {
    "username-password-form": {
      "connect": {
        "method":"POST",
        "path":"\/login",
        "params": {
          "username":"login-name",
          "password":"password"
        },
        "onsuccess": { "action":"reload" }
      },
      "disconnect": {
        "method":"GET",
        "path":"\/logout"
      }
    }
  }
}
JSON;
	killme();
}