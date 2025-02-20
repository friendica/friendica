<?php

if(! function_exists("string_plural_select_cs")) {
function string_plural_select_cs($n){
	$n = intval($n);
	if (($n == 1 && $n % 1 == 0)) { return 0; } else if (($n >= 2 && $n <= 4 && $n % 1 == 0)) { return 1; } else if (($n % 1 != 0 )) { return 2; } else  { return 3; }
}}
$a->strings['Unable to locate original post.'] = 'Nelze nalézt původní příspěvek.';
$a->strings['Empty post discarded.'] = 'Prázdný příspěvek odstraněn.';
$a->strings['Item not found.'] = 'Položka nenalezena.';
$a->strings['Permission denied.'] = 'Přístup odmítnut.';
$a->strings['No valid account found.'] = 'Nenalezen žádný platný účet.';
$a->strings['Password reset request issued. Check your email.'] = 'Požadavek o obnovení hesla vyřízen. Zkontrolujte Vaši e-mailovou schránku.';
$a->strings['
		Dear %1$s,
			A request was recently received at "%2$s" to reset your account
		password. In order to confirm this request, please select the verification link
		below or paste it into your web browser address bar.

		If you did NOT request this change, please DO NOT follow the link
		provided and ignore and/or delete this email, the request will expire shortly.

		Your password will not be changed unless we can verify that you
		issued this request.'] = '
		Vážený/á %1$s,
			Před nedávnem jsme obdrželi na „%2$s“ požadavek o obnovení
		hesla k vašemu účtu. Pro potvrzení tohoto požadavku, prosím klikněte na odkaz
		pro ověření dole, nebo ho zkopírujte do adresního řádku vašeho prohlížeče.

		Pokud jste o tuto změnu NEPOŽÁDAL/A, prosím NEKLIKEJTE na tento odkaz
		a ignorujte a/nebo smažte tento e-mail. Platnost požadavku brzy vyprší.

		Vaše heslo nebude změněno, dokud nedokážeme ověřit, že jste tento
		požadavek nevydal/a vy.';
$a->strings['
		Follow this link soon to verify your identity:

		%1$s

		You will then receive a follow-up message containing the new password.
		You may change that password from your account settings page after logging in.

		The login details are as follows:

		Site Location:	%2$s
		Login Name:	%3$s'] = '
		Klikněte na tento odkaz brzy pro ověření vaší identity:

		%1$s

		Obdržíte poté následnou zprávu obsahující nové heslo.
		Po přihlášení můžete toto heslo změnit na stránce nastavení vašeho účtu.

		Zde jsou vaše přihlašovací detaily:

		Adresa stránky:		%2$s
		Přihlašovací jméno:	%3$s';
$a->strings['Password reset requested at %s'] = 'Na %s bylo požádáno o obnovení hesla';
$a->strings['Request could not be verified. (You may have previously submitted it.) Password reset failed.'] = 'Požadavek nemohl být ověřen. (Možná jste jej odeslal/a již dříve.) Obnovení hesla se nezdařilo.';
$a->strings['Request has expired, please make a new one.'] = 'Platnost požadavku vypršela, prosím vytvořte nový.';
$a->strings['Forgot your Password?'] = 'Zapomněl/a jste heslo?';
$a->strings['Enter your email address and submit to have your password reset. Then check your email for further instructions.'] = 'Zadejte svůj e-mailovou adresu a odešlete pro obnovení vašeho hesla. Poté zkontrolujte svůj e-mail pro další instrukce.';
$a->strings['Nickname or Email: '] = 'Přezdívka nebo e-mail: ';
$a->strings['Reset'] = 'Obnovit';
$a->strings['Password Reset'] = 'Obnovit heslo';
$a->strings['Your password has been reset as requested.'] = 'Vaše heslo bylo na vaše přání obnoveno.';
$a->strings['Your new password is'] = 'Někdo vám napsal na vaši profilovou stránku';
$a->strings['Save or copy your new password - and then'] = 'Uložte si nebo zkopírujte nové heslo - a pak';
$a->strings['click here to login'] = 'klikněte zde pro přihlášení';
$a->strings['Your password may be changed from the <em>Settings</em> page after successful login.'] = 'Nezdá se, že by to bylo vaše celé jméno (křestní jméno a příjmení).';
$a->strings['
			Dear %1$s,
				Your password has been changed as requested. Please retain this
			information for your records (or change your password immediately to
			something that you will remember).
		'] = '
			Vážený/á %1$s,
				Vaše heslo bylo změněno, jak jste požádal/a. Prosím uchovejte
			tyto informace pro vaše záznamy (nebo si ihned změňte heslo na něco,
			co si zapamatujete).
		';
$a->strings['
			Your login details are as follows:

			Site Location:	%1$s
			Login Name:	%2$s
			Password:	%3$s

			You may change that password from your account settings page after logging in.
		'] = '
			Zde jsou vaše přihlašovací detaily:

			Adresa stránky:		%1$s
			Přihlašovací jméno:	%2$s
			Heslo:			%3$s

			Toto heslo si po přihlášení můžete změnit na stránce nastavení Vašeho účtu.
		';
$a->strings['Your password has been changed at %s'] = 'Vaše heslo bylo změněno na %s';
$a->strings['New Message'] = 'Nová zpráva';
$a->strings['No recipient selected.'] = 'Nevybrán příjemce.';
$a->strings['Unable to locate contact information.'] = 'Nepodařilo se najít kontaktní informace.';
$a->strings['Message could not be sent.'] = 'Zprávu se nepodařilo odeslat.';
$a->strings['Message collection failure.'] = 'Sběr zpráv selhal.';
$a->strings['Discard'] = 'Odstranit';
$a->strings['Messages'] = 'Zprávy';
$a->strings['Conversation not found.'] = 'Konverzace nenalezena.';
$a->strings['Please enter a link URL:'] = 'Zadejte prosím URL odkaz:';
$a->strings['Send Private Message'] = 'Odeslat soukromou zprávu';
$a->strings['To:'] = 'Adresát:';
$a->strings['Subject:'] = 'Předmět:';
$a->strings['Your message:'] = 'Vaše zpráva:';
$a->strings['Upload photo'] = 'Nahrát fotku';
$a->strings['Insert web link'] = 'Vložit webový odkaz';
$a->strings['Please wait'] = 'Čekejte prosím';
$a->strings['Submit'] = 'Odeslat';
$a->strings['No messages.'] = 'Žádné zprávy.';
$a->strings['Message not available.'] = 'Zpráva není k dispozici.';
$a->strings['Delete message'] = 'Smazat zprávu';
$a->strings['D, d M Y - g:i A'] = 'D d. M Y - g:i A';
$a->strings['Delete conversation'] = 'Odstranit konverzaci';
$a->strings['No secure communications available. You <strong>may</strong> be able to respond from the sender\'s profile page.'] = 'Není k dispozici zabezpečená komunikace. <strong>Možná</strong> budete schopen/na reagovat z odesílatelovy profilové stránky.';
$a->strings['Send Reply'] = 'Poslat odpověď';
$a->strings['Unknown sender - %s'] = 'Neznámý odesilatel - %s';
$a->strings['You and %s'] = 'Vy a %s';
$a->strings['%s and You'] = '%s a vy';
$a->strings['%d message'] = [
	0 => '%d zpráva',
	1 => '%d zprávy',
	2 => '%d zprávy',
	3 => '%d zpráv',
];
$a->strings['Personal Notes'] = 'Osobní poznámky';
$a->strings['Save'] = 'Uložit';
$a->strings['User not found.'] = 'Uživatel nenalezen.';
$a->strings['Photo Albums'] = 'Fotoalba';
$a->strings['Recent Photos'] = 'Nedávné fotky';
$a->strings['Upload New Photos'] = 'Nahrát nové fotky';
$a->strings['everybody'] = 'Žádost o připojení selhala nebo byla zrušena.';
$a->strings['Contact information unavailable'] = 'Kontakt byl zablokován';
$a->strings['Album not found.'] = 'Album nenalezeno.';
$a->strings['Album successfully deleted'] = 'Album úspěšně smazáno';
$a->strings['Album was empty.'] = 'Album bylo prázdné.';
$a->strings['a photo'] = 'fotce';
$a->strings['%1$s was tagged in %2$s by %3$s'] = '%1$s byl označen ve %2$s uživatelem %3$s';
$a->strings['Public access denied.'] = 'Veřejný přístup odepřen.';
$a->strings['No photos selected'] = 'Není vybrána žádná fotka';
$a->strings['Upload Photos'] = 'Nahrát fotky';
$a->strings['New album name: '] = 'Název nového alba: ';
$a->strings['or select existing album:'] = 'nebo si vyberte existující album:';
$a->strings['Do not show a status post for this upload'] = 'Nezobrazovat pro toto nahrání stavovou zprávu';
$a->strings['Permissions'] = 'Oprávnění';
$a->strings['Do you really want to delete this photo album and all its photos?'] = 'Opravdu chcete smazat toto fotoalbum a všechny jeho fotky?';
$a->strings['Delete Album'] = 'Smazat album';
$a->strings['Cancel'] = 'Zrušit';
$a->strings['Edit Album'] = 'Upravit album';
$a->strings['Drop Album'] = 'Smazat album';
$a->strings['Show Newest First'] = 'Zobrazit nejprve nejnovější';
$a->strings['Show Oldest First'] = 'Zobrazit nejprve nejstarší';
$a->strings['View Photo'] = 'Zobrazit fotku';
$a->strings['Permission denied. Access to this item may be restricted.'] = 'Oprávnění bylo zamítnuto. Přístup k této položce může být omezen.';
$a->strings['Photo not available'] = 'Fotka není k dispozici';
$a->strings['Do you really want to delete this photo?'] = 'Opravdu chcete smazat tuto fotku?';
$a->strings['Delete Photo'] = 'Smazat fotku';
$a->strings['View photo'] = 'Zobrazit fotku';
$a->strings['Edit photo'] = 'Upravit fotku';
$a->strings['Delete photo'] = 'Smazat fotku';
$a->strings['Use as profile photo'] = 'Použít jako profilovou fotku';
$a->strings['Private Photo'] = 'Soukromá fotka';
$a->strings['View Full Size'] = 'Zobrazit v plné velikosti';
$a->strings['Tags: '] = 'Štítky: ';
$a->strings['[Select tags to remove]'] = '[Vyberte štítky pro odstranění]';
$a->strings['New album name'] = 'Nové jméno alba';
$a->strings['Caption'] = 'Titulek';
$a->strings['Add a Tag'] = 'Přidat štítek';
$a->strings['Example: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping'] = 'Příklad: @jan, @Lucie_Nováková, @jakub@priklad.cz, #Morava, #taboreni';
$a->strings['Do not rotate'] = 'Neotáčet';
$a->strings['Rotate CW (right)'] = 'Otáčet po směru hodinových ručiček (doprava)';
$a->strings['Rotate CCW (left)'] = 'Otáčet proti směru hodinových ručiček (doleva)';
$a->strings['This is you'] = 'Tohle jste vy';
$a->strings['Comment'] = 'Okomentovat';
$a->strings['Preview'] = 'Náhled';
$a->strings['Select'] = 'Vybrat';
$a->strings['Delete'] = 'Odstranit';
$a->strings['I like this (toggle)'] = 'To se mi líbí (přepínat)';
$a->strings['I don\'t like this (toggle)'] = 'To se mi nelíbí (přepínat)';
$a->strings['Map'] = 'Mapa';
$a->strings['Delete this item?'] = 'Odstranit tuto položku?';
$a->strings['toggle mobile'] = 'přepínat mobilní zobrazení';
$a->strings['Page not found.'] = 'Stránka nenalezena';
$a->strings['You must be logged in to use addons. '] = 'Pro použití doplňků musíte být přihlášen/a.';
$a->strings['No system theme config value set.'] = 'Není nastavena konfigurační hodnota systémového motivu.';
$a->strings['The form security token was not correct. This probably happened because the form has been opened for too long (>3 hours) before submitting it.'] = 'Formulářový bezpečnostní token nebyl správný. To pravděpodobně nastalo kvůli tom, že formulář byl otevřen příliš dlouho (>3 hodiny) před jeho odesláním.';
$a->strings['All contacts'] = 'Všechny kontakty';
$a->strings['Followers'] = 'Sledující';
$a->strings['Following'] = 'Sleduji';
$a->strings['Could not find any unarchived contact entry for this URL (%s)'] = 'Nelze najít žádný nearchivovaný záznam kontaktu pro tuto URL adresu (%s)';
$a->strings['The contact entries have been archived'] = 'Záznamy kontaktů byly archivovány';
$a->strings['Could not find any contact entry for this URL (%s)'] = 'Nelze nalézt žádnou položku v kontaktech pro tuto URL adresu (%s)';
$a->strings['The contact has been blocked from the node'] = 'Kontakt byl na serveru zablokován';
$a->strings['Post update version number has been set to %s.'] = 'Číslo verze post update bylo nastaveno na %s.';
$a->strings['Check for pending update actions.'] = 'Zkontrolovat čekající akce po aktualizaci.';
$a->strings['Done.'] = 'Hotovo.';
$a->strings['Execute pending post updates.'] = 'Provést čekající aktualizace příspěvků.';
$a->strings['All pending post updates are done.'] = 'Všechny čekající aktualizace příspěvků jsou hotové.';
$a->strings['User not found'] = 'Uživatel nenalezen.';
$a->strings['Enter new password: '] = 'Zadejte nové heslo';
$a->strings['Password update failed. Please try again.'] = 'Aktualizace hesla se nezdařila. Zkuste to prosím znovu.';
$a->strings['Password changed.'] = 'Heslo bylo změněno.';
$a->strings['newer'] = 'novější';
$a->strings['older'] = 'starší';
$a->strings['Frequently'] = 'Často';
$a->strings['Hourly'] = 'Hodinově';
$a->strings['Twice daily'] = 'Dvakrát denně';
$a->strings['Daily'] = 'Denně';
$a->strings['Weekly'] = 'Týdně';
$a->strings['Monthly'] = 'Měsíčně';
$a->strings['DFRN'] = 'DFRN';
$a->strings['OStatus'] = 'OStatus';
$a->strings['RSS/Atom'] = 'RSS/Atom';
$a->strings['Email'] = 'E-mail';
$a->strings['Diaspora'] = 'Diaspora';
$a->strings['Zot!'] = 'Zot!';
$a->strings['LinkedIn'] = 'LinkedIn';
$a->strings['XMPP/IM'] = 'XMPP/IM';
$a->strings['MySpace'] = 'MySpace';
$a->strings['Google+'] = 'Google+';
$a->strings['pump.io'] = 'pump.io';
$a->strings['Twitter'] = 'Twitter';
$a->strings['Diaspora Connector'] = 'Diaspora Connector';
$a->strings['GNU Social Connector'] = 'GNU social Connector';
$a->strings['ActivityPub'] = 'ActivityPub';
$a->strings['pnut'] = 'pnut';
$a->strings['and'] = 'a';
$a->strings['and %d other people'] = 'a dalších %d lidí';
$a->strings['Visible to <strong>everybody</strong>'] = 'Viditelné pro <strong>všechny</strong>';
$a->strings['Please enter a image/video/audio/webpage URL:'] = 'Prosím zadejte URL obrázku/videa/audia/webové stránky:';
$a->strings['Tag term:'] = 'Štítek:';
$a->strings['Where are you right now?'] = 'Kde právě jste?';
$a->strings['Delete item(s)?'] = 'Smazat položku(y)?';
$a->strings['New Post'] = 'Nový příspěvek';
$a->strings['Share'] = 'Sdílet';
$a->strings['upload photo'] = 'nahrát fotku';
$a->strings['Attach file'] = 'Přiložit soubor';
$a->strings['attach file'] = 'přiložit soubor';
$a->strings['Bold'] = 'Tučné';
$a->strings['Italic'] = 'Kurziva';
$a->strings['Underline'] = 'Podtržené';
$a->strings['Quote'] = 'Citace';
$a->strings['Code'] = 'Kód';
$a->strings['Image'] = 'Obrázek';
$a->strings['Link'] = 'Odkaz';
$a->strings['Link or Media'] = 'Odkaz nebo média';
$a->strings['Set your location'] = 'Nastavit vaši polohu';
$a->strings['set location'] = 'nastavit polohu';
$a->strings['Clear browser location'] = 'Vymazat polohu v prohlížeči';
$a->strings['clear location'] = 'vymazat polohu';
$a->strings['Set title'] = 'Nastavit nadpis';
$a->strings['Categories (comma-separated list)'] = 'Kategorie (seznam, oddělujte čárkou)';
$a->strings['Permission settings'] = 'Nastavení oprávnění';
$a->strings['Public post'] = 'Veřejný příspěvek';
$a->strings['Message'] = 'Zpráva';
$a->strings['Browser'] = 'Prohlížeč';
$a->strings['remove'] = 'odstranit';
$a->strings['Delete Selected Items'] = 'Smazat vybrané položky';
$a->strings['%s reshared this.'] = '%s tohle znovusdílel/a.';
$a->strings['View %s\'s profile @ %s'] = 'Zobrazit profil uživatele %s na %s';
$a->strings['Categories:'] = 'Kategorie:';
$a->strings['Filed under:'] = 'Vyplněn pod:';
$a->strings['%s from %s'] = '%s z %s';
$a->strings['View in context'] = 'Zobrazit v kontextu';
$a->strings['Local Community'] = 'Místní komunita';
$a->strings['Posts from local users on this server'] = 'Příspěvky od místních uživatelů na tomto serveru';
$a->strings['Global Community'] = 'Globální komunita';
$a->strings['Posts from users of the whole federated network'] = 'Příspěvky od uživatelů z celé federované sítě';
$a->strings['Personal'] = 'Osobní';
$a->strings['Posts that mention or involve you'] = 'Příspěvky, které vás zmiňují nebo zahrnují';
$a->strings['Starred'] = 'S hvězdou';
$a->strings['Favourite Posts'] = 'Oblíbené přízpěvky';
$a->strings['General Features'] = 'Obecné vlastnosti';
$a->strings['Photo Location'] = 'Poloha fotky';
$a->strings['Photo metadata is normally stripped. This extracts the location (if present) prior to stripping metadata and links it to a map.'] = 'Metadata fotek jsou normálně odebrána. Tato funkce před odebrání metadat extrahuje polohu (pokud je k dispozici) a propojí ji s mapou.';
$a->strings['Post Composition Features'] = 'Nastavení vytváření příspěvků';
$a->strings['Explicit Mentions'] = 'Výslovné zmínky';
$a->strings['Add explicit mentions to comment box for manual control over who gets mentioned in replies.'] = 'Přidá do pole pro komentování výslovné zmínky pro ruční kontrolu nad tím, koho zmíníte v odpovědích.';
$a->strings['Post/Comment Tools'] = 'Nástroje příspěvků/komentářů';
$a->strings['Post Categories'] = 'Kategorie příspěvků';
$a->strings['Add categories to your posts'] = 'Přidat kategorie k vašim příspěvkům';
$a->strings['Archives'] = 'Archivy';
$a->strings['Protocols'] = 'Protokoly';
$a->strings['Account Types'] = 'Typy účtů';
$a->strings['Saved Searches'] = 'Uložená hledání';
$a->strings['Saved Folders'] = 'Uložené složky';
$a->strings['Trending Tags'] = 'Populární štítky';
$a->strings['Advanced Profile Settings'] = 'Pokročilá nastavení profilu';
$a->strings['Tag Cloud'] = 'Štítkový oblak';
$a->strings['Provide a personal tag cloud on your profile page'] = 'Poskytne na vaší profilové stránce osobní „štítkový oblak“';
$a->strings['Display Membership Date'] = 'Zobrazit datum členství';
$a->strings['Display membership date in profile'] = 'Zobrazit v profilu datum připojení';
$a->strings['show more'] = 'zobrazit více';
$a->strings['event'] = 'událost';
$a->strings['status'] = 'stav';
$a->strings['photo'] = 'fotka';
$a->strings['%1$s tagged %2$s\'s %3$s with %4$s'] = '%1$s označil/a %3$s uživatele %2$s štítkem %4$s';
$a->strings['Follow Thread'] = 'Sledovat vlákno';
$a->strings['View Status'] = 'Zobrazit stav';
$a->strings['View Profile'] = 'Zobrazit profil';
$a->strings['View Photos'] = 'Zobrazit fotky';
$a->strings['Network Posts'] = 'Síťové příspěvky';
$a->strings['View Contact'] = 'Zobrazit kontakt';
$a->strings['Send PM'] = 'Poslat soukromou zprávu';
$a->strings['Block'] = 'Blokovat';
$a->strings['Ignore'] = 'Ignorovat';
$a->strings['Connect/Follow'] = 'Spojit se/sledovat';
$a->strings['Nothing new here'] = 'Zde není nic nového';
$a->strings['Home'] = 'Domů';
$a->strings['Clear notifications'] = 'Vymazat oznámení';
$a->strings['Logout'] = 'Odhlásit se';
$a->strings['End this session'] = 'Konec této relace';
$a->strings['Login'] = 'Přihlásit se';
$a->strings['Sign in'] = 'Přihlásit se';
$a->strings['Profile'] = 'Profil';
$a->strings['Your profile page'] = 'Vaše profilová stránka';
$a->strings['Photos'] = 'Fotky';
$a->strings['Your photos'] = 'Vaše fotky';
$a->strings['Calendar'] = 'Kalendář';
$a->strings['Personal notes'] = 'Osobní poznámky';
$a->strings['Your personal notes'] = 'Vaše osobní poznámky';
$a->strings['Home Page'] = 'Domovská stránka';
$a->strings['Register'] = 'Registrovat';
$a->strings['Create an account'] = 'Vytvořit účet';
$a->strings['Help'] = 'Nápověda';
$a->strings['Help and documentation'] = 'Nápověda a dokumentace';
$a->strings['Apps'] = 'Aplikace';
$a->strings['Addon applications, utilities, games'] = 'Doplňkové aplikace, nástroje, hry';
$a->strings['Search'] = 'Hledat';
$a->strings['Search site content'] = 'Hledání na stránkách tohoto webu';
$a->strings['Full Text'] = 'Celý text';
$a->strings['Tags'] = 'Štítky';
$a->strings['Contacts'] = 'Kontakty';
$a->strings['Community'] = 'Komunita';
$a->strings['Conversations on this and other servers'] = 'Konverzace na tomto a jiných serverech';
$a->strings['Directory'] = 'Adresář';
$a->strings['People directory'] = 'Adresář';
$a->strings['Information'] = 'Informace';
$a->strings['Information about this friendica instance'] = 'Informace o této instanci Friendica';
$a->strings['Terms of Service'] = 'Podmínky používání';
$a->strings['Terms of Service of this Friendica instance'] = 'Podmínky používání této instance Friendica';
$a->strings['Network'] = 'Síť';
$a->strings['Conversations from your friends'] = 'Konverzace od vašich přátel';
$a->strings['Your posts and conversations'] = 'Vaše příspěvky a konverzace';
$a->strings['Introductions'] = 'Představení';
$a->strings['Friend Requests'] = 'Požadavky o přátelství';
$a->strings['Notifications'] = 'Oznámení';
$a->strings['See all notifications'] = 'Zobrazit všechna oznámení';
$a->strings['Mark as seen'] = 'Označit jako přečtené';
$a->strings['Private mail'] = 'Soukromá pošta';
$a->strings['Inbox'] = 'Doručená pošta';
$a->strings['Outbox'] = 'Odeslaná pošta';
$a->strings['Manage other pages'] = 'Spravovat jiné stránky';
$a->strings['Settings'] = 'Nastavení';
$a->strings['Account settings'] = 'Nastavení účtu';
$a->strings['Manage/edit friends and contacts'] = 'Spravovat/upravit přátelé a kontakty';
$a->strings['Admin'] = 'Administrátor';
$a->strings['Site setup and configuration'] = 'Nastavení webu a konfigurace';
$a->strings['Navigation'] = 'Navigace';
$a->strings['Site map'] = 'Mapa webu';
$a->strings['first'] = 'první';
$a->strings['prev'] = 'předchozí';
$a->strings['next'] = 'další';
$a->strings['last'] = 'poslední';
$a->strings['Click to open/close'] = 'Kliknutím otevřete/zavřete';
$a->strings['$1 wrote:'] = '$1 napsal/a:';
$a->strings['Encrypted content'] = 'Šifrovaný obsah';
$a->strings['Invalid source protocol'] = 'Neplatný protokol zdroje';
$a->strings['Invalid link protocol'] = 'Neplatný protokol odkazu';
$a->strings['Loading more entries...'] = 'Načítám více záznamů...';
$a->strings['The end'] = 'Konec';
$a->strings['Follow'] = 'Sledovat';
$a->strings['Add New Contact'] = 'Přidat nový kontakt';
$a->strings['Enter address or web location'] = 'Zadejte adresu nebo umístění webu';
$a->strings['Example: bob@example.com, http://example.com/barbara'] = 'Příklad: jan@priklad.cz, http://priklad.cz/lucie';
$a->strings['Connect'] = 'Spojit se';
$a->strings['%d invitation available'] = [
	0 => '%d pozvánka k dispozici',
	1 => '%d pozvánky k dispozici',
	2 => '%d pozvánky k dispozici',
	3 => '%d pozvánek k dispozici',
];
$a->strings['Find People'] = 'Najít lidi';
$a->strings['Enter name or interest'] = 'Zadejte jméno nebo zájmy';
$a->strings['Examples: Robert Morgenstein, Fishing'] = 'Příklady: Josef Dvořák, rybaření';
$a->strings['Find'] = 'Najít';
$a->strings['Friend Suggestions'] = 'Návrhy přátel';
$a->strings['Similar Interests'] = 'Podobné zájmy';
$a->strings['Random Profile'] = 'Náhodný profil';
$a->strings['Invite Friends'] = 'Pozvat přátele';
$a->strings['Global Directory'] = 'Globální adresář';
$a->strings['Local Directory'] = 'Místní adresář';
$a->strings['Relationships'] = 'Vztahy';
$a->strings['All Contacts'] = 'Všechny kontakty';
$a->strings['All Protocols'] = 'Všechny protokoly';
$a->strings['Everything'] = 'Všechno';
$a->strings['Categories'] = 'Kategorie';
$a->strings['%d contact in common'] = [
	0 => '%d společný kontakt',
	1 => '%d společné kontakty',
	2 => '%d společného kontaktu',
	3 => '%d společných kontaktů',
];
$a->strings['News'] = 'Zprávy';
$a->strings['Export'] = 'Exportovat';
$a->strings['Export calendar as ical'] = 'Exportovat kalendář jako ical';
$a->strings['Export calendar as csv'] = 'Exportovat kalendář jako csv';
$a->strings['No contacts'] = 'Žádné kontakty';
$a->strings['%d Contact'] = [
	0 => '%d kontakt',
	1 => '%d kontakty',
	2 => '%d kontaktu',
	3 => '%d kontaktů',
];
$a->strings['View Contacts'] = 'Zobrazit kontakty';
$a->strings['Remove term'] = 'Odstranit termín';
$a->strings['Trending Tags (last %d hour)'] = [
	0 => 'Populární štítky (poslední %d hodina)',
	1 => 'Populární štítky (poslední %d hodiny)',
	2 => 'Populární štítky (posledních %d hodin)',
	3 => 'Populární štítky (posledních %d hodin)',
];
$a->strings['More Trending Tags'] = 'Další populární štítky';
$a->strings['XMPP:'] = 'XMPP:';
$a->strings['Location:'] = 'Poloha:';
$a->strings['Network:'] = 'Síť:';
$a->strings['Unfollow'] = 'Přestat sledovat';
$a->strings['Mutuals'] = 'Vzájemní';
$a->strings['Post to Email'] = 'Poslat příspěvek na e-mail';
$a->strings['CC: email addresses'] = 'Kopie: e-mailové adresy';
$a->strings['Example: bob@example.com, mary@example.com'] = 'Příklad: jan@priklad.cz, lucie@priklad.cz';
$a->strings['Connectors'] = 'Konektory';
$a->strings['The database configuration file "config/local.config.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.'] = 'Databázový konfigurační soubor „config/local.config.php“ nemohl být zapsán. Prosím, použijte přiložený text k vytvoření konfiguračního souboru v kořenovém adresáři vašeho webového serveru.';
$a->strings['You may need to import the file "database.sql" manually using phpmyadmin or mysql.'] = 'Nejspíše budete muset manuálně importovat soubor „database.sql“ pomocí phpMyAdmin či MySQL.';
$a->strings['Could not find a command line version of PHP in the web server PATH.'] = 'Nelze najít verzi PHP pro příkazový řádek v PATH webového serveru.';
$a->strings['PHP executable path'] = 'Cesta ke spustitelnému souboru PHP';
$a->strings['Enter full path to php executable. You can leave this blank to continue the installation.'] = 'Zadejte plnou cestu ke spustitelnému souboru PHP. Tento údaj můžete ponechat nevyplněný a pokračovat v instalaci.';
$a->strings['Command line PHP'] = 'Příkazový řádek PHP';
$a->strings['PHP executable is not the php cli binary (could be cgi-fgci version)'] = 'PHP executable není php cli binary (může být verze cgi-fgci)';
$a->strings['Found PHP version: '] = 'Nalezena verze PHP:';
$a->strings['PHP cli binary'] = 'PHP cli binary';
$a->strings['The command line version of PHP on your system does not have "register_argc_argv" enabled.'] = 'Verze PHP pro příkazový řádek na vašem systému nemá povoleno nastavení „register_argc_argv“.';
$a->strings['This is required for message delivery to work.'] = 'Toto je nutné pro fungování doručování zpráv.';
$a->strings['PHP register_argc_argv'] = 'PHP register_argc_argv';
$a->strings['Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys'] = 'Chyba: funkce „openssl_pkey_new“ na tomto systému není schopna generovat šifrovací klíče';
$a->strings['If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".'] = 'Pokud systém běží na Windows, prosím přečtěte si „http://www.php.net/manual/en/openssl.installation.php“.';
$a->strings['Generate encryption keys'] = 'Generovat šifrovací klíče';
$a->strings['Error: Apache webserver mod-rewrite module is required but not installed.'] = 'Chyba: Modul mod_rewrite webového serveru Apache je vyadován, ale není nainstalován.';
$a->strings['Apache mod_rewrite module'] = 'Modul Apache mod_rewrite';
$a->strings['Error: PDO or MySQLi PHP module required but not installed.'] = 'Chyba: PHP modul PDO nebo MySQLi je vyžadován, ale není nainstalován.';
$a->strings['Error: The MySQL driver for PDO is not installed.'] = 'Chyba: Ovladač MySQL pro PDO není nainstalován';
$a->strings['PDO or MySQLi PHP module'] = 'PHP modul PDO nebo MySQLi';
$a->strings['Error, XML PHP module required but not installed.'] = 'Chyba: PHP modul XML je vyžadován, ale není nainstalován';
$a->strings['XML PHP module'] = 'PHP modul XML';
$a->strings['libCurl PHP module'] = 'PHP modul libCurl';
$a->strings['Error: libCURL PHP module required but not installed.'] = 'Chyba: PHP modul libcurl je vyžadován, ale není nainstalován.';
$a->strings['GD graphics PHP module'] = 'PHP modul GD graphics';
$a->strings['Error: GD graphics PHP module with JPEG support required but not installed.'] = 'Chyba: PHP modul GD graphics je vyžadován, ale není nainstalován.';
$a->strings['OpenSSL PHP module'] = 'PHP modul OpenSSL';
$a->strings['Error: openssl PHP module required but not installed.'] = 'Chyba: PHP modul openssl je vyžadován, ale není nainstalován.';
$a->strings['mb_string PHP module'] = 'PHP modul mb_string';
$a->strings['Error: mb_string PHP module required but not installed.'] = 'Chyba: PHP modul mb_string  je vyžadován, ale není nainstalován.';
$a->strings['iconv PHP module'] = 'PHP modul iconv';
$a->strings['Error: iconv PHP module required but not installed.'] = 'Chyba: PHP modul iconv je vyžadován, ale není nainstalován';
$a->strings['POSIX PHP module'] = 'PHP modul POSIX';
$a->strings['Error: POSIX PHP module required but not installed.'] = 'Chyba: PHP modul POSIX je vyžadován, ale není nainstalován.';
$a->strings['JSON PHP module'] = 'PHP modul JSON';
$a->strings['Error: JSON PHP module required but not installed.'] = 'Chyba: PHP modul JSON je vyžadován, ale není nainstalován';
$a->strings['File Information PHP module'] = 'PHP modul File Information';
$a->strings['Error: File Information PHP module required but not installed.'] = 'Chyba: PHP modul File Information  je vyžadován, ale není nainstalován.';
$a->strings['The web installer needs to be able to create a file called "local.config.php" in the "config" folder of your web server and it is unable to do so.'] = 'Webový instalátor musí být schopen vytvořit soubor s názvem „local.config.php“ v adresáři „config“ Vašeho webového serveru a není mu to umožněno. ';
$a->strings['This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.'] = 'Toto je nejčastěji nastavením oprávnění, kdy webový server nemusí být schopen zapisovat soubory do vašeho adresáře - i když vy můžete.';
$a->strings['At the end of this procedure, we will give you a text to save in a file named local.config.php in your Friendica "config" folder.'] = 'Na konci této procedury od nás obdržíte text k uložení v souboru pojmenovaném local.config.php v adresáři „config“ na Vaší instalaci Friendica.';
$a->strings['config/local.config.php is writable'] = 'Soubor config/local.config.php je zapisovatelný';
$a->strings['Friendica uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.'] = 'Friendica používá k zobrazení svých webových stránek šablonovací nástroj Smarty3. Smarty3 kompiluje šablony do PHP pro zrychlení vykreslování.';
$a->strings['In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top level folder.'] = 'Pro uložení kompilovaných šablon potřebuje webový server mít přístup k zápisu do adresáře view/smarty3/ pod kořenovým adresářem Friendica.';
$a->strings['Please ensure that the user that your web server runs as (e.g. www-data) has write access to this folder.'] = 'Prosím ujistěte se, že má uživatel webového serveru (jako například www-data) právo zápisu do tohoto adresáře';
$a->strings['Note: as a security measure, you should give the web server write access to view/smarty3/ only--not the template files (.tpl) that it contains.'] = 'Poznámka: jako bezpečnostní opatření byste měl/a přidělit webovém serveru právo zápisu pouze do adresáře /view/smarty3/ -- a nikoliv už do souborů s šablonami (.tpl), které obsahuje.';
$a->strings['view/smarty3 is writable'] = 'Adresář view/smarty3 je zapisovatelný';
$a->strings['Error message from Curl when fetching'] = 'Chybová zpráva od Curl při načítání';
$a->strings['Url rewrite is working'] = 'Url rewrite je funkční.';
$a->strings['ImageMagick PHP extension is not installed'] = 'PHP rozšíření ImageMagick není nainstalováno';
$a->strings['ImageMagick PHP extension is installed'] = 'PHP rozšíření ImageMagick je nainstalováno';
$a->strings['Database already in use.'] = 'Databáze se již používá.';
$a->strings['Could not connect to database.'] = 'Nelze se připojit k databázi.';
$a->strings['Monday'] = 'pondělí';
$a->strings['Tuesday'] = 'úterý';
$a->strings['Wednesday'] = 'středa';
$a->strings['Thursday'] = 'čtvrtek';
$a->strings['Friday'] = 'pátek';
$a->strings['Saturday'] = 'sobota';
$a->strings['Sunday'] = 'neděle';
$a->strings['January'] = 'leden';
$a->strings['February'] = 'únor';
$a->strings['March'] = 'březen';
$a->strings['April'] = 'duben';
$a->strings['May'] = 'květen';
$a->strings['June'] = 'červen';
$a->strings['July'] = 'červenec';
$a->strings['August'] = 'srpen';
$a->strings['September'] = 'září';
$a->strings['October'] = 'říjen';
$a->strings['November'] = 'listopad';
$a->strings['December'] = 'prosinec';
$a->strings['Mon'] = 'pon';
$a->strings['Tue'] = 'úte';
$a->strings['Wed'] = 'stř';
$a->strings['Thu'] = 'čtv';
$a->strings['Fri'] = 'pát';
$a->strings['Sat'] = 'sob';
$a->strings['Sun'] = 'ned';
$a->strings['Jan'] = 'led';
$a->strings['Feb'] = 'úno';
$a->strings['Mar'] = 'bře';
$a->strings['Apr'] = 'dub';
$a->strings['Jun'] = 'čvn';
$a->strings['Jul'] = 'čvc';
$a->strings['Aug'] = 'srp';
$a->strings['Sep'] = 'zář';
$a->strings['Oct'] = 'říj';
$a->strings['Nov'] = 'lis';
$a->strings['Dec'] = 'pro';
$a->strings['Storage base path'] = 'Cesta ke kořenové složce úložiště';
$a->strings['Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree'] = 'Složka, do které jsou ukládány nahrané soubory. Pro maximální bezpečnost to musí být cesta mimo složku webového serveru';
$a->strings['Enter a valid existing folder'] = 'Zadejte platnou existující složku';
$a->strings['Update %s failed. See error logs.'] = 'Aktualizace %s selhala. Zkontrolujte protokol chyb.';
$a->strings['
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can\'t do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.'] = '
				Vývojáři Friendica nedávno vydali aktualizaci %s,
				ale když jsem ji zkusil instalovat, něco se strašně pokazilo.
				Toto se musí ihned opravit a nemůžu to udělat sám. Prosím, kontaktujte
				vývojáře Friendica, pokud to nedokážete sám. Moje databáze může být neplatná.';
$a->strings['[Friendica Notify] Database update'] = '[Friendica:Oznámení] Aktualizace databáze';
$a->strings['
Error %d occurred during database update:
%s
'] = '
Při aktualizaci databáze se vyskytla chyba %d:
%s
';
$a->strings['Errors encountered performing database changes: '] = 'Při vykonávání změn v databázi se vyskytly chyby: ';
$a->strings['%s: Database update'] = '%s: Aktualizace databáze';
$a->strings['%s: updating %s table.'] = '%s: aktualizuji tabulku %s';
$a->strings['Unauthorized'] = 'Neautorizováno';
$a->strings['Internal Server Error'] = 'Vnitřní chyba serveru';
$a->strings['Legacy module file not found: %s'] = 'Soubor legacy modulu nenalezen: %s';
$a->strings['Everybody'] = 'Všichni';
$a->strings['edit'] = 'upravit';
$a->strings['add'] = 'přidat';
$a->strings['Approve'] = 'Schválit';
$a->strings['Organisation'] = 'Organizace';
$a->strings['Relay'] = 'Přeposílací server';
$a->strings['Disallowed profile URL.'] = 'Nepovolené URL profilu.';
$a->strings['Blocked domain'] = 'Zablokovaná doména';
$a->strings['Connect URL missing.'] = 'Chybí URL adresa pro připojení.';
$a->strings['The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.'] = 'Kontakt nemohl být přidán. Prosím zkontrolujte relevantní přihlašovací údaje sítě na stránce Nastavení -> Sociální sítě.';
$a->strings['The profile address specified does not provide adequate information.'] = 'Uvedená adresa profilu neposkytuje dostatečné informace.';
$a->strings['No compatible communication protocols or feeds were discovered.'] = 'Nenalezen žádný kompatibilní komunikační protokol nebo kanál.';
$a->strings['An author or name was not found.'] = 'Autor nebo jméno nenalezeno';
$a->strings['No browser URL could be matched to this address.'] = 'Této adrese neodpovídá žádné URL prohlížeče.';
$a->strings['Unable to match @-style Identity Address with a known protocol or email contact.'] = 'Není možné namapovat adresu identity ve stylu @ s žádným možným protokolem ani emailovým kontaktem.';
$a->strings['Use mailto: in front of address to force email check.'] = 'Použite mailo: před adresou k vynucení emailové kontroly.';
$a->strings['Limited profile. This person will be unable to receive direct/personal notifications from you.'] = 'Omezený profil. Tato osoba nebude schopna od vás přijímat přímá/osobní oznámení.';
$a->strings['Unable to retrieve contact information.'] = 'Nepodařilo se získat kontaktní informace.';
$a->strings['Starts:'] = 'Začíná:';
$a->strings['Finishes:'] = 'Končí:';
$a->strings['all-day'] = 'celodenní';
$a->strings['Sept'] = 'září';
$a->strings['today'] = 'dnes';
$a->strings['month'] = 'měsíc';
$a->strings['week'] = 'týden';
$a->strings['day'] = 'den';
$a->strings['No events to display'] = 'Žádné události k zobrazení';
$a->strings['Access to this profile has been restricted.'] = 'Přístup na tento profil byl omezen.';
$a->strings['l, F j'] = 'l, j. F';
$a->strings['Edit event'] = 'Upravit událost';
$a->strings['Duplicate event'] = 'Duplikovat událost';
$a->strings['Delete event'] = 'Smazat událost';
$a->strings['l F d, Y \@ g:i A'] = 'l d. F, Y v g:i A';
$a->strings['D g:i A'] = 'D g:i A';
$a->strings['g:i A'] = 'g:i A';
$a->strings['Show map'] = 'Zobrazit mapu';
$a->strings['Hide map'] = 'Skrýt mapu';
$a->strings['%s\'s birthday'] = '%s má narozeniny';
$a->strings['Happy Birthday %s'] = 'Veselé narozeniny, %s';
$a->strings['activity'] = 'aktivita';
$a->strings['post'] = 'příspěvek';
$a->strings['bytes'] = 'bytů';
$a->strings['View on separate page'] = 'Zobrazit na separátní stránce';
$a->strings['[no subject]'] = '[bez předmětu]';
$a->strings['Wall Photos'] = 'Fotky na zdi';
$a->strings['Edit profile'] = 'Upravit profil';
$a->strings['Change profile photo'] = 'Změnit profilovou fotku';
$a->strings['Homepage:'] = 'Domovská stránka:';
$a->strings['About:'] = 'O mně:';
$a->strings['Atom feed'] = 'Kanál Atom';
$a->strings['F d'] = 'd. F';
$a->strings['[today]'] = '[dnes]';
$a->strings['Birthday Reminders'] = 'Připomínka narozenin';
$a->strings['Birthdays this week:'] = 'Narozeniny tento týden:';
$a->strings['g A l F d'] = 'g A, l d. F';
$a->strings['[No description]'] = '[Žádný popis]';
$a->strings['Event Reminders'] = 'Připomenutí událostí';
$a->strings['Upcoming events the next 7 days:'] = 'Nadcházející události v příštích 7 dnech:';
$a->strings['Hometown:'] = 'Rodné město:';
$a->strings['Sexual Preference:'] = 'Sexuální orientace:';
$a->strings['Political Views:'] = 'Politické přesvědčení:';
$a->strings['Religious Views:'] = 'Náboženské přesvědčení:';
$a->strings['Likes:'] = 'Líbí se:';
$a->strings['Dislikes:'] = 'Nelibí se:';
$a->strings['Title/Description:'] = 'Název / Popis:';
$a->strings['Summary'] = 'Shrnutí';
$a->strings['Musical interests'] = 'Hudební vkus';
$a->strings['Books, literature'] = 'Knihy, literatura';
$a->strings['Television'] = 'Televize';
$a->strings['Film/dance/culture/entertainment'] = 'Film/tanec/kultura/zábava';
$a->strings['Hobbies/Interests'] = 'Koníčky/zájmy';
$a->strings['Love/romance'] = 'Láska/romantika';
$a->strings['Work/employment'] = 'Práce/zaměstnání';
$a->strings['School/education'] = 'Škola/vzdělání';
$a->strings['Contact information and Social Networks'] = 'Kontaktní informace a sociální sítě';
$a->strings['SERIOUS ERROR: Generation of security keys failed.'] = 'ZÁVAŽNÁ CHYBA: Generování bezpečnostních klíčů se nezdařilo.';
$a->strings['Login failed'] = 'Přihlášení selhalo';
$a->strings['Not enough information to authenticate'] = 'Není dost informací pro autentikaci';
$a->strings['Password can\'t be empty'] = 'Heslo nemůže být prázdné';
$a->strings['Empty passwords are not allowed.'] = 'Prázdná hesla nejsou povolena.';
$a->strings['The new password has been exposed in a public data dump, please choose another.'] = 'Nové heslo bylo zveřejněno ve veřejném výpisu dat, prosím zvolte si jiné.';
$a->strings['Passwords do not match. Password unchanged.'] = 'Hesla se neshodují. Heslo nebylo změněno.';
$a->strings['An invitation is required.'] = 'Je vyžadována pozvánka.';
$a->strings['Invitation could not be verified.'] = 'Pozvánka nemohla být ověřena.';
$a->strings['Invalid OpenID url'] = 'Neplatný odkaz OpenID';
$a->strings['We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.'] = 'Zaznamenali jsme problém s vaším přihlášením prostřednictvím Vámi zadaným OpenID. Prosím ověřte si, že jste ID zadali správně. ';
$a->strings['The error message was:'] = 'Chybová zpráva byla:';
$a->strings['Please enter the required information.'] = 'Zadejte prosím požadované informace.';
$a->strings['system.username_min_length (%s) and system.username_max_length (%s) are excluding each other, swapping values.'] = 'system.username_min_length (%s) a system.username_max_length (%s) se vzájemně vylučují, čímž se vyměňují hodnoty.';
$a->strings['Username should be at least %s character.'] = [
	0 => 'Uživateleké jméno musí mít alespoň %s znak.',
	1 => 'Uživateleké jméno musí mít alespoň %s znaky.',
	2 => 'Uživateleké jméno musí mít alespoň %s znaku.',
	3 => 'Uživateleké jméno musí mít alespoň %s znaků.',
];
$a->strings['Username should be at most %s character.'] = [
	0 => 'Uživateleké jméno musí mít nanejvýš %s znak.',
	1 => 'Uživateleké jméno musí mít nanejvýš %s znaky.',
	2 => 'Uživateleké jméno musí mít nanejvýš %s znaku.',
	3 => 'Uživateleké jméno musí mít nanejvýš %s znaků.',
];
$a->strings['That doesn\'t appear to be your full (First Last) name.'] = 'Nezdá se, že by to bylo vaše celé jméno (křestní jméno a příjmení).';
$a->strings['Your email domain is not among those allowed on this site.'] = 'Vaše e-mailová doména není na tomto serveru mezi povolenými.';
$a->strings['Not a valid email address.'] = 'Neplatná e-mailová adresa.';
$a->strings['The nickname was blocked from registration by the nodes admin.'] = 'Administrátor serveru zablokoval registraci této přezdívky.';
$a->strings['Cannot use that email.'] = 'Tento e-mail nelze použít.';
$a->strings['Your nickname can only contain a-z, 0-9 and _.'] = 'Uživatelské jméno může obsahovat pouze znaky a-z, 0-9 a _.';
$a->strings['Nickname is already registered. Please choose another.'] = 'Přezdívka je již registrována. Prosím vyberte jinou.';
$a->strings['An error occurred during registration. Please try again.'] = 'Došlo k chybě při registraci. Zkuste to prosím znovu.';
$a->strings['An error occurred creating your default profile. Please try again.'] = 'Při vytváření vašeho výchozího profilu došlo k chybě. Zkuste to prosím znovu.';
$a->strings['An error occurred creating your self contact. Please try again.'] = 'Při vytváření vašeho kontaktu na sebe došlo k chybě. Zkuste to prosím znovu.';
$a->strings['Friends'] = 'Přátelé';
$a->strings['Profile Photos'] = 'Profilové fotky';
$a->strings['Registration details for %s'] = 'Registrační údaje pro uživatele %s';
$a->strings['
			Dear %1$s,
				Thank you for registering at %2$s. Your account is pending for approval by the administrator.

			Your login details are as follows:

			Site Location:	%3$s
			Login Name:		%4$s
			Password:		%5$s
		'] = '
			Vážený/á %1$s,
				Děkujeme, že jste se registroval/a na %2$s. Váš účet čeká na schválení administrátora.

			Zde jsou vaše přihlašovací detaily:

			Adresa stránky:		%3$s
			Přihlašovací jméno:	%4$s
			Heslo:			%5$s
		';
$a->strings['Registration at %s'] = 'Registrace na %s';
$a->strings['Addon not found.'] = 'Doplněk nenalezen.';
$a->strings['Addon %s disabled.'] = 'Doplněk %s zakázán.';
$a->strings['Addon %s enabled.'] = 'Doplněk %s povolen.';
$a->strings['Disable'] = 'Zakázat';
$a->strings['Enable'] = 'Povolit';
$a->strings['Administration'] = 'Administrace';
$a->strings['Addons'] = 'Doplňky';
$a->strings['Toggle'] = 'Přepnout';
$a->strings['Author: '] = 'Autor: ';
$a->strings['Maintainer: '] = 'Správce: ';
$a->strings['Addon %s failed to install.'] = 'Instalace doplňku %s selhala.';
$a->strings['Save Settings'] = 'Uložit nastavení';
$a->strings['Reload active addons'] = 'Znovu načíst aktivní doplňky';
$a->strings['Update has been marked successful'] = 'Aktualizace byla označena jako úspěšná.';
$a->strings['Database structure update %s was successfully applied.'] = 'Aktualizace struktury databáze %s byla úspěšně aplikována.';
$a->strings['Executing of database structure update %s failed with error: %s'] = 'Provádění aktualizace databáze %s selhalo s chybou: %s';
$a->strings['Executing %s failed with error: %s'] = 'Vykonávání %s selhalo s chybou: %s';
$a->strings['Update %s was successfully applied.'] = 'Aktualizace %s byla úspěšně aplikována.';
$a->strings['Update %s did not return a status. Unknown if it succeeded.'] = 'Aktualizace %s nevrátila žádný stav. Není zřejmé, jestli byla úspěšná.';
$a->strings['There was no additional update function %s that needed to be called.'] = 'Nebyla nalezena žádná další aktualizační funkce %s která by měla být volána.';
$a->strings['No failed updates.'] = 'Žádné neúspěšné aktualizace.';
$a->strings['Check database structure'] = 'Ověřit strukturu databáze';
$a->strings['Failed Updates'] = 'Neúspěšné aktualizace';
$a->strings['This does not include updates prior to 1139, which did not return a status.'] = 'To nezahrnuje aktualizace do verze 1139, které nevracejí žádný status.';
$a->strings['Mark success (if update was manually applied)'] = 'Označit za úspěšné (pokud byla aktualizace aplikována manuálně)';
$a->strings['Attempt to execute this update step automatically'] = 'Pokusit se provést tuto aktualizaci automaticky.';
$a->strings['No'] = 'Ne';
$a->strings['Yes'] = 'Ano';
$a->strings['Manage Additional Features'] = 'Spravovat další funkce';
$a->strings['Other'] = 'Jiné';
$a->strings['unknown'] = 'neznámé';
$a->strings['This page offers you some numbers to the known part of the federated social network your Friendica node is part of. These numbers are not complete but only reflect the part of the network your node is aware of.'] = 'Tato stránka vám nabízí pár čísel pro známou část federované sociální sítě, které je váš server Friendica součástí. Tato čísla nejsou kompletní, ale pouze odrážejí část sítě, které si je Váš server vědom.';
$a->strings['Federation Statistics'] = 'Statistiky Federation';
$a->strings['The logfile \'%s\' is not writable. No logging possible'] = 'Záznamový soubor „%s“ není zapisovatelný. Zaznamenávání není možno.';
$a->strings['PHP log currently enabled.'] = 'PHP záznamy jsou aktuálně povolené.';
$a->strings['PHP log currently disabled.'] = 'PHP záznamy jsou aktuálně zakázané.';
$a->strings['Logs'] = 'Záznamy';
$a->strings['Clear'] = 'Vyčistit';
$a->strings['Enable Debugging'] = 'Povolit ladění';
$a->strings['Log file'] = 'Soubor se záznamem';
$a->strings['Must be writable by web server. Relative to your Friendica top-level directory.'] = 'Musí být zapisovatelný webovým serverem. Cesta relativní k vašemu kořenovému adresáři Friendica.';
$a->strings['Log level'] = 'Úroveň auditu';
$a->strings['PHP logging'] = 'Záznamování PHP';
$a->strings['To temporarily enable logging of PHP errors and warnings you can prepend the following to the index.php file of your installation. The filename set in the \'error_log\' line is relative to the friendica top-level directory and must be writeable by the web server. The option \'1\' for \'log_errors\' and \'display_errors\' is to enable these options, set to \'0\' to disable them.'] = 'Pro dočasné umožnění zaznamenávání PHP chyb a varování, můžete přidat do souboru index.php na vaší instalaci následující: Název souboru nastavený v řádku „error_log“ je relativní ke kořenovému adresáři Friendica a webový server musí mít povolení na něj zapisovat. Možnost „1“ pro „log_errors“ a „display_errors“ tyto funkce povoluje, nastavením hodnoty na „0“ je zakážete. ';
$a->strings['View Logs'] = 'Zobrazit záznamy';
$a->strings['Show all'] = 'Zobrazit vše';
$a->strings['Event details'] = 'Detaily události';
$a->strings['Inspect Deferred Worker Queue'] = 'Prozkoumat frontu odložených pracovníků';
$a->strings['This page lists the deferred worker jobs. This are jobs that couldn\'t be executed at the first time.'] = 'Na této stránce jsou vypsány odložené úlohy pracovníků. To jsou úlohy, které nemohly být napoprvé provedeny.';
$a->strings['Inspect Worker Queue'] = 'Prozkoumat frontu pro pracovníka';
$a->strings['This page lists the currently queued worker jobs. These jobs are handled by the worker cronjob you\'ve set up during install.'] = 'Na této stránce jsou vypsány aktuálně čekající úlohy pro pracovníka . Tyto úlohy vykonává úloha cron pracovníka, kterou jste nastavil/a při instalaci.';
$a->strings['ID'] = 'Identifikátor';
$a->strings['Job Parameters'] = 'Parametry úlohy';
$a->strings['Created'] = 'Vytvořeno';
$a->strings['Priority'] = 'Priorita';
$a->strings['No special theme for mobile devices'] = 'Žádný speciální motiv pro mobilní zařízení';
$a->strings['%s - (Experimental)'] = '%s - (Experimentální)';
$a->strings['No community page'] = 'Žádná komunitní stránka';
$a->strings['Public postings from users of this site'] = 'Veřejné příspěvky od místních uživatelů';
$a->strings['Public postings from the federated network'] = 'Veřejné příspěvky z federované sítě';
$a->strings['Public postings from local users and the federated network'] = 'Veřejné příspěvky od místních uživatelů a z federované sítě';
$a->strings['Multi user instance'] = 'Víceuživatelská instance';
$a->strings['Closed'] = 'Uzavřeno';
$a->strings['Requires approval'] = 'Vyžaduje schválení';
$a->strings['Open'] = 'Otevřeno';
$a->strings['Don\'t check'] = 'Nekontrolovat';
$a->strings['check the stable version'] = 'kontrolovat stabilní verzi';
$a->strings['check the development version'] = 'kontrolovat vývojovou verzi';
$a->strings['Site'] = 'Web';
$a->strings['Republish users to directory'] = 'Znovu publikovat uživatele do adresáře';
$a->strings['Registration'] = 'Registrace';
$a->strings['File upload'] = 'Nahrání souborů';
$a->strings['Policies'] = 'Politika';
$a->strings['Advanced'] = 'Pokročilé';
$a->strings['Auto Discovered Contact Directory'] = 'Adresář automaticky objevených kontaktů';
$a->strings['Performance'] = 'Výkon';
$a->strings['Worker'] = 'Pracovník (worker)';
$a->strings['Message Relay'] = 'Přeposílání zpráv';
$a->strings['Site name'] = 'Název webu';
$a->strings['Sender Email'] = 'E-mail odesílatele';
$a->strings['The email address your server shall use to send notification emails from.'] = 'E-mailová adresa, kterou bude Váš server používat pro posílání e-mailů s oznámeními.';
$a->strings['Banner/Logo'] = 'Banner/logo';
$a->strings['Shortcut icon'] = 'Favikona';
$a->strings['Link to an icon that will be used for browsers.'] = 'Odkaz k ikoně, která bude použita pro prohlížeče.';
$a->strings['Touch icon'] = 'Dotyková ikona';
$a->strings['Link to an icon that will be used for tablets and mobiles.'] = 'Odkaz k ikoně, která bude použita pro tablety a mobilní zařízení.';
$a->strings['Additional Info'] = 'Dodatečné informace';
$a->strings['For public servers: you can add additional information here that will be listed at %s/servers.'] = 'Pro veřejné servery: zde můžete přidat dodatečné informace, které budou vypsané na stránce %s/servers.';
$a->strings['System language'] = 'Systémový jazyk';
$a->strings['System theme'] = 'Systémový motiv';
$a->strings['Mobile system theme'] = 'Mobilní systémový motiv';
$a->strings['Theme for mobile devices'] = 'Motiv pro mobilní zařízení';
$a->strings['Force SSL'] = 'Vynutit SSL';
$a->strings['Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.'] = 'Vynutit SSL pro všechny ne-SSL žádosti - Upozornění: na některých systémech může dojít k nekonečnému zacyklení.';
$a->strings['Single user instance'] = 'Jednouživatelská instance';
$a->strings['Make this instance multi-user or single-user for the named user'] = 'Nastavit tuto instanci víceuživatelskou nebo jednouživatelskou pro pojmenovaného uživatele';
$a->strings['Maximum image size'] = 'Maximální velikost obrázků';
$a->strings['Maximum image length'] = 'Maximální velikost obrázků';
$a->strings['Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.'] = 'Maximální délka delší stránky nahrávaných obrázků v pixelech. Výchozí hodnota je -1, což znamená bez omezení.';
$a->strings['JPEG image quality'] = 'Kvalita obrázků JPEG';
$a->strings['Uploaded JPEGS will be saved at this quality setting [0-100]. Default is 100, which is full quality.'] = 'Nahrávané obrázky JPEG budou uloženy se zadanou kvalitou v rozmezí [0-100]. Výchozí hodnota je 100, což znamená plnou kvalitu.';
$a->strings['Register policy'] = 'Politika registrace';
$a->strings['Maximum Daily Registrations'] = 'Maximální počet denních registrací';
$a->strings['If registration is permitted above, this sets the maximum number of new user registrations to accept per day.  If register is set to closed, this setting has no effect.'] = 'Pokud je registrace výše povolena, zde se nastaví maximální počet registrací nových uživatelů za den. Pokud je registrace zakázána, toto nastavení nemá žádný efekt.';
$a->strings['Register text'] = 'Text při registraci';
$a->strings['Will be displayed prominently on the registration page. You can use BBCode here.'] = 'Bude zobrazen viditelně na stránce registrace. Zde můžete používat BBCode.';
$a->strings['Forbidden Nicknames'] = 'Zakázané přezdívky';
$a->strings['Comma separated list of nicknames that are forbidden from registration. Preset is a list of role names according RFC 2142.'] = 'Seznam přezdívek, které nelze registrovat, oddělených čárkami. Přednastaven je seznam častých přezdívek dle RFC 2142.';
$a->strings['Accounts abandoned after x days'] = 'Účty jsou opuštěny po x dnech';
$a->strings['Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.'] = 'Nebude se plýtvat systémovými zdroji kontaktováním externích webů s opuštěnými účty. Zadejte 0 pro žádný časový limit.';
$a->strings['Allowed friend domains'] = 'Povolené domény přátel';
$a->strings['Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains'] = 'Seznam domén, kterým je povoleno navazovat přátelství s tímto webem, oddělených čárkami. Zástupné znaky (wildcards) jsou povoleny. Prázné znamená libovolné domény.';
$a->strings['Allowed email domains'] = 'Povolené e-mailové domény';
$a->strings['Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains'] = 'Seznam domén e-mailových adres, kterým je povoleno provádět registraci na tomto webu, oddělených čárkami. Zástupné znaky (wildcards) jsou povoleny. Prázné znamená libovolné domény.';
$a->strings['No OEmbed rich content'] = 'Žádný obohacený obsah oEmbed';
$a->strings['Don\'t show the rich content (e.g. embedded PDF), except from the domains listed below.'] = 'Neukazovat obohacený obsah (např. vložené PDF dokumenty), kromě toho z domén vypsaných níže.';
$a->strings['Block public'] = 'Blokovat veřejný přístup';
$a->strings['Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.'] = 'Označením zablokujete veřejný přístup ke všem jinak veřejně přístupným osobním stránkám nepřihlášeným uživatelům.';
$a->strings['Force publish'] = 'Vynutit publikaci';
$a->strings['Check to force all profiles on this site to be listed in the site directory.'] = 'Označením budou všechny profily na tomto serveru uvedeny v adresáři stránky.';
$a->strings['Enabling this may violate privacy laws like the GDPR'] = 'Povolení této funkce může porušit zákony o ochraně soukromí, jako je Obecné nařízení o ochraně osobních údajů (GDPR)';
$a->strings['Global directory URL'] = 'Adresa URL globálního adresáře';
$a->strings['URL to the global directory. If this is not set, the global directory is completely unavailable to the application.'] = 'Adresa URL globálního adresáře. Pokud toto není nastaveno, globální adresář bude aplikaci naprosto nedostupný.';
$a->strings['Private posts by default for new users'] = 'Nastavit pro nové uživatele příspěvky jako soukromé';
$a->strings['Don\'t include post content in email notifications'] = 'Nezahrnovat v e-mailových oznámeních obsah příspěvků';
$a->strings['Don\'t include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.'] = ' V e-mailových oznámeních, které jsou odesílány z tohoto webu, nebudou z důvodů bezpečnosti obsaženy příspěvky/komentáře/soukromé zprávy apod. ';
$a->strings['Disallow public access to addons listed in the apps menu.'] = 'Zakázat veřejný přístup k rozšířením uvedeným v menu aplikace.';
$a->strings['Checking this box will restrict addons listed in the apps menu to members only.'] = 'Označení této volby omezí rozšíření uvedená v menu aplikace pouze pro členy.';
$a->strings['Don\'t embed private images in posts'] = 'Nepovolit přidávání soukromých obrázků do příspěvků';
$a->strings['Don\'t replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.'] = 'Nenahrazovat místní soukromé fotky v příspěvcích vloženou kopií obrázku. To znamená, že kontakty, které obdrží příspěvek obsahující soukromé fotky, budou muset autentikovat a načíst každý obrázek, což může zabrat nějaký čas.';
$a->strings['Explicit Content'] = 'Explicitní obsah';
$a->strings['Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.'] = 'Touto funkcí oznámíte, že je Váš server používán hlavně pro explicitní obsah, který nemusí být vhodný pro mladistvé. Tato informace bude publikována na stránce informací o serveru a může být využita např. globálním adresářem pro odfiltrování Vašeho serveru ze seznamu serverů pro spojení. Poznámka o tom bude navíc zobrazena na stránce registrace.';
$a->strings['Allow Users to set remote_self'] = 'Umožnit uživatelům nastavit remote_self';
$a->strings['With checking this, every user is allowed to mark every contact as a remote_self in the repair contact dialog. Setting this flag on a contact causes mirroring every posting of that contact in the users stream.'] = 'S tímto označením má každý uživatel možnost označit jakékoliv ze svých kontakt jako „remote_self“ v nastavení v dialogu opravit kontakt. Tímto označením se budou zrcadlit všechny správy tohoto kontaktu v uživatelově proudu.';
$a->strings['Community pages for visitors'] = 'Komunitní stránky pro návštěvníky';
$a->strings['Which community pages should be available for visitors. Local users always see both pages.'] = 'Které komunitní stránky by měly být viditelné pro návštěvníky. Místní uživatelé vždy vidí obě stránky.';
$a->strings['Posts per user on community page'] = 'Počet příspěvků na komunitní stránce';
$a->strings['Diaspora support can\'t be enabled because Friendica was installed into a sub directory.'] = 'Podpora pro Diasporu nemůže být zapnuta, protože Friendica byla nainstalována do podadresáře.';
$a->strings['Enable Diaspora support'] = 'Zapnout podporu pro Diaspora';
$a->strings['Verify SSL'] = 'Ověřit SSL';
$a->strings['If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.'] = 'Pokud si přejete, můžete vynutit striktní ověřování certifikátů. To znamená že se nebudete moci připojit k žádnému serveru s vlastním SSL certifikátem.';
$a->strings['Proxy user'] = 'Proxy uživatel';
$a->strings['Proxy URL'] = 'Proxy URL adresa';
$a->strings['Network timeout'] = 'Čas vypršení síťového spojení (timeout)';
$a->strings['Value is in seconds. Set to 0 for unlimited (not recommended).'] = 'Hodnota ve vteřinách. Nastavte 0 pro neomezeno (není doporučeno).';
$a->strings['Maximum Load Average'] = 'Maximální průměrné zatížení';
$a->strings['Maximum system load before delivery and poll processes are deferred - default %d.'] = 'Maximální systémová zátěž, než budou procesy pro doručení a dotazování odloženy - výchozí hodnota %d.';
$a->strings['Minimal Memory'] = 'Minimální paměť';
$a->strings['Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).'] = 'Minimální volná paměť v MB pro pracovníka. Potřebuje přístup do /proc/meminfo - výchozí hodnota 0 (deaktivováno)';
$a->strings['Discover contacts from other servers'] = 'Objevit kontakty z ostatních serverů';
$a->strings['Days between requery'] = 'Dny mezi dotazy';
$a->strings['Search the local directory'] = 'Hledat  v místním adresáři';
$a->strings['Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.'] = 'Prohledat místní adresář místo globálního adresáře. Při místním prohledávání bude každé hledání provedeno v globálním adresáři na pozadí. To vylepšuje výsledky při zopakování hledání.';
$a->strings['Publish server information'] = 'Zveřejnit informace o serveru';
$a->strings['If enabled, general server and usage data will be published. The data contains the name and version of the server, number of users with public profiles, number of posts and the activated protocols and connectors. See <a href="http://the-federation.info/">the-federation.info</a> for details.'] = 'Pokud je tohle povoleno, budou zveřejněna obecná data o serveru a jeho používání. Data obsahují jméno a verzi serveru, počet uživatelů s veřejnými profily, počet příspěvků a aktivované protokoly a konektory. Pro více informací navštivte <a href="http://the-federation.info/">the-federation.info</a>.';
$a->strings['Check upstream version'] = 'Zkontrolovat upstreamovou verzi';
$a->strings['Enables checking for new Friendica versions at github. If there is a new version, you will be informed in the admin panel overview.'] = 'Umožní kontrolovat nové verze Friendica na GitHubu. Pokud existuje nová verze, budete informován/a na přehledu administračního panelu.';
$a->strings['Suppress Tags'] = 'Potlačit štítky';
$a->strings['Suppress showing a list of hashtags at the end of the posting.'] = 'Potlačit zobrazení seznamu hastagů na konci příspěvků.';
$a->strings['Clean database'] = 'Vyčistit databázi';
$a->strings['Remove old remote items, orphaned database records and old content from some other helper tables.'] = 'Odstranit staré vzdálené položky, osiřelé záznamy v databázi a starý obsah z některých dalších pomocných tabulek.';
$a->strings['Lifespan of remote items'] = 'Životnost vzdálených položek';
$a->strings['When the database cleanup is enabled, this defines the days after which remote items will be deleted. Own items, and marked or filed items are always kept. 0 disables this behaviour.'] = 'Pokud je zapnuto čištění databáze, tato funkce definuje počet dnů, po kterých budou smazány vzdálené položky. Vlastní položky a označené či vyplněné položky jsou vždy ponechány. Hodnota 0 tuto funkci vypíná.';
$a->strings['Lifespan of unclaimed items'] = 'Životnost nevyžádaných položek';
$a->strings['When the database cleanup is enabled, this defines the days after which unclaimed remote items (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general lifespan value of remote items if set to 0.'] = 'Pokud je zapnuto čištění databáze, tato funkce definuje počet dnů, po kterých budou smazány nevyžádané vzdálené položky (většinou obsah z přeposílacího serveru). Výchozí hodnota je 90 dní. Pokud je zadaná hodnota 0, výchozí hodnotou bude obecná hodnota životnosti vzdálených položek.';
$a->strings['Lifespan of raw conversation data'] = 'Životnost hrubých dat konverzací';
$a->strings['Maximum numbers of comments per post'] = 'Maximální počet komentářů k příspěvku';
$a->strings['How much comments should be shown for each post? Default value is 100.'] = 'Kolik komentářů by mělo být zobrazeno k každému příspěvku? Výchozí hodnotou je 100.';
$a->strings['Temp path'] = 'Cesta k dočasným souborům';
$a->strings['If you have a restricted system where the webserver can\'t access the system temp path, enter another path here.'] = 'Pokud máte omezený systém, kde webový server nemá přístup k systémové složce temp, zde zadejte jinou cestu.';
$a->strings['Only search in tags'] = 'Hledat pouze ve štítcích';
$a->strings['On large systems the text search can slow down the system extremely.'] = 'Textové vyhledávání může u rozsáhlých systémů znamenat velmi citelné zpomalení systému.';
$a->strings['Maximum number of parallel workers'] = 'Maximální počet paralelních pracovníků';
$a->strings['On shared hosters set this to %d. On larger systems, values of %d are great. Default value is %d.'] = 'Na sdílených hostinzích toto nastavte na hodnotu %d. Na větších systémech se hodí hodnoty kolem %d. Výchozí hodnotou je %d.';
$a->strings['Enable fastlane'] = 'Povolit fastlane';
$a->strings['When enabed, the fastlane mechanism starts an additional worker if processes with higher priority are blocked by processes of lower priority.'] = 'Pokud je toto povoleno, mechanismus fastlane spustí dodatečného pracovníka, pokud jsou procesy vyšší priority zablokované procesy nižší priority.';
$a->strings['Direct relay transfer'] = 'Přímý přenos na server pro přeposílání';
$a->strings['Enables the direct transfer to other servers without using the relay servers'] = 'Umožňuje přímý přenos na ostatní servery bez použití přeposílacích serverů';
$a->strings['Relay scope'] = 'Rozsah příspěvků z přeposílacího serveru';
$a->strings['Can be "all" or "tags". "all" means that every public post should be received. "tags" means that only posts with selected tags should be received.'] = 'Může být buď „vše“ nebo „štítky“. „vše“ znamená, že budou přijaty všechny veřejné příspěvky. „štítky“ znamená, že budou přijaty pouze příspěvky s vybranými štítky.';
$a->strings['Disabled'] = 'Zakázáno';
$a->strings['all'] = 'vše';
$a->strings['tags'] = 'štítky';
$a->strings['Server tags'] = 'Serverové štítky';
$a->strings['Comma separated list of tags for the "tags" subscription.'] = 'Seznam štítků pro odběr „tags“, oddělených čárkami.';
$a->strings['Allow user tags'] = 'Povolit uživatelské štítky';
$a->strings['If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".'] = 'Pokud je toto povoleno, budou štítky z uložených hledání vedle odběru „relay_server_tags“ použity i pro odběr „tags“.';
$a->strings['Start Relocation'] = 'Začít přemístění';
$a->strings['Invalid storage backend setting value.'] = 'Neplatná hodnota nastavení backendu úložiště.';
$a->strings['Database (legacy)'] = 'Databáze (legacy)';
$a->strings['Your DB still runs with MyISAM tables. You should change the engine type to InnoDB. As Friendica will use InnoDB only features in the future, you should change this! See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = 'Vaše databáze stále běží s tabulkami MyISAM. Měl/a byste změnit typ datového úložiště na InnoDB. Protože Friendica bude v budoucnu používat pouze funkce pro InnoDB, měl/a byste to změnit! <a href="%s">Zde</a> naleznete návod, který by pro vás mohl být užitečný při konverzi úložišť. Můžete také použít příkaz <tt>php bin/console.php dbstructure toinnodb</tt> na Vaší instalaci Friendica pro automatickou konverzi.<br />';
$a->strings['There is a new version of Friendica available for download. Your current version is %1$s, upstream version is %2$s'] = 'Je dostupná ke stažení nová verze Friendica. Vaše aktuální verze je %1$s, upstreamová verze je %2$s';
$a->strings['The database update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear.'] = 'Aktualizace databáze selhala. Prosím, spusťte příkaz „php bin/console.php dbstructure update“ z příkazového řádku a podívejte se na chyby, které by se mohly vyskytnout.';
$a->strings['The last update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear. (Some of the errors are possibly inside the logfile.)'] = 'Polslední aktualizace selhala. Prosím spusťte příkaz „php bin/console.php dbstructure update“ z příkazového řádku a podívejte se na chyby, které se mohou stát. (Některé chyby mohou být v záznamvém souboru)';
$a->strings['The worker was never executed. Please check your database structure!'] = 'Pracovník nebyl nikdy spuštěn. Prosím zkontrolujte strukturu Vaší databáze!';
$a->strings['The last worker execution was on %s UTC. This is older than one hour. Please check your crontab settings.'] = 'Pracovník byl naposledy spuštěn v %s UTC. Toto je více než jedna hodina. Prosím zkontrolujte si nastavení crontab.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>.htconfig.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'Konfigurace Friendica je nyní uložena v souboru config/local.config.php, prosím zkopírujte soubor config/local-sample.config.php a přesuňte svou konfiguraci ze souboru <code>.htconfig.php</code>. Pro pomoc při přechodu navštivte <a href="%s">stránku Config v sekci nápovědy</a>.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>config/local.ini.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'Konfigurace Friendica je nyní uložena v souboru config/local.config.php, prosím zkopírujte soubor config/local-sample.config.php a přesuňte svou konfiguraci ze souboru <code>local.config.php</code>. Pro pomoc při přechodu navštivte <a href="%s">stránku Config v sekci nápovědy</a>.';
$a->strings['<a href="%s">%s</a> is not reachable on your system. This is a severe configuration issue that prevents server to server communication. See <a href="%s">the installation page</a> for help.'] = '<a href="%s">%s</a> není na Vašem systému dosažitelné. Tohle je závažná chyba konfigurace, která brání komunikaci mezi servery. Pro pomoc navštivte <a href="%s">stránku instalace</a>.';
$a->strings['Friendica\'s system.basepath was updated from \'%s\' to \'%s\'. Please remove the system.basepath from your db to avoid differences.'] = 'system.basepath Friendica bylo aktualizováno z „%s“ na „%s“. Pro vyhnutí se rozdílům prosím odstraňte z vaší databáze system.basepath.';
$a->strings['Friendica\'s current system.basepath \'%s\' is wrong and the config file \'%s\' isn\'t used.'] = 'Aktuální system.basepath Friendica „%s“ je špatné a konfigurační soubor „%s“ se nepoužívá.';
$a->strings['Friendica\'s current system.basepath \'%s\' is not equal to the config file \'%s\'. Please fix your configuration.'] = 'Aktuální system.basepath „%s“ není rovno konfguračnímu souboru „%s“. Prosím opravte si svou konfiguraci.';
$a->strings['Message queues'] = 'Fronty zpráv';
$a->strings['Server Settings'] = 'Nastavení serveru';
$a->strings['Version'] = 'Verze';
$a->strings['Active addons'] = 'Aktivní doplňky';
$a->strings['Theme %s disabled.'] = 'Motiv %s zakázán.';
$a->strings['Theme %s successfully enabled.'] = 'Motiv %s úspěšně povolen.';
$a->strings['Theme %s failed to install.'] = 'Instalace motivu %s selhala.';
$a->strings['Screenshot'] = 'Snímek obrazovky';
$a->strings['Themes'] = 'Motivy';
$a->strings['Unknown theme.'] = 'Neznámý motiv.';
$a->strings['Reload active themes'] = 'Znovu načíst aktivní motivy';
$a->strings['No themes found on the system. They should be placed in %1$s'] = 'V systému nebyly nalezeny žádné motivy. Měly by být uloženy v %1$s';
$a->strings['[Experimental]'] = '[Experimentální]';
$a->strings['[Unsupported]'] = '[Nepodporováno]';
$a->strings['Display Terms of Service'] = 'Zobrazit Podmínky používání';
$a->strings['Enable the Terms of Service page. If this is enabled a link to the terms will be added to the registration form and the general information page.'] = 'Povolí stránku Podmínky používání. Pokud je toto povoleno, bude na formulář pro registrací a stránku s obecnými informacemi přidán odkaz k podmínkám.';
$a->strings['Display Privacy Statement'] = 'Zobrazit Prohlášení o soukromí';
$a->strings['Privacy Statement Preview'] = 'Náhled Prohlášení o soukromí';
$a->strings['The Terms of Service'] = 'Podmínky používání';
$a->strings['Enter the Terms of Service for your node here. You can use BBCode. Headers of sections should be [h2] and below.'] = 'Zde zadejte podmínky používání vašeho serveru. Můžete používat BBCode. Záhlaví sekcí by měly být označeny [h2] a níže.';
$a->strings['Contact not found'] = 'Kontakt nenalezen.';
$a->strings['No installed applications.'] = 'Žádné nainstalované aplikace.';
$a->strings['Applications'] = 'Aplikace';
$a->strings['Item was not found.'] = 'Položka nebyla nalezena.';
$a->strings['Please login to continue.'] = 'Pro pokračování se prosím přihlaste.';
$a->strings['Overview'] = 'Přehled';
$a->strings['Configuration'] = 'Konfigurace';
$a->strings['Additional features'] = 'Dodatečné vlastnosti';
$a->strings['Database'] = 'Databáze';
$a->strings['DB updates'] = 'Aktualizace databáze';
$a->strings['Inspect Deferred Workers'] = 'Prozkoumat odložené pracovníky';
$a->strings['Inspect worker Queue'] = 'Prozkoumat frontu pro pracovníka';
$a->strings['Diagnostics'] = 'Diagnostika';
$a->strings['PHP Info'] = 'Info o PHP';
$a->strings['probe address'] = 'vyzkoušet adresu';
$a->strings['check webfinger'] = 'vyzkoušet webfinger';
$a->strings['Babel'] = 'Babel';
$a->strings['Addon Features'] = 'Vlastnosti doplňků';
$a->strings['User registrations waiting for confirmation'] = 'Registrace uživatelů čekající na potvrzení';
$a->strings['Daily posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Byl dosažen denní limit %d příspěvku. Příspěvek byl odmítnut.',
	1 => 'Byl dosažen denní limit %d příspěvků. Příspěvek byl odmítnut.',
	2 => 'Byl dosažen denní limit %d příspěvku. Příspěvek byl odmítnut.',
	3 => 'Byl dosažen denní limit %d příspěvků. Příspěvek byl odmítnut.',
];
$a->strings['Weekly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Byl dosažen týdenní limit %d příspěvku. Příspěvek byl odmítnut.',
	1 => 'Byl dosažen týdenní limit %d příspěvků. Příspěvek byl odmítnut.',
	2 => 'Byl dosažen týdenní limit %d příspěvku. Příspěvek byl odmítnut.',
	3 => 'Byl dosažen týdenní limit %d příspěvků. Příspěvek byl odmítnut.',
];
$a->strings['Users'] = 'Uživatelé';
$a->strings['Tools'] = 'Nástroje';
$a->strings['Contact Blocklist'] = 'Blokované kontakty';
$a->strings['Server Blocklist'] = 'Blokované servery';
$a->strings['Delete Item'] = 'Smazat položku';
$a->strings['Item Source'] = 'Zdroj položky';
$a->strings['Profile Details'] = 'Detaily profilu';
$a->strings['Only You Can See This'] = 'Toto můžete vidět jen vy';
$a->strings['Tips for New Members'] = 'Tipy pro nové členy';
$a->strings['People Search - %s'] = 'Vyhledávání lidí - %s';
$a->strings['No matches'] = 'Žádné shody';
$a->strings['Account'] = 'Účet';
$a->strings['Two-factor authentication'] = 'Dvoufázové ověřování';
$a->strings['Display'] = 'Zobrazení';
$a->strings['Social Networks'] = 'Sociální sítě';
$a->strings['Connected apps'] = 'Připojené aplikace';
$a->strings['Export personal data'] = 'Exportovat osobní údaje';
$a->strings['Remove account'] = 'Odstranit účet';
$a->strings['This page is missing a url parameter.'] = 'Této stránce chybí parametr url.';
$a->strings['The post was created'] = 'Příspěvek byl vytvořen';
$a->strings['Failed to remove event'] = 'Odstranění události selhalo';
$a->strings['Event can not end before it has started.'] = 'Událost nemůže končit dříve, než začala.';
$a->strings['Event title and start time are required.'] = 'Název události a datum začátku jsou vyžadovány.';
$a->strings['Starting date and Title are required.'] = 'Počáteční datum a Název jsou vyžadovány.';
$a->strings['Event Starts:'] = 'Událost začíná:';
$a->strings['Required'] = 'Vyžadováno';
$a->strings['Finish date/time is not known or not relevant'] = 'Datum/čas konce není zadán nebo není relevantní';
$a->strings['Event Finishes:'] = 'Akce končí:';
$a->strings['Share this event'] = 'Sdílet tuto událost';
$a->strings['Basic'] = 'Základní';
$a->strings['This calendar format is not supported'] = 'Tento formát kalendáře není podporován.';
$a->strings['No exportable data found'] = 'Nenalezena žádná data pro export';
$a->strings['calendar'] = 'kalendář';
$a->strings['Events'] = 'Události';
$a->strings['View'] = 'Zobrazit';
$a->strings['Create New Event'] = 'Vytvořit novou událost';
$a->strings['list'] = 'seznam';
$a->strings['Contact not found.'] = 'Kontakt nenalezen.';
$a->strings['Invalid contact.'] = 'Neplatný kontakt.';
$a->strings['Contact is deleted.'] = 'Knotakt je smazán.';
$a->strings['Bad request.'] = 'Špatný požadavek.';
$a->strings['Filter'] = 'Filtr';
$a->strings['Members'] = 'Členové';
$a->strings['Click on a contact to add or remove.'] = 'Klikněte na kontakt pro přidání nebo odebrání';
$a->strings['%d contact edited.'] = [
	0 => '%d kontakt upraven',
	1 => '%d kontakty upraveny',
	2 => '%d kontaktu upraveno',
	3 => '%d kontaktů upraveno',
];
$a->strings['Show all contacts'] = 'Zobrazit všechny kontakty';
$a->strings['Pending'] = 'Čekající';
$a->strings['Blocked'] = 'Blokované';
$a->strings['Only show blocked contacts'] = 'Zobrazit pouze blokované kontakty';
$a->strings['Ignored'] = 'Ignorované';
$a->strings['Only show ignored contacts'] = 'Zobrazit pouze ignorované kontakty';
$a->strings['Archived'] = 'Archivované';
$a->strings['Only show archived contacts'] = 'Zobrazit pouze archivované kontakty';
$a->strings['Hidden'] = 'Skryté';
$a->strings['Only show hidden contacts'] = 'Zobrazit pouze skryté kontakty';
$a->strings['Search your contacts'] = 'Prohledat vaše kontakty';
$a->strings['Results for: %s'] = 'Výsledky pro: %s';
$a->strings['Update'] = 'Aktualizace';
$a->strings['Unblock'] = 'Odblokovat';
$a->strings['Unignore'] = 'Přestat ignorovat';
$a->strings['Batch Actions'] = 'Souhrnné akce';
$a->strings['Conversations started by this contact'] = 'Konverzace, které tento kontakt začal';
$a->strings['Posts and Comments'] = 'Příspěvky a komentáře';
$a->strings['Advanced Contact Settings'] = 'Pokročilé nastavení kontaktu';
$a->strings['Mutual Friendship'] = 'Vzájemné přátelství';
$a->strings['is a fan of yours'] = 'je váš fanoušek';
$a->strings['you are a fan of'] = 'jste fanouškem';
$a->strings['Visit %s\'s profile [%s]'] = 'Navštivte profil uživatele %s [%s]';
$a->strings['Contact update failed.'] = 'Aktualizace kontaktu selhala.';
$a->strings['Return to contact editor'] = 'Zpět k editoru kontaktu';
$a->strings['Name'] = 'Jméno';
$a->strings['Account Nickname'] = 'Přezdívka účtu';
$a->strings['Account URL'] = 'URL adresa účtu';
$a->strings['Poll/Feed URL'] = 'URL adresa poll/feed';
$a->strings['New photo from this URL'] = 'Nová fotka z této URL adresy';
$a->strings['Follower (%s)'] = [
	0 => 'Sledující (%s)',
	1 => 'Sledující (%s)',
	2 => 'Sledující (%s)',
	3 => 'Sledující (%s)',
];
$a->strings['Following (%s)'] = [
	0 => 'Sledovaný (%s)',
	1 => 'Sledovaní (%s)',
	2 => 'Sledovaní (%s)',
	3 => 'Sleduji (%s)',
];
$a->strings['Mutual friend (%s)'] = [
	0 => 'Vzájemný přítel (%s)',
	1 => 'Vzájemní přátelé (%s)',
	2 => 'Vzájemní přátelé (%s)',
	3 => 'Vzájemní přátelé (%s)',
];
$a->strings['Contact (%s)'] = [
	0 => 'Kontakt (%s)',
	1 => 'Kontakty (%s)',
	2 => 'Kontakty (%s)',
	3 => 'Kontakty (%s)',
];
$a->strings['Access denied.'] = 'Přístup odmítnut.';
$a->strings['Submit Request'] = 'Odeslat požadavek';
$a->strings['You already added this contact.'] = 'Již jste si tento kontakt přidal/a';
$a->strings['The network type couldn\'t be detected. Contact can\'t be added.'] = 'Typ sítě nemohl být detekován. Kontakt nemůže být přidán.';
$a->strings['Diaspora support isn\'t enabled. Contact can\'t be added.'] = 'Podpora pro Diasporu není zapnuta. Kontakt nemůže být přidán.';
$a->strings['Please answer the following:'] = 'Odpovězte, prosím, následující:';
$a->strings['Your Identity Address:'] = 'Vaše adresa identity:';
$a->strings['Profile URL'] = 'URL profilu';
$a->strings['Tags:'] = 'Štítky:';
$a->strings['Add a personal note:'] = 'Přidejte osobní poznámku:';
$a->strings['The contact could not be added.'] = 'Kontakt nemohl být přidán.';
$a->strings['Invalid request.'] = 'Neplatný požadavek.';
$a->strings['Profile Match'] = 'Shoda profilu';
$a->strings['Failed to update contact record.'] = 'Nepodařilo se aktualizovat kontakt.';
$a->strings['Contact has been unblocked'] = 'Kontakt byl odblokován';
$a->strings['Contact has been blocked'] = 'Kontakt byl zablokován';
$a->strings['Contact has been unignored'] = 'Kontakt přestal být ignorován';
$a->strings['Contact has been ignored'] = 'Kontakt bude ignorován';
$a->strings['You are mutual friends with %s'] = 'Jste vzájemní přátelé s uživatelem %s';
$a->strings['You are sharing with %s'] = 'Sdílíte s uživatelem %s';
$a->strings['%s is sharing with you'] = '%s s vámi sdílí';
$a->strings['Private communications are not available for this contact.'] = 'Soukromá komunikace není dostupná pro tento kontakt.';
$a->strings['Never'] = 'Nikdy';
$a->strings['(Update was not successful)'] = '(Aktualizace nebyla úspěšná)';
$a->strings['(Update was successful)'] = '(Aktualizace byla úspěšná)';
$a->strings['Suggest friends'] = 'Navrhnout přátele';
$a->strings['Network type: %s'] = 'Typ sítě: %s';
$a->strings['Communications lost with this contact!'] = 'Komunikace s tímto kontaktem byla ztracena!';
$a->strings['Fetch further information for feeds'] = 'Načíst další informace pro kanál';
$a->strings['Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.'] = 'Načíst informace jako obrázky náhledu, nadpis a popisek z položky kanálu. Toto můžete aktivovat, pokud kanál neobsahuje moc textu. Klíčová slova jsou vzata z hlavičky meta v položce kanálu a jsou zveřejněna jako hashtagy.';
$a->strings['Fetch information'] = 'Načíst informace';
$a->strings['Fetch keywords'] = 'Načíst klíčová slova';
$a->strings['Fetch information and keywords'] = 'Načíst informace a klíčová slova';
$a->strings['No mirroring'] = 'Žádné zrcadlení';
$a->strings['Mirror as my own posting'] = 'Zrcadlit jako mé vlastní příspěvky';
$a->strings['Contact Information / Notes'] = 'Kontaktní informace / poznámky';
$a->strings['Contact Settings'] = 'Nastavení kontaktů';
$a->strings['Contact'] = 'Kontakt';
$a->strings['Their personal note'] = 'Jejich osobní poznámka';
$a->strings['Edit contact notes'] = 'Upravit poznámky kontaktu';
$a->strings['Block/Unblock contact'] = 'Blokovat / Odblokovat kontakt';
$a->strings['Ignore contact'] = 'Ignorovat kontakt';
$a->strings['View conversations'] = 'Zobrazit konverzace';
$a->strings['Last update:'] = 'Poslední aktualizace:';
$a->strings['Update public posts'] = 'Aktualizovat veřejné příspěvky';
$a->strings['Update now'] = 'Aktualizovat';
$a->strings['Awaiting connection acknowledge'] = 'Čekám na potrvzení spojení';
$a->strings['Currently blocked'] = 'V současnosti zablokováno';
$a->strings['Currently ignored'] = 'V současnosti ignorováno';
$a->strings['Currently archived'] = 'Aktuálně archivován';
$a->strings['Hide this contact from others'] = 'Skrýt tento kontakt před ostatními';
$a->strings['Replies/likes to your public posts <strong>may</strong> still be visible'] = 'Odpovědi/oblíbení na vaše veřejné příspěvky <strong>mohou</strong> být stále viditelné';
$a->strings['Notification for new posts'] = 'Oznámení o nových příspěvcích';
$a->strings['Send a notification of every new post of this contact'] = 'Posílat oznámení o každém novém příspěvku tohoto kontaktu';
$a->strings['Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected'] = 'Seznam klíčových slov, které by neměly být převáděna na hashtagy, když je zvoleno „Načíst informace a klíčová slova“. Oddělujte čárkami';
$a->strings['Actions'] = 'Akce';
$a->strings['Status'] = 'Stav';
$a->strings['Mirror postings from this contact'] = 'Zrcadlení příspěvků od tohoto kontaktu';
$a->strings['Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'] = 'Označit tento kontakt jako „remote_self“, s tímto nastavením bude Friendica znovupublikovat všechny nové příspěvky od tohoto kontaktu.';
$a->strings['Refetch contact data'] = 'Znovu načíst data kontaktu';
$a->strings['Toggle Blocked status'] = 'Přepínat stav Blokováno';
$a->strings['Toggle Ignored status'] = 'Přepínat stav Ignorováno';
$a->strings['No suggestions available. If this is a new site, please try again in 24 hours.'] = 'Nejsou dostupné žádné návrhy. Pokud je toto nový server, zkuste to znovu za 24 hodin.';
$a->strings['You aren\'t following this contact.'] = 'Tento kontakt nesledujete.';
$a->strings['Unfollowing is currently not supported by your network.'] = 'Zrušení sledování není aktuálně na Vaši síti podporováno.';
$a->strings['Disconnect/Unfollow'] = 'Odpojit se/Zrušit sledování';
$a->strings['No results.'] = 'Žádné výsledky.';
$a->strings['This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.'] = 'Tento komunitní proud ukazuje všechny veřejné příspěvky, které tento server přijme. Nemusí odrážet názory uživatelů serveru.';
$a->strings['Community option not available.'] = 'Možnost komunity není dostupná.';
$a->strings['Not available.'] = 'Není k dispozici.';
$a->strings['Credits'] = 'Poděkování';
$a->strings['Friendica is a community project, that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!'] = 'Friendica je komunitní projekt, který by nebyl možný bez pomoci mnoha lidí. Zde je seznam těch, kteří přispěli ke kódu nebo k překladu Friendica. Děkujeme všem!';
$a->strings['Error'] = [
	0 => 'Chyba',
	1 => 'Chyby',
	2 => 'Chyb',
	3 => 'Chyb',
];
$a->strings['Source input'] = 'Zdrojový vstup';
$a->strings['BBCode::toPlaintext'] = 'BBCode::toPlaintext';
$a->strings['BBCode::convert (raw HTML)'] = 'BBCode::convert (hrubé HTML)';
$a->strings['BBCode::convert'] = 'BBCode::convert';
$a->strings['BBCode::convert => HTML::toBBCode'] = 'BBCode::convert => HTML::toBBCode';
$a->strings['BBCode::toMarkdown'] = 'BBCode::toMarkdown';
$a->strings['BBCode::toMarkdown => Markdown::convert'] = 'BBCode::toMarkdown => Markdown::convert';
$a->strings['BBCode::toMarkdown => Markdown::toBBCode'] = 'BBCode::toMarkdown => Markdown::toBBCode';
$a->strings['BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode'] = 'BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode';
$a->strings['Item Body'] = 'Tělo položky';
$a->strings['Item Tags'] = 'Štítky položky';
$a->strings['Source input (Diaspora format)'] = 'Zdrojový vstup (formát Diaspora)';
$a->strings['Markdown::convert (raw HTML)'] = 'Markdown::convert (hrubé HTML)';
$a->strings['Markdown::convert'] = 'Markdown::convert';
$a->strings['Markdown::toBBCode'] = 'Markdown::toBBCode';
$a->strings['Raw HTML input'] = 'Hrubý HTML vstup';
$a->strings['HTML Input'] = 'HTML vstup';
$a->strings['HTML::toBBCode'] = 'HTML::toBBCode';
$a->strings['HTML::toBBCode => BBCode::convert'] = 'HTML::toBBCode => BBCode::convert';
$a->strings['HTML::toBBCode => BBCode::convert (raw HTML)'] = 'HTML::toBBCode => BBCode::convert (hrubé HTML)';
$a->strings['HTML::toBBCode => BBCode::toPlaintext'] = 'HTML::toBBCode => BBCode::toPlaintext';
$a->strings['HTML::toMarkdown'] = 'HTML::toMarkdown';
$a->strings['HTML::toPlaintext'] = 'HTML::toPlaintext';
$a->strings['HTML::toPlaintext (compact)'] = 'HTML::toPlaintext (kompaktní)';
$a->strings['Source text'] = 'Zdrojový text';
$a->strings['BBCode'] = 'BBCode';
$a->strings['Markdown'] = 'Markdown';
$a->strings['HTML'] = 'HTML';
$a->strings['You must be logged in to use this module'] = 'Pro používání tohoto modulu musíte být přihlášen/a';
$a->strings['Source URL'] = 'Zdrojová adresa URL';
$a->strings['Time Conversion'] = 'Časový převod';
$a->strings['Friendica provides this service for sharing events with other networks and friends in unknown timezones.'] = 'Friendica poskytuje tuto službu ke sdílení událostí s ostatními sítěmi a přáteli v neznámých časových pásmech';
$a->strings['UTC time: %s'] = 'UTC čas: %s';
$a->strings['Current timezone: %s'] = 'Aktuální časové pásmo: %s';
$a->strings['Converted localtime: %s'] = 'Převedený místní čas : %s';
$a->strings['Please select your timezone:'] = 'Prosím, vyberte své časové pásmo:';
$a->strings['Only logged in users are permitted to perform a probing.'] = 'Pouze přihlášení uživatelé mohou zkoušet adresy.';
$a->strings['Lookup address'] = 'Najít adresu';
$a->strings['No entries (some entries may be hidden).'] = 'Žádné záznamy (některé položky mohou být skryty).';
$a->strings['Find on this site'] = 'Najít na tomto webu';
$a->strings['Results for:'] = 'Výsledky pro:';
$a->strings['Site Directory'] = 'Adresář serveru';
$a->strings['- select -'] = '- vyberte -';
$a->strings['Suggested contact not found.'] = 'Navrhovaný kontakt nenalezen.';
$a->strings['Friend suggestion sent.'] = 'Návrh přátelství odeslán. ';
$a->strings['Suggest Friends'] = 'Navrhnout přátele';
$a->strings['Suggest a friend for %s'] = 'Navrhnout přítele pro uživatele %s';
$a->strings['Installed addons/apps:'] = 'Nainstalované doplňky/aplikace:';
$a->strings['No installed addons/apps'] = 'Žádne nainstalované doplňky/aplikace';
$a->strings['Read about the <a href="%1$s/tos">Terms of Service</a> of this node.'] = 'Přečtěte si o <a href="%1$s/tos">Podmínkách používání</a> tohoto serveru.';
$a->strings['On this server the following remote servers are blocked.'] = 'Na tomto serveru jsou zablokovány následující vzdálené servery.';
$a->strings['Reason for the block'] = 'Důvody pro zablokování';
$a->strings['This is Friendica, version %s that is running at the web location %s. The database version is %s, the post update version is %s.'] = 'Tohle je Friendica, verze %s, běžící na webové adrese %s. Verze databáze je %s, verze post update je %s.';
$a->strings['Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.'] = 'Pro více informací o projektu Friendica, prosím, navštivte stránku <a href="https://friendi.ca">Friendi.ca</a>';
$a->strings['Bug reports and issues: please visit'] = 'Pro hlášení chyb a námětů na změny prosím navštivte';
$a->strings['the bugtracker at github'] = 'sledování chyb na GitHubu';
$a->strings['Suggestions, praise, etc. - please email "info" at "friendi - dot - ca'] = 'Návrhy, pochvaly atd. prosím posílejte na adresu „info“ zavináč „friendi“-tečka-„ca“';
$a->strings['No profile'] = 'Žádný profil';
$a->strings['Help:'] = 'Nápověda:';
$a->strings['Welcome to %s'] = 'Vítejte na %s';
$a->strings['Friendica Communications Server - Setup'] = 'Komunikační server Friendica - Nastavení';
$a->strings['System check'] = 'Zkouška systému';
$a->strings['Next'] = 'Dále';
$a->strings['Check again'] = 'Vyzkoušet znovu';
$a->strings['Base settings'] = 'Základní nastavení';
$a->strings['Base path to installation'] = 'Základní cesta k instalaci';
$a->strings['If the system cannot detect the correct path to your installation, enter the correct path here. This setting should only be set if you are using a restricted system and symbolic links to your webroot.'] = 'Pokud systém nemůže detekovat správnou cestu k Vaší instalaci, zde zadejte jinou cestu. Toto nastavení by mělo být nastaveno pouze, pokud používáte omezený systém a symbolické odkazy ke kořenové složce webu.';
$a->strings['Database connection'] = 'Databázové spojení';
$a->strings['In order to install Friendica we need to know how to connect to your database.'] = 'Pro instalaci Friendica potřebujeme znát připojení k vaší databázi.';
$a->strings['Please contact your hosting provider or site administrator if you have questions about these settings.'] = 'Pokud máte otázky k následujícím nastavením, obraťte se na svého poskytovatele hostingu nebo administrátora serveru.';
$a->strings['The database you specify below should already exist. If it does not, please create it before continuing.'] = 'Databáze, kterou uvedete níže, by již měla existovat. Pokud to tak není, prosíme, vytvořte ji před pokračováním.';
$a->strings['Database Server Name'] = 'Jméno databázového serveru';
$a->strings['Database Login Name'] = 'Přihlašovací jméno k databázi';
$a->strings['Database Login Password'] = 'Heslo k databázovému účtu ';
$a->strings['For security reasons the password must not be empty'] = 'Z bezpečnostních důvodů nesmí být heslo prázdné.';
$a->strings['Database Name'] = 'Jméno databáze';
$a->strings['Please select a default timezone for your website'] = 'Prosím, vyberte výchozí časové pásmo pro váš server';
$a->strings['Site settings'] = 'Nastavení webu';
$a->strings['Site administrator email address'] = 'E-mailová adresa administrátora webu';
$a->strings['Your account email address must match this in order to use the web admin panel.'] = 'Vaše e-mailová adresa účtu se musí s touto shodovat, aby bylo možné využívat administrační panel ve webovém rozhraní.';
$a->strings['System Language:'] = 'Systémový jazyk';
$a->strings['Set the default language for your Friendica installation interface and to send emails.'] = 'Nastavte si výchozí jazyk pro vaše instalační rozhraní Friendica a pro odesílání e-mailů.';
$a->strings['Your Friendica site database has been installed.'] = 'Databáze vašeho serveru Friendica  byla nainstalována.';
$a->strings['Installation finished'] = 'Instalace dokončena';
$a->strings['<h1>What next</h1>'] = '<h1>Co dál</h1>';
$a->strings['IMPORTANT: You will need to [manually] setup a scheduled task for the worker.'] = 'DŮLEŽITÉ: Budete si muset [manuálně] nastavit naplánovaný úkol pro pracovníka.';
$a->strings['Go to your new Friendica node <a href="%s/register">registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.'] = 'Přejděte k <a href="%s/register">registrační stránce</a> vašeho nového serveru Friendica a zaregistrujte se jako nový uživatel. Nezapomeňte použít stejný e-mail, který jste zadal/a jako administrátorský e-mail. To vám umožní navštívit panel pro administraci stránky.';
$a->strings['Total invitation limit exceeded.'] = 'Celkový limit pozvánek byl překročen';
$a->strings['%s : Not a valid email address.'] = '%s : není platná e-mailová adresa.';
$a->strings['Please join us on Friendica'] = 'Prosím přidejte se k nám na Friendica';
$a->strings['Invitation limit exceeded. Please contact your site administrator.'] = 'Limit pozvánek byl překročen. Prosím kontaktujte administrátora vaší stránky.';
$a->strings['%s : Message delivery failed.'] = '%s : Doručení zprávy se nezdařilo.';
$a->strings['%d message sent.'] = [
	0 => '%d zpráva odeslána.',
	1 => '%d zprávy odeslány.',
	2 => '%d zprávy odesláno.',
	3 => '%d zpráv odesláno.',
];
$a->strings['You have no more invitations available'] = 'Nemáte k dispozici žádné další pozvánky';
$a->strings['Visit %s for a list of public sites that you can join. Friendica members on other sites can all connect with each other, as well as with members of many other social networks.'] = 'Navštiv %s pro seznam veřejných serverů, na kterých se můžeš přidat. Členové Friendica na jiných serverech se mohou spojit mezi sebou, jakožto i se členy mnoha dalších sociálních sítí.';
$a->strings['To accept this invitation, please visit and register at %s or any other public Friendica website.'] = 'K přijetí této pozvánky prosím navštivte a registrujte se na %s nebo na kterémkoliv jiném veřejném serveru Friendica.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks. See %s for a list of alternate Friendica sites you can join.'] = 'Stránky Friendica jsou navzájem propojené a vytváří tak obrovský sociální web s dúrazem na soukromí, kterou vlastní a kontrojují její členové, Mohou se také připojit k mnoha tradičním socilním sítím. Navštivte %s pro seznam alternativních serverů Friendica, ke kterým se můžete přidat.';
$a->strings['Our apologies. This system is not currently configured to connect with other public sites or invite members.'] = 'Omlouváme se. Systém nyní není nastaven tak, aby se připojil k ostatním veřejným serverům nebo umožnil pozvat nové členy.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks.'] = 'Stránky Friendica jsou navzájem propojené a vytváří tak obrovský sociální web s dúrazem na soukromí, kterou vlastní a kontrolují její členové. Mohou se také připojit k mnoha tradičním sociálním sítím.';
$a->strings['To accept this invitation, please visit and register at %s.'] = 'Pokud chcete tuto pozvánku přijmout, prosím navštivte %s a registrujte se tam.';
$a->strings['Send invitations'] = 'Poslat pozvánky';
$a->strings['Enter email addresses, one per line:'] = 'Zadejte e-mailové adresy, jednu na řádek:';
$a->strings['You are cordially invited to join me and other close friends on Friendica - and help us to create a better social web.'] = 'Jsi srdečně pozván/a se připojit ke mně a k mým blízkým přátelům na Friendica - a pomoci nám vytvořit lepší sociální web.';
$a->strings['You will need to supply this invitation code: $invite_code'] = 'Budeš muset zadat tento pozvánkový kód: $invite_code';
$a->strings['Once you have registered, please connect with me via my profile page at:'] = 'Jakmile se zaregistruješ, prosím spoj se se mnou přes mou profilovu stránku na:';
$a->strings['For more information about the Friendica project and why we feel it is important, please visit http://friendi.ca'] = 'Pro více informací o projektu Friendica a proč si myslím, že je důležitý, prosím navštiv http://friendi.ca';
$a->strings['Compose new personal note'] = 'Napsat novou osobní poznámku';
$a->strings['Compose new post'] = 'Napsat nový příspěvek';
$a->strings['The feed for this item is unavailable.'] = 'Proud pro tuto položku je nedostupný.';
$a->strings['System down for maintenance'] = 'Systém vypnut z důvodů údržby';
$a->strings['Files'] = 'Soubory';
$a->strings['Upload'] = 'Nahrát';
$a->strings['Sorry, maybe your upload is bigger than the PHP configuration allows'] = 'Omlouváme se, možná je váš soubor větší než je povolené maximum dle nastavení PHP';
$a->strings['Or - did you try to upload an empty file?'] = 'Nebo - nenahrával/a jste prázdný soubor?';
$a->strings['File exceeds size limit of %s'] = 'Velikost souboru přesáhla limit %s';
$a->strings['File upload failed.'] = 'Nahrání souboru se nezdařilo.';
$a->strings['Unable to process image.'] = 'Obrázek není možné zprocesovat';
$a->strings['Image upload failed.'] = 'Nahrání obrázku selhalo.';
$a->strings['Normal Account Page'] = 'Normální stránka účtu';
$a->strings['Soapbox Page'] = 'Propagační stránka';
$a->strings['Automatic Friend Page'] = 'Stránka s automatickými přátely';
$a->strings['Personal Page'] = 'Osobní stránka';
$a->strings['Organisation Page'] = 'Stránka organizace';
$a->strings['News Page'] = 'Zpravodajská stránka';
$a->strings['%s contact unblocked'] = [
	0 => '%s kontakt odblokován',
	1 => '%s kontakty odblokovány',
	2 => '%s kontaktu odblokováno',
	3 => '%s kontaktů odblokováno',
];
$a->strings['Remote Contact Blocklist'] = 'Blokované vzdálené kontakty';
$a->strings['This page allows you to prevent any message from a remote contact to reach your node.'] = 'Tato stránka vám umožňuje zabránit jakýmkoliv zprávám ze vzdáleného kontaktu, aby se k vašemu serveru dostaly.';
$a->strings['Block Remote Contact'] = 'Zablokovat vzdálený kontakt';
$a->strings['select all'] = 'Vybrat vše';
$a->strings['select none'] = 'nevybrat žádný';
$a->strings['No remote contact is blocked from this node.'] = 'Žádný vzdálený kontakt není na tomto serveru zablokován.';
$a->strings['Blocked Remote Contacts'] = 'Zablokované vzdálené kontakty';
$a->strings['Block New Remote Contact'] = 'Zablokovat nový vzdálený kontakt';
$a->strings['Photo'] = 'Fotka';
$a->strings['Reason'] = 'Důvod';
$a->strings['%s total blocked contact'] = [
	0 => 'Celkem %s zablokovaný kontakt',
	1 => 'Celkem %s zablokované kontakty',
	2 => 'Celkem %s zablokovaného kontaktu',
	3 => 'Celkem %s zablokovaných kontaktů',
];
$a->strings['URL of the remote contact to block.'] = 'Adresa URL vzdáleného kontaktu k zablokování.';
$a->strings['Block Reason'] = 'Důvod blokace';
$a->strings['Block reason'] = 'Důvod zablokování';
$a->strings['Check to delete this entry from the blocklist'] = 'Zaškrtnutím odstraníte tuto položku z blokovacího seznamu';
$a->strings['Save changes to the blocklist'] = 'Uložit změny do blokovacího seznamu';
$a->strings['Current Entries in the Blocklist'] = 'Aktuální položky v bokovacím seznamu';
$a->strings['Item marked for deletion.'] = 'Položka označená ke smazání';
$a->strings['Delete this Item'] = 'Smazat tuto položku';
$a->strings['On this page you can delete an item from your node. If the item is a top level posting, the entire thread will be deleted.'] = 'Na této stránce můžete smazat položku z vašeho serveru. Pokud je položkou příspěvek nejvyššího stupně, bude smazáno celé vlákno.';
$a->strings['You need to know the GUID of the item. You can find it e.g. by looking at the display URL. The last part of http://example.com/display/123456 is the GUID, here 123456.'] = 'Budete muset znát číslo GUID položky. Můžete jej najít např. v adrese URL. Poslední část adresy http://priklad.cz/display/123456 je GUID, v tomto případě 123456';
$a->strings['GUID'] = 'GUID';
$a->strings['The GUID of the item you want to delete.'] = 'Číslo GUID položky, kterou chcete smazat';
$a->strings['Type'] = 'Typ';
$a->strings['Item not found'] = 'Položka nenalezena';
$a->strings['Item Guid'] = 'Číslo GUID položky';
$a->strings['Registered users'] = 'Registrovaní uživatelé';
$a->strings['Pending registrations'] = 'Čekající registrace';
$a->strings['%s user blocked'] = [
	0 => '%s uživatel blokován',
	1 => '%s uživatelé blokování',
	2 => '%s uživatele blokováno',
	3 => '%s uživatelů blokováno',
];
$a->strings['You can\'t remove yourself'] = 'Nemůžete odstranit sebe sama';
$a->strings['%s user deleted'] = [
	0 => '%s uživatel smazán',
	1 => '%s uživatelů smazáno',
	2 => '%s uživatele smazáno',
	3 => '%s uživatelů smazáno',
];
$a->strings['Register date'] = 'Datum registrace';
$a->strings['Last login'] = 'Datum posledního přihlášení';
$a->strings['User blocked'] = 'Uživatel zablokován';
$a->strings['Site admin'] = 'Administrátor webu';
$a->strings['Account expired'] = 'Účtu vypršela platnost';
$a->strings['Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'] = 'Vybraní uživatelé budou smazáni!\n\n Vše, co tito uživatelé na těchto stránkách vytvořili, bude trvale odstraněno!\n\nOpravdu chcete pokračovat?';
$a->strings['The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'] = 'Uživatel {0} bude smazán!\n\n Vše, co tento uživatel na těchto stránkách vytvořil, bude trvale odstraněno!\n\n Opravdu chcete pokračovat?';
$a->strings['User "%s" deleted'] = 'Uživatel „%s“ smazán';
$a->strings['User "%s" blocked'] = 'Uživatel „%s“ zablokován';
$a->strings['%s user unblocked'] = [
	0 => '%s uživatel odblokován',
	1 => '%s uživatelé odblokováni',
	2 => '%s uživatele odblokováno',
	3 => '%s uživatelů odblokováno',
];
$a->strings['User "%s" unblocked'] = 'Uživatel „%s“ odblokován';
$a->strings['New User'] = 'Nový uživatel';
$a->strings['Add User'] = 'Přidat uživatele';
$a->strings['Name of the new user.'] = 'Jméno nového uživatele.';
$a->strings['Nickname'] = 'Přezdívka';
$a->strings['Nickname of the new user.'] = 'Přezdívka nového uživatele.';
$a->strings['Email address of the new user.'] = 'Emailová adresa nového uživatele.';
$a->strings['Permanent deletion'] = 'Trvalé smazání';
$a->strings['User waiting for permanent deletion'] = 'Uživatel čekající na trvalé smazání';
$a->strings['Account approved.'] = 'Účet schválen.';
$a->strings['Request date'] = 'Datum požadavku';
$a->strings['No registrations.'] = 'Žádné registrace.';
$a->strings['Note from the user'] = 'Poznámka od uživatele';
$a->strings['Deny'] = 'Odmítnout';
$a->strings['Show Ignored Requests'] = 'Zobrazit ignorované požadavky';
$a->strings['Hide Ignored Requests'] = 'Skrýt ignorované požadavky';
$a->strings['Notification type:'] = 'Typ oznámení:';
$a->strings['Suggested by:'] = 'Navrhl/a:';
$a->strings['Claims to be known to you: '] = 'Vaši údajní známí: ';
$a->strings['Shall your connection be bidirectional or not?'] = 'Má vaše spojení být obousměrné, nebo ne?';
$a->strings['Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.'] = 'Přijetí uživatele %s jako přítele dovolí uživateli %s odebírat Vaše příspěvky a Vy budete také přijímat aktualizace od něj ve Vašem kanále.';
$a->strings['Accepting %s as a subscriber allows them to subscribe to your posts, but you will not receive updates from them in your news feed.'] = 'Přijetí uživatele %s jako odběratele mu dovolí odebírat Vaše příspěvky, ale nebudete od něj přijímat aktualizace ve Vašem kanále.';
$a->strings['Friend'] = 'Přítel';
$a->strings['Subscriber'] = 'Odběratel';
$a->strings['No introductions.'] = 'Žádné představení.';
$a->strings['No more %s notifications.'] = 'Žádná další %s oznámení';
$a->strings['Network Notifications'] = 'Síťová oznámení';
$a->strings['System Notifications'] = 'Systémová oznámení';
$a->strings['Personal Notifications'] = 'Osobní oznámení';
$a->strings['Home Notifications'] = 'Oznámení na domovské stránce';
$a->strings['Show unread'] = 'Zobrazit nepřečtené';
$a->strings['{0} requested registration'] = '{0} požaduje registraci';
$a->strings['Authorize application connection'] = 'Povolit připojení aplikacím';
$a->strings['Do you want to authorize this application to access your posts and contacts, and/or create new posts for you?'] = 'Chcete umožnit této aplikaci přístup k vašim příspěvkům a kontaktům a/nebo k vytváření Vašich nových příspěvků?';
$a->strings['No contact provided.'] = 'Nebyl poskytnut žádný kontakt.';
$a->strings['Couldn\'t fetch information for contact.'] = 'Nelze načíst informace pro kontakt.';
$a->strings['Couldn\'t fetch friends for contact.'] = 'Nelze načíst přátele pro kontakt.';
$a->strings['Done'] = 'Hotovo';
$a->strings['success'] = 'úspěch';
$a->strings['failed'] = 'selhalo';
$a->strings['ignored'] = 'ignorován';
$a->strings['Keep this window open until done.'] = 'Toto okno nechte otevřené až do konce.';
$a->strings['Invalid photo with id %s.'] = 'Neplatná fotka s ID %s.';
$a->strings['Edit post'] = 'Upravit příspěvek';
$a->strings['web link'] = 'webový odkaz';
$a->strings['Insert video link'] = 'Vložit odkaz na video';
$a->strings['video link'] = 'odkaz na video';
$a->strings['Insert audio link'] = 'Vložit odkaz na audio';
$a->strings['audio link'] = 'odkaz na audio';
$a->strings['Remove Item Tag'] = 'Odebrat štítek položky';
$a->strings['Select a tag to remove: '] = 'Vyberte štítek k odebrání: ';
$a->strings['Remove'] = 'Odstranit';
$a->strings['Remote privacy information not available.'] = 'Vzdálené informace o soukromí nejsou k dispozici.';
$a->strings['Visible to:'] = 'Viditelné pro:';
$a->strings['Followers (%s)'] = 'Sledující (%s)';
$a->strings['No contacts.'] = 'Žádné kontakty.';
$a->strings['%s\'s posts'] = 'Příspěvky uživatele %s';
$a->strings['%s\'s comments'] = 'Komentáře uživatele %s';
$a->strings['%s\'s timeline'] = 'Časová osa uživatele %s';
$a->strings['Image exceeds size limit of %s'] = 'Velikost obrázku překročila limit %s';
$a->strings['Image upload didn\'t complete, please try again'] = 'Nahrávání obrázku nebylo dokončeno, zkuste to prosím znovu';
$a->strings['Image file is missing'] = 'Chybí soubor obrázku';
$a->strings['Server can\'t accept new file upload at this time, please contact your administrator'] = 'Server v tuto chvíli nemůže akceptovat nové nahrané soubory, prosím kontaktujte vašeho administrátora';
$a->strings['Image file is empty.'] = 'Soubor obrázku je prázdný.';
$a->strings['View Album'] = 'Zobrazit album';
$a->strings['Profile not found.'] = 'Profil nenalezen.';
$a->strings['Full Name:'] = 'Celé jméno:';
$a->strings['Member since:'] = 'Členem od:';
$a->strings['j F, Y'] = 'j F, Y';
$a->strings['j F'] = 'j F';
$a->strings['Birthday:'] = 'Narozeniny:';
$a->strings['Age: '] = 'Věk: ';
$a->strings['Description:'] = 'Popis:';
$a->strings['Profile unavailable.'] = 'Profil není k dispozici.';
$a->strings['Invalid locator'] = 'Neplatný odkaz';
$a->strings['Remote subscription can\'t be done for your network. Please subscribe directly on your system.'] = 'Vzdálený odběr nemůže být na vaší síti proveden. Prosím, přihlaste se k odběru přímo na vašem systému.';
$a->strings['Friend/Connection Request'] = 'Požadavek o přátelství/spojení';
$a->strings['This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.'] = 'Došlo k překročení maximálního povoleného počtu registrací za den na tomto serveru. Zkuste to  zítra znovu.';
$a->strings['You may (optionally) fill in this form via OpenID by supplying your OpenID and clicking "Register".'] = 'Tento formulář můžete (volitelně) vyplnit s pomocí OpenID tím, že vyplníte své OpenID a kliknete na tlačítko „Zaregistrovat“.';
$a->strings['If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.'] = 'Pokud nepoužíváte OpenID, nechte prosím toto pole prázdné a vyplňte zbylé položky.';
$a->strings['Your OpenID (optional): '] = 'Vaše OpenID (nepovinné): ';
$a->strings['Include your profile in member directory?'] = 'Chcete zahrnout váš profil v adresáři členů?';
$a->strings['Note for the admin'] = 'Poznámka pro administrátora';
$a->strings['Leave a message for the admin, why you want to join this node'] = 'Zanechejte administrátorovi zprávu, proč se k tomuto serveru chcete připojit';
$a->strings['Membership on this site is by invitation only.'] = 'Členství na tomto webu je pouze na pozvání.';
$a->strings['Your invitation code: '] = 'Váš kód pozvánky: ';
$a->strings['New Password:'] = 'Nové heslo:';
$a->strings['Leave empty for an auto generated password.'] = 'Ponechte prázdné pro automatické vygenerovaní hesla.';
$a->strings['Confirm:'] = 'Potvrďte:';
$a->strings['Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be "<strong>nickname@%s</strong>".'] = 'Vyberte si přezdívku pro váš profil. Musí začínat textovým znakem. Vaše profilová adresa na této stránce bude mít tvar „<strong>přezdívka@%s</strong>“.';
$a->strings['Choose a nickname: '] = 'Vyberte přezdívku:';
$a->strings['Import'] = 'Import';
$a->strings['Import your profile to this friendica instance'] = 'Importovat váš profil do této instance Friendica';
$a->strings['Note: This node explicitly contains adult content'] = 'Poznámka: Tento server explicitně obsahuje obsah pro dospělé';
$a->strings['Parent Password:'] = 'Rodičovské heslo:';
$a->strings['Please enter the password of the parent account to legitimize your request.'] = 'Prosím vložte heslo rodičovského účtu k legitimizaci vašeho požadavku.';
$a->strings['Registration successful. Please check your email for further instructions.'] = 'Registrace byla úspěšná. Zkontrolujte prosím svůj e-mail pro další instrukce.';
$a->strings['Failed to send email message. Here your accout details:<br> login: %s<br> password: %s<br><br>You can change your password after login.'] = 'Nepovedlo se odeslat e-mailovou zprávu. Zde jsou detaily vašeho účtu:<br> přihlašovací jméno: %s<br> heslo: %s<br><br>Své heslo si můžete změnit po přihlášení.';
$a->strings['Registration successful.'] = 'Registrace byla úspěšná.';
$a->strings['Your registration can not be processed.'] = 'Vaši registraci nelze zpracovat.';
$a->strings['Your registration is pending approval by the site owner.'] = 'Vaše registrace čeká na schválení vlastníkem serveru.';
$a->strings['Only logged in users are permitted to perform a search.'] = 'Pouze přihlášení uživatelé mohou prohledávat tento server.';
$a->strings['Only one search per minute is permitted for not logged in users.'] = 'Nepřihlášení uživatelé mohou vyhledávat pouze jednou za minutu.';
$a->strings['Items tagged with: %s'] = 'Položky označené štítkem: %s';
$a->strings['Create a New Account'] = 'Vytvořit nový účet';
$a->strings['Or login using OpenID: '] = 'Nebo se přihlaste pomocí OpenID: ';
$a->strings['Password: '] = 'Heslo: ';
$a->strings['Remember me'] = 'Pamatovat si mě';
$a->strings['Forgot your password?'] = 'Zapomněl/a jste heslo?';
$a->strings['Website Terms of Service'] = 'Podmínky používání stránky';
$a->strings['terms of service'] = 'podmínky používání';
$a->strings['Website Privacy Policy'] = 'Zásady soukromí serveru';
$a->strings['privacy policy'] = 'zásady soukromí';
$a->strings['Logged out.'] = 'Odhlášen.';
$a->strings['Passwords do not match.'] = 'Hasla se neshodují.';
$a->strings['Password unchanged.'] = 'Heslo nezměněno.';
$a->strings['Current Password:'] = 'Stávající heslo:';
$a->strings['Your current password to confirm the changes'] = 'Vaše stávající heslo k potvrzení změn';
$a->strings['Remaining recovery codes: %d'] = 'Zbývající záložní kódy: %d';
$a->strings['Invalid code, please retry.'] = 'Neplatný kód, prosím zkuste to znovu.';
$a->strings['Two-factor recovery'] = 'Dvoufázové záložní kódy';
$a->strings['<p>You can enter one of your one-time recovery codes in case you lost access to your mobile device.</p>'] = '<p>Pokud jste ztratil/a přístup k vašemu mobilnímu zařízení , můžete zadat jeden z vašich jednorázových záložních kódů.</p>';
$a->strings['Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>'] = 'Nemáte svůj mobil? <a href="%s">Zadejte dvoufázový záložní kód</a>';
$a->strings['Please enter a recovery code'] = 'Prosím zadejte záložní kód';
$a->strings['Submit recovery code and complete login'] = 'Odeslat záložní kód a dokončit přihlášení';
$a->strings['<p>Open the two-factor authentication app on your device to get an authentication code and verify your identity.</p>'] = '<p>Otevřete na vašem zařízení aplikaci pro dvoufázové ověřování, získejte autentizační kód a ověřte svou identitu.</p>';
$a->strings['Please enter a code from your authentication app'] = 'Prosím zadejte kód z vaší autentizační aplikace';
$a->strings['Verify code and complete login'] = 'Ověřit kód a dokončit přihlášení';
$a->strings['Invalid email.'] = 'Neplatný e-mail.';
$a->strings['Cannot change to that email.'] = 'Nelze změnit na tento e-mail.';
$a->strings['Relocate message has been send to your contacts'] = 'Správa o změně umístění byla odeslána vašim kontaktům';
$a->strings['Unable to find your profile. Please contact your admin.'] = 'Nelze najít Váš účet. Prosím kontaktujte vašeho administrátora.';
$a->strings['Personal Page Subtypes'] = 'Podtypy osobních stránek';
$a->strings['Account for a personal profile.'] = 'Účet pro osobní profil.';
$a->strings['Account for an organisation that automatically approves contact requests as "Followers".'] = 'Účet pro organizaci, který automaticky potvrzuje požadavky o přidání kontaktu jako „Sledující“.';
$a->strings['Account for a news reflector that automatically approves contact requests as "Followers".'] = 'Účet pro zpravodaje, který automaticky potvrzuje požadavky o přidání kontaktu jako „Sledující“.';
$a->strings['Account for community discussions.'] = 'Účet pro komunitní diskuze.';
$a->strings['Account for a regular personal profile that requires manual approval of "Friends" and "Followers".'] = 'Účet pro běžný osobní profil, který vyžaduje manuální potvrzení „Přátel“ a „Sledujících“.';
$a->strings['Account for a public profile that automatically approves contact requests as "Followers".'] = 'Účet pro veřejný profil, který automaticky potvrzuje požadavky o přidání kontaktu jako „Sledující“.';
$a->strings['Automatically approves all contact requests.'] = 'Automaticky potvrzuje všechny žádosti o přidání kontaktu.';
$a->strings['Account for a popular profile that automatically approves contact requests as "Friends".'] = 'Účet pro populární profil, který automaticky potvrzuje požadavky o přidání kontaktu jako „Přátele“.';
$a->strings['Requires manual approval of contact requests.'] = 'Vyžaduje manuální potvrzení požadavků o přidání kontaktu.';
$a->strings['OpenID:'] = 'OpenID:';
$a->strings['(Optional) Allow this OpenID to login to this account.'] = '(Volitelné) Povolit tomuto OpenID přihlášení k tomuto účtu.';
$a->strings['Your profile will be published in this node\'s <a href="%s">local directory</a>. Your profile details may be publicly visible depending on the system settings.'] = 'Váš profil bude publikován v <a href="%s">místním adresáři</a> tohoto serveru. Vaše detaily o profilu mohou být veřejně viditelné v závislosti na systémových nastaveních.';
$a->strings['Account Settings'] = 'Nastavení účtu';
$a->strings['Your Identity Address is <strong>\'%s\'</strong> or \'%s\'.'] = 'Vaše adresa identity je <strong>„%s“</strong> nebo „%s“.';
$a->strings['Password Settings'] = 'Nastavení hesla';
$a->strings['Leave password fields blank unless changing'] = 'Pokud nechcete změnit heslo, položku hesla nevyplňujte';
$a->strings['Password:'] = 'Heslo: ';
$a->strings['Basic Settings'] = 'Základní nastavení';
$a->strings['Email Address:'] = 'E-mailová adresa:';
$a->strings['Your Timezone:'] = 'Vaše časové pásmo:';
$a->strings['Your Language:'] = 'Váš jazyk:';
$a->strings['Set the language we use to show you friendica interface and to send you emails'] = 'Nastavte jazyk, který máme používat pro rozhraní Friendica a pro posílání e-mailů';
$a->strings['Default Post Location:'] = 'Výchozí poloha příspěvků:';
$a->strings['Use Browser Location:'] = 'Používat polohu dle prohlížeče:';
$a->strings['Security and Privacy Settings'] = 'Nastavení zabezpečení a soukromí';
$a->strings['Maximum Friend Requests/Day:'] = 'Maximální počet požadavků o přátelství za den:';
$a->strings['(to prevent spam abuse)'] = '(ay se zabránilo spamu)';
$a->strings['Allow friends to post to your profile page?'] = 'Povolit přátelům umisťování příspěvků na vaši profilovou stránku?';
$a->strings['Your contacts may write posts on your profile wall. These posts will be distributed to your contacts'] = 'Vaše kontakty mohou psát příspěvky na vaši profilovou zeď. Tyto příspěvky budou přeposílány vašim kontaktům.';
$a->strings['Allow friends to tag your posts?'] = 'Povolit přátelům označovat vaše příspěvky?';
$a->strings['Your contacts can add additional tags to your posts.'] = 'Vaše kontakty mohou přidávat k vašim příspěvkům dodatečné štítky.';
$a->strings['Default Post Permissions'] = 'Výchozí oprávnění pro příspěvek';
$a->strings['Automatically expire posts after this many days:'] = 'Automaticky expirovat příspěvky po zadaném počtu dní:';
$a->strings['If empty, posts will not expire. Expired posts will be deleted'] = 'Pokud je prázdné, příspěvky nebudou nikdy expirovat. Expirované příspěvky budou vymazány';
$a->strings['Notification Settings'] = 'Nastavení oznámení';
$a->strings['Send a notification email when:'] = 'Poslat oznámení e-mailem, když:';
$a->strings['You receive an introduction'] = 'obdržíte představení';
$a->strings['Your introductions are confirmed'] = 'jsou vaše představení potvrzena';
$a->strings['Someone writes on your profile wall'] = 'vám někdo napíše na vaši profilovou stránku';
$a->strings['Someone writes a followup comment'] = 'Vám někdo napíše následný komentář';
$a->strings['You receive a private message'] = 'obdržíte soukromou zprávu';
$a->strings['You receive a friend suggestion'] = 'obdržíte návrh přátelství';
$a->strings['You are tagged in a post'] = 'jste označen v příspěvku';
$a->strings['Activate desktop notifications'] = 'Aktivovat desktopová oznámení';
$a->strings['Show desktop popup on new notifications'] = 'Zobrazit desktopové zprávy při nových oznámeních.';
$a->strings['Text-only notification emails'] = 'Pouze textové oznamovací e-maily';
$a->strings['Send text only notification emails, without the html part'] = 'Posílat pouze textové oznamovací e-maily, bez HTML části.';
$a->strings['Show detailled notifications'] = 'Zobrazit detailní oznámení';
$a->strings['Per default, notifications are condensed to a single notification per item. When enabled every notification is displayed.'] = 'Ve výchozím nastavení jsou oznámení zhuštěné na jediné oznámení pro každou položku. Pokud je toto povolené, budou zobrazována všechna oznámení.';
$a->strings['Advanced Account/Page Type Settings'] = 'Pokročilé nastavení účtu/stránky';
$a->strings['Change the behaviour of this account for special situations'] = 'Změnit chování tohoto účtu ve speciálních situacích';
$a->strings['Relocate'] = 'Přemístit';
$a->strings['If you have moved this profile from another server, and some of your contacts don\'t receive your updates, try pushing this button.'] = 'Pokud jste přemístil/a tento profil z jiného serveru a nějaký z vašich kontaktů nedostává vaše aktualizace, zkuste stisknout toto tlačítko.';
$a->strings['Resend relocate message to contacts'] = 'Znovu odeslat správu o přemístění Vašim kontaktům';
$a->strings['Addon Settings'] = 'Nastavení doplňků';
$a->strings['No Addon settings configured'] = 'Žádná nastavení doplňků nenakonfigurována';
$a->strings['Add'] = 'Přidat';
$a->strings['Failed to connect with email account using the settings provided.'] = 'Nepodařilo se připojit k e-mailovému účtu pomocí dodaného nastavení.';
$a->strings['Diaspora (Socialhome, Hubzilla)'] = 'Diaspora (Socialhome, Hubzilla)';
$a->strings['Email access is disabled on this site.'] = 'Přístup k e-mailu je na tomto serveru zakázán.';
$a->strings['None'] = 'Žádné';
$a->strings['General Social Media Settings'] = 'Obecná nastavení sociálních sítí';
$a->strings['Email/Mailbox Setup'] = 'Nastavení e-mailu';
$a->strings['If you wish to communicate with email contacts using this service (optional), please specify how to connect to your mailbox.'] = 'Pokud chcete komunikovat pomocí této služby s vašimi kontakty z e-mailu (volitelné), uveďte, jak se připojit k Vaší e-mailové schránce.';
$a->strings['Last successful email check:'] = 'Poslední úspěšná kontrola e-mailu:';
$a->strings['IMAP server name:'] = 'Jméno IMAP serveru:';
$a->strings['IMAP port:'] = 'IMAP port:';
$a->strings['Security:'] = 'Zabezpečení:';
$a->strings['Email login name:'] = 'Přihlašovací jméno k e-mailu:';
$a->strings['Email password:'] = 'Heslo k e-mailu:';
$a->strings['Reply-to address:'] = 'Odpovědět na adresu:';
$a->strings['Send public posts to all email contacts:'] = 'Poslat veřejné příspěvky na všechny e-mailové kontakty:';
$a->strings['Action after import:'] = 'Akce po importu:';
$a->strings['Move to folder'] = 'Přesunout do složky';
$a->strings['Move to folder:'] = 'Přesunout do složky:';
$a->strings['No parent user'] = 'Žádný rodičovský uživatel';
$a->strings['Parent User'] = 'Rodičovský uživatel';
$a->strings['Parent users have total control about this account, including the account settings. Please double check whom you give this access.'] = 'Rodičovští uživatelé mají naprostou kontrolu nad tímto účtem, včetně nastavení účtu. Prosím překontrolujte, komu tento přístup dáváte.';
$a->strings['Delegates'] = 'Delegáti';
$a->strings['Delegates are able to manage all aspects of this account/page except for basic account settings. Please do not delegate your personal account to anybody that you do not trust completely.'] = 'Delegáti jsou schopni řídit všechny aspekty tohoto účtu/stránky, kromě základních nastavení účtu. Prosím, nedelegujte svůj osobní účet nikomu, komu zcela nedůvěřujete.';
$a->strings['Existing Page Delegates'] = 'Stávající delegáti stránky ';
$a->strings['Potential Delegates'] = 'Potenciální delegáti';
$a->strings['No entries.'] = 'Žádné záznamy.';
$a->strings['The theme you chose isn\'t available.'] = 'Motiv, který jste si vybral/a, není dostupný.';
$a->strings['%s - (Unsupported)'] = '%s - (Nepodporováno)';
$a->strings['Display Settings'] = 'Nastavení zobrazení';
$a->strings['General Theme Settings'] = 'Obecná nastavení motivu';
$a->strings['Custom Theme Settings'] = 'Vlastní nastavení motivu';
$a->strings['Content Settings'] = 'Nastavení obsahu';
$a->strings['Theme settings'] = 'Nastavení motivu';
$a->strings['Display Theme:'] = 'Motiv zobrazení:';
$a->strings['Mobile Theme:'] = 'Mobilní motiv:';
$a->strings['Number of items to display per page:'] = 'Počet položek zobrazených na stránce:';
$a->strings['Maximum of 100 items'] = 'Maximum 100 položek';
$a->strings['Number of items to display per page when viewed from mobile device:'] = 'Počet položek ke zobrazení na stránce při zobrazení na mobilním zařízení:';
$a->strings['Infinite scroll'] = 'Nekonečné posouvání';
$a->strings['Beginning of week:'] = 'Začátek týdne:';
$a->strings['Additional Features'] = 'Dodatečné vlastnosti';
$a->strings['Connected Apps'] = 'Připojené aplikace';
$a->strings['Remove authorization'] = 'Odstranit oprávnění';
$a->strings['(click to open/close)'] = '(klikněte pro otevření/zavření)';
$a->strings['Profile Actions'] = 'Akce profilu';
$a->strings['Edit Profile Details'] = 'Upravit podrobnosti profilu ';
$a->strings['Change Profile Photo'] = 'Změnit profilovou fotku';
$a->strings['Profile picture'] = 'Profilový obrázek';
$a->strings['Location'] = 'Poloha';
$a->strings['Miscellaneous'] = 'Různé';
$a->strings['Upload Profile Photo'] = 'Nahrát profilovou fotku';
$a->strings['Street Address:'] = 'Ulice:';
$a->strings['Locality/City:'] = 'Poloha/město:';
$a->strings['Region/State:'] = 'Region / stát:';
$a->strings['Postal/Zip Code:'] = 'PSČ:';
$a->strings['Country:'] = 'Země:';
$a->strings['XMPP (Jabber) address:'] = 'Adresa XMPP (Jabber):';
$a->strings['Homepage URL:'] = 'Odkaz na domovskou stránku:';
$a->strings['Public Keywords:'] = 'Veřejná klíčová slova:';
$a->strings['(Used for suggesting potential friends, can be seen by others)'] = '(Používá se pro doporučování potenciálních přátel, může být viděno ostatními)';
$a->strings['Private Keywords:'] = 'Soukromá klíčová slova:';
$a->strings['(Used for searching profiles, never shown to others)'] = '(Používá se pro vyhledávání profilů, není nikdy zobrazeno ostatním)';
$a->strings['Image size reduction [%s] failed.'] = 'Nepodařilo se snížit velikost obrázku [%s].';
$a->strings['Shift-reload the page or clear browser cache if the new photo does not display immediately.'] = 'Znovu načtěte stránku (Shift+F5) nebo vymažte cache prohlížeče, pokud se nová fotka nezobrazí okamžitě.';
$a->strings['Unable to process image'] = 'Obrázek nelze zpracovat ';
$a->strings['Crop Image'] = 'Oříznout obrázek';
$a->strings['Please adjust the image cropping for optimum viewing.'] = 'Prosím, ořízněte tento obrázek pro optimální zobrazení.';
$a->strings['or'] = 'nebo';
$a->strings['skip this step'] = 'tento krok přeskočte';
$a->strings['select a photo from your photo albums'] = 'si vyberte fotku z vašich fotoalb';
$a->strings['[Friendica System Notify]'] = '[Systémové oznámení Friendica]';
$a->strings['User deleted their account'] = 'Uživatel si smazal účet';
$a->strings['On your Friendica node an user deleted their account. Please ensure that their data is removed from the backups.'] = 'Uživatel na vašem serveru Friendica smazal svůj účet. Prosím ujistěte se, ře jsou jeho data odstraněna ze záloh dat.';
$a->strings['The user id is %d'] = 'Uživatelské ID je %d';
$a->strings['Remove My Account'] = 'Odstranit můj účet';
$a->strings['This will completely remove your account. Once this has been done it is not recoverable.'] = 'Tímto bude kompletně odstraněn váš účet. Jakmile bude účet odstraněn, nebude už možné ho obnovit.';
$a->strings['Please enter your password for verification:'] = 'Prosím, zadejte své heslo pro ověření:';
$a->strings['Please enter your password to access this page.'] = 'Pro přístup k této stránce prosím zadejte své heslo.';
$a->strings['Friendiqa on my Fairphone 2...'] = 'Friendiqa na mém Fairphone 2...';
$a->strings['Two-factor authentication successfully disabled.'] = 'Dvoufázové ověřování úspěšně zakázáno.';
$a->strings['<p>Use an application on a mobile device to get two-factor authentication codes when prompted on login.</p>'] = '<p>Pomocí aplikace na mobilním zařízení získejte při přihlášení kódy pro dvoufázové ověřování.</p>';
$a->strings['Authenticator app'] = 'Autentizační aplikace';
$a->strings['Configured'] = 'Nakonfigurováno';
$a->strings['Not Configured'] = 'Nenakonfigurováno';
$a->strings['<p>You haven\'t finished configuring your authenticator app.</p>'] = '<p>Nedokončil/a jste konfiguraci vaší autentizační aplikace.</p>';
$a->strings['<p>Your authenticator app is correctly configured.</p>'] = '<p>Vaše autentizační aplikace je správně nakonfigurována.</p>';
$a->strings['Recovery codes'] = 'Záložní kódy';
$a->strings['Remaining valid codes'] = 'Zbývající platné kódy';
$a->strings['<p>These one-use codes can replace an authenticator app code in case you have lost access to it.</p>'] = '<p>Tyto jednorázové kódy mohou nahradit kód autentizační aplikace, pokud k ní ztratíte přístup.</p>';
$a->strings['Current password:'] = 'Aktuální heslo:';
$a->strings['You need to provide your current password to change two-factor authentication settings.'] = 'Pro změnu nastavení dvoufázového ověřování musíte poskytnout vaše aktuální heslo.';
$a->strings['Enable two-factor authentication'] = 'Povolit dvoufázové ověřování';
$a->strings['Disable two-factor authentication'] = 'Zakázat dvoufázové ověřování';
$a->strings['Show recovery codes'] = 'Zobrazit záložní kódy';
$a->strings['Finish app configuration'] = 'Dokončit konfiguraci aplikace';
$a->strings['New recovery codes successfully generated.'] = 'Nové záložní kódy byly úspěšně vygenerovány.';
$a->strings['Two-factor recovery codes'] = 'Dvoufázové záložní kódy';
$a->strings['<p>Recovery codes can be used to access your account in the event you lose access to your device and cannot receive two-factor authentication codes.</p><p><strong>Put these in a safe spot!</strong> If you lose your device and don’t have the recovery codes you will lose access to your account.</p>'] = '<p>Záložní kódy mohou být použity pro přístup k vašemu účtu, pokud ztratíte přístup k vašemu zařízení a nemůžete obdržet dvoufázové autentizační kódy.</p><p><strong>Uložte je na bezpečné místo!</strong> Pokud zratíte vaše zařízení a nemáte Záložní kódy, ztratíte přístup ke svému účtu.</p>';
$a->strings['When you generate new recovery codes, you must copy the new codes. Your old codes won’t work anymore.'] = 'Když vygenerujete nové záložní kódy, musíte si zkopírovat nové kódy. Vaše staré kódy již nebudou fungovat.';
$a->strings['Generate new recovery codes'] = 'Vygenerovat nové záložní kódy';
$a->strings['Next: Verification'] = 'Další: Ověření';
$a->strings['Two-factor authentication successfully activated.'] = 'Dvoufázové ověření úspěšně aktivováno.';
$a->strings['<p>Or you can submit the authentication settings manually:</p>
<dl>
	<dt>Issuer</dt>
	<dd>%s</dd>
	<dt>Account Name</dt>
	<dd>%s</dd>
	<dt>Secret Key</dt>
	<dd>%s</dd>
	<dt>Type</dt>
	<dd>Time-based</dd>
	<dt>Number of digits</dt>
	<dd>6</dd>
	<dt>Hashing algorithm</dt>
	<dd>SHA-1</dd>
</dl>'] = '<p>Nebo můžete zadat autentizační nastavení manuálně:</p>
<dl>
	<dt>Vydavatel</dt>
	<dd>%s</dd>
	<dt>Jméno účtu</dt>
	<dd>%s</dd>
	<dt>Tajný klíč</dt>
	<dd>%s</dd>
	<dt>Typ</dt>
	<dd>Založený na čase</dd>
	<dt>Počet číslic</dt>
	<dd>6</dd>
	<dt>Hashovací algoritmus</dt>
	<dd>SHA-1</dd>
</dl>';
$a->strings['Two-factor code verification'] = 'Ověření dvoufázového kódu';
$a->strings['<p>Please scan this QR Code with your authenticator app and submit the provided code.</p>'] = '<p>Prosím naskenujte tento QR kód vaší autentizační aplikací a zadejte poskytnutý kód.</p>';
$a->strings['Verify code and enable two-factor authentication'] = 'Ověřit kód a povolit dvoufázové ověřování';
$a->strings['Export account'] = 'Exportovat účet';
$a->strings['Export your account info and contacts. Use this to make a backup of your account and/or to move it to another server.'] = 'Exportujte svůj účet a své kontakty. Použijte tuto funkci pro vytvoření  zálohy svého účtu a/nebo k přesunu na jiný server.';
$a->strings['Export all'] = 'Exportovat vše';
$a->strings['Go back'] = 'Přejít zpět';
$a->strings['At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name (pen name), an username (nickname) and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.'] = 'Ve chvíli registrace, a pro poskytování komunikace mezi uživatelským účtem a jeho kontakty, musí uživatel poskytnout zobrazované jméno (pseudonym), uživatelské jméno (přezdívku) a funkční e-mailovou adresu. Jména budou dostupná na profilové stránce účtu pro kteréhokoliv návštěvníka, i kdyby ostatní detaily nebyly zobrazeny. E-mailová adresa bude použita pouze pro zasílání oznámení o interakcích, nebude ale viditelně zobrazována. Zápis účtu do adresáře účtů serveru nebo globálního adresáře účtů je nepovinný a může být ovládán v nastavení uživatele, není potřebný pro komunikaci.';
$a->strings['This data is required for communication and is passed on to the nodes of the communication partners and is stored there. Users can enter additional private data that may be transmitted to the communication partners accounts.'] = 'Tato data jsou vyžadována ke komunikaci a jsou předávána serverům komunikačních partnerů a jsou tam ukládána. Uživatelé mohou zadávat dodatečná soukromá data, která mohou být odeslána na účty komunikačních partnerů.';
$a->strings['Privacy Statement'] = 'Prohlášení o soukromí';
$a->strings['The requested item doesn\'t exist or has been deleted.'] = 'Požadovaná položka neexistuje nebo byla smazána.';
$a->strings['Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions'] = 'Přepínání mezi různými identitami nebo komunitními/skupinovými stránkami, které sdílí vaše detaily účtu, nebo kterým jste přidělil/a oprávnění nastavovat přístupová práva.';
$a->strings['Select an identity to manage: '] = 'Vyberte identitu ke spravování: ';
$a->strings['User imports on closed servers can only be done by an administrator.'] = 'Importy uživatelů na uzavřených serverech může provést pouze administrátor.';
$a->strings['Move account'] = 'Přesunout účet';
$a->strings['You can import an account from another Friendica server.'] = 'Můžete importovat účet z jiného serveru Friendica.';
$a->strings['You need to export your account from the old server and upload it here. We will recreate your old account here with all your contacts. We will try also to inform your friends that you moved here.'] = 'Musíte exportovat svůj účet na starém serveru a nahrát ho zde. My následně vytvoříme Váš původní účet zde včetně všech kontaktů. Zároveň se pokusíme informovat všechny Vaše přátele, že jste se sem přestěhoval/a.';
$a->strings['This feature is experimental. We can\'t import contacts from the OStatus network (GNU Social/Statusnet) or from Diaspora'] = 'Tato vlastnost je experimentální. Nemůžeme importovat kontakty za sítě OStatus (GNU social/StatusNet) nebo z Diaspory';
$a->strings['Account file'] = 'Soubor s účtem';
$a->strings['To export your account, go to "Settings->Export your personal data" and select "Export account"'] = 'K exportu Vašeho účtu jděte na „Nastavení->Exportovat osobní údaje“ a zvolte „Exportovat účet“';
$a->strings['Error decoding account file'] = 'Chyba dekódování uživatelského účtu';
$a->strings['Error! No version data in file! This is not a Friendica account file?'] = 'Chyba! V souboru nejsou data o verzi! Je to opravdu soubor s účtem Friendica?';
$a->strings['User \'%s\' already exists on this server!'] = 'Uživatel „%s“ již na tomto serveru existuje!';
$a->strings['User creation error'] = 'Chyba při vytváření uživatele';
$a->strings['%d contact not imported'] = [
	0 => '%d kontakt nenaimportován',
	1 => '%d kontakty nenaimportovány',
	2 => '%d kontaktu nenaimportováno',
	3 => '%d kontaktů nenaimportováno',
];
$a->strings['User profile creation error'] = 'Chyba vytváření uživatelského profilu';
$a->strings['Done. You can now login with your username and password'] = 'Hotovo. Nyní  se můžete přihlásit se svým uživatelským jménem a heslem';
$a->strings['Welcome to Friendica'] = 'Vítejte na Friendica';
$a->strings['New Member Checklist'] = 'Seznam doporučení pro nového člena';
$a->strings['We would like to offer some tips and links to help make your experience enjoyable. Click any item to visit the relevant page. A link to this page will be visible from your home page for two weeks after your initial registration and then will quietly disappear.'] = 'Rádi bychom vám nabídli několik tipů a odkazů, abychom vám zpříjemnili zážitek. Kliknutím na jakoukoliv položku zobrazíte relevantní stránku. Odkaz na tuto stránku bude viditelný z vaší domovské stránky po dobu dvou týdnů od vaší první registrace a poté tiše zmizí.';
$a->strings['Getting Started'] = 'Začínáme';
$a->strings['Friendica Walk-Through'] = 'Prohlídka Friendica ';
$a->strings['On your <em>Quick Start</em> page - find a brief introduction to your profile and network tabs, make some new connections, and find some groups to join.'] = 'Na vaší stránce <em>Rychlý začátek</em> najděte stručný úvod k vašemu profilu a síťovým záložkám, spojte se s novými kontakty a najděte skupiny, ke kterým se můžete připojit.';
$a->strings['Go to Your Settings'] = 'Navštivte své nastavení';
$a->strings['On your <em>Settings</em> page -  change your initial password. Also make a note of your Identity Address. This looks just like an email address - and will be useful in making friends on the free social web.'] = 'Na vaší stránce <em>Nastavení</em> si změňte vaše první heslo. Věnujte také svou pozornost vaší adrese identity. Vypadá jako e-mailová adresa a bude vám užitečná pro navazování přátelství na svobodném sociálním webu.';
$a->strings['Review the other settings, particularly the privacy settings. An unpublished directory listing is like having an unlisted phone number. In general, you should probably publish your listing - unless all of your friends and potential friends know exactly how to find you.'] = 'Prohlédněte si další nastavení, a to zejména nastavení soukromí. Nezveřejnění svého účtu v adresáři je jako mít nezveřejněné telefonní číslo. Obecně platí, že je lepší mít svůj účet zveřejněný - ledaže by všichni vaši přátelé a potenciální přátelé přesně věděli, jak vás najít.';
$a->strings['Upload a profile photo if you have not done so already. Studies have shown that people with real photos of themselves are ten times more likely to make friends than people who do not.'] = 'Nahrajte si svou profilovou fotku, pokud jste tak již neučinil/a. Studie ukázaly, že lidé se skutečnými fotkami mají desetkrát častěji přátele než lidé, kteří nemají.';
$a->strings['Edit Your Profile'] = 'Upravte si svůj profil';
$a->strings['Edit your <strong>default</strong> profile to your liking. Review the settings for hiding your list of friends and hiding the profile from unknown visitors.'] = 'Upravte si <strong>výchozí</strong> profil podle vašich představ. Prověřte nastavení pro skrytí vašeho seznamu přátel a skrytí profilu před neznámými návštěvníky.';
$a->strings['Profile Keywords'] = 'Profilová klíčová slova';
$a->strings['Connecting'] = 'Připojuji se';
$a->strings['Importing Emails'] = 'Importuji e-maily';
$a->strings['Enter your email access information on your Connector Settings page if you wish to import and interact with friends or mailing lists from your email INBOX'] = 'Pokud chcete importovat své přátele nebo mailové skupiny z INBOX vašeho e-mailu a komunikovat s nimi, zadejte na vaší stránce Nastavení kontektoru své přístupové údaje do svého e-mailového účtu';
$a->strings['Go to Your Contacts Page'] = 'Navštivte vaši stránku Kontakty';
$a->strings['Your Contacts page is your gateway to managing friendships and connecting with friends on other networks. Typically you enter their address or site URL in the <em>Add New Contact</em> dialog.'] = 'Vaše stránka Kontakty je vaše brána k nastavování přátelství a propojení s přáteli z dalších sítí. Typicky zadáte jejich emailovou adresu nebo URL adresu jejich serveru prostřednictvím dialogu <em>Přidat nový kontakt</em>.';
$a->strings['Go to Your Site\'s Directory'] = 'Navštivte adresář vaší stránky';
$a->strings['The Directory page lets you find other people in this network or other federated sites. Look for a <em>Connect</em> or <em>Follow</em> link on their profile page. Provide your own Identity Address if requested.'] = 'Stránka Adresář vám pomůže najít další lidi na tomto serveru nebo v jiných propojených serverech. Najděte na jejich stránce odkaz <em>Spojit se</em> nebo <em>Sledovat</em>. Uveďte svou vlastní adresu identity, je-li požadována.';
$a->strings['Finding New People'] = 'Nalezení nových lidí';
$a->strings['On the side panel of the Contacts page are several tools to find new friends. We can match people by interest, look up people by name or interest, and provide suggestions based on network relationships. On a brand new site, friend suggestions will usually begin to be populated within 24 hours.'] = 'Na bočním panelu stránky s kontakty je několik nástrojů k nalezení nových přátel. Porovnáme lidi dle zájmů, najdeme lidi podle jména nebo zájmu a poskytneme Vám návrhy založené na přátelství v síti přátel. Na zcela novém serveru se návrhy přátelství nabínou obvykle během 24 hodin.';
$a->strings['Why Aren\'t My Posts Public?'] = 'Proč nejsou mé příspěvky veřejné?';
$a->strings['Friendica respects your privacy. By default, your posts will only show up to people you\'ve added as friends. For more information, see the help section from the link above.'] = 'Friendica respektuje vaše soukromí. Ve výchozím stavu jsou vaše příspěvky viditelné pouze lidem, které označíte jako vaše přátelé. Více informací naleznete v nápovědě na výše uvedeném odkazu';
$a->strings['Getting Help'] = 'Získání nápovědy';
$a->strings['Go to the Help Section'] = 'Navštivte sekci nápovědy';
$a->strings['Our <strong>help</strong> pages may be consulted for detail on other program features and resources.'] = 'Na stránkách <strong>Nápověda</strong> naleznete nejen další podrobnosti o všech funkcích Friendika ale také další zdroje informací.';
$a->strings['%s liked %s\'s post'] = 'Uživateli %s se líbí příspěvek uživatele %s';
$a->strings['%s disliked %s\'s post'] = 'Uživateli %s se nelíbí příspěvek uživatele %s';
$a->strings['%s is attending %s\'s event'] = '%s se zúčastní události %s';
$a->strings['%s is not attending %s\'s event'] = '%s se nezúčastní události %s';
$a->strings['%s is now friends with %s'] = '%s se nyní přátelí s uživatelem %s';
$a->strings['%s commented on %s\'s post'] = '%s okomentoval/a příspěvek uživatele %s';
$a->strings['%s created a new post'] = '%s vytvořil nový příspěvek';
$a->strings['Friend Suggestion'] = 'Návrh přátelství';
$a->strings['Friend/Connect Request'] = 'Požadavek o přátelství/spojení';
$a->strings['%1$s sent you a new private message at %2$s.'] = '%1$s vám poslal/a novou soukromou zprávu na %2$s.';
$a->strings['a private message'] = 'soukromou zprávu';
$a->strings['%1$s sent you %2$s.'] = '%1$s vám poslal/a %2$s.';
$a->strings['Please visit %s to view and/or reply to your private messages.'] = 'Pro zobrazení vašich soukromých zpráv a možnost na ně odpovědět prosím navštivte %s.';
$a->strings['%s commented on an item/conversation you have been following.'] = '%s okomentoval/a vámi sledovanou položku/konverzaci.';
$a->strings['Please visit %s to view and/or reply to the conversation.'] = 'Prosím navštivte %s pro zobrazení konverzace a možnosti odpovědět.';
$a->strings['%1$s posted to your profile wall at %2$s'] = '%1$s přidal/a příspěvek na vaši profilovou zeď na %2$s';
$a->strings['%1$s posted to [url=%2$s]your wall[/url]'] = '%1$s přidal/a příspěvek na [url=%2$s]vaši zeď[/url]';
$a->strings['You\'ve received an introduction from \'%1$s\' at %2$s'] = 'Obdržel/a jste představení od uživatele „%1$s“ na %2$s';
$a->strings['You\'ve received [url=%1$s]an introduction[/url] from %2$s.'] = 'Obdržel/a jste [url=%1$s]představení[/url] od uživatele %2$s.';
$a->strings['You may visit their profile at %s'] = 'Můžete navštívit jeho/její profil na %s';
$a->strings['Please visit %s to approve or reject the introduction.'] = 'Prosím navštivte %s pro schválení či zamítnutí představení.';
$a->strings['%1$s is sharing with you at %2$s'] = 'Uživatel %1$s s vámi sdílí na %2$s';
$a->strings['You have a new follower at %2$s : %1$s'] = 'Máte nového sledujícího na %2$s: %1$s';
$a->strings['You\'ve received a friend suggestion from \'%1$s\' at %2$s'] = 'Obdržel/a jste návrh přátelství od uživatele „%1$s“ na %2$s';
$a->strings['You\'ve received [url=%1$s]a friend suggestion[/url] for %2$s from %3$s.'] = 'Obdržel/a jste [url=%1$s]návrh přátelství[/url] s uživatelem %2$s od uživatele %3$s.';
$a->strings['Name:'] = 'Jméno:';
$a->strings['Photo:'] = 'Fotka:';
$a->strings['Please visit %s to approve or reject the suggestion.'] = 'Prosím navštivte %s pro schválení či zamítnutí návrhu.';
$a->strings['\'%1$s\' has accepted your connection request at %2$s'] = '„%1$s“ přijal/a váš požadavek o spojení na %2$s';
$a->strings['%2$s has accepted your [url=%1$s]connection request[/url].'] = '%2$s přijal/a váš [url=%1$s]požadavek o spojení[/url].';
$a->strings['You are now mutual friends and may exchange status updates, photos, and email without restriction.'] = 'Jste nyní vzájemní přátelé a můžete si vyměňovat stavové zprávy, fotky a e-maily bez omezení.';
$a->strings['Please visit %s if you wish to make any changes to this relationship.'] = 'Pokud chcete provést změny s tímto vztahem, prosím navštivte %s.';
$a->strings['\'%1$s\' has chosen to accept you a fan, which restricts some forms of communication - such as private messaging and some profile interactions. If this is a celebrity or community page, these settings were applied automatically.'] = '„%1$s“ se rozhodl/a vás přijmout jako fanouška, což omezuje některé formy komunikace - například soukoromé zprávy a některé interakce s profily. Pokud je toto stránka celebrity či komunity, byla tato nastavení aplikována automaticky.';
$a->strings['\'%1$s\' may choose to extend this into a two-way or more permissive relationship in the future.'] = '„%1$s“ se může rozhodnout tento vztah v budoucnosti rozšířit do oboustranného či jiného liberálnějšího vztahu.';
$a->strings['Please visit %s  if you wish to make any changes to this relationship.'] = 'Prosím navštivte %s  pokud chcete změnit tento vztah.';
$a->strings['registration request'] = 'požadavek o registraci';
$a->strings['You\'ve received a registration request from \'%1$s\' at %2$s'] = 'Obdržel/a jste požadavek o registraci od uživatele „%1$s“ na %2$s';
$a->strings['You\'ve received a [url=%1$s]registration request[/url] from %2$s.'] = 'Obdržel/a jste [url=%1$s]požadavek o registraci[/url] od uživatele %2$s.';
$a->strings['Please visit %s to approve or reject the request.'] = 'Prosím navštivte %s k odsouhlasení nebo k zamítnutí požadavku.';
$a->strings['This message was sent to you by %s, a member of the Friendica social network.'] = 'Tuto zprávu vám poslal/a %s, člen sociální sítě Friendica.';
$a->strings['You may visit them online at %s'] = 'Můžete jej/ji navštívit online na adrese %s';
$a->strings['Please contact the sender by replying to this post if you do not wish to receive these messages.'] = 'Pokud nechcete dostávat tyto zprávy, kontaktujte prosím odesílatele odpovědí na tuto zprávu.';
$a->strings['%s posted an update.'] = '%s poslal/a aktualizaci.';
$a->strings['Private Message'] = 'Soukromá zpráva';
$a->strings['This entry was edited'] = 'Tato položka byla upravena';
$a->strings['Edit'] = 'Upravit';
$a->strings['Delete globally'] = 'Smazat globálně';
$a->strings['Remove locally'] = 'Odstranit lokálně';
$a->strings['I will attend'] = 'zúčastním se';
$a->strings['I will not attend'] = 'nezúčastním se';
$a->strings['I might attend'] = 'mohl bych se zúčastnit';
$a->strings['to'] = 'na';
$a->strings['via'] = 'přes';
$a->strings['Wall-to-Wall'] = 'Ze zdi na zeď';
$a->strings['via Wall-To-Wall:'] = 'ze zdi na zeď';
$a->strings['Reply to %s'] = 'Odpovědět uživateli %s';
$a->strings['Notifier task is pending'] = 'Úloha pro notifiera čeká';
$a->strings['Delivery to remote servers is pending'] = 'Doručení vzdáleným serverům čeká';
$a->strings['Delivery to remote servers is underway'] = 'Doručení vzdáleným serverům je v plném proudu';
$a->strings['Delivery to remote servers is mostly done'] = 'Doručení vzdáleným serverům je téměř hotovo';
$a->strings['Delivery to remote servers is done'] = 'Doručení vzdáleným serverům je hotovo';
$a->strings['%d comment'] = [
	0 => '%d komentář',
	1 => '%d komentáře',
	2 => '%d komentáře',
	3 => '%d komentářů',
];
$a->strings['Show more'] = 'Zobrazit více';
$a->strings['Show fewer'] = 'Zobrazit méně';
$a->strings['Login failed.'] = 'Přihlášení se nezdařilo.';
$a->strings['Welcome %s'] = 'Vítejte, %s';
$a->strings['Please upload a profile photo.'] = 'Prosím nahrajte profilovou fotku.';
$a->strings['OpenWebAuth: %1$s welcomes %2$s'] = 'OpenWebAuth: %1$s vítá uživatele %2$s';
