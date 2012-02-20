<?php

// Nastavte následující pro Vaši instalaci MySQL
// Zkopírujte nebo přejmenujte tento soubor na .htconfig.php

$db_host = '$dbhost';
$db_user = '$dbuser';
$db_pass = '$dbpass';
$db_data = '$dbdata';

// Pokud používáte podadresár z vaší domény, budete zde potřebovat zadat
// relativní cestu (od kořene vaší domény).
// Například pokud je URL adresa vaší instance 'http://priklad.cz/adresar/podadresar',
// nastavte $a->path na 'adresar/podadresar'. 

$a->path = '$urlpath';
 
// Vyberte platnou defaultní časovou zónu. Pokud si nejste jistí, použijte use "Europe/Prague".
// Toto nastavení lze změnit i později a používá se pouze pro časové značky anonymních čtenářů.

$default_timezone = '$timezone';

// Jak se jmenuje Váš web?

$a->config['sitename'] = "Moje síť přátel";

// Nastavení defaultního jazyka webu

$a->config['system']['language'] = 'cs';

// Vaše možnosti jsou REGISTER_OPEN, REGISTER_APPROVE, or REGISTER_CLOSED.
// Ujistěte se, že jste si vytvořili Váš osobníúčet dříve, než nastavíte 
// REGISTER_CLOSED. 'register_text' (pokud je nastaven) se bude zobrazovat jako první text na 
// registrační stránce. REGISTER_APPROVE vyžaduje aby byl nastaven 'admin_email'
// na e-mailovou adresu již existující registrované osoby, která může autorizovat
// a/nebo schvalovat/odmítat žádosti o registraci.

$a->config['register_policy'] = REGISTER_OPEN;
$a->config['register_text'] = '';
$a->config['admin_email'] = '$adminmail';

// Maximální velikost importované zprávy, 0 je neomezeno

$a->config['max_import_size'] = 200000;

// maximální velikost nahrávaných fotografií

$a->config['system']['maximagesize'] = 800000;

// cesta k PHP command line processor

$a->config['php_path'] = '$phpath';

// URL adresy globálního adresáře.

$a->config['system']['directory_submit_url'] = 'http://dir.friendica.com/submit';
$a->config['system']['directory_search_url'] = 'http://dir.friendica.com/directory?search=';

// PuSH - také zvaný jako  pubsubhubbub URL. Tímto zajistíte doručování veřejných přízpěvků stejně rychle jako těch soukromých

$a->config['system']['huburl'] = 'http://pubsubhubbub.appspot.com';

// Server-to-server private message encryption (RINO) je defaultně povolen. 
// Šifrování bude zajištěno pouze pokud je toto nastaveno na true a
// PHP mcrypt extension jsou nainstalována na obou systémech 

$a->config['system']['rino_encrypt'] = true;

// defaultní systémové grafické téma

$a->config['system']['theme'] = 'duepuntozero';


