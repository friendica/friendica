<?php

if(! function_exists("string_plural_select_nl")) {
function string_plural_select_nl($n){
	$n = intval($n);
	return intval($n != 1);
}}
$a->strings['Unable to locate original post.'] = 'Ik kan de originele post niet meer vinden.';
$a->strings['Post updated.'] = 'Post geupdate.';
$a->strings['Item wasn\'t stored.'] = 'Item is niet opgeslagen.';
$a->strings['Item couldn\'t be fetched.'] = 'Item kan niet worden opgehaald.';
$a->strings['Empty post discarded.'] = 'Lege post weggegooid.';
$a->strings['Item not found.'] = 'Item niet gevonden.';
$a->strings['Permission denied.'] = 'Toegang geweigerd';
$a->strings['No valid account found.'] = 'Geen geldige account gevonden.';
$a->strings['Password reset request issued. Check your email.'] = 'Verzoek om wachtwoord opnieuw in te stellen werd verstuurd. Kijk uw e-mail na.';
$a->strings['
		Dear %1$s,
			A request was recently received at "%2$s" to reset your account
		password. In order to confirm this request, please select the verification link
		below or paste it into your web browser address bar.

		If you did NOT request this change, please DO NOT follow the link
		provided and ignore and/or delete this email, the request will expire shortly.

		Your password will not be changed unless we can verify that you
		issued this request.'] = '
		Beste %1$s,
			Er is recent om "%2$s" een verzoek gekomen om je wachtwoord te resetten.
		Om dit verzoek te bevestigen, gelieve de verificatie link hieronder te volgen of in je browser te kopiëren.

		Als je dit verzoek NIET hebt gedaan, volg deze link dan NIET en negeer 
		en/of verwijder deze email, het verzoek zal binnenkort vanzelf ongeldig worden.

		Je wachtwoord zal niet aangepast worden tenzij we kunnen verifiëren
		dat je dit verzoek verzonden hebt.';
$a->strings['
		Follow this link soon to verify your identity:

		%1$s

		You will then receive a follow-up message containing the new password.
		You may change that password from your account settings page after logging in.

		The login details are as follows:

		Site Location:	%2$s
		Login Name:	%3$s'] = '
		Volg nu deze link om je identiteit te bevestigen:

		%1$s

		Je zal dan een boodschap krijgen met je nieuw wachtwoord.
		Je kunt je wachtwoord veranderen in je instelling pagina nadat je ingelogd bent.

		De login details zijn de volgende:

		Site locatie:	%2$s
		Login naam:	%3$s';
$a->strings['Password reset requested at %s'] = 'Op %s werd gevraagd je wachtwoord opnieuw in te stellen';
$a->strings['Request could not be verified. (You may have previously submitted it.) Password reset failed.'] = 'Verzoek kon niet geverifieerd worden. (Misschien heb je het voordien al ingediend.) Wachtwoord niet opnieuw ingesteld.';
$a->strings['Request has expired, please make a new one.'] = 'Aanvraag is verlopen, gelieve een nieuwe aan te maken.';
$a->strings['Forgot your Password?'] = 'Wachtwoord vergeten?';
$a->strings['Enter your email address and submit to have your password reset. Then check your email for further instructions.'] = 'Voer je e-mailadres in en verstuur het om je wachtwoord opnieuw in te stellen. Kijk dan je e-mail na voor verdere instructies.';
$a->strings['Nickname or Email: '] = 'Bijnaam of e-mail:';
$a->strings['Reset'] = 'Opnieuw';
$a->strings['Password Reset'] = 'Wachtwoord opnieuw instellen';
$a->strings['Your password has been reset as requested.'] = 'Je wachtwoord is opnieuw ingesteld zoals gevraagd.';
$a->strings['Your new password is'] = 'Je nieuwe wachtwoord is';
$a->strings['Save or copy your new password - and then'] = 'Bewaar of kopieer je nieuw wachtwoord - en dan';
$a->strings['click here to login'] = 'klik hier om in te loggen';
$a->strings['Your password may be changed from the <em>Settings</em> page after successful login.'] = 'Je kunt dit wachtwoord veranderen nadat je bent ingelogd op de <em>Instellingen></em> pagina.';
$a->strings['Your password has been reset.'] = 'Je wachtwoord is opnieuw ingesteld.';
$a->strings['
			Dear %1$s,
				Your password has been changed as requested. Please retain this
			information for your records (or change your password immediately to
			something that you will remember).
		'] = '
			Beste %1$s,
				Je wachtwoord is aangepast zoals je gevraagd hebt. Hou deze informatie
			alstublieft bij (of pas je wachtwoord onmiddellijk aan
			naar iets wat je je kan herinneren).
		';
$a->strings['
			Your login details are as follows:

			Site Location:	%1$s
			Login Name:	%2$s
			Password:	%3$s

			You may change that password from your account settings page after logging in.
		'] = '
			Je login details zijn de volgende:

			Site Locatie:	%1$s
			Login Naam:	%2$s
			Wachtwwoord:	%3$s

			Je kan dit wachtwoord in het account instellingen aanpassen nadat je ingelogd bent.
		';
$a->strings['Your password has been changed at %s'] = 'Je wachtwoord is veranderd op %s';
$a->strings['New Message'] = 'Nieuw Bericht';
$a->strings['No recipient selected.'] = 'Geen ontvanger geselecteerd.';
$a->strings['Unable to locate contact information.'] = 'Ik kan geen contact informatie vinden.';
$a->strings['Message could not be sent.'] = 'Bericht kon niet verzonden worden.';
$a->strings['Message collection failure.'] = 'Fout bij het verzamelen van berichten.';
$a->strings['Discard'] = 'Verwerpen';
$a->strings['Messages'] = 'Privéberichten';
$a->strings['Conversation not found.'] = 'Gesprek niet gevonden.';
$a->strings['Message was not deleted.'] = 'Bericht was niet gewist.';
$a->strings['Conversation was not removed.'] = 'Conversatie was niet verwijderd.';
$a->strings['Please enter a link URL:'] = 'Vul een internetadres/URL in:';
$a->strings['Send Private Message'] = 'Verstuur privébericht';
$a->strings['To:'] = 'Aan:';
$a->strings['Subject:'] = 'Onderwerp:';
$a->strings['Your message:'] = 'Jouw bericht:';
$a->strings['Upload photo'] = 'Foto uploaden';
$a->strings['Insert web link'] = 'Voeg een webadres in';
$a->strings['Please wait'] = 'Even geduld';
$a->strings['Submit'] = 'Verstuur';
$a->strings['No messages.'] = 'Geen berichten.';
$a->strings['Message not available.'] = 'Bericht niet beschikbaar.';
$a->strings['Delete message'] = 'Verwijder bericht';
$a->strings['D, d M Y - g:i A'] = 'D, d M Y - g:i A';
$a->strings['Delete conversation'] = 'Verwijder gesprek';
$a->strings['No secure communications available. You <strong>may</strong> be able to respond from the sender\'s profile page.'] = 'Geen beveiligde communicatie beschikbaar. Je kunt <strong>misschien</strong> antwoorden vanaf de profiel-pagina van de afzender.';
$a->strings['Send Reply'] = 'Verstuur Antwoord';
$a->strings['Unknown sender - %s'] = 'Onbekende afzender - %s';
$a->strings['You and %s'] = 'Jij en %s';
$a->strings['%s and You'] = '%s en jij';
$a->strings['%d message'] = [
	0 => '%d bericht',
	1 => '%d berichten',
];
$a->strings['Personal Notes'] = 'Persoonlijke Nota\'s';
$a->strings['Save'] = 'Bewaren';
$a->strings['User not found.'] = 'Gebruiker niet gevonden.';
$a->strings['Photo Albums'] = 'Fotoalbums';
$a->strings['Recent Photos'] = 'Recente foto\'s';
$a->strings['Upload New Photos'] = 'Nieuwe foto\'s uploaden';
$a->strings['everybody'] = 'iedereen';
$a->strings['Contact information unavailable'] = 'Contactinformatie niet beschikbaar';
$a->strings['Album not found.'] = 'Album niet gevonden';
$a->strings['Album successfully deleted'] = 'Album succesvol gedeeld';
$a->strings['Album was empty.'] = 'Het album was leeg';
$a->strings['Failed to delete the photo.'] = 'Foto verwijderen mislukt.';
$a->strings['a photo'] = 'een foto';
$a->strings['%1$s was tagged in %2$s by %3$s'] = '%1$s is gelabeld in %2$s door %3$s';
$a->strings['Public access denied.'] = 'Niet vrij toegankelijk';
$a->strings['No photos selected'] = 'Geen foto\'s geselecteerd';
$a->strings['Upload Photos'] = 'Upload foto\'s';
$a->strings['New album name: '] = 'Nieuwe albumnaam: ';
$a->strings['or select existing album:'] = 'Of selecteer bestaand album:';
$a->strings['Do not show a status post for this upload'] = 'Toon geen bericht op je tijdlijn van deze upload';
$a->strings['Permissions'] = 'Rechten';
$a->strings['Do you really want to delete this photo album and all its photos?'] = 'Wil je echt dit fotoalbum en alle foto\'s erin verwijderen?';
$a->strings['Delete Album'] = 'Verwijder album';
$a->strings['Cancel'] = 'Annuleren';
$a->strings['Edit Album'] = 'Album wijzigen';
$a->strings['Drop Album'] = 'Album verwijderen';
$a->strings['Show Newest First'] = 'Toon niewste eerst';
$a->strings['Show Oldest First'] = 'Toon oudste eerst';
$a->strings['View Photo'] = 'Bekijk foto';
$a->strings['Permission denied. Access to this item may be restricted.'] = 'Toegang geweigerd. Toegang tot dit item is mogelijk beperkt.';
$a->strings['Photo not available'] = 'Foto is niet beschikbaar';
$a->strings['Do you really want to delete this photo?'] = 'Wil je echt deze foto verwijderen?';
$a->strings['Delete Photo'] = 'Verwijder foto';
$a->strings['View photo'] = 'Bekijk foto';
$a->strings['Edit photo'] = 'Bewerk foto';
$a->strings['Delete photo'] = 'Foto verwijderen';
$a->strings['Use as profile photo'] = 'Gebruik als profielfoto';
$a->strings['Private Photo'] = 'Privé foto';
$a->strings['View Full Size'] = 'Bekijk in volledig formaat';
$a->strings['Tags: '] = 'Labels: ';
$a->strings['[Select tags to remove]'] = '[Selecteer tags om te verwijderen]';
$a->strings['New album name'] = 'Nieuwe albumnaam';
$a->strings['Caption'] = 'Onderschrift';
$a->strings['Add a Tag'] = 'Een label toevoegen';
$a->strings['Example: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping'] = 'Voorbeeld: @bob, @Barbara_Jansen, @jan@voorbeeld.nl, #Ardennen, #camping ';
$a->strings['Do not rotate'] = 'Niet roteren';
$a->strings['Rotate CW (right)'] = 'Roteren met de klok mee (rechts)';
$a->strings['Rotate CCW (left)'] = 'Roteren tegen de klok in (links)';
$a->strings['This is you'] = 'Dit ben jij';
$a->strings['Comment'] = 'Reacties';
$a->strings['Preview'] = 'Voorvertoning';
$a->strings['Loading...'] = 'Aan het laden...';
$a->strings['Select'] = 'Kies';
$a->strings['Delete'] = 'Verwijder';
$a->strings['I like this (toggle)'] = 'Vind ik leuk';
$a->strings['I don\'t like this (toggle)'] = 'Vind ik niet leuk';
$a->strings['Map'] = 'Kaart';
$a->strings['Delete this item?'] = 'Dit item verwijderen?';
$a->strings['toggle mobile'] = 'mobiel thema omwisselen';
$a->strings['Page not found.'] = 'Pagina niet gevonden';
$a->strings['You must be logged in to use addons. '] = 'Je moet ingelogd zijn om deze addons te kunnen gebruiken. ';
$a->strings['No system theme config value set.'] = 'Geen systeem thema configuratie ingesteld.';
$a->strings['The form security token was not correct. This probably happened because the form has been opened for too long (>3 hours) before submitting it.'] = 'De beveiligingstoken van het formulier was foutief. Dit gebeurde waarschijnlijk omdat het formulier te lang (> 3 uur)  is blijven open staan voor het werd verstuurd.';
$a->strings['All contacts'] = 'Alle contacten';
$a->strings['Followers'] = 'Volgers';
$a->strings['Following'] = 'Volgend';
$a->strings['Common'] = 'Algemeen';
$a->strings['Could not find any unarchived contact entry for this URL (%s)'] = 'Kon geen niet-gearchiveerde contacten vinden voor deze URL (%s)';
$a->strings['The contact entries have been archived'] = 'The contacten zijn gearchiveerd';
$a->strings['Could not find any contact entry for this URL (%s)'] = 'Kon geen contact vinden op deze URL (%s)';
$a->strings['The contact has been blocked from the node'] = 'Het contact is geblokkeerd van deze node';
$a->strings['Post update version number has been set to %s.'] = 'Bericht update versie is ingesteld op %s';
$a->strings['Check for pending update actions.'] = 'Controleren op uitgestelde update acties.';
$a->strings['Done.'] = 'Gedaan';
$a->strings['Execute pending post updates.'] = 'uitgestelde bericht update acties uitvoeren';
$a->strings['All pending post updates are done.'] = 'Alle uitgestelde bericht update acties zijn uitgevoerd';
$a->strings['Enter user nickname: '] = 'Geef een bijnaam in:';
$a->strings['User not found'] = 'Gebruiker niet gevonden';
$a->strings['Enter new password: '] = 'Geef nieuw wachtwoord:';
$a->strings['Password update failed. Please try again.'] = 'Wachtwoord-)wijziging mislukt. Probeer opnieuw.';
$a->strings['Password changed.'] = 'Wachtwoord gewijzigd.';
$a->strings['Enter user name: '] = 'Geef gebruikersnaam in:';
$a->strings['Enter user email address: '] = 'Geef een gebruiker email adres in:';
$a->strings['Enter a language (optional): '] = 'Geef uw taalkeuze in (optioneel):';
$a->strings['User is not pending.'] = 'Gebruiker is niet in behandeling.';
$a->strings['User has already been marked for deletion.'] = 'De gebruiker is reeds gemarkeerd voor verwijdering.';
$a->strings['Type "yes" to delete %s'] = 'Type "Ja" om te wissen %s';
$a->strings['Deletion aborted.'] = 'Verwijdering afgebroken.';
$a->strings['newer'] = 'nieuwere berichten';
$a->strings['older'] = 'oudere berichten';
$a->strings['Frequently'] = 'Frequent';
$a->strings['Hourly'] = 'Ieder uur';
$a->strings['Twice daily'] = 'Twee maal daags';
$a->strings['Daily'] = 'Dagelijks';
$a->strings['Weekly'] = 'Wekelijks';
$a->strings['Monthly'] = 'Maandelijks';
$a->strings['DFRN'] = 'DFRN';
$a->strings['OStatus'] = 'OStatus';
$a->strings['RSS/Atom'] = 'RSS/Atom';
$a->strings['Email'] = 'E-mail';
$a->strings['Diaspora'] = 'Diaspora';
$a->strings['Zot!'] = 'Zot!';
$a->strings['LinkedIn'] = 'LinkedIn';
$a->strings['XMPP/IM'] = 'XMPP/Chat';
$a->strings['MySpace'] = 'MySpace';
$a->strings['Google+'] = 'Google+';
$a->strings['pump.io'] = 'pump.io';
$a->strings['Twitter'] = 'Twitter';
$a->strings['Discourse'] = 'Toespraak';
$a->strings['Diaspora Connector'] = 'Diaspora Connector';
$a->strings['GNU Social Connector'] = 'GNU Social Connector';
$a->strings['ActivityPub'] = 'ActivityPub';
$a->strings['pnut'] = 'pnut';
$a->strings['and'] = 'en';
$a->strings['and %d other people'] = 'en %d anderen';
$a->strings['Visible to <strong>everybody</strong>'] = 'Zichtbaar voor <strong>iedereen</strong>';
$a->strings['Please enter a image/video/audio/webpage URL:'] = 'Geef een afbeelding/video/audio/webpagina in:';
$a->strings['Tag term:'] = 'Label:';
$a->strings['Save to Folder:'] = 'Bewaren in map:';
$a->strings['Where are you right now?'] = 'Waar ben je nu?';
$a->strings['Delete item(s)?'] = 'Item(s) verwijderen?';
$a->strings['New Post'] = 'Nieuw bericht';
$a->strings['Share'] = 'Delen';
$a->strings['upload photo'] = 'Foto uploaden';
$a->strings['Attach file'] = 'Bestand bijvoegen';
$a->strings['attach file'] = 'bestand bijvoegen';
$a->strings['Bold'] = 'Vet';
$a->strings['Italic'] = 'Cursief';
$a->strings['Underline'] = 'Onderstrepen';
$a->strings['Quote'] = 'Citeren';
$a->strings['Code'] = 'Broncode';
$a->strings['Image'] = 'Afbeelding';
$a->strings['Link'] = 'Link';
$a->strings['Link or Media'] = 'Link of media';
$a->strings['Set your location'] = 'Stel je locatie in';
$a->strings['set location'] = 'Stel uw locatie in';
$a->strings['Clear browser location'] = 'Verwijder locatie uit uw webbrowser';
$a->strings['clear location'] = 'Verwijder locatie uit uw webbrowser';
$a->strings['Set title'] = 'Titel plaatsen';
$a->strings['Categories (comma-separated list)'] = 'Categorieën (komma-gescheiden lijst)';
$a->strings['Permission settings'] = 'Instellingen van rechten';
$a->strings['Public post'] = 'Openbare post';
$a->strings['Message'] = 'Bericht';
$a->strings['Browser'] = 'Browser';
$a->strings['Open Compose page'] = 'Open de opstelpagina';
$a->strings['remove'] = 'verwijder';
$a->strings['Delete Selected Items'] = 'Geselecteerde items verwijderen';
$a->strings['%s reshared this.'] = '%s heeft dit gedeeld';
$a->strings['View %s\'s profile @ %s'] = 'Bekijk het profiel van %s @ %s';
$a->strings['Categories:'] = 'Categorieën:';
$a->strings['Filed under:'] = 'Bewaard onder:';
$a->strings['%s from %s'] = '%s van %s';
$a->strings['View in context'] = 'In context bekijken';
$a->strings['Local Community'] = 'Lokale Groep';
$a->strings['Posts from local users on this server'] = 'Berichten van lokale gebruikers op deze server';
$a->strings['Global Community'] = 'Globale gemeenschap';
$a->strings['Posts from users of the whole federated network'] = 'Berichten van gebruikers van het hele gefedereerde netwerk';
$a->strings['Latest Activity'] = 'Laatste activiteit';
$a->strings['Sort by latest activity'] = 'Sorteer naar laatste activiteit';
$a->strings['Latest Posts'] = 'Laatste Berichten';
$a->strings['Sort by post received date'] = 'Sorteren naar ontvangstdatum bericht';
$a->strings['Personal'] = 'Persoonlijk';
$a->strings['Posts that mention or involve you'] = 'Alleen berichten die jou vermelden of op jou betrekking hebben';
$a->strings['Starred'] = 'Met ster';
$a->strings['Favourite Posts'] = 'Favoriete berichten';
$a->strings['General Features'] = 'Algemene functies';
$a->strings['Photo Location'] = 'Foto Locatie';
$a->strings['Photo metadata is normally stripped. This extracts the location (if present) prior to stripping metadata and links it to a map.'] = 'Foto metadata wordt normaal verwijderd. Dit extraheert de locatie (indien aanwezig) vooraleer de metadata te verwijderen en verbindt die met een kaart.';
$a->strings['Post Composition Features'] = 'Functies voor het opstellen van berichten';
$a->strings['Explicit Mentions'] = 'Expliciete vermeldingen';
$a->strings['Add explicit mentions to comment box for manual control over who gets mentioned in replies.'] = 'Voeg expliciete vermeldingen toe aan het opmerkingenvak voor handmatige controle over wie in antwoorden wordt vermeld.';
$a->strings['Post/Comment Tools'] = 'Bericht-/reactiehulpmiddelen';
$a->strings['Post Categories'] = 'Categorieën berichten';
$a->strings['Add categories to your posts'] = 'Voeg categorieën toe aan je berichten';
$a->strings['Archives'] = 'Archieven';
$a->strings['Protocols'] = 'Protocollen';
$a->strings['Account Types'] = 'Account Types';
$a->strings['Saved Searches'] = 'Opgeslagen zoekopdrachten';
$a->strings['Saved Folders'] = 'Bewaarde Mappen';
$a->strings['Trending Tags'] = 'Populaire Tags';
$a->strings['Advanced Profile Settings'] = 'Geavanceerde Profiel Instellingen';
$a->strings['Tag Cloud'] = 'Tag Wolk';
$a->strings['Provide a personal tag cloud on your profile page'] = 'Voorzie een persoonlijk tag wolk op je profiel pagina';
$a->strings['Display Membership Date'] = 'Toon Lidmaatschap Datum';
$a->strings['Display membership date in profile'] = 'Toon lidmaatschap datum in profiel';
$a->strings['show more'] = 'toon meer';
$a->strings['event'] = 'gebeurtenis';
$a->strings['status'] = 'status';
$a->strings['photo'] = 'foto';
$a->strings['%1$s tagged %2$s\'s %3$s with %4$s'] = '%1$s labelde %3$s van %2$s met %4$s';
$a->strings['Follow Thread'] = 'Gesprek volgen';
$a->strings['View Status'] = 'Bekijk status';
$a->strings['View Profile'] = 'Bekijk profiel';
$a->strings['View Photos'] = 'Bekijk foto\'s';
$a->strings['Network Posts'] = 'Netwerkberichten';
$a->strings['View Contact'] = 'Bekijk contact';
$a->strings['Send PM'] = 'Stuur een privébericht';
$a->strings['Block'] = 'Blokkeren';
$a->strings['Ignore'] = 'Negeren';
$a->strings['Connect/Follow'] = 'Verbind/Volg';
$a->strings['Nothing new here'] = 'Niets nieuw hier';
$a->strings['Go back'] = 'Ga terug';
$a->strings['Clear notifications'] = 'Notificaties verwijderen';
$a->strings['Logout'] = 'Uitloggen';
$a->strings['End this session'] = 'Deze sessie beëindigen';
$a->strings['Login'] = 'Login';
$a->strings['Sign in'] = 'Inloggen';
$a->strings['Profile'] = 'Profiel';
$a->strings['Your profile page'] = 'Jouw profiel pagina';
$a->strings['Photos'] = 'Foto\'s';
$a->strings['Your photos'] = 'Jouw foto\'s';
$a->strings['Calendar'] = 'Kalender';
$a->strings['Personal notes'] = 'Persoonlijke nota\'s';
$a->strings['Your personal notes'] = 'Je persoonlijke nota\'s';
$a->strings['Home'] = 'Tijdlijn';
$a->strings['Home Page'] = 'Jouw tijdlijn';
$a->strings['Register'] = 'Registreer';
$a->strings['Create an account'] = 'Maak een accoount';
$a->strings['Help'] = 'Help';
$a->strings['Help and documentation'] = 'Hulp en documentatie';
$a->strings['Apps'] = 'Apps';
$a->strings['Addon applications, utilities, games'] = 'Extra toepassingen, hulpmiddelen of spelletjes';
$a->strings['Search'] = 'Zoeken';
$a->strings['Search site content'] = 'Doorzoek de inhoud van de website';
$a->strings['Full Text'] = 'Volledige tekst';
$a->strings['Tags'] = 'Labels';
$a->strings['Contacts'] = 'Contacten';
$a->strings['Community'] = 'Website';
$a->strings['Conversations on this and other servers'] = 'Gesprekken op deze en andere servers';
$a->strings['Directory'] = 'Gids';
$a->strings['People directory'] = 'Personengids';
$a->strings['Information'] = 'Informatie';
$a->strings['Information about this friendica instance'] = 'informatie over deze friendica server';
$a->strings['Terms of Service'] = 'Gebruiksvoorwaarden';
$a->strings['Terms of Service of this Friendica instance'] = 'Gebruiksvoorwaarden op deze Friendica server';
$a->strings['Network'] = 'Netwerk';
$a->strings['Conversations from your friends'] = 'Gesprekken van je vrienden';
$a->strings['Your posts and conversations'] = 'Jouw berichten en gesprekken';
$a->strings['Introductions'] = 'Verzoeken';
$a->strings['Friend Requests'] = 'Vriendschapsverzoeken';
$a->strings['Notifications'] = 'Notificaties';
$a->strings['See all notifications'] = 'Toon alle notificaties';
$a->strings['Mark as seen'] = 'Als \'gelezen\' markeren';
$a->strings['Private mail'] = 'Privéberichten';
$a->strings['Inbox'] = 'Inbox';
$a->strings['Outbox'] = 'Verzonden berichten';
$a->strings['Accounts'] = 'Gebruikers';
$a->strings['Manage other pages'] = 'Andere pagina\'s beheren';
$a->strings['Settings'] = 'Instellingen';
$a->strings['Account settings'] = 'Account instellingen';
$a->strings['Manage/edit friends and contacts'] = 'Beheer/Wijzig vrienden en contacten';
$a->strings['Admin'] = 'Beheer';
$a->strings['Site setup and configuration'] = 'Website opzetten en configureren';
$a->strings['Navigation'] = 'Navigatie';
$a->strings['Site map'] = 'Sitemap';
$a->strings['first'] = 'eerste';
$a->strings['prev'] = 'vorige';
$a->strings['next'] = 'volgende';
$a->strings['last'] = 'laatste';
$a->strings['Click to open/close'] = 'klik om te openen/sluiten';
$a->strings['$1 wrote:'] = '$1 schreef:';
$a->strings['Encrypted content'] = 'Versleutelde inhoud';
$a->strings['Invalid source protocol'] = 'Ongeldig bron protocol';
$a->strings['Invalid link protocol'] = 'Ongeldig verbinding protocol';
$a->strings['Loading more entries...'] = 'Meer berichten aan het laden...';
$a->strings['The end'] = 'Het einde';
$a->strings['Follow'] = 'Volg';
$a->strings['Add New Contact'] = 'Nieuw Contact toevoegen';
$a->strings['Enter address or web location'] = 'Voeg een webadres of -locatie in:';
$a->strings['Example: bob@example.com, http://example.com/barbara'] = 'Voorbeeld: jan@voorbeeld.be, http://voorbeeld.nl/barbara';
$a->strings['Connect'] = 'Verbinden';
$a->strings['%d invitation available'] = [
	0 => '%d uitnodiging beschikbaar',
	1 => '%d uitnodigingen beschikbaar',
];
$a->strings['Find People'] = 'Zoek mensen';
$a->strings['Enter name or interest'] = 'Vul naam of interesse in';
$a->strings['Examples: Robert Morgenstein, Fishing'] = 'Voorbeelden: Jan Peeters, Vissen';
$a->strings['Find'] = 'Zoek';
$a->strings['Friend Suggestions'] = 'Vriendschapsvoorstellen';
$a->strings['Similar Interests'] = 'Dezelfde interesses';
$a->strings['Random Profile'] = 'Willekeurig Profiel';
$a->strings['Invite Friends'] = 'Vrienden uitnodigen';
$a->strings['Global Directory'] = 'Globale gids';
$a->strings['Local Directory'] = 'Lokale gids';
$a->strings['Everyone'] = 'Iedereen';
$a->strings['Relationships'] = 'Relaties';
$a->strings['All Contacts'] = 'Alle Contacten';
$a->strings['All Protocols'] = 'Alle protocollen';
$a->strings['Everything'] = 'Alles';
$a->strings['Categories'] = 'Categorieën';
$a->strings['%d contact in common'] = [
	0 => '%d gedeeld contact',
	1 => '%d gedeelde contacten',
];
$a->strings['News'] = 'Nieuws';
$a->strings['Export'] = 'Exporteer';
$a->strings['Export calendar as ical'] = 'Exporteer kalender als ical';
$a->strings['Export calendar as csv'] = 'Exporteer kalender als csv';
$a->strings['No contacts'] = 'Geen contacten';
$a->strings['%d Contact'] = [
	0 => '%d contact',
	1 => '%d contacten',
];
$a->strings['View Contacts'] = 'Bekijk contacten';
$a->strings['Remove term'] = 'Verwijder zoekterm';
$a->strings['Trending Tags (last %d hour)'] = [
	0 => 'Populaire Tags (laatste %d uur)',
	1 => 'Populaire Tags (laatste %d uur)',
];
$a->strings['More Trending Tags'] = 'Meer Populaire Tags';
$a->strings['XMPP:'] = 'XMPP:';
$a->strings['Location:'] = 'Plaats:';
$a->strings['Network:'] = 'Netwerk:';
$a->strings['Unfollow'] = 'Stop volgen';
$a->strings['Yourself'] = 'Jezelf';
$a->strings['Mutuals'] = 'Gemeenschappelijk';
$a->strings['Post to Email'] = 'Verzenden per e-mail';
$a->strings['Public'] = 'Openbaar';
$a->strings['This content will be shown to all your followers and can be seen in the community pages and by anyone with its link.'] = 'Deze inhoud wordt aan al uw volgers getoond en is te zien op de communitypagina\'s en door iedereen met de link.';
$a->strings['Limited/Private'] = 'Beperkt/Privé';
$a->strings['This content will be shown only to the people in the first box, to the exception of the people mentioned in the second box. It won\'t appear anywhere public.'] = 'Deze inhoud wordt alleen getoond aan de mensen in het eerste vak, met uitzondering van de mensen die in het tweede vak worden genoemd. Het wordt nergens openbaar weergegeven.';
$a->strings['Show to:'] = 'Toon aan:';
$a->strings['Except to:'] = 'Behalve aan:';
$a->strings['CC: email addresses'] = 'CC: e-mailadressen';
$a->strings['Example: bob@example.com, mary@example.com'] = 'Voorbeeld: bob@voorbeeld.nl, an@voorbeeld.be';
$a->strings['Connectors'] = 'Connectors';
$a->strings['The database configuration file "config/local.config.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.'] = 'Het databaseconfiguratiebestand "config/local.config.php" kon niet worden weggeschreven. Je kunt de bijgevoegde tekst gebruiken om in een configuratiebestand aan te maken in de hoogste map van je webserver. ';
$a->strings['You may need to import the file "database.sql" manually using phpmyadmin or mysql.'] = 'Het kan nodig zijn om het bestand "database.sql" manueel te importeren met phpmyadmin of mysql.';
$a->strings['Could not find a command line version of PHP in the web server PATH.'] = 'Kan geen command-line-versie van PHP vinden in het PATH van de webserver.';
$a->strings['PHP executable path'] = 'PATH van het PHP commando';
$a->strings['Enter full path to php executable. You can leave this blank to continue the installation.'] = 'Vul het volledige pad in naar het php programma. Je kunt dit leeg laten om de installatie verder te zetten.';
$a->strings['Command line PHP'] = 'PHP-opdrachtregel';
$a->strings['PHP executable is not the php cli binary (could be cgi-fgci version)'] = 'PHP uitvoerbaar bestand is niet de php cli binary (zou kunnen de cgi-fgci versie zijn)';
$a->strings['Found PHP version: '] = 'Gevonden PHP versie:';
$a->strings['PHP cli binary'] = 'PHP cli binary';
$a->strings['The command line version of PHP on your system does not have "register_argc_argv" enabled.'] = 'De command-line versie van PHP op jouw systeem heeft "register_argc_argv" niet geactiveerd.';
$a->strings['This is required for message delivery to work.'] = 'Dit is nodig om het verzenden van berichten mogelijk te maken.';
$a->strings['PHP register_argc_argv'] = 'PHP register_argc_argv';
$a->strings['Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys'] = 'Fout: de "openssl_pkey_new" functie op dit systeem kan geen encryptie sleutels genereren';
$a->strings['If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".'] = 'Zie "http://www.php.net/manual/en/openssl.installation.php" wanneer u Friendica onder Windows draait.';
$a->strings['Generate encryption keys'] = 'Genereer encryptie sleutels';
$a->strings['Error: Apache webserver mod-rewrite module is required but not installed.'] = 'Fout: Apache-module mod-rewrite is vereist, maar niet geïnstalleerd.';
$a->strings['Apache mod_rewrite module'] = 'Apache mod_rewrite module';
$a->strings['Error: PDO or MySQLi PHP module required but not installed.'] = 'Fout: PDO of MySQLi PHP module vereist maar niet geïnstalleerd.';
$a->strings['Error: The MySQL driver for PDO is not installed.'] = 'Fout: de MySQL driver voor PDO is niet geïnstalleerd.';
$a->strings['PDO or MySQLi PHP module'] = 'PDO of MySQLi PHP module';
$a->strings['Error, XML PHP module required but not installed.'] = 'Fout: XML PHP module vereist maar niet geinstalleerd.';
$a->strings['XML PHP module'] = 'XML PHP module';
$a->strings['libCurl PHP module'] = 'libCurl PHP module';
$a->strings['Error: libCURL PHP module required but not installed.'] = 'Fout: PHP-module libCURL is vereist, maar niet geïnstalleerd.';
$a->strings['GD graphics PHP module'] = 'GD graphics PHP module';
$a->strings['Error: GD graphics PHP module with JPEG support required but not installed.'] = 'Fout: PHP-module GD graphics met JPEG support is vereist, maar niet geïnstalleerd.';
$a->strings['OpenSSL PHP module'] = 'OpenSSL PHP module';
$a->strings['Error: openssl PHP module required but not installed.'] = 'Fout: PHP-module openssl is vereist, maar niet geïnstalleerd.';
$a->strings['mb_string PHP module'] = 'mb_string PHP module';
$a->strings['Error: mb_string PHP module required but not installed.'] = 'Fout: PHP-module mb_string is vereist, maar niet geïnstalleerd.';
$a->strings['iconv PHP module'] = 'iconv PHP module';
$a->strings['Error: iconv PHP module required but not installed.'] = 'Fout: iconv PHP module vereist maar niet geïnstalleerd.';
$a->strings['POSIX PHP module'] = 'POSIX PHP module';
$a->strings['Error: POSIX PHP module required but not installed.'] = 'Fout: POSIX PHP module vereist maar niet geïnstalleerd.';
$a->strings['The web installer needs to be able to create a file called "local.config.php" in the "config" folder of your web server and it is unable to do so.'] = 'Het installatieprogramma moet een bestand "local.config.php" in de "config" map van je webserver aanmaken, maar kan dit niet doen. ';
$a->strings['This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.'] = 'Dit is meestal een permissieprobleem, omdat de webserver niet in staat is om in deze map bestanden weg te schrijven - ook al kun je dit zelf wel.';
$a->strings['At the end of this procedure, we will give you a text to save in a file named local.config.php in your Friendica "config" folder.'] = 'Op het einde van deze procedure zal ik je een tekst geven om te bewaren in een bestand local.config.php in Friendica "config" map. ';
$a->strings['config/local.config.php is writable'] = 'config/local.config.php is schrijfbaar ';
$a->strings['Friendica uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.'] = 'Friendica gebruikt het Smarty3 sjabloon systeem om zijn webpagina\'s weer te geven. Smarty3 compileert sjablonen naar PHP om de weergave te versnellen.';
$a->strings['In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top level folder.'] = 'Om deze gecompileerde sjablonen op te slaan moet de webserver schrijftoegang hebben tot de folder view/smarty3, t.o.v. van de hoogste folder van je Friendica-installatie.';
$a->strings['Please ensure that the user that your web server runs as (e.g. www-data) has write access to this folder.'] = 'Zorg ervoor dat de gebruiker waaronder je webserver runt (bijv. www-data) schrijf-toegang heeft tot deze map.';
$a->strings['Note: as a security measure, you should give the web server write access to view/smarty3/ only--not the template files (.tpl) that it contains.'] = 'Opmerking: voor een goede beveiliging zou je de webserver alleen schrijf-toegang moeten geven voor de map view/smarty3 -- niet voor de template bestanden (.tpl) die in die map zitten.';
$a->strings['view/smarty3 is writable'] = 'view/smarty3 is schrijfbaar';
$a->strings['Error message from Curl when fetching'] = 'Fout boodschap van Curl bij ophalen';
$a->strings['Url rewrite is working'] = 'Url rewrite werkt correct';
$a->strings['ImageMagick PHP extension is not installed'] = 'ImageMagick PHP extensie is niet geïnstalleerd';
$a->strings['ImageMagick PHP extension is installed'] = 'ImageMagick PHP extensie is geïnstalleerd';
$a->strings['Database already in use.'] = 'Database al in gebruik.';
$a->strings['Could not connect to database.'] = 'Kon geen toegang krijgen tot de database.';
$a->strings['Monday'] = 'Maandag';
$a->strings['Tuesday'] = 'Dinsdag';
$a->strings['Wednesday'] = 'Woensdag';
$a->strings['Thursday'] = 'Donderdag';
$a->strings['Friday'] = 'Vrijdag';
$a->strings['Saturday'] = 'Zaterdag';
$a->strings['Sunday'] = 'Zondag';
$a->strings['January'] = 'Januari';
$a->strings['February'] = 'Februari';
$a->strings['March'] = 'Maart';
$a->strings['April'] = 'April';
$a->strings['May'] = 'Mei';
$a->strings['June'] = 'Juni';
$a->strings['July'] = 'Juli';
$a->strings['August'] = 'Augustus';
$a->strings['September'] = 'September';
$a->strings['October'] = 'Oktober';
$a->strings['November'] = 'November';
$a->strings['December'] = 'December';
$a->strings['Mon'] = 'Maa';
$a->strings['Tue'] = 'Din';
$a->strings['Wed'] = 'Woe';
$a->strings['Thu'] = 'Don';
$a->strings['Fri'] = 'Vrij';
$a->strings['Sat'] = 'Zat';
$a->strings['Sun'] = 'Zon';
$a->strings['Jan'] = 'Jan';
$a->strings['Feb'] = 'Feb';
$a->strings['Mar'] = 'Maa';
$a->strings['Apr'] = 'Apr';
$a->strings['Jun'] = 'Jun';
$a->strings['Jul'] = 'Jul';
$a->strings['Aug'] = 'Aug';
$a->strings['Sep'] = 'Sep';
$a->strings['Oct'] = 'Okt';
$a->strings['Nov'] = 'Nov';
$a->strings['Dec'] = 'Dec';
$a->strings['Friendica can\'t display this page at the moment, please contact the administrator.'] = 'Friendica kan deze pagina momenteel niet weergeven, neem contact op met de beheerder.';
$a->strings['Enter a valid existing folder'] = 'Geef een geldige bestaande folder in';
$a->strings['Update %s failed. See error logs.'] = 'Wijziging %s mislukt. Lees de error logbestanden.';
$a->strings['
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can\'t do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.'] = '
				De Friendica ontwikkelaars hebben recent update %svrijgegeven,
 				maar wanneer ik deze probeerde te installeren ging het verschrikkelijk fout.
 				Dit moet snel opgelost worden en ik kan het niet alleen. Contacteer alstublieft
 				een Friendica ontwikkelaar als je mij zelf niet kan helpen. Mijn database kan ongeldig zijn.';
$a->strings['
Error %d occurred during database update:
%s
'] = '
Fout %d is opgetreden tijdens database update:
%s
';
$a->strings['Errors encountered performing database changes: '] = 'Fouten opgetreden tijdens database aanpassingen:';
$a->strings['%s: Database update'] = '%s: Database update';
$a->strings['%s: updating %s table.'] = '%s: tabel %s aan het updaten.';
$a->strings['Unauthorized'] = 'Onbevoegd';
$a->strings['Legacy module file not found: %s'] = 'Legacy module bestand niet gevonden: %s';
$a->strings['Everybody'] = 'Iedereen';
$a->strings['edit'] = 'verander';
$a->strings['add'] = 'toevoegen';
$a->strings['Approve'] = 'Goedkeuren';
$a->strings['Organisation'] = 'Organisatie';
$a->strings['Disallowed profile URL.'] = 'Niet toegelaten profiel adres.';
$a->strings['Blocked domain'] = 'Domein geblokeerd';
$a->strings['Connect URL missing.'] = 'Connectie URL ontbreekt.';
$a->strings['The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.'] = 'Het contact kon niet toegevoegd worden. Gelieve de relevante netwerk gegevens na te kijken in Instellingen -> Sociale Netwerken.';
$a->strings['The profile address specified does not provide adequate information.'] = 'Het opgegeven profiel adres bevat geen adequate informatie.';
$a->strings['No compatible communication protocols or feeds were discovered.'] = 'Er werden geen compatibele communicatieprotocols of feeds ontdekt.';
$a->strings['An author or name was not found.'] = 'Er werd geen auteur of naam gevonden.';
$a->strings['No browser URL could be matched to this address.'] = 'Er kan geen browser URL gematcht worden met dit adres.';
$a->strings['Unable to match @-style Identity Address with a known protocol or email contact.'] = 'Het @-stijl-identiteitsadres komt niet overeen met een nekend protocol of e-mailcontact.';
$a->strings['Use mailto: in front of address to force email check.'] = 'Gebruik mailto: voor het adres om een e-mailcontrole af te dwingen.';
$a->strings['Limited profile. This person will be unable to receive direct/personal notifications from you.'] = 'Profiel met restricties. Deze peresoon zal geen directe/persoonlijke notificaties van jou kunnen ontvangen.';
$a->strings['Unable to retrieve contact information.'] = 'Het was niet mogelijk informatie over dit contact op te halen.';
$a->strings['Starts:'] = 'Begint:';
$a->strings['Finishes:'] = 'Eindigt:';
$a->strings['all-day'] = 'de hele dag';
$a->strings['Sept'] = 'Sep';
$a->strings['today'] = 'vandaag';
$a->strings['month'] = 'maand';
$a->strings['week'] = 'week';
$a->strings['day'] = 'dag';
$a->strings['No events to display'] = 'Geen gebeurtenissen te tonen';
$a->strings['Access to this profile has been restricted.'] = 'Toegang tot dit profiel is beperkt.';
$a->strings['l, F j'] = 'l j F';
$a->strings['Edit event'] = 'Gebeurtenis bewerken';
$a->strings['Duplicate event'] = 'Duplicate gebeurtenis';
$a->strings['Delete event'] = 'Verwijder gebeurtenis';
$a->strings['l F d, Y \@ g:i A'] = 'l F d, Y \@ g:i A';
$a->strings['D g:i A'] = 'D g:i A';
$a->strings['g:i A'] = 'g:i A';
$a->strings['Show map'] = 'Toon kaart';
$a->strings['Hide map'] = 'Verberg kaart';
$a->strings['%s\'s birthday'] = '%s\'s verjaardag';
$a->strings['Happy Birthday %s'] = 'Gefeliciteerd %s';
$a->strings['activity'] = 'activiteit';
$a->strings['post'] = 'bericht';
$a->strings['bytes'] = 'bytes';
$a->strings['View on separate page'] = 'Bekijk op aparte pagina';
$a->strings['[no subject]'] = '[geen onderwerp]';
$a->strings['Wall Photos'] = 'Tijdlijn foto\'s';
$a->strings['Edit profile'] = 'Bewerk profiel';
$a->strings['Change profile photo'] = 'Profiel foto wijzigen';
$a->strings['Homepage:'] = 'Website:';
$a->strings['About:'] = 'Over:';
$a->strings['Atom feed'] = 'Atom feed';
$a->strings['F d'] = 'd F';
$a->strings['[today]'] = '[vandaag]';
$a->strings['Birthday Reminders'] = 'Verjaardagsherinneringen';
$a->strings['Birthdays this week:'] = 'Verjaardagen deze week:';
$a->strings['g A l F d'] = 'G l j F';
$a->strings['[No description]'] = '[Geen omschrijving]';
$a->strings['Event Reminders'] = 'Gebeurtenisherinneringen';
$a->strings['Upcoming events the next 7 days:'] = 'Evenementen de komende 7 dagen:';
$a->strings['Hometown:'] = 'Woonplaats:';
$a->strings['With:'] = 'Met:';
$a->strings['Since:'] = 'Sinds:';
$a->strings['Sexual Preference:'] = 'Seksuele Voorkeur:';
$a->strings['Political Views:'] = 'Politieke standpunten:';
$a->strings['Religious Views:'] = 'Geloof:';
$a->strings['Likes:'] = 'Houdt van:';
$a->strings['Dislikes:'] = 'Houdt niet van:';
$a->strings['Title/Description:'] = 'Titel/Beschrijving:';
$a->strings['Summary'] = 'Samenvatting';
$a->strings['Musical interests'] = 'Muzikale interesses';
$a->strings['Books, literature'] = 'Boeken, literatuur';
$a->strings['Television'] = 'Televisie';
$a->strings['Film/dance/culture/entertainment'] = 'Film/dans/cultuur/ontspanning';
$a->strings['Hobbies/Interests'] = 'Hobby\'s/Interesses';
$a->strings['Love/romance'] = 'Liefde/romance';
$a->strings['Work/employment'] = 'Werk';
$a->strings['School/education'] = 'School/opleiding';
$a->strings['Contact information and Social Networks'] = 'Contactinformatie en sociale netwerken';
$a->strings['SERIOUS ERROR: Generation of security keys failed.'] = 'ERNSTIGE FOUT: aanmaken van beveiligingssleutels mislukt.';
$a->strings['Login failed'] = 'Login mislukt';
$a->strings['Not enough information to authenticate'] = 'Niet genoeg informatie om te authentificeren';
$a->strings['Password can\'t be empty'] = 'Wachtwoord mag niet leeg zijn';
$a->strings['Empty passwords are not allowed.'] = 'Lege wachtwoorden zijn niet toegestaan';
$a->strings['The new password has been exposed in a public data dump, please choose another.'] = 'The nieuwe wachtwoord is gecompromitteerd in een publieke data dump, kies alsjeblieft een ander.';
$a->strings['Passwords do not match. Password unchanged.'] = 'Wachtwoorden komen niet overeen. Wachtwoord niet gewijzigd.';
$a->strings['An invitation is required.'] = 'Een uitnodiging is vereist.';
$a->strings['Invitation could not be verified.'] = 'Uitnodiging kon niet geverifieerd worden.';
$a->strings['Invalid OpenID url'] = 'Ongeldige OpenID url';
$a->strings['We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.'] = 'Er is een probleem opgetreden bij het inloggen met het opgegeven OpenID. Kijk alsjeblieft de spelling van deze ID na.';
$a->strings['The error message was:'] = 'De foutboodschap was:';
$a->strings['Please enter the required information.'] = 'Vul de vereiste informatie in.';
$a->strings['system.username_min_length (%s) and system.username_max_length (%s) are excluding each other, swapping values.'] = 'system.username_min_length (%s) en system.username_max_length (%s) sluiten elkaar uit. Waarden worden omgedraaid.';
$a->strings['Username should be at least %s character.'] = [
	0 => 'Gebruikersnaam moet minimaal %s tekens bevatten.',
	1 => 'Gebruikersnaam moet minimaal %s tekens bevatten',
];
$a->strings['Username should be at most %s character.'] = [
	0 => 'Gebruikersnaam mag maximaal %s tekens bevatten.',
	1 => 'Gebruikersnaam mag maximaal %s tekens bevatten.',
];
$a->strings['That doesn\'t appear to be your full (First Last) name.'] = 'Dat lijkt niet je volledige naam (voor- en achternaam) te zijn.';
$a->strings['Your email domain is not among those allowed on this site.'] = 'Je e-maildomein is op deze website niet toegestaan.';
$a->strings['Not a valid email address.'] = 'Geen geldig e-mailadres.';
$a->strings['The nickname was blocked from registration by the nodes admin.'] = 'De bijnaam werd geblokkeerd voor registratie door de node admin';
$a->strings['Cannot use that email.'] = 'Ik kan die e-mail niet gebruiken.';
$a->strings['Your nickname can only contain a-z, 0-9 and _.'] = 'Je bijnaam mag alleen a-z, 0-9 of _ bevatten.';
$a->strings['Nickname is already registered. Please choose another.'] = 'Bijnaam is al geregistreerd. Kies een andere.';
$a->strings['An error occurred during registration. Please try again.'] = 'Er is een fout opgetreden tijdens de registratie. Probeer opnieuw.';
$a->strings['An error occurred creating your default profile. Please try again.'] = 'Er is een fout opgetreden bij het aanmaken van je standaard profiel. Probeer opnieuw.';
$a->strings['An error occurred creating your self contact. Please try again.'] = 'Er is een fout opgetreden bij het aanmaken van je self contact. Probeer opnieuw.';
$a->strings['Friends'] = 'Vrienden';
$a->strings['Profile Photos'] = 'Profielfoto\'s';
$a->strings['
		Dear %1$s,
			the administrator of %2$s has set up an account for you.'] = '
		Beste %1$s,
			de administrator van %2$s heeft een gebruiker voor je aangemaakt.';
$a->strings['Registration details for %s'] = 'Registratie details voor %s';
$a->strings['
			Dear %1$s,
				Thank you for registering at %2$s. Your account is pending for approval by the administrator.

			Your login details are as follows:

			Site Location:	%3$s
			Login Name:		%4$s
			Password:		%5$s
		'] = '
			Hallo %1$s,
				Bedankt voor uw registratie op %2$s. Uw account wacht op dit moment op bevestiging door de administrator.

			Uw login details zijn:

			Site locatie:	%3$s
			Gebruikersnaam:		%4$s
			Wachtwoord:		%5$s
		';
$a->strings['Registration at %s'] = 'Registratie bij %s';
$a->strings['
				Dear %1$s,
				Thank you for registering at %2$s. Your account has been created.
			'] = '
				Beste %1$s,
				Bedankt voor je inschrijving op %2$s. Je gebruiker is aangemaakt.
			';
$a->strings['Addon not found.'] = 'Addon niet gevonden.';
$a->strings['Addon %s disabled.'] = 'Addon %s gedeactiveerd';
$a->strings['Addon %s enabled.'] = 'Addon %s geactiveerd';
$a->strings['Disable'] = 'Uitschakelen';
$a->strings['Enable'] = 'Inschakelen';
$a->strings['Administration'] = 'Beheer';
$a->strings['Addons'] = 'Addons';
$a->strings['Toggle'] = 'Schakelaar';
$a->strings['Author: '] = 'Auteur:';
$a->strings['Maintainer: '] = 'Onderhoud:';
$a->strings['Addon %s failed to install.'] = 'Installatie Addon %s is mislukt.';
$a->strings['Save Settings'] = 'Instellingen opslaan';
$a->strings['Reload active addons'] = 'Herlaad actieve addons';
$a->strings['Update has been marked successful'] = 'Wijziging succesvol gemarkeerd ';
$a->strings['Database structure update %s was successfully applied.'] = 'Database structuur update %s werd met succes toegepast.';
$a->strings['Executing of database structure update %s failed with error: %s'] = 'Uitvoering van de database structuur update %s is mislukt met fout: %s';
$a->strings['Executing %s failed with error: %s'] = 'Uitvoering van %s mislukt met fout: %s';
$a->strings['Update %s was successfully applied.'] = 'Wijziging %s geslaagd.';
$a->strings['Update %s did not return a status. Unknown if it succeeded.'] = 'Wijziging %s gaf geen status terug. We weten niet of de wijziging geslaagd is.';
$a->strings['There was no additional update function %s that needed to be called.'] = 'Er was geen bijkomende update functie %s die moest opgeroepen worden.';
$a->strings['No failed updates.'] = 'Geen mislukte wijzigingen';
$a->strings['Check database structure'] = 'Controleer de database structuur';
$a->strings['Failed Updates'] = 'Mislukte wijzigingen';
$a->strings['This does not include updates prior to 1139, which did not return a status.'] = 'Dit is zonder de wijzigingen voor 1139, welke geen status teruggaven.';
$a->strings['Mark success (if update was manually applied)'] = 'Markeren als succes (als aanpassing manueel doorgevoerd werd)';
$a->strings['Attempt to execute this update step automatically'] = 'Probeer deze stap automatisch uit te voeren';
$a->strings['No'] = 'Nee';
$a->strings['Yes'] = 'Ja';
$a->strings['Manage Additional Features'] = 'Beheer Bijkomende Features';
$a->strings['Other'] = 'Anders';
$a->strings['unknown'] = 'onbekend';
$a->strings['This page offers you some numbers to the known part of the federated social network your Friendica node is part of. These numbers are not complete but only reflect the part of the network your node is aware of.'] = 'Deze pagina toont je statistieken van het gekende deel van het gefedereerde sociale netwerk waarvan je Friendica node deel uitmaakt. Deze statistieken zijn niet volledig maar reflecteren het deel van het network dat jouw node kent.';
$a->strings['Federation Statistics'] = 'Federatie Statistieken';
$a->strings['PHP log currently enabled.'] = 'PHP log momenteel geactiveerd';
$a->strings['PHP log currently disabled.'] = 'PHP log momenteel gedeactiveerd';
$a->strings['Logs'] = 'Logs';
$a->strings['Clear'] = 'Wis';
$a->strings['Enable Debugging'] = 'Activeer Debugging';
$a->strings['Log file'] = 'Logbestand';
$a->strings['Must be writable by web server. Relative to your Friendica top-level directory.'] = 'De webserver moet hier kunnen schrijven. Relatief t.o.v. de hoogste folder binnen je Friendica-installatie.';
$a->strings['Log level'] = 'Log niveau';
$a->strings['PHP logging'] = 'PHP logging';
$a->strings['To temporarily enable logging of PHP errors and warnings you can prepend the following to the index.php file of your installation. The filename set in the \'error_log\' line is relative to the friendica top-level directory and must be writeable by the web server. The option \'1\' for \'log_errors\' and \'display_errors\' is to enable these options, set to \'0\' to disable them.'] = 'Om logging van PHP fouten en waarschuwingen te activeren, kan je het volgende toevoegen aan het begin van je index.php bestand van je installatie. De naam van het bestand die ingesteld is in de \'error_log\' lijn is relatief tegenover de friendica top-level folder en de server moet erin kunnen schrijven. De optie \'1\' voor \'log_errors\' en \'display_errors\' activeert deze opties, configureer \'0\' om ze te deactiveren. ';
$a->strings['View Logs'] = 'Bekijk Logs';
$a->strings['Show all'] = 'Toon alles';
$a->strings['Event details'] = 'Gebeurtenis details';
$a->strings['Inspect Deferred Worker Queue'] = 'Inspecteer wachtrij van uitgestelde workers';
$a->strings['This page lists the deferred worker jobs. This are jobs that couldn\'t be executed at the first time.'] = 'Deze pagine geeft alle uitgestelde workertaken. Dit zijn taken die niet onmiddelijk konden worden uitgevoerd';
$a->strings['Inspect Worker Queue'] = 'Taakwachtrij inspecteren';
$a->strings['This page lists the currently queued worker jobs. These jobs are handled by the worker cronjob you\'ve set up during install.'] = 'Deze pagina toont alle taken in de wachtrij. Deze taken worden behandeld door de geplande taak die je hebt ingesteld tijdens installatie.';
$a->strings['ID'] = 'ID';
$a->strings['Job Parameters'] = 'Taak parameters';
$a->strings['Created'] = 'Aangemaakt';
$a->strings['Priority'] = 'Prioriteit';
$a->strings['No special theme for mobile devices'] = 'Geen speciaal thema voor mobiele apparaten';
$a->strings['%s - (Experimental)'] = '%s - (Experimenteel)';
$a->strings['No community page'] = 'Geen groepspagina';
$a->strings['Public postings from users of this site'] = 'Publieke berichten van gebruikers van deze site';
$a->strings['Public postings from the federated network'] = 'Publieke berichten van het gefedereerde netwerk';
$a->strings['Public postings from local users and the federated network'] = 'Publieke berichten van lokale gebruikers en van het gefedereerde netwerk';
$a->strings['Multi user instance'] = 'Server voor meerdere gebruikers';
$a->strings['Closed'] = 'Gesloten';
$a->strings['Requires approval'] = 'Toestemming vereist';
$a->strings['Open'] = 'Open';
$a->strings['Don\'t check'] = 'Geen rekening mee houden';
$a->strings['check the stable version'] = 'Neem de stabiele versie in rekening';
$a->strings['check the development version'] = 'Neem de ontwikkel versie in rekening';
$a->strings['none'] = 'geen';
$a->strings['Site'] = 'Website';
$a->strings['Republish users to directory'] = 'Opnieuw de gebruikers naar de gids publiceren';
$a->strings['Registration'] = 'Registratie';
$a->strings['File upload'] = 'Uploaden bestand';
$a->strings['Policies'] = 'Beleid';
$a->strings['Advanced'] = 'Geavanceerd';
$a->strings['Auto Discovered Contact Directory'] = 'Automatisch Achterhaalde Contact Gids';
$a->strings['Performance'] = 'Performantie';
$a->strings['Worker'] = 'Worker';
$a->strings['Message Relay'] = 'Boodschap Relais';
$a->strings['Site name'] = 'Site naam';
$a->strings['Sender Email'] = 'Verzender Email';
$a->strings['The email address your server shall use to send notification emails from.'] = 'Het email adres als afzender van notificatie emails.';
$a->strings['Banner/Logo'] = 'Banner/Logo';
$a->strings['Shortcut icon'] = 'Snelkoppeling icoon';
$a->strings['Link to an icon that will be used for browsers.'] = 'Link naar een icoon dat zal gebruikt worden voor browsers.';
$a->strings['Touch icon'] = 'Pictogram voor smartphones';
$a->strings['Link to an icon that will be used for tablets and mobiles.'] = 'Link naar een icoon dat zal gebruikt worden voor tablets en mobiele telefoons.';
$a->strings['Additional Info'] = 'Bijkomende Info';
$a->strings['For public servers: you can add additional information here that will be listed at %s/servers.'] = 'Voor publieke servers: je kan bijkomende informatie hier toevoegen die zal opgelijst zijn op %s/servers.';
$a->strings['System language'] = 'Systeemtaal';
$a->strings['System theme'] = 'Systeem thema';
$a->strings['Mobile system theme'] = 'Mobiel systeem thema';
$a->strings['Theme for mobile devices'] = 'Thema voor mobiele apparaten';
$a->strings['Force SSL'] = 'Dwing SSL af';
$a->strings['Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.'] = 'Forceer alle Niet-SSL aanvragen naar SSL - Pas op: dit kan op sommige systeem resulteren in oneindige lussen.';
$a->strings['Single user instance'] = 'Server voor één gebruiker';
$a->strings['Make this instance multi-user or single-user for the named user'] = 'Stel deze server in voor meerdere gebruikers, of enkel voor de geselecteerde gebruiker.';
$a->strings['Maximum image size'] = 'Maximum afbeeldingsgrootte';
$a->strings['Maximum image length'] = 'Maximum afbeeldingslengte';
$a->strings['Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.'] = 'Maximum lengte in pixels van de langste kant van afbeeldingen. Standaard is -1, dus geen beperkingen.';
$a->strings['JPEG image quality'] = 'JPEG afbeeldingskwaliteit';
$a->strings['Uploaded JPEGS will be saved at this quality setting [0-100]. Default is 100, which is full quality.'] = 'JPEGS zullen met deze kwaliteitsinstelling bewaard worden [0-100]. Standaard is 100, dit is volledige kwaliteit.';
$a->strings['Register policy'] = 'Registratiebeleid';
$a->strings['Maximum Daily Registrations'] = 'Maximum aantal registraties per dag';
$a->strings['If registration is permitted above, this sets the maximum number of new user registrations to accept per day.  If register is set to closed, this setting has no effect.'] = 'Als registratie hierboven is toegelaten, zet dit het maximum aantal registraties van nieuwe gebruikers per dag. Als registratie niet is toegelaten heeft deze instelling geen effect.';
$a->strings['Register text'] = 'Registratietekst';
$a->strings['Will be displayed prominently on the registration page. You can use BBCode here.'] = 'Zal prominent op de registratie pagina getoond worden. Je kan hierin BBCode gebruiken.';
$a->strings['Forbidden Nicknames'] = 'Verboden bijnamen';
$a->strings['Comma separated list of nicknames that are forbidden from registration. Preset is a list of role names according RFC 2142.'] = 'Kommagescheiden lijst van bijnamen die verboden zijn voor registratie. De lijst uit RFC2142 is op voorhand ingesteld.';
$a->strings['Accounts abandoned after x days'] = 'Verlaten accounts na x dagen';
$a->strings['Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.'] = 'Dit zal geen systeembronnen verspillen aan het nakijken van externe sites voor verlaten accounts. Geef 0 is voor geen tijdslimiet.';
$a->strings['Allowed friend domains'] = 'Toegelaten vriend domeinen';
$a->strings['Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains'] = 'Komma-gescheiden lijst van domeinen die een vriendschapsband met deze website mogen aangaan. Jokers zijn toegelaten. Laat leeg om alle domeinen toe te laten.';
$a->strings['Allowed email domains'] = 'Toegelaten e-mail domeinen';
$a->strings['Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains'] = 'Door komma\'s gescheiden lijst met e-maildomeinen die op deze website mogen registeren. Wildcards zijn toegestaan.
Leeg laten om alle domeinen toe te staan.';
$a->strings['No OEmbed rich content'] = 'Geen OEmbed richt content';
$a->strings['Don\'t show the rich content (e.g. embedded PDF), except from the domains listed below.'] = 'Toon geen rich content (bvb. embedded PDF), behalve van domeinen hieronder opgelijst.';
$a->strings['Block public'] = 'Openbare toegang blokkeren';
$a->strings['Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.'] = 'Kruis dit aan om alle openbare persoonlijke pagina\'s alleen toegankelijk te maken voor ingelogde gebruikers.';
$a->strings['Force publish'] = 'Dwing publiceren af';
$a->strings['Check to force all profiles on this site to be listed in the site directory.'] = 'Kruis dit aan om af te dwingen dat alle profielen op deze website in de gids van deze website gepubliceerd worden.';
$a->strings['Enabling this may violate privacy laws like the GDPR'] = 'Dit activeren zou privacy wetten zoals GDPR (AVG) kunnen overtreden';
$a->strings['Global directory URL'] = 'Algemene gids URL';
$a->strings['URL to the global directory. If this is not set, the global directory is completely unavailable to the application.'] = 'URL naar de globale gids. Als dit niet geconfigureerd is, dan zal de globale gids volledig onbeschikbaar zijn voor de applicatie.';
$a->strings['Private posts by default for new users'] = 'Privéberichten als standaard voor nieuwe gebruikers';
$a->strings['Don\'t include post content in email notifications'] = 'De inhoud van het bericht niet insluiten bij e-mailnotificaties';
$a->strings['Don\'t include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.'] = 'De inhoud van berichten/commentaar/privéberichten/enzovoort  niet insluiten in e-mailnotificaties die door deze website verzonden worden, voor de bescherming van je privacy.';
$a->strings['Disallow public access to addons listed in the apps menu.'] = 'Publieke toegang ontzeggen tot addons die opgelijst zijn in het applicatie menu.';
$a->strings['Checking this box will restrict addons listed in the apps menu to members only.'] = 'Dit vakje aanvinken zal de lijst van addons in het applicatie menu beperken tot alleen leden.';
$a->strings['Don\'t embed private images in posts'] = 'Privé beelden in berichten niet inwerken';
$a->strings['Don\'t replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.'] = 'Vervang lokaal gehoste privé foto\'s in berichten niet door een ingewerkte kopie van het beeld. Dit betekent dat contacten die berichten krijgen met privé foto\'s zullen moeten authentificeren en elk beeld apart laden, wat een tijdje kan duren.';
$a->strings['Explicit Content'] = 'Expliciete inhoud';
$a->strings['Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.'] = 'Vink dit aan om aan te duiden dat deze node veel expliciet materiaal verspreid en niet bedoeld is voor minderjarigen. Deze info zal gepubliceert worden bij de node-info en kan vb. gebruikt worden voor een filter in de globale lijst. Dit word ook getoont naar de gebruiker op de registratie pagina.';
$a->strings['Allow Users to set remote_self'] = 'Sta Gebruikers toe om remote_self te configureren';
$a->strings['With checking this, every user is allowed to mark every contact as a remote_self in the repair contact dialog. Setting this flag on a contact causes mirroring every posting of that contact in the users stream.'] = 'Als je dit aanvinkt, dan mag elke gebruiker elke contact als remote_self aanduiden in de \'herstel contact\' dialoog. Deze vlag aanzetten voor een contact zorgt ervoor dat elke bericht van dit contact gespiegeld wordt in de gebruiker zijn of haar stroom. ';
$a->strings['Community pages for visitors'] = 'Groepspagina voor bezoekers';
$a->strings['Which community pages should be available for visitors. Local users always see both pages.'] = 'Welke groepspagina\'s moeten beschikbaar zijn voor bezoekers. Lokale gebruikers zien altijd beide pagina\'s.';
$a->strings['Posts per user on community page'] = 'Berichten per gebruiker op de groepspagina';
$a->strings['Diaspora support can\'t be enabled because Friendica was installed into a sub directory.'] = 'Diaspora ondersteuning is niet mogelijk omdat Friendica in een sub folder geïnstalleerd is.';
$a->strings['Enable Diaspora support'] = 'Diaspora ondersteuning activeren';
$a->strings['Verify SSL'] = 'Controleer SSL';
$a->strings['If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.'] = 'Als je wilt kun je striktere certificaat controle activeren. Dit betekent dat je (totaal) niet kunt connecteren met sites die zelf-ondertekende SSL certificaten gebruiken.';
$a->strings['Proxy user'] = 'Proxy-gebruiker';
$a->strings['Proxy URL'] = 'Proxy-URL';
$a->strings['Network timeout'] = 'Netwerk timeout';
$a->strings['Value is in seconds. Set to 0 for unlimited (not recommended).'] = 'Waarde is in seconden. Zet op 0 voor onbeperkt (niet aanbevolen).';
$a->strings['Maximum Load Average'] = 'Maximum gemiddelde belasting';
$a->strings['Minimal Memory'] = 'Minimaal Geheugen';
$a->strings['Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).'] = 'Minimum vrij geheugen in MB voor de worker. Toegang nodig tot /proc/meminfo - standaard waarde 0 (gedeactiveerd).';
$a->strings['Discover contacts from other servers'] = 'Ontdek contacten van andere servers';
$a->strings['Days between requery'] = 'Dagen tussen herbevraging';
$a->strings['Search the local directory'] = 'Doorzoek de lokale gids';
$a->strings['Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.'] = 'Doorzoek de lokale gids in plaats van de globale gids. Bij lokale doorzoeking wordt elke opzoeking in de globale gids op de achtergrond uitgevoerd. Dit verbetert de zoekresultaten wanneer de zoekopdracht herhaald wordt.';
$a->strings['Publish server information'] = 'Publiceer server informatie';
$a->strings['Check upstream version'] = 'Controleer upstream versie';
$a->strings['Enables checking for new Friendica versions at github. If there is a new version, you will be informed in the admin panel overview.'] = 'Activeer het controleren op nieuwe versies van Friendica bij github. Als er een nieuwe versie is, dan word je geïnformeerd in the administratie paneel.';
$a->strings['Suppress Tags'] = 'Onderdruk Tags';
$a->strings['Suppress showing a list of hashtags at the end of the posting.'] = 'Onderdruk het tonen van een lijst van hastags op het einde van het bericht.';
$a->strings['Clean database'] = 'Database opruimen';
$a->strings['Remove old remote items, orphaned database records and old content from some other helper tables.'] = 'Verwijder oude remote items, database weesrecords en oude content van andere helper tabellen.';
$a->strings['Lifespan of remote items'] = 'Levensduur van remote items';
$a->strings['When the database cleanup is enabled, this defines the days after which remote items will be deleted. Own items, and marked or filed items are always kept. 0 disables this behaviour.'] = 'Als de database opruiming is geactiveerd, dan definieert dit na hoeveel dagen remote items verwijderd zullen worden. Eigen items, en gemarkeerde of opgeslagen items worden altijd behouden. 0 deactiveert dit gedrag.';
$a->strings['Lifespan of unclaimed items'] = 'Levensduur van niet geclaimde items';
$a->strings['When the database cleanup is enabled, this defines the days after which unclaimed remote items (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general lifespan value of remote items if set to 0.'] = 'Als de database opruiming geactiveerd is, dan definieert dit na hoeveel dagen ongeclaimde remote items (meestal content van een relais) zal verwijderd worden. Standaard waarde is 90 dagen. Als de waarde 0 is, dan is de waarde gelijk aan de algemene levensduur van remote items.';
$a->strings['Lifespan of raw conversation data'] = 'Levenstijd van ruwe gespreksdata';
$a->strings['Maximum numbers of comments per post'] = 'Maximum aantal reacties per bericht';
$a->strings['How much comments should be shown for each post? Default value is 100.'] = 'Hoeveel reacties moeten getoond worden per bericht? Standaard waarde is 100.';
$a->strings['Temp path'] = 'Tijdelijk pad';
$a->strings['If you have a restricted system where the webserver can\'t access the system temp path, enter another path here.'] = 'Als je een systeem met restricties hebt waarbij de webserver geen toegang heeft tot het systeem pad, geef hier dan een ander pad in. ';
$a->strings['Only search in tags'] = 'Zoek alleen in tags';
$a->strings['On large systems the text search can slow down the system extremely.'] = 'Het opzoeken van tekst kan grote systemen extreem vertragen.';
$a->strings['Maximum number of parallel workers'] = 'Maximum aantal parallelle workers';
$a->strings['On shared hosters set this to %d. On larger systems, values of %d are great. Default value is %d.'] = 'Op gedeelde hosts zet dit op %d. Op grotere systemen, waarden als %d zijn goed. standaard waarde is %d';
$a->strings['Enable fastlane'] = 'Activeer fastlane';
$a->strings['When enabed, the fastlane mechanism starts an additional worker if processes with higher priority are blocked by processes of lower priority.'] = 'Als deze parameter geactiveerd is, dan start het fastlane mechanisme een bijkomende worker als processen met hogere prioriteit geblokkeerd worden door processen met een lagere prioriteit.';
$a->strings['Direct relay transfer'] = 'Directe relais transfer';
$a->strings['Enables the direct transfer to other servers without using the relay servers'] = 'Activeert directe relais transfer naar andere servers zonder gebruik van relais servers';
$a->strings['Relay scope'] = 'Scope van de relais';
$a->strings['Disabled'] = 'Uitgeschakeld';
$a->strings['all'] = 'alle';
$a->strings['tags'] = 'tags';
$a->strings['Server tags'] = 'Server tags';
$a->strings['Allow user tags'] = 'Sta gebruiker tags toe.';
$a->strings['Start Relocation'] = 'Start verhuis';
$a->strings['Your DB still runs with MyISAM tables. You should change the engine type to InnoDB. As Friendica will use InnoDB only features in the future, you should change this! See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = 'Je DB opereert nog met MyISAM tabellen. Best is van engine te veranderen naar InnoDB. Aangezien Friendica in de toekomst gebruik zal maken van InnoDB features, zou je dit best aanpassen! Zie <a href="%s">hier</a> voor een gids die je kan helpen om de tabel engines te converteren. Je kan ook het commando<tt>php bin/console.php dbstructure toinnodb</tt> van je Friendica installatie gebruiken voor een automatische conversie.<br />';
$a->strings['There is a new version of Friendica available for download. Your current version is %1$s, upstream version is %2$s'] = 'Er is een nieuwe versie van Friendica beschikbaar om te downloaden. Je huidige versie is %1$s, upstream versie is %2$s';
$a->strings['The database update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear.'] = 'Database update is mislukt. Gelieve  "php bin/console.php dbstructure update" vanaf de command line uit te voeren en de foutmeldingen die zouden kunnen verschijnen na te kijken.';
$a->strings['The worker was never executed. Please check your database structure!'] = 'De worker werd nooit uitgevoerd. Best je database structuur eens nakijken!';
$a->strings['The last worker execution was on %s UTC. This is older than one hour. Please check your crontab settings.'] = 'De laatste worker uitvoering was op %s UTC. Dit is langer dan 1 uur geleden. Best je crontab instellingen nakijken.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>.htconfig.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'Het configuratiebestand bevind zich nu in config/local.config.php. Kopieer het bestand config/local-sample.config.php en verplaats je configuratie uit <code>.htconfig.php</code>. Ga naar de<a href="%s">configuratie help pagina</a> voor hulp bij transitie.';
$a->strings['<a href="%s">%s</a> is not reachable on your system. This is a severe configuration issue that prevents server to server communication. See <a href="%s">the installation page</a> for help.'] = '<a href="%s">%s</a> is niet bereikbaar. Dit is een belangrijk communicatieprobleem waardoor server-naar-server communicatie niet mogelijk is. Lees de <a href="%s">the installatie pagina</a> voor hulp.';
$a->strings['Message queues'] = 'Bericht-wachtrijen';
$a->strings['Server Settings'] = 'Server instellingen.';
$a->strings['Version'] = 'Versie';
$a->strings['Active addons'] = 'Actieve addons';
$a->strings['Theme %s disabled.'] = 'Thema %s uitgeschakeld.';
$a->strings['Theme %s successfully enabled.'] = 'Thema %s succesvol ingeschakeld.';
$a->strings['Theme %s failed to install.'] = 'Thema %s installatie mislukt.';
$a->strings['Screenshot'] = 'Schermafdruk';
$a->strings['Themes'] = 'Thema\'s';
$a->strings['Unknown theme.'] = 'Onbekend thema.';
$a->strings['Reload active themes'] = 'Herlaad actieve thema\'s';
$a->strings['No themes found on the system. They should be placed in %1$s'] = 'Geen thema\'s gevonden op het systeem. Ze zouden zich moeten bevinden in %1$s';
$a->strings['[Experimental]'] = '[Experimenteel]';
$a->strings['[Unsupported]'] = '[Niet ondersteund]';
$a->strings['Display Terms of Service'] = 'Toon Gebruiksvoorwaarden';
$a->strings['Enable the Terms of Service page. If this is enabled a link to the terms will be added to the registration form and the general information page.'] = 'Activeer de Gebruiksvoorwaarden pagina. Als deze geactiveerd is, dan zal er een link naar de voorwaarden toegevoegd worden aan het registratie formulier en de algemene informatie pagina.';
$a->strings['Display Privacy Statement'] = 'Toon Privacy Verklaring';
$a->strings['Privacy Statement Preview'] = 'Privacy Verklaring Voorbeeldweergave';
$a->strings['The Terms of Service'] = 'De Gebruiksvoorwaarden';
$a->strings['Enter the Terms of Service for your node here. You can use BBCode. Headers of sections should be [h2] and below.'] = 'Geef hier de Gebruiksvoorwaarden van je node op. Je kan BBCode gebruiken. Sectie headers moeten [h2] zijn of lager.';
$a->strings['Contact not found'] = 'Contact niet gevonden';
$a->strings['No installed applications.'] = 'Geen toepassingen geïnstalleerd';
$a->strings['Applications'] = 'Toepassingen';
$a->strings['Item was not found.'] = 'Item niet gevonden';
$a->strings['Please login to continue.'] = 'Log in om verder te gaan.';
$a->strings['Overview'] = 'Overzicht';
$a->strings['Configuration'] = 'Configuratie';
$a->strings['Additional features'] = 'Extra functies';
$a->strings['Database'] = 'Database';
$a->strings['DB updates'] = 'DB aanpassingen';
$a->strings['Inspect Deferred Workers'] = 'Inspecteer uitgestelde workers';
$a->strings['Inspect worker Queue'] = 'Taakwachtrij inspecteren';
$a->strings['Diagnostics'] = 'Diagnostiek';
$a->strings['PHP Info'] = 'PHP Info';
$a->strings['probe address'] = 'probe adres';
$a->strings['check webfinger'] = 'check webfinger';
$a->strings['Addon Features'] = 'Addon Features';
$a->strings['User registrations waiting for confirmation'] = 'Gebruikersregistraties wachten op bevestiging';
$a->strings['Daily posting limit of %d post reached. The post was rejected.'] = [
	0 => 'De dagelijkse limiet van %d bericht is bereikt. Dit bericht werd niet aanvaard.',
	1 => 'De dagelijkse limiet van %d berichten is bereikt.  Dit bericht werd niet aanvaard.',
];
$a->strings['Weekly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'De wekelijkse limiet van %d bericht is bereikt.  Dit bericht werd niet aanvaard.',
	1 => 'De wekelijkse limiet van %d berichten is bereikt.  Dit bericht werd niet aanvaard.',
];
$a->strings['Users'] = 'Gebruiker';
$a->strings['Tools'] = 'Hulpmiddelen';
$a->strings['Contact Blocklist'] = 'Contact Blokkeerlijst';
$a->strings['Server Blocklist'] = 'Server Blokkeerlijst';
$a->strings['Delete Item'] = 'Verwijder Item';
$a->strings['Profile Details'] = 'Profieldetails';
$a->strings['Only You Can See This'] = 'Alleen jij kunt dit zien';
$a->strings['Tips for New Members'] = 'Tips voor nieuwe leden';
$a->strings['People Search - %s'] = 'Mensen Zoeken - %s';
$a->strings['No matches'] = 'Geen resultaten';
$a->strings['Account'] = 'Account';
$a->strings['Two-factor authentication'] = '2-factor authenticatie';
$a->strings['Display'] = 'Weergave';
$a->strings['Social Networks'] = 'Sociale netwerken';
$a->strings['Manage Accounts'] = 'Beheer Gebruikers';
$a->strings['Connected apps'] = 'Verbonden applicaties';
$a->strings['Import Contacts'] = 'Importeer contacten';
$a->strings['Export personal data'] = 'Persoonlijke gegevens exporteren';
$a->strings['Remove account'] = 'Account verwijderen';
$a->strings['This page is missing a url parameter.'] = 'Deze pagina mist een url-parameter.';
$a->strings['The post was created'] = 'Het bericht is aangemaakt';
$a->strings['Failed to remove event'] = 'Kon remote event niet verwijderen';
$a->strings['Event can not end before it has started.'] = 'Gebeurtenis kan niet eindigen voor het begin.';
$a->strings['Event title and start time are required.'] = 'Titel en begintijd van de gebeurtenis zijn vereist.';
$a->strings['Starting date and Title are required.'] = 'Start datum en Titel zijn verplicht.';
$a->strings['Event Starts:'] = 'Gebeurtenis begint:';
$a->strings['Required'] = 'Vereist';
$a->strings['Finish date/time is not known or not relevant'] = 'Einddatum/tijd is niet gekend of niet relevant';
$a->strings['Event Finishes:'] = 'Gebeurtenis eindigt:';
$a->strings['Share this event'] = 'Deel deze gebeurtenis';
$a->strings['Basic'] = 'Basis';
$a->strings['This calendar format is not supported'] = 'Dit kalender formaat is niet ondersteund';
$a->strings['No exportable data found'] = 'Geen exporteerbare data gevonden';
$a->strings['calendar'] = 'kalender';
$a->strings['Events'] = 'Gebeurtenissen';
$a->strings['View'] = 'Beeld';
$a->strings['Create New Event'] = 'Maak een nieuwe gebeurtenis';
$a->strings['list'] = 'lijst';
$a->strings['Contact not found.'] = 'Contact niet gevonden';
$a->strings['Invalid contact.'] = 'Ongeldig contact.';
$a->strings['Contact is deleted.'] = 'Contact is verwijderd.';
$a->strings['Bad request.'] = 'Verkeerde aanvraag.';
$a->strings['Filter'] = 'filter';
$a->strings['Members'] = 'Leden';
$a->strings['Click on a contact to add or remove.'] = 'Klik op een contact om het toe te voegen of te verwijderen.';
$a->strings['%d contact edited.'] = [
	0 => '%d contact bewerkt.',
	1 => '%d contacten bewerkt.',
];
$a->strings['Show all contacts'] = 'Toon alle contacten';
$a->strings['Pending'] = 'In behandeling';
$a->strings['Only show pending contacts'] = 'Toon alleen contacten in behandeling';
$a->strings['Blocked'] = 'Geblokkeerd';
$a->strings['Only show blocked contacts'] = 'Toon alleen geblokkeerde contacten';
$a->strings['Ignored'] = 'Genegeerd';
$a->strings['Only show ignored contacts'] = 'Toon alleen genegeerde contacten';
$a->strings['Archived'] = 'Gearchiveerd';
$a->strings['Only show archived contacts'] = 'Toon alleen gearchiveerde contacten';
$a->strings['Hidden'] = 'Verborgen';
$a->strings['Only show hidden contacts'] = 'Toon alleen verborgen contacten';
$a->strings['Search your contacts'] = 'Doorzoek je contacten';
$a->strings['Results for: %s'] = 'Resultaten voor: %s';
$a->strings['Update'] = 'Wijzigen';
$a->strings['Unblock'] = 'Blokkering opheffen';
$a->strings['Unignore'] = 'Negeer niet meer';
$a->strings['Batch Actions'] = 'Bulk Acties';
$a->strings['Conversations started by this contact'] = 'Gesprekken gestart door dit contact';
$a->strings['Posts and Comments'] = 'Berichten en reacties';
$a->strings['Advanced Contact Settings'] = 'Geavanceerde instellingen voor contacten';
$a->strings['Mutual Friendship'] = 'Wederzijdse vriendschap';
$a->strings['is a fan of yours'] = 'Is een fan van jou';
$a->strings['you are a fan of'] = 'Jij bent een fan van';
$a->strings['Pending outgoing contact request'] = 'In afwachting van uitgaande contactaanvraag';
$a->strings['Pending incoming contact request'] = 'In afwachting van inkomende contactaanvraag';
$a->strings['Visit %s\'s profile [%s]'] = 'Bekijk het profiel van %s [%s]';
$a->strings['Contact update failed.'] = 'Aanpassen van contact mislukt.';
$a->strings['Return to contact editor'] = 'Ga terug naar contactbewerker';
$a->strings['Name'] = 'Naam';
$a->strings['Account Nickname'] = 'Bijnaam account';
$a->strings['Account URL'] = 'URL account';
$a->strings['Poll/Feed URL'] = 'URL poll/feed';
$a->strings['New photo from this URL'] = 'Nieuwe foto van deze URL';
$a->strings['Follower (%s)'] = [
	0 => 'Volger (%s)',
	1 => 'Volgers (%s)',
];
$a->strings['Following (%s)'] = [
	0 => 'Volgend (%s)',
	1 => 'Volgend (%s)',
];
$a->strings['Mutual friend (%s)'] = [
	0 => 'Gemeenschappelijke vriend (%s)',
	1 => 'Gemeenschappelijke vrienden (%s)',
];
$a->strings['Contact (%s)'] = [
	0 => 'Contact (%s)',
	1 => 'Contacten (%s)',
];
$a->strings['Access denied.'] = 'Toegang geweigerd';
$a->strings['Submit Request'] = 'Aanvraag indienen';
$a->strings['You already added this contact.'] = 'Je hebt deze kontakt al toegevoegd';
$a->strings['The network type couldn\'t be detected. Contact can\'t be added.'] = 'Het type netwerk kon niet gedetecteerd worden. Contact kan niet toegevoegd worden.';
$a->strings['Diaspora support isn\'t enabled. Contact can\'t be added.'] = 'Diaspora ondersteuning is niet geactiveerd. Contact kan niet toegevoegd worden.';
$a->strings['Please answer the following:'] = 'Beantwoord het volgende:';
$a->strings['Your Identity Address:'] = 'Adres van je identiteit:';
$a->strings['Profile URL'] = 'Profiel url';
$a->strings['Tags:'] = 'Labels:';
$a->strings['%s knows you'] = '%s kent je';
$a->strings['Add a personal note:'] = 'Voeg een persoonlijke opmerking toe:';
$a->strings['The contact could not be added.'] = 'Het contact kon niet toegevoegd worden.';
$a->strings['Invalid request.'] = 'Ongeldige aanvraag.';
$a->strings['No keywords to match. Please add keywords to your profile.'] = 'Geen overeenkomende zoekwoorden. Voeg zoekwoorden toe aan uw profiel.';
$a->strings['Profile Match'] = 'Profielmatch';
$a->strings['Failed to update contact record.'] = 'Ik kon de contactgegevens niet aanpassen.';
$a->strings['Contact has been unblocked'] = 'Contact is gedeblokkeerd';
$a->strings['Contact has been blocked'] = 'Contact is geblokkeerd';
$a->strings['Contact has been unignored'] = 'Contact wordt niet meer genegeerd';
$a->strings['Contact has been ignored'] = 'Contact wordt genegeerd';
$a->strings['You are mutual friends with %s'] = 'Je bent wederzijds bevriend met %s';
$a->strings['You are sharing with %s'] = 'Je deelt met %s';
$a->strings['%s is sharing with you'] = '%s deelt met jou';
$a->strings['Private communications are not available for this contact.'] = 'Privécommunicatie met dit contact is niet beschikbaar.';
$a->strings['Never'] = 'Nooit';
$a->strings['(Update was not successful)'] = '(Wijziging is niet geslaagd)';
$a->strings['(Update was successful)'] = '(Wijziging is geslaagd)';
$a->strings['Suggest friends'] = 'Stel vrienden voor';
$a->strings['Network type: %s'] = 'Netwerk type: %s';
$a->strings['Communications lost with this contact!'] = 'Communicatie met dit contact is verbroken!';
$a->strings['Fetch further information for feeds'] = 'Haal meer informatie op van de feeds';
$a->strings['Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.'] = 'Haal informatie op zoals preview beelden, titel en teaser van het feed item. Je kan dit activeren als de feed niet veel tekst bevat. Sleutelwoorden worden opgepikt uit de meta header in het feed item en worden gepost als hash tags.';
$a->strings['Fetch information'] = 'Haal informatie op';
$a->strings['Fetch keywords'] = 'Haal sleutelwoorden op';
$a->strings['Fetch information and keywords'] = 'Haal informatie en sleutelwoorden op';
$a->strings['No mirroring'] = 'Geen mirroring';
$a->strings['Mirror as my own posting'] = 'Spiegel als mijn eigen bericht';
$a->strings['Contact Information / Notes'] = 'Contactinformatie / aantekeningen';
$a->strings['Contact Settings'] = 'Contact instellingen';
$a->strings['Contact'] = 'Contact';
$a->strings['Their personal note'] = 'Hun persoonlijke nota';
$a->strings['Edit contact notes'] = 'Wijzig aantekeningen over dit contact';
$a->strings['Block/Unblock contact'] = 'Blokkeer/deblokkeer contact';
$a->strings['Ignore contact'] = 'Negeer contact';
$a->strings['View conversations'] = 'Toon gesprekken';
$a->strings['Last update:'] = 'Laatste wijziging:';
$a->strings['Update public posts'] = 'Openbare posts aanpassen';
$a->strings['Update now'] = 'Wijzig nu';
$a->strings['Awaiting connection acknowledge'] = 'Wait op bevestiging van de connectie';
$a->strings['Currently blocked'] = 'Op dit moment geblokkeerd';
$a->strings['Currently ignored'] = 'Op dit moment genegeerd';
$a->strings['Currently archived'] = 'Op dit moment gearchiveerd';
$a->strings['Hide this contact from others'] = 'Verberg dit contact voor anderen';
$a->strings['Replies/likes to your public posts <strong>may</strong> still be visible'] = 'Antwoorden of \'vind ik leuk\'s op je openbare posts <strong>kunnen</strong> nog zichtbaar zijn';
$a->strings['Notification for new posts'] = 'Meldingen voor nieuwe berichten';
$a->strings['Send a notification of every new post of this contact'] = 'Stuur een notificatie voor elk bericht van dit contact';
$a->strings['Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected'] = 'Door komma\'s gescheiden lijst van sleutelwoorden die niet in hashtags mogen omgezet worden, wanneer "Haal informatie en sleutelwoorden op" is geselecteerd';
$a->strings['Actions'] = 'Acties';
$a->strings['Status'] = 'Tijdlijn';
$a->strings['Mirror postings from this contact'] = 'Berichten van dit contact spiegelen';
$a->strings['Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'] = 'Markeer dit contact als remote_self, hierdoor zal friendica nieuwe berichten van dit contact opnieuw posten.';
$a->strings['Refetch contact data'] = 'Contact data opnieuw ophalen';
$a->strings['Toggle Blocked status'] = 'Schakel geblokkeerde status';
$a->strings['Toggle Ignored status'] = 'Schakel negeerstatus';
$a->strings['Bad Request.'] = 'Verkeerde aanvraag.';
$a->strings['No suggestions available. If this is a new site, please try again in 24 hours.'] = 'Geen voorstellen beschikbaar. Als dit een nieuwe website is, kun je het over 24 uur nog eens proberen.';
$a->strings['You aren\'t following this contact.'] = 'Je volgt dit contact niet.';
$a->strings['Unfollowing is currently not supported by your network.'] = 'Ontvolgen is momenteel niet gesupporteerd door je netwerk.';
$a->strings['Disconnect/Unfollow'] = 'Disconnecteer/stop met volgen';
$a->strings['No results.'] = 'Geen resultaten.';
$a->strings['This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.'] = 'Deze groepsstroom toont alle publieke berichten die deze node ontvangen heeft. Ze kunnen mogelijks niet de mening van de gebruikers van deze node weerspiegelen.';
$a->strings['Community option not available.'] = 'Groepsoptie niet beschikbaar';
$a->strings['Not available.'] = 'Niet beschikbaar';
$a->strings['Credits'] = 'Credits';
$a->strings['Friendica is a community project, that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!'] = 'Friendica is een gemeenschapsproject dat niet mogelijk zou zijn zonder de hulp van vele mensen.  Hier is een lijst van alle mensen die aan de code of vertalingen van Friendica hebben meegewerkt.  Allen van harte bedankt!';
$a->strings['Error'] = [
	0 => 'Fout',
	1 => 'Fouten',
];
$a->strings['Source input'] = 'Bron input';
$a->strings['BBCode::toPlaintext'] = 'BBCode::toPlaintext';
$a->strings['BBCode::convert (raw HTML)'] = 'BBCode::convert (raw HTML)';
$a->strings['BBCode::convert'] = 'BBCode::convert';
$a->strings['BBCode::convert => HTML::toBBCode'] = 'BBCode::convert => HTML::toBBCode';
$a->strings['BBCode::toMarkdown'] = 'BBCode::toMarkdown';
$a->strings['BBCode::toMarkdown => Markdown::convert'] = 'BBCode::toMarkdown => Markdown::convert';
$a->strings['BBCode::toMarkdown => Markdown::toBBCode'] = 'BBCode::toMarkdown => Markdown::toBBCode';
$a->strings['BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode'] = 'BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode';
$a->strings['Source input (Diaspora format)'] = 'Bron ingave (Diaspora formaat):';
$a->strings['Markdown::convert (raw HTML)'] = 'Markdown::convert (Ruwe HTML)';
$a->strings['Markdown::convert'] = 'Markdown::convert';
$a->strings['Markdown::toBBCode'] = 'Markdown::toBBCode';
$a->strings['Raw HTML input'] = 'Onverwerkte HTML input';
$a->strings['HTML Input'] = 'HTML Input';
$a->strings['HTML::toBBCode'] = 'HTML::toBBCode';
$a->strings['HTML::toBBCode => BBCode::convert'] = 'HTML::toBBCode => BBCode::convert';
$a->strings['HTML::toBBCode => BBCode::convert (raw HTML)'] = 'HTML::toBBCode => BBCode::convert (Ruwe HTML)';
$a->strings['HTML::toMarkdown'] = 'HTML::toMarkdown';
$a->strings['HTML::toPlaintext'] = 'HTML::toPlaintext';
$a->strings['Source text'] = 'Brontekst';
$a->strings['BBCode'] = 'BBCode';
$a->strings['Markdown'] = 'Markdown';
$a->strings['HTML'] = 'HTML';
$a->strings['You must be logged in to use this module'] = 'Je moet ingelogd zijn om deze module te gebruiken';
$a->strings['Source URL'] = 'Bron URL';
$a->strings['Time Conversion'] = 'Tijdsconversie';
$a->strings['Friendica provides this service for sharing events with other networks and friends in unknown timezones.'] = 'Friendica biedt deze dienst aan om gebeurtenissen te delen met andere netwerken en vrienden in onbekende tijdzones.';
$a->strings['UTC time: %s'] = 'UTC tijd: %s';
$a->strings['Current timezone: %s'] = 'Huidige Tijdzone: %s';
$a->strings['Converted localtime: %s'] = 'Omgerekende lokale tijd: %s';
$a->strings['Please select your timezone:'] = 'Selecteer je tijdzone:';
$a->strings['Only logged in users are permitted to perform a probing.'] = 'Alleen ingelogde gebruikers hebben toelating om aan probing te doen.';
$a->strings['Lookup address'] = 'Opzoekadres';
$a->strings['No entries (some entries may be hidden).'] = 'Geen gegevens (sommige gegevens kunnen verborgen zijn).';
$a->strings['Find on this site'] = 'Op deze website zoeken';
$a->strings['Results for:'] = 'Resultaten voor:';
$a->strings['Site Directory'] = 'Websitegids';
$a->strings['- select -'] = '- Kies -';
$a->strings['Suggested contact not found.'] = 'Voorgesteld contact werd niet gevonden';
$a->strings['Friend suggestion sent.'] = 'Vriendschapsvoorstel verzonden.';
$a->strings['Suggest Friends'] = 'Stel vrienden voor';
$a->strings['Suggest a friend for %s'] = 'Stel een vriend voor aan %s';
$a->strings['Installed addons/apps:'] = 'Geïnstalleerde addons/applicaties:';
$a->strings['No installed addons/apps'] = 'Geen geïnstalleerde addons/applicaties';
$a->strings['Read about the <a href="%1$s/tos">Terms of Service</a> of this node.'] = 'Lees de <a href="%1$s/tos">Gebruiksvoorwaarden</a> van deze node na.';
$a->strings['On this server the following remote servers are blocked.'] = 'De volgende remote servers zijn geblokkeerd.';
$a->strings['Reason for the block'] = 'Reden van de blokkering';
$a->strings['This is Friendica, version %s that is running at the web location %s. The database version is %s, the post update version is %s.'] = 'Dit is Friendica, versie %s en draait op op locatie %s. De databaseversie is %s, en de bericht update versie is %s.';
$a->strings['Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.'] = 'Ga naar <a href="https://friendi.ca">Friendi.ca</a> om meer te vernemen over het Friendica project.';
$a->strings['Bug reports and issues: please visit'] = 'Bug rapporten en problemen: bezoek';
$a->strings['the bugtracker at github'] = 'de github bugtracker';
$a->strings['Suggestions, praise, etc. - please email "info" at "friendi - dot - ca'] = 'Suggesties, appreciatie, enz. - aub stuur een email naar "info" at "friendi - dot - ca';
$a->strings['No profile'] = 'Geen profiel';
$a->strings['Method Not Allowed.'] = 'Methode niet toegestaan.';
$a->strings['Help:'] = 'Help:';
$a->strings['Welcome to %s'] = 'Welkom op %s';
$a->strings['Friendica Communications Server - Setup'] = 'Friendica Communicatie Server - Setup';
$a->strings['System check'] = 'Systeemcontrole';
$a->strings['Next'] = 'Volgende';
$a->strings['Check again'] = 'Controleer opnieuw';
$a->strings['Base settings'] = 'Basisinstellingen';
$a->strings['Base path to installation'] = 'Basispad voor installatie';
$a->strings['If the system cannot detect the correct path to your installation, enter the correct path here. This setting should only be set if you are using a restricted system and symbolic links to your webroot.'] = 'Als het systeem het correcte pad naar je installatie niet kan detecteren, geef hier dan het correcte pad in. Deze instelling zou alleen geconfigureerd moeten worden als je een systeem met restricties hebt en symbolische links naar je webroot.';
$a->strings['Database connection'] = 'Verbinding met database';
$a->strings['In order to install Friendica we need to know how to connect to your database.'] = 'Om Friendica te kunnen installeren moet ik weten hoe ik jouw database kan bereiken.';
$a->strings['Please contact your hosting provider or site administrator if you have questions about these settings.'] = 'Neem contact op met jouw hostingprovider of websitebeheerder, wanneer je vragen hebt over deze instellingen. ';
$a->strings['The database you specify below should already exist. If it does not, please create it before continuing.'] = 'De database die je hier opgeeft zou al moeten bestaan. Maak anders de database aan voordat je verder gaat.';
$a->strings['Database Server Name'] = 'Servernaam database';
$a->strings['Database Login Name'] = 'Gebruikersnaam database';
$a->strings['Database Login Password'] = 'Wachtwoord database';
$a->strings['For security reasons the password must not be empty'] = 'Om veiligheidsreden mag het wachtwoord niet leeg zijn';
$a->strings['Database Name'] = 'Naam database';
$a->strings['Please select a default timezone for your website'] = 'Selecteer een standaard tijdzone voor je website';
$a->strings['Site settings'] = 'Website-instellingen';
$a->strings['Site administrator email address'] = 'E-mailadres van de websitebeheerder';
$a->strings['Your account email address must match this in order to use the web admin panel.'] = 'Het e-mailadres van je account moet hiermee overeenkomen om het administratiepaneel te kunnen gebruiken.';
$a->strings['System Language:'] = 'Systeem taal:';
$a->strings['Set the default language for your Friendica installation interface and to send emails.'] = 'Stel de standaard taal in voor je Friendica installatie interface en emails.';
$a->strings['Your Friendica site database has been installed.'] = 'De database van je Friendica-website is geïnstalleerd.';
$a->strings['Installation finished'] = 'Installaitie beëindigd';
$a->strings['<h1>What next</h1>'] = '<h1>Wat nu</h1>';
$a->strings['IMPORTANT: You will need to [manually] setup a scheduled task for the worker.'] = 'BELANGRIJK: Je zal [manueel] een geplande taak moeten opzetten voor de worker.';
$a->strings['Go to your new Friendica node <a href="%s/register">registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.'] = 'Go naar je nieuwe Friendica node <a href="%s/register">registratie pagina</a> en registeer als nieuwe gebruiker. Vergeet niet hetzelfde email adres te gebruiken als wat je opgegeven hebt als administrator email. Dit zal je toelaten om het site administratie paneel te openen.';
$a->strings['Total invitation limit exceeded.'] = 'Totale uitnodigingslimiet overschreden.';
$a->strings['%s : Not a valid email address.'] = '%s: Geen geldig e-mailadres.';
$a->strings['Please join us on Friendica'] = 'Kom bij ons op Friendica';
$a->strings['Invitation limit exceeded. Please contact your site administrator.'] = 'Uitnodigingslimiet overschreden. Neem contact op met de beheerder van je website.';
$a->strings['%s : Message delivery failed.'] = '%s : Aflevering van bericht mislukt.';
$a->strings['%d message sent.'] = [
	0 => '%d bericht verzonden.',
	1 => '%d berichten verzonden.',
];
$a->strings['You have no more invitations available'] = 'Je kunt geen uitnodigingen meer sturen';
$a->strings['Visit %s for a list of public sites that you can join. Friendica members on other sites can all connect with each other, as well as with members of many other social networks.'] = 'Bezoek %s voor een lijst van openbare sites waar je je kunt aansluiten. Friendica leden op andere sites kunnen allemaal met elkaar verbonden worden, en ook met leden van verschillende andere sociale netwerken.';
$a->strings['To accept this invitation, please visit and register at %s or any other public Friendica website.'] = 'Om deze uitnodiging te accepteren kan je je op %s registreren of op een andere vrij toegankelijke Friendica-website.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks. See %s for a list of alternate Friendica sites you can join.'] = 'Friendica servers zijn allemaal onderling verbonden om een reusachtig sociaal web te maken met verbeterde privacy, dat eigendom is van en gecontroleerd door zijn leden. Ze kunnen ook verbindingen maken met verschillende traditionele sociale netwerken. Bekijk %s voor een lijst van alternatieve Friendica servers waar je aan kunt sluiten.';
$a->strings['Our apologies. This system is not currently configured to connect with other public sites or invite members.'] = 'Onze verontschuldigingen. Dit systeem is momenteel niet ingesteld om verbinding te maken met andere openbare plaatsen of leden uit te nodigen.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks.'] = 'Friendica servers zijn allemaal onderling verbonden om een reusachtig sociaal web te maken met verbeterde privacy, dat eigendom is van en gecontroleerd door zijn leden. Ze kunnen ook verbindingen maken met verschillende traditionele sociale netwerken.';
$a->strings['To accept this invitation, please visit and register at %s.'] = 'Om deze uitnodiging te accepteren, ga naar en registreer op %s.';
$a->strings['Send invitations'] = 'Verstuur uitnodigingen';
$a->strings['Enter email addresses, one per line:'] = 'Vul e-mailadressen in, één per lijn:';
$a->strings['You are cordially invited to join me and other close friends on Friendica - and help us to create a better social web.'] = 'Ik nodig je vriendelijk uit om bij mij en andere vrienden te komen op Friendica - en ons te helpen om een beter sociaal web te bouwen.';
$a->strings['You will need to supply this invitation code: $invite_code'] = 'Je zult deze uitnodigingscode moeten invullen: $invite_code';
$a->strings['Once you have registered, please connect with me via my profile page at:'] = 'Eens je geregistreerd bent kun je contact leggen met mij via mijn profielpagina op:';
$a->strings['For more information about the Friendica project and why we feel it is important, please visit http://friendi.ca'] = 'Voor meer informatie over het Friendica project en waarom wij denken dat het belangrijk is kun je http://friendi.ca/ bezoeken';
$a->strings['Please enter a post body.'] = 'Voer een berichttekst in.';
$a->strings['This feature is only available with the frio theme.'] = 'Deze functie is alleen beschikbaar met het frio-thema.';
$a->strings['Compose new personal note'] = 'Stel een nieuwe persoonlijke notitie op';
$a->strings['Compose new post'] = 'Nieuw bericht opstellen';
$a->strings['Visibility'] = 'Zichtbaarheid';
$a->strings['Clear the location'] = 'Wis de locatie';
$a->strings['Location services are unavailable on your device'] = 'Locatiediensten zijn niet beschikbaar op uw apparaat';
$a->strings['Location services are disabled. Please check the website\'s permissions on your device'] = 'Locatiediensten zijn uitgeschakeld. Controleer de toestemmingen van de website op uw apparaat';
$a->strings['The feed for this item is unavailable.'] = 'De tijdlijn voor dit item is niet beschikbaar';
$a->strings['System down for maintenance'] = 'Systeem onbeschikbaar wegens onderhoud';
$a->strings['A Decentralized Social Network'] = 'Een gedecentraliseerd sociaal netwerk';
$a->strings['Files'] = 'Bestanden';
$a->strings['Upload'] = 'Uploaden';
$a->strings['Sorry, maybe your upload is bigger than the PHP configuration allows'] = 'Sorry, je op te laden bestand is groter dan deze PHP configuratie toelaat';
$a->strings['Or - did you try to upload an empty file?'] = 'Of - probeerde je een lege file op te laden?';
$a->strings['File exceeds size limit of %s'] = 'Bestand is groter dan de limiet ( %s )';
$a->strings['File upload failed.'] = 'Uploaden van bestand mislukt.';
$a->strings['Unable to process image.'] = 'Niet in staat om de afbeelding te verwerken';
$a->strings['Image upload failed.'] = 'Uploaden van afbeelding mislukt.';
$a->strings['Normal Account Page'] = 'Normale accountpagina';
$a->strings['Soapbox Page'] = 'Zeepkist-pagina';
$a->strings['Automatic Friend Page'] = 'Automatisch Vriendschapspagina';
$a->strings['Personal Page'] = 'Persoonlijke pagina';
$a->strings['Organisation Page'] = 'Organisatie Pagina';
$a->strings['News Page'] = 'Nieuws pagina';
$a->strings['%s contact unblocked'] = [
	0 => '%s contact is niet langer geblokkeerd',
	1 => '%s contacten zijn niet langer geblokkeerd',
];
$a->strings['Remote Contact Blocklist'] = 'Remote Contact Blokkeerlijst';
$a->strings['This page allows you to prevent any message from a remote contact to reach your node.'] = 'De pagina laat je toe om te vermijden dat boodschappen van een remote contact je node bereiken.';
$a->strings['Block Remote Contact'] = 'Blokkeer Remote Contact';
$a->strings['select all'] = 'Alles selecteren';
$a->strings['select none'] = 'selecteer geen';
$a->strings['No remote contact is blocked from this node.'] = 'Geen enkel remote contact is geblokkeerd van deze node.';
$a->strings['Blocked Remote Contacts'] = 'Geblokkeerde Remote Contacts';
$a->strings['Block New Remote Contact'] = 'Blokkeer Nieuwe Remote Contacten';
$a->strings['Photo'] = 'Foto';
$a->strings['Reason'] = 'Reden';
$a->strings['%s total blocked contact'] = [
	0 => '%s geblokkeerde contacten in totaal',
	1 => '%s geblokkeerde contacten in totaal',
];
$a->strings['URL of the remote contact to block.'] = 'URL van de remote contact die je wil blokkeren.';
$a->strings['Block Reason'] = 'Reden voor blokkeren';
$a->strings['Block reason'] = 'Reden voor blokkering';
$a->strings['Check to delete this entry from the blocklist'] = 'Vink aan om dit item van de blokkeerlijst te verwijderen';
$a->strings['Save changes to the blocklist'] = 'Sla veranderingen in de blokkeerlijst op';
$a->strings['Current Entries in the Blocklist'] = 'Huidige Items in de blokkeerlijst';
$a->strings['Item marked for deletion.'] = 'Item gemarkeerd om te verwijderen.';
$a->strings['Delete this Item'] = 'Verwijder dit Item';
$a->strings['On this page you can delete an item from your node. If the item is a top level posting, the entire thread will be deleted.'] = 'Op deze pagina kan je een item van je node verwijderen. Als het item een bericht is op het eerste niveau, dan zal de hele gesprek verwijderd worden.';
$a->strings['You need to know the GUID of the item. You can find it e.g. by looking at the display URL. The last part of http://example.com/display/123456 is the GUID, here 123456.'] = 'Je moet de GUID van het item kennen. Je kan het terugvinden bvb. door te kijken naar de getoonde URL. Het laatste deel van http://example.com/display/123456 is de GUID, hier 123456.';
$a->strings['GUID'] = 'GUID';
$a->strings['The GUID of the item you want to delete.'] = 'De GUID van het item dat je wil verwijderen.';
$a->strings['Type'] = 'Type';
$a->strings['Item not found'] = 'Item niet gevonden';
$a->strings['Item Guid'] = 'Item identificatie';
$a->strings['Registered users'] = 'Geregistreerde gebruikers';
$a->strings['Pending registrations'] = 'Registraties die in de wacht staan';
$a->strings['You can\'t remove yourself'] = 'Je kan jezelf niet verwijderen';
$a->strings['%s user deleted'] = [
	0 => '%s gebruiker verwijderd',
	1 => '%s gebruikers verwijderd',
];
$a->strings['Register date'] = 'Registratiedatum';
$a->strings['Last login'] = 'Laatste login';
$a->strings['User blocked'] = 'Gebruiker geblokeerd';
$a->strings['Site admin'] = 'Sitebeheerder';
$a->strings['Account expired'] = 'Account verlopen';
$a->strings['Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'] = 'Geselecteerde gebruikers zullen verwijderd worden!\n\nAlles wat deze gebruikers gepost hebben op deze website zal permanent verwijderd worden!\n\nBen je zeker?';
$a->strings['The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'] = 'De gebruiker {0} zal verwijderd worden!\n\nAlles wat deze gebruiker gepost heeft op deze website zal permanent verwijderd worden!\n\nBen je zeker?';
$a->strings['New User'] = 'Nieuwe gebruiker';
$a->strings['Add User'] = 'Gebruiker toevoegen';
$a->strings['Name of the new user.'] = 'Naam van nieuwe gebruiker';
$a->strings['Nickname'] = 'Bijnaam';
$a->strings['Nickname of the new user.'] = 'Bijnaam van nieuwe gebruiker';
$a->strings['Email address of the new user.'] = 'E-mailadres van nieuwe gebruiker';
$a->strings['User waiting for permanent deletion'] = 'Gebruiker wacht op permanente verwijdering';
$a->strings['Account approved.'] = 'Account goedgekeurd.';
$a->strings['Request date'] = 'Registratiedatum';
$a->strings['No registrations.'] = 'Geen registraties.';
$a->strings['Note from the user'] = 'Nota van de gebruiker';
$a->strings['Deny'] = 'Weiger';
$a->strings['Show Ignored Requests'] = 'Toon genegeerde verzoeken';
$a->strings['Hide Ignored Requests'] = 'Verberg genegeerde verzoeken';
$a->strings['Notification type:'] = 'Notificatiesoort:';
$a->strings['Suggested by:'] = 'Voorgesteld door:';
$a->strings['Claims to be known to you: '] = 'Denkt dat je hem of haar kent:';
$a->strings['Shall your connection be bidirectional or not?'] = 'Zal je connectie bidirectioneel zijn of niet?';
$a->strings['Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.'] = '%s als vriend accepteren laat %s toe om in te schrijven op je berichten, en je zal ook updates ontvangen van hen in je nieuws feed.';
$a->strings['Accepting %s as a subscriber allows them to subscribe to your posts, but you will not receive updates from them in your news feed.'] = '%s als volger accepteren laat hen toe om in te schrijven op je berichten, maar je zal geen updates ontvangen van hen in je nieuws feed.';
$a->strings['Friend'] = 'Vriend';
$a->strings['Subscriber'] = 'Volger';
$a->strings['No introductions.'] = 'Geen vriendschaps- of connectieverzoeken.';
$a->strings['No more %s notifications.'] = 'Geen %s notificaties meer.';
$a->strings['You must be logged in to show this page.'] = 'Je moet ingelogd zijn om deze pagina te tonen.';
$a->strings['Network Notifications'] = 'Netwerknotificaties';
$a->strings['System Notifications'] = 'Systeemnotificaties';
$a->strings['Personal Notifications'] = 'Persoonlijke notificaties';
$a->strings['Home Notifications'] = 'Tijdlijn-notificaties';
$a->strings['Show unread'] = 'Toon ongelezen';
$a->strings['{0} requested registration'] = '{0} vroeg om zich te registreren';
$a->strings['Authorize application connection'] = 'Verbinding met de applicatie goedkeuren';
$a->strings['Do you want to authorize this application to access your posts and contacts, and/or create new posts for you?'] = 'Wil je deze toepassing toestemming geven om jouw berichten en contacten in te kijken, en/of nieuwe berichten in jouw plaats aan te maken?';
$a->strings['No contact provided.'] = 'Geen contact opgegeven.';
$a->strings['Couldn\'t fetch information for contact.'] = 'Kon de informatie voor het contact niet ophalen.';
$a->strings['Couldn\'t fetch friends for contact.'] = 'Kon de vrienden van contact niet ophalen.';
$a->strings['Done'] = 'Klaar';
$a->strings['success'] = 'Succesvol';
$a->strings['failed'] = 'Mislukt';
$a->strings['ignored'] = 'Verboden';
$a->strings['Keep this window open until done.'] = 'Houd dit scherm open tot het klaar is';
$a->strings['The Photo with id %s is not available.'] = 'De foto met id %s is niet beschikbaar';
$a->strings['Invalid photo with id %s.'] = 'Ongeldige foto met ID %s';
$a->strings['Edit post'] = 'Bericht bewerken';
$a->strings['web link'] = 'webadres';
$a->strings['Insert video link'] = 'Voeg video toe';
$a->strings['video link'] = 'video adres';
$a->strings['Insert audio link'] = 'Voeg audio adres toe';
$a->strings['audio link'] = 'audio adres';
$a->strings['Remove Item Tag'] = 'Verwijder label van item';
$a->strings['Select a tag to remove: '] = 'Selecteer een label om te verwijderen: ';
$a->strings['Remove'] = 'Verwijderen';
$a->strings['Remote privacy information not available.'] = 'Privacyinformatie op afstand niet beschikbaar.';
$a->strings['Visible to:'] = 'Zichtbaar voor:';
$a->strings['No contacts.'] = 'Geen contacten.';
$a->strings['%s\'s posts'] = 'Berichten van %s';
$a->strings['%s\'s comments'] = 'reactie van %s';
$a->strings['%s\'s timeline'] = 'Tijdslijn van %s';
$a->strings['Image exceeds size limit of %s'] = 'Beeld is groter dan de limiet ( %s )';
$a->strings['Image upload didn\'t complete, please try again'] = 'Opladen van het beeld is niet compleet, probeer het opnieuw';
$a->strings['Image file is missing'] = 'Beeld bestand ontbreekt';
$a->strings['Server can\'t accept new file upload at this time, please contact your administrator'] = 'De server kan op dit moment geen nieuw bestand opladen, contacteer alsjeblieft je beheerder';
$a->strings['Image file is empty.'] = 'Afbeeldingsbestand is leeg.';
$a->strings['View Album'] = 'Album bekijken';
$a->strings['Profile not found.'] = 'Profiel niet gevonden';
$a->strings['Full Name:'] = 'Volledige Naam:';
$a->strings['Member since:'] = 'Lid sinds:';
$a->strings['j F, Y'] = 'F j Y';
$a->strings['j F'] = 'F j';
$a->strings['Birthday:'] = 'Verjaardag:';
$a->strings['Age: '] = 'Leeftijd:';
$a->strings['%d year old'] = [
	0 => '%d jaar oud',
	1 => '%d jaar oud',
];
$a->strings['Description:'] = 'Beschrijving:';
$a->strings['View profile as:'] = 'Bekijk profiel als:';
$a->strings['Profile unavailable.'] = 'Profiel onbeschikbaar';
$a->strings['Invalid locator'] = 'Ongeldige plaatsbepaler';
$a->strings['The provided profile link doesn\'t seem to be valid'] = 'De verstrekte profiellink lijkt niet geldig te zijn';
$a->strings['Remote subscription can\'t be done for your network. Please subscribe directly on your system.'] = 'Remote inschrijving kan niet op jouw netwerk. Gelieve direct op je systeem in te schrijven.';
$a->strings['Friend/Connection Request'] = 'Vriendschaps-/connectieverzoek';
$a->strings['Enter your Webfinger address (user@domain.tld) or profile URL here. If this isn\'t supported by your system, you have to subscribe to <strong>%s</strong> or <strong>%s</strong> directly on your system.'] = 'Geef hier je Webfinger adres (gebruiker@domain.tld) of profiel URL. Als dit niet wordt ondersteund door je systeem, dan dien je in te schrijven op <strong>%s</strong> of <strong>%s</strong> direct op je systeem.';
$a->strings['If you are not yet a member of the free social web, <a href="%s">follow this link to find a public Friendica node and join us today</a>.'] = 'Als je nog geen lid bent van het vrije sociale web, <a href="%s">volg dan deze link om een publieke Friendica node te vinden en sluit je vandaag bij ons aan</a>.';
$a->strings['Your Webfinger address or profile URL:'] = 'Uw Webfinger adres of profiel-URL:';
$a->strings['Only parent users can create additional accounts.'] = 'Alleen bovenliggende gebruikers kunnen extra gebruikers maken.';
$a->strings['This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.'] = 'Deze website heeft het toegelaten dagelijkse aantal registraties overschreden. Probeer morgen opnieuw.';
$a->strings['You may (optionally) fill in this form via OpenID by supplying your OpenID and clicking "Register".'] = 'U kunt (optioneel) dit formulier invullen via OpenID door uw OpenID in te vullen en op \'Registreren\' te klikken.';
$a->strings['If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.'] = 'Laat dit veld leeg als je niet vertrouwd bent met OpenID, en vul de rest van de items in.';
$a->strings['Your OpenID (optional): '] = 'Je OpenID (optioneel):';
$a->strings['Include your profile in member directory?'] = 'Je profiel in de ledengids opnemen?';
$a->strings['Note for the admin'] = 'Nota voor de beheerder';
$a->strings['Leave a message for the admin, why you want to join this node'] = 'Laat een boodschap na voor de beheerder, waarom je bij deze node wil komen';
$a->strings['Membership on this site is by invitation only.'] = 'Lidmaatschap van deze website is uitsluitend op uitnodiging.';
$a->strings['Your invitation code: '] = 'Je uitnodigingscode:';
$a->strings['Your Email Address: (Initial information will be send there, so this has to be an existing address.)'] = 'Je Email Adres: (Initiële informatie zal hier naartoe gezonden worden, dus dit moet een bestaand adres zijn.)';
$a->strings['Please repeat your e-mail address:'] = 'Herhaal uw e-mailadres:';
$a->strings['New Password:'] = 'Nieuw Wachtwoord:';
$a->strings['Leave empty for an auto generated password.'] = 'Laat leeg voor een automatisch gegenereerd wachtwoord.';
$a->strings['Confirm:'] = 'Bevestig:';
$a->strings['Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be "<strong>nickname@%s</strong>".'] = 'Kies een profiel bijnaam. Deze dient te beginnen met een letter. Uw profiel adres op deze site zal dan "<strong>bijnaam@%s</strong>" zijn.';
$a->strings['Choose a nickname: '] = 'Kies een bijnaam:';
$a->strings['Import'] = 'Importeren';
$a->strings['Import your profile to this friendica instance'] = 'Importeer je profiel op deze friendica server';
$a->strings['Note: This node explicitly contains adult content'] = 'Waarschuwing: Deze node heeft inhoud enkel bedoeld voor volwassenen.';
$a->strings['Parent Password:'] = 'Ouderlijk wachtwoord:';
$a->strings['Please enter the password of the parent account to legitimize your request.'] = 'Geef alstublieft het wachtwoord van het ouderlijke account om je verzoek te legitimeren.';
$a->strings['Password doesn\'t match.'] = 'Wachtwoorden komen niet overeen.';
$a->strings['Please enter your password.'] = 'Voer uw wachtwoord in.';
$a->strings['You have entered too much information.'] = 'U heeft te veel informatie ingevoerd.';
$a->strings['Please enter the identical mail address in the second field.'] = 'Voer in het tweede veld het identieke mailadres in.';
$a->strings['The additional account was created.'] = 'De toegevoegde gebruiker is aangemaakt.';
$a->strings['Registration successful. Please check your email for further instructions.'] = 'Registratie geslaagd. Kijk je e-mail na voor verdere instructies.';
$a->strings['Failed to send email message. Here your accout details:<br> login: %s<br> password: %s<br><br>You can change your password after login.'] = 'Kon email niet verzenden. Hier zijn je account details: <br> login: %s <br> wachtwoord: %s<br><br> Je kan je wachtwoord aanpassen nadat je ingelogd bent.';
$a->strings['Registration successful.'] = 'Registratie succes.';
$a->strings['Your registration can not be processed.'] = 'Je registratie kan niet verwerkt worden.';
$a->strings['You have to leave a request note for the admin.'] = 'U dient een verzoekmelding achter te laten voor de beheerder.';
$a->strings['Your registration is pending approval by the site owner.'] = 'Jouw registratie wacht op goedkeuring van de beheerder.';
$a->strings['You must be logged in to use this module.'] = 'Je moet ingelogd zijn om deze module te gebruiken.';
$a->strings['Only logged in users are permitted to perform a search.'] = 'Alleen ingelogde gebruikers mogen een zoekopdracht starten.';
$a->strings['Only one search per minute is permitted for not logged in users.'] = 'Niet ingelogde gebruikers mogen slechts 1 opzoeking doen per minuut';
$a->strings['Items tagged with: %s'] = 'Items getagd met: %s';
$a->strings['Search term already saved.'] = 'Zoekterm is al opgeslagen.';
$a->strings['Create a New Account'] = 'Nieuwe account aanmaken';
$a->strings['Your OpenID: '] = 'Uw OpenID';
$a->strings['Please enter your username and password to add the OpenID to your existing account.'] = 'Voer uw gebruikersnaam en wachtwoord in om de OpenID toe te voegen aan uw bestaande gebruiker.';
$a->strings['Or login using OpenID: '] = 'Of log in met OpenID:';
$a->strings['Password: '] = 'Wachtwoord:';
$a->strings['Remember me'] = 'Onthoud mij';
$a->strings['Forgot your password?'] = 'Wachtwoord vergeten?';
$a->strings['Website Terms of Service'] = 'Gebruikersvoorwaarden website';
$a->strings['terms of service'] = 'servicevoorwaarden';
$a->strings['Website Privacy Policy'] = 'Privacybeleid website';
$a->strings['privacy policy'] = 'privacybeleid';
$a->strings['Logged out.'] = 'Uitgelogd.';
$a->strings['OpenID protocol error. No ID returned'] = 'OpenID-protocolfout. Geen ID terug ontvangen';
$a->strings['Account not found. Please login to your existing account to add the OpenID to it.'] = 'Account niet gevonden. Meld je aan met je bestaande account om de OpenID toe te voegen.';
$a->strings['Account not found. Please register a new account or login to your existing account to add the OpenID to it.'] = 'Account niet gevonden. Maak een nieuwe account aan of meld je aan met je bestaande account om de OpenID toe te voegen.';
$a->strings['Passwords do not match.'] = 'Wachtwoorden zijn niet gelijk';
$a->strings['Password unchanged.'] = 'Wachtwoord ongewijzigd';
$a->strings['Current Password:'] = 'Huidig wachtwoord:';
$a->strings['Your current password to confirm the changes'] = 'Je huidig wachtwoord om de wijzigingen te bevestigen';
$a->strings['Remaining recovery codes: %d'] = 'Resterende herstelcodes: %d';
$a->strings['Invalid code, please retry.'] = 'Ongeldige code, probeer het opnieuw.';
$a->strings['Two-factor recovery'] = 'Twee-factorenherstel';
$a->strings['<p>You can enter one of your one-time recovery codes in case you lost access to your mobile device.</p>'] = '<p>U kunt een van uw eenmalige herstelcodes invoeren als u de toegang tot uw mobiele apparaat bent kwijtgeraakt.</p>';
$a->strings['Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>'] = 'Heb je je telefoon niet? <a href="%s">Geef een twee-factor herstelcodecode in</a>';
$a->strings['Please enter a recovery code'] = 'Voer een herstelcode in';
$a->strings['Submit recovery code and complete login'] = 'Voer de herstelcode in en voltooi de login';
$a->strings['<p>Open the two-factor authentication app on your device to get an authentication code and verify your identity.</p>'] = '<p>Open de tweefactorauthenticatie-app op uw apparaat om een ​​authenticatiecode te krijgen en uw identiteit te verifiëren.</p>';
$a->strings['Please enter a code from your authentication app'] = 'Voer een code in van uw authenticatie-app';
$a->strings['Verify code and complete login'] = 'Controleer de code en voltooi de login';
$a->strings['Please use a shorter name.'] = 'Gebruik een kortere naam.';
$a->strings['Name too short.'] = 'Naam is te kort.';
$a->strings['Wrong Password.'] = 'Verkeerd wachtwoord.';
$a->strings['Invalid email.'] = 'Ongeldig email adres.';
$a->strings['Cannot change to that email.'] = 'Kan niet naar dat email adres veranderen.';
$a->strings['Settings were not updated.'] = 'Wijziging instellingen is niet opgeslagen.';
$a->strings['Relocate message has been send to your contacts'] = 'Verhuis boodschap is verzonden naar je contacten';
$a->strings['Unable to find your profile. Please contact your admin.'] = 'Kan je profiel niet vinden. Contacteer alsjeblieft je beheerder.';
$a->strings['Personal Page Subtypes'] = 'Persoonlijke Pagina Subtypes';
$a->strings['Account for a personal profile.'] = 'Account voor een persoonlijk profiel';
$a->strings['Account for an organisation that automatically approves contact requests as "Followers".'] = 'Account voor een organisatie die automatisch contact aanvragen goedkeurt als "Volgers".';
$a->strings['Account for a news reflector that automatically approves contact requests as "Followers".'] = 'Account voor een nieuws reflector die automatisch contact aanvragen goedkeurt als "Volgers".';
$a->strings['Account for community discussions.'] = 'Account voor groepsdiscussies.';
$a->strings['Account for a regular personal profile that requires manual approval of "Friends" and "Followers".'] = 'Account voor een normaal persoonlijk profiel dat manuele goedkeuring vereist van "Vrienden" en "Volgers".';
$a->strings['Account for a public profile that automatically approves contact requests as "Followers".'] = 'Account voor een publiek profiel dat automatisch contact aanvragen goedkeurt als "Volgers".';
$a->strings['Automatically approves all contact requests.'] = 'Aanvaardt automatisch all contact aanvragen.';
$a->strings['Account for a popular profile that automatically approves contact requests as "Friends".'] = 'Account voor een populair profiel dat automatisch contact aanvragen goedkeurt als "Vrienden".';
$a->strings['Requires manual approval of contact requests.'] = 'Vereist manuele goedkeuring van contact aanvragen.';
$a->strings['OpenID:'] = 'OpenID:';
$a->strings['(Optional) Allow this OpenID to login to this account.'] = '(Optioneel) Laat dit OpenID toe om in te loggen op deze account.';
$a->strings['Publish your profile in your local site directory?'] = 'Uw profiel publiceren in uw lokale sitemap?';
$a->strings['Your profile will be published in this node\'s <a href="%s">local directory</a>. Your profile details may be publicly visible depending on the system settings.'] = 'Je profiel zal gepubliceerd worden de <a href="%s">lokale gids</a> van deze node. Je profiel details kunnen publiek zichtbaar zijn afhankelijk van de systeem instellingen.';
$a->strings['Your profile will also be published in the global friendica directories (e.g. <a href="%s">%s</a>).'] = 'Je profiel zal ook worden gepubliceerd in de globale Friendica directories (e.g. <a href="%s">%s</a>).';
$a->strings['Account Settings'] = 'Account Instellingen';
$a->strings['Your Identity Address is <strong>\'%s\'</strong> or \'%s\'.'] = 'Je Identiteit adres is <strong>\'%s\'</strong> of \'%s\'.';
$a->strings['Password Settings'] = 'Wachtwoord Instellingen';
$a->strings['Leave password fields blank unless changing'] = 'Laat de wachtwoord-velden leeg, tenzij je het wilt veranderen';
$a->strings['Password:'] = 'Wachtwoord:';
$a->strings['Your current password to confirm the changes of the email address'] = 'Je huidige wachtwoord om de verandering in het email adres te bevestigen';
$a->strings['Delete OpenID URL'] = 'Verwijder OpenID URL';
$a->strings['Basic Settings'] = 'Basis Instellingen';
$a->strings['Display name:'] = 'Weergave naam:';
$a->strings['Email Address:'] = 'E-mailadres:';
$a->strings['Your Timezone:'] = 'Je Tijdzone:';
$a->strings['Your Language:'] = 'Je taal:';
$a->strings['Set the language we use to show you friendica interface and to send you emails'] = 'Configureer de taal van die we gebruiken als friendica interface en om je emails te sturen';
$a->strings['Default Post Location:'] = 'Standaard locatie:';
$a->strings['Use Browser Location:'] = 'Gebruik Webbrowser Locatie:';
$a->strings['Security and Privacy Settings'] = 'Instellingen voor Beveiliging en Privacy';
$a->strings['Maximum Friend Requests/Day:'] = 'Maximum aantal vriendschapsverzoeken per dag:';
$a->strings['(to prevent spam abuse)'] = '(om spam misbruik te voorkomen)';
$a->strings['Allow your profile to be searchable globally?'] = 'Wilt u dat uw profiel globaal doorzoekbaar is?';
$a->strings['Activate this setting if you want others to easily find and follow you. Your profile will be searchable on remote systems. This setting also determines whether Friendica will inform search engines that your profile should be indexed or not.'] = 'Activeer deze instelling als u wilt dat anderen u gemakkelijk kunnen vinden en volgen. Uw profiel is doorzoekbaar op externe systemen. Deze instelling bepaalt ook of Friendica zoekmachines zal informeren dat uw profiel moet worden geïndexeerd of niet.';
$a->strings['Hide your contact/friend list from viewers of your profile?'] = 'Uw contact- / vriendenlijst verbergen voor hen die uw profiel bekijken?';
$a->strings['A list of your contacts is displayed on your profile page. Activate this option to disable the display of your contact list.'] = 'Een lijst met uw contacten wordt weergegeven op uw profielpagina. Activeer deze optie om de weergave van uw contactenlijst uit te schakelen.';
$a->strings['Make public posts unlisted'] = 'Maak openbare berichten verborgen';
$a->strings['Your public posts will not appear on the community pages or in search results, nor be sent to relay servers. However they can still appear on public feeds on remote servers.'] = 'Je openbare berichten verschijnen niet op de communitypagina\'s of in de zoekresultaten en worden ook niet naar relayservers gestuurd. Ze kunnen echter nog steeds verschijnen op openbare feeds op externe servers.';
$a->strings['Make all posted pictures accessible'] = 'Maak alle geplaatste foto\'s toegankelijk';
$a->strings['This option makes every posted picture accessible via the direct link. This is a workaround for the problem that most other networks can\'t handle permissions on pictures. Non public pictures still won\'t be visible for the public on your photo albums though.'] = 'Deze optie maakt elke geplaatste foto toegankelijk via de directe link. Dit is een tijdelijke oplossing voor het probleem dat de meeste andere netwerken de rechten op afbeeldingen niet kunnen verwerken. Niet-openbare afbeeldingen zijn echter nog steeds niet zichtbaar voor het publiek in uw fotoalbums.';
$a->strings['Allow friends to post to your profile page?'] = 'Vrienden toestaan om op jouw profielpagina te posten?';
$a->strings['Your contacts may write posts on your profile wall. These posts will be distributed to your contacts'] = 'Je contacten kunnen berichten schrijven op je tijdslijn. Deze berichten zullen verspreid worden naar je contacten';
$a->strings['Allow friends to tag your posts?'] = 'Sta vrienden toe om jouw berichten te labelen?';
$a->strings['Your contacts can add additional tags to your posts.'] = 'Je contacten kunnen tags toevoegen aan je berichten.';
$a->strings['Default Post Permissions'] = 'Standaard rechten voor nieuwe berichten';
$a->strings['Expiration settings'] = 'Vervalinstellingen';
$a->strings['Automatically expire posts after this many days:'] = 'Laat berichten automatisch vervallen na zo veel dagen:';
$a->strings['If empty, posts will not expire. Expired posts will be deleted'] = 'Berichten zullen niet vervallen indien leeg. Vervallen berichten zullen worden verwijderd.';
$a->strings['Expire posts'] = 'Verlopen berichten';
$a->strings['When activated, posts and comments will be expired.'] = 'Indien geactiveerd, zullen berichten en opmerkingen verlopen.';
$a->strings['Expire personal notes'] = 'Verloop persoonlijke notities';
$a->strings['When activated, the personal notes on your profile page will be expired.'] = 'Indien geactiveerd, verlopen de persoonlijke notities op uw profielpagina.';
$a->strings['Expire starred posts'] = 'Berichten met ster laten vervallen';
$a->strings['Starring posts keeps them from being expired. That behaviour is overwritten by this setting.'] = 'Berichten met een ster verhinderen dat ze verlopen. Dat gedrag wordt door deze instelling overschreven.';
$a->strings['Only expire posts by others'] = 'Laat alleen berichten van anderen verlopen';
$a->strings['When activated, your own posts never expire. Then the settings above are only valid for posts you received.'] = 'Indien geactiveerd, vervallen je eigen berichten nooit. Dan zijn bovenstaande instellingen alleen geldig voor berichten die je hebt ontvangen.';
$a->strings['Notification Settings'] = 'Notificatie Instellingen';
$a->strings['Send a notification email when:'] = 'Stuur een notificatie e-mail wanneer:';
$a->strings['You receive an introduction'] = 'Je ontvangt een vriendschaps- of connectieverzoek';
$a->strings['Your introductions are confirmed'] = 'Jouw vriendschaps- of connectieverzoeken zijn bevestigd';
$a->strings['Someone writes on your profile wall'] = 'Iemand iets op je tijdlijn schrijft';
$a->strings['Someone writes a followup comment'] = 'Iemand een reactie schrijft';
$a->strings['You receive a private message'] = 'Je een privé-bericht ontvangt';
$a->strings['You receive a friend suggestion'] = 'Je een suggestie voor een vriendschap ontvangt';
$a->strings['You are tagged in a post'] = 'Je expliciet in een bericht bent genoemd';
$a->strings['Activate desktop notifications'] = 'Activeer desktop notificaties';
$a->strings['Show desktop popup on new notifications'] = 'Toon desktop pop-up bij nieuwe notificaties';
$a->strings['Text-only notification emails'] = 'Alleen-tekst notificatie emails';
$a->strings['Send text only notification emails, without the html part'] = 'Stuur alleen-tekst notificatie emails, zonder het html gedeelte';
$a->strings['Show detailled notifications'] = 'Toon gedetailleerde notificaties';
$a->strings['Per default, notifications are condensed to a single notification per item. When enabled every notification is displayed.'] = 'Standaard worden notificaties samengevoegd in een enkele notificatie per item. Als je deze parameter activeert wordt elke notificatie getoond.';
$a->strings['Advanced Account/Page Type Settings'] = 'Geavanceerde Account/Pagina Type Instellingen';
$a->strings['Change the behaviour of this account for special situations'] = 'Pas het gedrag van dit account aan voor speciale situaties';
$a->strings['Relocate'] = 'Verhuis';
$a->strings['If you have moved this profile from another server, and some of your contacts don\'t receive your updates, try pushing this button.'] = 'Als je je profiel van een andere server hebt verhuisd, en er zijn contacten die geen updates van je ontvangen, probeer dan eens deze knop.';
$a->strings['Resend relocate message to contacts'] = 'Stuur verhuis boodschap naar contacten';
$a->strings['Addon Settings'] = 'Addon instellingen';
$a->strings['No Addon settings configured'] = 'Geen Addon instellingen geconfigureerd';
$a->strings['Description'] = 'Omschrijving';
$a->strings['Add'] = 'Toevoegen';
$a->strings['Failed to connect with email account using the settings provided.'] = 'Ik kon geen verbinding maken met het e-mail account met de gegeven instellingen.';
$a->strings['Diaspora (Socialhome, Hubzilla)'] = 'Diaspora (Socialhome, Hubzilla)';
$a->strings['Email access is disabled on this site.'] = 'E-mailtoegang is op deze website uitgeschakeld.';
$a->strings['None'] = 'Geen';
$a->strings['General Social Media Settings'] = 'Algemene Sociale Media Instellingen';
$a->strings['Attach the link title'] = 'Voeg de linktitel toe';
$a->strings['When activated, the title of the attached link will be added as a title on posts to Diaspora. This is mostly helpful with "remote-self" contacts that share feed content.'] = 'Indien geactiveerd, wordt de titel van de bijgevoegde link toegevoegd als titel op berichten op Diaspora. Dit is vooral handig bij contacten op afstand die zelf feed-inhoud delen.';
$a->strings['Email/Mailbox Setup'] = 'E-mail Instellen';
$a->strings['If you wish to communicate with email contacts using this service (optional), please specify how to connect to your mailbox.'] = 'Als je wilt communiceren met e-mail contacten via deze dienst (optioneel), moet je hier opgeven hoe ik jouw mailbox kan bereiken.';
$a->strings['Last successful email check:'] = 'Laatste succesvolle e-mail controle:';
$a->strings['IMAP server name:'] = 'IMAP server naam:';
$a->strings['IMAP port:'] = 'IMAP poort:';
$a->strings['Security:'] = 'Beveiliging:';
$a->strings['Email login name:'] = 'E-mail login naam:';
$a->strings['Email password:'] = 'E-mail wachtwoord:';
$a->strings['Reply-to address:'] = 'Antwoord adres:';
$a->strings['Send public posts to all email contacts:'] = 'Openbare posts naar alle e-mail contacten versturen:';
$a->strings['Action after import:'] = 'Actie na importeren:';
$a->strings['Move to folder'] = 'Naar map verplaatsen';
$a->strings['Move to folder:'] = 'Verplaatsen naar map:';
$a->strings['Importing Contacts done'] = 'Importeren Contacten voltooid';
$a->strings['Upload a CSV file that contains the handle of your followed accounts in the first column you exported from the old account.'] = 'Upload een CSV-bestand met de handle van uw gevolgde gebruikers in de eerste kolom die u uit de oude gebruiker hebt geëxporteerd.';
$a->strings['Upload File'] = 'Upload bestand';
$a->strings['Delegation successfully granted.'] = 'Delegatie met succes verleend.';
$a->strings['Parent user not found, unavailable or password doesn\'t match.'] = 'Brongebruiker niet gevonden, niet beschikbaar of wachtwoord komt niet overeen.';
$a->strings['Delegation successfully revoked.'] = 'Delegatie is ingetrokken.';
$a->strings['Delegated administrators can view but not change delegation permissions.'] = 'Gedelegeerde beheerders kunnen delegatierechten bekijken, maar niet wijzigen.';
$a->strings['Delegate user not found.'] = 'Gemachtigde gebruiker niet gevonden.';
$a->strings['No parent user'] = 'Ouderlijke gebruiker ontbreekt';
$a->strings['Parent User'] = 'Ouderlijke gebruiker';
$a->strings['Additional Accounts'] = 'Toegevoegde gebruikers';
$a->strings['Register additional accounts that are automatically connected to your existing account so you can manage them from this account.'] = 'Registreer extra gebruikers die automatisch zijn verbonden met uw bestaande gebruiker, zodat u ze vanuit deze gebruiker kunt beheren.';
$a->strings['Register an additional account'] = 'Registreer een toegevoegde gebruiker';
$a->strings['Parent users have total control about this account, including the account settings. Please double check whom you give this access.'] = 'Ouderlijke gebruikers hebben totale controle over dit account, de account instellingen inbegrepen. Dubbel check dus alstublieft aan wie je deze toegang geeft.';
$a->strings['Delegates'] = 'Gemachtigden';
$a->strings['Delegates are able to manage all aspects of this account/page except for basic account settings. Please do not delegate your personal account to anybody that you do not trust completely.'] = 'Personen waaraan het beheer is uitbesteed kunnen alle onderdelen van een account/pagina beheren, behalve de basisinstellingen van een account. Besteed je persoonlijke account daarom niet uit aan personen die je niet volledig vertrouwt.';
$a->strings['Existing Page Delegates'] = 'Bestaande personen waaraan het paginabeheer is uitbesteed';
$a->strings['Potential Delegates'] = 'Mogelijke personen waaraan het paginabeheer kan worden uitbesteed ';
$a->strings['No entries.'] = 'Geen gegevens.';
$a->strings['The theme you chose isn\'t available.'] = 'Het thema dat je koos is niet beschikbaar';
$a->strings['%s - (Unsupported)'] = '%s - (Niet ondersteund)';
$a->strings['Display Settings'] = 'Scherminstellingen';
$a->strings['General Theme Settings'] = 'Algemene Thema Instellingen';
$a->strings['Custom Theme Settings'] = 'Speciale Thema Instellingen';
$a->strings['Content Settings'] = 'Content Instellingen';
$a->strings['Theme settings'] = 'Thema-instellingen';
$a->strings['Display Theme:'] = 'Schermthema:';
$a->strings['Mobile Theme:'] = 'Mobiel thema:';
$a->strings['Number of items to display per page:'] = 'Aantal items te tonen per pagina:';
$a->strings['Maximum of 100 items'] = 'Maximum 100 items';
$a->strings['Number of items to display per page when viewed from mobile device:'] = 'Aantal items per pagina als je een mobiel toestel gebruikt:';
$a->strings['Update browser every xx seconds'] = 'Browser elke xx seconden verversen';
$a->strings['Minimum of 10 seconds. Enter -1 to disable it.'] = 'Minimum 10 seconden. Geef -1 op om te deactiveren.';
$a->strings['Infinite scroll'] = 'Oneindig scrollen';
$a->strings['Beginning of week:'] = 'Begin van de week:';
$a->strings['Additional Features'] = 'Extra functies';
$a->strings['Connected Apps'] = 'Verbonden applicaties';
$a->strings['Remove authorization'] = 'Verwijder authorisatie';
$a->strings['Profile couldn\'t be updated.'] = 'Profiel kan niet worden bijgewerkt.';
$a->strings['Label:'] = 'Label:';
$a->strings['Value:'] = 'Waarde:';
$a->strings['Field Permissions'] = 'Veldrechten';
$a->strings['(click to open/close)'] = '(klik om te openen/sluiten)';
$a->strings['Add a new profile field'] = 'Voeg nieuw profielveld toe';
$a->strings['Profile Actions'] = 'Profiel Acties';
$a->strings['Edit Profile Details'] = 'Profieldetails bewerken';
$a->strings['Change Profile Photo'] = 'Profielfoto wijzigen';
$a->strings['Profile picture'] = 'Profiel foto';
$a->strings['Location'] = 'Plaats';
$a->strings['Miscellaneous'] = 'Diversen';
$a->strings['Custom Profile Fields'] = 'Aangepaste profielvelden';
$a->strings['Upload Profile Photo'] = 'Profielfoto uploaden';
$a->strings['Street Address:'] = 'Postadres:';
$a->strings['Locality/City:'] = 'Gemeente/Stad:';
$a->strings['Region/State:'] = 'Regio/Staat:';
$a->strings['Postal/Zip Code:'] = 'Postcode:';
$a->strings['Country:'] = 'Land:';
$a->strings['XMPP (Jabber) address:'] = 'XMPP (Jabber) adres:';
$a->strings['Homepage URL:'] = 'Adres tijdlijn:';
$a->strings['Public Keywords:'] = 'Publieke Sleutelwoorden:';
$a->strings['(Used for suggesting potential friends, can be seen by others)'] = '(Gebruikt om mogelijke vrienden voor te stellen, kan door anderen gezien worden)';
$a->strings['Private Keywords:'] = 'Privé Sleutelwoorden:';
$a->strings['(Used for searching profiles, never shown to others)'] = '(Gebruikt om profielen te zoeken, nooit aan anderen getoond)';
$a->strings['Image size reduction [%s] failed.'] = 'Verkleining van de afbeelding [%s] mislukt.';
$a->strings['Shift-reload the page or clear browser cache if the new photo does not display immediately.'] = 'Shift-herlaad de pagina, of maak de browser cache leeg als nieuwe foto\'s niet onmiddellijk verschijnen.';
$a->strings['Unable to process image'] = 'Ik kan de afbeelding niet verwerken';
$a->strings['Photo not found.'] = 'Foto niet gevonden.';
$a->strings['Profile picture successfully updated.'] = 'Profielfoto geüpdatet.';
$a->strings['Crop Image'] = 'Afbeelding bijsnijden';
$a->strings['Please adjust the image cropping for optimum viewing.'] = 'Pas het afsnijden van de afbeelding aan voor het beste resultaat.';
$a->strings['Use Image As Is'] = 'Gebruik afbeelding zoals deze is';
$a->strings['Missing uploaded image.'] = 'Ontbrekende geüploade afbeelding.';
$a->strings['Profile Picture Settings'] = 'Profiel afbeelding instellingen';
$a->strings['Current Profile Picture'] = 'Huidige profielafbeelding';
$a->strings['Upload Profile Picture'] = 'Upload profiel afbeelding';
$a->strings['Upload Picture:'] = 'Upload afbeelding';
$a->strings['or'] = 'of';
$a->strings['skip this step'] = 'Deze stap overslaan';
$a->strings['select a photo from your photo albums'] = 'Kies een foto uit je fotoalbums';
$a->strings['[Friendica System Notify]'] = '[Friendica systeem notificatie]';
$a->strings['User deleted their account'] = 'Gebruiker verwijderde zijn of haar account';
$a->strings['On your Friendica node an user deleted their account. Please ensure that their data is removed from the backups.'] = 'Een gebruiker heeft zijn of haar account verwijderd op je Friendica node. Zorg er zeker voor dat zijn of haar data verwijderd is uit de backups.';
$a->strings['The user id is %d'] = 'De gebruikers id is %d';
$a->strings['Remove My Account'] = 'Verwijder mijn account';
$a->strings['This will completely remove your account. Once this has been done it is not recoverable.'] = 'Dit zal je account volledig verwijderen. Dit kan niet hersteld worden als het eenmaal uitgevoerd is.';
$a->strings['Please enter your password for verification:'] = 'Voer je wachtwoord in voor verificatie:';
$a->strings['Please enter your password to access this page.'] = 'Voer uw wachtwoord in om deze pagina te openen.';
$a->strings['App-specific password generation failed: The description is empty.'] = 'App-specifiek wachtwoord genereren mislukt: de beschrijving is leeg.';
$a->strings['App-specific password generation failed: This description already exists.'] = 'App-specifieke wachtwoordgeneratie mislukt: deze beschrijving bestaat al.';
$a->strings['App-specific passwords successfully revoked.'] = 'App-specifieke wachtwoorden succesvol ingetrokken.';
$a->strings['App-specific password successfully revoked.'] = 'App-specifiek wachtwoord succesvol ingetrokken.';
$a->strings['Two-factor app-specific passwords'] = 'Twee-factor app-specifieke wachtwoorden';
$a->strings['<p>App-specific passwords are randomly generated passwords used instead your regular password to authenticate your account on third-party applications that don\'t support two-factor authentication.</p>'] = '<p>App-specifieke wachtwoorden zijn willekeurig gegenereerde wachtwoorden die in plaats daarvan uw normale wachtwoord worden gebruikt om uw account te verifiëren bij applicaties van derden die geen tweefactorauthenticatie ondersteunen.</p>';
$a->strings['Make sure to copy your new app-specific password now. You won’t be able to see it again!'] = 'Zorg ervoor dat u nu uw nieuwe app-specifieke wachtwoord kopieert. U zult het niet meer kunnen zien!';
$a->strings['Last Used'] = 'Laatst gebruikt';
$a->strings['Revoke'] = 'Intrekken';
$a->strings['Revoke All'] = 'Alles intrekken';
$a->strings['When you generate a new app-specific password, you must use it right away, it will be shown to you once after you generate it.'] = 'Wanneer u een nieuw app-specifiek wachtwoord genereert, moet u dit meteen gebruiken, het wordt u een keer getoond nadat u het hebt gegenereerd.';
$a->strings['Generate new app-specific password'] = 'Genereer een nieuw app-specifiek wachtwoord';
$a->strings['Generate'] = 'Genereer';
$a->strings['Two-factor authentication successfully disabled.'] = 'Twee-factor-authenticatie succesvol uitgeschakeld.';
$a->strings['<p>Use an application on a mobile device to get two-factor authentication codes when prompted on login.</p>'] = '<p>Gebruik een applicatie op een mobiel apparaat om tweefactorauthenticatiecodes te krijgen wanneer daarom wordt gevraagd bij het inloggen.</p>';
$a->strings['Authenticator app'] = 'Authenticatie-app';
$a->strings['Configured'] = 'Geconfigureerd';
$a->strings['Not Configured'] = 'Niet geconfigureerd';
$a->strings['<p>You haven\'t finished configuring your authenticator app.</p>'] = '<p>U bent nog niet klaar met het configureren van uw authenticator-app.</p>';
$a->strings['<p>Your authenticator app is correctly configured.</p>'] = '<p>Uw authenticator-app is correct geconfigureerd.</p>';
$a->strings['Recovery codes'] = 'Herstelcodes';
$a->strings['Remaining valid codes'] = 'Resterende geldige codes';
$a->strings['<p>These one-use codes can replace an authenticator app code in case you have lost access to it.</p>'] = '<p>Deze codes voor eenmalig gebruik kunnen een authenticator-app-code vervangen als u er geen toegang toe heeft.</p>';
$a->strings['App-specific passwords'] = 'App-specifieke wachtwoorden';
$a->strings['Generated app-specific passwords'] = 'App-specifieke wachtwoorden gegenereerd';
$a->strings['<p>These randomly generated passwords allow you to authenticate on apps not supporting two-factor authentication.</p>'] = '<p>Met deze willekeurig gegenereerde wachtwoorden kunt u verifiëren bij apps die geen tweefactorauthenticatie ondersteunen.</p>';
$a->strings['Current password:'] = 'Huidig wachtwoord:';
$a->strings['You need to provide your current password to change two-factor authentication settings.'] = 'U moet uw huidige wachtwoord opgeven om de instellingen voor tweefactorauthenticatie te wijzigen.';
$a->strings['Enable two-factor authentication'] = 'Schakel tweefactorauthenticatie in';
$a->strings['Disable two-factor authentication'] = 'Schakel tweefactorauthenticatie uit';
$a->strings['Show recovery codes'] = 'Toon herstelcodes';
$a->strings['Manage app-specific passwords'] = 'Beheer app-specifieke wachtwoorden';
$a->strings['Finish app configuration'] = 'Voltooi de app-configuratie';
$a->strings['New recovery codes successfully generated.'] = 'Nieuwe herstelcodes zijn succesvol gegenereerd.';
$a->strings['Two-factor recovery codes'] = 'Twee-factor herstelcodes';
$a->strings['<p>Recovery codes can be used to access your account in the event you lose access to your device and cannot receive two-factor authentication codes.</p><p><strong>Put these in a safe spot!</strong> If you lose your device and don’t have the recovery codes you will lose access to your account.</p>'] = '<p>Herstelcodes kunnen worden gebruikt om je gebruiker te benaderen in het geval dat je geen toegang meer hebt tot je apparaat en je geen twee-factor autentificatie codes kunt ontvangen.</p><p><strong>Bewaar deze op een veilige plek!</strong> Als je je apparaat verliest en je hebt geen toegang tot de herstelcodes dan heb je geen toegang meer tot je gebruiker.</p>';
$a->strings['When you generate new recovery codes, you must copy the new codes. Your old codes won’t work anymore.'] = 'Wanneer u nieuwe herstelcodes genereert, moet u de nieuwe codes kopiëren. Uw oude codes werken niet meer.';
$a->strings['Generate new recovery codes'] = 'Genereer nieuwe herstelcodes';
$a->strings['Next: Verification'] = 'Volgende: verificatie';
$a->strings['Two-factor authentication successfully activated.'] = 'Twee-factor-authenticatie succesvol geactiveerd.';
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
</dl>'] = '<p>Of je kan de autentificatie instellingen handmatig versturen:</p>
<dl>
	<dt>Uitgever</dt>
	<dd>%s</dd>
	<dt>Gebruikersnaam</dt>
	<dd>%s</dd>
	<dt>Geheime sleutel</dt>
	<dd>%s</dd>
	<dt>Type</dt>
	<dd>Time-based</dd>
	<dt>Aantal tekens</dt>
	<dd>6</dd>
	<dt>Hashing algorithm</dt>
	<dd>SHA-1</dd>
</dl>';
$a->strings['Two-factor code verification'] = 'Tweeledige codeverificatie';
$a->strings['<p>Please scan this QR Code with your authenticator app and submit the provided code.</p>'] = '<p>Scan deze QR-code met uw authenticator-app en verzend de opgegeven code.</p>';
$a->strings['Verify code and enable two-factor authentication'] = 'Controleer de code en schakel tweefactorauthenticatie in';
$a->strings['Export account'] = 'Account exporteren';
$a->strings['Export your account info and contacts. Use this to make a backup of your account and/or to move it to another server.'] = 'Je account informatie en contacten exporteren. Gebruik dit om een backup van je account te maken en/of om het te verhuizen naar een andere server.';
$a->strings['Export all'] = 'Alles exporteren';
$a->strings['Export your account info, contacts and all your items as json. Could be a very big file, and could take a lot of time. Use this to make a full backup of your account (photos are not exported)'] = 'Exporteer uw gebruikersgegevens, contacten en al uw items als json. Kan een heel groot bestand zijn en kan veel tijd in beslag nemen. Gebruik dit om een ​​volledige back-up van uw account te maken (foto\'s worden niet geëxporteerd)';
$a->strings['Export Contacts to CSV'] = 'Export Contacten naar CSV';
$a->strings['Export the list of the accounts you are following as CSV file. Compatible to e.g. Mastodon.'] = 'Exporteer de lijst met de gebruikers die u volgt als CSV-bestand. Compatibel met b.v. Mastodont.';
$a->strings['At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name (pen name), an username (nickname) and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.'] = 'Op het moment van de registratie, en om communicatie mogelijk te maken tussen de gebruikersaccount en zijn of haar contacten, moet de gebruiker een weergave naam opgeven, een gebruikersnaam (bijnaam) en een werkend email adres. De namen zullen toegankelijk zijn op de profiel pagina van het account voor elke bezoeker van de pagina, zelfs als andere profiel details niet getoond worden. Het email adres zal enkel gebruikt worden om de gebruiker notificaties te sturen over interacties, maar zal niet zichtbaar getoond worden. Het oplijsten van een account in de gids van de node van de gebruiker of in de globale gids is optioneel en kan beheerd worden in de gebruikersinstellingen, dit is niet nodig voor communicatie.';
$a->strings['This data is required for communication and is passed on to the nodes of the communication partners and is stored there. Users can enter additional private data that may be transmitted to the communication partners accounts.'] = 'Deze data is vereist voor communicatie en wordt doorgegeven aan de nodes van de communicatie partners en wordt daar opgeslagen. Gebruikers kunnen bijkomende privé data opgeven die mag doorgegeven worden aan de accounts van de communicatie partners.';
$a->strings['Privacy Statement'] = 'Privacy Verklaring';
$a->strings['The requested item doesn\'t exist or has been deleted.'] = 'Het gevraagde item bestaat niet of is verwijderd';
$a->strings['Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions'] = 'Wissel tussen verschillende identiteiten of forum/groeppagina\'s die jouw accountdetails delen of waar je "beheerdersrechten" hebt gekregen.';
$a->strings['Select an identity to manage: '] = 'Selecteer een identiteit om te beheren:';
$a->strings['User imports on closed servers can only be done by an administrator.'] = 'Importen van een gebruiker op een gesloten node kan enkel gedaan worden door een administrator';
$a->strings['Move account'] = 'Account verplaatsen';
$a->strings['You can import an account from another Friendica server.'] = 'Je kunt een account van een andere Friendica server importeren.';
$a->strings['You need to export your account from the old server and upload it here. We will recreate your old account here with all your contacts. We will try also to inform your friends that you moved here.'] = 'Je moet je account bij de oude server exporteren, en hier uploaden. We zullen je oude account hier opnieuw aanmaken, met al je contacten. We zullen ook proberen om je vrienden in te lichten dat je naar hier verhuisd bent.';
$a->strings['This feature is experimental. We can\'t import contacts from the OStatus network (GNU Social/Statusnet) or from Diaspora'] = 'Dit feature is experimenteel. We kunnen contacten van het OStatus netwerk (GNU Social/Statusnet) of van Diaspora niet importeren.';
$a->strings['Account file'] = 'Account bestand';
$a->strings['To export your account, go to "Settings->Export your personal data" and select "Export account"'] = 'Om je account te exporteren, ga naar "Instellingen->Exporteer je persoonlijke data" en selecteer "Exporteer account"';
$a->strings['Error decoding account file'] = 'Fout bij decoderen van het account bestand';
$a->strings['Error! No version data in file! This is not a Friendica account file?'] = 'Fout! Geen versie data in het bestand! Is dit wel een Friendica account bestand?';
$a->strings['User \'%s\' already exists on this server!'] = 'Gebruiker \'%s\' bestaat al op deze server!';
$a->strings['User creation error'] = 'Fout bij het aanmaken van de gebruiker';
$a->strings['%d contact not imported'] = [
	0 => '%d contact werd niet geïmporteerd',
	1 => '%d contacten werden niet geïmporteerd',
];
$a->strings['User profile creation error'] = 'Fout bij het aanmaken van het gebruikersprofiel';
$a->strings['Done. You can now login with your username and password'] = 'Gebeurd. Je kunt nu inloggen met je gebruikersnaam en wachtwoord';
$a->strings['Welcome to Friendica'] = 'Welkom bij Friendica';
$a->strings['New Member Checklist'] = 'Checklist voor nieuwe leden';
$a->strings['We would like to offer some tips and links to help make your experience enjoyable. Click any item to visit the relevant page. A link to this page will be visible from your home page for two weeks after your initial registration and then will quietly disappear.'] = 'We willen je een paar tips en verwijzingen aanreiken om je een aangename ervaring te bezorgen. Klik op een item om de relevante pagina\'s te bezoeken. Een verwijzing naar deze pagina zal twee weken lang na je registratie zichtbaar zijn op je tijdlijn. Daarna zal de verwijzing stilletjes verdwijnen.';
$a->strings['Getting Started'] = 'Aan de slag';
$a->strings['Friendica Walk-Through'] = 'Doorloop Friendica';
$a->strings['On your <em>Quick Start</em> page - find a brief introduction to your profile and network tabs, make some new connections, and find some groups to join.'] = 'Op je <em>Snelstart</em> pagina kun je een korte inleiding vinden over je profiel en netwerk tabs, om enkele nieuwe connecties te leggen en groepen te vinden om lid van te worden.';
$a->strings['Go to Your Settings'] = 'Ga naar je instellingen';
$a->strings['On your <em>Settings</em> page -  change your initial password. Also make a note of your Identity Address. This looks just like an email address - and will be useful in making friends on the free social web.'] = 'Verander je initieel wachtwoord op je <em>instellingenpagina</em>. Noteer ook het adres van je identiteit. Dit ziet er uit als een e-mailadres - en zal nuttig zijn om vrienden te maken op het vrije sociale web.';
$a->strings['Review the other settings, particularly the privacy settings. An unpublished directory listing is like having an unlisted phone number. In general, you should probably publish your listing - unless all of your friends and potential friends know exactly how to find you.'] = 'Controleer ook de andere instellingen, in het bijzonder de privacy-instellingen. Een niet-gepubliceerd adres is zoals een privé-telefoonnummer. In het algemeen wil je waarschijnlijk je adres publiceren - tenzij al je vrienden en mogelijke vrienden precies weten hoe je te vinden.';
$a->strings['Upload a profile photo if you have not done so already. Studies have shown that people with real photos of themselves are ten times more likely to make friends than people who do not.'] = 'Upload een profielfoto, als je dat nog niet gedaan hebt. Studies tonen aan dat mensen met echte foto\'s van zichzelf tien keer gemakkelijker vrienden maken dan mensen die dat niet doen.';
$a->strings['Edit Your Profile'] = 'Bewerk je profiel';
$a->strings['Edit your <strong>default</strong> profile to your liking. Review the settings for hiding your list of friends and hiding the profile from unknown visitors.'] = 'Bewerk je <strong>standaard</strong> profiel zoals je wilt. Controleer de instellingen om je vriendenlijst te verbergen, en om je profiel voor ongekende bezoekers te verbergen.';
$a->strings['Profile Keywords'] = 'Sleutelwoorden voor dit profiel';
$a->strings['Set some public keywords for your profile which describe your interests. We may be able to find other people with similar interests and suggest friendships.'] = 'Stel een aantal openbare zoekwoorden in voor uw profiel die uw interesses beschrijven. Mogelijk kunnen we andere mensen met dezelfde interesses vinden en vriendschappen voorstellen.';
$a->strings['Connecting'] = 'Verbinding aan het maken';
$a->strings['Importing Emails'] = 'E-mails importeren';
$a->strings['Enter your email access information on your Connector Settings page if you wish to import and interact with friends or mailing lists from your email INBOX'] = 'Vul je e-mailtoegangsinformatie in op je pagina met verbindingsinstellingen als je vrienden of mailinglijsten uit je e-mail-inbox wilt importeren, en met hen wilt communiceren';
$a->strings['Go to Your Contacts Page'] = 'Ga naar je contactenpagina';
$a->strings['Your Contacts page is your gateway to managing friendships and connecting with friends on other networks. Typically you enter their address or site URL in the <em>Add New Contact</em> dialog.'] = 'Je contactenpagina is jouw poort om vriendschappen te beheren en verbinding te leggen met vrienden op andere netwerken. Je kunt hun adres of URL toevoegen in de <em>Voeg nieuw contact toe</em> dialoog.';
$a->strings['Go to Your Site\'s Directory'] = 'Ga naar de gids van je website';
$a->strings['The Directory page lets you find other people in this network or other federated sites. Look for a <em>Connect</em> or <em>Follow</em> link on their profile page. Provide your own Identity Address if requested.'] = 'In de gids vind je andere mensen in dit netwerk of op andere federatieve sites. Zoek naar het woord <em>Connect</em> of <em>Follow</em> op hun profielpagina (meestal aan de linkerkant). Vul je eigen identiteitsadres in wanneer daar om wordt gevraagd.';
$a->strings['Finding New People'] = 'Nieuwe mensen vinden';
$a->strings['On the side panel of the Contacts page are several tools to find new friends. We can match people by interest, look up people by name or interest, and provide suggestions based on network relationships. On a brand new site, friend suggestions will usually begin to be populated within 24 hours.'] = 'Op het zijpaneel van de Contacten pagina vind je verschillende tools om nieuwe vrienden te zoeken. We kunnen mensen op interesses matchen, mensen opzoeken op naam of hobby, en suggesties doen gebaseerd op netwerk-relaties. Op een nieuwe webstek beginnen vriendschapssuggesties meestal binnen de 24 uur beschikbaar te worden.';
$a->strings['Why Aren\'t My Posts Public?'] = 'Waarom zijn mijn berichten niet openbaar?';
$a->strings['Friendica respects your privacy. By default, your posts will only show up to people you\'ve added as friends. For more information, see the help section from the link above.'] = 'Friendica respecteert je privacy. Standaard zullen je berichten alleen zichtbaar zijn voor personen die jij als vriend hebt toegevoegd. Lees de help (zie de verwijzing hierboven) voor meer informatie.';
$a->strings['Getting Help'] = 'Hulp krijgen';
$a->strings['Go to the Help Section'] = 'Ga naar de help';
$a->strings['Our <strong>help</strong> pages may be consulted for detail on other program features and resources.'] = 'Je kunt onze <strong>help</strong> pagina\'s raadplegen voor gedetailleerde informatie over andere functies van dit programma.';
$a->strings['%s liked %s\'s post'] = '%s vond het bericht van %s leuk';
$a->strings['%s disliked %s\'s post'] = '%s vond het bericht van %s niet leuk';
$a->strings['%s is attending %s\'s event'] = '%s woont het event van %s bij';
$a->strings['%s is not attending %s\'s event'] = '%s woont het event van %s niet bij';
$a->strings['%s may attending %s\'s event'] = '%s kan aanwezig zijn op %s\'s gebeurtenis';
$a->strings['%s is now friends with %s'] = '%s is nu bevriend met %s';
$a->strings['%s commented on %s\'s post'] = '%s gaf een reactie op het bericht van %s';
$a->strings['%s created a new post'] = '%s schreef een nieuw bericht';
$a->strings['Friend Suggestion'] = 'Vriendschapsvoorstel';
$a->strings['Friend/Connect Request'] = 'Vriendschapsverzoek';
$a->strings['%s New mail received at %s'] = '%s Nieuw bericht ontvangen op %s';
$a->strings['%1$s sent you a new private message at %2$s.'] = '%1$s sent you a new private message at %2$s.';
$a->strings['a private message'] = 'een prive bericht';
$a->strings['%1$s sent you %2$s.'] = '%1$s stuurde jou %2$s.';
$a->strings['Please visit %s to view and/or reply to your private messages.'] = 'Bezoek %s om je privé-berichten te bekijken en/of te beantwoorden.';
$a->strings['%1$s commented on %2$s\'s %3$s %4$s'] = '%1$s heeft een opmerking geplaatst op %2$s\'s %3$s %4$s';
$a->strings['%1$s commented on your %2$s %3$s'] = '%1$s heeft een opmerking geplaatst op jou %2$s %3$s';
$a->strings['%1$s commented on their %2$s %3$s'] = '%1$s heeft een opmering geschreven op hun %2$s %3$s';
$a->strings['%1$s Comment to conversation #%2$d by %3$s'] = '%1$s Opmerking bij conversatie #%2$d door %3$s';
$a->strings['%s commented on an item/conversation you have been following.'] = '%s gaf een reactie op een bericht/gesprek die jij volgt.';
$a->strings['Please visit %s to view and/or reply to the conversation.'] = 'Bezoek %s om het gesprek te bekijken en/of te beantwoorden.';
$a->strings['%s %s posted to your profile wall'] = '%s %s heeft op je profiel wall gepost';
$a->strings['%1$s posted to your profile wall at %2$s'] = '%1$splaatste een bericht op je tijdlijn op %2$s';
$a->strings['%1$s posted to [url=%2$s]your wall[/url]'] = '%1$s schreef op [url=%2$s]jouw tijdlijn[/url]';
$a->strings['%s Introduction received'] = '%s Introductie ontvangen';
$a->strings['You\'ve received an introduction from \'%1$s\' at %2$s'] = 'Je hebt een vriendschaps- of connectieverzoek ontvangen van \'%1$s\' om %2$s';
$a->strings['You\'ve received [url=%1$s]an introduction[/url] from %2$s.'] = 'Je ontving [url=%1$s]een vriendschaps- of connectieverzoek[/url] van %2$s.';
$a->strings['You may visit their profile at %s'] = 'Je kunt hun profiel bezoeken op %s';
$a->strings['Please visit %s to approve or reject the introduction.'] = 'Bezoek %s om het verzoek goed of af te keuren.';
$a->strings['%s A new person is sharing with you'] = '%s Een nieuwe persoon deelt met je';
$a->strings['%1$s is sharing with you at %2$s'] = '%1$s deelt met jou in %2$s';
$a->strings['%s You have a new follower'] = '%s Je hebt een nieuwe volger';
$a->strings['You have a new follower at %2$s : %1$s'] = 'Je hebt een nieuwe volger op %2$s: %1$s';
$a->strings['%s Friend suggestion received'] = '%s Vriend suggestie ontvangen';
$a->strings['You\'ve received a friend suggestion from \'%1$s\' at %2$s'] = 'Je kreeg een vriendschapssuggestie van \'%1$s\' op %2$s';
$a->strings['You\'ve received [url=%1$s]a friend suggestion[/url] for %2$s from %3$s.'] = 'Je kreeg een [url=%1$s]vriendschapssuggestie[/url] voor %2$s op %3$s.';
$a->strings['Name:'] = 'Naam:';
$a->strings['Photo:'] = 'Foto: ';
$a->strings['Please visit %s to approve or reject the suggestion.'] = 'Bezoek %s om de suggestie goed of af te keuren.';
$a->strings['%s Connection accepted'] = '%s Verbinding geaccepteerd';
$a->strings['\'%1$s\' has accepted your connection request at %2$s'] = '\'%1$s\' aanvaarde je contactaanvraag op %2$s';
$a->strings['%2$s has accepted your [url=%1$s]connection request[/url].'] = '%2$saanvaardde jouw [url=%1$s]contactaanvraag[/url].';
$a->strings['You are now mutual friends and may exchange status updates, photos, and email without restriction.'] = 'Jullie zijn nu in contact met elkaar en kunnen statusberichten, foto\'s en email delen zonder beperkingen.';
$a->strings['Please visit %s if you wish to make any changes to this relationship.'] = 'Bezoek alstublieft %s als je deze relatie wil wijzigen.';
$a->strings['\'%1$s\' has chosen to accept you a fan, which restricts some forms of communication - such as private messaging and some profile interactions. If this is a celebrity or community page, these settings were applied automatically.'] = '\'%1$s\' koos om je te accepteren als fan, wat sommige communicatievormen beperkt - zoals privéberichten en sommige profielfuncties.  Als dit een beroemdheid- of groepspagina is, werd dit automatisch toegepast.';
$a->strings['\'%1$s\' may choose to extend this into a two-way or more permissive relationship in the future.'] = '\'%1$s\' kan er later voor kiezen om deze beperkingen aan te passen.';
$a->strings['Please visit %s  if you wish to make any changes to this relationship.'] = 'Bezoek %s wanneer je deze relatie wil wijzigen.';
$a->strings['registration request'] = 'registratie verzoek';
$a->strings['You\'ve received a registration request from \'%1$s\' at %2$s'] = 'Je kreeg een registratieaanvraag van \'%1$s\' op %2$s';
$a->strings['You\'ve received a [url=%1$s]registration request[/url] from %2$s.'] = 'Je kreeg een [url=%1$s]registratieaanvraag[/url] van %2$s.';
$a->strings['Please visit %s to approve or reject the request.'] = 'Bezoek %s om de aanvraag goed of af te keuren.';
$a->strings['%s %s tagged you'] = '%s %s heeft jou getagged';
$a->strings['%s %s shared a new post'] = '%s %s deelde een nieuwe post';
$a->strings['This message was sent to you by %s, a member of the Friendica social network.'] = 'Dit bericht werd naar jou gestuurd door %s, een lid van het Friendica sociale netwerk.';
$a->strings['You may visit them online at %s'] = 'Je kunt ze online bezoeken op %s';
$a->strings['Please contact the sender by replying to this post if you do not wish to receive these messages.'] = 'Contacteer de afzender door op dit bericht te antwoorden als je deze berichten niet wilt ontvangen.';
$a->strings['%s posted an update.'] = '%s heeft een wijziging geplaatst.';
$a->strings['Private Message'] = 'Privébericht';
$a->strings['This entry was edited'] = 'Deze entry werd bewerkt';
$a->strings['Edit'] = 'Bewerken';
$a->strings['Delete globally'] = 'Verwijder globaal';
$a->strings['Remove locally'] = 'Verwijder lokaal';
$a->strings['I will attend'] = 'Ik zal er zijn';
$a->strings['I will not attend'] = 'Ik zal er niet zijn';
$a->strings['I might attend'] = 'Ik ga misschien';
$a->strings['to'] = 'aan';
$a->strings['via'] = 'via';
$a->strings['Wall-to-Wall'] = 'wall-to-wall';
$a->strings['via Wall-To-Wall:'] = 'via wall-to-wall';
$a->strings['Reply to %s'] = 'Antwoord aan %s';
$a->strings['More'] = 'Meer';
$a->strings['Notifier task is pending'] = 'Meldingstaak is in behandeling';
$a->strings['Delivery to remote servers is pending'] = 'Levering aan externe servers is in behandeling';
$a->strings['%d comment'] = [
	0 => '%d reactie',
	1 => '%d reacties',
];
$a->strings['Show more'] = 'Toon meer';
$a->strings['Show fewer'] = 'Toon minder';
$a->strings['Login failed.'] = 'Login mislukt.';
$a->strings['Login failed. Please check your credentials.'] = 'Aanmelden mislukt. Controleer uw inloggegevens.';
$a->strings['Welcome %s'] = 'Welkom %s';
$a->strings['Please upload a profile photo.'] = 'Upload een profielfoto.';
$a->strings['OpenWebAuth: %1$s welcomes %2$s'] = 'OpenWebAuth: %1$s verwelkomt %2$s';
