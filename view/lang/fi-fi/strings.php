<?php

if(! function_exists("string_plural_select_fi_fi")) {
function string_plural_select_fi_fi($n){
	$n = intval($n);
	return intval($n != 1);
}}
$a->strings['Unable to locate original post.'] = 'Alkuperäistä julkaisua ei löydy.';
$a->strings['Post updated.'] = 'Julkaisu päivitetty.';
$a->strings['Item wasn\'t stored.'] = 'Kohdetta ei tallennettu.';
$a->strings['Item couldn\'t be fetched.'] = 'Kohdetta ei voitu noutaa.';
$a->strings['Empty post discarded.'] = 'Hylättiin tyhjä julkaisu.';
$a->strings['Item not found.'] = 'Kohdetta ei löytynyt.';
$a->strings['Permission denied.'] = 'Käyttöoikeus evätty.';
$a->strings['No valid account found.'] = 'Voimassa olevaa tiliä ei löytynyt.';
$a->strings['Password reset request issued. Check your email.'] = 'Salasanan nollauspyyntö lähetetty. Tarkista sähköpostisi.';
$a->strings['
		Dear %1$s,
			A request was recently received at "%2$s" to reset your account
		password. In order to confirm this request, please select the verification link
		below or paste it into your web browser address bar.

		If you did NOT request this change, please DO NOT follow the link
		provided and ignore and/or delete this email, the request will expire shortly.

		Your password will not be changed unless we can verify that you
		issued this request.'] = '
		Hei %1$s,
			Saimme hetki sitten pyynnön nollata tilisi salasana palvelussa ”%2$s”.
		Vahvistaaksesi tämän pyynnön napsauta alla olevaa vahvistuslinkkiä
		tai kopioi ja liitä se internetselaimesi osoitekenttään.

		Jos ET pyytänyt tätä muutosta, ÄLÄ seuraa annettua linkkiä, vaan
		jätä tämä viesti huomiotta tai poista viesti. Nollauspyyntö raukeaa pian.

		Salasanaasi ei vaihdeta, ellemme voi varmistaa, että lähetit pyynnön itse.';
$a->strings['
		Follow this link soon to verify your identity:

		%1$s

		You will then receive a follow-up message containing the new password.
		You may change that password from your account settings page after logging in.

		The login details are as follows:

		Site Location:	%2$s
		Login Name:	%3$s'] = '
		Vahvista henkilöytesi seuraamalla tätä linkkiä pian:

		%1$s

		Saat sitten uuden viestin, jossa on uusi salasanasi.
		Salasanan voi tilisi asetussivulta kirjauduttuasi.

		Kirjautumistiedot ovat:

		Sivuston sijainti:	%2$s
		Käyttäjätunnus:	%3$s';
$a->strings['Password reset requested at %s'] = 'Salasanan nollauspyyntö kohteessa %s';
$a->strings['Request could not be verified. (You may have previously submitted it.) Password reset failed.'] = 'Pyyntöä ei voitu vahvistaa. (Saatoit lähettää pyyntöä aikaisemmin.) Salasanan nollaus epäonnistui.';
$a->strings['Request has expired, please make a new one.'] = 'Pyyntö on vanhentunut, lähetä uusi pyyntö.';
$a->strings['Forgot your Password?'] = 'Unohditko salasanasi?';
$a->strings['Enter your email address and submit to have your password reset. Then check your email for further instructions.'] = 'Syötä sähköpostiosoitteesi salasanan nollausta varten. Lisäohjeet lähetetään sähköpostitse.';
$a->strings['Nickname or email'] = 'Nimimerkki tai sähköposti';
$a->strings['Reset my password'] = 'Nollaa salasanani';
$a->strings['Password Reset'] = 'Salasanan nollaus';
$a->strings['Your password has been reset as requested.'] = 'Salasanasi on nollattu pyynnöstäsi.';
$a->strings['Your new password is'] = 'Uusi salasanasi on';
$a->strings['Save or copy your new password - and then'] = 'Tallenna tai kopioi uusi salasanasi, ja sitten';
$a->strings['click here to login'] = 'kirjaudu klikkaamalla tästä';
$a->strings['Your password may be changed from the <em>Settings</em> page after successful login.'] = 'Salsanaa voi vaihtaa <em>asetussivulta</em> kirjautumisen jälkeen.';
$a->strings['Your password has been reset.'] = 'Salasanasi on nollattu.';
$a->strings['
			Dear %1$s,
				Your password has been changed as requested. Please retain this
			information for your records (or change your password immediately to
			something that you will remember).
		'] = '
			Hei %1$s,
				Salasana on pyynnöstäsi vaihdettu. Säilytä nämä tiedot
			tulevaisuutta silmällä pitäen (tai vaihda salasanasi heti joksikin
			sellaiseksi, minkä muistat).
		';
$a->strings['
			Your login details are as follows:

			Site Location:	%1$s
			Login Name:	%2$s
			Password:	%3$s

			You may change that password from your account settings page after logging in.
		'] = '
			Käyttäjätilisi tiedot:

			Sivuston osoite:	%1$s
			Käyttäjätunnus:	%2$s
			Salasana:	%3$s

			Voit vaihtaa salasanasi kirjautumisen jälkeen asetussivulta.
		';
$a->strings['Your password has been changed at %s'] = 'Salasanasi on vaihdettu sivustolla %s';
$a->strings['Messages'] = 'Viestit';
$a->strings['New Message'] = 'Uusi viesti';
$a->strings['No recipient selected.'] = 'Vastaanottaja puuttuu.';
$a->strings['Unable to locate contact information.'] = 'Yhteystietoja ei löydy.';
$a->strings['Message could not be sent.'] = 'Viestiä ei voitu lähettää.';
$a->strings['Message collection failure.'] = 'Viestin noutaminen epäonnistui.';
$a->strings['Discard'] = 'Hylkää';
$a->strings['Conversation not found.'] = 'Keskustelua ei löydy.';
$a->strings['Message was not deleted.'] = 'Viestiä ei poistettu.';
$a->strings['Conversation was not removed.'] = 'Keskustelua ei poistettu.';
$a->strings['Please enter a link URL:'] = 'Lisää URL-linkki:';
$a->strings['Send Private Message'] = 'Lähetä yksityisviesti';
$a->strings['To:'] = 'Vastaanottaja:';
$a->strings['Subject:'] = 'Aihe:';
$a->strings['Your message:'] = 'Viestisi:';
$a->strings['Upload photo'] = 'Lähetä kuva';
$a->strings['Insert web link'] = 'Lisää linkki';
$a->strings['Please wait'] = 'Odota';
$a->strings['Send Message'] = 'Lähetä viesti';
$a->strings['You have no messages.'] = 'Sinulla ei ole viestejä.';
$a->strings['Message not available.'] = 'Viesti ei saatavilla.';
$a->strings['Delete message'] = 'Poista viesti';
$a->strings['D, d M Y - g:i A'] = 'D j.n.Y - H:i';
$a->strings['Delete conversation'] = 'Poista keskustelu';
$a->strings['No secure communications available. You <strong>may</strong> be able to respond from the sender\'s profile page.'] = 'Suojattu viestintä ei ole käytettävissä. <strong>Saatat</strong> pystyä vastaamaan lähettäjän profiilisivulta.';
$a->strings['Send Reply'] = 'Lähetä vastaus';
$a->strings['Unknown sender - %s'] = 'Tuntematon lähettäjä – %s';
$a->strings['You and %s'] = 'Sinä ja %s';
$a->strings['%s and You'] = '%s ja sinä';
$a->strings['%d message'] = [
	0 => '%d viesti',
	1 => '%d viestiä',
];
$a->strings['Personal Notes'] = 'Yksityismuistiinpanot';
$a->strings['Personal notes are visible only by yourself.'] = 'Yksityismuistiinpanot näkyvät vain sinulle.';
$a->strings['Save'] = 'Tallenna';
$a->strings['User not found.'] = 'Käyttäjää ei löydy.';
$a->strings['Photo Albums'] = 'Valokuva-albumit';
$a->strings['Recent Photos'] = 'Viimeaikaisia kuvia';
$a->strings['Upload Photos'] = 'Lähetä kuvia';
$a->strings['everybody'] = 'kaikki';
$a->strings['Contact information unavailable'] = 'Yhteystietoja ei saatavilla';
$a->strings['Album not found.'] = 'Albumia ei löydy.';
$a->strings['Album successfully deleted'] = 'Albumin poisto onnistui';
$a->strings['Album was empty.'] = 'Albumi oli tyhjä.';
$a->strings['Failed to delete the photo.'] = 'Kuvan poistaminen epäonnistui.';
$a->strings['a photo'] = 'valokuva';
$a->strings['%1$s was tagged in %2$s by %3$s'] = '%1$s merkattiin kuvaan %2$s ystävän %3$s toimesta';
$a->strings['Public access denied.'] = 'Julkinen käyttö estetty.';
$a->strings['No photos selected'] = 'Ei valittuja kuvia';
$a->strings['Upload selected picture'] = 'Lataa valittu kuva palvelimelle';
$a->strings['The maximum accepted image size is %s'] = 'Suurin sallittu kuvan koko on %s';
$a->strings['New album name: '] = 'Uuden albumin nimi: ';
$a->strings['or select existing album:'] = 'tai valitse olemassa oleva albumi:';
$a->strings['Do not show a status post for this upload'] = 'Älä näytä tilajulkaisua tälle lähetykselle';
$a->strings['Permissions'] = 'Käyttöoikeudet';
$a->strings['Do you really want to delete this photo album and all its photos?'] = 'Haluatko varmasti poistaa tämän albumin ja kaikki sen kuvat?';
$a->strings['Delete Album'] = 'Poista albumi';
$a->strings['Cancel'] = 'Peru';
$a->strings['Save changes'] = 'Tallenna muutokset';
$a->strings['Edit Album'] = 'Muokkaa albumia';
$a->strings['Delete album'] = 'Poista albumi';
$a->strings['Show Newest First'] = 'Näytä uusin ensin';
$a->strings['Show Oldest First'] = 'Näytä vanhin ensin';
$a->strings['View Photo'] = 'Näytä kuva';
$a->strings['Permission denied. Access to this item may be restricted.'] = 'Estetty. Tämän kohteen käyttöä on saatettu rajoittaa.';
$a->strings['Photo not available'] = 'Kuva ei ole saatavilla';
$a->strings['Do you really want to delete this photo?'] = 'Haluatko varmasti poistaa kuvan?';
$a->strings['Delete Photo'] = 'Poista kuva';
$a->strings['View photo'] = 'Näytä kuva';
$a->strings['Edit photo'] = 'Muokkaa kuvaa';
$a->strings['Delete photo'] = 'Poista kuva';
$a->strings['Use as profile picture'] = 'Käytä profiilikuvana';
$a->strings['Private Photo'] = 'Yksityinen kuva';
$a->strings['View Full Size'] = 'Näytä täysikokoisena';
$a->strings['Tags'] = 'Aihetunnisteet';
$a->strings['[Select tags to remove]'] = '[Valitse poistettavat aihetunnisteet]';
$a->strings['New album name'] = 'Albumin uusi nimi';
$a->strings['Caption'] = 'Kuvateksti';
$a->strings['Add a Tag'] = 'Lisää aihetunniste';
$a->strings['Example: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping'] = 'Esimerkkejä: @maija, @Matti_Meikäläinen, @kaisa@esimerkki.fi, #Suomi, #telttailu';
$a->strings['Do not rotate'] = 'Älä kierrä';
$a->strings['Rotate CW (right)'] = 'Kierrä myötäpäivään (oikealle)';
$a->strings['Rotate CCW (left)'] = 'Kierrä vastapäivään (vasemmalle)';
$a->strings['This is you'] = 'Tämä olet sinä';
$a->strings['Comment'] = 'Kommentti';
$a->strings['Preview'] = 'Esikatselu';
$a->strings['Loading...'] = 'Ladataan…';
$a->strings['Select'] = 'Valitse';
$a->strings['Delete'] = 'Poista';
$a->strings['Like'] = 'Tykkää';
$a->strings['I like this (toggle)'] = 'Tykkään tästä (vaihda)';
$a->strings['Dislike'] = 'En tykkää';
$a->strings['I don\'t like this (toggle)'] = 'En tykkää tästä (vaihda)';
$a->strings['Edit'] = 'Muokkaa';
$a->strings['Back to viewing'] = 'Takaisin katseluun';
$a->strings['Map'] = 'Kartta';
$a->strings['Apologies but the website is unavailable at the moment.'] = 'Valitettavasti sivusto ei ole tällä hetkellä käytettävissä.';
$a->strings['Delete this item?'] = 'Poistetaanko tämä kohde?';
$a->strings['Block this author? They won\'t be able to follow you nor see your public posts, and you won\'t be able to see their posts and their notifications.'] = 'Estetäänkö tämä tekijä? Hän ei voi seurata sinua eikä nähdä julkisia julkaisujasi, etkä sinä voi nähdä hänen julkaisujaan tai saa ilmoituksia hänestä.';
$a->strings['Ignore this author? You won\'t be able to see their posts and their notifications.'] = 'Sivuutetaanko tämä tekijä? Et voi nähdä hänen julkaisujaan etkä saa ilmoituksia hänestä.';
$a->strings['Collapse this author\'s posts?'] = 'Supistetaanko tämän tekijän julkaisut?';
$a->strings['Ignore this author\'s server?'] = 'Sivuutetaanko tämän tekijän palvelin?';
$a->strings['You won\'t see any content from this server including reshares in your Network page, the community pages and individual conversations.'] = 'Et näe mitään tämän palvelimen sisältöä, edes Verkosto-sivusi uudelleenjakoja, yhteisösivuja tai yksittäisiä keskusteluja.';
$a->strings['Like not successful'] = 'Tykkäys ei onnistunut';
$a->strings['Dislike not successful'] = 'En tykkää -reaktion peruminen ei onnistunut';
$a->strings['Sharing not successful'] = 'Jakaminen ei onnistunut';
$a->strings['Attendance unsuccessful'] = 'Osallistuminen ei onnistunut';
$a->strings['Backend error'] = 'Taustajärjestelmän virhe';
$a->strings['Network error'] = 'Verkkovirhe';
$a->strings['Drop files here to upload'] = 'Pudota lähetettävät tiedostot tähän';
$a->strings['Your browser does not support drag and drop file uploads.'] = 'Selaimesi ei tue tiedostojen lähettämistä vetämällä ja pudottamalla.';
$a->strings['Please use the fallback form below to upload your files like in the olden days.'] = 'Lähetä tiedostosi vanhaan tapaan käyttämällä alla olevaa varalomaketta.';
$a->strings['File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.'] = 'Tiedosto on liian suuri ({{filesize}}MiB). Tiedoston enimmäiskoko: {{maxFilesize}}MiB.';
$a->strings['You can\'t upload files of this type.'] = 'Tätä tiedostotyyppiä ei voi lähettää.';
$a->strings['Server responded with {{statusCode}} code.'] = 'Palvelin vastasi tilakoodilla {{statusCode}}.';
$a->strings['Cancel upload'] = 'Peru lähetys';
$a->strings['Upload canceled.'] = 'Lähetys peruttiin.';
$a->strings['Are you sure you want to cancel this upload?'] = 'Haluatko varmasti perua tämän lähetyksen?';
$a->strings['Remove file'] = 'Poista tiedosto';
$a->strings['You can\'t upload any more files.'] = 'Et voi ladata enempää tiedostoja.';
$a->strings['toggle mobile'] = 'käytä tai älä käytä mobiilisivustoa';
$a->strings['Close'] = 'Sulje';
$a->strings['Method not allowed for this module. Allowed method(s): %s'] = 'Moduuli ei salli tätä menetelmää. Sallitut menetelmät: %s';
$a->strings['Page not found.'] = 'Sivua ei löytynyt.';
$a->strings['You must be logged in to use addons. '] = 'Sinun pitää kirjautua sisään, jotta voit käyttää lisäosia.';
$a->strings['No system theme config value set.'] = 'Järjestelmäteeman asetusarvoa ei ole asetettu.';
$a->strings['The form security token was not correct. This probably happened because the form has been opened for too long (>3 hours) before submitting it.'] = 'Lomakkeen turvallisuusavain oli väärin. Tämä voi johtua siitä, että lomake on ollut avoinna liian kauan (yli 3 tuntia) ennen sen lähettämistä.';
$a->strings['All contacts'] = 'Kaikki yhteystiedot';
$a->strings['Followers'] = 'Seuraajat';
$a->strings['Following'] = 'Seurattavat';
$a->strings['Friends'] = 'Kaverit';
$a->strings['Common'] = 'Yhteiset';
$a->strings['Addon not found'] = 'Lisäosaa ei löydy';
$a->strings['Addon already enabled'] = 'Lisäosa on jo käytössä';
$a->strings['Addon already disabled'] = 'Lisäosa on jo pois käytöstä';
$a->strings['Could not find any unarchived contact entry for this URL (%s)'] = 'Tälle verkko-osoitteelle ei löytynyt arkistoimattomia yhteystietomerkintöjä (%s)';
$a->strings['The contact entries have been archived'] = 'Kontaktimerkinnät on arkistoitu';
$a->strings['The avatar cache needs to be disabled in local.config.php to use this command.'] = 'Tämän komennon käyttämiseksi avatar-välimuisti täytyy poistaa käytöstä tiedostossa local.config.php.';
$a->strings['Could not find any contact entry for this URL (%s)'] = 'Kontaktimerkintöjä ei löytynyt tälle URL-osoitteelle (%s)';
$a->strings['The contact has been blocked from the node'] = 'Kontakti on estetty tällä solmulla';
$a->strings['%d %s, %d duplicates.'] = '%d %s, %d duplikaattia.';
$a->strings['uri-id is empty for contact %s.'] = 'Kontaktin %s uri-ID on tyhjä.';
$a->strings['No valid first contact found for uri-id %d.'] = 'Validia ensimmäistä kontaktia ei löytynyt uri-id:lle %d.';
$a->strings['Wrong duplicate found for uri-id %d in %d (url: %s != %s).'] = 'Väärä duplikaatti löydetty uri-ID:lle %d paikassa %d (URL: %s != %s).';
$a->strings['Wrong duplicate found for uri-id %d in %d (nurl: %s != %s).'] = 'Väärä duplikaatti löydetty uri-ID:lle %d paikassa %d (nurl: %s != %s).';
$a->strings['Deletion of id %d failed'] = 'ID:n %d poistaminen epäonnistui';
$a->strings['Deletion of id %d was successful'] = 'ID:n %d poistaminen onnistui';
$a->strings['Updating "%s" in "%s" from %d to %d'] = 'Päivitetään "%s" paikassa "%s" %d:sta %d:ksi';
$a->strings[' - found'] = '- löydetty';
$a->strings[' - failed'] = '- epäonnistui';
$a->strings[' - success'] = '- onnistui';
$a->strings[' - deleted'] = '- poistettu';
$a->strings[' - done'] = '- valmis';
$a->strings['The avatar cache needs to be enabled to use this command.'] = 'Tämän komennon käyttämiseksi avatar-välimuistin täytyy olla käytössä.';
$a->strings['no resource in photo %s'] = 'ei lähdettä kuvassa %s';
$a->strings['no photo with id %s'] = 'ei kuvaa, jonka ID on %s';
$a->strings['no image data for photo with id %s'] = 'ei kuvatietoja kuvalle, jonka ID on %s';
$a->strings['invalid image for id %s'] = 'epävalidi kuva ID:lle %s';
$a->strings['Quit on invalid photo %s'] = 'Luovu epäkelvosta kuvasta %s';
$a->strings['Post update version number has been set to %s.'] = 'Julkaisun päivityksen versionumeroksi on asetettu %s.';
$a->strings['Check for pending update actions.'] = 'Tarkista keskeneräiset päivitystoimet.';
$a->strings['Done.'] = 'Valmis.';
$a->strings['Execute pending post updates.'] = 'Suorita vireillä olevat julkaisupäivitykset.';
$a->strings['All pending post updates are done.'] = 'Kaikki vireillä olevat julkaisupäivitykset on tehty.';
$a->strings['Enter user nickname: '] = 'Syötä käyttäjän lempinimi:';
$a->strings['User not found'] = 'Käyttäjää ei löydy';
$a->strings['Enter new password: '] = 'Syötä uusi salasana:';
$a->strings['Password update failed. Please try again.'] = 'Salasanan vaihto epäonnistui. Yritä uudelleen.';
$a->strings['Password changed.'] = 'Salasana vaihdettu.';
$a->strings['Enter user name: '] = 'Syötä käyttäjänimi:';
$a->strings['Enter user email address: '] = 'Syötä käyttäjän sähköpostiosoite:';
$a->strings['Enter a language (optional): '] = 'Syötä kieli (valinnainen):';
$a->strings['Enter URL of an image to use as avatar (optional): '] = 'Syötä avatarina käytettävän kuvan URL (valinnainen):';
$a->strings['User is not pending.'] = 'Käyttäjän tila ei ole avoin';
$a->strings['User has already been marked for deletion.'] = 'Käyttäjä on jo merkitty poistettavaksi.';
$a->strings['Type "yes" to delete %s'] = 'Kirjoita "yes" poistaaksesi %s';
$a->strings['Deletion aborted.'] = 'Poisto keskeytetty.';
$a->strings['Enter category: '] = 'Syötä kategoria:';
$a->strings['Enter key: '] = 'Syötä avain:';
$a->strings['Enter value: '] = 'Syötä arvo:';
$a->strings['newer'] = 'uudempi';
$a->strings['older'] = 'vanhempi';
$a->strings['Frequently'] = 'Usein';
$a->strings['Hourly'] = 'Tunneittain';
$a->strings['Twice daily'] = 'Kahdesti päivässä';
$a->strings['Daily'] = 'Päivittäin';
$a->strings['Weekly'] = 'Viikottain';
$a->strings['Monthly'] = 'Kuukausittain';
$a->strings['DFRN'] = 'DFRN';
$a->strings['OStatus'] = 'OStatus';
$a->strings['RSS/Atom'] = 'RSS/Atom';
$a->strings['Email'] = 'Sähköposti';
$a->strings['Diaspora'] = 'Diaspora';
$a->strings['Zot!'] = 'Zot!';
$a->strings['LinkedIn'] = 'LinkedIn';
$a->strings['XMPP/IM'] = 'XMPP/IM';
$a->strings['MySpace'] = 'MySpace';
$a->strings['Google+'] = 'Google+';
$a->strings['pump.io'] = 'pump.io';
$a->strings['Twitter'] = 'Twitter';
$a->strings['Discourse'] = 'Discourse';
$a->strings['Diaspora Connector'] = 'Diaspora-liitin';
$a->strings['GNU Social Connector'] = 'GNU Social -liitin';
$a->strings['ActivityPub'] = 'ActivityPub';
$a->strings['pnut'] = 'pnut';
$a->strings['Tumblr'] = 'Tumblr';
$a->strings['Bluesky'] = 'Bluesky';
$a->strings['%s (via %s)'] = '%s (%sn kautta)';
$a->strings['and'] = 'ja';
$a->strings['and %d other people'] = 'ja %d muuta ihmistä';
$a->strings['%2$s likes this.'] = [
	0 => '%2$s tykkää tästä.',
	1 => '%2$s tykkäävät tästä.',
];
$a->strings['%2$s doesn\'t like this.'] = [
	0 => '%2$s ei tykkää tästä.',
	1 => '%2$s eivät tykkää tästä.',
];
$a->strings['%2$s attends.'] = [
	0 => '%2$s osallistuu.',
	1 => '%2$s osallistuvat.',
];
$a->strings['%2$s doesn\'t attend.'] = [
	0 => '%2$s ei osallistu.',
	1 => '%2$s eivät osallistu.',
];
$a->strings['%2$s attends maybe.'] = [
	0 => '%2$s osallistuu ehkä.',
	1 => '%2$s osallistuvat ehkä.',
];
$a->strings['%2$s reshared this.'] = [
	0 => '%2$s jakoi tämän uudelleen.',
	1 => '%2$s jakoivat tämän uudelleen',
];
$a->strings['<button type="button" %2$s>%1$d person</button> likes this'] = [
	0 => '<button type="button" %2$s>%1$d ihminen</button> tykkää tästä',
	1 => '<button type="button" %2$s>%1$d ihmistä</button> tykkää tästä',
];
$a->strings['<button type="button" %2$s>%1$d person</button> attends'] = [
	0 => '<button type="button" %2$s>%1$d ihminen</button> osallistuu',
	1 => '<button type="button" %2$s>%1$d ihmistä</button> osallistuu',
];
$a->strings['<button type="button" %2$s>%1$d person</button> doesn\'t attend'] = [
	0 => '<button type="button" %2$s>%1$d ihminen</button> ei osallistu',
	1 => '<button type="button" %2$s>%1$d ihmistä</button> ei osallistu',
];
$a->strings['<button type="button" %2$s>%1$d person</button> attends maybe'] = [
	0 => '<button type="button" %2$s>%1$d ihminen</button> saattaa osallistua',
	1 => '<button type="button" %2$s>%1$d ihmistä</button> saattaa osallistua',
];
$a->strings['<button type="button" %2$s>%1$d person</button> reshared this'] = [
	0 => '<button type="button" %2$s>%1$d ihminen</button> jakoi tämän uudelleen',
	1 => '<button type="button" %2$s>%1$d ihmistä</button> jakoi tämän uudelleen',
];
$a->strings['Visible to <strong>everybody</strong>'] = 'Näkyy <strong>kaikille</strong>';
$a->strings['Please enter a image/video/audio/webpage URL:'] = 'Syötä kuvan/videon/audion/verkkosivun URL:';
$a->strings['Tag term:'] = 'Tunniste:';
$a->strings['Save to Folder'] = 'Tallenna kansioon';
$a->strings['Where are you right now?'] = 'Missä olet nyt?';
$a->strings['Delete item(s)?'] = 'Poista kohde/kohteet?';
$a->strings['Post published.'] = 'Julkaisu julkaistu.';
$a->strings['Go to post'] = 'Siirry julkaisuun';
$a->strings['Created at'] = 'Sijainti';
$a->strings['New Post'] = 'Uusi julkaisu';
$a->strings['Share'] = 'Jaa';
$a->strings['upload photo'] = 'lähetä kuva';
$a->strings['Attach file'] = 'Liitä tiedosto';
$a->strings['attach file'] = 'liitä tiedosto';
$a->strings['Bold'] = 'Lihavoitu';
$a->strings['Italic'] = 'Kursivoitu';
$a->strings['Underline'] = 'Alleviivaus';
$a->strings['Quote'] = 'Lainaus';
$a->strings['Add emojis'] = 'Lisää emoji';
$a->strings['Content Warning'] = 'Sisältövaroitus';
$a->strings['Code'] = 'Koodi';
$a->strings['Image'] = 'Kuva';
$a->strings['Link'] = 'Linkki';
$a->strings['Link or Media'] = 'Linkki tai media';
$a->strings['Video'] = 'Video';
$a->strings['Set your location'] = 'Aseta sijaintisi';
$a->strings['set location'] = 'aseta sijainti';
$a->strings['Clear browser location'] = 'Tyhjennä selaimen sijainti';
$a->strings['clear location'] = 'tyhjennä sijainti';
$a->strings['Set title'] = 'Aseta otsikko';
$a->strings['Categories (comma-separated list)'] = 'Luokat (pilkuilla eroteltu luettelo)';
$a->strings['Scheduled at'] = 'Ajastus';
$a->strings['Permission settings'] = 'Käyttöoikeusasetukset';
$a->strings['Public post'] = 'Julkinen julkaisu';
$a->strings['Message'] = 'Viesti';
$a->strings['Add file'] = 'Lisää tiedosto';
$a->strings['Open Compose page'] = 'Avaa julkaisun kirjoitussivu';
$a->strings['remove'] = 'poista';
$a->strings['Delete Selected Items'] = 'Poista valitut kohteet';
$a->strings['You had been addressed (%s).'] = 'Sinua puhutellaan (%s).';
$a->strings['You are following %s.'] = 'Sinä seuraat %s.';
$a->strings['You subscribed to %s.'] = 'Olet tilannut %s.';
$a->strings['You subscribed to one or more tags in this post.'] = 'Olet tilannut yhden tai useamman tunnisteen, joita on käytetty tässä julkaisussa.';
$a->strings['%s reshared this.'] = '%s jatkoi tämän uudelleen.';
$a->strings['Reshared'] = 'Uudelleenjaot';
$a->strings['Reshared by %s <%s>'] = 'Uudelleenjaot %s <%s>';
$a->strings['%s is participating in this thread.'] = '%s osallistuu tähän ketjuun.';
$a->strings['Stored for general reasons'] = 'Yleisistä syistä taltioitu';
$a->strings['Global post'] = 'Globaali julkaisu';
$a->strings['Sent via an relay server'] = 'Lähetetty välittäjäpalvelimen kautta';
$a->strings['Sent via the relay server %s <%s>'] = 'Lähetetty välittäjäpalvelimen %s <%s> kautta';
$a->strings['Fetched'] = 'Noudettu';
$a->strings['Fetched because of %s <%s>'] = 'Noudettu koska %s <%s>';
$a->strings['Stored because of a child post to complete this thread.'] = 'Taltioitu lapsijulkaisun takia, jotta tämä ketju olisi täydellinen';
$a->strings['Local delivery'] = 'Paikallinen jakelu';
$a->strings['Stored because of your activity (like, comment, star, ...)'] = 'Taltioitu johtuen toiminnastasi (tykkäys, kommentti, tähti...)';
$a->strings['Distributed'] = 'Jaeltu';
$a->strings['Pushed to us'] = 'Työnnetty meille';
$a->strings['Pinned item'] = 'Kiinnitetty kohde';
$a->strings['View %s\'s profile @ %s'] = 'Katso henkilön %s profiilia @ %s';
$a->strings['Categories:'] = 'Luokat:';
$a->strings['Filed under:'] = 'Arkistoitu kansioon:';
$a->strings['%s from %s'] = '%s sovelluksesta %s';
$a->strings['View in context'] = 'Näytä kontekstissa';
$a->strings['For you'] = 'Sinulle';
$a->strings['Posts from contacts you interact with and who interact with you'] = 'Julkaisut niiltä kontakteilta, joiden kanssa olet vuorovaikutuksessa';
$a->strings['Discover'] = 'Löydöt';
$a->strings['Posts from accounts that you don\'t follow, but that you might like.'] = 'Julkaisuja tileiltä, joita et seuraa, mutta joista saatat pitää.';
$a->strings['What\'s Hot'] = 'Kuumat aiheet';
$a->strings['Posts with a lot of interactions'] = 'Julkaisut, jotka ovat keränneet paljon vuorovaikutusta';
$a->strings['Posts in %s'] = '%s julkaisut';
$a->strings['Posts from your followers that you don\'t follow'] = 'Julkaisut seuraajiltasi, joita sinä et seuraa';
$a->strings['Sharers of sharers'] = 'Jakajien jakajat';
$a->strings['Posts from accounts that are followed by accounts that you follow'] = 'Julkaisut tileiltä, joita seuraavat tilit, joita sinä itse seuraat';
$a->strings['Quiet sharers'] = 'Hiljaiset jakajat';
$a->strings['Posts from accounts that you follow but who don\'t post very often'] = 'Julkaisut tileiltä, joita seuraat, mutta jotka eivät julkaise kovin usein';
$a->strings['Images'] = 'Kuvat';
$a->strings['Posts with images'] = 'Julkaisut, joissa on kuvia';
$a->strings['Audio'] = 'Ääni';
$a->strings['Posts with audio'] = 'Julkaisut, joissa on ääntä';
$a->strings['Videos'] = 'Videot';
$a->strings['Posts with videos'] = 'Julkaisut, joissa on videoita';
$a->strings['Local Community'] = 'Paikallinen yhteisö';
$a->strings['Posts from local users on this server'] = 'Julkaisut tämän palvelimen paikallisilta käyttäjiltä';
$a->strings['Global Community'] = 'Maailmanlaajuinen yhteisö';
$a->strings['Posts from users of the whole federated network'] = 'Julkaisut koko federoidun verkoston käyttäjiltä';
$a->strings['Latest Activity'] = 'Viimeisin toiminta';
$a->strings['Sort by latest activity'] = 'Lajittele viimesimmän toiminnan mukaan';
$a->strings['Latest Posts'] = 'Viimeisimmät julkaisut';
$a->strings['Sort by post received date'] = 'Julkaisut lajiteltuna niiden saapumispäivämäärän mukaan';
$a->strings['Latest Creation'] = 'Viimeisimpänä luodut';
$a->strings['Sort by post creation date'] = 'Julkaisut lajiteltuna niiden luontipäivän mukaan';
$a->strings['Personal'] = 'Minä';
$a->strings['Posts that mention or involve you'] = 'Julkaisut, jotka liittyvät sinuun tai mainitsevat sinut';
$a->strings['Starred'] = 'Tähdelliset';
$a->strings['Favourite Posts'] = 'Suosikkijulkaisut';
$a->strings['General Features'] = 'Yleiset ominaisuudet';
$a->strings['Photo Location'] = 'Kuvan sijainti';
$a->strings['Photo metadata is normally stripped. This extracts the location (if present) prior to stripping metadata and links it to a map.'] = 'Kuvan metadata poistetaan normaalisti. Tämä ottaa paikkatiedon (jos olemassa) ennen poistoa ja linkittää sen karttaan.';
$a->strings['Display the community in the navigation'] = 'Näytä yhteisö navigaatiossa';
$a->strings['If enabled, the community can be accessed via the navigation menu. Independent from this setting, the community timelines can always be accessed via the channels.'] = 'Jos valittuna, yhteisöön on pääsy navigointivalikon kautta. Yhteisöjen aikajanoille on aina pääsy kanavien kautta tästä asetuksesta riippumatta.';
$a->strings['Post Composition Features'] = 'Kirjoittamisen ominaisuudet';
$a->strings['Explicit Mentions'] = 'Suorat maininnat';
$a->strings['Add explicit mentions to comment box for manual control over who gets mentioned in replies.'] = 'Lisää suorat maininnat kommenttikenttään hallitaksesi manuaalisesti kenet mainitaan vastauksissa.';
$a->strings['Add an abstract from ActivityPub content warnings'] = 'Lisää tiivistelmä ActivityPub:in sisältövaroituksista';
$a->strings['Add an abstract when commenting on ActivityPub posts with a content warning. Abstracts are displayed as content warning on systems like Mastodon or Pleroma.'] = 'Lisää tiivistelmä, kun kommentoit ActivityPub-julkaisuja, joissa on sisältövaroitus. Tiivistelmät näytetään sisältövaroituksina esimerkiksi Mastodonissa ja Pleromasssa.';
$a->strings['Post/Comment Tools'] = 'Julkaisu/kommentti työkalut';
$a->strings['Post Categories'] = 'Julkaisuluokat';
$a->strings['Add categories to your posts'] = 'Lisää julkaisuihisi luokkia';
$a->strings['Network Widgets'] = 'Verkkowidgetit';
$a->strings['Circles'] = 'Piirit';
$a->strings['Display posts that have been created by accounts of the selected circle.'] = 'Näyttää julkaisut, jotka ovat julkaisseet valitun piirin käyttäjät.';
$a->strings['Groups'] = 'Ryhmät';
$a->strings['Display posts that have been distributed by the selected group.'] = 'Näyttää julkaisut, jotka ovat valitun ryhmän jakelemia.';
$a->strings['Archives'] = 'Arkisto';
$a->strings['Display an archive where posts can be selected by month and year.'] = 'Näyttää arkiston, jossa julkaisuja voidaan valita kuukauden ja vuoden perusteella.';
$a->strings['Protocols'] = 'Protokollat';
$a->strings['Display posts with the selected protocols.'] = 'Näyttää valittujen protokollien mukaiset julkaisut.';
$a->strings['Account Types'] = 'Tilityypit';
$a->strings['Display posts done by accounts with the selected account type.'] = 'Näyttää julkaisut, jotka ovat valitun tilityypin julkaisemia.';
$a->strings['Channels'] = 'Kanavat';
$a->strings['Display posts in the system channels and user defined channels.'] = 'Näyttää julkaisut järjestelmäkanavissa ja käyttäjän määrittelemissä kanavissa.';
$a->strings['Saved Searches'] = 'Tallennetut haut';
$a->strings['Display posts that contain subscribed hashtags.'] = 'Näyttää julkaisut, jotka sisältävät tilatut aihetunnisteet.';
$a->strings['Saved Folders'] = 'Tallennetut kansiot';
$a->strings['Display a list of folders in which posts are stored.'] = 'Näyttää luettelon kansioista, joihin julkaisut on tallennettu.';
$a->strings['Own Contacts'] = 'Omat kontaktit';
$a->strings['Include or exclude posts from subscribed accounts. This widget is not visible on all channels.'] = 'Sisällytä tai jätä pois tilattujen tilien julkaisut. Tämä widget ei ole näkyvillä kaikissa kanavissa.';
$a->strings['Trending Tags'] = 'Trendaavat tunnisteet';
$a->strings['Display a list of the most popular tags in recent public posts.'] = 'Näyttää listan suosittuimmista tunnisteista viimeaikaisissa julkisissa julkaisuissa.';
$a->strings['Advanced Profile Settings'] = 'Profiilin lisäasetukset';
$a->strings['Tag Cloud'] = 'Tunnistepilvi';
$a->strings['Provide a personal tag cloud on your profile page'] = 'Näytä henkilökohtainen tunnistepilvi profiilisivullasi';
$a->strings['Display Membership Date'] = 'Näytä liittymispäivämäärä';
$a->strings['Display membership date in profile'] = 'Näytä liittymispäivämäärä profiilissa';
$a->strings['Advanced Calendar Settings'] = 'Kalenterin lisäasetukset';
$a->strings['Allow anonymous access to your calendar'] = 'Salli anonyymi pääsy kalenteriisi';
$a->strings['Allows anonymous visitors to consult your calendar and your public events. Contact birthday events are private to you.'] = 'Sallii anonyymien vierailijoiden tutkia kalenteriasi ja julkisia tapahtumiasi. Kontaktien syntymäpäivätapahtumat ovat yksityisiä.';
$a->strings['External link to group'] = 'Ulkoinen linkki ryhmään';
$a->strings['show less'] = 'näytä vähemmän';
$a->strings['show more'] = 'näytä lisää';
$a->strings['Create new group'] = 'Luo uusi ryhmä';
$a->strings['Find groups to join'] = 'Löydä ryhmiä, joihin liittyä';
$a->strings['event'] = 'tapahtuma';
$a->strings['status'] = 'tila';
$a->strings['photo'] = 'kuva';
$a->strings['%1$s tagged %2$s\'s %3$s with %4$s'] = '%1$s lisäsi tunnisteen "%4$s" %2$sn %3$s-julkaisuun ';
$a->strings['Follow Thread'] = 'Seuraa ketjua';
$a->strings['Complete Thread'] = 'Koko ketju';
$a->strings['View Status'] = 'Näytä tila';
$a->strings['View Profile'] = 'Näytä profiili';
$a->strings['View Photos'] = 'Näytä kuvia';
$a->strings['Network Posts'] = 'Verkoston julkaisut';
$a->strings['View Contact'] = 'Näytä kontakti';
$a->strings['Block'] = 'Estä';
$a->strings['Ignore'] = 'Jätä huomiotta';
$a->strings['Collapse'] = 'Taittele piiloon';
$a->strings['Ignore %s server'] = 'Sivuuta %s palvelin ';
$a->strings['Detected languages'] = 'Havaitut kielet';
$a->strings['Raw content'] = 'Käsittelemätön sisältö';
$a->strings['Connect/Follow'] = 'Muodosta yhteys/Seuraa';
$a->strings['Unable to fetch user.'] = 'Käyttäjää ei voitu noutaa.';
$a->strings['Undetermined'] = 'Määrittämätön';
$a->strings['%s (%s - %s): %s'] = '%s (%s–%s): %s';
$a->strings['%s (%s): %s'] = '%s (%s): %s';
$a->strings['Detected languages in this post:
%s'] = 'Tässä julkaisussa havaitut kielet:
%s';
$a->strings['Nothing new here'] = 'Täällä ei ole mitään uutta';
$a->strings['Home'] = 'Koti';
$a->strings['Skip to main content'] = 'Ohita ja siirry pääsisältöön';
$a->strings['Clear notifications'] = 'Tyhjennä ilmoitukset';
$a->strings['Search: @name, !group, #tags, content'] = 'Etsi: @nimi, !ryhmä, #tunnisteet, sisältö';
$a->strings['Sign out'] = 'Kirjaudu ulos';
$a->strings['End this session'] = 'Lopeta istunto';
$a->strings['Sign in'] = 'Kirjaudu';
$a->strings['Conversations'] = 'Keskustelut';
$a->strings['Conversations you started'] = 'Aloittamasi keskustelut';
$a->strings['Profile'] = 'Profiili';
$a->strings['Your profile page'] = 'Profiilisivusi';
$a->strings['Photos'] = 'Kuvat';
$a->strings['Your photos'] = 'Kuvasi';
$a->strings['Media'] = 'Media';
$a->strings['Your postings with media'] = 'Mediaa sisältävät julkaisusi';
$a->strings['Calendar'] = 'Kalenteri';
$a->strings['Your calendar'] = 'Kalenterisi';
$a->strings['Personal notes'] = 'Yksityismuistiinpanot';
$a->strings['Your personal notes'] = 'Yksityismuistiinpanosi';
$a->strings['Home Page'] = 'Kotisivu';
$a->strings['Register'] = 'Rekisteröidy';
$a->strings['Create an account'] = 'Luo tili';
$a->strings['Help'] = 'Ohje';
$a->strings['Help and documentation'] = 'Ohjeet ja dokumentointi';
$a->strings['Apps'] = 'Sovellukset';
$a->strings['Addon applications, utilities, games'] = 'Lisäosasovelluksia, apuohjelmia, pelejä';
$a->strings['Search'] = 'Haku';
$a->strings['Search site content'] = 'Etsi sivuston sisällöstä';
$a->strings['Full Text'] = 'Kokoteksti';
$a->strings['Contacts'] = 'Yhteystiedot';
$a->strings['Community'] = 'Yhteisö';
$a->strings['Conversations on this and other servers'] = 'Keskustelut täällä ja muilla palvelimilla';
$a->strings['Directory'] = 'Hakemisto';
$a->strings['People directory'] = 'Henkilöhakemisto';
$a->strings['Information'] = 'Tietoa';
$a->strings['Information about this friendica instance'] = 'Tietoa tästä Friendica-ilmentymästä';
$a->strings['Terms of Service'] = 'Käyttöehdot';
$a->strings['Terms of Service of this Friendica instance'] = 'Tämän Friendica-ilmentymän käyttöehdot';
$a->strings['Network'] = 'Verkko';
$a->strings['Conversations from your friends'] = 'Kavereidesi aloittamat keskustelut';
$a->strings['Your posts and conversations'] = 'Julkaisusi ja keskustelusi';
$a->strings['Introductions'] = 'Esittelyt';
$a->strings['Friend Requests'] = 'Kaveripyynnöt';
$a->strings['Notifications'] = 'Ilmoitukset';
$a->strings['View all'] = 'Näytä kaikki';
$a->strings['Mark as read'] = 'Merkitse luetuksi';
$a->strings['Mark all system notifications as seen'] = 'Merkitse kaikki järjestelmäilmoitukset nähdyiksi';
$a->strings['Private mail'] = 'Yksityisviesti';
$a->strings['Inbox'] = 'Saapuneet';
$a->strings['Outbox'] = 'Lähtevät';
$a->strings['Accounts'] = 'Tilit';
$a->strings['Manage other accounts, including groups and pages'] = 'Hallitse muita tilejä mukaan lukien ryhmät ja sivut';
$a->strings['Settings'] = 'Asetukset';
$a->strings['Account settings'] = 'Tiliasetukset';
$a->strings['Manage/edit friends and contacts'] = 'Hallinnoi ja muokkaa kavereita ja yhteystietoja';
$a->strings['Admin'] = 'Ylläpitäjä';
$a->strings['Site setup and configuration'] = 'Sivuston asennus ja asetukset';
$a->strings['Moderation'] = 'Moderointi';
$a->strings['Content and user moderation'] = 'Sisällön ja käyttäjien moderointi';
$a->strings['Navigation'] = 'Navigointi';
$a->strings['Site map'] = 'Sivustokartta';
$a->strings['first'] = 'ensimmäinen';
$a->strings['prev'] = 'edellinen';
$a->strings['next'] = 'seuraava';
$a->strings['last'] = 'viimeinen';
$a->strings['<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> %3$s'] = '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> %3$s';
$a->strings['Link to source'] = 'Linkki lähteeseen';
$a->strings['Click to open/close'] = 'Avaa ja sulje napsauttamalla';
$a->strings['$1 wrote:'] = '$1 kirjoitti:';
$a->strings['Encrypted content'] = 'Salattu sisältö';
$a->strings['Invalid source protocol'] = 'Virheellinen lähdeyhteyskäytäntö';
$a->strings['Invalid link protocol'] = 'Virheellinen linkkiyhteyskäytäntö';
$a->strings['Loading more entries...'] = 'Ladataan lisää kohteita…';
$a->strings['The end'] = 'Loppu';
$a->strings['Save search'] = 'Tallenna haku';
$a->strings['@name, !group, #tags, content'] = '@nimi, !ryhmä, #tunniste, sisältö';
$a->strings['Add New Contact'] = 'Lisää uusi yhteystieto';
$a->strings['Enter address or web location'] = 'Syötä osoite tai verkkosijainti';
$a->strings['user@x.tld, x.tld/user'] = 'user@x.tld, x.tld/user';
$a->strings['Connect'] = 'Lisää kaveriksi';
$a->strings['%d invitation available'] = [
	0 => '%d kutsu saatavilla',
	1 => '%d kutsua saatavilla',
];
$a->strings['Find People'] = 'Löydä ihmisiä';
$a->strings['Enter name or interest'] = 'Syötä nimi tai harrastus';
$a->strings['Examples: Robert Morgenstein, Fishing'] = 'Esim. Matti Meikäläinen, kalastus';
$a->strings['Find'] = 'Etsi';
$a->strings['Friend Suggestions'] = 'Kaveriehdotukset';
$a->strings['Similar Interests'] = 'Samankaltaiset mielenkiinnonkohteet';
$a->strings['Random Profile'] = 'Satunnainen profiili';
$a->strings['Invite Friends'] = 'Kutsu kavereita';
$a->strings['Global Directory'] = 'Maailmanlaajuinen hakemisto';
$a->strings['Local Directory'] = 'Paikallinen hakemisto';
$a->strings['Everyone'] = 'Kaikki';
$a->strings['No relationship'] = 'Ei suhdetta';
$a->strings['Relationships'] = 'Suhteet';
$a->strings['All Contacts'] = 'Kaikki yhteystiedot';
$a->strings['All Protocols'] = 'Kaikki yhteyskäytännöt';
$a->strings['Everything'] = 'Kaikki';
$a->strings['Categories'] = 'Luokat';
$a->strings['%d contact in common'] = [
	0 => '%d yhteinen kontakti',
	1 => '%d yhteistä yhteystietoa',
];
$a->strings['On this date'] = 'Tällä päivämäärällä';
$a->strings['Persons'] = 'Henkilöt';
$a->strings['Organisations'] = 'Organisaatiot';
$a->strings['News'] = 'Uutiset';
$a->strings['Relays'] = 'Välittäjät';
$a->strings['All'] = 'Kaikki';
$a->strings['Export'] = 'Vie';
$a->strings['Export calendar as ical'] = 'Vie kalenteri ical-tiedostoksi';
$a->strings['Export calendar as csv'] = 'Vie kalenteri CSV-tiedostoksi';
$a->strings['No contacts'] = 'Ei yhteystietoja';
$a->strings['%d Contact'] = [
	0 => '%d kontakti',
	1 => '%d kontaktia',
];
$a->strings['View Contacts'] = 'Näytä kontaktit';
$a->strings['Remove term'] = 'Poista kohde';
$a->strings['(%d hour)'] = [
	0 => '(%d tunti)',
	1 => '(%d tuntia)',
];
$a->strings['More Trending Tags'] = 'Lisää trendaavia tunnisteita';
$a->strings['Show More'] = 'Näytä enemmän';
$a->strings['Show Less'] = 'Näytä vähemmän';
$a->strings['Post to group'] = 'Julkaise ryhmään';
$a->strings['Mention'] = 'Maininta';
$a->strings['XMPP:'] = 'XMPP:';
$a->strings['Matrix:'] = 'Matrix:';
$a->strings['Location:'] = 'Sijainti:';
$a->strings['Network:'] = 'Verkosto:';
$a->strings['Follow'] = 'Seuraa';
$a->strings['Unfollow'] = 'Lopeta seuraaminen';
$a->strings['View group'] = 'Näytä ryhmä';
$a->strings['Yourself'] = 'Sinä itse';
$a->strings['Mutuals'] = 'Keskinäiset';
$a->strings['Post to Email'] = 'Viesti sähköpostiin';
$a->strings['Public'] = 'Julkinen';
$a->strings['This content will be shown to all your followers and can be seen in the community pages and by anyone with its link.'] = 'Tämä sisältö näytetään kaikille seuraajillesi. Se on nähtävillä yhteisösivuilla ja kenelle tahansa, joilla on linkki sisältöön.';
$a->strings['Limited/Private'] = 'Rajoitettu/Yksityinen';
$a->strings['This content will be shown only to the people in the first box, to the exception of the people mentioned in the second box. It won\'t appear anywhere public.'] = 'Tämä sisältö näytetään vain henkilöille, jotka ovat ensimmäisessä kentässä, paitsi niille henkilöille, jotka on mainittu seuraavassa kentässä. Se ei näy missään julkisesti.';
$a->strings['Start typing the name of a contact or a circle to show a filtered list. You can also mention the special circles "Followers" and "Mutuals".'] = 'Suodata luetteloa alkamalla kirjoittaa yhteystiedon tai piirin nimeä. Myös erikoispiirit ”seuraajat” ja ”keskinäiset” voi mainita.';
$a->strings['Show to:'] = 'Näytä seuraaville:';
$a->strings['Except to:'] = 'Paitsi näille:';
$a->strings['CC: email addresses'] = 'Kopio: sähköpostiosoitteet';
$a->strings['Example: bob@example.com, mary@example.com'] = 'Esimerkki: maija@esimerkki.fi, kalle@esimerkki.fi';
$a->strings['Connectors'] = 'Yhdistäjät';
$a->strings['The database configuration file "config/local.config.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.'] = 'Tietokannan asetustiedostoa ”config/local.config.php” ei voitu kirjoittaa. Luo asetustiedosto verkkopalvelimen juureen käyttämällä oheista tekstiä.';
$a->strings['You may need to import the file "database.sql" manually using phpmyadmin or mysql.'] = '”database.sql”-tiedosto on ehkä luotava käsin phpmyadminilla tai mysql:llä.';
$a->strings['Please see the file "doc/INSTALL.md".'] = 'Katso tiedostosta ”doc/INSTALL.md”.';
$a->strings['Could not find a command line version of PHP in the web server PATH.'] = 'Verkkopalvelimen PATH-hakupolusta ei löytynyt PHP:n komentoriviversiota.';
$a->strings['If you don\'t have a command line version of PHP installed on your server, you will not be able to run the background processing. See <a href=\'https://github.com/friendica/friendica/blob/stable/doc/Install.md#set-up-the-worker\'>\'Setup the worker\'</a>'] = 'Jos PHP:n komentoriviversiota ei ole asennettu palvelimeen, taustakäsittelyä ei voi tehdä. Katso: <a href=\'https://github.com/friendica/friendica/blob/stable/doc/Install.md#set-up-the-worker\'>”Setup the worker”</a>';
$a->strings['PHP executable path'] = 'PHP-ohjelmatiedoston sijainti';
$a->strings['Enter full path to php executable. You can leave this blank to continue the installation.'] = 'Kirjoita PHP-ohjelmatiedoston täydellinen sijainti. Asennusta voi jatkaa jättämällä tämän tyhjäksi.';
$a->strings['Command line PHP'] = 'Komentorivi-PHP';
$a->strings['PHP executable is not the php cli binary (could be cgi-fgci version)'] = 'PHP-ohjelmatiedosto ei ole PHP:n komentoriviohjelmatiedosto (saattaa olla cgi-fgci-versio)';
$a->strings['Found PHP version: '] = 'Löytyi PHP-versio:';
$a->strings['PHP cli binary'] = 'PHP:n komentoriviohjelmatiedosto';
$a->strings['The command line version of PHP on your system does not have "register_argc_argv" enabled.'] = 'Järjestelmäsi komentorivi-PHP:ssä ei ole käytössä asetusta ”register_argc_argv”.';
$a->strings['This is required for message delivery to work.'] = 'Tätä vaaditaan viestinvälityksen toimimiseksi.';
$a->strings['PHP register_argc_argv'] = 'PHP register_argc_argv';
$a->strings['Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys'] = 'Virhe: järjestelmäsi "openssl_pkey_new" -funktio ei pysty generoimaan salausavaimia.';
$a->strings['If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".'] = 'Jos on kyse Windows-pavelimesta, katso "http://www.php.net/manual/en/openssl.installation.php".';
$a->strings['Generate encryption keys'] = 'Luo salausavaimet';
$a->strings['Error: Apache webserver mod-rewrite module is required but not installed.'] = 'Virhe: Apache-palvelimen mod-rewrite-moduuli vaaditaan, mutta sitä ei ole asennettu.';
$a->strings['Apache mod_rewrite module'] = 'Apachen mod_rewrite-moduuli';
$a->strings['Error: PDO or MySQLi PHP module required but not installed.'] = 'Virhe: PDO:n tai MySQLi:n PHP-moduuli vaaditaan, mutta sitä ei ole asennettu.';
$a->strings['Error: The MySQL driver for PDO is not installed.'] = 'Virhe: PDO:n MySQL-ajuria ei ole asennettu';
$a->strings['PDO or MySQLi PHP module'] = 'PDO:n tai MySQLi:n PHP-moduuli';
$a->strings['Error: The IntlChar module is not installed.'] = 'Virhe: IntlChar-moduulia ei ole asennettu.';
$a->strings['IntlChar PHP module'] = 'IntlCharin PHP-moduuli';
$a->strings['Error, XML PHP module required but not installed.'] = 'Virhe: XML PHP-moduuli vaaditaan, mutta sitä ei ole asennettu.';
$a->strings['XML PHP module'] = 'XML:n PHP-moduuli';
$a->strings['libCurl PHP module'] = 'libCurlin PHP-moduuli';
$a->strings['Error: libCURL PHP module required but not installed.'] = 'Virhe: libCURLin PHP-moduuli vaaditaan, mutta sitä ei ole asennettu.';
$a->strings['GD graphics PHP module'] = 'GD graphicsin PHP-moduuli';
$a->strings['Error: GD graphics PHP module with JPEG support required but not installed.'] = 'Virhe: GD graphicsin JPEG-tuellinen PHP-moduuli vaaditaan, mutta sitä ei ole asennettu.';
$a->strings['OpenSSL PHP module'] = 'OpenSSL:n PHP-moduuli';
$a->strings['Error: openssl PHP module required but not installed.'] = 'Virhe: openssl:n PHP-moduuli vaaditaan, mutta sitä ei ole asennettu.';
$a->strings['mb_string PHP module'] = 'PHP-moduuli mb_string';
$a->strings['Error: mb_string PHP module required but not installed.'] = 'Virhe: PHP-moduuli mb_string vaaditaan, mutta sitä ei ole asennettu.';
$a->strings['iconv PHP module'] = 'iconv PHP-moduuli';
$a->strings['Error: iconv PHP module required but not installed.'] = 'Virhe: iconv PHP-moduuli vaaditaan, mutta sitä ei ole asennettu.';
$a->strings['POSIX PHP module'] = 'POSIX PHP-moduuli';
$a->strings['Error: POSIX PHP module required but not installed.'] = 'Virhe: POSIX PHP-moduuli vaadittaan, mutta sitä ei ole asennettu.';
$a->strings['Program execution functions'] = 'Ohjelman suoritusfunktiot';
$a->strings['Error: Program execution functions (proc_open) required but not enabled.'] = 'Virhe: Ohjelman suoritusfunktiot (proc_open) vaaditaan, mutta niitä ei ole aktivoitu.';
$a->strings['JSON PHP module'] = 'JSON PHP -moduuli';
$a->strings['Error: JSON PHP module required but not installed.'] = 'Virhe: JSON PHP -moduuli vaaditaan, mutta sitä ei ole asennettu.';
$a->strings['File Information PHP module'] = 'Tiedoston tietojen PHP -moduuli';
$a->strings['Error: File Information PHP module required but not installed.'] = 'Virhe: Tiedoston tietojen PHP -moduuli vaaditaan, mutta sitä ei ole asennettu.';
$a->strings['GNU Multiple Precision PHP module'] = 'GNU Multiple Precision PHP -moduuli';
$a->strings['Error: GNU Multiple Precision PHP module required but not installed.'] = 'Virhe: GNU Multiple Precision PHP -moduuli vaaditaan, mutta sitä ei ole asennettu.';
$a->strings['IDN Functions PHP module'] = 'IDN Functions PHP -moduuli';
$a->strings['Error: IDN Functions PHP module required but not installed.'] = 'Virhe: IDN Functions PHP -moduuli vaaditaan, mutta sitä ei ole asennettu.';
$a->strings['The web installer needs to be able to create a file called "local.config.php" in the "config" folder of your web server and it is unable to do so.'] = 'Web-asennuksen tulee pystyä luomaan tiedosto "local.config.php" hakemistoon "config" verkkopalvelimellasi, mutta tämä epäonnistui.';
$a->strings['This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.'] = 'Tämä on yleensä käyttöoikeusasetus, jolloin web-palvelimesi ei pysty kirjoittamaan tiedostoja kansioosi, vaikka itse siihen pystyisit.';
$a->strings['At the end of this procedure, we will give you a text to save in a file named local.config.php in your Friendica "config" folder.'] = 'Annamme tämän proseduurin lopussa sinulle tekstin, joka sinun pitää tallentaa tiedostoon nimeltä local.config.php Friendican config-kansioon.';
$a->strings['You can alternatively skip this procedure and perform a manual installation. Please see the file "doc/INSTALL.md" for instructions.'] = 'Voit myös ohittaa tämän kohdan ja asentaa ohjelman manuaalisesti. Lisätiedot löydät tiedostosta "doc/INSTALL.md".';
$a->strings['config/local.config.php is writable'] = 'config/local.config.php on kirjoitus-tilassa';
$a->strings['Friendica uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.'] = 'Friendica käyttää Smarty3 template engineä webbinäkymien kuvantamisessa. Smarty3 kääntää sivupohjat PHP-muotoon kuvantamisprosessin nopeuttamiseksi.';
$a->strings['In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top level folder.'] = 'Tallentaakseen käännetyt mallit on verkkopalvelimella oltava kirjoitusoikeus hakemistoon "view/smarty3" Friendican päähakemistossa.';
$a->strings['Please ensure that the user that your web server runs as (e.g. www-data) has write access to this folder.'] = 'Varmistathan, että verkkopalvelimen käyttäjällä (esim. www-data) on kirjoitusoikeus tähän kansioon.';
$a->strings['Note: as a security measure, you should give the web server write access to view/smarty3/ only--not the template files (.tpl) that it contains.'] = 'Huomaa: Turvallisuuden vuoksi sinun pitää antaa web-palvelimelle kirjoitusoikeus vain kansioon view/smarty3/, ei template-tiedostoihin (.tpl), jotka kansio sisältää.';
$a->strings['view/smarty3 is writable'] = 'view/smarty3 on kirjoitettava';
$a->strings['Url rewrite in .htaccess seems not working. Make sure you copied .htaccess-dist to .htaccess.'] = 'Url rewrite (uudelleen muotoilu) tiedostossa ".htaccess" ei näytä toimivan. Varmistathan, että kopioit tiedoston ".htaccess-dist" nimelle ".htaccess".';
$a->strings['In some circumstances (like running inside containers), you can skip this error.'] = 'Joissakin tilanteissa (kuten säiliöiden sisällä suoritettaessa) voit ohittaa tämän virheen.';
$a->strings['Error message from Curl when fetching'] = 'Curlin virheviesti tietoja haettaessa';
$a->strings['Url rewrite is working'] = 'URL-osoitteen uudellenkirjoitus toimii';
$a->strings['The detection of TLS to secure the communication between the browser and the new Friendica server failed.'] = 'TLS:n tunnistaminen selaimen ja uuden Friendica-palvelimen yhteyden salaamiseksi epäonnistui.';
$a->strings['It is highly encouraged to use Friendica only over a secure connection as sensitive information like passwords will be transmitted.'] = 'Friendicaa suositellaan ehdottomasti käyttämään vain salatussa yhteydessä, koska välitetään myös arkaluonteisia tietoja kuten salasanoja.';
$a->strings['Please ensure that the connection to the server is secure.'] = 'Varmista, että palvelinyhteys on salattu.';
$a->strings['No TLS detected'] = 'TLS:ää ei tunnistettu';
$a->strings['TLS detected'] = 'TLS tunnistettu';
$a->strings['ImageMagick PHP extension is not installed'] = 'ImageMagickin PHP-laajennusta ei ole asennettu';
$a->strings['ImageMagick PHP extension is installed'] = 'ImageMagickin PHP-laajennus on asennettu';
$a->strings['Database already in use.'] = 'Tietokanta on jo käytössä.';
$a->strings['Could not connect to database.'] = 'Tietokantaan ei saada yhteyttä.';
$a->strings['%s (%s)'] = '%s (%s)';
$a->strings['Monday'] = 'maanantai';
$a->strings['Tuesday'] = 'tiistai';
$a->strings['Wednesday'] = 'keskiviikko';
$a->strings['Thursday'] = 'torstai';
$a->strings['Friday'] = 'perjantai';
$a->strings['Saturday'] = 'lauantai';
$a->strings['Sunday'] = 'sunnuntai';
$a->strings['January'] = 'tammikuu';
$a->strings['February'] = 'helmikuu';
$a->strings['March'] = 'maaliskuu';
$a->strings['April'] = 'huhtikuu';
$a->strings['May'] = 'toukokuu';
$a->strings['June'] = 'kesäkuu';
$a->strings['July'] = 'heinäkuu';
$a->strings['August'] = 'elokuu';
$a->strings['September'] = 'syyskuu';
$a->strings['October'] = 'lokakuu';
$a->strings['November'] = 'marraskuu';
$a->strings['December'] = 'joulukuu';
$a->strings['Mon'] = 'ma';
$a->strings['Tue'] = 'ti';
$a->strings['Wed'] = 'ke';
$a->strings['Thu'] = 'to';
$a->strings['Fri'] = 'pe';
$a->strings['Sat'] = 'la';
$a->strings['Sun'] = 'su';
$a->strings['Jan'] = 'tammi';
$a->strings['Feb'] = 'helmi';
$a->strings['Mar'] = 'maalis';
$a->strings['Apr'] = 'huhti';
$a->strings['Jun'] = 'kesä';
$a->strings['Jul'] = 'heinä';
$a->strings['Aug'] = 'elo';
$a->strings['Sep'] = 'syys';
$a->strings['Oct'] = 'loka';
$a->strings['Nov'] = 'marras';
$a->strings['Dec'] = 'joulu';
$a->strings['The logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'Lokitiedosto \'%s\' ei ole kelvollinen. Lokikirjaus ei ole mahdollinen (virhe: \'%s\')';
$a->strings['Friendica can\'t display this page at the moment, please contact the administrator.'] = 'Friendica ei voi näyttää tätä sivua juuri nyt. Ota yhteys ylläpitoon.';
$a->strings['template engine cannot be registered without a name.'] = 'mallinekonetta ei voi rekisteröidä ilman nimeä.';
$a->strings['template engine is not registered!'] = 'mallinekonetta ei ole rekisteröity!';
$a->strings['Storage base path'] = 'Tallennuksen kantapolku';
$a->strings['Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree'] = 'Tiedosto, johon palvelimelle ladatut tiedostot tallennetaan. Turvallisuuden takaamiseksi tämän pitää olla polku, joka ei ole osa verkkopalvelimen kansiorakennetta.';
$a->strings['Enter a valid existing folder'] = 'Syötä pätevä olemassa oleva kansio';
$a->strings['%s: executing pre update %d'] = '%s: suoritetaan esipäivitystä %d';
$a->strings['%s: executing post update %d'] = '%s: suoritetaan jälkipäivitystä %d';
$a->strings['Update %s failed. See error logs.'] = '%s päivitys epäonnistui, katso virhelokit.';
$a->strings['
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can\'t do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.'] = '
				Friendican kehittäjät julkaisivat hiljattain %s-päivityksen,
				mutta kun yritin asentaa sitä, asennuksessa meni jotain pahasti pieleen.
				Tämä on korjattava pian, mutta en voi tehdä sitä itse. Ota yhteyttä
				Friendica-kehittäjään jos et voi auttaa minua itse. Tietokantani saattaa olla virheellinen.';
$a->strings['The error message is\n[pre]%s[/pre]'] = 'Virheviesti on \n[pre]%s[/pre]';
$a->strings['[Friendica Notify] Database update'] = '[Friendica-ilmoitus] Tietokannan päivitys';
$a->strings['
				The friendica database was successfully updated from %s to %s.'] = '
				Frendican tietokanta päivitettiin onnistuneesti versiosta %s versioon %s.';
$a->strings['The database version had been set to %s.'] = 'Tietokannan versioksi on asetettu %s.';
$a->strings['No unused tables found.'] = 'Käyttämättömiä taulukkoja ei löytynyt';
$a->strings['
Error %d occurred during database update:
%s
'] = '
%d virhe tapahtui tietokannan päivityksen aikana:
%s
';
$a->strings['Errors encountered performing database changes: '] = 'Tietokannan muokkauksessa tapahtui virheitä:';
$a->strings['Another database update is currently running.'] = 'Toinen tietokantapäivitys on paraikaa meneillään.';
$a->strings['%s: Database update'] = '%s: Tietokantapäivitys';
$a->strings['%s: updating %s table.'] = '%s: päivitetään %s-taulukkoa.';
$a->strings['Record not found'] = 'Tietuetta ei löytynyt';
$a->strings['Unprocessable Entity'] = 'Entiteetti, jota ei voi käsitellä';
$a->strings['Unauthorized'] = 'Ei lupaa';
$a->strings['Token is not authorized with a valid user or is missing a required scope'] = 'Turvallisuusavainta ei ole valtuutettu pätevällä käyttäjällä tai siltä puuttuu vaadittu laajuus';
$a->strings['Internal Server Error'] = 'Sisäinen palvelinvirhe';
$a->strings['A deleted circle with this name was revived. Existing item permissions <strong>may</strong> apply to this circle and any future members. If this is not what you intended, please create another circle with a different name.'] = 'Kertalleen poistettu samanniminen piiri elvytettiin. Olemassa olevat kohteen käyttöoikeudet <strong>saattavat</strong> koskea tätä piiriä ja sen tulevia jäseniä. Jos tämä ei ollut tarkoituksesi, luo uusi piiri eri nimellä.';
$a->strings['Everybody'] = 'Kaikki';
$a->strings['edit'] = 'muokkaa';
$a->strings['add'] = 'lisää';
$a->strings['Edit circle'] = 'Muokkaa piiriä';
$a->strings['Contacts not in any circle'] = 'Kontaktit, jotka eivät ole missään piirissä';
$a->strings['Create a new circle'] = 'Luo uusi piiri';
$a->strings['Circle Name: '] = 'Piirin nimi:';
$a->strings['Edit circles'] = 'Muokkaa piirejä';
$a->strings['Approve'] = 'Hyväksy';
$a->strings['%s has blocked you'] = '%s on estänyt sinut';
$a->strings['Organisation'] = 'Järjestö';
$a->strings['Group'] = 'Ryhmä';
$a->strings['Relay'] = 'Välittäjä';
$a->strings['Disallowed profile URL.'] = 'Kielletty profiiliosoite.';
$a->strings['Blocked domain'] = 'Estetty verkkotunnus';
$a->strings['Connect URL missing.'] = 'Yhteys URL-linkki puuttuu.';
$a->strings['The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.'] = 'Kontaktia ei pystytty lisäämään. Tarkista verkkoasetukset omista asetuksistasi (Settings -> Social Networks).';
$a->strings['Expected network %s does not match actual network %s'] = 'Odotettu verkko %s ei vastaa todellista verkkoa %s';
$a->strings['This seems to be a relay account. They can\'t be followed by users.'] = 'Tämä vaikuttaa olevan välittäjätili. Käyttäjät eivät voi seurata välittäjätilejä.';
$a->strings['The profile address specified does not provide adequate information.'] = 'Annettu profiiliosoite ei sisällä riittävästi tietoa.';
$a->strings['No compatible communication protocols or feeds were discovered.'] = 'Yhteensopivia viestintäprotokolleja tai syötteitä ei löytynyt.';
$a->strings['An author or name was not found.'] = 'Julkaisija tai nimi puuttuu.';
$a->strings['No browser URL could be matched to this address.'] = 'Mitään selaimen URL-osoitetta ei voitu yhdistää tähän osoitteeseen.';
$a->strings['Use mailto: in front of address to force email check.'] = 'Käytä "mailto:" osoitteen edessä pakottaaksesi sähköpostin tarkastuksen.';
$a->strings['Limited profile. This person will be unable to receive direct/personal notifications from you.'] = 'Rajoitettu profiili. Tämä henkilö ei saa suoria/henkilökohtaisia ilmoituksia sinulta.';
$a->strings['Unable to retrieve contact information.'] = 'Kontaktin tietoja ei voitu hakea.';
$a->strings['l F d, Y \@ g:i A \G\M\TP (e)'] = 'l F d, Y \@ g:i A \G\M\TP (e)';
$a->strings['Starts:'] = 'Alkaa:';
$a->strings['Finishes:'] = 'Päättyy:';
$a->strings['all-day'] = 'koko päivä';
$a->strings['Sept'] = 'Syy.';
$a->strings['today'] = 'tänään';
$a->strings['month'] = 'kuukausi';
$a->strings['week'] = 'viikko';
$a->strings['day'] = 'päivä';
$a->strings['No events to display'] = 'Ei näytettäviä tapahtumia.';
$a->strings['Access to this profile has been restricted.'] = 'Pääsy tähän profiiliin on rajoitettu';
$a->strings['Event not found.'] = 'Tapahtumaa ei löytynyt.';
$a->strings['l, F j'] = 'l, F j';
$a->strings['Edit event'] = 'Muokkaa tapahtumaa';
$a->strings['Duplicate event'] = 'Monista tapahtuma';
$a->strings['Delete event'] = 'Poista tapahtuma';
$a->strings['l F d, Y \@ g:i A'] = 'l j.n.Y \@ H:i';
$a->strings['D g:i A'] = 'D H:i';
$a->strings['g:i A'] = 'H:i';
$a->strings['e'] = 'e';
$a->strings['Show map'] = 'Näytä kartta';
$a->strings['Hide map'] = 'Piilota kartta';
$a->strings['%s\'s birthday'] = '%s: syntymäpäivä';
$a->strings['Happy Birthday %s'] = 'Hyvää syntymäpäivää %s';
$a->strings['activity'] = 'toiminta';
$a->strings['comment'] = 'kommentti';
$a->strings['post'] = 'julkaisu';
$a->strings['%s is blocked'] = '%s on estetty';
$a->strings['%s is ignored'] = '%s on sivuutettu';
$a->strings['Content from %s is collapsed'] = 'Sisältö käyttäjältä %s on taiteltu piiloon';
$a->strings['Sensitive content'] = 'Arkaluonteista sisältöä';
$a->strings['bytes'] = 'tavua';
$a->strings['The media in this post is not displayed to visitors. To view it, please go to the <a href="%s">original post</a>.'] = 'Tässä julkaisussa oleva media ei näy vierailijoille. Nähdäksesi median, katso <a href="%s">alkuperäinen julkaisu</a>.';
$a->strings['The media in this post is not displayed to visitors. To view it, please log in.'] = 'Tässä julkaisussa oleva media ei näy vierailijoille. Nähdäksesi median, kirjaudu sisään.';
$a->strings['%2$s (%3$d%%, %1$d vote)'] = [
	0 => '%2$s (%3$d%%, %1$d ääni)',
	1 => '%2$s (%3$d%%, %1$d ääntä)',
];
$a->strings['%2$s (%1$d vote)'] = [
	0 => '%2$s (%1$d ääni)',
	1 => '%2$s (%1$d ääntä)',
];
$a->strings['%d voter. Poll end: %s'] = [
	0 => '%d äänestäjä. Kysely päättyy: %s',
	1 => '%d äänestäjää. Kysely päättyy: %s',
];
$a->strings['%d voter.'] = [
	0 => '%d äänestäjä.',
	1 => '%d äänestäjää.',
];
$a->strings['Poll end: %s'] = 'Kysely päättyy: %s';
$a->strings['View on separate page'] = 'Katso erillisellä sivulla';
$a->strings['[no subject]'] = '[ei aihetta]';
$a->strings['Wall Photos'] = 'Seinäkuvat';
$a->strings['Change profile picture'] = 'Vaihda profiilikuva';
$a->strings['Homepage:'] = 'Kotisivu:';
$a->strings['About:'] = 'Lisätietoja:';
$a->strings['Atom feed'] = 'Atom -syöte';
$a->strings['This website has been verified to belong to the same person.'] = 'Tämän verkkosivun on vahvistettu kuuluvan samalle henkilölle.';
$a->strings['F d'] = 'F d';
$a->strings['[today]'] = '[tänään]';
$a->strings['Birthday Reminders'] = 'Syntymäpäivämuistutukset';
$a->strings['Birthdays this week:'] = 'Syntymäpäiviä tällä viikolla:';
$a->strings['g A l F d'] = 'g A l F d';
$a->strings['[No description]'] = '[Ei kuvausta]';
$a->strings['Event Reminders'] = 'Tapahtumamuistutukset';
$a->strings['Upcoming events the next 7 days:'] = 'Tulevat tapahtumat seuraavan 7 päivän aikana:';
$a->strings['Hometown:'] = 'Kotikaupunki:';
$a->strings['Marital Status:'] = 'Siviilisääty:';
$a->strings['With:'] = 'Kanssa:';
$a->strings['Since:'] = 'Alkaen:';
$a->strings['Sexual Preference:'] = 'Seksuaalinen suuntautuminen:';
$a->strings['Political Views:'] = 'Poliittinen näkemys:';
$a->strings['Religious Views:'] = 'Uskonnollinen näkemys:';
$a->strings['Likes:'] = 'Tykkäykset:';
$a->strings['Dislikes:'] = 'Ei tykkää:';
$a->strings['Title/Description:'] = 'Otsikko/kuvaus:';
$a->strings['Summary'] = 'Yhteenveto';
$a->strings['Musical interests'] = 'Musiikki';
$a->strings['Books, literature'] = 'Kirjat, kirjallisuus';
$a->strings['Television'] = 'Televisio';
$a->strings['Film/dance/culture/entertainment'] = 'Elokuvat/tanssi/kulttuuri/viihde';
$a->strings['Hobbies/Interests'] = 'Harrastukset';
$a->strings['Love/romance'] = 'Rakkaus/romanssi';
$a->strings['Work/employment'] = 'Työ:';
$a->strings['School/education'] = 'Koulutus:';
$a->strings['Contact information and Social Networks'] = 'Yhteystiedot ja sosiaalinen media';
$a->strings['Responsible account: %s'] = 'Vastuutili: %s';
$a->strings['SERIOUS ERROR: Generation of security keys failed.'] = 'VAKAVA VIRHE: Salausavainten luominen epäonnistui.';
$a->strings['Login failed'] = 'Kirjautuminen epäonnistui';
$a->strings['Not enough information to authenticate'] = 'Ei tarpeeksi tietoja kirjautumiseen';
$a->strings['Password can\'t be empty'] = 'Salasanakenttä ei voi olla tyhjä';
$a->strings['Empty passwords are not allowed.'] = 'Tyhjä salasana ei ole sallittu.';
$a->strings['The new password has been exposed in a public data dump, please choose another.'] = 'Uusi salasana on paljastunut julkisessa tietovuodossa, valitse joku toinen salasana.';
$a->strings['The password length is limited to 72 characters.'] = 'Salasanan enimmäispituus on 72 merkkiä.';
$a->strings['The password can\'t contain white spaces nor accentuated letters'] = 'Salasana ei saa sisältää välilyöntejä eikä kirjaimia, joissa on aksenttimerkki.';
$a->strings['Passwords do not match. Password unchanged.'] = 'Salasanat eivät täsmää. Salasana ennallaan.';
$a->strings['An invitation is required.'] = 'Vaaditaa kutsu.';
$a->strings['Invitation could not be verified.'] = 'Kutsua ei voitu vahvistaa.';
$a->strings['Invalid OpenID url'] = 'Virheellinen OpenID-verkko-osoite';
$a->strings['We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.'] = 'Kohtasimme ongelman antamasi OpenID:n kanssa. Ole hyvä ja tarkista ID:n oikea kirjoitusasu.';
$a->strings['The error message was:'] = 'Virheviesti oli:';
$a->strings['Please enter the required information.'] = 'Syötä tarvittavat tiedot.';
$a->strings['system.username_min_length (%s) and system.username_max_length (%s) are excluding each other, swapping values.'] = 'system.username_min_length (%s) ja system.username_max_length (%s) sulkevat toisensa pois vaihtaen arvonsa keskenään.';
$a->strings['Username should be at least %s character.'] = [
	0 => 'Käyttäjänimessä pitää olla vähintään %s merkki.',
	1 => 'Käyttäjänimessä pitää olla vähintään %s merkkiä.',
];
$a->strings['Username should be at most %s character.'] = [
	0 => 'Käyttäjänimessä voi olla enintään %s merkki.',
	1 => 'Käyttäjänimessä voi olla korkeintaan %s merkkiä.',
];
$a->strings['That doesn\'t appear to be your full (First Last) name.'] = 'Tuo ei vaikuta koko nimeltäsi (Etunimi Sukunimi).';
$a->strings['Your email domain is not among those allowed on this site.'] = 'Sähköpostiosoitteesi verkkotunnus on tämän sivuston estolistalla.';
$a->strings['Not a valid email address.'] = 'Virheellinen sähköpostiosoite.';
$a->strings['The nickname was blocked from registration by the nodes admin.'] = 'Solmun ylläpitäjä on estänyt tämän lempinimen rekisteröinnin.';
$a->strings['Cannot use that email.'] = 'Sähköpostiosoitetta ei voitu käyttää.';
$a->strings['Your nickname can only contain a-z, 0-9 and _.'] = 'Nimimerkki voi sisältää merkkejä a–z, 0–9 ja _.';
$a->strings['Nickname is already registered. Please choose another.'] = 'Valitsemasi nimimerkki on jo käytössä. Valitse toinen nimimerkki.';
$a->strings['An error occurred during registration. Please try again.'] = 'Rekisteröityminen epäonnistui. Yritä uudelleen.';
$a->strings['An error occurred creating your default profile. Please try again.'] = 'Oletusprofiilin luominen epäonnistui. Yritä uudelleen.';
$a->strings['An error occurred creating your self contact. Please try again.'] = 'Yhteystietojesi luonti epäonnistui. Yritä uudelleen.';
$a->strings['An error occurred creating your default contact circle. Please try again.'] = 'Oletusyhteystietopiirin luonnissa tapahtui virhe. Yritä uudelleen.';
$a->strings['Profile Photos'] = 'Profiilin valokuvat';
$a->strings['
		Dear %1$s,
			the administrator of %2$s has set up an account for you.'] = '
		Hei %1$s
			%2$s:n ylläpitäjä on luonut sinulle tilin.';
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
		Kirjautumistiedot ovat seuraavat:

		Sivuston sijainti:	%1$s
		Käyttäjätunnus:		%2$s
		Salasana:		%3$s

		Kirjauduttuasi voit vaihtaa salasanasi tilisi asetussivulta.

		Käytä hetki saman sivun muiden tiliasetusten tarkistamiseen.

		Saatat myös haluta lisätä oletusprofiiliisi joitakin perustietoja
		(”Profiilit”-sivulla), jotta muiden on helpompi löytää sinut.

		Suosittelemme lisäämään profiilikuvan, joitakin profiilin ”hakusanoja”
		(avuksi ystävystyttäessä) ja ehkä missä maassa asut;
		sitä tarkempia tietoja ei tarvitse antaa.

		Kunnioitamme täysin yksityisyyttäsi, eikä mikään näistä ole pakollinen.
		Jos kuitenkin olet uusi etkä tunne täällä ketään, ne voivat auttaa sinua
		löytämään uusia ja kiinnostavia tuttavuuksia.

		Jos koskaan haluat poistaa tilisi, se onnistuu täällä: %1$s/settings/removeme

		Kiitoksia ja tervetuloa! – %4$s';
$a->strings['Registration details for %s'] = 'Rekisteröitymistiedot: %s';
$a->strings['
			Dear %1$s,
				Thank you for registering at %2$s. Your account is pending for approval by the administrator.

			Your login details are as follows:

			Site Location:	%3$s
			Login Name:		%4$s
			Password:		%5$s
		'] = '
			Hyvä %1$s!
				Kiitos rekisteröitymisestäsi (%2$s). Tilisi odottaa ylläpidon hyväksyntää.

			Kirjautumistietosi ovat seuraavat:

			Sivuston sijainti:	%3$s
			Käyttäjätunnus:		%4$s
			Salasana:		%5$s
		';
$a->strings['Registration at %s'] = 'Rekisteröityminen kohteessa %s';
$a->strings['
				Dear %1$s,
				Thank you for registering at %2$s. Your account has been created.
			'] = '
				Hei %1$s
				Kiitos rekisteröitymisestäsi sivulle %2$s. Tilisi on nyt luotu.
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
			Sisäänkirjautumistietosi ovat seuraavanlaiset:

			Sivuston osoite:	%3$s
			Käyttäjänimi:		%1$s
			Salasana:		%5$s

			Saatat haluta vaihtaa tilisi salasanan tilisi Asetukset-sivullla kun olet kirjautunut sisään.

			Käytäthän hieman aikaa myös muiden tiliasetusten läpikäymiseen samaisella sivulla.

			Saatat myös haluta lisätä joitain perustietoja oletusprofiiliisi (Profiilit-sivulla)
			niin, että muut löytävät sinut helposti.

			Suosittelemme, että lisäät profiilikuvan, joitakin profiilin "avainsanoja" (ne ovat
			hyödyllisiä uusien kavereiden löytämisessä) ja kenties asuinmaasi, jos et halua
			olla sitä tarkempi.

			Kunnioitamme oikeuttasi yksityisyyteeni täysin eikä mikään edellisistä ole tarpeen.
			Jos olet paikalla ensimmäistä kertaa etkä tunne täältä vielä ketään, saattaa niistä
			kuitenkin olla apua uusiin mielenkiintoisiin ystäviin tutustumisessa.

			Jos haluat joskus poistaa tilisi, voit tehdä se osoitteessa %3$s/settings/removeme

			Kiitos ja tervetuloa sivustolle %2$s.';
$a->strings['User with delegates can\'t be removed, please remove delegate users first'] = 'Käyttäjää, jolla on edustaja/edustajia, ei voida poistaa. Poista edustaja(t) ensin.';
$a->strings['Addon not found.'] = 'Lisäosaa ei löydy.';
$a->strings['Addon %s disabled.'] = 'Lisäosa %s poistettu käytöstä.';
$a->strings['Addon %s enabled.'] = 'Lisäosa %s käytössä.';
$a->strings['Disable'] = 'Poista käytöstä';
$a->strings['Enable'] = 'Ota käyttöön';
$a->strings['Invalid Addon found.'] = 'Virheellinen lisäosa löydetty.';
$a->strings['Administration'] = 'Ylläpito';
$a->strings['Addons'] = 'Lisäosat';
$a->strings['Toggle'] = 'Vaihda';
$a->strings['Author: '] = 'Tekijä';
$a->strings['Maintainer: '] = 'Ylläpitäjä:';
$a->strings['Addons reloaded'] = 'Lisäosat ladattu uudelleen';
$a->strings['Addon %s failed to install.'] = 'Lisäosan %s asennus epäonnistui.';
$a->strings['Save Settings'] = 'Tallenna asetukset';
$a->strings['Reload active addons'] = 'Lataa aktiiviset lisäosat uudelleen';
$a->strings['There are currently no addons available on your node. You can find the official addon repository at %1$s.'] = 'Solmullasi ei ole tällä hetkellä lisäosia saatavilla. Voit etsiä lisäosia virallisesta lisäosavarastosta osoitteessa %1$s.';
$a->strings['Update has been marked successful'] = 'Päivitys on merkitty onnistuneeksi';
$a->strings['Database structure update %s was successfully applied.'] = 'Tietokannan rakenteen %s-päivitys onnistui.';
$a->strings['Executing of database structure update %s failed with error: %s'] = 'Tietokannan rakennepäivitys %s epäonnistui virheviestillä %s';
$a->strings['Executing %s failed with error: %s'] = 'Toiminnon %s suorittaminen epäonnistui. Virhe: %s';
$a->strings['Update %s was successfully applied.'] = '%s-päivitys onnistui.';
$a->strings['Update %s did not return a status. Unknown if it succeeded.'] = 'Päivitys %s ei palauttanut tilaa. Ei ole tiedossa onnistuiko se.';
$a->strings['No failed updates.'] = 'Ei epäonnistuineita päivityksiä.';
$a->strings['Check database structure'] = 'Tarkista tietokannan rakenne';
$a->strings['Failed Updates'] = 'Epäonnistuineita päivityksiä';
$a->strings['This does not include updates prior to 1139, which did not return a status.'] = 'Tämä ei sisällä 1139 edeltäviä päivityksiä, jotka eivät palauttaneet tilaa.';
$a->strings['Mark success (if update was manually applied)'] = 'Merkitse onnistuneeksi (jos päivitys tehtiin manuaalisesti)';
$a->strings['Attempt to execute this update step automatically'] = 'Yritä suorittaa tämä päivitysvaihe automaattisesti';
$a->strings['No'] = 'Ei';
$a->strings['Yes'] = 'Kyllä';
$a->strings['Locked'] = 'Lukittu';
$a->strings['Manage Additional Features'] = 'Hallitse lisäominaisuudet';
$a->strings['Other'] = 'Toinen';
$a->strings['unknown'] = 'tuntematon';
$a->strings['%2$s total system'] = [
	0 => '%2$s järjestelmä yhteensä',
	1 => '%2$s järjestelmää yhteensä',
];
$a->strings['%2$s active user last month'] = [
	0 => '%2$s aktiivinen käyttäjä viime kuussa',
	1 => '%2$s aktiivista käyttäjää viime kuussa',
];
$a->strings['%2$s active user last six months'] = [
	0 => '%2$s aktiivinen käyttäjä puolessa vuodessa',
	1 => '%2$s aktiivista käyttäjää puolessa vuodessa',
];
$a->strings['%2$s registered user'] = [
	0 => '%2$s rekisteröitynyt käyttäjä',
	1 => '%2$s rekisteröitynyttä käyttäjää',
];
$a->strings['%2$s locally created post or comment'] = [
	0 => '%2$s paikallisesti luotu julkaisu tai kommentti',
	1 => '%2$s paikallisesti luotua julkaisua ja kommenttia',
];
$a->strings['%2$s post per user'] = [
	0 => '%2$s julkaisu/käyttäjä',
	1 => '%2$s julkaisua/käyttäjä',
];
$a->strings['%2$s user per system'] = [
	0 => '%2$s käyttäjä/järjestelmä',
	1 => '%2$s käyttäjää/järjestelmä',
];
$a->strings['Federation Statistics'] = 'Liiton tilastotiedot';
$a->strings['PHP log currently enabled.'] = 'PHP-loki käytössä';
$a->strings['PHP log currently disabled.'] = 'PHP-loki pois käytöstä';
$a->strings['Logs'] = 'Lokit';
$a->strings['Clear'] = 'Tyhjennä';
$a->strings['Enable Debugging'] = 'Ota virheenkorjaustila käyttöön';
$a->strings['Log file'] = 'Lokitiedosto';
$a->strings['Log level'] = 'Lokitaso';
$a->strings['PHP logging'] = 'PHP-loki';
$a->strings['View Logs'] = 'Katso lokit';
$a->strings['Search in logs'] = 'Etsi lokeista';
$a->strings['Show all'] = 'Näytä kaikki';
$a->strings['Date'] = 'Päivämäärä';
$a->strings['Level'] = 'Taso';
$a->strings['Context'] = 'Konteksti';
$a->strings['ALL'] = 'KAIKKI';
$a->strings['View details'] = 'Näytä tiedot';
$a->strings['Click to view details'] = 'Klikkaa nähdäksesi tiedot';
$a->strings['Event details'] = 'Tapahtuman tiedot';
$a->strings['Data'] = 'Data';
$a->strings['Source'] = 'Lähde';
$a->strings['File'] = 'Tiedosto';
$a->strings['Line'] = 'Rivi';
$a->strings['Function'] = 'Toiminto';
$a->strings['UID'] = 'UID';
$a->strings['Process ID'] = 'Prosessin ID';
$a->strings['ID'] = 'ID';
$a->strings['Command'] = 'Komento';
$a->strings['Job Parameters'] = 'Työparametrit';
$a->strings['Created'] = 'Luotu';
$a->strings['Next Try'] = 'Seuraava yritys';
$a->strings['Priority'] = 'Prioriteetti';
$a->strings['No special theme for mobile devices'] = 'Ei mobiiliteemaa';
$a->strings['%s - (Experimental)'] = '%s - (Kokeellinen)';
$a->strings['Fetch replies on all posts'] = 'Nouda vastaukset kaikkiin julkaisuihin';
$a->strings['Don\'t fetch replies'] = 'Älä nouda vastauksia';
$a->strings['Fetch replies on posts from followed contacts only'] = 'Nouda julkaisujen vastaukset vain seuraamiltasi kontakteilta';
$a->strings['Fetch replies on posts with interactions only'] = 'Nouda julkaisujen vastaukset vain jos olet ollut vuorovaikutuksessa niiden kanssa';
$a->strings['No community page'] = 'Ei yhteisösivua';
$a->strings['No community page for visitors'] = 'Ei yhteisösivua vierailijoille';
$a->strings['Public postings from users of this site'] = 'Julkiset julkaisut tämän sivuston käyttäjiltä';
$a->strings['Public postings from the federated network'] = 'Julkiset julkaisut liittoutuneelta verkolta';
$a->strings['Public postings from local users and the federated network'] = 'Julkiset julkaisut tältä sivustolta ja liittoutuneelta verkolta';
$a->strings['Multi user instance'] = 'Monen käyttäjän instanssi';
$a->strings['Closed'] = 'Suljettu';
$a->strings['Requires approval'] = 'Edellyttää hyväksyntää';
$a->strings['Open'] = 'Avoin';
$a->strings['Don\'t check'] = 'Älä tarkista';
$a->strings['check the stable version'] = 'tarkista vakaa versio';
$a->strings['check the development version'] = 'tarkista kehitysversio';
$a->strings['none'] = 'ei mitään';
$a->strings['Local contacts'] = 'Paikalliset kontaktit';
$a->strings['Interactors'] = 'Vuorovaikuttajat';
$a->strings['Site'] = 'Sivusto';
$a->strings['General Information'] = 'Yleiset tiedot';
$a->strings['Republish users to directory'] = 'Julkaiset käyttäjät uudestaan hakemistoon';
$a->strings['Registration'] = 'Rekisteröityminen';
$a->strings['File upload'] = 'Tiedoston lataus';
$a->strings['Policies'] = 'Käytännöt';
$a->strings['Advanced'] = 'Edistynyt';
$a->strings['Auto Discovered Contact Directory'] = 'Automaattinen löydettyjen kontaktien hakemisto';
$a->strings['Performance'] = 'Suoritus';
$a->strings['Worker'] = 'Worker';
$a->strings['Message Relay'] = 'Viestinvälitys';
$a->strings['Use the command "console relay" in the command line to add or remove relays.'] = 'Voit lisätä tai poistaa releitä komentorivillä komennolla ”console relay”.';
$a->strings['Relocate Node'] = 'Uudelleen sijoita solmu';
$a->strings['Site name'] = 'Sivuston nimi';
$a->strings['Sender Email'] = 'Lähettäjän sähköposti';
$a->strings['The email address your server shall use to send notification emails from.'] = 'Lähettäjän sähköpostiosoite palvelimen ilmoitussähköposteissa.';
$a->strings['Banner/Logo'] = 'Banneri/logo';
$a->strings['Email Banner/Logo'] = 'Sähköpostin banneri/logo';
$a->strings['Shortcut icon'] = 'Pikakuvake';
$a->strings['Link to an icon that will be used for browsers.'] = 'Linkki kuvakkeeseen, jota selaimet käyttävät.';
$a->strings['Touch icon'] = 'Kosketusnäyttökuvake';
$a->strings['Link to an icon that will be used for tablets and mobiles.'] = 'Linkki kuvakkeeseen, jota tabletit ja matkapuhelimet käyttävät.';
$a->strings['Additional Info'] = 'Lisätietoja';
$a->strings['For public servers: you can add additional information here that will be listed at %s/servers.'] = 'Julkisille palvelimille: Voit lisätä tähän lisätietoja, jotka luetellaan osoitteessa %s/servers.';
$a->strings['System language'] = 'Järjestelmän kieli';
$a->strings['System theme'] = 'Järjestelmäteema';
$a->strings['Default system theme - may be over-ridden by user profiles - <a href="%s" id="cnftheme">Change default theme settings</a>'] = 'Järjestelmän oletusteema: Voidaan ohittaa käyttäjäprofiileissa. <a href="%s" id="cnftheme">Muuta oletusteeman asetuksia</a>';
$a->strings['Mobile system theme'] = 'Mobiili järjestelmäteema';
$a->strings['Theme for mobile devices'] = 'Mobiiliteema';
$a->strings['Force SSL'] = 'Pakoita SSL-yhteyden käyttö';
$a->strings['Single user instance'] = 'Yksittäisen käyttäjän instanssi';
$a->strings['Make this instance multi-user or single-user for the named user'] = 'Tee tästä instanssista monen käyttäjän instanssi tai yhden nimetyn käyttäjän instanssi';
$a->strings['Maximum image size'] = 'Kuvan enimmäiskoko';
$a->strings['Maximum image length'] = 'Suurin kuvapituus';
$a->strings['Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.'] = 'Ladattavan kuvatiedoston enimmäispituus pikseleinä. Oletusarvo on -1, eli ei enimmäispituutta.';
$a->strings['JPEG image quality'] = 'JPEG-kuvanlaatu';
$a->strings['Uploaded JPEGS will be saved at this quality setting [0-100]. Default is 100, which is full quality.'] = 'Ladatut JPEG-kuvat tallennetaan tällä laatuasetuksella [0-100]. Oletus on 100, eli korkein laatu.';
$a->strings['Maximum media file size'] = 'Mediatiedoston enimmäiskoko';
$a->strings['Register policy'] = 'Rekisteröintymiskäytäntö';
$a->strings['Maximum Users'] = 'Käyttäjien enimmäismäärä';
$a->strings['If defined, the register policy is automatically closed when the given number of users is reached and reopens the registry when the number drops below the limit. It only works when the policy is set to open or close, but not when the policy is set to approval.'] = 'Jos määritetään, rekisteröityminen suljetaan automaattisesti, kun annettu käyttäjämäärä saavutetaan, ja rekisteröityminen avataan uudelleen, kun määrä laskee alle rajan. Tämä toimii vain, kun rekisteröitymiskäytäntö on asetettu avoimeksi tai suljetuksi, mutta ei, kun käytäntö on asetettu hyväksymiseksi.';
$a->strings['Maximum Daily Registrations'] = 'Päivittäinen rekisteröitymisraja';
$a->strings['If registration is permitted above, this sets the maximum number of new user registrations to accept per day.  If register is set to closed, this setting has no effect.'] = 'Mikäli rekisteröityminen on sallittu, tämä asettaa enimmäismäärä uusia rekisteröitymisiä päivässä. Jos reksiteröityminen ei ole sallittu, tällä asetuksella ei ole vaikutusta.';
$a->strings['Register text'] = 'Rekisteröitymisteksti';
$a->strings['Will be displayed prominently on the registration page. You can use BBCode here.'] = 'Näkyvästi esillä rekisteröitymissivulla. Voit käyttää BBCodea.';
$a->strings['Forbidden Nicknames'] = 'Kielletyt nimimerkit';
$a->strings['Comma separated list of nicknames that are forbidden from registration. Preset is a list of role names according RFC 2142.'] = 'Pilkulla eroteltu luettelo nimimerkeistä, joiden rekisteröiminen on kiellettyä. Esiasetuksena on luettelo RFC 2142:n mukaisista roolinimistä.';
$a->strings['Accounts abandoned after x days'] = 'Käyttäjätilit hylätään X päivän jälkeen';
$a->strings['Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.'] = 'Ei tuhlaa järjestelmän resursseja ulkoisten sivustojen kyselyihin hylätyistä tileistä. Syötä 0, jos aikarajaa ei ole.';
$a->strings['Allowed friend domains'] = 'Sallitut kaveri-verkkotunnukset';
$a->strings['Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains'] = 'Pilkulla eroteltu lista verkkotunnuksista, jotka saavat luoda ystävyyssuhteita tämän sivuston kanssa. Jokerimerkit hyväksytään. Jätä tyhjäksi salliaksesi kaikki verkkotunnukset';
$a->strings['Allowed email domains'] = 'Sallitut sähköpostin verkkotunnukset';
$a->strings['Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains'] = 'Pilkulla eroteltu lista verkkotunnuksista, joilla olevat sähköpostiosoitteiden käyttö rekisteröitymisessä on sallittu tällä sivustolla. Jokerimerkit hyväksytään. Jätä tyhjäksi salliaksesi kaikki verkkotunnukset.';
$a->strings['Disallowed email domains'] = 'Kielletyt sähköpostin verkkotunnukset';
$a->strings['Comma separated list of domains which are rejected as email addresses for registrations to this site. Wildcards are accepted.'] = 'Pilkulla eroteltu lista verkkotunnuksista, joilla olevien sähköpostiosoitteiden käyttö rekisteröitymisessä ei ole sallittu tällä sivustolla. Jokerimerkit hyväksytään.';
$a->strings['Block public'] = 'Estä vierailijat';
$a->strings['Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.'] = 'Valitse estääksesi julkisen pääsyn kaikkiin muutoin julkisiin henkilökohtaisiin sivuihin silloin kun et ole kirjautunut sisään.';
$a->strings['Force publish'] = 'Pakota julkaisu';
$a->strings['Check to force all profiles on this site to be listed in the site directory.'] = 'Valitse tämä pakottaaksesi kaikki tämän sivuston profiilit listatuiksi sivuston hakemistoon.';
$a->strings['Enabling this may violate privacy laws like the GDPR'] = 'Tämän asetuksen päälle laittaminen saattaa rikkoa yksityisyyteen liittyviä lakeja, kuten GDPR:ää.';
$a->strings['Global directory URL'] = 'Maailmanlaajuisen hakemiston URL-osoite';
$a->strings['Private posts by default for new users'] = 'Yksityiset julkaisut oletuksena uusille käyttäjille';
$a->strings['Don\'t include post content in email notifications'] = 'Älä lisää julkaisun sisältöä sähköposti-ilmoitukseen';
$a->strings['Don\'t include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.'] = 'Älä sisällytä tämän sivuston ulkopuolisesta lähteestä lähetettyjen julkaisujen/kommenttien/yksityisviestien ym. sisältöä sähköpostimuistutuksiin yksityisyyden turvaamikseksi.';
$a->strings['Don\'t embed private images in posts'] = 'Älä upota yksityisiä kuvia julkaisuihin';
$a->strings['Explicit Content'] = 'Epäsiveellinen sisältö';
$a->strings['Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.'] = 'Laita tämä asetus päälle ilmoittaaksesi, että sinun solmuasi käytetään pääasiassa epäsiveelliselle sisällölle, joka saattaa olla alaikäisille sopimatonta. Tämä tieto julkaistaan solmun tiedoissa ja sitä voidaan käyttää esimerkiksi maailmanlaajuisessa hakemistossa suodattamaan solmusi pois liityttävien solmujen luettelosta. Asiasta lisätään myös huomautus käyttäjien rekisteröitymissivulle.';
$a->strings['Only local search'] = 'Vain paikallinen haku';
$a->strings['Blocks search for users who are not logged in to prevent crawlers from blocking your system.'] = 'Estä sisään kirjautumattomien käyttäjien tekemät haut, niin että crawlerit eivät voi tukkia järjestelmääsi.';
$a->strings['Allow Users to set up relay channels'] = 'Salli käyttäjien perustaa välittäjäkanavia';
$a->strings['If enabled, it is possible to create relay users that are used to reshare content based on user defined channels.'] = 'Jos sallittu, on mahdollista luoda välittäjäkäyttäjiä, joita käytetään sisällön uudelleenjakamiseen käyttäjän valitsemiin kanaviin perustuen.';
$a->strings['Enable OpenID'] = 'Ota OpenID käyttöön';
$a->strings['Enable OpenID support for registration and logins.'] = 'Ota käyttöön OpenID-tuki rekisteröitymisessä ja sisäänkirjautumisessa.';
$a->strings['Community pages for visitors'] = 'Yhteisösivu vieraille';
$a->strings['Display local media to visitors'] = 'Näytä media vierailijoille.';
$a->strings['Diaspora support can\'t be enabled because Friendica was installed into a sub directory.'] = 'Diaspora-tukea ei voitu ottaa käyttöön, koska Friendica on asennettu alihakemistoon.';
$a->strings['Enable Diaspora support'] = 'Salli Diaspora-tuki';
$a->strings['Verify SSL'] = 'Vahvista SSL';
$a->strings['If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.'] = 'Voi laittaa tiukan sertifikaatin tarkistuksen pois päältä halutessasi. Tämä tarkoittaa, että et voi yhdistää (lainkaan) itse allekirjoitettuihin SSL-sivustoihin.';
$a->strings['Proxy user'] = 'Välityspalvelimen käyttäjä';
$a->strings['User name for the proxy server.'] = 'Välittäjäpalvelimen käyttäjänimi';
$a->strings['Proxy URL'] = 'Välityspalvelimen osoite';
$a->strings['Network timeout'] = 'Verkon aikakatkaisu';
$a->strings['Value is in seconds. Set to 0 for unlimited (not recommended).'] = 'Arvo sekunneissa. Aseta rajoittamattomaksi arvoksi 0 (ei suositeltu).';
$a->strings['Maximum Load Average'] = 'Kuorman enimmäiskeksiarvo';
$a->strings['Minimal Memory'] = 'Vähimmäismuisti';
$a->strings['Discover followers/followings from contacts'] = 'Löydä seuraajia/seurauksia kontakteista';
$a->strings['Only update contacts/servers with local data'] = 'Päivitä kontaktit/palvelimet vain paikallisella datalla';
$a->strings['Discover contacts from other servers'] = 'Etsi kontakteja muilta palvelimilta';
$a->strings['Search the local directory'] = 'Etsi paikallisesta hakemistosta';
$a->strings['Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.'] = 'Etsi paikallisesta hakemistosta maailmanlaajuisen hakemiston sijaan. Kun etsit paikallisesti, jokainen haku toteutetaan maailmanlaajuinen haku taustalla. Tämä parantaa hakutuloksia, kun haku toistetaan.';
$a->strings['Publish server information'] = 'Julkaise palvelintiedot';
$a->strings['Suppress Tags'] = 'Piilota tunnisteet';
$a->strings['Suppress showing a list of hashtags at the end of the posting.'] = 'Piilota tunnistelista julkaisun lopussa.';
$a->strings['Clean database'] = 'Siivoa tietokanta';
$a->strings['Lifespan of remote items'] = 'Etäkohteiden elinkaari';
$a->strings['Lifespan of raw conversation data'] = 'Keskustelun raakadatan elinkaari';
$a->strings['Maximum numbers of comments per post'] = 'Komenttien julkaisukohtainen enimmäismäärä';
$a->strings['How much comments should be shown for each post? Default value is 100.'] = 'Kuinka monta kommenttia näytetään kussakin julkaisussa? Oletusarvo on 100.';
$a->strings['Items per page'] = 'Kohteiden määrä sivulla';
$a->strings['Number of items per page in stream pages (network, community, profile/contact statuses, search).'] = 'Kohteiden sivukohtainen lukumäärä virtasivuilla (verkosto, yhteisö, profiilien/kontaktien tilapäivitykset, haku).';
$a->strings['Items per page for mobile devices'] = 'Kohteiden määrä sivulla mobiililaitteissa';
$a->strings['Number of items per page in stream pages (network, community, profile/contact statuses, search) for mobile devices.'] = 'Kohteiden sivukohtainen lukumäärä virtasivuilla (verkosto, yhteisö, profiilien/kontaktien tilapäivitykset, haku) mobiililaitteissa.';
$a->strings['Temp path'] = 'Väliaikaistiedostojen polku';
$a->strings['If you have a restricted system where the webserver can\'t access the system temp path, enter another path here.'] = 'Mikäli verkkopalvelimesi ei voi käyttää järjestelmän väliaikaistiedostojen polkua, syötä toinen polku tähän.';
$a->strings['Only search in tags'] = 'Esti vain tunnisteista';
$a->strings['On large systems the text search can slow down the system extremely.'] = 'Isoissa järjestelmissä tekstihaku saattaa hidastaa järjestelmää suuresti.';
$a->strings['Limited search scope'] = 'Rajoitettu hakuala';
$a->strings['If enabled, searches will only be performed in the data used for the channels and not in all posts.'] = 'Jos otat asetuksen käyttöön, haut suoritetaan vain kanavilla käytetystä datasta eikä kaikista julkaisuista.';
$a->strings['Maximum age of items in the search table'] = 'Hakutaulukon kohteiden enimimmäisikä';
$a->strings['Maximum number of parallel workers'] = 'Enimmäismäärä rinnakkaisia workereitä';
$a->strings['Enable fastlane'] = 'Käytä fastlane';
$a->strings['Fetch replies mode'] = 'Vastausten noutotila';
$a->strings['Missing replies can be fetched upon receipt of an ActivityPub post.'] = 'Puuttuvat vastaukset voidaan noutaa ActivityPub-viestin vastaanottamisen yhteydessä.';
$a->strings['Direct relay transfer'] = 'Suora releen siirto';
$a->strings['Relay scope'] = 'Relen soveltamisala';
$a->strings['Disabled'] = 'Pois käytöstä';
$a->strings['all'] = 'kaikki';
$a->strings['tags'] = 'tunnisteet';
$a->strings['Server tags'] = 'palvelintunnisteet';
$a->strings['Allow user tags'] = 'Salli käyttäjien tunnisteet';
$a->strings['Language Quality'] = 'Kielenlaatu';
$a->strings['Start Relocation'] = 'Aloita siirto';
$a->strings['Storage Configuration'] = 'Varaston kokoonpano';
$a->strings['Storage'] = 'Varasto';
$a->strings['Database (legacy)'] = 'Tietokanta (legacy)';
$a->strings['The database update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear.'] = 'Tietokannan päivitys epäonnistui. Suorita komento "php bin/console.php dbstructure update" komentoriviltä ja lue mahdolliset virheviestit.';
$a->strings['The worker was never executed. Please check your database structure!'] = 'Workeriä ei ole otettu käyttöön. Tarkista tietokantasi rakenne!';
$a->strings['The last worker execution was on %s UTC. This is older than one hour. Please check your crontab settings.'] = 'Viimeisin worker -käynnistys tapahtui klo %s UTC, eli yli tunti sitten. Tarkista crontab -asetukset.';
$a->strings['Message queues'] = 'Viestijonot';
$a->strings['Server Settings'] = 'Palvelimen asetukset';
$a->strings['Version'] = 'Versio';
$a->strings['Active addons'] = 'Käytössäolevat lisäosat';
$a->strings['Screenshot'] = 'Kuvakaappaus';
$a->strings['Themes'] = 'Teemat';
$a->strings['Unknown theme.'] = 'Tuntematon teema.';
$a->strings['Themes reloaded'] = 'Teemat ladattu uudelleen';
$a->strings['Reload active themes'] = 'Lataa aktiiviset teemat uudelleen';
$a->strings['No themes found on the system. They should be placed in %1$s'] = 'Teemoja ei löytynyt järjestelmästä. Teemat tulisi laittaa kansioon %1$s';
$a->strings['[Experimental]'] = '[Kokeellinen]';
$a->strings['[Unsupported]'] = '[Ei tueta]';
$a->strings['Display Terms of Service'] = 'Näytä käyttöehdot';
$a->strings['Display Privacy Statement'] = 'Näytä tietosuojalausunto';
$a->strings['The Terms of Service'] = 'Käyttöehdot';
$a->strings['The rules'] = 'Säännöt';
$a->strings['Enter your system rules here. Each line represents one rule.'] = 'Syötä järjestelmän säännöt tähän. Laita kukin sääntö omalle rivilleen.';
$a->strings['Missing parameters'] = 'Puuttuvat parametrit';
$a->strings['Only starting posts can be bookmarked'] = 'Vain aloitusjulkaisut voidaan lisätä kirjanmerkkeihin';
$a->strings['Only starting posts can be muted'] = 'Vain aloitusjulkaisut voidaan mykistää';
$a->strings['Posts from %s can\'t be shared'] = 'Käyttäjän %s julkaisuja ei voi jakaa';
$a->strings['Only starting posts can be unbookmarked'] = 'Vain aloitusjulkaisut voidaan poistaa kirjanmerkeistä';
$a->strings['Only starting posts can be unmuted'] = 'Mykistyksen voi poistaa vain aloitusjulkaisuista';
$a->strings['Posts from %s can\'t be unshared'] = 'Käyttäjän %s julkaisujen jakamista ei voi perua';
$a->strings['Contact not found'] = 'Kontaktia ei löydy';
$a->strings['No installed applications.'] = 'Ei asennettuja sovelluksia.';
$a->strings['Applications'] = 'Sovellukset';
$a->strings['Item was not found.'] = 'Kohdetta ei löytynyt.';
$a->strings['Please login to continue.'] = 'Ole hyvä ja kirjaudu jatkaaksesi.';
$a->strings['You don\'t have access to administration pages.'] = 'Sinulla ei ole pääsyä ylläpitosivuille.';
$a->strings['Overview'] = 'Yleiskatsaus';
$a->strings['Configuration'] = 'Kokoonpano';
$a->strings['Additional features'] = 'Lisäominaisuuksia';
$a->strings['Database'] = 'Tietokanta';
$a->strings['DB updates'] = 'Tietokannan päivitykset';
$a->strings['Diagnostics'] = 'Diagnostiikka';
$a->strings['PHP Info'] = 'PHP tietoja';
$a->strings['check webfinger'] = 'Tarkista webfinger';
$a->strings['Babel'] = 'Babel';
$a->strings['ActivityPub Conversion'] = 'ActivityPub-konversio';
$a->strings['Addon Features'] = 'Lisäosaominaisuudet';
$a->strings['User registrations waiting for confirmation'] = 'Käyttäjärekisteröinnit odottavat hyväksyntää';
$a->strings['Daily posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Päivittäinen julkaisuraja (%d) on tullut täyteen. Julkaisu hylätty.',
	1 => 'Päivittäinen julkaisuraja (%d) on tullut täyteen. Julkaisu hylätty.',
];
$a->strings['Weekly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Viikottainen julkaisuraja (%d) on tullut täyteen. Julkaisu hylätty.',
	1 => 'Viikottainen julkaisuraja (%d) on tullut täyteen. Julkaisu hylätty.',
];
$a->strings['Monthly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Kuukausittainen %d julkaisun yläraja on saavutettu. Tämä julkaisu on hylätty.',
	1 => 'Kuukausittainen %d julkaisun yläraja on saavutettu. Tämä julkaisu on hylätty.',
];
$a->strings['Reports'] = 'Raportit';
$a->strings['Users'] = 'Käyttäjät';
$a->strings['Tools'] = 'Työkalut';
$a->strings['Contact Blocklist'] = 'Kontaktien estolista';
$a->strings['Server Blocklist'] = 'Palvelimien estolista';
$a->strings['Delete Item'] = 'Poista kohde';
$a->strings['Item Source'] = 'Kohteen lähde';
$a->strings['Profile Details'] = 'Profiilitiedot';
$a->strings['Conversations started'] = 'Aloitetut keskustelut';
$a->strings['Only You Can See This'] = 'Vain sinä näet tämän';
$a->strings['Scheduled Posts'] = 'Ajastetut julkaisut';
$a->strings['Posts that are scheduled for publishing'] = 'Julkaisut, joiden julkaisu on ajastettu';
$a->strings['Tips for New Members'] = 'Vinkkejä uusille käyttäjille';
$a->strings['More'] = 'Lisää';
$a->strings['People Search - %s'] = 'Käyttäjähaku - %s';
$a->strings['Group Search - %s'] = 'Ryhmähaku - %s';
$a->strings['No results.'] = 'Ei tuloksia.';
$a->strings['%d result was filtered out because your node blocks the domain it is registered on. You can review the list of domains your node is currently blocking in the <a href="/friendica">About page</a>.'] = [
	0 => '%d tulos suodatettiin pois, koska sinun solmusi on estänyt domainin, jolle käyttäjä on rekisteröitynyt. Voit katsoa luettelon solmusi tällä hetkellä estämistä domaineista <a href="/friendica">Tietoja-sivulla</a>.',
	1 => '%d tulosta suodatettiin pois, koska sinun solmusi on estänyt domainin, jolle käyttäjä on rekisteröitynyt. Voit katsoa luettelon solmusi tällä hetkellä estämistä domaineista <a href="/friendica">Tietoja-sivullla</a>.',
];
$a->strings['Account'] = 'Tili';
$a->strings['Two-factor authentication'] = 'Kaksivaiheinen todennus';
$a->strings['Display'] = 'Ulkonäkö';
$a->strings['Social Networks'] = 'Sosiaalinen media';
$a->strings['Manage Accounts'] = 'Hallinnoi tilejä';
$a->strings['Connected apps'] = 'Yhdistetyt sovellukset';
$a->strings['Remote servers'] = 'Etäpalvelimet';
$a->strings['Import Contacts'] = 'Tuo kontaktit';
$a->strings['Export personal data'] = 'Vie henkilökohtaiset tiedot';
$a->strings['Remove account'] = 'Poista tili';
$a->strings['The post was created'] = 'Julkaisu luotu';
$a->strings['Invalid Request'] = 'Virheellinen pyyntö';
$a->strings['Event id is missing.'] = 'Tapahtuman tunniste puuttuu.';
$a->strings['Failed to remove event'] = 'Tapahtuman poisto epäonnistui';
$a->strings['Event can not end before it has started.'] = 'Tapahtuma ei voi päättyä ennen kuin on alkanut.';
$a->strings['Event title and start time are required.'] = 'Tapahtuman nimi ja alkamisaika vaaditaan.';
$a->strings['Starting date and Title are required.'] = 'Aloituspvm ja otsikko vaaditaan.';
$a->strings['Event Starts:'] = 'Tapahtuma alkaa:';
$a->strings['Required'] = 'Vaaditaan';
$a->strings['Finish date/time is not known or not relevant'] = 'Päättymispvm ja kellonaika ei ole tiedossa tai niillä ei ole merkitystä';
$a->strings['Event Finishes:'] = 'Tapahtuma päättyy:';
$a->strings['Share this event'] = 'Jaa tämä tapahtuma';
$a->strings['Submit'] = 'Lähetä';
$a->strings['Basic'] = 'Perus';
$a->strings['This calendar format is not supported'] = 'Tätä kalenteriformaattia ei tueta';
$a->strings['No exportable data found'] = 'Vientikelpoista dataa ei löytynyt';
$a->strings['calendar'] = 'kalenteri';
$a->strings['Events'] = 'Tapahtumat';
$a->strings['View'] = 'Katso';
$a->strings['New Event'] = 'Uusi tapahtuma';
$a->strings['list'] = 'luettelo';
$a->strings['Unknown circle.'] = 'Tuntematon piiri.';
$a->strings['Contact not found.'] = 'Kontaktia ei löydy.';
$a->strings['Invalid contact.'] = 'Virheellinen kontakti.';
$a->strings['Contact is deleted.'] = 'Kontakti on poistettu';
$a->strings['Bad request.'] = 'Virheellinen pyyntö.';
$a->strings['Save Circle'] = 'Tallenna piiri';
$a->strings['Filter'] = 'Suodatin';
$a->strings['Create a circle of contacts/friends.'] = 'Luo kontakti-/kaveripiiri';
$a->strings['Delete Circle'] = 'Poista piiri';
$a->strings['Members'] = 'Jäsenet';
$a->strings['Remove contact from circle'] = 'Poista kontakti piiristä';
$a->strings['Click on a contact to add or remove.'] = 'Valitse kontakti, jota haluat poistaa tai lisätä.';
$a->strings['Add contact to circle'] = 'Lisää kontakti piiriin';
$a->strings['%d contact edited.'] = [
	0 => '%d kontakti muokattu',
	1 => '%d kontaktia muokattu.',
];
$a->strings['Show all contacts'] = 'Näytä kaikki kontaktit';
$a->strings['Pending'] = 'Vireillä';
$a->strings['Only show pending contacts'] = 'Näytä vain vireillä olevat kontaktit';
$a->strings['Blocked'] = 'Estetty';
$a->strings['Only show blocked contacts'] = 'Näytä vain estetyt kontaktit';
$a->strings['Ignored'] = 'Jätetty huomiotta';
$a->strings['Only show ignored contacts'] = 'Näytä vain kontaktit, jotka jätetty huomiotta';
$a->strings['Collapsed'] = 'Piiloon laskostettu';
$a->strings['Only show collapsed contacts'] = 'Näytä vain piiloon taitetut kontaktit';
$a->strings['Archived'] = 'Arkistoitu';
$a->strings['Only show archived contacts'] = 'Näytä vain arkistoidut kontaktit';
$a->strings['Hidden'] = 'Piilotettu';
$a->strings['Only show hidden contacts'] = 'Näytä vain piilotetut kontaktit';
$a->strings['Search your contacts'] = 'Etsi kontakteistasi';
$a->strings['Results for: %s'] = 'Haun "%s" tulokset';
$a->strings['Update'] = 'Päivitä';
$a->strings['Unblock'] = 'Salli';
$a->strings['Unignore'] = 'Ota huomioon';
$a->strings['Uncollapse'] = 'Laskosta esiin';
$a->strings['Batch Actions'] = 'Niputa toiminnot';
$a->strings['Conversations started by this contact'] = 'Tämän kontaktin aloittamat keskustelut';
$a->strings['Posts and Comments'] = 'Julkaisut ja kommentit';
$a->strings['Individual Posts and Replies'] = 'Yksittäiset julkaisut ja vastaukset';
$a->strings['Posts containing media objects'] = 'Julkaisut, jotka sisältävät mediaobjekteja';
$a->strings['View all known contacts'] = 'Näytä kaikki tunnetut kontaktit';
$a->strings['Advanced Contact Settings'] = 'Kontakti-lisäasetukset';
$a->strings['Friend'] = 'Kaveri';
$a->strings['Follows you'] = 'Seuraa sinua';
$a->strings['You follow'] = 'Sinä seuraat';
$a->strings['Pending outgoing contact request'] = 'Avoimet lähetetyt kontaktipyynnöt';
$a->strings['Pending incoming contact request'] = 'Avoimet sisääntulevat kontaktipyynnöt';
$a->strings['Visit %s\'s profile [%s]'] = 'Näytä %s-käyttäjän profiili [%s]';
$a->strings['Contact update failed.'] = 'Kontaktipäivitys epäonnistui.';
$a->strings['Return to contact editor'] = 'Palaa kontaktin muokkaamiseen';
$a->strings['Name'] = 'Nimi';
$a->strings['Account Nickname'] = 'Tilin lempinimi';
$a->strings['Account URL'] = 'Tilin URL-osoite';
$a->strings['Poll/Feed URL'] = 'URL äänestyksiä/syötteitä varten';
$a->strings['New photo from this URL'] = 'Uusi kuva osoitteesta';
$a->strings['No known contacts.'] = 'Ei tunnettuja kontakteja.';
$a->strings['No common contacts.'] = 'Ei yhteisiä kontakteja.';
$a->strings['Follower (%s)'] = [
	0 => 'Seuraaja (%s)',
	1 => 'Seuraajat (%s)',
];
$a->strings['Following (%s)'] = [
	0 => 'Seurattava (%s)',
	1 => 'Seurattavat (%s)',
];
$a->strings['Friend (%s)'] = [
	0 => 'Kaveri (%s)',
	1 => 'Kaverit (%s)',
];
$a->strings['Common contact (%s)'] = [
	0 => 'Yhteinen kontakti (%s)',
	1 => 'Yhteiset kontaktit (%s)',
];
$a->strings['Contact (%s)'] = [
	0 => 'Kontakti (%s)',
	1 => 'Kontaktit (%s)',
];
$a->strings['Access denied.'] = 'Käyttö estetty.';
$a->strings['Submit Request'] = 'Lähetä pyyntö';
$a->strings['You already added this contact.'] = 'Olet jo lisännyt tämän kontaktin.';
$a->strings['The network type couldn\'t be detected. Contact can\'t be added.'] = 'Verkostotyyppiä ei voitu havaita. Kontaktia ei voitu lisätä.';
$a->strings['Diaspora support isn\'t enabled. Contact can\'t be added.'] = 'Diaspora -tuki ei ole käytössä. Kontaktia ei voi lisätä.';
$a->strings['Please answer the following:'] = 'Vastaa seuraavaan:';
$a->strings['Your Identity Address:'] = 'Identiteettisi osoite:';
$a->strings['Profile URL'] = 'Profiilin URL';
$a->strings['Tags:'] = 'Tunnisteet:';
$a->strings['Add a personal note:'] = 'Lisää yksityismuistiinpano:';
$a->strings['The contact could not be added.'] = 'Kontaktia ei voitu lisätä.';
$a->strings['Invalid request.'] = 'Virheellinen pyyntö.';
$a->strings['Profile Match'] = 'Vastaavien profiilien haku';
$a->strings['No keywords to match. Please add keywords to your profile.'] = 'Ei vastaavia avainsanoja. Lisää avainsanoja profiiliisi.';
$a->strings['No matches'] = 'Ei täsmääviä profiileja';
$a->strings['Failed to update contact record.'] = 'Kontaktitietojen päivitys epäonnistui.';
$a->strings['Contact has been unblocked'] = 'Henkilö on jälleen sallittu';
$a->strings['Contact has been blocked'] = 'Henkilö on estetty';
$a->strings['Contact has been unignored'] = 'Henkilö on jälleen huomioituna.';
$a->strings['Contact has been ignored'] = 'Henkilöä ei enää huomioida';
$a->strings['Contact has been uncollapsed'] = 'Kontakti ei ole enää laskostettu piiloon';
$a->strings['Contact has been collapsed'] = 'Kontakti on laskostettu piiloon';
$a->strings['Connection:'] = 'Yhteys:';
$a->strings['Private communications are not available for this contact.'] = 'Yksityiskeskustelu ei ole käytettävissä tälle henkilölle.';
$a->strings['This contact is on a server you ignored.'] = 'Tämä kontakti on palvelimella, joka olet estänyt.';
$a->strings['Never'] = 'Ei koskaan';
$a->strings['(Update was not successful)'] = '(Päivitys epäonnistui)';
$a->strings['(Update was successful)'] = '(Päivitys onnistui)';
$a->strings['Suggest friends'] = 'Ehdota kavereita';
$a->strings['Network type: %s'] = 'Verkostotyyppi: %s';
$a->strings['Communications lost with this contact!'] = 'Yhteys tähän henkilöön menetettiin!';
$a->strings['Fetch further information for feeds'] = 'Nouda lisää tietoja syötteisiin';
$a->strings['Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.'] = 'Nouda tietoja – kuten kuvien esikatselu, otsikko tai teaser – syötteen kohteesta. Voit aktivoida tämän, jos syöte ei sisällä paljon tekstiä. Avainsanat otetaan syötteen kohteen metatunnisteesta ja ne julkaistaan aihetunnisteina.';
$a->strings['Fetch information'] = 'Nouda tiedot';
$a->strings['Fetch keywords'] = 'Nouda avainsanat';
$a->strings['Fetch information and keywords'] = 'Nouda tiedot ja avainsanat';
$a->strings['No mirroring'] = 'Ei peilausta';
$a->strings['Mirror as my own posting'] = 'Peilaa omana julkaisuna';
$a->strings['Native reshare'] = 'Natiivi uudelleenjako';
$a->strings['Contact Information / Notes'] = 'Yhteystiedot / Muistiinpanot';
$a->strings['Contact Settings'] = 'Yhteystietoasetukset';
$a->strings['Contact'] = 'Kontakti';
$a->strings['Their personal note'] = 'Hänen yksityismuistiinpanonsa';
$a->strings['Edit contact notes'] = 'Muokkaa yhteystietojen muistiinpanoja';
$a->strings['Block/Unblock contact'] = 'Estä/salli henkilö';
$a->strings['Ignore contact'] = 'Jätä henkilö huomiotta';
$a->strings['View conversations'] = 'Näytä keskustelut';
$a->strings['Last update:'] = 'Viimeksi päivitetty:';
$a->strings['Update public posts'] = 'Päivitä julkiset julkaisut';
$a->strings['Update now'] = 'Päivitä nyt';
$a->strings['Awaiting connection acknowledge'] = 'Odotetaan yhteyden kuittausta';
$a->strings['Currently blocked'] = 'Estetty tällä hetkellä';
$a->strings['Currently ignored'] = 'Jätetty huomiotta tällä hetkellä';
$a->strings['Currently collapsed'] = 'Laskostettu piiloon tällä hetkellä';
$a->strings['Currently archived'] = 'Arkistoitu tällä hetkellä';
$a->strings['Hide this contact from others'] = 'Piilota kontakti muilta';
$a->strings['Replies/likes to your public posts <strong>may</strong> still be visible'] = 'Vastaukset/tykkäykset sinun julkisiin julkaisuihisi <strong>saattavat</strong> olla edelleen näkyvillä';
$a->strings['Notification for new posts'] = 'Ilmoitukset uusista julkaisuista';
$a->strings['Send a notification of every new post of this contact'] = 'Lähetä ilmoitus tämän kontaktin kaikista uusista julkaisuista';
$a->strings['Keyword Deny List'] = 'Avainsanojen kieltolista';
$a->strings['Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected'] = 'Pilkulla eroteltu luettelo avainsanoista, joita ei muunneta aihetunnisteiksi, kun "Nouda tiedot ja avainsanat" on valittuna';
$a->strings['Actions'] = 'Toimenpiteet';
$a->strings['Status'] = 'Tila';
$a->strings['Mirror postings from this contact'] = 'Peilaa tämän kontaktin julkaisut';
$a->strings['Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'] = 'Merkitse tämä kontakti etäitseksi. Tämä saa Friendican uudelleenjulkaisemaan tämän kontaktin uudet merkinnät.';
$a->strings['Channel Settings'] = 'Kanava-asetukset';
$a->strings['Default frequency'] = 'Oletustoistuvuus';
$a->strings['Display all posts of this contact'] = 'Näytä tämän kontaktin kaikki julkaisut';
$a->strings['Display only few posts'] = 'Näytä vain muutamia julkaisuja';
$a->strings['Never display posts'] = 'Älä näytä julkaisuja ikinä';
$a->strings['Channel Only'] = 'Vain kanava';
$a->strings['Refetch contact data'] = 'Nouda kontantin tiedot uudelleen';
$a->strings['Fetch latest posts'] = 'Nouda viimeisimmät julkaisut';
$a->strings['Toggle Blocked status'] = 'Estetty tila päälle/pois';
$a->strings['Toggle Ignored status'] = 'Sivuuta/seuraa';
$a->strings['Revoke Follow'] = 'Mitätöi seuraaminen';
$a->strings['Revoke the follow from this contact'] = 'Poista tämä kontakti seuraajistasi';
$a->strings['Bad Request.'] = 'Virheellinen pyyntö.';
$a->strings['Contact is being deleted.'] = 'Kontaktia poistetaan.';
$a->strings['Follow was successfully revoked.'] = 'Seuraamisen mitätöinti onnistui.';
$a->strings['Do you really want to revoke this contact\'s follow? This cannot be undone and they will have to manually follow you back again.'] = 'Haluatko todella poistaa tämän kontaktin seuraajistasi? Toimintoa ei voi peruuttaa. Kontaktin täytyy seurata sinua uudestaan manuaalisesti.';
$a->strings['No suggestions available. If this is a new site, please try again in 24 hours.'] = 'Ehdotuksia ei löydy. Jos tämä on uusi sivusto, kokeile uudelleen vuorokauden kuluttua.';
$a->strings['You aren\'t following this contact.'] = 'Et seuraa tätä kontaktia.';
$a->strings['Unfollowing is currently not supported by your network.'] = 'Seuraamisen lopettaminen ei tällä hetkellä tueta verkossasi.';
$a->strings['Disconnect/Unfollow'] = 'Katkaise / Lopeta seuraaminen';
$a->strings['Contact was successfully unfollowed'] = 'Kontaktin seuraamisen lopettaminen onnistui';
$a->strings['Unable to unfollow this contact, please contact your administrator'] = 'Tämän kontaktin seuraamisen lopettaminen ei onnitu. Ota yhteyttä ylläpitoon.';
$a->strings['Channel not available.'] = 'Kanava ei saatavilla.';
$a->strings['Community option not available.'] = 'Yhteisö vaihtoehto ei saatavilla.';
$a->strings['Not available.'] = 'Ei saatavilla.';
$a->strings['Circle: %s'] = 'Piiri: %s';
$a->strings['Include'] = 'Sisällytä';
$a->strings['Hide'] = 'Piilota';
$a->strings['Credits'] = 'Lopputekstit';
$a->strings['Friendica is a community project, that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!'] = 'Friendica on yhteisöprojekti, joka ei olisi mahdollinen ilman monien ihmisten apua. Tässä on luettelo niistä, jotka ovat antaneet työpanoksensa Friendican koodin tai käännöksen kehittämiseen. Kiitos teille kaikille!';
$a->strings['Formatted'] = 'Muotoiltu';
$a->strings['Activity'] = 'Toiminta';
$a->strings['Result Item'] = 'Tuloskohde';
$a->strings['Error'] = [
	0 => 'Virhe',
	1 => 'Virhettä',
];
$a->strings['You must be logged in to use this module'] = 'Sinun pitää kirjautua sisään, jotta voit käyttää tätä moduulia';
$a->strings['Source URL'] = 'Lähde URL';
$a->strings['Time Conversion'] = 'Aikamuunnos';
$a->strings['Friendica provides this service for sharing events with other networks and friends in unknown timezones.'] = 'Friendica tarjoaa tämän palvelun tapahtumien jakamiseen muiden verkostojen ja tuntemattomilla aikavyöhykkeillä olevien ystävien kanssa.';
$a->strings['UTC time: %s'] = 'UTC-aika: %s';
$a->strings['Current timezone: %s'] = 'Aikavyöhyke: %s';
$a->strings['Converted localtime: %s'] = 'Muunnettu paikallisaika: %s';
$a->strings['Please select your timezone:'] = 'Valitse aikavyöhykkeesi:';
$a->strings['Output'] = 'Tuloste';
$a->strings['Results for:'] = 'Tulokset haulla:';
$a->strings['Site Directory'] = 'Sivuston luettelo';
$a->strings['Folder:'] = 'Kansio:';
$a->strings['- select -'] = '- valitse -';
$a->strings['Suggested contact not found.'] = 'Ehdotettua kontaktia ei löytynyt.';
$a->strings['Friend suggestion sent.'] = 'Kaveriehdotus lähetettiin.';
$a->strings['Suggest Friends'] = 'Ehdota kavereita';
$a->strings['Suggest a friend for %s'] = 'Ehdota kaveria kaverille %s';
$a->strings['Installed addons/apps:'] = 'Asennettu lisäosat/sovellukset:';
$a->strings['No installed addons/apps'] = 'Ei asennettuja lisäosia/sovelluksia';
$a->strings['Read about the <a href="%1$s/tos">Terms of Service</a> of this node.'] = 'Lue tämän solmun <a href="%1$s/tos">käyttöehdot</a>.';
$a->strings['On this server the following remote servers are blocked.'] = 'Tällä palvelimella seuraavat etäpalvelimet ovat estetty.';
$a->strings['Reason for the block'] = 'Eston syy';
$a->strings['This is Friendica, version %s that is running at the web location %s. The database version is %s, the post update version is %s.'] = 'Tämä on Friendica, versio %s, joka toimii verkkosijainnissa %s. Tietokannan versio on %s ja julkaisupäivitysversio on %s.';
$a->strings['Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.'] = 'Vieraile osoitteessa <a href="https://friendi.ca">Friendi.ca</a> saadaksesi lisätietoja Friendica- projektista.';
$a->strings['Bug reports and issues: please visit'] = 'Bugiraportit ja kysymykset: vieraile osoitteessa';
$a->strings['the bugtracker at github'] = 'githubin bugtrackeri';
$a->strings['Suggestions, praise, etc. - please email "info" at "friendi - dot - ca'] = 'Ehdotukset ja palaute: lähetä sähköposti osoitteeseen "info" at "friendi - piste - ca';
$a->strings['No profile'] = 'Ei profiilia';
$a->strings['Help:'] = 'Ohje:';
$a->strings['Welcome to %s'] = 'Tervetuloa %s';
$a->strings['System check'] = 'Järjestelmän tarkistus';
$a->strings['OK'] = 'OK';
$a->strings['Next'] = 'Seuraava';
$a->strings['Check again'] = 'Tarkista uudelleen';
$a->strings['Base settings'] = 'Perusasetukset';
$a->strings['Base path to installation'] = 'Asennuksen peruspolku';
$a->strings['The Friendica system URL'] = 'Friendica-järjestelmän URL';
$a->strings['Database connection'] = 'Tietokantayhteys';
$a->strings['In order to install Friendica we need to know how to connect to your database.'] = 'Jotta voit asentaa Friendican, tarvitaan tieto siitä, miten tietokantaasi saa yhteyden.';
$a->strings['Please contact your hosting provider or site administrator if you have questions about these settings.'] = 'Ota yhteyttä web-palveluntarjoajaasi tai sivuston ylläpitäjään, jos sinulla on näihin asetuksiin liittyviä kysymyksiä.';
$a->strings['The database you specify below should already exist. If it does not, please create it before continuing.'] = 'Alla määritetyn tietokannan tulisi olla jo olemassa. Jos se ei ole, luo se ennen kuin jatkat.';
$a->strings['Database Server Name'] = 'Tietokannan palvelimen nimi';
$a->strings['Database Login Name'] = 'Tietokannan käyttäjän nimi';
$a->strings['Database Login Password'] = 'Tietokannan käyttäjän salasana';
$a->strings['For security reasons the password must not be empty'] = 'Turvallisuussyistä salasanakenttä ei saa olla tyhjä';
$a->strings['Database Name'] = 'Tietokannan nimi';
$a->strings['Please select a default timezone for your website'] = 'Valitse oletusaikavyöhyke sivustollesi';
$a->strings['Site settings'] = 'Sivuston asetukset';
$a->strings['Site administrator email address'] = 'Sivuston ylläpitäjän sähköpostiosoite';
$a->strings['Your account email address must match this in order to use the web admin panel.'] = 'Tilisi sähköpostiosoitteen on vastattava tätä, jotta voit käyttää ylläpitokäyttöliittymää.';
$a->strings['System Language:'] = 'Järjestelmän kieli:';
$a->strings['Set the default language for your Friendica installation interface and to send emails.'] = 'Valitse Friendica-sivustosi oletuskieli.';
$a->strings['Your Friendica site database has been installed.'] = 'Friendica-sivustosi tietokanta on asennettu.';
$a->strings['Installation finished'] = 'Asennus valmis';
$a->strings['<h1>What next</h1>'] = '<h1>Mitä seuraavaksi</h1>';
$a->strings['IMPORTANT: You will need to [manually] setup a scheduled task for the worker.'] = 'TÄRKEÄÄ: Sinun pitää asettaa [manuaalisesti] ajastettu tehtävä Workerille.';
$a->strings['Total invitation limit exceeded.'] = 'Kutsuraja ylitetty.';
$a->strings['%s : Not a valid email address.'] = '%s : Virheellinen sähköpostiosoite.';
$a->strings['Please join us on Friendica'] = 'Liity meihin Friendicassa';
$a->strings['Invitation limit exceeded. Please contact your site administrator.'] = 'Kutsuraja ylitetty. Ota yhteyttä ylläpitäjään.';
$a->strings['%s : Message delivery failed.'] = '%s : Viestin toimitus epäonnistui.';
$a->strings['%d message sent.'] = [
	0 => '%d viesti lähetetty.',
	1 => '%d viestiä lähetetty.',
];
$a->strings['You have no more invitations available'] = 'Sinulla ei ole kutsuja jäljellä';
$a->strings['To accept this invitation, please visit and register at %s or any other public Friendica website.'] = 'Hyväksyäksesi tämän kutsun, rekisteröidy sivustolla %s tai millä tahansa muulla Friendica -sivustolla.';
$a->strings['To accept this invitation, please visit and register at %s.'] = 'Hyväksyäksesi tämän kutsun, rekisteröidy sivustolla  %s.';
$a->strings['Send invitations'] = 'Lähetä kutsut';
$a->strings['Enter email addresses, one per line:'] = 'Syötä sähköpostiosoitteet, yksi riviä kohden:';
$a->strings['Once you have registered, please connect with me via my profile page at:'] = 'Kun olet rekisteröitynyt, lähetä minulle kaverikutsu profiilisivuni kautta:';
$a->strings['Please enter a post body.'] = 'Syötä julkaisun leipäteksti.';
$a->strings['This feature is only available with the frio theme.'] = 'Tämä ominaisuus on saatavilla vain frio-teemassa.';
$a->strings['Compose new personal note'] = 'Kirjoita uusi yksityismuistiinpano';
$a->strings['Compose new post'] = 'Kirjoita uusi julkaisu';
$a->strings['Visibility'] = 'Näkyvyys';
$a->strings['Clear the location'] = 'Tyhjennä sijainti';
$a->strings['Post by %s'] = 'Käyttäjän %s julkaisut';
$a->strings['System down for maintenance'] = 'Järjestelmä poiskytketty huoltoa varten';
$a->strings['Files'] = 'Tiedostot';
$a->strings['Upload'] = 'Lähetä';
$a->strings['Or - did you try to upload an empty file?'] = 'Yrititkö ladata tyhjän tiedoston?';
$a->strings['File exceeds size limit of %s'] = 'Tiedosto ylittää kokorajoituksen %s';
$a->strings['File upload failed.'] = 'Tiedoston lähettäminen epäonnistui.';
$a->strings['Unable to process image.'] = 'Kuvan käsitteleminen epäonnistui.';
$a->strings['Image upload failed.'] = 'Kuvan lähettäminen epäonnistui.';
$a->strings['Active'] = 'Aktiivinen';
$a->strings['Deleted'] = 'Poistettu';
$a->strings['Normal Account Page'] = 'Tavallinen käyttäjätili';
$a->strings['Soapbox Page'] = 'Saarnatuoli sivu';
$a->strings['Public Group'] = 'Julkinen ryhmä';
$a->strings['Automatic Friend Page'] = 'Automaattinen kaverisivu';
$a->strings['Private Group'] = 'Yksityinen ryhmä';
$a->strings['Personal Page'] = 'Henkilökohtainen sivu';
$a->strings['Organisation Page'] = 'Järjestön sivu';
$a->strings['News Page'] = 'Uutissivu';
$a->strings['Community Group'] = 'Yhteisöryhmä';
$a->strings['You can\'t block a local contact, please block the user instead'] = 'Et voi estää paikallista kontaktia. Estä sen sijaan käyttäjä';
$a->strings['%s contact unblocked'] = [
	0 => '%s kontakti poistettu estolistalta',
	1 => '%s kontaktia poistettu estolistalta',
];
$a->strings['Remote Contact Blocklist'] = 'Etäkontaktien estolista';
$a->strings['This page allows you to prevent any message from a remote contact to reach your node.'] = 'Tällä sivulla voit estää minkä tahansa etäkontaktilta tulevan viestin solmuusi';
$a->strings['Block Remote Contact'] = 'Estä etäkontakti';
$a->strings['Import/Export'] = 'Tuonti/vienti';
$a->strings['Import from CSV file'] = 'Tuo CSV-tiedostosta';
$a->strings['Export to CSV file'] = 'Vie CSV-tiedostoon';
$a->strings['select all'] = 'valitse kaikki';
$a->strings['select none'] = 'älä valitse mitään';
$a->strings['No remote contact is blocked from this node.'] = 'Yhtään etäkontaktia ei ole estetty tältä solmulta.';
$a->strings['Blocked Remote Contacts'] = 'Estetyt etäkontaktit';
$a->strings['Block New Remote Contact'] = 'Estä uusi etäkontakti';
$a->strings['Photo'] = 'Kuva';
$a->strings['Reason'] = 'Syy';
$a->strings['%s total blocked contact'] = [
	0 => 'Yhteensä %s estetty kontakti',
	1 => 'Yhteensä %s estettyjä kontaktia',
];
$a->strings['URL of the remote contact to block.'] = 'Estettävän etäkontaktin URL-osoite';
$a->strings['Also purge contact'] = 'Siivoa kontakti myös pois';
$a->strings['Removes all content related to this contact from the node. Keeps the contact record. This action cannot be undone.'] = 'Poistaa solmusta kaiken sisällön, joka liittyy tähän kontaktiin. Säilyttää kontatin tallenteet. Tätä toimintoa ei voi perua.';
$a->strings['Block Reason'] = 'Eston syy';
$a->strings['No valid contacts found in CSV file.'] = 'Käypiä kontakteja ei löytynyt CSV-tiedostosta.';
$a->strings['Error importing contact file: %s'] = 'Virhe kontaktitiedostoa tuotaessa: %s';
$a->strings['Error parsing contact data.'] = 'Virhe kontaktitietojen käsittelyssä.';
$a->strings['%d contact blocking was queued.'] = [
	0 => '%d kontaktiesto on jonossa.',
	1 => '%d kontaktiestoa on jonossa.',
];
$a->strings['%d contact was skipped (empty URL).'] = [
	0 => '%d kontakti sivuutettu (tyhjä URL).',
	1 => '%d kontaktia sivuutettu (tyhjä URL).',
];
$a->strings['Import a Contact Blocklist'] = 'Tuo kontaktien estolista';
$a->strings['<p>Upload a CSV file with contact URLs and reasons for blocking.</p>'] = '<p>Lataa palvelimelle CSV-tiedosto, jossa on kontaktien URL-osoitteet ja estojen syyt.</p>';
$a->strings['Upload file'] = 'Lähetä tiedosto';
$a->strings['Contacts to import'] = 'Tuotavat kontaktit';
$a->strings['Contact URL'] = 'Kontaktin URL';
$a->strings['%d total contact'] = [
	0 => '%d kontakti yhteensä',
	1 => '%d kontaktia yhteensä',
];
$a->strings['Also purge contacts'] = 'Lisäksi puhdista kontaktit';
$a->strings['Removes all content related to these contacts from the node. Keeps the contact records. This action cannot be undone.'] = 'Poistaa kaiken näihin kontakteihin liittyvän sisällön solmulta. Säilyttää kontaktitallenteet. Tätä toimintoa ei voi perua.';
$a->strings['Contact blocklist CSV file'] = 'Kontaktien estolistan CSV-tiedosto';
$a->strings['The file "%s" could not be opened for importing'] = 'Tiedostoa "%s" ei voitu avata tuontia varten';
$a->strings['Check pattern'] = 'Tarkistuskaava';
$a->strings['Server Name'] = 'Palvelimen nimi';
$a->strings['Server Domain'] = 'Palvelimen verkkotunnus';
$a->strings['Known Contacts'] = 'Tunnetut kontaktit';
$a->strings['Also purges all the locally stored content authored by the known contacts registered on that server. Keeps the contacts and the server records. This action cannot be undone.'] = [
	0 => 'Siivoaa pois myös kaiken paikallisesti tallennetun sisällön, joka on tälle palvelimelle rekisteröityneiden tunnettujen kontatien tekemää. Säilyttää kontaktien ja palvelimen tallenteet. Tätä toimintoa ei voi perua.',
	1 => 'Siivoaa pois myös kaiken paikallisesti tallennetun sisällön, joka on näille palvelimille rekisteröityneiden tunnettujen kontatien tekemää. Säilyttää kontaktien ja palvelinten tallenteet. Tätä toimintoa ei voi perua.',
];
$a->strings['Block reason'] = 'Estosyy';
$a->strings['<p>This file can be downloaded from the <code>/friendica</code> path of any Friendica server.</p>'] = '<p>Tämän tiedoston voi ladata <code>/friendica</code> -polusta miltä tahansa Friendica-palvelimelta.</p>';
$a->strings['Domain Pattern'] = 'Verkkotunnuskaava';
$a->strings['Import Mode'] = 'Tuontitila';
$a->strings['Import Patterns'] = 'Tuontikaavat';
$a->strings['Replace'] = 'Korvaa';
$a->strings['Replaces the current blocklist by the imported patterns.'] = 'Korvaa nykyinen estolista tuoduilla mallineilla.';
$a->strings['Check to delete this entry from the blocklist'] = 'Laita rasti poistaaksesi kohde estolistalta';
$a->strings['Add new entry to the blocklist'] = 'Lisää uusi merkintä estolistalle';
$a->strings['Save changes to the blocklist'] = 'Tallenna muutokset estolistaan';
$a->strings['Current Entries in the Blocklist'] = 'Nykyinen estolista';
$a->strings['Delete entry from the blocklist'] = 'Poista merkintä estolistalta';
$a->strings['Delete entry from the blocklist?'] = 'Poistetaanko merkintä estolistalta?';
$a->strings['Item marked for deletion.'] = 'Kohde merkitty poistettavaksi.';
$a->strings['Delete this Item'] = 'Poista tämä kohde';
$a->strings['On this page you can delete an item from your node. If the item is a top level posting, the entire thread will be deleted.'] = 'Tällä sivulla voit poistaa kohteen solmustasi. Jos kohde on ylätason julkaisu, koko ketju poistetaan.';
$a->strings['GUID'] = 'GUID';
$a->strings['The GUID of the item you want to delete.'] = 'Poistettavan kohteen GUID.';
$a->strings['Item Id'] = 'Kohteen ID';
$a->strings['Item URI'] = 'Kohteen URI';
$a->strings['Terms'] = 'Termit';
$a->strings['Tag'] = 'Tunniste';
$a->strings['Type'] = 'Tyyppi';
$a->strings['Term'] = 'Termi';
$a->strings['URL'] = 'URL';
$a->strings['Implicit Mention'] = 'Epäsuora maininta';
$a->strings['Item not found'] = 'Kohdetta ei löytynyt';
$a->strings['No source recorded'] = 'Ei tallennettua lähdettä';
$a->strings['Contact not found or their server is already blocked on this node.'] = 'Kontaktia ei löydy tai palvelin on jo estetty tällä solmulla.';
$a->strings['Pick Contact'] = 'Valitse kontakti';
$a->strings['Please enter below the contact address or profile URL you would like to create a moderation report about.'] = 'Syötä alle sen kontaktin osoite tai profiilin URL-osoite, josta haluat luoda moderointiraportin.';
$a->strings['Contact address/URL'] = 'Kontaktin osoite/URL';
$a->strings['Pick Category'] = 'Valitse luokka';
$a->strings['Spam'] = 'Roskasisältö';
$a->strings['This contact is publishing many repeated/overly long posts/replies or advertising their product/websites in otherwise irrelevant conversations.'] = 'Tämä kontakti julkaisee useita toistuvia/ylipitkiä julkaisuja/vastauksia tai mainostaa tuotteitaan/verkkosivujaan muutoin asiaan liittymättömissä keskusteluissa.';
$a->strings['Illegal Content'] = 'Laiton sisältö';
$a->strings['This contact is publishing content that is considered illegal in this node\'s hosting juridiction.'] = 'Tämä kontakti julkaisee sisältöä, joka on laitonta paikassa, jossa tämän solmun hosting-palvelu sijaitsee.';
$a->strings['Community Safety'] = 'Yhteisön turhvallisuus';
$a->strings['This contact aggravated you or other people, by being provocative or insensitive, intentionally or not. This includes disclosing people\'s private information (doxxing), posting threats or offensive pictures in posts or replies.'] = 'Tämä kontakti ärsyttää sinua tai muita ihmisiä olemalla provosoiva tai välinpitämätön, tahallista tai ei. Tämä sisältää ihmisten henkilötietojen jakamisen (doxxing) sekä uhkausten tai loukkaavien kuvien julkaisun julkaisuissa tai vastauksissa.';
$a->strings['Unwanted Content/Behavior'] = 'Epätoivottu sisältö/käytös';
$a->strings['This contact has repeatedly published content irrelevant to the node\'s theme or is openly criticizing the node\'s administration/moderation without directly engaging with the relevant people for example or repeatedly nitpicking on a sensitive topic.'] = 'Tämä kontakti on toistuvasti julkaissut sisältöä, joka ei liity solmun teemaan tai kritisoi somun ylläpitoa/moderointia avoimesti, mutta ei kuitenkaan ole yhteydessä asianomaisiin ihmisiin, esimerkiksi toistuvasti jankuttamalla arkaluontoisesta aiheesta.';
$a->strings['Rules Violation'] = 'Sääntöjen rikkominen';
$a->strings['This contact violated one or more rules of this node. You will be able to pick which one(s) in the next step.'] = 'Tämä kontakti rikkoi yhtä tai useampaa tämän solmun sääntöä. Voit valita rikotut säännöt seuraavassa vaiheessa.';
$a->strings['Please elaborate below why you submitted this report. The more details you provide, the better your report can be handled.'] = 'Kerro alla tarkemmin, miksi lähetät tämän raportin. Mitä enemmän yksityiskohtia kerrot, sitä paremmin raporttisi voidaan käsitellä.';
$a->strings['Additional Information'] = 'Lisätiedot';
$a->strings['Please provide any additional information relevant to this particular report. You will be able to attach posts by this contact in the next step, but any context is welcome.'] = 'Anna lisätietoja liittyen tähän raporttiin. Voit liittää tämän kontaktin julkaisuja seuraavassa vaiheessa, mutta mikä tahansa lisäkonteksti on tervetullutta.';
$a->strings['Pick Rules'] = 'Valitse säännöt';
$a->strings['Please pick below the node rules you believe this contact violated.'] = 'Valitse alta ne solmun säännöt, joita tämä kontakti on mielestäsi rikkonut.';
$a->strings['Pick Posts'] = 'Valitse julkaisut';
$a->strings['Would you ike to forward this report to the remote server?'] = 'Haluatko välittää tämän raportin etäpalvelimelle?';
$a->strings['Submit Report'] = 'Lähetä raportti';
$a->strings['Further Action'] = 'Lisätoiminnot';
$a->strings['You can also perform one of the following action on the contact you reported:'] = 'Voit myös suorittaa seuraavat toimenpiteet raportoimallesi kontaktille:';
$a->strings['Nothing'] = 'Ei mitään';
$a->strings['Collapse contact'] = 'Taittele kontakti piilloon';
$a->strings['Their posts won\'t appear in your Network page anymore, but their replies can appear in forum threads. They still can follow you.'] = 'Hänen julkaisunsa eivät ilmesty enää Verkosto-sivullesi, mutta hänen vastauksensa voivat näkyä foorumiketjuissa. Hän voi edelleen seurata sinua.';
$a->strings['Block contact'] = 'Estä kontakti';
$a->strings['Their posts won\'t appear in your Network page anymore, but their replies can appear in forum threads, with their content collapsed by default. They cannot follow you but still can have access to your public posts by other means.'] = 'Hänen julkaisunsa eivät enää ilmesty Verkosto-sivulllesi, mutta hänen vastauksensa voivat näkyä foorumiketjuissa, niin että hänen sisältönsä on oletuksena taiteltu piiloon. Hän ei voi seurata sinua, mutta hänellä on pääsy julkisiin julkaisuihisi muilla keinoin.';
$a->strings['Forward report'] = 'Lähetä raportti edelleen';
$a->strings['1. Pick a contact'] = '1. Valitse kontakti';
$a->strings['Category'] = 'Luokka';
$a->strings['URL of the reported contact.'] = 'Raportoidun kontaktin URL';
$a->strings['Channel Relay'] = 'Kanavavälitys';
$a->strings['Registered users'] = 'Rekisteröityneet käyttäjät';
$a->strings['Pending registrations'] = 'Vireillä olevat rekisteröinnit';
$a->strings['%s user deleted'] = [
	0 => '%s käyttäjä poistettu',
	1 => '%s käyttäjää poistettu',
];
$a->strings['Register date'] = 'Rekisteripäivämäärä';
$a->strings['Last login'] = 'Viimeisin kirjautuminen';
$a->strings['Active Accounts'] = 'Aktiiviset tilit';
$a->strings['User blocked'] = 'Käyttäjä estetty';
$a->strings['Site admin'] = 'Sivuston ylläpito';
$a->strings['Account expired'] = 'Tili vanhentunut';
$a->strings['Blocked Users'] = 'Estetyt käyttäjät';
$a->strings['Create user'] = 'Luo käyttäjä';
$a->strings['Type in the details for the new user to be created.'] = 'Kirjoita uuden luotavan käyttäjän tiedot.';
$a->strings['Display name'] = 'Näyttönimi';
$a->strings['Nickname'] = 'Lempinimi';
$a->strings['Permanent deletion'] = 'Pysyvä poisto';
$a->strings['%s registration revoked'] = [
	0 => '%s rekisteröityminen kumottu',
	1 => '%s rekisteröitymistä kumottu',
];
$a->strings['Account approved.'] = 'Tili hyväksytty.';
$a->strings['Registration revoked'] = 'Rekisteröityminen kumottu';
$a->strings['Request date'] = 'Pyynnön päivämäärä';
$a->strings['No registrations.'] = 'Ei rekisteröintejä.';
$a->strings['Deny'] = 'Kieltäydy';
$a->strings['Show Ignored Requests'] = 'Näytä ohitetut pyynnöt';
$a->strings['Hide Ignored Requests'] = 'Piilota ohitetut pyynnöt';
$a->strings['Notification type:'] = 'Ilmoitustyyppi:';
$a->strings['Suggested by:'] = 'Ehdottaja:';
$a->strings['Remove'] = 'Poista';
$a->strings['Claims to be known to you: '] = 'Väittää tuntevansa sinut:';
$a->strings['Accept %s as a friend or follower?'] = 'Hyväksytäänkö %s kaveriksi tai seuraajaksi?';
$a->strings['Allows them to follow your posts.'] = 'Sallii heidän seuraavan sinun julkaisujasi.';
$a->strings['You will also follow them and receive their posts.'] = 'Myös sinä seuraat häntä ja saat nähdäksesi hänen julkaisunsa.';
$a->strings['You won\'t follow them and won\'t receive their posts.'] = 'Sinä et seuraa häntä etkä saa nähdäksesi hänen julkaisujansa.';
$a->strings['Friend (Follow them back)'] = 'Lisää kaveriksi (Seuraa takaisin)';
$a->strings['Follower'] = 'Seuraaja';
$a->strings['Accept request'] = 'Hyväksy pyyntö';
$a->strings['No more %s notifications.'] = 'Ei muita %s ilmoituksia.';
$a->strings['Network Notifications'] = 'Uutisvirtailmoitukset';
$a->strings['System Notifications'] = 'Järjestelmäilmoitukset';
$a->strings['Personal Notifications'] = 'Henkilökohtaiset ilmoitukset';
$a->strings['Home Notifications'] = 'Koti-ilmoitukset';
$a->strings['Show unread'] = 'Näytä lukemattomat';
$a->strings['{0} requested registration'] = '{0} jätti rekisteröintipyynnön';
$a->strings['Authorize application connection'] = 'Vahvista sovellusyhteys';
$a->strings['Do you want to authorize this application to access your posts and contacts, and/or create new posts for you?'] = 'Haluatko antaa tälle sovellukselle pääsyn julkaisuhisi ja kontakteihisi ja/tai antaa sovellukselle luvan luoda uusia julkaisuja?';
$a->strings['Subscribing to contacts'] = 'Kontaktien tilaaminen';
$a->strings['No contact provided.'] = 'Kontakti puuttuu.';
$a->strings['Couldn\'t fetch information for contact.'] = 'Kontaktin tietoja ei voitu hakea.';
$a->strings['Couldn\'t fetch friends for contact.'] = 'Kontaktin kavereita ei voitu hakea.';
$a->strings['Couldn\'t fetch following contacts.'] = 'Seuraavia kontakteja ei voitu hakea.';
$a->strings['Unsupported network'] = 'Verkko, jota ei tueta';
$a->strings['Done'] = 'Valmis';
$a->strings['success'] = 'onnistui';
$a->strings['failed'] = 'epäonnistui';
$a->strings['ignored'] = 'ohitettu';
$a->strings['Keep this window open until done.'] = 'Pidä tämä ikkuna auki kunnes kaikki tehtävät on suoritettu.';
$a->strings['Search in Friendica %s'] = 'Etsi Friendicassa %s';
$a->strings['Edit post'] = 'Muokkaa viestiä';
$a->strings['web link'] = 'WWW-linkki';
$a->strings['Insert video link'] = 'Lisää videolinkki';
$a->strings['video link'] = 'videolinkki';
$a->strings['Insert audio link'] = 'Lisää äänilinkki';
$a->strings['audio link'] = 'äänilinkki';
$a->strings['Remove Item Tag'] = 'Poista tägi';
$a->strings['Select a tag to remove: '] = 'Valitse tägi poistamista varten:';
$a->strings['Unlisted'] = 'Luetteloimaton';
$a->strings['Remote privacy information not available.'] = 'Yksityisyyden etätietoja ei saatavilla.';
$a->strings['Visible to:'] = 'Näkyvissä:';
$a->strings['CC:'] = 'Kopio:';
$a->strings['BCC:'] = 'Piilokopio:';
$a->strings['Audience:'] = 'Yleisö:';
$a->strings['Collection (%s)'] = 'Kokoelma (%s)';
$a->strings['Followers (%s)'] = 'Seuraajat (%s)';
$a->strings['%d more'] = '%d lisää';
$a->strings['No contacts.'] = 'Ei kontakteja.';
$a->strings['%s\'s posts'] = '%s: julkaisut';
$a->strings['%s\'s comments'] = '%s: kommentit';
$a->strings['%s\'s timeline'] = '%s: aikajana';
$a->strings['Image exceeds size limit of %s'] = 'Kuva ylittää kokorajoituksen %s';
$a->strings['Image upload didn\'t complete, please try again'] = 'Kuvan lataus ei onnistunut, yritä uudelleen';
$a->strings['Image file is missing'] = 'Kuvatiedosto puuttuu';
$a->strings['Server can\'t accept new file upload at this time, please contact your administrator'] = 'Palvelin ei voi tällä hetkellä hyväksyä uusia tiedoston lähetyksiä. Ota yhteyttä ylläpitoon.';
$a->strings['Image file is empty.'] = 'Kuvatiedosto on tyhjä.';
$a->strings['View Album'] = 'Näytä albumi';
$a->strings['Profile not found.'] = 'Profiilia ei löytynyt.';
$a->strings['Display name:'] = 'Näytettävä nimi:';
$a->strings['Member since:'] = 'Liittymispäivämäärä:';
$a->strings['j F, Y'] = 'j F, Y';
$a->strings['j F'] = 'j F';
$a->strings['Birthday:'] = 'Syntymäpäivä:';
$a->strings['Age: '] = 'Ikä:';
$a->strings['Description:'] = 'Kuvaus:';
$a->strings['Groups:'] = 'Ryhmät:';
$a->strings['View as selected profile'] = 'Näytä valittuna profiilina';
$a->strings['Edit profile'] = 'Muokkaa profiilia';
$a->strings['View as'] = 'Katsele roolissa';
$a->strings['Profile unavailable.'] = 'Profiili ei saatavilla.';
$a->strings['Invalid locator'] = 'Viallinen paikannin';
$a->strings['Friend/Connection Request'] = 'Kaveri-/yhteyspyyntö';
$a->strings['Restricted profile'] = 'Rajoitettu profiili';
$a->strings['Private Message'] = 'Yksityisviesti';
$a->strings['via'] = 'kautta';
$a->strings['Scheduled'] = 'Aikataulutettu';
$a->strings['Content'] = 'Sisältö';
$a->strings['Remove post'] = 'Poista julkaisu';
$a->strings['This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.'] = 'Sivuston päivittäinen rekisteröintiraja ylitetty. Yritä uudelleen huomenna.';
$a->strings['If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.'] = 'Jos OpenID ei ole tuttu, jätä kenttä tyhjäksi.';
$a->strings['Your OpenID (optional): '] = 'OpenID -tunnus (valinnainen):';
$a->strings['Include your profile in member directory?'] = 'Lisää profiilisi jäsenluetteloon?';
$a->strings['Note for the admin'] = 'Viesti ylläpidolle';
$a->strings['Leave a message for the admin, why you want to join this node'] = 'Kerro yllåpitäjälle miksi haluat liittyä tähän Friendica -sivustoon';
$a->strings['Membership on this site is by invitation only.'] = 'Tähän sivustoon voi liittyä vain kutsusta.';
$a->strings['Your invitation code: '] = 'Kutsukoodisi:';
$a->strings['New Password:'] = 'Uusi salasana:';
$a->strings['Leave empty for an auto generated password.'] = 'Jätä tyhjäksi jos haluat automaattisesti luotu salasanan.';
$a->strings['Confirm:'] = 'Vahvista:';
$a->strings['Choose a nickname: '] = 'Valitse lempinimi:';
$a->strings['Import'] = 'Tuo';
$a->strings['Import your profile to this friendica instance'] = 'Tuo profiilisi tähän Friendica -instanssiin.';
$a->strings['Parent Password:'] = 'Ylätason salasana:';
$a->strings['Nickname can only contain US-ASCII characters.'] = 'Nimimerkki voi sisältää vain US-ASCII-merkkejä.';
$a->strings['Registration successful. Please check your email for further instructions.'] = 'Rekisteröityminen onnistui. Saat kohta lisäohjeita sähköpostitse.';
$a->strings['Registration successful.'] = 'Rekisteröityminen onnistui.';
$a->strings['Your registration can not be processed.'] = 'Rekisteröintisi ei voida käsitellä.';
$a->strings['An internal error occured.'] = 'Tapahtui sisäinen virhe.';
$a->strings['Your registration is pending approval by the site owner.'] = 'Rekisteröintisi odottaa ylläpitäjän hyväksyntää.';
$a->strings['You must be logged in to use this module.'] = 'Sinun pitää kirjautua sisälle käyttääksesi tätä moduulia.';
$a->strings['Only logged in users are permitted to perform a search.'] = 'Vain sisäänkirjautuneet käyttäjät voivat tehdä hakuja.';
$a->strings['Only one search per minute is permitted for not logged in users.'] = 'Käyttäjät, jotka eivät ole kirjautuneet sisään, voivat tehdä vain yhden haun minuutissa.';
$a->strings['Items tagged with: %s'] = 'Tunnisteella "%s" merkityt kohteet';
$a->strings['Search term was not saved.'] = 'Hakusanaa ei tallennettu.';
$a->strings['Search term already saved.'] = 'Hakusana on jo tallennettu.';
$a->strings['Search term was not removed.'] = 'Hakusanaa ei poistettu.';
$a->strings['Your OpenID: '] = 'OpenID-tunnuksesi';
$a->strings['Please enter your username and password to add the OpenID to your existing account.'] = 'Syötä käyttäjänimesi ja salasanasi lisätäksesi OpenID:n olemassa olevaan tiliisi.';
$a->strings['Or sign in using OpenID'] = 'Tai kirjaudu sisään OpenID:llä';
$a->strings['OpenID'] = 'OpenID';
$a->strings['New here?'] = 'Uusi täällä?';
$a->strings['Password'] = 'Salasana';
$a->strings['Remember me'] = 'Muista minut';
$a->strings['Forgot your password?'] = 'Unohditko salasanasi?';
$a->strings['Website Terms of Service'] = 'Verkkosivun käyttöehdot';
$a->strings['terms of service'] = 'käyttöehdot';
$a->strings['Website Privacy Policy'] = 'Sivuston tietosuojakäytäntö';
$a->strings['privacy policy'] = 'tietosuojakäytäntö';
$a->strings['OpenID protocol error. No ID returned'] = 'OpenID-protokollavirhe. Ei palautettua tunnistetta.';
$a->strings['Account not found. Please login to your existing account to add the OpenID to it.'] = 'Tiliä ei löydy. Kirjaudu sisälle olemassa olevaan tiliisi lisätäksesi siihen OpenID:n.';
$a->strings['Account not found. Please register a new account or login to your existing account to add the OpenID to it.'] = 'Tiliä ei löydy. Luo uusi tili tai kirjaudu sisälle olemassa olevaan tiliisi lisätäksesi siihen OpenID:n.';
$a->strings['Passwords do not match.'] = 'Salasanat eivät täsmää.';
$a->strings['Password does not need changing.'] = 'Salasanaa ei tarvitse vaihtaa.';
$a->strings['Password unchanged.'] = 'Salasanaa ei muutettu.';
$a->strings['Password Too Long'] = 'Salasana liian pitkä';
$a->strings['Since version 2022.09, we\'ve realized that any password longer than 72 characters is truncated during hashing. To prevent any confusion about this behavior, please update your password to be fewer or equal to 72 characters.'] = 'Huomasimme versiosta 2022.09 julkaisun jälkeen, että hajaitusfunktiot lyhentävät salasanat, jotka ovat yli 72 merkkiä pitkiä. Estääksesi tähän liittyvää hämmennystä, päivitä salasanasi siten, että se on enintään 72 merkkiä pitkä.';
$a->strings['Update Password'] = 'Päivitä salasana';
$a->strings['Current Password:'] = 'Nykyinen salasana:';
$a->strings['Your current password to confirm the changes'] = 'Syötä nykyinen salasanasi vahvistaaksesi muutokset';
$a->strings['Allowed characters are a-z, A-Z, 0-9 and special characters except white spaces and accentuated letters.'] = 'Sallitut merkit ovat a-z, A-Z, 0-9 ja erikoismerkit lukuun ottamatta välilyöntiä ja kirjaimia, joissa on aksenttimerkki.';
$a->strings['Password length is limited to 72 characters.'] = 'Salasanan enimmäispituus on 72 merkkiä.';
$a->strings['Remaining recovery codes: %d'] = 'Jäljellä olevat palautuskoodit: %d';
$a->strings['Invalid code, please retry.'] = 'Väärä koodi, yritä uudelleen.';
$a->strings['Two-factor recovery'] = 'Kaksivaiheinen palautus';
$a->strings['<p>You can enter one of your one-time recovery codes in case you lost access to your mobile device.</p>'] = '<p>Voit syöttää yhden kertakäyttöisistä palautuskoodeistasi, jos sinulla ei ole pääsyä mobiililaitteeseesi.</p>';
$a->strings['Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>'] = 'Eikö puhelimesi ole saatavilla? <a href="%s">Syötä kaksivaiheinen palautuskoodi</a>';
$a->strings['Please enter a recovery code'] = 'Syötä palautuskoodi';
$a->strings['Submit recovery code and complete login'] = 'Syötä palautuskoodi ja viimeistele sisäänkirjautuminen';
$a->strings['Sign out of this browser?'] = 'Kirjaudutaanko ulos tästä selaimesta?';
$a->strings['<p>If you trust this browser, you will not be asked for verification code the next time you sign in.</p>'] = '<p>Jos luotat tähän selaimeen, sinulta ei pyydetä vahvistuskoodia seuraavan kerran kun kirjaudut sisään</p>';
$a->strings['Trust and sign out'] = 'Luota ja kirjaudu ulos';
$a->strings['Couldn\'t save browser to Cookie.'] = 'Selainta ei voitu tallentaa evästeisiin.';
$a->strings['Trust this browser?'] = 'Luotatko tähän selaimeen?';
$a->strings['<p>If you choose to trust this browser, you will not be asked for a verification code the next time you sign in.</p>'] = '<p>Jos päätät luottaa tähän selaimeen, sinulta ei pyydetä vahvistuskoodia seuraavan kerran kun kirjaudut sisään</p>';
$a->strings['Not now'] = 'Ei nyt';
$a->strings['Don\'t trust'] = 'Älä luota';
$a->strings['Trust'] = 'Luota';
$a->strings['<p>Open the two-factor authentication app on your device to get an authentication code and verify your identity.</p>'] = '<p>Avaa kaksivaiheisen todennuksen sovellus mobiililaitteellasi saadaksesi todennuskoodin ja vahvistaaksesi henkilöllisyytesi.</p>';
$a->strings['If you do not have access to your authentication code you can use a <a href="%s">two-factor recovery code</a>.'] = 'Jos sinulla ei ole pääsyä todennuskoodiisi, voit käyttää <a href="%s">kaksivaiheista palautuskoodia</a>.';
$a->strings['Please enter a code from your authentication app'] = 'Syötä todennussovelluksessasi oleva koodi';
$a->strings['Verify code and complete login'] = 'Vahvista koodi ja viimeistele sisäänkirjautuminen';
$a->strings['Please use a shorter name.'] = 'Käytä lyhyempää nimeä.';
$a->strings['Name too short.'] = 'Nimi on liian lyhyt.';
$a->strings['Wrong Password.'] = 'Väärä salasana.';
$a->strings['Invalid email.'] = 'Virheellinen sähköposti.';
$a->strings['Cannot change to that email.'] = 'Tähän sähköpostiosoitteeseen ei voida vaihtaa.';
$a->strings['Settings were not updated.'] = 'Asetuksia ei ole päivitetty.';
$a->strings['Relocate message has been send to your contacts'] = 'Siirtymisviesti on lähetetty kontakteillesi';
$a->strings['Unable to find your profile. Please contact your admin.'] = 'Profiiliasi ei löytynyt. Ota yhteyttä ylläpitoon.';
$a->strings['Account for a service that automatically shares content based on user defined channels.'] = 'Tili palvelulle, joka jakaa automaattisesti sisältöä perustuen käyttäjän määrittelemiin kanaviin.';
$a->strings['Personal Page Subtypes'] = 'Henkilökohtaisen sivun alatyypit';
$a->strings['Community Group Subtypes'] = 'Yhteisöryhmien alatyypit';
$a->strings['Account for a personal profile.'] = 'Henkilökohtaisen profiilin käyttäjätili.';
$a->strings['Account for an organisation that automatically approves contact requests as "Followers".'] = 'Tili organisaatiolle, joka hyväksyy kontaktipyynnöt automaattisesti "seuraajiksi".';
$a->strings['Account for a news reflector that automatically approves contact requests as "Followers".'] = 'Tili uutisten välittäjälle, joka hyväksyy kontaktipyynnöt automaattisesti "seuraajiksi".';
$a->strings['Account for community discussions.'] = 'Tili yhteisökeskusteluja varten.';
$a->strings['Account for a regular personal profile that requires manual approval of "Friends" and "Followers".'] = 'Tavallisen henkilöprofiilin tili, joka vaatii "kavereiden" ja "seuraajien" manuaalisen hyväksynnän.';
$a->strings['Account for a public profile that automatically approves contact requests as "Followers".'] = 'Tili julkiselle profiilille, joka hyväksyy kontaktipyynnöt automaattisesti "seuraajiksi".';
$a->strings['Automatically approves all contact requests.'] = 'Hyväksyy kaikki kontaktipyynnöt automaattisesti';
$a->strings['Contact requests have to be manually approved.'] = 'Kontaktipyynnöt täytyy hyväksyä manuaalisesti.';
$a->strings['Account for a popular profile that automatically approves contact requests as "Friends".'] = 'Suositun profiilin tili, joka automaattisesti hyväksyy kontaktipyynnöt ”kavereiksi”.';
$a->strings['Private Group [Experimental]'] = 'Yksityinen ryhmä [kokeellinen]';
$a->strings['Requires manual approval of contact requests.'] = 'Vaatii kontaktipyyntöjen manuaalisen hyväksynnän';
$a->strings['OpenID:'] = 'OpenID:';
$a->strings['(Optional) Allow this OpenID to login to this account.'] = '(Valinnainen) Salli sisäänkirjautuminen tähän tiliin tällä OpenID-tunnuksella.';
$a->strings['Publish your profile in your local site directory?'] = 'Julkaistaanko profiilisi paikallisessa sivustohakemistossasi?';
$a->strings['Your profile will be published in this node\'s <a href="%s">local directory</a>. Your profile details may be publicly visible depending on the system settings.'] = 'Profiilisi julkaistaan tämän solmun <a href="%s">paikallisessa hakemistossa</a>. Profiilisi yksityiskohdat saattavat olla julkisesti nähtävissä riippuen järjestelmän asetuksista.';
$a->strings['Your profile will also be published in the global friendica directories (e.g. <a href="%s">%s</a>).'] = 'Profiilisi julkaistaan myös maailmanlaajuisissa Friendica-hakemistoissa (esim. <a href="%s">%s</a>).';
$a->strings['Account Settings'] = 'Tiliasetukset';
$a->strings['Your Identity Address is <strong>\'%s\'</strong> or \'%s\'.'] = 'Identiteettisi osoite on <strong>\'%s\'</strong> tai \'%s\'.';
$a->strings['Password Settings'] = 'Salasana-asetukset';
$a->strings['Leave password fields blank unless changing'] = 'Jätä salasana kenttää tyhjäksi jos et halua vaihtaa salasanaa';
$a->strings['Password:'] = 'Salasana:';
$a->strings['Your current password to confirm the changes of the email address'] = 'Nykyinen salasanasi sähköpostiosoitteen muutoksen vahvistamiseksi';
$a->strings['Delete OpenID URL'] = 'Poista OpenID-URL';
$a->strings['Basic Settings'] = 'Perusasetukset';
$a->strings['Email Address:'] = 'Sähköpostiosoite:';
$a->strings['Your Timezone:'] = 'Aikavyöhyke:';
$a->strings['Your Language:'] = 'Kieli:';
$a->strings['Set the language we use to show you friendica interface and to send you emails'] = 'Aseta Friendican käyttöliittymän ja sähköpostiviestien kieli';
$a->strings['Default Post Location:'] = 'Julkaisun oletussijainti:';
$a->strings['Use browser location'] = 'Käytä selaimen sijaintia';
$a->strings['Security and Privacy Settings'] = 'Turvallisuus ja tietosuoja-asetukset';
$a->strings['Maximum Friend Requests/Day:'] = 'Kaveripyyntöjen enimmäismäärä päivässä:';
$a->strings['(to prevent spam abuse)'] = '(roskapostin estämiseksi)';
$a->strings['Allow your profile to be searchable globally?'] = 'Sallitko profiilisi näkyvän maailmanlaajuisessa haussa?';
$a->strings['Activate this setting if you want others to easily find and follow you. Your profile will be searchable on remote systems. This setting also determines whether Friendica will inform search engines that your profile should be indexed or not.'] = 'Ota tämä asetus käyttöön, jos haluat muiden löytävän sinut helposti ja seuraavan sinua. Profiilisi on haettavissa etäjärjestelmissä. Tämä asetus määrittää myös sen, ilmoittaako Friendica hakukoneille voiko profiilisi indeksoida vai ei.';
$a->strings['Hide your contact/friend list from viewers of your profile?'] = 'Piilotetaanko kontakti-/ystävälistasi profiilisi katselijoilta?';
$a->strings['A list of your contacts is displayed on your profile page. Activate this option to disable the display of your contact list.'] = 'Luettelo kontakteistasi näytetään profiilisivullasi. Laita tämä valinta päälle, jos et halua näyttää kontaktiluetteloasi.';
$a->strings['Hide your public content from anonymous viewers'] = 'Piilota julkinen sisältösi anonyymeiltä katselijoilta';
$a->strings['Anonymous visitors will only see your basic profile details. Your public posts and replies will still be freely accessible on the remote servers of your followers and through relays.'] = 'Anonyymit vierailijat näkevät vain profiilisi perustiedot. Julkisiin julkaisuihisi ja vastauksiisi on edelleen vapaa pääsy seuraajiesi etäpalvelimilla ja välittäjien kautta.';
$a->strings['Make public posts unlisted'] = 'Tee julkisista julkaisuista listaamattomia';
$a->strings['Your public posts will not appear on the community pages or in search results, nor be sent to relay servers. However they can still appear on public feeds on remote servers.'] = 'Julkiset julkaisusi eivät näy yhteisösivuilla tai hakutuloksissa, eikä niitä lähetetä välittäjäpalvelimille. Julkaisusi saattavat kuitenkin näkyä etäpalvelimien julkisissa syötteissä.';
$a->strings['Make all posted pictures accessible'] = 'Anna pääsy kaikkiin julkaistuihin kuviin';
$a->strings['This option makes every posted picture accessible via the direct link. This is a workaround for the problem that most other networks can\'t handle permissions on pictures. Non public pictures still won\'t be visible for the public on your photo albums though.'] = 'Tämä valita antaa pääsyn jokaiseen julkaistuun kuvaan suoran linkin kautta. Tämä on hätäratkaisu siihen ongelmaan, että suurin osa muista verkostoista ei pysty käsittelemään kuvien käyttöoikeuksia. Yksityiset kuvat eivät kuitenkaan edelleenkään ole julkisesti näkyvillä valokuva-albumeissasi.';
$a->strings['Allow friends to post to your profile page?'] = 'Annetaanko kavereiden julkaista profiilisivullasi?';
$a->strings['Your contacts may write posts on your profile wall. These posts will be distributed to your contacts'] = 'Kontaktisi voivat kirjoittaa julkaisuja profiiliseinälllesi. Nämä julkaisut jaetaan kontakteillesi.';
$a->strings['Allow friends to tag your posts?'] = 'Annetaanko kavereiden lisätä tunnisteita julkaisuihisi?';
$a->strings['Your contacts can add additional tags to your posts.'] = 'Kontaktisi voi lisätä ylimääräisiä tunnisteita julkaisuusi.';
$a->strings['Default privacy circle for new contacts'] = 'Uusien kontaktien oletusyksityisyyspiiri';
$a->strings['Default privacy circle for new group contacts'] = 'Uusien ryhmäkontaktien oletusyksityisyyspiiri';
$a->strings['Default Post Permissions'] = 'Julkaisun oletuskäyttöoikeudet:';
$a->strings['Expiration settings'] = 'Vanhentumisasetukset';
$a->strings['Automatically expire posts after this many days:'] = 'Tee julkaisuista vanhentuneita näin monen päivän kuluttua:';
$a->strings['If empty, posts will not expire. Expired posts will be deleted'] = 'Jos kenttä jää tyhjäksi, julkaisut eivät vanhene. Vanhentuneet julkaisut poistetaan.';
$a->strings['Expire posts'] = 'Tee julkaisuista vanhentuneita';
$a->strings['When activated, posts and comments will be expired.'] = 'Valitse tehdäksesi julkaisusta ja kommenteista vanhentuvia';
$a->strings['Expire personal notes'] = 'Tee yksityismuistiinpanoista vanhentuvia';
$a->strings['When activated, the personal notes on your profile page will be expired.'] = 'Aktivoituna profiilisivusi yksityismuistiinpanot vanhentuvat.';
$a->strings['Expire starred posts'] = 'Anna tähdellisten julkaisujen vanheta';
$a->strings['Starring posts keeps them from being expired. That behaviour is overwritten by this setting.'] = 'Julkaisujen merkitseminen tähdellä estää niitä vanhenemasta. Tämä asetus kumoaa kyseisen ominaisuuden.';
$a->strings['Only expire posts by others'] = 'Anna vain muiden tekemien julkaisujen vanheta';
$a->strings['When activated, your own posts never expire. Then the settings above are only valid for posts you received.'] = 'Jos aktivoit tämän asetuksen, omat julkaisusi eivät vanhene koskaan. Ylläolevat asetukset koskevat siten vain sinulle lähetettyjä julkaisuja.';
$a->strings['Notification Settings'] = 'Ilmoitusasetukset';
$a->strings['Send a notification email when:'] = 'Lähetä sähköposti-ilmoitus kun:';
$a->strings['You receive an introduction'] = 'Vastaanotat kaverikutsun';
$a->strings['Your introductions are confirmed'] = 'Kaverikutsusi on hyväksytty';
$a->strings['Someone writes on your profile wall'] = 'Joku kirjoittaa profiiliseinällesi';
$a->strings['Someone writes a followup comment'] = 'Joku vastaa kommenttiin';
$a->strings['You receive a private message'] = 'Vastaanotat yksityisviestin';
$a->strings['You receive a friend suggestion'] = 'Saat kaveriehdotuksen';
$a->strings['You are tagged in a post'] = 'Sinut on merkitty julkaisuun';
$a->strings['Create a desktop notification when:'] = 'Luo työpöytäilmoitus kun:';
$a->strings['Someone tagged you'] = 'Joku merkitsi sinut julkaisuun';
$a->strings['Someone directly commented on your post'] = 'Joku kommentoi suoraan julkaisuusi';
$a->strings['Someone liked your content'] = 'Joku tykkäsi sisällöstäsi';
$a->strings['Can only be enabled, when the direct comment notification is enabled.'] = 'Voidaan laittaa päälle vain kun suora kommentti -ilmoitus on laitettu päälle.';
$a->strings['Someone shared your content'] = 'Joku jakoi sisältösi';
$a->strings['Someone commented in your thread'] = 'Joku jätti kommentin ketjuusi.';
$a->strings['Someone commented in a thread where you commented'] = 'Joku kommentoi ketjussa, jossa sinä olet kommentoinut';
$a->strings['Someone commented in a thread where you interacted'] = 'Joku kommentoi ketjussa, jonka kanssa olet ollut vuorovaikutuksessa';
$a->strings['Activate desktop notifications'] = 'Ota työpöytäilmoitukset käyttöön';
$a->strings['Show desktop popup on new notifications'] = 'Näytä uudet työpöytäilmoitukset ponnahdusikkunassa';
$a->strings['Text-only notification emails'] = 'Ilmoitussähköposteissa vain tekstiä';
$a->strings['Send text only notification emails, without the html part'] = 'Lähetä ilmoitussähköposteissa vain tekstiä ilman HTML-koodia';
$a->strings['Show detailled notifications'] = 'Näytä yksityiskohtaiset ilmoitukset';
$a->strings['Per default, notifications are condensed to a single notification per item. When enabled every notification is displayed.'] = 'Oletuksena ilmoituksia lähetään yksi per kohde. Kun tämä asetus on valittuna, jokainen ilmoitus näytetään.';
$a->strings['Show notifications of ignored contacts'] = 'Näytä ilmoitukset sivuutetuilta kontakteilta';
$a->strings['Advanced Account/Page Type Settings'] = 'Käyttäjätilin/sivutyypini lisäasetukset';
$a->strings['Relocate'] = 'Uudelleensijoitus';
$a->strings['Addon Settings'] = 'Lisäosa-asetukset';
$a->strings['None of the addons installed on this server have any settings.'] = 'Yhdessäkään tälle palvelimelle asennetuissa lisäosissa ei ole mitään asetuksia.';
$a->strings['This page can be used to define the channels that will automatically be reshared by your account.'] = 'Voit käyttää tätä sivua niiden kanavien määrittämiseen, jotka jaetaan automaattisesti tililäsi.';
$a->strings['This page can be used to define your own channels.'] = 'Voit määrittää omat kanavasi tällä sivulla.';
$a->strings['Publish'] = 'Julkaise';
$a->strings['When selected, the channel results are reshared. This only works for public ActivityPub posts from the public timeline or the user defined circles.'] = 'Kun valinta on päällä, kanavan tulokset jaetaan uudestaan. Tämä toimii vain julkisilta aikajanoilta tai käyttäjän määrittämilta piireiltä tulleille julkisille ActivityPub-julkaisuille.';
$a->strings['Label'] = 'Nimike';
$a->strings['Description'] = 'Kuvaus';
$a->strings['Access Key'] = 'Pääsyavain';
$a->strings['Circle/Channel'] = 'Piiri/kanava';
$a->strings['Include Tags'] = 'Sisällytä tunnisteet';
$a->strings['Exclude Tags'] = 'Sulje pois tunnisteet';
$a->strings['Minimum Size'] = 'Vähimmäiskoko';
$a->strings['Maximum Size'] = 'Enimmäiskoko';
$a->strings['Full Text Search'] = 'Haku koko tekstistä';
$a->strings['Languages'] = 'Kielet';
$a->strings['Delete channel'] = 'Poista kanava';
$a->strings['Check to delete this entry from the channel list'] = 'Valitse poistaaksesi tämän merkinnän kanavaluettelosta';
$a->strings['Add'] = 'Lisää';
$a->strings['Diaspora (Socialhome, Hubzilla)'] = 'Diaspora (Socialhome, Hubzilla)';
$a->strings['Email access is disabled on this site.'] = 'Sähköpostikäyttö on pois käytöstä tällä sivustolla.';
$a->strings['None'] = 'Ei mitään';
$a->strings['General Social Media Settings'] = 'Yleiset some-asetukset';
$a->strings['By default, conversations in which your follows participated but didn\'t start will be shown in your timeline. You can turn this behavior off, or expand it to the conversations in which your follows liked a post.'] = 'Keskustelut, joihin seurattavasi osallistuivat, mutta joita he eivät aloittaneet, näkyvät oletuksena aikajanallasi. Voit laittaa tämän toiminnon pois päältä tai laajentaa näkyvyyttä keskusteluihin, joissa seurattavasi tykkäsivät jostain julkaisusta.';
$a->strings['Only conversations my follows started'] = 'Vain keskustelut, jotka seurattavani ovat aloittaneet';
$a->strings['Conversations my follows started or commented on (default)'] = 'Keskustelut, jotka seurattavani ovat aloittaneet tai joihin he ovat kommentoineet (oletus)';
$a->strings['Normally the system tries to find the best link to add to shortened posts. If disabled, every shortened post will always point to the original friendica post.'] = 'Tavallisesti järjestelmä yrittää löytää parhaan linkin lyhennettyihin julkaisuihin. Jos tämä asetus on pois käytöstä, jokainen lyhennetty julkaisu osoittaa aina alkuperäiseen Friendica-julkaisuun.';
$a->strings['Email/Mailbox Setup'] = 'Sähköpostin asennus';
$a->strings['Last successful email check:'] = 'Viimeisin onnistunut sähköpostitarkistus:';
$a->strings['IMAP server name:'] = 'IMAP-palvelimen nimi:';
$a->strings['IMAP port:'] = 'IMAP-porttti:';
$a->strings['Security:'] = 'Turvallisuus:';
$a->strings['Email login name:'] = 'Sähköpostitilin käyttäjätunnus:';
$a->strings['Email password:'] = 'Sähköpostin salasana:';
$a->strings['Reply-to address:'] = 'Vastausosoite:';
$a->strings['Send public posts to all email contacts:'] = 'Lähetä julkiset julkaisut kaikille sähköpostikontakteille:';
$a->strings['Action after import:'] = 'Toiminta tuonnin jälkeen:';
$a->strings['Move to folder'] = 'Siirrä kansioon';
$a->strings['Move to folder:'] = 'Siirrä kansioon:';
$a->strings['Importing Contacts done'] = 'Kontaktien tuonti valmis';
$a->strings['Upload File'] = 'Lataa tiedosto';
$a->strings['Delegation successfully revoked.'] = 'Delegoinin kumoaminen onnistui.';
$a->strings['No parent user'] = 'Ei emokäyttäjää';
$a->strings['Parent User'] = 'Emokäyttäjä';
$a->strings['Additional Accounts'] = 'Lisätilit';
$a->strings['Delegates'] = 'Valtuutetut';
$a->strings['Existing Page Delegates'] = 'Sivun valtuutetut';
$a->strings['Potential Delegates'] = 'Mahdolliset valtuutetut';
$a->strings['No entries.'] = 'Ei kohteita.';
$a->strings['The theme you chose isn\'t available.'] = 'Valitsemasi teema ei ole saatavilla.';
$a->strings['%s - (Unsupported)'] = '%s - (Ei tueta)';
$a->strings['Color/Black'] = 'Väri/musta';
$a->strings['Black'] = 'Musta';
$a->strings['Color/White'] = 'Väri/valkoinen';
$a->strings['White'] = 'Valkoinen';
$a->strings['No preview'] = 'Ei esikatselua';
$a->strings['No image'] = 'Ei kuvaa';
$a->strings['Small Image'] = 'Pieni kuva';
$a->strings['Large Image'] = 'Suuri kuva';
$a->strings['Automatic image size'] = 'Automaattinen kuvan koko';
$a->strings['Display Settings'] = 'Näyttöasetukset';
$a->strings['Content Settings'] = 'Sisältöasetukset';
$a->strings['Theme settings'] = 'Teeman asetukset';
$a->strings['Settings for %s'] = 'Asetukset kohteelle "%s"';
$a->strings['Note: If you switch the theme, you need to save changes before you can see the settings for the new theme below.'] = 'Huomautus: Jos vaihdat teemaa, sinun täytyy tallentaa muutokset ennen kuin voit nähdä uuden teemaan asetukset alla.';
$a->strings['Timelines'] = 'Aikajanat';
$a->strings['Drag to reorder or tab to item with keyboard and move up/down with arrow keys'] = 'Järjestääkseesi uudelleen raahaa kohde haluamaasi sijaintiin tai paina sarkain-näppäintä kohteen kohdalla ja siirrä sitä ylös/alas nuolinäppäimillä';
$a->strings['Reset order'] = 'Palauta järjestys alkuperäiseen';
$a->strings['Display theme'] = 'Näyttöteema';
$a->strings['Mobile theme'] = 'Mobiiliteema';
$a->strings['Number of items to display per page:'] = 'Näytettävien kohteiden määrä sivulla:';
$a->strings['Maximum of 100 items'] = 'Enintään 100 kohdetta';
$a->strings['Number of items to display per page when viewed from mobile device:'] = 'Näytettävien kohteiden määrä sivulla mobiililaitteella katseltaessa:';
$a->strings['Display emoticons'] = 'Näytä emoticonit';
$a->strings['When enabled, emoticons are replaced with matching symbols.'] = 'Kun asetus on päällä, emoticonit korvataan vastaavilla symboleilla.';
$a->strings['Infinite scroll'] = 'Loputon selaaminen';
$a->strings['Automatic fetch new items when reaching the page end.'] = 'Nouda automaattisesti uudet kohteet, kun saavutaan sivun loppuun.';
$a->strings['Enable Smart Threading'] = 'Laita päälle älykäs ketjutus';
$a->strings['Enable the automatic suppression of extraneous thread indentation.'] = 'Laita päälle automaattinen liiallisten ketjusisennysten esto.';
$a->strings['Display the Dislike feature'] = 'Näytä En tykkää -toiminto';
$a->strings['Display the Dislike button and dislike reactions on posts and comments.'] = 'Näytä En tykkää -painike ja En tykkää -reaktiot julkaisuissa ja kommenteissa.';
$a->strings['Display the resharer'] = 'Näytä uudelleenjakaja';
$a->strings['Display the first resharer as icon and text on a reshared item.'] = 'Näytä ensimmäinen uudelleen jakaja kuvakkeena ja tekstinä uudelleen jaetussa kohteessa.';
$a->strings['Stay local'] = 'Pysy paikallisena';
$a->strings['Don\'t go to a remote system when following a contact link.'] = 'Älä siirry etäjärjestelmään kun seuraat kontaktilinkkiä.';
$a->strings['Show the post deletion checkbox'] = 'Näytä poista julkaisu -valintaruutu';
$a->strings['Display the checkbox for the post deletion on the network page.'] = 'Näytä valintaruutu, jolla voi poistaa julkaisun verkostosivulla.';
$a->strings['DIsplay the event list'] = 'Näytä tapahtumaluettelo';
$a->strings['Display the birthday reminder and event list on the network page.'] = 'Näytä syntymäpäivämuistus- ja tapahtumaluettelo verkostosivulla.';
$a->strings['Link preview mode'] = 'Linkin esikatselun ulkoasu';
$a->strings['Appearance of the link preview that is added to each post with a link.'] = 'Linkin esikatselun ulkoasu, joka lisätään jokaiseen linkin sisältävään julkaisuun.';
$a->strings['Hide pictures with empty alternative text'] = 'Piilota kuvat, joiden vaihtoehtoinen teksti on tyhjä';
$a->strings['Don\'t display pictures that are missing the alternative text.'] = 'Älä näytä kuvia, joista puuttuu vaihtoehtoinen teksti.';
$a->strings['Hide custom emojis'] = 'Piilota kustomoidut emojit';
$a->strings['Don\'t display custom emojis.'] = 'Älä näytä kustomoituja emojeja.';
$a->strings['Platform icons style'] = 'Alustan kuvakkeiden tyyli';
$a->strings['Style of the platform icons'] = 'Alustan kuvakkeiden tyyli';
$a->strings['Embed remote media'] = 'Upota etämedia';
$a->strings['When enabled, remote media will be embedded in the post, like for example YouTube videos.'] = 'Kun tämä asetus on valittu, etämedia upotetaan julkaisuun, esimerkiksi YouTube-videot.';
$a->strings['Embed supported media'] = 'Upota tuettu media';
$a->strings['Channels Widget'] = 'Kanavat-pienoisohjelma';
$a->strings['Top Menu'] = 'Ylävalikko';
$a->strings['Enable timelines that you want to see in the channels widget. Bookmark timelines that you want to see in the top menu.'] = 'Salli kanavat-widgetissä aikajanat, jotka haluat nähdä. Lisää kirjanmerkki aikajanoille, jotka haluat nähdä ylävalikossa.';
$a->strings['Channel languages:'] = 'Kanavan kielet:';
$a->strings['Beginning of week:'] = 'Viikon alku:';
$a->strings['Default calendar view:'] = 'Oletus kalenterinäkymä:';
$a->strings['Additional Features'] = 'Lisäominaisuuksia';
$a->strings['Connected Apps'] = 'Yhdistetyt sovellukset';
$a->strings['Remove authorization'] = 'Poista lupa';
$a->strings['Display Name is required.'] = 'Näytettävä nimi on pakollinen.';
$a->strings['Profile couldn\'t be updated.'] = 'Profiilia ei voitu päivittää.';
$a->strings['Label:'] = 'Nimike:';
$a->strings['Value:'] = 'Arvo:';
$a->strings['Field Permissions'] = 'Kentän käyttöoikeudet';
$a->strings['(click to open/close)'] = '(klikkaa auki/kiinni)';
$a->strings['The homepage is verified. A rel="me" link back to your Friendica profile page was found on the homepage.'] = 'Kotisivu on vahvistettu. Löysimme kotisivultasi rel="me" -linkin, joka osoittaa Friendica-profiilisivullesi.';
$a->strings['Profile Actions'] = 'Profiilin toiminnot';
$a->strings['Edit Profile Details'] = 'Muokkaa profiilin yksityiskohdat';
$a->strings['Upload new picture'] = 'Lataa uusi kuva';
$a->strings['Pick existing picture from photos'] = 'Valitse kuva valokuvistasi';
$a->strings['Go to my photos'] = 'Siirry omiin kuviin';
$a->strings['Profile picture'] = 'Profiilikuva';
$a->strings['Location'] = 'Sijainti';
$a->strings['Miscellaneous'] = 'Sekalaista';
$a->strings['<p>Custom fields appear on <a href="%s">your profile page</a>.</p>
				<p>You can use BBCodes in the field values.</p>
				<p>Reorder by dragging the field title.</p>
				<p>Empty the label field to remove a custom field.</p>
				<p>Non-public fields can only be seen by the selected Friendica contacts or the Friendica contacts in the selected circles.</p>'] = '<p>Kustomoidut kentät näkyvät <a href="%s">profiilisivullasi</a>.</p>
				<p>Voit käyttää BBCodea kentän arvoissa.</p>
				<p>Järjestä uudelleen raahaamalla kentän otsakkeesta</p>
				<p>Tyhjennä nimike-kenttä, jos haluat poistaa kustomoidun kentän.</p>
				<p>Yksityiset kentät näkyvät vain valituille Friendica-kontakteille tai valituissa piireissä oleville Friendica-kontakteille.</p>';
$a->strings['Street Address:'] = 'Katuosoite:';
$a->strings['Locality/City:'] = 'Kaupunki:';
$a->strings['Region/State:'] = 'Alue/osavaltio:';
$a->strings['Postal/Zip Code:'] = 'Postinumero:';
$a->strings['Country:'] = 'Maa:';
$a->strings['XMPP (Jabber) address:'] = 'XMPP (Jabber) osoite:';
$a->strings['Homepage URL:'] = 'Kotisivun URL-osoite:';
$a->strings['Public Keywords:'] = 'Julkiset avainsanat:';
$a->strings['(Used for suggesting potential friends, can be seen by others)'] = '(Käytetään kaveriehdotuksia varten, näkyy muille)';
$a->strings['Private Keywords:'] = 'Yksityiset avainsanat:';
$a->strings['(Used for searching profiles, never shown to others)'] = '(Käytetään profiilihakua varten, ei näy muille)';
$a->strings['Image size reduction [%s] failed.'] = 'Kuvan pienentäminen [%s] epäonnistui.';
$a->strings['Shift-reload the page or clear browser cache if the new photo does not display immediately.'] = 'Jos kuva ei näy heti, lataa sivu uudelleen tai tyhjennä selaimen välimuisti.';
$a->strings['Unable to process image'] = 'Kuvan käsitteleminen epäonnistui';
$a->strings['Crop Image'] = 'Rajaa kuva';
$a->strings['Please adjust the image cropping for optimum viewing.'] = 'Rajaa kuva sopivasti.';
$a->strings['Use Image As Is'] = 'Käytä kuvaa sinänsä';
$a->strings['Missing uploaded image.'] = 'Lähetetty kuva puuttuu.';
$a->strings['Profile Picture Settings'] = 'Profiilikuvan asetukset';
$a->strings['Current Profile Picture'] = 'Nykyinen profiilikuva';
$a->strings['Upload Profile Picture'] = 'Lähetä profiilikuva';
$a->strings['Upload Picture:'] = 'Lähetä kuva:';
$a->strings['or'] = 'tai';
$a->strings['skip this step'] = 'ohita tämä vaihe';
$a->strings['select a photo from your photo albums'] = 'valitse kuva albumeistasi';
$a->strings['[Friendica System Notify]'] = '[Friendican järjestelmäilmoitus]';
$a->strings['User deleted their account'] = 'Käyttäjä poisti tilinsä';
$a->strings['On your Friendica node an user deleted their account. Please ensure that their data is removed from the backups.'] = 'Friendica-solmullasi ollut käyttäjä poisti tilinsä. Varmista, että hänen tietonsa poistetaan varmuuskopioista.';
$a->strings['The user id is %d'] = 'Käyttäjätunnus on %d';
$a->strings['Remove My Account'] = 'Poista tilini';
$a->strings['This will completely remove your account. Once this has been done it is not recoverable.'] = 'Tämä poistaa käyttäjätilisi pysyvästi. Poistoa ei voi perua myöhemmin.';
$a->strings['Please enter your password for verification:'] = 'Syötä salasanasi varmistusta varten:';
$a->strings['Do you want to ignore this server?'] = 'Haluatko sivuuttaa tämän palvelimen?';
$a->strings['Do you want to unignore this server?'] = 'Haluatko lakata sivuuttamasta tätä palvelinta?';
$a->strings['Remote server settings'] = 'Etäpalvelinasetukset';
$a->strings['Server URL'] = 'Palvelimen verkko-osoite';
$a->strings['Settings saved'] = 'Asetukset tallennettu';
$a->strings['Please enter your password to access this page.'] = 'Sivulle päästäksesi syötä salasanasi.';
$a->strings['App-specific password generation failed: The description is empty.'] = 'Sovelluskohtaisen salasanan luominen epäonnistui: kuvaus on tyhjä.';
$a->strings['App-specific passwords successfully revoked.'] = 'Sovelluskohtaisten salasanojen kumoaminen onnistui.';
$a->strings['App-specific password successfully revoked.'] = 'Sovelluskohtaisen salasanan kumoaminen onnistui.';
$a->strings['Make sure to copy your new app-specific password now. You won’t be able to see it again!'] = 'Kopioi uusi sovelluskohtainen salasanasi nyt. Et enää näe sitä!';
$a->strings['Last Used'] = 'Viimeksi käytetyt';
$a->strings['Revoke'] = 'Mitätöi';
$a->strings['Revoke All'] = 'Mitätöi kaikki';
$a->strings['When you generate a new app-specific password, you must use it right away, it will be shown to you once after you generate it.'] = 'Uutta sovelluskohtaista salasanaa pitää käyttää heti, kun se on luotu: se näytetään sinulle heti luomisen jälkeen.';
$a->strings['Generate new app-specific password'] = 'Luo uusi sovelluskohtainen salasana';
$a->strings['Friendiqa on my Fairphone 2...'] = 'Friendica minun Fairphone 2:ssani...';
$a->strings['Generate'] = 'Luo';
$a->strings['Two-factor authentication successfully disabled.'] = 'Kahden tekijän todennuksen poistaminen käytöstä onnistui.';
$a->strings['<p>Use an application on a mobile device to get two-factor authentication codes when prompted on login.</p>'] = '<p>Kirjautumisen niitä pyytäessä hanki kahden tekijän todennuksen koodit sovelluksella tai mobiililaitteella.</p>';
$a->strings['Authenticator app'] = 'Todennussovellus';
$a->strings['Configured'] = 'Asetettu';
$a->strings['Not Configured'] = 'Ei asetettu';
$a->strings['Recovery codes'] = 'Palautuskoodit';
$a->strings['App-specific passwords'] = 'Sovelluskohtaiset salasanat';
$a->strings['Current password:'] = 'Nykyinen salasana:';
$a->strings['Generate new recovery codes'] = 'Luo uudet palautuskoodit';
$a->strings['Next: Verification'] = 'Seuraava: Vahvistus';
$a->strings['Trusted browsers successfully removed.'] = 'Luotetut selaimet poistettu onnistuneesti.';
$a->strings['Trusted browser successfully removed.'] = 'Luotettu selain poistettu onnistuneesti.';
$a->strings['Two-factor Trusted Browsers'] = 'Kaksivaiheisen todennukset luotetut selaimet';
$a->strings['Trusted browsers are individual browsers you chose to skip two-factor authentication to access Friendica. Please use this feature sparingly, as it can negate the benefit of two-factor authentication.'] = 'Luotetut selaimet ovat valitsemiasi yksilöllisiä selaimia, joissa kaksivaiheista todennusta ei vaadita, kun kirjaudut sisään Friendicaan. Käytä tätä ominaisuutta säästeliäästi, sillä se saattaa vesittää kaksivaiheisesta todennuksesta saatavan hyödyn.';
$a->strings['Device'] = 'Laite';
$a->strings['OS'] = 'OS';
$a->strings['Browser'] = 'Selain';
$a->strings['Trusted'] = 'Luotettu';
$a->strings['Created At'] = 'Luotu';
$a->strings['Last Use'] = 'Viimeinen käyttö';
$a->strings['Remove All'] = 'Poista kaikki';
$a->strings['Two-factor authentication successfully activated.'] = 'Kaksivaiheinen todennus otettu onnistuneesti käyttöön.';
$a->strings['Two-factor code verification'] = 'Kaksivaiheinen kooditodennus';
$a->strings['Verify code and enable two-factor authentication'] = 'Vahvista koodi ja ota käyttöön kaksivaiheinen todennus';
$a->strings['Export account'] = 'Vie tili';
$a->strings['Export your account info and contacts. Use this to make a backup of your account and/or to move it to another server.'] = 'Vie tilin tiedot ja yhteystiedot. Käytä tätä tilisi varmuuskopiointiin ja/tai siirtämiseen toiselle palvelimelle.';
$a->strings['Export all'] = 'Vie kaikki';
$a->strings['Export Contacts to CSV'] = 'Vie kontaktit CSV-muodossa';
$a->strings['The top-level post isn\'t visible.'] = 'Ylätason julkaisu ei ole näkyvillä.';
$a->strings['The top-level post was deleted.'] = 'Ylätason julkaisu on poistettu.';
$a->strings['This node has blocked the top-level author or the author of the shared post.'] = 'Ylätason julkaisija tai jaetun julkaisun julkaisija on estetty tällä solmulla.';
$a->strings['You have ignored or blocked the top-level author or the author of the shared post.'] = 'Olet sivuuttanut tai estänyt ylätason julkaisijan tai jaetun julkaisun julkaisijan.';
$a->strings['You have ignored the top-level author\'s server or the shared post author\'s server.'] = 'Olet sivuuttanut ylätason julkaisijan palvelimen tai jaetun julkaisun tekijän palvelimen.';
$a->strings['Conversation Not Found'] = 'Keskustelua ei löytynyt';
$a->strings['Unfortunately, the requested conversation isn\'t available to you.'] = 'Valitettavasti pyytämäsi keskustelu ei ole sinun saatavillasi.';
$a->strings['Possible reasons include:'] = 'Mahdollisia syitä ovat muun muassa:';
$a->strings['Go back'] = 'Palaa takaisin';
$a->strings['Privacy Statement'] = 'Tietosuojalausunto';
$a->strings['Rules'] = 'Säännöt';
$a->strings['Parameter uri_id is missing.'] = 'Parameterin uri_id puuttuu.';
$a->strings['The requested item doesn\'t exist or has been deleted.'] = 'Pyytämäsi kohde ei ole olemassa tai se on poistettu.';
$a->strings['You are now logged in as %s'] = 'Olet nyt kirjautunut sisään tilillä %s';
$a->strings['Switch between your accounts'] = 'Vaihda tiliesi välillä';
$a->strings['Manage your accounts'] = 'Hallinnoi tilejäsi';
$a->strings['Select an identity to manage: '] = 'Valitse identiteetti hallitavaksi:';
$a->strings['Move account'] = 'Siirrä tili';
$a->strings['You can import an account from another Friendica server.'] = 'Voit tuoda käyttäjätilin toiselta Friendica-palvelimelta.';
$a->strings['This feature is experimental. We can\'t import contacts from the OStatus network (GNU Social/Statusnet) or from Diaspora'] = 'Tämä on kokeellinen ominaisuus. Emme voi tuoda kontakteja OStatus-verkolta (GNU social/Statusnet) tai Diasporalta.';
$a->strings['Account file'] = 'Tilitiedosto';
$a->strings['To export your account, go to "Settings->Export your personal data" and select "Export account"'] = 'Vie tilisi siirtymällä asetusten kohtaan ”Vie henkilökohtaiset tiedot” ja valitse ”Vie tili”';
$a->strings['Account file size is too high'] = 'Tilin tiedostokoko on liian suuri';
$a->strings['Error decoding account file'] = 'Tilitiedoston tulkinnassa tapahtui virhe';
$a->strings['Error! No version data in file! This is not a Friendica account file?'] = 'Virhe! Tiedostosta puuttuvat versiotiedot! Eikö tämä ole Friendica-tilitiedosto?';
$a->strings['User \'%s\' already exists on this server!'] = 'Käyttäjä ”%s” on jo olemassa tällä palvelimella!';
$a->strings['User creation error'] = 'Virhe käyttäjän luomisessa';
$a->strings['%d contact not imported'] = [
	0 => '%d kontakti ei tuotu',
	1 => '%d yhteystietoa ei tuotu',
];
$a->strings['User profile creation error'] = 'Virhe käyttäjäprofiilin luomisessa';
$a->strings['Done. You can now login with your username and password'] = 'Suoritettu. Voit nyt kirjautua sisään käyttäjätunnuksellasi.';
$a->strings['Welcome to Friendica'] = 'Tervetuloa Friendicaan';
$a->strings['New Member Checklist'] = 'Uuden jäsenen tarkistuslista';
$a->strings['We would like to offer some tips and links to help make your experience enjoyable. Click any item to visit the relevant page. A link to this page will be visible from your home page for two weeks after your initial registration and then will quietly disappear.'] = 'Haluamme tarjota joitakin vinkkejä ja linkkejä saadaksesi kokemuksesta miellyttävän. Siirry halutulle sivulle napsauttamalla mitä hyvänsä kohdetta. Tämän sivun linkki näkyy kotisivullasi kaksi viikkoa alkurekisteröitymisestäsi, minkä jälkeen se vaivihkaa häviää.';
$a->strings['Getting Started'] = 'Ensiaskeleet';
$a->strings['Friendica Walk-Through'] = 'Opastettu kierros Friendicaan';
$a->strings['Go to Your Settings'] = 'Omat Asetukset';
$a->strings['Upload Profile Photo'] = 'Lataa profiilikuva';
$a->strings['Upload a profile photo if you have not done so already. Studies have shown that people with real photos of themselves are ten times more likely to make friends than people who do not.'] = 'Lataa profiilikuva, jos et ole jo tehnyt niin. Tutkimukset osoittavat, että henkilöt, joilla on itsestään oikea valokuva profiilissa, saavat kavereita kymmenen kertaa todennäköisemmin kuin henkilöt, joilla ei ole omaa kuvaa profiilikuvana.';
$a->strings['Edit Your Profile'] = 'Muokkaa profiilisi';
$a->strings['Edit your <strong>default</strong> profile to your liking. Review the settings for hiding your list of friends and hiding the profile from unknown visitors.'] = 'Muokkaa <strong>oletus</strong>profiiliasi sinulle mieluisaksi. Tarkista asetuksesi kaverilistojesi piilottamiseksi ja piilottaaksesi profiilisi tuntemattomilta vierailijoilta.';
$a->strings['Profile Keywords'] = 'Profiilin avainsanat';
$a->strings['Set some public keywords for your profile which describe your interests. We may be able to find other people with similar interests and suggest friendships.'] = 'Aseta profiiliisi muutamia julkisia avainsanoja, jotka kuvaavat kiinnostuksenkohteitasi. Saatamme löytää muita samanlaisista asioista kiinnostuneita ja ehdottaa teille kaveruutta.';
$a->strings['Connecting'] = 'Yhdistetään';
$a->strings['Importing Emails'] = 'Sähköpostien tuominen';
$a->strings['Enter your email access information on your Connector Settings page if you wish to import and interact with friends or mailing lists from your email INBOX'] = 'Syötä sähköpostiosoitteesi pääsytiedot yhdistäjä-asetukset sivulla, jos haluat tuoda yhteystietoja tai postituslistoja sähköpostistasi ja olla niiden kanssa vuorovaikutuksessa.';
$a->strings['Go to Your Contacts Page'] = 'Siirry yhteystietosivulleni';
$a->strings['Go to Your Site\'s Directory'] = 'Näytä oman sivuston luettelo';
$a->strings['Finding New People'] = 'Kavereiden hankkiminen';
$a->strings['Add Your Contacts To Circle'] = 'Lisää yhteystietosi piiriin';
$a->strings['Once you have made some friends, organize them into private conversation circles from the sidebar of your Contacts page and then you can interact with each circle privately on your Network page.'] = 'Kun olet saanut ystäviä, järjestä heidät yksityisiin keskustelupiireihin kontaktit-sivun sivupalkista. Voit sitten olla vuorovaikutuksessa kunkin piirin kanssa yksityisesti verkostosivullasi.';
$a->strings['Why Aren\'t My Posts Public?'] = 'Miksi julkaisuni eivät ole julkisia?';
$a->strings['Friendica respects your privacy. By default, your posts will only show up to people you\'ve added as friends. For more information, see the help section from the link above.'] = 'Friendica kunnioittaa yksityisyyttäsi. Julkaisusi näkyvät oletuksena henkilöille, jotka olet lisännyt kavereiksesi. Katso lisätietoja yllä olevasta ohje-osion linkistä.';
$a->strings['Getting Help'] = 'Miten saada apua';
$a->strings['Go to the Help Section'] = 'Siirry ohjeosioon';
$a->strings['{0} wants to follow you'] = '{0} haluaa seurata sinua';
$a->strings['{0} has started following you'] = '{0} alkoi seurata sinua';
$a->strings['%s liked %s\'s post'] = '%s tykkäsi julkaisusta, jonka kirjoitti %s';
$a->strings['%s disliked %s\'s post'] = '%s ei tykännyt julkaisusta, jonka kirjoitti %s';
$a->strings['%s is attending %s\'s event'] = '%s osallistuu tapahtumaan, jonka järjestää %s';
$a->strings['%s is not attending %s\'s event'] = '%s ei osallistu tapahtumaan, jonka järjestää %s';
$a->strings['%s may attending %s\'s event'] = '%ssaattaa osallistua tapahtumaan, jonka järjestää %s';
$a->strings['%s is now friends with %s'] = '%s ja %s ovat nyt kavereita';
$a->strings['%s commented on %s\'s post'] = '%s kommentoi julkaisuun jonka kirjoitti %s';
$a->strings['%s created a new post'] = '%s teki uuden julkaisun';
$a->strings['Friend Suggestion'] = 'Kaveriehdotus';
$a->strings['Friend/Connect Request'] = 'Ystävä/yhteyspyyntö';
$a->strings['%1$s wants to follow you'] = '%1$s haluaa seurata sinua';
$a->strings['%1$s has started following you'] = '%1$s alkoi seurata sinua';
$a->strings['%1$s liked your comment on %2$s'] = '%1$s tykkäsi kommentistasi julkaisuun %2$s';
$a->strings['%1$s liked your post %2$s'] = '%1$s tykkäsi julkaisustasi %2$s';
$a->strings['%1$s disliked your comment on %2$s'] = '%1$s ei tykännyt kommentistasi %2$s';
$a->strings['%1$s disliked your post %2$s'] = '%1$s ei tykännyt julkaisustasi %2$s';
$a->strings['%1$s shared your comment %2$s'] = '%1$s jakoi kommenttisi %2$s';
$a->strings['%1$s shared your post %2$s'] = '%1$s jakoi julkaisusi %2$s';
$a->strings['%1$s shared the post %2$s from %3$s'] = '%1$s jakoi julkaisun %2$s sijainnista %3$s';
$a->strings['%1$s shared a post from %3$s'] = '%1$s jakoi julkaisun käyttäjältä %3$s';
$a->strings['%1$s shared the post %2$s'] = '%1$s jakoi julkaisun %2$s';
$a->strings['%1$s shared a post'] = '%1$s jakoi julkaisun';
$a->strings['%1$s wants to attend your event %2$s'] = '%1$s haluaa osallistua tapahtumaasi %2$s';
$a->strings['%1$s does not want to attend your event %2$s'] = '%1$s ei halua osallistua tapahtumaasi %2$s';
$a->strings['%1$s maybe wants to attend your event %2$s'] = '%1$s haluaa ehkä osallistua tapahtumaasi %2$s';
$a->strings['%1$s tagged you on %2$s'] = '%1$s merkitsi sinut julkaisuun %2$s';
$a->strings['%1$s replied to you on %2$s'] = '%1$s vastasi sinulle julkaisussa %2$s';
$a->strings['%1$s commented in your thread %2$s'] = '%1$s kommentoi ketjuusi %2$s';
$a->strings['%1$s commented on your thread %2$s'] = '%1$s kommentoi säikeeseesi %2$s';
$a->strings['[Friendica:Notify]'] = '[Friendica: Ilmoitus]';
$a->strings['%1$s sent you a new private message at %2$s.'] = '%1$s lähetti sinulle uuden yksityisviestin kohteessa %2$s.';
$a->strings['a private message'] = 'yksityisviesti';
$a->strings['%1$s sent you %2$s.'] = '%1$s lähetti sinulle %2$s.';
$a->strings['Please visit %s to view and/or reply to your private messages.'] = 'Katso yksityisviestisi kohteessa %s.';
$a->strings['%s commented on an item/conversation you have been following.'] = '%s kommentoi kohteessa/keskustelussa jota seuraat.';
$a->strings['Please visit %s to view and/or reply to the conversation.'] = 'Käy %s nähdäksesi keskustelun ja/tai vastataksesi siihen';
$a->strings['%s %s posted to your profile wall'] = '%s %s julkaisi profiiliseinällesi';
$a->strings['%1$s posted to your profile wall at %2$s'] = '%1$s kirjoitti seinällesi kohteessa %2$s';
$a->strings['%1$s posted to [url=%2$s]your wall[/url]'] = '%1$s kirjoitti [url=%2$s]seinällesi[/url]';
$a->strings['%s Introduction received'] = '%s esittely saatu';
$a->strings['You\'ve received an introduction from \'%1$s\' at %2$s'] = 'Olet vastaanottanut kaverikutsun henkilöltä \'%1$s\' kohteessa %2$s';
$a->strings['You\'ve received [url=%1$s]an introduction[/url] from %2$s.'] = 'Olet vastaanottanut [url=%1$s]kaverikutsun[/url] henkilöltä %2$s.';
$a->strings['You may visit their profile at %s'] = 'Voit vierailla hänen profiilissaan kohteessa %s';
$a->strings['Please visit %s to approve or reject the introduction.'] = 'Hyväksy tai hylkää esittely %s-sivustossa';
$a->strings['%s You have a new friend'] = '%s Sinulla on yksi uusi kaveri';
$a->strings['%1$s is your friend at %2$s'] = '%1$s on kaverisi paikassa %2$s';
$a->strings['%s You have a new follower'] = '%s Sinulla on uusi seuraaja';
$a->strings['You have a new follower at %2$s : %1$s'] = 'Sinulla on uusi seuraaja sivustolla %2$s : %1$s';
$a->strings['%s Friend suggestion received'] = '%s kaveriehdotus vastaanotettu';
$a->strings['You\'ve received a friend suggestion from \'%1$s\' at %2$s'] = 'Sait kaveriehdotuksen henkilöltä \'%1$s\' (%2$s)';
$a->strings['You\'ve received [url=%1$s]a friend suggestion[/url] for %2$s from %3$s.'] = 'Sait [url=%1$s]kaveriehdotuksen[/url] %2$s käyttäjältä %3$s.';
$a->strings['Name:'] = 'Nimi:';
$a->strings['Photo:'] = 'Kuva:';
$a->strings['Please visit %s to approve or reject the suggestion.'] = 'Hyväksy tai hylkää ehdotus %s-sivustossa';
$a->strings['%s Connection accepted'] = '%s yhteys hyväksytty';
$a->strings['\'%1$s\' has accepted your connection request at %2$s'] = '%1$s on hyväksynyt kaverikutsusi kohteessa %2$s';
$a->strings['%2$s has accepted your [url=%1$s]connection request[/url].'] = '%2$s hyväksyi [url=%1$s]kaverikutsusi[/url].';
$a->strings['You are now friends and may exchange status updates, photos, and messages without restriction.'] = 'Olette nyt kavereita ja voitte vaihtaa tilapäivityksiä, kuvia ja viestejä ilman rajoituksia.';
$a->strings['Please visit %s if you wish to make any changes to this relationship.'] = 'Käy osoitteessa %s muokkaamaan tätä kaverisuhdetta.';
$a->strings['\'%1$s\' has chosen to accept you a fan, which restricts some forms of communication - such as private messaging and some profile interactions. If this is a celebrity or community page, these settings were applied automatically.'] = '\'%1$s\' on hyväksynyt sinut faniksi. Tämä rajoittaa joitain kommunikointitapoja - kuten yksityisviestiettely ja joitain profiilitoimintoja. Jos tämä on julkisuuden henkilö tai yhteisösivu, asetukset on valittu automaattisesti.';
$a->strings['\'%1$s\' may choose to extend this into a two-way or more permissive relationship in the future.'] = '\'%1$s\' voi halutessaan laajentaa tulevaisuudessa suhteenne kahdenväliseksi tai sallivammaksi.';
$a->strings['Please visit %s  if you wish to make any changes to this relationship.'] = 'Käy osoitteessa %s muokkaamaan tätä kaverisuhdetta.';
$a->strings['registration request'] = 'rekisteröintipyyntö';
$a->strings['You\'ve received a registration request from \'%1$s\' at %2$s'] = 'Olet vastaanottanut rekisteröintipyynnön henkilöltä \'%1$s\' kohteessa %2$s';
$a->strings['You\'ve received a [url=%1$s]registration request[/url] from %2$s.'] = 'Olet vastaanottanut [url=%1$s]rekisteröintipyynnön[/url] henkilöltä %2$s.';
$a->strings['Display Name:	%s
Site Location:	%s
Login Name:	%s (%s)'] = 'Näyttönimi:	%s
Sivusto:	%s
Käyttäjätunnus:	%s (%s)';
$a->strings['Please visit %s to approve or reject the request.'] = 'Hyväksy tai hylkää pyyntö %s-sivustossa.';
$a->strings['new registration'] = 'uusi rekisteröityminen';
$a->strings['You\'ve received a new registration from \'%1$s\' at %2$s'] = 'Olet saanut uuden rekisteröitymisen nimimerkiltä %1$s kohteessa %2$s';
$a->strings['You\'ve received a [url=%1$s]new registration[/url] from %2$s.'] = 'Olet saanut [url=%1$s]uuden rekisteröitymisen[/url] nimimerkiltä %2$s.';
$a->strings['Please visit %s to have a look at the new registration.'] = 'Käy osoitteessa %s nähdäksesi uuden rekisteröitymisen.';
$a->strings['%s %s tagged you'] = '%s %s mainitsi sinut';
$a->strings['%s %s shared a new post'] = '%s %s jakoi uuden julkaisun';
$a->strings['This message was sent to you by %s, a member of the Friendica social network.'] = 'Viestin lähetti %s Friendica-sosiaaliverkoston kautta.';
$a->strings['You may visit them online at %s'] = 'Voit vierailla heidän luonaan netissä paikassa %s';
$a->strings['Please contact the sender by replying to this post if you do not wish to receive these messages.'] = 'Ota yhteyttä lähettäjään vastaamalla tähän julkaisuun, jos et halua enää vastaanottaa näitä viestejä.';
$a->strings['%s posted an update.'] = '%s julkaisi päivityksen.';
$a->strings['Public Message'] = 'Julkinen viesti';
$a->strings['Unlisted Message'] = 'Listaamaton viesti';
$a->strings['This entry was edited'] = 'Tätä kohdetta on muokattu';
$a->strings['Connector Message'] = 'Yhdistäjäviesti';
$a->strings['Delete globally'] = 'Poista maailmanlaajuisesti';
$a->strings['Remove locally'] = 'Poista paikallisesti';
$a->strings['Block %s'] = 'Estä %s';
$a->strings['Ignore %s'] = 'Sivuuta %s';
$a->strings['Collapse %s'] = 'Laskosta piiloon %s';
$a->strings['Report post'] = 'Raportoi julkaisu';
$a->strings['Save to folder'] = 'Tallenna kansioon';
$a->strings['I will attend'] = 'Osallistun';
$a->strings['I will not attend'] = 'En aio osallistua';
$a->strings['I might attend'] = 'Saatan osallistua';
$a->strings['Ignore thread'] = 'Sivuuta ketju';
$a->strings['Unignore thread'] = 'Lopeta ketjun sivuutus';
$a->strings['Toggle ignore status'] = 'Sivuutus-tila päälle/pois';
$a->strings['Add star'] = 'Lisää tähti';
$a->strings['Remove star'] = 'Poista tähti';
$a->strings['Toggle star status'] = 'Tähti-tila päälle/pois';
$a->strings['Pin'] = 'Kiinnitä';
$a->strings['Unpin'] = 'Poista kiinnitys';
$a->strings['Toggle pin status'] = 'Kiinnitys-tila päälle/pois';
$a->strings['Pinned'] = 'Kiinnitetty';
$a->strings['Add tag'] = 'Lisää tunniste';
$a->strings['Quote share this'] = 'Lainaa ja jaa tämä';
$a->strings['Quote Share'] = 'Lainaa ja jaa';
$a->strings['Reshare this'] = 'Jaa tämä uudelleen';
$a->strings['Reshare'] = 'Jaa uudelleen';
$a->strings['Cancel your Reshare'] = 'Peruuta uudelleenjako';
$a->strings['Unshare'] = 'Peruuta jako';
$a->strings['%s (Received %s)'] = '%s (Vastaanotettu %s)';
$a->strings['Comment this item on your system'] = 'Kommentoi tätä kohdetta järjestelmässäsi';
$a->strings['Remote comment'] = 'Etäkommentti';
$a->strings['Share via ...'] = 'Jaa palvelun ... kautta';
$a->strings['Share via external services'] = 'Jaa ulkoisten palvelujen kautta';
$a->strings['Unknown parent'] = 'Tuntematon vanhempi';
$a->strings['in reply to %s'] = 'vastauksena käyttäjälle %s';
$a->strings['Parent is probably private or not federated.'] = 'Vanhempi on todennäköisesti yksityinen tai sitä ei ole federoitu.';
$a->strings['to'] = 'henkilölle';
$a->strings['Wall-to-Wall'] = 'Seinäjulkaisu';
$a->strings['via Wall-To-Wall:'] = 'seinäjulkaisun kautta';
$a->strings['Reply to %s'] = 'Vastaus käyttäjälle %s';
$a->strings['Notifier task is pending'] = 'Muistutustehtävä vireillä';
$a->strings['Delivery to remote servers is pending'] = 'Toimitus etäservereille vireillä';
$a->strings['Delivery to remote servers is underway'] = 'Toimitus etäservereillle käynnissä';
$a->strings['Delivery to remote servers is mostly done'] = 'Toimitus etäservereille pääosin valmis';
$a->strings['Delivery to remote servers is done'] = 'Toimitus etäservereille valmis';
$a->strings['%d comment'] = [
	0 => '%d kommentti',
	1 => '%d kommenttia',
];
$a->strings['Show more'] = 'Näytä lisää';
$a->strings['Show fewer'] = 'Näytä vähemmän';
$a->strings['Reshared by: %s'] = 'Käyttäjän %s uudelleen jakama';
$a->strings['Viewed by: %s'] = '%s näki';
$a->strings['Read by: %s'] = '%s luki';
$a->strings['Liked by: %s'] = '%s tykkäsi';
$a->strings['Disliked by: %s'] = '%s ei tykännyt';
$a->strings['Attended by: %s'] = 'Osallistujat: %s';
$a->strings['Maybe attended by: %s'] = 'Mahdollisesti osallistuvat: %s';
$a->strings['Not attended by: %s'] = 'Eivät osallistu: %s';
$a->strings['Commented by: %s'] = '%s kommentoi';
$a->strings['Reacted with %s by: %s'] = 'Reaktio %s käyttäjiltä %s';
$a->strings['Quote shared by: %s'] = 'Käyttäjän %s lainaama ja jakama';
$a->strings['Chat'] = 'Chat';
$a->strings['(no subject)'] = '(ei aihetta)';
$a->strings['The folder %s must be writable by webserver.'] = 'Kansion %s täytyy olla verkkopalvelimen kirjoitettavissa.';
$a->strings['Login failed.'] = 'Kirjautuminen epäonnistui';
$a->strings['Login failed. Please check your credentials.'] = 'Sisään kirjautuminen epäonnistui. Tarkista sisäänkirjautumistietosi.';
$a->strings['Login failed because your account is blocked.'] = 'Sisäänkirjautuminen epäonnistui, koska tilisi on estetty.';
$a->strings['Welcome %s'] = 'Tervetuloa %s';
$a->strings['Please upload a profile photo.'] = 'Lataa profiilikuva.';
$a->strings['OpenWebAuth: %1$s welcomes %2$s'] = 'OpenWebAuth: %1$s toivottaa tervetulleeksi %2$s';
$a->strings['Friendica Notification'] = 'Friendica-ilmoitus';
$a->strings['%1$s, %2$s Administrator'] = '%1$s, %2$s ylläpitäjä';
$a->strings['%s Administrator'] = '%s ylläpitäjä';
$a->strings['thanks'] = 'kiitos';
$a->strings['YYYY-MM-DD or MM-DD'] = 'VVVV-KK-PP tai KK-PP';
$a->strings['Time zone: <strong>%s</strong> <a href="%s">Change in Settings</a>'] = 'Aikavyöhyke: <strong>%s</strong> <a href="%s">Vaihda asetuksissa</a>';
$a->strings['never'] = 'ei koskaan';
$a->strings['less than a second ago'] = 'alle sekunti sitten';
$a->strings['year'] = 'vuosi';
$a->strings['years'] = 'vuotta';
$a->strings['months'] = 'kuukautta';
$a->strings['weeks'] = 'viikkoa';
$a->strings['days'] = 'päivää';
$a->strings['hour'] = 'tunti';
$a->strings['hours'] = 'tuntia';
$a->strings['minute'] = 'minuutti';
$a->strings['minutes'] = 'minuuttia';
$a->strings['second'] = 'sekuntti';
$a->strings['seconds'] = 'sekunttia';
$a->strings['%1$d %2$s ago'] = '%1$d %2$s sitten';
$a->strings['Notification from Friendica'] = 'Ilmoitus Friendicasta';
$a->strings['Empty Post'] = 'Tyhjä julkaisu';
$a->strings['Variations'] = 'Variaatiot';
$a->strings['Note'] = 'Huomautus';
$a->strings['Check image permissions if all users are allowed to see the image'] = 'Tarkista kuvan käyttöoikeudet, jos kaikkien käyttäjien on tarkoitus se nähdä';
$a->strings['Save settings'] = 'Tallenna asetukset';
$a->strings['Appearance'] = 'Ulkoasu';
$a->strings['Accent color'] = 'Korostusväri';
$a->strings['Copy or paste schemestring'] = 'Kopioi tai liitä skeemamerkkijono';
$a->strings['You can copy this string to share your theme with others. Pasting here applies the schemestring'] = 'Voit kopioida tämän merkkijonon jakaaksesi teemasi muiden kanssa. Tähän liittäminen ottaa käyttöön liitetyn skeemamerkkijonon.';
$a->strings['Navigation bar background color'] = 'Navigointipalkin taustaväri';
$a->strings['Navigation bar icon color '] = 'Navigointipalkin kuvakkeen väri';
$a->strings['Link color'] = 'Linkkiväri';
$a->strings['Set the background color'] = 'Aseta taustaväri';
$a->strings['Content background opacity'] = 'Sisällön taustan peittävyys';
$a->strings['Set the background image'] = 'Aseta taustakuva';
$a->strings['Background image style'] = 'Taustakuvan tyyli';
$a->strings['Always open Compose page'] = 'Avaa aina kirjoitussivu';
$a->strings['The New Post button always open the <a href="/compose">Compose page</a> instead of the modal form. When this is disabled, the Compose page can be accessed with a middle click on the link or from the modal.'] = 'Uusi julkaisu -painike avaa aina <a href="/compose">kirjoitussivun</a> modaalinäkymän sijaan. Kun tämä on pois käytöstä, pääset kirjoitussivulle linkin keskiklikkauksella tai modaalinäkymästä.';
$a->strings['Login page background image'] = 'Kirjautumissivun taustakuva';
$a->strings['Login page background color'] = 'Kirjautumissivun taustaväri';
$a->strings['Leave background image and color empty for theme defaults'] = 'Jätä taustakuva ja -väri tyhjiksi teeman oletuksia varten';
$a->strings['Top Banner'] = 'Yläbanneri';
$a->strings['Resize image to the width of the screen and show background color below on long pages.'] = 'Muuta kuvan kokoa näyttöleveyteen ja näytä pitkillä sivuilla alla taustaväri.';
$a->strings['Full screen'] = 'Koko näyttö';
$a->strings['Resize image to fill entire screen, clipping either the right or the bottom.'] = 'Muuta kuvan kokoa täyttämään ruutu; rajaus joko oikealta tai alhaalta.';
$a->strings['Single row mosaic'] = 'Yksirivinen mosaikki';
$a->strings['Resize image to repeat it on a single row, either vertical or horizontal.'] = 'Toista kuvaa vaaka- tai pystysuunnassa muuttamalla sen kokoa.';
$a->strings['Mosaic'] = 'Mosaiikki';
$a->strings['Repeat image to fill the screen.'] = 'Täytä ruutu toistamalla kuvaa.';
$a->strings['Back to top'] = 'Takaisin ylös';
$a->strings['Light'] = 'Vaalea';
$a->strings['Dark'] = 'Tumma';
$a->strings['Custom'] = 'Mukautettu';
$a->strings['Guest'] = 'Vieras';
$a->strings['Visitor'] = 'Vierailija';
$a->strings['Alignment'] = 'Tasaus';
$a->strings['Left'] = 'Vasemmalle';
$a->strings['Center'] = 'Keskitä';
$a->strings['Color scheme'] = 'Väriteema';
$a->strings['Posts font size'] = 'Julkaisun fonttikoko';
$a->strings['Textareas font size'] = 'Tekstikentän fonttikoko';
$a->strings['Comma separated list of helper groups'] = 'Pilkulla eroteltu luettelo apuryhmistä';
$a->strings['don\'t show'] = 'älä näytä';
$a->strings['show'] = 'näytä';
$a->strings['Set style'] = 'Aseta tyyli';
$a->strings['Community Pages'] = 'Yhteisösivut';
$a->strings['Community Profiles'] = 'Yhteisöprofiilit';
$a->strings['Help or @NewHere ?'] = 'Ohje tai @UusiTäällä ?';
$a->strings['Connect Services'] = 'Yhteyspalvelut';
$a->strings['Find Friends'] = 'Löydä kavereita';
$a->strings['Last users'] = 'Viimeisimmät käyttäjät';
$a->strings['Quick Start'] = 'Pika-aloitus';
