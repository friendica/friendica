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

    Addon::registerHook($hookname, $file, $function);

$hookname ist ein String und entspricht einem bekannten Friendica-Hook.

$file steht für den Pfadnamen, der relativ zum Top-Level-Friendicaverzeichnis liegt.
Das *sollte* "addon/addon_name/addon_name.php' sein.

$function ist ein String und der Name der Funktion, die ausgeführt wird, wenn der Hook aufgerufen wird.

Argumente
---

Deine Hook-Callback-Funktion wird mit mindestens einem und bis zu zwei Argumenten aufgerufen

    function myhook_function(App $a, &$b) {

    }

Wenn du Änderungen an den aufgerufenen Daten vornehmen willst, musst du diese als Referenzvariable (mit "&") während der Funktionsdeklaration deklarieren.

$a ist die Friendica "App"-Klasse, die eine Menge an Informationen über den aktuellen Friendica-Status beinhaltet, u.a. welche Module genutzt werden, Konfigurationsinformationen, Inhalte der Seite zum Zeitpunkt des Hook-Aufrufs.
Es ist empfohlen, diese Funktion "$a" zu nennen, um seine Nutzung an den Gebrauch an anderer Stelle anzugleichen.

$b kann frei benannt werden.
Diese Information ist speziell auf den Hook bezogen, der aktuell bearbeitet wird, und beinhaltet normalerweise Daten, die du sofort nutzen, anzeigen oder bearbeiten kannst.
Achte darauf, diese mit "&" zu deklarieren, wenn du sie bearbeiten willst.


Module
---

Addons können auch als "Module" agieren und alle Seitenanfragen für eine bestimte URL abfangen.
Um ein Addon als Modul zu nutzen, ist es nötig, die Funktion "addon_name_module()" zu definieren, die keine Argumente benötigt und nichts weiter machen muss.

Wenn diese Funktion existiert, wirst du nun alle Seitenanfragen für "http://example.com/addon_name" erhalten - mit allen URL-Komponenten als zusätzliche Argumente.
Diese werden in ein Array $a->argv geparst und stimmen mit $a->argc überein, wobei sie die Anzahl der URL-Komponenten abbilden.
So würde http://example.com/addon/arg1/arg2 nach einem Modul "addon" suchen und seiner Modulfunktion die $a-App-Strukur übergeben (dies ist für viele Komponenten verfügbar). Das umfasst:

    $a->argc = 3
    $a->argv = array(0 => 'addon', 1 => 'arg1', 2 => 'arg2');

Deine Modulfunktionen umfassen oft die Funktion addon_name_content(App $a), welche den Seiteninhalt definiert und zurückgibt.
Sie können auch addon_name_post(App $a) umfassen, welches vor der content-Funktion aufgerufen wird und normalerweise die Resultate der POST-Formulare handhabt.
Du kannst ebenso addon_name_init(App $a) nutzen, was oft frühzeitig aufgerufen wird und das Modul initialisert.


Derzeitige Hooks
---

**'authenticate'** - wird aufgerufen, wenn sich der User einloggt.
    $b ist ein Array
        'username' => der übertragene Nutzername
        'password' => das übertragene Passwort
        'authenticated' => setze das auf einen anderen Wert als "0", damit der User sich authentifiziert
        'user_record' => die erfolgreiche Authentifizierung muss auch einen gültigen Nutzereintrag aus der Datenbank zurückgeben

**'logged_in'** - wird aufgerufen, sobald ein Nutzer sich erfolgreich angemeldet hat.
    $b beinhaltet den $a->Nutzer-Array


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

**'settings_form'** - wird aufgerufen, wenn die HTML-Ausgabe für die Einstellungsseite generiert wird.
    $b ist die HTML-Ausgabe (String) der Einstellungsseite vor dem finalen "</form>"-Tag.

**'settings_post'** - wird aufgerufen, wenn die Einstellungsseiten geladen werden.
    $b ist der $_POST-Array

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
    (Die Details des Profil-Arrays sind in $a->profile)

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

index.php:	Addon::callHooks('init_1');
index.php:	Addon::callHooks('app_menu', $arr);
index.php:	Addon::callHooks('page_content_top', $a->page['content']);
index.php:	Addon::callHooks($a->module.'_mod_init', $placeholder);
index.php:	Addon::callHooks($a->module.'_mod_init', $placeholder);
index.php:	Addon::callHooks($a->module.'_mod_post', $_POST);
index.php:	Addon::callHooks($a->module.'_mod_afterpost', $placeholder);
index.php:	Addon::callHooks($a->module.'_mod_content', $arr);
index.php:	Addon::callHooks($a->module.'_mod_aftercontent', $arr);
index.php:	Addon::callHooks('page_end', $a->page['content']);

include/api.php:	Addon::callHooks('logged_in', $a->user);
include/api.php:	Addon::callHooks('authenticate', $addon_auth);
include/api.php:	Addon::callHooks('logged_in', $a->user);

include/enotify.php:	Addon::callHooks('enotify', $h);
include/enotify.php:	Addon::callHooks('enotify_store', $datarray);
include/enotify.php:	Addon::callHooks('enotify_mail', $datarray);
include/enotify.php:	Addon::callHooks('check_item_notification', $notification_data);

include/conversation.php:	Addon::callHooks('conversation_start', $cb);
include/conversation.php:	Addon::callHooks('render_location', $locate);
include/conversation.php:	Addon::callHooks('display_item', $arr);
include/conversation.php:	Addon::callHooks('display_item', $arr);
include/conversation.php:	Addon::callHooks('item_photo_menu', $args);
include/conversation.php:	Addon::callHooks('jot_tool', $jotplugins);

include/security.php:	Addon::callHooks('logged_in', $a->user);

include/text.php:	Addon::callHooks('contact_block_end', $arr);
include/text.php:	Addon::callHooks('poke_verbs', $arr);
include/text.php:	Addon::callHooks('prepare_body_init', $item);
include/text.php:	Addon::callHooks('prepare_body_content_filter', $hook_data);
include/text.php:	Addon::callHooks('prepare_body', $hook_data);
include/text.php:	Addon::callHooks('prepare_body_final', $hook_data);

include/items.php:	Addon::callHooks('page_info_data', $data);

mod/directory.php:	Addon::callHooks('directory_item', $arr);

mod/xrd.php:	Addon::callHooks('personal_xrd', $arr);

mod/ping.php:	Addon::callHooks('network_ping', $arr);

mod/parse_url.php:	Addon::callHooks("parse_link", $arr);

mod/manage.php:	Addon::callHooks('home_init', $ret);

mod/acl.php:	Addon::callHooks('acl_lookup_end', $results);

mod/network.php:	Addon::callHooks('network_content_init', $arr);
mod/network.php:	Addon::callHooks('network_tabs', $arr);

mod/friendica.php:	Addon::callHooks('about_hook', $o);
mod/subthread.php:	Addon::callHooks('post_local_end', $arr);

mod/profiles.php:	Addon::callHooks('profile_post', $_POST);
mod/profiles.php:	Addon::callHooks('profile_edit', $arr);

mod/settings.php:	Addon::callHooks('addon_settings_post', $_POST);
mod/settings.php:	Addon::callHooks('connector_settings_post', $_POST);
mod/settings.php:	Addon::callHooks('display_settings_post', $_POST);
mod/settings.php:	Addon::callHooks('settings_post', $_POST);
mod/settings.php:	Addon::callHooks('addon_settings', $settings_addons);
mod/settings.php:	Addon::callHooks('connector_settings', $settings_connectors);
mod/settings.php:	Addon::callHooks('display_settings', $o);
mod/settings.php:	Addon::callHooks('settings_form', $o);

mod/photos.php:	Addon::callHooks('photo_post_init', $_POST);
mod/photos.php:	Addon::callHooks('photo_post_file', $ret);
mod/photos.php:	Addon::callHooks('photo_post_end', $foo);
mod/photos.php:	Addon::callHooks('photo_post_end', $foo);
mod/photos.php:	Addon::callHooks('photo_post_end', $foo);
mod/photos.php:	Addon::callHooks('photo_post_end', $foo);
mod/photos.php:	Addon::callHooks('photo_post_end', intval($item_id));
mod/photos.php:	Addon::callHooks('photo_upload_form', $ret);

mod/profile.php:	Addon::callHooks('profile_advanced', $o);

mod/home.php:	Addon::callHooks('home_init', $ret);
mod/home.php:	Addon::callHooks("home_content", $content);

mod/poke.php:	Addon::callHooks('post_local_end', $arr);

mod/contacts.php:	Addon::callHooks('contact_edit_post', $_POST);
mod/contacts.php:	Addon::callHooks('contact_edit', $arr);

mod/tagger.php:	Addon::callHooks('post_local_end', $arr);

mod/lockview.php:	Addon::callHooks('lockview_content', $item);

mod/uexport.php:	Addon::callHooks('uexport_options', $options);

mod/register.php:	Addon::callHooks('register_post', $arr);
mod/register.php:	Addon::callHooks('register_form', $arr);

mod/item.php:	Addon::callHooks('post_local_start', $_REQUEST);
mod/item.php:	Addon::callHooks('post_local', $datarray);
mod/item.php:	Addon::callHooks('post_local_end', $datarray);

mod/editpost.php:	Addon::callHooks('jot_tool', $jotplugins);

src/Network/FKOAuth1.php:	Addon::callHooks('logged_in', $a->user);

src/Render/FriendicaSmartyEngine.php:	Addon::callHooks("template_vars", $arr);

src/Model/Item.php:	Addon::callHooks('post_local', $item);
src/Model/Item.php:	Addon::callHooks('post_remote', $item);
src/Model/Item.php:	Addon::callHooks('post_local_end', $posted_item);
src/Model/Item.php:	Addon::callHooks('post_remote_end', $posted_item);
src/Model/Item.php:	Addon::callHooks('tagged', $arr);
src/Model/Item.php:	Addon::callHooks('post_local_end', $new_item);

src/Model/Contact.php:	Addon::callHooks('contact_photo_menu', $args);
src/Model/Contact.php:	Addon::callHooks('follow', $arr);

src/Model/Profile.php:	Addon::callHooks('profile_sidebar_enter', $profile);
src/Model/Profile.php:	Addon::callHooks('profile_sidebar', $arr);
src/Model/Profile.php:	Addon::callHooks('profile_tabs', $arr);
src/Model/Profile.php:	Addon::callHooks('zrl_init', $arr);

src/Model/Event.php:	Addon::callHooks('event_updated', $event['id']);
src/Model/Event.php:	Addon::callHooks("event_created", $event['id']);

src/Model/User.php:	Addon::callHooks('register_account', $uid);
src/Model/User.php:	Addon::callHooks('remove_user', $user);

src/Content/Text/BBCode.php:	Addon::callHooks('bbcode', $text);
src/Content/Text/BBCode.php:	Addon::callHooks('bb2diaspora', $text);

src/Content/Text/HTML.php:	Addon::callHooks('html2bbcode', $message);

src/Content/Smilies.php:	Addon::callHooks('smilie', $params);

src/Content/Feature.php:	Addon::callHooks('isEnabled', $arr);
src/Content/Feature.php:	Addon::callHooks('get', $arr);

src/Content/ContactSelector.php:	Addon::callHooks('network_to_name', $nets);
src/Content/ContactSelector.php:	Addon::callHooks('gender_selector', $select);
src/Content/ContactSelector.php:	Addon::callHooks('sexpref_selector', $select);
src/Content/ContactSelector.php:	Addon::callHooks('marital_selector', $select);

src/Content/OEmbed.php:	Addon::callHooks('oembed_fetch_url', $embedurl, $j);

src/Content/Nav.php:	Addon::callHooks('page_header', $a->page['nav']);
src/Content/Nav.php:	Addon::callHooks('nav_info', $nav);

src/Worker/Directory.php:	Addon::callHooks('globaldir_update', $arr);
src/Worker/Notifier.php:	Addon::callHooks('notifier_end', $target_item);
src/Worker/Queue.php:	Addon::callHooks('queue_predeliver', $r);
src/Worker/Queue.php:	Addon::callHooks('queue_deliver', $params);

src/Module/Login.php:	Addon::callHooks('authenticate', $addon_auth);
src/Module/Login.php:	Addon::callHooks('login_hook', $o);
src/Module/Logout.php:	Addon::callHooks("logging_out");

src/Object/Post.php:	Addon::callHooks('render_location', $locate);
src/Object/Post.php:	Addon::callHooks('display_item', $arr);

src/Core/ACL.php:	Addon::callHooks('contact_select_options', $x);
src/Core/ACL.php:	Addon::callHooks($a->module.'_pre_'.$selname, $arr);
src/Core/ACL.php:	Addon::callHooks($a->module.'_post_'.$selname, $o);
src/Core/ACL.php:	Addon::callHooks($a->module.'_pre_'.$selname, $arr);
src/Core/ACL.php:	Addon::callHooks($a->module.'_post_'.$selname, $o);
src/Core/ACL.php:	Addon::callHooks('jot_networks', $jotnets);

src/Core/Worker.php:	Addon::callHooks("proc_run", $arr);

src/Util/Emailer.php:	Addon::callHooks('emailer_send_prepare', $params);
src/Util/Emailer.php:	Addon::callHooks("emailer_send", $hookdata);

src/Util/Map.php:	Addon::callHooks('generate_map', $arr);
src/Util/Map.php:	Addon::callHooks('generate_named_map', $arr);
src/Util/Map.php:	Addon::callHooks('Map::getCoordinates', $arr);

src/Util/Network.php:	Addon::callHooks('avatar_lookup', $avatar);

src/Util/ParseUrl.php:	Addon::callHooks("getsiteinfo", $siteinfo);

src/Protocol/DFRN.php:	Addon::callHooks('atom_feed_end', $atom);
src/Protocol/DFRN.php:	Addon::callHooks('atom_feed_end', $atom);
