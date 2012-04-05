<?php

function amcd_content(&$a) {
//header("Content-type: text/json");
echo <<< EOT
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
EOT;
killme();
}