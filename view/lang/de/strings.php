<?php

if(! function_exists("string_plural_select_de")) {
function string_plural_select_de($n){
	$n = intval($n);
	return intval($n != 1);
}}
$a->strings['Unable to locate original post.'] = 'Konnte den Originalbeitrag nicht finden.';
$a->strings['Post updated.'] = 'Beitrag aktualisiert.';
$a->strings['Item wasn\'t stored.'] = 'Eintrag wurde nicht gespeichert';
$a->strings['Item couldn\'t be fetched.'] = 'Eintrag konnte nicht geholt werden.';
$a->strings['Empty post discarded.'] = 'Leerer Beitrag wurde verworfen.';
$a->strings['Item not found.'] = 'Beitrag nicht gefunden.';
$a->strings['Permission denied.'] = 'Zugriff verweigert.';
$a->strings['No valid account found.'] = 'Kein gültiges Konto gefunden.';
$a->strings['Password reset request issued. Check your email.'] = 'Zurücksetzen des Passworts eingeleitet. Bitte überprüfe Deine E-Mail.';
$a->strings['
		Dear %1$s,
			A request was recently received at "%2$s" to reset your account
		password. In order to confirm this request, please select the verification link
		below or paste it into your web browser address bar.

		If you did NOT request this change, please DO NOT follow the link
		provided and ignore and/or delete this email, the request will expire shortly.

		Your password will not be changed unless we can verify that you
		issued this request.'] = '
Hallo %1$s,

Auf "%2$s" ist eine Anfrage auf das Zurücksetzen deines Passworts gestellt
worden. Um diese Anfrage zu verifizieren, folge bitte dem unten stehenden
Link oder kopiere und füge ihn in die Adressleiste deines Browsers ein.

Solltest du die Anfrage NICHT gestellt haben, ignoriere und/oder lösche diese
E-Mail bitte.

Dein Passwort wird nicht geändert, solange wir nicht verifiziert haben, dass
du diese Änderung angefragt hast.';
$a->strings['
		Follow this link soon to verify your identity:

		%1$s

		You will then receive a follow-up message containing the new password.
		You may change that password from your account settings page after logging in.

		The login details are as follows:

		Site Location:	%2$s
		Login Name:	%3$s'] = '
Um deine Identität zu verifizieren, folge bitte diesem Link:

%1$s

Du wirst eine weitere E-Mail mit deinem neuen Passwort erhalten. Sobald du dich
angemeldet hast, kannst du dein Passwort in den Einstellungen ändern.

Die Anmeldedetails sind die folgenden:

Adresse der Seite:	%2$s
Benutzername:	%3$s';
$a->strings['Password reset requested at %s'] = 'Anfrage zum Zurücksetzen des Passworts auf %s erhalten';
$a->strings['Request could not be verified. (You may have previously submitted it.) Password reset failed.'] = 'Anfrage konnte nicht verifiziert werden. (Eventuell hast du bereits eine ähnliche Anfrage gestellt.) Zurücksetzen des Passworts gescheitert.';
$a->strings['Request has expired, please make a new one.'] = 'Die Anfrage ist abgelaufen. Bitte stelle eine erneute.';
$a->strings['Forgot your Password?'] = 'Hast du dein Passwort vergessen?';
$a->strings['Enter your email address and submit to have your password reset. Then check your email for further instructions.'] = 'Gib Deine E-Mail-Adresse an und fordere ein neues Passwort an. Es werden dir dann weitere Informationen per Mail zugesendet.';
$a->strings['Nickname or Email: '] = 'Spitzname oder E-Mail:';
$a->strings['Reset'] = 'Zurücksetzen';
$a->strings['Password Reset'] = 'Passwort zurücksetzen';
$a->strings['Your password has been reset as requested.'] = 'Dein Passwort wurde wie gewünscht zurückgesetzt.';
$a->strings['Your new password is'] = 'Dein neues Passwort lautet';
$a->strings['Save or copy your new password - and then'] = 'Speichere oder kopiere dein neues Passwort - und dann';
$a->strings['click here to login'] = 'hier klicken, um dich anzumelden';
$a->strings['Your password may be changed from the <em>Settings</em> page after successful login.'] = 'Du kannst das Passwort in den <em>Einstellungen</em> ändern, sobald du dich erfolgreich angemeldet hast.';
$a->strings['Your password has been reset.'] = 'Dein Passwort wurde zurückgesetzt.';
$a->strings['
			Dear %1$s,
				Your password has been changed as requested. Please retain this
			information for your records (or change your password immediately to
			something that you will remember).
		'] = '
Hallo %1$s,

Dein Passwort wurde wie gewünscht geändert. Bitte bewahre diese Informationen gut auf (oder ändere dein Passwort in eines, das du dir leicht merken kannst).';
$a->strings['
			Your login details are as follows:

			Site Location:	%1$s
			Login Name:	%2$s
			Password:	%3$s

			You may change that password from your account settings page after logging in.
		'] = '
Die Anmeldedaten sind die folgenden:

Adresse der Seite: %1$s
Login Name: %2$s
Passwort: %3$s

Das Passwort kann und sollte in den Kontoeinstellungen nach der Anmeldung geändert werden.';
$a->strings['Your password has been changed at %s'] = 'Auf %s wurde dein Passwort geändert';
$a->strings['New Message'] = 'Neue Nachricht';
$a->strings['No recipient selected.'] = 'Kein Empfänger gewählt.';
$a->strings['Unable to locate contact information.'] = 'Konnte die Kontaktinformationen nicht finden.';
$a->strings['Message could not be sent.'] = 'Nachricht konnte nicht gesendet werden.';
$a->strings['Message collection failure.'] = 'Konnte Nachrichten nicht abrufen.';
$a->strings['Discard'] = 'Verwerfen';
$a->strings['Messages'] = 'Nachrichten';
$a->strings['Conversation not found.'] = 'Unterhaltung nicht gefunden.';
$a->strings['Message was not deleted.'] = 'Nachricht wurde nicht gelöscht';
$a->strings['Conversation was not removed.'] = 'Unterhaltung wurde nicht entfernt';
$a->strings['Please enter a link URL:'] = 'Bitte gib die URL des Links ein:';
$a->strings['Send Private Message'] = 'Private Nachricht senden';
$a->strings['To:'] = 'An:';
$a->strings['Subject:'] = 'Betreff:';
$a->strings['Your message:'] = 'Deine Nachricht:';
$a->strings['Upload photo'] = 'Foto hochladen';
$a->strings['Insert web link'] = 'Einen Link einfügen';
$a->strings['Please wait'] = 'Bitte warten';
$a->strings['Submit'] = 'Senden';
$a->strings['No messages.'] = 'Keine Nachrichten.';
$a->strings['Message not available.'] = 'Nachricht nicht verfügbar.';
$a->strings['Delete message'] = 'Nachricht löschen';
$a->strings['D, d M Y - g:i A'] = 'D, d. M Y - H:i';
$a->strings['Delete conversation'] = 'Unterhaltung löschen';
$a->strings['No secure communications available. You <strong>may</strong> be able to respond from the sender\'s profile page.'] = 'Sichere Kommunikation ist nicht verfügbar. <strong>Eventuell</strong> kannst du auf der Profilseite des Absenders antworten.';
$a->strings['Send Reply'] = 'Antwort senden';
$a->strings['Unknown sender - %s'] = 'Unbekannter Absender - %s';
$a->strings['You and %s'] = 'Du und %s';
$a->strings['%s and You'] = '%s und du';
$a->strings['%d message'] = [
	0 => '%d Nachricht',
	1 => '%d Nachrichten',
];
$a->strings['Personal Notes'] = 'Persönliche Notizen';
$a->strings['Personal notes are visible only by yourself.'] = 'Persönliche Notizen sind nur für dich sichtbar.';
$a->strings['Save'] = 'Speichern';
$a->strings['User not found.'] = 'Benutzer nicht gefunden.';
$a->strings['Photo Albums'] = 'Fotoalben';
$a->strings['Recent Photos'] = 'Neueste Fotos';
$a->strings['Upload New Photos'] = 'Neue Fotos hochladen';
$a->strings['everybody'] = 'jeder';
$a->strings['Contact information unavailable'] = 'Kontaktinformationen nicht verfügbar';
$a->strings['Album not found.'] = 'Album nicht gefunden.';
$a->strings['Album successfully deleted'] = 'Album wurde erfolgreich gelöscht.';
$a->strings['Album was empty.'] = 'Album ist leer.';
$a->strings['Failed to delete the photo.'] = 'Das Foto konnte nicht gelöscht werden.';
$a->strings['a photo'] = 'einem Foto';
$a->strings['%1$s was tagged in %2$s by %3$s'] = '%1$s wurde von %3$s in %2$s getaggt';
$a->strings['Public access denied.'] = 'Öffentlicher Zugriff verweigert.';
$a->strings['No photos selected'] = 'Keine Bilder ausgewählt';
$a->strings['The maximum accepted image size is %s'] = 'Die maximale erlaubte Größe von Bildern beträgt %s';
$a->strings['Upload Photos'] = 'Bilder hochladen';
$a->strings['New album name: '] = 'Name des neuen Albums: ';
$a->strings['or select existing album:'] = 'oder wähle ein bestehendes Album:';
$a->strings['Do not show a status post for this upload'] = 'Keine Status-Mitteilung für diesen Beitrag anzeigen';
$a->strings['Permissions'] = 'Berechtigungen';
$a->strings['Do you really want to delete this photo album and all its photos?'] = 'Möchtest du wirklich dieses Foto-Album und all seine Foto löschen?';
$a->strings['Delete Album'] = 'Album löschen';
$a->strings['Cancel'] = 'Abbrechen';
$a->strings['Edit Album'] = 'Album bearbeiten';
$a->strings['Drop Album'] = 'Album löschen';
$a->strings['Show Newest First'] = 'Zeige neueste zuerst';
$a->strings['Show Oldest First'] = 'Zeige älteste zuerst';
$a->strings['View Photo'] = 'Foto betrachten';
$a->strings['Permission denied. Access to this item may be restricted.'] = 'Zugriff verweigert. Zugriff zu diesem Eintrag könnte eingeschränkt sein.';
$a->strings['Photo not available'] = 'Foto nicht verfügbar';
$a->strings['Do you really want to delete this photo?'] = 'Möchtest du wirklich dieses Foto löschen?';
$a->strings['Delete Photo'] = 'Foto löschen';
$a->strings['View photo'] = 'Fotos ansehen';
$a->strings['Edit photo'] = 'Foto bearbeiten';
$a->strings['Delete photo'] = 'Foto löschen';
$a->strings['Use as profile photo'] = 'Als Profilbild verwenden';
$a->strings['Private Photo'] = 'Privates Foto';
$a->strings['View Full Size'] = 'Betrachte Originalgröße';
$a->strings['Tags: '] = 'Tags: ';
$a->strings['[Select tags to remove]'] = '[Zu entfernende Tags auswählen]';
$a->strings['New album name'] = 'Name des neuen Albums';
$a->strings['Caption'] = 'Bildunterschrift';
$a->strings['Add a Tag'] = 'Tag hinzufügen';
$a->strings['Example: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping'] = 'Beispiel: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping';
$a->strings['Do not rotate'] = 'Nicht rotieren';
$a->strings['Rotate CW (right)'] = 'Drehen US (rechts)';
$a->strings['Rotate CCW (left)'] = 'Drehen EUS (links)';
$a->strings['This is you'] = 'Das bist du';
$a->strings['Comment'] = 'Kommentar';
$a->strings['Preview'] = 'Vorschau';
$a->strings['Loading...'] = 'lädt...';
$a->strings['Select'] = 'Auswählen';
$a->strings['Delete'] = 'Löschen';
$a->strings['Like'] = 'Mag ich';
$a->strings['I like this (toggle)'] = 'Ich mag das (toggle)';
$a->strings['Dislike'] = 'Mag ich nicht';
$a->strings['I don\'t like this (toggle)'] = 'Ich mag das nicht (toggle)';
$a->strings['Map'] = 'Karte';
$a->strings['Apologies but the website is unavailable at the moment.'] = 'Entschuldigung, aber die Webseite ist derzeit nicht erreichbar.';
$a->strings['Delete this item?'] = 'Diesen Beitrag löschen?';
$a->strings['Block this author? They won\'t be able to follow you nor see your public posts, and you won\'t be able to see their posts and their notifications.'] = 'Soll dieser Autor geblockt werden? Sie werden nicht in der Lage sein, dir zu folgen oder deine öffentlichen Beiträge zu sehen. Außerdem wirst du nicht in der Lage sein ihre Beiträge und Benachrichtigungen zu lesen.';
$a->strings['Ignore this author? You won\'t be able to see their posts and their notifications.'] = 'Diesen Autor ignorieren? Du wirst seine Beiträge und Benachrichtigungen nicht mehr sehen können.';
$a->strings['Collapse this author\'s posts?'] = 'Beiträge dieses Autors zusammenklappen?';
$a->strings['Ignore this author\'s server?'] = 'Den Server dieses Autors ignorieren?';
$a->strings['You won\'t see any content from this server including reshares in your Network page, the community pages and individual conversations.'] = 'Du wirst keine Inhalte von dieser Instanz sehen, auch nicht das erneute Teilen auf Ihrer Netzwerkseite, den Gemeinschaftsseiten und einzelnen Unterhaltungen.';
$a->strings['Like not successful'] = 'Das "Mag ich" war nicht erfolgreich';
$a->strings['Dislike not successful'] = 'Das "Mag ich nicht" war nicht erfolgreich';
$a->strings['Sharing not successful'] = 'Das Teilen war nicht erfolgreich';
$a->strings['Attendance unsuccessful'] = 'Die Teilnahme war nicht erfolgreich';
$a->strings['Backend error'] = 'Fehler im Backend';
$a->strings['Network error'] = 'Netzwerkfehler';
$a->strings['Drop files here to upload'] = 'Ziehe Dateien hierher, um sie hochzuladen';
$a->strings['Your browser does not support drag and drop file uploads.'] = 'Dein Browser unterstützt das Hochladen von Dateien per Drag & Drop nicht.';
$a->strings['Please use the fallback form below to upload your files like in the olden days.'] = 'Bitte verwende das unten stehende Formular, um Ihre Dateien wie früher hochzuladen.';
$a->strings['File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.'] = 'Datei ist zu groß ({{filesize}}MiB). Maximale Dateigröße: {{maxFilesize}}MiB.';
$a->strings['You can\'t upload files of this type.'] = 'Du kannst keine Dateien dieses Typs hochladen.';
$a->strings['Server responded with {{statusCode}} code.'] = 'Der Server antwortete mit Status-Code {{statusCode}} ';
$a->strings['Cancel upload'] = 'Hochladen abbrechen';
$a->strings['Upload canceled.'] = 'Hochladen abgebrochen';
$a->strings['Are you sure you want to cancel this upload?'] = 'Bist du sicher, dass du diesen Upload abbrechen möchten?';
$a->strings['Remove file'] = 'Datei entfernen';
$a->strings['You can\'t upload any more files.'] = 'Du kannst keine weiteren Dateien hochladen.';
$a->strings['toggle mobile'] = 'mobile Ansicht umschalten';
$a->strings['Close'] = 'Schließen';
$a->strings['Method not allowed for this module. Allowed method(s): %s'] = 'Diese Methode ist in diesem Modul nicht erlaubt. Erlaubte Methoden sind: %s';
$a->strings['Page not found.'] = 'Seite nicht gefunden.';
$a->strings['You must be logged in to use addons. '] = 'Du musst angemeldet sein, um Addons benutzen zu können.';
$a->strings['No system theme config value set.'] = 'Es wurde kein Konfigurationswert für das systemweite Theme gesetzt.';
$a->strings['The form security token was not correct. This probably happened because the form has been opened for too long (>3 hours) before submitting it.'] = 'Das Sicherheitsmerkmal war nicht korrekt. Das passiert meistens, wenn das Formular vor dem Absenden zu lange geöffnet war (länger als 3 Stunden).';
$a->strings['All contacts'] = 'Alle Kontakte';
$a->strings['Followers'] = 'Folgende';
$a->strings['Following'] = 'Gefolgte';
$a->strings['Mutual friends'] = 'Gegenseitige Freundschaft';
$a->strings['Common'] = 'Gemeinsam';
$a->strings['Addon not found'] = 'Addon nicht gefunden';
$a->strings['Addon already enabled'] = 'Addon bereits aktiviert';
$a->strings['Addon already disabled'] = 'Addon bereits deaktiviert';
$a->strings['Could not find any unarchived contact entry for this URL (%s)'] = 'Für die URL (%s) konnte kein nicht-archivierter Kontakt gefunden werden';
$a->strings['The contact entries have been archived'] = 'Die Kontakteinträge wurden archiviert.';
$a->strings['The avatar cache needs to be disabled in local.config.php to use this command.'] = 'Der Zwischenspeicher für Kontaktprofilbilder muss in der local.config.php deaktiviert sein, um diesen Befehl nutzen zu können.';
$a->strings['Could not find any contact entry for this URL (%s)'] = 'Für die URL (%s) konnte kein Kontakt gefunden werden';
$a->strings['The contact has been blocked from the node'] = 'Der Kontakt wurde von diesem Knoten geblockt';
$a->strings['%d %s, %d duplicates.'] = '%d %s, Duplikat %d.';
$a->strings['uri-id is empty for contact %s.'] = 'URI-ID ist leer für den Kontakt %s.';
$a->strings['No valid first contact found for uri-id %d.'] = 'Kein gültiger erster Kontakt für die url-id %d gefunden.';
$a->strings['Wrong duplicate found for uri-id %d in %d (url: %s != %s).'] = 'Falsches Dublikat für die URI-ID %d in %d gefunden (URI: %s != %s).';
$a->strings['Wrong duplicate found for uri-id %d in %d (nurl: %s != %s).'] = 'Falsches Dublikat für die URI-ID %d in %d (nurl: %s != %s).';
$a->strings['Deletion of id %d failed'] = 'Löschung der ID %d fehlgeschlagen';
$a->strings['Deletion of id %d was successful'] = 'Löschug der ID %d war erfolgreich';
$a->strings['Updating "%s" in "%s" from %d to %d'] = 'Aktualisieren "%s" nach "%s" von %d nach %d';
$a->strings[' - found'] = '- gefunden';
$a->strings[' - failed'] = '- fehlgeschlagen';
$a->strings[' - success'] = '- Erfolg';
$a->strings[' - deleted'] = '- gelöscht';
$a->strings[' - done'] = '- erledigt';
$a->strings['The avatar cache needs to be enabled to use this command.'] = 'Der Zwischenspeicher für Kontaktprofilbilder muss aktiviert sein, um diesen Befehl nutzen zu können.';
$a->strings['no resource in photo %s'] = 'keine Ressource im Foto %s';
$a->strings['no photo with id %s'] = 'es existiert kein Foto mit der ID %s';
$a->strings['no image data for photo with id %s'] = 'es gibt eine Bilddaten für das Foto mit der ID %s';
$a->strings['invalid image for id %s'] = 'ungültiges Bild für die ID %s';
$a->strings['Quit on invalid photo %s'] = 'Abbruch bei ungültigem Foto %s';
$a->strings['Post update version number has been set to %s.'] = 'Die Post-Update-Versionsnummer wurde auf %s gesetzt.';
$a->strings['Check for pending update actions.'] = 'Überprüfe ausstehende Update-Aktionen';
$a->strings['Done.'] = 'Erledigt.';
$a->strings['Execute pending post updates.'] = 'Ausstehende Post-Updates ausführen';
$a->strings['All pending post updates are done.'] = 'Alle ausstehenden Post-Updates wurden ausgeführt.';
$a->strings['Enter user nickname: '] = 'Spitzname angeben:';
$a->strings['User not found'] = 'Nutzer nicht gefunden';
$a->strings['Enter new password: '] = 'Neues Passwort eingeben:';
$a->strings['Password update failed. Please try again.'] = 'Aktualisierung des Passworts gescheitert, bitte versuche es noch einmal.';
$a->strings['Password changed.'] = 'Passwort geändert.';
$a->strings['Enter user name: '] = 'Nutzername angeben';
$a->strings['Enter user email address: '] = 'E-Mail Adresse angeben:';
$a->strings['Enter a language (optional): '] = 'Sprache angeben (optional):';
$a->strings['Enter URL of an image to use as avatar (optional): '] = 'Gib die URL eines Bildes ein, das als Avatar verwendet werden soll (optional):';
$a->strings['User is not pending.'] = 'Benutzer wartet nicht.';
$a->strings['User has already been marked for deletion.'] = 'User wurde bereits zum Löschen ausgewählt';
$a->strings['Type "yes" to delete %s'] = '"yes" eingeben um %s zu löschen';
$a->strings['Deletion aborted.'] = 'Löschvorgang abgebrochen.';
$a->strings['Enter category: '] = 'Kategorie eingeben';
$a->strings['Enter key: '] = 'Schlüssel eingeben';
$a->strings['Enter value: '] = 'Wert eingeben';
$a->strings['newer'] = 'neuer';
$a->strings['older'] = 'älter';
$a->strings['Frequently'] = 'immer wieder';
$a->strings['Hourly'] = 'Stündlich';
$a->strings['Twice daily'] = 'Zweimal täglich';
$a->strings['Daily'] = 'Täglich';
$a->strings['Weekly'] = 'Wöchentlich';
$a->strings['Monthly'] = 'Monatlich';
$a->strings['DFRN'] = 'DFRN';
$a->strings['OStatus'] = 'OStatus';
$a->strings['RSS/Atom'] = 'RSS/Atom';
$a->strings['Email'] = 'E-Mail';
$a->strings['Diaspora'] = 'Diaspora';
$a->strings['Zot!'] = 'Zott';
$a->strings['LinkedIn'] = 'LinkedIn';
$a->strings['XMPP/IM'] = 'XMPP/Chat';
$a->strings['MySpace'] = 'MySpace';
$a->strings['Google+'] = 'Google+';
$a->strings['pump.io'] = 'pump.io';
$a->strings['Twitter'] = 'Twitter';
$a->strings['Discourse'] = 'Discourse';
$a->strings['Diaspora Connector'] = 'Diaspora Connector';
$a->strings['GNU Social Connector'] = 'GNU Social Connector';
$a->strings['ActivityPub'] = 'ActivityPub';
$a->strings['pnut'] = 'pnut';
$a->strings['Tumblr'] = 'Tumblr';
$a->strings['Bluesky'] = 'Bluesky';
$a->strings['%s (via %s)'] = '%s (via %s)';
$a->strings['and'] = 'und';
$a->strings['and %d other people'] = 'und %dandere';
$a->strings['%2$s likes this.'] = [
	0 => '%2$s mag das.',
	1 => '%2$s mögen das.',
];
$a->strings['%2$s doesn\'t like this.'] = [
	0 => '%2$s mag das nicht.',
	1 => '%2$s mögen das nicht.',
];
$a->strings['%2$s attends.'] = [
	0 => '%2$s nimmt teil.',
	1 => '%2$s nehmen teil.',
];
$a->strings['%2$s doesn\'t attend.'] = [
	0 => '%2$s nimmt nicht teil.',
	1 => '%2$s nehmen nicht teil.',
];
$a->strings['%2$s attends maybe.'] = [
	0 => '%2$s nimmt eventuell teil.',
	1 => '%2$s nehmen eventuell teil.',
];
$a->strings['%2$s reshared this.'] = [
	0 => '%2$s hat dies geteilt.',
	1 => '%2$s haben dies geteilt.',
];
$a->strings['<button type="button" %2$s>%1$d person</button> likes this'] = [
	0 => '<button type="button" %2$s>%1$d Person</button> mag das',
	1 => '<button type="button" %2$s>%1$d Menschen</button> mögen das',
];
$a->strings['<button type="button" %2$s>%1$d person</button> doesn\'t like this'] = [
	0 => '<button type="button" %2$s>%1$d Person</button> mag das nicht',
	1 => '<button type="button" %2$s>%1$d Menschen </button> mögen das nicht',
];
$a->strings['<button type="button" %2$s>%1$d person</button> attends'] = [
	0 => '<button type="button" %2$s>%1$d Person</button> besucht',
	1 => '<button type="button" %2$s>%1$d Menschen </button> besuchen',
];
$a->strings['<button type="button" %2$s>%1$d person</button> doesn\'t attend'] = [
	0 => '<button type="button" %2$s>%1$d Person</button> besucht nicht',
	1 => '<button type="button" %2$s>%1$d Menschen </button> besuchen nicht',
];
$a->strings['<button type="button" %2$s>%1$d person</button> attends maybe'] = [
	0 => '<button type="button" %2$s>%1$d Person</button> besucht vielleicht',
	1 => '<button type="button" %2$s>%1$d Menschen</button> besuchen vielleicht',
];
$a->strings['<button type="button" %2$s>%1$d person</button> reshared this'] = [
	0 => '<button type="button" %2$s>%1$d Person </button> teilte dies erneut',
	1 => '<button type="button" %2$s>%1$d Menschen </button> teilten dies erneut',
];
$a->strings['Visible to <strong>everybody</strong>'] = 'Für <strong>jedermann</strong> sichtbar';
$a->strings['Please enter a image/video/audio/webpage URL:'] = 'Bitte gib eine Bild/Video/Audio/Webseiten-URL ein:';
$a->strings['Tag term:'] = 'Tag:';
$a->strings['Save to Folder'] = 'In Ordner speichern';
$a->strings['Where are you right now?'] = 'Wo hältst du dich jetzt gerade auf?';
$a->strings['Delete item(s)?'] = 'Einträge löschen?';
$a->strings['Created at'] = 'Erstellt am';
$a->strings['New Post'] = 'Neuer Beitrag';
$a->strings['Share'] = 'Teilen';
$a->strings['upload photo'] = 'Bild hochladen';
$a->strings['Attach file'] = 'Datei anhängen';
$a->strings['attach file'] = 'Datei anhängen';
$a->strings['Bold'] = 'Fett';
$a->strings['Italic'] = 'Kursiv';
$a->strings['Underline'] = 'Unterstrichen';
$a->strings['Quote'] = 'Zitat';
$a->strings['Add emojis'] = 'Emojis hinzufügen';
$a->strings['Content Warning'] = 'Inhaltswarnung';
$a->strings['Code'] = 'Code';
$a->strings['Image'] = 'Bild';
$a->strings['Link'] = 'Link';
$a->strings['Link or Media'] = 'Link oder Mediendatei';
$a->strings['Video'] = 'Video';
$a->strings['Set your location'] = 'Deinen Standort festlegen';
$a->strings['set location'] = 'Ort setzen';
$a->strings['Clear browser location'] = 'Browser-Standort leeren';
$a->strings['clear location'] = 'Ort löschen';
$a->strings['Set title'] = 'Titel setzen';
$a->strings['Categories (comma-separated list)'] = 'Kategorien (kommasepariert)';
$a->strings['Scheduled at'] = 'Geplant für';
$a->strings['Permission settings'] = 'Berechtigungseinstellungen';
$a->strings['Public post'] = 'Öffentlicher Beitrag';
$a->strings['Message'] = 'Nachricht';
$a->strings['Browser'] = 'Browser';
$a->strings['Open Compose page'] = 'Composer Seite öffnen';
$a->strings['remove'] = 'löschen';
$a->strings['Delete Selected Items'] = 'Lösche die markierten Beiträge';
$a->strings['You had been addressed (%s).'] = 'Du wurdest angeschrieben (%s).';
$a->strings['You are following %s.'] = 'Du folgst %s.';
$a->strings['You subscribed to %s.'] = 'Du hast %s abonniert.';
$a->strings['You subscribed to one or more tags in this post.'] = 'Du folgst einem oder mehreren Hashtags dieses Beitrags.';
$a->strings['%s reshared this.'] = '%s hat dies geteilt';
$a->strings['Reshared'] = 'Geteilt';
$a->strings['Reshared by %s <%s>'] = 'Geteilt von %s <%s>';
$a->strings['%s is participating in this thread.'] = '%s ist an der Unterhaltung beteiligt.';
$a->strings['Stored for general reasons'] = 'Aus allgemeinen Gründen aufbewahrt';
$a->strings['Global post'] = 'Globaler Beitrag';
$a->strings['Sent via an relay server'] = 'Über einen Relay-Server gesendet';
$a->strings['Sent via the relay server %s <%s>'] = 'Über den Relay-Server %s <%s> gesendet';
$a->strings['Fetched'] = 'Abgerufen';
$a->strings['Fetched because of %s <%s>'] = 'Wegen %s <%s> abgerufen';
$a->strings['Stored because of a child post to complete this thread.'] = 'Gespeichert wegen eines untergeordneten Beitrags zur Vervollständigung dieses Themas.';
$a->strings['Local delivery'] = 'Lokale Zustellung';
$a->strings['Stored because of your activity (like, comment, star, ...)'] = 'Gespeichert aufgrund Ihrer Aktivität (Like, Kommentar, Stern, ...)';
$a->strings['Distributed'] = 'Verteilt';
$a->strings['Pushed to us'] = 'Zu uns gepusht';
$a->strings['Pinned item'] = 'Angehefteter Beitrag';
$a->strings['View %s\'s profile @ %s'] = 'Das Profil von %s auf %s betrachten.';
$a->strings['Categories:'] = 'Kategorien:';
$a->strings['Filed under:'] = 'Abgelegt unter:';
$a->strings['%s from %s'] = '%s von %s';
$a->strings['View in context'] = 'Im Zusammenhang betrachten';
$a->strings['For you'] = 'Für Dich';
$a->strings['Posts from contacts you interact with and who interact with you'] = 'Beiträge von Kontakten, mit denen du interagierst und die mit dir interagieren';
$a->strings['Discover'] = 'Entdecken';
$a->strings['Posts from accounts that you don\'t follow, but that you might like.'] = 'Beiträge von Konten, denen du nicht folgst, aber die dir gefallen könnten.';
$a->strings['What\'s Hot'] = 'Angesagt';
$a->strings['Posts with a lot of interactions'] = 'Beiträge mit vielen Interaktionen';
$a->strings['Posts in %s'] = 'Beiträge in %s';
$a->strings['Posts from your followers that you don\'t follow'] = 'Beiträge von deinen Followern, denen du nicht folgst';
$a->strings['Sharers of sharers'] = 'Geteilt von teilenden ';
$a->strings['Posts from accounts that are followed by accounts that you follow'] = 'Beiträge von Accounts, welche von von Accounts gefolgt werden, denen du folgst ';
$a->strings['Quiet sharers'] = 'Ruhige teilende';
$a->strings['Posts from accounts that you follow but who don\'t post very often'] = 'Beiträge von Konten, denen du folgst, die jedoch nicht sehr oft posten.';
$a->strings['Images'] = 'Bilder';
$a->strings['Posts with images'] = 'Beiträge mit Bildern';
$a->strings['Audio'] = 'Audio';
$a->strings['Posts with audio'] = 'Beiträge mit Audio';
$a->strings['Videos'] = 'Videos';
$a->strings['Posts with videos'] = 'Beiträge mit Videos';
$a->strings['Local Community'] = 'Lokale Gemeinschaft';
$a->strings['Posts from local users on this server'] = 'Beiträge von Nutzern dieses Servers';
$a->strings['Global Community'] = 'Globale Gemeinschaft';
$a->strings['Posts from users of the whole federated network'] = 'Beiträge von Nutzern des gesamten  föderalen Netzwerks';
$a->strings['Latest Activity'] = 'Neu - Aktivität';
$a->strings['Sort by latest activity'] = 'Sortiere nach neueste Aktivität';
$a->strings['Latest Posts'] = 'Neu - Empfangen';
$a->strings['Sort by post received date'] = 'Nach Empfangsdatum der Beiträge sortiert';
$a->strings['Latest Creation'] = 'Neu - Erstellung';
$a->strings['Sort by post creation date'] = 'Sortiert nach dem Erstellungsdatum';
$a->strings['Personal'] = 'Persönlich';
$a->strings['Posts that mention or involve you'] = 'Beiträge, in denen es um dich geht';
$a->strings['Starred'] = 'Markierte';
$a->strings['Favourite Posts'] = 'Favorisierte Beiträge';
$a->strings['General Features'] = 'Allgemeine Features';
$a->strings['Photo Location'] = 'Aufnahmeort';
$a->strings['Photo metadata is normally stripped. This extracts the location (if present) prior to stripping metadata and links it to a map.'] = 'Die Foto-Metadaten werden ausgelesen. Dadurch kann der Aufnahmeort (wenn vorhanden) in einer Karte angezeigt werden.';
$a->strings['Display the community in the navigation'] = 'Gemeinschaftsseiten in Navigation anzeigen';
$a->strings['If enabled, the community can be accessed via the navigation menu. Independent from this setting, the community timelines can always be accessed via the channels.'] = 'Soll die Gemeinschaftsseite im Navigationsmenü verlinkt sein? Unabhängig von dieser Einstellung kann immer über Kanäle auf die Gemeinschaftszeitleiste zugegriffen werden.';
$a->strings['Post Composition Features'] = 'Beitragserstellung-Features';
$a->strings['Explicit Mentions'] = 'Explizite Erwähnungen';
$a->strings['Add explicit mentions to comment box for manual control over who gets mentioned in replies.'] = 'Füge Erwähnungen zum Kommentarfeld hinzu, um manuell über die explizite Erwähnung von Gesprächsteilnehmern zu entscheiden.';
$a->strings['Add an abstract from ActivityPub content warnings'] = 'Abstract aus Inhaltswarnungen von ActivityPub zu Beiträgen hinzufügen';
$a->strings['Add an abstract when commenting on ActivityPub posts with a content warning. Abstracts are displayed as content warning on systems like Mastodon or Pleroma.'] = 'Wenn ActivityPub Beiträge kommentiert werden, die mit einer Inhaltswarnung versehen sind, wird mit dieser Option automatisch ein identischer Abstract angefügt. Systeme wie Mastodon oder Pleroma verwenden diesen als Inhaltswarnung.';
$a->strings['Post/Comment Tools'] = 'Werkzeuge für Beiträge und Kommentare';
$a->strings['Post Categories'] = 'Beitragskategorien';
$a->strings['Add categories to your posts'] = 'Eigene Beiträge mit Kategorien versehen';
$a->strings['Network Widgets'] = 'Netzwerk Widges';
$a->strings['Circles'] = 'Circles';
$a->strings['Display posts that have been created by accounts of the selected circle.'] = 'Beiträge anzeigen, die von Konten des ausgewählten Circles erstellt wurden.';
$a->strings['Groups'] = 'Gruppen';
$a->strings['Display posts that have been distributed by the selected group.'] = 'Anzeige der Beiträge, die von der ausgewählten Gruppe verteilt wurden.';
$a->strings['Archives'] = 'Archiv';
$a->strings['Display an archive where posts can be selected by month and year.'] = 'Anzeige eines Archivs, in dem die Beiträge nach Monat und Jahr ausgewählt werden können.';
$a->strings['Protocols'] = 'Protokolle';
$a->strings['Display posts with the selected protocols.'] = 'Beiträge mit den ausgewählten Protokollen anzeigen.';
$a->strings['Account Types'] = 'Kontenarten';
$a->strings['Display posts done by accounts with the selected account type.'] = 'Anzeige der Beiträge, die von Konten mit dem ausgewählten Kontotyp erstellt wurden.';
$a->strings['Channels'] = 'Kanäle';
$a->strings['Display posts in the system channels and user defined channels.'] = 'Anzeige von Beiträgen in den Systemkanälen und benutzerdefinierten Kanälen.';
$a->strings['Saved Searches'] = 'Gespeicherte Suchen';
$a->strings['Display posts that contain subscribed hashtags.'] = 'Weitergeleitete Beiträge anzeigen, wenn abonnierte Hashtags enthalten sind.';
$a->strings['Saved Folders'] = 'Gespeicherte Ordner';
$a->strings['Display a list of folders in which posts are stored.'] = 'Ordnerliste anzeigen in denen Beiträge gespeichert sind.';
$a->strings['Own Contacts'] = 'Eigene Kontakte';
$a->strings['Include or exclude posts from subscribed accounts. This widget is not visible on all channels.'] = 'Beiträge von abonnierten Konten einschließen oder ausschließen. Dieses Widget ist nicht auf allen Kanälen sichtbar.';
$a->strings['Trending Tags'] = 'Trending Tags';
$a->strings['Display a list of the most popular tags in recent public posts.'] = 'Zeigt eine Liste der beliebtesten Tags in den letzten öffentlichen Beiträgen an.';
$a->strings['Advanced Profile Settings'] = 'Erweiterte Profil-Einstellungen';
$a->strings['Tag Cloud'] = 'Schlagwortwolke';
$a->strings['Provide a personal tag cloud on your profile page'] = 'Wortwolke aus den von dir verwendeten Schlagwörtern im Profil anzeigen';
$a->strings['Display Membership Date'] = 'Mitgliedschaftsdatum anzeigen';
$a->strings['Display membership date in profile'] = 'Das Datum der Registrierung deines Accounts im Profil anzeigen';
$a->strings['Advanced Calendar Settings'] = 'Erweiterte Kalender Einstellungen';
$a->strings['Allow anonymous access to your calendar'] = 'Erlaube anonymen Zugriff auf deinen Kalender';
$a->strings['Allows anonymous visitors to consult your calendar and your public events. Contact birthday events are private to you.'] = 'Anonyme Besucher können deinen Kalender öffnen und dort deine öffentliche Ereignisse einsehen. Geburtstage deiner Kontakte sind nicht öffentlich.';
$a->strings['External link to group'] = 'Externer Link zur Gruppe';
$a->strings['show less'] = 'weniger anzeigen';
$a->strings['show more'] = 'mehr anzeigen';
$a->strings['Create new group'] = 'Neue Gruppe erstellen';
$a->strings['event'] = 'Veranstaltung';
$a->strings['status'] = 'Status';
$a->strings['photo'] = 'Foto';
$a->strings['%1$s tagged %2$s\'s %3$s with %4$s'] = '%1$s hat %2$ss %3$s mit %4$s getaggt';
$a->strings['Follow Thread'] = 'Folge der Unterhaltung';
$a->strings['View Status'] = 'Status anschauen';
$a->strings['View Profile'] = 'Profil anschauen';
$a->strings['View Photos'] = 'Bilder anschauen';
$a->strings['Network Posts'] = 'Netzwerkbeiträge';
$a->strings['View Contact'] = 'Kontakt anzeigen';
$a->strings['Send PM'] = 'Private Nachricht senden';
$a->strings['Block'] = 'Sperren';
$a->strings['Ignore'] = 'Ignorieren';
$a->strings['Collapse'] = 'Zuklappen';
$a->strings['Ignore %s server'] = 'Ignoriere %s Server';
$a->strings['Languages'] = 'Sprachen';
$a->strings['Search Text'] = 'Suchtext';
$a->strings['Connect/Follow'] = 'Verbinden/Folgen';
$a->strings['Unable to fetch user.'] = 'Benutzer kann nicht abgerufen werden.';
$a->strings['Nothing new here'] = 'Keine Neuigkeiten';
$a->strings['Home'] = 'Pinnwand';
$a->strings['Skip to main content'] = 'Zum Inhalt der Seite gehen';
$a->strings['Clear notifications'] = 'Bereinige Benachrichtigungen';
$a->strings['@name, !group, #tags, content'] = '@name, !gruppe, #tags, content';
$a->strings['Logout'] = 'Abmelden';
$a->strings['End this session'] = 'Diese Sitzung beenden';
$a->strings['Login'] = 'Anmeldung';
$a->strings['Sign in'] = 'Anmelden';
$a->strings['Conversations'] = 'Unterhaltungen';
$a->strings['Conversations you started'] = 'Unterhaltungen die du begonnen hast';
$a->strings['Profile'] = 'Profil';
$a->strings['Your profile page'] = 'Deine Profilseite';
$a->strings['Photos'] = 'Bilder';
$a->strings['Your photos'] = 'Deine Fotos';
$a->strings['Media'] = 'Medien';
$a->strings['Your postings with media'] = 'Deine Beiträge die Medien beinhalten';
$a->strings['Calendar'] = 'Kalender';
$a->strings['Your calendar'] = 'Dein Kalender';
$a->strings['Personal notes'] = 'Persönliche Notizen';
$a->strings['Your personal notes'] = 'Deine persönlichen Notizen';
$a->strings['Home Page'] = 'Homepage';
$a->strings['Register'] = 'Registrieren';
$a->strings['Create an account'] = 'Nutzerkonto erstellen';
$a->strings['Help'] = 'Hilfe';
$a->strings['Help and documentation'] = 'Hilfe und Dokumentation';
$a->strings['Apps'] = 'Apps';
$a->strings['Addon applications, utilities, games'] = 'Zusätzliche Anwendungen, Dienstprogramme, Spiele';
$a->strings['Search'] = 'Suche';
$a->strings['Search site content'] = 'Inhalt der Seite durchsuchen';
$a->strings['Full Text'] = 'Volltext';
$a->strings['Tags'] = 'Tags';
$a->strings['Contacts'] = 'Kontakte';
$a->strings['Community'] = 'Gemeinschaft';
$a->strings['Conversations on this and other servers'] = 'Unterhaltungen auf diesem und anderen Servern';
$a->strings['Directory'] = 'Verzeichnis';
$a->strings['People directory'] = 'Nutzerverzeichnis';
$a->strings['Information'] = 'Information';
$a->strings['Information about this friendica instance'] = 'Informationen zu dieser Friendica-Instanz';
$a->strings['Terms of Service'] = 'Nutzungsbedingungen';
$a->strings['Terms of Service of this Friendica instance'] = 'Die Nutzungsbedingungen dieser Friendica-Instanz';
$a->strings['Network'] = 'Netzwerk';
$a->strings['Conversations from your friends'] = 'Unterhaltungen Deiner Kontakte';
$a->strings['Your posts and conversations'] = 'Deine Beiträge und Unterhaltungen';
$a->strings['Introductions'] = 'Kontaktanfragen';
$a->strings['Friend Requests'] = 'Kontaktanfragen';
$a->strings['Notifications'] = 'Benachrichtigungen';
$a->strings['See all notifications'] = 'Alle Benachrichtigungen anzeigen';
$a->strings['Mark as seen'] = 'Als gelesen markieren';
$a->strings['Mark all system notifications as seen'] = 'Markiere alle Systembenachrichtigungen als gelesen';
$a->strings['Private mail'] = 'Private E-Mail';
$a->strings['Inbox'] = 'Eingang';
$a->strings['Outbox'] = 'Ausgang';
$a->strings['Accounts'] = 'Nutzerkonten';
$a->strings['Manage other pages'] = 'Andere Seiten verwalten';
$a->strings['Settings'] = 'Einstellungen';
$a->strings['Account settings'] = 'Kontoeinstellungen';
$a->strings['Manage/edit friends and contacts'] = 'Freunde und Kontakte verwalten/bearbeiten';
$a->strings['Admin'] = 'Administration';
$a->strings['Site setup and configuration'] = 'Einstellungen der Seite und Konfiguration';
$a->strings['Moderation'] = 'Moderation';
$a->strings['Content and user moderation'] = 'Moderation von Nutzern und Inhalten';
$a->strings['Navigation'] = 'Navigation';
$a->strings['Site map'] = 'Sitemap';
$a->strings['first'] = 'erste';
$a->strings['prev'] = 'vorige';
$a->strings['next'] = 'nächste';
$a->strings['last'] = 'letzte';
$a->strings['<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> %3$s'] = '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>%3$s';
$a->strings['Link to source'] = 'Link zum Originalbeitrag';
$a->strings['Click to open/close'] = 'Zum Öffnen/Schließen klicken';
$a->strings['$1 wrote:'] = '$1 hat geschrieben:';
$a->strings['Encrypted content'] = 'Verschlüsselter Inhalt';
$a->strings['Invalid source protocol'] = 'Ungültiges Quell-Protokoll';
$a->strings['Invalid link protocol'] = 'Ungültiges Link-Protokoll';
$a->strings['Loading more entries...'] = 'lade weitere Einträge...';
$a->strings['The end'] = 'Das Ende';
$a->strings['Follow'] = 'Folge';
$a->strings['Add New Contact'] = 'Neuen Kontakt hinzufügen';
$a->strings['Enter address or web location'] = 'Adresse oder Web-Link eingeben';
$a->strings['Example: bob@example.com, http://example.com/barbara'] = 'Beispiel: bob@example.com, http://example.com/barbara';
$a->strings['Connect'] = 'Verbinden';
$a->strings['%d invitation available'] = [
	0 => '%d Einladung verfügbar',
	1 => '%d Einladungen verfügbar',
];
$a->strings['Find People'] = 'Leute finden';
$a->strings['Enter name or interest'] = 'Name oder Interessen eingeben';
$a->strings['Examples: Robert Morgenstein, Fishing'] = 'Beispiel: Robert Morgenstein, Angeln';
$a->strings['Find'] = 'Finde';
$a->strings['Friend Suggestions'] = 'Kontaktvorschläge';
$a->strings['Similar Interests'] = 'Ähnliche Interessen';
$a->strings['Random Profile'] = 'Zufälliges Profil';
$a->strings['Invite Friends'] = 'Freunde einladen';
$a->strings['Global Directory'] = 'Weltweites Verzeichnis';
$a->strings['Local Directory'] = 'Lokales Verzeichnis';
$a->strings['Everyone'] = 'Jeder';
$a->strings['No relationship'] = 'Keine Beziehung';
$a->strings['Relationships'] = 'Beziehungen';
$a->strings['All Contacts'] = 'Alle Kontakte';
$a->strings['All Protocols'] = 'Alle Protokolle';
$a->strings['Everything'] = 'Alles';
$a->strings['Categories'] = 'Kategorien';
$a->strings['%d contact in common'] = [
	0 => '%d gemeinsamer Kontakt',
	1 => '%d gemeinsame Kontakte',
];
$a->strings['On this date'] = 'An diesem Datum';
$a->strings['Persons'] = 'Personen';
$a->strings['Organisations'] = 'Organisationen';
$a->strings['News'] = 'Nachrichten';
$a->strings['Relays'] = 'Relais';
$a->strings['All'] = 'Alle';
$a->strings['Export'] = 'Exportieren';
$a->strings['Export calendar as ical'] = 'Kalender als ical exportieren';
$a->strings['Export calendar as csv'] = 'Kalender als csv exportieren';
$a->strings['No contacts'] = 'Keine Kontakte';
$a->strings['%d Contact'] = [
	0 => '%d Kontakt',
	1 => '%d Kontakte',
];
$a->strings['View Contacts'] = 'Kontakte anzeigen';
$a->strings['Remove term'] = 'Begriff entfernen';
$a->strings['Trending Tags (last %d hour)'] = [
	0 => 'Trending Tags (%d Stunde)',
	1 => 'Trending Tags (%d Stunden)',
];
$a->strings['More Trending Tags'] = 'mehr Trending Tags';
$a->strings['Post to group'] = 'Beitrag an Gruppe';
$a->strings['Mention'] = 'Mention';
$a->strings['XMPP:'] = 'XMPP:';
$a->strings['Matrix:'] = 'Matrix:';
$a->strings['Location:'] = 'Ort:';
$a->strings['Network:'] = 'Netzwerk:';
$a->strings['Unfollow'] = 'Entfolgen';
$a->strings['View group'] = 'Gruppe betrachten';
$a->strings['Yourself'] = 'Du selbst';
$a->strings['Mutuals'] = 'Beidseitige Freundschaft';
$a->strings['Post to Email'] = 'An E-Mail senden';
$a->strings['Public'] = 'Öffentlich';
$a->strings['This content will be shown to all your followers and can be seen in the community pages and by anyone with its link.'] = 'Dieser Inhalt wird all deine Abonenten sowie auf der Gemeinschaftsseite angezeigt. Außerdem kann ihn jeder sehen, der den Link kennt.';
$a->strings['Limited/Private'] = 'Begrenzt/Privat';
$a->strings['This content will be shown only to the people in the first box, to the exception of the people mentioned in the second box. It won\'t appear anywhere public.'] = 'Dieser Inhalt wird außschließlich den Kontakten gezeigt, die du in der ersten Box ausgewählt hast, mit den Ausnahmen derer die du in der zweiten Box auflistest. Er wird nicht öffentlich zugänglich sein.';
$a->strings['Start typing the name of a contact or a circle to show a filtered list. You can also mention the special circles "Followers" and "Mutuals".'] = 'Gebe den Namen eines Kontakts oder eines Circles ein, um eine gefilterte Liste anzuzeigen. Du kannst auch die speziellen Kreise "Folgende" und "beidseitige Freundschaft" erwähnen.';
$a->strings['Show to:'] = 'Sichtbar für:';
$a->strings['Except to:'] = 'Ausgenommen:';
$a->strings['CC: email addresses'] = 'Cc: E-Mail-Addressen';
$a->strings['Example: bob@example.com, mary@example.com'] = 'Z.B.: bob@example.com, mary@example.com';
$a->strings['Connectors'] = 'Connectoren';
$a->strings['The database configuration file "config/local.config.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.'] = 'Die Datenbankkonfigurationsdatei "config/local.config.php" konnte nicht erstellt werden. Um eine Konfigurationsdatei in Ihrem Webserver-Verzeichnis zu erstellen, gehe wie folgt vor.';
$a->strings['You may need to import the file "database.sql" manually using phpmyadmin or mysql.'] = 'Möglicherweise musst du die Datei "database.sql" manuell mit phpmyadmin oder mysql importieren.';
$a->strings['Please see the file "doc/INSTALL.md".'] = 'Lies bitte die "doc/INSTALL.md".';
$a->strings['Could not find a command line version of PHP in the web server PATH.'] = 'Konnte keine Kommandozeilenversion von PHP im PATH des Servers finden.';
$a->strings['If you don\'t have a command line version of PHP installed on your server, you will not be able to run the background processing. See <a href=\'https://github.com/friendica/friendica/blob/stable/doc/Install.md#set-up-the-worker\'>\'Setup the worker\'</a>'] = 'Wenn auf deinem Server keine Kommandozeilenversion von PHP installiert ist, kannst du den Hintergrundprozess nicht einrichten. Hier findest du alternative Möglichkeiten<a href=\'https://github.com/friendica/friendica/blob/stable/doc/Install.md#set-up-the-worker\'>\'für das Worker-Setup\'</a>';
$a->strings['PHP executable path'] = 'Pfad zu PHP';
$a->strings['Enter full path to php executable. You can leave this blank to continue the installation.'] = 'Gib den kompletten Pfad zur ausführbaren Datei von PHP an. Du kannst dieses Feld auch frei lassen und mit der Installation fortfahren.';
$a->strings['Command line PHP'] = 'Kommandozeilen-PHP';
$a->strings['PHP executable is not the php cli binary (could be cgi-fgci version)'] = 'Die ausführbare Datei von PHP stimmt nicht mit der PHP cli Version überein (es könnte sich um die cgi-fgci Version handeln)';
$a->strings['Found PHP version: '] = 'Gefundene PHP Version:';
$a->strings['PHP cli binary'] = 'PHP CLI Binary';
$a->strings['The command line version of PHP on your system does not have "register_argc_argv" enabled.'] = 'Die Kommandozeilenversion von PHP auf Deinem System hat "register_argc_argv" nicht aktiviert.';
$a->strings['This is required for message delivery to work.'] = 'Dies wird für die Auslieferung von Nachrichten benötigt.';
$a->strings['PHP register_argc_argv'] = 'PHP register_argc_argv';
$a->strings['Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys'] = 'Fehler: Die Funktion "openssl_pkey_new" auf diesem System ist nicht in der Lage, Verschlüsselungsschlüssel zu erzeugen';
$a->strings['If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".'] = 'Wenn der Server unter Windows läuft, schau dir bitte "http://www.php.net/manual/en/openssl.installation.php" an.';
$a->strings['Generate encryption keys'] = 'Schlüssel erzeugen';
$a->strings['Error: Apache webserver mod-rewrite module is required but not installed.'] = 'Fehler: Das Apache-Modul mod-rewrite wird benötigt, es ist allerdings nicht installiert.';
$a->strings['Apache mod_rewrite module'] = 'Apache mod_rewrite module';
$a->strings['Error: PDO or MySQLi PHP module required but not installed.'] = 'Fehler: PDO oder MySQLi PHP Modul erforderlich, aber nicht installiert.';
$a->strings['Error: The MySQL driver for PDO is not installed.'] = 'Fehler: der MySQL Treiber für PDO ist nicht installiert';
$a->strings['PDO or MySQLi PHP module'] = 'PDO oder MySQLi PHP Modul';
$a->strings['Error: The IntlChar module is not installed.'] = 'Fehler: Das IntlChar-Modul von PHP ist nicht installiert.';
$a->strings['IntlChar PHP module'] = 'PHP: IntlChar-Modul';
$a->strings['Error, XML PHP module required but not installed.'] = 'Fehler: XML PHP Modul erforderlich aber nicht installiert.';
$a->strings['XML PHP module'] = 'XML PHP Modul';
$a->strings['libCurl PHP module'] = 'PHP: libCurl-Modul';
$a->strings['Error: libCURL PHP module required but not installed.'] = 'Fehler: Das libCURL PHP Modul wird benötigt, ist aber nicht installiert.';
$a->strings['GD graphics PHP module'] = 'PHP: GD-Grafikmodul';
$a->strings['Error: GD graphics PHP module with JPEG support required but not installed.'] = 'Fehler: Das GD-Graphikmodul für PHP mit JPEG-Unterstützung ist nicht installiert.';
$a->strings['OpenSSL PHP module'] = 'PHP: OpenSSL-Modul';
$a->strings['Error: openssl PHP module required but not installed.'] = 'Fehler: Das openssl-Modul von PHP ist nicht installiert.';
$a->strings['mb_string PHP module'] = 'PHP: mb_string-Modul';
$a->strings['Error: mb_string PHP module required but not installed.'] = 'Fehler: mb_string PHP Module wird benötigt, ist aber nicht installiert.';
$a->strings['iconv PHP module'] = 'PHP iconv Modul';
$a->strings['Error: iconv PHP module required but not installed.'] = 'Fehler: Das iconv-Modul von PHP ist nicht installiert.';
$a->strings['POSIX PHP module'] = 'PHP POSIX Modul';
$a->strings['Error: POSIX PHP module required but not installed.'] = 'Fehler POSIX PHP Modul erforderlich, aber nicht installiert.';
$a->strings['Program execution functions'] = 'Funktionen zur Programmausführung';
$a->strings['Error: Program execution functions (proc_open) required but not enabled.'] = 'Fehler: Die Funktionen zur Ausführung von Programmen (proc_open) müssen aktiviert sein.';
$a->strings['JSON PHP module'] = 'PHP JSON Modul';
$a->strings['Error: JSON PHP module required but not installed.'] = 'Fehler: Das JSON PHP Modul wird benötigt, ist aber nicht installiert.';
$a->strings['File Information PHP module'] = 'PHP Datei Informations-Modul';
$a->strings['Error: File Information PHP module required but not installed.'] = 'Fehler: Das Datei Informations PHP Modul ist nicht installiert.';
$a->strings['GNU Multiple Precision PHP module'] = 'GNU Multiple Precision PHP Modul';
$a->strings['Error: GNU Multiple Precision PHP module required but not installed.'] = 'Fehler: GNU Multiple Precision PHP Modul wird benötigt, ist aber nicht installiert.';
$a->strings['IDN Functions PHP module'] = 'PHP Modul: IDN Functions';
$a->strings['Error: IDN Functions PHP module required but not installed.'] = 'Fehler: Das PHP Modul IDN Functions wird benötigt ist aber nicht installiert.';
$a->strings['The web installer needs to be able to create a file called "local.config.php" in the "config" folder of your web server and it is unable to do so.'] = 'Das Installationsprogramm muss in der Lage sein, eine Datei namens "local.config.php" im Ordner "config" Ihres Webservers zu erstellen, ist aber nicht in der Lage dazu.';
$a->strings['This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.'] = 'In den meisten Fällen ist dies ein Problem mit den Schreibrechten. Der Webserver könnte keine Schreiberlaubnis haben, selbst wenn du sie hast.';
$a->strings['At the end of this procedure, we will give you a text to save in a file named local.config.php in your Friendica "config" folder.'] = 'Am Ende dieser Prozedur bekommst du einen Text, der in der local.config.php im Friendica "config" Ordner gespeichert werden muss.';
$a->strings['You can alternatively skip this procedure and perform a manual installation. Please see the file "doc/INSTALL.md" for instructions.'] = 'Alternativ kannst du diesen Schritt aber auch überspringen und die Installation manuell durchführen. Eine Anleitung dazu (Englisch) findest du in der Datei "doc/INSTALL.md".';
$a->strings['config/local.config.php is writable'] = 'config/local.config.php ist schreibbar';
$a->strings['Friendica uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.'] = 'Friendica nutzt die Smarty3-Template-Engine, um die Webansichten zu rendern. Smarty3 kompiliert Templates zu PHP, um das Rendern zu beschleunigen.';
$a->strings['In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top level folder.'] = 'Um diese kompilierten Templates zu speichern, benötigt der Webserver Schreibrechte zum Verzeichnis view/smarty3/ im obersten Ordner von Friendica.';
$a->strings['Please ensure that the user that your web server runs as (e.g. www-data) has write access to this folder.'] = 'Bitte stelle sicher, dass der Nutzer, unter dem der Webserver läuft (z.B. www-data), Schreibrechte zu diesem Verzeichnis hat.';
$a->strings['Note: as a security measure, you should give the web server write access to view/smarty3/ only--not the template files (.tpl) that it contains.'] = 'Hinweis: aus Sicherheitsgründen solltest du dem Webserver nur Schreibrechte für view/smarty3/ geben -- Nicht für die darin enthaltenen Template-Dateien (.tpl).';
$a->strings['view/smarty3 is writable'] = 'view/smarty3 ist schreibbar';
$a->strings['Url rewrite in .htaccess seems not working. Make sure you copied .htaccess-dist to .htaccess.'] = 'Umschreiben der URLs in der .htaccess funktioniert nicht. Vergewissere dich, dass du .htaccess-dist nach.htaccess kopiert hast.';
$a->strings['In some circumstances (like running inside containers), you can skip this error.'] = 'Unter bestimmten Umständen (z.B. Installationen in Containern) kannst du diesen Fehler übergehen.';
$a->strings['Error message from Curl when fetching'] = 'Fehlermeldung von Curl während des Ladens';
$a->strings['Url rewrite is working'] = 'URL rewrite funktioniert';
$a->strings['The detection of TLS to secure the communication between the browser and the new Friendica server failed.'] = 'Die Erkennung von TLS, um die Kommunikation zwischen dem Browser und dem neuen Friendica-Server zu sichern, scheiterte.';
$a->strings['It is highly encouraged to use Friendica only over a secure connection as sensitive information like passwords will be transmitted.'] = 'Friendica sollte nur über eine sichere Verbindung verwendet werden da sensible Informationen wie Passwörter übertragen werden.';
$a->strings['Please ensure that the connection to the server is secure.'] = 'Bitte vergewissere dich, dass die Verbindung zum Server sicher ist.';
$a->strings['No TLS detected'] = 'Kein TLS gefunden';
$a->strings['TLS detected'] = 'TLS gefunden';
$a->strings['ImageMagick PHP extension is not installed'] = 'ImageMagicx PHP Erweiterung ist nicht installiert.';
$a->strings['ImageMagick PHP extension is installed'] = 'ImageMagick PHP Erweiterung ist installiert';
$a->strings['Database already in use.'] = 'Die Datenbank wird bereits verwendet.';
$a->strings['Could not connect to database.'] = 'Verbindung zur Datenbank gescheitert.';
$a->strings['Undetermined'] = 'Unspezifiziert';
$a->strings['%s (%s)'] = '%s (%s)';
$a->strings['Monday'] = 'Montag';
$a->strings['Tuesday'] = 'Dienstag';
$a->strings['Wednesday'] = 'Mittwoch';
$a->strings['Thursday'] = 'Donnerstag';
$a->strings['Friday'] = 'Freitag';
$a->strings['Saturday'] = 'Samstag';
$a->strings['Sunday'] = 'Sonntag';
$a->strings['January'] = 'Januar';
$a->strings['February'] = 'Februar';
$a->strings['March'] = 'März';
$a->strings['April'] = 'April';
$a->strings['May'] = 'Mai';
$a->strings['June'] = 'Juni';
$a->strings['July'] = 'Juli';
$a->strings['August'] = 'August';
$a->strings['September'] = 'September';
$a->strings['October'] = 'Oktober';
$a->strings['November'] = 'November';
$a->strings['December'] = 'Dezember';
$a->strings['Mon'] = 'Mo';
$a->strings['Tue'] = 'Di';
$a->strings['Wed'] = 'Mi';
$a->strings['Thu'] = 'Do';
$a->strings['Fri'] = 'Fr';
$a->strings['Sat'] = 'Sa';
$a->strings['Sun'] = 'So';
$a->strings['Jan'] = 'Jan';
$a->strings['Feb'] = 'Feb';
$a->strings['Mar'] = 'März';
$a->strings['Apr'] = 'Apr';
$a->strings['Jun'] = 'Jun';
$a->strings['Jul'] = 'Juli';
$a->strings['Aug'] = 'Aug';
$a->strings['Sep'] = 'Sep';
$a->strings['Oct'] = 'Okt';
$a->strings['Nov'] = 'Nov';
$a->strings['Dec'] = 'Dez';
$a->strings['The logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'Die Logdatei \'%s\' ist nicht beschreibbar. Derzeit ist keine Aufzeichnung möglich (Fehler: \'%s\')';
$a->strings['The debug logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'Die Logdatei \'%s\' ist nicht beschreibbar. Derzeit ist keine Aufzeichnung möglich (Fehler: \'%s\')';
$a->strings['Friendica can\'t display this page at the moment, please contact the administrator.'] = 'Friendica kann die Seite im Moment nicht darstellen. Bitte kontaktiere das Administratoren Team.';
$a->strings['template engine cannot be registered without a name.'] = 'Die Template Engine kann nicht ohne einen Namen registriert werden.';
$a->strings['template engine is not registered!'] = 'Template Engine wurde nicht registriert!';
$a->strings['Storage base path'] = 'Dateipfad zum Speicher';
$a->strings['Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree'] = 'Verzeichnis, in das Dateien hochgeladen werden. Für maximale Sicherheit sollte dies ein Pfad außerhalb der Webserver-Verzeichnisstruktur sein';
$a->strings['Enter a valid existing folder'] = 'Gib einen gültigen, existierenden Ordner ein';
$a->strings['Updates from version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.'] = 'Aktualisierungen von der Version %s werden nicht unterstützt. Bitte aktualisiere vorher auf die Version 2021.01 von Friendica und warte bis das Postupdate auf die Version 1383 abgeschlossen ist.';
$a->strings['Updates from postupdate version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.'] = 'Aktualisierungen von der Postupdate Version %s werden nicht unterstützt. Bitte aktualisiere zunächst auf die Friendica Version 2021.01 und warte bis das Postupdate 1383 abgeschlossen ist.';
$a->strings['%s: executing pre update %d'] = '%s: Pre-Update %d wird ausgeführt';
$a->strings['%s: executing post update %d'] = '%s: Post-Update %d wird ausgeführt';
$a->strings['Update %s failed. See error logs.'] = 'Update %s fehlgeschlagen. Bitte Fehlerprotokoll überprüfen.';
$a->strings['
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can\'t do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.'] = '
Die Friendica-Entwickler haben vor kurzem das Update %s veröffentlicht, aber bei der Installation ging etwas schrecklich schief.

Das Problem sollte so schnell wie möglich gelöst werden, aber ich schaffe es nicht alleine. Bitte kontaktiere einen Friendica-Entwickler, falls du mir nicht alleine helfen kannst. Meine Datenbank könnte ungültig sein.';
$a->strings['The error message is\n[pre]%s[/pre]'] = 'Die Fehlermeldung lautet [pre]%s[/pre]';
$a->strings['[Friendica Notify] Database update'] = '[Friendica-Benachrichtigung]: Datenbank Update';
$a->strings['
				The friendica database was successfully updated from %s to %s.'] = '
				Die Friendica Datenbank wurde erfolgreich von %s auf %s aktualisiert.';
$a->strings['The database version had been set to %s.'] = 'Die Datenbank Version wurde auf %s gesetzt.';
$a->strings['The post update is at version %d, it has to be at %d to safely drop the tables.'] = 'Das post-update ist auf der Version %d. Damit die Tabellen sicher entfernt werden können muss es die Version %d haben.';
$a->strings['No unused tables found.'] = 'Keine Tabellen gefunden die nicht verwendet werden.';
$a->strings['These tables are not used for friendica and will be deleted when you execute "dbstructure drop -e":'] = 'Diese Tabellen werden nicht von Friendica verwendet. Sie werden gelöscht, wenn du "dbstructure drop -e" ausführst.';
$a->strings['There are no tables on MyISAM or InnoDB with the Antelope file format.'] = 'Es gibt keine MyISAM oder InnoDB Tabellem mit dem Antelope Dateiformat.';
$a->strings['
Error %d occurred during database update:
%s
'] = '
Fehler %d beim Update der Datenbank aufgetreten
%s
';
$a->strings['Errors encountered performing database changes: '] = 'Fehler beim Ändern der Datenbank aufgetreten';
$a->strings['Another database update is currently running.'] = 'Es läuft bereits ein anderes Datenbank Update';
$a->strings['%s: Database update'] = '%s: Datenbank Aktualisierung';
$a->strings['%s: updating %s table.'] = '%s: aktualisiere Tabelle %s';
$a->strings['Record not found'] = 'Eintrag nicht gefunden';
$a->strings['Unprocessable Entity'] = 'Entität konnte nicht verarbeitet werden';
$a->strings['Unauthorized'] = 'Nicht autorisiert';
$a->strings['Token is not authorized with a valid user or is missing a required scope'] = 'Token ist nicht durch einen gültigen Benutzer autorisiert oder es fehlt ein erforderlicher Geltungsbereich';
$a->strings['Internal Server Error'] = 'Interner Serverfehler';
$a->strings['Legacy module file not found: %s'] = 'Legacy-Moduldatei nicht gefunden: %s';
$a->strings['A deleted circle with this name was revived. Existing item permissions <strong>may</strong> apply to this circle and any future members. If this is not what you intended, please create another circle with a different name.'] = 'Ein gelöschter Circle mit diesem Namen wurde wiederhergestellt. Bestehende Objektberechtigungen <strong>können</strong> für diesen Circle und alle zukünftigen Mitglieder gelten. Wenn dies nicht das ist, was du beabsichtigst, erstelle bitte einen neuen Circle mit einem anderen Namen.';
$a->strings['Everybody'] = 'Alle Kontakte';
$a->strings['edit'] = 'bearbeiten';
$a->strings['add'] = 'hinzufügen';
$a->strings['Edit circle'] = 'Circle ändern';
$a->strings['Contacts not in any circle'] = 'Kontakte, die keinem Circle zugeordnet sind';
$a->strings['Create a new circle'] = 'Erstelle neuen Circle';
$a->strings['Circle Name: '] = 'Circle Name: ';
$a->strings['Edit circles'] = 'Circles bearbeiten';
$a->strings['Approve'] = 'Genehmigen';
$a->strings['%s has blocked you'] = '%s hat dich blockiert';
$a->strings['Organisation'] = 'Organisation';
$a->strings['Group'] = 'Gruppe';
$a->strings['Relay'] = 'Relais';
$a->strings['Disallowed profile URL.'] = 'Nicht erlaubte Profil-URL.';
$a->strings['Blocked domain'] = 'Blockierte Domain';
$a->strings['Connect URL missing.'] = 'Connect-URL fehlt';
$a->strings['The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.'] = 'Der Kontakt konnte nicht hinzugefügt werden. Bitte überprüfe die Einstellungen unter Einstellungen -> Soziale Netzwerke';
$a->strings['Expected network %s does not match actual network %s'] = 'Erwartetes Netzwerk %s stimmt nicht mit dem tatsächlichen Netzwerk überein %s';
$a->strings['This seems to be a relay account. They can\'t be followed by users.'] = 'Dies scheint ein Relais-Konto zu sein. Diese können nicht von Nutzern gefolgt werden.';
$a->strings['The profile address specified does not provide adequate information.'] = 'Die angegebene Profiladresse liefert unzureichende Informationen.';
$a->strings['No compatible communication protocols or feeds were discovered.'] = 'Es wurden keine kompatiblen Kommunikationsprotokolle oder Feeds gefunden.';
$a->strings['An author or name was not found.'] = 'Es wurde kein Autor oder Name gefunden.';
$a->strings['No browser URL could be matched to this address.'] = 'Zu dieser Adresse konnte keine passende Browser-URL gefunden werden.';
$a->strings['Unable to match @-style Identity Address with a known protocol or email contact.'] = 'Konnte die @-Adresse mit keinem der bekannten Protokolle oder Email-Kontakte abgleichen.';
$a->strings['Use mailto: in front of address to force email check.'] = 'Verwende mailto: vor der E-Mail-Adresse, um eine Überprüfung der E-Mail-Adresse zu erzwingen.';
$a->strings['Limited profile. This person will be unable to receive direct/personal notifications from you.'] = 'Eingeschränktes Profil. Diese Person wird keine direkten/privaten Nachrichten von dir erhalten können.';
$a->strings['Unable to retrieve contact information.'] = 'Konnte die Kontaktinformationen nicht empfangen.';
$a->strings['l F d, Y \@ g:i A \G\M\TP (e)'] = 'l F d, Y \@ g:i A \G\M\TP (e)';
$a->strings['Starts:'] = 'Beginnt:';
$a->strings['Finishes:'] = 'Endet:';
$a->strings['all-day'] = 'ganztägig';
$a->strings['Sept'] = 'Sep';
$a->strings['today'] = 'Heute';
$a->strings['month'] = 'Monat';
$a->strings['week'] = 'Woche';
$a->strings['day'] = 'Tag';
$a->strings['No events to display'] = 'Keine Veranstaltung zum Anzeigen';
$a->strings['Access to this profile has been restricted.'] = 'Der Zugriff zu diesem Profil wurde eingeschränkt.';
$a->strings['Event not found.'] = 'Veranstaltung nicht gefunden.';
$a->strings['l, F j'] = 'l, F j';
$a->strings['Edit event'] = 'Veranstaltung bearbeiten';
$a->strings['Duplicate event'] = 'Veranstaltung kopieren';
$a->strings['Delete event'] = 'Veranstaltung löschen';
$a->strings['l F d, Y \@ g:i A'] = 'l, d. F Y\, H:i';
$a->strings['D g:i A'] = 'D H:i';
$a->strings['g:i A'] = 'H:i';
$a->strings['Show map'] = 'Karte anzeigen';
$a->strings['Hide map'] = 'Karte verbergen';
$a->strings['%s\'s birthday'] = '%ss Geburtstag';
$a->strings['Happy Birthday %s'] = 'Herzlichen Glückwunsch, %s';
$a->strings['%s (%s - %s): %s'] = '%s (%s - %s): %s';
$a->strings['%s (%s): %s'] = '%s (%s): %s';
$a->strings['Detected languages in this post:
%s'] = 'Erkannte Sprachen in diesem Beitrag:
%s';
$a->strings['activity'] = 'Aktivität';
$a->strings['comment'] = 'Kommentar';
$a->strings['post'] = 'Beitrag';
$a->strings['%s is blocked'] = '%s ist blockiert';
$a->strings['%s is ignored'] = '%s ist ignoriert';
$a->strings['Content from %s is collapsed'] = 'Inhalt vom %s ist zugeklappt';
$a->strings['Sensitive content'] = 'Sensitiver Inhalt';
$a->strings['bytes'] = 'Byte';
$a->strings['%2$s (%3$d%%, %1$d vote)'] = [
	0 => '%2$s (%3$d%%, %1$d Stimme)',
	1 => '%2$s (%3$d%%, %1$d Stimmen)',
];
$a->strings['%2$s (%1$d vote)'] = [
	0 => '%2$s (%1$d Stimme)',
	1 => '%2$s (%1$d Stimmen)',
];
$a->strings['%d voter. Poll end: %s'] = [
	0 => '%d Stimme, Abstimmung endet: %s',
	1 => '%d Stimmen, Abstimmung endet: %s',
];
$a->strings['%d voter.'] = [
	0 => '%d Stimme.',
	1 => '%d Stimmen.',
];
$a->strings['Poll end: %s'] = 'Abstimmung endet: %s';
$a->strings['View on separate page'] = 'Auf separater Seite ansehen';
$a->strings['[no subject]'] = '[kein Betreff]';
$a->strings['Wall Photos'] = 'Pinnwand-Bilder';
$a->strings['Edit profile'] = 'Profil bearbeiten';
$a->strings['Change profile photo'] = 'Profilbild ändern';
$a->strings['Homepage:'] = 'Homepage:';
$a->strings['About:'] = 'Über:';
$a->strings['Atom feed'] = 'Atom-Feed';
$a->strings['This website has been verified to belong to the same person.'] = 'Die Webseite wurde verifiziert und gehört der gleichen Person.';
$a->strings['F d'] = 'd. F';
$a->strings['[today]'] = '[heute]';
$a->strings['Birthday Reminders'] = 'Geburtstagserinnerungen';
$a->strings['Birthdays this week:'] = 'Geburtstage diese Woche:';
$a->strings['g A l F d'] = 'l, d. F G \U\h\r';
$a->strings['[No description]'] = '[keine Beschreibung]';
$a->strings['Event Reminders'] = 'Veranstaltungserinnerungen';
$a->strings['Upcoming events the next 7 days:'] = 'Veranstaltungen der nächsten 7 Tage:';
$a->strings['Hometown:'] = 'Heimatort:';
$a->strings['Marital Status:'] = 'Familienstand:';
$a->strings['With:'] = 'Mit:';
$a->strings['Since:'] = 'Seit:';
$a->strings['Sexual Preference:'] = 'Sexuelle Vorlieben:';
$a->strings['Political Views:'] = 'Politische Ansichten:';
$a->strings['Religious Views:'] = 'Religiöse Ansichten:';
$a->strings['Likes:'] = 'Likes:';
$a->strings['Dislikes:'] = 'Dislikes:';
$a->strings['Title/Description:'] = 'Titel/Beschreibung:';
$a->strings['Summary'] = 'Zusammenfassung';
$a->strings['Musical interests'] = 'Musikalische Interessen';
$a->strings['Books, literature'] = 'Bücher, Literatur';
$a->strings['Television'] = 'Fernsehen';
$a->strings['Film/dance/culture/entertainment'] = 'Filme/Tänze/Kultur/Unterhaltung';
$a->strings['Hobbies/Interests'] = 'Hobbies/Interessen';
$a->strings['Love/romance'] = 'Liebe/Romantik';
$a->strings['Work/employment'] = 'Arbeit/Anstellung';
$a->strings['School/education'] = 'Schule/Ausbildung';
$a->strings['Contact information and Social Networks'] = 'Kontaktinformationen und Soziale Netzwerke';
$a->strings['Responsible account: %s'] = 'Verantwortlicher Account: %s';
$a->strings['SERIOUS ERROR: Generation of security keys failed.'] = 'FATALER FEHLER: Sicherheitsschlüssel konnten nicht erzeugt werden.';
$a->strings['Login failed'] = 'Anmeldung fehlgeschlagen';
$a->strings['Not enough information to authenticate'] = 'Nicht genügend Informationen für die Authentifizierung';
$a->strings['Password can\'t be empty'] = 'Das Passwort kann nicht leer sein';
$a->strings['Empty passwords are not allowed.'] = 'Leere Passwörter sind nicht erlaubt.';
$a->strings['The new password has been exposed in a public data dump, please choose another.'] = 'Das neue Passwort wurde in einem öffentlichen Daten-Dump veröffentlicht. Bitte verwende ein anderes Passwort.';
$a->strings['The password length is limited to 72 characters.'] = 'Die Länge des Passworts ist auf 72 Zeichen begrenzt.';
$a->strings['The password can\'t contain white spaces nor accentuated letters'] = 'Das Passwort kann weder Leerzeichen noch akzentuierte Zeichen beinhalten.';
$a->strings['Passwords do not match. Password unchanged.'] = 'Die Passwörter stimmen nicht überein. Das Passwort bleibt unverändert.';
$a->strings['An invitation is required.'] = 'Du benötigst eine Einladung.';
$a->strings['Invitation could not be verified.'] = 'Die Einladung konnte nicht überprüft werden.';
$a->strings['Invalid OpenID url'] = 'Ungültige OpenID URL';
$a->strings['We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.'] = 'Beim Versuch, dich mit der von dir angegebenen OpenID anzumelden, trat ein Problem auf. Bitte überprüfe, dass du die OpenID richtig geschrieben hast.';
$a->strings['The error message was:'] = 'Die Fehlermeldung lautete:';
$a->strings['Please enter the required information.'] = 'Bitte trage die erforderlichen Informationen ein.';
$a->strings['system.username_min_length (%s) and system.username_max_length (%s) are excluding each other, swapping values.'] = 'system.username_min_length (%s) and system.username_max_length (%s) schließen sich gegenseitig aus, tausche Werte aus.';
$a->strings['Username should be at least %s character.'] = [
	0 => 'Der Benutzername sollte aus mindestens %s Zeichen bestehen.',
	1 => 'Der Benutzername sollte aus mindestens %s Zeichen bestehen.',
];
$a->strings['Username should be at most %s character.'] = [
	0 => 'Der Benutzername sollte aus maximal %s Zeichen bestehen.',
	1 => 'Der Benutzername sollte aus maximal %s Zeichen bestehen.',
];
$a->strings['That doesn\'t appear to be your full (First Last) name.'] = 'Das scheint nicht dein kompletter Name (Vor- und Nachname) zu sein.';
$a->strings['Your email domain is not among those allowed on this site.'] = 'Die Domain Deiner E-Mail-Adresse ist auf dieser Seite nicht erlaubt.';
$a->strings['Not a valid email address.'] = 'Keine gültige E-Mail-Adresse.';
$a->strings['The nickname was blocked from registration by the nodes admin.'] = 'Der Admin des Knotens hat den Spitznamen für die Registrierung gesperrt.';
$a->strings['Cannot use that email.'] = 'Konnte diese E-Mail-Adresse nicht verwenden.';
$a->strings['Your nickname can only contain a-z, 0-9 and _.'] = 'Dein Spitzname darf nur aus Buchstaben und Zahlen ("a-z","0-9" und "_") bestehen.';
$a->strings['Nickname is already registered. Please choose another.'] = 'Dieser Spitzname ist bereits vergeben. Bitte wähle einen anderen.';
$a->strings['An error occurred during registration. Please try again.'] = 'Während der Anmeldung ist ein Fehler aufgetreten. Bitte versuche es noch einmal.';
$a->strings['An error occurred creating your default profile. Please try again.'] = 'Bei der Erstellung des Standardprofils ist ein Fehler aufgetreten. Bitte versuche es noch einmal.';
$a->strings['An error occurred creating your self contact. Please try again.'] = 'Bei der Erstellung deines self-Kontakts ist ein Fehler aufgetreten. Bitte versuche es erneut.';
$a->strings['Friends'] = 'Kontakte';
$a->strings['An error occurred creating your default contact circle. Please try again.'] = 'Beim Erstellen Ihres Circles ist ein Fehler aufgetreten. Bitte versuche es erneut.';
$a->strings['Profile Photos'] = 'Profilbilder';
$a->strings['
		Dear %1$s,
			the administrator of %2$s has set up an account for you.'] = '
Hallo %1$s
ein Admin von %2$s hat dir ein Nutzerkonto angelegt.';
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
		Anbei die Anmeldedetails::

		Adresse der Seite:	%1$s
		Benutzername:		%2$s
		Passwort:		%3$s

		Du kannst dein Passwort unter "Einstellungen" ändern, sobald du dich angemeldet
		hast.

		Bitte nimm dir ein paar Minuten, um die anderen Einstellungen auf deiner Account-Seite zu kontrollieren. 

		Eventuell magst du ja auch einige Informationen über dich in deinem Profil veröffentlichen, damit andere Leute dich einfacher finden können.
		Bearbeite hierfür einfach dein Standard-Profil (über die Profil-Seite).

		Wir empfehlen das Hinzufügen eines Profilfotos, das Hinzufügen einiger Profil-"Schlüsselwörter"
		(sehr nützlich, um neue Freunde zu finden) - und vielleicht das Land,
		in dem Du lebst, wenn Du nicht noch spezifischer sein willst.

		Wir respektieren deine Privatsphäre - keine dieser Angaben ist notwendig
		Wenn du neu im Netzwerk bist und noch niemanden kennst, dann können sie allerdings dabei helfen,
		neue und interessante Kontakte zu knüpfen.

		Du kannst dein Nutzerkonto jederzeit unter %1$s/settings/removeme wieder löschen.

		Danke und willkommen auf %4$s.';
$a->strings['Registration details for %s'] = 'Details der Registration von %s';
$a->strings['
			Dear %1$s,
				Thank you for registering at %2$s. Your account is pending for approval by the administrator.

			Your login details are as follows:

			Site Location:	%3$s
			Login Name:		%4$s
			Password:		%5$s
		'] = '
			Hallo %1$s,
				danke für deine Registrierung auf %2$s. Dein Account muss noch vom Admin des Knotens freigeschaltet werden.

			Deine Zugangsdaten lauten wie folgt:

			Seitenadresse:	%3$s
			Anmeldename:		%4$s
			Passwort:		%5$s
		';
$a->strings['Registration at %s'] = 'Registrierung als %s';
$a->strings['
				Dear %1$s,
				Thank you for registering at %2$s. Your account has been created.
			'] = '
				Hallo %1$s,
				Danke für die Registrierung auf %2$s. Dein Account wurde angelegt.
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
Die Anmelde-Details sind die folgenden:
	Adresse der Seite:	%3$s
	Benutzernamename:	%1$s
	Passwort:	%5$s

Du kannst dein Passwort unter "Einstellungen" ändern, sobald du dich
angemeldet hast.

Bitte nimm dir ein paar Minuten, um die anderen Einstellungen auf dieser
Seite zu kontrollieren.

Eventuell magst du ja auch einige Informationen über dich in deinem
Profil veröffentlichen, damit andere Leute dich einfacher finden können.
Bearbeite hierfür einfach dein Standard-Profil (über die Profil-Seite).

Wir empfehlen dir,  ein zu dir passendes Profilbild zu wählen, damit dich alte Bekannte wiederfinden.
Außerdem ist es nützlich, wenn du auf deinem Profil Schlüsselwörter
angibst. Das erleichtert es, Leute zu finden, die deine Interessen teilen.

Wir respektieren deine Privatsphäre - keine dieser Angaben ist nötig.
Wenn du neu im Netzwerk bist und noch niemanden kennst, dann können sie
allerdings dabei helfen, neue und interessante Kontakte zu knüpfen.

Solltest du dein Nutzerkonto löschen wollen, kannst du dies unter %3$s/settings/removeme jederzeit tun.

Danke für deine Aufmerksamkeit und willkommen auf %2$s.';
$a->strings['User with delegates can\'t be removed, please remove delegate users first'] = 'Benutzer mit Delegaten können nicht entfernt werden, bitte entferne zuerst die delegierten Benutzer';
$a->strings['Addon not found.'] = 'Addon nicht gefunden.';
$a->strings['Addon %s disabled.'] = 'Addon %s ausgeschaltet.';
$a->strings['Addon %s enabled.'] = 'Addon %s aktiviert.';
$a->strings['Disable'] = 'Ausschalten';
$a->strings['Enable'] = 'Einschalten';
$a->strings['Administration'] = 'Administration';
$a->strings['Addons'] = 'Addons';
$a->strings['Toggle'] = 'Umschalten';
$a->strings['Author: '] = 'Autor:';
$a->strings['Maintainer: '] = 'Betreuer:';
$a->strings['Addons reloaded'] = 'Addons neu geladen';
$a->strings['Addon %s failed to install.'] = 'Addon %s konnte nicht installiert werden';
$a->strings['Save Settings'] = 'Einstellungen speichern';
$a->strings['Reload active addons'] = 'Aktivierte Addons neu laden';
$a->strings['There are currently no addons available on your node. You can find the official addon repository at %1$s.'] = 'Es sind derzeit keine Addons auf diesem Knoten verfügbar. Du findest das offizielle Addon-Repository unter %1$s.';
$a->strings['Update has been marked successful'] = 'Update wurde als erfolgreich markiert';
$a->strings['Database structure update %s was successfully applied.'] = 'Das Update %s der Struktur der Datenbank wurde erfolgreich angewandt.';
$a->strings['Executing of database structure update %s failed with error: %s'] = 'Das Update %s der Struktur der Datenbank schlug mit folgender Fehlermeldung fehl: %s';
$a->strings['Executing %s failed with error: %s'] = 'Die Ausführung von %s schlug fehl. Fehlermeldung: %s';
$a->strings['Update %s was successfully applied.'] = 'Update %s war erfolgreich.';
$a->strings['Update %s did not return a status. Unknown if it succeeded.'] = 'Update %s hat keinen Status zurückgegeben. Unbekannter Status.';
$a->strings['There was no additional update function %s that needed to be called.'] = 'Es gab keine weitere Update-Funktion, die von %s ausgeführt werden musste.';
$a->strings['No failed updates.'] = 'Keine fehlgeschlagenen Updates.';
$a->strings['Check database structure'] = 'Datenbankstruktur überprüfen';
$a->strings['Failed Updates'] = 'Fehlgeschlagene Updates';
$a->strings['This does not include updates prior to 1139, which did not return a status.'] = 'Ohne Updates vor 1139, da diese keinen Status zurückgegeben haben.';
$a->strings['Mark success (if update was manually applied)'] = 'Als erfolgreich markieren (falls das Update manuell installiert wurde)';
$a->strings['Attempt to execute this update step automatically'] = 'Versuchen, diesen Schritt automatisch auszuführen';
$a->strings['No'] = 'Nein';
$a->strings['Yes'] = 'Ja';
$a->strings['Locked'] = 'Abgeschlossen';
$a->strings['Manage Additional Features'] = 'Zusätzliche Features Verwalten';
$a->strings['Other'] = 'Andere';
$a->strings['unknown'] = 'Unbekannt';
$a->strings['%2$s total system'] = [
	0 => '%2$sServer gesamt',
	1 => '%2$s Server gesamt',
];
$a->strings['%2$s active user last month'] = [
	0 => '%2$s aktiver Nutzer im letzten Monat',
	1 => '%2$s aktive Nutzer im letzten Monat',
];
$a->strings['%2$s active user last six months'] = [
	0 => '%2$s aktive Nutzer im letzten halben Jahr',
	1 => '%2$s aktive Nutzer im letzten halben Jahr',
];
$a->strings['%2$s registered user'] = [
	0 => '%2$sregistrierter Nutzer',
	1 => '%2$s registrierte Nutzer',
];
$a->strings['%2$s locally created post or comment'] = [
	0 => '%2$slokal erstellter Beitrag oder Kommentar',
	1 => '%2$slokal erstellte Beiträge und Kommentare',
];
$a->strings['%2$s post per user'] = [
	0 => '%2$sBeitrag pro Nutzer',
	1 => '%2$sBeiträge pro Nutzer',
];
$a->strings['%2$s user per system'] = [
	0 => '%2$sNutzer pro System',
	1 => '%2$sNutzer pro System',
];
$a->strings['This page offers you some numbers to the known part of the federated social network your Friendica node is part of. These numbers are not complete but only reflect the part of the network your node is aware of.'] = 'Diese Seite präsentiert einige Zahlen zu dem bekannten Teil des föderalen sozialen Netzwerks, von dem deine Friendica Installation ein Teil ist. Diese Zahlen sind nicht absolut und reflektieren nur den Teil des Netzwerks, den dein Knoten kennt.';
$a->strings['Federation Statistics'] = 'Föderation Statistik';
$a->strings['Currently this node is aware of %2$s node (%3$s active users last month, %4$s active users last six months, %5$s registered users in total) from the following platforms:'] = [
	0 => 'Derzeit kennt dieser Knoten %2$s andere Knoten (mit %3$s aktiven Nutzern im letzten Monat, %4$s aktiven Nutzern im letzten halben Jahr, %5$s registrierten Nutzern insgesamt) von den folgenden Plattformen:',
	1 => 'Derzeit kennt dieser Knoten %2$s andere Knoten (mit %3$s aktiven Nutzern im letzten Monat, %4$s aktiven Nutzern im letzten halben Jahr, %5$s registrierten Nutzern insgesamt) von den folgenden Plattformen:',
];
$a->strings['The logfile \'%s\' is not writable. No logging possible'] = 'Die Logdatei \'%s\' ist nicht beschreibbar. Derzeit ist keine Aufzeichnung möglich.';
$a->strings['PHP log currently enabled.'] = 'PHP Protokollierung ist derzeit aktiviert.';
$a->strings['PHP log currently disabled.'] = 'PHP Protokollierung ist derzeit nicht aktiviert.';
$a->strings['Logs'] = 'Protokolle';
$a->strings['Clear'] = 'löschen';
$a->strings['Enable Debugging'] = 'Protokoll führen';
$a->strings['<strong>Read-only</strong> because it is set by an environment variable'] = '<strong>Schreibgeschützt</strong>, weil es durch eine Umgebungsvariable festgelegt ist';
$a->strings['Log file'] = 'Protokolldatei';
$a->strings['Must be writable by web server. Relative to your Friendica top-level directory.'] = 'Webserver muss Schreibrechte besitzen. Abhängig vom Friendica-Installationsverzeichnis.';
$a->strings['Log level'] = 'Protokoll-Level';
$a->strings['PHP logging'] = 'PHP Protokollieren';
$a->strings['To temporarily enable logging of PHP errors and warnings you can prepend the following to the index.php file of your installation. The filename set in the \'error_log\' line is relative to the friendica top-level directory and must be writeable by the web server. The option \'1\' for \'log_errors\' and \'display_errors\' is to enable these options, set to \'0\' to disable them.'] = 'Um die Protokollierung von PHP-Fehlern und Warnungen vorübergehend zu aktivieren, kannst du der Datei index.php deiner Installation Folgendes voranstellen. Der in der Datei \'error_log\' angegebene Dateiname ist relativ zum obersten Verzeichnis von Friendica und muss vom Webserver beschreibbar sein. Die Option \'1\' für \'log_errors\' und \'display_errors\' aktiviert diese Optionen, ersetze die \'1\' durch eine \'0\', um sie zu deaktivieren.';
$a->strings['Error trying to open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s exist and is readable.'] = 'Fehler beim Öffnen der Logdatei <strong>%1$s</strong>.<br/>Bitte überprüfe ob die Datei %1$s existiert und gelesen werden kann.';
$a->strings['Couldn\'t open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s is readable.'] = 'Konnte die Logdatei <strong>%1$s</strong> nicht öffnen.<br/>Bitte stelle sicher, dass die Datei %1$s lesbar ist.';
$a->strings['View Logs'] = 'Protokolle anzeigen';
$a->strings['Search in logs'] = 'Logs durchsuchen';
$a->strings['Show all'] = 'Alle anzeigen';
$a->strings['Date'] = 'Datum';
$a->strings['Level'] = 'Level';
$a->strings['Context'] = 'Zusammenhang';
$a->strings['ALL'] = 'ALLE';
$a->strings['View details'] = 'Details anzeigen';
$a->strings['Click to view details'] = 'Anklicken zum Anzeigen der Details';
$a->strings['Event details'] = 'Ereignisdetails';
$a->strings['Data'] = 'Daten';
$a->strings['Source'] = 'Quelle';
$a->strings['File'] = 'Datei';
$a->strings['Line'] = 'Zeile';
$a->strings['Function'] = 'Funktion';
$a->strings['UID'] = 'UID';
$a->strings['Process ID'] = 'Prozess ID';
$a->strings['Inspect Deferred Worker Queue'] = 'Verzögerte Worker-Warteschlange inspizieren';
$a->strings['This page lists the deferred worker jobs. This are jobs that couldn\'t be executed at the first time.'] = 'Auf dieser Seite werden die aufgeschobenen Worker-Jobs aufgelistet. Dies sind Jobs, die beim ersten Mal nicht ausgeführt werden konnten.';
$a->strings['Inspect Worker Queue'] = 'Worker-Warteschlange inspizieren';
$a->strings['This page lists the currently queued worker jobs. These jobs are handled by the worker cronjob you\'ve set up during install.'] = 'Auf dieser Seite werden die derzeit in der Warteschlange befindlichen Worker-Jobs aufgelistet. Diese Jobs werden vom Cronjob verarbeitet, den du während der Installation eingerichtet hast.';
$a->strings['ID'] = 'ID';
$a->strings['Command'] = 'Befehl';
$a->strings['Job Parameters'] = 'Parameter der Aufgabe';
$a->strings['Created'] = 'Erstellt';
$a->strings['Next Try'] = 'Nächster Versuch';
$a->strings['Priority'] = 'Priorität';
$a->strings['%s is no valid input for maximum media size'] = '%s ist keine gültige Angabe der maximalen Größe von Medien';
$a->strings['%s is no valid input for maximum image size'] = '%s ist keine gültige Angabe der maximalen Größe von Bildern';
$a->strings['No special theme for mobile devices'] = 'Kein spezielles Theme für mobile Geräte verwenden.';
$a->strings['%s - (Experimental)'] = '%s - (Experimentell)';
$a->strings['No community page'] = 'Keine Gemeinschaftsseite';
$a->strings['No community page for visitors'] = 'Keine Gemeinschaftsseite für Besucher';
$a->strings['Public postings from users of this site'] = 'Öffentliche Beiträge von NutzerInnen dieser Seite';
$a->strings['Public postings from the federated network'] = 'Öffentliche Beiträge aus dem föderalen Netzwerk';
$a->strings['Public postings from local users and the federated network'] = 'Öffentliche Beiträge von lokalen Nutzern und aus dem föderalen Netzwerk';
$a->strings['Multi user instance'] = 'Mehrbenutzer-Instanz';
$a->strings['Closed'] = 'Geschlossen';
$a->strings['Requires approval'] = 'Bedarf der Zustimmung';
$a->strings['Open'] = 'Offen';
$a->strings['Don\'t check'] = 'Nicht überprüfen';
$a->strings['check the stable version'] = 'überprüfe die stabile Version';
$a->strings['check the development version'] = 'überprüfe die Entwicklungsversion';
$a->strings['none'] = 'keine';
$a->strings['Local contacts'] = 'Lokale Kontakte';
$a->strings['Interactors'] = 'Interaktionen';
$a->strings['Site'] = 'Seite';
$a->strings['General Information'] = 'Allgemeine Informationen';
$a->strings['Republish users to directory'] = 'Nutzer erneut im globalen Verzeichnis veröffentlichen.';
$a->strings['Registration'] = 'Registrierung';
$a->strings['File upload'] = 'Datei hochladen';
$a->strings['Policies'] = 'Regeln';
$a->strings['Advanced'] = 'Erweitert';
$a->strings['Auto Discovered Contact Directory'] = 'Automatisch ein Kontaktverzeichnis erstellen';
$a->strings['Performance'] = 'Performance';
$a->strings['Worker'] = 'Worker';
$a->strings['Message Relay'] = 'Nachrichten-Relais';
$a->strings['Use the command "console relay" in the command line to add or remove relays.'] = 'Verwende den Befehl "console relay" auf der Kommandozeile um weitere Relays hinzu zu fügen oder zu entfernen.';
$a->strings['The system is not subscribed to any relays at the moment.'] = 'Das System hat derzeit keinerlei Relays abonniert.';
$a->strings['The system is currently subscribed to the following relays:'] = 'Das System hat derzeit Abonnements bei folgenden Releays:';
$a->strings['Relocate Node'] = 'Knoten umziehen';
$a->strings['Relocating your node enables you to change the DNS domain of this node and keep all the existing users and posts. This process takes a while and can only be started from the relocate console command like this:'] = 'Um deinen Friendica Knoten auf einen andere Domainnamen umzuziehen, und dabei alle existierenden Accounts und Beiträge zu behalten, kannst du dazu einen Konsolenbefehl verwenden. Die nötigen Aktualisierungen wird einige Zeit dauern und können nur auf der Konsole gestartet werden. Hierzu verwendest du einen Befehl wie den folgenden:';
$a->strings['(Friendica directory)# bin/console relocate https://newdomain.com'] = '(Friendica Verzeichnis)# bin/console relocate https://newdomain.com';
$a->strings['Site name'] = 'Seitenname';
$a->strings['Sender Email'] = 'Absender für Emails';
$a->strings['The email address your server shall use to send notification emails from.'] = 'Die E-Mail Adresse, die dein Server zum Versenden von Benachrichtigungen verwenden soll.';
$a->strings['Name of the system actor'] = 'Name des System-Actors';
$a->strings['Name of the internal system account that is used to perform ActivityPub requests. This must be an unused username. If set, this can\'t be changed again.'] = 'Name des internen System-Accounts der für ActivityPub Anfragen verwendet wird. Der Nutzername darf bisher nicht verwendet werden. Ist der Name einmal gesetzt kann er nicht mehr geändert werden.';
$a->strings['Banner/Logo'] = 'Banner/Logo';
$a->strings['Email Banner/Logo'] = 'E-Mail Banner / Logo';
$a->strings['Shortcut icon'] = 'Shortcut Icon';
$a->strings['Link to an icon that will be used for browsers.'] = 'Link zu einem Icon, das Browser verwenden werden.';
$a->strings['Touch icon'] = 'Touch Icon';
$a->strings['Link to an icon that will be used for tablets and mobiles.'] = 'Link zu einem Icon, das Tablets und Mobiltelefone verwenden sollen.';
$a->strings['Additional Info'] = 'Zusätzliche Informationen';
$a->strings['For public servers: you can add additional information here that will be listed at %s/servers.'] = 'Für öffentliche Server kannst du hier zusätzliche Informationen angeben, die dann auf %s/servers angezeigt werden.';
$a->strings['System language'] = 'Systemsprache';
$a->strings['System theme'] = 'Systemweites Theme';
$a->strings['Default system theme - may be over-ridden by user profiles - <a href="%s" id="cnftheme">Change default theme settings</a>'] = 'Standard-Theme des Systems - kann von Benutzerprofilen überschrieben werden - <a href="%s" id="cnftheme">Einstellungen des Standard-Themes ändern</a>';
$a->strings['Mobile system theme'] = 'Systemweites mobiles Theme';
$a->strings['Theme for mobile devices'] = 'Theme für mobile Geräte';
$a->strings['Force SSL'] = 'Erzwinge SSL';
$a->strings['Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.'] = 'Erzwinge SSL für alle Nicht-SSL-Anfragen - Achtung: auf manchen Systemen verursacht dies eine Endlosschleife.';
$a->strings['Show help entry from navigation menu'] = 'Zeige den Hilfe-Eintrag im Navigationsmenü an';
$a->strings['Displays the menu entry for the Help pages from the navigation menu. It is always accessible by calling /help directly.'] = 'Zeigt im Navigationsmenü den Eintrag für die Hilfe-Seiten an. Es ist immer möglich diese Seiten direkt über /help in der Adresseingabe des Browsers aufzurufen.';
$a->strings['Single user instance'] = 'Ein-Nutzer Instanz';
$a->strings['Make this instance multi-user or single-user for the named user'] = 'Bestimmt, ob es sich bei dieser Instanz um eine Installation mit nur einen Nutzer oder mit mehreren Nutzern handelt.';
$a->strings['Maximum image size'] = 'Maximale Bildgröße';
$a->strings['Maximum size in bytes of uploaded images. Default is 0, which means no limits. You can put k, m, or g behind the desired value for KiB, MiB, GiB, respectively.
													The value of <code>upload_max_filesize</code> in your <code>PHP.ini</code> needs be set to at least the desired limit.
													Currently <code>upload_max_filesize</code> is set to %s (%s byte)'] = 'Die maximale Größe von Bildern in Bytes. Grundeinstellung ist 0, welches keine Limitierung durch die Bildgröße bedeutet. Du kannst k, m oder g als Abkürzung hinter der Zahl angeben um KiB, MIB oder GiB zu definieren.
													Der Wert der <code>1upload_max_filesize1</code> Variable in der <code>php.ini</code> Datei muss diesem Limit mindestens entsprechen.
													Derzeit ist <code>3upload_max_filesize3</code> auf %s (%sByte) gesetzt.';
$a->strings['Maximum image length'] = 'Maximale Bildlänge';
$a->strings['Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.'] = 'Maximale Länge in Pixeln der längsten Seite eines hochgeladenen Bildes. Grundeinstellung ist -1, was keine Einschränkung bedeutet.';
$a->strings['JPEG image quality'] = 'Qualität des JPEG Bildes';
$a->strings['Uploaded JPEGS will be saved at this quality setting [0-100]. Default is 100, which is full quality.'] = 'Hochgeladene JPEG-Bilder werden mit dieser Qualität [0-100] gespeichert. Grundeinstellung ist 100, kein Qualitätsverlust.';
$a->strings['Maximum media file size'] = 'Maximale Medien-Dateigröße';
$a->strings['Maximum size in bytes of uploaded media files. Default is 0, which means no limits. You can put k, m, or g behind the desired value for KiB, MiB, GiB, respectively.
													The value of <code>upload_max_filesize</code> in your <code>PHP.ini</code> needs be set to at least the desired limit.
													Currently <code>upload_max_filesize</code> is set to %s (%s byte)'] = 'Die maximale Größe von Multimedia Dateien in Bytes. Grundeinstellung ist 0, welches keine Limitierung durch die Bildgröße bedeutet. Du kannst k, m oder g als Abkürzung hinter der Zahl angeben um KiB, MIB oder GiB zu definieren.
													Der Wert der <code>1upload_max_filesize1</code> Variable in der <code>php.ini</code> Datei muss diesem Limit mindestens entsprechen.
													Derzeit ist <code>3upload_max_filesize3</code> auf %s (%sByte) gesetzt.';
$a->strings['Register policy'] = 'Registrierungsmethode';
$a->strings['Maximum Users'] = 'Maximale Benutzeranzahl';
$a->strings['If defined, the register policy is automatically closed when the given number of users is reached and reopens the registry when the number drops below the limit. It only works when the policy is set to open or close, but not when the policy is set to approval.'] = 'Falls definiert, wird die Registrierungsrichtlinie automatisch geschlossen, wenn die angegebene Anzahl von Benutzern erreicht ist, und die Registrierung wieder geöffnet, wenn die Anzahl unter den Grenzwert fällt. Dies funktioniert nur, wenn die Richtlinie auf "Öffnen" oder "Schließen" eingestellt ist, nicht aber, wenn die Richtlinie auf "Genehmigung" eingestellt ist.';
$a->strings['Maximum Daily Registrations'] = 'Maximum täglicher Registrierungen';
$a->strings['If registration is permitted above, this sets the maximum number of new user registrations to accept per day.  If register is set to closed, this setting has no effect.'] = 'Wenn die Registrierung weiter oben erlaubt ist, regelt dies die maximale Anzahl von Neuanmeldungen pro Tag. Wenn die Registrierung geschlossen ist, hat diese Einstellung keinen Effekt.';
$a->strings['Register text'] = 'Registrierungstext';
$a->strings['Will be displayed prominently on the registration page. You can use BBCode here.'] = 'Wird gut sichtbar auf der Registrierungsseite angezeigt. BBCode kann verwendet werden.';
$a->strings['Forbidden Nicknames'] = 'Verbotene Spitznamen';
$a->strings['Comma separated list of nicknames that are forbidden from registration. Preset is a list of role names according RFC 2142.'] = 'Durch Kommas getrennte Liste von Spitznamen, die von der Registrierung ausgeschlossen sind. Die Vorgabe ist eine Liste von Rollennamen nach RFC 2142.';
$a->strings['Accounts abandoned after x days'] = 'Nutzerkonten gelten nach x Tagen als unbenutzt';
$a->strings['Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.'] = 'Verschwende keine System-Ressourcen auf das Pollen externer Seiten, wenn Konten nicht mehr benutzt werden. 0 eingeben für kein Limit.';
$a->strings['Allowed friend domains'] = 'Erlaubte Domains für Kontakte';
$a->strings['Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains'] = 'Liste der Domains, die für Kontakte erlaubt sind, durch Kommas getrennt. Platzhalter werden akzeptiert. Leer lassen, um alle Domains zu erlauben.';
$a->strings['Allowed email domains'] = 'Erlaubte Domains für E-Mails';
$a->strings['Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains'] = 'Liste der Domains, die für E-Mail-Adressen bei der Registrierung erlaubt sind, durch Kommas getrennt. Platzhalter werden akzeptiert. Leer lassen, um alle Domains zu erlauben.';
$a->strings['Disallowed email domains'] = 'Nicht erlaubte Domains für E-Mails';
$a->strings['Comma separated list of domains which are rejected as email addresses for registrations to this site. Wildcards are accepted.'] = 'Komma-getrennte Liste von Domains, die als E-Mail-Adressen für Registrierungen auf dieser Website abgelehnt werden. Platzhalter sind erlaubt.';
$a->strings['No OEmbed rich content'] = 'OEmbed nicht verwenden';
$a->strings['Don\'t show the rich content (e.g. embedded PDF), except from the domains listed below.'] = 'Verhindert das Einbetten von reichhaltigen Inhalten (z.B. eingebettete PDF Dateien). Ausgenommen von dieser Regel werden Domänen, die unten aufgeführt werden.';
$a->strings['Trusted third-party domains'] = 'Vertrauenswürdige Drittanbieter-Domains';
$a->strings['Comma separated list of domains from which content is allowed to be embedded in posts like with OEmbed. All sub-domains of the listed domains are allowed as well.'] = 'Komma separierte Liste von Domains von denen Inhalte in Beiträgen eingebettet werden dürfen. Alle Subdomains werden ebenfalls akzeptiert.';
$a->strings['Block public'] = 'Öffentlichen Zugriff blockieren';
$a->strings['Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.'] = 'Klicken, um öffentlichen Zugriff auf sonst öffentliche Profile zu blockieren, wenn man nicht eingeloggt ist.';
$a->strings['Force publish'] = 'Erzwinge Veröffentlichung';
$a->strings['Check to force all profiles on this site to be listed in the site directory.'] = 'Klicken, um Anzeige aller Profile dieses Servers im Verzeichnis zu erzwingen.';
$a->strings['Enabling this may violate privacy laws like the GDPR'] = 'Wenn du diese Option aktivierst, verstößt das unter Umständen gegen Gesetze wie die EU-DSGVO.';
$a->strings['Global directory URL'] = 'URL des weltweiten Verzeichnisses';
$a->strings['URL to the global directory. If this is not set, the global directory is completely unavailable to the application.'] = 'URL des weltweiten Verzeichnisses. Wenn diese nicht gesetzt ist, ist das Verzeichnis für die Applikation nicht erreichbar.';
$a->strings['Private posts by default for new users'] = 'Private Beiträge als Standard für neue Nutzer';
$a->strings['Set default post permissions for all new members to the default privacy circle rather than public.'] = 'Die Standard-Zugriffsrechte für neue Nutzer werden so gesetzt, dass als Voreinstellung in den privaten Circle gepostet wird anstelle von öffentlichen.';
$a->strings['Don\'t include post content in email notifications'] = 'Inhalte von Beiträgen nicht in E-Mail-Benachrichtigungen versenden';
$a->strings['Don\'t include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.'] = 'Inhalte von Beiträgen/Kommentaren/privaten Nachrichten/usw. zum Datenschutz nicht in E-Mail-Benachrichtigungen einbinden.';
$a->strings['Disallow public access to addons listed in the apps menu.'] = 'Öffentlichen Zugriff auf Addons im Apps Menü verbieten.';
$a->strings['Checking this box will restrict addons listed in the apps menu to members only.'] = 'Wenn ausgewählt, werden die im Apps Menü aufgeführten Addons nur angemeldeten Nutzern der Seite zur Verfügung gestellt.';
$a->strings['Don\'t embed private images in posts'] = 'Private Bilder nicht in Beiträgen einbetten.';
$a->strings['Don\'t replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.'] = 'Ersetze lokal gehostete, private Fotos in Beiträgen nicht mit einer eingebetteten Kopie des Bildes. Dies bedeutet, dass Kontakte, die Beiträge mit privaten Fotos erhalten, sich zunächst auf den jeweiligen Servern authentifizieren müssen, bevor die Bilder geladen und angezeigt werden, was eine gewisse Zeit dauert.';
$a->strings['Explicit Content'] = 'Sensibler Inhalt';
$a->strings['Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.'] = 'Wähle dies, um anzuzeigen, dass dein Knoten hauptsächlich für explizite Inhalte verwendet wird, die möglicherweise nicht für Minderjährige geeignet sind. Diese Info wird in der Knoteninformation veröffentlicht und kann durch das Globale Verzeichnis genutzt werden, um deinen Knoten von den Auflistungen auszuschließen. Zusätzlich wird auf der Registrierungsseite ein Hinweis darüber angezeigt.';
$a->strings['Only local search'] = 'Nur lokale Suche';
$a->strings['Blocks search for users who are not logged in to prevent crawlers from blocking your system.'] = 'Sperrt die Suche für nicht eingeloggte Benutzer, um zu verhindern, dass Crawler Ihr System blockieren.';
$a->strings['Blocked tags for trending tags'] = 'Blockierte Tags für Trend-Tags';
$a->strings['Comma separated list of hashtags that shouldn\'t be displayed in the trending tags.'] = 'Durch Kommata getrennte Liste von Hashtags, die nicht in den Trending Tags angezeigt werden sollen.';
$a->strings['Cache contact avatars'] = 'Kontaktprofilbilder zwischenspeichern';
$a->strings['Locally store the avatar pictures of the contacts. This uses a lot of storage space but it increases the performance.'] = 'Die Profilbilder der Kontakte zwischenspeichern. Der Zwischenspeicher verbraucht viel Platz im Speicherplatz, verbessert aber die Performance.';
$a->strings['Allow Users to set remote_self'] = 'Nutzern erlauben, das remote_self Flag zu setzen';
$a->strings['With checking this, every user is allowed to mark every contact as a remote_self in the repair contact dialog. Setting this flag on a contact causes mirroring every posting of that contact in the users stream.'] = 'Ist dies ausgewählt, kann jeder Nutzer jeden seiner Kontakte als remote_self (entferntes Konto) im "Erweitert"-Reiter der Kontaktansicht markieren. Nach dem Setzen dieses Flags werden alle Top-Level-Beiträge dieser Kontakte automatisch in den Stream dieses Nutzers gepostet (gespiegelt).';
$a->strings['Allow Users to set up relay channels'] = 'Benutzern erlauben Relaiskanäle einzurichten';
$a->strings['If enabled, it is possible to create relay users that are used to reshare content based on user defined channels.'] = 'Wenn aktiviert, ist es möglich, Relaisbenutzer zu erstellen, die dazu verwendet werden, Inhalte basierend auf benutzerdefinierten Kanälen erneut zu teilen.';
$a->strings['Adjust the feed poll frequency'] = 'Einstellen der Abrufhäufigkeit';
$a->strings['Automatically detect and set the best feed poll frequency.'] = 'Automatisches Erkennen und Einstellen der besten Abrufhäufigkeit.';
$a->strings['Minimum poll interval'] = 'Minimales Abfrageintervall';
$a->strings['Minimal distance in minutes between two polls for mail and feed contacts. Reasonable values are between 1 and 59.'] = 'Minimaler Abstand in Minuten zwischen zwei Abfragen für Mail- und Feed-Kontakte. Sinnvolle Werte liegen zwischen 1 und 59.';
$a->strings['Enable multiple registrations'] = 'Erlaube Mehrfachregistrierung';
$a->strings['Enable users to register additional accounts for use as pages.'] = 'Erlaube es Benutzern weitere Konten für Organisationen o.ä. mit der gleichen E-Mail Adresse anzulegen.';
$a->strings['Enable OpenID'] = 'OpenID aktivieren';
$a->strings['Enable OpenID support for registration and logins.'] = 'OpenID Unterstützung bei der Registrierung und dem Login aktivieren.';
$a->strings['Enable full name check'] = 'Namen auf Vollständigkeit überprüfen';
$a->strings['Prevents users from registering with a display name with fewer than two parts separated by spaces.'] = 'Verhindert, dass sich Benutzer mit einem Anzeigenamen registrieren, der aus weniger als zwei durch Leerzeichen getrennten Teilen besteht.';
$a->strings['Email administrators on new registration'] = 'Email den Administratoren bei neuen Registrierungen';
$a->strings['If enabled and the system is set to an open registration, an email for each new registration is sent to the administrators.'] = 'Wenn diese Option aktiviert ist und die Registrierung auf offen eingestellt ist, wird den Administratoren bei jeder neuen Registierung eine Email geschickt.';
$a->strings['Community pages for visitors'] = 'Für Besucher verfügbare Gemeinschaftsseite';
$a->strings['Which community pages should be available for visitors. Local users always see both pages.'] = 'Welche Gemeinschaftsseiten sollen für Besucher dieses Knotens verfügbar sein? Lokale Nutzer können grundsätzlich beide Seiten verwenden.';
$a->strings['Posts per user on community page'] = 'Anzahl der Beiträge pro Benutzer auf der Gemeinschaftsseite';
$a->strings['The maximum number of posts per user on the local community page. This is useful, when a single user floods the local community page.'] = 'Die maximale Anzahl von Beiträgen pro Benutzer auf der lokalen Gemeinschaftsseite. Dies ist nützlich, wenn ein einzelner Benutzer die lokale Gemeinschaftsseite überflutet.';
$a->strings['Posts per server on community page'] = 'Beiträge pro Server auf der Gemeinschaftsseite';
$a->strings['The maximum number of posts per server on the global community page. This is useful, when posts from a single server flood the global community page.'] = 'Die maximale Anzahl von Beiträgen pro Server auf der globalen Gemeinschaftsseite. Dies ist nützlich, wenn Beiträge von einem einzelnen Server die globale Gemeinschaftsseite überfluten.';
$a->strings['Enable Mail support'] = 'E-Mail Unterstützung aktivieren';
$a->strings['Enable built-in mail support to poll IMAP folders and to reply via mail.'] = 'Aktiviert die Unterstützung IMAP Ordner abzurufen und ermöglicht es auch auf E-Mails zu antworten.';
$a->strings['Mail support can\'t be enabled because the PHP IMAP module is not installed.'] = 'E-Mail Unterstützung kann nicht aktiviert werden, da das PHP IMAP Modul nicht installiert ist.';
$a->strings['Diaspora support can\'t be enabled because Friendica was installed into a sub directory.'] = 'Diaspora Unterstützung kann nicht aktiviert werden, da Friendica in ein Unterverzeichnis installiert ist.';
$a->strings['Enable Diaspora support'] = 'Diaspora-Unterstützung aktivieren';
$a->strings['Enable built-in Diaspora network compatibility for communicating with diaspora servers.'] = 'Aktiviere die Unterstützung des Diaspora Protokolls zur Kommunikation mit Diaspora Servern.';
$a->strings['Verify SSL'] = 'SSL Überprüfen';
$a->strings['If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.'] = 'Wenn gewollt, kann man hier eine strenge Zertifikatskontrolle einstellen. Das bedeutet, dass man zu keinen Seiten mit selbst unterzeichnetem SSL-Zertifikat eine Verbindung herstellen kann.';
$a->strings['Proxy user'] = 'Proxy-Nutzer';
$a->strings['User name for the proxy server.'] = 'Nutzername für den Proxy-Server';
$a->strings['Proxy URL'] = 'Proxy-URL';
$a->strings['If you want to use a proxy server that Friendica should use to connect to the network, put the URL of the proxy here.'] = 'Wenn Friendica einen Proxy-Server verwenden soll um das Netzwerk zu erreichen, füge hier die URL des Proxys ein.';
$a->strings['Network timeout'] = 'Netzwerk-Wartezeit';
$a->strings['Value is in seconds. Set to 0 for unlimited (not recommended).'] = 'Der Wert ist in Sekunden. Setze 0 für unbegrenzt (nicht empfohlen).';
$a->strings['Maximum Load Average'] = 'Maximum Load Average';
$a->strings['Maximum system load before delivery and poll processes are deferred - default %d.'] = 'Maximale System-LOAD bevor Verteil- und Empfangsprozesse verschoben werden - Standard %d';
$a->strings['Minimal Memory'] = 'Minimaler Speicher';
$a->strings['Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).'] = 'Minimal freier Speicher in MB für den Worker Prozess. Benötigt Zugriff auf /proc/meminfo - Standardwert ist 0 (deaktiviert)';
$a->strings['Periodically optimize tables'] = 'Optimiere die Tabellen regelmäßig';
$a->strings['Periodically optimize tables like the cache and the workerqueue'] = 'Optimiert Tabellen wie den Cache oder die Worker-Warteschlage regelmäßig.';
$a->strings['Discover followers/followings from contacts'] = 'Endecke folgende und gefolgte Kontakte von Kontakten';
$a->strings['If enabled, contacts are checked for their followers and following contacts.'] = 'Ist dies aktiv, werden die Kontakte auf deren folgenden und gefolgten Kontakte überprüft.';
$a->strings['None - deactivated'] = 'Keine - deaktiviert';
$a->strings['Local contacts - contacts of our local contacts are discovered for their followers/followings.'] = 'Lokale Kontakte - Die Beziehungen der lokalen Kontakte werden analysiert.';
$a->strings['Interactors - contacts of our local contacts and contacts who interacted on locally visible postings are discovered for their followers/followings.'] = 'Interaktionen - Kontakte der lokalen Kontakte sowie die Profile die mit öffentlichen lokalen Beiträgen interagiert haben, werden bzgl. ihrer Beziehungen analysiert.';
$a->strings['Only update contacts/servers with local data'] = 'Nur Kontakte/Server mit lokalen Daten aktualisieren';
$a->strings['If enabled, the system will only look for changes in contacts and servers that engaged on this system by either being in a contact list of a user or when posts or comments exists from the contact on this system.'] = 'Wenn diese Option aktiviert ist, sucht das System nur nach Änderungen bei Kontakten und Servern, die mit dieser Instanz interagiert haben, indem sie entweder in einer Kontaktliste eines Benutzers enthalten sind oder wenn Beiträge oder Kommentare von diesem Kontakt in dieser Instanz vorhanden sind.';
$a->strings['Only update contacts with relations'] = 'Nur Kontakte mit Verbindungen aktualisieren';
$a->strings['If enabled, the system will only look for changes in contacts that are in a contact list of a user on this system.'] = 'Wenn diese Option aktiviert ist, wird das System nur bei Kontakten mit direkten Verbindungen zu Accounts auf diesem System nach Änderungen der Kontaktdetails suchen.';
$a->strings['Synchronize the contacts with the directory server'] = 'Gleiche die Kontakte mit dem Directory-Server ab';
$a->strings['if enabled, the system will check periodically for new contacts on the defined directory server.'] = 'Ist dies aktiv, wird das System regelmäßig auf dem Verzeichnis-Server nach neuen potentiellen Kontakten nachsehen.';
$a->strings['Discover contacts from other servers'] = 'Neue Kontakte auf anderen Servern entdecken';
$a->strings['Periodically query other servers for contacts and servers that they know of. The system queries Friendica, Mastodon and Hubzilla servers. Keep it deactivated on small machines to decrease the database size and load.'] = 'Regelmäßige Abfrage anderer Server nach Kontakten und Servern, welche dort bekannt sind. Das System fragt Friendica, Mastodon und Hubzilla Server ab. Lass diese Options auf kleinen Instanzen deaktiviert, um die Datenbankgröße und -last zu verringern.';
$a->strings['Days between requery'] = 'Tage zwischen erneuten Abfragen';
$a->strings['Number of days after which a server is requeried for their contacts and servers it knows of. This is only used when the discovery is activated.'] = 'Anzahl der Tage, nach denen ein Server nach seinen Kontakten und den ihm bekannten Servern abgefragt wird. Dies wird nur verwendet, wenn die Erkennung aktiviert ist.';
$a->strings['Search the local directory'] = 'Lokales Verzeichnis durchsuchen';
$a->strings['Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.'] = 'Suche im lokalen Verzeichnis anstelle des globalen Verzeichnisses durchführen. Jede Suche wird im Hintergrund auch im globalen Verzeichnis durchgeführt, um die Suchresultate zu verbessern, wenn die Suche wiederholt wird.';
$a->strings['Publish server information'] = 'Server-Informationen veröffentlichen';
$a->strings['If enabled, general server and usage data will be published. The data contains the name and version of the server, number of users with public profiles, number of posts and the activated protocols and connectors. See <a href="http://the-federation.info/">the-federation.info</a> for details.'] = 'Wenn aktiviert, werden allgemeine Informationen über den Server und Nutzungsdaten veröffentlicht. Die Daten beinhalten den Namen sowie die Version des Servers, die Anzahl der Personen mit öffentlichen Profilen, die Anzahl der Beiträge sowie aktivierte Protokolle und Konnektoren. Für Details bitte <a href="http://the-federation.info/">the-federation.info</a> aufrufen.';
$a->strings['Check upstream version'] = 'Suche nach Updates';
$a->strings['Enables checking for new Friendica versions at github. If there is a new version, you will be informed in the admin panel overview.'] = 'Wenn diese Option aktiviert ist, wird regelmäßig nach neuen Friendica-Versionen auf github gesucht. Wenn es eine neue Version gibt, wird dies auf der Übersichtsseite im Admin-Panel angezeigt.';
$a->strings['Suppress Tags'] = 'Tags unterdrücken';
$a->strings['Suppress showing a list of hashtags at the end of the posting.'] = 'Unterdrückt die Anzeige von Tags am Ende eines Beitrags.';
$a->strings['Clean database'] = 'Datenbank aufräumen';
$a->strings['Remove old remote items, orphaned database records and old content from some other helper tables.'] = 'Entferne alte Beiträge von anderen Knoten, verwaiste Einträge und alten Inhalt einiger Hilfstabellen.';
$a->strings['Lifespan of remote items'] = 'Lebensdauer von Beiträgen anderer Knoten';
$a->strings['When the database cleanup is enabled, this defines the days after which remote items will be deleted. Own items, and marked or filed items are always kept. 0 disables this behaviour.'] = 'Wenn das Aufräumen der Datenbank aktiviert ist, definiert dies die Anzahl in Tagen, nach der Beiträge, die auf anderen Knoten des Netzwerks verfasst wurden, gelöscht werden sollen. Eigene Beiträge sowie markierte oder abgespeicherte Beiträge werden nicht gelöscht. Ein Wert von 0 deaktiviert das automatische Löschen von Beiträgen.';
$a->strings['Lifespan of unclaimed items'] = 'Lebensdauer nicht angeforderter Beiträge';
$a->strings['When the database cleanup is enabled, this defines the days after which unclaimed remote items (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general lifespan value of remote items if set to 0.'] = 'Wenn das Aufräumen der Datenbank aktiviert ist, definiert dies die Anzahl von Tagen, nach denen nicht angeforderte Beiträge (hauptsächlich solche, die über das Relais eintreffen) gelöscht werden. Der Standardwert beträgt 90 Tage. Wird dieser Wert auf 0 gesetzt, wird die Lebensdauer von Beiträgen anderer Knoten verwendet.';
$a->strings['Lifespan of raw conversation data'] = 'Lebensdauer der Beiträge';
$a->strings['The conversation data is used for ActivityPub, as well as for debug purposes. It should be safe to remove it after 14 days, default is 90 days.'] = 'Die Konversationsdaten werden für ActivityPub sowie für Debug-Zwecke verwendet. Sie sollten gefahrlos nach 14 Tagen entfernt werden können, der Standardwert beträgt 90 Tage.';
$a->strings['Maximum numbers of comments per post'] = 'Maximale Anzahl von Kommentaren pro Beitrag';
$a->strings['How much comments should be shown for each post? Default value is 100.'] = 'Wie viele Kommentare sollen pro Beitrag angezeigt werden? Standardwert sind 100.';
$a->strings['Maximum numbers of comments per post on the display page'] = 'Maximale Anzahl von Kommentaren in der Einzelansicht';
$a->strings['How many comments should be shown on the single view for each post? Default value is 1000.'] = 'Wie viele Kommentare sollen auf der Einzelansicht eines Beitrags angezeigt werden? Grundeinstellung sind 1000.';
$a->strings['Items per page'] = 'Beiträge pro Seite';
$a->strings['Number of items per page in stream pages (network, community, profile/contact statuses, search).'] = 'Anzahl der Elemente pro Seite in den Stream-Seiten (Netzwerk, Community, Profil/Kontaktstatus, Suche).';
$a->strings['Items per page for mobile devices'] = 'Beiträge pro Seite für mobile Endgeräte';
$a->strings['Number of items per page in stream pages (network, community, profile/contact statuses, search) for mobile devices.'] = 'Anzahl der Beiträge pro Seite in Stream-Seiten (Netzwerk, Community, Profil-/Kontaktstatus, Suche) für mobile Endgeräte.';
$a->strings['Temp path'] = 'Temp-Pfad';
$a->strings['If you have a restricted system where the webserver can\'t access the system temp path, enter another path here.'] = 'Solltest du ein eingeschränktes System haben, auf dem der Webserver nicht auf das temp-Verzeichnis des Systems zugreifen kann, setze hier einen anderen Pfad.';
$a->strings['Only search in tags'] = 'Nur in Tags suchen';
$a->strings['On large systems the text search can slow down the system extremely.'] = 'Auf großen Knoten kann die Volltext-Suche das System ausbremsen.';
$a->strings['Limited search scope'] = 'Suchfeld begrenzen';
$a->strings['If enabled, searches will only be performed in the data used for the channels and not in all posts.'] = 'Ist dies aktiviert, werden bei Suchen nur die Daten der Kanäle durchsucht anstatt alle Beiträge zu durchsuchen.';
$a->strings['Maximum age of items in the search table'] = 'Maximales Alter der Elemente in der Suchtabelle';
$a->strings['Maximum age of items in the search table in days. Lower values will increase the performance and reduce disk usage. 0 means no age restriction.'] = 'Maximales Alter der Elemente in der Suchtabelle in Tagen. Niedrigere Werte erhöhen die Leistung und reduzieren den Festplattenverbrauch. 0 bedeutet keine Altersbeschränkung.';
$a->strings['Generate counts per contact circle when calculating network count'] = 'Erstelle Zählungen je Circle bei der Berechnung der Netzwerkanzahl';
$a->strings['On systems with users that heavily use contact circles the query can be very expensive.'] = 'Auf Systemen mit Benutzern, die häufig Circles verwenden, kann die Abfrage sehr aufwändig sein.';
$a->strings['Process "view" activities'] = '"view"-Aktivitäten verarbeiten';
$a->strings['"view" activities are mostly geberated by Peertube systems. Per default they are not processed for performance reasons. Only activate this option on performant system.'] = '"view"-Aktivitäten werden zumeist von Peertube-Systemen genutzt. Standardmäßig werden sie aus Leistungsgründen nicht verarbeitet. Aktivieren Sie diese Option nur auf performanten Systemen.';
$a->strings['Days, after which a contact is archived'] = 'Anzahl der Tage, nach denen ein Kontakt archiviert wird';
$a->strings['Number of days that we try to deliver content or to update the contact data before we archive a contact.'] = 'Die Anzahl der Tage, die wir versuchen, Inhalte zu liefern oder die Kontaktdaten zu aktualisieren, bevor wir einen Kontakt archivieren.';
$a->strings['Maximum number of parallel workers'] = 'Maximale Anzahl parallel laufender Worker';
$a->strings['On shared hosters set this to %d. On larger systems, values of %d are great. Default value is %d.'] = 'Wenn dein Knoten bei einem Shared Hoster ist, setze diesen Wert auf %d. Auf größeren Systemen funktioniert ein Wert von %d recht gut. Standardeinstellung sind %d.';
$a->strings['Maximum load for workers'] = 'Maximale Last für Worker';
$a->strings['Maximum load that causes a cooldown before each worker function call.'] = 'Maximale Auslastung des Systems, welche eine Verzögerung vor jedem Aufruf des Workers verursacht.';
$a->strings['Enable fastlane'] = 'Aktiviere Fastlane';
$a->strings['When enabed, the fastlane mechanism starts an additional worker if processes with higher priority are blocked by processes of lower priority.'] = 'Wenn aktiviert, wird der Fastlane-Mechanismus einen weiteren Worker-Prozeß starten, wenn Prozesse mit höherer Priorität von Prozessen mit niedrigerer Priorität blockiert werden.';
$a->strings['Decoupled receiver'] = 'Entkoppelter Empfänger';
$a->strings['Decouple incoming ActivityPub posts by processing them in the background via a worker process. Only enable this on fast systems.'] = 'Entkoppelt eingehende ActivityPub-Beiträge, indem sie im Hintergrund über einen Worker-Prozess verarbeitet werden. Aktiviere dies nur auf schnellen Systemen.';
$a->strings['Cron interval'] = 'Cron Intervall';
$a->strings['Minimal period in minutes between two calls of the "Cron" worker job.'] = 'Minimaler Intervall in Minuten zwischen zwei Aufrufen des "Cron" Arbeitsprozesses.';
$a->strings['Worker defer limit'] = 'Worker-Verzögerungsgrenze';
$a->strings['Per default the systems tries delivering for 15 times before dropping it.'] = 'Standardmäßig versucht das System 15 Mal zuzustellen, bevor es den Vorgang abbricht.';
$a->strings['Worker fetch limit'] = 'Worker Abrufbegrenzung';
$a->strings['Number of worker tasks that are fetched in a single query. Higher values should increase the performance, too high values will mostly likely decrease it. Only change it, when you know how to measure the performance of your system.'] = 'Anzahl der Worker-Aufgaben, die in einer einzigen Abfrage abgerufen werden. Höhere Werte sollten die Leistung verbessern, zu hohe Werte werden sie wahrscheinlich verringern. Änder dies nur, wenn Du weißt, wie Du die Leistung des Systems messen kannst.';
$a->strings['Direct relay transfer'] = 'Direkte Relais-Übertragung';
$a->strings['Enables the direct transfer to other servers without using the relay servers'] = 'Aktiviert das direkte Verteilen an andere Server, ohne dass ein Relais-Server verwendet wird.';
$a->strings['Relay scope'] = 'Geltungsbereich des Relais';
$a->strings['Can be "all" or "tags". "all" means that every public post should be received. "tags" means that only posts with selected tags should be received.'] = 'Der Wert kann entweder \'Alle\' oder \'Schlagwörter\' sein. \'Alle\' bedeutet, dass alle öffentliche Beiträge empfangen werden sollen. \'Schlagwörter\' schränkt dem Empfang auf Beiträge ein, die bestimmte Schlagwörter beinhalten.';
$a->strings['Disabled'] = 'Deaktiviert';
$a->strings['all'] = 'Alle';
$a->strings['tags'] = 'Schlagwörter';
$a->strings['Server tags'] = 'Server-Schlagworte';
$a->strings['Comma separated list of tags for the "tags" subscription.'] = 'Liste von Schlagworten, die abonniert werden sollen, mit Komma getrennt.';
$a->strings['Deny Server tags'] = 'Server Tags ablehnen';
$a->strings['Comma separated list of tags that are rejected.'] = 'Durch Kommas getrennte Liste der Tags, die abgelehnt werden';
$a->strings['Maximum amount of tags'] = 'Maximale Anzahl an Tags';
$a->strings['Maximum amount of tags in a post before it is rejected as spam. The post has to contain at least one link. Posts from subscribed accounts will not be rejected.'] = 'Maximale Anzahl von Tags in einem Beitrag, bevor er als Spam abgelehnt wird. Der Beitrag muss mindestens einen Link enthalten. Beiträge von abonnierten Konten werden nicht abgelehnt.';
$a->strings['Allow user tags'] = 'Verwende Schlagworte der Nutzer';
$a->strings['If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".'] = 'Ist dies aktiviert, werden die Schlagwörter der gespeicherten Suchen zusätzlich zu den oben definierten Server-Schlagworten abonniert.';
$a->strings['Deny undetected languages'] = 'Nicht erkannte Sprachen ablehnen';
$a->strings['If enabled, posts with undetected languages will be rejected.'] = 'Wenn diese Option aktiviert ist, werden Beiträge mit nicht erkannten Sprachen abgelehnt.';
$a->strings['Language Quality'] = 'Sprachqualität';
$a->strings['The minimum language quality that is required to accept the post.'] = 'Die minimale erkannte Sprachqualität, die erforderlich ist, um den Beitrag anzunehmen.';
$a->strings['Number of languages for the language detection'] = 'Anzahl der Sprachen für die Spracherkennung';
$a->strings['The system detects a list of languages per post. Only if the desired languages are in the list, the message will be accepted. The higher the number, the more posts will be falsely detected.'] = 'Das System erkennt eine Liste von Sprachen pro Beitrag. Nur wenn die gewünschten Sprachen in der Liste enthalten sind, wird die Nachricht akzeptiert. Je höher die Zahl, desto mehr Beiträge werden fälschlicherweise erkannt.';
$a->strings['Maximum age of channel'] = 'Maximales Alter des Kanals';
$a->strings['This defines the maximum age in hours of items that should be displayed in channels. This affects the channel performance.'] = 'Hier wird das maximale Alter in Stunden von Beiträgen festgelegt, die in Kanälen angezeigt werden sollen. Dies wirkt sich auf die Leistung der Kanäle aus.';
$a->strings['Maximum number of channel posts'] = 'Maximale Anzahl von Kanalbeiträgen';
$a->strings['For performance reasons, the channels use a dedicated table to store content. The higher the value the slower the channels.'] = 'Aus Leistungsgründen verwenden die Kanäle eine dedizierte Tabelle zur Speicherung von Inhalten. Je höher der Wert, desto langsamer sind die Kanäle.';
$a->strings['Interaction score days'] = 'Interaktionsscore Tage';
$a->strings['Number of days that are used to calculate the interaction score.'] = 'Anzahl der Tage, die zur Berechnung des Interaktionsscores herangezogen werden.';
$a->strings['Maximum number of posts per author'] = 'Maximale Anzahl von Beiträgen pro Autor';
$a->strings['Maximum number of posts per page by author if the contact frequency is set to "Display only few posts". If there are more posts, then the post with the most interactions will be displayed.'] = 'Maximale Anzahl von Beiträgen pro Seite und Autor, wenn die Kontakthäufigkeit auf "Nur wenige Beiträge anzeigen" eingestellt ist. Wenn es mehr Beiträge gibt, wird der Beitrag mit den meisten Interaktionen angezeigt.';
$a->strings['Sharer interaction days'] = 'Interaktionstage für teilende';
$a->strings['Number of days of the last interaction that are used to define which sharers are used for the "sharers of sharers" channel.'] = 'Anzahl der Tage der letzten Interaktion, die verwendet werden, um festzulegen, welche teilenden für den Kanal "Geteilt von teilenden" verwendet werden.';
$a->strings['Start Relocation'] = 'Umsiedlung starten';
$a->strings['Storage backend, %s is invalid.'] = 'Speicher-Backend, %s ist ungültig.';
$a->strings['Storage backend %s error: %s'] = 'Speicher-Backend %s Fehler %s';
$a->strings['Invalid storage backend setting value.'] = 'Ungültige Einstellung für das Datenspeicher-Backend';
$a->strings['Current Storage Backend'] = 'Aktuelles Speicher-Backend';
$a->strings['Storage Configuration'] = 'Speicher Konfiguration';
$a->strings['Storage'] = 'Speicher';
$a->strings['Save & Use storage backend'] = 'Speichern & Dieses Speicher-Backend verwenden';
$a->strings['Use storage backend'] = 'Dieses Speicher-Backend verwenden';
$a->strings['Save & Reload'] = 'Speichern & Neu Laden';
$a->strings['This backend doesn\'t have custom settings'] = 'Dieses Backend hat keine zusätzlichen Einstellungen';
$a->strings['Changing the current backend is prohibited because it is set by an environment variable'] = 'Das Ändern des aktuellen Backends ist nicht möglich, da es durch eine Umgebungsvariable festgelegt ist';
$a->strings['Database (legacy)'] = 'Datenbank (legacy)';
$a->strings['Template engine (%s) error: %s'] = 'Template engine (%s) Fehler: %s';
$a->strings['Your DB still runs with MyISAM tables. You should change the engine type to InnoDB. As Friendica will use InnoDB only features in the future, you should change this! See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = 'Deine DB verwendet derzeit noch MyISAM Tabellen. Du solltest die Datenbank Engine auf InnoDB umstellen, da Friendica in Zukunft InnoDB-Features verwenden wird. Eine Anleitung zur Umstellung der Datenbank kannst du  <a href="%s">hier</a>  finden. Du kannst außerdem mit dem Befehl <tt>php bin/console.php dbstructure toinnodb</tt> auf der Kommandozeile die Umstellung automatisch vornehmen lassen.';
$a->strings['Your DB still runs with InnoDB tables in the Antelope file format. You should change the file format to Barracuda. Friendica is using features that are not provided by the Antelope format. See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = 'Deine DB verwendet derzeit noch InnoDB Tabellen im Antelope Dateiformat. Du solltest diese auf das Barracuda Format ändern. Friendica verwendet einige Features, die nicht vom Antelope Format unterstützt werden. <a href="%s">Hier</a> findest du eine Anleitung für die Umstellung. Alternativ kannst du auch den Befehl <tt>php bin/console.php dbstructure toinnodb</tt> In der Kommandozeile deiner Friendica Instanz verwenden um die Formate automatisch anzupassen.<br />';
$a->strings['Your table_definition_cache is too low (%d). This can lead to the database error "Prepared statement needs to be re-prepared". Please set it at least to %d. See <a href="%s">here</a> for more information.<br />'] = 'Der Wert table_definition_cache ist zu niedrig (%d). Dadurch können Datenbank Fehler "Prepared statement needs to be re-prepared" hervor gerufen werden. Bitte setze den Wert auf mindestens %d. Weiterführende Informationen <a href="%s">findest du hier</a>.';
$a->strings['There is a new version of Friendica available for download. Your current version is %1$s, upstream version is %2$s'] = 'Es gibt eine neue Version von Friendica. Du verwendest derzeit die Version %1$s, die aktuelle Version ist %2$s.';
$a->strings['The database update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear.'] = 'Das Update der Datenbank ist fehlgeschlagen. Bitte führe \'php bin/console.php dbstructure update\' in der Kommandozeile aus und achte auf eventuell auftretende Fehlermeldungen.';
$a->strings['The last update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear. (Some of the errors are possibly inside the logfile.)'] = 'Das letzte Update ist fehlgeschlagen. Bitte führe "php bin/console.php dbstructure update" auf der Kommandozeile aus und werfe einen Blick auf eventuell auftretende Fehler. (Zusätzliche Informationen zu Fehlern könnten in den Logdateien stehen.)';
$a->strings['The system.url entry is missing. This is a low level setting and can lead to unexpected behavior. Please add a valid entry as soon as possible in the config file or per console command!'] = 'Der Eintrag system.url fehlt. Dies ist eine Einstellung auf niedriger Ebene und kann zu unerwartetem Verhalten führen. Bitt füge so bald wie möglich einen gültigen Eintrag in der Konfigurationsdatei oder per Konsolenbefehl hinzu!';
$a->strings['The worker was never executed. Please check your database structure!'] = 'Der Hintergrundprozess (worker) wurde noch nie gestartet. Bitte überprüfe deine Datenbankstruktur.';
$a->strings['The last worker execution was on %s UTC. This is older than one hour. Please check your crontab settings.'] = 'Der Hintergrundprozess (worker) wurde zuletzt um %s UTC ausgeführt. Das war vor mehr als einer Stunde. Bitte überprüfe deine crontab-Einstellungen.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>.htconfig.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'Die Konfiguration von Friendica befindet sich ab jetzt in der \'config/local.config.php\' Datei. Kopiere bitte die Datei  \'config/local-sample.config.php\' nach \'config/local.config.php\' und setze die Konfigurationvariablen so wie in der alten <code>.htconfig.php</code>. Wie die Übertragung der Werte aussehen muss, kannst du der <a href="%s">Konfiguration Hilfeseite</a> entnehmen.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>config/local.ini.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'Die Konfiguration von Friendica befindet sich ab jetzt in der \'config/local.config.php\' Datei. Kopiere bitte die Datei  \'config/local-sample.config.php\' nach \'config/local.config.php\' und setze die Konfigurationvariablen so wie in der alten <code>config/local.ini.php</code>. Wie die Übertragung der Werte aussehen muss, kannst du der <a href="%s">Konfiguration Hilfeseite</a> entnehmen.';
$a->strings['<a href="%s">%s</a> is not reachable on your system. This is a severe configuration issue that prevents server to server communication. See <a href="%s">the installation page</a> for help.'] = '<a href="%s">%s</a> konnte von deinem System nicht aufgerufen werden. Dies deutet auf ein schwerwiegendes Problem deiner Konfiguration hin. Bitte konsultiere <a href="%s">die Installations-Dokumentation</a> zum Beheben des Problems.';
$a->strings['Friendica\'s system.basepath was updated from \'%s\' to \'%s\'. Please remove the system.basepath from your db to avoid differences.'] = 'Friendica\'s system.basepath wurde aktualisiert \'%s\' von \'%s\'. Bitte entferne system.basepath aus der Datenbank um Unterschiede zu vermeiden.';
$a->strings['Friendica\'s current system.basepath \'%s\' is wrong and the config file \'%s\' isn\'t used.'] = 'Friendica\'s aktueller system.basepath \'%s\' ist verkehrt und die config file \'%s\' wird nicht benutzt.';
$a->strings['Friendica\'s current system.basepath \'%s\' is not equal to the config file \'%s\'. Please fix your configuration.'] = 'Friendica\'s aktueller system.basepath \'%s\' ist nicht gleich wie die config file \'%s\'. Bitte korrigiere deine Konfiguration.';
$a->strings['Message queues'] = 'Nachrichten-Warteschlangen';
$a->strings['Server Settings'] = 'Servereinstellungen';
$a->strings['Version'] = 'Version';
$a->strings['Active addons'] = 'Aktivierte Addons';
$a->strings['Theme %s disabled.'] = 'Theme %s deaktiviert.';
$a->strings['Theme %s successfully enabled.'] = 'Theme %s erfolgreich aktiviert.';
$a->strings['Theme %s failed to install.'] = 'Theme %s konnte nicht aktiviert werden.';
$a->strings['Screenshot'] = 'Bildschirmfoto';
$a->strings['Themes'] = 'Themen';
$a->strings['Unknown theme.'] = 'Unbekanntes Theme';
$a->strings['Themes reloaded'] = 'Themes wurden neu geladen';
$a->strings['Reload active themes'] = 'Aktives Theme neu laden';
$a->strings['No themes found on the system. They should be placed in %1$s'] = 'Es wurden keine Themes auf dem System gefunden. Diese sollten in %1$s platziert werden.';
$a->strings['[Experimental]'] = '[Experimentell]';
$a->strings['[Unsupported]'] = '[Nicht unterstützt]';
$a->strings['Display Terms of Service'] = 'Nutzungsbedingungen anzeigen';
$a->strings['Enable the Terms of Service page. If this is enabled a link to the terms will be added to the registration form and the general information page.'] = 'Aktiviert die Seite für die Nutzungsbedingungen. Ist dies der Fall, werden sie auch von der Registrierungsseite und der allgemeinen Informationsseite verlinkt.';
$a->strings['Display Privacy Statement'] = 'Datenschutzerklärung anzeigen';
$a->strings['Show some informations regarding the needed information to operate the node according e.g. to <a href="%s" target="_blank" rel="noopener noreferrer">EU-GDPR</a>.'] = 'Zeige Informationen über die zum Betrieb der Seite notwendigen, personenbezogenen Daten an, wie es z.B. die <a href="%s" target="_blank" rel="noopener noreferrer">EU.-DSGVO</a> verlangt.';
$a->strings['Privacy Statement Preview'] = 'Vorschau: Datenschutzerklärung';
$a->strings['The Terms of Service'] = 'Die Nutzungsbedingungen';
$a->strings['Enter the Terms of Service for your node here. You can use BBCode. Headers of sections should be [h2] and below.'] = 'Füge hier die Nutzungsbedingungen deines Knotens ein. Du kannst BBCode zur Formatierung verwenden. Überschriften sollten [h2] oder darunter sein.';
$a->strings['The rules'] = 'Die Regeln';
$a->strings['Enter your system rules here. Each line represents one rule.'] = 'Gib die Regeln deines Server hier ein. Jede Zeile steht für eine Regel.';
$a->strings['API endpoint %s %s is not implemented but might be in the future.'] = 'API Endpunkt %s %s ist noch nicht implementiert, vielleicht in der Zukunft.';
$a->strings['Missing parameters'] = 'Fehlende Parameter';
$a->strings['Only starting posts can be bookmarked'] = 'Lesezeichen können nur für den ersten Beitrag einer Unterhaltung angelegt werden';
$a->strings['Only starting posts can be muted'] = 'Nur die ersten Beiträge von Unterhaltungen können stumm geschaltet werden';
$a->strings['Posts from %s can\'t be shared'] = 'Beiträge von %s können nicht geteilt werden';
$a->strings['Only starting posts can be unbookmarked'] = 'Lesezeichen können nur für die ersten Beiträge einer Unterhaltung entfernt werden';
$a->strings['Only starting posts can be unmuted'] = 'Nur die ersten Beiträge einer Unterhaltung können wieder auf laut gestellt werden';
$a->strings['Posts from %s can\'t be unshared'] = 'Beiträge von %s können nicht ungeteilt werden';
$a->strings['Contact not found'] = 'Kontakt nicht gefunden';
$a->strings['No installed applications.'] = 'Keine Applikationen installiert.';
$a->strings['Applications'] = 'Anwendungen';
$a->strings['Item was not found.'] = 'Beitrag konnte nicht gefunden werden.';
$a->strings['Please login to continue.'] = 'Bitte melde dich an, um fortzufahren.';
$a->strings['You don\'t have access to administration pages.'] = 'Du hast keinen Zugriff auf die Administrationsseiten.';
$a->strings['Submanaged account can\'t access the administration pages. Please log back in as the main account.'] = 'Verwaltete Benutzerkonten haben keinen Zugriff auf die Administrationsseiten. Bitte wechsle wieder zurück auf das Administrator Konto.';
$a->strings['Overview'] = 'Übersicht';
$a->strings['Configuration'] = 'Konfiguration';
$a->strings['Additional features'] = 'Zusätzliche Features';
$a->strings['Database'] = 'Datenbank';
$a->strings['DB updates'] = 'DB Updates';
$a->strings['Inspect Deferred Workers'] = 'Verzögerte Worker inspizieren';
$a->strings['Inspect worker Queue'] = 'Worker Warteschlange inspizieren';
$a->strings['Diagnostics'] = 'Diagnostik';
$a->strings['PHP Info'] = 'PHP-Info';
$a->strings['probe address'] = 'Adresse untersuchen';
$a->strings['check webfinger'] = 'Webfinger überprüfen';
$a->strings['Babel'] = 'Babel';
$a->strings['ActivityPub Conversion'] = 'Umwandlung nach ActivityPub';
$a->strings['Addon Features'] = 'Addon Features';
$a->strings['User registrations waiting for confirmation'] = 'Nutzeranmeldungen, die auf Bestätigung warten';
$a->strings['Too Many Requests'] = 'Zu viele Abfragen';
$a->strings['Daily posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Das tägliche Limit von %d Beitrag wurde erreicht. Die Nachricht wurde verworfen.',
	1 => 'Das tägliche Limit von %d Beiträgen wurde erreicht. Der Beitrag wurde verworfen.',
];
$a->strings['Weekly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Das wöchentliche Limit von %d Beitrag wurde erreicht. Die Nachricht wurde verworfen.',
	1 => 'Das wöchentliche Limit von %d Beiträgen wurde erreicht. Der Beitrag wurde verworfen.',
];
$a->strings['Monthly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Das monatliche Limit von %d Beitrag wurde erreicht. Der Beitrag wurde verworfen.',
	1 => 'Das monatliche Limit von %d Beiträgen wurde erreicht. Der Beitrag wurde verworfen.',
];
$a->strings['You don\'t have access to moderation pages.'] = 'Du hast keinen Zugriff zu den Moderationsseiten.';
$a->strings['Submanaged account can\'t access the moderation pages. Please log back in as the main account.'] = 'Das verwaltete Konto kann nicht auf die Moderationsseiten zugreifen. Bitte melde dich wieder mit dem Hauptkonto an.';
$a->strings['Reports'] = 'Reports';
$a->strings['Users'] = 'Nutzer';
$a->strings['Tools'] = 'Werkzeuge';
$a->strings['Contact Blocklist'] = 'Kontakt Blockliste';
$a->strings['Server Blocklist'] = 'Server Blockliste';
$a->strings['Delete Item'] = 'Eintrag löschen';
$a->strings['Item Source'] = 'Beitrags Quelle';
$a->strings['Profile Details'] = 'Profildetails';
$a->strings['Conversations started'] = 'Begonnene Unterhaltungen';
$a->strings['Only You Can See This'] = 'Nur du kannst das sehen';
$a->strings['Scheduled Posts'] = 'Geplante Beiträge';
$a->strings['Posts that are scheduled for publishing'] = 'Beiträge die für einen späteren Zeitpunkt für die Veröffentlichung geplant sind';
$a->strings['Tips for New Members'] = 'Tipps für neue Nutzer';
$a->strings['More'] = 'Mehr';
$a->strings['People Search - %s'] = 'Personensuche - %s';
$a->strings['Group Search - %s'] = 'Gruppensuche - %s';
$a->strings['No matches'] = 'Keine Übereinstimmungen';
$a->strings['%d result was filtered out because your node blocks the domain it is registered on. You can review the list of domains your node is currently blocking in the <a href="/friendica">About page</a>.'] = [
	0 => '%d Ergebnis wurde herausgefiltert, weil Ihr Knoten die Domäne blockiert, auf der das Ergebnis registriert ist. Sie können die Liste aller Domänen, die Ihr Knoten derzeit blockiert, auf der <a href="/friendica">Info-Seite</a> einsehen.',
	1 => '%d Ergebnisse wurden herausgefiltert, weil Ihr Knoten die Domäne blockiert, auf der die Ergebnisse registriert sind. Du kannst die Liste aller Domänen, die Ihr Knoten derzeit blockiert, auf der<a href="/friendica">Info-Seite</a> einsehen.',
];
$a->strings['Account'] = 'Nutzerkonto';
$a->strings['Two-factor authentication'] = 'Zwei-Faktor Authentifizierung';
$a->strings['Display'] = 'Anzeige';
$a->strings['Social Networks'] = 'Soziale Netzwerke';
$a->strings['Manage Accounts'] = 'Accounts Verwalten';
$a->strings['Connected apps'] = 'Verbundene Programme';
$a->strings['Remote servers'] = 'Remote Instanzen';
$a->strings['Import Contacts'] = 'Kontakte Importieren';
$a->strings['Export personal data'] = 'Persönliche Daten exportieren';
$a->strings['Remove account'] = 'Konto löschen';
$a->strings['This page is missing a url parameter.'] = 'Der Seite fehlt ein URL Parameter.';
$a->strings['The post was created'] = 'Der Beitrag wurde angelegt';
$a->strings['Invalid Request'] = 'Ungültige Anfrage';
$a->strings['Event id is missing.'] = 'Die Veranstaltung fehlt.';
$a->strings['Failed to remove event'] = 'Entfernen der Veranstaltung fehlgeschlagen';
$a->strings['Event can not end before it has started.'] = 'Die Veranstaltung kann nicht enden, bevor sie beginnt.';
$a->strings['Event title and start time are required.'] = 'Der Veranstaltungstitel und die Anfangszeit müssen angegeben werden.';
$a->strings['Starting date and Title are required.'] = 'Anfangszeitpunkt und Titel werden benötigt';
$a->strings['Event Starts:'] = 'Veranstaltungsbeginn:';
$a->strings['Required'] = 'Benötigt';
$a->strings['Finish date/time is not known or not relevant'] = 'Enddatum/-zeit ist nicht bekannt oder nicht relevant';
$a->strings['Event Finishes:'] = 'Veranstaltungsende:';
$a->strings['Title (BBCode not allowed)'] = 'Titel (BBCode nicht erlaubt)';
$a->strings['Description (BBCode allowed)'] = 'Beschreibung (BBCode erlaubt)';
$a->strings['Location (BBCode not allowed)'] = 'Ort (BBCode nicht erlaubt)';
$a->strings['Share this event'] = 'Veranstaltung teilen';
$a->strings['Basic'] = 'Allgemein';
$a->strings['This calendar format is not supported'] = 'Dieses Kalenderformat wird nicht unterstützt.';
$a->strings['No exportable data found'] = 'Keine exportierbaren Daten gefunden';
$a->strings['calendar'] = 'Kalender';
$a->strings['Events'] = 'Veranstaltungen';
$a->strings['View'] = 'Ansehen';
$a->strings['Create New Event'] = 'Neue Veranstaltung erstellen';
$a->strings['list'] = 'Liste';
$a->strings['Could not create circle.'] = 'Der Circle konnte nicht erstellt werden.';
$a->strings['Circle not found.'] = 'Circle nicht gefunden.';
$a->strings['Circle name was not changed.'] = 'Der Name des Circles wurde nicht geändert.';
$a->strings['Unknown circle.'] = 'Unbekannter Circle.';
$a->strings['Contact not found.'] = 'Kontakt nicht gefunden.';
$a->strings['Invalid contact.'] = 'Ungültiger Kontakt.';
$a->strings['Contact is deleted.'] = 'Kontakt wurde gelöscht';
$a->strings['Unable to add the contact to the circle.'] = 'Es ist nicht möglich, den Kontakt zum Circle hinzuzufügen.';
$a->strings['Contact successfully added to circle.'] = 'Der Kontakt wurde erfolgreich dem Circle hinzugefügt.';
$a->strings['Unable to remove the contact from the circle.'] = 'Es ist nicht möglich, den Kontakt aus dem Circle zu entfernen.';
$a->strings['Contact successfully removed from circle.'] = 'Kontakt erfolgreich aus dem Circle entfernt.';
$a->strings['Bad request.'] = 'Ungültige Anfrage.';
$a->strings['Save Circle'] = 'Circle speichern';
$a->strings['Filter'] = 'Filter';
$a->strings['Create a circle of contacts/friends.'] = 'Erstelle einen Circle aus Kontakten/Freunden';
$a->strings['Unable to remove circle.'] = 'Der Circle kann nicht entfernt werden.';
$a->strings['Delete Circle'] = 'Circle löschen';
$a->strings['Edit Circle Name'] = 'Name des Circles ändern';
$a->strings['Members'] = 'Mitglieder';
$a->strings['Circle is empty'] = 'Dieser Circle ist leer';
$a->strings['Remove contact from circle'] = 'Kontakt aus Circle entfernen';
$a->strings['Click on a contact to add or remove.'] = 'Klicke einen Kontakt an, um ihn hinzuzufügen oder zu entfernen';
$a->strings['Add contact to circle'] = 'Kontakt zu Circle hinzufügen';
$a->strings['%d contact edited.'] = [
	0 => '%d Kontakt bearbeitet.',
	1 => '%d Kontakte bearbeitet.',
];
$a->strings['Show all contacts'] = 'Alle Kontakte anzeigen';
$a->strings['Pending'] = 'Ausstehend';
$a->strings['Only show pending contacts'] = 'Zeige nur noch ausstehende Kontakte.';
$a->strings['Blocked'] = 'Geblockt';
$a->strings['Only show blocked contacts'] = 'Nur blockierte Kontakte anzeigen';
$a->strings['Ignored'] = 'Ignoriert';
$a->strings['Only show ignored contacts'] = 'Nur ignorierte Kontakte anzeigen';
$a->strings['Collapsed'] = 'Zugeklappt';
$a->strings['Only show collapsed contacts'] = 'Zeige nur zugeklappte Kontakte';
$a->strings['Archived'] = 'Archiviert';
$a->strings['Only show archived contacts'] = 'Nur archivierte Kontakte anzeigen';
$a->strings['Hidden'] = 'Verborgen';
$a->strings['Only show hidden contacts'] = 'Nur verborgene Kontakte anzeigen';
$a->strings['Organize your contact circles'] = 'Verwalte Deine Circles';
$a->strings['Search your contacts'] = 'Suche in deinen Kontakten';
$a->strings['Results for: %s'] = 'Ergebnisse für: %s';
$a->strings['Update'] = 'Aktualisierungen';
$a->strings['Unblock'] = 'Entsperren';
$a->strings['Unignore'] = 'Ignorieren aufheben';
$a->strings['Uncollapse'] = 'Aufklappen';
$a->strings['Batch Actions'] = 'Stapelverarbeitung';
$a->strings['Conversations started by this contact'] = 'Unterhaltungen, die von diesem Kontakt begonnen wurden';
$a->strings['Posts and Comments'] = 'Statusnachrichten und Kommentare';
$a->strings['Individual Posts and Replies'] = 'Individuelle Beiträge und Antworten';
$a->strings['Posts containing media objects'] = 'Beiträge die Medien Objekte beinhalten';
$a->strings['View all known contacts'] = 'Alle bekannten Kontakte anzeigen';
$a->strings['Advanced Contact Settings'] = 'Fortgeschrittene Kontakteinstellungen';
$a->strings['Mutual Friendship'] = 'Beidseitige Freundschaft';
$a->strings['is a fan of yours'] = 'ist ein Fan von dir';
$a->strings['you are a fan of'] = 'Du bist Fan von';
$a->strings['Pending outgoing contact request'] = 'Ausstehende ausgehende Kontaktanfrage';
$a->strings['Pending incoming contact request'] = 'Ausstehende eingehende Kontaktanfrage';
$a->strings['Visit %s\'s profile [%s]'] = 'Besuche %ss Profil [%s]';
$a->strings['Contact update failed.'] = 'Konnte den Kontakt nicht aktualisieren.';
$a->strings['Return to contact editor'] = 'Zurück zum Kontakteditor';
$a->strings['Name'] = 'Name';
$a->strings['Account Nickname'] = 'Konto-Spitzname';
$a->strings['Account URL'] = 'Konto-URL';
$a->strings['Poll/Feed URL'] = 'Pull/Feed-URL';
$a->strings['New photo from this URL'] = 'Neues Foto von dieser URL';
$a->strings['No known contacts.'] = 'Keine bekannten Kontakte.';
$a->strings['No common contacts.'] = 'Keine gemeinsamen Kontakte.';
$a->strings['Follower (%s)'] = [
	0 => 'Folgende (%s)',
	1 => 'Folgende (%s)',
];
$a->strings['Following (%s)'] = [
	0 => 'Gefolgte (%s)',
	1 => 'Gefolgte (%s)',
];
$a->strings['Mutual friend (%s)'] = [
	0 => 'Beidseitige Freundschafte (%s)',
	1 => 'Beidseitige Freundschaften (%s)',
];
$a->strings['These contacts both follow and are followed by <strong>%s</strong>.'] = 'Diese Kontakte sind sowohl Folgende als auch Gefolgte von <strong>%s</strong>.';
$a->strings['Common contact (%s)'] = [
	0 => 'Gemeinsamer Kontakt (%s)',
	1 => 'Gemeinsame Kontakte (%s)',
];
$a->strings['Both <strong>%s</strong> and yourself have publicly interacted with these contacts (follow, comment or likes on public posts).'] = 'Du und <strong>%s</strong> haben mit diesen Kontakten öffentlich interagiert (Folgen, Kommentare und Likes in öffentlichen Beiträgen)';
$a->strings['Contact (%s)'] = [
	0 => 'Kontakt (%s)',
	1 => 'Kontakte (%s)',
];
$a->strings['Access denied.'] = 'Zugriff verweigert.';
$a->strings['Submit Request'] = 'Anfrage abschicken';
$a->strings['You already added this contact.'] = 'Du hast den Kontakt bereits hinzugefügt.';
$a->strings['The network type couldn\'t be detected. Contact can\'t be added.'] = 'Der Netzwerktyp wurde nicht erkannt. Der Kontakt kann nicht hinzugefügt werden.';
$a->strings['Diaspora support isn\'t enabled. Contact can\'t be added.'] = 'Diaspora-Unterstützung ist nicht aktiviert. Der Kontakt kann nicht zugefügt werden.';
$a->strings['Please answer the following:'] = 'Bitte beantworte folgendes:';
$a->strings['Your Identity Address:'] = 'Adresse Deines Profils:';
$a->strings['Profile URL'] = 'Profil URL';
$a->strings['Tags:'] = 'Tags:';
$a->strings['%s knows you'] = '%skennt dich';
$a->strings['Add a personal note:'] = 'Eine persönliche Notiz beifügen:';
$a->strings['Posts and Replies'] = 'Beiträge und Antworten';
$a->strings['The contact could not be added.'] = 'Der Kontakt konnte nicht hinzugefügt werden.';
$a->strings['Invalid request.'] = 'Ungültige Anfrage';
$a->strings['No keywords to match. Please add keywords to your profile.'] = 'Keine Schlüsselwörter zum Abgleichen gefunden. Bitte füge einige Schlüsselwörter zu deinem Profil hinzu.';
$a->strings['Profile Match'] = 'Profilübereinstimmungen';
$a->strings['Failed to update contact record.'] = 'Aktualisierung der Kontaktdaten fehlgeschlagen.';
$a->strings['Contact has been unblocked'] = 'Kontakt wurde wieder freigegeben';
$a->strings['Contact has been blocked'] = 'Kontakt wurde blockiert';
$a->strings['Contact has been unignored'] = 'Kontakt wird nicht mehr ignoriert';
$a->strings['Contact has been ignored'] = 'Kontakt wurde ignoriert';
$a->strings['Contact has been uncollapsed'] = 'Kontakt wurde aufgeklappt';
$a->strings['Contact has been collapsed'] = 'Kontakt wurde zugeklappt';
$a->strings['You are mutual friends with %s'] = 'Du hast mit %s eine beidseitige Freundschaft';
$a->strings['You are sharing with %s'] = 'Du teilst mit %s';
$a->strings['%s is sharing with you'] = '%s teilt mit dir';
$a->strings['Private communications are not available for this contact.'] = 'Private Kommunikation ist für diesen Kontakt nicht verfügbar.';
$a->strings['This contact is on a server you ignored.'] = 'Dieser Kontakt befindet sich auf einem Server, den du ignoriert hast.';
$a->strings['Never'] = 'Niemals';
$a->strings['(Update was not successful)'] = '(Aktualisierung war nicht erfolgreich)';
$a->strings['(Update was successful)'] = '(Aktualisierung war erfolgreich)';
$a->strings['Suggest friends'] = 'Kontakte vorschlagen';
$a->strings['Network type: %s'] = 'Netzwerktyp: %s';
$a->strings['Communications lost with this contact!'] = 'Verbindungen mit diesem Kontakt verloren!';
$a->strings['Fetch further information for feeds'] = 'Weitere Informationen zu Feeds holen';
$a->strings['Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.'] = 'Zusätzliche Informationen wie Vorschaubilder, Titel und Zusammenfassungen vom Feed-Eintrag laden. Du kannst diese Option aktivieren, wenn der Feed nicht allzu viel Text beinhaltet. Schlagwörter werden aus den Meta-Informationen des Feed-Headers bezogen und als Hash-Tags verwendet.';
$a->strings['Fetch information'] = 'Beziehe Information';
$a->strings['Fetch keywords'] = 'Schlüsselwörter abrufen';
$a->strings['Fetch information and keywords'] = 'Beziehe Information und Schlüsselworte';
$a->strings['No mirroring'] = 'Kein Spiegeln';
$a->strings['Mirror as my own posting'] = 'Spiegeln als meine eigenen Beiträge';
$a->strings['Native reshare'] = 'Natives Teilen';
$a->strings['Contact Information / Notes'] = 'Kontakt-Informationen / -Notizen';
$a->strings['Contact Settings'] = 'Kontakteinstellungen';
$a->strings['Contact'] = 'Kontakt';
$a->strings['Their personal note'] = 'Die persönliche Mitteilung';
$a->strings['Edit contact notes'] = 'Notizen zum Kontakt bearbeiten';
$a->strings['Block/Unblock contact'] = 'Kontakt blockieren/freischalten';
$a->strings['Ignore contact'] = 'Ignoriere den Kontakt';
$a->strings['View conversations'] = 'Unterhaltungen anzeigen';
$a->strings['Last update:'] = 'Letzte Aktualisierung: ';
$a->strings['Update public posts'] = 'Öffentliche Beiträge aktualisieren';
$a->strings['Update now'] = 'Jetzt aktualisieren';
$a->strings['Awaiting connection acknowledge'] = 'Bedarf der Bestätigung des Kontakts';
$a->strings['Currently blocked'] = 'Derzeit geblockt';
$a->strings['Currently ignored'] = 'Derzeit ignoriert';
$a->strings['Currently collapsed'] = 'Derzeit zugeklappt';
$a->strings['Currently archived'] = 'Momentan archiviert';
$a->strings['Manage remote servers'] = 'Verwaltung entfernter Instanzen';
$a->strings['Hide this contact from others'] = 'Verbirg diesen Kontakt vor Anderen';
$a->strings['Replies/likes to your public posts <strong>may</strong> still be visible'] = 'Antworten/Likes auf deine öffentlichen Beiträge <strong>könnten</strong> weiterhin sichtbar sein';
$a->strings['Notification for new posts'] = 'Benachrichtigung bei neuen Beiträgen';
$a->strings['Send a notification of every new post of this contact'] = 'Sende eine Benachrichtigung, wann immer dieser Kontakt einen neuen Beitrag schreibt.';
$a->strings['Keyword Deny List'] = 'Liste der gesperrten Schlüsselwörter';
$a->strings['Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected'] = 'Komma-Separierte Liste mit Schlüsselworten, die nicht in Hashtags konvertiert werden, wenn "Beziehe Information und Schlüsselworte" aktiviert wurde';
$a->strings['Actions'] = 'Aktionen';
$a->strings['Status'] = 'Status';
$a->strings['Mirror postings from this contact'] = 'Spiegle Beiträge dieses Kontakts';
$a->strings['Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'] = 'Markiere diesen Kontakt als remote_self (entferntes Konto), dies veranlasst Friendica, alle Top-Level Beiträge dieses Kontakts an all Deine Kontakte zu senden (spiegeln).';
$a->strings['Channel Settings'] = 'Kanal Einstellungen';
$a->strings['Frequency of this contact in relevant channels'] = 'Häufigkeit dieses Kontakts in relevanten Kanälen';
$a->strings['Depending on the type of the channel not all posts from this contact are displayed. By default, posts need to have a minimum amount of interactions (comments, likes) to show in your channels. On the other hand there can be contacts who flood the channel, so you might want to see only some of their posts. Or you don\'t want to see their content at all, but you don\'t want to block or hide the contact completely.'] = 'Je nach Art des Kanals werden nicht alle Beiträge dieses Kontakts angezeigt. Standardmäßig müssen Beiträge eine Mindestanzahl an Interaktionen (Kommentare, Gefällt mir Angaben) aufweisen, um in Ihren Kanälen angezeigt zu werden. Andererseits kann es Kontakte geben, die den Kanal überfluten, so dass du vielleicht nur einige ihrer Beiträge sehen möchtest. Oder du willst deren Inhalte überhaupt nicht sehen, aber du willst den Kontakt nicht komplett blockieren oder ausblenden.';
$a->strings['Default frequency'] = 'Standardhäufigkeit';
$a->strings['Posts by this contact are displayed in the "for you" channel if you interact often with this contact or if a post reached some level of interaction.'] = 'Beiträge dieses Kontakts werden im "Für Dich"-Kanal angezeigt, wenn du häufig mit diesem Kontakt interagieren oder wenn ein Beitrag ein gewisses Maß an Interaktion erreicht hat.';
$a->strings['Display all posts of this contact'] = 'Alle Beiträge dieses Kontakts anzeigen';
$a->strings['All posts from this contact will appear on the "for you" channel'] = 'Alle Beiträge dieses Kontakts werden auf dem Kanal "Für Dich" erscheinen';
$a->strings['Display only few posts'] = 'Zeige nur einige Beiträge an';
$a->strings['When a contact creates a lot of posts in a short period, this setting reduces the number of displayed posts in every channel.'] = 'Wenn ein Kontakt viele Beiträge in einem kurzen Zeitraum erstellt, reduziert diese Einstellung die Anzahl der angezeigten Beiträge in jedem Kanal.';
$a->strings['Never display posts'] = 'Zeige keine Beiträge an';
$a->strings['Posts from this contact will never be displayed in any channel'] = 'Beiträge von diesem Kontakt werden in keinem Kanal angezeigt';
$a->strings['Channel Only'] = 'Ausschließlich Kanäle';
$a->strings['If enabled, posts from this contact will only appear in channels and network streams in circles, but not in the general network stream.'] = 'Ist dies aktiviert, werden Beiträge dieses Kontakt nur in Kanälen und Circles angezeigt werden, aber nicht im allgemeinen Netzwerk Stream.';
$a->strings['Refetch contact data'] = 'Kontaktdaten neu laden';
$a->strings['Toggle Blocked status'] = 'Geblockt-Status ein-/ausschalten';
$a->strings['Toggle Ignored status'] = 'Ignoriert-Status ein-/ausschalten';
$a->strings['Toggle Collapsed status'] = 'Status auf "Zusammengeklappt" umschalten';
$a->strings['Revoke Follow'] = 'Folgen widerrufen';
$a->strings['Revoke the follow from this contact'] = 'Widerruft das Folgen dieses Kontaktes';
$a->strings['Bad Request.'] = 'Ungültige Anfrage.';
$a->strings['Contact is being deleted.'] = 'Kontakt wurde gelöscht.';
$a->strings['Follow was successfully revoked.'] = 'Folgen wurde erfolgreich widerrufen.';
$a->strings['Do you really want to revoke this contact\'s follow? This cannot be undone and they will have to manually follow you back again.'] = 'Willst du das Folgen dieses Kontakt wirklich widerrufen? Dies kann nicht rückgängig gemacht werden und der Kontakt muss Ihnen manuell wieder folgen.';
$a->strings['No suggestions available. If this is a new site, please try again in 24 hours.'] = 'Keine Vorschläge verfügbar. Falls der Server frisch aufgesetzt wurde, versuche es bitte in 24 Stunden noch einmal.';
$a->strings['You aren\'t following this contact.'] = 'Du folgst diesem Kontakt.';
$a->strings['Unfollowing is currently not supported by your network.'] = 'Bei diesem Netzwerk wird das Entfolgen derzeit nicht unterstützt.';
$a->strings['Disconnect/Unfollow'] = 'Verbindung lösen/Nicht mehr folgen';
$a->strings['Contact was successfully unfollowed'] = 'Kontakt wurde erfolgreich entfolgt.';
$a->strings['Unable to unfollow this contact, please contact your administrator'] = 'Konnte dem Kontakt nicht entfolgen. Bitte kontaktiere deinen Administrator.';
$a->strings['No results.'] = 'Keine Ergebnisse.';
$a->strings['Channel not available.'] = 'Channel nicht verüfgbar';
$a->strings['This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.'] = 'Diese Gemeinschaftsseite zeigt alle öffentlichen Beiträge, die auf diesem Knoten eingegangen sind. Der Inhalt entspricht nicht zwingend der Meinung der Nutzer dieses Servers.';
$a->strings['Community option not available.'] = 'Optionen für die Gemeinschaftsseite nicht verfügbar.';
$a->strings['Not available.'] = 'Nicht verfügbar.';
$a->strings['No such circle'] = 'Circle ist nicht vorhanden';
$a->strings['Circle: %s'] = 'Circle: %s';
$a->strings['Error %d (%s) while fetching the timeline.'] = 'Fehler %d (%s) beim Abruf der Timeline.';
$a->strings['Network feed not available.'] = 'Netzwerkfeed nicht verfügbar.';
$a->strings['Include'] = 'Einschließen';
$a->strings['Hide'] = 'Verbergen';
$a->strings['Credits'] = 'Credits';
$a->strings['Friendica is a community project, that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!'] = 'Friendica ist ein Gemeinschaftsprojekt, das nicht ohne die Hilfe vieler Personen möglich wäre. Hier ist eine Aufzählung der Personen, die zum Code oder der Übersetzung beigetragen haben. Dank an alle !';
$a->strings['Formatted'] = 'Formatiert';
$a->strings['Activity'] = 'Aktivität';
$a->strings['Object data'] = 'Objekt Daten';
$a->strings['Result Item'] = 'Resultierender Eintrag';
$a->strings['Error'] = [
	0 => 'Fehler',
	1 => 'Fehler',
];
$a->strings['Source activity'] = 'Quelle der Aktivität';
$a->strings['Source input'] = 'Originaltext:';
$a->strings['BBCode::toPlaintext'] = 'BBCode::toPlaintext';
$a->strings['BBCode::convert (raw HTML)'] = 'BBCode::convert (pures HTML)';
$a->strings['BBCode::convert (hex)'] = 'BBCode::convert (hex)';
$a->strings['BBCode::convert'] = 'BBCode::convert';
$a->strings['BBCode::convert => HTML::toBBCode'] = 'BBCode::convert => HTML::toBBCode';
$a->strings['BBCode::toMarkdown'] = 'BBCode::toMarkdown';
$a->strings['BBCode::toMarkdown => Markdown::convert (raw HTML)'] = 'BBCode::toMarkdown => Markdown::convert (rohes HTML)';
$a->strings['BBCode::toMarkdown => Markdown::convert'] = 'BBCode::toMarkdown => Markdown::convert';
$a->strings['BBCode::toMarkdown => Markdown::toBBCode'] = 'BBCode::toMarkdown => Markdown::toBBCode';
$a->strings['BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode'] = 'BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode';
$a->strings['Item Body'] = 'Beitragskörper';
$a->strings['Item Tags'] = 'Tags des Beitrags';
$a->strings['PageInfo::appendToBody'] = 'PageInfo::appendToBody';
$a->strings['PageInfo::appendToBody => BBCode::convert (raw HTML)'] = 'PageInfo::appendToBody => BBCode::convert (pures HTML)';
$a->strings['PageInfo::appendToBody => BBCode::convert'] = 'PageInfo::appendToBody => BBCode::convert';
$a->strings['Source input (Diaspora format)'] = 'Originaltext (Diaspora Format): ';
$a->strings['Source input (Markdown)'] = 'Originaltext (Markdown)';
$a->strings['Markdown::convert (raw HTML)'] = 'Markdown::convert (pures HTML)';
$a->strings['Markdown::convert'] = 'Markdown::convert';
$a->strings['Markdown::toBBCode'] = 'Markdown::toBBCode';
$a->strings['Raw HTML input'] = 'Reine  HTML  Eingabe';
$a->strings['HTML Input'] = 'HTML Eingabe';
$a->strings['HTML Purified (raw)'] = 'HTML Purified (raw)';
$a->strings['HTML Purified (hex)'] = 'HTML Purified (hex)';
$a->strings['HTML Purified'] = 'HTML Purified';
$a->strings['HTML::toBBCode'] = 'HTML::toBBCode';
$a->strings['HTML::toBBCode => BBCode::convert'] = 'HTML::toBBCode => BBCode::convert';
$a->strings['HTML::toBBCode => BBCode::convert (raw HTML)'] = 'HTML::toBBCode => BBCode::convert (pures HTML)';
$a->strings['HTML::toBBCode => BBCode::toPlaintext'] = 'HTML::toBBCode => BBCode::toPlaintext';
$a->strings['HTML::toMarkdown'] = 'HTML::toMarkdown';
$a->strings['HTML::toPlaintext'] = 'HTML::toPlaintext';
$a->strings['HTML::toPlaintext (compact)'] = 'HTML::toPlaintext (kompakt)';
$a->strings['Decoded post'] = 'Dekodierter Beitrag';
$a->strings['Post array before expand entities'] = 'Beiträgs Array bevor die Entitäten erweitert wurden.';
$a->strings['Babel Diagnostic'] = 'Babel Diagnostik';
$a->strings['Source text'] = 'Quelltext';
$a->strings['BBCode'] = 'BBCode';
$a->strings['Markdown'] = 'Markdown';
$a->strings['HTML'] = 'HTML';
$a->strings['Twitter Source / Tweet URL (requires API key)'] = 'Twitter Quelle / Tweet URL (benötigt API Schlüssel)';
$a->strings['You must be logged in to use this module'] = 'Du musst eingeloggt sein, um dieses Modul benutzen zu können.';
$a->strings['Source URL'] = 'URL der Quelle';
$a->strings['Time Conversion'] = 'Zeitumrechnung';
$a->strings['Friendica provides this service for sharing events with other networks and friends in unknown timezones.'] = 'Friendica bietet diese Funktion an, um das Teilen von Events mit Kontakten zu vereinfachen, deren Zeitzone nicht ermittelt werden kann.';
$a->strings['UTC time: %s'] = 'UTC Zeit: %s';
$a->strings['Current timezone: %s'] = 'Aktuelle Zeitzone: %s';
$a->strings['Converted localtime: %s'] = 'Umgerechnete lokale Zeit: %s';
$a->strings['Please select your timezone:'] = 'Bitte wähle Deine Zeitzone:';
$a->strings['Only logged in users are permitted to perform a probing.'] = 'Nur eingeloggten Benutzern ist das Untersuchen von Adressen gestattet.';
$a->strings['Probe Diagnostic'] = 'Probe Diagnostik';
$a->strings['Output'] = 'Ergebnis';
$a->strings['Lookup address'] = 'Adresse nachschlagen';
$a->strings['Webfinger Diagnostic'] = 'Webfinger Diagnostik';
$a->strings['Lookup address:'] = 'Adresse nachschlagen:';
$a->strings['No entries (some entries may be hidden).'] = 'Keine Einträge (einige Einträge könnten versteckt sein).';
$a->strings['Find on this site'] = 'Auf diesem Server suchen';
$a->strings['Results for:'] = 'Ergebnisse für:';
$a->strings['Site Directory'] = 'Verzeichnis';
$a->strings['Item was not deleted'] = 'Item wurde nicht gelöscht';
$a->strings['Item was not removed'] = 'Item wurde nicht entfernt';
$a->strings['Folder:'] = 'Ordner:';
$a->strings['- select -'] = '- auswählen -';
$a->strings['Suggested contact not found.'] = 'Vorgeschlagener Kontakt wurde nicht gefunden.';
$a->strings['Friend suggestion sent.'] = 'Kontaktvorschlag gesendet.';
$a->strings['Suggest Friends'] = 'Kontakte vorschlagen';
$a->strings['Suggest a friend for %s'] = 'Schlage %s einen Kontakt vor';
$a->strings['Installed addons/apps:'] = 'Installierte Apps und Addons';
$a->strings['No installed addons/apps'] = 'Es sind keine Addons oder Apps installiert';
$a->strings['Read about the <a href="%1$s/tos">Terms of Service</a> of this node.'] = 'Erfahre mehr über die <a href="%1$s/tos">Nutzungsbedingungen</a> dieses Knotens.';
$a->strings['On this server the following remote servers are blocked.'] = 'Auf diesem Server werden die folgenden, entfernten Server blockiert.';
$a->strings['Reason for the block'] = 'Begründung für die Blockierung';
$a->strings['Download this list in CSV format'] = 'Liste im CSV-Format herunterladen';
$a->strings['This is Friendica, version %s that is running at the web location %s. The database version is %s, the post update version is %s.'] = 'Diese Friendica-Instanz verwendet die Version %s, sie ist unter der folgenden Adresse im Web zu finden %s. Die Datenbankversion ist %s und die Post-Update-Version %s.';
$a->strings['Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.'] = 'Bitte besuche <a href="https://friendi.ca">Friendi.ca</a>, um mehr über das Friendica-Projekt zu erfahren.';
$a->strings['Bug reports and issues: please visit'] = 'Probleme oder Fehler gefunden? Bitte besuche';
$a->strings['the bugtracker at github'] = 'den Bugtracker auf github';
$a->strings['Suggestions, praise, etc. - please email "info" at "friendi - dot - ca'] = 'Vorschläge, Lob usw.: E-Mail an "Info" at "Friendi - dot ca"';
$a->strings['No profile'] = 'Kein Profil';
$a->strings['Method Not Allowed.'] = 'Methode nicht erlaubt.';
$a->strings['Help:'] = 'Hilfe:';
$a->strings['Welcome to %s'] = 'Willkommen zu %s';
$a->strings['Friendica Communications Server - Setup'] = 'Friendica Komunikationsserver - Installation';
$a->strings['System check'] = 'Systemtest';
$a->strings['Requirement not satisfied'] = 'Anforderung ist nicht erfüllt';
$a->strings['Optional requirement not satisfied'] = 'Optionale Anforderung ist nicht erfüllt';
$a->strings['OK'] = 'Ok';
$a->strings['Next'] = 'Nächste';
$a->strings['Check again'] = 'Noch einmal testen';
$a->strings['Base settings'] = 'Grundeinstellungen';
$a->strings['Base path to installation'] = 'Basis-Pfad zur Installation';
$a->strings['If the system cannot detect the correct path to your installation, enter the correct path here. This setting should only be set if you are using a restricted system and symbolic links to your webroot.'] = 'Falls das System nicht den korrekten Pfad zu deiner Installation gefunden hat, gib den richtigen Pfad bitte hier ein. Du solltest hier den Pfad nur auf einem eingeschränkten System angeben müssen, bei dem du mit symbolischen Links auf dein Webverzeichnis verweist.';
$a->strings['The Friendica system URL'] = 'Die Friendica System URL';
$a->strings['Overwrite this field in case the system URL determination isn\'t right, otherwise leave it as is.'] = 'Überschreibe dieses Feld, falls die System-URL-Erkennung nicht korrekt ist, ansonsten lasse es unverändert.';
$a->strings['Database connection'] = 'Datenbankverbindung';
$a->strings['In order to install Friendica we need to know how to connect to your database.'] = 'Um Friendica installieren zu können, müssen wir wissen, wie wir mit Deiner Datenbank Kontakt aufnehmen können.';
$a->strings['Please contact your hosting provider or site administrator if you have questions about these settings.'] = 'Bitte kontaktiere den Hosting-Provider oder den Administrator der Seite, falls du Fragen zu diesen Einstellungen haben solltest.';
$a->strings['The database you specify below should already exist. If it does not, please create it before continuing.'] = 'Die Datenbank, die du unten angibst, sollte bereits existieren. Ist dies noch nicht der Fall, erzeuge sie bitte, bevor du mit der Installation fortfährst.';
$a->strings['Database Server Name'] = 'Datenbank-Server';
$a->strings['Database Login Name'] = 'Datenbank-Nutzer';
$a->strings['Database Login Password'] = 'Datenbank-Passwort';
$a->strings['For security reasons the password must not be empty'] = 'Aus Sicherheitsgründen darf das Passwort nicht leer sein.';
$a->strings['Database Name'] = 'Datenbank-Name';
$a->strings['Please select a default timezone for your website'] = 'Bitte wähle die Standardzeitzone Deiner Webseite';
$a->strings['Site settings'] = 'Server-Einstellungen';
$a->strings['Site administrator email address'] = 'E-Mail-Adresse des Administrators';
$a->strings['Your account email address must match this in order to use the web admin panel.'] = 'Die E-Mail-Adresse, die in Deinem Friendica-Account eingetragen ist, muss mit dieser Adresse übereinstimmen, damit du das Admin-Panel benutzen kannst.';
$a->strings['System Language:'] = 'Systemsprache:';
$a->strings['Set the default language for your Friendica installation interface and to send emails.'] = 'Wähle die Standardsprache für deine Friendica-Installations-Oberfläche und den E-Mail-Versand';
$a->strings['Your Friendica site database has been installed.'] = 'Die Datenbank Deiner Friendica-Seite wurde installiert.';
$a->strings['Installation finished'] = 'Installation abgeschlossen';
$a->strings['<h1>What next</h1>'] = '<h1>Wie geht es weiter?</h1>';
$a->strings['IMPORTANT: You will need to [manually] setup a scheduled task for the worker.'] = 'Wichtig: du musst [manuell] einen Cronjob (o.ä.) für den Worker einrichten.';
$a->strings['Go to your new Friendica node <a href="%s/register">registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.'] = 'Du solltest nun die Seite zur <a href="%s/register">Nutzerregistrierung</a> deiner neuen Friendica Instanz besuchen und einen neuen Nutzer einrichten. Bitte denke daran, dieselbe E-Mail Adresse anzugeben, die du auch als Administrator-E-Mail angegeben hast, damit du das Admin-Panel verwenden kannst.';
$a->strings['Total invitation limit exceeded.'] = 'Limit für Einladungen erreicht.';
$a->strings['%s : Not a valid email address.'] = '%s: Keine gültige Email Adresse.';
$a->strings['Please join us on Friendica'] = 'Ich lade dich zu unserem sozialen Netzwerk Friendica ein';
$a->strings['Invitation limit exceeded. Please contact your site administrator.'] = 'Limit für Einladungen erreicht. Bitte kontaktiere des Administrator der Seite.';
$a->strings['%s : Message delivery failed.'] = '%s: Zustellung der Nachricht fehlgeschlagen.';
$a->strings['%d message sent.'] = [
	0 => '%d Nachricht gesendet.',
	1 => '%d Nachrichten gesendet.',
];
$a->strings['You have no more invitations available'] = 'Du hast keine weiteren Einladungen';
$a->strings['Visit %s for a list of public sites that you can join. Friendica members on other sites can all connect with each other, as well as with members of many other social networks.'] = 'Besuche %s für eine Liste der öffentlichen Server, denen du beitreten kannst. Friendica-Mitglieder unterschiedlicher Server können sich sowohl alle miteinander verbinden, als auch mit Mitgliedern anderer sozialer Netzwerke.';
$a->strings['To accept this invitation, please visit and register at %s or any other public Friendica website.'] = 'Um diese Kontaktanfrage zu akzeptieren, besuche und registriere dich bitte bei %s oder einer anderen öffentlichen Friendica-Website.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks. See %s for a list of alternate Friendica sites you can join.'] = 'Friendica Server verbinden sich alle untereinander, um ein großes, datenschutzorientiertes Soziales Netzwerk zu bilden, das von seinen Mitgliedern betrieben und kontrolliert wird. Du kannst dich auch mit vielen üblichen Sozialen Netzwerken verbinden. Besuche %s für eine Liste alternativer Friendica-Server, denen du beitreten kannst.';
$a->strings['Our apologies. This system is not currently configured to connect with other public sites or invite members.'] = 'Es tut uns leid. Dieses System ist zurzeit nicht dafür konfiguriert, sich mit anderen öffentlichen Seiten zu verbinden oder Mitglieder einzuladen.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks.'] = 'Friendica Server verbinden sich alle untereinander, um ein großes, datenschutzorientiertes Soziales Netzwerk zu bilden, das von seinen Mitgliedern betrieben und kontrolliert wird. Du kannst dich auch mit vielen üblichen Sozialen Netzwerken verbinden.';
$a->strings['To accept this invitation, please visit and register at %s.'] = 'Um diese Kontaktanfrage zu akzeptieren, besuche und registriere dich bitte bei %s.';
$a->strings['Send invitations'] = 'Einladungen senden';
$a->strings['Enter email addresses, one per line:'] = 'E-Mail-Adressen eingeben, eine pro Zeile:';
$a->strings['You are cordially invited to join me and other close friends on Friendica - and help us to create a better social web.'] = 'Du bist herzlich dazu eingeladen, dich mir und anderen guten Freunden auf Friendica anzuschließen - und ein besseres, soziales Netz aufzubauen.';
$a->strings['You will need to supply this invitation code: $invite_code'] = 'Du benötigst den folgenden Einladungscode: $invite_code';
$a->strings['Once you have registered, please connect with me via my profile page at:'] = 'Sobald du registriert bist, kontaktiere mich bitte auf meiner Profilseite:';
$a->strings['For more information about the Friendica project and why we feel it is important, please visit http://friendi.ca'] = 'Für weitere Informationen über das Friendica-Projekt und warum wir es für ein wichtiges Projekt halten, besuche bitte http://friendi.ca.';
$a->strings['Please enter a post body.'] = 'Bitte gibt den Text des Beitrags an';
$a->strings['This feature is only available with the frio theme.'] = 'Diese Seite kann ausschließlich mit dem Frio Theme verwendet werden.';
$a->strings['Compose new personal note'] = 'Neue persönliche Notiz verfassen';
$a->strings['Compose new post'] = 'Neuen Beitrag verfassen';
$a->strings['Visibility'] = 'Sichtbarkeit';
$a->strings['Clear the location'] = 'Ort löschen';
$a->strings['Location services are unavailable on your device'] = 'Ortungsdienste sind auf Ihrem Gerät nicht verfügbar';
$a->strings['Location services are disabled. Please check the website\'s permissions on your device'] = 'Ortungsdienste sind deaktiviert. Bitte überprüfe die Berechtigungen der Website auf deinem Gerät';
$a->strings['You can make this page always open when you use the New Post button in the <a href="/settings/display">Theme Customization settings</a>.'] = 'Wenn du magst, kannst du unter den <a href="/settings/display">Benutzerdefinierte Theme-Einstellungen</a> einstellen, dass diese Seite immer geöffnet wird, wenn du den "Neuer Beitrag" Button verwendest.';
$a->strings['The feed for this item is unavailable.'] = 'Der Feed für diesen Beitrag ist nicht verfügbar.';
$a->strings['Unable to follow this item.'] = 'Konnte dem Beitrag nicht folgen.';
$a->strings['System down for maintenance'] = 'System zur Wartung abgeschaltet';
$a->strings['This Friendica node is currently in maintenance mode, either automatically because it is self-updating or manually by the node administrator. This condition should be temporary, please come back in a few minutes.'] = 'Diese Friendica Instanz befindet sich derzeit im Wartungsmodus, entweder aufgrund von automatischen Updateprozessen oder weil die Administratoren der Instanz den Wartungsmodus aktiviert haben. Dies sollte ein vorübergehender Zustand sein. Bitte versuche es in ein paar Minuten erneut.';
$a->strings['A Decentralized Social Network'] = 'Ein dezentrales Soziales Netzwerk';
$a->strings['You need to be logged in to access this page.'] = 'Du musst angemeldet sein, um auf diese Seite zuzugreifen. ';
$a->strings['Files'] = 'Dateien';
$a->strings['Upload'] = 'Hochladen';
$a->strings['Sorry, maybe your upload is bigger than the PHP configuration allows'] = 'Entschuldige, die Datei scheint größer zu sein, als es die PHP-Konfiguration erlaubt.';
$a->strings['Or - did you try to upload an empty file?'] = 'Oder - hast du versucht, eine leere Datei hochzuladen?';
$a->strings['File exceeds size limit of %s'] = 'Die Datei ist größer als das erlaubte Limit von %s';
$a->strings['File upload failed.'] = 'Hochladen der Datei fehlgeschlagen.';
$a->strings['Unable to process image.'] = 'Konnte das Bild nicht bearbeiten.';
$a->strings['Image upload failed.'] = 'Hochladen des Bildes gescheitert.';
$a->strings['List of all users'] = 'Liste aller Benutzerkonten';
$a->strings['Active'] = 'Aktive';
$a->strings['List of active accounts'] = 'Liste der aktiven Benutzerkonten';
$a->strings['List of pending registrations'] = 'Liste der anstehenden Benutzerkonten';
$a->strings['List of blocked users'] = 'Liste der geblockten Benutzer';
$a->strings['Deleted'] = 'Gelöscht';
$a->strings['List of pending user deletions'] = 'Liste der auf Löschung wartenden Benutzer';
$a->strings['Normal Account Page'] = 'Normales Konto';
$a->strings['Soapbox Page'] = 'Marktschreier-Konto';
$a->strings['Public Group'] = 'Öffentliche Gruppe';
$a->strings['Public Group - Restricted'] = 'Öffentliche Gruppe - Beschränkt';
$a->strings['Automatic Friend Page'] = 'Automatische Freunde-Seite';
$a->strings['Private Group'] = 'Private Gruppe';
$a->strings['Personal Page'] = 'Persönliche Seite';
$a->strings['Organisation Page'] = 'Organisationsseite';
$a->strings['News Page'] = 'Nachrichtenseite';
$a->strings['Community Group'] = 'Gemeinschaftsgruppe';
$a->strings['You can\'t block a local contact, please block the user instead'] = 'Lokale Kontakte können nicht geblockt werden. Bitte blocke den Nutzer stattdessen.';
$a->strings['%s contact unblocked'] = [
	0 => '%sKontakt wieder freigegeben',
	1 => '%s Kontakte wieder freigegeben',
];
$a->strings['Remote Contact Blocklist'] = 'Blockliste entfernter Kontakte';
$a->strings['This page allows you to prevent any message from a remote contact to reach your node.'] = 'Auf dieser Seite kannst du Accounts von anderen Knoten blockieren und damit verhindern, dass ihre Beiträge von deinem Knoten angenommen werden.';
$a->strings['Block Remote Contact'] = 'Blockiere entfernten Kontakt';
$a->strings['select all'] = 'Alle auswählen';
$a->strings['select none'] = 'Auswahl aufheben';
$a->strings['No remote contact is blocked from this node.'] = 'Derzeit werden keine Kontakte auf diesem Knoten blockiert.';
$a->strings['Blocked Remote Contacts'] = 'Blockierte Kontakte von anderen Knoten';
$a->strings['Block New Remote Contact'] = 'Blockieren von weiteren Kontakten';
$a->strings['Photo'] = 'Foto:';
$a->strings['Reason'] = 'Grund';
$a->strings['%s total blocked contact'] = [
	0 => 'Insgesamt %s blockierter Kontakt',
	1 => 'Insgesamt %s blockierte Kontakte',
];
$a->strings['URL of the remote contact to block.'] = 'Die URL des entfernten Kontakts, der blockiert werden soll.';
$a->strings['Also purge contact'] = 'Kontakt auch löschen';
$a->strings['Removes all content related to this contact from the node. Keeps the contact record. This action cannot be undone.'] = 'Entfernt alle Inhalte von diesem Knoten, die in Verbindung zu dem Kontakt stehen. Der Kontakt-Eintrag bleibt erhalten. Dieser Vorgang kann nicht rückgängig gemacht werden.';
$a->strings['Block Reason'] = 'Sperrgrund';
$a->strings['Server domain pattern added to the blocklist.'] = 'Server Domain Muster zur Blockliste hinzugefügt';
$a->strings['%s server scheduled to be purged.'] = [
	0 => '%s Server für die Löschung eingeplant.',
	1 => '%s Server für die Löschung eingeplant.',
];
$a->strings['← Return to the list'] = '← zurück zur Liste';
$a->strings['Block A New Server Domain Pattern'] = 'Neues Domainmuster blockieren';
$a->strings['<p>The server domain pattern syntax is case-insensitive shell wildcard, comprising the following special characters:</p>
<ul>
	<li><code>*</code>: Any number of characters</li>
	<li><code>?</code>: Any single character</li>
</ul>'] = '<p>Die Syntax für das Domainmuster ist unabhängig von der Groß-/Kleinschreibung. Shell Willdcards bestehen aus den folgenden Zeichen:</p>
<ul>
<li><code>*</code>: Eine beliebige Anzahl von Zeichen</li>
<li><code>?</code>: Ein einzelnes Zeichen</li>
</ul>';
$a->strings['Check pattern'] = 'Muster überprüfen';
$a->strings['Matching known servers'] = 'Passende bekannte Server';
$a->strings['Server Name'] = 'Server Name';
$a->strings['Server Domain'] = 'Server Domain';
$a->strings['Known Contacts'] = 'Bekannte Kontakte';
$a->strings['%d known server'] = [
	0 => '%d bekannter Server',
	1 => '%d bekannte Server',
];
$a->strings['Add pattern to the blocklist'] = 'Muster zur Blockliste hinzufügen';
$a->strings['Server Domain Pattern'] = 'Server Domain Muster';
$a->strings['The domain pattern of the new server to add to the blocklist. Do not include the protocol.'] = 'Das Muster zur Erkennung der Domain, das zur Blockliste hinzugefügt werden soll. Das Protokoll nicht mir angeben.';
$a->strings['Purge server'] = 'Server entfernen';
$a->strings['Also purges all the locally stored content authored by the known contacts registered on that server. Keeps the contacts and the server records. This action cannot be undone.'] = [
	0 => 'Sollen die Inhalte der bekannten Kontakte die auf diesem Server registriert sind, auch lokal gelöscht werden. Die Kontakt- und Server-Einträge verbleiben in der Datenbank deines Servers. Diese Aktion kann nicht rückgängig gemacht werden.',
	1 => 'Sollen die Inhalte der bekannten Kontakte die auf diesen Servern registriert sind, auch lokal gelöscht werden. Die Kontakt- und Server-Einträge verbleiben in der Datenbank deines Servers. Diese Aktion kann nicht rückgängig gemacht werden.',
];
$a->strings['Block reason'] = 'Begründung der Blockierung';
$a->strings['The reason why you blocked this server domain pattern. This reason will be shown publicly in the server information page.'] = 'Warum werden Server die diesem Domainmuster entsprechen geblockt? Die Begründung wird öffentlich auf der Server-Informationsseite sichtbar sein.';
$a->strings['Error importing pattern file'] = 'Fehler beim Import der Muster Datei';
$a->strings['Local blocklist replaced with the provided file.'] = 'Lokale Blockliste wurde durch die bereitgestellte Datei ersetzt.';
$a->strings['%d pattern was added to the local blocklist.'] = [
	0 => '%d Muster wurde zur lokalen Blockliste hinzugefügt.',
	1 => '%d Muster wurden zur lokalen Blockliste hinzugefügt.',
];
$a->strings['No pattern was added to the local blocklist.'] = 'Kein Muster wurde zur lokalen Blockliste hinzugefügt.';
$a->strings['Import a Server Domain Pattern Blocklist'] = 'Server Domain Muster Blockliste importieren';
$a->strings['<p>This file can be downloaded from the <code>/friendica</code> path of any Friendica server.</p>'] = '<p>Diese Datei kann vom <code>/friendica</code> Pfad auf jedem  Friendica Server heruntergeladen werden.</p>';
$a->strings['Upload file'] = 'Datei hochladen';
$a->strings['Patterns to import'] = 'Zu importierende Muster';
$a->strings['Domain Pattern'] = 'Domain Muster';
$a->strings['Import Mode'] = 'Importmodus';
$a->strings['Import Patterns'] = 'Muster importieren';
$a->strings['%d total pattern'] = [
	0 => '%dMuster gesamt',
	1 => '%dMuster gesamt',
];
$a->strings['Server domain pattern blocklist CSV file'] = 'Server Domain Muster Blockliste CSV-Datei';
$a->strings['Append'] = 'Anhängen';
$a->strings['Imports patterns from the file that weren\'t already existing in the current blocklist.'] = 'Importiert Muster aus der Datei, die nicht bereits in der aktuellen Blockliste vorhanden waren.';
$a->strings['Replace'] = 'Ersetzen';
$a->strings['Replaces the current blocklist by the imported patterns.'] = 'Ersetzt die aktuelle Blockliste durch die importierten Muster.';
$a->strings['Blocked server domain pattern'] = 'Blockierte Server Domain Muster';
$a->strings['Delete server domain pattern'] = 'Server Domain Muster löschen';
$a->strings['Check to delete this entry from the blocklist'] = 'Markieren, um diesen Eintrag von der Blocklist zu entfernen';
$a->strings['Server Domain Pattern Blocklist'] = 'Server Domain Muster Blockliste';
$a->strings['This page can be used to define a blocklist of server domain patterns from the federated network that are not allowed to interact with your node. For each domain pattern you should also provide the reason why you block it.'] = 'Auf dieser Seite kannst du Muster definieren mit denen Server Domains aus dem föderierten Netzwerk daran gehindert werden mit deiner Instanz zu interagieren. Es ist ratsam für jedes Muster anzugeben, warum du es zur Blockliste hinzugefügt hast.';
$a->strings['The list of blocked server domain patterns will be made publically available on the <a href="/friendica">/friendica</a> page so that your users and people investigating communication problems can find the reason easily.'] = 'Die Liste der blockierten Domain Muster wird auf der Seite <a href="/friendica">/friendica</a> öffentlich einsehbar gemacht, damit deine Nutzer und Personen, die Kommunikationsprobleme erkunden, die Ursachen einfach finden können.';
$a->strings['Import server domain pattern blocklist'] = 'Server Domain Muster Blockliste importieren';
$a->strings['Add new entry to the blocklist'] = 'Neuen Eintrag in die Blockliste';
$a->strings['Save changes to the blocklist'] = 'Änderungen der Blockliste speichern';
$a->strings['Current Entries in the Blocklist'] = 'Aktuelle Einträge der Blockliste';
$a->strings['Delete entry from the blocklist'] = 'Eintrag von der Blockliste entfernen';
$a->strings['Delete entry from the blocklist?'] = 'Eintrag von der Blockliste entfernen?';
$a->strings['Item marked for deletion.'] = 'Eintrag wurden zur Löschung markiert';
$a->strings['Delete this Item'] = 'Diesen Eintrag löschen';
$a->strings['On this page you can delete an item from your node. If the item is a top level posting, the entire thread will be deleted.'] = 'Auf dieser Seite kannst du Einträge von deinem Knoten löschen. Wenn der Eintrag der Anfang einer Diskussion ist, wird der gesamte Diskussionsverlauf gelöscht.';
$a->strings['You need to know the GUID of the item. You can find it e.g. by looking at the display URL. The last part of http://example.com/display/123456 is the GUID, here 123456.'] = 'Zur Löschung musst du die GUID des Eintrags kennen. Diese findest du z.B. durch die /display URL des Eintrags. Der letzte Teil der URL ist die GUID. Lautet die URL beispielsweise http://example.com/display/123456, ist die GUID 123456.';
$a->strings['GUID'] = 'GUID';
$a->strings['The GUID of the item you want to delete.'] = 'Die GUID des zu löschenden Eintrags';
$a->strings['Item Id'] = 'Item Id';
$a->strings['Item URI'] = 'Item URI';
$a->strings['Terms'] = 'Terms';
$a->strings['Tag'] = 'Tag';
$a->strings['Type'] = 'Typ';
$a->strings['Term'] = 'Term';
$a->strings['URL'] = 'URL';
$a->strings['Implicit Mention'] = 'Implicit Mention';
$a->strings['Item not found'] = 'Beitrag nicht gefunden';
$a->strings['No source recorded'] = 'Keine Quelle aufgezeichnet';
$a->strings['Please make sure the <code>debug.store_source</code> config key is set in <code>config/local.config.php</code> for future items to have sources.'] = 'Bitte stelle sicher, dass der Config-Schlüssel <code>debug.store_source</code>  in der <code>config/local.config.php</code> gesetzt ist um in Zukunft Quellen zu haben.';
$a->strings['Item Guid'] = 'Beitrags-Guid';
$a->strings['Contact not found or their server is already blocked on this node.'] = 'Kontakt nicht gefunden oder seine Instanz ist bereits auf dieser Instanz blockiert.';
$a->strings['Please login to access this page.'] = 'Bitte melde dich an, um auf diese Seite zuzugreifen.';
$a->strings['Create Moderation Report'] = 'Moderationsbericht erstellen';
$a->strings['Pick Contact'] = 'Kontakt wählen';
$a->strings['Please enter below the contact address or profile URL you would like to create a moderation report about.'] = 'Bitte gib unten die Kontaktadresse oder Profil-URL ein, über die du einen Moderationsbericht erstellen möchten.';
$a->strings['Contact address/URL'] = 'Kontaktadresse/URL';
$a->strings['Pick Category'] = 'Kategorie auswählen';
$a->strings['Please pick below the category of your report.'] = 'Bitte wähle unten die Kategorie für deinen Bericht.';
$a->strings['Spam'] = 'Spam';
$a->strings['This contact is publishing many repeated/overly long posts/replies or advertising their product/websites in otherwise irrelevant conversations.'] = 'Dieser Kontakt veröffentlicht viele wiederholte/überlange Beiträge/Antworten oder wirbt für sein Produkt/seine Website in ansonsten belanglosen Gesprächen.';
$a->strings['Illegal Content'] = 'Illegaler Inhalt';
$a->strings['This contact is publishing content that is considered illegal in this node\'s hosting juridiction.'] = 'Dieser Kontakt veröffentlicht Inhalte, die in dem Land, in dem diese Instanz gehostet wird, als illegal gelten.';
$a->strings['Community Safety'] = 'Sicherheit in der Gemeinschaft';
$a->strings['This contact aggravated you or other people, by being provocative or insensitive, intentionally or not. This includes disclosing people\'s private information (doxxing), posting threats or offensive pictures in posts or replies.'] = 'Dieser Kontakt hat dich oder andere Personen verärgert, indem er absichtlich oder unabsichtlich provokativ oder unsensibel war. Dazu gehören die Offenlegung privater Informationen (Doxxing), das Posten von Drohungen oder anstößigen Bildern in Beiträgen oder Antworten.';
$a->strings['Unwanted Content/Behavior'] = 'Unerwünschte Inhalte/Verhaltensweisen';
$a->strings['This contact has repeatedly published content irrelevant to the node\'s theme or is openly criticizing the node\'s administration/moderation without directly engaging with the relevant people for example or repeatedly nitpicking on a sensitive topic.'] = 'Dieser Kontakt hat wiederholt Inhalte veröffentlicht, die für das Thema der Instanz irrelevant sind, oder er kritisiert offen die Verwaltung/Moderation der Instanz, ohne sich direkt mit den betreffenden Personen auseinanderzusetzen, oder er ist wiederholt erbsenzählerisch bei einem heiklen Thema.';
$a->strings['Rules Violation'] = 'Verstoß gegen die Regeln';
$a->strings['This contact violated one or more rules of this node. You will be able to pick which one(s) in the next step.'] = 'Dieser Kontakt hat gegen eine oder mehrere Regeln dieser Instanz verstoßen. Du kannst im nächsten Schritt auswählen, welche.';
$a->strings['Please elaborate below why you submitted this report. The more details you provide, the better your report can be handled.'] = 'Bitte gib im Folgenden an, warum du diese Meldung eingereicht hast. Je mehr Details du angibst, desto besser kann Ihre Meldung bearbeitet werden.';
$a->strings['Additional Information'] = 'Zusätzliche Informationen';
$a->strings['Please provide any additional information relevant to this particular report. You will be able to attach posts by this contact in the next step, but any context is welcome.'] = 'Bitte gib alle zusätzlichen Informationen an, die für diesen Bericht relevant sind. Du kannst im nächsten Schritt Beiträge dieser Kontaktperson anhängen, aber jeder Kontext ist willkommen.';
$a->strings['Pick Rules'] = 'Regeln auswählen';
$a->strings['Please pick below the node rules you believe this contact violated.'] = 'Bitte wähle unten die Instanzregeln aus, gegen die dieser Kontakt deiner Meinung nach verstoßen hat.';
$a->strings['Pick Posts'] = 'Beiträge auswählen';
$a->strings['Please optionally pick posts to attach to your report.'] = 'Bitte wähle optional Beiträge aus, die du an diesen Bericht anhängen möchtest.';
$a->strings['Submit Report'] = 'Bericht senden';
$a->strings['Further Action'] = 'Weiteres Vorgehen';
$a->strings['You can also perform one of the following action on the contact you reported:'] = 'Du kannst auch eine der folgenden Aktionen für den gemeldeten Kontakt durchführen:';
$a->strings['Nothing'] = 'Nichts';
$a->strings['Collapse contact'] = 'Kontakt verbergen';
$a->strings['Their posts and replies will keep appearing in your Network page but their content will be collapsed by default.'] = 'Ihre Beiträge und Antworten werden weiterhin auf Ihrer Netzwerkseite angezeigt, aber ihr Inhalt wird standardmäßig ausgeblendet.';
$a->strings['Their posts won\'t appear in your Network page anymore, but their replies can appear in forum threads. They still can follow you.'] = 'Ihre Beiträge werden nicht mehr auf deiner Netzwerkseite angezeigt, aber ihre Antworten können in Forenbeiträgen erscheinen. Sie können dir immer noch folgen.';
$a->strings['Block contact'] = 'Kontakt blockieren';
$a->strings['Their posts won\'t appear in your Network page anymore, but their replies can appear in forum threads, with their content collapsed by default. They cannot follow you but still can have access to your public posts by other means.'] = 'Ihre Beiträge erscheinen nicht mehr auf deiner Netzwerkseite, aber ihre Antworten können in Forumsthemen erscheinen, wobei ihr Inhalt standardmäßig eingeklappt ist. Sie können dir nicht folgen, haben aber auf anderem Wege weiterhin Zugang zu deinen öffentlichen Beiträgen.';
$a->strings['Forward report'] = 'Bericht weiterleiten';
$a->strings['Would you ike to forward this report to the remote server?'] = 'Möchtest du diesen Bericht an den Entfernten-Server weiterleiten?';
$a->strings['1. Pick a contact'] = '1. Wähle einen Kontakt';
$a->strings['2. Pick a category'] = '2. Wähle eine Kategorie';
$a->strings['2a. Pick rules'] = '2a. Regeln wählen';
$a->strings['2b. Add comment'] = '2b. Kommentar hinzufügen';
$a->strings['3. Pick posts'] = '3. Beiträge auswählen';
$a->strings['List of reports'] = 'Liste der Reports';
$a->strings['This page display reports created by our or remote users.'] = 'Auf dieser Seite werden Reports angezeigt, die von unseren oder entfernten Benutzern erstellt wurden.';
$a->strings['No report exists at this node.'] = 'Auf dieser Instanz ist kein Report vorhanden.';
$a->strings['Category'] = 'Kategorie';
$a->strings['%s total report'] = [
	0 => '%s Report',
	1 => '%s Reports insgesamt',
];
$a->strings['URL of the reported contact.'] = 'URL des gemeldeten Kontakts.';
$a->strings['Channel Relay'] = 'Kanalrelais';
$a->strings['Registered users'] = 'Registrierte Personen';
$a->strings['Pending registrations'] = 'Anstehende Anmeldungen';
$a->strings['%s user blocked'] = [
	0 => '%s Nutzer blockiert',
	1 => '%s Nutzer blockiert',
];
$a->strings['You can\'t remove yourself'] = 'Du kannst dich nicht selbst löschen!';
$a->strings['%s user deleted'] = [
	0 => '%s Nutzer gelöscht',
	1 => '%s Nutzer gelöscht',
];
$a->strings['Register date'] = 'Anmeldedatum';
$a->strings['Last login'] = 'Letzte Anmeldung';
$a->strings['Last public item'] = 'Letzter öffentliche Beitrag';
$a->strings['Active Accounts'] = 'Aktive Benutzerkonten';
$a->strings['User blocked'] = 'Nutzer blockiert.';
$a->strings['Site admin'] = 'Seitenadministrator';
$a->strings['Account expired'] = 'Account ist abgelaufen';
$a->strings['Create a new user'] = 'Neues Benutzerkonto anlegen';
$a->strings['Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'] = 'Die markierten Nutzer werden gelöscht!\n\nAlle Beiträge, die diese Nutzer auf dieser Seite veröffentlicht haben, werden permanent gelöscht!\n\nBist du sicher?';
$a->strings['The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'] = 'Der Nutzer {0} wird gelöscht!\n\nAlles, was dieser Nutzer auf dieser Seite veröffentlicht hat, wird permanent gelöscht!\n\nBist du sicher?';
$a->strings['User "%s" deleted'] = 'Nutzer "%s" gelöscht';
$a->strings['User "%s" blocked'] = 'Nutzer "%s" blockiert';
$a->strings['%s user unblocked'] = [
	0 => '%s Nutzer freigeschaltet',
	1 => '%s Nutzer freigeschaltet',
];
$a->strings['Blocked Users'] = 'Blockierte Benutzer';
$a->strings['User "%s" unblocked'] = 'Nutzer "%s" frei geschaltet';
$a->strings['New User'] = 'Neuer Nutzer';
$a->strings['Add User'] = 'Nutzer hinzufügen';
$a->strings['Name of the new user.'] = 'Name des neuen Nutzers';
$a->strings['Nickname'] = 'Spitzname';
$a->strings['Nickname of the new user.'] = 'Spitznamen für den neuen Nutzer';
$a->strings['Email address of the new user.'] = 'Email Adresse des neuen Nutzers';
$a->strings['Users awaiting permanent deletion'] = 'Nutzer wartet auf permanente Löschung';
$a->strings['Permanent deletion'] = 'Permanent löschen';
$a->strings['User waiting for permanent deletion'] = 'Nutzer wartet auf permanente Löschung';
$a->strings['%s user approved'] = [
	0 => '%sNutzer zugelassen',
	1 => '%sNutzer zugelassen',
];
$a->strings['%s registration revoked'] = [
	0 => '%sRegistration zurückgezogen',
	1 => '%sRegistrierungen zurückgezogen',
];
$a->strings['Account approved.'] = 'Konto freigegeben.';
$a->strings['Registration revoked'] = 'Registrierung zurückgezogen';
$a->strings['User registrations awaiting review'] = 'Neuanmeldungen, die auf Deine Bestätigung warten';
$a->strings['Request date'] = 'Anfragedatum';
$a->strings['No registrations.'] = 'Keine Neuanmeldungen.';
$a->strings['Note from the user'] = 'Hinweis vom Nutzer';
$a->strings['Deny'] = 'Verwehren';
$a->strings['Show Ignored Requests'] = 'Zeige ignorierte Anfragen';
$a->strings['Hide Ignored Requests'] = 'Verberge ignorierte Anfragen';
$a->strings['Notification type:'] = 'Art der Benachrichtigung:';
$a->strings['Suggested by:'] = 'Vorgeschlagen von:';
$a->strings['Claims to be known to you: '] = 'Behauptet, dich zu kennen: ';
$a->strings['Shall your connection be bidirectional or not?'] = 'Soll die Verbindung beidseitig sein oder nicht?';
$a->strings['Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.'] = 'Akzeptierst du %s als Kontakt, erlaubst du damit das Lesen deiner Beiträge und abonnierst selbst auch die Beiträge von %s.';
$a->strings['Accepting %s as a subscriber allows them to subscribe to your posts, but you will not receive updates from them in your news feed.'] = 'Wenn du %s als Abonnent akzeptierst, erlaubst du damit das Lesen deiner Beiträge, wirst aber selbst die Beiträge der anderen Seite nicht erhalten.';
$a->strings['Friend'] = 'Kontakt';
$a->strings['Subscriber'] = 'Abonnent';
$a->strings['No introductions.'] = 'Keine Kontaktanfragen.';
$a->strings['No more %s notifications.'] = 'Keine weiteren %s-Benachrichtigungen';
$a->strings['You must be logged in to show this page.'] = 'Du musst eingeloggt sein damit diese Seite angezeigt werden kann.';
$a->strings['Network Notifications'] = 'Netzwerkbenachrichtigungen';
$a->strings['System Notifications'] = 'Systembenachrichtigungen';
$a->strings['Personal Notifications'] = 'Persönliche Benachrichtigungen';
$a->strings['Home Notifications'] = 'Pinnwandbenachrichtigungen';
$a->strings['Show unread'] = 'Ungelesene anzeigen';
$a->strings['{0} requested registration'] = '{0} möchte sich registrieren';
$a->strings['{0} and %d others requested registration'] = '{0} und %d weitere möchten sich registrieren';
$a->strings['Authorize application connection'] = 'Verbindung der Applikation autorisieren';
$a->strings['Do you want to authorize this application to access your posts and contacts, and/or create new posts for you?'] = 'Möchtest du dieser Anwendung den Zugriff auf Deine Beiträge und Kontakte sowie das Erstellen neuer Beiträge in Deinem Namen gestatten?';
$a->strings['Unsupported or missing response type'] = 'Der Typ der Antwort fehlt oder wird nicht unterstützt';
$a->strings['Incomplete request data'] = 'Daten der Anfrage sind nicht vollständig';
$a->strings['Please copy the following authentication code into your application and close this window: %s'] = 'Bitte kopiere den folgenden Authentifizierungscode in deine App und schließe dieses Fenster: %s';
$a->strings['Invalid data or unknown client'] = 'Ungültige Daten oder unbekannter Client';
$a->strings['Unsupported or missing grant type'] = 'Der Grant-Typ fehlt oder wird nicht unterstützt';
$a->strings['Subscribing to contacts'] = 'Kontakten folgen';
$a->strings['No contact provided.'] = 'Keine Kontakte gefunden.';
$a->strings['Couldn\'t fetch information for contact.'] = 'Konnte die Kontaktinformationen nicht einholen.';
$a->strings['Couldn\'t fetch friends for contact.'] = 'Konnte die Kontaktliste des Kontakts nicht abfragen.';
$a->strings['Couldn\'t fetch following contacts.'] = 'Konnte Liste der gefolgten Kontakte nicht einholen.';
$a->strings['Couldn\'t fetch remote profile.'] = 'Konnte das entfernte Profil nicht laden.';
$a->strings['Unsupported network'] = 'Netzwerk wird nicht unterstützt';
$a->strings['Done'] = 'Erledigt';
$a->strings['success'] = 'Erfolg';
$a->strings['failed'] = 'Fehlgeschlagen';
$a->strings['ignored'] = 'Ignoriert';
$a->strings['Keep this window open until done.'] = 'Lasse dieses Fenster offen, bis der Vorgang abgeschlossen ist.';
$a->strings['Search in Friendica %s'] = 'Friendica durchsuchen %s';
$a->strings['The Photo is not available.'] = 'Das Foto ist nicht verfügbar.';
$a->strings['The Photo with id %s is not available.'] = 'Das Bild mit ID %s ist nicht verfügbar.';
$a->strings['Invalid external resource with url %s.'] = 'Ungültige externe Ressource mit der URL %s';
$a->strings['Invalid photo with id %s.'] = 'Fehlerhaftes Foto mit der ID %s.';
$a->strings['Post not found.'] = 'Beitrag nicht gefunden.';
$a->strings['Edit post'] = 'Beitrag bearbeiten';
$a->strings['web link'] = 'Weblink';
$a->strings['Insert video link'] = 'Video-Adresse einfügen';
$a->strings['video link'] = 'Video-Link';
$a->strings['Insert audio link'] = 'Audio-Adresse einfügen';
$a->strings['audio link'] = 'Audio-Link';
$a->strings['Remove Item Tag'] = 'Gegenstands-Tag entfernen';
$a->strings['Select a tag to remove: '] = 'Wähle ein Tag zum Entfernen aus: ';
$a->strings['Remove'] = 'Entfernen';
$a->strings['Wrong type "%s", expected one of: %s'] = 'Falscher Typ "%s", hatte einen der Folgenden erwartet: %s';
$a->strings['Model not found'] = 'Model nicht gefunden';
$a->strings['Unlisted'] = 'Ungelistet';
$a->strings['Remote privacy information not available.'] = 'Entfernte Privatsphäreneinstellungen nicht verfügbar.';
$a->strings['Visible to:'] = 'Sichtbar für:';
$a->strings['CC:'] = 'CC:';
$a->strings['BCC:'] = 'BCC:';
$a->strings['Audience:'] = 'Publikum:';
$a->strings['Attributed To:'] = 'Zuzuschreiben:';
$a->strings['Collection (%s)'] = 'Sammlung (%s)';
$a->strings['Followers (%s)'] = 'Folgende (%s)';
$a->strings['%d more'] = '%d weitere';
$a->strings['No contacts.'] = 'Keine Kontakte.';
$a->strings['%s\'s posts'] = 'Beiträge von %s';
$a->strings['%s\'s comments'] = 'Kommentare von %s';
$a->strings['%s\'s timeline'] = 'Timeline von %s';
$a->strings['Image exceeds size limit of %s'] = 'Bildgröße überschreitet das Limit von %s';
$a->strings['Image upload didn\'t complete, please try again'] = 'Der Upload des Bildes war nicht vollständig. Bitte versuche es erneut.';
$a->strings['Image file is missing'] = 'Bilddatei konnte nicht gefunden werden.';
$a->strings['Server can\'t accept new file upload at this time, please contact your administrator'] = 'Der Server kann derzeit keine neuen Datei-Uploads akzeptieren. Bitte kontaktiere deinen Administrator.';
$a->strings['Image file is empty.'] = 'Bilddatei ist leer.';
$a->strings['View Album'] = 'Album betrachten';
$a->strings['Profile not found.'] = 'Profil nicht gefunden.';
$a->strings['You\'re currently viewing your profile as <b>%s</b> <a href="%s" class="btn btn-sm pull-right">Cancel</a>'] = 'Du betrachtest dein Profil gerade als <b>%s</b> <a href="%s" class="btn btn-sm pull-right">Abbrechen</a>';
$a->strings['Full Name:'] = 'Kompletter Name:';
$a->strings['Member since:'] = 'Mitglied seit:';
$a->strings['j F, Y'] = 'j F, Y';
$a->strings['j F'] = 'j F';
$a->strings['Birthday:'] = 'Geburtstag:';
$a->strings['Age: '] = 'Alter: ';
$a->strings['%d year old'] = [
	0 => '%d Jahr alt',
	1 => '%d Jahre alt',
];
$a->strings['Description:'] = 'Beschreibung';
$a->strings['Groups:'] = 'Gruppen:';
$a->strings['View profile as:'] = 'Das Profil aus der Sicht von jemandem anderen betrachten:';
$a->strings['View as'] = 'Betrachten als';
$a->strings['Profile unavailable.'] = 'Profil nicht verfügbar.';
$a->strings['Invalid locator'] = 'Ungültiger Locator';
$a->strings['The provided profile link doesn\'t seem to be valid'] = 'Der angegebene Profil-Link scheint nicht gültig zu sein.';
$a->strings['Remote subscription can\'t be done for your network. Please subscribe directly on your system.'] = 'Entferntes Abon­nie­ren kann für dein Netzwerk nicht durchgeführt werden. Bitte nutze direkt die Abonnieren-Funktion deines Systems.   ';
$a->strings['Friend/Connection Request'] = 'Kontaktanfrage';
$a->strings['Enter your Webfinger address (user@domain.tld) or profile URL here. If this isn\'t supported by your system, you have to subscribe to <strong>%s</strong> or <strong>%s</strong> directly on your system.'] = 'Gib entweder deine Webfinger- (user@domain.tld) oder die Profil-Adresse an. Wenn dies von deinem System nicht unterstützt wird, folge bitte <strong>%s</strong> oder <strong>%s</strong> direkt von deinem System. ';
$a->strings['If you are not yet a member of the free social web, <a href="%s">follow this link to find a public Friendica node and join us today</a>.'] = 'Solltest du das freie Soziale Netzwerk noch nicht benutzen, kannst du <a href="%s">diesem Link folgen</a> um eine öffentliche Friendica Instanz zu finden um noch heute dem Netzwerk beizutreten.';
$a->strings['Your Webfinger address or profile URL:'] = 'Deine Webfinger Adresse oder Profil-URL';
$a->strings['Restricted profile'] = 'Eingeschränktes Profil';
$a->strings['This profile has been restricted which prevents access to their public content from anonymous visitors.'] = 'Das Profil wurde eingeschränkt, dies verhindert den Zugriff auf öffentliche Beiträge durch anonyme Besucher des Profils.';
$a->strings['Scheduled'] = 'Zeitplan';
$a->strings['Content'] = 'Inhalt';
$a->strings['Remove post'] = 'Beitrag entfernen';
$a->strings['Only parent users can create additional accounts.'] = 'Zusätzliche Nutzerkonten können nur von Verwaltern angelegt werden.';
$a->strings['This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.'] = 'Die maximale Anzahl täglicher Registrierungen auf dieser Seite wurde überschritten. Bitte versuche es morgen noch einmal.';
$a->strings['You may (optionally) fill in this form via OpenID by supplying your OpenID and clicking "Register".'] = 'Du kannst dieses Formular auch (optional) mit deiner OpenID ausfüllen, indem du deine OpenID angibst und \'Registrieren\' klickst.';
$a->strings['If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.'] = 'Wenn du nicht mit OpenID vertraut bist, lass dieses Feld bitte leer und fülle die restlichen Felder aus.';
$a->strings['Your OpenID (optional): '] = 'Deine OpenID (optional): ';
$a->strings['Include your profile in member directory?'] = 'Soll dein Profil im Nutzerverzeichnis angezeigt werden?';
$a->strings['Note for the admin'] = 'Hinweis für den Admin';
$a->strings['Leave a message for the admin, why you want to join this node'] = 'Hinterlasse eine Nachricht an den Admin, warum du einen Account auf dieser Instanz haben möchtest.';
$a->strings['Membership on this site is by invitation only.'] = 'Mitgliedschaft auf dieser Seite ist nur nach vorheriger Einladung möglich.';
$a->strings['Your invitation code: '] = 'Dein Ein­la­dungs­code';
$a->strings['Your Display Name (as you would like it to be displayed on this system):'] = 'Dein Anzeigename (wie er auf dieser Instanz angezeigt werden soll):';
$a->strings['Your Email Address (initial information will be sent there, so this must be a valid address):'] = 'Deine E-Mail Adresse (Informationen zur Registrierung werden an diese Adresse gesendet, darum muss sie existieren.)';
$a->strings['Please repeat your e-mail address:'] = 'Bitte wiederhole deine E-Mail Adresse';
$a->strings['New Password:'] = 'Neues Passwort:';
$a->strings['Leave empty for an auto generated password.'] = 'Leer lassen, um das Passwort automatisch zu generieren.';
$a->strings['Confirm:'] = 'Bestätigen:';
$a->strings['Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be "<strong>nickname@%s</strong>".'] = 'Wähle einen Spitznamen für dein Profil. Dieser muss mit einem Buchstaben beginnen. Die Adresse deines Profils auf dieser Seite wird \'<strong>spitzname@%s</strong>\' sein.';
$a->strings['Choose a nickname: '] = 'Spitznamen wählen: ';
$a->strings['Import'] = 'Import';
$a->strings['Import your profile to this friendica instance'] = 'Importiere dein Profil auf diese Friendica-Instanz';
$a->strings['Note: This node explicitly contains adult content'] = 'Hinweis: Dieser Knoten enthält explizit Inhalte für Erwachsene';
$a->strings['Parent Password:'] = 'Passwort des Verwalters';
$a->strings['Please enter the password of the parent account to legitimize your request.'] = 'Bitte gib das Passwort des Verwalters ein, um deine Anfrage zu bestätigen.';
$a->strings['Password doesn\'t match.'] = 'Das Passwort stimmt nicht.';
$a->strings['Please enter your password.'] = 'Bitte gib dein Passwort an.';
$a->strings['You have entered too much information.'] = 'Du hast zu viele Informationen eingegeben.';
$a->strings['Please enter the identical mail address in the second field.'] = 'Bitte gib die gleiche E-Mail Adresse noch einmal an.';
$a->strings['Nickname cannot start with a digit.'] = 'Der Spitzname darf nicht mit einer Zahl beginnen.';
$a->strings['Nickname can only contain US-ASCII characters.'] = 'Spitzname darf nur US-ASCII-Zeichen enthalten.';
$a->strings['The additional account was created.'] = 'Das zusätzliche Nutzerkonto wurde angelegt.';
$a->strings['Registration successful. Please check your email for further instructions.'] = 'Registrierung erfolgreich. Eine E-Mail mit weiteren Anweisungen wurde an dich gesendet.';
$a->strings['Failed to send email message. Here your accout details:<br> login: %s<br> password: %s<br><br>You can change your password after login.'] = 'Versenden der E-Mail fehlgeschlagen. Hier sind Deine Account-Details:

Login: %s
Passwort: %s

Du kannst das Passwort nach dem Anmelden ändern.';
$a->strings['Registration successful.'] = 'Registrierung erfolgreich.';
$a->strings['Your registration can not be processed.'] = 'Deine Registrierung konnte nicht verarbeitet werden.';
$a->strings['You have to leave a request note for the admin.'] = 'Du musst eine Nachricht für den Administrator als Begründung deiner Anfrage hinterlegen.';
$a->strings['An internal error occured.'] = 'Ein interner Fehler ist aufgetreten. ';
$a->strings['Your registration is pending approval by the site owner.'] = 'Deine Registrierung muss noch vom Betreiber der Seite freigegeben werden.';
$a->strings['You must be logged in to use this module.'] = 'Du musst eingeloggt sein, um dieses Modul benutzen zu können.';
$a->strings['Only logged in users are permitted to perform a search.'] = 'Nur eingeloggten Benutzern ist das Suchen gestattet.';
$a->strings['Only one search per minute is permitted for not logged in users.'] = 'Es ist nur eine Suchanfrage pro Minute für nicht eingeloggte Benutzer gestattet.';
$a->strings['Items tagged with: %s'] = 'Beiträge, die mit %s getaggt sind';
$a->strings['Search term was not saved.'] = 'Der Suchbegriff wurde nicht gespeichert.';
$a->strings['Search term already saved.'] = 'Suche ist bereits gespeichert.';
$a->strings['Search term was not removed.'] = 'Der Suchbegriff wurde nicht entfernt.';
$a->strings['Create a New Account'] = 'Neues Konto erstellen';
$a->strings['Your OpenID: '] = 'Deine OpenID:';
$a->strings['Please enter your username and password to add the OpenID to your existing account.'] = 'Bitte gib seinen Nutzernamen und das Passwort ein um die OpenID zu deinem bestehenden Nutzerkonto hinzufügen zu können.';
$a->strings['Or login using OpenID: '] = 'Oder melde dich mit deiner OpenID an: ';
$a->strings['Password: '] = 'Passwort: ';
$a->strings['Remember me'] = 'Anmeldedaten merken';
$a->strings['Forgot your password?'] = 'Passwort vergessen?';
$a->strings['Website Terms of Service'] = 'Website-Nutzungsbedingungen';
$a->strings['terms of service'] = 'Nutzungsbedingungen';
$a->strings['Website Privacy Policy'] = 'Website-Datenschutzerklärung';
$a->strings['privacy policy'] = 'Datenschutzerklärung';
$a->strings['Logged out.'] = 'Abgemeldet.';
$a->strings['OpenID protocol error. No ID returned'] = 'OpenID Protokollfehler. Keine ID zurückgegeben.';
$a->strings['Account not found. Please login to your existing account to add the OpenID to it.'] = 'Nutzerkonto nicht gefunden. Bitte melde dich an und füge die OpenID zu deinem Konto hinzu.';
$a->strings['Account not found. Please register a new account or login to your existing account to add the OpenID to it.'] = 'Nutzerkonto nicht gefunden. Bitte registriere ein neues Konto oder melde dich mit einem existierendem Konto an um diene OpenID hinzuzufügen.';
$a->strings['Passwords do not match.'] = 'Die Passwörter stimmen nicht überein.';
$a->strings['Password does not need changing.'] = 'Passwort muss nicht geändert werden.';
$a->strings['Password unchanged.'] = 'Passwort unverändert.';
$a->strings['Password Too Long'] = 'Passwort ist zu lang';
$a->strings['Since version 2022.09, we\'ve realized that any password longer than 72 characters is truncated during hashing. To prevent any confusion about this behavior, please update your password to be fewer or equal to 72 characters.'] = 'Mit der Version 2022.09 haben wir festgestellt, dass jedes Passwort, das länger als 72 Zeichen ist, beim Hashing abgeschnitten wird. Um Verwirrung über dieses Verhalten zu vermeiden, aktualisiere dein Passwort bitte so, dass es höchstens 72 Zeichen hat.';
$a->strings['Update Password'] = 'Passwort aktualisieren';
$a->strings['Current Password:'] = 'Aktuelles Passwort:';
$a->strings['Your current password to confirm the changes'] = 'Dein aktuelles Passwort um die Änderungen zu bestätigen';
$a->strings['Allowed characters are a-z, A-Z, 0-9 and special characters except white spaces and accentuated letters.'] = 'Erlaube Zeichen sind a-z, A-Z, 0-9 und Sonderzeichen, abgesehen von Leerzeichen und akzentuierten Buchstaben.';
$a->strings['Password length is limited to 72 characters.'] = 'Die Länge des Passworts ist auf 72 Zeichen begrenzt.';
$a->strings['Remaining recovery codes: %d'] = 'Verbleibende Wiederherstellungscodes: %d';
$a->strings['Invalid code, please retry.'] = 'Ungültiger Code, bitte erneut versuchen.';
$a->strings['Two-factor recovery'] = 'Zwei-Faktor-Wiederherstellung';
$a->strings['<p>You can enter one of your one-time recovery codes in case you lost access to your mobile device.</p>'] = 'Du kannst einen deiner einmaligen Wiederherstellungscodes eingeben, falls du den Zugriff auf dein Mobilgerät verloren hast.</p>';
$a->strings['Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>'] = 'Hast du dein Handy nicht? <a href="%s">Gib einen Zwei-Faktor-Wiederherstellungscode ein</a>';
$a->strings['Please enter a recovery code'] = 'Bitte gib einen Wiederherstellungscode ein';
$a->strings['Submit recovery code and complete login'] = 'Sende den Wiederherstellungscode und schließe die Anmeldung ab';
$a->strings['Sign out of this browser?'] = 'Von diesem Browser abmelden?';
$a->strings['<p>If you trust this browser, you will not be asked for verification code the next time you sign in.</p>'] = '<p>Wenn du diesem Browser vertraust, wirst du bei zukünftigen Anmeldungen nicht nach dem Verifikationscode gefragt.</p>';
$a->strings['Sign out'] = 'Abmelden';
$a->strings['Trust and sign out'] = 'Vertrauen und Abmelden';
$a->strings['Couldn\'t save browser to Cookie.'] = 'Konnte keine Cookies speichern.';
$a->strings['Trust this browser?'] = 'Vertraust du diesen Browser?';
$a->strings['<p>If you choose to trust this browser, you will not be asked for a verification code the next time you sign in.</p>'] = '<p>Wenn du diesem Browser vertraust, wirst du bei zukünftigen Anmeldungen nicht nach dem Verifikationscode gefragt.</p>';
$a->strings['Not now'] = 'Nicht jetzt';
$a->strings['Don\'t trust'] = 'Nicht vertrauen';
$a->strings['Trust'] = 'Vertrauen';
$a->strings['<p>Open the two-factor authentication app on your device to get an authentication code and verify your identity.</p>'] = '<p>Öffne die Zwei-Faktor-Authentifizierungs-App auf deinem Gerät, um einen Authentifizierungscode abzurufen und deine Identität zu überprüfen.</p>';
$a->strings['If you do not have access to your authentication code you can use a <a href="%s">two-factor recovery code</a>.'] = 'Wenn du keinen Zugriff auf deinen Authentifikationscode hast, kannst du einen <a href="%s">Zwei-Faktor Wiederherstellungsschlüssel</a> verwenden.';
$a->strings['Please enter a code from your authentication app'] = 'Bitte gebe einen Code aus Ihrer Authentifizierungs-App ein';
$a->strings['Verify code and complete login'] = 'Code überprüfen und Anmeldung abschließen';
$a->strings['Please use a shorter name.'] = 'Bitte verwende einen kürzeren Namen.';
$a->strings['Name too short.'] = 'Der Name ist zu kurz.';
$a->strings['Wrong Password.'] = 'Falsches Passwort';
$a->strings['Invalid email.'] = 'Ungültige E-Mail-Adresse.';
$a->strings['Cannot change to that email.'] = 'Ändern der E-Mail nicht möglich. ';
$a->strings['Settings were not updated.'] = 'Einstellungen nicht aktualisiert';
$a->strings['Relocate message has been send to your contacts'] = 'Die Umzugsbenachrichtigung wurde an Deine Kontakte versendet.';
$a->strings['Unable to find your profile. Please contact your admin.'] = 'Konnte dein Profil nicht finden. Bitte kontaktiere den Admin.';
$a->strings['Account for a service that automatically shares content based on user defined channels.'] = 'Konto für einen Dienst, der automatisch Inhalte basierend auf vom Benutzer definierten Kanälen teilt.';
$a->strings['Personal Page Subtypes'] = 'Unterarten der persönlichen Seite';
$a->strings['Community Group Subtypes'] = 'Unterarten der Gemeinschaftsgruppen';
$a->strings['Account for a personal profile.'] = 'Konto für ein persönliches Profil.';
$a->strings['Account for an organisation that automatically approves contact requests as "Followers".'] = 'Konto für eine Organisation, das Kontaktanfragen automatisch als "Follower" annimmt.';
$a->strings['Account for a news reflector that automatically approves contact requests as "Followers".'] = 'Konto für einen Feedspiegel, das Kontaktanfragen automatisch als "Follower" annimmt.';
$a->strings['Account for community discussions.'] = 'Konto für Diskussionsforen. ';
$a->strings['Account for a regular personal profile that requires manual approval of "Friends" and "Followers".'] = 'Konto für ein normales, persönliches Profil. Kontaktanfragen müssen manuell als "Friend" oder "Follower" bestätigt werden.';
$a->strings['Account for a public profile that automatically approves contact requests as "Followers".'] = 'Konto für ein öffentliches Profil, das Kontaktanfragen automatisch als "Follower" annimmt.';
$a->strings['Automatically approves all contact requests.'] = 'Bestätigt alle Kontaktanfragen automatisch.';
$a->strings['Contact requests have to be manually approved.'] = 'Kontaktanfragen müssen manuell akzeptiert werden.';
$a->strings['Account for a popular profile that automatically approves contact requests as "Friends".'] = 'Konto für ein gefragtes Profil, das Kontaktanfragen automatisch als "Friend" annimmt.';
$a->strings['Private Group [Experimental]'] = 'Private Gruppe [experimentell]';
$a->strings['Requires manual approval of contact requests.'] = 'Kontaktanfragen müssen manuell bestätigt werden.';
$a->strings['OpenID:'] = 'OpenID:';
$a->strings['(Optional) Allow this OpenID to login to this account.'] = '(Optional) Erlaube die Anmeldung für dieses Konto mit dieser OpenID.';
$a->strings['Publish your profile in your local site directory?'] = 'Darf dein Profil im lokalen Verzeichnis dieses Servers veröffentlicht werden?';
$a->strings['Your profile will be published in this node\'s <a href="%s">local directory</a>. Your profile details may be publicly visible depending on the system settings.'] = 'Dein Profil wird im <a href="%s">lokalen Verzeichnis</a> dieses Knotens veröffentlicht. Je nach Systemeinstellungen kann es öffentlich auffindbar sein.';
$a->strings['Your profile will also be published in the global friendica directories (e.g. <a href="%s">%s</a>).'] = 'Dein Profil wird auch in den globalen Friendica Verzeichnissen (z.B. <a href="%s">%s</a>) veröffentlicht werden.';
$a->strings['Account Settings'] = 'Kontoeinstellungen';
$a->strings['Your Identity Address is <strong>\'%s\'</strong> or \'%s\'.'] = 'Die Adresse deines Profils lautet <strong>\'%s\'</strong> oder \'%s\'.';
$a->strings['Password Settings'] = 'Passwort-Einstellungen';
$a->strings['Leave password fields blank unless changing'] = 'Lass die Passwort-Felder leer, außer du willst das Passwort ändern';
$a->strings['Password:'] = 'Passwort:';
$a->strings['Your current password to confirm the changes of the email address'] = 'Dein aktuelles Passwort um die Änderungen deiner E-Mail Adresse zu bestätigen';
$a->strings['Delete OpenID URL'] = 'OpenID URL löschen';
$a->strings['Basic Settings'] = 'Grundeinstellungen';
$a->strings['Display name:'] = 'Anzeigename:';
$a->strings['Email Address:'] = 'E-Mail-Adresse:';
$a->strings['Your Timezone:'] = 'Deine Zeitzone:';
$a->strings['Your Language:'] = 'Deine Sprache:';
$a->strings['Set the language we use to show you friendica interface and to send you emails'] = 'Wähle die Sprache, in der wir dir die Friendica-Oberfläche präsentieren sollen und dir E-Mail schicken';
$a->strings['Default Post Location:'] = 'Standardstandort:';
$a->strings['Use Browser Location:'] = 'Standort des Browsers verwenden:';
$a->strings['Security and Privacy Settings'] = 'Sicherheits- und Privatsphäre-Einstellungen';
$a->strings['Maximum Friend Requests/Day:'] = 'Maximale Anzahl von Kontaktanfragen/Tag:';
$a->strings['(to prevent spam abuse)'] = '(um SPAM zu vermeiden)';
$a->strings['Allow your profile to be searchable globally?'] = 'Darf dein Profil bei Suchanfragen gefunden werden?';
$a->strings['Activate this setting if you want others to easily find and follow you. Your profile will be searchable on remote systems. This setting also determines whether Friendica will inform search engines that your profile should be indexed or not.'] = 'Aktiviere diese Einstellung, wenn du von anderen einfach gefunden und gefolgt werden möchtest. Dein Profil wird dann auf anderen Systemen leicht durchsuchbar. Außerdem regelt diese Einstellung ob Friendica Suchmaschinen mitteilen soll, ob dein Profil indiziert werden soll oder nicht.';
$a->strings['Hide your contact/friend list from viewers of your profile?'] = 'Liste der Kontakte vor Betrachtern des Profil verbergen?';
$a->strings['A list of your contacts is displayed on your profile page. Activate this option to disable the display of your contact list.'] = 'Auf deiner Profilseite wird eine Liste deiner Kontakte angezeigt. Aktiviere diese Option wenn du das nicht möchtest.';
$a->strings['Hide your public content from anonymous viewers'] = 'Verbirg die öffentliche Inhalte vor anonymen Besuchern';
$a->strings['Anonymous visitors will only see your basic profile details. Your public posts and replies will still be freely accessible on the remote servers of your followers and through relays.'] = 'Anonyme Besucher deines Profils werden nur grundlegende Informationen angezeigt bekommen. Deine öffentlichen Beiträge und Kommentare werden weiterhin frei zugänglich auf den Servern deiner Kontakte und über Relays sein.';
$a->strings['Make public posts unlisted'] = 'Öffentliche Beiträge nicht listen';
$a->strings['Your public posts will not appear on the community pages or in search results, nor be sent to relay servers. However they can still appear on public feeds on remote servers.'] = 'Deine öffentlichen Beiträge werden nicht auf der Gemeinschaftsseite oder in den Suchergebnissen erscheinen, außerdem werden sie nicht an Relay-Server geschickt. Sie werden aber weiterhin in allen öffentlichen Feeds, auch auf entfernten Servern, erscheinen.';
$a->strings['Make all posted pictures accessible'] = 'Alle geposteten Bilder zugreifbar machen';
$a->strings['This option makes every posted picture accessible via the direct link. This is a workaround for the problem that most other networks can\'t handle permissions on pictures. Non public pictures still won\'t be visible for the public on your photo albums though.'] = 'Diese Option macht jedes veröffentlichte Bild über den direkten Link zugänglich. Dies ist eine Problemumgehung für das Problem, dass die meisten anderen Netzwerke keine Berechtigungen für Bilder verarbeiten können. Nicht öffentliche Bilder sind in Ihren Fotoalben jedoch immer noch nicht für die Öffentlichkeit sichtbar.';
$a->strings['Allow friends to post to your profile page?'] = 'Dürfen deine Kontakte auf deine Pinnwand schreiben?';
$a->strings['Your contacts may write posts on your profile wall. These posts will be distributed to your contacts'] = 'Deine Kontakte können Beiträge auf deiner Pinnwand hinterlassen. Diese werden an deine Kontakte verteilt.';
$a->strings['Allow friends to tag your posts?'] = 'Dürfen deine Kontakte deine Beiträge mit Schlagwörtern versehen?';
$a->strings['Your contacts can add additional tags to your posts.'] = 'Deine Kontakte dürfen deine Beiträge mit zusätzlichen Schlagworten versehen.';
$a->strings['Default privacy circle for new contacts'] = 'Voreingestellter Circle für neue Kontakte';
$a->strings['Default privacy circle for new group contacts'] = 'Voreingestellter Circle für neue Gruppenkontakte';
$a->strings['Default Post Permissions'] = 'Standard-Zugriffsrechte für Beiträge';
$a->strings['Expiration settings'] = 'Verfalls-Einstellungen';
$a->strings['Automatically expire posts after this many days:'] = 'Beiträge verfallen automatisch nach dieser Anzahl von Tagen:';
$a->strings['If empty, posts will not expire. Expired posts will be deleted'] = 'Wenn leer, verfallen Beiträge nie automatisch. Verfallene Beiträge werden gelöscht.';
$a->strings['Expire posts'] = 'Beiträge verfallen lassen';
$a->strings['When activated, posts and comments will be expired.'] = 'Ist dies aktiviert, werden Beiträge und Kommentare verfallen.';
$a->strings['Expire personal notes'] = 'Persönliche Notizen verfallen lassen';
$a->strings['When activated, the personal notes on your profile page will be expired.'] = 'Ist dies aktiviert, werden persönliche Notizen auf deiner Pinnwand verfallen.';
$a->strings['Expire starred posts'] = 'Markierte Beiträge verfallen lassen';
$a->strings['Starring posts keeps them from being expired. That behaviour is overwritten by this setting.'] = 'Markierte Beiträge verfallen eigentlich nicht. Mit dieser Option kannst du sie verfallen lassen.';
$a->strings['Only expire posts by others'] = 'Nur Beiträge anderer verfallen lassen.';
$a->strings['When activated, your own posts never expire. Then the settings above are only valid for posts you received.'] = 'Wenn aktiviert werden deine eigenen Beiträge niemals verfallen. Die obigen Einstellungen betreffen dann ausschließlich die Beiträge von anderen Accounts.';
$a->strings['Notification Settings'] = 'Benachrichtigungseinstellungen';
$a->strings['Send a notification email when:'] = 'Benachrichtigungs-E-Mail senden, wenn:';
$a->strings['You receive an introduction'] = '– du eine Kontaktanfrage erhältst';
$a->strings['Your introductions are confirmed'] = '– eine Deiner Kontaktanfragen akzeptiert wurde';
$a->strings['Someone writes on your profile wall'] = '– jemand etwas auf Deine Pinnwand schreibt';
$a->strings['Someone writes a followup comment'] = '– jemand auch einen Kommentar verfasst';
$a->strings['You receive a private message'] = '– du eine private Nachricht erhältst';
$a->strings['You receive a friend suggestion'] = '– du eine Empfehlung erhältst';
$a->strings['You are tagged in a post'] = '– du in einem Beitrag erwähnt wirst';
$a->strings['Create a desktop notification when:'] = 'Benachrichtigungen anzeigen wenn:';
$a->strings['Someone tagged you'] = 'Dich jemand erwähnt';
$a->strings['Someone directly commented on your post'] = 'Jemand einen Beitrag von dir kommentiert hat';
$a->strings['Someone liked your content'] = 'Einer deiner Beiträge gemocht wurde';
$a->strings['Can only be enabled, when the direct comment notification is enabled.'] = 'Kann nur aktiviert werden, wenn die "Jemand einen Beitrag von dir kommentiert hat  " Option eingeschaltet ist.';
$a->strings['Someone shared your content'] = 'Einer deiner Beiträge geteilt wurde';
$a->strings['Someone commented in your thread'] = 'Jemand hat in deiner Unterhaltung kommentiert';
$a->strings['Someone commented in a thread where you commented'] = 'Jemand in einer Unterhaltung kommentiert hat, in der du auch kommentiert hast';
$a->strings['Someone commented in a thread where you interacted'] = 'Jemand kommentierte in einer Unterhaltung mit der du interagiert hast';
$a->strings['Activate desktop notifications'] = 'Desktop-Benachrichtigungen einschalten';
$a->strings['Show desktop popup on new notifications'] = 'Desktop-Benachrichtigungen einschalten';
$a->strings['Text-only notification emails'] = 'Benachrichtigungs-E-Mail als Rein-Text.';
$a->strings['Send text only notification emails, without the html part'] = 'Sende Benachrichtigungs-E-Mail als Rein-Text - ohne HTML-Teil';
$a->strings['Show detailled notifications'] = 'Detaillierte Benachrichtigungen anzeigen';
$a->strings['Per default, notifications are condensed to a single notification per item. When enabled every notification is displayed.'] = 'Normalerweise werden alle Benachrichtigungen zu einem Thema in einer einzigen Benachrichtigung zusammengefasst. Wenn diese Option aktiviert ist, wird jede Benachrichtigung einzeln angezeigt.';
$a->strings['Show notifications of ignored contacts'] = 'Zeige Benachrichtigungen von ignorierten Kontakten';
$a->strings['You don\'t see posts from ignored contacts. But you still see their comments. This setting controls if you want to still receive regular notifications that are caused by ignored contacts or not.'] = 'Beiträge von ignorierten Kontakten werden dir nicht angezeigt. Aber du siehst immer noch ihre Kommentare. Diese Einstellung legt fest, ob du dazu weiterhin Benachrichtigungen erhalten willst oder ob diese einfach verworfen werden sollen.';
$a->strings['Advanced Account/Page Type Settings'] = 'Erweiterte Konto-/Seitentyp-Einstellungen';
$a->strings['Change the behaviour of this account for special situations'] = 'Verhalten dieses Kontos in bestimmten Situationen:';
$a->strings['Relocate'] = 'Umziehen';
$a->strings['If you have moved this profile from another server, and some of your contacts don\'t receive your updates, try pushing this button.'] = 'Wenn du dein Profil von einem anderen Server umgezogen hast und einige deiner Kontakte deine Beiträge nicht erhalten, verwende diesen Button.';
$a->strings['Resend relocate message to contacts'] = 'Umzugsbenachrichtigung erneut an Kontakte senden';
$a->strings['Addon Settings'] = 'Addon Einstellungen';
$a->strings['No Addon settings configured'] = 'Keine Addon-Einstellungen konfiguriert';
$a->strings['This page can be used to define the channels that will automatically be reshared by your account.'] = 'Diese Seite kann verwendet werden, um die Kanäle zu definieren, die automatisch von Deinem Konto geteilt werden.';
$a->strings['This page can be used to define your own channels.'] = 'Auf dieser Seite kannst du deine eigenen Kanäle definieren.';
$a->strings['Publish'] = 'Veröffentlichen';
$a->strings['When selected, the channel results are reshared. This only works for public ActivityPub posts from the public timeline or the user defined circles.'] = 'Wenn ausgewählt, werden die Kanalergebnisse erneut geteilt. Dies funktioniert nur für öffentliche ActivityPub-Beiträge aus der öffentlichen Timeline oder den vom Benutzer definierten Circles.';
$a->strings['Label'] = 'Bezeichnung';
$a->strings['Description'] = 'Beschreibung';
$a->strings['Access Key'] = 'Zugriffsschlüssel';
$a->strings['Circle/Channel'] = 'Circle/Kanal';
$a->strings['Include Tags'] = 'Tags einschließen';
$a->strings['Exclude Tags'] = 'Tags ausschließen';
$a->strings['Minimum Size'] = 'Mindestgröße';
$a->strings['Maximum Size'] = 'Maximale Größe';
$a->strings['Full Text Search'] = 'Volltextsuche';
$a->strings['Select all languages that you want to see in this channel. "Unspecified" describes all posts for which no language information was detected (e.g. posts with just an image or too little text to be sure of the language). If you want to see all languages, you will need to select all items in the list.'] = 'Wähle alle Sprachen aus, die du in dem Kanal sehen willst. "Unspezifiziert" umfasst alle Beiträge, für die keine Sprachinformationen gefunden wurden (z.B: Beiträge die ausschließlich aus Bildern bestehen, oder die zu kurz sind um die Sprache sicher bestimmen zu können). Wenn du alles Sprachen sehen willst, dann wähle hier in der Liste alle Sprachen aus.';
$a->strings['Delete channel'] = 'Lösche Kanal';
$a->strings['Check to delete this entry from the channel list'] = 'Haken setzen, um diesen Eintrag aus der Kanalliste zu löschen';
$a->strings['Short name for the channel. It is displayed on the channels widget.'] = 'Kurzname für den Kanal. Er wird im Widget für die Kanäle angezeigt.';
$a->strings['This should describe the content of the channel in a few word.'] = 'Dies sollte den Inhalt des Kanals in wenigen Worten beschreiben.';
$a->strings['When you want to access this channel via an access key, you can define it here. Pay attention to not use an already used one.'] = 'Wenn du auf diesen Kanal über einen Zugangsschlüssel zugreifen willst, kannst du ihn hier festlegen. Achte darauf, dass du nicht einen bereits verwendeten Schlüssel benutzt.';
$a->strings['Select a circle or channel, that your channel should be based on.'] = 'Wähle einen Circle oder Kanal, auf dem Ihr Kanal basieren soll.';
$a->strings['Comma separated list of tags. A post will be used when it contains any of the listed tags.'] = 'Durch Kommata getrennte Liste von Tags. Ein Beitrag wird verwendet, wenn er eines der aufgeführten Tags enthält.';
$a->strings['Comma separated list of tags. If a post contain any of these tags, then it will not be part of nthis channel.'] = 'Durch Kommata getrennte Liste von Tags. Wenn ein Beitrag eines dieser Tags enthält, wird er nicht Teil dieses Kanals sein.';
$a->strings['Minimum post size. Leave empty for no minimum size. The size is calculated without links, attached posts, mentions or hashtags.'] = 'Mindestbeitragsgröße. Lass dieses Feld leer um die Mindestgröße nicht anzugeben. Die Größe wird ohne Links, angehängte Beiträge, Erwähnungen oder Hashtags berechnet.';
$a->strings['Maximum post size. Leave empty for no maximum size. The size is calculated without links, attached posts, mentions or hashtags.'] = 'Maximalbeitragsgröße. Lass dieses Feld leer um die Maximalgröße nicht anzugeben. Die Größe wird ohne Links, angehängte Beiträge, Erwähnungen oder Hashtags berechnet.';
$a->strings['Search terms for the body, supports the "boolean mode" operators from MariaDB. See the help for a complete list of operators and additional keywords: %s'] = 'Suchbegriffe für den Body, unterstützt die "boolean mode"-Operatoren von MariaDB. In der Hilfe findest du eine vollständige Liste der Operatoren und zusätzliche Schlüsselwörter: %s';
$a->strings['Check to display images in the channel.'] = 'Aktiviere diese Option, um Bilder im Kanal anzuzeigen.';
$a->strings['Check to display videos in the channel.'] = 'Aktiviere diese Option, um Videos im Kanal anzuzeigen.';
$a->strings['Check to display audio in the channel.'] = 'Aktiviere diese Option, um Audio im Kanal anzuzeigen.';
$a->strings['Select all languages that you want to see in this channel.'] = 'Wähle alle Sprachen aus, die du in diesem Kanal sehen willst.';
$a->strings['Add new entry to the channel list'] = 'Neuen Eintrag zur Kanalliste hinzufügen';
$a->strings['Add'] = 'Hinzufügen';
$a->strings['Current Entries in the channel list'] = 'Aktuelle Einträge in der Kanalliste';
$a->strings['Delete entry from the channel list'] = 'Eintrag aus der Kanalliste löschen';
$a->strings['Delete entry from the channel list?'] = 'Eintrag aus der Kanalliste löschen?';
$a->strings['Failed to connect with email account using the settings provided.'] = 'Verbindung zum E-Mail-Konto mit den angegebenen Einstellungen nicht möglich.';
$a->strings['Diaspora (Socialhome, Hubzilla)'] = 'Diaspora (Socialhome, Hubzilla)';
$a->strings['Built-in support for %s connectivity is enabled'] = 'Eingebaute Unterstützung für die Verbindung zu %s ist aktiviert.';
$a->strings['Built-in support for %s connectivity is disabled'] = 'Eingebaute Unterstützung für die Verbindung zu %s ist nicht aktiviert.';
$a->strings['Email access is disabled on this site.'] = 'Zugriff auf E-Mails für diese Seite deaktiviert.';
$a->strings['None'] = 'Keine';
$a->strings['Default (Mastodon will display the title and a link to the post)'] = 'Grundeinstellung (Mastodon zweigt den Titel und einen Link auf den Beitrag)';
$a->strings['Use the summary (Mastodon and some others will treat it as content warning)'] = 'Verwende die Zusammenfassung (sie wird auf Mastodon und anderen als Inhaltswarnung behandelt)';
$a->strings['Embed the title in the body'] = 'Titel in Nachrichtenkörper einbetten';
$a->strings['General Social Media Settings'] = 'Allgemeine Einstellungen zu Sozialen Medien';
$a->strings['Followed content scope'] = 'Umfang zu folgender Inhalte';
$a->strings['By default, conversations in which your follows participated but didn\'t start will be shown in your timeline. You can turn this behavior off, or expand it to the conversations in which your follows liked a post.'] = 'Normalerweise werden Unterhaltungen an denen deine Kontakte beteiligt sind, sie aber nicht begonnen haben, in deiner Timeline angezeigt. Mit dieser Einstellung kann dieses Vorgehen kontrolliert werden. Es kann entweder dahin erweitert werden, dass auch Unterhaltungen angezeigt werden in denen deine Kontakte einen Kommentar mögen, oder komplett ausgeschaltet werden, so dass nur noch die Unterhaltungen angezeigt werden, die von deinen Kontakten gestartet wurden.';
$a->strings['Only conversations my follows started'] = 'Nur Unterhaltungen, die meine Kontakte gestartet haben';
$a->strings['Conversations my follows started or commented on (default)'] = 'Unterhaltungen an denen meine Kontakte beteiligt sind (Grundeinstellung)';
$a->strings['Any conversation my follows interacted with, including likes'] = 'Unterhaltungen mit denen meine Kontakte interagiert haben, inklusive likes';
$a->strings['Collapse sensitive posts'] = '"Sensible" Inhalte einklappen';
$a->strings['If a post is marked as "sensitive", it will be displayed in a collapsed state, if this option is enabled.'] = 'Wenn ein Beitrag als "sensibel" gekennzeichnet ist, wird er eingeklappt angezeigt, wenn diese Option aktiviert ist.';
$a->strings['Enable intelligent shortening'] = 'Intelligentes kürzen einschalten';
$a->strings['Normally the system tries to find the best link to add to shortened posts. If disabled, every shortened post will always point to the original friendica post.'] = 'Normalerweise versucht das System, den besten Link zu finden, um ihn zum gekürzten Postings hinzuzufügen. Wird diese Option ausgewählt, wird stets ein Link auf die originale Friendica-Nachricht beigefügt.';
$a->strings['Enable simple text shortening'] = 'Einfache Textkürzung aktivieren';
$a->strings['Normally the system shortens posts at the next line feed. If this option is enabled then the system will shorten the text at the maximum character limit.'] = 'Normalerweise kürzt das System Beiträge bei Zeilenumbrüchen. Ist diese Option aktiv, wird das System die Kürzung beim Erreichen der maximalen Zeichenzahl vornehmen.';
$a->strings['Attach the link title'] = 'Link Titel hinzufügen';
$a->strings['When activated, the title of the attached link will be added as a title on posts to Diaspora. This is mostly helpful with "remote-self" contacts that share feed content.'] = 'Ist dies aktiviert, wird der Titel von angehangenen Links bei Beiträgen nach Diaspora* angefügt. Dies ist vorallem bei Entfernten Konten nützlich die Beiträge von Feeds weiterleiten.';
$a->strings['API: Use spoiler field as title'] = 'API: Verwende den Spoiler Text als Titel';
$a->strings['When activated, the "spoiler_text" field in the API will be used for the title on standalone posts. When deactivated it will be used for spoiler text. For comments it will always be used for spoiler text.'] = 'Ist dies aktiviert, wird das "spoiler_text" der API als Titel von neuen Beiträgen verwendet. Ist es deaktiviert wird der Text als Spoiler-Text verwendet. Bei Kommentaren wird der Inhalt immer als Spoiler-Text verwendet.';
$a->strings['API: Automatically links at the end of the post as attached posts'] = 'API: Automatische Links am Ende des Beitrags als angehängte Beiträge';
$a->strings['When activated, added links at the end of the post react the same way as added links in the web interface.'] = 'Wenn dies aktiviert ist, reagieren hinzugefügte Links am Ende des Beitrags genauso wie hinzugefügte Links in der Weboberfläche.';
$a->strings['Article Mode'] = 'Artikel Modus';
$a->strings['Controls how posts with titles are transmitted. Mastodon and its forks don\'t display the content of these posts if the post is created in the correct (default) way.'] = 'Kontrolliert wie Beiträge mit Titeln übermittel werden. Mastodon und dessen Forks stellen den Inhalt dieser Beiträge nicht dar, wenn sie an sich korrekt in den Grundeinstellungen übertragen werden.';
$a->strings['Email/Mailbox Setup'] = 'E-Mail/Postfach-Einstellungen';
$a->strings['If you wish to communicate with email contacts using this service (optional), please specify how to connect to your mailbox.'] = 'Wenn du mit E-Mail-Kontakten über diesen Service kommunizieren möchtest (optional), gib bitte die Einstellungen für dein Postfach an.';
$a->strings['Last successful email check:'] = 'Letzter erfolgreicher E-Mail-Check';
$a->strings['IMAP server name:'] = 'IMAP-Server-Name:';
$a->strings['IMAP port:'] = 'IMAP-Port:';
$a->strings['Security:'] = 'Sicherheit:';
$a->strings['Email login name:'] = 'E-Mail-Login-Name:';
$a->strings['Email password:'] = 'E-Mail-Passwort:';
$a->strings['Reply-to address:'] = 'Reply-to Adresse:';
$a->strings['Send public posts to all email contacts:'] = 'Sende öffentliche Beiträge an alle E-Mail-Kontakte:';
$a->strings['Action after import:'] = 'Aktion nach Import:';
$a->strings['Move to folder'] = 'In einen Ordner verschieben';
$a->strings['Move to folder:'] = 'In diesen Ordner verschieben:';
$a->strings['Contact CSV file upload error'] = 'Fehler beim Hochladen der Kontakt CSV Datei';
$a->strings['Importing Contacts done'] = 'Kontakte wurden importiert.';
$a->strings['Upload a CSV file that contains the handle of your followed accounts in the first column you exported from the old account.'] = 'Lade eine CSV Datei hoch, die das Handle der Kontakte deines alten Nutzerkontos in der ersten Spalte enthält.';
$a->strings['Upload File'] = 'Datei hochladen';
$a->strings['Your legacy ActivityPub/GNU Social account'] = 'Dein alter ActivityPub/GNU Social-Account';
$a->strings['If you enter your old account name from an ActivityPub based system or your GNU Social/Statusnet account name here (in the format user@domain.tld), your contacts will be added automatically. The field will be emptied when done.'] = 'Wenn du deinen alten ActivityPub oder GNU Social/Statusnet-Account-Namen hier angibst (Format name@domain.tld), werden deine Kontakte automatisch hinzugefügt. Dieses Feld wird geleert, wenn die Kontakte hinzugefügt wurden.';
$a->strings['Delegation successfully granted.'] = 'Delegierung erfolgreich eingerichtet.';
$a->strings['Parent user not found, unavailable or password doesn\'t match.'] = 'Der angegebene Nutzer konnte nicht gefunden werden, ist nicht verfügbar oder das angegebene Passwort ist nicht richtig.';
$a->strings['Delegation successfully revoked.'] = 'Delegation erfolgreich aufgehoben.';
$a->strings['Delegated administrators can view but not change delegation permissions.'] = 'Verwalter können die Berechtigungen der Delegationen einsehen, sie aber nicht ändern.';
$a->strings['Delegate user not found.'] = 'Delegierter Nutzer nicht gefunden';
$a->strings['No parent user'] = 'Kein Verwalter';
$a->strings['Parent User'] = 'Verwalter';
$a->strings['Additional Accounts'] = 'Zusätzliche Accounts';
$a->strings['Register additional accounts that are automatically connected to your existing account so you can manage them from this account.'] = 'Zusätzliche Accounts registrieren, die automatisch mit deinem bestehenden Account verknüpft werden, damit du sie anschließend verwalten kannst.';
$a->strings['Register an additional account'] = 'Einen zusätzlichen Account registrieren';
$a->strings['Parent users have total control about this account, including the account settings. Please double check whom you give this access.'] = 'Verwalter haben Zugriff auf alle Funktionen dieses Benutzerkontos und können dessen Einstellungen ändern.';
$a->strings['Delegates'] = 'Bevollmächtigte';
$a->strings['Delegates are able to manage all aspects of this account/page except for basic account settings. Please do not delegate your personal account to anybody that you do not trust completely.'] = 'Bevollmächtigte sind in der Lage, alle Aspekte dieses Kontos/dieser Seite zu verwalten, abgesehen von den Grundeinstellungen des Kontos. Bitte gib niemandem eine Bevollmächtigung für Deinen privaten Account, dem du nicht absolut vertraust!';
$a->strings['Existing Page Delegates'] = 'Vorhandene Bevollmächtigte für die Seite';
$a->strings['Potential Delegates'] = 'Potentielle Bevollmächtigte';
$a->strings['No entries.'] = 'Keine Einträge.';
$a->strings['The theme you chose isn\'t available.'] = 'Das gewählte Theme ist nicht verfügbar';
$a->strings['%s - (Unsupported)'] = '%s - (Nicht unterstützt)';
$a->strings['Color/Black'] = 'Farbe/Schwarz';
$a->strings['Black'] = 'Schwarz';
$a->strings['Color/White'] = 'Farbe/Weiß';
$a->strings['White'] = 'Weiß';
$a->strings['No preview'] = 'Keine Vorschau';
$a->strings['No image'] = 'Kein Bild';
$a->strings['Small Image'] = 'Kleines Bild';
$a->strings['Large Image'] = 'Große Bilder';
$a->strings['Display Settings'] = 'Anzeige-Einstellungen';
$a->strings['General Theme Settings'] = 'Allgemeine Theme-Einstellungen';
$a->strings['Custom Theme Settings'] = 'Benutzerdefinierte Theme-Einstellungen';
$a->strings['Content Settings'] = 'Einstellungen zum Inhalt';
$a->strings['Theme settings'] = 'Theme-Einstellungen';
$a->strings['Timelines'] = 'Timelines';
$a->strings['Display Theme:'] = 'Theme:';
$a->strings['Mobile Theme:'] = 'Mobiles Theme';
$a->strings['Number of items to display per page:'] = 'Zahl der Beiträge, die pro Netzwerkseite angezeigt werden sollen: ';
$a->strings['Maximum of 100 items'] = 'Maximal 100 Beiträge';
$a->strings['Number of items to display per page when viewed from mobile device:'] = 'Zahl der Beiträge, die pro Netzwerkseite auf mobilen Geräten angezeigt werden sollen:';
$a->strings['Regularly update the page content'] = 'Regelmäßiges Aktualisieren der Seiteninhalte';
$a->strings['When enabled, new content on network, community and channels are added on top.'] = 'Wenn diese Option aktiviert ist, werden neue Inhalte auf der Netzwerk-, Gemeinschafts- und Kanalseiten automatisch am Anfang der Seiten eingefügt.';
$a->strings['Display emoticons'] = 'Zeige Emoticons';
$a->strings['When enabled, emoticons are replaced with matching symbols.'] = 'Wenn dies aktiviert ist, werden Text-Emoticons in Beiträgen durch Symbole ersetzt.';
$a->strings['Infinite scroll'] = 'Endloses Scrollen';
$a->strings['Automatic fetch new items when reaching the page end.'] = 'Automatisch neue Beiträge laden, wenn das Ende der Seite erreicht ist.';
$a->strings['Enable Smart Threading'] = 'Intelligentes Threading aktivieren';
$a->strings['Enable the automatic suppression of extraneous thread indentation.'] = 'Schaltet das automatische Unterdrücken von überflüssigen Thread-Einrückungen ein.';
$a->strings['Display the Dislike feature'] = 'Das "Nicht-mögen" Feature anzeigen';
$a->strings['Display the Dislike button and dislike reactions on posts and comments.'] = 'Einen "Ich mag das nicht" Button  und die dislike Reaktion auf Beiträge und Kommentare anzeigen.';
$a->strings['Display the resharer'] = 'Teilenden anzeigen';
$a->strings['Display the first resharer as icon and text on a reshared item.'] = 'Zeige das Profilbild des ersten Kontakts von dem ein Beitrag geteilt wurde.';
$a->strings['Stay local'] = 'Bleib lokal';
$a->strings['Don\'t go to a remote system when following a contact link.'] = 'Gehe nicht zu einem Remote-System, wenn einem Kontaktlink gefolgt wird';
$a->strings['Show the post deletion checkbox'] = 'Die Checkbox zum Löschen von Beiträgen anzeigen';
$a->strings['Display the checkbox for the post deletion on the network page.'] = 'Zeigt die Checkbox für das Löschen von Beiträgen auf der Netzwerkseite an.';
$a->strings['DIsplay the event list'] = 'Anzeige der der anstehenden Ereignisse';
$a->strings['Display the birthday reminder and event list on the network page.'] = 'Zeigt die Geburtstagserinnerungen und die anstehenden Veranstaltungen auf der Netzwerkseite an.';
$a->strings['Link preview mode'] = 'Vorschau Modus für Links';
$a->strings['Appearance of the link preview that is added to each post with a link.'] = 'Aussehen der Linkvorschau, die zu jedem Beitrag mit einem Link hinzugefügt wird.';
$a->strings['Hide pictures with empty alternative text'] = 'Bilder ohne Alternativtext verbergen';
$a->strings['Don\'t display pictures that are missing the alternative text.'] = 'Bilder die keinen beschreibenden Alternativtext besitzen werden nicht angezeigt.';
$a->strings['Hide custom emojis'] = 'Benutzerdefinierte Emojis ausblenden';
$a->strings['Don\'t display custom emojis.'] = 'Benutzerdefinierte Emojis werden nicht angezeigt.';
$a->strings['Platform icons style'] = 'Stiel der Platformicons';
$a->strings['Style of the platform icons'] = 'Stiel der verwendeten Platformicons';
$a->strings['Bookmark'] = 'Lesezeichen';
$a->strings['Enable timelines that you want to see in the channels widget. Bookmark timelines that you want to see in the top menu.'] = 'Aktiviere die Timelines, die Sie im Kanäle-Widget sehen möchten. Setze ein Lesezeichen für Timelines, die du im oberen Menü sehen willst.';
$a->strings['Channel languages:'] = 'Channel Sprachen:';
$a->strings['Select all the languages you want to see in your channels. "Unspecified" describes all posts for which no language information was detected (e.g. posts with just an image or too little text to be sure of the language). If you want to see all languages, you will need to select all items in the list.'] = 'Wähle alle Sprachen aus, die du in deinen Kanälen sehen willst. "Unspezifiziert" umfasst alle Beiträge, für die keine Sprachinformationen gefunden wurden (z.B: Beiträge die ausschließlich aus Bildern bestehen, oder die zu kurz sind um die Sprache sicher bestimmen zu können). Wenn du alles Sprachen sehen willst, dann wähle hier in der Liste alle Sprachen aus.';
$a->strings['Beginning of week:'] = 'Wochenbeginn:';
$a->strings['Default calendar view:'] = 'Standard-Kalenderansicht:';
$a->strings['Additional Features'] = 'Zusätzliche Features';
$a->strings['Connected Apps'] = 'Verbundene Programme';
$a->strings['Remove authorization'] = 'Autorisierung entziehen';
$a->strings['Display Name is required.'] = 'Der Anzeigename ist erforderlich.';
$a->strings['Profile couldn\'t be updated.'] = 'Das Profil konnte nicht aktualisiert werden.';
$a->strings['Label:'] = 'Bezeichnung:';
$a->strings['Value:'] = 'Wert:';
$a->strings['Field Permissions'] = 'Berechtigungen des Felds';
$a->strings['(click to open/close)'] = '(klicke zum Öffnen/Schließen)';
$a->strings['Add a new profile field'] = 'Neues Profilfeld hinzufügen';
$a->strings['The homepage is verified. A rel="me" link back to your Friendica profile page was found on the homepage.'] = 'Die Homepage ist verifiziert. Ein rel="me" Link zurück auf dein Friendica Profil wurde gefunden.';
$a->strings['To verify your homepage, add a rel="me" link to it, pointing to your profile URL (%s).'] = 'Um deine Homepage zu verifizieren, füge einen rel="me" Link auf der Seite hinzu, der auf dein Profil mit der URL (%s) verweist.';
$a->strings['Profile Actions'] = 'Profilaktionen';
$a->strings['Edit Profile Details'] = 'Profil bearbeiten';
$a->strings['Change Profile Photo'] = 'Profilbild ändern';
$a->strings['Profile picture'] = 'Profilbild';
$a->strings['Location'] = 'Wohnort';
$a->strings['Miscellaneous'] = 'Verschiedenes';
$a->strings['Custom Profile Fields'] = 'Benutzerdefinierte Profilfelder';
$a->strings['Upload Profile Photo'] = 'Profilbild hochladen';
$a->strings['<p>Custom fields appear on <a href="%s">your profile page</a>.</p>
				<p>You can use BBCodes in the field values.</p>
				<p>Reorder by dragging the field title.</p>
				<p>Empty the label field to remove a custom field.</p>
				<p>Non-public fields can only be seen by the selected Friendica contacts or the Friendica contacts in the selected circles.</p>'] = '<p>Die benutzerdefinierten Felder erscheinen auf <a href="%s">deiner Profil-Seite</a></p>.

<p>BBCode kann verwendet werden</p>
<p>Die Reihenfolge der Felder kann durch Ziehen des Feld-Titels mit der Maus angepasst werden.</p>
<p>Wird die Bezeichnung des Felds geleert, wird das Feld beim Speichern aus dem Profil entfernt.</p>
<p>Nicht öffentliche Felder können nur von den ausgewählten Friendica Circles gesehen werden.</p>';
$a->strings['Street Address:'] = 'Adresse:';
$a->strings['Locality/City:'] = 'Wohnort:';
$a->strings['Region/State:'] = 'Region/Bundesstaat:';
$a->strings['Postal/Zip Code:'] = 'Postleitzahl:';
$a->strings['Country:'] = 'Land:';
$a->strings['XMPP (Jabber) address:'] = 'XMPP (Jabber) Adresse';
$a->strings['The XMPP address will be published so that people can follow you there.'] = 'Die XMPP Adresse wird veröffentlicht, damit man dort mit dir kommunizieren kann.';
$a->strings['Matrix (Element) address:'] = 'Matrix (Element) Adresse:';
$a->strings['The Matrix address will be published so that people can follow you there.'] = 'Die Matrix Adresse wird veröffentlicht, damit man dort mit dir kommunizieren kann.';
$a->strings['Homepage URL:'] = 'Adresse der Homepage:';
$a->strings['Public Keywords:'] = 'Öffentliche Schlüsselwörter:';
$a->strings['(Used for suggesting potential friends, can be seen by others)'] = '(Wird verwendet, um potentielle Kontakte zu finden, kann von Kontakten eingesehen werden)';
$a->strings['Private Keywords:'] = 'Private Schlüsselwörter:';
$a->strings['(Used for searching profiles, never shown to others)'] = '(Wird für die Suche nach Profilen verwendet und niemals veröffentlicht)';
$a->strings['Image size reduction [%s] failed.'] = 'Verkleinern der Bildgröße von [%s] scheiterte.';
$a->strings['Shift-reload the page or clear browser cache if the new photo does not display immediately.'] = 'Drücke Umschalt+Neu Laden oder leere den Browser-Cache, falls das neue Foto nicht gleich angezeigt wird.';
$a->strings['Unable to process image'] = 'Bild konnte nicht verarbeitet werden';
$a->strings['Photo not found.'] = 'Foto nicht gefunden';
$a->strings['Profile picture successfully updated.'] = 'Profilbild erfolgreich aktualisiert.';
$a->strings['Crop Image'] = 'Bild zurechtschneiden';
$a->strings['Please adjust the image cropping for optimum viewing.'] = 'Passe bitte den Bildausschnitt an, damit das Bild optimal dargestellt werden kann.';
$a->strings['Use Image As Is'] = 'Bild wie es ist verwenden';
$a->strings['Missing uploaded image.'] = 'Hochgeladenes Bild nicht gefunden.';
$a->strings['Profile Picture Settings'] = 'Einstellungen zum Profilbild';
$a->strings['Current Profile Picture'] = 'Aktuelles Profilbild';
$a->strings['Upload Profile Picture'] = 'Profilbild aktualisieren';
$a->strings['Upload Picture:'] = 'Bild hochladen';
$a->strings['or'] = 'oder';
$a->strings['skip this step'] = 'diesen Schritt überspringen';
$a->strings['select a photo from your photo albums'] = 'wähle ein Foto aus deinen Fotoalben';
$a->strings['There was a validation error, please make sure you\'re logged in with the account you want to remove and try again.'] = 'Es gab einen Überprüfungsfehler. Bitte vergewisser dich, dass du mit dem Konto, das du entfernen möchtest angemeldet bist, und versuche es erneut.';
$a->strings['If this error persists, please contact your administrator.'] = 'Wenn dieser Fehler weiterhin besteht, wende dich bitte an den Administrator deiner Instanz.';
$a->strings['[Friendica System Notify]'] = '[Friendica-Systembenachrichtigung]';
$a->strings['User deleted their account'] = 'Gelöschter Nutzeraccount';
$a->strings['On your Friendica node an user deleted their account. Please ensure that their data is removed from the backups.'] = 'Ein Nutzer deiner Friendica-Instanz hat seinen Account gelöscht. Bitte stelle sicher, dass dessen Daten aus deinen Backups entfernt werden.';
$a->strings['The user id is %d'] = 'Die ID des Users lautet %d';
$a->strings['Your account has been successfully removed. Bye bye!'] = 'Ihr Konto wurde erfolgreich gelöscht. Auf Wiedersehen!';
$a->strings['Remove My Account'] = 'Konto löschen';
$a->strings['This will completely remove your account. Once this has been done it is not recoverable.'] = 'Dein Konto wird endgültig gelöscht. Es gibt keine Möglichkeit, es wiederherzustellen.';
$a->strings['Please enter your password for verification:'] = 'Bitte gib dein Passwort zur Verifikation ein:';
$a->strings['Do you want to ignore this server?'] = 'Möchtest du diese Instanz ignorieren?';
$a->strings['Do you want to unignore this server?'] = 'Möchtest du diese Instanz nicht mehr ignorieren?';
$a->strings['Remote server settings'] = 'Einstellungen der Remote-Instanz';
$a->strings['Server URL'] = 'Server URL';
$a->strings['Settings saved'] = 'Einstellungen gespeichert';
$a->strings['Here you can find all the remote servers you have taken individual moderation actions against. For a list of servers your node has blocked, please check out the <a href="friendica">Information</a> page.'] = 'Hier findest du alle Entfernten-Server, gegen die du individuelle Moderationsmaßnahmen ergriffen hast. Eine Liste der Server, die deine Instanz blockiert hat, findest du auf der <a href="friendica">Informationseite</a> .';
$a->strings['Delete all your settings for the remote server'] = 'Lösche alle deine Einstellungen für die Entfernte-Instanz';
$a->strings['Save changes'] = 'Einstellungen speichern';
$a->strings['Please enter your password to access this page.'] = 'Bitte gib dein Passwort ein, um auf diese Seite zuzugreifen.';
$a->strings['App-specific password generation failed: The description is empty.'] = 'Die Erzeugung des App spezifischen Passworts ist fehlgeschlagen. Die Beschreibung ist leer.';
$a->strings['App-specific password generation failed: This description already exists.'] = 'Die Erzeugung des App spezifischen Passworts ist fehlgeschlagen. Die Beschreibung existiert bereits.';
$a->strings['New app-specific password generated.'] = 'Neues App spezifisches Passwort erzeugt.';
$a->strings['App-specific passwords successfully revoked.'] = 'App spezifische Passwörter erfolgreich widerrufen.';
$a->strings['App-specific password successfully revoked.'] = 'App spezifisches Passwort erfolgreich widerrufen.';
$a->strings['Two-factor app-specific passwords'] = 'Zwei-Faktor App spezifische Passwörter.';
$a->strings['<p>App-specific passwords are randomly generated passwords used instead your regular password to authenticate your account on third-party applications that don\'t support two-factor authentication.</p>'] = '<p>App spezifische Passwörter sind zufällig generierte Passwörter die anstelle des regulären Passworts zur Anmeldung mit Client Anwendungen verwendet werden, wenn diese Anwendungen die Zwei-Faktor-Authentifizierung nicht unterstützen.</p>';
$a->strings['Make sure to copy your new app-specific password now. You won’t be able to see it again!'] = 'Das neue App spezifische Passwort muss jetzt übertragen werden. Später wirst du es nicht mehr einsehen können!';
$a->strings['Last Used'] = 'Zuletzt verwendet';
$a->strings['Revoke'] = 'Widerrufen';
$a->strings['Revoke All'] = 'Alle widerrufen';
$a->strings['When you generate a new app-specific password, you must use it right away, it will be shown to you once after you generate it.'] = 'Wenn du eine neues App spezifisches Passwort erstellst, musst du es sofort verwenden. Es wird dir nur ein einziges Mal nach der Erstellung angezeigt.';
$a->strings['Generate new app-specific password'] = 'Neues App spezifisches Passwort erstellen';
$a->strings['Friendiqa on my Fairphone 2...'] = 'Friendiqa auf meinem Fairphone 2';
$a->strings['Generate'] = 'Erstellen';
$a->strings['Two-factor authentication successfully disabled.'] = 'Zwei-Faktor Authentifizierung erfolgreich deaktiviert.';
$a->strings['<p>Use an application on a mobile device to get two-factor authentication codes when prompted on login.</p>'] = '<p>Benutze eine App auf deinem Smartphone um einen Zwei-Faktor Identifikationscode zu bekommen wenn beim Anmelden das verlangt wird.</p>';
$a->strings['Authenticator app'] = 'Zwei-Faktor Authentifizierungsapp';
$a->strings['Configured'] = 'Konfiguriert';
$a->strings['Not Configured'] = 'Nicht konfiguriert';
$a->strings['<p>You haven\'t finished configuring your authenticator app.</p>'] = '<p>Die Konfiguration deiner Zwei-Faktor Authentifizierungsapp ist nicht abgeschlossen.</p>';
$a->strings['<p>Your authenticator app is correctly configured.</p>'] = '<p>Deine Zwei-Faktor Authentifizierungsapp ist korrekt konfiguriert.</p>';
$a->strings['Recovery codes'] = 'Wiederherstellungsschlüssel';
$a->strings['Remaining valid codes'] = 'Verbleibende Wiederherstellungsschlüssel';
$a->strings['<p>These one-use codes can replace an authenticator app code in case you have lost access to it.</p>'] = '<p>Diese Einmalcodes können einen Authentifikator-App-Code ersetzen, falls du den Zugriff darauf verloren hast.</p>';
$a->strings['App-specific passwords'] = 'App spezifische Passwörter';
$a->strings['Generated app-specific passwords'] = 'App spezifische Passwörter erstellen';
$a->strings['<p>These randomly generated passwords allow you to authenticate on apps not supporting two-factor authentication.</p>'] = '<p>Diese zufällig erzeugten Passwörter erlauben es dir dich mit Apps anzumelden, die keine Zwei-Faktor-Authentifizierung unterstützen.</p>';
$a->strings['Current password:'] = 'Aktuelles Passwort:';
$a->strings['You need to provide your current password to change two-factor authentication settings.'] = 'Du musst dein aktuelles Passwort eingeben um die Einstellungen der Zwei-Faktor-Authentifizierung zu ändern';
$a->strings['Enable two-factor authentication'] = 'Aktiviere die Zwei-Faktor-Authentifizierung';
$a->strings['Disable two-factor authentication'] = 'Deaktiviere die Zwei-Faktor-Authentifizierung';
$a->strings['Show recovery codes'] = 'Wiederherstellungscodes anzeigen';
$a->strings['Manage app-specific passwords'] = 'App spezifische Passwörter verwalten';
$a->strings['Manage trusted browsers'] = 'Vertrauenswürdige Browser verwalten';
$a->strings['Finish app configuration'] = 'Beende die App-Konfiguration';
$a->strings['New recovery codes successfully generated.'] = 'Neue Wiederherstellungscodes erfolgreich generiert.';
$a->strings['Two-factor recovery codes'] = 'Zwei-Faktor-Wiederherstellungscodes';
$a->strings['<p>Recovery codes can be used to access your account in the event you lose access to your device and cannot receive two-factor authentication codes.</p><p><strong>Put these in a safe spot!</strong> If you lose your device and don’t have the recovery codes you will lose access to your account.</p>'] = '<p>Wiederherstellungscodes können verwendet werden, um auf dein Konto zuzugreifen, falls du den Zugriff auf dein Gerät verlieren und keine Zwei-Faktor-Authentifizierungscodes erhalten kannst.</p><p><strong>Bewahre diese an einem sicheren Ort auf!</strong> Wenn du dein Gerät verlierst und nicht über die Wiederherstellungscodes verfügst, verlierst du den Zugriff auf dein Konto.</p>';
$a->strings['When you generate new recovery codes, you must copy the new codes. Your old codes won’t work anymore.'] = 'Wenn du neue Wiederherstellungscodes generierst, mußt du die neuen Codes kopieren. Deine alten Codes funktionieren nicht mehr.';
$a->strings['Generate new recovery codes'] = 'Generiere neue Wiederherstellungscodes';
$a->strings['Next: Verification'] = 'Weiter: Überprüfung';
$a->strings['Trusted browsers successfully removed.'] = 'Die vertrauenswürdigen Browser wurden erfolgreich entfernt.';
$a->strings['Trusted browser successfully removed.'] = 'Der vertrauenswürdige Browser erfolgreich entfernt.';
$a->strings['Two-factor Trusted Browsers'] = 'Zwei-Faktor vertrauenswürdige Browser';
$a->strings['Trusted browsers are individual browsers you chose to skip two-factor authentication to access Friendica. Please use this feature sparingly, as it can negate the benefit of two-factor authentication.'] = 'Vertrauenswürdige Browser sind spezielle Browser für die du entscheidest, dass die Zwei-Faktor Authentifikation übersprungen werden soll. Bitte verwende diese Option sparsam, da sie die Vorteile der 2FA aufhebt.';
$a->strings['Device'] = 'Gerät';
$a->strings['OS'] = 'OS';
$a->strings['Trusted'] = 'Vertrauenswürdig';
$a->strings['Created At'] = 'Erstellt am';
$a->strings['Last Use'] = 'Zuletzt verwendet';
$a->strings['Remove All'] = 'Alle entfernen';
$a->strings['Two-factor authentication successfully activated.'] = 'Zwei-Faktor-Authentifizierung erfolgreich aktiviert.';
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
</dl>'] = '<p>Oder du kannst die Authentifizierungseinstellungen manuell übermitteln:</p>
<dl>
	Verursacher
	<dd>%s</dd>
	<dt>Kontoname</dt>
	<dd>%s</dd>
	<dt>Geheimer Schlüssel</dt>
	<dd>%s</dd>
	<dt>Typ</dt>
	<dd>Zeitbasiert</dd>
	<dt>Anzahl an Ziffern</dt>
	<dd>6</dd>
	<dt>Hashing-Algorithmus</dt>
	<dd>SHA-1</dd>
</dl>';
$a->strings['Two-factor code verification'] = 'Überprüfung des Zwei-Faktor-Codes';
$a->strings['<p>Please scan this QR Code with your authenticator app and submit the provided code.</p>'] = '<p>Bitte scanne diesen QR-Code mit deiner Authentifikator-App und übermittele den bereitgestellten Code.</p>';
$a->strings['<p>Or you can open the following URL in your mobile device:</p><p><a href="%s">%s</a></p>'] = '<p>Oder du kannst die folgende URL in deinem Mobilgerät öffnen:</p><p><a href="%s">%s</a></p>';
$a->strings['Verify code and enable two-factor authentication'] = 'Überprüfe den Code und aktiviere die Zwei-Faktor-Authentifizierung';
$a->strings['Export account'] = 'Account exportieren';
$a->strings['Export your account info and contacts. Use this to make a backup of your account and/or to move it to another server.'] = 'Exportiere Deine Account-Informationen und Kontakte. Verwende dies, um ein Backup Deines Accounts anzulegen und/oder damit auf einen anderen Server umzuziehen.';
$a->strings['Export all'] = 'Alles exportieren';
$a->strings['Export your account info, contacts and all your items as json. Could be a very big file, and could take a lot of time. Use this to make a full backup of your account (photos are not exported)'] = 'Exportiere deine Account Informationen, Kontakte und alle Einträge als JSON Datei. Dies könnte eine sehr große Datei werden und dementsprechend viel Zeit benötigen. Verwende dies um ein komplettes Backup deines Accounts anzulegen (Photos werden nicht exportiert).';
$a->strings['Export Contacts to CSV'] = 'Kontakte nach CSV exportieren';
$a->strings['Export the list of the accounts you are following as CSV file. Compatible to e.g. Mastodon.'] = 'Exportiert die Liste der Nutzerkonten denen du folgst in eine CSV Datei. Das Format ist z.B. zu Mastodon kompatibel.';
$a->strings['The top-level post isn\'t visible.'] = 'Der Beitrag der obersten Ebene ist nicht sichtbar.';
$a->strings['The top-level post was deleted.'] = 'Der Beitrag auf der obersten Ebene wurde gelöscht.';
$a->strings['This node has blocked the top-level author or the author of the shared post.'] = 'Diese Instanz hat den Top-Level-Autor oder den Autor des freigegebenen Beitrags gesperrt.';
$a->strings['You have ignored or blocked the top-level author or the author of the shared post.'] = 'Du hast den Autor der obersten Ebene oder den Autor des freigegebenen Beitrags ignoriert oder blockiert.';
$a->strings['You have ignored the top-level author\'s server or the shared post author\'s server.'] = 'Du hast die Instanz des übergeordneten Autors oder die Instanz des Autors des freigegebenen Beitrags ignoriert.';
$a->strings['Conversation Not Found'] = 'Konversation nicht gefunden';
$a->strings['Unfortunately, the requested conversation isn\'t available to you.'] = 'Leider ist die gewünschte Konversation für dich nicht verfügbar.';
$a->strings['Possible reasons include:'] = 'Mögliche Gründe sind:';
$a->strings['Go back'] = 'Geh zurück';
$a->strings['Stack trace:'] = 'Stack trace:';
$a->strings['Exception thrown in %s:%d'] = 'Exception thrown in %s:%d';
$a->strings['At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name (pen name), an username (nickname) and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.'] = 'Zum Zwecke der Registrierung und um die Kommunikation zwischen dem Nutzer und seinen Kontakten zu gewährleisten, muß der Nutzer einen Namen (auch Pseudonym) und einen Nutzernamen (Spitzname) sowie eine funktionierende E-Mail-Adresse angeben. Der Name ist auf der Profilseite für alle Nutzer sichtbar, auch wenn die Profildetails nicht angezeigt werden.
Die E-Mail-Adresse wird nur zur Benachrichtigung des Nutzers verwendet, sie wird nirgends angezeigt. Die Anzeige des Nutzerkontos im Server-Verzeichnis bzw. dem weltweiten Verzeichnis erfolgt gemäß den Einstellungen des Nutzers, sie ist zur Kommunikation nicht zwingend notwendig.';
$a->strings['This data is required for communication and is passed on to the nodes of the communication partners and is stored there. Users can enter additional private data that may be transmitted to the communication partners accounts.'] = 'Diese Daten sind für die Kommunikation notwendig und werden an die Knoten der Kommunikationspartner übermittelt und dort gespeichert. Nutzer können weitere, private Angaben machen, die ebenfalls an die verwendeten Server der Kommunikationspartner übermittelt werden können.';
$a->strings['At any point in time a logged in user can export their account data from the <a href="%1$s/settings/userexport">account settings</a>. If the user wants to delete their account they can do so at <a href="%1$s/settings/removeme">%1$s/settings/removeme</a>. The deletion of the account will be permanent. Deletion of the data will also be requested from the nodes of the communication partners.'] = 'Angemeldete Nutzer können ihre Nutzerdaten jederzeit von den <a href="%1$s/settings/userexport">Kontoeinstellungen</a> aus exportieren. Wenn ein Nutzer wünscht das Nutzerkonto zu löschen, so ist dies jederzeit unter <a href="%1$s/settings/removeme">%1$s/settings/removeme</a> möglich. Die Löschung des Nutzerkontos ist permanent. Die Löschung der Daten wird auch von den Knoten der Kommunikationspartner angefordert.';
$a->strings['Privacy Statement'] = 'Datenschutzerklärung';
$a->strings['Rules'] = 'Regeln';
$a->strings['Parameter uri_id is missing.'] = 'Der Parameter uri_id fehlt.';
$a->strings['The requested item doesn\'t exist or has been deleted.'] = 'Der angeforderte Beitrag existiert nicht oder wurde gelöscht.';
$a->strings['You are now logged in as %s'] = 'Du bist jetzt als %s angemeldet';
$a->strings['Switch between your accounts'] = 'Wechsle deine Konten';
$a->strings['Manage your accounts'] = 'Verwalte deine Konten';
$a->strings['Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions'] = 'Zwischen verschiedenen Identitäten oder Gemeinschafts-/Gruppenseiten wechseln, die deine Kontoinformationen teilen oder zu denen du „Verwalten“-Befugnisse bekommen hast.';
$a->strings['Select an identity to manage: '] = 'Wähle eine Identität zum Verwalten aus: ';
$a->strings['User imports on closed servers can only be done by an administrator.'] = 'Auf geschlossenen Servern können ausschließlich die Administratoren Benutzerkonten importieren.';
$a->strings['Move account'] = 'Account umziehen';
$a->strings['You can import an account from another Friendica server.'] = 'Du kannst einen Account von einem anderen Friendica Server importieren.';
$a->strings['You need to export your account from the old server and upload it here. We will recreate your old account here with all your contacts. We will try also to inform your friends that you moved here.'] = 'Du musst deinen Account vom alten Server exportieren und hier hochladen. Wir stellen deinen alten Account mit all deinen Kontakten wieder her. Wir werden auch versuchen, deine Kontakte darüber zu informieren, dass du hierher umgezogen bist.';
$a->strings['This feature is experimental. We can\'t import contacts from the OStatus network (GNU Social/Statusnet) or from Diaspora'] = 'Dieses Feature ist experimentell. Wir können keine Kontakte vom OStatus-Netzwerk (GNU Social/Statusnet) oder von Diaspora importieren';
$a->strings['Account file'] = 'Account-Datei';
$a->strings['To export your account, go to "Settings->Export your personal data" and select "Export account"'] = 'Um Deinen Account zu exportieren, rufe "Einstellungen -> Persönliche Daten exportieren" auf und wähle "Account exportieren"';
$a->strings['Error decoding account file'] = 'Fehler beim Verarbeiten der Account-Datei';
$a->strings['Error! No version data in file! This is not a Friendica account file?'] = 'Fehler! Keine Versionsdaten in der Datei! Ist das wirklich eine Friendica-Account-Datei?';
$a->strings['User \'%s\' already exists on this server!'] = 'Nutzer \'%s\' existiert bereits auf diesem Server!';
$a->strings['User creation error'] = 'Fehler beim Anlegen des Nutzer-Accounts aufgetreten';
$a->strings['%d contact not imported'] = [
	0 => '%d Kontakt nicht importiert',
	1 => '%d Kontakte nicht importiert',
];
$a->strings['User profile creation error'] = 'Fehler beim Anlegen des Nutzer-Profils';
$a->strings['Done. You can now login with your username and password'] = 'Erledigt. Du kannst dich jetzt mit deinem Nutzernamen und Passwort anmelden';
$a->strings['Welcome to Friendica'] = 'Willkommen bei Friendica';
$a->strings['New Member Checklist'] = 'Checkliste für neue Mitglieder';
$a->strings['We would like to offer some tips and links to help make your experience enjoyable. Click any item to visit the relevant page. A link to this page will be visible from your home page for two weeks after your initial registration and then will quietly disappear.'] = 'Wir möchten dir einige Tipps und Links anbieten, die dir helfen könnten, den Einstieg angenehmer zu machen. Klicke auf ein Element, um die entsprechende Seite zu besuchen. Ein Link zu dieser Seite hier bleibt für dich an deiner Pinnwand für zwei Wochen nach dem Registrierungsdatum sichtbar und wird dann verschwinden.';
$a->strings['Getting Started'] = 'Einstieg';
$a->strings['Friendica Walk-Through'] = 'Friendica Rundgang';
$a->strings['On your <em>Quick Start</em> page - find a brief introduction to your profile and network tabs, make some new connections, and find some groups to join.'] = 'Auf der <em>Quick Start</em>-Seite findest du eine kurze Einleitung in die einzelnen Funktionen deines Profils und die Netzwerk-Reiter, wo du interessante Foren findest und neue Kontakte knüpfst.';
$a->strings['Go to Your Settings'] = 'Gehe zu deinen Einstellungen';
$a->strings['On your <em>Settings</em> page -  change your initial password. Also make a note of your Identity Address. This looks just like an email address - and will be useful in making friends on the free social web.'] = 'Ändere bitte unter <em>Einstellungen</em> dein Passwort. Außerdem merke dir deine Identifikationsadresse. Diese sieht aus wie eine E-Mail-Adresse und wird benötigt, um Kontakte mit anderen im Friendica Netzwerk zu knüpfen..';
$a->strings['Review the other settings, particularly the privacy settings. An unpublished directory listing is like having an unlisted phone number. In general, you should probably publish your listing - unless all of your friends and potential friends know exactly how to find you.'] = 'Überprüfe die restlichen Einstellungen, insbesondere die Einstellungen zur Privatsphäre. Wenn du dein Profil nicht veröffentlichst, ist das, als wenn du deine Telefonnummer nicht ins Telefonbuch einträgst. Im Allgemeinen solltest du es veröffentlichen - außer all deine Kontakte und potentiellen Kontakte wissen genau, wie sie dich finden können.';
$a->strings['Upload a profile photo if you have not done so already. Studies have shown that people with real photos of themselves are ten times more likely to make friends than people who do not.'] = 'Lade ein Profilbild hoch, falls du es noch nicht getan hast. Studien haben gezeigt, dass es zehnmal wahrscheinlicher ist, neue Kontakte zu finden, wenn du ein Bild von dir selbst verwendest, als wenn du dies nicht tust.';
$a->strings['Edit Your Profile'] = 'Editiere dein Profil';
$a->strings['Edit your <strong>default</strong> profile to your liking. Review the settings for hiding your list of friends and hiding the profile from unknown visitors.'] = 'Editiere dein <strong>Standard</strong>-Profil nach deinen Vorlieben. Überprüfe die Einstellungen zum Verbergen deiner Kontaktliste vor unbekannten Betrachtern des Profils.';
$a->strings['Profile Keywords'] = 'Profil-Schlüsselbegriffe';
$a->strings['Set some public keywords for your profile which describe your interests. We may be able to find other people with similar interests and suggest friendships.'] = 'Trage ein paar öffentliche Stichwörter in dein Profil ein, die deine Interessen beschreiben. Eventuell sind wir in der Lage Leute zu finden, die deine Interessen teilen und können dir dann Kontakte vorschlagen.';
$a->strings['Connecting'] = 'Verbindungen knüpfen';
$a->strings['Importing Emails'] = 'Emails Importieren';
$a->strings['Enter your email access information on your Connector Settings page if you wish to import and interact with friends or mailing lists from your email INBOX'] = 'Gib deine E-Mail-Zugangsinformationen auf der Connector-Einstellungsseite ein, falls du E-Mails aus Deinem Posteingang importieren und mit Kontakten und Mailinglisten interagieren willst.';
$a->strings['Go to Your Contacts Page'] = 'Gehe zu deiner Kontakt-Seite';
$a->strings['Your Contacts page is your gateway to managing friendships and connecting with friends on other networks. Typically you enter their address or site URL in the <em>Add New Contact</em> dialog.'] = 'Die Kontakte-Seite ist die Einstiegsseite, von der aus du Kontakte verwalten und dich mit Personen in anderen Netzwerken verbinden kannst. Normalerweise gibst du dazu einfach ihre Adresse oder die URL der Seite im Kasten <em>Neuen Kontakt hinzufügen</em> ein.';
$a->strings['Go to Your Site\'s Directory'] = 'Gehe zum Verzeichnis Deiner Friendica-Instanz';
$a->strings['The Directory page lets you find other people in this network or other federated sites. Look for a <em>Connect</em> or <em>Follow</em> link on their profile page. Provide your own Identity Address if requested.'] = 'Über die Verzeichnisseite kannst du andere Personen auf diesem Server oder anderen, verknüpften Seiten finden. Halte nach einem <em>Verbinden</em>- oder <em>Folgen</em>-Link auf deren Profilseiten Ausschau und gib deine eigene Profiladresse an, falls du danach gefragt wirst.';
$a->strings['Finding New People'] = 'Neue Leute kennenlernen';
$a->strings['On the side panel of the Contacts page are several tools to find new friends. We can match people by interest, look up people by name or interest, and provide suggestions based on network relationships. On a brand new site, friend suggestions will usually begin to be populated within 24 hours.'] = 'Im seitlichen Bedienfeld der Kontakteseite gibt es diverse Werkzeuge, um neue Personen zu finden. Wir können Menschen mit den gleichen Interessen finden, anhand von Namen oder Interessen suchen oder aber aufgrund vorhandener Kontakte neue Leute vorschlagen.
Auf einer brandneuen - soeben erstellten - Seite starten die Kontaktvorschläge innerhalb von 24 Stunden.';
$a->strings['Add Your Contacts To Circle'] = 'Kontakte zum Circle hinzufügen';
$a->strings['Once you have made some friends, organize them into private conversation circles from the sidebar of your Contacts page and then you can interact with each circle privately on your Network page.'] = 'Sobald du einige Freunde gefunden hast, kannst du diese in der Seitenleiste deiner Kontaktseite in private Circles einteilen und dann mit jedem Circle auf deiner Netzwerkseite privat interagieren.';
$a->strings['Why Aren\'t My Posts Public?'] = 'Warum sind meine Beiträge nicht öffentlich?';
$a->strings['Friendica respects your privacy. By default, your posts will only show up to people you\'ve added as friends. For more information, see the help section from the link above.'] = 'Friendica respektiert Deine Privatsphäre. Mit der Grundeinstellung werden Deine Beiträge ausschließlich Deinen Kontakten angezeigt. Für weitere Informationen diesbezüglich lies dir bitte den entsprechenden Abschnitt in der Hilfe unter dem obigen Link durch.';
$a->strings['Getting Help'] = 'Hilfe bekommen';
$a->strings['Go to the Help Section'] = 'Zum Hilfe Abschnitt gehen';
$a->strings['Our <strong>help</strong> pages may be consulted for detail on other program features and resources.'] = 'Unsere <strong>Hilfe</strong>-Seiten können herangezogen werden, um weitere Einzelheiten zu anderen Programm-Features zu erhalten.';
$a->strings['{0} wants to follow you'] = '{0} möchte dir folgen';
$a->strings['{0} has started following you'] = '{0} folgt dir jetzt';
$a->strings['%s liked %s\'s post'] = '%s mag %ss Beitrag';
$a->strings['%s disliked %s\'s post'] = '%s mag %ss Beitrag nicht';
$a->strings['%s is attending %s\'s event'] = '%s nimmt an %s\'s Event teil';
$a->strings['%s is not attending %s\'s event'] = '%s nimmt nicht an %s\'s Event teil';
$a->strings['%s may attending %s\'s event'] = '%s nimmt eventuell an %s\'s Veranstaltung teil';
$a->strings['%s is now friends with %s'] = '%s ist jetzt mit %s befreundet';
$a->strings['%s commented on %s\'s post'] = '%s hat %ss Beitrag kommentiert';
$a->strings['%s created a new post'] = '%s hat einen neuen Beitrag erstellt';
$a->strings['Friend Suggestion'] = 'Kontaktvorschlag';
$a->strings['Friend/Connect Request'] = 'Kontakt-/Freundschaftsanfrage';
$a->strings['%1$s wants to follow you'] = '%1$s möchte dir folgen';
$a->strings['%1$s has started following you'] = '%1$s hat angefangen dir zu folgen';
$a->strings['%1$s liked your comment on %2$s'] = '%1$s mag deinen Kommentar %2$s';
$a->strings['%1$s liked your post %2$s'] = '%1$s mag deinen Beitrag %2$s';
$a->strings['%1$s disliked your comment on %2$s'] = '%1$s mag deinen Kommentar %2$s  nicht';
$a->strings['%1$s disliked your post %2$s'] = '%1$s mag deinen Beitrag %2$s nicht';
$a->strings['%1$s shared your comment %2$s'] = '%1$s hat deinen Kommentar %2$s geteilt';
$a->strings['%1$s shared your post %2$s'] = '%1$s hat deinen Beitrag %2$s geteilt';
$a->strings['%1$s shared the post %2$s from %3$s'] = '%1$s hat den Beitrag %2$s von %3$s geteilt';
$a->strings['%1$s shared a post from %3$s'] = '%1$s hat einen Beitrag von %3$s geteilt';
$a->strings['%1$s shared the post %2$s'] = '%1$s hat den Beitrag %2$s geteilt';
$a->strings['%1$s shared a post'] = '%1$s hat einen Beitrag geteilt';
$a->strings['%1$s wants to attend your event %2$s'] = '%1$s möchte an deiner Veranstaltung %2$s teilnehmen';
$a->strings['%1$s does not want to attend your event %2$s'] = '%1$s möchte nicht an deiner Veranstaltung %2$s teilnehmen';
$a->strings['%1$s maybe wants to attend your event %2$s'] = '%1$s nimmt eventuell an deiner Veranstaltung %2$s teil';
$a->strings['%1$s tagged you on %2$s'] = '%1$s erwähnte dich auf %2$s';
$a->strings['%1$s replied to you on %2$s'] = '%1$s hat dir auf %2$s geantwortet';
$a->strings['%1$s commented in your thread %2$s'] = '%1$s hat deine Unterhaltung %2$s kommentiert';
$a->strings['%1$s commented on your comment %2$s'] = '%1$s hat deinen Kommentar %2$s kommentiert';
$a->strings['%1$s commented in their thread %2$s'] = '%1$s hat in der eigenen Unterhaltung %2$s kommentiert';
$a->strings['%1$s commented in their thread'] = '%1$s kommentierte in der eigenen Unterhaltung';
$a->strings['%1$s commented in the thread %2$s from %3$s'] = '%1$s hat in der Unterhaltung %2$s von %3$s kommentiert';
$a->strings['%1$s commented in the thread from %3$s'] = '%1$s hat in der Unterhaltung von %3$s kommentiert';
$a->strings['%1$s commented on your thread %2$s'] = '%1$s hat in deiner Unterhaltung %2$s kommentiert';
$a->strings['[Friendica:Notify]'] = '[Friendica Meldung]';
$a->strings['%s New mail received at %s'] = '%sNeue Nachricht auf %s empfangen';
$a->strings['%1$s sent you a new private message at %2$s.'] = '%1$s hat dir eine neue, private Nachricht auf %2$s geschickt.';
$a->strings['a private message'] = 'eine private Nachricht';
$a->strings['%1$s sent you %2$s.'] = '%1$s schickte dir %2$s.';
$a->strings['Please visit %s to view and/or reply to your private messages.'] = 'Bitte besuche %s, um Deine privaten Nachrichten anzusehen und/oder zu beantworten.';
$a->strings['%1$s commented on %2$s\'s %3$s %4$s'] = '%1$s kommentierte %2$s\'s %3$s%4$s';
$a->strings['%1$s commented on your %2$s %3$s'] = '%1$s kommentierte auf  (%2$s) %3$s';
$a->strings['%1$s commented on their %2$s %3$s'] = '%1$s hat den eigenen %2$s %3$s kommentiert';
$a->strings['%1$s Comment to conversation #%2$d by %3$s'] = '%1$sKommentar von %3$s auf Unterhaltung %2$d';
$a->strings['%s commented on an item/conversation you have been following.'] = '%s hat einen Beitrag kommentiert, dem du folgst.';
$a->strings['Please visit %s to view and/or reply to the conversation.'] = 'Bitte besuche %s, um die Konversation anzusehen und/oder zu kommentieren.';
$a->strings['%s %s posted to your profile wall'] = '%s%s hat auf deine Pinnwand gepostet';
$a->strings['%1$s posted to your profile wall at %2$s'] = '%1$s schrieb um %2$s auf Deine Pinnwand';
$a->strings['%1$s posted to [url=%2$s]your wall[/url]'] = '%1$s hat etwas auf [url=%2$s]Deiner Pinnwand[/url] gepostet';
$a->strings['%s Introduction received'] = '%sVorstellung erhalten';
$a->strings['You\'ve received an introduction from \'%1$s\' at %2$s'] = 'Du hast eine Kontaktanfrage von \'%1$s\' auf %2$s erhalten';
$a->strings['You\'ve received [url=%1$s]an introduction[/url] from %2$s.'] = 'Du hast eine [url=%1$s]Kontaktanfrage[/url] von %2$s erhalten.';
$a->strings['You may visit their profile at %s'] = 'Hier kannst du das Profil betrachten: %s';
$a->strings['Please visit %s to approve or reject the introduction.'] = 'Bitte besuche %s, um die Kontaktanfrage anzunehmen oder abzulehnen.';
$a->strings['%s A new person is sharing with you'] = '%sEine neue Person teilt nun mit dir';
$a->strings['%1$s is sharing with you at %2$s'] = '%1$s teilt mit dir auf %2$s';
$a->strings['%s You have a new follower'] = '%sDu hast einen neuen Kontakt';
$a->strings['You have a new follower at %2$s : %1$s'] = 'Du hast einen neuen Kontakt auf %2$s: %1$s';
$a->strings['%s Friend suggestion received'] = '%sKontaktvorschlag erhalten';
$a->strings['You\'ve received a friend suggestion from \'%1$s\' at %2$s'] = 'Du hast einen Kontakt-Vorschlag von \'%1$s\' auf %2$s erhalten';
$a->strings['You\'ve received [url=%1$s]a friend suggestion[/url] for %2$s from %3$s.'] = 'Du hast einen [url=%1$s]Kontakt-Vorschlag[/url] %2$s von %3$s erhalten.';
$a->strings['Name:'] = 'Name:';
$a->strings['Photo:'] = 'Foto:';
$a->strings['Please visit %s to approve or reject the suggestion.'] = 'Bitte besuche %s, um den Vorschlag zu akzeptieren oder abzulehnen.';
$a->strings['%s Connection accepted'] = '%sKontaktanfrage bestätigt';
$a->strings['\'%1$s\' has accepted your connection request at %2$s'] = '\'%1$s\' hat Deine Kontaktanfrage auf  %2$s bestätigt';
$a->strings['%2$s has accepted your [url=%1$s]connection request[/url].'] = '%2$s hat Deine [url=%1$s]Kontaktanfrage[/url] akzeptiert.';
$a->strings['You are now mutual friends and may exchange status updates, photos, and email without restriction.'] = 'Ihr seid nun beidseitige Kontakte und könnt Statusmitteilungen, Bilder und E-Mails ohne Einschränkungen austauschen.';
$a->strings['Please visit %s if you wish to make any changes to this relationship.'] = 'Bitte besuche %s, wenn du Änderungen an eurer Beziehung vornehmen willst.';
$a->strings['\'%1$s\' has chosen to accept you a fan, which restricts some forms of communication - such as private messaging and some profile interactions. If this is a celebrity or community page, these settings were applied automatically.'] = '\'%1$s\' hat sich entschieden dich als Fan zu akzeptieren, dies schränkt einige Kommunikationswege - wie private Nachrichten und einige Interaktionsmöglichkeiten auf der Profilseite - ein. Wenn dies eine Berühmtheiten- oder Gemeinschaftsseite ist, werden diese Einstellungen automatisch vorgenommen.';
$a->strings['\'%1$s\' may choose to extend this into a two-way or more permissive relationship in the future.'] = '\'%1$s\' kann den Kontaktstatus zu einem späteren Zeitpunkt erweitern und diese Einschränkungen aufheben. ';
$a->strings['Please visit %s  if you wish to make any changes to this relationship.'] = 'Bitte besuche %s, wenn du Änderungen an eurer Beziehung vornehmen willst.';
$a->strings['registration request'] = 'Registrierungsanfrage';
$a->strings['You\'ve received a registration request from \'%1$s\' at %2$s'] = 'Du hast eine Registrierungsanfrage von %2$s auf \'%1$s\' erhalten';
$a->strings['You\'ve received a [url=%1$s]registration request[/url] from %2$s.'] = 'Du hast eine [url=%1$s]Registrierungsanfrage[/url] von %2$s erhalten.';
$a->strings['Display Name:	%s
Site Location:	%s
Login Name:	%s (%s)'] = 'Anzeigename: %s
URL der Seite: %s
Login Name: %s(%s)';
$a->strings['Please visit %s to approve or reject the request.'] = 'Bitte besuche %s, um die Anfrage zu bearbeiten.';
$a->strings['new registration'] = 'Neue Registrierung';
$a->strings['You\'ve received a new registration from \'%1$s\' at %2$s'] = 'Du hast eine neue Registrierung von %1$s auf %2$s erhalten.';
$a->strings['You\'ve received a [url=%1$s]new registration[/url] from %2$s.'] = 'Du hast eine [url=%1$s]neue Registrierung[/url] von %2$s erhalten.';
$a->strings['Please visit %s to have a look at the new registration.'] = 'Bitte rufe %s auf, um dir die Registrierung zu sichten.';
$a->strings['%s %s tagged you'] = '%s %s hat dich erwähnt';
$a->strings['%s %s shared a new post'] = '%s%shat einen Beitrag geteilt';
$a->strings['%1$s %2$s liked your post #%3$d'] = '%1$s%2$s mag deinen Beitrag #%3$d';
$a->strings['%1$s %2$s liked your comment on #%3$d'] = '%1$s%2$s mag deinen Kommentar zu #%3$d';
$a->strings['This message was sent to you by %s, a member of the Friendica social network.'] = 'Diese Nachricht wurde dir von %s geschickt, einem Mitglied des Sozialen Netzwerks Friendica.';
$a->strings['You may visit them online at %s'] = 'Du kannst sie online unter %s besuchen';
$a->strings['Please contact the sender by replying to this post if you do not wish to receive these messages.'] = 'Falls du diese Beiträge nicht erhalten möchtest, kontaktiere bitte den Autor, indem du auf diese Nachricht antwortest.';
$a->strings['%s posted an update.'] = '%s hat ein Update veröffentlicht.';
$a->strings['Private Message'] = 'Private Nachricht';
$a->strings['Public Message'] = 'Öffentlicher Beitrag';
$a->strings['Unlisted Message'] = 'Nicht gelisteter Beitrag';
$a->strings['This entry was edited'] = 'Dieser Beitrag wurde bearbeitet.';
$a->strings['Connector Message'] = 'Connector Nachricht';
$a->strings['Edit'] = 'Bearbeiten';
$a->strings['Delete globally'] = 'Global löschen';
$a->strings['Remove locally'] = 'Lokal entfernen';
$a->strings['Block %s'] = 'Blockiere %s';
$a->strings['Ignore %s'] = 'Ignoriere %s';
$a->strings['Collapse %s'] = 'Klappe %s zu';
$a->strings['Report post'] = 'Beitrag melden';
$a->strings['Save to folder'] = 'In Ordner speichern';
$a->strings['I will attend'] = 'Ich werde teilnehmen';
$a->strings['I will not attend'] = 'Ich werde nicht teilnehmen';
$a->strings['I might attend'] = 'Ich werde eventuell teilnehmen';
$a->strings['Ignore thread'] = 'Thread ignorieren';
$a->strings['Unignore thread'] = 'Thread nicht mehr ignorieren';
$a->strings['Toggle ignore status'] = 'Ignoriert-Status ein-/ausschalten';
$a->strings['Add star'] = 'Markieren';
$a->strings['Remove star'] = 'Markierung entfernen';
$a->strings['Toggle star status'] = 'Markierung umschalten';
$a->strings['Pin'] = 'Anheften';
$a->strings['Unpin'] = 'Losmachen';
$a->strings['Toggle pin status'] = 'Angeheftet Status ändern';
$a->strings['Pinned'] = 'Angeheftet';
$a->strings['Add tag'] = 'Tag hinzufügen';
$a->strings['Quote share this'] = 'Teile und zitiere dies';
$a->strings['Quote Share'] = 'Zitat teilen';
$a->strings['Reshare this'] = 'Teile dies';
$a->strings['Reshare'] = 'Teilen';
$a->strings['Cancel your Reshare'] = 'Teilen aufheben';
$a->strings['Unshare'] = 'Nicht mehr teilen';
$a->strings['%s (Received %s)'] = '%s (Empfangen %s)';
$a->strings['Comment this item on your system'] = 'Kommentiere diesen Beitrag von deinem System aus';
$a->strings['Remote comment'] = 'Entfernter Kommentar';
$a->strings['Share via ...'] = 'Teile mit...';
$a->strings['Share via external services'] = 'Teile mit einem externen Dienst';
$a->strings['Unknown parent'] = 'Unbekannter Ursprungsbeitrag';
$a->strings['in reply to %s'] = 'Als Antwort auf %s';
$a->strings['Parent is probably private or not federated.'] = 'Der Urspungsbeitrag ist wahrscheinlich privat oder nicht föderiert.';
$a->strings['to'] = 'zu';
$a->strings['via'] = 'via';
$a->strings['Wall-to-Wall'] = 'Wall-to-Wall';
$a->strings['via Wall-To-Wall:'] = 'via Wall-To-Wall:';
$a->strings['Reply to %s'] = 'Antworte %s';
$a->strings['Notifier task is pending'] = 'Die Benachrichtigungsaufgabe ist ausstehend';
$a->strings['Delivery to remote servers is pending'] = 'Die Auslieferung an Remote-Server steht noch aus';
$a->strings['Delivery to remote servers is underway'] = 'Die Auslieferung an Remote-Server ist unterwegs';
$a->strings['Delivery to remote servers is mostly done'] = 'Die Zustellung an Remote-Server ist fast erledigt';
$a->strings['Delivery to remote servers is done'] = 'Die Zustellung an die Remote-Server ist erledigt';
$a->strings['%d comment'] = [
	0 => '%d Kommentar',
	1 => '%d Kommentare',
];
$a->strings['Show more'] = 'Zeige mehr';
$a->strings['Show fewer'] = 'Zeige weniger';
$a->strings['Reshared by: %s'] = 'Geteilt von: %s';
$a->strings['Viewed by: %s'] = 'Gesehen von: %s';
$a->strings['Read by: %s'] = 'Gelesen von: %s';
$a->strings['Liked by: %s'] = 'Diese Menschen mögen das: %s';
$a->strings['Disliked by: %s'] = 'Unbeliebt bei: %s';
$a->strings['Attended by: %s'] = 'Besucht von: %s';
$a->strings['Maybe attended by: %s'] = 'Vielleicht besucht von: %s';
$a->strings['Not attended by: %s'] = 'Nicht besucht von: %s';
$a->strings['Commented by: %s'] = 'Kommentiert von: %s';
$a->strings['Reacted with %s by: %s'] = 'Reagierte mit %s von: %s';
$a->strings['Quote shared by: %s'] = 'Zitat geteilt von: %s';
$a->strings['Chat'] = 'Chat';
$a->strings['(no subject)'] = '(kein Betreff)';
$a->strings['The folder %s must be writable by webserver.'] = 'Das Verzeichnis %s muss für den Web-Server beschreibbar sein.';
$a->strings['Login failed.'] = 'Anmeldung fehlgeschlagen.';
$a->strings['Login failed. Please check your credentials.'] = 'Anmeldung fehlgeschlagen. Bitte überprüfe deine Angaben.';
$a->strings['Login failed because your account is blocked.'] = 'Die Anmeldung ist fehlgeschlagen, da der Account gesperrt wurde.';
$a->strings['Welcome %s'] = 'Willkommen %s';
$a->strings['Please upload a profile photo.'] = 'Bitte lade ein Profilbild hoch.';
$a->strings['OpenWebAuth: %1$s welcomes %2$s'] = 'OpenWebAuth: %1$s heißt %2$s herzlich willkommen';
$a->strings['Friendica Notification'] = 'Friendica-Benachrichtigung';
$a->strings['%1$s, %2$s Administrator'] = '%1$s, %2$s Administrator';
$a->strings['%s Administrator'] = 'der Administrator von %s';
$a->strings['thanks'] = 'danke';
$a->strings['YYYY-MM-DD or MM-DD'] = 'YYYY-MM-DD oder MM-DD';
$a->strings['Time zone: <strong>%s</strong> <a href="%s">Change in Settings</a>'] = 'Zeitzone: <strong>%s</strong> <a href="%s">Änderbar in den Einstellungen</a>';
$a->strings['never'] = 'nie';
$a->strings['less than a second ago'] = 'vor weniger als einer Sekunde';
$a->strings['year'] = 'Jahr';
$a->strings['years'] = 'Jahre';
$a->strings['months'] = 'Monate';
$a->strings['weeks'] = 'Wochen';
$a->strings['days'] = 'Tage';
$a->strings['hour'] = 'Stunde';
$a->strings['hours'] = 'Stunden';
$a->strings['minute'] = 'Minute';
$a->strings['minutes'] = 'Minuten';
$a->strings['second'] = 'Sekunde';
$a->strings['seconds'] = 'Sekunden';
$a->strings['in %1$d %2$s'] = 'in %1$d %2$s';
$a->strings['%1$d %2$s ago'] = '%1$d %2$s her';
$a->strings['Notification from Friendica'] = 'Benachrichtigung von Friendica';
$a->strings['Empty Post'] = 'Leerer Beitrag';
$a->strings['default'] = 'Standard';
$a->strings['greenzero'] = 'greenzero';
$a->strings['purplezero'] = 'purplezero';
$a->strings['easterbunny'] = 'easterbunny';
$a->strings['darkzero'] = 'darkzero';
$a->strings['comix'] = 'comix';
$a->strings['slackr'] = 'slackr';
$a->strings['Variations'] = 'Variationen';
$a->strings['Note'] = 'Hinweis';
$a->strings['Check image permissions if all users are allowed to see the image'] = 'Überprüfe, dass alle Benutzer die Berechtigung haben dieses Bild anzusehen';
$a->strings['Appearance'] = 'Erscheinungsbild';
$a->strings['Accent color'] = 'Akzentfarbe';
$a->strings['Blue'] = 'Blau';
$a->strings['Red'] = 'Rot';
$a->strings['Purple'] = 'Violett';
$a->strings['Green'] = 'Grün';
$a->strings['Pink'] = 'Rosa';
$a->strings['Copy or paste schemestring'] = 'Farbschema kopieren oder einfügen';
$a->strings['You can copy this string to share your theme with others. Pasting here applies the schemestring'] = 'Du kannst den String mit den Farbschema Informationen mit anderen Teilen. Wenn du einen neuen Farbschema-String hier einfügst wird er für deine Einstellungen übernommen.';
$a->strings['Navigation bar background color'] = 'Hintergrundfarbe der Navigationsleiste';
$a->strings['Navigation bar icon color '] = 'Icon Farbe in der Navigationsleiste';
$a->strings['Link color'] = 'Linkfarbe';
$a->strings['Set the background color'] = 'Hintergrundfarbe festlegen';
$a->strings['Content background opacity'] = 'Opazität des Hintergrunds von Beiträgen';
$a->strings['Set the background image'] = 'Hintergrundbild festlegen';
$a->strings['Background image style'] = 'Stil des Hintergrundbildes';
$a->strings['Always open Compose page'] = 'Immer die Composer Seite öffnen';
$a->strings['The New Post button always open the <a href="/compose">Compose page</a> instead of the modal form. When this is disabled, the Compose page can be accessed with a middle click on the link or from the modal.'] = 'Neue Beiträge werden immer in der <a href="/compose">Composer Seite</a> anstelle des Dialoges bearbeitet. Ist diese Option deaktiviert, kann die Composer Seite durch einen Klick mit der mittleren Maustaste geöffnet werden.';
$a->strings['Login page background image'] = 'Hintergrundbild der Login-Seite';
$a->strings['Login page background color'] = 'Hintergrundfarbe der Login-Seite';
$a->strings['Leave background image and color empty for theme defaults'] = 'Wenn die Theme-Vorgaben verwendet werden sollen, lass bitte die Felder für die Hintergrundfarbe und das Hintergrundbild leer.';
$a->strings['Top Banner'] = 'Top Banner';
$a->strings['Resize image to the width of the screen and show background color below on long pages.'] = 'Skaliere das Hintergrundbild so, dass es die Breite der Seite einnimmt, und fülle den Rest der Seite mit der Hintergrundfarbe bei langen Seiten.';
$a->strings['Full screen'] = 'Vollbildmodus';
$a->strings['Resize image to fill entire screen, clipping either the right or the bottom.'] = 'Skaliere das Bild so, dass es den gesamten Bildschirm füllt. Hierfür wird entweder die Breite oder die Höhe des Bildes automatisch abgeschnitten.';
$a->strings['Single row mosaic'] = 'Mosaik in einer Zeile';
$a->strings['Resize image to repeat it on a single row, either vertical or horizontal.'] = 'Skaliere das Bild so, dass es in einer einzelnen Reihe, entweder horizontal oder vertikal, wiederholt wird.';
$a->strings['Mosaic'] = 'Mosaik';
$a->strings['Repeat image to fill the screen.'] = 'Wiederhole das Bild, um den Bildschirm zu füllen.';
$a->strings['Back to top'] = 'Zurück nach Oben';
$a->strings['Light'] = 'Hell';
$a->strings['Dark'] = 'Dunkel';
$a->strings['Custom'] = 'Benutzerdefiniert';
$a->strings['Guest'] = 'Gast';
$a->strings['Visitor'] = 'Besucher';
$a->strings['Alignment'] = 'Ausrichtung';
$a->strings['Left'] = 'Links';
$a->strings['Center'] = 'Mitte';
$a->strings['Color scheme'] = 'Farbschema';
$a->strings['Posts font size'] = 'Schriftgröße in Beiträgen';
$a->strings['Textareas font size'] = 'Schriftgröße in Eingabefeldern';
$a->strings['Comma separated list of helper groups'] = 'Komma-separierte Liste der Helfer-Gruppen';
$a->strings['don\'t show'] = 'nicht zeigen';
$a->strings['show'] = 'zeigen';
$a->strings['Set style'] = 'Stil auswählen';
$a->strings['Community Pages'] = 'Gemeinschaftsseiten';
$a->strings['Community Profiles'] = 'Gemeinschaftsprofile';
$a->strings['Help or @NewHere ?'] = 'Hilfe oder @NewHere';
$a->strings['Connect Services'] = 'Verbinde Dienste';
$a->strings['Find Friends'] = 'Kontakte finden';
$a->strings['Last users'] = 'Letzte Nutzer';
$a->strings['Quick Start'] = 'Schnell-Start';
