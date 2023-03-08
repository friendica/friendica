Friendica Addon/Entwicklung
==============

* [Zur Startseite der Hilfe](help)

Bitte schau dir das Beispiel-Addon "randplace" für ein funktionierendes Beispiel für manche der hier aufgeführten Funktionen an.
Das Facebook-Addon bietet ein Beispiel dafür, die "addon"- und "module"-Funktion gemeinsam zu integrieren.
Addons arbeiten, indem sie Event Hooks abfangen.
Module arbeiten, indem bestimmte Seitenanfragen (durch den URL-Pfad) abgefangen werden.

Addon-Namen können keine Leerstellen oder andere Interpunktionen enthalten und werden als Datei- und Funktionsnamen genutzt.
Du kannst einen lesbaren Namen im Kommentarblock eintragen.
Jedes Addon muss beides beinhalten - eine Installations- und eine Deinstallationsfunktion, die auf dem Addon-Namen basieren; z.B. "addon1name_install()".
Diese beiden Funktionen haben keine Argumente und sind dafür verantwortlich, Event Hooks zu registrieren und abzumelden (unregistering), die dein Addon benötigt.
Die Installations- und Deinstallationsfunktionfunktionen werden auch ausgeführt (z.B. neu installiert), wenn sich das Addon nach der Installation ändert - somit sollte deine Deinstallationsfunktion keine Daten zerstört und deine Installationsfunktion sollte bestehende Daten berücksichtigen.
Zukünftige Extensions werden möglicherweise "Setup" und "Entfernen" anbieten.

Addons sollten einen Kommentarblock mit den folgenden vier Parametern enthalten:

    /*
     * Name: My Great Addon
     * Description: This is what my addon does. It's really cool.
     * Version: 1.0
     * Author: John Q. Public <john@myfriendicasite.com>
     */

Registriere deine Addon-Hooks während der Installation.

    \Friendica\Core\Hook::register($hookname, $file, $function);

$hookname ist ein String und entspricht einem bekannten Friendica-Hook.

$file steht für den Pfadnamen, der relativ zum Top-Level-Friendicaverzeichnis liegt.
Das *sollte* "addon/addon_name/addon_name.php' sein.

$function ist ein String und der Name der Funktion, die ausgeführt wird, wenn der Hook aufgerufen wird.

Argumente
---

Deine Hook-Callback-Funktion wird mit höchstens einem Argumenten aufgerufen

    function myhook_function(&$b) {

    }

Wenn du Änderungen an den aufgerufenen Daten vornehmen willst, musst du diese als Referenzvariable (mit "&") während der Funktionsdeklaration deklarieren.

$b kann frei benannt werden.
Diese Information ist speziell auf den Hook bezogen, der aktuell bearbeitet wird, und beinhaltet normalerweise Daten, die du sofort nutzen, anzeigen oder bearbeiten kannst.
Achte darauf, diese mit "&" zu deklarieren, wenn du sie bearbeiten willst.


Module
---

Addons können auch als "Module" agieren und alle Seitenanfragen für eine bestimte URL abfangen.
Um ein Addon als Modul zu nutzen, ist es nötig, die Funktion "addon_name_module()" zu definieren, die keine Argumente benötigt und nichts weiter machen muss.

Wenn diese Funktion existiert, wirst du nun alle Seitenanfragen für "http://example.com/addon_name" erhalten - mit allen URL-Komponenten als zusätzliche Argumente.
Diese werden in das App\Arguments Objekt geparst.
So würde `http://example.com/addon/arg1/arg2` dies ergeben:
```php
DI::args()->getArgc(); // = 3
DI::args()->get(0); // = 'addon'
DI::args()->get(1); // = 'arg1'
DI::args()->get(2); // = 'arg2'
```

Deine Modulfunktionen umfassen oft die Funktion `addon_name_content()`, welche den Seiteninhalt definiert und zurückgibt.
Sie können auch `addon_name_post()` umfassen, welches vor der content-Funktion aufgerufen wird und normalerweise die Resultate der POST-Formulare handhabt.
Du kannst ebenso `addon_name_init()` nutzen, was oft frühzeitig aufgerufen wird und das Modul initialisert.


Derzeitige Hooks
---

**'authenticate'** - wird aufgerufen, wenn sich der User einloggt.
    $b ist ein Array
        'username' => der übertragene Nutzername
        'password' => das übertragene Passwort
        'authenticated' => setze das auf einen anderen Wert als "0", damit der User sich authentifiziert
        'user_record' => die erfolgreiche Authentifizierung muss auch einen gültigen Nutzereintrag aus der Datenbank zurückgeben

**'logged_in'** - wird aufgerufen, sobald ein Nutzer sich erfolgreich angemeldet hat.
    $b beinhaltet den `App->user`


**'display_item'** - wird aufgerufen, wenn ein Beitrag für die Anzeige formatiert wird.
    $b ist ein Array
        'item' => Die Item-Details (Array), die von der Datenbank ausgegeben werden
        'output' => Die HTML-Ausgabe (String) des Items, bevor es zur Seite hinzugefügt wird

**'post_local'** - wird aufgerufen, wenn der Statusbeitrag oder ein Kommentar im lokalen System eingetragen wird.
    $b ist das Item-Array der Information, die in der Datenbank hinterlegt wird.
        {Bitte beachte: der Seiteninhalt ist bbcode - nicht HTML)

**'post_local_end'** - wird aufgerufen, wenn ein lokaler Statusbeitrag oder Kommentar im lokalen System gespeichert wird.
    $b ist das Item-Array einer Information, die gerade in der Datenbank gespeichert wurden.
        {Bitte beachte: der Seiteninhalt ist bbcode - nicht HTML)

**'post_remote'** - wird aufgerufen, wenn ein Beitrag aus einer anderen Quelle empfangen wird. Dies kann auch genutzt werden, um lokale Aktivitäten oder systemgenerierte Nachrichten zu veröffentlichen/posten.
    $b ist das Item-Array einer Information, die in der Datenbank und im Item gespeichert ist.
        {Bitte beachte: der Seiteninhalt ist bbcode - nicht HTML)

**'addon_settings'** - wird aufgerufen, wenn die HTML-Ausgabe der Addon-Einstellungsseite generiert wird.
    $b ist die HTML-Ausgabe (String) der Addon-Einstellungsseite vor dem finalen "</form>"-Tag.

**'addon_settings_post'** - wird aufgerufen, wenn die Addon-Einstellungsseite geladen wird.
    $b ist der $_POST-Array

**'profile_post'** - wird aufgerufen, wenn die Profilseite angezeigt wird.
    $b ist der $_POST-Array

**'profile_edit'** - wird aufgerufen, bevor die Profil-Bearbeitungsseite angezeigt wird.
    $b ist ein Array
        'profile' => Profileintrag (Array) aus der Datenbank
        'entry' => die HTML-Ausgabe (string) des generierten Eintrags

**'profile_advanced'** - wird aufgerufen, wenn die HTML-Ausgabe für das "Advanced profile" generiert wird; stimmt mit dem "Profil"-Tab auf der Profilseite der Nutzer überein.
    $b ist die HTML-Ausgabe (String) des erstellten Profils
    (Die Details des Profil-Arrays sind in `App->profile`)

**'directory_item'** - wird von der Verzeichnisseite aufgerufen, wenn ein Item für die Anzeige formatiert wird.
    $b ist ein Array
        'contact' => Kontakteintrag (Array) einer Person aus der Datenbank
        'entry' => die HTML-Ausgabe (String) des generierten Eintrags

**'profile_sidebar_enter'** - wird aufgerufen, bevor die Sidebar "Kurzprofil" einer Seite erstellt wird.
    $b ist der Profil-Array einer Person

**'profile_sidebar'** - wird aufgerufen, wenn die Sidebar "Kurzprofil" einer Seite erstellt wird.
    $b ist ein Array
        'profile' => Kontakteintrag (Array) einer Person aus der Datenbank
        'entry' => die HTML-Ausgabe (String) des generierten Eintrags

**'contact_block_end'** - wird aufgerufen, wenn der Block "Kontakte/Freunde" der Profil-Sidebar komplett formatiert wurde.
    $b ist ein Array
          'contacts' => Array von "contacts"
          'output' => die HTML-Ausgabe (String) des Kontaktblocks

**'bbcode'** - wird während der Umwandlung von bbcode auf HTML aufgerufen.
    $b ist der konvertierte Text (String)

**'html2bbcode'** - wird während der Umwandlung von HTML zu bbcode aufgerufen (z.B. bei Nachrichtenbeiträgen).
    $b ist der konvertierte Text (String)

**'page_header'** - wird aufgerufen, nachdem der Bereich der Seitennavigation geladen wurde.
    $b ist die HTML-Ausgabe (String) der "nav"-Region

**'personal_xrd'** - wird aufgerufen, bevor die Ausgabe der persönlichen XRD-Datei erzeugt wird.
    $b ist ein Array
        'user' => die hinterlegten Einträge der Person
        'xml' => die komplette XML-Datei die ausgegeben wird

**'home_content'** - wird aufgerufen, bevor die Ausgabe des Homepage-Inhalts erstellt wird; wird nicht eingeloggten Nutzern angezeigt.
    $b ist die HTML-Ausgabe (String) der Auswahlregion

**'contact_edit'** - wird aufgerufen, wenn die Kontaktdetails vom Nutzer auf der "Kontakte"-Seite bearbeitet werden.
    $b ist ein Array
        'contact' => Kontakteintrag (Array) des abgezielten Kontakts
        'output' => die HTML-Ausgabe (String) der "Kontakt bearbeiten"-Seite

**'contact_edit_post'** - wird aufgerufen, wenn die "Kontakt bearbeiten"-Seite ausgegeben wird.
    $b ist der $_POST-Array

**'init_1'** - wird aufgerufen, kurz nachdem die Datenbank vor Beginn der Sitzung geöffnet wird.
    $b wird nicht genutzt

**'page_end'** - wird aufgerufen, nachdem die Funktion des HTML-Inhalts komplett abgeschlossen ist.
    $b ist die HTML-Ausgabe (String) vom Inhalt-"div"

**'avatar_lookup'** - wird aufgerufen, wenn der Avatar geladen wird.
    $b ist ein Array
        'size' => Größe des Avatars, der geladen wird
        'email' => Email-Adresse, um nach dem Avatar zu suchen
        'url' => generierte URL (String) des Avatars

**'nav_info'**
 - wird aufgerufen nachdem in include/nav,php der Inhalt des Navigations Menüs erzeugt wurde.
 - $b ist ein Array, das $nav wiederspiegelt.

Komplette Liste der Hook-Callbacks
---

Eine komplette Liste aller Hook-Callbacks mit den zugehörigen Dateien (am 01-Apr-2018 generiert): Bitte schau in die Quellcodes für Details zu Hooks, die oben nicht dokumentiert sind.

### index.php

    Hook::callAll('init_1');
    Hook::callAll('app_menu', $arr);
    Hook::callAll('page_content_top', DI::page()['content']);
    Hook::callAll($a->module.'_mod_init', $placeholder);
    Hook::callAll($a->module.'_mod_init', $placeholder);
    Hook::callAll($a->module.'_mod_post', $_POST);
    Hook::callAll($a->module.'_mod_content', $arr);
    Hook::callAll($a->module.'_mod_aftercontent', $arr);
    Hook::callAll('page_end', DI::page()['content']);

### include/api.php

    Hook::callAll('logged_in', $a->user);
    Hook::callAll('authenticate', $addon_auth);
    Hook::callAll('logged_in', $a->user);

### include/enotify.php

    Hook::callAll('enotify', $h);
    Hook::callAll('enotify_store', $datarray);
    Hook::callAll('enotify_mail', $datarray);
    Hook::callAll('check_item_notification', $notification_data);

### src/Content/Conversation.php

    Hook::callAll('conversation_start', $cb);
    Hook::callAll('render_location', $locate);
    Hook::callAll('display_item', $arr);
    Hook::callAll('display_item', $arr);
    Hook::callAll('item_photo_menu', $args);
    Hook::callAll('jot_tool', $jotplugins);

### mod/directory.php

    Hook::callAll('directory_item', $arr);

### mod/xrd.php

    Hook::callAll('personal_xrd', $arr);

### mod/parse_url.php

    Hook::callAll("parse_link", $arr);

### src/Module/Delegation.php

    Hook::callAll('home_init', $ret);

### mod/acl.php

    Hook::callAll('acl_lookup_end', $results);

### mod/network.php

    Hook::callAll('network_content_init', $arr);
    Hook::callAll('network_tabs', $arr);

### mod/friendica.php

    Hook::callAll('about_hook', $o);

### mod/profiles.php

    Hook::callAll('profile_post', $_POST);
    Hook::callAll('profile_edit', $arr);

### mod/settings.php

    Hook::callAll('addon_settings_post', $_POST);
    Hook::callAll('connector_settings_post', $_POST);
    Hook::callAll('display_settings_post', $_POST);
    Hook::callAll('addon_settings', $settings_addons);
    Hook::callAll('connector_settings', $settings_connectors);
    Hook::callAll('display_settings', $o);

### mod/photos.php

    Hook::callAll('photo_post_init', $_POST);
    Hook::callAll('photo_post_file', $ret);
    Hook::callAll('photo_post_end', $foo);
    Hook::callAll('photo_post_end', $foo);
    Hook::callAll('photo_post_end', $foo);
    Hook::callAll('photo_post_end', $foo);
    Hook::callAll('photo_post_end', intval($item_id));
    Hook::callAll('photo_upload_form', $ret);

### mod/profile.php

    Hook::callAll('profile_advanced', $o);

### mod/home.php

    Hook::callAll('home_init', $ret);
    Hook::callAll("home_content", $content);

### mod/contacts.php

    Hook::callAll('contact_edit_post', $_POST);
    Hook::callAll('contact_edit', $arr);

### mod/tagger.php

    Hook::callAll('post_local_end', $arr);

### mod/uexport.php

    Hook::callAll('uexport_options', $options);

### mod/register.php

    Hook::callAll('register_post', $arr);
    Hook::callAll('register_form', $arr);

### mod/item.php

    Hook::callAll('post_local_start', $_REQUEST);
    Hook::callAll('post_local', $datarray);
    Hook::callAll('post_local_end', $datarray);

### src/Network/FKOAuth1.php

    Hook::callAll('logged_in', $a->user);

### src/Render/FriendicaSmartyEngine.php

    Hook::callAll("template_vars", $arr);

### src/Model/Item.php

    Hook::callAll('post_local', $item);
    Hook::callAll('post_remote', $item);
    Hook::callAll('post_local_end', $posted_item);
    Hook::callAll('post_remote_end', $posted_item);
    Hook::callAll('tagged', $arr);
    Hook::callAll('post_local_end', $new_item);
    Hook::callAll('put_item_in_cache', $hook_data);
    Hook::callAll('prepare_body_init', $item);
    Hook::callAll('prepare_body_content_filter', $hook_data);
    Hook::callAll('prepare_body', $hook_data);
    Hook::callAll('prepare_body_final', $hook_data);

### src/Model/Contact.php

    Hook::callAll('contact_photo_menu', $args);
    Hook::callAll('follow', $arr);

### src/Model/Profile.php

    Hook::callAll('profile_sidebar_enter', $profile);
    Hook::callAll('profile_sidebar', $arr);
    Hook::callAll('profile_tabs', $arr);
    Hook::callAll('zrl_init', $arr);

### src/Model/Event.php

    Hook::callAll('event_updated', $event['id']);
    Hook::callAll("event_created", $event['id']);

### src/Model/User.php

    Hook::callAll('register_account', $uid);
    Hook::callAll('remove_user', $user);
    
### src/Content/ContactBlock.php

    Hook::callAll('contact_block_end', $arr);

### src/Content/Text/BBCode.php

    Hook::callAll('bbcode', $text);
    Hook::callAll('bb2diaspora', $text);

### src/Content/Text/HTML.php

    Hook::callAll('html2bbcode', $message);

### src/Content/Smilies.php

    Hook::callAll('smilie', $params);

### src/Content/Feature.php

    Hook::callAll('isEnabled', $arr);
    Hook::callAll('get', $arr);

### src/Content/ContactSelector.php

    Hook::callAll('network_to_name', $nets);

### src/Content/OEmbed.php

    Hook::callAll('oembed_fetch_url', $embedurl, $j);

### src/Content/Nav.php

    Hook::callAll('page_header', DI::page()['nav']);
    Hook::callAll('nav_info', $nav);

### src/Core/Authentication.php

    Hook::callAll('logged_in', $a->user);

### src/Core/Protocol.php

    Hook::callAll('support_follow', $hook_data);
    Hook::callAll('support_revoke_follow', $hook_data);
    Hook::callAll('unfollow', $hook_data);
    Hook::callAll('revoke_follow', $hook_data);
    Hook::callAll('block', $hook_data);
    Hook::callAll('unblock', $hook_data);

### src/Core/Logger/Factory.php

    Hook::callAll('logger_instance', $data);

### src/Core/StorageManager

    Hook::callAll('storage_instance', $data);
    Hook::callAll('storage_config', $data);

### src/Module/Notifications/Ping.php

    Hook::callAll('network_ping', $arr);

### src/Module/PermissionTooltip.php

    Hook::callAll('lockview_content', $item);

### src/Module/Post/Edit.php

    Hook::callAll('jot_tool', $jotplugins);

### src/Worker/Directory.php

    Hook::callAll('globaldir_update', $arr);

### src/Worker/Notifier.php

    Hook::callAll('notifier_end', $target_item);

### src/Module/Login.php

    Hook::callAll('authenticate', $addon_auth);
    Hook::callAll('login_hook', $o);

### src/Module/Logout.php

    Hook::callAll("logging_out");

### src/Object/Post.php

    Hook::callAll('render_location', $locate);
    Hook::callAll('display_item', $arr);

### src/Core/ACL.php

    Hook::callAll('contact_select_options', $x);
    Hook::callAll($a->module.'_pre_'.$selname, $arr);
    Hook::callAll($a->module.'_post_'.$selname, $o);
    Hook::callAll($a->module.'_pre_'.$selname, $arr);
    Hook::callAll($a->module.'_post_'.$selname, $o);
    Hook::callAll('jot_networks', $jotnets);

### src/Core/Authentication.php

    Hook::callAll('logged_in', $a->user);

### src/Core/Hook.php

    self::callSingle(self::getApp(), 'hook_fork', $fork_hook, $hookdata);

### src/Core/Worker.php

    Hook::callAll("proc_run", $arr);

### src/Util/Emailer.php

    Hook::callAll('emailer_send_prepare', $email);
    Hook::callAll("emailer_send", $hookdata);

### src/Util/Map.php

    Hook::callAll('generate_map', $arr);
    Hook::callAll('generate_named_map', $arr);
    Hook::callAll('Map::getCoordinates', $arr);

### src/Util/Network.php

    Hook::callAll('avatar_lookup', $avatar);

### src/Util/ParseUrl.php

    Hook::callAll("getsiteinfo", $siteinfo);

### src/Protocol/DFRN.php

    Hook::callAll('atom_feed_end', $atom);
    Hook::callAll('atom_feed_end', $atom);

### src/Protocol/Email.php

    Hook::callAll('email_getmessage', $message);
    Hook::callAll('email_getmessage_end', $ret);
