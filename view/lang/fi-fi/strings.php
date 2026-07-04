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
$a->strings['Empty post discarded.'] = 'Tyhjä julkaisu hylättiin.';
$a->strings['Item not found.'] = 'Kohdetta ei löytynyt.';
$a->strings['Permission denied.'] = 'Käyttöoikeus evätty.';
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
$a->strings['Start typing the name of a contact and select from the list'] = 'Ala kirjoittaa kontaktin nimeä ja valitse luettelosta';
$a->strings['Subject'] = 'Aihe';
$a->strings['Your message'] = 'Viestisi';
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
$a->strings['Subject:'] = 'Aihe:';
$a->strings['Your message:'] = 'Viestisi:';
$a->strings['Unknown sender - %s'] = 'Tuntematon lähettäjä – %s';
$a->strings['You and %s'] = 'Sinä ja %s';
$a->strings['%s and You'] = '%s ja sinä';
$a->strings['%d message'] = [
	0 => '%d viesti',
	1 => '%d viestiä',
];
$a->strings['User not found.'] = 'Käyttäjää ei löydy.';
$a->strings['Photo Albums'] = 'Valokuva-albumit';
$a->strings['Recent Photos'] = 'Viimeaikaisia kuvia';
$a->strings['everybody'] = 'kaikki';
$a->strings['Contact information unavailable'] = 'Yhteystietoja ei saatavilla';
$a->strings['Album not found.'] = 'Albumia ei löydy.';
$a->strings['Album successfully deleted'] = 'Albumin poisto onnistui';
$a->strings['Album was empty.'] = 'Albumi oli tyhjä.';
$a->strings['Failed to delete the photo.'] = 'Kuvan poistaminen epäonnistui.';
$a->strings['Public access denied.'] = 'Julkinen käyttö estetty.';
$a->strings['No photos selected'] = 'Ei valittuja kuvia';
$a->strings['Upload photo to this album'] = 'Lataa kuva tähän albumiin';
$a->strings['Upload selected photo'] = 'Lataa valittu kuva palvelimelle';
$a->strings['The maximum accepted image size is %s'] = 'Suurin sallittu kuvan koko on %s';
$a->strings['Upload Photos to %s'] = 'Lataa kuvat albumiin %s';
$a->strings['Upload Photos'] = 'Lataa kuvat';
$a->strings['Album name: '] = 'Albumin nimi:';
$a->strings['If you want to add this photo to an album, begin typing its name, and existing albums will be suggested, which you can select. If you choose something new, it will be created.'] = 'Jos haluat lisätä tämän kuvan albumiin, ala kirjoittaa sen nimeä, jolloin ehdotukset olemassa olevista ablumeista tulevat esiin ja voit valita niistä haluamasi. Jos kirjoitat uuden albumin nimen, se luodaan.';
$a->strings['Permissions'] = 'Käyttöoikeudet';
$a->strings['Do you really want to delete this photo album and all its photos?'] = 'Haluatko varmasti poistaa tämän albumin ja kaikki sen kuvat?';
$a->strings['Delete Album'] = 'Poista albumi';
$a->strings['Cancel'] = 'Peru';
$a->strings['New album name: '] = 'Uuden albumin nimi: ';
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
$a->strings['New album name'] = 'Albumin uusi nimi';
$a->strings['Caption'] = 'Kuvateksti';
$a->strings['Do not rotate'] = 'Älä kierrä';
$a->strings['Rotate CW (right)'] = 'Kierrä myötäpäivään (oikealle)';
$a->strings['Rotate CCW (left)'] = 'Kierrä vastapäivään (vasemmalle)';
$a->strings['Edit'] = 'Muokkaa';
$a->strings['Delete'] = 'Poista';
$a->strings['Back to viewing'] = 'Takaisin katseluun';
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
$a->strings['Backend error'] = 'Taustapalvelimen virhe';
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
$a->strings['invalid image for id %s'] = 'epäkelpo kuva ID:lle %s';
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
$a->strings['AT Protocol'] = 'AT-protokolla';
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
$a->strings['Post'] = 'Julkaisu';
$a->strings['Loading...'] = 'Ladataan…';
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
$a->strings['Set your location'] = 'Aseta sijaintisi';
$a->strings['set location'] = 'aseta sijainti';
$a->strings['Clear browser location'] = 'Tyhjennä selaimen sijainti';
$a->strings['clear location'] = 'tyhjennä sijainti';
$a->strings['Set title'] = 'Aseta otsikko';
$a->strings['Set summary, abstract or spoiler text'] = 'Aseta yhteenveto, tiivistelmä tai spoileriteksti';
$a->strings['Categories (comma-separated list)'] = 'Luokat (pilkuilla eroteltu luettelo)';
$a->strings['Sensitive post'] = 'Arkaluontoista materiaalia sisältävä julkaisu';
$a->strings['Scheduled at'] = 'Ajastus';
$a->strings['Permission settings'] = 'Käyttöoikeusasetukset';
$a->strings['Public post'] = 'Julkinen julkaisu';
$a->strings['Preview'] = 'Esikatselu';
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
$a->strings['Stored because of your activity (like, comment, bookmark, ...)'] = 'Tallennettu toiminnoistasi johtuen (tykkäys, kommentti, kirjanmerkki...)';
$a->strings['Distributed'] = 'Jaeltu';
$a->strings['Pushed to us'] = 'Työnnetty meille';
$a->strings['Channel "%s": %s'] = 'Kanava "%s": %s';
$a->strings['Channel "%s"'] = 'Kanava "%s"';
$a->strings['Select'] = 'Valitse';
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
$a->strings['Bookmarked'] = 'Lisätty kirjanmerkkeihin';
$a->strings['Favourite Posts'] = 'Suosikkijulkaisut';
$a->strings['General Features'] = 'Yleiset ominaisuudet';
$a->strings['Display the community in the navigation'] = 'Näytä yhteisö navigaatiossa';
$a->strings['If enabled, the community can be accessed via the navigation menu. Independent from this setting, the community timelines can always be accessed via the channels.'] = 'Jos käytössä, yhteisöön on pääsy navigointivalikon kautta. Yhteisöjen aikajanoille on aina pääsy kanavien kautta tästä asetuksesta riippumatta.';
$a->strings['Post Composition Features'] = 'Kirjoittamisen ominaisuudet';
$a->strings['Explicit Mentions'] = 'Suorat maininnat';
$a->strings['Add explicit mentions to comment box for manual control over who gets mentioned in replies.'] = 'Lisää suorat maininnat kommenttikenttään hallitaksesi manuaalisesti kenet mainitaan vastauksissa.';
$a->strings['Add an abstract from ActivityPub content warnings'] = 'Lisää tiivistelmä ActivityPub:in sisältövaroituksista';
$a->strings['Add an abstract when commenting on ActivityPub posts with a content warning. Abstracts are displayed as content warning on systems like Mastodon or Pleroma.'] = 'Lisää tiivistelmä, kun kommentoit ActivityPub-julkaisuja, joissa on sisältövaroitus. Tiivistelmät näytetään sisältövaroituksina esimerkiksi Mastodonissa ja Pleromasssa.';
$a->strings['Post/Comment Tools'] = 'Julkaisu/kommentti työkalut';
$a->strings['Post Categories'] = 'Julkaisuluokat';
$a->strings['Add categories to your posts'] = 'Lisää julkaisuihisi luokkia';
$a->strings['Summary'] = 'Yhteenveto';
$a->strings['Add a summary, abstract or spoiler text to your posts'] = 'Lisää yhteenveto, tiivistelmä tai spoileriteksti julkaisuihisi';
$a->strings['Network Widgets'] = 'Verkkowidgetit';
$a->strings['Circles'] = 'Piirit';
$a->strings['Display posts that have been created by accounts of the selected circle.'] = 'Näyttää julkaisut, jotka ovat julkaisseet valitun piirin käyttäjät.';
$a->strings['Groups'] = 'Ryhmät';
$a->strings['Display posts that have been distributed by the selected group.'] = 'Näyttää julkaisut, jotka ovat valitun ryhmän jakelemia.';
$a->strings['Archives'] = 'Arkisto';
$a->strings['Display an archive where posts can be selected by month and year.'] = 'Näyttää arkiston, jossa julkaisuja voidaan valita kuukauden ja vuoden perusteella.';
$a->strings['Protocols'] = 'Yhteyskäytännöt';
$a->strings['Display posts with the selected protocols.'] = 'Näyttää valittujen yhteyskäytäntöjen julkaisut.';
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
$a->strings['Turn on notifications for this post'] = 'Laita tämän julkaisun ilmoitukset pois päältä';
$a->strings['Fetch more replies'] = 'Nouda lisää vastauksia';
$a->strings['View Status'] = 'Näytä tila';
$a->strings['View Profile'] = 'Näytä profiili';
$a->strings['View Photos'] = 'Näytä kuvia';
$a->strings['Network Posts'] = 'Verkoston julkaisut';
$a->strings['View Contact'] = 'Näytä kontakti';
$a->strings['Collapse'] = 'Taittele piiloon';
$a->strings['Ignore user'] = 'Sivuuta käyttäjä';
$a->strings['Block user'] = 'Estä käyttäjä';
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
$a->strings['Profile'] = 'Profiili';
$a->strings['Your profile page'] = 'Profiilisivusi';
$a->strings['Conversations'] = 'Keskustelut';
$a->strings['Conversations you started'] = 'Aloittamasi keskustelut';
$a->strings['Photos'] = 'Kuvat';
$a->strings['Your photos'] = 'Kuvasi';
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
$a->strings['Tags'] = 'Aihetunnisteet';
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
$a->strings['Invalid source protocol'] = 'Epäkelpo lähdeyhteyskäytäntö';
$a->strings['Invalid link protocol'] = 'Epäkelpo linkkiyhteyskäytäntö';
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
$a->strings['Error: Program execution functions (proc_open) required but not enabled.'] = 'Virhe: Ohjelman suoritusfunktiot (proc_open) vaaditaan, mutta niitä ei ole otettu käyttöön.';
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
$a->strings['In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top level folder.'] = 'Verkkopalvelimella on oltava kirjoitusoikeus hakemistoon "view/smarty3" Friendican päähakemistossa käännettyjen mallineiden tallentamiseksi.';
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
$a->strings['The logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'Lokitiedosto \'%s\' ei ole kelvollinen. Lokikirjaus ei ole mahdollinen (virhe: \'%s\')';
$a->strings['The debug logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'Debug-lokitiedosto \'%s\' on käyttökelvoton. Lokitiedostojen tallennus ei ole mahdollista (virhe: \'%s\')';
$a->strings['Friendica can\'t display this page at the moment, please contact the administrator.'] = 'Friendica ei voi näyttää tätä sivua juuri nyt. Ota yhteys ylläpitoon.';
$a->strings['template engine cannot be registered without a name.'] = 'mallinekonetta ei voi rekisteröidä ilman nimeä.';
$a->strings['template engine is not registered!'] = 'mallinekonetta ei ole rekisteröity!';
$a->strings['Storage base path'] = 'Tallennuksen kantapolku';
$a->strings['Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree'] = 'Tiedosto, johon palvelimelle ladatut tiedostot tallennetaan. Turvallisuuden takaamiseksi tämän pitää olla polku, joka ei ole osa verkkopalvelimen kansiorakennetta.';
$a->strings['Enter a valid existing folder'] = 'Syötä pätevä olemassa oleva kansio';
$a->strings['Updates from version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.'] = 'Päivityksiä versiosta %s ei tueta. Päivitä vähintään versioon 2021.01 ja odota, kunnes päivitys on valmis versioon 1383.';
$a->strings['Updates from postupdate version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.'] = 'Päivityksiä postupdate-versiosta %s ei tueta. Päivitä vähintään versioon 2021.01 ja odota, kunnes postupdate-päivitys versioon 1383 on valmis.';
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
$a->strings['The post update is at version %d, it has to be at %d to safely drop the tables.'] = 'Postupdate on versiossa %d, sen on oltava versiossa %d, jotta taulukot voidaan poistaa turvallisesti.';
$a->strings['No unused tables found.'] = 'Käyttämättömiä taulukkoja ei löytynyt';
$a->strings['These tables are not used for friendica and will be deleted when you execute "dbstructure drop -e":'] = 'Näitä taulukoita ei käytetä Friendicassa, ja ne poistetaan, kun suoritat komennon ”dbstructure drop -e”:';
$a->strings['There are no tables on MyISAM or InnoDB with the Antelope file format.'] = 'MyISAM- tai InnoDB-tietokannoissa ei ole Antelope-tiedostomuotoisia taulukoita.';
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
$a->strings['Legacy module file not found: %s'] = 'Vanhaa moduulitiedostoa ei löydy: %s';
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
$a->strings['No compatible communication protocols or feeds were discovered.'] = 'Yhteensopivia viestintäyhteyskäytäntöjä tai syötteitä ei löytynyt.';
$a->strings['An author or name was not found.'] = 'Julkaisija tai nimi puuttuu.';
$a->strings['No browser URL could be matched to this address.'] = 'Mitään selaimen URL-osoitetta ei voitu yhdistää tähän osoitteeseen.';
$a->strings['Unable to match @-style Identity Address with a known protocol or email contact.'] = '@-tyylin henkilöysosoitetta ei voi yhdistää tunnettuun yhteyskäytäntöön tai sähköpostiosoitteeseen.';
$a->strings['Use mailto: in front of address to force email check.'] = 'Käytä "mailto:" osoitteen edessä pakottaaksesi sähköpostin tarkastuksen.';
$a->strings['Limited profile. This person will be unable to receive direct/personal notifications from you.'] = 'Rajoitettu profiili. Tämä henkilö ei saa suoria/henkilökohtaisia ilmoituksia sinulta.';
$a->strings['Unable to retrieve contact information.'] = 'Kontaktin tietoja ei voitu hakea.';
$a->strings['Starts:'] = 'Alkaa:';
$a->strings['Ends:'] = 'Päättyy:';
$a->strings['all-day'] = 'koko päivä';
$a->strings['Sun'] = 'su';
$a->strings['Mon'] = 'ma';
$a->strings['Tue'] = 'ti';
$a->strings['Wed'] = 'ke';
$a->strings['Thu'] = 'to';
$a->strings['Fri'] = 'pe';
$a->strings['Sat'] = 'la';
$a->strings['Sunday'] = 'sunnuntai';
$a->strings['Monday'] = 'maanantai';
$a->strings['Tuesday'] = 'tiistai';
$a->strings['Wednesday'] = 'keskiviikko';
$a->strings['Thursday'] = 'torstai';
$a->strings['Friday'] = 'perjantai';
$a->strings['Saturday'] = 'lauantai';
$a->strings['Jan'] = 'tammi';
$a->strings['Feb'] = 'helmi';
$a->strings['Mar'] = 'maalis';
$a->strings['Apr'] = 'huhti';
$a->strings['May'] = 'toukokuu';
$a->strings['Jun'] = 'kesä';
$a->strings['Jul'] = 'heinä';
$a->strings['Aug'] = 'elo';
$a->strings['Sept'] = 'Syy.';
$a->strings['Oct'] = 'loka';
$a->strings['Nov'] = 'marras';
$a->strings['Dec'] = 'joulu';
$a->strings['January'] = 'tammikuu';
$a->strings['February'] = 'helmikuu';
$a->strings['March'] = 'maaliskuu';
$a->strings['April'] = 'huhtikuu';
$a->strings['June'] = 'kesäkuu';
$a->strings['July'] = 'heinäkuu';
$a->strings['August'] = 'elokuu';
$a->strings['September'] = 'syyskuu';
$a->strings['October'] = 'lokakuu';
$a->strings['November'] = 'marraskuu';
$a->strings['December'] = 'joulukuu';
$a->strings['today'] = 'tänään';
$a->strings['month'] = 'kuukausi';
$a->strings['week'] = 'viikko';
$a->strings['day'] = 'päivä';
$a->strings['No events to display'] = 'Ei näytettäviä tapahtumia.';
$a->strings['Access to this profile has been restricted.'] = 'Pääsy tähän profiiliin on rajoitettu';
$a->strings['Event not found.'] = 'Tapahtumaa ei löytynyt.';
$a->strings['l, F j'] = 'l, F j';
$a->strings['Duplicate'] = 'Kaksoiskappale';
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
$a->strings['View related post'] = 'Näytä liittyvä julkaisu';
$a->strings['[no subject]'] = '[ei aihetta]';
$a->strings['Wall Photos'] = 'Seinäkuvat';
$a->strings['Change profile picture'] = 'Vaihda profiilikuva';
$a->strings['Homepage:'] = 'Kotisivu:';
$a->strings['About:'] = 'Lisätietoja:';
$a->strings['Joined:'] = 'Liittynyt: ';
$a->strings['Edit profile'] = 'Muokkaa profiilia';
$a->strings['Atom feed'] = 'Atom -syöte';
$a->strings['This website has been verified to belong to the same person.'] = 'Tämän verkkosivun on vahvistettu kuuluvan samalle henkilölle.';
$a->strings['[today]'] = '[tänään]';
$a->strings['Birthday Reminders'] = 'Syntymäpäivämuistutukset';
$a->strings['Birthdays this week:'] = 'Syntymäpäiviä tällä viikolla:';
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
$a->strings['Invalid OpenID url'] = 'Epäkelpo OpenID-URL';
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

			Sivuston sijainti:	%3$s
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
$a->strings['User with delegates can\'t be removed, please remove delegate users first'] = 'Käyttäjää, jolla on valtuudet, ei voida poistaa. Poista valtuutetut käyttäjät ensin.';
$a->strings['Addon not found.'] = 'Lisäosaa ei löydy.';
$a->strings['Addon %s disabled.'] = 'Lisäosa %s poistettu käytöstä.';
$a->strings['Addon %s enabled.'] = 'Lisäosa %s käytössä.';
$a->strings['Disable'] = 'Poista käytöstä';
$a->strings['Enable'] = 'Ota käyttöön';
$a->strings['Invalid Addon found.'] = 'Epäkelpo lisäosa löydetty.';
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
$a->strings['There was no additional update function %s that needed to be called.'] = 'Ei ollut kutsuttavaa lisäpäivitystoimintoa %s. ';
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
$a->strings['This page offers you some numbers to the known part of the federated social network your Friendica node is part of. These numbers are not complete but only reflect the part of the network your node is aware of.'] = 'Tämä sivu tarjoaa sinulle joitakin lukuja siitä osasta liittoutuneen sosiaalisen verkoston, johon Friendica-solmusi kuuluu. Nämä luvut eivät ole täydellisiä, vaan ne kuvaavat vain sitä osan verkosta, josta solmusi on tietoinen.';
$a->strings['Federation Statistics'] = 'Liiton tilastotiedot';
$a->strings['Currently this node is aware of %2$s node (%3$s active users last month, %4$s active users last six months, %5$s registered users in total) from the following platforms:'] = [
	0 => 'Tällä hetkellä tämä solmu on tietoinen %2$s solmusta (%3$s aktiivista käyttäjää viime kuussa, %4$s aktiivista käyttäjää viimeisen kuuden kuukauden aikana, %5$s rekisteröitynyttä käyttäjää yhteensä) seuraavilta alustoilta:',
	1 => 'Tällä hetkellä tämä solmu on tietoinen%2$s solmusta (%3$s aktiivista käyttäjää viime kuussa, %4$s aktiivista käyttäjää viimeisen kuuden kuukauden aikana, %5$s rekisteröitynyttä käyttäjää yhteensä) seuraavilta alustoilta:',
];
$a->strings['The logfile \'%s\' is not writable. No logging possible'] = 'Lokitiedostoa \'%s\' ei ole kirjoitettavissa. Lokitiedoston tallennus ei ole mahdollista.';
$a->strings['PHP log currently enabled.'] = 'PHP-loki käytössä.';
$a->strings['PHP log currently disabled.'] = 'PHP-loki pois käytöstä.';
$a->strings['Log settings'] = 'Lokiasetukset';
$a->strings['Clear'] = 'Tyhjennä';
$a->strings['Enable Debugging'] = 'Ota virheenkorjaustila käyttöön';
$a->strings['<strong>Read-only</strong> because it is set by an environment variable'] = '<strong>Vain luku</strong>, koska se on asetettu ympäristömuuttujalla';
$a->strings['Log file'] = 'Lokitiedosto';
$a->strings['Must be writable by web server. Relative to your Friendica top-level directory.'] = 'Web-palvelimen on voitava kirjoittaa tähän. Tämä on suhteessa Friendican ylätason hakemistoon.';
$a->strings['Log level'] = 'Lokitaso';
$a->strings['PHP logging'] = 'PHP-loki';
$a->strings['To temporarily enable logging of PHP errors and warnings you can prepend the following to the index.php file of your installation. The filename set in the \'error_log\' line is relative to the friendica top-level directory and must be writeable by the web server. The option \'1\' for \'log_errors\' and \'display_errors\' is to enable these options, set to \'0\' to disable them.'] = 'Voit ottaa PHP-virheiden ja -varoitusten lokituksen väliaikaisesti käyttöön lisäämällä seuraavan rivin asennuksesi index.php-tiedostoon. Rivillä \'error_log\' määritetty tiedostonimi on suhteellinen Friendican ylätason hakemistoon nähden, ja web-palvelimen on voitava kirjoittaa siihen. Asetukset \'1\' ja \'0\' kohdissa \'log_errors\' ja \'display_errors\' ottavat nämä asetukset käyttöön (\'1\') tai poistavat ne käytöstä (\'0\').';
$a->strings['Error trying to open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s exist and is readable.'] = 'Virhe yritettäessä avata <strong>%1$s</strong> loki-tiedostoa.<br/>Tarkista onko tiedosto %1$s olemassa ja että se on luku-muodossa.';
$a->strings['Couldn\'t open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s is readable.'] = '<strong>%1$s</strong>Loki-tiedostoa<br/> ei voitu avata. Tarkista, että tiedosto %1$s on lukumuodossa.';
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
$a->strings['Current log path: %s'] = 'Nykyinen lokipolku: %s';
$a->strings['Inspect Deferred Worker Queue'] = 'Tarkista lykättyjen työntekijöiden jono';
$a->strings['This page lists the deferred worker jobs. This are jobs that couldn\'t be executed at the first time.'] = 'Tällä sivulla on luettelo lykätyistä työntekijöiden töistä. Nämä ovat töitä, joita ei voitu suorittaa ensimmäisellä kerralla.';
$a->strings['Inspect Worker Queue'] = 'Tarkista työntekijäjono';
$a->strings['This page lists the currently queued worker jobs. These jobs are handled by the worker cronjob you\'ve set up during install.'] = 'Tällä hetkellä jonossa olevat työntekijän työt on listattu tälle sivulle. Asennuksen yhteydessä määrittelemäsi määräajoin tehtävien töiden automatisoitu työntekijä hoitaa nämä työt.';
$a->strings['ID'] = 'ID';
$a->strings['Command'] = 'Komento';
$a->strings['Job Parameters'] = 'Työparametrit';
$a->strings['Created'] = 'Luotu';
$a->strings['Next Try'] = 'Seuraava yritys';
$a->strings['Priority'] = 'Prioriteetti';
$a->strings['%s is no valid input for maximum media size'] = '%s ei kelpaa arvoksi median enimmäiskoolle';
$a->strings['%s is no valid input for maximum image size'] = '%s ei ole kelvollinen arvo kuvan enimmäiskoolle';
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
$a->strings['Use the command "console relay" in the command line to add or remove relays.'] = 'Voit lisätä tai poistaa välittäjiä komentorivillä komennolla ”console relay”.';
$a->strings['The system is not subscribed to any relays at the moment.'] = 'Järjestelmää ei ole tällä hetkellä tilattu yhteenkään välittäjään.';
$a->strings['The system is currently subscribed to the following relays:'] = 'Järjestelmä on tällä hetkellä tilattu seuraaviin välittäjiin:';
$a->strings['Relocate Node'] = 'Uudelleen sijoita solmu';
$a->strings['Relocating your node enables you to change the DNS domain of this node and keep all the existing users and posts. This process takes a while and can only be started from the relocate console command like this:'] = 'Solmusi siirtäminen sallii sinun muuttaa tämän solmun DNS-toimialuetta ja pitää olemassa olevat käyttäjät ja julkaisut. Tämä prosessi kestää jonkin aikaa ja sen voi aloittaa vain siirtokonsolikäskyllä näin:';
$a->strings['(Friendica directory)# bin/console relocate https://newdomain.com'] = '(Friendica hakemisto)# bin/console relocate https://uusiverkkotunnus.com';
$a->strings['Site name'] = 'Sivuston nimi';
$a->strings['Sender Email'] = 'Lähettäjän sähköposti';
$a->strings['The email address your server shall use to send notification emails from.'] = 'Lähettäjän sähköpostiosoite palvelimen ilmoitussähköposteissa.';
$a->strings['Name of the system actor'] = 'Järjestelmätoimijan nimi';
$a->strings['Name of the internal system account that is used to perform ActivityPub requests. This must be an unused username. If set, this can\'t be changed again.'] = 'Sisäisen järjestelmätilin nimi, jota käytetään ActivityPub-pyyntöjen suorittamiseen. Tämän täytyy olla käyttämätön käyttäjänimi. Kerran asetettua käyttäjänimeä ei voi muuttaa.';
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
$a->strings['Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.'] = 'Pakota kaikki Non-SSL -pyynnöt SSL -pyynnöiksi. Huom! Tämä voi johtaa loputtomiin silmukoihin joissain järjestelmissä.';
$a->strings['Show help entry from navigation menu'] = 'Näytä navigointimenun Ohje-artikkeli';
$a->strings['Displays the menu entry for the Help pages from the navigation menu. It is always accessible by calling /help directly.'] = 'Näyttää navigointivalikossa Ohje-sivujen valikkoartikkelin. Se on aina saatavilla suoralla kutsulla /help.';
$a->strings['Single user instance'] = 'Yksittäisen käyttäjän instanssi';
$a->strings['Make this instance multi-user or single-user for the named user'] = 'Tee tästä instanssista monen käyttäjän instanssi tai yhden nimetyn käyttäjän instanssi';
$a->strings['Maximum image size'] = 'Kuvan enimmäiskoko';
$a->strings['Maximum size in bytes of uploaded images. Default is 0, which means no limits. You can put k, m, or g behind the desired value for KiB, MiB, GiB, respectively.
													The value of <code>upload_max_filesize</code> in your <code>PHP.ini</code> needs be set to at least the desired limit.
													Currently <code>upload_max_filesize</code> is set to %s (%s byte)'] = 'Ladattujen kuvien enimmäiskoko bitteinä. Oletus on 0, joka tarkoittaa "ei rajoituksia". Voit syöttää k, m tai g haluamasi arvon jälkeen merkityksessä KiB, MiB ja GiB.
													Arvo <code>upload_max_filesize</code> <code>PHP.ini</code>-tiedostossasi täytyy asettaa vähintään haluttuun rajaan.
													Tällä hetkellä <code>upload_max_filesize</code> on asetettu arvoon %s (%s bittiä).';
$a->strings['Maximum image length'] = 'Suurin kuvapituus';
$a->strings['Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.'] = 'Ladattavan kuvatiedoston enimmäispituus pikseleinä. Oletusarvo on -1, eli ei enimmäispituutta.';
$a->strings['JPEG image quality'] = 'JPEG-kuvanlaatu';
$a->strings['Uploaded JPEGS will be saved at this quality setting [0-100]. Default is 100, which is full quality.'] = 'Ladatut JPEG-kuvat tallennetaan tällä laatuasetuksella [0-100]. Oletus on 100, eli korkein laatu.';
$a->strings['Maximum media file size'] = 'Mediatiedoston enimmäiskoko';
$a->strings['Maximum size in bytes of uploaded media files. Default is 0, which means no limits. You can put k, m, or g behind the desired value for KiB, MiB, GiB, respectively.
													The value of <code>upload_max_filesize</code> in your <code>PHP.ini</code> needs be set to at least the desired limit.
													Currently <code>upload_max_filesize</code> is set to %s (%s byte)'] = 'Ladattujen mediatiedostojen enimmäiskoko bitteinä. Oletus on 0, joka tarkoittaa "ei rajoituksia". Voit syöttää k, m tai g haluamasi arvon jälkeen merkityksessä KiB, MiB ja GiB.
													Arvo <code>upload_max_filesize</code> <code>PHP.ini</code>-tiedostossasi täytyy asettaa vähintään haluttuun rajaan.
													Tällä hetkellä <code>upload_max_filesize</code> on asetettu arvoon %s (%s bittiä)';
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
$a->strings['URL to the global directory. If this is not set, the global directory is completely unavailable to the application.'] = 'URL-osoite globaaliin hakemistoon. Jos tätä ei ole määritetty, globaali hakemisto ei ole lainkaan käytettävissä sovelluksessa.';
$a->strings['Private posts by default for new users'] = 'Yksityiset julkaisut oletuksena uusille käyttäjille';
$a->strings['Set default post permissions for all new members to the default privacy circle rather than public.'] = 'Aseta kaikkien uusien jäsenten oletusarvoiset viestien käyttöoikeudet oletusarvoisesti yksityisyyspiiriksi julkisen sijaan.';
$a->strings['Don\'t include post content in email notifications'] = 'Älä lisää julkaisun sisältöä sähköposti-ilmoitukseen';
$a->strings['Don\'t include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.'] = 'Älä sisällytä tämän sivuston ulkopuolisesta lähteestä lähetettyjen julkaisujen/kommenttien/yksityisviestien ym. sisältöä sähköpostimuistutuksiin yksityisyyden turvaamikseksi.';
$a->strings['Disallow public access to addons listed in the apps menu.'] = 'Estä julkinen pääsy sovellusvalikossa lueteltuihin lisäosiin.';
$a->strings['Checking this box will restrict addons listed in the apps menu to members only.'] = 'Tämän valintaruudun valitseminen rajoittaa sovellusvalikossa luetellut lisäosat vain jäsenille.';
$a->strings['Don\'t embed private images in posts'] = 'Älä upota yksityisiä kuvia julkaisuihin';
$a->strings['Don\'t replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.'] = 'Älä korvaa paikallisesti isännöityjä yksityisiä valokuvia julkaisuissa upotetulla kopiona kuvasta. Tämä tarkoittaa, että kontaktit, jotka vastaanottavat yksityisiä valokuvia sisältäviä julkaisuja, joutuvat todentamaan itsensä ja lataamaan jokaisen kuvan, mikä voi kestää jonkin aikaa.';
$a->strings['Explicit Content'] = 'Epäsiveellinen sisältö';
$a->strings['Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.'] = 'Laita tämä asetus päälle ilmoittaaksesi, että sinun solmuasi käytetään pääasiassa epäsiveelliselle sisällölle, joka saattaa olla alaikäisille sopimatonta. Tämä tieto julkaistaan solmun tiedoissa ja sitä voidaan käyttää esimerkiksi maailmanlaajuisessa hakemistossa suodattamaan solmusi pois liityttävien solmujen luettelosta. Asiasta lisätään myös huomautus käyttäjien rekisteröitymissivulle.';
$a->strings['Only local search'] = 'Vain paikallinen haku';
$a->strings['Blocks search for users who are not logged in to prevent crawlers from blocking your system.'] = 'Estä sisään kirjautumattomien käyttäjien tekemät haut, niin että crawlerit eivät voi tukkia järjestelmääsi.';
$a->strings['Blocked tags for trending tags'] = 'Estetyt tunnisteet trendaavista tunnisteista';
$a->strings['Comma separated list of hashtags that shouldn\'t be displayed in the trending tags.'] = 'Pilkulla eroteltu luettelo aihetunnisteista, joita ei saa näyttää trendaavissa tunnisteissa.';
$a->strings['Cache contact avatars'] = 'Tallenna kontaktien avatarit välimuistiin';
$a->strings['Locally store the avatar pictures of the contacts. This uses a lot of storage space but it increases the performance.'] = 'Tallenna kontaktien avatar-kuvat paikallisesti. Tämä käyttää paljon muistitilaa, mutta parantaa suorituskykyä.';
$a->strings['Allow Users to set remote_self'] = 'Salli käyttäjien asettaa etäitse';
$a->strings['With checking this, every user is allowed to mark every contact as a remote_self in the repair contact dialog. Setting this flag on a contact causes mirroring every posting of that contact in the users stream.'] = 'Kun tämä on valittuna, jokainen käyttäjä voi merkitä jokaisen kontaktin etäitsekseen korjaa kontakti -valintaikkunassa. Tämän lipun asettaminen kontaktiin aiheuttaa jokaisen kyseisen kontaktin julkaisun peilaantumisen käyttäjän virtaan.';
$a->strings['Allow Users to set up relay channels'] = 'Salli käyttäjien perustaa välittäjäkanavia';
$a->strings['If enabled, it is possible to create relay users that are used to reshare content based on user defined channels.'] = 'Jos käytössä, on mahdollista luoda välittäjäkäyttäjiä, joita käytetään sisällön uudelleenjakamiseen käyttäjän valitsemiin kanaviin perustuen.';
$a->strings['Adjust the feed poll frequency'] = 'Säädä syötekyselyn esiintymistiheyttä';
$a->strings['Automatically detect and set the best feed poll frequency.'] = 'Havannoi automaattisesti ja aseta paras syötekyselyn esiintymistieheys.';
$a->strings['Minimum poll interval'] = 'Kyselyiden vähimmäisväli';
$a->strings['Minimal distance in minutes between two polls for mail and feed contacts. Reasonable values are between 1 and 59.'] = 'Posti- ja syötekontaktien tekemien kahden kyselyn vähimmäisväli minuuteissa. Sopivat arvot ovat välillä 1 ja 59.';
$a->strings['Enable multiple registrations'] = 'Ota käyttöön moninkertaiset rekisteröitymiset';
$a->strings['Enable users to register additional accounts for use as pages.'] = 'Ota käyttöön käyttäjien rekisteröimät lisätilit, joita he voivat käyttää sivuina.';
$a->strings['Enable OpenID'] = 'Ota OpenID käyttöön';
$a->strings['Enable OpenID support for registration and logins.'] = 'Ota käyttöön OpenID-tuki rekisteröitymisessä ja sisäänkirjautumisessa.';
$a->strings['Enable full name check'] = 'Ota käyttöön koko nimen tarkistus';
$a->strings['Prevents users from registering with a display name with fewer than two parts separated by spaces.'] = 'Estää käyttäjiä rekisteröimästä näyttönimeä, jossa on vähemmän kuin kaksi välilyönnillä erotettua osaa.';
$a->strings['Email administrators on new registration'] = 'Sähköpostin lähettäminen järjestelmänvalvojille uusista käyttäjistä';
$a->strings['If enabled and the system is set to an open registration, an email for each new registration is sent to the administrators.'] = 'Jos tämä toiminto on käytössä ja järjestelmä on asetettu avoimeksi rekisteröinniksi, jokaisesta uudesta rekisteröinnistä lähetetään sähköpostiviesti järjestelmänvalvojille.';
$a->strings['Community pages for visitors'] = 'Yhteisösivu vieraille';
$a->strings['Which community pages should be available for visitors. Local users always see both pages.'] = 'Mitkä yhteisösivut tulisi olla vierailijoiden käytettävissä. Paikalliset käyttäjät näkevät aina molemmat sivut.';
$a->strings['Posts per user on community page'] = 'Käyttäjäkohtaiset julkaisut yhteisösivulla';
$a->strings['The maximum number of posts per user on the local community page. This is useful, when a single user floods the local community page.'] = 'Käyttäjäkohtaisten julkaisujen enimmäismäärä paikallisella yhteisösivulla. Tämä asetus on hyödyllinen, kun yksittäinen käyttäjä floodaa paikallisen yhteisösivun.';
$a->strings['Posts per server on community page'] = 'Palvelinkohtaiset julkaisut yhteisösivulla';
$a->strings['The maximum number of posts per server on the global community page. This is useful, when posts from a single server flood the global community page.'] = 'Palvelinkohtaisten julkaisujen enimmäismäärä globaalilla yhteisösivulla. Tämä asetus on hyödyllinen, kun julkaisut yksittäiseltä palvelimelta floodavat globaalin yhteisösivun.';
$a->strings['Display local media to visitors'] = 'Näytä paikallinen media vierailijoille';
$a->strings['When enabled, locally stored media, such as pictures and videos, will be displayed to visitors who are not logged in. When disabled, a message will appear informing visitors that the media is only available to logged-in users.'] = 'Kun käytössä, paikallisesti tallennettu media – kuten kuvat ja videot – näytetään vierailijoille, jotka eivät ole kirjautuneet sisään. Kun pois käytöstä, vierailijat näkevät viestin, jossa heille kerrotaan, että media on vain sisään kirjautuneiden käyttäjien nähtävillä.';
$a->strings['Display remote media to visitors'] = 'Näytä etämedia vierailijoille';
$a->strings['When enabled, visitors who are not logged in will be able to view non-locally stored media such as pictures and videos. When disabled, visitors will see a message informing them that the media is available on the remote site.'] = 'Kun käytössä, vierailijat, jotka eivät ole kirjautuneet sisään, voivat nähdä ei-paikallisesti tallennetun median, kuten kuvat ja videot. Kun pois käytöstä, vierailijat näkevät viestin, jossa heille kerrotaan, että media on saatavilla etäsivulla.';
$a->strings['Enable Mail support'] = 'Ota käyttöön sähköpostituki';
$a->strings['Enable built-in mail support to poll IMAP folders and to reply via mail.'] = 'Ota käyttöön sisäänrakennettu tuki IMAP-kansioiden pollaukselle ja vastauksille sähköpostin kautta.';
$a->strings['Mail support can\'t be enabled because the PHP IMAP module is not installed.'] = 'Postitukea ei voida ottaa käyttöön, koska PHP IMAP -moduulia ei ole asennettu.';
$a->strings['Diaspora support can\'t be enabled because Friendica was installed into a sub directory.'] = 'Diaspora-tukea ei voida ottaa käyttöön, koska Friendica on asennettu alihakemistoon.';
$a->strings['Enable Diaspora support'] = 'Salli Diaspora-tuki';
$a->strings['Enable built-in Diaspora network compatibility for communicating with diaspora servers.'] = 'Ota käyttöön sisäänrakennettu yhteensopivuus Diaspora-verkostoon kommunikoidaksesi diaspora-palvelinten kanssa.';
$a->strings['Verify SSL'] = 'Vahvista SSL';
$a->strings['If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.'] = 'Voi laittaa tiukan sertifikaatin tarkistuksen pois päältä halutessasi. Tämä tarkoittaa, että et voi yhdistää (lainkaan) itse allekirjoitettuihin SSL-sivustoihin.';
$a->strings['Proxy user'] = 'Välityspalvelimen käyttäjä';
$a->strings['User name for the proxy server.'] = 'Välittäjäpalvelimen käyttäjänimi';
$a->strings['Proxy URL'] = 'Välityspalvelimen osoite';
$a->strings['If you want to use a proxy server that Friendica should use to connect to the network, put the URL of the proxy here.'] = 'Jos haluat käyttää välityspalvelinta, jota Friendican pitää käyttää verkostoon yhdistyessä, laita välityspalvelimen URL tähän.';
$a->strings['Network timeout'] = 'Verkon aikakatkaisu';
$a->strings['Value is in seconds. Set to 0 for unlimited (not recommended).'] = 'Arvo sekunneissa. Aseta rajoittamattomaksi arvoksi 0 (ei suositeltu).';
$a->strings['Maximum Load Average'] = 'Kuorman enimmäiskeksiarvo';
$a->strings['Maximum system load before delivery and poll processes are deferred - default %d.'] = 'Järjestelmän enimmäiskuormitus ennen toimitus- ja kyselyprosessien lykkäämistä – oletusarvo %d.';
$a->strings['Minimal Memory'] = 'Vähimmäismuisti';
$a->strings['Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).'] = 'Vapaan muistin vähimmäismäärä (MB) työntekijälle. Vaatii pääsyn polkuun /proc/meminfo – oletusarvo 0 (ei aktivoitu).';
$a->strings['Periodically optimize tables'] = 'Optimoi säännöllisesti taulukot';
$a->strings['Periodically optimize tables like the cache and the workerqueue'] = 'Optimoi säännöllisesti taulukot, kuten välimuisti ja työjono.';
$a->strings['Discover followers/followings from contacts'] = 'Löydä seuraajia/seurauksia kontakteista';
$a->strings['If enabled, contacts are checked for their followers and following contacts.'] = 'Kun käytössä, kontaktien seuraajat ja keitä kontaktit seuraavat tarkistetaan.';
$a->strings['None - deactivated'] = 'Ei mitään – ei aktivoitu';
$a->strings['Local contacts - contacts of our local contacts are discovered for their followers/followings.'] = 'Paikalliset kontaktit – paikallisten kontaktiemme kontaktit näytetään heidän seuraajilleen/seurauksilleen.';
$a->strings['Interactors - contacts of our local contacts and contacts who interacted on locally visible postings are discovered for their followers/followings.'] = 'Vuorovaikuttajat – paikallisten kontaktiemme kontaktit ja kontaktit, jotka ovat olleet vuorovaikutuksessa paikallisesti näkyvien julkaisujen kanssa, näytetään heidän seuraajilleen/seurauksilleen.';
$a->strings['Only update contacts/servers with local data'] = 'Päivitä kontaktit/palvelimet vain paikallisella datalla';
$a->strings['If enabled, the system will only look for changes in contacts and servers that engaged on this system by either being in a contact list of a user or when posts or comments exists from the contact on this system.'] = 'Jos käytössä, järjestelmä etsii muutoksia vain kontakteissa ja palvelimissa, jotka ovat kytköksissä tähän järjestelmään joko olemalla käyttäjän kontaktilistalla tai kun palvelimella on tässä järjestelmässä olevalta kontaktilta julkaisuja tai kommentteja.';
$a->strings['Only update contacts with relations'] = 'Päivitä vain kontaktit, joihin on suhde';
$a->strings['If enabled, the system will only look for changes in contacts that are in a contact list of a user on this system.'] = 'Jos käytössä, järjestelmä etsii muutoksia vain kontakteissa, jotka ovat tässä järjestelmässä olevien käyttäjien kontaktilistalla.';
$a->strings['Synchronize the contacts with the directory server'] = 'Synkronoi kontaktit hakemistopalvelimen kanssa';
$a->strings['if enabled, the system will check periodically for new contacts on the defined directory server.'] = 'Jos käytössä, järjestelmä tarkistaa säännöllisesti uudet kontaktit määritellyltä hakemistopalvelimelta.';
$a->strings['Discover contacts from other servers'] = 'Etsi kontakteja muilta palvelimilta';
$a->strings['Periodically query other servers for contacts and servers that they know of. The system queries Friendica, Mastodon and Hubzilla servers. Keep it deactivated on small machines to decrease the database size and load.'] = 'Lähetä ajoittain kysely muille palvelimille kontakteista ja palvelimista, joista he tietävät. Järjestelmä kyselee Friendica-, Mastodon- ja Hubzilla-palvelmilta. Pidä poissa käytöstä pienillä koneilla, jotta voit pienentää tietokannan kokoa ja kuormaa.';
$a->strings['Days between requery'] = 'Päivien määrä uudelleenkyselyn välillä';
$a->strings['Number of days after which a server is requeried for their contacts and servers it knows of. This is only used when the discovery is activated.'] = 'Niiden päivien määrä, jonka jälkeen palvelimelta kysellään uudestaan heidän kontakteistaan ja heidän tuntemistaan palvelimista. Tämä on käytössä vain kun löytäminen on käytössä.';
$a->strings['Search the local directory'] = 'Etsi paikallisesta hakemistosta';
$a->strings['Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.'] = 'Etsi paikallisesta hakemistosta maailmanlaajuisen hakemiston sijaan. Kun etsit paikallisesti, jokainen haku toteutetaan maailmanlaajuinen haku taustalla. Tämä parantaa hakutuloksia, kun haku toistetaan.';
$a->strings['Publish server information'] = 'Julkaise palvelintiedot';
$a->strings['If enabled, general server and usage data will be published. The data contains the name and version of the server, number of users with public profiles, number of posts and the activated protocols and connectors. See <a href="http://the-federation.info/">the-federation.info</a> for details.'] = 'Jos käytössä, yleiset palvelimen ja käytön tiedot julkaistaan. Tiedot sisältävät palvelimen nimen ja version, julkisen profiilin omaavien käyttäjien määrän, julkaisujen määrän sekä aktivoidut yhteyskäytännöt ja yhdistimet. Lisätietoa, ks. <a href="http://the-federation.info/">the-federation.info</a>.';
$a->strings['Check upstream version'] = 'Tarkista paluusuunnan versio';
$a->strings['Enables checking for new Friendica versions at github. If there is a new version, you will be informed in the admin panel overview.'] = 'Mahdollistaa uusien Friendica-versioiden tarkistamisen githubista. Jos uusi versio on saatavilla, saat siitä ilmoituksen ylläpitäjän yleisnäkymäpaneeliin.';
$a->strings['Suppress Tags'] = 'Piilota tunnisteet';
$a->strings['Suppress showing a list of hashtags at the end of the posting.'] = 'Piilota tunnistelista julkaisun lopussa.';
$a->strings['Clean database'] = 'Siivoa tietokanta';
$a->strings['Remove old remote items, orphaned database records and old content from some other helper tables.'] = 'Poistaa vanhat etäkohteet, orvoksi jääneet tietokantatietueet ja vanhan sisällön muutamista muista avustajataulukoista.';
$a->strings['Lifespan of remote items'] = 'Etäkohteiden elinkaari';
$a->strings['When the database cleanup is enabled, this defines the days after which remote items will be deleted. Own items, and marked or filed items are always kept. 0 disables this behaviour.'] = 'Kun tietokannan siivous on pois käytöstä, tämä määrittää niiden päivien lukumäärän, joiden kuluttua etäkohteet poistetaan. Omat kohteet sekä merkityt ja kansioidut kohteet pidetään aina. 0 ottaa tämän toiminnon pois käytöstä.';
$a->strings['Lifespan of unclaimed items'] = 'Elinkaari ja omistajamattomat kohteet';
$a->strings['When the database cleanup is enabled, this defines the days after which unclaimed remote items (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general lifespan value of remote items if set to 0.'] = 'Kun tietokannan siivous on käytössä, tämä määrittää kuinka monen päivän jälkeen omistamattomat etäkohteet (pääasiassa sisältöä välittäjistä) poistetaan. Oletusarvo on 90 päivää. Jos arvoksi asetetaan 0, käytetään oletuksena etäkohteiden yleistä elinkaariarvoa.';
$a->strings['Lifespan of raw conversation data'] = 'Keskustelun raakadatan elinkaari';
$a->strings['The conversation data is used for ActivityPub, as well as for debug purposes. It should be safe to remove it after 14 days, default is 90 days.'] = 'Keskusteludataa käytetään ActivityPubia varten ja virheiden etsintätarkoituksiin. Sen poistaminen 14 päivän jälkeen pitäisi olla turvallista, oletus on 90 päivää.';
$a->strings['Maximum numbers of comments per post'] = 'Komenttien julkaisukohtainen enimmäismäärä';
$a->strings['How much comments should be shown for each post? Default value is 100.'] = 'Kuinka monta kommenttia näytetään kussakin julkaisussa? Oletusarvo on 100.';
$a->strings['Maximum numbers of comments per post on the display page'] = 'Kommenttien julkaisukohtainen enimmäismäärä näyttösivulla';
$a->strings['How many comments should be shown on the single view for each post? Default value is 1000.'] = 'Kuinka monta kommenttia näytetään kunkin julkaisun yksittäisessä näkymässä? Oletusarvo on 1 000.';
$a->strings['Items per page'] = 'Kohteiden määrä sivulla';
$a->strings['Number of items per page in stream pages (network, community, profile/contact statuses, search).'] = 'Kohteiden sivukohtainen lukumäärä virtasivuilla (verkosto, yhteisö, profiilien/kontaktien tilapäivitykset, haku).';
$a->strings['Items per page for mobile devices'] = 'Kohteiden määrä sivulla mobiililaitteissa';
$a->strings['Number of items per page in stream pages (network, community, profile/contact statuses, search) for mobile devices.'] = 'Kohteiden sivukohtainen lukumäärä virtasivuilla (verkosto, yhteisö, profiilien/kontaktien tilapäivitykset, haku) mobiililaitteissa.';
$a->strings['Temp path'] = 'Väliaikaistiedostojen polku';
$a->strings['If you have a restricted system where the webserver can\'t access the system temp path, enter another path here.'] = 'Mikäli verkkopalvelimesi ei voi käyttää järjestelmän väliaikaistiedostojen polkua, syötä toinen polku tähän.';
$a->strings['Only search in tags'] = 'Esti vain tunnisteista';
$a->strings['On large systems the text search can slow down the system extremely.'] = 'Isoissa järjestelmissä tekstihaku saattaa hidastaa järjestelmää suuresti.';
$a->strings['Limited search scope'] = 'Rajoitettu hakuala';
$a->strings['If enabled, searches will only be performed in the data used for the channels and not in all posts.'] = 'Jos käytössä, haut suoritetaan vain kanavilla käytetystä datasta eikä kaikista julkaisuista.';
$a->strings['Maximum age of items in the search table'] = 'Hakutaulukon kohteiden enimimmäisikä';
$a->strings['Maximum age of items in the search table in days. Lower values will increase the performance and reduce disk usage. 0 means no age restriction.'] = 'Hakutaulukon kohteiden enimmäisikä päivinä. Pienemmät arvot parantavat suorituskykyä ja vähentävät levytilan käyttöä. 0 tarkoittaa, että ikärajaa ei ole.';
$a->strings['Generate counts per contact circle when calculating network count'] = 'Luo kontaktipiirin mukaiset laskelmat verkoston laskennan yhteydessä';
$a->strings['On systems with users that heavily use contact circles the query can be very expensive.'] = 'Järjestelmissä, joissa käyttäjät käyttävät paljon yhteyspiirejä, kysely voi olla erittäin kallis.';
$a->strings['Process "view" activities'] = 'Prosessoi "näyttökerrat"-aktiviteetit';
$a->strings['"view" activities are mostly geberated by Peertube systems. Per default they are not processed for performance reasons. Only activate this option on performant system.'] = '"Näyttökerta"-aktiviteetit luodaan pääasiassa Peertube-järjestelmissä. Oletuksena niitä ei prosessoida suorituskykysyistä. Ota tämä valinta käyttöön vain järjestelmässä, jolla on korkea suorituskyky.';
$a->strings['Days, after which a contact is archived'] = 'Päivät, joiden kuluttua kontakti arkistoidaan';
$a->strings['Number of days that we try to deliver content or to update the contact data before we archive a contact.'] = 'Niiden päivien lukumäärä, joina yritämme toimittaa sisältöä tai päivittää kontaktidataa ennen kuin arkistoimme kontaktin.';
$a->strings['Maximum number of parallel workers'] = 'Enimmäismäärä rinnakkaisia workereitä';
$a->strings['On shared hosters set this to %d. On larger systems, values of %d are great. Default value is %d.'] = 'Jaetuilla isännillä tämä on asetettu arvoon %d. Suuremmissa järjestelmissä %d:n arvot käyvät hyvin. Oletus arvo on %d.';
$a->strings['Maximum load for workers'] = 'Työntekijöiden enimmäiskuorma';
$a->strings['Maximum load that causes a cooldown before each worker function call.'] = 'Enimmäiskuorma, joka aiheuttaa jäähdytyksen ennen kunkin työntekijätoiminnon kutsumista.';
$a->strings['Enable fastlane'] = 'Käytä fastlane';
$a->strings['When enabed, the fastlane mechanism starts an additional worker if processes with higher priority are blocked by processes of lower priority.'] = 'Kun otettu käyttöön, pikakaistamekanismi laittaa töihin lisätyöntekijän, jos alemman prioriteetin prosessit estävät korkeamman prioriteetin prosessit.';
$a->strings['Decoupled receiver'] = 'Irrotettu vastaanotin';
$a->strings['Decouple incoming ActivityPub posts by processing them in the background via a worker process. Only enable this on fast systems.'] = 'Irroita sisääntulevat ActivityPub-julkaisut prosessoimalla ne taustalla työntekijäprosessin kautta. Ota tämä käyttöön vain nopeissa järjestelmissä.';
$a->strings['Fetch replies mode'] = 'Vastausten noutotila';
$a->strings['Missing replies can be fetched upon receipt of an ActivityPub post.'] = 'Puuttuvat vastaukset voidaan noutaa ActivityPub-viestin vastaanottamisen yhteydessä.';
$a->strings['Cron interval'] = 'Cron-väli';
$a->strings['Minimal period in minutes between two calls of the "Cron" worker job.'] = 'Vähimmäisaika minuutteina kahden ”Cron”-työntekijän työn välillä.';
$a->strings['Worker defer limit'] = 'Työntekijän lykkäysraja';
$a->strings['Per default the systems tries delivering for 15 times before dropping it.'] = 'Oletuksena järjestelmä yrittää toimittaa 15 kertaa ennen tehtävän hylkäämistä.';
$a->strings['Worker fetch limit'] = 'Työntekijän noutoraja';
$a->strings['Number of worker tasks that are fetched in a single query. Higher values should increase the performance, too high values will mostly likely decrease it. Only change it, when you know how to measure the performance of your system.'] = 'Työntekijän tehtävien määrä, jotka noudetaan yhdellä kyselyllä. Suurempien arvojen pitäisi lisätä suorituskykyä, mutta liian suuret arvot todennäköisesti laskevat sitä. Muuta tätä vain, kun tiedät miten mitata järjestelmäsi suorituskykyä.';
$a->strings['Direct relay transfer'] = 'Suora välittäjän siirto';
$a->strings['Enables the direct transfer to other servers without using the relay servers'] = 'Sallii suoran siirron muihin palvelimiin käyttämättä välittäjäpalvelimia';
$a->strings['Relay scope'] = 'Välittäjän soveltamisala';
$a->strings['Can be "all" or "tags". "all" means that every public post should be received. "tags" means that only posts with selected tags should be received.'] = 'Voi olla "kaikki" tai "tunnisteet". "Kaikki" tarkoittaa, että jokainen julkinen julkaisu vastaanotetaan. "Tunnisteet" tarkoittaa, että vain julkaisut, joissa on valitut tunnisteet, vastaanotetaan.';
$a->strings['Disabled'] = 'Pois käytöstä';
$a->strings['all'] = 'kaikki';
$a->strings['tags'] = 'tunnisteet';
$a->strings['Server tags'] = 'palvelintunnisteet';
$a->strings['Comma separated list of tags for the "tags" subscription.'] = 'Pilkulla eroteltu luettelo tunnisteista "tunniste"-tilausta varten.';
$a->strings['Deny Server tags'] = 'Kielletyt palvelimen tunnisteet';
$a->strings['Comma separated list of tags that are rejected.'] = 'Pilkulla eroteltu luettelo tunnisteista, jotka hylätään.';
$a->strings['Maximum amount of tags'] = 'Tunnisteiden enimmäismäärä';
$a->strings['Maximum amount of tags in a post before it is rejected as spam. The post has to contain at least one link. Posts from subscribed accounts will not be rejected.'] = 'Julkaisussa olevien tunnisteiden enimmäismäärä ennen kuin se hylätään roskajulkaisuna. Julkaisussa täytyy olla vähintään yksi linkki. Tilatujen tilien julkaisuja ei hylätä.';
$a->strings['Allow user tags'] = 'Salli käyttäjien tunnisteet';
$a->strings['If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".'] = 'Jos käytössä, tallennettujen hakujen tunnisteita käytetään "tunniste"-tilauksiin "relay_server_tags"-tunnisteiden lisäksi.';
$a->strings['Deny undetected languages'] = 'Kieltäydy tunnistamattomista kielistä';
$a->strings['If enabled, posts with undetected languages will be rejected.'] = 'Jos käytössä, julkaisut, joiden kieltä ei ole tunnistettu, hylätään.';
$a->strings['Language Quality'] = 'Kielenlaatu';
$a->strings['The minimum language quality that is required to accept the post.'] = 'Viestin hyväksymiseen vaadittava vähimmäiskielenlaatu.';
$a->strings['Number of languages for the language detection'] = 'Kielentunnistuksessa olevien kielten lukumäärä';
$a->strings['The system detects a list of languages per post. Only if the desired languages are in the list, the message will be accepted. The higher the number, the more posts will be falsely detected.'] = 'Järjestelmä tunnistaa julkaisukohtaisen kieliluettelon. Viesti hyväksytään vain, jos halutut kielet ovat luettelossa. Mitä suurempi luku, sitä enemmän viestejä tunnistetaan virheellisesti.';
$a->strings['Maximum age of channel'] = 'Kanavan enimmäisikä';
$a->strings['This defines the maximum age in hours of items that should be displayed in channels. This affects the channel performance.'] = 'Tämä määrittää kanavissa näytettävien kohteiden enimmäisikärajan tunteina. Tämä vaikuttaa kanavan suorituskykyyn.';
$a->strings['Maximum number of channel posts'] = 'Kanavan julkaisujen enimmäismäärä';
$a->strings['For performance reasons, the channels use a dedicated table to store content. The higher the value the slower the channels.'] = 'Suorituskyvyn vuoksi kanavat käyttävät sisältöjen tallentamiseen erillistä taulukkoa. Mitä suurempi arvo, sitä hitaammat kanavat ovat.';
$a->strings['Interaction score days'] = 'Vuorovaikutuspistepäivät';
$a->strings['Number of days that are used to calculate the interaction score.'] = 'Vuorovaikutuspisteiden laskemiseen käytettyjen päivien lukumäärä.';
$a->strings['Maximum number of posts per author'] = 'Tekijäkohtainen julkaisujen enimmäismäärä';
$a->strings['Maximum number of posts per page by author if the contact frequency is set to "Display only few posts". If there are more posts, then the post with the most interactions will be displayed.'] = 'Sivuittainen tekijäkohtaisten julkaisujen enimmäismäärä, jos kontaktin esiintymistiheys on asetettu tilaan "Näytä vain muutamia julkaisuja". Jos julkaisuja on enemmän, näytetään julkaisut, joilla on eniten vuorovaikutusta.';
$a->strings['Sharer interaction days'] = 'Jakajan vuorovaikutuspäivät';
$a->strings['Number of days of the last interaction that are used to define which sharers are used for the "sharers of sharers" channel.'] = 'Viimeisen vuorovaikutuksen päivien lukumäärä, jota käytetään määrittämään, mitä jakajia käytetään "jakajien jakajien" -kanavalla.';
$a->strings['Start Relocation'] = 'Aloita siirto';
$a->strings['Storage backend, %s is invalid.'] = 'Tallennustilan taustapalvelin, %s on epäkelpo.';
$a->strings['Storage backend %s error: %s'] = 'Tallennustilan taustapalvelin %s virhe: %s';
$a->strings['Invalid storage backend setting value.'] = 'Epäkelpo tallennustilan taustapalvelimen asetusarvo.';
$a->strings['Current Storage Backend'] = 'Nykyinen tallennustilan taustapalvelin';
$a->strings['Storage Configuration'] = 'Varaston kokoonpano';
$a->strings['Storage'] = 'Varasto';
$a->strings['Save'] = 'Tallenna';
$a->strings['Save & Use storage backend'] = 'Tallennustilan taustapalvelin, tallennus ja käyttö';
$a->strings['Use storage backend'] = 'Tallennustilan taustapalvelin, käyttö';
$a->strings['Save & Reload'] = 'Tallenna ja lataa uudelleen';
$a->strings['This backend doesn\'t have custom settings'] = 'Tällä taustapalvelimella ei ole mukautettuja asetuksia.';
$a->strings['Changing the current backend is prohibited because it is set by an environment variable'] = 'Nykyisen taustapalvelimen vaihtaminen on kielletty, koska sen on asettanut ympäristömuuttuja';
$a->strings['Database (legacy)'] = 'Tietokanta (legacy)';
$a->strings['Template engine (%s) error: %s'] = 'Mallinekone (%s) virhe: %s';
$a->strings['Your DB still runs with MyISAM tables. You should change the engine type to InnoDB. As Friendica will use InnoDB only features in the future, you should change this! See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = 'Tietokantasi toimii edelleen MyISAM-taulukoilla. Sinun tulisi vaihtaa moottorin tyyppi InnoDB:ksi. Koska Friendica käyttää tulevaisuudessa vain InnoDB:n ominaisuuksia, sinun tulisi muuttaa tämä! Katso <a href="%s">täältä ohjeet</a>, jotka voivat olla hyödyllisiä taulukkomoottoreiden muuntamisessa. Voit myös käyttää komentoa <tt>php bin/console.php dbstructure toinnodb</tt> Friendica-asennuksessasi automaattista muuntamista varten.<br />';
$a->strings['Your DB still runs with InnoDB tables in the Antelope file format. You should change the file format to Barracuda. Friendica is using features that are not provided by the Antelope format. See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = 'Tietokantasi toimii edelleen InnoDB-taulukoilla Antelope-tiedostomuodossa. Sinun tulisi muuttaa tiedostomuoto Barracudaksi. Friendica käyttää ominaisuuksia, joita Antelope-muoto ei tarjoa. Katso <a href="%s">täältä</a> ohjeet, jotka voivat olla hyödyllisiä taulukkomoottoreiden muuntamisessa. Voit myös käyttää komentoa <tt>php bin/console.php dbstructure toinnodb</tt> Friendica-asennuksessasi automaattista muuntamista varten.<br />';
$a->strings['Your table_definition_cache is too low (%d). This can lead to the database error "Prepared statement needs to be re-prepared". Please set it at least to %d. See <a href="%s">here</a> for more information.<br />'] = 'Asettamasi table_definition_cache on liian pieni (%d). Tämä voi aiheuttaa tietokantavirheen "Prepared statement needs to be re-prepared" ("Valmisteltu lause on valmisteltava uudelleen"). Aseta tämä vähintään arvoon %d. Katso lisätietoja <a href="%s">täältä</a>.<br />';
$a->strings['There is a new version of Friendica available for download. Your current version is %1$s, upstream version is %2$s'] = 'Friendican uusi versio on ladattavissa. Nykyinen versiosi on %1$s, paluusuunnan versio on %2$s';
$a->strings['The database update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear.'] = 'Tietokannan päivitys epäonnistui. Suorita komento "php bin/console.php dbstructure update" komentoriviltä ja lue mahdolliset virheviestit.';
$a->strings['The last update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear. (Some of the errors are possibly inside the logfile.)'] = 'Viimeisin päivitys epäonnistui. Suorita komento ”php bin/console.php dbstructure update” komentoriviltä ja tarkista mahdollisesti näkyvät virheet. (Osa virheistä saattaa löytyä lokitiedostosta.)';
$a->strings['The system.url entry is missing. This is a low level setting and can lead to unexpected behavior. Please add a valid entry as soon as possible in the config file or per console command!'] = 'system.url -merkintä puuttuu. Tämä on matalan tason asetus, joka voi aiheuttaa odottamattomia toimintahäiriöitä. Lisää kelvollinen merkintä mahdollisimman pian konfiguraatiotiedostoon tai konsolikomennolla!';
$a->strings['The worker was never executed. Please check your database structure!'] = 'Workeriä ei ole otettu käyttöön. Tarkista tietokantasi rakenne!';
$a->strings['The last worker execution was on %s UTC. This is older than one hour. Please check your crontab settings.'] = 'Viimeisin worker -käynnistys tapahtui klo %s UTC, eli yli tunti sitten. Tarkista crontab -asetukset.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>.htconfig.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'Friendican asetukset tallennetaan nyt tiedostoon config/local.config.php. Kopioi tiedosto config/local-sample.config.php ja siirrä asetuksesi tiedostosta <code>.htconfig.php</code>. Katso ohjeita siirtämiseen <a href="%s">Asetusten ohjesivulta</a>.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>config/local.ini.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'Friendican konfigurointi on nyt tallennettu tiedostoon config/local.config.php. Kopioi config/local-sample.config.php ja siirrä konfigurointitiedostosi sijainnista <code>config/local.ini.php</code>. Katso <a href="%s">Konfiguiroinnin ohjeet</a> -sivulta apuja siirtoon.';
$a->strings['<a href="%s">%s</a> is not reachable on your system. This is a severe configuration issue that prevents server to server communication. See <a href="%s">the installation page</a> for help.'] = '<a href="%s">%s</a> ei ole saavutettavissa sinun järjestelmässäsi. Tämä on vakava konfigurointiongelma, mikä estää kommunnikoinnin palvelimelta palvelimelle. Katso ohjeita <a href="%s">asennus-sivulta</a>.';
$a->strings['Friendica\'s system.basepath was updated from \'%s\' to \'%s\'. Please remove the system.basepath from your db to avoid differences.'] = 'Friendican system.basepath on päivitetty versiosta "%s" versioon "%s". Poista system.basepath tietokannastasi välttyäksesi eroilta.';
$a->strings['Friendica\'s current system.basepath \'%s\' is wrong and the config file \'%s\' isn\'t used.'] = 'Friendican nykyinen system.basepath "%s" on väärä ja konfigurointitiedostoa "%s" ei käytetä.';
$a->strings['Friendica\'s current system.basepath \'%s\' is not equal to the config file \'%s\'. Please fix your configuration.'] = 'Friendican nykyinen system.basepath "%s" ei vastaa konfigurointitiedostoa "%s". Korjaa konfiguraatiosi.';
$a->strings['Message queues'] = 'Viestijonot';
$a->strings['Server Settings'] = 'Palvelimen asetukset';
$a->strings['Version'] = 'Versio';
$a->strings['Active addons'] = 'Käytössäolevat lisäosat';
$a->strings['Enable new addons'] = 'Ota käyttöön uusia lisäosia';
$a->strings['Theme %s disabled.'] = 'Teema %s pois käytöstä.';
$a->strings['Theme %s successfully enabled.'] = 'Teema %s poistettu käytöstä onnistuneesti.';
$a->strings['Theme %s failed to install.'] = 'Teeman %s asennus epäonnistui.';
$a->strings['Screenshot'] = 'Kuvakaappaus';
$a->strings['Themes'] = 'Teemat';
$a->strings['Unknown theme.'] = 'Tuntematon teema.';
$a->strings['Themes reloaded'] = 'Teemat ladattu uudelleen';
$a->strings['Reload active themes'] = 'Lataa aktiiviset teemat uudelleen';
$a->strings['No themes found on the system. They should be placed in %1$s'] = 'Teemoja ei löytynyt järjestelmästä. Teemat tulisi laittaa kansioon %1$s';
$a->strings['[Experimental]'] = '[Kokeellinen]';
$a->strings['[Unsupported]'] = '[Ei tueta]';
$a->strings['Display Terms of Service'] = 'Näytä käyttöehdot';
$a->strings['Enable the Terms of Service page. If this is enabled a link to the terms will be added to the registration form and the general information page.'] = 'Ota käyttöön käyttöehdot-sivu. Jos tämä on otettu käyttöön, linkki käyttöehtoihin lisätään rekisteröitymislomakkeelle ja yleistä tietoa -sivulle.';
$a->strings['Display Privacy Statement'] = 'Näytä tietosuojalausunto';
$a->strings['Show some informations regarding the needed information to operate the node according e.g. to <a href="%s" target="_blank" rel="noopener noreferrer">EU-GDPR</a>.'] = 'Näytä tarvittavat tiedot liittyen solmun toimintaa varten, niin että se vastaa esimerkiksi <a href="%s" target="_blank" rel="noopener noreferrer">EU-GDPR</a>-vaatimuksiin.';
$a->strings['Privacy Statement Preview'] = 'Tietosuojaselosteen esikatselu';
$a->strings['The Terms of Service'] = 'Käyttöehdot';
$a->strings['Enter the Terms of Service for your node here. You can use BBCode. Headers of sections should be [h2] and below.'] = 'Syötä solmusi käyttöehdot tähän. Voit käyttää BBCodea. Osioiden otsikoiden tason tulisi olla [h2] tai sitä alempaa tasoa.';
$a->strings['The rules'] = 'Säännöt';
$a->strings['Enter your system rules here. Each line represents one rule.'] = 'Syötä järjestelmän säännöt tähän. Laita kukin sääntö omalle rivilleen.';
$a->strings['API endpoint %s %s is not implemented but might be in the future.'] = 'API päätepistettä %s %s ei ole otettu käyttöön mutta niin saatetaan tehdä tulevaisuudessa.';
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
$a->strings['Submanaged account can\'t access the administration pages. Please log back in as the main account.'] = 'Alitilillä ei voi käyttää ylläpidon sivuja. Kirjaudu uudelleen sisään päätilillä.';
$a->strings['Overview'] = 'Yleiskatsaus';
$a->strings['Configuration'] = 'Kokoonpano';
$a->strings['Additional features'] = 'Lisäominaisuuksia';
$a->strings['Database'] = 'Tietokanta';
$a->strings['DB updates'] = 'Tietokannan päivitykset';
$a->strings['Inspect Deferred Workers'] = 'Tarkista lykätyt työntekijät';
$a->strings['Inspect worker Queue'] = 'Tarkista työntekijän työjono';
$a->strings['Logs'] = 'Lokit';
$a->strings['View'] = 'Katso';
$a->strings['Diagnostics'] = 'Diagnostiikka';
$a->strings['PHP Info'] = 'PHP tietoja';
$a->strings['probe address'] = 'luotausosoite';
$a->strings['check webfinger'] = 'Tarkista webfinger';
$a->strings['Babel'] = 'Babel';
$a->strings['ActivityPub Conversion'] = 'ActivityPub-konversio';
$a->strings['Addon settings'] = 'Lisäosa-asetukset';
$a->strings['User registrations waiting for confirmation'] = 'Käyttäjärekisteröinnit odottavat hyväksyntää';
$a->strings['Too Many Requests'] = 'Liian monta pyyntöä';
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
$a->strings['You don\'t have access to moderation pages.'] = 'Sinulla ei ole pääsyä moderointisivuille.';
$a->strings['Submanaged account can\'t access the moderation pages. Please log back in as the main account.'] = 'Alitilillä ei voi käyttää moderointisivuja. Kirjaudu uudelleen sisään päätilillä.';
$a->strings['Reports'] = 'Raportit';
$a->strings['Users'] = 'Käyttäjät';
$a->strings['Tools'] = 'Työkalut';
$a->strings['Contact Blocklist'] = 'Kontaktien estolista';
$a->strings['Server Blocklist'] = 'Palvelimien estolista';
$a->strings['Delete Item'] = 'Poista kohde';
$a->strings['Item Source'] = 'Kohteen lähde';
$a->strings['Profile Details'] = 'Profiilitiedot';
$a->strings['Conversations started'] = 'Aloitetut keskustelut';
$a->strings['Media'] = 'Media';
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
$a->strings['This page is missing a url parameter.'] = 'Tältä sivulta puuttuu URL-parametri.';
$a->strings['The post was created'] = 'Julkaisu luotu';
$a->strings['Invalid Request'] = 'Epäkelpo pyyntö';
$a->strings['Event id is missing.'] = 'Tapahtuman tunniste puuttuu.';
$a->strings['Failed to remove event'] = 'Tapahtuman poisto epäonnistui';
$a->strings['Event can not end before it has started.'] = 'Tapahtuma ei voi päättyä ennen kuin on alkanut.';
$a->strings['Event title and start time are required.'] = 'Tapahtuman nimi ja alkamisaika vaaditaan.';
$a->strings['Create event'] = 'Luo tapahtuma';
$a->strings['Formatting is not supported for this field'] = 'Muotoiluja ei tueta tässä kentässä';
$a->strings['Edit event'] = 'Muokkaa tapahtumaa';
$a->strings['New event'] = 'Uusi tapahtuma';
$a->strings['Ends'] = 'Päättyy';
$a->strings['Back to the calendar'] = 'Takaisin kalenteriin';
$a->strings['Starting date and Title are required.'] = 'Aloituspvm ja otsikko vaaditaan.';
$a->strings['Starts'] = 'Alkaa';
$a->strings['Required'] = 'Vaaditaan';
$a->strings['When does it happen?'] = 'Milloin se tapahtuu?';
$a->strings['Further details'] = 'Lisätietoja';
$a->strings['Don\'t specify ending'] = 'Älä määritä päättymistä';
$a->strings['Title'] = 'Otsikko';
$a->strings['Location'] = 'Sijainti';
$a->strings['Where does it happen?'] = 'Missä se tapahtuu?';
$a->strings['Description'] = 'Kuvaus';
$a->strings['What are the details?'] = 'Mitkä ovat tarkemmat tiedot?';
$a->strings['Event title'] = 'Tapahtuman otsikko';
$a->strings['Share this event'] = 'Jaa tämä tapahtuma';
$a->strings['Basic'] = 'Perus';
$a->strings['This calendar format is not supported'] = 'Tätä kalenteriformaattia ei tueta';
$a->strings['No exportable data found'] = 'Vientikelpoista dataa ei löytynyt';
$a->strings['calendar'] = 'kalenteri';
$a->strings['New Event'] = 'Uusi tapahtuma';
$a->strings['list'] = 'luettelo';
$a->strings['Could not create circle.'] = 'Piiriä ei voitu luoda.';
$a->strings['Circle not found.'] = 'Piiriä ei löytynyt.';
$a->strings['Circle name was not changed.'] = 'Piirin nimeä ei muutettu.';
$a->strings['Unknown circle.'] = 'Tuntematon piiri.';
$a->strings['Contact not found.'] = 'Kontaktia ei löydy.';
$a->strings['Invalid contact.'] = 'Epäkelpo kontakti.';
$a->strings['Contact is deleted.'] = 'Kontakti on poistettu';
$a->strings['Unable to add the contact to the circle.'] = 'Kontaktia ei voida lisätä piiriin.';
$a->strings['Contact successfully added to circle.'] = 'Kontakti lisätty piiriin onnistuneesti.';
$a->strings['Unable to remove the contact from the circle.'] = 'Kontaktia ei voitu poistaa piiristä.';
$a->strings['Contact successfully removed from circle.'] = 'Kontakti poistettu piiristä onnistuneesti.';
$a->strings['Bad request.'] = 'Virheellinen pyyntö.';
$a->strings['Save Circle'] = 'Tallenna piiri';
$a->strings['Filter'] = 'Suodatin';
$a->strings['Create a circle of contacts/friends.'] = 'Luo kontakti-/kaveripiiri';
$a->strings['Unable to remove circle.'] = 'Piiriä ei voida poistaa.';
$a->strings['Delete Circle'] = 'Poista piiri';
$a->strings['Edit Circle Name'] = 'Muokkaa piirin nimeä';
$a->strings['Mark all as read'] = 'Merkitse kaikki luetuiksi';
$a->strings['Members'] = 'Jäsenet';
$a->strings['Circle is empty'] = 'Piiri on tyhjä';
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
$a->strings['Ignored'] = 'Sivuutettu';
$a->strings['Only show ignored contacts'] = 'Näytä vain kontaktit, jotka on sivuutettu';
$a->strings['Collapsed'] = 'Piiloon laskostettu';
$a->strings['Only show collapsed contacts'] = 'Näytä vain piiloon taitetut kontaktit';
$a->strings['Archived'] = 'Arkistoitu';
$a->strings['Only show archived contacts'] = 'Näytä vain arkistoidut kontaktit';
$a->strings['Hidden'] = 'Piilotettu';
$a->strings['Only show hidden contacts'] = 'Näytä vain piilotetut kontaktit';
$a->strings['Organize your contact circles'] = 'Järjestä kontaktipiirejäsi';
$a->strings['Search your contacts'] = 'Etsi kontakteistasi';
$a->strings['Results for: %s'] = 'Haun "%s" tulokset';
$a->strings['Update'] = 'Päivitä';
$a->strings['Block'] = 'Estä';
$a->strings['Unblock'] = 'Salli';
$a->strings['Ignore'] = 'Jätä huomiotta';
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
$a->strings['This is you'] = 'Tämä olet sinä';
$a->strings['Visit %s\'s profile [%s]'] = 'Näytä %s-käyttäjän profiili [%s]';
$a->strings['Contact update failed.'] = 'Kontaktipäivitys epäonnistui.';
$a->strings['Return to contact editor'] = 'Palaa kontaktin muokkaamiseen';
$a->strings['Submit'] = 'Lähetä';
$a->strings['Name'] = 'Nimi';
$a->strings['Account Nickname'] = 'Tilin lempinimi';
$a->strings['Account URL'] = 'Tilin URL-osoite';
$a->strings['Poll/Feed URL'] = 'URL kyselyitä/syötteitä varten';
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
$a->strings['These contacts both follow and are followed by <strong>%s</strong>.'] = 'Nämä kontaktit seuraavat ja ovat <strong>%s</strong>:n seuraamia.';
$a->strings['Common contact (%s)'] = [
	0 => 'Yhteinen kontakti (%s)',
	1 => 'Yhteiset kontaktit (%s)',
];
$a->strings['Both <strong>%s</strong> and yourself have publicly interacted with these contacts (follow, comment or likes on public posts).'] = 'Sekä <strong>%s</strong> että sinä itse olette olleet julkisesti vuorovaikutuksessa näiden kontaktien kanssa (seuranta, kommentit tai tykkäykset julkisissa julkaisuissa).';
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
$a->strings['%s knows you'] = '%s tuntee sinut';
$a->strings['Add a personal note:'] = 'Lisää yksityismuistiinpano:';
$a->strings['Posts and Replies'] = 'Julkaisut ja vastaukset';
$a->strings['The contact could not be added.'] = 'Kontaktia ei voitu lisätä.';
$a->strings['Invalid request.'] = 'Epäkelpo pyyntö.';
$a->strings['Profile Match'] = 'Vastaavien profiilien haku';
$a->strings['No keywords to match. Please add keywords to your profile.'] = 'Ei vastaavia avainsanoja. Lisää avainsanoja profiiliisi.';
$a->strings['No matches'] = 'Ei osumia';
$a->strings['Failed to update contact record.'] = 'Kontaktitietojen päivitys epäonnistui.';
$a->strings['Contact has been unblocked'] = 'Henkilö on ei ole enää estetty';
$a->strings['Contact has been blocked'] = 'Henkilö on estetty';
$a->strings['Contact has been unignored'] = 'Kontakti ei ole enää sivuutettu';
$a->strings['Contact has been ignored'] = 'Kontakti on siivuutettu';
$a->strings['Contact has been uncollapsed'] = 'Kontakti ei ole enää laskostettu piiloon';
$a->strings['Contact has been collapsed'] = 'Kontakti on laskostettu piiloon';
$a->strings['Connection:'] = 'Yhteys:';
$a->strings['Private communications are not available for this contact.'] = 'Yksityiskeskustelu ei ole käytettävissä tälle henkilölle.';
$a->strings['This contact is on a server you ignored.'] = 'Tämä kontakti on palvelimella, joka olet sivuuttanut.';
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
$a->strings['Currently ignored'] = 'Tällä hetkellä sivuutettu';
$a->strings['Currently collapsed'] = 'Laskostettu piiloon tällä hetkellä';
$a->strings['Currently archived'] = 'Arkistoitu tällä hetkellä';
$a->strings['Manage remote servers'] = 'Hallinnoi etäpalvelimia';
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
$a->strings['Frequency of this contact in relevant channels'] = 'Tämän kontaktin esiintymistiheys liittyvillä kanavilla';
$a->strings['Depending on the type of the channel not all posts from this contact are displayed. By default, posts need to have a minimum amount of interactions (comments, likes) to show in your channels. On the other hand there can be contacts who flood the channel, so you might want to see only some of their posts. Or you don\'t want to see their content at all, but you don\'t want to block or hide the contact completely.'] = 'Kaikkia tämän kontaktin julkaisuja ei näytetä kanavatyypistä riippuen. Oletuksena julkaisuilla pitää olla vähimmäismäärä vuorovaikutusta (kommentteja, tykkäyksiä), jotta ne näkyvät kanavillasi. Toisaalta, osa kontakteista saattaa saada kanavan tulvimaan, joten saatat haluta nähdä vain osan heidän julkaisuistaan. Tai et ehkä halua nähdä heidän sisältöään lainkaan, mutta et halua estää tai piilottaa kontaktia täysin.';
$a->strings['Default frequency'] = 'Oletusesiintymistiheys';
$a->strings['Posts by this contact are displayed in the "for you" channel if you interact often with this contact or if a post reached some level of interaction.'] = 'Tämän kontaktin julkaisut näkyvät Sinulle-kanavalla, jos olet usein vuorovaikutuksessa tämän kontaktin kanssa tai jos julkaisu on saavuttanut tietyn vuorovaikutustason.';
$a->strings['Display all posts of this contact'] = 'Näytä tämän kontaktin kaikki julkaisut';
$a->strings['All posts from this contact will appear on the "for you" channel'] = 'Kaikki tämän kontaktin julkaisut näkyvät Sinulle-kanavalla';
$a->strings['Display only few posts'] = 'Näytä vain muutamia julkaisuja';
$a->strings['When a contact creates a lot of posts in a short period, this setting reduces the number of displayed posts in every channel.'] = 'Kun kontakti luo paljon julkaisuja lyhyessä ajassa, tämä asetu vähentää sinulle näytettävien julkaisujen määrää jokaisessa kanavassa.';
$a->strings['Never display posts'] = 'Älä näytä julkaisuja ikinä';
$a->strings['Posts from this contact will never be displayed in any channel'] = 'Tämän kontaktin julkaisuja ei koskaan näytetä millään kanavalla.';
$a->strings['Channel Only'] = 'Vain kanava';
$a->strings['If enabled, posts from this contact will only appear in channels and network streams in circles, but not in the general network stream.'] = 'Kun käytössä, tämän kontaktin julkaisut näkyvät vain kanavissa ja piirien verkostovirroissa, mutta eivät yleisessä verkostovirrassa.';
$a->strings['Refetch contact data'] = 'Nouda kontantin tiedot uudelleen';
$a->strings['Fetch latest posts'] = 'Nouda viimeisimmät julkaisut';
$a->strings['Toggle Blocked status'] = 'Vaihtele estetty-tilaa';
$a->strings['Toggle Ignored status'] = 'Vaihtele sivuuta-tilaa';
$a->strings['Toggle Collapsed status'] = 'Vaihtele laskostettu-tilaa';
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
$a->strings['This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.'] = 'Tämä yhteisövirta näyttää kaikki julkiset julkaisut, jotka tämä solmu on vastaanottanut. Ne eivät välttämättä vastaa tämän solmun käyttäjien mielipiteitä.';
$a->strings['Community option not available.'] = 'Yhteisö vaihtoehto ei saatavilla.';
$a->strings['Not available.'] = 'Ei saatavilla.';
$a->strings['No such circle'] = 'Piiriä ei ole olemassa';
$a->strings['Circle: %s'] = 'Piiri: %s';
$a->strings['Error %d (%s) while fetching the timeline.'] = 'Virhe %d (%s) aikajanaa noudettaessa.';
$a->strings['Network feed not available.'] = 'Verkostosyöte ei saatavilla.';
$a->strings['Include'] = 'Sisällytä';
$a->strings['Hide'] = 'Piilota';
$a->strings['Credits'] = 'Tunnustukset';
$a->strings['Friendica is a community project, that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!'] = 'Friendica on yhteisöprojekti, joka ei olisi mahdollinen ilman monien ihmisten apua. Tässä on luettelo niistä, jotka ovat antaneet työpanoksensa Friendican koodin tai käännöksen kehittämiseen. Kiitos teille kaikille!';
$a->strings['Formatted'] = 'Muotoiltu';
$a->strings['Activity'] = 'Toiminta';
$a->strings['Object data'] = 'Objektin tiedot';
$a->strings['Result Item'] = 'Tuloskohde';
$a->strings['Error'] = [
	0 => 'Virhe',
	1 => 'Virheet',
];
$a->strings['Source activity'] = 'Lähdetapahtuma';
$a->strings['Babel Diagnostic'] = 'Babel-diagnostiikka';
$a->strings['Twitter Source / Tweet URL (requires API key)'] = 'Twitter-lähde/Twitter-URL (vaatii API-avaimen)';
$a->strings['You must be logged in to use this module'] = 'Sinun pitää kirjautua sisään, jotta voit käyttää tätä moduulia';
$a->strings['Source URL'] = 'Lähde URL';
$a->strings['l F d, Y \@ g:i A'] = 'l j.n.Y \@ H:i';
$a->strings['Time Conversion'] = 'Aikamuunnos';
$a->strings['Friendica provides this service for sharing events with other networks and friends in unknown timezones.'] = 'Friendica tarjoaa tämän palvelun tapahtumien jakamiseen muiden verkostojen ja tuntemattomilla aikavyöhykkeillä olevien ystävien kanssa.';
$a->strings['UTC time: %s'] = 'UTC-aika: %s';
$a->strings['Current timezone: %s'] = 'Aikavyöhyke: %s';
$a->strings['Converted localtime: %s'] = 'Muunnettu paikallisaika: %s';
$a->strings['Please select your timezone:'] = 'Valitse aikavyöhykkeesi:';
$a->strings['Only logged in users are permitted to perform a probing.'] = 'Vain kirjautuneet käyttäjät voivat suorittaa luotauksen.';
$a->strings['Probe Diagnostic'] = 'Luotausdiagnostiikka';
$a->strings['Output'] = 'Tuloste';
$a->strings['Lookup address'] = 'Hakuosoite';
$a->strings['Webfinger Diagnostic'] = 'Webfinger-diagnostiikka';
$a->strings['Lookup address:'] = 'Hakuosoite:';
$a->strings['Find on this site'] = 'Etsi tältä sivulta';
$a->strings['No accounts found (some may be hidden).'] = 'Tilejä ei löytynyt (jotkin niistä saattavat olla piilotettuja).';
$a->strings['Results for:'] = 'Tulokset haulla:';
$a->strings['Site Directory'] = 'Sivuston luettelo';
$a->strings['Accounts listed: %s'] = 'Luetteloidut tilit: %s';
$a->strings['Item was not deleted'] = 'Kohdetta ei poistettu';
$a->strings['Item was not removed'] = 'Kohdetta ei poistettu';
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
$a->strings['Download this list in CSV format'] = 'Lataa tämä luettelo CSV-muodossa';
$a->strings['This is Friendica, version %s that is running at the web location %s. The database version is %s, the post update version is %s.'] = 'Tämä on Friendica, versio %s, joka toimii verkkosijainnissa %s. Tietokannan versio on %s ja julkaisupäivitysversio on %s.';
$a->strings['Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.'] = 'Vieraile osoitteessa <a href="https://friendi.ca">Friendi.ca</a> saadaksesi lisätietoja Friendica- projektista.';
$a->strings['Bug reports and issues: please visit'] = 'Bugiraportit ja kysymykset: vieraile osoitteessa';
$a->strings['the bugtracker at github'] = 'githubin bugtrackeri';
$a->strings['Suggestions, praise, etc. - please email "info" at "friendi - dot - ca'] = 'Ehdotukset ja palaute: lähetä sähköposti osoitteeseen "info" at "friendi - piste - ca';
$a->strings['No profile'] = 'Ei profiilia';
$a->strings['Method Not Allowed.'] = 'Metodi ei ole sallittu.';
$a->strings['Help:'] = 'Ohje:';
$a->strings['Welcome to %s'] = 'Tervetuloa %s';
$a->strings['Friendica Communications Server - Setup'] = 'Friendica Communications -palvelimen asetus';
$a->strings['System check'] = 'Järjestelmän tarkistus';
$a->strings['Requirement not satisfied'] = 'Vaatimus ei täyty';
$a->strings['Optional requirement not satisfied'] = 'Valinnainen vaatimus ei täyty';
$a->strings['OK'] = 'OK';
$a->strings['Next'] = 'Seuraava';
$a->strings['Check again'] = 'Tarkista uudelleen';
$a->strings['Base settings'] = 'Perusasetukset';
$a->strings['Base path to installation'] = 'Asennuksen peruspolku';
$a->strings['If the system cannot detect the correct path to your installation, enter the correct path here. This setting should only be set if you are using a restricted system and symbolic links to your webroot.'] = 'Jos järjestelmä ei löydä oikeaa polkua asennukseesi, kirjoita oikea polku tähän. Tämä asetus tulee määrittää vain, jos käytät rajoitettua järjestelmää ja symbolisia linkkejä web-juurihakemistoosi.';
$a->strings['The Friendica system URL'] = 'Friendica-järjestelmän URL';
$a->strings['Overwrite this field in case the system URL determination isn\'t right, otherwise leave it as is.'] = 'Korvaa tämä kenttä, jos järjestelmän URL-osoitteen määrittäminen ei ole oikein, muuten jätä se ennalleen.';
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
$a->strings['Go to your new Friendica node <a href="%s/register">registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.'] = 'Siirry uuden Friendica-solmun <a href="%s/register">rekisteröintisivulle</a> rekisteröidy uutena käyttäjänä. Muista käyttää samaa sähköpostiosoitetta, jonka olet antanut ylläpitäjän sähköpostiosoitteeksi. Näin pääset sivuston hallintapaneeliin.';
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
$a->strings['Visit %s for a list of public sites that you can join. Friendica members on other sites can all connect with each other, as well as with members of many other social networks.'] = 'Käy osoitteessa %s, josta löydät luettelon julkisista sivustoista, joihin voit liittyä. Friendican jäsenet muilla sivustoilla voivat olla yhteydessä toisiinsa sekä monien muiden sosiaalisten verkostojen jäseniin.';
$a->strings['To accept this invitation, please visit and register at %s or any other public Friendica website.'] = 'Hyväksyäksesi tämän kutsun, rekisteröidy sivustolla %s tai millä tahansa muulla Friendica -sivustolla.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks. See %s for a list of alternate Friendica sites you can join.'] = 'Friendica-sivustot ovat kaikki yhteydessä toisiinsa ja muodostavat valtavan, yksityisyyttä parantavan sosiaalisen verkoston, jonka omistavat ja hallitsevat sen jäsenet. Ne voivat myös olla yhteydessä moniin perinteisiin sosiaalisiin verkostoihin. Käy katsomassa täältä %s luetteloa vaihtoehtoisista Friendica-sivustoista, joihin voit liittyä.';
$a->strings['Our apologies. This system is not currently configured to connect with other public sites or invite members.'] = 'Pahoittelemme. Tämä järjestelmä ei ole tällä hetkellä konfiguroitu yhdistämään muihin julkisiin sivustoihin tai kutsumaan jäseniä.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks.'] = 'Friendica-sivustot ovat kaikki yhteydessä toisiinsa ja muodostavat valtavan, yksityisyyttä parantavan sosiaalisen verkoston, jonka omistavat ja hallitsevat sen jäsenet. Ne voivat myös olla yhteydessä moniin perinteisiin sosiaalisiin verkostoihin.';
$a->strings['To accept this invitation, please visit and register at %s.'] = 'Hyväksyäksesi tämän kutsun, rekisteröidy sivustolla  %s.';
$a->strings['Send invitations'] = 'Lähetä kutsut';
$a->strings['Enter email addresses, one per line:'] = 'Syötä sähköpostiosoitteet, yksi riviä kohden:';
$a->strings['You are cordially invited to join me and other close friends on Friendica - and help us to create a better social web.'] = 'Olet sydämellisesti tervetullut liittymään minun ja muiden läheisten ystävien seuraan Friendicassa – ja auttamaan meitä luomaan yhdessä paremman sosiaalisen verkon.';
$a->strings['You will need to supply this invitation code: $invite_code'] = 'Sinun täytyy antaa tämä kutsukoodi: $invite_code';
$a->strings['Once you have registered, please connect with me via my profile page at:'] = 'Kun olet rekisteröitynyt, lähetä minulle kaverikutsu profiilisivuni kautta:';
$a->strings['For more information about the Friendica project and why we feel it is important, please visit http://friendi.ca'] = 'Lisätietoja Friendica-projektista ja siitä, miksi pidämme sitä tärkeänä, löytyy osoitteesta http://friendi.ca.';
$a->strings['Please enter a post body.'] = 'Syötä julkaisun leipäteksti.';
$a->strings['This feature is only available with the frio theme.'] = 'Tämä ominaisuus on saatavilla vain frio-teemassa.';
$a->strings['Compose new personal note'] = 'Kirjoita uusi yksityismuistiinpano';
$a->strings['Compose new post'] = 'Kirjoita uusi julkaisu';
$a->strings['Visibility'] = 'Näkyvyys';
$a->strings['Clear the location'] = 'Tyhjennä sijainti';
$a->strings['Location services are unavailable on your device'] = 'Laitteesi sijaintipalvelut eivät ole käytettävissä';
$a->strings['Location services are disabled. Please check the website\'s permissions on your device'] = 'Sijaintipalvelut eivät ole käytössä. Tarkista laitteeltasi sivuston käyttöoikeudet';
$a->strings['If you want to always use this editor for posting, you can configure the New Post button to always open it in your <a href="/settings/display">Theme settings</a>.'] = 'Jos julkaisemiseen haluaa aina käyttää tätä muokkainta, <a href="/settings/display">teeman asetuksista</a> Uusi julkaisu -painikkeen voi asettaa avaamaan sen.';
$a->strings['Post by %s'] = 'Käyttäjän %s julkaisut';
$a->strings['The feed for this item is unavailable.'] = 'Tämän kohteen syöte ei ole käytettävissä.';
$a->strings['Unable to follow this item.'] = 'Tätä kohdetta ei voi seurata.';
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
			Hei %1$s
				Osoitteessa "%2$s" tehtiin äskettäin pyyntö nollata tilisi salasana.
			Vahvistaaksesi tämän pyynnön, klikkaa alla olevaa vahvistuslinkkiä 
			tai liitä se selaimesi osoiteriville.

			Jos ET ole pyytänyt tätä muutosta, ÄLÄ seuraa annettua linkkiä
			ja jätä tämä sähköposti huomiotta ja/tai poista se. Pyyntö vanhenee pian.

			Salasanaasi ei muuteta, ellei voida varmistaa, että 
			pyyntö on tullut sinulta.';
$a->strings['
			Follow this link soon to verify your identity:

			%1$s

			You will then receive a follow-up message containing the new password.
			You may change that password from your account settings page after logging in.

			The login details are as follows:

			Site Location:	%2$s
			Login Name:	%3$s'] = '
			Seuraa tätä linkkiä pian vahvistaaksesi henkilöllisyytesi:

			%1$s

			Saat sitten seurantaviestin, joka sisältää uuden salasanan.
			Voit muuttaa salasanan tilisi asetussivulla kirjautumisen jälkeen.

			Kirjautumistiedot ovat seuraavat:

			Sivuston sijainti: 	%2$s
			Kirjautumistunnus:	%3$s';
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
				Hyvä %1$s,
					Salasanasi on vaihdettu pyynnöstäsi. Säilytäthän nämä
				tiedot myöhempää käyttöä varten (tai vaihdathan salasanasi välittömästi
				sellaiseksi, että muistat sen).
			';
$a->strings['
				Your login details are as follows:

				Site Location:	%1$s
				Login Name:	%2$s
				Password:	%3$s

				You may change that password from your account settings page after logging in.
			'] = '
				Sisäänkirjautumistietosi ovat seuraavanlaiset:

				Sivuston sijainti:	%1$s
				Sisäänkirjautumisnimi:	%2$s
				Salasana:	%3$s

				Voit vaihtaa salasanasi tilisi asetukset-sivulla kun olet kirjautunut sisään.
			';
$a->strings['Your password has been changed at %s'] = 'Salasanasi on vaihdettu sivustolla %s';
$a->strings['System down for maintenance'] = 'Järjestelmä poissa käytöstä huollon takia';
$a->strings['This Friendica node is currently in maintenance mode, either automatically because it is self-updating or manually by the node administrator. This condition should be temporary, please come back in a few minutes.'] = 'Tämä Friendica-solmu on tällä hetkellä huollossa joko automaattisesti – koska se päivittyy itsestään – tai solmun ylläpitäjän asettamana. Tilanne on väliaikainen. Palaa muutaman minuutin kuluttua.';
$a->strings['A Decentralized Social Network'] = 'Hajautettu sosiaalinen verkko';
$a->strings['You need to be logged in to access this page.'] = 'Tämän sivun käyttöön vaaditaan kirjautuminen.';
$a->strings['Files'] = 'Tiedostot';
$a->strings['Upload'] = 'Lähetä';
$a->strings['Breadcrumb'] = 'Leivänmuru';
$a->strings['Switch between photo and attachment mode'] = 'Vaihda valokuva- ja liitetilan välillä';
$a->strings['Album navigation'] = 'Albumin navigointi';
$a->strings['Browser content'] = 'Selainsisältö';
$a->strings['Sorry, maybe your upload is bigger than the PHP configuration allows'] = 'Lähetyksesi vaikuttaa olevan suurempi kuin mitä PHP-asetus sallii';
$a->strings['Or - did you try to upload an empty file?'] = 'Yrititkö ladata tyhjän tiedoston?';
$a->strings['File exceeds size limit of %s'] = 'Tiedosto ylittää kokorajoituksen %s';
$a->strings['File upload failed.'] = 'Tiedoston lähettäminen epäonnistui.';
$a->strings['Unable to process image.'] = 'Kuvan käsitteleminen epäonnistui.';
$a->strings['Image upload failed.'] = 'Kuvan lähettäminen epäonnistui.';
$a->strings['List of all users'] = 'Kaikkien käyttäjien luettelo';
$a->strings['Active'] = 'Aktiivinen';
$a->strings['List of active accounts'] = 'Aktiivisten tilien luettelo';
$a->strings['List of pending registrations'] = 'Odottavien rekisteröintien luettelo';
$a->strings['List of blocked users'] = 'Estettyjen käyttäjien luettelo';
$a->strings['Deleted'] = 'Poistettu';
$a->strings['List of pending user deletions'] = 'Odottavien käyttäjäpoistojen luettelo';
$a->strings['Normal Account Page'] = 'Tavallinen käyttäjätili';
$a->strings['Soapbox Page'] = 'Saarnatuolisivu';
$a->strings['Public Group'] = 'Julkinen ryhmä';
$a->strings['Public Group - Restricted'] = 'Julkinen ryhmä – rajoitettu';
$a->strings['Automatic Friend Page'] = 'Automaattinen kaverisivu';
$a->strings['Private Group'] = 'Yksityinen ryhmä';
$a->strings['Personal Page'] = 'Henkilökohtainen sivu';
$a->strings['Organisation Page'] = 'Järjestön sivu';
$a->strings['News Page'] = 'Uutissivu';
$a->strings['Community Group'] = 'Yhteisöryhmä';
$a->strings['You can\'t block a local contact, please block the user instead'] = 'Paikallista yhteystietoa ei voi estää: estä sen sijaan käyttäjä';
$a->strings['%s contact unblocked'] = [
	0 => '%s kontaktin esto poistettu',
	1 => '%s kontaktin esto poistettu',
];
$a->strings['Remote Contact Blocklist'] = 'Etäyhteystietojen estolista';
$a->strings['This page allows you to prevent any message from a remote contact to reach your node.'] = 'Tällä sivulla voi estää minkä tahansa etäyhteystiedolta solmuusi tulevan viestin';
$a->strings['Block Remote Contact'] = 'Estä etäyhteystieto';
$a->strings['Import/Export'] = 'Tuonti ja vienti';
$a->strings['Import from CSV file'] = 'Tuo CSV-tiedostosta';
$a->strings['Export to CSV file'] = 'Vie CSV-tiedostoksi';
$a->strings['select all'] = 'valitse kaikki';
$a->strings['select none'] = 'älä valitse mitään';
$a->strings['No remote contact is blocked from this node.'] = 'Tästä solmusta ei ole estetty yhtään etäyhteystietoa';
$a->strings['Blocked Remote Contacts'] = 'Estetyt etäyhteystiedot';
$a->strings['Block New Remote Contact'] = 'Estä uusi etäyhteystieto';
$a->strings['Photo'] = 'Valokuva';
$a->strings['Reason'] = 'Syy';
$a->strings['%s total blocked contact'] = [
	0 => 'Yhteensä %s estetty yhteystieto',
	1 => 'Yhteensä %s estettyä yhteystietoa',
];
$a->strings['URL of the remote contact to block.'] = 'Estettävän etäyhteystiedon verkko-osoite.';
$a->strings['Also purge contact'] = 'Siivoa myös yhteystieto';
$a->strings['Removes all content related to this contact from the node. Keeps the contact record. This action cannot be undone.'] = 'Poistaa solmusta kaiken tähän yhteystietoon liittyvän sisällön. Säilyttää yhteystietotietueen. Toimintoa ei voi kumota.';
$a->strings['Block Reason'] = 'Eston syy';
$a->strings['No valid contacts found in CSV file.'] = 'CSV-tiedostosta ei löytynyt kelvollisia yhteystietoja.';
$a->strings['Error importing contact file: %s'] = 'Virhe yhteystietotiedostoa tuotaessa: %s';
$a->strings['Error parsing contact data.'] = 'Virhe yhteystietodataa jäsennettäessä.';
$a->strings['%d contact blocking was queued.'] = [
	0 => '%d yhteystietoesto vietiin jonoon.',
	1 => '%d yhteystietoestoa vietiin jonoon.',
];
$a->strings['%d contact was skipped (empty URL).'] = [
	0 => 'Sivuutettu %d yhteystieto  (tyhjä verkko-osoite).',
	1 => 'Sivuutettu %d yhteystietoa (tyhjä verkko-osoite).',
];
$a->strings['← Return to the list'] = '← Takaisin luetteloon';
$a->strings['Import a Contact Blocklist'] = 'Tuo yhteystietoestoluettelo';
$a->strings['<p>Upload a CSV file with contact URLs and reasons for blocking.</p>'] = '<p>Lähetä palvelimeen CSV-tiedosto, jossa on yhteystietojen verkko-osoitteet sekä ja estojen syyt.</p>';
$a->strings['Upload file'] = 'Lähetä tiedosto';
$a->strings['Contacts to import'] = 'Tuotavat yhteystiedot';
$a->strings['Contact URL'] = 'Yhteystiedon verkko-osoite';
$a->strings['%d total contact'] = [
	0 => 'Yhteensä %d yhteystieto',
	1 => 'Yhteensä %d yhteystietoa',
];
$a->strings['Also purge contacts'] = 'Siivoa yhteystiedotkin';
$a->strings['Removes all content related to these contacts from the node. Keeps the contact records. This action cannot be undone.'] = 'Poistaa solmulta kaiken näihin yhteystietohin liittyvän sisällön. Säilyttää yhteystietotietueet. Toimintoa ei voi kumota.';
$a->strings['Contact blocklist CSV file'] = 'Yhteystietoestoluettelon CSV-tiedosto';
$a->strings['The file "%s" could not be opened for importing'] = 'Tiedostoa ”%s” ei voitu avata tuotavaksi';
$a->strings['Server domain pattern added to the blocklist.'] = 'Palvelinverkkoaluekuvio lisätty estoluetteloon.';
$a->strings['%s server scheduled to be purged.'] = [
	0 => '%s palvelin ajastettu siivottavaksi.',
	1 => '%s palvelinta ajastettu siivottavaksi.',
];
$a->strings['Block A New Server Domain Pattern'] = 'Estä uusi palvelinverkkoaluekuvio';
$a->strings['<p>The server domain pattern syntax is case-insensitive shell wildcard, comprising the following special characters:</p>
<ul>
	<li><code>*</code>: Any number of characters</li>
	<li><code>?</code>: Any single character</li>
</ul>'] = '<p>Palvelimen toimialueen mallin syntaksissa käytetään kirjainkokoa huomioimattomia komentotulkki-villikortteja, jotka koostuvat seuraavista erikoismerkeistä:</p>
<ul>
	<li><code>*</code>: Mikä tahansa merkkimäärä</li>
	<li><code>?</code>: Mikä tahansa yksittäinen merkki</li>
</ul>';
$a->strings['Check pattern'] = 'Tarkistuskaava';
$a->strings['Matching known servers'] = 'Tunnetut vastaavat palvelimet';
$a->strings['Server Name'] = 'Palvelimen nimi';
$a->strings['Server Domain'] = 'Palvelimen verkkotunnus';
$a->strings['Known Contacts'] = 'Tunnetut yhteystiedot';
$a->strings['%d known server'] = [
	0 => '%d tunnettu palvelin',
	1 => '%d tunnettua palvelinta',
];
$a->strings['Add pattern to the blocklist'] = 'Lisää kuvio estoluetteloon';
$a->strings['Server Domain Pattern'] = 'Palvelimen verkkotunnusmalli';
$a->strings['The domain pattern of the new server to add to the blocklist. Do not include the protocol.'] = 'Estoluetteloon lisättävän uuden palvelimen verkkoaluekuvio. Älä sisällytä yhteyskäytäntöä.';
$a->strings['Purge server'] = 'Siivoa palvelin';
$a->strings['Also purges all the locally stored content authored by the known contacts registered on that server. Keeps the contacts and the server records. This action cannot be undone.'] = [
	0 => 'Siivoaa pois myös kaiken paikallisesti tallennetun sisällön, joka on tälle palvelimelle rekisteröityneiden tunnettujen kontatien tekemää. Säilyttää kontaktien ja palvelimen tallenteet. Tätä toimintoa ei voi perua.',
	1 => 'Siivoaa pois myös kaiken paikallisesti tallennetun, tunnetusti näihin palvelimiin rekisteröityneiden tekijöiden sisällön. Säilyttää yhteystiedot ja palvelintietueet. Toimintoa ei voi kumota.',
];
$a->strings['Block reason'] = 'Estosyy';
$a->strings['The reason why you blocked this server domain pattern. This reason will be shown publicly in the server information page.'] = 'Syy, miksi estät tämän palvelinverkkoaluekuvion. Syy näytetään julkisesti palvelintietosivulla.';
$a->strings['Error importing pattern file'] = 'Virhe kuviotiedostoa tuotaessa';
$a->strings['Local blocklist replaced with the provided file.'] = 'Annettu tiedosto korvasi paikallisen estoluettelon.';
$a->strings['%d pattern was added to the local blocklist.'] = [
	0 => 'Paikalliseen estoluetteloon lisättiin %d kuvio.',
	1 => 'Paikalliseen estoluetteloon lisättiin %d kuviota.',
];
$a->strings['No pattern was added to the local blocklist.'] = 'Paikalliseen estoluetteloon ei lisätty kuvioita.';
$a->strings['Import a Server Domain Pattern Blocklist'] = 'Tuo palvelinverkkoaluekuvioiden estoluettelo';
$a->strings['<p>This file can be downloaded from the <code>/friendica</code> path of any Friendica server.</p>'] = '<p>Tämän tiedoston voi ladata <code>/friendica</code> -polusta miltä tahansa Friendica-palvelimelta.</p>';
$a->strings['Patterns to import'] = 'Tuotavat kuviot';
$a->strings['Domain Pattern'] = 'Verkkotunnuskaava';
$a->strings['Import Mode'] = 'Tuontitila';
$a->strings['Import Patterns'] = 'Tuontikaavat';
$a->strings['%d total pattern'] = [
	0 => '%d kaava yhteensä',
	1 => '%d kaavaa yhteensä',
];
$a->strings['Server domain pattern blocklist CSV file'] = 'Palvelimen toimialueen kaavojen estolista CSV-tiedostona';
$a->strings['Append'] = 'Lisää';
$a->strings['Imports patterns from the file that weren\'t already existing in the current blocklist.'] = 'Tuo tiedostosta kaavat, joita ei vielä ole nykyisessä estolistassa.';
$a->strings['Replace'] = 'Korvaa';
$a->strings['Replaces the current blocklist by the imported patterns.'] = 'Korvaa nykyinen estolista tuoduilla mallineilla.';
$a->strings['Blocked server domain pattern'] = 'Estetty palvelimen verkkotunnusmalli';
$a->strings['Delete server domain pattern'] = 'Poista palvelimen verkkotunnusmalli';
$a->strings['Check to delete this entry from the blocklist'] = 'Laita rasti poistaaksesi kohde estolistalta';
$a->strings['Server Domain Pattern Blocklist'] = 'Palvelimen verkkotunnusmallien estolista';
$a->strings['This page can be used to define a blocklist of server domain patterns from the federated network that are not allowed to interact with your node. For each domain pattern you should also provide the reason why you block it.'] = 'Tällä sivulla voit määritellä estolistan, joka sisältää liittoutuneen verkon palvelindomainien kaavat, joiden ei sallita olla vuorovaikutuksessa solmusi kanssa. Jokaiselle toimialueen kaavalle sinun tulee myös ilmoittaa syy, miksi estät sen.';
$a->strings['The list of blocked server domain patterns will be made publically available on the <a href="/friendica">/friendica</a> page so that your users and people investigating communication problems can find the reason easily.'] = 'Estettyjen palvelindomainien luettelo julkaistaan <a href="/friendica">/friendica</a>-sivulla, jotta käyttäjät ja viestintäongelmia tutkivat henkilöt voivat helposti selvittää syyn.';
$a->strings['Import server domain pattern blocklist'] = 'Tuo palvelimen toimialueen kaavojen estolista';
$a->strings['Add new entry to the blocklist'] = 'Lisää uusi merkintä estolistalle';
$a->strings['Save changes to the blocklist'] = 'Tallenna muutokset estolistaan';
$a->strings['Current Entries in the Blocklist'] = 'Nykyinen estolista';
$a->strings['Delete entry from the blocklist'] = 'Poista merkintä estolistalta';
$a->strings['Delete entry from the blocklist?'] = 'Poistetaanko merkintä estolistalta?';
$a->strings['Item marked for deletion.'] = 'Kohde merkitty poistettavaksi.';
$a->strings['Delete this Item'] = 'Poista tämä kohde';
$a->strings['On this page you can delete an item from your node. If the item is a top level posting, the entire thread will be deleted.'] = 'Tällä sivulla voit poistaa kohteen solmustasi. Jos kohde on ylätason julkaisu, koko ketju poistetaan.';
$a->strings['You need to know the GUID of the item. You can find it e.g. by looking at the display URL. The last part of http://example.com/display/123456 is the GUID, here 123456.'] = 'Sinun täytyy tietää kohteen GUID. Löydät sen esimerkiksi katsomalla näytettävää URL-osoitetta. Osoitteen http://example.com/display/123456 viimeinen osa on GUID, tässä tapauksessa 123456.';
$a->strings['GUID'] = 'GUID';
$a->strings['The GUID of the item you want to delete.'] = 'Poistettavan kohteen GUID.';
$a->strings['Item Id'] = 'Kohteen ID';
$a->strings['Item URI'] = 'Kohteen URI';
$a->strings['Terms'] = 'Termit';
$a->strings['Tag'] = 'Aihetunniste';
$a->strings['Type'] = 'Tyyppi';
$a->strings['Term'] = 'Termi';
$a->strings['URL'] = 'Verkko-osoite';
$a->strings['Implicit Mention'] = 'Epäsuora maininta';
$a->strings['Item not found'] = 'Kohdetta ei löytynyt';
$a->strings['No source recorded'] = 'Ei tallennettua lähdettä';
$a->strings['Please make sure the <code>debug.store_source</code> config key is set in <code>config/local.config.php</code> for future items to have sources.'] = 'Varmistathan, että <code>debug.store_source</code> konfigurointiavain on asetettu osoitteessa <code>config/local.config.php</code>, jotta tulevilla kohteilla on lähteet.';
$a->strings['Item Guid'] = 'Kohdeopas';
$a->strings['Contact not found or their server is already blocked on this node.'] = 'Kontaktia ei löydy tai palvelin on jo estetty tällä solmulla.';
$a->strings['Please login to access this page.'] = 'Kirjaudu sisään saadaksesi pääsyn tälle sivulle.';
$a->strings['Create Moderation Report'] = 'Luo moderointiraportti';
$a->strings['Pick Contact'] = 'Valitse kontakti';
$a->strings['Please enter below the contact address or profile URL you would like to create a moderation report about.'] = 'Syötä alle sen kontaktin osoite tai profiilin URL-osoite, josta haluat luoda moderointiraportin.';
$a->strings['Contact address/URL'] = 'Yhteystiedon (verkko-)osoite';
$a->strings['Pick Category'] = 'Valitse luokka';
$a->strings['Please pick below the category of your report.'] = 'Valitse alta raporttisi luokka.';
$a->strings['Spam'] = 'Roskasisältö';
$a->strings['This contact is publishing many repeated/overly long posts/replies or advertising their product/websites in otherwise irrelevant conversations.'] = 'Tämä kontakti julkaisee useita toistuvia/ylipitkiä julkaisuja/vastauksia tai mainostaa tuotteitaan/verkkosivujaan muutoin asiaan liittymättömissä keskusteluissa.';
$a->strings['Illegal Content'] = 'Laiton sisältö';
$a->strings['This contact is publishing content that is considered illegal in this node\'s hosting juridiction.'] = 'Tämä kontakti julkaisee sisältöä, joka on laitonta paikassa, jossa tämän solmun hosting-palvelu sijaitsee.';
$a->strings['Community Safety'] = 'Yhteisön turvallisuus';
$a->strings['This contact aggravated you or other people, by being provocative or insensitive, intentionally or not. This includes disclosing people\'s private information (doxxing), posting threats or offensive pictures in posts or replies.'] = 'Joku ärsytti sinua tai muita olemalla provosoiva tai välinpitämätön, tahallisesti tai tahattomasti. Tähän sisältyvät ihmisten henkilötietojen jakaminen sekä uhkausten tai loukkaavien kuvien julkaisu viesteissä tai vastauksissa.';
$a->strings['Unwanted Content/Behavior'] = 'Epätoivottu sisältö tai käytös';
$a->strings['This contact has repeatedly published content irrelevant to the node\'s theme or is openly criticizing the node\'s administration/moderation without directly engaging with the relevant people for example or repeatedly nitpicking on a sensitive topic.'] = 'Joku on toistuvasti julkaissut sisältöä, joka ei liity solmun aiheeseen tai kritisoi avoimesti solmun ylläpitoa tai moderointia, muttei kuitenkaan ole yhteydessä näihin, esimerkiksi toistuvasti jankuttamalla arkaluontoisesta aiheesta.';
$a->strings['Rules Violation'] = 'Sääntöjen rikkominen';
$a->strings['This contact violated one or more rules of this node. You will be able to pick which one(s) in the next step.'] = 'Joku on rikkonut yhtä tai useampaa solmun sääntöä. Rikotut säännöt voi valita seuraavassa vaiheessa.';
$a->strings['Please elaborate below why you submitted this report. The more details you provide, the better your report can be handled.'] = 'Kerro alla tarkemmin, miksi lähetät tämän raportin. Mitä enemmän yksityiskohtia kerrot, sitä paremmin raporttisi voidaan käsitellä.';
$a->strings['Additional Information'] = 'Lisätietoa';
$a->strings['Please provide any additional information relevant to this particular report. You will be able to attach posts by this contact in the next step, but any context is welcome.'] = 'Anna lisätietoja raporttiin liittyen. Seuraavassa vaiheessa voit liittää yhteystiedon julkaisuja, mutta mikä tahansa lisäkonteksti on tervetullutta.';
$a->strings['Pick Rules'] = 'Valitse säännöt';
$a->strings['Please pick below the node rules you believe this contact violated.'] = 'Valitse alta ne solmun säännöt, joita tämä yhteystieto on mielestäsi rikkonut.';
$a->strings['Pick Posts'] = 'Valitse julkaisut';
$a->strings['Please optionally pick posts to attach to your report.'] = 'Valinnaisesti valitse raporttiisi liitettäviä julkaisuja.';
$a->strings['Would you like to forward this report to the remote server?'] = 'Haluatko edentää tämän raportin etäpalvelimeen?';
$a->strings['Would you ike to forward this report to the remote server?'] = 'Haluatko välittää tämän raportin etäpalvelimelle?';
$a->strings['Submit Report'] = 'Lähetä raportti';
$a->strings['Further Action'] = 'Lisätoiminnot';
$a->strings['You can also perform one of the following action on the contact you reported:'] = 'Voit myös suorittaa seuraavat toimenpiteet yhteystiedolle, josta raportoit:';
$a->strings['Nothing'] = 'Ei mitään';
$a->strings['Collapse contact'] = 'Supista yhteystieto';
$a->strings['Their posts and replies will keep appearing in your Network page but their content will be collapsed by default.'] = 'Yhteystiedon julkaisut vastauksineen näkyvät Verkosto-sivulla, mutta niiden sisältö oletusarvoisesti supistetaan.';
$a->strings['Their posts won\'t appear in your Network page anymore, but their replies can appear in forum threads. They still can follow you.'] = 'Yhteystiedon julkaisut eivät enää näy Verkosto-sivulla, mutta vastaukset voivat näkyä foorumisäikeissä. Hän voi edelleen seurata sinua.';
$a->strings['Block contact'] = 'Estä yhteystieto';
$a->strings['Their posts won\'t appear in your Network page anymore, but their replies can appear in forum threads, with their content collapsed by default. They cannot follow you but still can have access to your public posts by other means.'] = 'Hänen julkaisunsa eivät enää ilmesty Verkosto-sivulllesi, mutta hänen vastauksensa voivat näkyä foorumiketjuissa, niin että hänen sisältönsä on oletuksena taiteltu piiloon. Hän ei voi seurata sinua, mutta hänellä on pääsy julkisiin julkaisuihisi muilla keinoin.';
$a->strings['Forward report'] = 'Edennä raportti';
$a->strings['1. Pick a contact'] = '1. Valitse yhteystieto';
$a->strings['2. Pick a category'] = '2. Valitse luokka';
$a->strings['2a. Pick rules'] = '2a. Valitse säännöt';
$a->strings['2b. Add comment'] = '2b. Lisää huomautus';
$a->strings['3. Pick posts'] = '3. Valitse julkaisuja';
$a->strings['List of reports'] = 'Raporttiluettelo';
$a->strings['This page display reports created by our or remote users.'] = 'Tällä sivulla näytetään raportit, joka ovat luoneet meidän käyttäjämme tai etäkäyttäjät.';
$a->strings['No report exists at this node.'] = 'Tässä solmussa ei ole raportteja.';
$a->strings['Comment'] = 'Kommentti';
$a->strings['Category'] = 'Luokka';
$a->strings['Select all'] = 'Valitse kaikki';
$a->strings['Close selected reports'] = 'Sulje valitut raportit';
$a->strings['%s total report'] = [
	0 => 'Yhteensä %s raportti',
	1 => 'Yhteensä %s raporttia',
];
$a->strings['URL of the reported contact.'] = 'Raportoidun kontaktin URL';
$a->strings['Channel Relay'] = 'Kanavavälitys';
$a->strings['Registered users'] = 'Rekisteröityneet käyttäjät';
$a->strings['Pending registrations'] = 'Vireillä olevat rekisteröinnit';
$a->strings['Registered accounts by type'] = 'Rekisteröidyt tilit tyypin mukaan';
$a->strings['%s user blocked'] = [
	0 => 'Estetty %s käyttäjä',
	1 => 'Estetty %s käyttäjää',
];
$a->strings['You can\'t remove yourself'] = 'Et voi poistaa itseäsi';
$a->strings['%s user deleted'] = [
	0 => '%s käyttäjä poistettu',
	1 => '%s käyttäjää poistettu',
];
$a->strings['Register date'] = 'Rekisteripäivämäärä';
$a->strings['Last login'] = 'Viimeisin kirjautuminen';
$a->strings['Last public item'] = 'Viimeisin julkinen kohde';
$a->strings['Active Accounts'] = 'Aktiiviset tilit';
$a->strings['User blocked'] = 'Käyttäjä estetty';
$a->strings['Site admin'] = 'Sivuston ylläpito';
$a->strings['Account expired'] = 'Tili vanhentunut';
$a->strings['Create a new user'] = 'Luo uusi käyttäjä';
$a->strings['Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'] = 'Valitut käyttäjät poistetaan!\n\nKaikki näiden tälle sivustolle julkaisemat viestit poistetaan pysyvästi!\n\nOletko varma?';
$a->strings['The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'] = 'Käyttäjä {0} poistetaan!\n\nKaikki mitä tämä käyttäjä on julkaissut tällä sivustolla poistetaan pysyvästi!\n\nOletko varma?';
$a->strings['User "%s" deleted'] = 'Poistettiin käyttäjä ”%s”';
$a->strings['User "%s" blocked'] = 'Estettiin käyttäjä ”%s”';
$a->strings['%s user unblocked'] = [
	0 => '%s käyttäjän esto poistetut',
	1 => '%s käyttäjän esto poistettu',
];
$a->strings['Blocked Users'] = 'Estetyt käyttäjät';
$a->strings['User "%s" unblocked'] = 'Käyttäjän "%s" esto poistettu';
$a->strings['Create user'] = 'Luo käyttäjä';
$a->strings['Type in the details for the new user to be created.'] = 'Kirjoita uuden luotavan käyttäjän tiedot.';
$a->strings['Display name'] = 'Näyttönimi';
$a->strings['Nickname'] = 'Lempinimi';
$a->strings['Users awaiting permanent deletion'] = 'Pysyvää poistoa odottavat käyttäjät';
$a->strings['Permanent deletion'] = 'Pysyvä poisto';
$a->strings['User waiting for permanent deletion'] = 'Pysyvää poistoa odottava käyttäjä';
$a->strings['%s user approved'] = [
	0 => 'Hyväksyttiin %s käyttäjä',
	1 => 'Hyväksyttiin %s käyttäjää',
];
$a->strings['%s registration revoked'] = [
	0 => 'Peruttiin %s rekisteröinti',
	1 => 'Peruttiin %s rekisteröintiä',
];
$a->strings['Account approved.'] = 'Tili hyväksytty.';
$a->strings['Registration revoked'] = 'Rekisteröinti peruttu';
$a->strings['User registrations awaiting review'] = 'Tarkastamista odottavat käyttäjärekisteröinnit';
$a->strings['Request date'] = 'Pyyntöpäivä';
$a->strings['No registrations.'] = 'Ei rekisteröintejä.';
$a->strings['Note from the user'] = 'Huomautus käyttäjältä';
$a->strings['Deny'] = 'Estä';
$a->strings['Show Ignored Requests'] = 'Näytä sivuutetut pyynnöt';
$a->strings['Hide Ignored Requests'] = 'Piilota sivuutetut pyynnöt';
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
$a->strings['You must be logged in to show this page.'] = 'Tämän sivun näyttäminen vaatii kirjautumisen.';
$a->strings['Network Notifications'] = 'Verkostoilmoitukset';
$a->strings['System Notifications'] = 'Järjestelmäilmoitukset';
$a->strings['Personal Notifications'] = 'Henkilökohtaiset ilmoitukset';
$a->strings['Home Notifications'] = 'Koti-ilmoitukset';
$a->strings['Show unread'] = 'Näytä lukemattomat';
$a->strings['{0} requested registration'] = '{0} jätti rekisteröintipyynnön';
$a->strings['{0} and %d others requested registration'] = '{0} ja %d muuta pyysivät rekisteröitymistä';
$a->strings['Authorize application connection'] = 'Vahvista sovellusyhteys';
$a->strings['Do you want to authorize this application to access your posts and contacts, and/or create new posts for you?'] = 'Haluatko antaa tälle sovellukselle pääsyn julkaisuhisi ja kontakteihisi ja/tai antaa sovellukselle luvan luoda uusia julkaisuja?';
$a->strings['Unsupported or missing response type'] = 'Ei-tuettu tai puuttuva vastaustyyppi';
$a->strings['Incomplete request data'] = 'Pyynnön tiedot puutteelliset';
$a->strings['Please copy the following authentication code into your application and close this window: %s'] = 'Kopioi seuraava todennuskoodi sovellukseesi ja sulje tämä ikkuna: %s';
$a->strings['Invalid data or unknown client'] = 'Epäkelpo data tai tuntematon asiakasohjelma';
$a->strings['Unsupported or missing grant type'] = 'Ei-tuettu tai puuttuva lupatyyppi';
$a->strings['Search in Friendica %s'] = 'Etsi Friendicasta %s';
$a->strings['The Photo is not available.'] = 'Valokuva ei ole saatavilla.';
$a->strings['The Photo with id %s is not available.'] = 'Tunnisteen %s valokuva ei ole saatavilla.';
$a->strings['Invalid external resource with url %s.'] = 'Epäkelpo ulkoinen resurssi URL:ssä %s.';
$a->strings['Invalid photo with id %s.'] = 'Epäkelpo valokuva, jonka ID on %s.';
$a->strings['Post not found.'] = 'Julkaisua ei löytynyt.';
$a->strings['Edit post'] = 'Muokkaa viestiä';
$a->strings['web link'] = 'WWW-linkki';
$a->strings['Insert video link'] = 'Lisää videolinkki';
$a->strings['video link'] = 'videolinkki';
$a->strings['Insert audio link'] = 'Lisää äänilinkki';
$a->strings['audio link'] = 'äänilinkki';
$a->strings['Remove Item Tag'] = 'Poista kohteelta aihetunniste';
$a->strings['Select a tag to remove: '] = 'Valitse poistettava aihetunniste:';
$a->strings['Wrong type "%s", expected one of: %s'] = 'Väärä tyyppi ”%s”, odotettiin yhtä seuraavista: %s';
$a->strings['Model not found'] = 'Mallia ei löytynyt';
$a->strings['Unlisted'] = 'Luetteloimaton';
$a->strings['Remote privacy information not available.'] = 'Yksityisyyden etätietoja ei saatavilla.';
$a->strings['Visible to:'] = 'Näkyvissä:';
$a->strings['CC:'] = 'Kopio:';
$a->strings['BCC:'] = 'Piilokopio:';
$a->strings['Audience:'] = 'Yleisö:';
$a->strings['Attributed To:'] = 'Lähde:';
$a->strings['Collection (%s)'] = 'Kokoelma (%s)';
$a->strings['Followers (%s)'] = 'Seuraajat (%s)';
$a->strings['%d more'] = '%d lisää';
$a->strings['No contacts.'] = 'Ei yhteystietoja.';
$a->strings['%s\'s posts'] = 'Käyttäjän %s julkaisut';
$a->strings['%s\'s comments'] = 'Käyttäjän %s kommentit';
$a->strings['%s\'s timeline'] = 'Käyttäjän %s aikajana';
$a->strings['Personal notes are visible only by yourself.'] = 'Yksityismuistiinpanot näkyvät vain sinulle.';
$a->strings['Image exceeds size limit of %s'] = 'Kuva ylittää kokorajoituksen %s';
$a->strings['Image upload didn\'t complete, please try again'] = 'Kuvan lataus ei onnistunut, yritä uudelleen';
$a->strings['Image file is missing'] = 'Kuvatiedosto puuttuu';
$a->strings['Server can\'t accept new file upload at this time, please contact your administrator'] = 'Palvelin ei tällä hetkellä hyväksy uusien tiedostojen lähettämistä: ota yhteys ylläpitoon';
$a->strings['Image file is empty.'] = 'Kuvatiedosto on tyhjä.';
$a->strings['View Album'] = 'Näytä albumi';
$a->strings['Profile not found.'] = 'Profiilia ei löytynyt.';
$a->strings['You\'re currently viewing your profile as <b>%s</b> <a href="%s" class="btn btn-sm pull-right">Cancel</a>'] = 'Katselet profiiliasi parhaillaan roolissa <b>%s</b> <a href="%s" class="btn btn-sm pull-right">Peruutal</a>';
$a->strings['Display name:'] = 'Näyttönimi:';
$a->strings['d MMMM'] = 'd MMMM';
$a->strings['Birthday:'] = 'Syntymäpäivä:';
$a->strings['Age: '] = 'Ikä:';
$a->strings['%d year old'] = [
	0 => '%d vuoden vanha',
	1 => '%d vuotta vanha',
];
$a->strings['Description:'] = 'Kuvaus:';
$a->strings['Groups:'] = 'Ryhmät:';
$a->strings['View profile as:'] = 'Näytä profiili:';
$a->strings['View as selected profile'] = 'Näytä valittuna profiilina';
$a->strings['View as'] = 'Katsele roolissa';
$a->strings['Profile unavailable.'] = 'Profiili ei saatavilla.';
$a->strings['Invalid locator'] = 'Epäkelpo paikannin';
$a->strings['The provided profile link doesn\'t seem to be valid'] = 'Annettu profiililinkki ei vaikuta kelvolliselta';
$a->strings['Remote subscription can\'t be done for your network. Please subscribe directly on your system.'] = 'Verkostostasi ei voi tehdä etätilauksia. Tilaa suoraan omassa järjestelmässäsi.';
$a->strings['Friend/Connection Request'] = 'Kaveri- tai yhteyspyyntö';
$a->strings['Enter your Webfinger address (user@domain.tld) or profile URL here. If this isn\'t supported by your system, you have to subscribe to <strong>%s</strong> or <strong>%s</strong> directly on your system.'] = 'Syötä tähän Webfinger-osoitteesi (käyttäjä@domain.tld) tai profiilisi URL-osoite. Jos tätä ei tueta järjestelmässäsi, sinun täytyy tilata <strong>%s</strong> tai <strong>%s</strong> suoraan järjestelmässäsi.';
$a->strings['If you are not yet a member of the free social web, <a href="%s">follow this link to find a public Friendica node and join us today</a>.'] = 'Jos et ole vielä vapaan sosiaalisen verkoston jäsen, <a href="%s">seuraa tätä linkkiä löytääksesi julkisen Friendica-palvelimen ja liity meihin tänään</a>.';
$a->strings['Your Webfinger address or profile URL:'] = 'Sinun Webfinger-osoitteesi tai -profiilisi URL-osoite:';
$a->strings['Restricted profile'] = 'Rajoitettu profiili';
$a->strings['This profile has been restricted which prevents access to their public content from anonymous visitors.'] = 'Tämä profiili on rajoitettu, mikä estää anonyymien kävijöiden pääsyn sen julkiseen sisältöön.';
$a->strings['Private Message'] = 'Yksityisviesti';
$a->strings['via'] = 'kautta';
$a->strings['Scheduled'] = 'Ajastettu';
$a->strings['Content'] = 'Sisältö';
$a->strings['Remove post'] = 'Poista julkaisu';
$a->strings['Only parent users can create additional accounts.'] = 'Vain päätason käyttäjät voivat luoda lisäkäyttäjätilejä.';
$a->strings['Registration Closed'] = 'Rekisteröintyminen suljettu';
$a->strings['Registration is currently closed on this node.'] = 'Tälle solmulle rekisteröityminen on tällä hetkellä suljettu.';
$a->strings['The administrators have decided to limit new registrations. This could be temporary or permanent.'] = 'Ylläpitäjät ovat päättäneet rajoittaa uusia rekisteröitymisiä. Tämä saattaa olla väliaikaista tai pysyvää.';
$a->strings['You can find other open Friendica servers at %s where you can register.'] = 'Voit etsiä osoitteessa %s muita avoimia Friendica-palvelimia, joihin voit rekisteröityä.';
$a->strings['This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.'] = 'Sivuston päivittäinen rekisteröintiraja ylitetty. Yritä uudelleen huomenna.';
$a->strings['You may (optionally) fill in this form via OpenID by supplying your OpenID and clicking "Register".'] = 'Tämän lomakkeen voi (valinnaisesti) täyttää OpenID:n kautta antamalla OpenID:n ja napsauttamalla ”Rekisteröidy”.';
$a->strings['If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.'] = 'Jos OpenID ei ole tuttu, jätä kenttä tyhjäksi.';
$a->strings['Your OpenID (optional): '] = 'OpenID (valinnainen):';
$a->strings['Include your profile in member directory?'] = 'Lisätäänkö profiili jäsenhakemistoon?';
$a->strings['Create Account'] = 'Luo tili';
$a->strings['Personal (standard account)'] = 'Henkilökohtainen (vakiotili)';
$a->strings['Soap-Box (auto-approve Follow requests)'] = 'Saippualaatikko (hyväksyy automaattisesti seuraajapyynnöt)';
$a->strings['Love-All (auto-approve Friend requests)'] = 'Rakkautta kaikille (hyväksyy automaattisesti kaveripyynnöt)';
$a->strings['Organization Page'] = 'Organisaation sivu';
$a->strings['Restricted Group'] = 'Rajoitettu ryhmä';
$a->strings['Create Group'] = 'Luo ryhmä';
$a->strings['Create Page'] = 'Luo sivu';
$a->strings['Personal Soap-Box (auto-approve Follow requests)'] = 'Henkilökohtainen saippualaatikko (hyväksyy automaattisesti seuraajapyynöt)';
$a->strings['Personal Love-All (auto-approve Friend requests)'] = 'Henkilökohtainen rakkautta kaikille (hyväksyy automaattisesti kaveripyynnöt)';
$a->strings['Account type:'] = 'Tilityyppi:';
$a->strings['You can change the account type later. (<a href="'] = 'Voit muuttaa tilityyppiä myöhemmin. (<a href="';
$a->strings['Note for the admin'] = 'Huomautus ylläpidolle';
$a->strings['Leave a message for the admin, why you want to join this node'] = 'Kerro ylläpitäjälle, miksi haluat liittyä tähän solmuun';
$a->strings['Membership on this site is by invitation only.'] = 'Tähän sivustoon voi liittyä vain kutsusta.';
$a->strings['Your invitation code: '] = 'Kutsukoodisi:';
$a->strings['Your Display Name (as you would like it to be displayed on this system):'] = 'Näyttönimi (miten haluat itsesi näkyvän järjestelmässä):';
$a->strings['Your Email Address (initial information will be sent there, so this must be a valid address):'] = 'Sähköpostiosoite (minne aloitustiedot lähetetään, joten on oltava kelvollinen osoite):';
$a->strings['Please repeat your e-mail address:'] = 'Toista sähköpostiosoite:';
$a->strings['New Password:'] = 'Uusi salasana:';
$a->strings['Leave empty for an auto generated password.'] = 'Jätä tyhjäksi, jos haluat automaattisesti luodun salasanan.';
$a->strings['Confirm:'] = 'Vahvista:';
$a->strings['Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be "<strong>nickname@%s</strong>".'] = 'Valitse profiilin kutsumanimi. Sen on alettava kirjaimella. Profiilin osoite sivustolla on tällöin ”<strong>kutsumanimi@%s</strong>”.';
$a->strings['Choose a nickname: '] = 'Valitse kutsumanimi:';
$a->strings['Import'] = 'Tuo';
$a->strings['Import your profile to this friendica instance'] = 'Tuo profiilisi tähän Friendica-ilmentymään.';
$a->strings['Note: This node explicitly contains adult content'] = 'Huom! Tämä solmu sisältää aikuisille suunnattua sisältöä.';
$a->strings['Parent Password:'] = 'Emosalasana:';
$a->strings['Please enter the password of the parent account to legitimize your request.'] = 'Anna emotilisi salasana pyyntösi tarkistamiseksi.';
$a->strings['Password doesn\'t match.'] = 'Salasanat eivät täsmää.';
$a->strings['Please enter your password.'] = 'Anna salasana.';
$a->strings['You have entered too much information.'] = 'Olet antanut liikaa tietoa.';
$a->strings['Please enter the identical mail address in the second field.'] = 'Anna toiseen kenttään tarkalleen sama sähköpostiosoite.';
$a->strings['Nickname cannot start with a digit.'] = 'Kutsumanimi ei voi alkaa numerolla.';
$a->strings['Nickname can only contain US-ASCII characters.'] = 'Kutsumanimi voi sisältää vain US-ASCII-merkkejä.';
$a->strings['The additional account was created.'] = 'Lisätili luotiin.';
$a->strings['Registration successful. Please check your email for further instructions.'] = 'Rekisteröityminen onnistui. Saat kohta lisäohjeita sähköpostitse.';
$a->strings['Failed to send email message. Here your accout details:<br> login: %s<br> password: %s<br><br>You can change your password after login.'] = 'Sähköpostiviestin lähettäminen epäonnistui. Tässä tilitietosi: <br>tunnus: %s <br>salasana: %s<br><br> Salasanaa voi vaihtaa kirjautumisen jälkeen. ';
$a->strings['Registration successful.'] = 'Rekisteröityminen onnistui.';
$a->strings['Your registration can not be processed.'] = 'Rekisteröitymistäsi ei voida käsitellä.';
$a->strings['You have to leave a request note for the admin.'] = 'Sinun pitää jättää viesti pyynnöstäsi ylläpitäjälle.';
$a->strings['An internal error occured.'] = 'Tapahtui sisäinen virhe.';
$a->strings['Your registration is pending approval by the site owner.'] = 'Rekisteröintisi odottaa ylläpitäjän hyväksyntää.';
$a->strings['You must be logged in to use this module.'] = 'Tämän moduulin käyttö vaatii kirjautumista.';
$a->strings['Only logged in users are permitted to perform a search.'] = 'Vain kirjautuneet käyttäjät voivat etsiä.';
$a->strings['Only one search per minute is permitted for not logged in users.'] = 'Kirjautumattomat käyttäjät voivat tehdä vain yhden haun minuutissa.';
$a->strings['Items tagged with: %s'] = 'Aihetunnisteella ”%s” merkityt kohteet';
$a->strings['Search term was not saved.'] = 'Hakusanaa ei tallennettu.';
$a->strings['Search term already saved.'] = 'Hakusana on jo tallennettu.';
$a->strings['Search term was not removed.'] = 'Hakusanaa ei poistettu.';
$a->strings['Your OpenID: '] = 'OpenID-tunnuksesi';
$a->strings['Please enter your username and password to add the OpenID to your existing account.'] = 'Syötä käyttäjänimi ja salasana lisätäksesi OpenID:n olemassa olevaan tiliin.';
$a->strings['Or sign in using OpenID'] = 'Tai kirjaudu OpenID:llä';
$a->strings['OpenID'] = 'OpenID';
$a->strings['New here?'] = 'Uusi täällä?';
$a->strings['Password'] = 'Salasana';
$a->strings['Remember me'] = 'Muista minut';
$a->strings['Forgot your password?'] = 'Unohditko salasanan?';
$a->strings['Website Terms of Service'] = 'Sivuston käyttöehdot';
$a->strings['terms of service'] = 'käyttöehdot';
$a->strings['Website Privacy Policy'] = 'Sivuston tietosuojakäytäntö';
$a->strings['privacy policy'] = 'tietosuojakäytäntö';
$a->strings['OpenID protocol error. No ID returned'] = 'OpenID-yhteyskäytöntövirhe. Yhtään tunnistetta ei palautettu';
$a->strings['Account not found. Please login to your existing account to add the OpenID to it.'] = 'Tiliä ei löydy. Kirjaudu olemassa olevaan tiliin lisätäksesi siihen OpenID:n.';
$a->strings['Account not found. Please register a new account or login to your existing account to add the OpenID to it.'] = 'Tiliä ei löydy. Luo uusi tili tai kirjaudu olemassa olevaan tiliin lisätäksesi siihen OpenID:n.';
$a->strings['Passwords do not match.'] = 'Salasanat eivät täsmää.';
$a->strings['Password does not need changing.'] = 'Salasanaa ei tarvitse vaihtaa.';
$a->strings['Password unchanged.'] = 'Salasanaa ei vaihdettu.';
$a->strings['Password Too Long'] = 'Liian pitkä salasana';
$a->strings['Since version 2022.09, we\'ve realized that any password longer than 72 characters is truncated during hashing. To prevent any confusion about this behavior, please update your password to be fewer or equal to 72 characters.'] = 'Version 2022.09 jälkeen huomasimme, että yli 72-merkkiset salasanat tiivistettäessä katkaistaan. Jottei tämä aiheuttaisi ongelmia, vaihda salasanasi korkeintaan 72 merkkiä pitkäksi.';
$a->strings['Update Password'] = 'Päivitä salasana';
$a->strings['Current Password:'] = 'Nykyinen salasana:';
$a->strings['Your current password to confirm the changes'] = 'Vahvista vaihdos antamallasi nykyinen salasana';
$a->strings['Allowed characters are a-z, A-Z, 0-9 and special characters except white spaces and accentuated letters.'] = 'Sallitut merkit ovat a–z, A–Z, 0–9 ja erikoismerkit paitsi välilyönti ja tarkkeelliset kirjaimet';
$a->strings['Password length is limited to 72 characters.'] = 'Salasanan enimmäispituus on 72 merkkiä.';
$a->strings['Remaining recovery codes: %d'] = 'Jäljellä olevat palautuskoodit: %d';
$a->strings['Invalid code, please retry.'] = 'Väärä koodi, yritä uudelleen.';
$a->strings['Two-factor recovery'] = 'Kaksivaiheinen palautus';
$a->strings['<p>You can enter one of your one-time recovery codes in case you lost access to your mobile device.</p>'] = '<p>Voit syöttää yhden kertakäyttöisistä palautuskoodeistasi, jos sinulla ei ole pääsyä mobiililaitteeseesi.</p>';
$a->strings['Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>'] = 'Eikö puhelimesi ole saatavilla? <a href="%s">Syötä kaksivaiheinen palautuskoodi</a>';
$a->strings['Please enter a recovery code'] = 'Syötä palautuskoodi';
$a->strings['Submit recovery code and complete login'] = 'Syötä palautuskoodi ja viimeistele sisäänkirjautuminen';
$a->strings['Sign out of this browser?'] = 'Kirjaudutaanko ulos tästä selaimesta?';
$a->strings['<p>If you trust this browser, you will not be asked for verification code the next time you sign in.</p>'] = '<p>Jos luotat tähän selaimeen, vahvistuskoodia ei kysytä kirjauduttaessa seuraavan kerran.</p>';
$a->strings['Trust and sign out'] = 'Luota ja kirjaudu ulos';
$a->strings['Couldn\'t save browser to Cookie.'] = 'Selainta ei voitu tallentaa evästeisiin.';
$a->strings['Trust this browser?'] = 'Luotatko tähän selaimeen?';
$a->strings['<p>If you choose to trust this browser, you will not be asked for a verification code the next time you sign in.</p>'] = '<p>Jos päätät luottaa tähän selaimeen, sinulta ei kysytä vahvistuskoodia seuraavan kerran kirjautuessasi.</p>';
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
$a->strings['Cannot change to that email.'] = 'Tähän sähköpostiosoitteeseen ei voi vaihtaa.';
$a->strings['Settings were not updated.'] = 'Asetuksia ei ole päivitetty.';
$a->strings['Relocate message has been send to your contacts'] = 'Yhteystiedoillesi on lähetetty siirtymisviesti';
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
$a->strings['(Optional) Allow this OpenID to login to this account.'] = '(Valinnainen) Salli tähän tiliin kirjautuminen tällä OpenID:llä.';
$a->strings['Publish your profile in your local site directory?'] = 'Julkaistaanko profiilisi paikallisessa sivustohakemistossasi?';
$a->strings['Your profile will be published in this node\'s <a href="%s">local directory</a>. Your profile details may be publicly visible depending on the system settings.'] = 'Profiilisi julkaistaan tämän solmun <a href="%s">paikallisessa hakemistossa</a>. Profiilisi yksityiskohdat saattavat olla julkisesti nähtävissä riippuen järjestelmän asetuksista.';
$a->strings['Your profile will also be published in the global friendica directories (e.g. <a href="%s">%s</a>).'] = 'Profiilisi julkaistaan myös maailmanlaajuisissa Friendica-hakemistoissa (esim. <a href="%s">%s</a>).';
$a->strings['Account Settings'] = 'Tiliasetukset';
$a->strings['Your Identity Address is <strong>\'%s\'</strong> or \'%s\'.'] = 'Identiteettisi osoite on <strong>\'%s\'</strong> tai \'%s\'.';
$a->strings['Save settings'] = 'Tallenna asetukset';
$a->strings['Password Settings'] = 'Salasana-asetukset';
$a->strings['Leave password fields blank unless changing'] = 'Jätä salasanakentät tyhjäksi, ellet halua vaihtaa salasanaa';
$a->strings['Password:'] = 'Salasana:';
$a->strings['Your current password to confirm the changes of the email address'] = 'Nykyinen salasanasi sähköpostiosoitteen muutoksen vahvistamiseksi';
$a->strings['Delete OpenID URL'] = 'Poista OpenID-verkko-osoite';
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
$a->strings['Post expiration'] = 'Julkaisun erääntyminen';
$a->strings['Automatically expire posts after this many days:'] = 'Tee julkaisuista vanhentuneita näin monen päivän kuluttua:';
$a->strings['If empty, posts will not expire. Expired posts will be deleted'] = 'Jos kenttä jää tyhjäksi, julkaisut eivät vanhene. Vanhentuneet julkaisut poistetaan.';
$a->strings['Expire posts'] = 'Tee julkaisuista vanhentuneita';
$a->strings['When activated, posts and comments will be expired.'] = 'Kun tämä on käytössä, julkaisuista ja kommenteista tulee vanhentuvia.';
$a->strings['Expire personal notes'] = 'Tee yksityismuistiinpanoista vanhentuvia';
$a->strings['When activated, the personal notes on your profile page will be expired.'] = 'Kun tämä on käytössä, profiilisivusi yksityismuistiinpanot vanhentuvat.';
$a->strings['Expire bookmarked posts'] = 'Anna kirjanmerkkeihin lisättyjen julkaisujen vanheta';
$a->strings['Bookmarking posts keeps them from being expired. That behaviour is overwritten by this setting.'] = 'Julkaisujen lisääminen kirjanmerkkeihin estää niitä vanhenemasta. Tämä asetus kumoaa kyseisen ominaisuuden.';
$a->strings['Only expire posts by others'] = 'Anna vain muiden tekemien julkaisujen vanheta';
$a->strings['When activated, your own posts never expire. Then the settings above are only valid for posts you received.'] = 'Kun tämä on käytössä, omat julkaisusi eivät vanhene koskaan. Ylläolevat asetukset koskevat siten vain sinulle lähetettyjä julkaisuja.';
$a->strings['Send an email when:'] = 'Lähetä sähköposti kun: ';
$a->strings['You receive an introduction'] = 'Vastaanotat kaverikutsun';
$a->strings['Your introductions are confirmed'] = 'Kaverikutsusi on hyväksytty';
$a->strings['Someone writes on your wall'] = 'Joku kirjoittaa seinällesi';
$a->strings['Someone writes a followup comment'] = 'Joku vastaa kommenttiin';
$a->strings['You receive a private message'] = 'Vastaanotat yksityisviestin';
$a->strings['You receive a friend suggestion'] = 'Saat kaveriehdotuksen';
$a->strings['You are tagged in a post'] = 'Sinut on merkitty julkaisuun';
$a->strings['Notify when:'] = 'Lähetä ilmoitus kun: ';
$a->strings['Someone tagged you'] = 'Joku merkitsi sinut julkaisuun';
$a->strings['Someone directly commented on your post'] = 'Joku kommentoi suoraan julkaisuusi';
$a->strings['Someone liked your content'] = 'Joku tykkäsi sisällöstäsi';
$a->strings['Can only be enabled, when the direct comment notification is enabled.'] = 'Voidaan ottaa käyttöön vain kun suora kommentti -ilmoitus on laitettu päälle.';
$a->strings['Someone shared your content'] = 'Joku jakoi sisältösi';
$a->strings['Someone commented in your thread'] = 'Joku jätti kommentin ketjuusi.';
$a->strings['Someone commented in a thread where you commented'] = 'Joku kommentoi ketjussa, jossa sinä olet kommentoinut';
$a->strings['Someone commented in a thread where you interacted'] = 'Joku kommentoi ketjussa, jonka kanssa olet ollut vuorovaikutuksessa';
$a->strings['Activate desktop notifications'] = 'Ota työpöytäilmoitukset käyttöön';
$a->strings['Show desktop popup on new notifications'] = 'Näytä uudet työpöytäilmoitukset ponnahdusikkunassa';
$a->strings['Text-only notification emails'] = 'Ilmoitussähköposteissa vain tekstiä';
$a->strings['Send text only notification emails, without the html part'] = 'Lähetä ilmoitussähköposteissa vain tekstiä ilman HTML-koodia';
$a->strings['Show detailled notifications'] = 'Näytä yksityiskohtaiset ilmoitukset';
$a->strings['Per default, notifications are condensed to a single notification per item. When enabled every notification is displayed.'] = 'Oletuksena ilmoituksia lähetään yksi per kohde. Kun tämä asetus on käytössä, jokainen ilmoitus näytetään.';
$a->strings['Show notifications of ignored contacts'] = 'Näytä ilmoitukset sivuutetuilta kontakteilta';
$a->strings['You don\'t see posts from ignored contacts. But you still see their comments. This setting controls if you want to still receive regular notifications that are caused by ignored contacts or not.'] = 'Et näe sivuutettujen kontaktien julkaisuja. Näet kuitenkin edelleen heidän kommenttinsa. Tällä asetuksella voit määrittää, haluatko edelleen vastaanottaa sivuutettujen kontaktien aiheuttamia tavallisia ilmoituksia.';
$a->strings['Advanced Account/Page Type Settings'] = 'Käyttäjätilin/sivutyypini lisäasetukset';
$a->strings['Change the behaviour of this account for special situations'] = 'Muuta tämän tilin käyttäytymistä erityistilanteissa';
$a->strings['Relocate'] = 'Uudelleensijoitus';
$a->strings['If you have moved this profile from another server, and some of your contacts don\'t receive your updates, try pushing this button.'] = 'Jos olet siirtänyt tämän profiilin toiselta palvelimelta ja jotkut yhteyshenkilöistäsi eivät saa päivityksiäsi, kokeile painaa tätä painiketta.';
$a->strings['Resend relocate message to contacts'] = 'Lähetä siirtoviesti uudelleen kontakteille';
$a->strings['Addon Settings'] = 'Lisäosa-asetukset';
$a->strings['None of the addons installed on this server have any settings.'] = 'Yhdessäkään tälle palvelimelle asennetuissa lisäosissa ei ole mitään asetuksia.';
$a->strings['This page can be used to define the channels that will automatically be reshared by your account.'] = 'Voit käyttää tätä sivua niiden kanavien määrittämiseen, jotka jaetaan automaattisesti tililäsi.';
$a->strings['This page can be used to define your own channels.'] = 'Voit määrittää omat kanavasi tällä sivulla.';
$a->strings['Publish'] = 'Julkaise';
$a->strings['When selected, the channel results are reshared. This only works for public ActivityPub posts from the public timeline or the user defined circles.'] = 'Kun valinta on päällä, kanavan tulokset jaetaan uudestaan. Tämä toimii vain julkisilta aikajanoilta tai käyttäjän määrittämilta piireiltä tulleille julkisille ActivityPub-julkaisuille.';
$a->strings['Label'] = 'Nimike';
$a->strings['Access Key'] = 'Pääsyavain';
$a->strings['Circle/Channel'] = 'Piiri/kanava';
$a->strings['Include Tags'] = 'Sisällytä tunnisteet';
$a->strings['Exclude Tags'] = 'Sulje pois tunnisteet';
$a->strings['Minimum Size'] = 'Vähimmäiskoko';
$a->strings['Maximum Size'] = 'Enimmäiskoko';
$a->strings['Full Text Search'] = 'Haku koko tekstistä';
$a->strings['Languages'] = 'Kielet';
$a->strings['Select all languages that you want to see in this channel. "Unspecified" describes all posts for which no language information was detected (e.g. posts with just an image or too little text to be sure of the language). If you want to see all languages, you will need to select all items in the list.'] = 'Valitse kaikki kielet, joita haluat nähdä tällä kanavalla. ”Määrittelemätön” tarkoittaa kaikkia julkaisuja, joiden kielitietoja ei ole tunnistettu (esim. julkaisuja, joissa on vain kuva tai liian vähän tekstiä kielen tunnistamiseksi). Jos haluat nähdä kaikki kielet, sinun on valittava kaikki luettelon kohteet.';
$a->strings['Delete channel'] = 'Poista kanava';
$a->strings['Check to delete this entry from the channel list'] = 'Valitse poistaaksesi tämän merkinnän kanavaluettelosta';
$a->strings['Comma separated list of tags. If a post contain any of these tags, then it will not be part of this channel.'] = 'Pilkuilla erotettu luettelo tunnisteista. Jos julkaisu sisältää yhdenkin näistä tunnisteista, se ei kuulu tähän kanavaan.';
$a->strings['Comma separated list of tags. If a post contain any of these tags, then it will not be part of nthis channel.'] = 'Pilkuilla erotettu luettelo tunnisteista. Jos julkaisu sisältää yhdenkin näistä tunnisteista, se ei kuulu tähän kanavaan.';
$a->strings['Short name for the channel. It is displayed on the channels widget.'] = 'Kanavan lyhyt nimi. Se näytetään kanavat-widgetissä.';
$a->strings['This should describe the content of the channel in a few word.'] = 'Kanavan sisältöä kuvaava lyhyt teksti.';
$a->strings['When you want to access this channel via an access key, you can define it here. Pay attention to not use an already used one.'] = 'Jos haluat käyttää tätä kanavaa pääsykoodilla, voit määrittää sen tässä. Varo, ettet valitse jo käytössä olevaa koodia.';
$a->strings['Select a circle or channel, that your channel should be based on.'] = 'Valitse piiri tai kanava, johon kanavasi perustuu.';
$a->strings['Comma separated list of tags. A post will be used when it contains any of the listed tags.'] = 'Pilkuilla erotettu luettelo tunnisteista. Julkaisu otetaan huomioon, jos se sisältää jonkin luettelossa mainituista tunnisteista.';
$a->strings['Minimum post size. Leave empty for no minimum size. The size is calculated without links, attached posts, mentions or hashtags.'] = 'Julkaisun vähimmäiskoko. Jätä tyhjäksi, jos et halua määrittää vähimmiskokoa. Koko lasketaan ilman linkkejä, liitettyjä julkaisuja, mainintoja tai aihetunnisteita.';
$a->strings['Maximum post size. Leave empty for no maximum size. The size is calculated without links, attached posts, mentions or hashtags.'] = 'Julkaisun enimmäiskoko. Jätä tyhjäksi, jos et halua määrittää enimmäiskokoa. Koko lasketaan ilman linkkejä, liitettyjä julkaisuja, mainintoja tai aihetunnisteita.';
$a->strings['Search terms for the body, supports the "boolean mode" operators from MariaDB. See the help for a complete list of operators and additional keywords: %s'] = 'Tekstikentän hakuehdot tukevat MariaDB:n ”boolean mode” -operaattoreita. Katso ohjeista täydellinen luettelo operaattoreista ja muista avainsanoista: %s';
$a->strings['Check to display images in the channel.'] = 'Valitse, jos haluat, että kuvat näkyvät kanavalla.';
$a->strings['Check to display videos in the channel.'] = 'Valitse, jos haluat, että videot näkyvät kanavalla.';
$a->strings['Check to display audio in the channel.'] = 'Valitse, jos haluat, että äänitiedostot näkyvät kanavalla.';
$a->strings['Select all languages that you want to see in this channel.'] = 'Valitse kielet, jotka haluat nähdä tällä kanavalla.';
$a->strings['Add new entry to the channel list'] = 'Lisää uusi kohde kanavaluetteloon';
$a->strings['Add'] = 'Lisää';
$a->strings['Current Entries in the channel list'] = 'Nykyiset kanavaluettelon kohteet';
$a->strings['Delete entry from the channel list'] = 'Poista kohde kanavaluettelosta';
$a->strings['Delete entry from the channel list?'] = 'Poistetaanko kohde kanavaluettelosta?';
$a->strings['Failed to connect with email account using the settings provided.'] = 'Yhteyden muodostaminen sähköpostitiliin annettujen asetusten avulla epäonnistui.';
$a->strings['Diaspora (Socialhome, Hubzilla)'] = 'Diaspora (Socialhome, Hubzilla)';
$a->strings['Built-in support for %s connectivity is enabled'] = 'Sisäänrakennettu tuki %s-yhteydelle on otettu käyttöön.';
$a->strings['Built-in support for %s connectivity is disabled'] = 'Sisäänrakennettu tuki %s-liitettävyydelle on pois käytöstä';
$a->strings['Email access is disabled on this site.'] = 'Sähköpostikäyttö on pois käytöstä tällä sivustolla.';
$a->strings['None'] = 'Ei mitään';
$a->strings['Default (Mastodon will display the title and a link to the post)'] = 'Oletus (Mastodon näyttää otsikon ja linkin julkaisuun)';
$a->strings['Use the summary (Mastodon and some others will treat it as content warning)'] = 'Käytä yhteenvetoa (Mastodon ja muutama muut käsittelevät sitä sisältövaroituksena)';
$a->strings['Embed the title in the body'] = 'Upota otsikko tekstiin';
$a->strings['General Social Media Settings'] = 'Yleiset some-asetukset';
$a->strings['Followed content scope'] = 'Seuratun sisällön ala';
$a->strings['By default, conversations in which your follows participated but didn\'t start will be shown in your timeline. You can turn this behavior off, or expand it to the conversations in which your follows liked a post.'] = 'Keskustelut, joihin seurattavasi osallistuivat, mutta joita he eivät aloittaneet, näkyvät oletuksena aikajanallasi. Voit laittaa tämän toiminnon pois päältä tai laajentaa näkyvyyttä keskusteluihin, joissa seurattavasi tykkäsivät jostain julkaisusta.';
$a->strings['Only conversations my follows started'] = 'Vain keskustelut, jotka seurattavani ovat aloittaneet';
$a->strings['Conversations my follows started or commented on (default)'] = 'Keskustelut, jotka seurattavani ovat aloittaneet tai joihin he ovat kommentoineet (oletus)';
$a->strings['Any conversation my follows interacted with, including likes'] = 'Mikä tahansa keskustelu, jonka kanssa seurattuni ovat olleet vuorovaikutuksessa, sisältäen tykkäykset';
$a->strings['Collapse sensitive posts'] = 'Laskosta piiloon julkaisut, joissa on arkaluonteista sisältöä';
$a->strings['If a post is marked as "sensitive", it will be displayed in a collapsed state, if this option is enabled.'] = 'Jos julkaisu on merkitty "arkaluontoiseksi," se näytetään piiloon laskostettuna, jos tämä valinta on otettu käyttöön.';
$a->strings['Enable intelligent shortening'] = 'Ota älykäs lyhentäminen käyttöön';
$a->strings['Normally the system tries to find the best link to add to shortened posts. If disabled, every shortened post will always point to the original friendica post.'] = 'Tavallisesti järjestelmä yrittää löytää parhaan linkin lyhennettyihin julkaisuihin. Jos tämä asetus on pois käytöstä, jokainen lyhennetty julkaisu osoittaa aina alkuperäiseen Friendica-julkaisuun.';
$a->strings['Enable simple text shortening'] = 'Ota käyttöön yksinkertainen tekstin lyhennys';
$a->strings['Normally the system shortens posts at the next line feed. If this option is enabled then the system will shorten the text at the maximum character limit.'] = 'Yleensä järjestelmä lyhentää julkaisut seuraavalla rivinvaihdolla. Jos tämä asetus on käytössä, järjestelmä lyhentää tekstiä merkkimäärän ylärajaan asti.';
$a->strings['Attach the link title'] = 'Liitä linkin otsikko';
$a->strings['When activated, the title of the attached link will be added as a title on posts to Diaspora. This is mostly helpful with "remote-self" contacts that share feed content.'] = 'Kun tämä on käytössä, liitetyn linkin otsikko lisätään Diasporan viesteihin otsikkona. Tämä on erityisen hyödyllistä ”etäitse”-kontaktien kanssa, jotka jakavat syötteen sisältöä.';
$a->strings['API: Use spoiler field as title'] = 'API: Käytä spoileri-kenttää otsikkona';
$a->strings['When activated, the "spoiler_text" field in the API will be used for the title on standalone posts. When deactivated it will be used for spoiler text. For comments it will always be used for spoiler text.'] = 'Kun tämä on käytössä, sovellusrajapinnan "spoiler_text"-kenttää käytetään yksittäisten julkaisujen otsikoissa. Kun tämä on pois käytöstä, kenttää käytetään spoiler-teksteille. Kommenteissa kenttää käytetään aina spoiler-teksteille.';
$a->strings['API: Automatically links at the end of the post as attached posts'] = 'API: Linkittää automaattisesti julkaisun loppuun liitettyinä julkaisuina';
$a->strings['When activated, added links at the end of the post react the same way as added links in the web interface.'] = 'Kun tämä on käytössä, julkaisun loppuun lisätyt linkit toimivat samalla tavalla kuin web-käyttöliittymässä lisätyt linkit.';
$a->strings['Article Mode'] = 'Artikkelitila';
$a->strings['Controls how posts with titles are transmitted. Mastodon and its forks don\'t display the content of these posts if the post is created in the correct (default) way.'] = 'Määrittää, miten otsikoidut julkaisut välitetään. Mastodon ja sen haara-versiot eivät näytä näiden julkaisujen sisältöä, jos ne on luotu oikealla (oletus)tavalla.';
$a->strings['Minimal Posting Interval'] = 'Julkaisujen vähimmäisväli';
$a->strings['The minimum interval in minutes between two posts. Default is 0. If enabled, your own posts are automatically delayed by the specified number of minutes.'] = 'Kahden julkaisun vähimmäisväli minuuteissa. Oletus on 0. Jos otettu käyttöön, julkaisujasi viivästytetään automaattisesti määritellyllä minuuttimäärällä.';
$a->strings['Email/Mailbox Setup'] = 'Sähköpostin asennus';
$a->strings['If you wish to communicate with email contacts using this service (optional), please specify how to connect to your mailbox.'] = 'Jos haluat käyttää tätä palvelua yhteydenpitoon sähköpostikontaktien kanssa (valinnainen), ilmoita, miten yhteys postilaatikkoosi muodostetaan.';
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
$a->strings['Contact CSV file upload error'] = 'Kontakti-CSV-tiedoston latausvirhe';
$a->strings['Upload File'] = 'Lataa tiedosto';
$a->strings['Your legacy ActivityPub/GNU Social account'] = 'Vanha ActivityPub/GNU Social -tilisi';
$a->strings['Delegation successfully granted.'] = 'Valtuutus myönnetty onnistuneesti';
$a->strings['Parent user not found, unavailable or password doesn\'t match.'] = 'Emokäyttäjää ei löytynyt, se ei ole saatavilla tai salasana ei täsmää.';
$a->strings['Delegation successfully revoked.'] = 'Delegoinin kumoaminen onnistui.';
$a->strings['Delegated administrators can view but not change delegation permissions.'] = 'Valtuutetut ylläpitäjät voivat tarkastella valtuutusoikeuksia, mutta eivät muuttaa niitä.';
$a->strings['Delegate user not found.'] = 'Valtuutettua käyttäjää ei löytynyt.';
$a->strings['No parent user'] = 'Ei emokäyttäjää';
$a->strings['Parent User'] = 'Emokäyttäjä';
$a->strings['Additional Accounts'] = 'Lisätilit';
$a->strings['Register additional accounts that are automatically connected to your existing account so you can manage them from this account.'] = 'Rekisteröi lisätilejä, jotka liitetään automaattisesti olemassa olevaan tiliisi, jotta voit hallinnoida niitä tämän tilin kautta.';
$a->strings['Register an additional account'] = 'Rekisteröi lisätili';
$a->strings['Parent users have total control about this account, including the account settings. Please double check whom you give this access.'] = 'Pääkäyttäjillä on täysi hallintaoikeus tähän tiliin, mukaan lukien tilin asetukset. Tarkista huolellisesti, kenelle annat käyttöoikeuden.';
$a->strings['Delegates'] = 'Valtuutetut';
$a->strings['Delegates are able to manage all aspects of this account/page except for basic account settings. Please do not delegate your personal account to anybody that you do not trust completely.'] = 'Valtuutetut voivat hallita kaikkia tämän tilin/sivun ominaisuuksia lukuun ottamatta tilin perusasetuksia. Älä anna valtuuksia henkilökohtaiselle tilillesi kenellekään, johon et luota täysin.';
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
$a->strings['Drag to reorder, use arrow buttons on each item, or tab to item with keyboard and move up/down with arrow keys'] = 'Raahaa järjestääksesi uudelleen, käytä nuolipainikkeita kunkin kohteen kohdalla, tai paina sarkain-painiketta näppäimistöllä ja siirrä sitten näppäimistön nuolinäppäimillä ylös/alas';
$a->strings['Reset order'] = 'Palauta järjestys alkuperäiseen';
$a->strings['Theme'] = 'Teema';
$a->strings['Mobile theme'] = 'Mobiiliteema';
$a->strings['Number of items to display per page:'] = 'Näytettävien kohteiden määrä sivulla:';
$a->strings['Maximum of 100 items'] = 'Enintään 100 kohdetta';
$a->strings['Number of items to display per page when viewed from mobile device:'] = 'Näytettävien kohteiden määrä sivulla mobiililaitteella katseltaessa:';
$a->strings['Regularly update the page content'] = 'Päivitä sivun sisältö säännöllisesti';
$a->strings['When enabled, new content on network, community and channels are added on top.'] = 'Kun käytössä, verkoston, yhteisön ja kanavien uusi sisältö lisätään ylös.';
$a->strings['Display emojis'] = 'Näytä emojit';
$a->strings['When enabled, emoticons are replaced with matching emojis.'] = 'Kun käytössä, emoticonit korvataan vastaavilla emojeilla.';
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
$a->strings['When enabled, remote media will be embedded in the post, like for example YouTube videos.'] = 'Kun käytössä, etämedia upotetaan julkaisuun, esimerkiksi YouTube-videot.';
$a->strings['Embed supported media'] = 'Upota tuettu media';
$a->strings['When enabled, remote media will be embedded in the post instead of using the local player if this is supported by the remote system. This is useful for media where the remote player is better than the local one, like for example Peertube videos.'] = 'Kun käytössä, etämedia upotetaan julkaisuun sen sijaan, että käytetään paikallista toisto-ohjelmaa, jos etäjärjestelmä tukee tätä ominaisuutta. Tämä on hyödyllinen sellaisen median tapauksessa, jossa etätoisto-ohjelma on parempi kuin paikallinen, esimerkiksi Peertube-videot.';
$a->strings['Channels Widget'] = 'Kanavat-pienoisohjelma';
$a->strings['Top Menu'] = 'Ylävalikko';
$a->strings['Enable timelines that you want to see in the channels widget. Bookmark timelines that you want to see in the top menu.'] = 'Salli kanavat-widgetissä aikajanat, jotka haluat nähdä. Lisää kirjanmerkki aikajanoille, jotka haluat nähdä ylävalikossa.';
$a->strings['Channel languages:'] = 'Kanavan kielet:';
$a->strings['Select all the languages you want to see in your channels. "Unspecified" describes all posts for which no language information was detected (e.g. posts with just an image or too little text to be sure of the language). If you want to see all languages, you will need to select all items in the list.'] = 'Valitse kaikki kielet, jotka haluat nähdä kanavissasi. ”Määrittelemätön” tarkoittaa kaikkia viestejä, joiden kielitietoja ei ole tunnistettu (esim. viestejä, joissa on vain kuva tai liian vähän tekstiä kielen tunnistamiseksi). Jos haluat nähdä kaikki kielet, sinun on valittava kaikki luettelon kohteet.';
$a->strings['Timeline channels:'] = 'Aikajana kanavat:';
$a->strings['Select all the channels that you want to see in your network timeline.'] = 'Valitse kaikki kanavat, jotka haluat nähdä verkostoaikajanallasi.';
$a->strings['Filter channels:'] = 'Suodatinkanavat:';
$a->strings['Select all the channels that you want to use as a filter for your network timeline. All posts from these channels will be hidden. For technical reasons postings that are older than %s will not be filtered.'] = 'Valitse kaikki kanavat, joita haluat käyttää suodattimina verkostoaikajanallasi. Kaikki julkaisut näistä kanavista piilotetaan. Teknisistä syistä julkaisuja, jotka ovat vanhempia kuin %s, ei suodateta.';
$a->strings['Beginning of week:'] = 'Viikon alku:';
$a->strings['Default calendar view:'] = 'Oletus kalenterinäkymä:';
$a->strings['Additional Features'] = 'Lisäominaisuuksia';
$a->strings['Connected Apps'] = 'Yhdistetyt sovellukset';
$a->strings['Remove authorization'] = 'Poista lupa';
$a->strings['You have no connected apps.'] = 'Sinulla ei ole yhdistettyjä sovelluksia.';
$a->strings['Display Name is required.'] = 'Näyttönimi on pakollinen.';
$a->strings['Profile couldn\'t be updated.'] = 'Profiilia ei voitu päivittää.';
$a->strings['Label:'] = 'Nimike:';
$a->strings['Value:'] = 'Arvo:';
$a->strings['Field Permissions'] = 'Kentän käyttöoikeudet';
$a->strings['(click to open/close)'] = '(klikkaa auki/kiinni)';
$a->strings['Add a new profile field'] = 'Lisää uusi profiilikenttä';
$a->strings['The homepage is verified. A rel="me" link back to your Friendica profile page was found on the homepage.'] = 'Kotisivu on vahvistettu. Löysimme kotisivultasi rel="me" -linkin, joka osoittaa Friendica-profiilisivullesi.';
$a->strings['To verify your homepage, add a rel="me" link to it, pointing to your profile URL (%s).'] = 'Vahvistaaksesi kotisivusi, lisää profiilisi URL-osoitteeseen (%s) osoittava rel="me" -linkki sivuillesi.';
$a->strings['Profile Actions'] = 'Profiilin toiminnot';
$a->strings['Edit Profile Details'] = 'Muokkaa profiilin yksityiskohdat';
$a->strings['To change your profile picture, you can either upload a new picture here, or click to visit your photos to pick among your existing pictures.'] = 'Voit joko ladata uuden kuvan tässä tai mennä kuviisi ja valita jonkin olemassa olevista kuvistasi vaihtaaksesi profiilikuvasi.';
$a->strings['Upload new picture'] = 'Lataa uusi kuva';
$a->strings['Upload selected picture'] = 'Lataa valittu kuva palvelimelle';
$a->strings['Pick existing picture from photos'] = 'Valitse kuva valokuvistasi';
$a->strings['Go to my photos'] = 'Siirry omiin kuviin';
$a->strings['Profile picture'] = 'Profiilikuva';
$a->strings['Miscellaneous'] = 'Sekalaista';
$a->strings['Custom Profile Fields'] = 'Kustomoidut profiilikentät';
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
$a->strings['The XMPP address will be published so that people can follow you there.'] = 'XMPP-osoite julkaistaan, jotta sinua voidaan seurata siellä.';
$a->strings['Matrix (Element) address:'] = 'Matrix (Element) osoite:';
$a->strings['The Matrix address will be published so that people can follow you there.'] = 'Matrix-osoite julkaistaan, jotta sinua voidaan seurata siellä.';
$a->strings['Homepage URL:'] = 'Kotisivun URL-osoite:';
$a->strings['Public Keywords:'] = 'Julkiset avainsanat:';
$a->strings['Used for suggesting potential friends, can be seen by others.'] = 'Käytetään mahdollisten kavereiden suositteluun. Muut voivat nähdä tämän.';
$a->strings['Private Keywords:'] = 'Yksityiset avainsanat:';
$a->strings['Used for searching profiles, never shown to others.'] = 'Käytetään profiilien etsimisessä. Ei näytetä koskaan muille.';
$a->strings['Image size reduction [%s] failed.'] = 'Kuvan pienentäminen [%s] epäonnistui.';
$a->strings['Shift-reload the page or clear browser cache if the new photo does not display immediately.'] = 'Jos kuva ei näy heti, lataa sivu uudelleen tai tyhjennä selaimen välimuisti.';
$a->strings['Unable to process image'] = 'Kuvan käsitteleminen epäonnistui';
$a->strings['Photo not found.'] = 'Kuvaa ei löytynyt.';
$a->strings['Profile picture successfully updated.'] = 'Profiilikuva päivitettty onnistuneesti.';
$a->strings['Crop Image'] = 'Rajaa kuva';
$a->strings['Please adjust the image cropping for optimum viewing.'] = 'Rajaa kuva sopivasti.';
$a->strings['Use Image As Is'] = 'Käytä kuvaa sellaisenaan';
$a->strings['Missing uploaded image.'] = 'Lähetetty kuva puuttuu.';
$a->strings['Profile Picture Settings'] = 'Profiilikuvan asetukset';
$a->strings['Current Profile Picture'] = 'Nykyinen profiilikuva';
$a->strings['Upload Profile Picture'] = 'Lähetä profiilikuva';
$a->strings['Upload Picture:'] = 'Lähetä kuva:';
$a->strings['or'] = 'tai';
$a->strings['skip this step'] = 'ohita tämä vaihe';
$a->strings['select a photo from your photo albums'] = 'valitse kuva albumeistasi';
$a->strings['There was a validation error, please make sure you\'re logged in with the account you want to remove and try again.'] = 'Validointivirhe. Varmista, että olet kirjautunut sisään poistettavalla tilillä, ja yritä uudelleen.';
$a->strings['If this error persists, please contact your administrator.'] = 'Jos virhe on jatkuva, ota yhteyttä ylläpitäjään.';
$a->strings['[Friendica System Notify]'] = '[Friendican järjestelmäilmoitus]';
$a->strings['User deleted their account'] = 'Käyttäjä poisti tilinsä';
$a->strings['On your Friendica node an user deleted their account. Please ensure that their data is removed from the backups.'] = 'Friendica-solmullasi ollut käyttäjä poisti tilinsä. Varmista, että hänen tietonsa poistetaan varmuuskopioista.';
$a->strings['The user id is %d'] = 'Käyttäjätunnus on %d';
$a->strings['Your account has been successfully removed. Bye bye!'] = 'Tilisi on poistettu onnistuneesti. Hei hei!';
$a->strings['Remove My Account'] = 'Poista tilini';
$a->strings['This will completely remove your account. Once this has been done it is not recoverable.'] = 'Tämä poistaa käyttäjätilisi pysyvästi. Poistoa ei voi perua myöhemmin.';
$a->strings['Please enter your password for verification:'] = 'Syötä salasanasi varmistusta varten:';
$a->strings['Do you want to ignore this server?'] = 'Haluatko sivuuttaa tämän palvelimen?';
$a->strings['Do you want to unignore this server?'] = 'Haluatko lakata sivuuttamasta tätä palvelinta?';
$a->strings['Remote server settings'] = 'Etäpalvelinasetukset';
$a->strings['Server URL'] = 'Palvelimen verkko-osoite';
$a->strings['Settings saved'] = 'Asetukset tallennettu';
$a->strings['Here you can find all the remote servers you have taken individual moderation actions against. For a list of servers your node has blocked, please check out the <a href="friendica">Information</a> page.'] = 'Täältä löydät kaikki etäpalvelimet, joihin olet suorittanut yksilön moderointitoimia. Luettelo palvelimista, jotka solmusi on estänyt, löytyy <a href="friendica">Tiedot</a>-sivulta.';
$a->strings['This includes ignored servers. You can ignore a server by clicking the "More" options button on a post, and selecting the option to "Ignore" the server the given post is from.'] = 'Tämä sisältää sivuutetut palvelimet. Voit sivuuttaa palvelimen klikkaamalla "Lisää" valintapainiketta julkaisussa ja valitsemalla valinnan "sivuuta" palvelin, jolta kyseinen julkaisu on tullut.';
$a->strings['Delete all your settings for the remote server'] = 'Poista kaikki asetuksesi etäpalvelimelta';
$a->strings['You have not taken individual moderation actions against any servers.'] = 'Et ole suorittanut yksilön moderointitoimenpiteitä millekään palvelimelle.';
$a->strings['Please enter your password to access this page.'] = 'Sivulle päästäksesi syötä salasanasi.';
$a->strings['App-specific password generation failed: The description is empty.'] = 'Sovelluskohtaisen salasanan luominen epäonnistui: kuvaus on tyhjä.';
$a->strings['App-specific password generation failed: This description already exists.'] = 'Sovelluskohtainen salasanan luominen epäonnistui: Tämä kuvaus on jo olemassa.';
$a->strings['New app-specific password generated.'] = 'Uusi sovelluskohtainen salasana luotu.';
$a->strings['App-specific passwords successfully revoked.'] = 'Sovelluskohtaisten salasanojen kumoaminen onnistui.';
$a->strings['App-specific password successfully revoked.'] = 'Sovelluskohtaisen salasanan kumoaminen onnistui.';
$a->strings['Two-factor app-specific passwords'] = 'Kaksivaiheiset sovelluskohtaiset salasanat';
$a->strings['<p>App-specific passwords are randomly generated passwords used instead your regular password to authenticate your account on third-party applications that don\'t support two-factor authentication.</p>'] = '<p>Sovelluskohtaiset salasanat ovat satunnaisesti luotuja salasanoja, joita käytetään tavallisen salasanan sijaan tilisi todentamiseen kolmannen osapuolen sovelluksissa, jotka eivät tue kaksivaiheista todennusta.</p>';
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
$a->strings['<p>You haven\'t finished configuring your authenticator app.</p>'] = '<p>Et ole tehnyt valmiiksi todennussovelluksesi määrityksiä.</p>';
$a->strings['<p>Your authenticator app is correctly configured.</p>'] = '<p>Todennussovelluksesi on määritelty oikein.</p>';
$a->strings['Recovery codes'] = 'Palautuskoodit';
$a->strings['Remaining valid codes'] = 'Jäljellä olevat kelpaavat koodit';
$a->strings['<p>These one-use codes can replace an authenticator app code in case you have lost access to it.</p>'] = '<p>Nämä ovat kertakäyttökoodeja, joilla voit korvata todennussovelluksen, jos olet menettänyt pääsyn siihen.</p>';
$a->strings['App-specific passwords'] = 'Sovelluskohtaiset salasanat';
$a->strings['Generated app-specific passwords'] = 'Luodut sovelluskohtaiset salasanat';
$a->strings['<p>These randomly generated passwords allow you to authenticate on apps not supporting two-factor authentication.</p>'] = '<p>Näiden satunnaisesti luotujen salasanojen avulla voit todentaa henkilöllisyytesi sovelluksissa, jotka eivät tue kaksivaiheista todennusta.</p>';
$a->strings['Current password:'] = 'Nykyinen salasana:';
$a->strings['You need to provide your current password to change two-factor authentication settings.'] = 'Sinun on annettava nykyinen salasanasi, jotta voit muuttaa kaksivaiheisen todennuksen asetuksia.';
$a->strings['Enable two-factor authentication'] = 'Ota kaksivaiheinen todennus käyttöön';
$a->strings['Disable two-factor authentication'] = 'Ota kaksivaiheinen todennus pois käytöstä';
$a->strings['Show recovery codes'] = 'Näytä palautuskoodit';
$a->strings['Manage app-specific passwords'] = 'Hallinnoi sovelluskohtaisia salasanoja';
$a->strings['Manage trusted browsers'] = 'Hallinnoi luotettuja selaimia';
$a->strings['Finish app configuration'] = 'Tee valmiiksi sovelluksen määrittelyt';
$a->strings['New recovery codes successfully generated.'] = 'Uudet palautuskoodit luotu onnistuneesti';
$a->strings['Two-factor recovery codes'] = 'Kaksivaiheiset palautuskoodit';
$a->strings['<p>Recovery codes can be used to access your account in the event you lose access to your device and cannot receive two-factor authentication codes.</p><p><strong>Put these in a safe spot!</strong> If you lose your device and don’t have the recovery codes you will lose access to your account.</p>'] = '<p>Palautuskoodeja voidaan käyttää tilillesi kirjautumiseen, jos menetät laitteesi ja et voi vastaanottaa kaksivaiheisen todennuksen koodeja.</p><p><strong>Säilytä nämä turvallisessa paikassa!</strong> Jos kadotat laitteesi etkä ole tallentanut palautuskoodeja, menetät pääsyn tilillesi.</p>';
$a->strings['When you generate new recovery codes, you must copy the new codes. Your old codes won’t work anymore.'] = 'Kun luot uudet palautuskoodit, sinun täytyy kopioida uudet koodit. Vanhat koodisi eivät toimi enää.';
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
</dl>'] = '<p>Tai voit syöttää todennusasetukset manuaalisesti:</p>
<dl>
	<dt>Liikkeeseenlaskija</dt>
	<dd>%s</dd>
	<dt>Tilin nimi</dt>
	<dd>%s</dd>
	<dt>Salainen avain</dt>
	<dd>%s</dd>
	<dt>Tyyppi</dt>
	<dd>Aikaperusteinen</dd>
	<dt>Numeroiden määrä</dt>
	<dd>6</dd>
	<dt>Hajautusalgoritmi</dt>
	<dd>SHA-1</dd>
</dl>';
$a->strings['Two-factor code verification'] = 'Kaksivaiheinen kooditodennus';
$a->strings['<p>Please scan this QR Code with your authenticator app and submit the provided code.</p>'] = '<p>Skannaa tämä QR-koodi todennussovelluksellasi ja syötä annettu koodi.';
$a->strings['<p>Or you can open the following URL in your mobile device:</p><p><a href="%s">%s</a></p>'] = '<p>Tai voi avata seuraavan URL-osoitteen mobiililaitteellasi:</p><p><a href="%s">%s</a></p>';
$a->strings['Verify code and enable two-factor authentication'] = 'Vahvista koodi ja ota käyttöön kaksivaiheinen todennus';
$a->strings['Export account'] = 'Vie tili';
$a->strings['Export your account info and contacts. Use this to make a backup of your account and/or to move it to another server.'] = 'Vie tilin tiedot ja yhteystiedot. Käytä tätä tilisi varmuuskopiointiin ja/tai siirtämiseen toiselle palvelimelle.';
$a->strings['Export all'] = 'Vie kaikki';
$a->strings['Export your account info, contacts and all your items as json. Could be a very big file, and could take a lot of time. Use this to make a full backup of your account (photos are not exported)'] = 'Vie tilitietosi, kontaktisi ja kaikki kohteesi JSON-tiedostona. Tiedosto voi olla erittäin suuri ja sen vienti voi kestää kauan. Käytä tätä toimintoa tilisi täydellisen varmuuskopion luomiseen (valokuvia ei viedä).';
$a->strings['Export Contacts to CSV'] = 'Vie kontaktit CSV-muodossa';
$a->strings['Export the list of the accounts you are following as CSV file. Compatible to e.g. Mastodon.'] = 'Vie lista seuraamistasi tileistä CSV-tiedostona. Yhteensopiva mm. Mastodonin kanssa.';
$a->strings['The top-level post isn\'t visible.'] = 'Ylätason julkaisu ei ole näkyvillä.';
$a->strings['The top-level post was deleted.'] = 'Ylätason julkaisu on poistettu.';
$a->strings['This node has blocked the top-level author or the author of the shared post.'] = 'Ylätason julkaisija tai jaetun julkaisun julkaisija on estetty tällä solmulla.';
$a->strings['You have ignored or blocked the top-level author or the author of the shared post.'] = 'Olet sivuuttanut tai estänyt ylätason julkaisijan tai jaetun julkaisun julkaisijan.';
$a->strings['You have ignored the top-level author\'s server or the shared post author\'s server.'] = 'Olet sivuuttanut ylätason julkaisijan palvelimen tai jaetun julkaisun tekijän palvelimen.';
$a->strings['Conversation Not Found'] = 'Keskustelua ei löytynyt';
$a->strings['Unfortunately, the requested conversation isn\'t available to you.'] = 'Valitettavasti pyytämäsi keskustelu ei ole sinun saatavillasi.';
$a->strings['Possible reasons include:'] = 'Mahdollisia syitä ovat muun muassa:';
$a->strings['Go back'] = 'Palaa takaisin';
$a->strings['Stack trace:'] = 'Pinojälki:';
$a->strings['Exception thrown in %s:%d'] = 'Poikkeus aiheutui kohdassa %s:%d';
$a->strings['At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name (pen name), an username (nickname) and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.'] = 'Rekisteröitymisen yhteydessä sekä käyttäjätilin ja hänen kontaktiensa välisen viestinnän mahdollistamiseksi käyttäjän on annettava näyttönimi (nimimerkki), käyttäjätunnus (lempinimi) ja toimiva sähköpostiosoite. Nimet ovat näkyvissä tilin profiilisivulla kaikille sivun kävijöille, vaikka muita profiilitietoja ei näytetä. Sähköpostiosoitetta käytetään vain käyttäjälle lähetettäviin ilmoituksiin vuorovaikutuksesta, mutta se ei ole muille näkyvissä. Tilin lisääminen solmun käyttäjähakemistoon tai yleiseen käyttäjähakemistoon on vapaaehtoista ja sitä voi hallita käyttäjäasetuksissa; se ei ole välttämätöntä viestinnän kannalta. ';
$a->strings['This data is required for communication and is passed on to the nodes of the communication partners and is stored there. Users can enter additional private data that may be transmitted to the communication partners accounts.'] = 'Nämä tiedot ovat välttämättömiä viestinnän kannalta, ja ne välitetään viestintäkumppaneiden solmuihin, joihin ne tallennetaan. Käyttäjät voivat syöttää muita henkilökohtaisia tietoja, jotka saatetaan välittää viestintäkumppaneiden tileille.';
$a->strings['At any point in time a logged in user can export their account data from the <a href="%1$s/settings/userexport">account settings</a>. If the user wants to delete their account they can do so at <a href="%1$s/settings/removeme">%1$s/settings/removeme</a>. The deletion of the account will be permanent. Deletion of the data will also be requested from the nodes of the communication partners.'] = 'Kirjautunut käyttäjä voi milloin tahansa viedä tilitietonsa <a href="%1$s/settings/userexport">tilin asetuksista</a>. Jos käyttäjä haluaa poistaa tilinsä, hän voi tehdä sen osoitteessa <a href="%1$s/settings/removeme">%1$s/settings/removeme</a>. Tilin poistaminen on lopullista. Tietojen poistamista pyydetään myös viestintäkumppaneiden solmuilta.';
$a->strings['Privacy Statement'] = 'Tietosuojalausunto';
$a->strings['Rules'] = 'Säännöt';
$a->strings['Parameter uri_id is missing.'] = 'Parameterin uri_id puuttuu.';
$a->strings['The requested item doesn\'t exist or has been deleted.'] = 'Pyytämäsi kohde ei ole olemassa tai se on poistettu.';
$a->strings['You are now logged in as %s'] = 'Olet nyt kirjautunut sisään tilillä %s';
$a->strings['Switch between your accounts'] = 'Vaihda tiliesi välillä';
$a->strings['Manage your accounts'] = 'Hallinnoi tilejäsi';
$a->strings['Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions'] = 'Vaihda eri identiteettien tai yhteisö-/ryhmäsivujen välillä, jotka jakavat tilisi tiedot tai joille sinulle on myönnetty hallintaoikeudet.';
$a->strings['Select an identity to manage: '] = 'Valitse identiteetti hallitavaksi:';
$a->strings['User imports on closed servers can only be done by an administrator.'] = 'Suljetuilla palvelimilla vain ylläpitäjät voivat tuoda käyttäjiä.';
$a->strings['Move account'] = 'Siirrä tili';
$a->strings['You can import an account from another Friendica server.'] = 'Voit tuoda käyttäjätilin toiselta Friendica-palvelimelta.';
$a->strings['You need to export your account from the old server and upload it here. We will recreate your old account here with all your contacts. We will try also to inform your friends that you moved here.'] = 'Sinun on viety tilisi vanhalta palvelimelta ja ladattava se tänne. Luomme vanhan tilisi uudelleen tänne kaikkine yhteystietoinesi. Yritämme myös ilmoittaa kavereillesi, että olet siirtynyt tänne.';
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
$a->strings['Complete the walk-through to get a quick overview of your profile and network tabs, establish new connections and find groups to join.'] = 'Suorita opastettu kierros, niin saat nopean yleiskatsauksen profiili- ja verkosto-välilehdistä, voit luoda uusia yhteyksiä ja löytää ryhmiä, joihin liittyä.';
$a->strings['Go to Your Settings'] = 'Omat Asetukset';
$a->strings['On your <em>Settings</em> page -  change your initial password. Also make a note of your Identity Address. This looks just like an email address - and will be useful in making friends on the free social web.'] = 'Muuta alkuperäinen salasanasi <em>Asetukset</em>-sivulla. Kirjoita myös muistiin identiteettiosoitteesi. Se näyttää aivan sähköpostiosoitteelta ja siitä on hyötyä, kun haluat hankkia ystäviä ilmaisessa sosiaalisessa verkostossa.';
$a->strings['Review the other settings, particularly the privacy settings. An unpublished directory listing is like having an unlisted phone number. In general, you should probably publish your listing - unless all of your friends and potential friends know exactly how to find you.'] = 'Tarkista muut asetukset, erityisesti tietosuoja-asetukset. Julkaisematon hakemistomerkintä on kuin salainen puhelinnumero. Yleensä sinun kannattaa todennäköisesti julkaista merkintäsi, elleivät kaikki ystäväsi ja potentiaaliset ystäväsi tiedä tarkalleen, miten sinut löytää.';
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
$a->strings['Your Contacts page is your gateway to managing friendships and connecting with friends on other networks. Typically you enter their address or site URL in the <em>Add New Contact</em> dialog.'] = 'Sinun kontaktisi -sivu on portti kaverisuhteiden hallinnointiin ja suhteiden muodostamiseen muissa verkostoissa oleviin kavereihin. Yleinen tapa on syöttää heidän osoitteensa tai sivunsa URL  <em>Lisää uusi kontakti</em> -valintaikkunassa.';
$a->strings['Go to Your Site\'s Directory'] = 'Näytä oman sivuston luettelo';
$a->strings['The Directory page lets you find other people in this network or other federated sites. Look for a <em>Connect</em> or <em>Follow</em> link on their profile page. Provide your own Identity Address if requested.'] = 'Hakemistosivulta voit löytää muita tämän verkoston tai muiden federoitujen sivustojen ihmisiä. Etsi <em>Yhdistä</em>- tai <em>Seuraa</em>-linkki heidän profiilisivuiltaan. Anna identiteettiosoitteesi, jos sitä pyydetään.';
$a->strings['Finding New People'] = 'Kavereiden hankkiminen';
$a->strings['On the side panel of the Contacts page are several tools to find new friends. We can match people by interest, look up people by name or interest, and provide suggestions based on network relationships. On a brand new site, friend suggestions will usually begin to be populated within 24 hours.'] = 'Kontaktit-sivun sivupaneelissa on useita työkaluja uusien kaverien löytämiseen. Voimme yhdistää käyttäjiä toisiinsa perustuen heidän mielenkiinnonkohteisiinsa, etsiä käyttäjiä nimellä tai mielenkiinnonkohteen perusteella, ja tarjota ehdotuksia verkostosuhteisiin pohjautuen. Upouudella sivulla kaveriehdotukset alkavat yleensä tulla esiin 24 tunnin sisään.';
$a->strings['Add Your Contacts To Circle'] = 'Lisää yhteystietosi piiriin';
$a->strings['Once you have made some friends, organize them into private conversation circles from the sidebar of your Contacts page and then you can interact with each circle privately on your Network page.'] = 'Kun olet saanut ystäviä, järjestä heidät yksityisiin keskustelupiireihin kontaktit-sivun sivupalkista. Voit sitten olla vuorovaikutuksessa kunkin piirin kanssa yksityisesti verkostosivullasi.';
$a->strings['Why Aren\'t My Posts Public?'] = 'Miksi julkaisuni eivät ole julkisia?';
$a->strings['Friendica respects your privacy. By default, your posts will only show up to people you\'ve added as friends. For more information, see the help section from the link above.'] = 'Friendica kunnioittaa yksityisyyttäsi. Julkaisusi näkyvät oletuksena henkilöille, jotka olet lisännyt kavereiksesi. Katso lisätietoja yllä olevasta ohje-osion linkistä.';
$a->strings['Getting Help'] = 'Miten saada apua';
$a->strings['Go to the Help Section'] = 'Siirry ohjeosioon';
$a->strings['Our <strong>help</strong> pages may be consulted for detail on other program features and resources.'] = 'Muista ohjelman ominaisuuksista ja resursseista saat lisätietoja <strong>Ohje</strong> -sivuiltamme.';
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
$a->strings['%1$s commented on your comment %2$s'] = '%1$s kommentoi kommenttiisi %2$s';
$a->strings['%1$s commented in their thread %2$s'] = '%1$s kommentoi ketjuunsa %2$s';
$a->strings['%1$s commented in their thread'] = '%1$s kommentoi ketjuunsa';
$a->strings['%1$s commented in the thread %2$s from %3$s'] = '%1$s kommentoi ketjussa %2$s lähteestä %3$s';
$a->strings['%1$s commented in the thread from %3$s'] = '%1$s kommentoi ketjuun %3$s';
$a->strings['%1$s commented on your thread %2$s'] = '%1$s kommentoi säikeeseesi %2$s';
$a->strings['[Friendica:Notify]'] = '[Friendica: Ilmoitus]';
$a->strings['%s New mail received at %s'] = '%s Uusi viesti saapunut %s';
$a->strings['%1$s sent you a new private message at %2$s.'] = '%1$s lähetti sinulle uuden yksityisviestin kohteessa %2$s.';
$a->strings['a private message'] = 'yksityisviesti';
$a->strings['%1$s sent you %2$s.'] = '%1$s lähetti sinulle %2$s.';
$a->strings['Please visit %s to view and/or reply to your private messages.'] = 'Katso yksityisviestisi kohteessa %s.';
$a->strings['%1$s commented on %2$s\'s %3$s %4$s'] = '%1$s kommentoi käyttäjän %2$s %3$s %4$s';
$a->strings['%1$s commented on your %2$s %3$s'] = '%1$s kommentoi sinun %2$s %3$s';
$a->strings['%1$s commented on their %2$s %3$s'] = '%1$s kommentoi omaa %2$s %3$s';
$a->strings['%1$s Comment to conversation #%2$d by %3$s'] = '%1$s Kommentti keskusteluun aiheesta #%2$d käyttäjältä %3$s';
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
$a->strings['\'%1$s\' has chosen to accept you a fan, which restricts some forms of communication - such as private messaging and some profile interactions. If this is a celebrity or community page, these settings were applied automatically.'] = '\'%1$s\' on hyväksynyt sinut faniksi. Tämä rajoittaa joitain kommunikointitapoja – kuten yksityisviestiettely ja joitain profiilitoimintoja. Jos tämä on julkisuuden henkilö tai yhteisösivu, asetukset on asetettu automaattisesti.';
$a->strings['\'%1$s\' may choose to extend this into a two-way or more permissive relationship in the future.'] = '\'%1$s\' voi halutessaan laajentaa tulevaisuudessa suhteenne kahdenväliseksi tai sallivammaksi.';
$a->strings['Please visit %s  if you wish to make any changes to this relationship.'] = 'Käy osoitteessa %s muokkaamaan tätä kaverisuhdetta.';
$a->strings['registration request'] = 'rekisteröintipyyntö';
$a->strings['You\'ve received a registration request from \'%1$s\' at %2$s'] = 'Olet vastaanottanut rekisteröintipyynnön henkilöltä \'%1$s\' kohteessa %2$s';
$a->strings['You\'ve received a [url=%1$s]registration request[/url] from %2$s.'] = 'Olet vastaanottanut [url=%1$s]rekisteröintipyynnön[/url] henkilöltä %2$s.';
$a->strings['Display Name:	%s
Site Location:	%s
Login Name:	%s (%s)'] = 'Näyttönimi:	%s
Sivuston sijainti:	%s
Käyttäjätunnus:	%s (%s)';
$a->strings['Please visit %s to approve or reject the request.'] = 'Hyväksy tai hylkää pyyntö %s-sivustossa.';
$a->strings['new registration'] = 'uusi rekisteröityminen';
$a->strings['You\'ve received a new registration from \'%1$s\' at %2$s'] = 'Olet saanut uuden rekisteröitymisen nimimerkiltä %1$s kohteessa %2$s';
$a->strings['You\'ve received a [url=%1$s]new registration[/url] from %2$s.'] = 'Olet saanut [url=%1$s]uuden rekisteröitymisen[/url] nimimerkiltä %2$s.';
$a->strings['Please visit %s to have a look at the new registration.'] = 'Käy osoitteessa %s nähdäksesi uuden rekisteröitymisen.';
$a->strings['%s %s tagged you'] = '%s %s mainitsi sinut';
$a->strings['%s %s shared a new post'] = '%s %s jakoi uuden julkaisun';
$a->strings['%1$s %2$s liked your post #%3$d'] = '%1$s %2$s tykkäsi julkaisustasi #%3$d';
$a->strings['%1$s %2$s liked your comment on #%3$d'] = '%1$s %2$s tykkäsi kommentistasi #%3$d';
$a->strings['This message was sent to you by %s, a member of the Friendica social network.'] = 'Viestin lähetti %s Friendica-sosiaaliverkoston kautta.';
$a->strings['You may visit them online at %s'] = 'Voit vierailla heidän luonaan netissä paikassa %s';
$a->strings['Please contact the sender by replying to this post if you do not wish to receive these messages.'] = 'Ota yhteyttä lähettäjään vastaamalla tähän julkaisuun, jos et halua enää vastaanottaa näitä viestejä.';
$a->strings['%s posted an update.'] = '%s julkaisi päivityksen.';
$a->strings['Public Message'] = 'Julkinen viesti';
$a->strings['Unlisted Message'] = 'Listaamaton viesti';
$a->strings['This entry was edited'] = 'Tätä kohdetta on muokattu';
$a->strings['Connector Message'] = 'Yhdistäjäviesti';
$a->strings['Pinned to your wall'] = 'Kiinnitetty seinällesi';
$a->strings['Delete globally'] = 'Poista maailmanlaajuisesti';
$a->strings['Remove locally'] = 'Poista paikallisesti';
$a->strings['Block %s'] = 'Estä %s';
$a->strings['Ignore %s'] = 'Sivuuta %s';
$a->strings['Collapse %s'] = 'Laskosta piiloon %s';
$a->strings['Report this post'] = 'Raportoi tämä julkaisu';
$a->strings['Save to folder'] = 'Tallenna kansioon';
$a->strings['I will attend'] = 'Osallistun';
$a->strings['I will not attend'] = 'En aio osallistua';
$a->strings['I might attend'] = 'Saatan osallistua';
$a->strings['Turn off related notifications'] = 'Laita pois päältä liittyvät ilmoitukset';
$a->strings['Turn on related notifications'] = 'Laita päälle liittyvät ilmoitukset';
$a->strings['Notifications turned off for this post'] = 'Tämän julkaisun ilmoitukset on laitettu pois päältä';
$a->strings['Bookmark'] = 'Lisää kirjanmerkki';
$a->strings['Remove bookmark'] = 'Pois kirjanmerkki';
$a->strings['Starred'] = 'Tähdelliset';
$a->strings['Pin to your wall'] = 'Kiinnitä seinällesi';
$a->strings['Unpin from your wall'] = 'Poista kiinnitys seinältäsi';
$a->strings['Add tag to post'] = 'Lisää tunniste julkaisuun';
$a->strings['I like this (toggle)'] = 'Tykkään tästä (vaihda)';
$a->strings['Like'] = 'Tykkää';
$a->strings['I don\'t like this (toggle)'] = 'En tykkää tästä (vaihda)';
$a->strings['Dislike'] = 'En tykkää';
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
$a->strings['Post comment'] = 'Julkaise kommentti';
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
$a->strings['in %1$d %2$s'] = 'kohteessa %1$d %2$s';
$a->strings['%1$d %2$s ago'] = '%1$d %2$s sitten';
$a->strings['Notification from Friendica'] = 'Ilmoitus Friendicasta';
$a->strings['Empty Post'] = 'Tyhjä julkaisu';
$a->strings['Note'] = 'Huomautus';
$a->strings['Ensure that the image has the correct permissions, allowing all users to view it.'] = 'Varmista, että kuvalla on oikeat käyttöoikeudet, jotka sallivat kaikkien käyttäjien nähdä sen.';
$a->strings['Appearance'] = 'Ulkoasu';
$a->strings['Accent color'] = 'Korostusväri';
$a->strings['Copy or paste theme settings'] = 'Kopioi ja liitä teema-asetukset';
$a->strings['You can copy this text to share your theme settings with others. Pasting here updates the theme settings below. Afterwards, if you want, click the save button below to use the new settings.'] = 'Voit kopioida tämän tekstin jakaaksesi teema-asetuksesi muiden kanssa. Jos liität tähän muualta saadut teema-asetukset, alla olevat asetukset päivitetään. Klikkaa lopuksi tallennuspainiketta käyttääksesi uusia asetuksia.';
$a->strings['Navigation bar background color'] = 'Navigointipalkin taustaväri';
$a->strings['Navigation bar icon color '] = 'Navigointipalkin kuvakkeen väri';
$a->strings['Link color'] = 'Linkkiväri';
$a->strings['Set the background color'] = 'Aseta taustaväri';
$a->strings['Content background opacity'] = 'Sisällön taustan peittävyys';
$a->strings['Set the background image'] = 'Aseta taustakuva';
$a->strings['Background image style'] = 'Taustakuvan tyyli';
$a->strings['Always open Compose page'] = 'Avaa aina kirjoitussivu';
$a->strings['If enabled, the button to make a new post always opens a dedicated page (the <a href="/compose">Compose page</a>) instead of a small window on top of the current page. When disabled, the "Compose page" can be accessed with a middle click on the button to make a new post, or via a button in the small window.'] = 'Kun käytössä, painike, joka avaa uuden julkaisun, avaa sen aina erilliselle sivulle (<a href="/compose">kirjoitussivulle</a>) sen sijaan, että avaisi sen pieneen ikkunaan nykyisen sivun yläreunaan. Kun pois käytöstä, kirjoitussivulle pääsee keskiklikkaamalla uuden julkaisun painiketta tai pienessä ikkunassa olevasta painikkeesta.';
$a->strings['Login page background image'] = 'Kirjautumissivun taustakuva';
$a->strings['Login page background color'] = 'Kirjautumissivun taustaväri';
$a->strings['Leave background image and color empty to use theme defaults.'] = 'Jätä taustakuva ja väri tyhjiksi, jos haluat käyttää teeman oletusasetuksia.';
$a->strings['Top Banner'] = 'Yläbanneri';
$a->strings['Resize image to the width of the screen and show background color below on long pages.'] = 'Muuta kuvan kokoa näyttöleveyteen ja näytä pitkillä sivuilla alla taustaväri.';
$a->strings['Full screen'] = 'Koko näyttö';
$a->strings['Resize image to fill entire screen, clipping either the right or the bottom.'] = 'Muuta kuvan kokoa täyttämään ruutu; rajaus joko oikealta tai alhaalta.';
$a->strings['Single row mosaic'] = 'Yksirivinen mosaikki';
$a->strings['Resize image to repeat it in a single direction, either vertical or horizontal.'] = 'Muuta kuvan kokoa saadaksesi sen toistumaan yhteen suuntaan, joko pystysuoraan tai vaakasuoraan.';
$a->strings['Mosaic'] = 'Mosaiikki';
$a->strings['Repeat image to fill the screen.'] = 'Täytä ruutu toistamalla kuvaa.';
$a->strings['Back to top'] = 'Takaisin ylös';
$a->strings['Light'] = 'Vaalea';
$a->strings['Dark'] = 'Tumma';
$a->strings['Custom'] = 'Mukautettu';
$a->strings['Guest'] = 'Vieras';
$a->strings['Visitor'] = 'Vierailija';
$a->strings['Your postings with media'] = 'Mediaa sisältävät julkaisusi';
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
