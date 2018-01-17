Friendica Addon/Plugin-Entwicklung
==============

* [Zur Startseite der Hilfe](help)

Bitte schau dir das Beispiel-Addon "randplace" für ein funktionierendes Beispiel für manche der hier aufgeführten Funktionen an.
Das Facebook-Addon bietet ein Beispiel dafür, die "addon"- und "module"-Funktion gemeinsam zu integrieren.
Addons arbeiten, indem sie Event Hooks abfangen.
Module arbeiten, indem bestimmte Seitenanfragen (durch den URL-Pfad) abgefangen werden.

Plugin-Namen können keine Leerstellen oder andere Interpunktionen enthalten und werden als Datei- und Funktionsnamen genutzt.
Du kannst einen lesbaren Namen im Kommentarblock eintragen.
Jedes Addon muss beides beinhalten - eine Installations- und eine Deinstallationsfunktion, die auf dem Addon-/Plugin-Namen basieren; z.B. "plugin1name_install()".
Diese beiden Funktionen haben keine Argumente und sind dafür verantwortlich, Event Hooks zu registrieren und abzumelden (unregistering), die dein Plugin benötigt.
Die Installations- und Deinstallationsfunktionfunktionen werden auch ausgeführt (z.B. neu installiert), wenn sich das Plugin nach der Installation ändert - somit sollte deine Deinstallationsfunktion keine Daten zerstört und deine Installationsfunktion sollte bestehende Daten berücksichtigen.
Zukünftige Extensions werden möglicherweise "Setup" und "Entfernen" anbieten.

Plugins sollten einen Kommentarblock mit den folgenden vier Parametern enthalten:

    /*
     * Name: My Great Plugin
     * Description: This is what my plugin does. It's really cool.
     * Version: 1.0
     * Author: John Q. Public <john@myfriendicasite.com>
     */

Registriere deine Plugin-Hooks während der Installation.

    Addon::registerHook($hookname, $file, $function);

$hookname ist ein String und entspricht einem bekannten Friendica-Hook.

$file steht für den Pfadnamen, der relativ zum Top-Level-Friendicaverzeichnis liegt.
Das *sollte* "addon/plugin_name/plugin_name.php' sein.

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

Plugins/Addons können auch als "Module" agieren und alle Seitenanfragen für eine bestimte URL abfangen.
Um ein Plugin als Modul zu nutzen, ist es nötig, die Funktion "plugin_name_module()" zu definieren, die keine Argumente benötigt und nichts weiter machen muss.

Wenn diese Funktion existiert, wirst du nun alle Seitenanfragen für "http://example.com/plugin_name" erhalten - mit allen URL-Komponenten als zusätzliche Argumente.
Diese werden in ein Array $a->argv geparst und stimmen mit $a->argc überein, wobei sie die Anzahl der URL-Komponenten abbilden.
So würde http://example.com/plugin/arg1/arg2 nach einem Modul "plugin" suchen und seiner Modulfunktion die $a-App-Strukur übergeben (dies ist für viele Komponenten verfügbar). Das umfasst:

    $a->argc = 3
    $a->argv = array(0 => 'plugin', 1 => 'arg1', 2 => 'arg2');

Deine Modulfunktionen umfassen oft die Funktion plugin_name_content(App $a), welche den Seiteninhalt definiert und zurückgibt.
Sie können auch plugin_name_post(App $a) umfassen, welches vor der content-Funktion aufgerufen wird und normalerweise die Resultate der POST-Formulare handhabt.
Du kannst ebenso plugin_name_init(App $a) nutzen, was oft frühzeitig aufgerufen wird und das Modul initialisert.


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

**'plugin_settings'** - wird aufgerufen, wenn die HTML-Ausgabe der Addon-Einstellungsseite generiert wird.
    $b ist die HTML-Ausgabe (String) der Addon-Einstellungsseite vor dem finalen "</form>"-Tag.

**'plugin_settings_post'** - wird aufgerufen, wenn die Addon-Einstellungsseite geladen wird.
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

Eine komplette Liste aller Hook-Callbacks mit den zugehörigen Dateien (am 14-Feb-2012 generiert): Bitte schau in die Quellcodes für Details zu Hooks, die oben nicht dokumentiert sind.

boot.php:			Addon::callHooks('login_hook',$o);

boot.php:			Addon::callHooks('profile_sidebar_enter', $profile);

boot.php:			Addon::callHooks('profile_sidebar', $arr);

boot.php:			Addon::callHooks("proc_run", $arr);

include/contact_selectors.php:	Addon::callHooks('network_to_name', $nets);

include/api.php:		Addon::callHooks('logged_in', $a->user);

include/api.php:		Addon::callHooks('logged_in', $a->user);

include/queue.php:		Addon::callHooks('queue_predeliver', $a, $r);

include/queue.php:		Addon::callHooks('queue_deliver', $a, $params);

include/text.php:		Addon::callHooks('contact_block_end', $arr);

include/text.php:		Addon::callHooks('smilie', $s);

include/text.php:		Addon::callHooks('prepare_body_init', $item);

include/text.php:		Addon::callHooks('prepare_body', $prep_arr);

include/text.php:		Addon::callHooks('prepare_body_final', $prep_arr);

include/nav.php:		Addon::callHooks('page_header', $a->page['nav']);

include/auth.php:		Addon::callHooks('authenticate', $addon_auth);

include/bbcode.php:		Addon::callHooks('bbcode',$Text);

include/oauth.php:		Addon::callHooks('logged_in', $a->user);

include/acl_selectors.php:	Addon::callHooks($a->module . '_pre_' . $selname, $arr);

include/acl_selectors.php:	Addon::callHooks($a->module . '_post_' . $selname, $o);

include/acl_selectors.php:	Addon::callHooks('contact_select_options', $x);

include/acl_selectors.php:	Addon::callHooks($a->module . '_pre_' . $selname, $arr);

include/acl_selectors.php:	Addon::callHooks($a->module . '_post_' . $selname, $o);

include/acl_selectors.php:	Addon::callHooks($a->module . '_pre_' . $selname, $arr);

include/acl_selectors.php:	Addon::callHooks($a->module . '_post_' . $selname, $o);

include/notifier.php:		Addon::callHooks('notifier_normal',$target_item);

include/notifier.php:		Addon::callHooks('notifier_end',$target_item);

include/items.php:		Addon::callHooks('atom_feed', $atom);

include/items.php:		Addon::callHooks('atom_feed_end', $atom);

include/items.php:		Addon::callHooks('atom_feed_end', $atom);

include/items.php:		Addon::callHooks('parse_atom', $arr);

include/items.php:		Addon::callHooks('post_remote',$arr);

include/items.php:		Addon::callHooks('atom_author', $o);

include/items.php:		Addon::callHooks('atom_entry', $o);

include/bb2diaspora.php:	Addon::callHooks('bb2diaspora',$Text);

include/cronhooks.php:		Addon::callHooks('cron', $d);

include/security.php:		Addon::callHooks('logged_in', $a->user);

include/html2bbcode.php:	Addon::callHooks('html2bbcode', $text);

include/Contact.php:		Addon::callHooks('remove_user',$r[0]);

include/Contact.php:		Addon::callHooks('contact_photo_menu', $args);

include/conversation.php:	Addon::callHooks('conversation_start',$cb);

include/conversation.php:	Addon::callHooks('render_location',$locate);

include/conversation.php:	Addon::callHooks('display_item', $arr);

include/conversation.php:	Addon::callHooks('render_location',$locate);

include/conversation.php:	Addon::callHooks('display_item', $arr);

include/conversation.php:	Addon::callHooks('item_photo_menu', $args);

include/conversation.php:	Addon::callHooks('jot_tool', $jotplugins);

include/conversation.php:	Addon::callHooks('jot_networks', $jotnets);

include/plugin.php:		if(! function_exists('call_hooks')) {

include/plugin.php:function 	Addon::callHooks($name, &$data = null) {

index.php:			Addon::callHooks('init_1');

index.php:			Addon::callHooks('app_menu', $arr);

index.php:			Addon::callHooks('page_end', $a->page['content']);

mod/photos.php:			Addon::callHooks('photo_post_init', $_POST);

mod/photos.php:			Addon::callHooks('photo_post_file',$ret);

mod/photos.php:			Addon::callHooks('photo_post_end',$foo);

mod/photos.php:			Addon::callHooks('photo_post_end',$foo);

mod/photos.php:			Addon::callHooks('photo_post_end',$foo);

mod/photos.php:			Addon::callHooks('photo_post_end',intval($item_id));

mod/photos.php:			Addon::callHooks('photo_upload_form',$ret);

mod/friendica.php:		Addon::callHooks('about_hook', $o);

mod/editpost.php:		Addon::callHooks('jot_tool', $jotplugins);

mod/editpost.php:		Addon::callHooks('jot_networks', $jotnets);

mod/parse_url.php:		Addon::callHooks('parse_link', $arr);

mod/home.php:			Addon::callHooks('home_init',$ret);

mod/home.php:			Addon::callHooks("home_content",$o);

mod/contacts.php:		Addon::callHooks('contact_edit_post', $_POST);

mod/contacts.php:		Addon::callHooks('contact_edit', $arr);

mod/settings.php:		Addon::callHooks('plugin_settings_post', $_POST);

mod/settings.php:		Addon::callHooks('connector_settings_post', $_POST);

mod/settings.php:		Addon::callHooks('settings_post', $_POST);

mod/settings.php:		Addon::callHooks('plugin_settings', $settings_addons);

mod/settings.php:		Addon::callHooks('connector_settings', $settings_connectors);

mod/settings.php:		Addon::callHooks('settings_form',$o);

mod/register.php:		Addon::callHooks('register_account', $newuid);

mod/like.php:			Addon::callHooks('post_local_end', $arr);

mod/xrd.php:			Addon::callHooks('personal_xrd', $arr);

mod/item.php:			Addon::callHooks('post_local_start', $_REQUEST);

mod/item.php:			Addon::callHooks('post_local',$datarray);

mod/item.php:			Addon::callHooks('post_local_end', $datarray);

mod/profile.php:		Addon::callHooks('profile_advanced',$o);

mod/profiles.php:		Addon::callHooks('profile_post', $_POST);

mod/profiles.php:		Addon::callHooks('profile_edit', $arr);

mod/tagger.php:			Addon::callHooks('post_local_end', $arr);

mod/cb.php:			Addon::callHooks('cb_init');

mod/cb.php:			Addon::callHooks('cb_post', $_POST);

mod/cb.php:			Addon::callHooks('cb_afterpost');

mod/cb.php:			Addon::callHooks('cb_content', $o);

mod/directory.php:		Addon::callHooks('directory_item', $arr);
