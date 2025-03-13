<?php

if(! function_exists("string_plural_select_hu")) {
function string_plural_select_hu($n){
	$n = intval($n);
	return intval($n != 1);
}}
$a->strings['Unable to locate original post.'] = 'Nem lehet megtalálni az eredeti bejegyzést.';
$a->strings['Post updated.'] = 'Bejegyzés frissítve.';
$a->strings['Item wasn\'t stored.'] = 'Az elem nem lett eltárolva.';
$a->strings['Item couldn\'t be fetched.'] = 'Az elemet nem sikerült lekérni.';
$a->strings['Empty post discarded.'] = 'Az üres bejegyzés elvetve.';
$a->strings['Item not found.'] = 'Az elem nem található.';
$a->strings['Permission denied.'] = 'Hozzáférés megtagadva.';
$a->strings['No valid account found.'] = 'Nem található érvényes fiók.';
$a->strings['Password reset request issued. Check your email.'] = 'A jelszó-visszaállítási kérés el lett küldve. Nézze meg a leveleit.';
$a->strings['
		Dear %1$s,
			A request was recently received at "%2$s" to reset your account
		password. In order to confirm this request, please select the verification link
		below or paste it into your web browser address bar.

		If you did NOT request this change, please DO NOT follow the link
		provided and ignore and/or delete this email, the request will expire shortly.

		Your password will not be changed unless we can verify that you
		issued this request.'] = '
		Kedves %1$s!
			Nemrég kérés érkezett a „%2$s” oldalról a fiókja jelszavának
		visszaállítására. A kérés megerősítése érdekében kattintson a lenti
		ellenőrző hivatkozásra, vagy illessze be a webböngészője címsávjába.

		Ha NEM Ön kérte ezt a változtatást, akkor NE kövesse a megadott
		hivatkozást, illetve hagyja figyelmen kívül és/vagy törölje ezt az
		e-mailt. A kérés hamarosan le fog járni.

		A jelszava nem lesz megváltoztatva, hacsak nem tudjuk ellenőrizni, hogy
		Ön indította ezt a kérést.';
$a->strings['
		Follow this link soon to verify your identity:

		%1$s

		You will then receive a follow-up message containing the new password.
		You may change that password from your account settings page after logging in.

		The login details are as follows:

		Site Location:	%2$s
		Login Name:	%3$s'] = '
		Kövesse ezt a hivatkozást a személyazonossága ellenőrzéséhez:

		%1$s

		Ezután kapni fog egy követő üzenetet, amely az új jelszavát tartalmazza.
		A jelszót a fiókja beállításainak oldalán változtathatja meg, miután bejelentkezett.

		A bejelentkezés részletei a következők:

		Oldal címe:	%2$s
		Bejelentkezési név:	%3$s';
$a->strings['Password reset requested at %s'] = 'Jelszó-visszaállítás kérve itt: %s';
$a->strings['Request could not be verified. (You may have previously submitted it.) Password reset failed.'] = 'A kérést nem sikerült ellenőrizni (lehet, hogy korábban már elküldte). A jelszó-visszaállítás sikertelen.';
$a->strings['Request has expired, please make a new one.'] = 'A kérés lejárt, készítsen egy újat.';
$a->strings['Forgot your Password?'] = 'Elfelejtette a jelszavát?';
$a->strings['Enter your email address and submit to have your password reset. Then check your email for further instructions.'] = 'Adja meg az e-mail-címét, és küldje el a jelszó-visszaállítás kéréséhez. Azután nézze meg a postafiókját a további utasításokért.';
$a->strings['Nickname or Email: '] = 'Becenév vagy e-mail-cím: ';
$a->strings['Reset'] = 'Visszaállítás';
$a->strings['Password Reset'] = 'Jelszó visszaállítása';
$a->strings['Your password has been reset as requested.'] = 'A jelszava vissza lett állítva a kérés alapján.';
$a->strings['Your new password is'] = 'Az új jelszava';
$a->strings['Save or copy your new password - and then'] = 'Mentse el vagy másolja le az új jelszavát – majd';
$a->strings['click here to login'] = 'kattintson ide a bejelentkezéshez';
$a->strings['Your password may be changed from the <em>Settings</em> page after successful login.'] = 'A jelszava megváltoztatható a <em>Beállítások</em> oldalon, miután sikeresen bejelentkezett.';
$a->strings['Your password has been reset.'] = 'A jelszava vissza lett állítva.';
$a->strings['
			Dear %1$s,
				Your password has been changed as requested. Please retain this
			information for your records (or change your password immediately to
			something that you will remember).
		'] = '
			Kedves %1$s!
				A jelszava vissza lett állítva a kérés alapján. Őrizze meg ezt az
			információt a feljegyzéséhez (vagy változtassa meg a jelszót azonnal
			valami olyanra, amelyre emlékezni fog).
		';
$a->strings['
			Your login details are as follows:

			Site Location:	%1$s
			Login Name:	%2$s
			Password:	%3$s

			You may change that password from your account settings page after logging in.
		'] = '
			A bejelentkezés részletei a következők:

			Oldal címe:	%1$s
			Bejelentkezési név:	%2$s
			Jelszó:	%3$s

			Megváltoztathatja a jelszót a fiókbeállítások oldalon, miután bejelentkezett.
		';
$a->strings['Your password has been changed at %s'] = 'A jelszava meg lett változtatva itt: %s';
$a->strings['New Message'] = 'Új üzenet';
$a->strings['No recipient selected.'] = 'Nincs címzett kiválasztva.';
$a->strings['Unable to locate contact information.'] = 'Nem lehet megtalálni a partner információit.';
$a->strings['Message could not be sent.'] = 'Az üzenetet nem sikerült elküldeni.';
$a->strings['Message collection failure.'] = 'Üzenet-összegyűjtési hiba.';
$a->strings['Discard'] = 'Elvetés';
$a->strings['Messages'] = 'Üzenetek';
$a->strings['Conversation not found.'] = 'A beszélgetés nem található.';
$a->strings['Message was not deleted.'] = 'Az üzenet nem lett törölve.';
$a->strings['Conversation was not removed.'] = 'A beszélgetés nem lett eltávolítva.';
$a->strings['Please enter a link URL:'] = 'Írjon be egy hivatkozás URL-t:';
$a->strings['Send Private Message'] = 'Személyes üzenet küldése';
$a->strings['To:'] = 'Címzett:';
$a->strings['Subject:'] = 'Tárgy:';
$a->strings['Your message:'] = 'Az üzenete:';
$a->strings['Upload photo'] = 'Fénykép feltöltése';
$a->strings['Insert web link'] = 'Webhivatkozás beszúrása';
$a->strings['Please wait'] = 'Kis türelmet';
$a->strings['Submit'] = 'Elküldés';
$a->strings['No messages.'] = 'Nincsenek üzenetek.';
$a->strings['Message not available.'] = 'Az üzenet nem érhető el.';
$a->strings['Delete message'] = 'Üzenet törlése';
$a->strings['D, d M Y - g:i A'] = 'Y. M. j., D. – H:i';
$a->strings['Delete conversation'] = 'Beszélgetés törlése';
$a->strings['No secure communications available. You <strong>may</strong> be able to respond from the sender\'s profile page.'] = 'Nem érhető el biztonságos kommunikáció. <strong>Esetleg</strong> válaszolhat a küldő profiloldaláról.';
$a->strings['Send Reply'] = 'Válasz küldése';
$a->strings['Unknown sender - %s'] = 'Ismeretlen küldő – %s';
$a->strings['You and %s'] = 'Ön és %s';
$a->strings['%s and You'] = '%s és Ön';
$a->strings['%d message'] = [
	0 => '%d üzenet',
	1 => '%d üzenet',
];
$a->strings['Personal Notes'] = 'Személyes jegyzetek';
$a->strings['Personal notes are visible only by yourself.'] = 'A személyes jegyzetek csak az Ön számára láthatók.';
$a->strings['Save'] = 'Mentés';
$a->strings['User not found.'] = 'A felhasználó nem található.';
$a->strings['Photo Albums'] = 'Fényképalbumok';
$a->strings['Recent Photos'] = 'Legutóbbi fényképek';
$a->strings['Upload New Photos'] = 'Új fényképek feltöltése';
$a->strings['everybody'] = 'mindenki';
$a->strings['Contact information unavailable'] = 'A partner információi nem érhetők el';
$a->strings['Album not found.'] = 'Az album nem található.';
$a->strings['Album successfully deleted'] = 'Az album sikeresen törölve';
$a->strings['Album was empty.'] = 'Az album üres volt.';
$a->strings['Failed to delete the photo.'] = 'Nem sikerült törölni a fényképet.';
$a->strings['a photo'] = 'egy fényképen';
$a->strings['%1$s was tagged in %2$s by %3$s'] = '%1$s meg lett jelölve %2$s %3$s által';
$a->strings['Public access denied.'] = 'Nyilvános hozzáférés megtagadva.';
$a->strings['No photos selected'] = 'Nincsenek fényképek kijelölve';
$a->strings['The maximum accepted image size is %s'] = 'A legnagyobb elfogadott képméret %s';
$a->strings['Upload Photos'] = 'Fényképek feltöltése';
$a->strings['New album name: '] = 'Új album neve: ';
$a->strings['or select existing album:'] = 'vagy meglévő album kiválasztása:';
$a->strings['Do not show a status post for this upload'] = 'Ne jelenítsen meg állapotbejegyzést ehhez a feltöltéshez';
$a->strings['Permissions'] = 'Jogosultságok';
$a->strings['Do you really want to delete this photo album and all its photos?'] = 'Valóban törölni szeretné ezt a fényképalbumot és az összes fényképét?';
$a->strings['Delete Album'] = 'Album törlése';
$a->strings['Cancel'] = 'Mégse';
$a->strings['Edit Album'] = 'Album szerkesztése';
$a->strings['Drop Album'] = 'Album eldobása';
$a->strings['Show Newest First'] = 'Legújabb megjelenítése először';
$a->strings['Show Oldest First'] = 'Legrégebbi megjelenítése először';
$a->strings['View Photo'] = 'Fénykép megtekintése';
$a->strings['Permission denied. Access to this item may be restricted.'] = 'Hozzáférés megtagadva. Az elemhez való hozzáférés korlátozva lehet.';
$a->strings['Photo not available'] = 'A fénykép nem érhető el';
$a->strings['Do you really want to delete this photo?'] = 'Valóban törölni szeretné ezt a fényképet?';
$a->strings['Delete Photo'] = 'Fénykép törlése';
$a->strings['View photo'] = 'Fénykép megtekintése';
$a->strings['Edit photo'] = 'Fénykép szerkesztése';
$a->strings['Delete photo'] = 'Fénykép törlése';
$a->strings['Use as profile photo'] = 'Használat profilfényképként';
$a->strings['Private Photo'] = 'Személyes fénykép';
$a->strings['View Full Size'] = 'Teljes méret megtekintése';
$a->strings['Tags: '] = 'Címkék: ';
$a->strings['[Select tags to remove]'] = '[Eltávolítandó címkék kiválasztása]';
$a->strings['New album name'] = 'Új album neve';
$a->strings['Caption'] = 'Felirat';
$a->strings['Add a Tag'] = 'Címke hozzáadása';
$a->strings['Example: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping'] = 'Példa: @bob, @Barbara_Jensen, @jim@example.com, #Budapest, #kemping';
$a->strings['Do not rotate'] = 'Ne forgassa el';
$a->strings['Rotate CW (right)'] = 'Forgatás jobbra';
$a->strings['Rotate CCW (left)'] = 'Forgatás balra';
$a->strings['This is you'] = 'Ez Ön';
$a->strings['Comment'] = 'Hozzászólás';
$a->strings['Preview'] = 'Előnézet';
$a->strings['Loading...'] = 'Betöltés…';
$a->strings['Select'] = 'Kiválasztás';
$a->strings['Delete'] = 'Törlés';
$a->strings['Like'] = 'Tetszik';
$a->strings['I like this (toggle)'] = 'Ezt kedvelem (átváltás)';
$a->strings['Dislike'] = 'Nem tetszik';
$a->strings['I don\'t like this (toggle)'] = 'Ezt nem kedvelem (átváltás)';
$a->strings['Map'] = 'Térkép';
$a->strings['Delete this item?'] = 'Törli ezt az elemet?';
$a->strings['Block this author? They won\'t be able to follow you nor see your public posts, and you won\'t be able to see their posts and their notifications.'] = 'Tiltja ezt a szerzőt? Nem lesz képes követni Önt, és a nyilvános bejegyzéseit sem látja, valamint Ön sem lesz képes megtekinteni az ő bejegyzéseit és értesítéseit.';
$a->strings['Ignore this author? You won\'t be able to see their posts and their notifications.'] = 'Mellőzi ezt a szerzőt? Nem lesz képes megtekinteni az ő bejegyzéseit és értesítéseit.';
$a->strings['Collapse this author\'s posts?'] = 'Összecsukja ennek a szerzőnek a bejegyzéseit?';
$a->strings['Ignore this author\'s server?'] = 'Mellőzi ennek a szerzőnek a kiszolgálóját?';
$a->strings['You won\'t see any content from this server including reshares in your Network page, the community pages and individual conversations.'] = 'Nem fog látni semmilyen tartalmat erről a kiszolgálóról, beleértve a hálózat oldalon, a közösségi oldalakon és az egyéni beszélgetésekben lévő újra megosztásokat is.';
$a->strings['Like not successful'] = 'A kedvelés sikertelen';
$a->strings['Dislike not successful'] = 'A nem kedvelés sikertelen';
$a->strings['Sharing not successful'] = 'A megosztás sikertelen';
$a->strings['Attendance unsuccessful'] = 'A részvétel sikertelen';
$a->strings['Backend error'] = 'Háttérprogram hiba';
$a->strings['Network error'] = 'Hálózati hiba';
$a->strings['Drop files here to upload'] = 'Dobja ide a fájlokat a feltöltéséhez';
$a->strings['Your browser does not support drag and drop file uploads.'] = 'A böngészője nem támogatja a fogd és vidd fájlfeltöltéseket.';
$a->strings['Please use the fallback form below to upload your files like in the olden days.'] = 'Használja az alábbi tartalék űrlapot a fájlok feltöltéséhez, mint a régi időkben.';
$a->strings['File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.'] = 'A fájl túl nagy ({{filesize}} MiB). A legnagyobb fájlméret: {{maxFilesize}} MiB.';
$a->strings['You can\'t upload files of this type.'] = 'Nem tud ilyen típusú fájlokat feltölteni.';
$a->strings['Server responded with {{statusCode}} code.'] = 'A kiszolgáló {{statusCode}} állapotkóddal válaszolt.';
$a->strings['Cancel upload'] = 'Feltöltés megszakítása';
$a->strings['Upload canceled.'] = 'A feltöltés megszakítva.';
$a->strings['Are you sure you want to cancel this upload?'] = 'Biztosan meg szeretné szakítani ezt a feltöltést?';
$a->strings['Remove file'] = 'Fájl eltávolítása';
$a->strings['You can\'t upload any more files.'] = 'Nem tud több fájlt feltölteni.';
$a->strings['toggle mobile'] = 'váltás mobilra';
$a->strings['Close'] = 'Bezárás';
$a->strings['Method not allowed for this module. Allowed method(s): %s'] = 'A módszer nem engedélyezett ennél a modulnál. Az engedélyezett módszerek: %s';
$a->strings['Page not found.'] = 'Az oldal nem található.';
$a->strings['You must be logged in to use addons. '] = 'Bejelentkezve kell lennie a bővítmények használatához.';
$a->strings['No system theme config value set.'] = 'Nincs rendszertéma beállítási érték megadva.';
$a->strings['The form security token was not correct. This probably happened because the form has been opened for too long (>3 hours) before submitting it.'] = 'Az űrlap biztonsági tokenje nem volt helyes. Ez valószínűleg azért történt, mert az űrlapot túl hosszan tartották nyitva (>3 óra), mielőtt elküldték volna.';
$a->strings['All contacts'] = 'Összes partner';
$a->strings['Followers'] = 'Követők';
$a->strings['Following'] = 'Követés';
$a->strings['Mutual friends'] = 'Kölcsönösen ismerősök';
$a->strings['Common'] = 'Közös';
$a->strings['Addon not found'] = 'A bővítmény nem található';
$a->strings['Addon already enabled'] = 'A bővítmény már engedélyezve van';
$a->strings['Addon already disabled'] = 'A bővítmény már le van tiltva';
$a->strings['Could not find any unarchived contact entry for this URL (%s)'] = 'Nem sikerült találni egyetlen archiválatlan partnerbejegyzést sem erről az URL-ről (%s)';
$a->strings['The contact entries have been archived'] = 'A partnerbejegyzések archiválva lettek';
$a->strings['The avatar cache needs to be disabled in local.config.php to use this command.'] = 'A profilkép gyorsítótárának letiltva kell lennie a local.config.php fájlban a parancs használatához.';
$a->strings['Could not find any contact entry for this URL (%s)'] = 'Nem sikerült találni egyetlen partnerbejegyzést sem erről az URL-ről (%s)';
$a->strings['The contact has been blocked from the node'] = 'A partner tiltva lett a csomópontról';
$a->strings['%d %s, %d duplicates.'] = '%d %s, %d kettőzés.';
$a->strings['uri-id is empty for contact %s.'] = 'Az URI-azonosító üres %s partnernél.';
$a->strings['No valid first contact found for uri-id %d.'] = 'Nem található érvényes első partner a(z) %d. URI-azonosítóhoz.';
$a->strings['Wrong duplicate found for uri-id %d in %d (url: %s != %s).'] = 'Hibás kettőzés található a(z) %d. URI-azonosítónál ebben: %d (URL: %s != %s).';
$a->strings['Wrong duplicate found for uri-id %d in %d (nurl: %s != %s).'] = 'Hibás kettőzés található a(z) %d. URI-azonosítónál ebben: %d (NURL: %s != %s).';
$a->strings['Deletion of id %d failed'] = 'A(z) %d. azonosító törlése sikertelen';
$a->strings['Deletion of id %d was successful'] = 'A(z) %d. azonosító törlése sikeres volt';
$a->strings['Updating "%s" in "%s" from %d to %d'] = '„%s” frissítése ebben: „%s”, %d. értéktől %d. értékig';
$a->strings[' - found'] = ' – megtalálva';
$a->strings[' - failed'] = ' – sikertelen';
$a->strings[' - success'] = ' – sikeres';
$a->strings[' - deleted'] = ' – törölve';
$a->strings[' - done'] = ' – kész';
$a->strings['The avatar cache needs to be enabled to use this command.'] = 'A profilkép gyorsítótárának engedélyezve kell lennie a parancs használatához.';
$a->strings['no resource in photo %s'] = 'nincs erőforrás a(z) %s fényképen';
$a->strings['no photo with id %s'] = 'nincs %s azonosítóval rendelkező fénykép';
$a->strings['no image data for photo with id %s'] = 'nincsenek képadatok a(z) %s azonosítóval rendelkező fényképnél';
$a->strings['invalid image for id %s'] = 'érvénytelen kép a(z) %s azonosítónál';
$a->strings['Quit on invalid photo %s'] = 'Kilépés az érvénytelen %s fényképnél';
$a->strings['Post update version number has been set to %s.'] = 'A bejegyzésfrissítés verziószáma erre lett beállítva: %s.';
$a->strings['Check for pending update actions.'] = 'Függőben lévő frissítési műveletek ellenőrzése.';
$a->strings['Done.'] = 'Kész.';
$a->strings['Execute pending post updates.'] = 'Függőben lévő bejegyzésfrissítések végrehajtása.';
$a->strings['All pending post updates are done.'] = 'Az összes függőben lévő bejegyzésfrissítés kész.';
$a->strings['Enter user nickname: '] = 'Felhasználó becenevének megadása: ';
$a->strings['User not found'] = 'A felhasználó nem található';
$a->strings['Enter new password: '] = 'Új jelszó megadása: ';
$a->strings['Password update failed. Please try again.'] = 'A jelszó frissítése sikertelen. Próbálja újra.';
$a->strings['Password changed.'] = 'A jelszó megváltoztatva.';
$a->strings['Enter user name: '] = 'Felhasználónév megadása: ';
$a->strings['Enter user email address: '] = 'Felhasználó e-mail-címének megadása: ';
$a->strings['Enter a language (optional): '] = 'Nyelv megadása (elhagyható): ';
$a->strings['Enter URL of an image to use as avatar (optional): '] = 'Adja meg a profilképként használandó kép URL-jét (elhagyható): ';
$a->strings['User is not pending.'] = 'A felhasználó nincs függőben.';
$a->strings['User has already been marked for deletion.'] = 'A felhasználó már meg lett jelölve törlésre.';
$a->strings['Type "yes" to delete %s'] = 'Írja be a „yes” szót %s törléséhez';
$a->strings['Deletion aborted.'] = 'Törlés megszakítva.';
$a->strings['Enter category: '] = 'Kategória megadása: ';
$a->strings['Enter key: '] = 'Kulcs megadása: ';
$a->strings['Enter value: '] = 'Érték megadása: ';
$a->strings['newer'] = 'soha';
$a->strings['older'] = 'régebbi';
$a->strings['Frequently'] = 'Gyakran';
$a->strings['Hourly'] = 'Óránként';
$a->strings['Twice daily'] = 'Naponta kétszer';
$a->strings['Daily'] = 'Naponta';
$a->strings['Weekly'] = 'Hetente';
$a->strings['Monthly'] = 'Havonta';
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
$a->strings['Discourse'] = 'Discourse';
$a->strings['Diaspora Connector'] = 'Diaspora összekötő';
$a->strings['GNU Social Connector'] = 'GNU Social összekötő';
$a->strings['ActivityPub'] = 'ActivityPub';
$a->strings['pnut'] = 'pnut';
$a->strings['Tumblr'] = 'Tumblr';
$a->strings['Bluesky'] = 'Bluesky';
$a->strings['%s (via %s)'] = '%s (ezen keresztül: %s)';
$a->strings['and'] = 'és';
$a->strings['and %d other people'] = 'és %d más személy';
$a->strings['%2$s likes this.'] = [
	0 => '%2$s kedveli ezt.',
	1 => '%2$s kedveli ezt.',
];
$a->strings['%2$s doesn\'t like this.'] = [
	0 => '%2$s nem kedveli ezt.',
	1 => '%2$s nem kedveli ezt.',
];
$a->strings['%2$s attends.'] = [
	0 => '%2$s részt vesz.',
	1 => '%2$s részt vesz.',
];
$a->strings['%2$s doesn\'t attend.'] = [
	0 => '%2$s nem vesz részt.',
	1 => '%2$s nem vesz részt.',
];
$a->strings['%2$s attends maybe.'] = [
	0 => '%2$s talán részt vesz.',
	1 => '%2$s talán részt vesz.',
];
$a->strings['%2$s reshared this.'] = [
	0 => '%2$s újra megosztotta ezt.',
	1 => '%2$s újra megosztotta ezt.',
];
$a->strings['<button type="button" %2$s>%1$d person</button> likes this'] = [
	0 => '<button type="button" %2$s>%1$d személy</button> kedveli ezt',
	1 => '<button type="button" %2$s>%1$d személy</button> kedveli ezt',
];
$a->strings['<button type="button" %2$s>%1$d person</button> doesn\'t like this'] = [
	0 => '<button type="button" %2$s>%1$d személy</button> nem kedveli ezt',
	1 => '<button type="button" %2$s>%1$d személy</button> nem kedveli ezt',
];
$a->strings['<button type="button" %2$s>%1$d person</button> attends'] = [
	0 => '<button type="button" %2$s>%1$d személy</button> részt vesz',
	1 => '<button type="button" %2$s>%1$d személy</button> részt vesz',
];
$a->strings['<button type="button" %2$s>%1$d person</button> doesn\'t attend'] = [
	0 => '<button type="button" %2$s>%1$d személy</button> nem vesz részt',
	1 => '<button type="button" %2$s>%1$d személy</button> nem vesz részt',
];
$a->strings['<button type="button" %2$s>%1$d person</button> attends maybe'] = [
	0 => '<button type="button" %2$s>%1$d személy</button> talán részt vesz',
	1 => '<button type="button" %2$s>%1$d személy</button> talán részt vesz',
];
$a->strings['<button type="button" %2$s>%1$d person</button> reshared this'] = [
	0 => '<button type="button" %2$s>%1$d személy</button> újra megosztotta ezt',
	1 => '<button type="button" %2$s>%1$d személy</button> újra megosztotta ezt',
];
$a->strings['Visible to <strong>everybody</strong>'] = 'Látható <strong>mindenkinek</strong>';
$a->strings['Please enter a image/video/audio/webpage URL:'] = 'Írjon be egy kép, videó, hang vagy weboldal URL-t:';
$a->strings['Tag term:'] = 'Címkézési kifejezés:';
$a->strings['Where are you right now?'] = 'Hol van most éppen?';
$a->strings['Delete item(s)?'] = 'Törli az elemeket?';
$a->strings['Created at'] = 'Létrehozva';
$a->strings['New Post'] = 'Új bejegyzés';
$a->strings['Share'] = 'Megosztás';
$a->strings['upload photo'] = 'fénykép feltöltése';
$a->strings['Attach file'] = 'Fájl csatolása';
$a->strings['attach file'] = 'fájl csatolása';
$a->strings['Bold'] = 'Félkövér';
$a->strings['Italic'] = 'Dőlt';
$a->strings['Underline'] = 'Aláhúzott';
$a->strings['Quote'] = 'Idézet';
$a->strings['Add emojis'] = 'Emodzsik hozzáadása';
$a->strings['Content Warning'] = 'Tartalom figyelmeztetés';
$a->strings['Code'] = 'Kód';
$a->strings['Image'] = 'Kép';
$a->strings['Link'] = 'Hivatkozás';
$a->strings['Link or Media'] = 'Hivatkozás vagy média';
$a->strings['Video'] = 'Videó';
$a->strings['Set your location'] = 'Az Ön helyének beállítása';
$a->strings['set location'] = 'hely beállítása';
$a->strings['Clear browser location'] = 'Böngésző helyének törlése';
$a->strings['clear location'] = 'hely törlése';
$a->strings['Set title'] = 'Cím beállítása';
$a->strings['Categories (comma-separated list)'] = 'Kategóriák (vesszővel elválasztott lista)';
$a->strings['Scheduled at'] = 'Ütemezve ekkor';
$a->strings['Permission settings'] = 'Jogosultsági beállítások';
$a->strings['Public post'] = 'Nyilvános bejegyzés';
$a->strings['Message'] = 'Üzenet';
$a->strings['Browser'] = 'Böngésző';
$a->strings['Open Compose page'] = 'Írás oldal megnyitása';
$a->strings['remove'] = 'eltávolítás';
$a->strings['Delete Selected Items'] = 'Kijelölt elemek törlése';
$a->strings['You had been addressed (%s).'] = 'Önt megszólították (%s).';
$a->strings['You are following %s.'] = 'Ön követi őt: %s.';
$a->strings['You subscribed to %s.'] = 'Ön feliratkozott erre: %s.';
$a->strings['You subscribed to one or more tags in this post.'] = 'Ön feliratkozott egy vagy több címkére ebben a bejegyzésben.';
$a->strings['%s reshared this.'] = '%s újra megosztotta ezt.';
$a->strings['Reshared'] = 'Újra megosztva';
$a->strings['Reshared by %s <%s>'] = '%s <%s> újra megosztotta';
$a->strings['%s is participating in this thread.'] = '%s részt vesz ebben a szálban.';
$a->strings['Stored for general reasons'] = 'Általános okokból tárolva';
$a->strings['Global post'] = 'Globális bejegyzés';
$a->strings['Sent via an relay server'] = 'Elküldve egy továbbító kiszolgálón keresztül';
$a->strings['Sent via the relay server %s <%s>'] = 'Elküldve a(z) %s <%s> továbbító kiszolgálón keresztül';
$a->strings['Fetched'] = 'Lekérve';
$a->strings['Fetched because of %s <%s>'] = 'Lekérve %s <%s> miatt';
$a->strings['Stored because of a child post to complete this thread.'] = 'Eltárolva egy gyermekbejegyzés miatt, hogy befejezze ezt a szálat.';
$a->strings['Local delivery'] = 'Helyi kézbesítés';
$a->strings['Stored because of your activity (like, comment, star, ...)'] = 'Eltárolva az Ön tevékenysége miatt (kedvelés, hozzászólás, csillagozás stb.)';
$a->strings['Distributed'] = 'Elosztott';
$a->strings['Pushed to us'] = 'Leküldve nekünk';
$a->strings['Pinned item'] = 'Kitűzött elem';
$a->strings['View %s\'s profile @ %s'] = '%s profiljának megtekintése ezen: %s';
$a->strings['Categories:'] = 'Kategóriák:';
$a->strings['Filed under:'] = 'Iktatva itt:';
$a->strings['%s from %s'] = '%s tőle: %s';
$a->strings['View in context'] = 'Megtekintés környezetben';
$a->strings['For you'] = 'Önnek';
$a->strings['Posts from contacts you interact with and who interact with you'] = 'Azoktól a partnerektől származó bejegyzések, akikkel kapcsolatba kerül és akik kapcsolatba kerülnek Önnel';
$a->strings['Discover'] = 'Felfedezés';
$a->strings['Posts from accounts that you don\'t follow, but that you might like.'] = 'Az olyan fiókokból származó bejegyzések, amelyeket nem követ, de kedvelheti azokat.';
$a->strings['What\'s Hot'] = 'Mi a menő';
$a->strings['Posts with a lot of interactions'] = 'Sok interakcióval rendelkező bejegyzések';
$a->strings['Posts in %s'] = 'Bejegyzések ebben: %s';
$a->strings['Posts from your followers that you don\'t follow'] = 'Az olyan követőitől származó bejegyzések, akiket nem követ';
$a->strings['Sharers of sharers'] = 'Megosztók megosztói';
$a->strings['Posts from accounts that are followed by accounts that you follow'] = 'Az Ön által követett fiókok által követett fiókokból származó bejegyzések';
$a->strings['Quiet sharers'] = 'Csendes megosztók';
$a->strings['Posts from accounts that you follow but who don\'t post very often'] = 'Az olyan fiókokból származó bejegyzések, amelyeket követ, de nem hoznak létre bejegyzést túl gyakran';
$a->strings['Images'] = 'Képek';
$a->strings['Posts with images'] = 'Képekkel rendelkező bejegyzések';
$a->strings['Audio'] = 'Hang';
$a->strings['Posts with audio'] = 'Hanggal rendelkező bejegyzések';
$a->strings['Videos'] = 'Videók';
$a->strings['Posts with videos'] = 'Videókkal rendelkező bejegyzések';
$a->strings['Local Community'] = 'Helyi közösség';
$a->strings['Posts from local users on this server'] = 'Bejegyzések a kiszolgálón lévő helyi felhasználóktól';
$a->strings['Global Community'] = 'Globális közösség';
$a->strings['Posts from users of the whole federated network'] = 'Bejegyzések a teljes föderált hálózat felhasználóitól';
$a->strings['Latest Activity'] = 'Legutóbbi tevékenység';
$a->strings['Sort by latest activity'] = 'Rendezés a legutóbbi tevékenység szerint';
$a->strings['Latest Posts'] = 'Legutóbbi bejegyzések';
$a->strings['Sort by post received date'] = 'Rendezés a bejegyzés érkezési dátuma szerint';
$a->strings['Latest Creation'] = 'Legutóbbi létrehozás';
$a->strings['Sort by post creation date'] = 'Rendezés a bejegyzés létrehozási dátuma szerint';
$a->strings['Personal'] = 'Személyes';
$a->strings['Posts that mention or involve you'] = 'Bejegyzések, amelyek említik vagy tartalmazzák Önt';
$a->strings['Starred'] = 'Csillagozott';
$a->strings['Favourite Posts'] = 'Kedvenc bejegyzések';
$a->strings['General Features'] = 'Általános funkciók';
$a->strings['Photo Location'] = 'Fénykép helye';
$a->strings['Photo metadata is normally stripped. This extracts the location (if present) prior to stripping metadata and links it to a map.'] = 'A fénykép metaadatai általában ki vannak törölve. Ez kinyeri a helyet (ha meg van adva) a metaadatok törlése előtt, és hivatkozást készít rá egy térképen.';
$a->strings['Display the community in the navigation'] = 'A közösség megjelenítése a navigációban';
$a->strings['If enabled, the community can be accessed via the navigation menu. Independent from this setting, the community timelines can always be accessed via the channels.'] = 'Ha engedélyezve van, akkor a közösség elérhető a navigációs menün keresztül. Ettől a beállítástól függetlenül a közösség idővonalai mindig elérhetők a csatornákon keresztül.';
$a->strings['Post Composition Features'] = 'Bejegyzés-összeállítási funkciók';
$a->strings['Explicit Mentions'] = 'Közvetlen említések';
$a->strings['Add explicit mentions to comment box for manual control over who gets mentioned in replies.'] = 'Közvetlen említések hozzáadása a hozzászólásmezőhöz kézi vezérléssel, hogy ki lesz megemlítve a válaszokban.';
$a->strings['Add an abstract from ActivityPub content warnings'] = 'Kivonat hozzáadása az ActivityPub tartalomfigyelmeztetéseiből';
$a->strings['Add an abstract when commenting on ActivityPub posts with a content warning. Abstracts are displayed as content warning on systems like Mastodon or Pleroma.'] = 'Kivonat hozzáadása a tartalomfigyelmeztetéssel rendelkező ActivityPub bejegyzéseknél történő hozzászóláskor. A kivonatok tartalomfigyelmeztetésként jelennek meg az olyan rendszerekben, mint a Mastodon vagy a Pleroma.';
$a->strings['Post/Comment Tools'] = 'Bejegyzés és hozzászólás eszközök';
$a->strings['Post Categories'] = 'Bejegyzéskategóriák';
$a->strings['Add categories to your posts'] = 'Kategóriák hozzáadása a bejegyzéseihez.';
$a->strings['Network Widgets'] = 'Hálózat felületi elemek';
$a->strings['Circles'] = 'Körök';
$a->strings['Display posts that have been created by accounts of the selected circle.'] = 'Azon bejegyzések megjelenítése, amelyeket a kiválasztott kör fiókjai hoztak létre.';
$a->strings['Groups'] = 'Csoportok';
$a->strings['Display posts that have been distributed by the selected group.'] = 'Azon bejegyzések megjelenítése, amelyeket a kiválasztott csoport terjeszt.';
$a->strings['Archives'] = 'Archívumok';
$a->strings['Display an archive where posts can be selected by month and year.'] = 'Egy olyan archívum megjelenítése, ahol a bejegyzések kiválaszthatók hónap és év szerint.';
$a->strings['Protocols'] = 'Protokollok';
$a->strings['Display posts with the selected protocols.'] = 'A kiválasztott protokollokkal rendelkező bejegyzések megjelenítése.';
$a->strings['Account Types'] = 'Fióktípusok';
$a->strings['Display posts done by accounts with the selected account type.'] = 'A kiválasztott fióktípussal rendelkező fiókok által készített bejegyzések megjelenítése.';
$a->strings['Channels'] = 'Csatornák';
$a->strings['Display posts in the system channels and user defined channels.'] = 'Bejegyzések megjelenítése a rendszercsatornákon és a felhasználó által meghatározott csatornákon.';
$a->strings['Saved Searches'] = 'Mentett keresések';
$a->strings['Display posts that contain subscribed hashtags.'] = 'A feliratkozott kettős keresztes címkéket tartalmazó bejegyzések megjelenítése.';
$a->strings['Saved Folders'] = 'Mentett mappák';
$a->strings['Display a list of folders in which posts are stored.'] = 'Azon mappák listájának megjelenítése, amelyekben bejegyzések vannak tárolva.';
$a->strings['Own Contacts'] = 'Saját partnerek';
$a->strings['Include or exclude posts from subscribed accounts. This widget is not visible on all channels.'] = 'A feliratkozott fiókokból származó bejegyzések felvétele vagy kizárása. Ez a felületi elem nem látható az összes csatornán.';
$a->strings['Trending Tags'] = 'Népszerű címkék';
$a->strings['Display a list of the most popular tags in recent public posts.'] = 'A legutóbbi nyilvános bejegyzésekben lévő legnépszerűbb címkék listájának megjelenítése.';
$a->strings['Advanced Profile Settings'] = 'Speciális profilbeállítások';
$a->strings['Tag Cloud'] = 'Címkefelhő';
$a->strings['Provide a personal tag cloud on your profile page'] = 'Személyes címkefelhő biztosítása a profiloldalán.';
$a->strings['Display Membership Date'] = 'Tagsági dátum megjelenítése';
$a->strings['Display membership date in profile'] = 'Tagsági dátum megjelenítése a profilban.';
$a->strings['Advanced Calendar Settings'] = 'Speciális naptárbeállítások';
$a->strings['Allow anonymous access to your calendar'] = 'Névtelen hozzáférés engedélyezése a naptárához';
$a->strings['Allows anonymous visitors to consult your calendar and your public events. Contact birthday events are private to you.'] = 'Lehetővé teszi a névtelen látogatók számára a naptára és a nyilvános eseményei megtekintését. A partner születésnapi eseményei az Ön számára magánjellegűek.';
$a->strings['External link to group'] = 'Külső hivatkozás a csoporthoz';
$a->strings['show less'] = 'kevesebb megjelenítése';
$a->strings['show more'] = 'több megjelenítése';
$a->strings['Create new group'] = 'Új csoport létrehozása';
$a->strings['event'] = 'esemény';
$a->strings['status'] = 'állapot';
$a->strings['photo'] = 'fénykép';
$a->strings['%1$s tagged %2$s\'s %3$s with %4$s'] = '%1$s megjelölte %2$s %3$s vele: %4$s';
$a->strings['Follow Thread'] = 'Szál követése';
$a->strings['View Status'] = 'Állapot megtekintése';
$a->strings['View Profile'] = 'Profil megtekintése';
$a->strings['View Photos'] = 'Fényképek megtekintése';
$a->strings['Network Posts'] = 'Hálózati bejegyzések';
$a->strings['View Contact'] = 'Partner megtekintése';
$a->strings['Send PM'] = 'Személyes üzenet küldése';
$a->strings['Block'] = 'Tiltás';
$a->strings['Ignore'] = 'Mellőzés';
$a->strings['Collapse'] = 'Összecsukás';
$a->strings['Ignore %s server'] = 'A(z) %s kiszolgáló mellőzése';
$a->strings['Languages'] = 'Nyelvek';
$a->strings['Search Text'] = 'Szöveg keresése';
$a->strings['Connect/Follow'] = 'Kapcsolódás vagy követés';
$a->strings['Unable to fetch user.'] = 'Nem lehet lekérni a felhasználót.';
$a->strings['Nothing new here'] = 'Semmi új nincs itt';
$a->strings['Home'] = 'Kezdőlap';
$a->strings['Skip to main content'] = 'Kihagyás a fő tartalomhoz';
$a->strings['Clear notifications'] = 'Értesítések törlése';
$a->strings['@name, !group, #tags, content'] = '@név, !csoport, #címkék, tartalom';
$a->strings['Logout'] = 'Kijelentkezés';
$a->strings['End this session'] = 'Munkamenet befejezése';
$a->strings['Login'] = 'Bejelentkezés';
$a->strings['Sign in'] = 'Bejelentkezés';
$a->strings['Conversations'] = 'Beszélgetések';
$a->strings['Conversations you started'] = 'Ön által elkezdett beszélgetések';
$a->strings['Profile'] = 'Profil';
$a->strings['Your profile page'] = 'Az Ön profiloldala';
$a->strings['Photos'] = 'Fényképek';
$a->strings['Your photos'] = 'Az Ön fényképei';
$a->strings['Media'] = 'Média';
$a->strings['Your postings with media'] = 'Az Ön médiával rendelkező beküldései';
$a->strings['Calendar'] = 'Naptár';
$a->strings['Your calendar'] = 'Az Ön naptára';
$a->strings['Personal notes'] = 'Személyes jegyzetek';
$a->strings['Your personal notes'] = 'Az Ön személyes jegyzetei';
$a->strings['Home Page'] = 'Kezdőlap';
$a->strings['Register'] = 'Regisztráció';
$a->strings['Create an account'] = 'Fiók létrehozása';
$a->strings['Help'] = 'Súgó';
$a->strings['Help and documentation'] = 'Súgó és dokumentáció';
$a->strings['Apps'] = 'Alkalmazások';
$a->strings['Addon applications, utilities, games'] = 'Bővítményalkalmazások, segédprogramok és játékok';
$a->strings['Search'] = 'Keresés';
$a->strings['Search site content'] = 'Oldaltartalom keresése';
$a->strings['Full Text'] = 'Teljes szöveg';
$a->strings['Tags'] = 'Címkék';
$a->strings['Contacts'] = 'Partnerek';
$a->strings['Community'] = 'Közösség';
$a->strings['Conversations on this and other servers'] = 'Beszélgetések ezen és más kiszolgálókon';
$a->strings['Directory'] = 'Könyvtár';
$a->strings['People directory'] = 'Emberek könyvtár';
$a->strings['Information'] = 'Információk';
$a->strings['Information about this friendica instance'] = 'Információk erről a Friendica példányról';
$a->strings['Terms of Service'] = 'Használati feltételek';
$a->strings['Terms of Service of this Friendica instance'] = 'Ezen Friendica példány használati feltételei';
$a->strings['Network'] = 'Hálózat';
$a->strings['Conversations from your friends'] = 'Ismerősökkel való beszélgetések';
$a->strings['Your posts and conversations'] = 'Az Ön bejegyzései és beszélgetései';
$a->strings['Introductions'] = 'Bemutatkozások';
$a->strings['Friend Requests'] = 'Ismerőskérések';
$a->strings['Notifications'] = 'Értesítések';
$a->strings['See all notifications'] = 'Összes értesítés megtekintése';
$a->strings['Mark as seen'] = 'Megjelölés olvasottként';
$a->strings['Mark all system notifications as seen'] = 'Összes rendszerértesítés megjelölése olvasottként';
$a->strings['Private mail'] = 'Személyes levél';
$a->strings['Inbox'] = 'Beérkezett üzenetek';
$a->strings['Outbox'] = 'Elküldött üzenetek';
$a->strings['Accounts'] = 'Fiókok';
$a->strings['Manage other pages'] = 'Más oldalak kezelése';
$a->strings['Settings'] = 'Beállítások';
$a->strings['Account settings'] = 'Fiókbeállítások';
$a->strings['Manage/edit friends and contacts'] = 'Ismerősök és partnerek kezelése vagy szerkesztése';
$a->strings['Admin'] = 'Adminisztráció';
$a->strings['Site setup and configuration'] = 'Oldal beállítása és konfigurálás';
$a->strings['Moderation'] = 'Moderálás';
$a->strings['Content and user moderation'] = 'Tartalom- és felhasználómoderálás';
$a->strings['Navigation'] = 'Navigáció';
$a->strings['Site map'] = 'Oldaltérkép';
$a->strings['first'] = 'első';
$a->strings['prev'] = 'előző';
$a->strings['next'] = 'következő';
$a->strings['last'] = 'utolsó';
$a->strings['<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> %3$s'] = '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> %3$s';
$a->strings['Link to source'] = 'Hivatkozás a forráshoz';
$a->strings['Click to open/close'] = 'Kattintson a megnyitáshoz vagy bezáráshoz';
$a->strings['$1 wrote:'] = '$1 írta:';
$a->strings['Encrypted content'] = 'Titkosított tartalom';
$a->strings['Invalid source protocol'] = 'Érvénytelen forrásprotokoll';
$a->strings['Invalid link protocol'] = 'Érvénytelen hivatkozási protokoll';
$a->strings['Loading more entries...'] = 'További bejegyzések betöltése…';
$a->strings['The end'] = 'Vége';
$a->strings['Follow'] = 'Követés';
$a->strings['Add New Contact'] = 'Új partner hozzáadása';
$a->strings['Enter address or web location'] = 'Cím vagy webhely megadása';
$a->strings['Example: bob@example.com, http://example.com/barbara'] = 'Példa: bob@example.com, http://example.com/barbara';
$a->strings['Connect'] = 'Kapcsolódás';
$a->strings['%d invitation available'] = [
	0 => '%d meghívás érhető el',
	1 => '%d meghívás érhető el',
];
$a->strings['Find People'] = 'Emberek keresése';
$a->strings['Enter name or interest'] = 'Név vagy érdeklődés beírása';
$a->strings['Examples: Robert Morgenstein, Fishing'] = 'Példák: Szabó János, Halászat';
$a->strings['Find'] = 'Keresés';
$a->strings['Friend Suggestions'] = 'Ismerős javaslatok';
$a->strings['Similar Interests'] = 'Hasonló érdeklődések';
$a->strings['Random Profile'] = 'Véletlen profil';
$a->strings['Invite Friends'] = 'Ismerősök meghívása';
$a->strings['Global Directory'] = 'Globális könyvtár';
$a->strings['Local Directory'] = 'Helyi könyvtár';
$a->strings['Everyone'] = 'Mindenki';
$a->strings['No relationship'] = 'Nincs kapcsolat';
$a->strings['Relationships'] = 'Kapcsolatok';
$a->strings['All Contacts'] = 'Összes partner';
$a->strings['All Protocols'] = 'Összes protokoll';
$a->strings['Everything'] = 'Minden';
$a->strings['Categories'] = 'Kategóriák';
$a->strings['%d contact in common'] = [
	0 => '%d partner közös',
	1 => '%d partner közös',
];
$a->strings['On this date'] = 'Ezen a napon';
$a->strings['Persons'] = 'Személyek';
$a->strings['Organisations'] = 'Szervezetek';
$a->strings['News'] = 'Hírek';
$a->strings['Relays'] = 'Továbbítók';
$a->strings['All'] = 'Összes';
$a->strings['Export'] = 'Exportálás';
$a->strings['Export calendar as ical'] = 'Naptár exportálása iCal-ként';
$a->strings['Export calendar as csv'] = 'Naptár exportálása CSV-ként';
$a->strings['No contacts'] = 'Nincsenek partnerek';
$a->strings['%d Contact'] = [
	0 => '%d partner',
	1 => '%d partner',
];
$a->strings['View Contacts'] = 'Partnerek megtekintése';
$a->strings['Remove term'] = 'Kifejezés eltávolítása';
$a->strings['Trending Tags (last %d hour)'] = [
	0 => 'Népszerű címkék (legutóbbi %d óra)',
	1 => 'Népszerű címkék (legutóbbi %d óra)',
];
$a->strings['More Trending Tags'] = 'További népszerű címkék';
$a->strings['Post to group'] = 'Beküldés csoportba';
$a->strings['Mention'] = 'Említés';
$a->strings['XMPP:'] = 'XMPP:';
$a->strings['Matrix:'] = 'Mátrix:';
$a->strings['Location:'] = 'Hely:';
$a->strings['Network:'] = 'Hálózat:';
$a->strings['Unfollow'] = 'Követés megszüntetése';
$a->strings['View group'] = 'Csoport megtekintése';
$a->strings['Yourself'] = 'Önmaga';
$a->strings['Mutuals'] = 'Kölcsönösen ismerősök';
$a->strings['Post to Email'] = 'Beküldés e-mailbe';
$a->strings['Public'] = 'Nyilvános';
$a->strings['This content will be shown to all your followers and can be seen in the community pages and by anyone with its link.'] = 'Ez a tartalom meg fog jelenni az összes követőjének, és látható lesz a közösségi oldalakon, valamint bárki számára a hivatkozásával.';
$a->strings['Limited/Private'] = 'Korlátozott vagy személyes';
$a->strings['This content will be shown only to the people in the first box, to the exception of the people mentioned in the second box. It won\'t appear anywhere public.'] = 'Ez a tartalom csak az első mezőben lévő embereknek fog megjelenni, kivéve a második mezőben említett embereknek. Nem jelenik meg sehol sem nyilvánosan.';
$a->strings['Start typing the name of a contact or a circle to show a filtered list. You can also mention the special circles "Followers" and "Mutuals".'] = 'Kezdje el gépelni egy partner vagy kör nevét egy szűrt lista megjelenítéséhez. Megemlítheti a „Követők” és a „Kölcsönösen ismerősök” különleges köröket is.';
$a->strings['Show to:'] = 'Megjelenítés nekik:';
$a->strings['Except to:'] = 'Kivéve nekik:';
$a->strings['CC: email addresses'] = 'Másolat: e-mail-címek';
$a->strings['Example: bob@example.com, mary@example.com'] = 'Példa: bob@example.com, mary@example.com';
$a->strings['Connectors'] = 'Összekötők';
$a->strings['The database configuration file "config/local.config.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.'] = 'A „config/local.config.php” adatbázis-beállítófájlt nem sikerült írni. Használja a mellékelt szöveget egy beállítófájl létrehozásához a webkiszolgáló gyökerében.';
$a->strings['You may need to import the file "database.sql" manually using phpmyadmin or mysql.'] = 'Lehet, hogy kézzel kell importálnia a „database.sql” fájlt phpMyAdmin vagy MySQL használatával.';
$a->strings['Please see the file "doc/INSTALL.md".'] = 'Nézze meg a „doc/INSTALL.md” fájlt.';
$a->strings['Could not find a command line version of PHP in the web server PATH.'] = 'Nem sikerült megtalálni a PHP parancssori verzióját a webkiszolgáló PATH környezeti változójában.';
$a->strings['If you don\'t have a command line version of PHP installed on your server, you will not be able to run the background processing. See <a href=\'https://github.com/friendica/friendica/blob/stable/doc/Install.md#set-up-the-worker\'>\'Setup the worker\'</a>'] = 'Ha nincs telepítve a PHP parancssori verziója a kiszolgálóján, akkor nem lesz képes futtatni a háttérfeldolgozást. Nézze meg a <a href=\'https://github.com/friendica/friendica/blob/stable/doc/Install.md#set-up-the-worker\'>Setup the worker</a> szakaszt a dokumentációban (angol nyelven).';
$a->strings['PHP executable path'] = 'PHP végrehajtható útvonala';
$a->strings['Enter full path to php executable. You can leave this blank to continue the installation.'] = 'A teljes útvonal megadása a PHP végrehajthatóhoz. Ezt üresen hagyhatja a telepítés folytatásához.';
$a->strings['Command line PHP'] = 'Parancssori PHP';
$a->strings['PHP executable is not the php cli binary (could be cgi-fgci version)'] = 'A PHP végrehajtható nem a PHP parancssori bináris (lehet cgi-fgci verzió)';
$a->strings['Found PHP version: '] = 'Megtalált PHP-verzió: ';
$a->strings['PHP cli binary'] = 'PHP parancssori bináris';
$a->strings['The command line version of PHP on your system does not have "register_argc_argv" enabled.'] = 'A rendszerén lévő PHP parancssori verziójában a „register_argc_argv” nincs engedélyezve.';
$a->strings['This is required for message delivery to work.'] = 'Ez ahhoz szükséges, hogy az üzenetkézbesítés működjön.';
$a->strings['PHP register_argc_argv'] = 'PHP register_argc_argv';
$a->strings['Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys'] = 'Hiba: az „openssl_pkey_new” függvény ezen a rendszeren nem képes titkosítási kulcsokat előállítani';
$a->strings['If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".'] = 'Ha Windows alatt fut, akkor nézze meg a „http://www.php.net/manual/en/openssl.installation.php” dokumentációt.';
$a->strings['Generate encryption keys'] = 'Titkosítási kulcsok előállítása';
$a->strings['Error: Apache webserver mod-rewrite module is required but not installed.'] = 'Hiba: az Apache webkiszolgáló mod-rewrite modulja szükséges, de nincs telepítve.';
$a->strings['Apache mod_rewrite module'] = 'Apache mod_rewrite modul';
$a->strings['Error: PDO or MySQLi PHP module required but not installed.'] = 'Hiba: a PDO vagy a MySQLi PHP-modul szükséges, de nincs telepítve.';
$a->strings['Error: The MySQL driver for PDO is not installed.'] = 'Hiba: a PDO-hoz szükséges MySQL illesztőprogram nincs telepítve.';
$a->strings['PDO or MySQLi PHP module'] = 'PDO vagy MySQLi PHP-modul';
$a->strings['Error: The IntlChar module is not installed.'] = 'Hiba: az IntlChar modul nincs telepítve.';
$a->strings['IntlChar PHP module'] = 'IntlChar PHP-modul';
$a->strings['Error, XML PHP module required but not installed.'] = 'Hiba: az XML PHP-modul szükséges, de nincs telepítve.';
$a->strings['XML PHP module'] = 'XML PHP-modul';
$a->strings['libCurl PHP module'] = 'libCurl PHP-modul';
$a->strings['Error: libCURL PHP module required but not installed.'] = 'Hiba: az libCURL PHP-modul szükséges, de nincs telepítve.';
$a->strings['GD graphics PHP module'] = 'GD grafikai PHP-modul';
$a->strings['Error: GD graphics PHP module with JPEG support required but not installed.'] = 'Hiba: a JPEG támogatással rendelkező GD grafikai PHP-modul szükséges, de nincs telepítve.';
$a->strings['OpenSSL PHP module'] = 'OpenSSL PHP-modul';
$a->strings['Error: openssl PHP module required but not installed.'] = 'Hiba: az OpenSSL PHP-modul szükséges, de nincs telepítve.';
$a->strings['mb_string PHP module'] = 'mb_string PHP-modul';
$a->strings['Error: mb_string PHP module required but not installed.'] = 'Hiba: az mb_string PHP-modul szükséges, de nincs telepítve.';
$a->strings['iconv PHP module'] = 'iconv PHP-modul';
$a->strings['Error: iconv PHP module required but not installed.'] = 'Hiba: az iconv PHP-modul szükséges, de nincs telepítve.';
$a->strings['POSIX PHP module'] = 'POSIX PHP-modul';
$a->strings['Error: POSIX PHP module required but not installed.'] = 'Hiba: a POSIX PHP-modul szükséges, de nincs telepítve.';
$a->strings['Program execution functions'] = 'Programvégrehajtási funkciók';
$a->strings['Error: Program execution functions (proc_open) required but not enabled.'] = 'Hiba: a programvégrehajtási funkciók (proc_open) szükségesek, de nincsenek engedélyezve.';
$a->strings['JSON PHP module'] = 'JSON PHP-modul';
$a->strings['Error: JSON PHP module required but not installed.'] = 'Hiba: a JSON PHP-modul szükséges, de nincs telepítve.';
$a->strings['File Information PHP module'] = 'Fájlinformációk PHP-modul';
$a->strings['Error: File Information PHP module required but not installed.'] = 'Hiba: a fájlinformációk PHP-modul szükséges, de nincs telepítve.';
$a->strings['GNU Multiple Precision PHP module'] = 'GNU Multiple Precision PHP-modul';
$a->strings['Error: GNU Multiple Precision PHP module required but not installed.'] = 'Hiba: a GNU Multiple Precision PHP-modul szükséges, de nincs telepítve.';
$a->strings['IDN Functions PHP module'] = 'IDN-függvények PHP-modul';
$a->strings['Error: IDN Functions PHP module required but not installed.'] = 'Hiba: az IDN-függvények PHP-modul szükséges, de nincs telepítve.';
$a->strings['The web installer needs to be able to create a file called "local.config.php" in the "config" folder of your web server and it is unable to do so.'] = 'A webes telepítőnek képesnek kell lennie létrehozni egy „local.config.php” nevű fájlt a webkiszolgáló „config” mappájában, és ezt nem lehet megtenni.';
$a->strings['This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.'] = 'Ez leggyakrabban jogosultsági beállítás, mivel előfordulhat, hogy a webkiszolgáló nem képes fájlokat írni a mappájába, annak ellenére, hogy Ön tud.';
$a->strings['At the end of this procedure, we will give you a text to save in a file named local.config.php in your Friendica "config" folder.'] = 'Ezen eljárás végén adni fogunk Önnek egy szöveget, hogy elmentse egy „local.config.php” nevű fájlba a Friendica „config” mappájában.';
$a->strings['You can alternatively skip this procedure and perform a manual installation. Please see the file "doc/INSTALL.md" for instructions.'] = 'Alternatívaként kihagyhatja ezt az eljárást, és végezhet kézi telepítést. Az utasításokért nézze meg a „doc/INSTALL.txt” fájlt.';
$a->strings['config/local.config.php is writable'] = 'a config/local.config.php írható';
$a->strings['Friendica uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.'] = 'A Friendica a Smarty3 sablonmotort használja a webes nézetei megjelenítéséhez. A Smarty3 lefordítja a sablonokat PHP-ra a megjelenítés felgyorsításához.';
$a->strings['In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top level folder.'] = 'A lefordított sablonok tárolása érdekében a webkiszolgálónak írási hozzáférésre van szüksége a Friendica felső szintű mappája alatti „view/smarty3/” könyvtárhoz.';
$a->strings['Please ensure that the user that your web server runs as (e.g. www-data) has write access to this folder.'] = 'Biztosítsa, hogy a webkiszolgálót futtató felhasználónak (például www-data) legyen írási hozzáférése ehhez a mappához.';
$a->strings['Note: as a security measure, you should give the web server write access to view/smarty3/ only--not the template files (.tpl) that it contains.'] = 'Megjegyzés: biztonsági intézkedésként csak a „view/smarty3/” mappához kell írási hozzáférést adnia a webkiszolgálónak, nem azokhoz a sablonfájlokhoz (.tpl), amelyeket tartalmaz.';
$a->strings['view/smarty3 is writable'] = 'A „view/smarty3” írható';
$a->strings['Url rewrite in .htaccess seems not working. Make sure you copied .htaccess-dist to .htaccess.'] = 'Úgy tűnik, hogy a .htaccess fájlban lévő URL átírás nem működik. Győződjön meg arról, hogy lemásolta-e a .htaccess-dist fájlt .htaccess néven.';
$a->strings['In some circumstances (like running inside containers), you can skip this error.'] = 'Bizonyos körülmények között (például konténereken belül való futtatáskor) átugorhatja ezt a hibát.';
$a->strings['Error message from Curl when fetching'] = 'Hibaüzenet a cURL-től a lekéréskor';
$a->strings['Url rewrite is working'] = 'Az URL átírás működik';
$a->strings['The detection of TLS to secure the communication between the browser and the new Friendica server failed.'] = 'Nem sikerült a TLS felismerése a böngésző és a Friendica kiszolgálója közötti kommunikáció biztonságossá tételéhez.';
$a->strings['It is highly encouraged to use Friendica only over a secure connection as sensitive information like passwords will be transmitted.'] = 'Erősen ajánlott a Friendica kiszolgálót csak biztonságos kapcsolaton keresztül használni, mivel olyan érzékeny információk kerülnek továbbításra, mint például a jelszavak.';
$a->strings['Please ensure that the connection to the server is secure.'] = 'Győződjön meg arról, hogy a kiszolgálóval való kapcsolat biztonságos.';
$a->strings['No TLS detected'] = 'Nincs TLS felismerve';
$a->strings['TLS detected'] = 'TLS felismerve';
$a->strings['ImageMagick PHP extension is not installed'] = 'Az ImageMagick PHP-kiterjesztés nincs telepítve';
$a->strings['ImageMagick PHP extension is installed'] = 'Az ImageMagick PHP-kiterjesztés telepítve van';
$a->strings['Database already in use.'] = 'Az adatbázis már használatban van.';
$a->strings['Could not connect to database.'] = 'Nem sikerült kapcsolódni az adatbázishoz.';
$a->strings['Undetermined'] = 'Nem meghatározott';
$a->strings['%s (%s)'] = '%s (%s)';
$a->strings['Monday'] = 'Hétfő';
$a->strings['Tuesday'] = 'Kedd';
$a->strings['Wednesday'] = 'Szerda';
$a->strings['Thursday'] = 'Csütörtök';
$a->strings['Friday'] = 'Péntek';
$a->strings['Saturday'] = 'Szombat';
$a->strings['Sunday'] = 'Vasárnap';
$a->strings['January'] = 'Január';
$a->strings['February'] = 'Február';
$a->strings['March'] = 'Március';
$a->strings['April'] = 'Április';
$a->strings['May'] = 'Május';
$a->strings['June'] = 'Június';
$a->strings['July'] = 'Július';
$a->strings['August'] = 'Augusztus';
$a->strings['September'] = 'Szeptember';
$a->strings['October'] = 'Október';
$a->strings['November'] = 'November';
$a->strings['December'] = 'December';
$a->strings['Mon'] = 'Hét';
$a->strings['Tue'] = 'Ked';
$a->strings['Wed'] = 'Sze';
$a->strings['Thu'] = 'Csü';
$a->strings['Fri'] = 'Pén';
$a->strings['Sat'] = 'Szo';
$a->strings['Sun'] = 'Vas';
$a->strings['Jan'] = 'Jan';
$a->strings['Feb'] = 'Feb';
$a->strings['Mar'] = 'Már';
$a->strings['Apr'] = 'Ápr';
$a->strings['Jun'] = 'Jún';
$a->strings['Jul'] = 'Júl';
$a->strings['Aug'] = 'Aug';
$a->strings['Sep'] = 'Sze';
$a->strings['Oct'] = 'Okt';
$a->strings['Nov'] = 'Nov';
$a->strings['Dec'] = 'Dec';
$a->strings['The logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'A(z) „%s” naplófájl nem használható. Nem lehetséges a naplózás (hiba: „%s”).';
$a->strings['The debug logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'A(z) „%s” hibakeresési naplófájl nem használható. Nem lehetséges a naplózás (hiba: „%s”).';
$a->strings['Friendica can\'t display this page at the moment, please contact the administrator.'] = 'A Friendica jelenleg nem tudja megjeleníteni ezt az oldalt. Vegye fel a kapcsolatot a rendszergazdával.';
$a->strings['template engine cannot be registered without a name.'] = 'a sablonmotort nem lehet regisztrálni név nélkül.';
$a->strings['template engine is not registered!'] = 'a sablonmotor nincs regisztrálva!';
$a->strings['Storage base path'] = 'Tároló alapútvonala';
$a->strings['Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree'] = 'Az a mappa, ahova a feltöltött fájlok mentve lesznek. A legnagyobb biztonság érdekében ennek a webkiszolgáló mappafáján kívüli útvonalnak kell lennie.';
$a->strings['Enter a valid existing folder'] = 'Adjon meg egy érvényes, létező mappát';
$a->strings['Updates from version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.'] = 'A korábbi %s verzióról való frissítések nem támogatottak. Frissítsen legalább a 2021.01-es verzióra, és várja meg, amíg a bejegyzésfrissítés befejezi az 1383-as verziót.';
$a->strings['Updates from postupdate version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.'] = 'A korábbi %s bejegyzésfrissítési verzióról való frissítések nem támogatottak. Frissítsen legalább a 2021.01-es verzióra, és várja meg, amíg a bejegyzésfrissítés befejezi az 1383-as verziót.';
$a->strings['%s: executing pre update %d'] = '%s: %d előfrissítés végrehajtása';
$a->strings['%s: executing post update %d'] = '%s: %d bejegyzésfrissítés végrehajtása';
$a->strings['Update %s failed. See error logs.'] = 'A(z) %s frissítés sikertelen. Nézze meg a hibanaplókat.';
$a->strings['
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can\'t do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.'] = '
				A Friendica fejlesztői nemrég kiadták a(z) %s frissítést, de amikor
				megpróbáltam telepíteni, valami nagyon elromlott.
				Ezt hamarosan javítani kell, és én nem tudom egyedül elvégezni.
				Vegye fel a kapcsolatot egy Friendica fejlesztővel, ha egyedül nem
				tud nekem segíteni. Az adatbázisunk érvénytelen lehet.';
$a->strings['The error message is\n[pre]%s[/pre]'] = 'A hibaüzenet a következő:\n[pre]%s[/pre]';
$a->strings['[Friendica Notify] Database update'] = '[Friendica értesítés] Adatbázis-frissítés';
$a->strings['
				The friendica database was successfully updated from %s to %s.'] = '
				A Friendica adatbázisa sikeresen frissítve lett: %s → %s.';
$a->strings['The database version had been set to %s.'] = 'Az adatbázis verziója %s értékre lett állítva.';
$a->strings['The post update is at version %d, it has to be at %d to safely drop the tables.'] = 'A bejegyzésfrissítés %d. verziónál van. %d. verziónál kell lennie a táblák biztonságos eldobásához.';
$a->strings['No unused tables found.'] = 'Nem találhatók nem használt táblák.';
$a->strings['These tables are not used for friendica and will be deleted when you execute "dbstructure drop -e":'] = 'Ezek a táblák nincsenek használatban a Friendica programnál, és törölve lesznek, ha lefuttatja a „dbstructure drop -e” parancsot:';
$a->strings['There are no tables on MyISAM or InnoDB with the Antelope file format.'] = 'Nincsenek Antelope fájlformátummal rendelkező táblák MyISAM vagy InnoDB motorokon.';
$a->strings['
Error %d occurred during database update:
%s
'] = '
Hiba (%d) történt az adatbázis frissítése során:
%s
';
$a->strings['Errors encountered performing database changes: '] = 'Hibák történtek az adatbázis változtatásainak végrehajtásakor: ';
$a->strings['Another database update is currently running.'] = 'Egy másik adatbázis-frissítés is fut jelenleg.';
$a->strings['%s: Database update'] = '%s: adatbázis-frissítés';
$a->strings['%s: updating %s table.'] = '%s: a(z) %s tábla frissítése.';
$a->strings['Record not found'] = 'A rekord nem található';
$a->strings['Unprocessable Entity'] = 'Feldolgozhatatlan entitás';
$a->strings['Unauthorized'] = 'Nem engedélyezett';
$a->strings['Token is not authorized with a valid user or is missing a required scope'] = 'A token nincs felhatalmazva egy érvényes felhasználóval, vagy hiányzik a szükséges hatókör';
$a->strings['Internal Server Error'] = 'Belső kiszolgálóhiba';
$a->strings['Legacy module file not found: %s'] = 'Az örökölt modulfájl nem található: %s';
$a->strings['A deleted circle with this name was revived. Existing item permissions <strong>may</strong> apply to this circle and any future members. If this is not what you intended, please create another circle with a different name.'] = 'Egy ilyen névvel rendelkező törölt kör újraéledt. <strong>Lehet</strong>, hogy a meglévő elemjogosultságok alkalmazva lesznek erre a körre és bármely jövőbeli tagjaira. Ha ez nem az, amit szeretett volna, akkor hozzon létre egy másik kört eltérő névvel.';
$a->strings['Everybody'] = 'Mindenki';
$a->strings['edit'] = 'szerkesztés';
$a->strings['add'] = 'hozzáadás';
$a->strings['Edit circle'] = 'Kör szerkesztése';
$a->strings['Contacts not in any circle'] = 'Egyetlen körben sem lévő partnerek';
$a->strings['Create a new circle'] = 'Új kör létrehozása';
$a->strings['Circle Name: '] = 'Kör neve: ';
$a->strings['Edit circles'] = 'Körök szerkesztése';
$a->strings['Approve'] = 'Jóváhagyás';
$a->strings['%s has blocked you'] = '%s letiltotta Önt';
$a->strings['Organisation'] = 'Szervezet';
$a->strings['Group'] = 'Csoport';
$a->strings['Relay'] = 'Továbbítás';
$a->strings['Disallowed profile URL.'] = 'Nem engedélyezett profil URL.';
$a->strings['Blocked domain'] = 'Tiltott tartomány';
$a->strings['Connect URL missing.'] = 'A kapcsolódási URL hiányzik.';
$a->strings['The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.'] = 'A partnert nem sikerült hozzáadni. Ellenőrizze a hozzá tartozó hálózat hitelesítési adatait a Beállítások → Közösségi hálózatok oldalon.';
$a->strings['Expected network %s does not match actual network %s'] = 'A várt %s hálózat nem egyezik a tényleges %s hálózattal';
$a->strings['This seems to be a relay account. They can\'t be followed by users.'] = 'Úgy tűnik, hogy ez egy továbbító fiók. Ezeket nem követhetik a felhasználók.';
$a->strings['The profile address specified does not provide adequate information.'] = 'A megadott profilcím nem biztosít elegendő információt.';
$a->strings['No compatible communication protocols or feeds were discovered.'] = 'Nem lettek megfelelő kommunikációs protokollok vagy hírforrások felfedezve.';
$a->strings['An author or name was not found.'] = 'Egy szerző vagy név nem található.';
$a->strings['No browser URL could be matched to this address.'] = 'Egyetlen böngésző URL-t sem sikerült illeszteni ehhez a címhez.';
$a->strings['Unable to match @-style Identity Address with a known protocol or email contact.'] = 'Nem lehet illeszteni @-stílusú személyazonosság-címet egy ismert protokollal vagy e-mailes partnerrel.';
$a->strings['Use mailto: in front of address to force email check.'] = 'Használja a mailto: előtagot a cím előtt az e-mail-ellenőrzés kényszerítéséhez.';
$a->strings['Limited profile. This person will be unable to receive direct/personal notifications from you.'] = 'Korlátozott profil. Ez a személy nem lesz képes közvetlen vagy személyes értesítéseket fogadni Öntől.';
$a->strings['Unable to retrieve contact information.'] = 'Nem lehet lekérni a partner információit.';
$a->strings['l F d, Y \@ g:i A \G\M\TP (e)'] = 'Y. F j., l, H:i \G\M\TP (e)';
$a->strings['Starts:'] = 'Kezdődik:';
$a->strings['Finishes:'] = 'Befejeződik:';
$a->strings['all-day'] = 'egész nap';
$a->strings['Sept'] = 'Szept';
$a->strings['today'] = 'ma';
$a->strings['month'] = 'hónap';
$a->strings['week'] = 'hét';
$a->strings['day'] = 'nap';
$a->strings['No events to display'] = 'Nincsenek megjelenítendő események';
$a->strings['Access to this profile has been restricted.'] = 'A profilhoz való hozzáférés korlátozva lett.';
$a->strings['Event not found.'] = 'Az esemény nem található.';
$a->strings['l, F j'] = 'F j., l';
$a->strings['Edit event'] = 'Esemény szerkesztése';
$a->strings['Duplicate event'] = 'Esemény kettőzése';
$a->strings['Delete event'] = 'Esemény törlése';
$a->strings['l F d, Y \@ g:i A'] = 'Y. F j., l, H:i';
$a->strings['D g:i A'] = 'D H:i';
$a->strings['g:i A'] = 'H:i';
$a->strings['Show map'] = 'Térkép megjelenítése';
$a->strings['Hide map'] = 'Térkép elrejtése';
$a->strings['%s\'s birthday'] = '%s születésnapja';
$a->strings['Happy Birthday %s'] = 'Boldog születésnapot, %s';
$a->strings['%s (%s - %s): %s'] = '%s (%s – %s): %s';
$a->strings['%s (%s): %s'] = '%s (%s): %s';
$a->strings['Detected languages in this post:
%s'] = 'A bejegyzésben felismert nyelvek:
%s';
$a->strings['activity'] = 'tevékenység';
$a->strings['comment'] = 'hozzászólás';
$a->strings['post'] = 'bejegyzés';
$a->strings['%s is blocked'] = '%s tiltva van';
$a->strings['%s is ignored'] = '%s mellőzve van';
$a->strings['Content from %s is collapsed'] = 'A(z) %s helyről származó tartalom összecsukva';
$a->strings['Sensitive content'] = 'Érzékeny tartalom';
$a->strings['bytes'] = 'bájt';
$a->strings['%2$s (%3$d%%, %1$d vote)'] = [
	0 => '%2$s (%3$d%%, %1$d szavazat)',
	1 => '%2$s (%3$d%%, %1$d szavazat)',
];
$a->strings['%2$s (%1$d vote)'] = [
	0 => '%2$s (%1$d szavazat)',
	1 => '%2$s (%1$d szavazat)',
];
$a->strings['%d voter. Poll end: %s'] = [
	0 => '%d szavazó. A szavazás vége: %s',
	1 => '%d szavazó. A szavazás vége: %s',
];
$a->strings['%d voter.'] = [
	0 => '%d szavazó.',
	1 => '%d szavazó.',
];
$a->strings['Poll end: %s'] = 'Szavazás vége: %s';
$a->strings['View on separate page'] = 'Megtekintés külön oldalon';
$a->strings['[no subject]'] = '[nincs tárgy]';
$a->strings['Wall Photos'] = 'Falfényképek';
$a->strings['Edit profile'] = 'Profil szerkesztése';
$a->strings['Change profile photo'] = 'Profilfénykép megváltoztatása';
$a->strings['Homepage:'] = 'Honlap:';
$a->strings['About:'] = 'Névjegy:';
$a->strings['Atom feed'] = 'Atom hírforrás';
$a->strings['This website has been verified to belong to the same person.'] = 'Ez a weboldal ellenőrizve lett, hogy ugyanahhoz a személyhez tartozik.';
$a->strings['F d'] = 'F j.';
$a->strings['[today]'] = '[ma]';
$a->strings['Birthday Reminders'] = 'Születésnapi emlékeztetők';
$a->strings['Birthdays this week:'] = 'Születésnapok ezen a héten:';
$a->strings['g A l F d'] = 'F j., l, H';
$a->strings['[No description]'] = '[Nincs leírás]';
$a->strings['Event Reminders'] = 'Eseményemlékeztetők';
$a->strings['Upcoming events the next 7 days:'] = 'Közelgő események a következő 7 napon:';
$a->strings['Hometown:'] = 'Szülőváros:';
$a->strings['Marital Status:'] = 'Családi állapot:';
$a->strings['With:'] = 'Ezzel:';
$a->strings['Since:'] = 'Ekkortól:';
$a->strings['Sexual Preference:'] = 'Szexuális irányultság:';
$a->strings['Political Views:'] = 'Politikai nézetek:';
$a->strings['Religious Views:'] = 'Vallási néztek:';
$a->strings['Likes:'] = 'Kedvelések:';
$a->strings['Dislikes:'] = 'Nem kedvelések:';
$a->strings['Title/Description:'] = 'Cím vagy leírás:';
$a->strings['Summary'] = 'Összefoglaló';
$a->strings['Musical interests'] = 'Zenei érdeklődések';
$a->strings['Books, literature'] = 'Könyvek, irodalom';
$a->strings['Television'] = 'Televízió';
$a->strings['Film/dance/culture/entertainment'] = 'Film, tánc, kultúra, szórakozás';
$a->strings['Hobbies/Interests'] = 'Hobbik, érdeklődések';
$a->strings['Love/romance'] = 'Szerelem, romantika';
$a->strings['Work/employment'] = 'Munka, foglalkoztatás';
$a->strings['School/education'] = 'Iskola, oktatás';
$a->strings['Contact information and Social Networks'] = 'Partnerinformációk és közösségi hálózatok';
$a->strings['Responsible account: %s'] = 'Felelős fiók: %s';
$a->strings['SERIOUS ERROR: Generation of security keys failed.'] = 'SÚLYOS HIBA: a biztonsági kulcsok előállítása nem sikerült.';
$a->strings['Login failed'] = 'Bejelentkezés sikertelen';
$a->strings['Not enough information to authenticate'] = 'Nincs elegendő információ a hitelesítéshez';
$a->strings['Password can\'t be empty'] = 'A jelszó nem lehet üres';
$a->strings['Empty passwords are not allowed.'] = 'Az üres jelszavak nem megengedettek.';
$a->strings['The new password has been exposed in a public data dump, please choose another.'] = 'Az új jelszót közzétették egy nyilvános adattárban. Válasszon egy másikat.';
$a->strings['The password length is limited to 72 characters.'] = 'A jelszó hossza 72 karakterre van korlátozva.';
$a->strings['The password can\'t contain white spaces nor accentuated letters'] = 'A jelszó nem tartalmazhat üres karaktereket vagy ékezetes betűket';
$a->strings['Passwords do not match. Password unchanged.'] = 'A jelszavak nem egyeznek. A jelszó változatlan maradt.';
$a->strings['An invitation is required.'] = 'Egy meghívás szükséges.';
$a->strings['Invitation could not be verified.'] = 'A meghívást nem sikerült ellenőrizni.';
$a->strings['Invalid OpenID url'] = 'Érvénytelen OpenID URL';
$a->strings['We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.'] = 'Problémába ütköztünk a megadott OpenID-val történő bejelentkezés közben. Ellenőrizze az azonosító helyesírását.';
$a->strings['The error message was:'] = 'A hibaüzenet ez volt:';
$a->strings['Please enter the required information.'] = 'Adja meg a szükséges információkat.';
$a->strings['system.username_min_length (%s) and system.username_max_length (%s) are excluding each other, swapping values.'] = 'A system.username_min_length (%s) és a system.username_max_length (%s) kizárják egymást, értékek felcserélése.';
$a->strings['Username should be at least %s character.'] = [
	0 => 'A felhasználónévnek legalább %s karakternek kell lennie.',
	1 => 'A felhasználónévnek legalább %s karakternek kell lennie.',
];
$a->strings['Username should be at most %s character.'] = [
	0 => 'A felhasználónévnek legfeljebb %s karakternek kell lennie.',
	1 => 'A felhasználónévnek legfeljebb %s karakternek kell lennie.',
];
$a->strings['That doesn\'t appear to be your full (First Last) name.'] = 'Úgy tűnik, hogy ez nem a teljes neve (vezetéknév és keresztnév).';
$a->strings['Your email domain is not among those allowed on this site.'] = 'Az e-mail tartománya nem tartozik azok közé, amelyek megengedettek ezen az oldalon.';
$a->strings['Not a valid email address.'] = 'Nem érvényes e-mail-cím.';
$a->strings['The nickname was blocked from registration by the nodes admin.'] = 'A becenevet a csomópont adminisztrátora tiltotta a regisztrációtól.';
$a->strings['Cannot use that email.'] = 'Nem lehet használni azt az e-mail-címet.';
$a->strings['Your nickname can only contain a-z, 0-9 and _.'] = 'A becenév csak a-z, 0-9 és _ karaktereket tartalmazhat.';
$a->strings['Nickname is already registered. Please choose another.'] = 'A becenév már regisztrálva van. Válasszon egy másikat.';
$a->strings['An error occurred during registration. Please try again.'] = 'Hiba történt a regisztráció során. Próbálja újra.';
$a->strings['An error occurred creating your default profile. Please try again.'] = 'Hiba történt az alapértelmezett profil létrehozásakor. Próbálja újra.';
$a->strings['An error occurred creating your self contact. Please try again.'] = 'Hiba történt a saját partnere létrehozásakor. Próbálja újra.';
$a->strings['Friends'] = 'Ismerősök';
$a->strings['An error occurred creating your default contact circle. Please try again.'] = 'Hiba történt az alapértelmezett partnerkör létrehozásakor. Próbálja újra.';
$a->strings['Profile Photos'] = 'Profilfényképek';
$a->strings['
		Dear %1$s,
			the administrator of %2$s has set up an account for you.'] = '
		Kedves %1$s!
			A(z) %2$s adminisztrátora beállított egy fiókot Önnek.';
$a->strings['
		The login details are as follows:

		Site Location:	%1$s
		Login Name:		%2$s
		Password:		%3$s

		You may change your password from your account "Settings" page after logging
		in.

		Please take a few moments to review the other account settings on that page.

		You may also wish to add some basic information to your default profile
		(on the "Profiles" page) so that other people can easily find you.

		We recommend adding a profile photo, adding some profile "keywords"
		(very useful in making new friends) - and perhaps what country you live in;
		if you do not wish to be more specific than that.

		We fully respect your right to privacy, and none of these items are necessary.
		If you are new and do not know anybody here, they may help
		you to make some new and interesting friends.

		If you ever want to delete your account, you can do so at %1$s/settings/removeme

		Thank you and welcome to %4$s.'] = '
		A bejelentkezés részletei a következők:

		Oldal címe:	%1$s
		Bejelentkezési név:		%2$s
		Jelszó:		%3$s

		A jelszavát a fiókja „Beállítások” oldalán változtathatja meg, miután
		bejelentkezett.

		Szánjon pár percet a többi fiókbeállítás felülvizsgálatára is azon az oldalon.

		Érdemes lehet néhány alapvető információt is hozzáadnia az
		alapértelmezett profiljához (a „Profilok” oldalon), azért hogy más
		emberek egyszerűen megtalálják Önt.

		Azt ajánljuk, hogy adjon hozzá profilfényképet, adjon hozzá néhány
		profil „kulcsszót” (nagyon hasznos új ismerősök kereséséhez),
		valamint talán azt, hogy mely országban él, ha nem szeretne annál
		pontosabbat megadni.

		Teljes mértékben tiszteletben tartjuk az adatvédelemmel kapcsolatos
		jogát, és ezen elemek egyike sem kötelező. Ha még új itt, és senkit
		sem ismer, akkor ezek segíthetnek Önnek néhány új és érdekes
		ismerőst találni.

		Ha bármikor törölni szeretné a fiókját, akkor megteheti azt a
		következő címen: %1$s/settings/removeme

		Köszönjük, és üdvözöljük a(z) %4$s oldalon.';
$a->strings['Registration details for %s'] = 'Regisztrációs részletek ehhez: %s';
$a->strings['
			Dear %1$s,
				Thank you for registering at %2$s. Your account is pending for approval by the administrator.

			Your login details are as follows:

			Site Location:	%3$s
			Login Name:		%4$s
			Password:		%5$s
		'] = '
			Kedves %1$s!
				Köszönjük, hogy regisztrált itt: %2$s. A fiókja adminisztrátori jóváhagyásra vár.

			A bejelentkezés részletei a következők:

			Oldal címe:	%3$s
			Bejelentkezési név:		%4$s
			Jelszó:		%5$s
		';
$a->strings['Registration at %s'] = 'Regisztráció itt: %s';
$a->strings['
				Dear %1$s,
				Thank you for registering at %2$s. Your account has been created.
			'] = '
				Kedves %1$s!
				Köszönjük, hogy regisztrált itt: %2$s. A fiókja létrejött.
			';
$a->strings['
			The login details are as follows:

			Site Location:	%3$s
			Login Name:		%1$s
			Password:		%5$s

			You may change your password from your account "Settings" page after logging
			in.

			Please take a few moments to review the other account settings on that page.

			You may also wish to add some basic information to your default profile
			(on the "Profiles" page) so that other people can easily find you.

			We recommend adding a profile photo, adding some profile "keywords" (very useful
			in making new friends) - and perhaps what country you live in; if you do not wish
			to be more specific than that.

			We fully respect your right to privacy, and none of these items are necessary.
			If you are new and do not know anybody here, they may help
			you to make some new and interesting friends.

			If you ever want to delete your account, you can do so at %3$s/settings/removeme

			Thank you and welcome to %2$s.'] = '
			A bejelentkezés részletei a következők:

			Oldal címe:	%3$s
			Bejelentkezési név:		%1$s
			Jelszó:		%5$s

			A jelszavát a fiókja „Beállítások” oldalán változtathatja meg, miután
			bejelentkezett.

			Szánjon pár percet a többi fiókbeállítás felülvizsgálatára is azon az oldalon.

			Érdemes lehet néhány alapvető információt is hozzáadnia az
			alapértelmezett profiljához (a „Profilok” oldalon), azért hogy más
			emberek egyszerűen megtalálják Önt.

			Azt ajánljuk, hogy adjon hozzá profilfényképet, adjon hozzá néhány
			profil „kulcsszót” (nagyon hasznos új ismerősök kereséséhez),
			valamint talán azt, hogy mely országban él, ha nem szeretne annál
			pontosabbat megadni.

			Teljes mértékben tiszteletben tartjuk az adatvédelemmel kapcsolatos
			jogát, és ezen elemek egyike sem kötelező. Ha még új itt, és senkit
			sem ismer, akkor ezek segíthetnek Önnek néhány új és érdekes
			ismerőst találni.

			Ha bármikor törölni szeretné a fiókját, akkor megteheti azt a
			következő címen: %3$s/settings/removeme

			Köszönjük, és üdvözöljük a(z) %2$s oldalon.';
$a->strings['User with delegates can\'t be removed, please remove delegate users first'] = 'A meghatalmazásokkal rendelkező felhasználót nem lehet eltávolítani, először távolítsa el a meghatalmazott felhasználókat';
$a->strings['Addon not found.'] = 'A bővítmény nem található.';
$a->strings['Addon %s disabled.'] = 'A(z) „%s” bővítmény letiltva.';
$a->strings['Addon %s enabled.'] = 'A(z) „%s” bővítmény engedélyezve.';
$a->strings['Disable'] = 'Letiltás';
$a->strings['Enable'] = 'Engedélyezés';
$a->strings['Administration'] = 'Adminisztráció';
$a->strings['Addons'] = 'Bővítmények';
$a->strings['Toggle'] = 'Átváltás';
$a->strings['Author: '] = 'Szerző: ';
$a->strings['Maintainer: '] = 'Karbantartó: ';
$a->strings['Addons reloaded'] = 'Bővítmények újratöltve';
$a->strings['Addon %s failed to install.'] = 'A(z) „%s” bővítmény telepítése sikertelen.';
$a->strings['Save Settings'] = 'Beállítások mentése';
$a->strings['Reload active addons'] = 'Bekapcsolt bővítmények újratöltése';
$a->strings['There are currently no addons available on your node. You can find the official addon repository at %1$s.'] = 'Jelenleg nem érhetők el bővítmények az Ön csomópontján. A hivatalos bővítménytároló megtalálható a %1$s oldalon.';
$a->strings['Update has been marked successful'] = 'A frissítés sikeresen meg lett jelölve';
$a->strings['Database structure update %s was successfully applied.'] = 'A(z) %s adatbázisszerkezet-frissítés sikeresen alkalmazva.';
$a->strings['Executing of database structure update %s failed with error: %s'] = 'A(z) %s adatbázisszerkezet-frissítés végrehajtása meghiúsult ezzel a hibával: %s';
$a->strings['Executing %s failed with error: %s'] = 'A(z) %s végrehajtása meghiúsult ezzel a hibával: %s';
$a->strings['Update %s was successfully applied.'] = 'A(z) %s frissítés sikeresen alkalmazva.';
$a->strings['Update %s did not return a status. Unknown if it succeeded.'] = 'A(z) %s frissítés nem adott vissza állapotot. Nem tudni, hogy sikerült-e.';
$a->strings['There was no additional update function %s that needed to be called.'] = 'Nem volt további frissítési funkció, %s amelyet meg kellett hívni.';
$a->strings['No failed updates.'] = 'Nincsenek sikertelen frissítések.';
$a->strings['Check database structure'] = 'Adatbázis-szerkezet ellenőrzése';
$a->strings['Failed Updates'] = 'Sikertelen frissítések';
$a->strings['This does not include updates prior to 1139, which did not return a status.'] = 'Ez nem tartalmazza az 1139 előtti frissítéseket, amelyek nem adtak vissza állapotot.';
$a->strings['Mark success (if update was manually applied)'] = 'Megjelölés sikeresként (ha a frissítés kézzel lett alkalmazva)';
$a->strings['Attempt to execute this update step automatically'] = 'Próbálja meg automatikusan végrehajtani ezt a frissítési lépést';
$a->strings['No'] = 'Nem';
$a->strings['Yes'] = 'Igen';
$a->strings['Locked'] = 'Zárolva';
$a->strings['Manage Additional Features'] = 'További funkciók kezelése';
$a->strings['Other'] = 'Egyéb';
$a->strings['unknown'] = 'ismeretlen';
$a->strings['%2$s total system'] = [
	0 => '%2$s rendszer összesen',
	1 => '%2$s rendszer összesen',
];
$a->strings['%2$s active user last month'] = [
	0 => '%2$s aktív felhasználó az elmúlt hónapban',
	1 => '%2$s aktív felhasználó az elmúlt hónapban',
];
$a->strings['%2$s active user last six months'] = [
	0 => '%2$s aktív felhasználó az elmúlt hat hónapban',
	1 => '%2$s aktív felhasználó az elmúlt hat hónapban',
];
$a->strings['%2$s registered user'] = [
	0 => '%2$s regisztrált felhasználó',
	1 => '%2$s regisztrált felhasználó',
];
$a->strings['%2$s locally created post or comment'] = [
	0 => '%2$s helyileg létrehozott bejegyzés vagy hozzászólás',
	1 => '%2$s helyileg létrehozott bejegyzés és hozzászólás',
];
$a->strings['%2$s post per user'] = [
	0 => '%2$s bejegyzés felhasználónként',
	1 => '%2$s bejegyzés felhasználónként',
];
$a->strings['%2$s user per system'] = [
	0 => '%2$s felhasználó rendszerenként',
	1 => '%2$s felhasználó rendszerenként',
];
$a->strings['This page offers you some numbers to the known part of the federated social network your Friendica node is part of. These numbers are not complete but only reflect the part of the network your node is aware of.'] = 'Ez az oldal néhány számadatot nyújt a föderált közösségi hálózat azon ismert részéhez, amelynek része az Ön Friendica csomópontja. Ezek a számok nem teljesek, hanem csak a hálózat azon részét tükrözik, amelyről a csomópontja tud.';
$a->strings['Federation Statistics'] = 'Föderációs statisztikák';
$a->strings['Currently this node is aware of %2$s node (%3$s active users last month, %4$s active users last six months, %5$s registered users in total) from the following platforms:'] = [
	0 => 'Jelenleg erről a csomópontról %2$s csomópontnak van tudomása (%3$s aktív felhasználóval az elmúlt hónapban, %4$s aktív felhasználóval az elmúlt hat hónapban, összesen %5$s regisztrált felhasználóval) a következő platformokról:',
	1 => 'Jelenleg erről a csomópontról %2$s csomópontnak van tudomása (%3$s aktív felhasználóval az elmúlt hónapban, %4$s aktív felhasználóval az elmúlt hat hónapban, összesen %5$s regisztrált felhasználóval) a következő platformokról:',
];
$a->strings['The logfile \'%s\' is not writable. No logging possible'] = 'A(z) „%s” naplófájl nem írható. A naplózás nem lehetséges.';
$a->strings['PHP log currently enabled.'] = 'A PHP-naplózás jelenleg engedélyezve van.';
$a->strings['PHP log currently disabled.'] = 'A PHP-naplózás jelenleg le van tiltva.';
$a->strings['Logs'] = 'Naplók';
$a->strings['Clear'] = 'Törlés';
$a->strings['Enable Debugging'] = 'Hibakeresés engedélyezése';
$a->strings['<strong>Read-only</strong> because it is set by an environment variable'] = '<strong>Csak olvasható</strong>, mert egy környezeti változó állítja be';
$a->strings['Log file'] = 'Naplófájl';
$a->strings['Must be writable by web server. Relative to your Friendica top-level directory.'] = 'Írhatónak kell lennie a webkiszolgáló által. Relatívan kell megadni a Friendica felső szintű könyvtárához képest.';
$a->strings['Log level'] = 'Naplózási szint';
$a->strings['PHP logging'] = 'PHP-naplózás';
$a->strings['To temporarily enable logging of PHP errors and warnings you can prepend the following to the index.php file of your installation. The filename set in the \'error_log\' line is relative to the friendica top-level directory and must be writeable by the web server. The option \'1\' for \'log_errors\' and \'display_errors\' is to enable these options, set to \'0\' to disable them.'] = 'A PHP hibák és figyelmeztetések naplózásának átmeneti engedélyezéséhez beszúrhatja a következőket a telepítése index.php fájljának elejére. Az „error_log” sorban beállított fájlnév relatív a Friendica felső szintű könyvtárához képest, és írhatónak kell lennie a webkiszolgáló által. A „log_errors” és a „display_errors” beállítások „1” értéke a beállítások engedélyezéséhez kell. Állítsa „0” értékre a letiltásukhoz.';
$a->strings['Error trying to open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s exist and is readable.'] = 'Hiba a(z) <strong>%1$s</strong> naplófájl megnyitási kísérlete során.<br/>Ellenőrizze, hogy a(z) „%1$s” fájl létezik-e és olvasható-e.';
$a->strings['Couldn\'t open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s is readable.'] = 'Nem sikerült megnyitni a(z) <strong>%1$s</strong> naplófájlt.<br/>Ellenőrizze, hogy a(z) „%1$s” fájl olvasható-e.';
$a->strings['View Logs'] = 'Naplók megtekintése';
$a->strings['Search in logs'] = 'Keresés a naplókban';
$a->strings['Show all'] = 'Összes megjelenítése';
$a->strings['Date'] = 'Dátum';
$a->strings['Level'] = 'Szint';
$a->strings['Context'] = 'Környezet';
$a->strings['ALL'] = 'Összes';
$a->strings['View details'] = 'Részletek megtekintése';
$a->strings['Click to view details'] = 'Kattintson a részletek megtekintéséhez';
$a->strings['Event details'] = 'Esemény részletei';
$a->strings['Data'] = 'Adatok';
$a->strings['Source'] = 'Forrás';
$a->strings['File'] = 'Fájl';
$a->strings['Line'] = 'Sor';
$a->strings['Function'] = 'Függvény';
$a->strings['UID'] = 'UID';
$a->strings['Process ID'] = 'Folyamatazonosító';
$a->strings['Inspect Deferred Worker Queue'] = 'Elhalasztott feldolgozó várakozási sorának vizsgálata';
$a->strings['This page lists the deferred worker jobs. This are jobs that couldn\'t be executed at the first time.'] = 'Ez az oldal az elhalasztott feldolgozó feladatokat sorolja fel. Ezek azok a feladatok, amelyeket nem sikerült végrehajtani első alkalommal.';
$a->strings['Inspect Worker Queue'] = 'Feldolgozó várakozási sorának vizsgálata';
$a->strings['This page lists the currently queued worker jobs. These jobs are handled by the worker cronjob you\'ve set up during install.'] = 'Ez az oldal a jelenleg sorba állított feldolgozó feladatokat sorolja fel. Ezeket a feladatokat a feldolgozó cron-feladata kezeli, amelyet a telepítés során állított be.';
$a->strings['ID'] = 'Azonosító';
$a->strings['Command'] = 'Parancs';
$a->strings['Job Parameters'] = 'Feladat paraméterei';
$a->strings['Created'] = 'Létrehozva';
$a->strings['Next Try'] = 'Következő próba';
$a->strings['Priority'] = 'Prioritás';
$a->strings['%s is no valid input for maximum media size'] = 'A(z) %s nem érvényes bemenet a legnagyobb médiamérethez';
$a->strings['%s is no valid input for maximum image size'] = 'A(z) %s nem érvényes bemenet a legnagyobb képmérethez';
$a->strings['No special theme for mobile devices'] = 'Nincs különleges téma a mobil eszközökhöz';
$a->strings['%s - (Experimental)'] = '%s – (kísérleti)';
$a->strings['No community page'] = 'Nincs közösségi oldal';
$a->strings['No community page for visitors'] = 'Nincs közösségi oldal a látogatóknak';
$a->strings['Public postings from users of this site'] = 'Nyilvános beküldések ezen oldal felhasználóitól';
$a->strings['Public postings from the federated network'] = 'Nyilvános beküldések a föderált hálózatból';
$a->strings['Public postings from local users and the federated network'] = 'Nyilvános beküldések a helyi felhasználóktól és a föderált hálózatból';
$a->strings['Multi user instance'] = 'Többfelhasználós példány';
$a->strings['Closed'] = 'Lezárva';
$a->strings['Requires approval'] = 'Jóváhagyást igényel';
$a->strings['Open'] = 'Nyitott';
$a->strings['Don\'t check'] = 'Ne ellenőrizze';
$a->strings['check the stable version'] = 'a stabil verzió ellenőrzése';
$a->strings['check the development version'] = 'a fejlesztői verzió ellenőrzése';
$a->strings['none'] = 'nincs';
$a->strings['Local contacts'] = 'Helyi partnerek';
$a->strings['Interactors'] = 'Interaktorok';
$a->strings['Site'] = 'Oldal';
$a->strings['General Information'] = 'Általános információk';
$a->strings['Republish users to directory'] = 'Felhasználók újra közzé tétele a könyvtárba';
$a->strings['Registration'] = 'Regisztráció';
$a->strings['File upload'] = 'Fájlfeltöltés';
$a->strings['Policies'] = 'Irányelvek';
$a->strings['Advanced'] = 'Speciális';
$a->strings['Auto Discovered Contact Directory'] = 'Automatikusan felfedezett partnerkönyvtár';
$a->strings['Performance'] = 'Teljesítmény';
$a->strings['Worker'] = 'Feldolgozó';
$a->strings['Message Relay'] = 'Üzenettovábbítás';
$a->strings['Use the command "console relay" in the command line to add or remove relays.'] = 'Használja a „console relay” parancsot a parancssorban a továbbítók hozzáadásához vagy eltávolításához.';
$a->strings['The system is not subscribed to any relays at the moment.'] = 'A rendszer jelenleg nincs feliratkozva semmilyen továbbítóra sem.';
$a->strings['The system is currently subscribed to the following relays:'] = 'A rendszer jelenleg a következő továbbítókra van feliratkozva:';
$a->strings['Relocate Node'] = 'Csomópont áthelyezése';
$a->strings['Relocating your node enables you to change the DNS domain of this node and keep all the existing users and posts. This process takes a while and can only be started from the relocate console command like this:'] = 'A csomópont áthelyezése lehetővé teszi a csomópont DNS-tartományának megváltoztatását, valamint az összes meglévő felhasználó és bejegyzés megtartását. Ez a folyamat eltart egy ideig, és csak az áthelyezés konzolparanccsal indítható el az alábbi módon:';
$a->strings['(Friendica directory)# bin/console relocate https://newdomain.com'] = '(Friendica könyvtár)# bin/console relocate https://uj-tartomany.hu';
$a->strings['Site name'] = 'Oldal neve';
$a->strings['Sender Email'] = 'Küldő e-mail-címe';
$a->strings['The email address your server shall use to send notification emails from.'] = 'Az az e-mail-cím, amelyet a kiszolgáló használhat az értesítési e-mailek kiküldéséhez.';
$a->strings['Name of the system actor'] = 'A rendszer szereplőjének neve';
$a->strings['Name of the internal system account that is used to perform ActivityPub requests. This must be an unused username. If set, this can\'t be changed again.'] = 'A belső rendszerfiók neve, amely az ActivityPub kérések végrehajtásához lesz használva. Ennek egy nem használt felhasználónévnek kell lennie. Ha be van állítva, akkor ez nem változtatható meg újra.';
$a->strings['Banner/Logo'] = 'Reklámcsík vagy logó';
$a->strings['Email Banner/Logo'] = 'E-mail reklámcsík vagy logó';
$a->strings['Shortcut icon'] = 'Böngészőikon';
$a->strings['Link to an icon that will be used for browsers.'] = 'Hivatkozás egy ikonra, amely a böngészőknél lesz használva.';
$a->strings['Touch icon'] = 'Érintő ikon';
$a->strings['Link to an icon that will be used for tablets and mobiles.'] = 'Hivatkozás egy ikonra, amely táblagépeknél és mobiltelefonoknál lesz használva.';
$a->strings['Additional Info'] = 'További információk';
$a->strings['For public servers: you can add additional information here that will be listed at %s/servers.'] = 'Nyilvános kiszolgálóknál: itt adhat meg további információkat, amelyek a %s/servers oldalon lesznek felsorolva.';
$a->strings['System language'] = 'Rendszer nyelve';
$a->strings['System theme'] = 'Rendszer témája';
$a->strings['Default system theme - may be over-ridden by user profiles - <a href="%s" id="cnftheme">Change default theme settings</a>'] = 'Alapértelmezett rendszertéma – a felhasználói profilok felülbírálhatják – <a href="%s" id="cnftheme">alapértelmezett témabeállítások megváltoztatása</a>.';
$a->strings['Mobile system theme'] = 'Mobilrendszer témája';
$a->strings['Theme for mobile devices'] = 'Téma a mobil eszközökhöz.';
$a->strings['Force SSL'] = 'SSL kényszerítése';
$a->strings['Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.'] = 'Az összes nem SSL kérés SSL-re kényszerítése – Figyelem: néhány rendszeren végtelen hurkokat eredményezhet.';
$a->strings['Show help entry from navigation menu'] = 'Súgó bejegyzés megjelenítése a navigációs menüből';
$a->strings['Displays the menu entry for the Help pages from the navigation menu. It is always accessible by calling /help directly.'] = 'Megjeleníti a súgóoldalak menübejegyzését a navigációs menüből. Ez mindig elérhető a „/help” közvetlen meghívásával.';
$a->strings['Single user instance'] = 'Egyfelhasználós példány';
$a->strings['Make this instance multi-user or single-user for the named user'] = 'Többfelhasználóssá vagy a megnevezett felhasználó számára egyfelhasználóssá teszi ezt a rendszert.';
$a->strings['Maximum image size'] = 'Legnagyobb képméret';
$a->strings['Maximum size in bytes of uploaded images. Default is 0, which means no limits. You can put k, m, or g behind the desired value for KiB, MiB, GiB, respectively.
													The value of <code>upload_max_filesize</code> in your <code>PHP.ini</code> needs be set to at least the desired limit.
													Currently <code>upload_max_filesize</code> is set to %s (%s byte)'] = 'A feltöltött képek legnagyobb mérete bájtban. Alapértelmezetten 0, ami azt jelenti, hogy nincs korlátozás. A kívánt érték mögé k, m vagy g értékeket is írhat a KiB, MiB, GiB értékhez, ebben a sorrendben.
													A <code>PHP.ini</code> fájlban lévő <code>upload_max_filesize</code> értékét be kell állítani legalább a kívánt korlátra.
													Jelenleg az <code>upload_max_filesize</code> %s (%s bájt) értékre van állítva.';
$a->strings['Maximum image length'] = 'Legnagyobb képhossz';
$a->strings['Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.'] = 'A feltöltött képek leghosszabb oldalának legnagyobb hossza képpontban. Alapértelmezetten -1, ami azt jelenti, hogy nincs korlát.';
$a->strings['JPEG image quality'] = 'JPEG-képek minősége';
$a->strings['Uploaded JPEGS will be saved at this quality setting [0-100]. Default is 100, which is full quality.'] = 'A feltöltött JPEG-képek ezzel a minőségbeállítással lesznek elmentve [0-100]. Alapértelmezetten 100, ami teljes minőséget jelent.';
$a->strings['Maximum media file size'] = 'Legnagyobb médiafájlméret';
$a->strings['Maximum size in bytes of uploaded media files. Default is 0, which means no limits. You can put k, m, or g behind the desired value for KiB, MiB, GiB, respectively.
													The value of <code>upload_max_filesize</code> in your <code>PHP.ini</code> needs be set to at least the desired limit.
													Currently <code>upload_max_filesize</code> is set to %s (%s byte)'] = 'A feltöltött médiafájlok legnagyobb mérete bájtban. Alapértelmezetten 0, ami azt jelenti, hogy nincs korlátozás. A kívánt érték mögé k, m vagy g értékeket is írhat a KiB, MiB, GiB értékhez, ebben a sorrendben.
													A <code>PHP.ini</code> fájlban lévő <code>upload_max_filesize</code> értékét be kell állítani legalább a kívánt korlátra.
													Jelenleg az <code>upload_max_filesize</code> %s (%s bájt) értékre van állítva.';
$a->strings['Register policy'] = 'Regisztrációs irányelv';
$a->strings['Maximum Users'] = 'Legtöbb felhasználó';
$a->strings['If defined, the register policy is automatically closed when the given number of users is reached and reopens the registry when the number drops below the limit. It only works when the policy is set to open or close, but not when the policy is set to approval.'] = 'Ha meg van adva, akkor a regisztrációs házirend automatikusan lezárja a regisztrációt a megadott számú felhasználó elérésekor, és újra megnyitja a regisztrációt, ha a felhasználók száma a határérték alá csökken. Ez csak akkor működik, ha a házirend nyitottra vagy zártra van beállítva, de nem működik, ha a házirend jóváhagyásra van beállítva.';
$a->strings['Maximum Daily Registrations'] = 'Legtöbb napi regisztráció';
$a->strings['If registration is permitted above, this sets the maximum number of new user registrations to accept per day.  If register is set to closed, this setting has no effect.'] = 'Ha a regisztrációk megengedettek fent, akkor ez állítja be a naponta elfogadandó új felhasználói regisztrációk legnagyobb számát. Ha a regisztráció lezártra van állítva, akkor ennek a beállításnak nincs hatása.';
$a->strings['Register text'] = 'Regisztrációs szöveg';
$a->strings['Will be displayed prominently on the registration page. You can use BBCode here.'] = 'Szembetűnően lesz megjelenítve a regisztrációs oldalon. BBCode formázást is használhat itt.';
$a->strings['Forbidden Nicknames'] = 'Tiltott becenevek';
$a->strings['Comma separated list of nicknames that are forbidden from registration. Preset is a list of role names according RFC 2142.'] = 'A becenevek vesszővel elválasztott listája, amelyek tiltottak a regisztrációnál. Az előbeállítás az RFC 2142 szerinti szerepnevek listája.';
$a->strings['Accounts abandoned after x days'] = 'Fiókok elhagyottak X nap után';
$a->strings['Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.'] = 'Nem fogja pazarolni a rendszer erőforrásait a külső oldalak lekérdezésével az elhagyott fiókoknál. Adjon meg 0 értéket, hogy ne legyen időkorlát.';
$a->strings['Allowed friend domains'] = 'Engedélyezett ismerőstartományok';
$a->strings['Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains'] = 'Azon tartományok vesszővel elválasztott listája, amelyeknek engedélyezett ismeretséget létesíteni ezzel az oldallal. A helyettesítő karakterek is elfogadottak. Ha üresen marad, akkor bármely tartomány megengedett.';
$a->strings['Allowed email domains'] = 'Engedélyezett e-mail-tartományok';
$a->strings['Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains'] = 'Azon tartományok vesszővel elválasztott listája, amelyek engedélyezettek az e-mail-címekben az oldalra történő regisztrációkhoz. A helyettesítő karakterek is elfogadottak. Ha üresen marad, akkor bármely tartomány megengedett.';
$a->strings['Disallowed email domains'] = 'Nem engedélyezett e-mail-tartományok';
$a->strings['Comma separated list of domains which are rejected as email addresses for registrations to this site. Wildcards are accepted.'] = 'Azon tartományok vesszővel elválasztott listája, amelyek visszautasítottak az e-mail-címekben az oldalra történő regisztrációkhoz. A helyettesítő karakterek is elfogadottak.';
$a->strings['No OEmbed rich content'] = 'Nincs OEmbed gazdag tartalom';
$a->strings['Don\'t show the rich content (e.g. embedded PDF), except from the domains listed below.'] = 'Ne jelenítse meg a gazdag tartalmat (például beágyazott PDF), kivéve az alább felsorolt tartományokról.';
$a->strings['Trusted third-party domains'] = 'Megbízható harmadik fél tartományok';
$a->strings['Comma separated list of domains from which content is allowed to be embedded in posts like with OEmbed. All sub-domains of the listed domains are allowed as well.'] = 'Tartományok vesszővel elválasztott listája, amelyekről engedélyezett a tartalom bejegyzésekben való beágyazása, mint például az OEmbed használatával. A felsorolt tartományok összes altartománya is engedélyezve van.';
$a->strings['Block public'] = 'Nyilvános tiltása';
$a->strings['Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.'] = 'Jelölje be az ezen az oldalon lévő összes, egyébként nyilvános személyes oldal nyilvános hozzáférésének tiltásához, hacsak jelenleg nincs bejelentkezve.';
$a->strings['Force publish'] = 'Közzététel kényszerítése';
$a->strings['Check to force all profiles on this site to be listed in the site directory.'] = 'Jelölje be, hogy ezen az oldalon az összes profil kényszerítetten fel legyen sorolva az oldal könyvtárában.';
$a->strings['Enabling this may violate privacy laws like the GDPR'] = 'Ennek engedélyezése megsértheti az adatvédelmi rendeleteket, mint például a GDPR-t.';
$a->strings['Global directory URL'] = 'Globális könyvtár URL';
$a->strings['URL to the global directory. If this is not set, the global directory is completely unavailable to the application.'] = 'Az URL a globális könyvtárhoz. Ha ez nincs beállítva, akkor a globális könyvtár teljesen elérhetetlen lesz az alkalmazásoknak.';
$a->strings['Private posts by default for new users'] = 'Alapértelmezetten személyes bejegyzések az új felhasználóknál';
$a->strings['Set default post permissions for all new members to the default privacy circle rather than public.'] = 'Az összes új tag alapértelmezett bejegyzés-jogosultságainak beállítása az alapértelmezett adatvédelmi körre a nyilvános helyett.';
$a->strings['Don\'t include post content in email notifications'] = 'Ne ágyazza be a bejegyzés tartalmát az e-mailes értesítésekbe';
$a->strings['Don\'t include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.'] = 'Adatvédelmi intézkedésként ne ágyazza be egy bejegyzés, hozzászólás, személyes üzenet stb. tartalmát azokba az e-mailes értesítésekbe, amelyek erről az oldalról kerülnek kiküldésre.';
$a->strings['Disallow public access to addons listed in the apps menu.'] = 'Nyilvános hozzáférés letiltása az alkalmazások menüben felsorolt bővítményekhez';
$a->strings['Checking this box will restrict addons listed in the apps menu to members only.'] = 'A jelölőnégyzet bejelölésével csak a tagok számára fogja korlátozni az alkalmazások menüben felsorolt bővítményeket.';
$a->strings['Don\'t embed private images in posts'] = 'Ne ágyazzon be személyes képeket a bejegyzésekbe';
$a->strings['Don\'t replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.'] = 'Ne cserélje ki a bejegyzésekben lévő helyileg kiszolgált személyes fényképeket a kép beágyazott másolatával. Ez azt jelenti, hogy a személyes fényképeket tartalmazó bejegyzéseket fogadó partnereknek hitelesíteniük kell magukat és be kell tölteniük minden egyes képet, ami eltarthat egy ideig.';
$a->strings['Explicit Content'] = 'Felnőtteknek szánt tartalom';
$a->strings['Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.'] = 'Állítsa be ezt annak közléséhez, hogy a csomópontját főként felnőtteknek szóló tartalomhoz használják, ami lehet, hogy nem alkalmas kiskorúak számára. Ez az információ közzé lesz téve a csomópont információiban, és használhatja például a globális könyvtár is, hogy kiszűrje a csomópontját a csatlakozáshoz felajánlott csomópontok listájából. Ezenkívül egy megjegyzés is meg lesz jelenítve ezzel kapcsolatban a felhasználó regisztrációs oldalán.';
$a->strings['Only local search'] = 'Csak helyi keresés';
$a->strings['Blocks search for users who are not logged in to prevent crawlers from blocking your system.'] = 'Letiltja a keresést a nem bejelentkezett felhasználók számára, így megakadályozza a keresőmotoroknak, hogy letiltsák a rendszerét.';
$a->strings['Blocked tags for trending tags'] = 'Letiltott címkék a népszerű címkéknél';
$a->strings['Comma separated list of hashtags that shouldn\'t be displayed in the trending tags.'] = 'Kettős keresztes címkék vesszővel elválasztott listája, amelyeket nem szabad megjeleníteni a népszerű címkékben.';
$a->strings['Cache contact avatars'] = 'Partnerprofilképek gyorsítótárazása';
$a->strings['Locally store the avatar pictures of the contacts. This uses a lot of storage space but it increases the performance.'] = 'A partnerek profilképeinek helyi tárolása. Ez nagyon sok tárhelyet használ, de növeli a teljesítményt.';
$a->strings['Allow Users to set remote_self'] = 'Távoli önmaguk beállításának engedélyezése a felhasználóknak';
$a->strings['With checking this, every user is allowed to mark every contact as a remote_self in the repair contact dialog. Setting this flag on a contact causes mirroring every posting of that contact in the users stream.'] = 'Ennek bejelölésével minden egyes felhasználó számára engedélyezett, hogy az egyes partnereket távoli önmagukként jelöljék meg a partner javítása párbeszédablakban. Ezen jelző beállítása egy partnernél a tartalom minden egyes beküldésének tükrözését okozza a felhasználók adatfolyamában.';
$a->strings['Allow Users to set up relay channels'] = 'Továbbító csatornák beállításának engedélyezése a felhasználóknak';
$a->strings['If enabled, it is possible to create relay users that are used to reshare content based on user defined channels.'] = 'Ha engedélyezve van, akkor lehetőség van olyan továbbító felhasználók létrehozására, akik a felhasználó által meghatározott csatornákon alapuló tartalmak újbóli megosztására használhatók.';
$a->strings['Adjust the feed poll frequency'] = 'A hírforrás lekérdezési gyakoriságának beállítása';
$a->strings['Automatically detect and set the best feed poll frequency.'] = 'A legjobb hírforrás-lekérdezési gyakoriság automatikus felismerése és beállítása.';
$a->strings['Minimum poll interval'] = 'Legkisebb lekérdezési időköz';
$a->strings['Minimal distance in minutes between two polls for mail and feed contacts. Reasonable values are between 1 and 59.'] = 'Két lekérdezés közötti legkisebb időbeli távolság percben a levél- és hírforráspartnereknél. Az észszerű értékek 1 és 59 között vannak.';
$a->strings['Enable multiple registrations'] = 'Többszörös regisztrációk engedélyezése';
$a->strings['Enable users to register additional accounts for use as pages.'] = 'Lehetővé teszi a felhasználóknak, hogy további fiókokat regisztráljanak oldalakként történő használathoz.';
$a->strings['Enable OpenID'] = 'OpenID engedélyezése';
$a->strings['Enable OpenID support for registration and logins.'] = 'Az OpenID támogatás engedélyezése a regisztrációnál és a bejelentkezéseknél.';
$a->strings['Enable full name check'] = 'Teljes név ellenőrzésének engedélyezése';
$a->strings['Prevents users from registering with a display name with fewer than two parts separated by spaces.'] = 'Megakadályozza a felhasználókat abban, hogy olyan megjelenített névvel regisztráljanak, amelyben kevesebb mint két, szóközzel elválasztott rész van.';
$a->strings['Email administrators on new registration'] = 'E-mail küldése az adminisztrátoroknak új regisztrációkor';
$a->strings['If enabled and the system is set to an open registration, an email for each new registration is sent to the administrators.'] = 'Ha engedélyezve van, és a rendszer nyitott regisztrációhoz van beállítva, akkor minden új regisztrációról e-mail lesz küldve az adminisztrátoroknak.';
$a->strings['Community pages for visitors'] = 'Közösségi oldalak a látogatók számára';
$a->strings['Which community pages should be available for visitors. Local users always see both pages.'] = 'Mely közösségi oldalaknak kell elérhetőnek lenniük a látogatók számára. A helyi felhasználók mindig mindkét oldalt látják.';
$a->strings['Posts per user on community page'] = 'Felhasználónkénti bejegyzések a közösségi oldalon';
$a->strings['The maximum number of posts per user on the local community page. This is useful, when a single user floods the local community page.'] = 'A felhasználónkénti bejegyzések legnagyobb száma a helyi közösségi oldalon. Ez akkor hasznos, ha egy bizonyos felhasználó elárasztja a helyi közösségi oldalt.';
$a->strings['Posts per server on community page'] = 'Kiszolgálónkénti bejegyzések a közösségi oldalon';
$a->strings['The maximum number of posts per server on the global community page. This is useful, when posts from a single server flood the global community page.'] = 'A kiszolgálónkénti bejegyzések legnagyobb száma a globális közösségi oldalon. Ez akkor hasznos, ha egy bizonyos kiszolgálótól származó bejegyzések elárasztják a globális közösségi oldalt.';
$a->strings['Enable Mail support'] = 'Levelezési támogatás engedélyezése';
$a->strings['Enable built-in mail support to poll IMAP folders and to reply via mail.'] = 'A beépített levelezési támogatás engedélyezése az IMAP-mappák lekérdezéséhez és az e-mailben történő válaszhoz.';
$a->strings['Mail support can\'t be enabled because the PHP IMAP module is not installed.'] = 'A levelezési támogatást nem lehet engedélyezni, mert a PHP IMAP-modulja nincs telepítve.';
$a->strings['Diaspora support can\'t be enabled because Friendica was installed into a sub directory.'] = 'A Diaspora támogatást nem lehet engedélyezni, mert a Friendica egy alkönyvtárba lett telepítve.';
$a->strings['Enable Diaspora support'] = 'Diaspora támogatás engedélyezése';
$a->strings['Enable built-in Diaspora network compatibility for communicating with diaspora servers.'] = 'A beépített Diaspora hálózati kompatibilitás engedélyezése a Diaspora kiszolgálókkal való kommunikációhoz.';
$a->strings['Verify SSL'] = 'SSL ellenőrzése';
$a->strings['If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.'] = 'Ha szeretné, bekapcsolhatja a szigorú tanúsítvány-ellenőrzést. Ezt azt jelenti, hogy nem tud kapcsolódni (egyáltalán) az önaláírt SSL-t használó oldalakhoz.';
$a->strings['Proxy user'] = 'Proxy felhasználó';
$a->strings['User name for the proxy server.'] = 'Felhasználónév a proxy-kiszolgálóhoz.';
$a->strings['Proxy URL'] = 'Proxy URL';
$a->strings['If you want to use a proxy server that Friendica should use to connect to the network, put the URL of the proxy here.'] = 'Ha olyan proxy-kiszolgálót szeretne használni, amelyet a Friendicának a hálózathoz való kapcsolódáshoz használnia kell, akkor itt adja meg a proxy URL-jét.';
$a->strings['Network timeout'] = 'Hálózati időkorlát';
$a->strings['Value is in seconds. Set to 0 for unlimited (not recommended).'] = 'Az érték másodpercben van. Állítsa 0-ra a korlátlan időhöz (nem ajánlott).';
$a->strings['Maximum Load Average'] = 'Legnagyobb terhelésátlag';
$a->strings['Maximum system load before delivery and poll processes are deferred - default %d.'] = 'A legnagyobb rendszerterhelés, mielőtt a kézbesítési és lekérdezési folyamatok elhalasztásra kerülnek. Alapértelmezetten %d.';
$a->strings['Minimal Memory'] = 'Legkevesebb memória';
$a->strings['Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).'] = 'A legkevesebb szabad memória MB-ban a feldolgozónál. Hozzáférést igényel a /proc/meminfo fájlhoz. Alapértelmezetten 0 (kikapcsolva).';
$a->strings['Periodically optimize tables'] = 'Táblák időszakos optimalizálása';
$a->strings['Periodically optimize tables like the cache and the workerqueue'] = 'A táblák időszakos optimalizálása, mint például a gyorsítótár és a feldolgozó várakozási sorának táblái.';
$a->strings['Discover followers/followings from contacts'] = 'Követők vagy követések felfedezése a partnerekből';
$a->strings['If enabled, contacts are checked for their followers and following contacts.'] = 'Ha engedélyezve van, akkor a partnerek ellenőrizve lesznek a követő és követett partnereik számára.';
$a->strings['None - deactivated'] = 'Nincs: ki van kapcsolva.';
$a->strings['Local contacts - contacts of our local contacts are discovered for their followers/followings.'] = 'Helyi partnerek: a helyi partnereink partnerei lesznek felfedezve a követőik vagy követésiek számára.';
$a->strings['Interactors - contacts of our local contacts and contacts who interacted on locally visible postings are discovered for their followers/followings.'] = 'Interaktorok: a helyi partnereink partnerei és a helyileg látható beküldésekkel kapcsolatba került partnerek lesznek felfedezve a követőik vagy követésiek számára.';
$a->strings['Only update contacts/servers with local data'] = 'Csak helyi adatokkal rendelkező partnerek vagy kiszolgálók frissítése';
$a->strings['If enabled, the system will only look for changes in contacts and servers that engaged on this system by either being in a contact list of a user or when posts or comments exists from the contact on this system.'] = 'Ha engedélyezve van, akkor a rendszer csak olyan partnerek és kiszolgálók változásait keresi, amelyek részt vesznek ezen a rendszeren, azáltal hogy vagy egy felhasználó partnerlistáján szerepelnek, vagy ha a partnertől származó bejegyzések vagy hozzászólások léteznek ezen a rendszeren.';
$a->strings['Synchronize the contacts with the directory server'] = 'A partnerek szinkronizálása a könyvtárkiszolgálóval';
$a->strings['if enabled, the system will check periodically for new contacts on the defined directory server.'] = 'Ha engedélyezve van, akkor a rendszer időszakosan ellenőrizni fogja az új partnereket a meghatározott könyvtárkiszolgálón.';
$a->strings['Discover contacts from other servers'] = 'Partnerek felfedezése más kiszolgálókról';
$a->strings['Periodically query other servers for contacts and servers that they know of. The system queries Friendica, Mastodon and Hubzilla servers. Keep it deactivated on small machines to decrease the database size and load.'] = 'Más kiszolgálók időszakos lekérdezése olyan partnerek és kiszolgálók után, amelyekről tudnak. A rendszer Friendica, Mastodon és Hubzilla kiszolgálókat kérdez le. Kisebb gépeken tartsa kikapcsolva az adatbázis méretének és terhelésének csökkentése érdekében.';
$a->strings['Days between requery'] = 'Ismételt lekérdezések közti napok';
$a->strings['Number of days after which a server is requeried for their contacts and servers it knows of. This is only used when the discovery is activated.'] = 'A napok száma, amely után egy kiszolgáló ismét lekérdezésre kerül az általa tudott partnereiért és kiszolgálóiért. Ez csak akkor van használatban, ha a felfedezés be van kapcsolva.';
$a->strings['Search the local directory'] = 'A helyi könyvtár keresése';
$a->strings['Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.'] = 'A helyi könyvtár keresése a globális könyvtár helyett. Helyi kereséskor minden egyes keresés a globális könyvtárban lesz végrehajtva a háttérben. Ez javítja a keresési eredményeket, ha a keresést megismétlik.';
$a->strings['Publish server information'] = 'Kiszolgálóinformációk közzététele';
$a->strings['If enabled, general server and usage data will be published. The data contains the name and version of the server, number of users with public profiles, number of posts and the activated protocols and connectors. See <a href="http://the-federation.info/">the-federation.info</a> for details.'] = 'Ha engedélyezve van, akkor az általános kiszolgáló és használati adatok közzé lesznek téve. Az adatok tartalmazzák a kiszolgáló nevét és verzióját, a nyilvános profillal rendelkező felhasználók számát, a bejegyzések számát, valamint a engedélyezett protokollokat és összekötőket. A részletekért nézze meg a <a href="https://the-federation.info/">the-federation.info</a> weboldalt.';
$a->strings['Check upstream version'] = 'Távoli verzió ellenőrzése';
$a->strings['Enables checking for new Friendica versions at github. If there is a new version, you will be informed in the admin panel overview.'] = 'Engedélyezi az új Friendica verziójának keresését a GitHubon. Ha új verzió érhető el, akkor tájékoztatva lesz az adminisztrátori panel áttekintőjében.';
$a->strings['Suppress Tags'] = 'Címkék letiltása';
$a->strings['Suppress showing a list of hashtags at the end of the posting.'] = 'A kettős keresztes címkék listája megjelenítésének letiltása a beküldések végénél.';
$a->strings['Clean database'] = 'Adatbázis tisztítása';
$a->strings['Remove old remote items, orphaned database records and old content from some other helper tables.'] = 'Régi távoli elemek, árva adatbázisrekordok és néhány egyéb segédtáblából származó régi tartalom eltávolítása.';
$a->strings['Lifespan of remote items'] = 'Távoli elemek élettartama';
$a->strings['When the database cleanup is enabled, this defines the days after which remote items will be deleted. Own items, and marked or filed items are always kept. 0 disables this behaviour.'] = 'Ha az adatbázis-tisztítás engedélyezve van, akkor ez határozza meg azon napok számát, amely után a távoli elemek törölve lesznek. A saját elemek, valamint a megjelölt és iktatott elemek mindig meg lesznek tartva. A 0 érték letiltja ezt a viselkedést.';
$a->strings['Lifespan of unclaimed items'] = 'Nem igényelt elemek élettartama';
$a->strings['When the database cleanup is enabled, this defines the days after which unclaimed remote items (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general lifespan value of remote items if set to 0.'] = 'Ha az adatbázis-tisztítás engedélyezve van, akkor ez határozza meg azon napok számát, amely után a nem igényelt távoli elemek (főleg a továbbításból származó tartalmak) törölve lesznek. Az alapértelmezett érték 90 nap. A távoli elemek általános élettartamértékének alapértelmezettje lesz, ha 0 értékre van állítva.';
$a->strings['Lifespan of raw conversation data'] = 'Nyers beszélgetési adatok élettartama';
$a->strings['The conversation data is used for ActivityPub, as well as for debug purposes. It should be safe to remove it after 14 days, default is 90 days.'] = 'A beszélgetési adatok az ActivityPub hálózatoknál, valamint hibakeresési célokhoz vannak használva. Biztonságosan el lehet távolítani azokat 14 nap után. Alapértelmezetten 90 nap.';
$a->strings['Maximum numbers of comments per post'] = 'Bejegyzésenkénti hozzászólások legnagyobb száma';
$a->strings['How much comments should be shown for each post? Default value is 100.'] = 'Mennyi hozzászólást kell megjeleníteni az egyes bejegyzéseknél? Az alapértelmezett érték 100.';
$a->strings['Maximum numbers of comments per post on the display page'] = 'Bejegyzésenkénti hozzászólások legnagyobb száma a megjelenítési oldalon';
$a->strings['How many comments should be shown on the single view for each post? Default value is 1000.'] = 'Mennyi hozzászólást kell megjeleníteni egy önálló nézeten az egyes bejegyzéseknél? Az alapértelmezett érték 1000.';
$a->strings['Items per page'] = 'Oldalankénti elemek';
$a->strings['Number of items per page in stream pages (network, community, profile/contact statuses, search).'] = 'Oldalankénti elemek száma az adatfolyam oldalakon (hálózat, közösség, profil- vagy partnerállapotok, keresés).';
$a->strings['Items per page for mobile devices'] = 'Oldalankénti elemek száma mobil eszközöknél';
$a->strings['Number of items per page in stream pages (network, community, profile/contact statuses, search) for mobile devices.'] = 'Oldalankénti elemek száma az adatfolyam oldalakon (hálózat, közösség, profil- vagy partnerállapotok, keresés) mobil eszközöknél.';
$a->strings['Temp path'] = 'Ideiglenes mappa útvonala';
$a->strings['If you have a restricted system where the webserver can\'t access the system temp path, enter another path here.'] = 'Ha korlátozott rendszere van, ahol a webkiszolgáló nem tudja elérni a rendszer ideiglenes mappájának útvonalát, akkor adjon meg egy másik útvonalat itt.';
$a->strings['Only search in tags'] = 'Keresés csak címkékben';
$a->strings['On large systems the text search can slow down the system extremely.'] = 'Nagy rendszereknél a szöveges keresés rendkívüli módon lelassíthatja a rendszert.';
$a->strings['Limited search scope'] = 'Korlátozott keresési hatókör';
$a->strings['If enabled, searches will only be performed in the data used for the channels and not in all posts.'] = 'Ha engedélyezve van, akkor a keresések csak a csatornákhoz használt adatokban lesznek végrehajtva, nem az összes bejegyzésben.';
$a->strings['Maximum age of items in the search table'] = 'A keresési táblában lévő elemek legnagyobb életkora';
$a->strings['Maximum age of items in the search table in days. Lower values will increase the performance and reduce disk usage. 0 means no age restriction.'] = 'A keresési táblában lévő elemek legnagyobb életkora napokban. Az alacsonyabb értékek növelik a teljesítményt és csökkentik a lemezhasználatot. A 0 azt jelenti, hogy nincs életkori korlátozás.';
$a->strings['Generate counts per contact circle when calculating network count'] = 'Partnerkörönkénti számlálások előállítása a hálózatszám kiszámításakor';
$a->strings['On systems with users that heavily use contact circles the query can be very expensive.'] = 'Olyan rendszereken, ahol a felhasználók nagymértékben használják a partnerköröket, a lekérdezés nagyon költséges lehet.';
$a->strings['Process "view" activities'] = '„Megtekintés” tevékenységek feldolgozása';
$a->strings['"view" activities are mostly geberated by Peertube systems. Per default they are not processed for performance reasons. Only activate this option on performant system.'] = 'A „megtekintés” tevékenységeket többnyire a Peertube rendszerek hozzák létre. Alapértelmezetten nem kerülnek feldolgozásra teljesítménybeli okokból. Csak teljesítőképes rendszerben kapcsolja be ezt a beállítást.';
$a->strings['Days, after which a contact is archived'] = 'Napok, amely után a partner archiválásra kerül';
$a->strings['Number of days that we try to deliver content or to update the contact data before we archive a contact.'] = 'Azon napok száma, amikor megpróbálunk tartalmat szállítani vagy a partner adatait frissíteni, mielőtt archiváljuk a partnert.';
$a->strings['Maximum number of parallel workers'] = 'Párhuzamos feldolgozók legnagyobb száma';
$a->strings['On shared hosters set this to %d. On larger systems, values of %d are great. Default value is %d.'] = 'Osztott tárhelyszolgáltatóknál állítsa ezt %d értékre. Nagyobb rendszereknél érdemes a számot %d értékre állítani. Az alapértelmezett érték %d.';
$a->strings['Maximum load for workers'] = 'Feldolgozók legnagyobb terhelése';
$a->strings['Maximum load that causes a cooldown before each worker function call.'] = 'A legnagyobb terhelés, amely minden egyes feldolgozófüggvény-hívás előtt várakozást okoz.';
$a->strings['Enable fastlane'] = 'Prioritásos sor engedélyezése';
$a->strings['When enabed, the fastlane mechanism starts an additional worker if processes with higher priority are blocked by processes of lower priority.'] = 'Ha engedélyezve van, akkor a prioritásos sor mechanizmus további feldolgozót indít, ha a magasabb prioritással rendelkező folyamatokat blokkolják az alacsonyabb prioritású folyamatok.';
$a->strings['Decoupled receiver'] = 'Szétválasztott fogadó';
$a->strings['Decouple incoming ActivityPub posts by processing them in the background via a worker process. Only enable this on fast systems.'] = 'A bejövő ActivityPub-bejegyzések szétválasztása egy feldolgozófolyamaton keresztüli, háttérben történő feldolgozással. Ezt csak gyors rendszereken engedélyezze.';
$a->strings['Cron interval'] = 'Cron időköz';
$a->strings['Minimal period in minutes between two calls of the "Cron" worker job.'] = 'Legkisebb időtartam percben a „Cron” feldolgozófeladat két hívása között.';
$a->strings['Worker defer limit'] = 'Feldolgozó halasztási korlátja';
$a->strings['Per default the systems tries delivering for 15 times before dropping it.'] = 'Alapértelmezetten a rendszerek tizenötször próbálkoznak a kézbesítéssel, mielőtt eldobnák azt.';
$a->strings['Worker fetch limit'] = 'Feldolgozó lekérési korlátja';
$a->strings['Number of worker tasks that are fetched in a single query. Higher values should increase the performance, too high values will mostly likely decrease it. Only change it, when you know how to measure the performance of your system.'] = 'Az egyetlen lekérdezésben lekért feldolgozófeladatok száma. A magasabb értékeknek növelniük kellene a teljesítményt, a túl magas értékek viszont valószínűleg csökkentik azt. Csak akkor változtassa meg, ha tudja, hogy hogyan mérje a rendszere teljesítményét.';
$a->strings['Direct relay transfer'] = 'Közvetlen továbbító-átvitel';
$a->strings['Enables the direct transfer to other servers without using the relay servers'] = 'Engedélyezi a más kiszolgálókra való közvetlen átvitelt a továbbító kiszolgálók használata nélkül.';
$a->strings['Relay scope'] = 'Továbbítás hatóköre';
$a->strings['Can be "all" or "tags". "all" means that every public post should be received. "tags" means that only posts with selected tags should be received.'] = 'Lehet „összes” vagy „címkék”. Az „összes” azt jelenti, hogy minden nyilvános bejegyzést meg kell kapni. A „címkék” jelentése, hogy csak a kijelölt címkékkel rendelkező bejegyzéseket kell megkapni.';
$a->strings['Disabled'] = 'Letiltva';
$a->strings['all'] = 'összes';
$a->strings['tags'] = 'címkék';
$a->strings['Server tags'] = 'Kiszolgálócímkék';
$a->strings['Comma separated list of tags for the "tags" subscription.'] = 'Címkék vesszővel elválasztott listája a „címkék” feliratkozáshoz.';
$a->strings['Deny Server tags'] = 'Kiszolgálócímkék megtagadása';
$a->strings['Comma separated list of tags that are rejected.'] = 'Címkék vesszővel elválasztott listája, amelyek vissza lesznek utasítva.';
$a->strings['Maximum amount of tags'] = 'Címkék legnagyobb száma';
$a->strings['Maximum amount of tags in a post before it is rejected as spam. The post has to contain at least one link. Posts from subscribed accounts will not be rejected.'] = 'Egy bejegyzésben lévő címkék legnagyobb száma, mielőtt az kéretlen üzenetként visszautasításra kerülne. A bejegyzésnek legalább egy hivatkozást kell tartalmaznia. A feliratkozott fiókokból származó bejegyzések nem kerülnek visszautasításra.';
$a->strings['Allow user tags'] = 'Felhasználói címkék engedélyezése';
$a->strings['If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".'] = 'Ha engedélyezve van, akkor a mentett keresésekből származó címkék lesznek használva a „címkék” feliratkozásnál a „relay_server_tags” címkéken kívül.';
$a->strings['Deny undetected languages'] = 'Fel nem ismert nyelvek megtagadása';
$a->strings['If enabled, posts with undetected languages will be rejected.'] = 'Ha engedélyezve van, akkor a fel nem ismert nyelveket tartalmazó bejegyzések visszautasításra kerülnek.';
$a->strings['Language Quality'] = 'Nyelvi minőség';
$a->strings['The minimum language quality that is required to accept the post.'] = 'A bejegyzés elfogadásához szükséges legkisebb nyelvi minőség.';
$a->strings['Number of languages for the language detection'] = 'Nyelvek száma a nyelvfelismeréshez';
$a->strings['The system detects a list of languages per post. Only if the desired languages are in the list, the message will be accepted. The higher the number, the more posts will be falsely detected.'] = 'A rendszer felismeri a bejegyzésenkénti nyelvek listáját. Csak akkor kerül elfogadásra az üzenet, ha a kívánt nyelvek szerepelnek a listán. Minél magasabb a szám, annál több bejegyzést lesz tévesen felismerve.';
$a->strings['Maximum age of channel'] = 'Csatorna legnagyobb életkora';
$a->strings['This defines the maximum age in hours of items that should be displayed in channels. This affects the channel performance.'] = 'Ez határozza meg azon elemek legnagyobb életkorát órákban kifejezve, amelyeket meg kell jeleníteni a csatornákon. Ez hatással van a csatorna teljesítményére.';
$a->strings['Maximum number of channel posts'] = 'Csatornabejegyzések legnagyobb száma';
$a->strings['For performance reasons, the channels use a dedicated table to store content. The higher the value the slower the channels.'] = 'Teljesítményi okokból a csatornák külön táblát használnak a tartalom tárolására. Minél magasabb az érték, annál lassabbak a csatornák.';
$a->strings['Interaction score days'] = 'Interakció-pontszám napjai';
$a->strings['Number of days that are used to calculate the interaction score.'] = 'Az interakció-pontszám kiszámításához használt napok száma.';
$a->strings['Maximum number of posts per author'] = 'Szerzőnkénti bejegyzések legnagyobb száma';
$a->strings['Maximum number of posts per page by author if the contact frequency is set to "Display only few posts". If there are more posts, then the post with the most interactions will be displayed.'] = 'Az oldalankénti bejegyzések szerző szerinti legnagyobb száma, ha a partner gyakorisága „Csak néhány bejegyzés megjelenítése” értékre van állítva. Ha több bejegyzés van, akkor a legtöbb interakcióval rendelkező bejegyzés kerül megjelenítésre.';
$a->strings['Sharer interaction days'] = 'Megosztó interakciós napjai';
$a->strings['Number of days of the last interaction that are used to define which sharers are used for the "sharers of sharers" channel.'] = 'Az utolsó interakció azon napjainak száma, amelyek annak meghatározására szolgálnak, hogy mely megosztók legyenek használva a „megosztók megosztói” csatornához.';
$a->strings['Start Relocation'] = 'Áthelyezés indítása';
$a->strings['Storage backend, %s is invalid.'] = 'Tároló háttérprogram, a(z) %s érvénytelen.';
$a->strings['Storage backend %s error: %s'] = 'Tároló háttérprogram (%s) hiba: %s';
$a->strings['Invalid storage backend setting value.'] = 'Érvénytelen tároló-háttérprogram beállítási érték.';
$a->strings['Current Storage Backend'] = 'Jelenlegi tároló háttérprogram';
$a->strings['Storage Configuration'] = 'Tároló beállításai';
$a->strings['Storage'] = 'Tároló';
$a->strings['Save & Use storage backend'] = 'Mentés és a tároló háttérprogram használata';
$a->strings['Use storage backend'] = 'Tároló háttérprogram használata';
$a->strings['Save & Reload'] = 'Mentés és újratöltés';
$a->strings['This backend doesn\'t have custom settings'] = 'Ennek a háttérprogramnak nincsenek egyéni beállításai';
$a->strings['Changing the current backend is prohibited because it is set by an environment variable'] = 'A jelenlegi háttérprogram megváltoztatása tiltva van, mivel azt egy környezeti változó állítja be';
$a->strings['Database (legacy)'] = 'Adatbázis (örökölt)';
$a->strings['Template engine (%s) error: %s'] = 'Sablonmotor (%s) hiba: %s';
$a->strings['Your DB still runs with MyISAM tables. You should change the engine type to InnoDB. As Friendica will use InnoDB only features in the future, you should change this! See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = 'Az adatbázisa még mindig MyISAM táblákkal fut. Meg kell változtatnia a motor típusát InnoDB-re. Mivel a Friendica a jövőben olyan funkciókat fog használni, amely csak InnoDB használatával érhető el, ezért meg kell változtatnia! <a href="%s">Nézze meg ezt az útmutatót</a>, amely hasznos lehet a táblamotorok átalakításához. Használhatja a Friendica telepítésének <tt>php bin/console.php dbstructure toinnodb</tt> parancsát is az automatikus átalakításhoz.<br />';
$a->strings['Your DB still runs with InnoDB tables in the Antelope file format. You should change the file format to Barracuda. Friendica is using features that are not provided by the Antelope format. See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = 'Az adatbázisa még mindig Antelope fájlformátumban lévő InnoDB táblákkal fut. Meg kell változtatnia a fájlformátumot Barracudára. A Friendica olyan funkciókat használ, amelyeket az Antelope fájlformátum nem biztosít. <a href="%s">Nézze meg ezt az útmutatót</a>, amely hasznos lehet a táblamotorok átalakításához. Használhatja a Friendica telepítésének <tt>php bin/console.php dbstructure toinnodb</tt> parancsát is az automatikus átalakításhoz.<br />';
$a->strings['Your table_definition_cache is too low (%d). This can lead to the database error "Prepared statement needs to be re-prepared". Please set it at least to %d. See <a href="%s">here</a> for more information.<br />'] = 'A table_definition_cache értéke túl alacsony (%d). Ez a „Prepared statement needs to be re-prepared” adatbázishibához vezethet. Állítsa legalább %d értékre. További információkért <a href="%s">nézze meg ezt</a>.<br />';
$a->strings['There is a new version of Friendica available for download. Your current version is %1$s, upstream version is %2$s'] = 'Elérhető a Friendica új verziója a letöltéshez. A jelenlegi verziója %1$s, a távoli verzió %2$s.';
$a->strings['The database update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear.'] = 'Az adatbázis frissítése sikertelen. Futtassa a „php bin/console.php dbstructure update” parancsot a parancssorból, és nézze meg a hibákat, amelyek esetleg megjelennek.';
$a->strings['The last update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear. (Some of the errors are possibly inside the logfile.)'] = 'A legutóbbi frissítés sikertelen. Futtassa a „php bin/console.php dbstructure update” parancsot a parancssorból, és nézze meg a hibákat, amelyek esetleg megjelennek (néhány hiba valószínűleg a naplófájlban lesz).';
$a->strings['The system.url entry is missing. This is a low level setting and can lead to unexpected behavior. Please add a valid entry as soon as possible in the config file or per console command!'] = 'A system.url bejegyzés hiányzik. Ez egy alacsony szintű beállítás, és váratlan viselkedéshez vezethet. Adjon meg egy érvényes bejegyzést a lehető leghamarabb a beállítófájlban vagy konzolparancsonként!';
$a->strings['The worker was never executed. Please check your database structure!'] = 'A feldolgozó sosem lett végrehajtva. Ellenőrizze az adatbázis szerkezetét!';
$a->strings['The last worker execution was on %s UTC. This is older than one hour. Please check your crontab settings.'] = 'Az utolsó feldolgozó-végrehajtás ideje %s volt (UTC szerint). Ez régebbi mint egy óra. Ellenőrizze a cron-feladat beállításait.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>.htconfig.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'A Friendica beállításai most a „config/local.config.php” fájlban vannak eltárolva. Másolja le a „config/local-sample.config.php” fájlt, és helyezze át a beállításokat a <code>.htconfig.php</code> fájlból. Az átvitelhez való segítségért nézze meg a <a href="%s">beállítások súgóoldalát</a>.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>config/local.ini.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'A Friendica beállításai most a „config/local.config.php” fájlban vannak eltárolva. Másolja le a „config/local-sample.config.php” fájlt, és helyezze át a beállításokat a <code>config/local.ini.php</code> fájlból. Az átvitelhez való segítségért nézze meg a <a href="%s">beállítások súgóoldalát</a>.';
$a->strings['<a href="%s">%s</a> is not reachable on your system. This is a severe configuration issue that prevents server to server communication. See <a href="%s">the installation page</a> for help.'] = 'A <a href="%s">%s</a> nem érhető el a rendszeréről. Ez súlyos beállítási probléma, amely megakadályozza a kiszolgálók közti kommunikációt. Nézze meg a <a href="%s">telepítési oldalt</a> a segítségért.';
$a->strings['Friendica\'s system.basepath was updated from \'%s\' to \'%s\'. Please remove the system.basepath from your db to avoid differences.'] = 'A Friendica „system.basepath” beállítása frissítve lett „%s” értékről „%s” értékre. Távolítsa el a „system.basepath” beállítást az adatbázisából az eltérések elkerüléséhez.';
$a->strings['Friendica\'s current system.basepath \'%s\' is wrong and the config file \'%s\' isn\'t used.'] = 'A Friendica jelenlegi „system.basepath” értéke („%s”) hibás, és a(z) „%s” beállítófájl nincs használva.';
$a->strings['Friendica\'s current system.basepath \'%s\' is not equal to the config file \'%s\'. Please fix your configuration.'] = 'A Friendica jelenlegi „system.basepath” értéke („%s”) nem azonos a(z) „%s” beállítófájlban lévővel. Javítsa a beállításokat.';
$a->strings['Message queues'] = 'Üzenet várakozási sorai';
$a->strings['Server Settings'] = 'Kiszolgálóbeállítások';
$a->strings['Version'] = 'Verzió';
$a->strings['Active addons'] = 'Bekapcsolt bővítmények';
$a->strings['Theme %s disabled.'] = 'A(z) „%s” téma letiltva.';
$a->strings['Theme %s successfully enabled.'] = 'A(z) „%s” téma sikeresen engedélyezve.';
$a->strings['Theme %s failed to install.'] = 'A(z) „%s” téma telepítése sikertelen.';
$a->strings['Screenshot'] = 'Képernyőkép';
$a->strings['Themes'] = 'Témák';
$a->strings['Unknown theme.'] = 'Ismeretlen téma.';
$a->strings['Themes reloaded'] = 'Témák újratöltve';
$a->strings['Reload active themes'] = 'Bekapcsolt témák újratöltése';
$a->strings['No themes found on the system. They should be placed in %1$s'] = 'Nem találhatók témák a rendszeren. A témákat a %1$s könyvtárba kell elhelyezni.';
$a->strings['[Experimental]'] = '[Kísérleti]';
$a->strings['[Unsupported]'] = '[Nem támogatott]';
$a->strings['Display Terms of Service'] = 'Használati feltételek megjelenítése';
$a->strings['Enable the Terms of Service page. If this is enabled a link to the terms will be added to the registration form and the general information page.'] = 'A használati feltételek oldal engedélyezése. Ha ez engedélyezve van, akkor a használati feltételekre mutató hivatkozás hozzá lesz adva a regisztrációs űrlaphoz és az általános információk oldalához.';
$a->strings['Display Privacy Statement'] = 'Adatvédelmi nyilatkozatok megjelenítése';
$a->strings['Show some informations regarding the needed information to operate the node according e.g. to <a href="%s" target="_blank" rel="noopener noreferrer">EU-GDPR</a>.'] = 'Néhány információ megjelenítése a csomópont üzemeltetésére vonatkozó szükséges információkról, például az <a href="%s" target="_blank" rel="noopener noreferrer">EU-GDPR</a> szerint.';
$a->strings['Privacy Statement Preview'] = 'Adatvédelmi nyilatkozat előnézete';
$a->strings['The Terms of Service'] = 'A használati feltételek';
$a->strings['Enter the Terms of Service for your node here. You can use BBCode. Headers of sections should be [h2] and below.'] = 'Itt adja meg a csomópontja használati feltételeit. Használhat BBCode formázást is. A szakaszok címeinek [h2] vagy az alattiaknak kell lenniük.';
$a->strings['The rules'] = 'A szabályok';
$a->strings['Enter your system rules here. Each line represents one rule.'] = 'Itt adja meg a rendszer szabályait. Minden sor egy szabályt jelent.';
$a->strings['API endpoint %s %s is not implemented but might be in the future.'] = 'A(z) %s %s API-végpont nincs megvalósítva, de a jövőben megvalósításra kerülhet.';
$a->strings['Missing parameters'] = 'Hiányzó paraméterek';
$a->strings['Only starting posts can be bookmarked'] = 'Csak a kezdeti bejegyzéseket lehet könyvjelzőzni';
$a->strings['Only starting posts can be muted'] = 'Csak a kezdeti bejegyzéseket lehet némítani';
$a->strings['Posts from %s can\'t be shared'] = '%s bejegyzéseit nem lehet megosztani';
$a->strings['Only starting posts can be unbookmarked'] = 'Csak a kezdeti bejegyzéseket lehet kivenni a könyvjelzőkből';
$a->strings['Only starting posts can be unmuted'] = 'Csak a kezdeti bejegyzéseket némítását lehet megszüntetni';
$a->strings['Posts from %s can\'t be unshared'] = '%s bejegyzéseinek megosztását nem lehet visszavonni';
$a->strings['Contact not found'] = 'A partner nem található';
$a->strings['No installed applications.'] = 'Nincsenek telepített alkalmazások.';
$a->strings['Applications'] = 'Alkalmazások';
$a->strings['Item was not found.'] = 'Az elem nem található.';
$a->strings['Please login to continue.'] = 'Jelentkezzen be a folytatáshoz.';
$a->strings['You don\'t have access to administration pages.'] = 'Nincs hozzáférése az adminisztrációs oldalakhoz.';
$a->strings['Submanaged account can\'t access the administration pages. Please log back in as the main account.'] = 'Az alkezelt fiókok nem férhetnek hozzá az adminisztrációs oldalakhoz. Jelentkezzen vissza a fő fiókkal.';
$a->strings['Overview'] = 'Áttekintő';
$a->strings['Configuration'] = 'Beállítás';
$a->strings['Additional features'] = 'További funkciók';
$a->strings['Database'] = 'Adatbázis';
$a->strings['DB updates'] = 'Adatbázis-frissítések';
$a->strings['Inspect Deferred Workers'] = 'Elhalasztott feldolgozók vizsgálata';
$a->strings['Inspect worker Queue'] = 'Feldolgozó várakozási sorának vizsgálata';
$a->strings['Diagnostics'] = 'Diagnosztika';
$a->strings['PHP Info'] = 'PHP-információk';
$a->strings['probe address'] = 'Cím szondázása';
$a->strings['check webfinger'] = 'WebFinger ellenőrzése';
$a->strings['Babel'] = 'Babel';
$a->strings['ActivityPub Conversion'] = 'ActivityPub beszélgetés';
$a->strings['Addon Features'] = 'Bővítményszolgáltatások';
$a->strings['User registrations waiting for confirmation'] = 'Megerősítésre váró felhasználói regisztrációk';
$a->strings['Too Many Requests'] = 'Túl sok kérés';
$a->strings['Daily posting limit of %d post reached. The post was rejected.'] = [
	0 => 'A napi %d bejegyzésből álló beküldési korlát elérve. A bejegyzés vissza lett utasítva.',
	1 => 'A napi %d bejegyzésből álló beküldési korlát elérve. A bejegyzés vissza lett utasítva.',
];
$a->strings['Weekly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'A heti %d bejegyzésből álló beküldési korlát elérve. A bejegyzés vissza lett utasítva.',
	1 => 'A heti %d bejegyzésből álló beküldési korlát elérve. A bejegyzés vissza lett utasítva.',
];
$a->strings['Monthly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'A havi %d bejegyzésből álló beküldési korlát elérve. A bejegyzés vissza lett utasítva.',
	1 => 'A havi %d bejegyzésből álló beküldési korlát elérve. A bejegyzés vissza lett utasítva.',
];
$a->strings['You don\'t have access to moderation pages.'] = 'Nincs hozzáférése a moderálási oldalakhoz.';
$a->strings['Submanaged account can\'t access the moderation pages. Please log back in as the main account.'] = 'Az alkezelt fiókok nem férhetnek hozzá a moderálási oldalakhoz. Jelentkezzen vissza a fő fiókkal.';
$a->strings['Reports'] = 'Jelentések';
$a->strings['Users'] = 'Felhasználók';
$a->strings['Tools'] = 'Eszközök';
$a->strings['Contact Blocklist'] = 'Partnertiltólista';
$a->strings['Server Blocklist'] = 'Kiszolgáló-tiltólista';
$a->strings['Delete Item'] = 'Elem törlése';
$a->strings['Item Source'] = 'Elem forrása';
$a->strings['Profile Details'] = 'Profil részletei';
$a->strings['Conversations started'] = 'Beszélgetések elkezdve';
$a->strings['Only You Can See This'] = 'Csak Ön láthatja ezt';
$a->strings['Scheduled Posts'] = 'Ütemezett bejegyzések';
$a->strings['Posts that are scheduled for publishing'] = 'Bejegyzések, amelyek közzétételre vannak üzemezve';
$a->strings['Tips for New Members'] = 'Tippek új tagoknak';
$a->strings['More'] = 'Több';
$a->strings['People Search - %s'] = 'Emberek keresése – %s';
$a->strings['Group Search - %s'] = 'Csoportkeresés – %s';
$a->strings['No matches'] = 'Nincs találat';
$a->strings['%d result was filtered out because your node blocks the domain it is registered on. You can review the list of domains your node is currently blocking in the <a href="/friendica">About page</a>.'] = [
	0 => '%d találat ki lett szűrve, mert az Ön csomópontja tiltja azt a tartományt, amelyen az regisztrálva van. A <a href="/friendica">Névjegy oldalon</a> felülvizsgálhatja azon tartományok listáját, amelyet a csomópontja jelenleg letilt.',
	1 => '%d találat ki lett szűrve, mert az Ön csomópontja tiltja azt a tartományt, amelyen azok regisztrálva vannak. A <a href="/friendica">Névjegy oldalon</a> felülvizsgálhatja azon tartományok listáját, amelyet a csomópontja jelenleg letilt.',
];
$a->strings['Account'] = 'Fiók';
$a->strings['Two-factor authentication'] = 'Kétlépcsős hitelesítés';
$a->strings['Display'] = 'Megjelenítés';
$a->strings['Social Networks'] = 'Közösségi hálózatok';
$a->strings['Manage Accounts'] = 'Fiókok kezelése';
$a->strings['Connected apps'] = 'Kapcsolt alkalmazások';
$a->strings['Remote servers'] = 'Távoli kiszolgálók';
$a->strings['Import Contacts'] = 'Partnerek importálása';
$a->strings['Export personal data'] = 'Személyes adatok exportálása';
$a->strings['Remove account'] = 'Fiók eltávolítása';
$a->strings['This page is missing a url parameter.'] = 'Erről az oldalról hiányzik egy URL paraméter.';
$a->strings['The post was created'] = 'A bejegyzés létrejött';
$a->strings['Invalid Request'] = 'Érvénytelen kérés';
$a->strings['Event id is missing.'] = 'Az eseményazonosító hiányzik.';
$a->strings['Failed to remove event'] = 'Nem sikerült eltávolítani az eseményt';
$a->strings['Event can not end before it has started.'] = 'Az esemény nem fejeződhet be, mielőtt elkezdődött volna.';
$a->strings['Event title and start time are required.'] = 'Az esemény címe és a kezdési idő kötelező.';
$a->strings['Starting date and Title are required.'] = 'A kezdési dátum és a cím kötelező.';
$a->strings['Event Starts:'] = 'Esemény kezdete:';
$a->strings['Required'] = 'Kötelező';
$a->strings['Finish date/time is not known or not relevant'] = 'A befejezési dátum vagy idő nem ismert vagy nem fontos';
$a->strings['Event Finishes:'] = 'Esemény befejezése:';
$a->strings['Title (BBCode not allowed)'] = 'Cím (BBCode nem engedélyezett)';
$a->strings['Description (BBCode allowed)'] = 'Leírás (BBCode engedélyezett)';
$a->strings['Location (BBCode not allowed)'] = 'Hely (BBCode nem engedélyezett)';
$a->strings['Share this event'] = 'Az esemény megosztása';
$a->strings['Basic'] = 'Alap';
$a->strings['This calendar format is not supported'] = 'Ez a naptárformátum nem támogatott';
$a->strings['No exportable data found'] = 'Nem található exportálható adat';
$a->strings['calendar'] = 'naptár';
$a->strings['Events'] = 'Események';
$a->strings['View'] = 'Nézet';
$a->strings['Create New Event'] = 'Új esemény létrehozása';
$a->strings['list'] = 'lista';
$a->strings['Could not create circle.'] = 'Nem sikerült létrehozni a kört.';
$a->strings['Circle not found.'] = 'A kör nem található.';
$a->strings['Circle name was not changed.'] = 'A kör neve nem változott meg.';
$a->strings['Unknown circle.'] = 'Ismeretlen kör.';
$a->strings['Contact not found.'] = 'A partner nem található.';
$a->strings['Invalid contact.'] = 'Érvénytelen partner.';
$a->strings['Contact is deleted.'] = 'A partner törölve.';
$a->strings['Unable to add the contact to the circle.'] = 'Nem lehet hozzáadni a partnert a körhöz.';
$a->strings['Contact successfully added to circle.'] = 'A partner sikeresen hozzáadva a körhöz.';
$a->strings['Unable to remove the contact from the circle.'] = 'Nem lehet eltávolítani a partnert a körből.';
$a->strings['Contact successfully removed from circle.'] = 'A partner sikeresen eltávolítva a körből.';
$a->strings['Bad request.'] = 'Hibás kérés.';
$a->strings['Save Circle'] = 'Kör mentése';
$a->strings['Filter'] = 'Szűrő';
$a->strings['Create a circle of contacts/friends.'] = 'Partnerek vagy ismerősök körének létrehozása.';
$a->strings['Unable to remove circle.'] = 'Nem lehet eltávolítani a kört.';
$a->strings['Delete Circle'] = 'Kör törlése';
$a->strings['Edit Circle Name'] = 'Kör nevének szerkesztése';
$a->strings['Members'] = 'Tagok';
$a->strings['Circle is empty'] = 'A kör üres';
$a->strings['Remove contact from circle'] = 'Partner eltávolítása a körből';
$a->strings['Click on a contact to add or remove.'] = 'Kattintson egy partnerre a hozzáadáshoz vagy eltávolításhoz.';
$a->strings['Add contact to circle'] = 'Partner hozzáadása a körhöz';
$a->strings['%d contact edited.'] = [
	0 => '%d partner szerkesztve.',
	1 => '%d partner szerkesztve.',
];
$a->strings['Show all contacts'] = 'Összes partner megjelenítése';
$a->strings['Pending'] = 'Függőben';
$a->strings['Only show pending contacts'] = 'Csak a függőben lévő partnerek megjelenítése';
$a->strings['Blocked'] = 'Tiltva';
$a->strings['Only show blocked contacts'] = 'Csak a tiltott partnerek megjelenítése';
$a->strings['Ignored'] = 'Mellőzve';
$a->strings['Only show ignored contacts'] = 'Csak a mellőzött partnerek megjelenítése';
$a->strings['Collapsed'] = 'Összecsukva';
$a->strings['Only show collapsed contacts'] = 'Csak az összecsukott partnerek megjelenítése';
$a->strings['Archived'] = 'Archiválva';
$a->strings['Only show archived contacts'] = 'Csak az archivált partnerek megjelenítése';
$a->strings['Hidden'] = 'Rejtett';
$a->strings['Only show hidden contacts'] = 'Csak a rejtett partnerek megjelenítése';
$a->strings['Organize your contact circles'] = 'Partnerkörök szervezése';
$a->strings['Search your contacts'] = 'Partnerek keresése';
$a->strings['Results for: %s'] = 'Találatok erre: %s';
$a->strings['Update'] = 'Frissítés';
$a->strings['Unblock'] = 'Tiltás feloldása';
$a->strings['Unignore'] = 'Mellőzés feloldása';
$a->strings['Uncollapse'] = 'Összecsukás megszüntetése';
$a->strings['Batch Actions'] = 'Tömeges műveletek';
$a->strings['Conversations started by this contact'] = 'A partner által elkezdett beszélgetések';
$a->strings['Posts and Comments'] = 'Bejegyzések és hozzászólások';
$a->strings['Individual Posts and Replies'] = 'Egyéni bejegyzések és válaszok';
$a->strings['Posts containing media objects'] = 'Médiaobjektumokat tartalmazó bejegyzések';
$a->strings['View all known contacts'] = 'Összes ismert partner megtekintése';
$a->strings['Advanced Contact Settings'] = 'Speciális partnerbeállítások';
$a->strings['Mutual Friendship'] = 'Kölcsönös ismeretség';
$a->strings['is a fan of yours'] = 'az Ön rajongója';
$a->strings['you are a fan of'] = 'Ön rajong érte:';
$a->strings['Pending outgoing contact request'] = 'Függőben lévő kimenő partnerkérés';
$a->strings['Pending incoming contact request'] = 'Függőben lévő bejövő partnerkérés';
$a->strings['Visit %s\'s profile [%s]'] = '%s profiljának megtekintése [%s]';
$a->strings['Contact update failed.'] = 'A partner frissítése sikertelen.';
$a->strings['Return to contact editor'] = 'Visszatérés a partnerszerkesztőhöz';
$a->strings['Name'] = 'Név';
$a->strings['Account Nickname'] = 'Fiók beceneve';
$a->strings['Account URL'] = 'Fiók URL';
$a->strings['Poll/Feed URL'] = 'Lekérés vagy hírforrás URL';
$a->strings['New photo from this URL'] = 'Új fénykép erről az URL-ről';
$a->strings['No known contacts.'] = 'Nincsenek ismert partnerek.';
$a->strings['No common contacts.'] = 'Nincsenek közös partnerek.';
$a->strings['Follower (%s)'] = [
	0 => 'Követő (%s)',
	1 => 'Követők (%s)',
];
$a->strings['Following (%s)'] = [
	0 => 'Követés (%s)',
	1 => 'Követés (%s)',
];
$a->strings['Mutual friend (%s)'] = [
	0 => 'Kölcsönösen ismerősök (%s)',
	1 => 'Kölcsönösen ismerősök (%s)',
];
$a->strings['These contacts both follow and are followed by <strong>%s</strong>.'] = 'Ezeket a partnereket <strong>%s</strong> követi és ők is követik őt.';
$a->strings['Common contact (%s)'] = [
	0 => 'Közös partner (%s)',
	1 => 'Közös partnerek (%s)',
];
$a->strings['Both <strong>%s</strong> and yourself have publicly interacted with these contacts (follow, comment or likes on public posts).'] = '<strong>%s</strong> és Ön is nyilvánosan kapcsolatba léptek ezekkel a partnerekkel (követés, hozzászólás vagy kedvelések a nyilvános bejegyzéseknél).';
$a->strings['Contact (%s)'] = [
	0 => 'Partner (%s)',
	1 => 'Partnerek (%s)',
];
$a->strings['Access denied.'] = 'Hozzáférés megtagadva.';
$a->strings['Submit Request'] = 'Kérés elküldése';
$a->strings['You already added this contact.'] = 'Már hozzáadta ezt a partnert.';
$a->strings['The network type couldn\'t be detected. Contact can\'t be added.'] = 'A hálózat típusát nem sikerült felismerni. A partnert nem lehet hozzáadni.';
$a->strings['Diaspora support isn\'t enabled. Contact can\'t be added.'] = 'A Diaspora támogatása nincs engedélyezve. A partnert nem lehet hozzáadni.';
$a->strings['Please answer the following:'] = 'Válaszoljon a következőre:';
$a->strings['Your Identity Address:'] = 'Az Ön személyazonosság-címe:';
$a->strings['Profile URL'] = 'Profil URL';
$a->strings['Tags:'] = 'Címkék:';
$a->strings['%s knows you'] = '%s ismeri Önt';
$a->strings['Add a personal note:'] = 'Személyes jegyzet hozzáadása:';
$a->strings['Posts and Replies'] = 'Bejegyzések és válaszok';
$a->strings['The contact could not be added.'] = 'A partnert nem sikerült hozzáadni.';
$a->strings['Invalid request.'] = 'Érvénytelen kérés.';
$a->strings['No keywords to match. Please add keywords to your profile.'] = 'Nincs illesztendő kulcsszó. Adjon kulcsszavakat a profiljához.';
$a->strings['Profile Match'] = 'Profilegyezés';
$a->strings['Failed to update contact record.'] = 'Nem sikerült frissíteni a partner rekordját.';
$a->strings['Contact has been unblocked'] = 'A partner tiltása fel lett oldva';
$a->strings['Contact has been blocked'] = 'A partner tiltva lett';
$a->strings['Contact has been unignored'] = 'A partner mellőzése fel lett oldva';
$a->strings['Contact has been ignored'] = 'A partner mellőzve lett';
$a->strings['Contact has been uncollapsed'] = 'A partner összecsukása meg lett szüntetve';
$a->strings['Contact has been collapsed'] = 'A partner össze lett csukva';
$a->strings['You are mutual friends with %s'] = 'Ön kölcsönösen ismerős %s partnerrel';
$a->strings['You are sharing with %s'] = 'Ön megoszt %s partnerrel';
$a->strings['%s is sharing with you'] = '%s megoszt Önnel';
$a->strings['Private communications are not available for this contact.'] = 'A személyes kommunikációk nem érhetők el ennél a partnernél.';
$a->strings['This contact is on a server you ignored.'] = 'Ez a partner olyan kiszolgálón van, amelyet mellőzött.';
$a->strings['Never'] = 'Soha';
$a->strings['(Update was not successful)'] = '(a frissítés nem volt sikeres)';
$a->strings['(Update was successful)'] = '(a frissítés sikeres volt)';
$a->strings['Suggest friends'] = 'Ismerősök ajánlása';
$a->strings['Network type: %s'] = 'Hálózat típusa: %s';
$a->strings['Communications lost with this contact!'] = 'A kommunikációk megszakadtak ezzel a partnerrel!';
$a->strings['Fetch further information for feeds'] = 'További információk lekérése a hírforrásokhoz';
$a->strings['Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.'] = 'Információk lekérése a hírforrás eleméből, mint például előnézeti képek, cím és előzetes. Akkor kapcsolhatja be ezt, ha a hírforrás nem tartalmaz sok szöveget. A kulcsszavak a hírforrás elemében lévő metafejlécéből lesznek kiszedve, és kettős keresztes címkékként lesznek beküldve.';
$a->strings['Fetch information'] = 'Információk lekérése';
$a->strings['Fetch keywords'] = 'Kulcsszavak lekérése';
$a->strings['Fetch information and keywords'] = 'Információk és kulcsszavak lekérése';
$a->strings['No mirroring'] = 'Nincs tükrözés';
$a->strings['Mirror as my own posting'] = 'Tükrözés saját beküldésként';
$a->strings['Native reshare'] = 'Natív újra megosztás';
$a->strings['Contact Information / Notes'] = 'Partner információ vagy jegyzetek';
$a->strings['Contact Settings'] = 'Partnerbeállítások';
$a->strings['Contact'] = 'Partner';
$a->strings['Their personal note'] = 'A személyes jegyzeteik';
$a->strings['Edit contact notes'] = 'Partner jegyzeteinek szerkesztése';
$a->strings['Block/Unblock contact'] = 'Partner tiltása vagy tiltásának feloldása';
$a->strings['Ignore contact'] = 'Partner mellőzése';
$a->strings['View conversations'] = 'Beszélgetések megtekintése';
$a->strings['Last update:'] = 'Utolsó frissítés:';
$a->strings['Update public posts'] = 'Nyilvános bejegyzések frissítése';
$a->strings['Update now'] = 'Frissítés most';
$a->strings['Awaiting connection acknowledge'] = 'Várakozás a kapcsolat nyugtázására';
$a->strings['Currently blocked'] = 'Jelenleg tiltva';
$a->strings['Currently ignored'] = 'Jelenleg mellőzve';
$a->strings['Currently collapsed'] = 'Jelenleg összecsukva';
$a->strings['Currently archived'] = 'Jelenleg archiválva';
$a->strings['Manage remote servers'] = 'Távoli kiszolgálók kezelése';
$a->strings['Hide this contact from others'] = 'A partner elrejtése mások elől';
$a->strings['Replies/likes to your public posts <strong>may</strong> still be visible'] = 'A nyilvános bejegyzéseire adott válaszok vagy kedvelések továbbra is láthatóak <strong>lehetnek</strong>.';
$a->strings['Notification for new posts'] = 'Értesítés új bejegyzéseknél';
$a->strings['Send a notification of every new post of this contact'] = 'Értesítés küldése a partner minden új bejegyzéséről.';
$a->strings['Keyword Deny List'] = 'Kulcsszavas tiltólista';
$a->strings['Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected'] = 'Kulcsszavak vesszővel elválasztott listája, amelyeket nem szabad kettős keresztes címkékké átalakítani, ha az „Információk és kulcsszavak lekérése” ki van jelölve.';
$a->strings['Actions'] = 'Műveletek';
$a->strings['Status'] = 'Állapot';
$a->strings['Mirror postings from this contact'] = 'Beküldés tükrözése ettől a partnertől';
$a->strings['Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'] = 'A partner megjelölése távoli önmagaként. Ezt azt fogja okozni, hogy a Friendica újraküldi az ettől a partnertől származó új bejegyzéseket.';
$a->strings['Channel Settings'] = 'Csatorna beállításai';
$a->strings['Frequency of this contact in relevant channels'] = 'A partner gyakorisága a kapcsolódó csatornákban';
$a->strings['Depending on the type of the channel not all posts from this contact are displayed. By default, posts need to have a minimum amount of interactions (comments, likes) to show in your channels. On the other hand there can be contacts who flood the channel, so you might want to see only some of their posts. Or you don\'t want to see their content at all, but you don\'t want to block or hide the contact completely.'] = 'A csatorna típusától függően nem minden bejegyzés jelenik meg ettől a partnertől. Alapértelmezetten a bejegyzéseknek minimális számú interakcióval (hozzászólások, kedvelések) kell rendelkezniük ahhoz, hogy megjelenjenek a csatornákon. Másrészt lehetnek olyan partnerek is, akik elárasztják a csatornát, így előfordulhat, hogy csak néhány bejegyzésüket szeretné látni. Vagy egyáltalán nem szeretné látni a tartalmaikat, de nem szeretné teljesen letiltani vagy elrejteni a partnert.';
$a->strings['Default frequency'] = 'Alapértelmezett gyakoriság';
$a->strings['Posts by this contact are displayed in the "for you" channel if you interact often with this contact or if a post reached some level of interaction.'] = 'Ennek a partnernek a bejegyzései akkor jelennek meg az „Önnek” csatornán, ha gyakran lép kapcsolatba ezzel a partnerrel, vagy ha egy bejegyzés elért egy bizonyos interakciós szintet.';
$a->strings['Display all posts of this contact'] = 'A partner összes bejegyzésének megjelenítése';
$a->strings['All posts from this contact will appear on the "for you" channel'] = 'Ennek a partnernek az összes bejegyzése megjelenik az „Önnek” csatornán.';
$a->strings['Display only few posts'] = 'Csak néhány bejegyzés megjelenítése';
$a->strings['When a contact creates a lot of posts in a short period, this setting reduces the number of displayed posts in every channel.'] = 'Ha egy partner rövid időn belül sok bejegyzést hoz létre, akkor ez a beállítás csökkenti a megjelenített bejegyzések számát minden csatornán.';
$a->strings['Never display posts'] = 'Soha se jelenítsen meg bejegyzéseket';
$a->strings['Posts from this contact will never be displayed in any channel'] = 'Ennek a partnernek a bejegyzései soha sem jelennek meg semmilyen csatornán.';
$a->strings['Channel Only'] = 'Csak csatorna';
$a->strings['If enabled, posts from this contact will only appear in channels and network streams in circles, but not in the general network stream.'] = 'Ha engedélyezve van, akkor az ettől a partnertől származó bejegyzések csak a körökben lévő csatornákon és hálózati adatfolyamokon jelennek meg, de az általános hálózati adatfolyamban nem.';
$a->strings['Refetch contact data'] = 'Partneradatok ismételt lekérése';
$a->strings['Toggle Blocked status'] = 'Tiltott állapot átváltása';
$a->strings['Toggle Ignored status'] = 'Mellőzött állapot átváltása';
$a->strings['Toggle Collapsed status'] = 'Összecsukott állapot átváltása';
$a->strings['Revoke Follow'] = 'Követés visszavonása';
$a->strings['Revoke the follow from this contact'] = 'A követés visszavonása ettől a partnertől';
$a->strings['Bad Request.'] = 'Hibás kérés.';
$a->strings['Contact is being deleted.'] = 'A partner törlésre került.';
$a->strings['Follow was successfully revoked.'] = 'A követés sikeresen vissza lett vonva.';
$a->strings['Do you really want to revoke this contact\'s follow? This cannot be undone and they will have to manually follow you back again.'] = 'Valóban vissza szeretné vonni ennek a partnernek a követését? Ezt a műveletet nem lehet visszavonni, és a partnernek kézzel kell majd újra követnie Önt.';
$a->strings['No suggestions available. If this is a new site, please try again in 24 hours.'] = 'Nem érhetők el javaslatok. Ha ez egy új oldal, akkor próbálja újra 24 óra múlva.';
$a->strings['You aren\'t following this contact.'] = 'Ön nem követi ezt a partnert.';
$a->strings['Unfollowing is currently not supported by your network.'] = 'A követés megszüntetését jelenleg nem támogatja a hálózata.';
$a->strings['Disconnect/Unfollow'] = 'Leválasztás vagy követés megszüntetése';
$a->strings['Contact was successfully unfollowed'] = 'A partner követése sikeresen meg lett szüntetve';
$a->strings['Unable to unfollow this contact, please contact your administrator'] = 'Nem lehet megszüntetni ennek a partnernek a követését, vegye fel a kapcsolatot az adminisztrátorral';
$a->strings['No results.'] = 'Nincs találat.';
$a->strings['Channel not available.'] = 'A csatorna nem érhető el.';
$a->strings['This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.'] = 'Ez a közösségi adatfolyam megjeleníti az összes nyilvános bejegyzést, amelyet ez a csomópont megkapott. Előfordulhat, hogy azok nem tükrözik ezen csomópont felhasználóinak véleményét.';
$a->strings['Community option not available.'] = 'A közösségi beállítás nem érhető el.';
$a->strings['Not available.'] = 'Nem érhető el.';
$a->strings['No such circle'] = 'Nincs ilyen kör';
$a->strings['Circle: %s'] = 'Kör: %s';
$a->strings['Error %d (%s) while fetching the timeline.'] = 'Hiba %d (%s) az idővonal lekérése során.';
$a->strings['Network feed not available.'] = 'A hálózati hírforrás nem érhető el.';
$a->strings['Include'] = 'Tartalmazás';
$a->strings['Hide'] = 'Elrejtés';
$a->strings['Credits'] = 'Köszönetnyilvánítás';
$a->strings['Friendica is a community project, that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!'] = 'A Friendica egy közösségi projekt, amely nem lehetne lehetséges a sok ember segítsége nélkül. Itt van azok listája, akik közreműködtek a kódban vagy a Friendica fordításában. Köszönet mindannyiuknak!';
$a->strings['Formatted'] = 'Formázott';
$a->strings['Activity'] = 'Tevékenység';
$a->strings['Object data'] = 'Objektum adatai';
$a->strings['Result Item'] = 'Eredményelem';
$a->strings['Error'] = [
	0 => 'Hiba',
	1 => 'Hibák',
];
$a->strings['Source activity'] = 'Forrástevékenység';
$a->strings['Source input'] = 'Forrás bevitele';
$a->strings['BBCode::toPlaintext'] = 'BBCode::toPlaintext';
$a->strings['BBCode::convert (raw HTML)'] = 'BBCode::convert (nyers HTML)';
$a->strings['BBCode::convert (hex)'] = 'BBCode::convert (hexa)';
$a->strings['BBCode::convert'] = 'BBCode::convert';
$a->strings['BBCode::convert => HTML::toBBCode'] = 'BBCode::convert => HTML::toBBCode';
$a->strings['BBCode::toMarkdown'] = 'BBCode::toMarkdown';
$a->strings['BBCode::toMarkdown => Markdown::convert (raw HTML)'] = 'BBCode::toMarkdown => Markdown::convert (nyers HTML)';
$a->strings['BBCode::toMarkdown => Markdown::convert'] = 'BBCode::toMarkdown => Markdown::convert';
$a->strings['BBCode::toMarkdown => Markdown::toBBCode'] = 'BBCode::toMarkdown => Markdown::toBBCode';
$a->strings['BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode'] = 'BBCode::toMarkdown => Markdown::convert => HTML::toBBCode';
$a->strings['Item Body'] = 'Elem törzse';
$a->strings['Item Tags'] = 'Elem címkéi';
$a->strings['PageInfo::appendToBody'] = 'PageInfo::appendToBody';
$a->strings['PageInfo::appendToBody => BBCode::convert (raw HTML)'] = 'PageInfo::appendToBody => BBCode::convert (nyers HTML)';
$a->strings['PageInfo::appendToBody => BBCode::convert'] = 'PageInfo::appendToBody => BBCode::convert';
$a->strings['Source input (Diaspora format)'] = 'Forrás bevitele (Diaspora formátum)';
$a->strings['Source input (Markdown)'] = 'Forrás bevitele (Markdown)';
$a->strings['Markdown::convert (raw HTML)'] = 'Markdown::convert (nyers HTML)';
$a->strings['Markdown::convert'] = 'Markdown::convert';
$a->strings['Markdown::toBBCode'] = 'Markdown::toBBCode';
$a->strings['Raw HTML input'] = 'Nyers HTML bevitel';
$a->strings['HTML Input'] = 'HTML bevitel';
$a->strings['HTML Purified (raw)'] = 'HTML megtisztítva (nyers)';
$a->strings['HTML Purified (hex)'] = 'HTML megtisztítva (hexa)';
$a->strings['HTML Purified'] = 'HTML megtisztítva';
$a->strings['HTML::toBBCode'] = 'HTML::toBBCode';
$a->strings['HTML::toBBCode => BBCode::convert'] = 'HTML::toBBCode => BBCode::convert';
$a->strings['HTML::toBBCode => BBCode::convert (raw HTML)'] = 'HTML::toBBCode => BBCode::convert (nyers HTML)';
$a->strings['HTML::toBBCode => BBCode::toPlaintext'] = 'HTML::toBBCode => BBCode::toPlaintext';
$a->strings['HTML::toMarkdown'] = 'HTML::toMarkdown';
$a->strings['HTML::toPlaintext'] = 'HTML::toPlaintext';
$a->strings['HTML::toPlaintext (compact)'] = 'HTML::toPlaintext (tömör)';
$a->strings['Decoded post'] = 'Dekódolt bejegyzés';
$a->strings['Post array before expand entities'] = 'A bejegyzéstömb az entitások kiterjesztése előtt';
$a->strings['Babel Diagnostic'] = 'Babel diagnosztika';
$a->strings['Source text'] = 'Forrásszöveg';
$a->strings['BBCode'] = 'BBCode';
$a->strings['Markdown'] = 'Markdown';
$a->strings['HTML'] = 'HTML';
$a->strings['Twitter Source / Tweet URL (requires API key)'] = 'Twitter forrás vagy Tweet URL (API-kulcsot igényel)';
$a->strings['You must be logged in to use this module'] = 'Bejelentkezve kell lennie a modul használatához';
$a->strings['Source URL'] = 'Forrás URL';
$a->strings['Time Conversion'] = 'Időátalakítás';
$a->strings['Friendica provides this service for sharing events with other networks and friends in unknown timezones.'] = 'A Friendica ezt a szolgáltatást az ismeretlen időzónákban lévő más hálózatokkal és ismerősökkel történő események megosztásához biztosítja.';
$a->strings['UTC time: %s'] = 'UTC idő: %s';
$a->strings['Current timezone: %s'] = 'Jelenlegi időzóna: %s';
$a->strings['Converted localtime: %s'] = 'Átalakított helyi idő: %s';
$a->strings['Please select your timezone:'] = 'Válassza ki az időzónáját:';
$a->strings['Only logged in users are permitted to perform a probing.'] = 'Csak bejelentkezett felhasználóknak engedélyezett a szondázás végrehajtása.';
$a->strings['Probe Diagnostic'] = 'Szondázási diagnosztika';
$a->strings['Output'] = 'Kimenet';
$a->strings['Lookup address'] = 'Keresési cím';
$a->strings['Webfinger Diagnostic'] = 'WebFinger diagnosztika';
$a->strings['Lookup address:'] = 'Keresési cím:';
$a->strings['No entries (some entries may be hidden).'] = 'Nincsenek bejegyzések (néhány bejegyzés rejtve lehet).';
$a->strings['Find on this site'] = 'Keresés ezen az oldalon';
$a->strings['Results for:'] = 'Találat erre:';
$a->strings['Site Directory'] = 'Oldal könyvtára';
$a->strings['Item was not deleted'] = 'Az elem nem lett törölve';
$a->strings['Item was not removed'] = 'Az elem nem lett eltávolítva';
$a->strings['- select -'] = '– válasszon –';
$a->strings['Suggested contact not found.'] = 'Az ajánlott partner nem található.';
$a->strings['Friend suggestion sent.'] = 'Az ismerősajánlás elküldve.';
$a->strings['Suggest Friends'] = 'Ismerősök ajánlása';
$a->strings['Suggest a friend for %s'] = 'Ismerős ajánlása %s számára';
$a->strings['Installed addons/apps:'] = 'Telepített bővítmények vagy alkalmazások:';
$a->strings['No installed addons/apps'] = 'Nincsenek telepített bővítmények vagy alkalmazások';
$a->strings['Read about the <a href="%1$s/tos">Terms of Service</a> of this node.'] = 'Olvassa el ennek a csomópontnak a <a href="%1$s/tos">használati feltételeit</a>.';
$a->strings['On this server the following remote servers are blocked.'] = 'Ezen a kiszolgálón a következő távoli kiszolgálók vannak tiltva.';
$a->strings['Reason for the block'] = 'A tiltás oka';
$a->strings['Download this list in CSV format'] = 'A lista letöltése CSV formátumban';
$a->strings['This is Friendica, version %s that is running at the web location %s. The database version is %s, the post update version is %s.'] = 'Ez egy %s verziójú Friendica, amely a %s helyen fut a weben. Az adatbázis verziója %s, a bejegyzésfrissítés verziója %s.';
$a->strings['Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.'] = 'Látogassa meg a <a href="https://friendi.ca">Friendi.ca</a> oldalt, hogy többet tudjon meg a Friendica projektről.';
$a->strings['Bug reports and issues: please visit'] = 'Hibák és problémák jelentéséhez látogassa meg';
$a->strings['the bugtracker at github'] = 'a GitHubon lévő hibakövetőt';
$a->strings['Suggestions, praise, etc. - please email "info" at "friendi - dot - ca'] = 'Javaslatokat, dicséretet és egyebeket az „info” kukac friendi pont ca címre küldhet.';
$a->strings['No profile'] = 'Nincs profil';
$a->strings['Method Not Allowed.'] = 'A módszer nem engedélyezett.';
$a->strings['Help:'] = 'Súgó:';
$a->strings['Welcome to %s'] = 'Üdvözli a(z) %s!';
$a->strings['Friendica Communications Server - Setup'] = 'Friendica kommunikációs kiszolgáló – Beállítás';
$a->strings['System check'] = 'Rendszerellenőrzés';
$a->strings['Requirement not satisfied'] = 'A követelmény nincs kielégítve';
$a->strings['Optional requirement not satisfied'] = 'A választható követelmény nincs kielégítve';
$a->strings['OK'] = 'Rendben';
$a->strings['Next'] = 'Következő';
$a->strings['Check again'] = 'Ellenőrzés újra';
$a->strings['Base settings'] = 'Alapvető beállítások';
$a->strings['Base path to installation'] = 'Alap útvonal a telepítéshez';
$a->strings['If the system cannot detect the correct path to your installation, enter the correct path here. This setting should only be set if you are using a restricted system and symbolic links to your webroot.'] = 'Ha a rendszer nem tudja felismerni a helyes útvonalat a telepítéshez, akkor itt adja meg a helyes útvonalat. Ezt a beállítást csak akkor kell megadni, ha korlátozott rendszert és a webgyökérre mutató szimbolikus hivatkozásokat használ.';
$a->strings['The Friendica system URL'] = 'A Friendica rendszer URL-je';
$a->strings['Overwrite this field in case the system URL determination isn\'t right, otherwise leave it as is.'] = 'Abban az esetben írja felül ezt a mezőt, ha a rendszer URL-meghatározása nem megfelelő, egyébként hagyja változatlanul.';
$a->strings['Database connection'] = 'Adatbázis-kapcsolat';
$a->strings['In order to install Friendica we need to know how to connect to your database.'] = 'A Friendica telepítése érdekében tudnunk kell, hogy hogyan kell kapcsolódni az adatbázisához.';
$a->strings['Please contact your hosting provider or site administrator if you have questions about these settings.'] = 'Vegye fel a kapcsolatot a tárhelyszolgáltatóval vagy az oldal adminisztrátorával, ha kérdései vannak ezekkel a beállításokkal kapcsolatban.';
$a->strings['The database you specify below should already exist. If it does not, please create it before continuing.'] = 'A lent megadott adatbázisnak már léteznie kell. Ha még nem létezik, akkor hozza létre a folytatás előtt.';
$a->strings['Database Server Name'] = 'Adatbázis-kiszolgáló neve';
$a->strings['Database Login Name'] = 'Adatbázis bejelentkezési neve';
$a->strings['Database Login Password'] = 'Adatbázis bejelentkezési jelszava';
$a->strings['For security reasons the password must not be empty'] = 'Biztonsági okokból a jelszó nem lehet üres';
$a->strings['Database Name'] = 'Adatbázis neve';
$a->strings['Please select a default timezone for your website'] = 'Válasszon egy alapértelmezett időzónát a weboldalához';
$a->strings['Site settings'] = 'Oldalbeállítások';
$a->strings['Site administrator email address'] = 'Oldal adminisztrátorának e-mail-címe';
$a->strings['Your account email address must match this in order to use the web admin panel.'] = 'A fiókja e-mail-címének egyeznie kell ezzel a webes adminisztrátori panel használata érdekében.';
$a->strings['System Language:'] = 'Rendszer nyelve:';
$a->strings['Set the default language for your Friendica installation interface and to send emails.'] = 'Az alapértelmezett nyelv beállítása a Friendica telepítésnek felületéhez és az e-mailek küldéséhez.';
$a->strings['Your Friendica site database has been installed.'] = 'A Friendica oldalának adatbázisa telepítve lett.';
$a->strings['Installation finished'] = 'A telepítés befejeződött';
$a->strings['<h1>What next</h1>'] = '<h1>Mi következik?</h1>';
$a->strings['IMPORTANT: You will need to [manually] setup a scheduled task for the worker.'] = 'FONTOS: be kell állítania [kézzel] egy ütemezett feladatot a feldolgozóhoz.';
$a->strings['Go to your new Friendica node <a href="%s/register">registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.'] = 'Menjen az új Friendica csomópont <a href="%s/register">regisztrációs oldalára</a>, és regisztráljon új felhasználóként. Ne felejtse el ugyanazt az e-mail-címet használni, mint amelyet adminisztrátori e-mail-címként adott meg. Ez lehetővé fogja tenni az oldal adminisztrátori paneljére történő belépést.';
$a->strings['Total invitation limit exceeded.'] = 'Az összes meghívás korlátja túllépve.';
$a->strings['%s : Not a valid email address.'] = '%s: nem érvényes e-mail-cím.';
$a->strings['Please join us on Friendica'] = 'Csatlakozzon hozzánk a Friendica hálózatán';
$a->strings['Invitation limit exceeded. Please contact your site administrator.'] = 'A meghíváskorlát túllépve. Vegye fel a kapcsolatot az oldal adminisztrátorával.';
$a->strings['%s : Message delivery failed.'] = '%s: az üzenetkézbesítés sikertelen.';
$a->strings['%d message sent.'] = [
	0 => '%d üzenet elküldve.',
	1 => '%d üzenet elküldve.',
];
$a->strings['You have no more invitations available'] = 'Nincs több elérhető meghívása';
$a->strings['Visit %s for a list of public sites that you can join. Friendica members on other sites can all connect with each other, as well as with members of many other social networks.'] = 'Látogassa meg a %s oldalt azon nyilvános oldalak listájáért, amelyekhez csatlakozhat. A egyéb oldalakon lévő Friendica-tagok mindegyike tud egymással kapcsolódni, valamint számos más közösségi hálózat tagjaival is.';
$a->strings['To accept this invitation, please visit and register at %s or any other public Friendica website.'] = 'A meghívás elfogadásához látogasson el és regisztráljon a(z) %s címen vagy bármely más nyilvános Friendica weboldalon.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks. See %s for a list of alternate Friendica sites you can join.'] = 'A Friendica oldalak mindegyike össze van kapcsolva, hogy egy hatalmas, fokozott magánszféra-védelemmel ellátott közösségi hálót hozzanak létre, amelyet a tagjai birtokolnak és vezérelnek. A tagok képesek sok hagyományos közösségi hálózathoz is kapcsolódni. Nézze meg a(z) %s oldalt azon alternatív Friendica oldalak listájáért, amelyekhez csatlakozhat.';
$a->strings['Our apologies. This system is not currently configured to connect with other public sites or invite members.'] = 'Elnézést kérünk. Ez a rendszer jelenleg nem úgy van beállítva, hogy más nyilvános oldalakhoz kapcsolódjon vagy tagokat hívjon meg.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks.'] = 'A Friendica oldalak mindegyike össze van kapcsolva, hogy egy hatalmas, fokozott magánszféra-védelemmel ellátott közösségi hálót hozzanak létre, amelyet a tagjai birtokolnak és vezérelnek. A tagok képesek sok hagyományos közösségi hálózathoz is kapcsolódni.';
$a->strings['To accept this invitation, please visit and register at %s.'] = 'A meghívás elfogadásához látogasson el és regisztráljon a(z) %s címen.';
$a->strings['Send invitations'] = 'Meghívások küldése';
$a->strings['Enter email addresses, one per line:'] = 'Adja meg az e-mail-címeket, soronként egyet:';
$a->strings['You are cordially invited to join me and other close friends on Friendica - and help us to create a better social web.'] = 'Szeretettel meghívom Önt, hogy csatlakozzon hozzám és más közeli ismerősökhöz a Friendica hálózatán – és segítsen nekünk egy jobb közösségi hálót létrehozni.';
$a->strings['You will need to supply this invitation code: $invite_code'] = 'Meg kell majd adnia ezt a meghívási kódot: $invite_code';
$a->strings['Once you have registered, please connect with me via my profile page at:'] = 'Miután regisztrált, lépjen velem kapcsolatba a profiloldalamon keresztül itt:';
$a->strings['For more information about the Friendica project and why we feel it is important, please visit http://friendi.ca'] = 'A Friendica projekttel kapcsolatos további információkért, valamint hogy miért tartjuk ezt fontosnak, látogasson el a https://friendi.ca/ oldalra.';
$a->strings['Please enter a post body.'] = 'Adjon meg egy bejegyzéstörzset.';
$a->strings['This feature is only available with the frio theme.'] = 'Ez a funkció csak a frio témával érhető el.';
$a->strings['Compose new personal note'] = 'Új személyes jegyzet írása';
$a->strings['Compose new post'] = 'Új bejegyzés írása';
$a->strings['Visibility'] = 'Láthatóság';
$a->strings['Clear the location'] = 'A hely törlése';
$a->strings['Location services are unavailable on your device'] = 'A helymeghatározó szolgáltatások nem érhetők el az Ön eszközén';
$a->strings['Location services are disabled. Please check the website\'s permissions on your device'] = 'A helymeghatározó szolgáltatások le vannak tiltva. Ellenőrizze a weboldal jogosultságait az Ön eszközén';
$a->strings['You can make this page always open when you use the New Post button in the <a href="/settings/display">Theme Customization settings</a>.'] = 'Mindig megnyitottá teheti ezt az oldalt, ha a <a href="/settings/display">téma személyre szabási beállításaiban</a> lévő új bejegyzés gombot használja.';
$a->strings['The feed for this item is unavailable.'] = 'Ennek az elemnek a hírforrása nem érhető el.';
$a->strings['Unable to follow this item.'] = 'Nem lehet követni ezt az elemet.';
$a->strings['System down for maintenance'] = 'A rendszer le van kapcsolva a karbantartáshoz';
$a->strings['This Friendica node is currently in maintenance mode, either automatically because it is self-updating or manually by the node administrator. This condition should be temporary, please come back in a few minutes.'] = 'Ez a Friendica csomópont jelenleg karbantartási módban van, vagy automatikusan, mert épp önmagát frissíti, vagy kézileg a csomópont adminisztrátora által. Ennek az állapotnak átmenetinek kell lennie, jöjjön vissza néhány perc múlva.';
$a->strings['A Decentralized Social Network'] = 'Egy decentralizált közösségi hálózat';
$a->strings['You need to be logged in to access this page.'] = 'Bejelentkezve kell lennie az oldalhoz való hozzáféréshez.';
$a->strings['Files'] = 'Fájlok';
$a->strings['Upload'] = 'Feltöltés';
$a->strings['Sorry, maybe your upload is bigger than the PHP configuration allows'] = 'Elnézést, talán a feltöltése nagyobb annál, amit a PHP beállítása megenged';
$a->strings['Or - did you try to upload an empty file?'] = 'Vagy – egy üres fájlt próbált meg feltölteni?';
$a->strings['File exceeds size limit of %s'] = 'A fájl meghaladja a beállított %s méretkorlátot';
$a->strings['File upload failed.'] = 'A fájl feltöltése sikertelen.';
$a->strings['Unable to process image.'] = 'Nem lehet feldolgozni a képet.';
$a->strings['Image upload failed.'] = 'A kép feltöltése sikertelen.';
$a->strings['List of all users'] = 'Összes felhasználó listája';
$a->strings['Active'] = 'Aktív';
$a->strings['List of active accounts'] = 'Aktív fiókok listája';
$a->strings['List of pending registrations'] = 'Függőben lévő regisztrációk listája';
$a->strings['List of blocked users'] = 'Tiltott felhasználók listája';
$a->strings['Deleted'] = 'Törölve';
$a->strings['List of pending user deletions'] = 'Függőben lévő felhasználó-törlések listája';
$a->strings['Normal Account Page'] = 'Normál fiókoldal';
$a->strings['Soapbox Page'] = 'Szappantartó oldal';
$a->strings['Public Group'] = 'Nyilvános csoport';
$a->strings['Public Group - Restricted'] = 'Nyilvános csoport – korlátozott';
$a->strings['Automatic Friend Page'] = 'Automatikus ismerős oldal';
$a->strings['Private Group'] = 'Személyes csoport';
$a->strings['Personal Page'] = 'Személyes oldal';
$a->strings['Organisation Page'] = 'Szervezeti oldal';
$a->strings['News Page'] = 'Hírek oldal';
$a->strings['Community Group'] = 'Közösségi csoport';
$a->strings['You can\'t block a local contact, please block the user instead'] = 'Nem tilthat egy helyi partnert, inkább a felhasználót tiltsa';
$a->strings['%s contact unblocked'] = [
	0 => '%s partner tiltása feloldva',
	1 => '%s partner tiltása feloldva',
];
$a->strings['Remote Contact Blocklist'] = 'Távoli partnerek tiltólistája';
$a->strings['This page allows you to prevent any message from a remote contact to reach your node.'] = 'Ez az oldal lehetővé teszi, hogy megakadályozzon egy távoli partnertől származó minden üzenetet, amely elérné a csomópontját.';
$a->strings['Block Remote Contact'] = 'Távoli partner tiltása';
$a->strings['select all'] = 'összes kijelölése';
$a->strings['select none'] = 'kijelölés törlése';
$a->strings['No remote contact is blocked from this node.'] = 'Nincs olyan távoli partner, aki tiltva lenne erről a csomópontról.';
$a->strings['Blocked Remote Contacts'] = 'Tiltott távoli partnerek';
$a->strings['Block New Remote Contact'] = 'Új távoli partner tiltása';
$a->strings['Photo'] = 'Fénykép';
$a->strings['Reason'] = 'Indok';
$a->strings['%s total blocked contact'] = [
	0 => 'Összesen %s tiltott partner',
	1 => 'Összesen %s tiltott partner',
];
$a->strings['URL of the remote contact to block.'] = 'A tiltandó távoli partner URL-je.';
$a->strings['Also purge contact'] = 'Távolítsa el a partnert is';
$a->strings['Removes all content related to this contact from the node. Keeps the contact record. This action cannot be undone.'] = 'Eltávolítja az ehhez a partnerhez kapcsolódó összes partnert a csomópontról. Megtartja a partner rekordját. Ezt a műveletet nem lehet visszavonni.';
$a->strings['Block Reason'] = 'Tiltás oka';
$a->strings['Server domain pattern added to the blocklist.'] = 'A tiltólistához hozzáadott kiszolgáló tartománymintája.';
$a->strings['%s server scheduled to be purged.'] = [
	0 => '%s kiszolgáló van ütemezve eltávolításra.',
	1 => '%s kiszolgáló van ütemezve eltávolításra.',
];
$a->strings['← Return to the list'] = '← Vissza a listához';
$a->strings['Block A New Server Domain Pattern'] = 'Új kiszolgálótartomány-minta tiltása';
$a->strings['<p>The server domain pattern syntax is case-insensitive shell wildcard, comprising the following special characters:</p>
<ul>
	<li><code>*</code>: Any number of characters</li>
	<li><code>?</code>: Any single character</li>
</ul>'] = '<p>A kiszolgálótartomány-minta szintaxisa kis- és nagybetű-érzéketlen parancsértelmezői helyettesítő karakter, amely a következő különleges karakterekből áll:</p>
<ul>
	<li><code>*</code>: Tetszőleges számú karakter</li>
	<li><code>?</code>: Egy önálló karakter</li>
</ul>';
$a->strings['Check pattern'] = 'Minta ellenőrzése';
$a->strings['Matching known servers'] = 'Illeszkedő ismert kiszolgálók';
$a->strings['Server Name'] = 'Kiszolgáló neve';
$a->strings['Server Domain'] = 'Kiszolgáló tartománya';
$a->strings['Known Contacts'] = 'Ismert partnerek';
$a->strings['%d known server'] = [
	0 => '%d ismert kiszolgáló',
	1 => '%d ismert kiszolgáló',
];
$a->strings['Add pattern to the blocklist'] = 'Minta hozzáadása a tiltólistához';
$a->strings['Server Domain Pattern'] = 'Kiszolgálótartomány-minta';
$a->strings['The domain pattern of the new server to add to the blocklist. Do not include the protocol.'] = 'A tiltólistához hozzáadandó új kiszolgáló tartományának mintája. Ne tegye bele a protokollt.';
$a->strings['Purge server'] = 'Kiszolgáló eltávolítása';
$a->strings['Also purges all the locally stored content authored by the known contacts registered on that server. Keeps the contacts and the server records. This action cannot be undone.'] = [
	0 => 'Eltávolítja az összes olyan helyileg tárolt tartalmat is, amelyet az adott kiszolgálón regisztrált ismert partnerek hoztak létre. Megtartja a partnereket és a kiszolgáló rekordjait. Ezt a művelet nem lehet visszavonni.',
	1 => 'Eltávolítja az összes olyan helyileg tárolt tartalmat is, amelyet az adott kiszolgálókon regisztrált ismert partnerek hoztak létre. Megtartja a partnereket és a kiszolgálók rekordjait. Ezt a művelet nem lehet visszavonni.',
];
$a->strings['Block reason'] = 'Tiltás oka';
$a->strings['The reason why you blocked this server domain pattern. This reason will be shown publicly in the server information page.'] = 'Az ok, amiért kitiltotta ezt a kiszolgálótartomány-mintát. Az ok nyilvánosan meg lesz jelenítve a kiszolgáló információs oldalán.';
$a->strings['Error importing pattern file'] = 'Hiba a mintafájl importálásakor';
$a->strings['Local blocklist replaced with the provided file.'] = 'A helyi tiltólista le lett cserélve a megadott fájllal.';
$a->strings['%d pattern was added to the local blocklist.'] = [
	0 => '%d minta lett hozzáadva a helyi tiltólistához.',
	1 => '%d minta lett hozzáadva a helyi tiltólistához.',
];
$a->strings['No pattern was added to the local blocklist.'] = 'Nem lett minta hozzáadva a helyi tiltólistához.';
$a->strings['Import a Server Domain Pattern Blocklist'] = 'Kiszolgálótartomány-minta tiltólista importálása';
$a->strings['<p>This file can be downloaded from the <code>/friendica</code> path of any Friendica server.</p>'] = '<p>Ez a fájl letölthető bármely Friendica kiszolgáló <code>/friendica</code> útvonaláról.</p>';
$a->strings['Upload file'] = 'Fájl feltöltése';
$a->strings['Patterns to import'] = 'Importálandó minták';
$a->strings['Domain Pattern'] = 'Tartományminta';
$a->strings['Import Mode'] = 'Mód importálása';
$a->strings['Import Patterns'] = 'Minták importálása';
$a->strings['%d total pattern'] = [
	0 => '%d minta összesen',
	1 => '%d minta összesen',
];
$a->strings['Server domain pattern blocklist CSV file'] = 'Kiszolgálótartomány-minta tiltólista CSV-fájl';
$a->strings['Append'] = 'Hozzáfűzés';
$a->strings['Imports patterns from the file that weren\'t already existing in the current blocklist.'] = 'Olyan mintákat importál a fájlból, amelyek még nem léteztek a jelenlegi tiltólistán.';
$a->strings['Replace'] = 'Csere';
$a->strings['Replaces the current blocklist by the imported patterns.'] = 'Lecseréli a jelenlegi tiltólistát az importált mintákkal.';
$a->strings['Blocked server domain pattern'] = 'Tiltott kiszolgálótartomány-minta';
$a->strings['Delete server domain pattern'] = 'Kiszolgálótartomány-minta törlése';
$a->strings['Check to delete this entry from the blocklist'] = 'Jelölje be a bejegyzés tiltólistából való törléséhez';
$a->strings['Server Domain Pattern Blocklist'] = 'Kiszolgálótartomány-minta tiltólistája';
$a->strings['This page can be used to define a blocklist of server domain patterns from the federated network that are not allowed to interact with your node. For each domain pattern you should also provide the reason why you block it.'] = 'Ez az oldal használható a föderált hálózatból származó azon kiszolgálótartomány-minták tiltólistájának meghatározásához, amelyeknek nem engedélyezett kapcsolatba lépniük az Ön csomópontjával. Minden egyes tartománymintához meg kell adnia az indokot is, hogy miért tiltja azt.';
$a->strings['The list of blocked server domain patterns will be made publically available on the <a href="/friendica">/friendica</a> page so that your users and people investigating communication problems can find the reason easily.'] = 'A tiltott kiszolgálótartomány-minták listája nyilvánosan elérhetővé lesz téve a <a href="/friendica">/friendica</a> oldalon, azért hogy a kommunikációs problémákat kivizsgáló felhasználók és emberek egyszerűen megtalálják az okot.';
$a->strings['Import server domain pattern blocklist'] = 'Kiszolgálótartomány-minta tiltólistájának importálása';
$a->strings['Add new entry to the blocklist'] = 'Új bejegyzés hozzáadása a tiltólistához';
$a->strings['Save changes to the blocklist'] = 'Változtatások mentése a tiltólistába';
$a->strings['Current Entries in the Blocklist'] = 'Jelenlegi bejegyzések a tiltólistán';
$a->strings['Delete entry from the blocklist'] = 'Bejegyzés törlése a tiltólistáról';
$a->strings['Delete entry from the blocklist?'] = 'Törli a bejegyzést a tiltólistáról?';
$a->strings['Item marked for deletion.'] = 'Az elem megjelölve törlésre.';
$a->strings['Delete this Item'] = 'Az elem törlése';
$a->strings['On this page you can delete an item from your node. If the item is a top level posting, the entire thread will be deleted.'] = 'Ezen az oldalon törölhet egy elemet a csomópontjáról. Ha az elem egy felső szintű beküldés, akkor a teljes szál törlésre fog kerülni.';
$a->strings['You need to know the GUID of the item. You can find it e.g. by looking at the display URL. The last part of http://example.com/display/123456 is the GUID, here 123456.'] = 'Tudnia kell az elem GUID értékét. Ezt megtalálhatja például a megjelenített URL-re tekintve. A http://example.com/display/123456 utolsó része a GUID, itt az 123456.';
$a->strings['GUID'] = 'GUID';
$a->strings['The GUID of the item you want to delete.'] = 'Annak az elemnek GUID értéke, amelyet törölni szeretne.';
$a->strings['Item Id'] = 'Elemazonosító';
$a->strings['Item URI'] = 'Elem URI';
$a->strings['Terms'] = 'Kifejezések';
$a->strings['Tag'] = 'Címke';
$a->strings['Type'] = 'Típus';
$a->strings['Term'] = 'Kifejezés';
$a->strings['URL'] = 'URL';
$a->strings['Implicit Mention'] = 'Implicit említés';
$a->strings['Item not found'] = 'Az elem nem található';
$a->strings['No source recorded'] = 'Nincs forrás rögzítve';
$a->strings['Please make sure the <code>debug.store_source</code> config key is set in <code>config/local.config.php</code> for future items to have sources.'] = 'Győződjön meg arról, hogy a <code>debug.store_source</code> beállítási kulcs be van-e állítva a <code>config/local.config.php</code> fájlban, hogy a jövőbeli elemek forrásokkal rendelkezzenek.';
$a->strings['Item Guid'] = 'Elem GUID értéke';
$a->strings['Contact not found or their server is already blocked on this node.'] = 'A partner nem található, vagy a kiszolgálója már tiltva van ezen a csomóponton.';
$a->strings['Please login to access this page.'] = 'Jelentkezzen be az oldal eléréséhez.';
$a->strings['Create Moderation Report'] = 'Moderálási jelentés létrehozása';
$a->strings['Pick Contact'] = 'Partner kiválasztása';
$a->strings['Please enter below the contact address or profile URL you would like to create a moderation report about.'] = 'Adja meg lent a partner címét vagy a profiljának URL-jét, amelyről moderálási jelentést szeretne létrehozni.';
$a->strings['Contact address/URL'] = 'Partner címe vagy URL-je';
$a->strings['Pick Category'] = 'Kategória kiválasztása';
$a->strings['Please pick below the category of your report.'] = 'Válassza ki lent a jelentés kategóriáját.';
$a->strings['Spam'] = 'Kéretlen üzenet';
$a->strings['This contact is publishing many repeated/overly long posts/replies or advertising their product/websites in otherwise irrelevant conversations.'] = 'Ez a partner sok ismétlődő vagy túl hosszú bejegyzést vagy választ tesz közzé, illetve egyébként nem kapcsolódó beszélgetésekben reklámozza a termékét vagy weboldalait.';
$a->strings['Illegal Content'] = 'Illegális tartalom';
$a->strings['This contact is publishing content that is considered illegal in this node\'s hosting juridiction.'] = 'Ez a partner olyan tartalmat tesz közzé, amely a csomópont tárhelyének joghatósága szerint illegálisnak minősül.';
$a->strings['Community Safety'] = 'Közösségi biztonság';
$a->strings['This contact aggravated you or other people, by being provocative or insensitive, intentionally or not. This includes disclosing people\'s private information (doxxing), posting threats or offensive pictures in posts or replies.'] = 'Ez a partner provokációval vagy érzéketlenséggel, szándékosan vagy akaratlanul, de felbosszantotta Önt vagy másokat. Ebbe beletartozik az emberek személyes adatainak felfedése (doxolás), fenyegetések vagy sértő képek közzététele a bejegyzésekben vagy válaszokban.';
$a->strings['Unwanted Content/Behavior'] = 'Nemkívánatos tartalom vagy viselkedés';
$a->strings['This contact has repeatedly published content irrelevant to the node\'s theme or is openly criticizing the node\'s administration/moderation without directly engaging with the relevant people for example or repeatedly nitpicking on a sensitive topic.'] = 'Ez a partner ismételten a csomópont témájához nem kapcsolódó tartalmakat tesz közzé, nyíltan kritizálja a csomópont adminisztrációját és moderálását, anélkül hogy például közvetlenül kapcsolatba lépett volna az érintettekkel, vagy ismételten feszeget egy érzékeny témát.';
$a->strings['Rules Violation'] = 'Szabályok megszegése';
$a->strings['This contact violated one or more rules of this node. You will be able to pick which one(s) in the next step.'] = 'Ez a partner megszegte a csomópont egy vagy több szabályát. A következő lépésben kiválaszthatja, hogy melyeket.';
$a->strings['Please elaborate below why you submitted this report. The more details you provide, the better your report can be handled.'] = 'Az alábbiakban részletezze, hogy miért küldte be ezt a jelentést. Minél több részletet ad meg, annál jobban lehet kezelni a jelentését.';
$a->strings['Additional Information'] = 'További információk';
$a->strings['Please provide any additional information relevant to this particular report. You will be able to attach posts by this contact in the next step, but any context is welcome.'] = 'Adjon meg bármilyen további információt, amely az adott jelentéssel kapcsolatos. A következő lépésben csatolhatja az ettől a partnertől származó bejegyzéseket, de bármilyen további információt is szívesen fogadunk.';
$a->strings['Pick Rules'] = 'Szabályok kiválasztása';
$a->strings['Please pick below the node rules you believe this contact violated.'] = 'Válassza ki az alábbiakban azokat a csomópontszabályokat, amelyeket Ön szerint a partner megszegett.';
$a->strings['Pick Posts'] = 'Bejegyzések kiválasztása';
$a->strings['Please optionally pick posts to attach to your report.'] = 'Esetlegesen válassza ki a jelentéséhez csatolandó bejegyzéseket.';
$a->strings['Submit Report'] = 'Jelentés elküldése';
$a->strings['Further Action'] = 'További művelet';
$a->strings['You can also perform one of the following action on the contact you reported:'] = 'Az alábbi műveletek egyikét is végrehajthatja a jelentett partnerrel kapcsolatban:';
$a->strings['Nothing'] = 'Semmi';
$a->strings['Collapse contact'] = 'Partner összecsukása';
$a->strings['Their posts and replies will keep appearing in your Network page but their content will be collapsed by default.'] = 'A bejegyzéseik és válaszaik továbbra is megjelennek a hálózat oldalon, de a tartalmuk alapértelmezetten össze lesz csukva.';
$a->strings['Their posts won\'t appear in your Network page anymore, but their replies can appear in forum threads. They still can follow you.'] = 'A bejegyzéseik nem jelennek meg többé a hálózat oldalon, de a válaszaik megjelenhetnek a fórum szálaiban. Továbbra is követhetik Önt.';
$a->strings['Block contact'] = 'Partner tiltása';
$a->strings['Their posts won\'t appear in your Network page anymore, but their replies can appear in forum threads, with their content collapsed by default. They cannot follow you but still can have access to your public posts by other means.'] = 'A bejegyzéseik nem jelennek meg többé a hálózat oldalon, de a válaszaik megjelenhetnek a fórum szálaiban alapértelmezetten összecsukott tartalommal. Nem követhetik Önt, de más módon továbbra is hozzáférhetnek az Ön nyilvános bejegyzéseihez.';
$a->strings['Forward report'] = 'Jelentés továbbítása';
$a->strings['Would you ike to forward this report to the remote server?'] = 'Szeretné továbbítani ezt a jelentést a távoli kiszolgálóra?';
$a->strings['1. Pick a contact'] = '1. Partner kiválasztása';
$a->strings['2. Pick a category'] = '2. Kategória kiválasztása';
$a->strings['2a. Pick rules'] = '2a. Szabályok kiválasztása';
$a->strings['2b. Add comment'] = '2b. Megjegyzés hozzáadása';
$a->strings['3. Pick posts'] = '3. Bejegyzések kiválasztása';
$a->strings['List of reports'] = 'Jelentések listája';
$a->strings['This page display reports created by our or remote users.'] = 'Ez az oldal a saját vagy a távoli felhasználók által létrehozott jelentéseket jeleníti meg.';
$a->strings['No report exists at this node.'] = 'Nem létezik jelentés ezen a csomóponton.';
$a->strings['Category'] = 'Kategória';
$a->strings['%s total report'] = [
	0 => '%s jelentés összesen',
	1 => '%s jelentés összesen',
];
$a->strings['URL of the reported contact.'] = 'A jelentett partner URL-je.';
$a->strings['Channel Relay'] = 'Csatornatovábbítás';
$a->strings['Registered users'] = 'Regisztrált felhasználók';
$a->strings['Pending registrations'] = 'Függőben lévő regisztrációk';
$a->strings['%s user blocked'] = [
	0 => '%s felhasználó tiltva',
	1 => '%s felhasználó tiltva',
];
$a->strings['You can\'t remove yourself'] = 'Nem távolíthatja el önmagát';
$a->strings['%s user deleted'] = [
	0 => '%s felhasználó törölve',
	1 => '%s felhasználó törölve',
];
$a->strings['Register date'] = 'Regisztráció dátuma';
$a->strings['Last login'] = 'Utolsó bejelentkezés';
$a->strings['Last public item'] = 'Utolsó nyilvános elem';
$a->strings['Active Accounts'] = 'Aktív fiókok';
$a->strings['User blocked'] = 'Felhasználó tiltva';
$a->strings['Site admin'] = 'Oldal adminisztrátor';
$a->strings['Account expired'] = 'A fiók lejárt';
$a->strings['Create a new user'] = 'Új felhasználó létrehozása';
$a->strings['Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'] = 'A kijelölt felhasználók törölve lesznek!\n\nMinden, amit ezek a felhasználók erre az oldalra beküldtek, véglegesen törölve lesz!\n\nBiztos benne?';
$a->strings['The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'] = '{0} felhasználó törölve lesz!\n\nMinden, amit ez a felhasználó erre az oldalra beküldött, véglegesen törölve lesz!\n\nBiztos benne?';
$a->strings['User "%s" deleted'] = '„%s” felhasználó törölve';
$a->strings['User "%s" blocked'] = '„%s” felhasználó tiltva';
$a->strings['%s user unblocked'] = [
	0 => '%s felhasználó tiltása feloldva',
	1 => '%s felhasználó tiltása feloldva',
];
$a->strings['Blocked Users'] = 'Tiltott felhasználók';
$a->strings['User "%s" unblocked'] = '„%s” felhasználó tiltása feloldva';
$a->strings['New User'] = 'Új felhasználó';
$a->strings['Add User'] = 'Felhasználó hozzáadása';
$a->strings['Name of the new user.'] = 'Az új felhasználó neve.';
$a->strings['Nickname'] = 'Becenév';
$a->strings['Nickname of the new user.'] = 'Az új felhasználó beceneve.';
$a->strings['Email address of the new user.'] = 'Az új felhasználó e-mail-címe.';
$a->strings['Users awaiting permanent deletion'] = 'Végleges törlésre váró felhasználók';
$a->strings['Permanent deletion'] = 'Végleges törlés';
$a->strings['User waiting for permanent deletion'] = 'Végleges törlésre váró felhasználó';
$a->strings['%s user approved'] = [
	0 => '%s felhasználó jóváhagyva',
	1 => '%s felhasználó jóváhagyva',
];
$a->strings['%s registration revoked'] = [
	0 => '%s regisztráció visszavonva',
	1 => '%s regisztráció visszavonva',
];
$a->strings['Account approved.'] = 'Fiók jóváhagyva.';
$a->strings['Registration revoked'] = 'Regisztráció visszavonva';
$a->strings['User registrations awaiting review'] = 'Felülvizsgálatra váró felhasználói regisztrációk';
$a->strings['Request date'] = 'Kérés dátuma';
$a->strings['No registrations.'] = 'Nincsenek regisztrációk.';
$a->strings['Note from the user'] = 'Jegyzet a felhasználótól';
$a->strings['Deny'] = 'Elutasítás';
$a->strings['Show Ignored Requests'] = 'Mellőzött kérések megjelenítése';
$a->strings['Hide Ignored Requests'] = 'Mellőzött kérések elrejtése';
$a->strings['Notification type:'] = 'Értesítés típusa:';
$a->strings['Suggested by:'] = 'Ajánlotta:';
$a->strings['Claims to be known to you: '] = 'Azt állítja, hogy Ön ismeri: ';
$a->strings['Shall your connection be bidirectional or not?'] = 'Legyen a kapcsolata kétirányú vagy sem?';
$a->strings['Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.'] = '%s ismerősként való elfogadása lehetővé teszi %s számára, hogy feliratkozzon a bejegyzéseire, és Ön is frissítéseket fog kapni tőle a hírforrásában.';
$a->strings['Accepting %s as a subscriber allows them to subscribe to your posts, but you will not receive updates from them in your news feed.'] = '%s feliratkozóként való elfogadása lehetővé teszi számára, hogy feliratkozzon a bejegyzéseire, de Ön nem fog frissítéseket kapni tőle a hírforrásában.';
$a->strings['Friend'] = 'Ismerős';
$a->strings['Subscriber'] = 'Feliratkozó';
$a->strings['No introductions.'] = 'Nincsenek bemutatkozások.';
$a->strings['No more %s notifications.'] = 'Nincs több %s értesítés.';
$a->strings['You must be logged in to show this page.'] = 'Bejelentkezve kell lennie az oldal megtekintéséhez.';
$a->strings['Network Notifications'] = 'Hálózati értesítések';
$a->strings['System Notifications'] = 'Rendszerértesítések';
$a->strings['Personal Notifications'] = 'Személyes értesítések';
$a->strings['Home Notifications'] = 'Saját értesítések';
$a->strings['Show unread'] = 'Olvasatlanok megjelenítése';
$a->strings['{0} requested registration'] = '{0} regisztrációt kért';
$a->strings['{0} and %d others requested registration'] = '{0} és még %d személy regisztrációt kért';
$a->strings['Authorize application connection'] = 'Alkalmazáskapcsolat felhatalmazása';
$a->strings['Do you want to authorize this application to access your posts and contacts, and/or create new posts for you?'] = 'Szeretné felhatalmazni ezt az alkalmazást, hogy hozzáférjen a bejegyzéseihez és a partnereihez, és/vagy új bejegyzéseket hozzon létre Önnek?';
$a->strings['Unsupported or missing response type'] = 'Nem támogatott vagy hiányzó választípus';
$a->strings['Incomplete request data'] = 'Befejezetlen kérésadat';
$a->strings['Please copy the following authentication code into your application and close this window: %s'] = 'Másolja be a következő hitelesítési kódot az alkalmazásába, és zárja be ezt az ablakot: %s';
$a->strings['Invalid data or unknown client'] = 'Érvénytelen adatok vagy ismeretlen ügyfél';
$a->strings['Unsupported or missing grant type'] = 'Nem támogatott vagy hiányzó felhatalmazástípus';
$a->strings['Subscribing to contacts'] = 'Feliratkozás a partnerekre';
$a->strings['No contact provided.'] = 'Nincs partner megadva.';
$a->strings['Couldn\'t fetch information for contact.'] = 'Nem sikerült lekérni a partner információit.';
$a->strings['Couldn\'t fetch friends for contact.'] = 'Nem sikerült lekérni a partner ismerőseit.';
$a->strings['Couldn\'t fetch following contacts.'] = 'Nem sikerült lekérni a következő partnereket.';
$a->strings['Couldn\'t fetch remote profile.'] = 'Nem sikerült lekérni a távoli profilt.';
$a->strings['Unsupported network'] = 'Nem támogatott hálózat';
$a->strings['Done'] = 'Kész';
$a->strings['success'] = 'sikeres';
$a->strings['failed'] = 'sikertelen';
$a->strings['ignored'] = 'mellőzve';
$a->strings['Keep this window open until done.'] = 'Tartsa nyitva ezt az ablakot, amíg el nem készül.';
$a->strings['Search in Friendica %s'] = 'Keresés itt: Friendica %s';
$a->strings['The Photo is not available.'] = 'A fénykép nem érhető el.';
$a->strings['The Photo with id %s is not available.'] = 'A(z) %s azonosítóval rendelkező fénykép nem érhető el.';
$a->strings['Invalid external resource with url %s.'] = 'Érvénytelen külső erőforrás a(z) %s URL-lel.';
$a->strings['Invalid photo with id %s.'] = 'Érvénytelen %s azonosítóval rendelkező fénykép.';
$a->strings['Post not found.'] = 'A bejegyzés nem található.';
$a->strings['Edit post'] = 'Bejegyzés szerkesztése';
$a->strings['web link'] = 'webhivatkozás';
$a->strings['Insert video link'] = 'Videohivatkozás beszúrása';
$a->strings['video link'] = 'videohivatkozás';
$a->strings['Insert audio link'] = 'Hanghivatkozás beszúrása';
$a->strings['audio link'] = 'hanghivatkozás';
$a->strings['Remove Item Tag'] = 'Elem címkéjének eltávolítása';
$a->strings['Select a tag to remove: '] = 'Eltávolítandó címke kiválasztása: ';
$a->strings['Remove'] = 'Eltávolítás';
$a->strings['Wrong type "%s", expected one of: %s'] = 'Hibás típus: „%s”, a következők egyike várt: %s';
$a->strings['Model not found'] = 'A modell nem található';
$a->strings['Unlisted'] = 'Listázatlan';
$a->strings['Remote privacy information not available.'] = 'A távoli adatvédelmi információk nem érhetők el.';
$a->strings['Visible to:'] = 'Látható nekik:';
$a->strings['CC:'] = 'Másolat:';
$a->strings['BCC:'] = 'Rejtett másolat:';
$a->strings['Audience:'] = 'Célközönség:';
$a->strings['Attributed To:'] = 'Neki tulajdonítható:';
$a->strings['Collection (%s)'] = 'Gyűjtemény (%s)';
$a->strings['Followers (%s)'] = 'Követők (%s)';
$a->strings['%d more'] = '%d további';
$a->strings['No contacts.'] = 'Nincsenek partnerek.';
$a->strings['%s\'s posts'] = '%s bejegyzései';
$a->strings['%s\'s comments'] = '%s hozzászólásai';
$a->strings['%s\'s timeline'] = '%s idővonala';
$a->strings['Image exceeds size limit of %s'] = 'A kép meghaladja a beállított %s méretkorlátot';
$a->strings['Image upload didn\'t complete, please try again'] = 'A kép feltöltése nem fejeződött be, próbálja újra';
$a->strings['Image file is missing'] = 'A képfájl hiányzik';
$a->strings['Server can\'t accept new file upload at this time, please contact your administrator'] = 'A kiszolgáló jelenleg nem tud új fájlfeltöltést fogadni, vegye fel a kapcsolatot a rendszergazdával';
$a->strings['Image file is empty.'] = 'A képfájl üres.';
$a->strings['View Album'] = 'Album megtekintése';
$a->strings['Profile not found.'] = 'A profil nem található.';
$a->strings['You\'re currently viewing your profile as <b>%s</b> <a href="%s" class="btn btn-sm pull-right">Cancel</a>'] = 'A profilját jelenleg <b>%s</b> nevében nézi <a href="%s" class="btn btn-sm pull-right">Mégse</a>';
$a->strings['Full Name:'] = 'Teljes név:';
$a->strings['Member since:'] = 'Ekkortól tag:';
$a->strings['j F, Y'] = 'Y. F j.';
$a->strings['j F'] = 'F j.';
$a->strings['Birthday:'] = 'Születésnap:';
$a->strings['Age: '] = 'Életkor: ';
$a->strings['%d year old'] = [
	0 => '%d éves',
	1 => '%d éves',
];
$a->strings['Description:'] = 'Leírás:';
$a->strings['Groups:'] = 'Csoportok:';
$a->strings['View profile as:'] = 'Profil megtekintése másként:';
$a->strings['View as'] = 'Megtekintés másként';
$a->strings['Profile unavailable.'] = 'A profil nem érhető el.';
$a->strings['Invalid locator'] = 'Érvénytelen kereső';
$a->strings['The provided profile link doesn\'t seem to be valid'] = 'A megadott profilhivatkozás nem tűnik érvényesnek';
$a->strings['Remote subscription can\'t be done for your network. Please subscribe directly on your system.'] = 'A távoli feliratkozást nem lehet elvégezni az Ön hálózatánál. Iratkozzon fel közvetlenül a saját rendszerén.';
$a->strings['Friend/Connection Request'] = 'Ismerős- vagy kapcsolódási kérés';
$a->strings['Enter your Webfinger address (user@domain.tld) or profile URL here. If this isn\'t supported by your system, you have to subscribe to <strong>%s</strong> or <strong>%s</strong> directly on your system.'] = 'Adja meg itt a WebFinger-címét (felhasználó@tartomány.tld) vagy a profil URL-jét. Ha ezt nem támogatja a rendszere, akkor fel kell iratkoznia a(z) <strong>%s</strong> vagy a(z) <strong>%s</strong> címre közvetlenül a rendszerén.';
$a->strings['If you are not yet a member of the free social web, <a href="%s">follow this link to find a public Friendica node and join us today</a>.'] = 'Ha még nem tagja a szabad közösségi hálónak, akkor <a href="%s">kövesse ezt a hivatkozást egy nyilvános Friendica csomópont kereséséhez, és csatlakozzon hozzánk még ma</a>.';
$a->strings['Your Webfinger address or profile URL:'] = 'A WebFinger-címe vagy profil URL-je:';
$a->strings['Restricted profile'] = 'Korlátozott profil';
$a->strings['This profile has been restricted which prevents access to their public content from anonymous visitors.'] = 'Ez a profil korlátozva lett, ami megakadályozza, hogy a névtelen látogatók hozzáférjenek a nyilvános tartalmához.';
$a->strings['Scheduled'] = 'Ütemezett';
$a->strings['Content'] = 'Tartalom';
$a->strings['Remove post'] = 'Bejegyzés eltávolítása';
$a->strings['Only parent users can create additional accounts.'] = 'Csak fölérendelt felhasználók hozhatnak létre további fiókokat.';
$a->strings['This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.'] = 'Ez az oldal túllépte a fiókregisztrációk naponta megengedett számát. Próbálja újra holnap.';
$a->strings['You may (optionally) fill in this form via OpenID by supplying your OpenID and clicking "Register".'] = 'Kitöltheti ezt az űrlapot OpenID használatán keresztül is az OpenID azonosítója megadásával és „Regisztráció” gombra kattintva (nem kötelező).';
$a->strings['If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.'] = 'Ha nem ismeri az OpenID-t, akkor hagyja a mezőt üresen, és töltse ki a többi elemet.';
$a->strings['Your OpenID (optional): '] = 'Az Ön OpenID-ja (opcionális): ';
$a->strings['Include your profile in member directory?'] = 'Felveszi a profilját a tagkönyvtárba?';
$a->strings['Note for the admin'] = 'Jegyzet az adminisztrátornak';
$a->strings['Leave a message for the admin, why you want to join this node'] = 'Hagyjon üzenetet az adminisztrátornak, hogy miért szeretne ehhez a csomóponthoz csatlakozni';
$a->strings['Membership on this site is by invitation only.'] = 'Ezen az oldalon a tagság csak meghívás alapján van.';
$a->strings['Your invitation code: '] = 'A meghívási kódja: ';
$a->strings['Please repeat your e-mail address:'] = 'Ismételje meg az e-mail-címét:';
$a->strings['New Password:'] = 'Új jelszó:';
$a->strings['Leave empty for an auto generated password.'] = 'Hagyja üresen egy automatikusan előállított jelszóhoz.';
$a->strings['Confirm:'] = 'Megerősítés:';
$a->strings['Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be "<strong>nickname@%s</strong>".'] = 'Válasszon profilbecenevet. Ennek betűvel kell kezdődnie. Ezután a profilcíme ezen az oldalon „<strong>becenév@%s</strong>” lesz.';
$a->strings['Choose a nickname: '] = 'Becenév választása: ';
$a->strings['Import'] = 'Importálás';
$a->strings['Import your profile to this friendica instance'] = 'A profilja importálása erre a Friendica példányra';
$a->strings['Note: This node explicitly contains adult content'] = 'Megjegyzés: ez a csomópont kifejezetten tartalmaz felnőtt tartalmat';
$a->strings['Parent Password:'] = 'Fölérendelt jelszó:';
$a->strings['Please enter the password of the parent account to legitimize your request.'] = 'Adja meg a fölérendelt fiók jelszavát a kérése törvényesítéséhez.';
$a->strings['Password doesn\'t match.'] = 'A jelszó nem egyezik.';
$a->strings['Please enter your password.'] = 'Adja meg a jelszavát.';
$a->strings['You have entered too much information.'] = 'Túl sok információt adott meg.';
$a->strings['Please enter the identical mail address in the second field.'] = 'Adja meg a megegyező e-mail-címet a második mezőben.';
$a->strings['Nickname cannot start with a digit.'] = 'A becenév nem kezdődhet számmal.';
$a->strings['Nickname can only contain US-ASCII characters.'] = 'A becenév csak US-ASCII karaktereket tartalmazhat.';
$a->strings['The additional account was created.'] = 'A további fiók létre lett hozva.';
$a->strings['Registration successful. Please check your email for further instructions.'] = 'A regisztráció sikerült. Nézze meg a postafiókját a további utasításokért.';
$a->strings['Failed to send email message. Here your accout details:<br> login: %s<br> password: %s<br><br>You can change your password after login.'] = 'Nem sikerült elküldeni az e-mail üzenetet. Itt vannak a fiók részletei:<br> Bejelentkezés: %s<br> Jelszó: %s<br><br>A jelszavát bejelentkezés után változtathatja meg.';
$a->strings['Registration successful.'] = 'A regisztráció sikerült.';
$a->strings['Your registration can not be processed.'] = 'A regisztrációját nem lehet feldolgozni.';
$a->strings['You have to leave a request note for the admin.'] = 'Hagynia kell egy kérelmi jegyzetet az adminisztrátornak.';
$a->strings['An internal error occured.'] = 'Belső hiba történt.';
$a->strings['Your registration is pending approval by the site owner.'] = 'A regisztrációja jóváhagyásra vár az oldal tulajdonosától.';
$a->strings['You must be logged in to use this module.'] = 'Bejelentkezve kell lennie a modul használatához.';
$a->strings['Only logged in users are permitted to perform a search.'] = 'Csak bejelentkezett felhasználóknak engedélyezett a keresés végrehajtása.';
$a->strings['Only one search per minute is permitted for not logged in users.'] = 'Percenként csak egy keresés engedélyezett a nem bejelentkezett felhasználóknak.';
$a->strings['Items tagged with: %s'] = 'Ezzel címkézett elemek: %s';
$a->strings['Search term was not saved.'] = 'A keresési kifejezés nem lett elmentve.';
$a->strings['Search term already saved.'] = 'A keresési kifejezés már el van mentve.';
$a->strings['Search term was not removed.'] = 'A keresési kifejezés nem lett eltávolítva.';
$a->strings['Create a New Account'] = 'Új fiók létrehozása';
$a->strings['Your OpenID: '] = 'Az Ön OpenID-ja: ';
$a->strings['Please enter your username and password to add the OpenID to your existing account.'] = 'Adja meg a felhasználónevét és a jelszavát, hogy hozzáadja az OpenID azonosítóját a meglévő fiókjához.';
$a->strings['Or login using OpenID: '] = 'Vagy bejelentkezés OpenID használatával: ';
$a->strings['Password: '] = 'Jelszó: ';
$a->strings['Remember me'] = 'Emlékezzen rám';
$a->strings['Forgot your password?'] = 'Elfelejtette a jelszavát?';
$a->strings['Website Terms of Service'] = 'Weboldal használati feltételei';
$a->strings['terms of service'] = 'használati feltételek';
$a->strings['Website Privacy Policy'] = 'Weboldal adatvédelmi irányelvei';
$a->strings['privacy policy'] = 'adatvédelmi irányelv';
$a->strings['Logged out.'] = 'Kijelentkezve.';
$a->strings['OpenID protocol error. No ID returned'] = 'OpenID protokollhiba. Nem lett azonosító visszaadva';
$a->strings['Account not found. Please login to your existing account to add the OpenID to it.'] = 'A fiók nem található. Jelentkezzen be a meglévő fiókjába, hogy hozzáadja az OpenID-t ahhoz.';
$a->strings['Account not found. Please register a new account or login to your existing account to add the OpenID to it.'] = 'A fiók nem található. Regisztráljon új fiókot vagy jelentkezzen be a meglévő fiókjába, hogy hozzáadja az OpenID-t ahhoz.';
$a->strings['Passwords do not match.'] = 'A jelszavak nem egyeznek.';
$a->strings['Password does not need changing.'] = 'A jelszót nem kell megváltoztatni.';
$a->strings['Password unchanged.'] = 'A jelszó nincs megváltoztatva.';
$a->strings['Password Too Long'] = 'A jelszó túl hosszú';
$a->strings['Since version 2022.09, we\'ve realized that any password longer than 72 characters is truncated during hashing. To prevent any confusion about this behavior, please update your password to be fewer or equal to 72 characters.'] = 'A 2022.09-es verzió óta rájöttünk, hogy a 72 karakternél hosszabb jelszavak a kivonatolás során le lesznek vágva. Az ezzel a viselkedéssel kapcsolatos félreértések elkerülése érdekében arra kérjük, frissítse jelszavát úgy, hogy az legfeljebb 72 karakterből álljon.';
$a->strings['Update Password'] = 'Jelszó frissítése';
$a->strings['Current Password:'] = 'Jelenlegi jelszó:';
$a->strings['Your current password to confirm the changes'] = 'A jelenlegi jelszava a változtatások megerősítéséhez';
$a->strings['Allowed characters are a-z, A-Z, 0-9 and special characters except white spaces and accentuated letters.'] = 'Az engedélyezett karakterek az a-z, A-Z, 0-9 tartományokban lévők és a különleges karakterek, kivéve az üres karaktereket és az ékezetes betűket.';
$a->strings['Password length is limited to 72 characters.'] = 'A jelszó hossza 72 karakterre van korlátozva.';
$a->strings['Remaining recovery codes: %d'] = 'Hátralévő visszaszerzési kódok: %d';
$a->strings['Invalid code, please retry.'] = 'Érvénytelen kód, próbálja újra.';
$a->strings['Two-factor recovery'] = 'Kétlépcsős visszaszerzés';
$a->strings['<p>You can enter one of your one-time recovery codes in case you lost access to your mobile device.</p>'] = '<p>Megadhatja az egyszeri visszaszerzési kódjai egyikét abban az esetben, ha elvesztette a hozzáférést a mobil eszközéhez.</p>';
$a->strings['Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>'] = 'Nincs meg a telefonja? <a href="%s">Adjon meg egy kétlépcsős visszaszerzési kódot</a>';
$a->strings['Please enter a recovery code'] = 'Adjon meg egy visszaszerzési kódot';
$a->strings['Submit recovery code and complete login'] = 'Visszaszerzési kód elküldése és a bejelentkezés befejezése';
$a->strings['Sign out of this browser?'] = 'Kijelentkezni ebből a böngészőből?';
$a->strings['<p>If you trust this browser, you will not be asked for verification code the next time you sign in.</p>'] = '<p>Ha megbízik ebben a böngészőben, akkor a következő bejelentkezéskor nem kéri Öntől az ellenőrző kódot.</p>';
$a->strings['Sign out'] = 'Kijelentkezés';
$a->strings['Trust and sign out'] = 'Megbízás és kijelentkezés';
$a->strings['Couldn\'t save browser to Cookie.'] = 'Nem sikerült elmenteni a böngészőt a sütibe.';
$a->strings['Trust this browser?'] = 'Megbízik ebben a böngészőben?';
$a->strings['<p>If you choose to trust this browser, you will not be asked for a verification code the next time you sign in.</p>'] = '<p>Ha azt választja, hogy megbízik ebben a böngészőben, akkor a következő bejelentkezéskor nem kéri Öntől az ellenőrző kódot.</p>';
$a->strings['Not now'] = 'Most nem';
$a->strings['Don\'t trust'] = 'Ne bízzon meg';
$a->strings['Trust'] = 'Megbízás';
$a->strings['<p>Open the two-factor authentication app on your device to get an authentication code and verify your identity.</p>'] = '<p>Nyissa meg a kétlépcsős hitelesítés alkalmazást az eszközén, hogy megkapjon egy hitelesítő kódot és ellenőrizze a személyazonosságát.</p>';
$a->strings['If you do not have access to your authentication code you can use a <a href="%s">two-factor recovery code</a>.'] = 'Ha nem fér hozzá a hitelesítési kódjához, akkor használhat egy <a href="%s">kétlépcsős visszaszerzési kódot</a>.';
$a->strings['Please enter a code from your authentication app'] = 'Adjon meg egy kódot a hitelesítő alkalmazásából';
$a->strings['Verify code and complete login'] = 'Kód ellenőrzése és a bejelentkezés befejezése';
$a->strings['Please use a shorter name.'] = 'Használjon rövidebb nevet.';
$a->strings['Name too short.'] = 'A név túl rövid.';
$a->strings['Wrong Password.'] = 'Hibás jelszó.';
$a->strings['Invalid email.'] = 'Érvénytelen e-mail-cím.';
$a->strings['Cannot change to that email.'] = 'Nem lehet megváltoztatni arra az e-mail-címre.';
$a->strings['Settings were not updated.'] = 'A beállítások nem lettek frissítve.';
$a->strings['Relocate message has been send to your contacts'] = 'Az áthelyezési üzenet el lett küldve a partnereknek';
$a->strings['Unable to find your profile. Please contact your admin.'] = 'Nem található a profilja. Vegye fel a kapcsolatot a rendszergazdával.';
$a->strings['Account for a service that automatically shares content based on user defined channels.'] = 'Fiók egy olyan szolgáltatáshoz, amely automatikusan megosztja a tartalmat a felhasználó által meghatározott csatornák alapján.';
$a->strings['Personal Page Subtypes'] = 'Személyes oldal altípusai';
$a->strings['Community Group Subtypes'] = 'Közösségi csoport altípusai';
$a->strings['Account for a personal profile.'] = 'Egy személyes profil fiókja.';
$a->strings['Account for an organisation that automatically approves contact requests as "Followers".'] = 'Egy szervezet fiókja, amely automatikusan jóváhagyja a partnerkéréseket, mint például a „követőket”.';
$a->strings['Account for a news reflector that automatically approves contact requests as "Followers".'] = 'Egy hírportál fiókja, amely automatikusan jóváhagyja a partnerkéréseket, mint például a „követőket”.';
$a->strings['Account for community discussions.'] = 'Közösségi beszélgetések fiókja.';
$a->strings['Account for a regular personal profile that requires manual approval of "Friends" and "Followers".'] = 'Egy szokásos személyes profil fiókja, amely az „ismerősök” és a „követők” kézi jóváhagyását igényli.';
$a->strings['Account for a public profile that automatically approves contact requests as "Followers".'] = 'Egy nyilvános profil fiókja, amely automatikusan jóváhagyja a partnerkéréseket, mint például a „követőket”.';
$a->strings['Automatically approves all contact requests.'] = 'Automatikusan jóváhagyja az összes partnerkérést.';
$a->strings['Contact requests have to be manually approved.'] = 'A partnerkéréseket kézzel kell jóváhagyni.';
$a->strings['Account for a popular profile that automatically approves contact requests as "Friends".'] = 'Egy népszerű profil fiókja, amely automatikusan jóváhagyja a partnerkéréseket, mint például az „ismerősöket”.';
$a->strings['Private Group [Experimental]'] = 'Személyes csoport [kísérleti]';
$a->strings['Requires manual approval of contact requests.'] = 'A partnerkérések kézi jóváhagyását igényli.';
$a->strings['OpenID:'] = 'OpenID:';
$a->strings['(Optional) Allow this OpenID to login to this account.'] = '(Kihagyható) Lehetővé teszi ezen OpenID számára, hogy bejelentkezzen ebbe a fiókba.';
$a->strings['Publish your profile in your local site directory?'] = 'Közzéteszi a profilját a helyi oldal könyvtárában?';
$a->strings['Your profile will be published in this node\'s <a href="%s">local directory</a>. Your profile details may be publicly visible depending on the system settings.'] = 'A profilja közzé lesz téve ennek a csomópontnak a <a href="%s">helyi könyvtárában</a>. A profilrészletei esetleg nyilvánosan láthatóak lehetnek a rendszerbeállításoktól függően.';
$a->strings['Your profile will also be published in the global friendica directories (e.g. <a href="%s">%s</a>).'] = 'A profilja közzé lesz téve a globális Friendica könyvtárakban is (például itt: <a href="%s">%s</a>).';
$a->strings['Account Settings'] = 'Fiókbeállítások';
$a->strings['Your Identity Address is <strong>\'%s\'</strong> or \'%s\'.'] = 'Az Ön személyazonosság-címe <strong>„%s”</strong> vagy „%s”.';
$a->strings['Password Settings'] = 'Jelszóbeállítások';
$a->strings['Leave password fields blank unless changing'] = 'Hagyja üresen a jelszómezőket, különben megváltozik';
$a->strings['Password:'] = 'Jelszó:';
$a->strings['Your current password to confirm the changes of the email address'] = 'A jelenlegi jelszava az e-mail-címe megváltoztatásának megerősítéséhez';
$a->strings['Delete OpenID URL'] = 'OpenID URL törlése';
$a->strings['Basic Settings'] = 'Alapvető beállítások';
$a->strings['Display name:'] = 'Megjelenített név:';
$a->strings['Email Address:'] = 'E-mail-cím:';
$a->strings['Your Timezone:'] = 'Az Ön időzónája:';
$a->strings['Your Language:'] = 'Az Ön nyelve:';
$a->strings['Set the language we use to show you friendica interface and to send you emails'] = 'Annak a nyelvnek a beállítása, amelyet a Friendica felületének megjelenítéséhez és a levelek küldéséhez használunk';
$a->strings['Default Post Location:'] = 'Alapértelmezett bejegyzésküldési hely:';
$a->strings['Use Browser Location:'] = 'Böngésző helyének használata:';
$a->strings['Security and Privacy Settings'] = 'Biztonsági és adatvédelmi beállítások';
$a->strings['Maximum Friend Requests/Day:'] = 'Legtöbb ismerőskérés naponta:';
$a->strings['(to prevent spam abuse)'] = '(a kéretlen üzenettel való visszaélés elkerüléséhez)';
$a->strings['Allow your profile to be searchable globally?'] = 'Engedélyezi, hogy a profilja globálisan kereshető legyen?';
$a->strings['Activate this setting if you want others to easily find and follow you. Your profile will be searchable on remote systems. This setting also determines whether Friendica will inform search engines that your profile should be indexed or not.'] = 'Akkor kapcsolja be ezt a beállítást, ha azt szeretné, hogy mások egyszerűen megtalálják és kövessék Önt. A profilja kereshető lesz a távoli rendszereken. Ez a beállítás azt is meghatározza, hogy a Friendica tájékoztatja-e a keresőmotorokat arról, hogy a profilját indexelni kell-e vagy sem.';
$a->strings['Hide your contact/friend list from viewers of your profile?'] = 'Elrejti a partnerlistáját vagy ismerőslistáját a profilja megtekintői elől?';
$a->strings['A list of your contacts is displayed on your profile page. Activate this option to disable the display of your contact list.'] = 'A partnereinek listája a profiloldalán van megjelenítve. Kapcsolja be ezt a beállítást, hogy letiltsa a partnerlistája megjelenítését.';
$a->strings['Hide your public content from anonymous viewers'] = 'Nyilvános tartalom elrejtése a névtelen megtekintők elől';
$a->strings['Anonymous visitors will only see your basic profile details. Your public posts and replies will still be freely accessible on the remote servers of your followers and through relays.'] = 'A névtelen látogatók csak az alapvető profilrészleteit fogják látni. A nyilvános bejegyzései és válaszai továbbra is szabadon elérhetőek lesznek a követői távoli kiszolgálóin és a továbbítókon keresztül.';
$a->strings['Make public posts unlisted'] = 'Nyilvános bejegyzések felsorolatlanná tétele';
$a->strings['Your public posts will not appear on the community pages or in search results, nor be sent to relay servers. However they can still appear on public feeds on remote servers.'] = 'A nyilvános bejegyzései nem fognak megjelenni a közösségi oldalakon vagy a keresési találatokban, és nem lesznek elküldve az átjátszó kiszolgálóknak. Azonban továbbra is megjelenhetnek a nyilvános hírforrásokban a távoli kiszolgálókon.';
$a->strings['Make all posted pictures accessible'] = 'Az összes beküldött fénykép elérhetővé tétele';
$a->strings['This option makes every posted picture accessible via the direct link. This is a workaround for the problem that most other networks can\'t handle permissions on pictures. Non public pictures still won\'t be visible for the public on your photo albums though.'] = 'Ez a beállítás elérhetővé tesz minden egyes beküldött fényképet a közvetlen hivatkozáson keresztül. Ez egy kerülőmegoldás arra a problémára, hogy a legtöbb más hálózat nem tudja kezelni a fényképek jogosultságait. A nem nyilvános fényképek továbbra sem lesznek láthatóak a nyilvánosság számára a fényképalbumán keresztül.';
$a->strings['Allow friends to post to your profile page?'] = 'Engedélyezi az ismerősöknek, hogy beküldjenek a profiloldalára?';
$a->strings['Your contacts may write posts on your profile wall. These posts will be distributed to your contacts'] = 'A partnerei bejegyzéseket írhatnak az Ön profilfalára. Ezek a bejegyzések továbbítva lesznek a partnereinek.';
$a->strings['Allow friends to tag your posts?'] = 'Engedélyezi az ismerőseinek, hogy címkézzék a bejegyzéseit?';
$a->strings['Your contacts can add additional tags to your posts.'] = 'A partnerei további címkéket adhatnak a bejegyzéseihez.';
$a->strings['Default privacy circle for new contacts'] = 'Alapértelmezett adatvédelmi kör az új partnerekhez';
$a->strings['Default privacy circle for new group contacts'] = 'Alapértelmezett adatvédelmi kör az új csoportpartnerekhez';
$a->strings['Default Post Permissions'] = 'Alapértelmezett bejegyzés-jogosultságok';
$a->strings['Expiration settings'] = 'Lejárati jogosultságok';
$a->strings['Automatically expire posts after this many days:'] = 'Bejegyzések automatikus lejárata ennyi nap után:';
$a->strings['If empty, posts will not expire. Expired posts will be deleted'] = 'Ha üres, akkor a bejegyzések nem járnak le. A lejárt bejegyzések törölve lesznek.';
$a->strings['Expire posts'] = 'Bejegyzések lejárata';
$a->strings['When activated, posts and comments will be expired.'] = 'Ha be van kapcsolva, akkor a bejegyzések és a hozzászólások le fognak járni.';
$a->strings['Expire personal notes'] = 'Személyes jegyzetek lejárata';
$a->strings['When activated, the personal notes on your profile page will be expired.'] = 'Ha be van kapcsolva, akkor a profiloldalán lévő személyes jegyzetek le fognak járni.';
$a->strings['Expire starred posts'] = 'Csillagozott bejegyzések lejárata';
$a->strings['Starring posts keeps them from being expired. That behaviour is overwritten by this setting.'] = 'A bejegyzések csillagozása megakadályozza azok lejáratát. Ez a viselkedés felülírható ezzel a beállítással.';
$a->strings['Only expire posts by others'] = 'Csak a másoktól származó bejegyzések lejárata';
$a->strings['When activated, your own posts never expire. Then the settings above are only valid for posts you received.'] = 'Ha be van kapcsolva, akkor a saját bejegyzései sosem járnak le. Ekkor a fenti beállítás csak azokra a bejegyzésekre érvényes, amelyeket megkap.';
$a->strings['Notification Settings'] = 'Értesítési beállítások';
$a->strings['Send a notification email when:'] = 'Értesítési e-mail küldése a következő esetekben:';
$a->strings['You receive an introduction'] = 'Egy bemutatkozást fogad';
$a->strings['Your introductions are confirmed'] = 'A bemutatkozásait jóváhagyták';
$a->strings['Someone writes on your profile wall'] = 'Valaki ír a profilfalára';
$a->strings['Someone writes a followup comment'] = 'Valaki egy követő hozzászólást ír';
$a->strings['You receive a private message'] = 'Személyes üzenetet kap';
$a->strings['You receive a friend suggestion'] = 'Ismerősajánlást kap';
$a->strings['You are tagged in a post'] = 'Megjelölték egy bejegyzésben';
$a->strings['Create a desktop notification when:'] = 'Asztali értesítés létrehozása ekkor:';
$a->strings['Someone tagged you'] = 'Valaki megjelölte Önt';
$a->strings['Someone directly commented on your post'] = 'Valaki közvetlenül hozzászólt a bejegyzéséhez';
$a->strings['Someone liked your content'] = 'Valaki kedvelte az Ön tartalmát';
$a->strings['Can only be enabled, when the direct comment notification is enabled.'] = 'Csak akkor engedélyezhető, ha a közvetlen hozzászólási értesítés engedélyezve van.';
$a->strings['Someone shared your content'] = 'Valaki megosztotta az Ön tartalmát';
$a->strings['Someone commented in your thread'] = 'Valaki hozzászólt az Ön szálában';
$a->strings['Someone commented in a thread where you commented'] = 'Valaki hozzászólt egy olyan szálban, ahol Ön hozzászólt';
$a->strings['Someone commented in a thread where you interacted'] = 'Valaki hozzászólt egy olyan szálban, ahol Ön interakcióba került';
$a->strings['Activate desktop notifications'] = 'Asztali értesítések bekapcsolása';
$a->strings['Show desktop popup on new notifications'] = 'Felugró üzenet megjelenítése az asztalon új értesítések esetén.';
$a->strings['Text-only notification emails'] = 'Csak szöveges értesítési e-mailek';
$a->strings['Send text only notification emails, without the html part'] = 'Csak szöveges értesítési e-mailek küldése a HTML rész nélkül.';
$a->strings['Show detailled notifications'] = 'Részletes értesítések megjelenítése';
$a->strings['Per default, notifications are condensed to a single notification per item. When enabled every notification is displayed.'] = 'Alapértelmezetten az értesítések elemenként egyetlen értesítésbe vannak összevonva. Ha engedélyezve van, akkor minden értesítés megjelenik.';
$a->strings['Show notifications of ignored contacts'] = 'Mellőzött partnerek értesítéseinek megjelenítése';
$a->strings['You don\'t see posts from ignored contacts. But you still see their comments. This setting controls if you want to still receive regular notifications that are caused by ignored contacts or not.'] = 'Nem látja a mellőzött partnerektől érkező bejegyzéseket. Viszont továbbra is látja a hozzászólásaikat. Ez a beállítás azt vezérli, hogy továbbra is szeretne-e olyan normál értesítéseket kapni vagy sem, amelyeket mellőzött partnerek okoznak.';
$a->strings['Advanced Account/Page Type Settings'] = 'Speciális fióktípus vagy oldaltípus beállítások';
$a->strings['Change the behaviour of this account for special situations'] = 'A fiók viselkedésének megváltoztatása bizonyos helyzetekre.';
$a->strings['Relocate'] = 'Áthelyezés';
$a->strings['If you have moved this profile from another server, and some of your contacts don\'t receive your updates, try pushing this button.'] = 'Ha áthelyezte ezt a profilt egy másik kiszolgálóról, és néhány partnere nem kapta meg a frissítéseket, akkor próbálja meg megnyomni ezt a gombot.';
$a->strings['Resend relocate message to contacts'] = 'Áthelyezési üzenet küldése a partnereknek';
$a->strings['Addon Settings'] = 'Bővítménybeállítások';
$a->strings['No Addon settings configured'] = 'Nincsenek bővítménybeállítások meghatározva';
$a->strings['This page can be used to define the channels that will automatically be reshared by your account.'] = 'Ez az oldal használható azon csatornák meghatározásához, amelyeket a fiókja automatikusan meg fog osztani.';
$a->strings['This page can be used to define your own channels.'] = 'Ez az oldal használható a saját csatornák meghatározásához.';
$a->strings['Publish'] = 'Közzététel';
$a->strings['When selected, the channel results are reshared. This only works for public ActivityPub posts from the public timeline or the user defined circles.'] = 'Ha ki van választva, akkor a csatorna eredményei újra megosztásra kerülnek. Ez csak a nyilvános idővonalról vagy a felhasználó által meghatározott körökből származó nyilvános ActivityPub-bejegyzéseknél működik.';
$a->strings['Label'] = 'Címke';
$a->strings['Description'] = 'Leírás';
$a->strings['Access Key'] = 'Hívóbetű';
$a->strings['Circle/Channel'] = 'Kör vagy csatorna';
$a->strings['Include Tags'] = 'Címkék felvétele';
$a->strings['Exclude Tags'] = 'Címkék kizárása';
$a->strings['Minimum Size'] = 'Legkisebb méret';
$a->strings['Maximum Size'] = 'Legnagyobb méret';
$a->strings['Full Text Search'] = 'Teljes szöveges keresés';
$a->strings['Delete channel'] = 'Csatorna törlése';
$a->strings['Check to delete this entry from the channel list'] = 'Jelölje be a bejegyzés csatornalistából való törléséhez';
$a->strings['Short name for the channel. It is displayed on the channels widget.'] = 'A csatorna rövid neve. Ez a csatornák felületi elemen jelenik meg.';
$a->strings['This should describe the content of the channel in a few word.'] = 'Ennek néhány szóban le kell írnia a csatorna tartalmát.';
$a->strings['When you want to access this channel via an access key, you can define it here. Pay attention to not use an already used one.'] = 'Ha hívóbetűn keresztül szeretne hozzáférni ehhez a csatornához, akkor itt határozhatja meg azt. Figyeljen arra, hogy ne használjon már használatban lévőt.';
$a->strings['Select a circle or channel, that your channel should be based on.'] = 'Válasszon egy kört vagy csatornát, amelyen a csatornájának alapulnia kell.';
$a->strings['Comma separated list of tags. A post will be used when it contains any of the listed tags.'] = 'Címkék vesszővel elválasztott listája. Egy bejegyzés akkor lesz használva, ha a felsorolt címkék bármelyikét tartalmazza.';
$a->strings['Comma separated list of tags. If a post contain any of these tags, then it will not be part of nthis channel.'] = 'Címkék vesszővel elválasztott listája. Ha egy bejegyzés ezen címkék bármelyikét tartalmazza, akkor nem lesz része ennek a csatornának.';
$a->strings['Minimum post size. Leave empty for no minimum size. The size is calculated without links, attached posts, mentions or hashtags.'] = 'Legkisebb bejegyzésméret. Hagyja üresen, ha nincs legkisebb méret. A méret hivatkozások, csatolt bejegyzések, említések vagy kettős keresztes címkék nélkül kerül kiszámításra.';
$a->strings['Maximum post size. Leave empty for no maximum size. The size is calculated without links, attached posts, mentions or hashtags.'] = 'Legnagyobb bejegyzésméret. Hagyja üresen, ha nincs legnagyobb méret. A méret hivatkozások, csatolt bejegyzések, említések vagy kettős keresztes címkék nélkül kerül kiszámításra.';
$a->strings['Search terms for the body, supports the "boolean mode" operators from MariaDB. See the help for a complete list of operators and additional keywords: %s'] = 'A törzs keresési kifejezései. Támogatja a MariaDB „logikai módú” operátorait. Nézze meg a súgóban az operátorok és a további kulcsszavak teljes listáját: %s';
$a->strings['Check to display images in the channel.'] = 'Jelölje be a csatornában lévő képek megjelenítéséhez.';
$a->strings['Check to display videos in the channel.'] = 'Jelölje be a csatornában lévő videók megjelenítéséhez.';
$a->strings['Check to display audio in the channel.'] = 'Jelölje be a csatornában lévő hangok megjelenítéséhez.';
$a->strings['Select all languages that you want to see in this channel.'] = 'Válassza ki az összes nyelvet, amelyet látni szeretne ezen a csatornán.';
$a->strings['Add new entry to the channel list'] = 'Új bejegyzés hozzáadása a csatornalistához';
$a->strings['Add'] = 'Hozzáadás';
$a->strings['Current Entries in the channel list'] = 'Jelenlegi bejegyzések a csatornalistában';
$a->strings['Delete entry from the channel list'] = 'Bejegyzés törlése a csatornalistáról';
$a->strings['Delete entry from the channel list?'] = 'Törli a bejegyzést a csatornalistáról?';
$a->strings['Failed to connect with email account using the settings provided.'] = 'Nem sikerült kapcsolódni a megadott beállításokat használó e-mail-fiókkal.';
$a->strings['Diaspora (Socialhome, Hubzilla)'] = 'Diaspora (Socialhome, Hubzilla)';
$a->strings['Built-in support for %s connectivity is enabled'] = 'A(z) %s összekapcsolhatóságának beépített támogatása engedélyezve';
$a->strings['Built-in support for %s connectivity is disabled'] = 'A(z) %s összekapcsolhatóságának beépített támogatása letiltva';
$a->strings['Email access is disabled on this site.'] = 'Az e-mailes hozzáférés le van tiltva ezen az oldalon.';
$a->strings['None'] = 'Nincs';
$a->strings['Default (Mastodon will display the title and a link to the post)'] = 'Alapértelmezett (a Mastodon megjeleníti a címet és a bejegyzésre mutató hivatkozást)';
$a->strings['Use the summary (Mastodon and some others will treat it as content warning)'] = 'Az összefoglaló használata (a Mastodon és néhányan egyéb tartalomfigyelmeztetésként fogja kezelni)';
$a->strings['Embed the title in the body'] = 'A cím beágyazása a törzsbe';
$a->strings['General Social Media Settings'] = 'Általános közösségimédia-beállítások';
$a->strings['Followed content scope'] = 'Követett tartalom hatóköre';
$a->strings['By default, conversations in which your follows participated but didn\'t start will be shown in your timeline. You can turn this behavior off, or expand it to the conversations in which your follows liked a post.'] = 'Alapértelmezetten az idővonalán megjelennek azok a beszélgetések, amelyekben a követői részt vettek, de nem ők indították el. Ezt a viselkedést kikapcsolhatja, vagy kiterjesztheti azokra a beszélgetésekre, amelyekben a követőinek tetszett egy bejegyzés.';
$a->strings['Only conversations my follows started'] = 'Csak a követőim által indított beszélgetések';
$a->strings['Conversations my follows started or commented on (default)'] = 'A követőim által indított vagy hozzászólt beszélgetések (alapértelmezett)';
$a->strings['Any conversation my follows interacted with, including likes'] = 'A követőim által interakcióba került beszélgetések, beleértve a kedveléseket is';
$a->strings['Collapse sensitive posts'] = 'Érzékeny bejegyzések összecsukása';
$a->strings['If a post is marked as "sensitive", it will be displayed in a collapsed state, if this option is enabled.'] = 'Ha egy bejegyzés „érzékenyként” van jelölve, akkor az összecsukott állapotban jelenik meg, ha ez a beállítás engedélyezve van.';
$a->strings['Enable intelligent shortening'] = 'Intelligens rövidítés engedélyezése';
$a->strings['Normally the system tries to find the best link to add to shortened posts. If disabled, every shortened post will always point to the original friendica post.'] = 'Általában a rendszer megpróbálja megkeresni a legjobb hivatkozást a rövidített bejegyzésekhez történő hozzáadáshoz. Ha le van tiltva, akkor minden egyes rövidített bejegyzés mindig az eredeti Friendica bejegyzésre fog mutatni.';
$a->strings['Enable simple text shortening'] = 'Egyszerű szövegrövidítés engedélyezése';
$a->strings['Normally the system shortens posts at the next line feed. If this option is enabled then the system will shorten the text at the maximum character limit.'] = 'Általában a rendszer lerövidíti a bejegyzéseket a következő soremelésnél. Ha ez a beállítás engedélyezve van, akkor a rendszer a legnagyobb karakterkorlátnál fogja rövidíteni a szöveget.';
$a->strings['Attach the link title'] = 'A hivatkozás címének csatolása';
$a->strings['When activated, the title of the attached link will be added as a title on posts to Diaspora. This is mostly helpful with "remote-self" contacts that share feed content.'] = 'Ha be van kapcsolva, akkor a csatolt hivatkozás címe címként lesz hozzáadva a Diaspora hálózatra küldött bejegyzéseknél. Ez többnyire az olyan „távoli önmaga” partnerekkel hasznos, amelyek megosztják a hírforrás tartalmát.';
$a->strings['API: Use spoiler field as title'] = 'API: a spoiler mező használata címként';
$a->strings['When activated, the "spoiler_text" field in the API will be used for the title on standalone posts. When deactivated it will be used for spoiler text. For comments it will always be used for spoiler text.'] = 'Ha aktiválva van, akkor az API-ban lévő „spoiler_text” mező lesz használva az önálló bejegyzések címeként. Ha ki van kapcsolva, akkor a spoiler szövegéhez lesz használva. A megjegyzéseknél mindig a spoiler szövegéhez lesz használva.';
$a->strings['API: Automatically links at the end of the post as attached posts'] = 'API: automatikusan a bejegyzés végéhez kapcsolja csatolt bejegyzésként';
$a->strings['When activated, added links at the end of the post react the same way as added links in the web interface.'] = 'Ha aktiválva van, akkor a bejegyzés végéhez hozzáadott hivatkozások ugyanúgy reagálnak, mint a webes felületen hozzáadott hivatkozások.';
$a->strings['Article Mode'] = 'Cikk mód';
$a->strings['Controls how posts with titles are transmitted. Mastodon and its forks don\'t display the content of these posts if the post is created in the correct (default) way.'] = 'Azt vezérli, hogy a címekkel rendelkező bejegyzések hogyan kerülnek továbbításra. A Mastodon és elágaztatásai nem jelenítik meg ezeknek a bejegyzéseknek a tartalmát, ha a bejegyzést a megfelelő (alapértelmezett) módon hozták létre.';
$a->strings['Email/Mailbox Setup'] = 'E-mail vagy postafiók-beállítások';
$a->strings['If you wish to communicate with email contacts using this service (optional), please specify how to connect to your mailbox.'] = 'Ha e-mailes partnerekkel szeretne kommunikálni ezen szolgáltatás használatával (opcionális), akkor adja meg, hogy hogyan kell kapcsolódni a postafiókjához.';
$a->strings['Last successful email check:'] = 'Legutóbbi sikeres e-mail-ellenőrzés:';
$a->strings['IMAP server name:'] = 'IMAP-kiszolgáló neve:';
$a->strings['IMAP port:'] = 'IMAP port:';
$a->strings['Security:'] = 'Biztonság:';
$a->strings['Email login name:'] = 'E-mail bejelentkezési neve:';
$a->strings['Email password:'] = 'E-mail jelszava:';
$a->strings['Reply-to address:'] = 'Válaszcím:';
$a->strings['Send public posts to all email contacts:'] = 'Nyilvános bejegyzések küldése az összes e-mail partnernek:';
$a->strings['Action after import:'] = 'Importálás utáni művelet:';
$a->strings['Move to folder'] = 'Áthelyezés mappába';
$a->strings['Move to folder:'] = 'Áthelyezés mappába:';
$a->strings['Contact CSV file upload error'] = 'Partner CSV-fájl feltöltési hiba';
$a->strings['Importing Contacts done'] = 'A partnerek importálása kész';
$a->strings['Upload a CSV file that contains the handle of your followed accounts in the first column you exported from the old account.'] = 'Töltsön fel egy olyan CSV-fájlt, amely a követett fiókok kezelőjét tartalmazza az első oszlopban, ahogy a régi fiókból exportálta.';
$a->strings['Upload File'] = 'Fájl feltöltése';
$a->strings['Your legacy ActivityPub/GNU Social account'] = 'Az örökölt ActivityPub/GNU Social fiókja';
$a->strings['If you enter your old account name from an ActivityPub based system or your GNU Social/Statusnet account name here (in the format user@domain.tld), your contacts will be added automatically. The field will be emptied when done.'] = 'Ha megadja itt a régi, egy ActivityPub alapú rendszerből származó fiókja nevét, illetve a GNU Social vagy Statusnet fiókja nevét (felhasználó@tartomány.tld formátumban), akkor a partnerei automatikusan hozzá lesznek adva. A mező ki lesz ürítve, ha elkészült.';
$a->strings['Delegation successfully granted.'] = 'A meghatalmazás sikeresen megadva.';
$a->strings['Parent user not found, unavailable or password doesn\'t match.'] = 'A fölérendelt felhasználó nem található, nem érhető el vagy a jelszó nem egyezik.';
$a->strings['Delegation successfully revoked.'] = 'A meghatalmazás sikeresen visszavonva.';
$a->strings['Delegated administrators can view but not change delegation permissions.'] = 'A meghatalmazott adminisztrátorok megtekinthetik, de nem változtathatják meg a meghatalmazás jogosultságait.';
$a->strings['Delegate user not found.'] = 'A meghatalmazott felhasználó nem található.';
$a->strings['No parent user'] = 'Nincs fölérendelt felhasználó';
$a->strings['Parent User'] = 'Fölérendelt felhasználó';
$a->strings['Additional Accounts'] = 'További fiókok';
$a->strings['Register additional accounts that are automatically connected to your existing account so you can manage them from this account.'] = 'További fiókok regisztrálása, amelyek automatikusan hozzá vannak kapcsolva a meglévő fiókjához, így ebből a fiókból kezelheti azokat.';
$a->strings['Register an additional account'] = 'További fiók regisztrálása';
$a->strings['Parent users have total control about this account, including the account settings. Please double check whom you give this access.'] = 'A fölérendelt felhasználóknak teljes ellenőrzése van ezen fiók fölött, beleértve a fiók beállításait is. Ellenőrizze még egyszer, hogy kinek ad hozzáférést.';
$a->strings['Delegates'] = 'Meghatalmazottak';
$a->strings['Delegates are able to manage all aspects of this account/page except for basic account settings. Please do not delegate your personal account to anybody that you do not trust completely.'] = 'A meghatalmazottak képesek ezen fiókot vagy oldalt minden szempontból kezelni, kivéve az alapvető fiókbeállításokat. Ne hatalmazzon meg senki mást a személyes fiókja kezeléséhez, akiben nem bízik meg teljes mértékben.';
$a->strings['Existing Page Delegates'] = 'Meglévő oldalmeghatalmazottak';
$a->strings['Potential Delegates'] = 'Lehetséges meghatalmazottak';
$a->strings['No entries.'] = 'Nincsenek bejegyzések.';
$a->strings['The theme you chose isn\'t available.'] = 'A választott téma nem érhető el.';
$a->strings['%s - (Unsupported)'] = '%s – (nem támogatott)';
$a->strings['Color/Black'] = 'Színes/fekete';
$a->strings['Black'] = 'Fekete';
$a->strings['Color/White'] = 'Színes/fehér';
$a->strings['White'] = 'Fehér';
$a->strings['No preview'] = 'Nincs előnézet';
$a->strings['No image'] = 'Nincs kép';
$a->strings['Small Image'] = 'Kis kép';
$a->strings['Large Image'] = 'Nagy kép';
$a->strings['Display Settings'] = 'Megjelenítési beállítások';
$a->strings['General Theme Settings'] = 'Általános témabeállítások';
$a->strings['Custom Theme Settings'] = 'Egyéni témabeállítások';
$a->strings['Content Settings'] = 'Tartalombeállítások';
$a->strings['Theme settings'] = 'Témabeállítások';
$a->strings['Timelines'] = 'Idővonalak';
$a->strings['Display Theme:'] = 'Megjelenítés témája:';
$a->strings['Mobile Theme:'] = 'Mobil téma:';
$a->strings['Number of items to display per page:'] = 'Oldalanként megjelenítendő elemek száma:';
$a->strings['Maximum of 100 items'] = 'Legfeljebb 100 elem';
$a->strings['Number of items to display per page when viewed from mobile device:'] = 'Oldalanként megjelenítendő elemek száma, ha mobil eszközről nézik:';
$a->strings['Display emoticons'] = 'Hangulatjelek megjelenítése';
$a->strings['When enabled, emoticons are replaced with matching symbols.'] = 'Ha engedélyezve van, akkor a hangulatjelek ki lesznek cserélve a megfelelő szimbólumokkal.';
$a->strings['Infinite scroll'] = 'Végtelen görgetés';
$a->strings['Automatic fetch new items when reaching the page end.'] = 'Új elemek automatikus lekérése az oldal végének elérésekor.';
$a->strings['Enable Smart Threading'] = 'Intelligens szálkezelés engedélyezése';
$a->strings['Enable the automatic suppression of extraneous thread indentation.'] = 'A nem odatartozó szálbehúzások automatikus elnyomásának engedélyezése.';
$a->strings['Display the Dislike feature'] = 'A nem tetszik funkció megjelenítése';
$a->strings['Display the Dislike button and dislike reactions on posts and comments.'] = 'A nem tetszik gomb és a nem tetszik reakciók megjelenítése a bejegyzéseknél és a hozzászólásoknál.';
$a->strings['Display the resharer'] = 'Az újramegosztó megjelenítése';
$a->strings['Display the first resharer as icon and text on a reshared item.'] = 'Az első újramegosztó megjelenítése ikonként és szövegként egy újra megosztott elemnél.';
$a->strings['Stay local'] = 'Maradjon helyi';
$a->strings['Don\'t go to a remote system when following a contact link.'] = 'Ne menjen távoli rendszerre, ha egy partnerhivatkozást követ.';
$a->strings['Show the post deletion checkbox'] = 'A bejegyzéstörlés jelölőnégyzet megjelenítése';
$a->strings['Display the checkbox for the post deletion on the network page.'] = 'Jelölőnégyzet megjelenítése a bejegyzés törléséhez a hálózat oldalán.';
$a->strings['DIsplay the event list'] = 'Az eseménylista megjelenítése';
$a->strings['Display the birthday reminder and event list on the network page.'] = 'A születésnapi emlékeztető és az eseménylista megjelenítése a hálózat oldalán.';
$a->strings['Link preview mode'] = 'Hivatkozás-előnézeti mód';
$a->strings['Appearance of the link preview that is added to each post with a link.'] = 'A hivatkozás előnézetének megjelenése, amely minden egyes hivatkozással rendelkező bejegyzéshez hozzá van adva.';
$a->strings['Hide pictures with empty alternative text'] = 'Üres alternatív szöveggel rendelkező képek elrejtése';
$a->strings['Don\'t display pictures that are missing the alternative text.'] = 'Ne jelenítse meg azokat a képeket, amelyeknél hiányzik az alternatív szöveg.';
$a->strings['Hide custom emojis'] = 'Egyéni emodzsik elrejtése';
$a->strings['Don\'t display custom emojis.'] = 'Ne jelenítse meg az egyéni emodzsikat.';
$a->strings['Platform icons style'] = 'Platformikonok stílusa';
$a->strings['Style of the platform icons'] = 'A platformikonok stílusa';
$a->strings['Bookmark'] = 'Könyvjelző';
$a->strings['Enable timelines that you want to see in the channels widget. Bookmark timelines that you want to see in the top menu.'] = 'Azon idővonalak engedélyezése, amelyeket a csatornák felületi elemben szeretne látni. Azon idővonalak könyvjelzőzése, amelyeket a felső menüben szeretne látni.';
$a->strings['Channel languages:'] = 'Csatorna nyelvei:';
$a->strings['Beginning of week:'] = 'A hét kezdete:';
$a->strings['Default calendar view:'] = 'Alapértelmezett naptárnézet:';
$a->strings['Additional Features'] = 'További funkciók';
$a->strings['Connected Apps'] = 'Kapcsolt alkalmazások';
$a->strings['Remove authorization'] = 'Felhatalmazás eltávolítása';
$a->strings['Display Name is required.'] = 'A megjelenített név kötelező.';
$a->strings['Profile couldn\'t be updated.'] = 'A profilt nem sikerült frissíteni.';
$a->strings['Label:'] = 'Címke:';
$a->strings['Value:'] = 'Érték:';
$a->strings['Field Permissions'] = 'Mező jogosultságai';
$a->strings['(click to open/close)'] = '(kattintson a megnyitáshoz vagy bezáráshoz)';
$a->strings['Add a new profile field'] = 'Új profilmező hozzáadása';
$a->strings['The homepage is verified. A rel="me" link back to your Friendica profile page was found on the homepage.'] = 'A honlap ellenőrizve. A Friendica profiloldalára visszamutató rel="me" hivatkozás található a honlapon.';
$a->strings['To verify your homepage, add a rel="me" link to it, pointing to your profile URL (%s).'] = 'A honlapja ellenőrzéséhez adjon hozzá egy rel="me" hivatkozást a honlapjához, amely a profilja URL-jére mutat (%s).';
$a->strings['Profile Actions'] = 'Profilműveletek';
$a->strings['Edit Profile Details'] = 'Profil részleteinek szerkesztése';
$a->strings['Change Profile Photo'] = 'Profilfénykép megváltoztatása';
$a->strings['Profile picture'] = 'Profilfénykép';
$a->strings['Location'] = 'Hely';
$a->strings['Miscellaneous'] = 'Egyebek';
$a->strings['Custom Profile Fields'] = 'Egyéni profilmezők';
$a->strings['Upload Profile Photo'] = 'Profilfénykép feltöltése';
$a->strings['<p>Custom fields appear on <a href="%s">your profile page</a>.</p>
				<p>You can use BBCodes in the field values.</p>
				<p>Reorder by dragging the field title.</p>
				<p>Empty the label field to remove a custom field.</p>
				<p>Non-public fields can only be seen by the selected Friendica contacts or the Friendica contacts in the selected circles.</p>'] = '<p>Az egyéni mezők a <a href="%s">profiloldalán</a> jelennek meg
				<p>Használhat BBCode formázásokat a mező értékeiben.</p>
				<p>Átrendezheti a mező címének húzásával.</p>
				<p>Törölje ki a címkemezőt egy egyéni mező eltávolításához.</p>
				<p>A nem nyilvános mezőket csak a kijelölt Friendica partnerek vagy a kijelölt körökben lévő Friendica partnerek láthatják.</p>';
$a->strings['Street Address:'] = 'Utca, házszám:';
$a->strings['Locality/City:'] = 'Helység vagy város:';
$a->strings['Region/State:'] = 'Régió vagy állam:';
$a->strings['Postal/Zip Code:'] = 'Irányítószám:';
$a->strings['Country:'] = 'Ország:';
$a->strings['XMPP (Jabber) address:'] = 'XMPP (Jabber) cím:';
$a->strings['The XMPP address will be published so that people can follow you there.'] = 'Az XMPP-cím közzé lesz téve, hogy az emberek képesek legyenek ott követni Önt.';
$a->strings['Matrix (Element) address:'] = 'Mátrix (Element) cím:';
$a->strings['The Matrix address will be published so that people can follow you there.'] = 'A Mátrix-cím közzé lesz téve, hogy az emberek képesek legyenek ott követni Önt.';
$a->strings['Homepage URL:'] = 'Honlap URL:';
$a->strings['Public Keywords:'] = 'Nyilvános kulcsszavak:';
$a->strings['(Used for suggesting potential friends, can be seen by others)'] = '(Lehetséges ismerősök ajánlásához lesz használva, mások is láthatják)';
$a->strings['Private Keywords:'] = 'Személyes kulcsszavak:';
$a->strings['(Used for searching profiles, never shown to others)'] = '(Profilok kereséséhez lesz használva, sosem látható másoknak)';
$a->strings['Image size reduction [%s] failed.'] = 'A kép méretének csökkentése [%s] sikertelen.';
$a->strings['Shift-reload the page or clear browser cache if the new photo does not display immediately.'] = 'Töltse újra az oldalt a Shift billentyű lenyomása közben, vagy törölje a böngésző gyorsítótárát, ha az új fénykép nem jelenik meg azonnal.';
$a->strings['Unable to process image'] = 'Nem lehet feldolgozni a képet';
$a->strings['Photo not found.'] = 'A fénykép nem található.';
$a->strings['Profile picture successfully updated.'] = 'A profilfénykép sikeresen frissítve.';
$a->strings['Crop Image'] = 'Kép levágása';
$a->strings['Please adjust the image cropping for optimum viewing.'] = 'Igazítsa a kép levágását az optimális megtekintéshez.';
$a->strings['Use Image As Is'] = 'Kép használata, ahogy van';
$a->strings['Missing uploaded image.'] = 'Hiányzó feltöltött kép.';
$a->strings['Profile Picture Settings'] = 'Profilfénykép beállításai';
$a->strings['Current Profile Picture'] = 'Jelenlegi profilfénykép';
$a->strings['Upload Profile Picture'] = 'Profilfénykép feltöltése';
$a->strings['Upload Picture:'] = 'Fénykép feltöltése:';
$a->strings['or'] = 'vagy';
$a->strings['skip this step'] = 'ezen lépés kihagyása';
$a->strings['select a photo from your photo albums'] = 'fénykép kiválasztása a fényképalbumából';
$a->strings['There was a validation error, please make sure you\'re logged in with the account you want to remove and try again.'] = 'Ellenőrzési hiba történt. Győződjön meg arról, hogy az eltávolítani kívánt fiókkal van-e bejelentkezve, és próbálja meg újra.';
$a->strings['If this error persists, please contact your administrator.'] = 'Ha ez a hiba továbbra is fennáll, akkor vegye fel a kapcsolatot az adminisztrátorral.';
$a->strings['[Friendica System Notify]'] = '[Friendica rendszerértesítés]';
$a->strings['User deleted their account'] = 'A felhasználó törölte a fiókját';
$a->strings['On your Friendica node an user deleted their account. Please ensure that their data is removed from the backups.'] = 'Az Ön Friendica csomópontján egy felhasználó törölte a fiókját. Győződjön meg arról, hogy az adatai el lettek-e távolítva a biztonsági mentésekből.';
$a->strings['The user id is %d'] = 'A felhasználó-azonosító %d';
$a->strings['Your account has been successfully removed. Bye bye!'] = 'A fiókja sikeresen el lett távolítva. Viszlát!';
$a->strings['Remove My Account'] = 'Saját fiók eltávolítása';
$a->strings['This will completely remove your account. Once this has been done it is not recoverable.'] = 'Ez teljesen el fogja távolítani a fiókját. Miután ez megtörtént, nem lesz visszaállítható.';
$a->strings['Please enter your password for verification:'] = 'Adja meg a jelszavát az ellenőrzéshez:';
$a->strings['Do you want to ignore this server?'] = 'Szeretné mellőzni ezt a kiszolgálót?';
$a->strings['Do you want to unignore this server?'] = 'Szeretné megszüntetni ennek a kiszolgálónak a mellőzését?';
$a->strings['Remote server settings'] = 'Távoli kiszolgáló beállításai';
$a->strings['Server URL'] = 'Kiszolgáló URL';
$a->strings['Settings saved'] = 'Beállítások elmentve';
$a->strings['Here you can find all the remote servers you have taken individual moderation actions against. For a list of servers your node has blocked, please check out the <a href="friendica">Information</a> page.'] = 'Itt találhatja meg az összes olyan távoli kiszolgálót, amelyekkel szemben egyéni moderálási műveleteket hajtott végre. A csomópontja által tiltott kiszolgálók listájáért nézze meg az <a href="friendica">Információk</a> oldalt.';
$a->strings['Delete all your settings for the remote server'] = 'Az Ön összes beállításának törlése a távoli kiszolgálónál';
$a->strings['Save changes'] = 'Változtatások mentése';
$a->strings['Please enter your password to access this page.'] = 'Adja meg a jelszavát az oldal eléréséhez.';
$a->strings['App-specific password generation failed: The description is empty.'] = 'Az alkalmazásfüggő jelszó előállítása sikertelen: a leírás üres.';
$a->strings['App-specific password generation failed: This description already exists.'] = 'Az alkalmazásfüggő jelszó előállítása sikertelen: a leírás már létezik.';
$a->strings['App-specific passwords successfully revoked.'] = 'Az alkalmazásfüggő jelszavak sikeresen visszavonva.';
$a->strings['App-specific password successfully revoked.'] = 'Az alkalmazásfüggő jelszó sikeresen visszavonva.';
$a->strings['Two-factor app-specific passwords'] = 'Kétlépcsős alkalmazásfüggő jelszavak';
$a->strings['<p>App-specific passwords are randomly generated passwords used instead your regular password to authenticate your account on third-party applications that don\'t support two-factor authentication.</p>'] = '<p>Az alkalmazásfüggő jelszavak az Ön szokásos jelszava helyett használt véletlenszerűen előállított jelszavak, hogy hitelesítsék a fiókját az olyan harmadik féltől származó alkalmazásoknál, amelyek nem támogatják a kétlépcsős hitelesítést.</p>';
$a->strings['Make sure to copy your new app-specific password now. You won’t be able to see it again!'] = 'Győződjön meg arról, hogy lemásolta-e most az új alkalmazásfüggő jelszavát. Nem fogja tudni újra megnézni a jelszót!';
$a->strings['Last Used'] = 'Legutóbb használt';
$a->strings['Revoke'] = 'Visszavonás';
$a->strings['Revoke All'] = 'Összes visszavonása';
$a->strings['When you generate a new app-specific password, you must use it right away, it will be shown to you once after you generate it.'] = 'Ha új alkalmazásfüggő jelszót állít elő, akkor azonnal fel kell használnia. Akkor lesz megjelenítve Önnek, miután előállította azt.';
$a->strings['Generate new app-specific password'] = 'Új alkalmazásfüggő jelszó előállítása';
$a->strings['Friendiqa on my Fairphone 2...'] = 'Friendiqa a Fairphone 2 készülékemen…';
$a->strings['Generate'] = 'Előállítás';
$a->strings['Two-factor authentication successfully disabled.'] = 'A kétlépcsős hitelesítés sikeresen letiltva.';
$a->strings['<p>Use an application on a mobile device to get two-factor authentication codes when prompted on login.</p>'] = '<p>Egy alkalmazás használata egy mobil eszközön, hogy megkapja a kétlépcsős hitelesítés kódjait, ha a bejelentkezéskor kérik.</p>';
$a->strings['Authenticator app'] = 'Hitelesítő alkalmazás';
$a->strings['Configured'] = 'Beállítva';
$a->strings['Not Configured'] = 'Nincs beállítva';
$a->strings['<p>You haven\'t finished configuring your authenticator app.</p>'] = '<p>Nem fejezte be a hitelesítő alkalmazása beállítását.</p>';
$a->strings['<p>Your authenticator app is correctly configured.</p>'] = '<p>A hitelesítő alkalmazása megfelelően be van állítva.</p>';
$a->strings['Recovery codes'] = 'Visszaszerzési kódok';
$a->strings['Remaining valid codes'] = 'Hátralévő érvényes kódok';
$a->strings['<p>These one-use codes can replace an authenticator app code in case you have lost access to it.</p>'] = '<p>Ezek az egyszer használható kódok helyettesíthetnek egy hitelesítő alkalmazás kódot abban az esetben, ha elveszíti a hozzáférést ahhoz.</p>';
$a->strings['App-specific passwords'] = 'Alkalmazásfüggő jelszavak';
$a->strings['Generated app-specific passwords'] = 'Előállított alkalmazásfüggő jelszavak';
$a->strings['<p>These randomly generated passwords allow you to authenticate on apps not supporting two-factor authentication.</p>'] = '<p>Ezek a véletlenszerűen előállított jelszavak lehetővé teszik, hogy olyan alkalmazásoknál hitelesítsen, amelyek nem támogatják a kétlépcsős hitelesítést.</p>';
$a->strings['Current password:'] = 'Jelenlegi jelszó:';
$a->strings['You need to provide your current password to change two-factor authentication settings.'] = 'Meg kell adnia a jelenlegi jelszavát a kétlépcsős hitelesítési beállítások megváltoztatásához.';
$a->strings['Enable two-factor authentication'] = 'Kétlépcsős hitelesítés engedélyezése';
$a->strings['Disable two-factor authentication'] = 'Kétlépcsős hitelesítés letiltása';
$a->strings['Show recovery codes'] = 'Visszaszerzési kódok megjelenítése';
$a->strings['Manage app-specific passwords'] = 'Alkalmazásfüggő jelszavak kezelése';
$a->strings['Manage trusted browsers'] = 'Megbízható böngészők kezelése';
$a->strings['Finish app configuration'] = 'Alkalmazás beállításának befejezése';
$a->strings['New recovery codes successfully generated.'] = 'Az új visszaszerzési kódok sikeresen előállítva.';
$a->strings['Two-factor recovery codes'] = 'Kétlépcsős visszaszerzési kódok';
$a->strings['<p>Recovery codes can be used to access your account in the event you lose access to your device and cannot receive two-factor authentication codes.</p><p><strong>Put these in a safe spot!</strong> If you lose your device and don’t have the recovery codes you will lose access to your account.</p>'] = '<p>A visszaszerzési kódok használhatók a fiókjához való hozzáféréséhez abban az esetben, ha elveszti a hozzáférést az eszközéhez, és nem tud kétlépcsős hitelesítési kódokat fogadni.</p><p><strong>Tegye ezeket biztonságos helyre!</strong> Ha elveszti az eszközét és nincsenek meg a visszaszerzési kódjai, akkor el fogja veszíteni a fiókjához való hozzáférést.</p>';
$a->strings['When you generate new recovery codes, you must copy the new codes. Your old codes won’t work anymore.'] = 'Ha új visszaszerzési kódokat állít elő, akkor le kell másolnia az új kódokat. A régi kódjai többé nem fognak működni.';
$a->strings['Generate new recovery codes'] = 'Új visszaszerzési kódok előállítása';
$a->strings['Next: Verification'] = 'Következő: ellenőrzés';
$a->strings['Trusted browsers successfully removed.'] = 'A megbízható böngészők sikeresen eltávolítva.';
$a->strings['Trusted browser successfully removed.'] = 'A megbízható böngésző sikeresen eltávolítva.';
$a->strings['Two-factor Trusted Browsers'] = 'Kétlépcsős megbízható böngészők';
$a->strings['Trusted browsers are individual browsers you chose to skip two-factor authentication to access Friendica. Please use this feature sparingly, as it can negate the benefit of two-factor authentication.'] = 'A megbízható böngészők azok az egyéni böngészők, amelyeknél a kétlépcsős hitelesítés kihagyását választotta a Friendica alkalmazáshoz való hozzáféréshez. Lehetőleg mellőzze ennek a funkciónak a használatát, mivel ez megszüntetheti a kétlépcsős hitelesítés előnyeit.';
$a->strings['Device'] = 'Eszköz';
$a->strings['OS'] = 'Operációs rendszer';
$a->strings['Trusted'] = 'Megbízható';
$a->strings['Created At'] = 'Létrehozva:';
$a->strings['Last Use'] = 'Utolsó használat';
$a->strings['Remove All'] = 'Összes eltávolítása';
$a->strings['Two-factor authentication successfully activated.'] = 'A kétlépcsős hitelesítés sikeresen bekapcsolva.';
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
</dl>'] = '<p>Vagy elküldheti a hitelesítési beállításokat kézzel:</p>
<dl>
	<dt>Kibocsájtó</dt>
	<dd>%s</dd>
	<dt>Fiók neve</dt>
	<dd>%s</dd>
	<dt>Titkos kulcs</dt>
	<dd>%s</dd>
	<dt>Típus</dt>
	<dd>Időalapú</dd>
	<dt>Számjegyek száma</dt>
	<dd>6</dd>
	<dt>Kivonatoló algoritmus</dt>
	<dd>SHA-1</dd>
</dl>';
$a->strings['Two-factor code verification'] = 'Kétlépcsős kód ellenőrzése';
$a->strings['<p>Please scan this QR Code with your authenticator app and submit the provided code.</p>'] = '<p>Olvassa be ezt a QR-kódot a hitelesítő alkalmazásával, és küldje el a megkapott kódot.</p>';
$a->strings['<p>Or you can open the following URL in your mobile device:</p><p><a href="%s">%s</a></p>'] = '<p>Vagy megnyithatja a következő URL-t a mobil eszközén:</p><p><a href="%s">%s</a></p>';
$a->strings['Verify code and enable two-factor authentication'] = 'Kód ellenőrzése és a kétlépcsős hitelesítés engedélyezése';
$a->strings['Export account'] = 'Fiók exportálása';
$a->strings['Export your account info and contacts. Use this to make a backup of your account and/or to move it to another server.'] = 'Fiókinformációk és partnerek exportálása. A fiókjáról történő biztonsági mentés készítéséhez és/vagy egy másik kiszolgálóra való áthelyezéséhez használja ezt.';
$a->strings['Export all'] = 'Összes exportálása';
$a->strings['Export your account info, contacts and all your items as json. Could be a very big file, and could take a lot of time. Use this to make a full backup of your account (photos are not exported)'] = 'Fiókinformációk, partnerek és az összes elem exportálása JSON-formátumban. nagyon nagy fájl is lehet, és sokáig eltarthat. A fiókja teljes biztonsági mentésének elkészítéséhez használja ezt (a fényképek nem lesznek exportálva).';
$a->strings['Export Contacts to CSV'] = 'Partnerek exportálása CSV-fájlba';
$a->strings['Export the list of the accounts you are following as CSV file. Compatible to e.g. Mastodon.'] = 'A követett fiókok listájának exportálása CSV-fájlként. Kompatibilis például a Mastodonnal.';
$a->strings['The top-level post isn\'t visible.'] = 'A felső szintű bejegyzés nem látható.';
$a->strings['The top-level post was deleted.'] = 'A felső szintű bejegyzés törölve lett.';
$a->strings['This node has blocked the top-level author or the author of the shared post.'] = 'Ez a csomópont letiltotta a felső szintű szerzőt vagy a megosztott bejegyzés szerzőjét.';
$a->strings['You have ignored or blocked the top-level author or the author of the shared post.'] = 'Ön mellőzte vagy letiltotta a felső szintű szerzőt vagy a megosztott bejegyzés szerzőjét.';
$a->strings['You have ignored the top-level author\'s server or the shared post author\'s server.'] = 'Ön mellőzte a felső szintű szerző kiszolgálóját vagy a megosztott bejegyzés szerzőjének kiszolgálóját.';
$a->strings['Conversation Not Found'] = 'A beszélgetés nem található';
$a->strings['Unfortunately, the requested conversation isn\'t available to you.'] = 'Sajnos a kért beszélgetés nem érhető el az Ön számára.';
$a->strings['Possible reasons include:'] = 'A lehetséges okok a következők:';
$a->strings['Go back'] = 'Vissza';
$a->strings['Stack trace:'] = 'Veremkiíratás:';
$a->strings['Exception thrown in %s:%d'] = 'Kivétel történt itt: %s:%d';
$a->strings['At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name (pen name), an username (nickname) and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.'] = 'A regisztrációkor, valamint a felhasználói fiók és a partnerei között történő kommunikáció biztosításához a felhasználónak biztosítania kell egy megjelenített nevet (álnevet), egy felhasználónevet (becenevet) és egy működő e-mail-címet. A nevek hozzáférhetőek lesznek a fiók profiloldalán az oldal bármely látogatója számára, még akkor is, ha más profilrészletek nem jelennek meg. Az e-mail-cím csak az interakciókkal kapcsolatos felhasználói értesítések küldéséhez lesz használva, de nem lesz láthatóan megjelenítve. A fiók felsorolása a csomópont felhasználói könyvtárában vagy a globális felhasználói könyvtárban választható, és a felhasználói beállításokban szabályozható. Ez nem szükséges a kommunikációhoz.';
$a->strings['This data is required for communication and is passed on to the nodes of the communication partners and is stored there. Users can enter additional private data that may be transmitted to the communication partners accounts.'] = 'Ezek az adatok a kommunikációhoz szükségesek, és átadásra kerül a kommunikációs partnerek csomópontjainak, valamint el is lesznek tárolva ott. A felhasználók megadhatnak további személyes adatokat, amelyek szintén átvitelre kerülhetnek a kommunikációs partnerek fiókjaiba.';
$a->strings['At any point in time a logged in user can export their account data from the <a href="%1$s/settings/userexport">account settings</a>. If the user wants to delete their account they can do so at <a href="%1$s/settings/removeme">%1$s/settings/removeme</a>. The deletion of the account will be permanent. Deletion of the data will also be requested from the nodes of the communication partners.'] = 'Egy bejelentkezett felhasználó bármely időpontban exportálhatja a fiókja adatait a <a href="%1$s/settings/userexport">fiók beállításaiból</a>. Ha a felhasználó törölni szeretné a fiókját, akkor azt megteheti a <a href="%1$s/settings/removeme">%1$s/settings/removeme</a> oldalon. A fiók törlése végleges lesz. Az adatok törlése kérve lesz a kommunikációs partnerek csomópontjairól is.';
$a->strings['Privacy Statement'] = 'Adatvédelmi nyilatkozat';
$a->strings['Rules'] = 'Szabályok';
$a->strings['Parameter uri_id is missing.'] = 'Az uri_id paraméter hiányzik.';
$a->strings['The requested item doesn\'t exist or has been deleted.'] = 'A kért elem nem létezik vagy törölték.';
$a->strings['You are now logged in as %s'] = 'Most a következő néven van bejelentkezve: %s';
$a->strings['Switch between your accounts'] = 'Váltás a fiókjai között';
$a->strings['Manage your accounts'] = 'Fiókok kezelése';
$a->strings['Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions'] = 'Váltás a különböző személyazonosságok vagy közösségi és csoportoldalak között, amelyek megosztják a fiókja részleteit, vagy amelyeket „kezelés” jogosultságokkal ruházott fel';
$a->strings['Select an identity to manage: '] = 'A kezelendő személyazonosság kiválasztása: ';
$a->strings['User imports on closed servers can only be done by an administrator.'] = 'A lezárt kiszolgálókon történő felhasználó-importálásokat csak egy adminisztrátor végezheti el.';
$a->strings['Move account'] = 'Fiók áthelyezése';
$a->strings['You can import an account from another Friendica server.'] = 'Importálhat egy fiókot egy másik Friendica kiszolgálóról.';
$a->strings['You need to export your account from the old server and upload it here. We will recreate your old account here with all your contacts. We will try also to inform your friends that you moved here.'] = 'Exportálnia kell a fiókját a régi kiszolgálóról, és fel kell töltenie ide. Itt újra létre fogjuk hozni a régi fiókját az összes partnerével. Megpróbáljuk tájékoztatni az ismerőseit arról is, hogy átköltözött ide.';
$a->strings['This feature is experimental. We can\'t import contacts from the OStatus network (GNU Social/Statusnet) or from Diaspora'] = 'Ez a funkció kísérleti. Nem tudunk partnereket importálni az OStatus hálózatból (GNU Social/Statusnet) vagy Diaspora hálózatból.';
$a->strings['Account file'] = 'Fiókfájl';
$a->strings['To export your account, go to "Settings->Export your personal data" and select "Export account"'] = 'A fiókja exportálásához menjen a „Beállítások → Személyes adatok exportálása” oldalra, és válassza a „Fiók exportálása” lehetőséget.';
$a->strings['Error decoding account file'] = 'Hiba a fiókfájl dekódolásakor';
$a->strings['Error! No version data in file! This is not a Friendica account file?'] = 'Hiba! Nincsenek verzióadatok a fájlban! Ez nem Friendica fiókfájl?';
$a->strings['User \'%s\' already exists on this server!'] = '„%s” felhasználó már létezik ezen a kiszolgálón!';
$a->strings['User creation error'] = 'Felhasználó-létrehozási hiba';
$a->strings['%d contact not imported'] = [
	0 => '%d partner nincs importálva',
	1 => '%d partner nincs importálva',
];
$a->strings['User profile creation error'] = 'Felhasználóiprofil-létrehozási hiba';
$a->strings['Done. You can now login with your username and password'] = 'Kész. Most már bejelentkezhet a felhasználónevével és a jelszavával.';
$a->strings['Welcome to Friendica'] = 'Üdvözli a Friendica!';
$a->strings['New Member Checklist'] = 'Új tag ellenőrzőlistája';
$a->strings['We would like to offer some tips and links to help make your experience enjoyable. Click any item to visit the relevant page. A link to this page will be visible from your home page for two weeks after your initial registration and then will quietly disappear.'] = 'Tippeket és hivatkozásokat szeretnénk ajánlani, hogy élvezhetővé tegyük az alkalmazás használatát. Kattintson bármelyik elemre a kapcsolódó oldal megtekintéséhez. Az erre az oldalra mutató hivatkozás a kezdőlapjáról érhető el a kezdeti regisztrációt követő két héten belül, azután csendben eltűnik.';
$a->strings['Getting Started'] = 'Kezdeti lépések';
$a->strings['Friendica Walk-Through'] = 'Friendica útmutató';
$a->strings['On your <em>Quick Start</em> page - find a brief introduction to your profile and network tabs, make some new connections, and find some groups to join.'] = 'A <em>Gyors kezdés</em> oldalon találhat egy rövid bemutatót a profil és hálózati lapokról, új kapcsolatok létesítésről, valamint találhat néhány csoportot, amelyekhez csatlakozhat.';
$a->strings['Go to Your Settings'] = 'Ugrás a beállításaihoz';
$a->strings['On your <em>Settings</em> page -  change your initial password. Also make a note of your Identity Address. This looks just like an email address - and will be useful in making friends on the free social web.'] = 'A <em>Beállítások</em> oldalon változtathatja meg a kezdeti jelszavát. Szintén itt talál egy megjegyzést a személyazonosság-címével kapcsolatban. Ez úgy néz ki mint egy e-mail-cím, és hasznos lesz a szabad közösségi hálón az ismerősök kereséséhez.';
$a->strings['Review the other settings, particularly the privacy settings. An unpublished directory listing is like having an unlisted phone number. In general, you should probably publish your listing - unless all of your friends and potential friends know exactly how to find you.'] = 'Vizsgálja felül az egyéb beállításokat is, különösen az adatvédelmi beállításokat. Egy nem közzétett könyvtárlistázás olyan, mint ha egy nyilvánosságra nem hozott telefonszáma lenne. Általában valószínűleg közzé kell tennie a listázását, hacsak az ismerősei és a lehetséges ismerősei nem tudják pontosan, hogy hogyan találják meg Önt.';
$a->strings['Upload a profile photo if you have not done so already. Studies have shown that people with real photos of themselves are ten times more likely to make friends than people who do not.'] = 'Töltsön fel profilfényképet, ha ezt még nem tette meg. A tanulmányok kimutatták, hogy a saját magukról valódi fényképpel rendelkező emberek tízszer valószínűbben találnak ismerősöket mint azok az emberek, akiknek nincs profilfényképük.';
$a->strings['Edit Your Profile'] = 'A profil szerkesztése';
$a->strings['Edit your <strong>default</strong> profile to your liking. Review the settings for hiding your list of friends and hiding the profile from unknown visitors.'] = 'Szerkessze az <strong>alapértelmezett</strong> profilját a kedve szerint. Vizsgálja felül a beállításokat az ismerősök listájának elrejtéséhez és a profiljának az ismeretlen látogatók elől történő elrejtéséhez.';
$a->strings['Profile Keywords'] = 'Profil kulcsszavai';
$a->strings['Set some public keywords for your profile which describe your interests. We may be able to find other people with similar interests and suggest friendships.'] = 'Állítson be néhány nyilvános kulcsszót a profiljához, amely bemutatja az érdeklődési köreit. Képesek lehetünk megtalálni a hasonló érdeklődéssel rendelkező más embereket, és ajánlhatunk ismeretségeket.';
$a->strings['Connecting'] = 'Kapcsolatépítés';
$a->strings['Importing Emails'] = 'E-mailek importálása';
$a->strings['Enter your email access information on your Connector Settings page if you wish to import and interact with friends or mailing lists from your email INBOX'] = 'Adja meg az e-mail-címéhez való hozzáférés információt az összekötő beállításainak oldalán, ha a postafiókja beérkező üzeneteiből szeretne ismerősöket vagy levelezési listákat importálni, valamint velük kapcsolatba lépni.';
$a->strings['Go to Your Contacts Page'] = 'Ugrás a partnerek oldalára';
$a->strings['Your Contacts page is your gateway to managing friendships and connecting with friends on other networks. Typically you enter their address or site URL in the <em>Add New Contact</em> dialog.'] = 'A partnerek oldal az átjáró az ismeretségek kezeléséhez és a más hálózatokon lévő ismerősökkel történő kapcsolatfelvételhez. Jellemzően csak be kell írnia a címüket vagy az oldal URL-jét az <em>Új partner hozzáadása</em> párbeszédablakba.';
$a->strings['Go to Your Site\'s Directory'] = 'Ugrás az oldal könyvtárához';
$a->strings['The Directory page lets you find other people in this network or other federated sites. Look for a <em>Connect</em> or <em>Follow</em> link on their profile page. Provide your own Identity Address if requested.'] = 'A könyvtárak oldal lehetővé teszi más emberek keresését ezen a hálózaton vagy más föderált oldalakon. Keresse meg a <em>Kapcsolódás</em> vagy a <em>Követés</em> hivatkozást a profiloldalukon. Adja meg a saját személyazonosság-címét, ha kérik.';
$a->strings['Finding New People'] = 'Új emberek keresése';
$a->strings['On the side panel of the Contacts page are several tools to find new friends. We can match people by interest, look up people by name or interest, and provide suggestions based on network relationships. On a brand new site, friend suggestions will usually begin to be populated within 24 hours.'] = 'A partnerek oldal oldalsávjában számos eszköz található új ismerősök kereséséhez. Találhatunk embereket az érdeklődésük szerint, kereshetünk embereket név vagy érdeklődés szerint, valamint ajánlásokat adhatunk a hálózati kapcsolatok alapján. Egy teljesen új oldalon az ismerősök ajánlásai általában 24 órán belül kezdenek megjelenni.';
$a->strings['Add Your Contacts To Circle'] = 'Partnerek hozzáadása a körhöz';
$a->strings['Once you have made some friends, organize them into private conversation circles from the sidebar of your Contacts page and then you can interact with each circle privately on your Network page.'] = 'Miután szerezett néhány ismerőst, szervezze őket személyes beszélgetési körökbe a partnerek oldal oldalsávján keresztül, és ezután személyes módon léphet kapcsolatba minden egyes körrel a hálózat oldalon.';
$a->strings['Why Aren\'t My Posts Public?'] = 'Miért nem nyilvánosak a bejegyzéseim?';
$a->strings['Friendica respects your privacy. By default, your posts will only show up to people you\'ve added as friends. For more information, see the help section from the link above.'] = 'A Friendica tiszteletben tartja a magánszférát. Alapértelmezetten a bejegyzései csak azoknak az embereknek jelennek meg, akiket ismerősként felvett. További információkért nézze meg a súgószakaszt a fenti hivatkozáson keresztül.';
$a->strings['Getting Help'] = 'Segítség kérése';
$a->strings['Go to the Help Section'] = 'Ugrás a súgószakaszhoz';
$a->strings['Our <strong>help</strong> pages may be consulted for detail on other program features and resources.'] = 'A <strong>súgó</strong> oldalaink további részleteket közölhetnek a program egyéb funkcióiról és erőforrásairól.';
$a->strings['{0} wants to follow you'] = '{0} követni szeretné Önt';
$a->strings['{0} has started following you'] = '{0} elkezdte követni Önt';
$a->strings['%s liked %s\'s post'] = '%s kedvelte %s bejegyzését';
$a->strings['%s disliked %s\'s post'] = '%s nem kedvelte %s bejegyzését';
$a->strings['%s is attending %s\'s event'] = '%s részt vesz %s eseményén';
$a->strings['%s is not attending %s\'s event'] = '%s nem vesz részt %s eseményén';
$a->strings['%s may attending %s\'s event'] = '%s talán részt vesz %s eseményén';
$a->strings['%s is now friends with %s'] = '%s és %s mostantól ismerősök';
$a->strings['%s commented on %s\'s post'] = '%s hozzászólt %s bejegyzéséhez';
$a->strings['%s created a new post'] = '%s létrehozott egy új bejegyzést';
$a->strings['Friend Suggestion'] = 'Ismerősajánlás';
$a->strings['Friend/Connect Request'] = 'Ismerős vagy kapcsolódási kérés';
$a->strings['%1$s wants to follow you'] = '%1$s követni szeretné Önt';
$a->strings['%1$s has started following you'] = '%1$s elkezdte követni Önt';
$a->strings['%1$s liked your comment on %2$s'] = '%1$s kedvelte az Ön hozzászólását ehhez: %2$s';
$a->strings['%1$s liked your post %2$s'] = '%1$s kedvelte az Ön %2$s bejegyzését';
$a->strings['%1$s disliked your comment on %2$s'] = '%1$s nem kedvelte az Ön hozzászólását ehhez: %2$s';
$a->strings['%1$s disliked your post %2$s'] = '%1$s nem kedvelte az Ön %2$s bejegyzését';
$a->strings['%1$s shared your comment %2$s'] = '%1$s megosztotta az Ön %2$s hozzászólását';
$a->strings['%1$s shared your post %2$s'] = '%1$s megosztotta az Ön %2$s bejegyzését';
$a->strings['%1$s shared the post %2$s from %3$s'] = '%1$s megosztott egy %3$s által közzétett %2$s bejegyzést';
$a->strings['%1$s shared a post from %3$s'] = '%1$s megosztott egy %3$s által közzétett bejegyzést';
$a->strings['%1$s shared the post %2$s'] = '%1$s megosztotta a(z) %2$s bejegyzést';
$a->strings['%1$s shared a post'] = '%1$s megosztott egy bejegyzést';
$a->strings['%1$s wants to attend your event %2$s'] = '%1$s szeretne részt venni az Ön %2$s eseményén';
$a->strings['%1$s does not want to attend your event %2$s'] = '%1$s nem szeretne részt venni az Ön %2$s eseményén';
$a->strings['%1$s maybe wants to attend your event %2$s'] = '%1$s talán szeretne részt venni az Ön %2$s eseményén';
$a->strings['%1$s tagged you on %2$s'] = '%1$s megjelölte Önt ezen: %2$s';
$a->strings['%1$s replied to you on %2$s'] = '%1$s válaszolt Önnek ezen: %2$s';
$a->strings['%1$s commented in your thread %2$s'] = '%1$s hozzászólt az Ön %2$s szálában';
$a->strings['%1$s commented on your comment %2$s'] = '%1$s hozzászólt az Ön %2$s hozzászólásánál';
$a->strings['%1$s commented in their thread %2$s'] = '%1$s hozzászólt az ő %2$s szálában';
$a->strings['%1$s commented in their thread'] = '%1$s hozzászólt az ő szálában';
$a->strings['%1$s commented in the thread %2$s from %3$s'] = '%1$s hozzászólt egy %3$s által közzétett %2$s szálában';
$a->strings['%1$s commented in the thread from %3$s'] = '%1$s hozzászólt egy %3$s által közzétett szálában';
$a->strings['%1$s commented on your thread %2$s'] = '%1$s hozzászólt az Ön %2$s szálánál';
$a->strings['[Friendica:Notify]'] = '[Friendica: értesítés]';
$a->strings['%s New mail received at %s'] = '%s Új levél érkezett ekkor: %s';
$a->strings['%1$s sent you a new private message at %2$s.'] = '%1$s személyes üzenetet küldött ekkor: %2$s.';
$a->strings['a private message'] = 'egy személyes üzenetet';
$a->strings['%1$s sent you %2$s.'] = '%1$s küldött Önnek %2$s.';
$a->strings['Please visit %s to view and/or reply to your private messages.'] = 'Látogassa meg a(z) %s oldalt a személyes üzenete megtekintéséhez és/vagy megválaszolásához.';
$a->strings['%1$s commented on %2$s\'s %3$s %4$s'] = '%1$s hozzászólt egy %2$s által megosztott %3$s kapcsán: %4$s';
$a->strings['%1$s commented on your %2$s %3$s'] = '%1$s hozzászólt egy vele megosztott %2$s kapcsán: %3$s';
$a->strings['%1$s commented on their %2$s %3$s'] = '%1$s hozzászólt egy saját %2$s kapcsán: %3$s';
$a->strings['%1$s Comment to conversation #%2$d by %3$s'] = '%1$s Hozzászólás a #%2$d beszélgetéshez %3$s által';
$a->strings['%s commented on an item/conversation you have been following.'] = '%s hozzászólt egy olyan elemhez vagy beszélgetéshez, amelyet Ön követ.';
$a->strings['Please visit %s to view and/or reply to the conversation.'] = 'Látogassa meg a %s oldalt a beszélgetés megtekintéséhez és/vagy megválaszolásához.';
$a->strings['%s %s posted to your profile wall'] = '%s %s bejegyzést írt az Ön profilfalára';
$a->strings['%1$s posted to your profile wall at %2$s'] = '%1$s bejegyzést írt az Ön profilfalára itt: %2$s';
$a->strings['%1$s posted to [url=%2$s]your wall[/url]'] = '%1$s bejegyzést írt [url=%2$s]az Ön falára[/url]';
$a->strings['%s Introduction received'] = '%s Bemutatkozás érkezett';
$a->strings['You\'ve received an introduction from \'%1$s\' at %2$s'] = 'Kapott egy %1$s által elküldött bemutatkozását itt: %2$s';
$a->strings['You\'ve received [url=%1$s]an introduction[/url] from %2$s.'] = 'Kapott egy %2$s által elküldött [url=%1$s]bemutatkozást[/url].';
$a->strings['You may visit their profile at %s'] = 'Meglátogathatja a profilját itt: %s';
$a->strings['Please visit %s to approve or reject the introduction.'] = 'Látogassa meg a(z) %s oldalt a bemutatkozás jóváhagyásához vagy visszautasításához.';
$a->strings['%s A new person is sharing with you'] = '%s Egy új személy megoszt Önnel';
$a->strings['%1$s is sharing with you at %2$s'] = '%1$s megoszt Önnel itt: %2$s';
$a->strings['%s You have a new follower'] = '%s Van egy új követője';
$a->strings['You have a new follower at %2$s : %1$s'] = 'Van egy új követője, %1$s itt: %2$s';
$a->strings['%s Friend suggestion received'] = '%s Ismerősajánlás érkezett';
$a->strings['You\'ve received a friend suggestion from \'%1$s\' at %2$s'] = 'Kapott egy %1$s által elküldött ismerősajánlást itt: %2$s';
$a->strings['You\'ve received [url=%1$s]a friend suggestion[/url] for %2$s from %3$s.'] = 'Kapott egy %3$s által elküldött [url=%1$s]ismerősajánlást[/url] %2$s partnerhez.';
$a->strings['Name:'] = 'Név:';
$a->strings['Photo:'] = 'Fénykép:';
$a->strings['Please visit %s to approve or reject the suggestion.'] = 'Látogassa meg a(z) %s oldalt az ajánlás jóváhagyásához vagy visszautasításához.';
$a->strings['%s Connection accepted'] = '%s Kapcsolat elfogadva';
$a->strings['\'%1$s\' has accepted your connection request at %2$s'] = '%1$s elfogadta a kapcsolódási kérését itt: %2$s';
$a->strings['%2$s has accepted your [url=%1$s]connection request[/url].'] = '%2$s elfogadta a [url=%1$s]kapcsolódási kérését[/url].';
$a->strings['You are now mutual friends and may exchange status updates, photos, and email without restriction.'] = 'Önök most már kölcsönösen ismerősök, és korlátozások nélkül megoszthatják az állapotfrissítéseiket, fényképeiket és az e-mail-címüket egymással.';
$a->strings['Please visit %s if you wish to make any changes to this relationship.'] = 'Látogassa meg a(z) %s oldalt, ha bármilyen változtatást szeretne tenni ebben a kapcsolatban.';
$a->strings['\'%1$s\' has chosen to accept you a fan, which restricts some forms of communication - such as private messaging and some profile interactions. If this is a celebrity or community page, these settings were applied automatically.'] = '%1$s úgy döntött, hogy elfogadja Önt rajongóként, ami korlátozza a kommunikáció néhány formáját, mint például a személyes üzenet küldését és néhány profil-interakciót. Ha ez egy híres ember vagy egy közösségi oldal, akkor ezek a beállítások automatikusan alkalmazva lettek.';
$a->strings['\'%1$s\' may choose to extend this into a two-way or more permissive relationship in the future.'] = '%1$s dönthet úgy, hogy kiterjeszti ezt egy kétirányú vagy egy megengedőbb kapcsolattá a jövőben.';
$a->strings['Please visit %s  if you wish to make any changes to this relationship.'] = 'Látogassa meg a(z) %s oldalt, ha bármilyen változtatást szeretne tenni ebben a kapcsolatban.';
$a->strings['registration request'] = 'regisztrációs kérés';
$a->strings['You\'ve received a registration request from \'%1$s\' at %2$s'] = 'Kapott egy regisztrációs kérést „%1$s” partnertől itt: %2$s';
$a->strings['You\'ve received a [url=%1$s]registration request[/url] from %2$s.'] = 'Kapott egy %2$s által elküldött [url=%1$s]regisztrációs kérést[/url].';
$a->strings['Display Name:	%s
Site Location:	%s
Login Name:	%s (%s)'] = 'Megjelenített név:	%s
Oldal címe:	%s
Bejelentkezési név:	%s (%s)';
$a->strings['Please visit %s to approve or reject the request.'] = 'Látogassa meg a(z) %s oldalt a kérés jóváhagyásához vagy visszautasításához.';
$a->strings['new registration'] = 'új regisztráció';
$a->strings['You\'ve received a new registration from \'%1$s\' at %2$s'] = 'Kapott egy új regisztrációs kérést „%1$s” partnertől itt: %2$s';
$a->strings['You\'ve received a [url=%1$s]new registration[/url] from %2$s.'] = 'Kapott egy %2$s által elküldött [url=%1$s]új regisztrációt[/url].';
$a->strings['Please visit %s to have a look at the new registration.'] = 'Látogassa meg a(z) %s oldal, hogy egy pillantást vessen az új regisztrációra.';
$a->strings['%s %s tagged you'] = '%s %s megjelölte Önt';
$a->strings['%s %s shared a new post'] = '%s %s megosztott egy új bejegyzést';
$a->strings['%1$s %2$s liked your post #%3$d'] = '%1$s %2$s kedvelte az Ön #%3$d bejegyzését';
$a->strings['%1$s %2$s liked your comment on #%3$d'] = '%1$s %2$s kedvelte az Ön hozzászólását ehhez: #%3$d';
$a->strings['This message was sent to you by %s, a member of the Friendica social network.'] = 'Ezt az üzenetet %s, a Friendica közösségi hálózatának tagja küldte Önnek.';
$a->strings['You may visit them online at %s'] = 'Meglátogathatja őket az interneten ezen a címen: %s';
$a->strings['Please contact the sender by replying to this post if you do not wish to receive these messages.'] = 'Vegye fel a kapcsolatot a küldővel erre a bejegyzésre válaszolva, ha nem szeretné megkapni ezeket az üzeneteket.';
$a->strings['%s posted an update.'] = '%s frissítést küldött.';
$a->strings['Private Message'] = 'Személyes üzenet';
$a->strings['Public Message'] = 'Nyilvános üzenet';
$a->strings['Unlisted Message'] = 'Listázatlan üzenet';
$a->strings['This entry was edited'] = 'Ezt a bejegyzést szerkesztették';
$a->strings['Connector Message'] = 'Csatlakozóüzenet';
$a->strings['Edit'] = 'Szerkesztés';
$a->strings['Delete globally'] = 'Törlés globálisan';
$a->strings['Remove locally'] = 'Eltávolítás helyileg';
$a->strings['Block %s'] = '%s tiltása';
$a->strings['Ignore %s'] = '%s mellőzése';
$a->strings['Collapse %s'] = '%s összecsukása';
$a->strings['Report post'] = 'Bejegyzés jelentése';
$a->strings['Save to folder'] = 'Mentés mappába';
$a->strings['I will attend'] = 'Részt veszek';
$a->strings['I will not attend'] = 'Nem veszek részt';
$a->strings['I might attend'] = 'Talán részt veszek';
$a->strings['Ignore thread'] = 'Szál mellőzése';
$a->strings['Unignore thread'] = 'Szál mellőzésének megszüntetése';
$a->strings['Toggle ignore status'] = 'Mellőzési állapot átváltása';
$a->strings['Add star'] = 'Csillag hozzáadása';
$a->strings['Remove star'] = 'Csillag eltávolítása';
$a->strings['Toggle star status'] = 'Csillagállapot átváltása';
$a->strings['Pin'] = 'Kitűzés';
$a->strings['Unpin'] = 'Kitűzés megszüntetése';
$a->strings['Toggle pin status'] = 'Kitűzés állapotának átváltása';
$a->strings['Pinned'] = 'Kitűzve';
$a->strings['Add tag'] = 'Címke hozzáadása';
$a->strings['Quote share this'] = 'Idézett megosztás';
$a->strings['Quote Share'] = 'Idézett megosztás';
$a->strings['Reshare this'] = 'Újra megosztás';
$a->strings['Reshare'] = 'Újra megosztás';
$a->strings['Cancel your Reshare'] = 'Újra megosztás megszakítása';
$a->strings['Unshare'] = 'Megosztás megszüntetése';
$a->strings['%s (Received %s)'] = '%s (fogadva: %s)';
$a->strings['Comment this item on your system'] = 'Hozzászólás az elemhez a saját rendszerén';
$a->strings['Remote comment'] = 'Távoli hozzászólás';
$a->strings['Share via ...'] = 'Megosztás ezen keresztül…';
$a->strings['Share via external services'] = 'Megosztás külső szolgáltatásokon keresztül';
$a->strings['Unknown parent'] = 'Ismeretlen szülő';
$a->strings['in reply to %s'] = 'válasz a következőre: %s';
$a->strings['Parent is probably private or not federated.'] = 'A szülő valószínűleg személyes vagy nem föderált.';
$a->strings['to'] = 'ide:';
$a->strings['via'] = 'ezen keresztül:';
$a->strings['Wall-to-Wall'] = 'Falról-falra';
$a->strings['via Wall-To-Wall:'] = 'falról-falra szolgáltatáson keresztül:';
$a->strings['Reply to %s'] = 'Válasz erre: %s';
$a->strings['Notifier task is pending'] = 'Az értesítőfeladat függőben van';
$a->strings['Delivery to remote servers is pending'] = 'A távoli kiszolgálókra történő kézbesítés függőben van';
$a->strings['Delivery to remote servers is underway'] = 'A távoli kiszolgálókra történő kézbesítés úton van';
$a->strings['Delivery to remote servers is mostly done'] = 'A távoli kiszolgálókra történő kézbesítés többnyire készen van';
$a->strings['Delivery to remote servers is done'] = 'A távoli kiszolgálókra történő kézbesítés készen van';
$a->strings['%d comment'] = [
	0 => '%d hozzászólás',
	1 => '%d hozzászólás',
];
$a->strings['Show more'] = 'Több megjelenítése';
$a->strings['Show fewer'] = 'Kevesebb megjelenítése';
$a->strings['Reshared by: %s'] = 'Újra megosztotta: %s';
$a->strings['Viewed by: %s'] = 'Megtekintette: %s';
$a->strings['Read by: %s'] = 'Olvasta: %s';
$a->strings['Liked by: %s'] = 'Kedvelte: %s';
$a->strings['Disliked by: %s'] = 'Nem kedvelte: %s';
$a->strings['Attended by: %s'] = 'Részt vett: %s';
$a->strings['Maybe attended by: %s'] = 'Talán részt vett: %s';
$a->strings['Not attended by: %s'] = 'Nem vett részt: %s';
$a->strings['Commented by: %s'] = 'Hozzászólt: %s';
$a->strings['Reacted with %s by: %s'] = 'Reagált ezzel: %s: %s';
$a->strings['Quote shared by: %s'] = 'Idézetten osztott meg: %s';
$a->strings['Chat'] = 'Csevegés';
$a->strings['(no subject)'] = '(nincs tárgy)';
$a->strings['The folder %s must be writable by webserver.'] = 'A „%s” mappának írhatónak kell lennie a webkiszolgáló által.';
$a->strings['Login failed.'] = 'Bejelentkezés sikertelen.';
$a->strings['Login failed. Please check your credentials.'] = 'Bejelentkezés sikertelen. Ellenőrizze a hitelesítési adatait.';
$a->strings['Welcome %s'] = 'Üdvözöljük, %s!';
$a->strings['Please upload a profile photo.'] = 'Töltsön fel egy profilfényképet.';
$a->strings['OpenWebAuth: %1$s welcomes %2$s'] = 'OpenWebAuth: %1$s üdvözli őt: %2$s';
$a->strings['Friendica Notification'] = 'Friendica értesítés';
$a->strings['%1$s, %2$s Administrator'] = '%1$s, a(z) %2$s adminisztrátora';
$a->strings['%s Administrator'] = 'A(z) %s adminisztrátora';
$a->strings['thanks'] = 'köszönet';
$a->strings['YYYY-MM-DD or MM-DD'] = 'YYYY-MM-DD vagy MM-DD';
$a->strings['Time zone: <strong>%s</strong> <a href="%s">Change in Settings</a>'] = 'Időzóna: <strong>%s</strong> <a href="%s">Megváltoztatás a beállításokban</a>';
$a->strings['never'] = 'soha';
$a->strings['less than a second ago'] = 'kevesebb mint egy másodperce';
$a->strings['year'] = 'év';
$a->strings['years'] = 'év';
$a->strings['months'] = 'hónap';
$a->strings['weeks'] = 'hét';
$a->strings['days'] = 'nap';
$a->strings['hour'] = 'óra';
$a->strings['hours'] = 'óra';
$a->strings['minute'] = 'perc';
$a->strings['minutes'] = 'perc';
$a->strings['second'] = 'másodperc';
$a->strings['seconds'] = 'másodperc';
$a->strings['in %1$d %2$s'] = '%1$d %2$s múlva';
$a->strings['%1$d %2$s ago'] = '%1$d %2$s óta';
$a->strings['Notification from Friendica'] = 'Értesítés a Friendicától';
$a->strings['Empty Post'] = 'Üres bejegyzés';
$a->strings['default'] = 'alapértelmezett';
$a->strings['greenzero'] = 'zöld nulla';
$a->strings['purplezero'] = 'lila nulla';
$a->strings['easterbunny'] = 'húsvéti nyúl';
$a->strings['darkzero'] = 'sötét nulla';
$a->strings['comix'] = 'comix';
$a->strings['slackr'] = 'slackr';
$a->strings['Variations'] = 'Variációk';
$a->strings['Note'] = 'Jegyzet';
$a->strings['Check image permissions if all users are allowed to see the image'] = 'Ellenőrizze a kép jogosultságait, hogy minden felhasználó képes-e megtekinteni a képet.';
$a->strings['Appearance'] = 'Megjelenés';
$a->strings['Accent color'] = 'Kiemelőszín';
$a->strings['Blue'] = 'Kék';
$a->strings['Red'] = 'Piros';
$a->strings['Purple'] = 'Lila';
$a->strings['Green'] = 'Zöld';
$a->strings['Pink'] = 'Rózsaszín';
$a->strings['Copy or paste schemestring'] = 'Sémakarakterlánc másolása vagy beillesztése';
$a->strings['You can copy this string to share your theme with others. Pasting here applies the schemestring'] = 'Lemásolhatja ezt a karakterláncot, hogy megossza a témáját másokkal. Ide beillesztve alkalmazza a sémakarakterláncot';
$a->strings['Navigation bar background color'] = 'Navigációs sáv háttérszíne';
$a->strings['Navigation bar icon color '] = 'Navigációs sáv ikonszíne ';
$a->strings['Link color'] = 'Hivatkozás színe';
$a->strings['Set the background color'] = 'Háttérszín beállítása';
$a->strings['Content background opacity'] = 'Tartalom hátterének átlátszatlansága';
$a->strings['Set the background image'] = 'A háttérkép beállítása';
$a->strings['Background image style'] = 'Háttérkép stílusa';
$a->strings['Always open Compose page'] = 'Mindig az írás oldal megnyitása';
$a->strings['The New Post button always open the <a href="/compose">Compose page</a> instead of the modal form. When this is disabled, the Compose page can be accessed with a middle click on the link or from the modal.'] = 'Az új bejegyzés gomb mindig az írás oldalt nyitja meg a modális űrlap helyett. Ha ez le van tiltva, akkor az írás oldal a hivatkozásra való középső kattintással vagy a modális űrlapról érhető el.';
$a->strings['Login page background image'] = 'Bejelentkezési oldal háttérképe';
$a->strings['Login page background color'] = 'Bejelentkezési oldal háttérszíne';
$a->strings['Leave background image and color empty for theme defaults'] = 'Hagyja üresen a háttérképet és színt a téma alapértelmezettjéhez';
$a->strings['Top Banner'] = 'Felső reklámcsík';
$a->strings['Resize image to the width of the screen and show background color below on long pages.'] = 'Kép átméretezése a képernyő szélességéhez, és a lenti háttérszín megjelenítése hosszú oldalakon.';
$a->strings['Full screen'] = 'Teljes képernyő';
$a->strings['Resize image to fill entire screen, clipping either the right or the bottom.'] = 'Kép átméretezése a teljes képernyő kitöltéséhez, levágva a jobb szélét vagy az alját.';
$a->strings['Single row mosaic'] = 'Egysoros mozaik';
$a->strings['Resize image to repeat it on a single row, either vertical or horizontal.'] = 'Kép átméretezése az egy sorban való ismétléshez, függőlegesen vagy vízszintesen.';
$a->strings['Mosaic'] = 'Mozaik';
$a->strings['Repeat image to fill the screen.'] = 'Kép ismétlése a képernyő kitöltéséhez.';
$a->strings['Back to top'] = 'Vissza a tetejére';
$a->strings['Light'] = 'Világos';
$a->strings['Dark'] = 'Sötét';
$a->strings['Custom'] = 'Egyéni';
$a->strings['Guest'] = 'Vendég';
$a->strings['Visitor'] = 'Látogató';
$a->strings['Alignment'] = 'Igazítás';
$a->strings['Left'] = 'Balra';
$a->strings['Center'] = 'Középre';
$a->strings['Color scheme'] = 'Színséma';
$a->strings['Posts font size'] = 'Bejegyzések betűmérete';
$a->strings['Textareas font size'] = 'Szövegdobozok betűmérete';
$a->strings['Comma separated list of helper groups'] = 'Segítő csoportok vesszővel elválasztott listája';
$a->strings['don\'t show'] = 'ne jelenítse meg';
$a->strings['show'] = 'megjelenítés';
$a->strings['Set style'] = 'Stílus beállítása';
$a->strings['Community Pages'] = 'Közösségi oldalak';
$a->strings['Community Profiles'] = 'Közösségi profilok';
$a->strings['Help or @NewHere ?'] = 'Súgó vagy @NewHere?';
$a->strings['Connect Services'] = 'Szolgáltatások hozzákapcsolása';
$a->strings['Find Friends'] = 'Ismerősök keresése';
$a->strings['Last users'] = 'Utolsó felhasználók';
$a->strings['Quick Start'] = 'Gyors kezdés';
