<?php

if(! function_exists("string_plural_select_pl")) {
function string_plural_select_pl($n){
	$n = intval($n);
	if ($n==1) { return 0; } else if (($n%10>=2 && $n%10<=4) && ($n%100<12 || $n%100>14)) { return 1; } else if ($n!=1 && ($n%10>=0 && $n%10<=1) || ($n%10>=5 && $n%10<=9) || ($n%100>=12 && $n%100<=14)) { return 2; } else  { return 3; }
}}
$a->strings['Unable to locate original post.'] = 'Nie znaleziono oryginalnego wpisu.';
$a->strings['Post updated.'] = 'Wpis zaktualizowany.';
$a->strings['Item wasn\'t stored.'] = 'Obiekt nie został zapisany. ';
$a->strings['Item couldn\'t be fetched.'] = 'Nie można pobrać obiektu.';
$a->strings['Empty post discarded.'] = 'Pusty wpis odrzucony.';
$a->strings['Item not found.'] = 'Nie znaleziono obiektu.';
$a->strings['Permission denied.'] = 'Odmowa dostępu.';
$a->strings['No valid account found.'] = 'Nie znaleziono odpowiedniego konta.';
$a->strings['Password reset request issued. Check your email.'] = 'Prośba o zresetowanie hasła została złożona. Sprawdź swój e-mail.';
$a->strings['
		Dear %1$s,
			A request was recently received at "%2$s" to reset your account
		password. In order to confirm this request, please select the verification link
		below or paste it into your web browser address bar.

		If you did NOT request this change, please DO NOT follow the link
		provided and ignore and/or delete this email, the request will expire shortly.

		Your password will not be changed unless we can verify that you
		issued this request.'] = '
		Cześć %1$s,
			otrzymaliśmy prośbę o zresetowanie hasła do Twojego konta na %2$s.
		Aby kontynuować, użyj poniższego linku weryfikacyjnego lub skopiuj go
		do paska adresu przeglądarki internetowej.

		Jeśli to nie Ty składałeś prośbę o zresetowanie hasła, nie klikaj w ten link!
		Po prostu zignoruj tę wiadomość, a zgłoszenie samo wkrótce wygaśnie.

		Twoje hasło nie zostanie zmienione, dopóki nie będziemy mogli zweryfikować,
		że to Ty wysłałeś tę prośbę.';
$a->strings['
		Follow this link soon to verify your identity:

		%1$s

		You will then receive a follow-up message containing the new password.
		You may change that password from your account settings page after logging in.

		The login details are as follows:

		Site Location:	%2$s
		Login Name:	%3$s'] = '
		Kliknij w poniższy link, aby potwierdzić swoją tożsamość:

		%1$s

		Po weryfikacji otrzymasz kolejną wiadomość, zawierającą hasło do Twojego konta.
		Możesz zmienić to hasło w ustawieniach swojego konta po zalogowaniu.

		Twoje dane do logowania:

		Adres strony: 	%2$s
		Nazwa użytkownika:	%3$s';
$a->strings['Password reset requested at %s'] = 'Prośba o reset hasła na %s';
$a->strings['Request could not be verified. (You may have previously submitted it.) Password reset failed.'] = 'Nie można zweryfikować żądania. (Być może zostało ono już wcześniej przesłane). Nie udało się zresetować hasła.';
$a->strings['Request has expired, please make a new one.'] = 'Prośba wygasła, proszę złożyć nową.';
$a->strings['Forgot your Password?'] = 'Zapomniałeś hasła?';
$a->strings['Enter your email address and submit to have your password reset. Then check your email for further instructions.'] = 'Podaj swój adres e-mail i prześlij formularz, aby zresetować hasło. Następnie sprawdź swoją skrzynkę e-mail, gdzie znajdziesz dalsze instrukcje.';
$a->strings['Password Reset'] = 'Resetowanie hasła';
$a->strings['Your password has been reset as requested.'] = 'Zgodnie z prośbą, Twoje hasło zostało zresetowane.';
$a->strings['Your new password is'] = 'Twoje nowe hasło to';
$a->strings['Save or copy your new password - and then'] = 'Zapisz lub skopiuj swoje nowe hasło - a następnie';
$a->strings['click here to login'] = 'kliknij tutaj, aby się zalogować';
$a->strings['Your password may be changed from the <em>Settings</em> page after successful login.'] = 'Po zalogowaniu możesz zmienić swoje hasło na stronie <em>Ustawień</em> ';
$a->strings['Your password has been reset.'] = 'Twoje hasło zostało zresetowane.';
$a->strings['
			Dear %1$s,
				Your password has been changed as requested. Please retain this
			information for your records (or change your password immediately to
			something that you will remember).
		'] = '
		Cześć %1$s,
				zgodnie z prośbą, Twoje hasło zostało zmienione. Zachowaj tę wiadomość
			lub od razu zmień hasło na takie, które bez problemu zapamiętasz.
		';
$a->strings['
			Your login details are as follows:

			Site Location:	%1$s
			Login Name:	%2$s
			Password:	%3$s

			You may change that password from your account settings page after logging in.
		'] = '
			Twoje dane do logowania:

			Adres witryny:	%1$s
			Nazwa użytkownika:	%2$s
			Hasło:	%3$s

			Możesz zmienić hasło w ustawieniach swojego konta po zalogowaniu.
		';
$a->strings['Your password has been changed at %s'] = 'Twoje hasło na %s zostało zmienione';
$a->strings['Messages'] = 'Wiadomości';
$a->strings['New Message'] = 'Nowa wiadomość';
$a->strings['No recipient selected.'] = 'Nie wybrano odbiorcy.';
$a->strings['Unable to locate contact information.'] = 'Nie można znaleźć informacji o kontakcie.';
$a->strings['Message could not be sent.'] = 'Wiadomość nie mogła zostać wysłana.';
$a->strings['Message collection failure.'] = 'Błąd podczas pobierania wiadomości.';
$a->strings['Discard'] = 'Odrzuć';
$a->strings['Conversation not found.'] = 'Nie znaleziono wątku.';
$a->strings['Message was not deleted.'] = 'Wiadomość nie została usunięta.';
$a->strings['Conversation was not removed.'] = 'Wątek nie został usunięty.';
$a->strings['Please enter a link URL:'] = 'Wprowadź URL linku:';
$a->strings['Send Private Message'] = 'Wyślij prywatną wiadomość';
$a->strings['To:'] = 'Do:';
$a->strings['Subject:'] = 'Temat:';
$a->strings['Your message:'] = 'Twoja wiadomość:';
$a->strings['Upload photo'] = 'Wgraj zdjęcie';
$a->strings['Insert web link'] = 'Wstaw link';
$a->strings['Please wait'] = 'Proszę czekać';
$a->strings['You have no messages.'] = 'Nie masz żadnych wiadomości.';
$a->strings['Message not available.'] = 'Wiadomość niedostępna.';
$a->strings['Delete message'] = 'Usuń wiadomość';
$a->strings['D, d M Y - g:i A'] = 'D, d M Y - G:i';
$a->strings['Delete conversation'] = 'Usuń wątek';
$a->strings['No secure communications available. You <strong>may</strong> be able to respond from the sender\'s profile page.'] = 'Brak dostępnej bezpiecznej komunikacji. <strong>Możesz</strong> spróbować odpowiedzieć ze strony profilowej nadawcy.';
$a->strings['Send Reply'] = 'Wyślij odpowiedź';
$a->strings['Unknown sender - %s'] = 'Nieznany nadawca - %s';
$a->strings['You and %s'] = 'Ty i %s';
$a->strings['%s and You'] = '%s i ty';
$a->strings['%d message'] = [
	0 => '%d wiadomość',
	1 => '%d wiadomości',
	2 => '%d wiadomości',
	3 => '%d wiadomości',
];
$a->strings['Personal Notes'] = 'Notatki osobiste';
$a->strings['Personal notes are visible only by yourself.'] = 'Notatki osobiste są widoczne tylko dla Ciebie.';
$a->strings['Save'] = 'Zapisz';
$a->strings['User not found.'] = 'Nie znaleziono użytkownika.';
$a->strings['Photo Albums'] = 'Albumy zdjęć';
$a->strings['Recent Photos'] = 'Ostatnio dodane';
$a->strings['Upload Photos'] = 'Wgraj zdjęcia';
$a->strings['everybody'] = 'wszyscy';
$a->strings['Contact information unavailable'] = 'Informacje o kontakcie są niedostępne';
$a->strings['Album not found.'] = 'Nie znaleziono albumu.';
$a->strings['Album successfully deleted'] = 'Album został pomyślnie usunięty';
$a->strings['Album was empty.'] = 'Album był pusty.';
$a->strings['Failed to delete the photo.'] = 'Nie udało się usunąć zdjęcia.';
$a->strings['a photo'] = 'zdjęciu';
$a->strings['%1$s was tagged in %2$s by %3$s'] = '%1$s został otagowany na %2$s przez %3$s';
$a->strings['Public access denied.'] = 'Dostęp publiczny zabroniony.';
$a->strings['No photos selected'] = 'Nie wybrano zdjęć';
$a->strings['The maximum accepted image size is %s'] = 'Maksymalny akceptowany rozmiar zdjęcia to %s';
$a->strings['New album name: '] = 'Podaj nazwę nowego albumu: ';
$a->strings['or select existing album:'] = 'lub wybierz istniejący:';
$a->strings['Do not show a status post for this upload'] = 'Wgraj bez tworzenia wpisu';
$a->strings['Permissions'] = 'Dostęp';
$a->strings['Do you really want to delete this photo album and all its photos?'] = 'Czy na pewno chcesz usunąć ten album wraz z jego zawartością?';
$a->strings['Delete Album'] = 'Usuń album';
$a->strings['Cancel'] = 'Anuluj';
$a->strings['Save changes'] = 'Zapisz zmiany';
$a->strings['Edit Album'] = 'Edytuj album';
$a->strings['Show Newest First'] = 'Pokaż najpierw najnowsze';
$a->strings['Show Oldest First'] = 'Pokaż najpierw najstarsze';
$a->strings['View Photo'] = 'Pokaż zdjęcie';
$a->strings['Permission denied. Access to this item may be restricted.'] = 'Odmowa dostępu. Dostęp do tego obiektu może być zastrzeżony.';
$a->strings['Photo not available'] = 'Zdjęcie niedostępne';
$a->strings['Do you really want to delete this photo?'] = 'Czy na pewno chcesz usunąć to zdjęcie?';
$a->strings['Delete Photo'] = 'Usuń zdjęcie';
$a->strings['View photo'] = 'Pokaż zdjęcie';
$a->strings['Edit photo'] = 'Edytuj zdjęcie';
$a->strings['Delete photo'] = 'Usuń zdjęcie';
$a->strings['Private Photo'] = 'Prywatne zdjęcie';
$a->strings['View Full Size'] = 'Pokaż w pełnym rozmiarze';
$a->strings['Tags'] = 'Tagi';
$a->strings['[Select tags to remove]'] = '[Wybierz tagi do usunięcia]';
$a->strings['New album name'] = 'Nazwa nowego albumu';
$a->strings['Caption'] = 'Podpis';
$a->strings['Add a Tag'] = 'Dodaj tag';
$a->strings['Example: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping'] = 'Np. @jan, @jan_nowak, @jan@toja.pl, #Warszawa, #góry';
$a->strings['Do not rotate'] = 'Nie obracaj';
$a->strings['Rotate CW (right)'] = 'Obróć zgodnie z kierunkiem wskazówek zegara (w prawo)';
$a->strings['Rotate CCW (left)'] = 'Obróć w przeciwnym kierunku do ruchu wskazówek zegara (w lewo)';
$a->strings['This is you'] = 'To Ty';
$a->strings['Comment'] = 'Skomentuj';
$a->strings['Preview'] = 'Podgląd';
$a->strings['Loading...'] = 'Ładowanie...';
$a->strings['Select'] = 'Wybierz';
$a->strings['Delete'] = 'Usuń';
$a->strings['Like'] = 'Lubię';
$a->strings['I like this (toggle)'] = 'Lubię to';
$a->strings['Dislike'] = 'Nie lubię';
$a->strings['I don\'t like this (toggle)'] = 'Nie lubię tego';
$a->strings['Edit'] = 'Edytuj';
$a->strings['Map'] = 'Mapa';
$a->strings['Apologies but the website is unavailable at the moment.'] = 'Przepraszamy, witryna jest w tym momencie niedostępna.';
$a->strings['Delete this item?'] = 'Usunąć ten element?';
$a->strings['Block this author? They won\'t be able to follow you nor see your public posts, and you won\'t be able to see their posts and their notifications.'] = 'Zablokować tego autora? Nie będzie mógł Cię obserwować ani widzieć Twoich wpisów. Ty również nie zobaczysz jego wpisów ani nie otrzymasz od niego powiadomień.';
$a->strings['Ignore this author? You won\'t be able to see their posts and their notifications.'] = 'Czy ignorować tego autora? Nie zobaczysz już jego wpisów ani powiadomień.';
$a->strings['Collapse this author\'s posts?'] = 'Czy zwinąć wpisy tego autora?';
$a->strings['Ignore this author\'s server?'] = 'Czy ignorować serwer tego autora?';
$a->strings['You won\'t see any content from this server including reshares in your Network page, the community pages and individual conversations.'] = 'Nie zobaczysz żadnych treści z tego serwera, włączając w to treści przekazane dalej na stronie Twojej Sieci, stronach Społeczności oraz w indywidualnych wątkach.';
$a->strings['Like not successful'] = 'Polubienie nie powiodło się';
$a->strings['Dislike not successful'] = '"Nie lubię" nie powiodło się';
$a->strings['Sharing not successful'] = 'Przekazanie dalej nie powiodło się';
$a->strings['Attendance unsuccessful'] = 'Nieudane zgłoszenie uczestnictwa';
$a->strings['Backend error'] = 'Błąd zaplecza strony';
$a->strings['Network error'] = 'Błąd sieci';
$a->strings['Drop files here to upload'] = 'Upuść tutaj pliki do wgrania';
$a->strings['Your browser does not support drag and drop file uploads.'] = 'Twoja przeglądarka nie obsługuje przesyłania plików za pomocą funkcji "przeciągnij i upuść".';
$a->strings['Please use the fallback form below to upload your files like in the olden days.'] = 'Proszę skorzystać z poniższego formularza awaryjnego, aby wgrać pliki w tradycyjny sposób.';
$a->strings['File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.'] = 'Plik jest za duży ({{filesize}}MiB). Maksymalny rozmiar pliku: {{maxFilesize}}MiB.';
$a->strings['You can\'t upload files of this type.'] = 'Nie możesz wgrywać plików o tym formacie.';
$a->strings['Server responded with {{statusCode}} code.'] = 'Odpowiedź serwera: {{statusCode}} ';
$a->strings['Cancel upload'] = 'Anuluj wgrywanie';
$a->strings['Upload canceled.'] = 'Wgrywanie anulowane.';
$a->strings['Are you sure you want to cancel this upload?'] = 'Czy na pewno chcesz anulować wgrywanie plików?';
$a->strings['Remove file'] = 'Usuń plik';
$a->strings['You can\'t upload any more files.'] = 'Nie możesz wgrać więcej plików.';
$a->strings['toggle mobile'] = 'przełącz widok mobilny';
$a->strings['Close'] = 'Zamknij';
$a->strings['Method not allowed for this module. Allowed method(s): %s'] = 'Metoda niedozwolona dla tego modułu. Dozwolona(-e) metoda(-y): %s';
$a->strings['Page not found.'] = 'Nie znaleziono strony.';
$a->strings['You must be logged in to use addons. '] = 'Musisz się zalogować, aby móc korzystać z dodatków. ';
$a->strings['No system theme config value set.'] = 'Nie ustawiono wartości konfiguracji motywu systemu.';
$a->strings['The form security token was not correct. This probably happened because the form has been opened for too long (>3 hours) before submitting it.'] = 'Token zabezpieczeń formularza nie był poprawny. Prawdopodobnie formularz byl otwarty zbyt długo (ponad 3 godziny) przed jego przesłaniem.';
$a->strings['All contacts'] = 'Wszystkie kontakty';
$a->strings['Followers'] = 'Obserwujący';
$a->strings['Following'] = 'Obserwowani';
$a->strings['Friends'] = 'Znajomi';
$a->strings['Common'] = 'Wspólne kontakty';
$a->strings['Addon not found'] = 'Dodatek nieznaleziony';
$a->strings['Addon already enabled'] = 'Dodatek jest już włączony';
$a->strings['Addon already disabled'] = 'Dodatek jest już wyłączony';
$a->strings['Could not find any unarchived contact entry for this URL (%s)'] = 'Nie znaleziono żadnej pozycji niezarchiwizowanego kontaktu dla tego URL (%s)';
$a->strings['The contact entries have been archived'] = 'Pozycje kontaktu zostały zarchiwizowane';
$a->strings['The avatar cache needs to be disabled in local.config.php to use this command.'] = 'Aby użyć tego polecenia, należy wyłączyć pamięć podręczną awatarów w pliku local.config.php.';
$a->strings['Could not find any contact entry for this URL (%s)'] = 'Nie można znaleźć żadnego kontaktu dla tego adresu URL (%s)';
$a->strings['The contact has been blocked from the node'] = 'Kontakt został zablokowany na tej instancji';
$a->strings['%d %s, %d duplicates.'] = '%d %s, duplikaty: %d.';
$a->strings['uri-id is empty for contact %s.'] = 'uri-id jest pusty dla kontaktu %s.';
$a->strings['No valid first contact found for uri-id %d.'] = 'Nie znaleziono odpowiedniego kontaktu początkowego dla uri-id %d.';
$a->strings['Wrong duplicate found for uri-id %d in %d (url: %s != %s).'] = 'Odnaleziono nieprawidłowy duplikat dla uri-id %d w %d (url: %s != %s).';
$a->strings['Wrong duplicate found for uri-id %d in %d (nurl: %s != %s).'] = 'Odnaleziono nieprawidłowy duplikat dla uri-id %d w %d (nurl: %s != %s).';
$a->strings['Deletion of id %d failed'] = 'Nie udało się usunąć ID %d';
$a->strings['Deletion of id %d was successful'] = 'Usunięcie ID %d powiodło się';
$a->strings['Updating "%s" in "%s" from %d to %d'] = 'Aktualizacja "%s" w "%s" z %d na %d';
$a->strings[' - found'] = '- znaleziono';
$a->strings[' - failed'] = ' - błąd';
$a->strings[' - success'] = ' - sukces';
$a->strings[' - deleted'] = ' - usunięto';
$a->strings[' - done'] = ' - ukończono';
$a->strings['The avatar cache needs to be enabled to use this command.'] = 'Aby użyć tej komendy, pamięć podręczna awatarów musi być włączona.';
$a->strings['no resource in photo %s'] = 'brak zasobu w zdjęciu %s';
$a->strings['no photo with id %s'] = 'brak zdjęcia o ID %s';
$a->strings['no image data for photo with id %s'] = 'brak danych obrazu dla zdjęcia o ID %s';
$a->strings['invalid image for id %s'] = 'nieprawidłowy obraz dla ID %s';
$a->strings['Quit on invalid photo %s'] = 'Zakończenie na nieprawidłowym zdjęciu %s';
$a->strings['Post update version number has been set to %s.'] = 'Numer wersji poaktualizacyjnej został ustawiony na %s.';
$a->strings['Check for pending update actions.'] = 'Sprawdzenie oczekujących działań aktualizacyjnych.';
$a->strings['Done.'] = 'Gotowe.';
$a->strings['Execute pending post updates.'] = 'Wykonanie oczekujących działań poaktualizacyjnych.';
$a->strings['All pending post updates are done.'] = 'Wszystkie oczekujące działania poaktualizacyjne zostały wykonane.';
$a->strings['Enter user nickname: '] = 'Podaj alias użytkownika: ';
$a->strings['User not found'] = 'Nie znaleziono użytkownika';
$a->strings['Enter new password: '] = 'Wprowadź nowe hasło: ';
$a->strings['Password update failed. Please try again.'] = 'Aktualizacja hasła nie powiodła się. Proszę spróbować ponownie.';
$a->strings['Password changed.'] = 'Hasło zostało zmienione.';
$a->strings['Enter user name: '] = 'Podaj nazwę użytkownika: ';
$a->strings['Enter user email address: '] = 'Podaj adres e-mail użytkownika:';
$a->strings['Enter a language (optional): '] = 'Podaj język (opcjonalnie):';
$a->strings['Enter URL of an image to use as avatar (optional): '] = 'Podaj adres URL obrazka, który ma być używany jako awatar (opcjonalnie):';
$a->strings['User is not pending.'] = 'Użytkownik nie oczekuje na zatwierdzenie.';
$a->strings['User has already been marked for deletion.'] = 'Użytkownik jest już przeznaczony do usunięcia.';
$a->strings['Type "yes" to delete %s'] = 'Wpisz „tak”, aby usunąć %s';
$a->strings['Deletion aborted.'] = 'Przerwano usuwanie.';
$a->strings['Enter category: '] = 'Podaj kategorię: ';
$a->strings['Enter key: '] = 'Podaj klucz: ';
$a->strings['Enter value: '] = 'Podaj wartość: ';
$a->strings['newer'] = 'nowsze';
$a->strings['older'] = 'starsze';
$a->strings['Frequently'] = 'Na bieżąco';
$a->strings['Hourly'] = 'Co godzinę';
$a->strings['Twice daily'] = 'Dwa razy dziennie';
$a->strings['Daily'] = 'Codziennie';
$a->strings['Weekly'] = 'Co tydzień';
$a->strings['Monthly'] = 'Co miesiąc';
$a->strings['DFRN'] = 'DFRN';
$a->strings['OStatus'] = 'OStatus';
$a->strings['RSS/Atom'] = 'RSS/Atom';
$a->strings['Email'] = 'Email';
$a->strings['Diaspora'] = 'Diaspora';
$a->strings['Zot!'] = 'Zot!';
$a->strings['LinkedIn'] = 'LinkedIn';
$a->strings['XMPP/IM'] = 'XMPP/IM';
$a->strings['MySpace'] = 'MySpace';
$a->strings['Google+'] = 'Google+';
$a->strings['pump.io'] = 'pump.io';
$a->strings['Twitter'] = 'Twitter';
$a->strings['Discourse'] = 'Discourse';
$a->strings['Diaspora Connector'] = 'Integracja Diaspory';
$a->strings['GNU Social Connector'] = 'Integracja GNU Social';
$a->strings['ActivityPub'] = 'ActivityPub';
$a->strings['pnut'] = 'pnut';
$a->strings['Tumblr'] = 'Tumblr';
$a->strings['Bluesky'] = 'Bluesky';
$a->strings['%s (via %s)'] = '%s (przez %s)';
$a->strings['and'] = 'i';
$a->strings['and %d other people'] = 'i inni (%d)';
$a->strings['%2$s likes this.'] = [
	0 => '%2$s lubi to.',
	1 => '%2$s lubi to.',
	2 => '%2$s lubi to.',
	3 => '%2$s lubi to. ',
];
$a->strings['%2$s doesn\'t like this.'] = [
	0 => '%2$s nie lubi tego.',
	1 => '%2$s nie lubi tego.',
	2 => '%2$s nie lubi tego.',
	3 => '%2$s nie lubi tego.',
];
$a->strings['%2$s attends.'] = [
	0 => '%2$s uczestniczy.',
	1 => '%2$s uczestniczą.',
	2 => '%2$s uczestniczy.',
	3 => '%2$s uczestniczy.',
];
$a->strings['%2$s doesn\'t attend.'] = [
	0 => '%2$s nie uczestniczy.',
	1 => '%2$s nie uczestniczą.',
	2 => '%2$s nie uczestniczy.',
	3 => '%2$s nie uczestniczy.',
];
$a->strings['%2$s attends maybe.'] = [
	0 => '%2$s niezdecydowany.',
	1 => '%2$s niezdecydowane.',
	2 => '%2$s niezdecydowanych.',
	3 => '%2$s niezdecydowanych.',
];
$a->strings['%2$s reshared this.'] = [
	0 => '%2$s przekazuje dalej.',
	1 => '%2$s przekazało dalej.',
	2 => '%2$s przekazało dalej.',
	3 => '%2$s przekazało dalej.',
];
$a->strings['<button type="button" %2$s>%1$d person</button> likes this'] = [
	0 => '<button type="button" %2$s>%1$d osoba </button> lubi to',
	1 => '<button type="button" %2$s>%1$d osoby </button> lubią to',
	2 => '<button type="button" %2$s>%1$d osób </button> lubi to',
	3 => '<button type="button" %2$s>%1$d osób </button> lubi to',
];
$a->strings['<button type="button" %2$s>%1$d person</button> attends'] = [
	0 => '<button type="button" %2$s>%1$d osoba</button> bierze udział',
	1 => '<button type="button" %2$s>%1$d osoby</button> biorą udział',
	2 => '<button type="button" %2$s>%1$d osób</button> bierze udział',
	3 => '<button type="button" %2$s>%1$d osób</button> bierze udział',
];
$a->strings['<button type="button" %2$s>%1$d person</button> doesn\'t attend'] = [
	0 => '<button type="button" %2$s>%1$d osoba</button> nie bierze udziału',
	1 => '<button type="button" %2$s>%1$d osoby</button> nie biorą udziału',
	2 => '<button type="button" %2$s>%1$d osób</button> nie bierze udziału',
	3 => '<button type="button" %2$s>%1$d osób</button> nie bierze udziału',
];
$a->strings['<button type="button" %2$s>%1$d person</button> attends maybe'] = [
	0 => '<button type="button" %2$s>%1$d osoba</button> niezdecydowana',
	1 => '<button type="button" %2$s>%1$d osoby</button> niezdecydowane',
	2 => '<button type="button" %2$s>%1$d osób</button> niezdecydowanych',
	3 => '<button type="button" %2$s>%1$d osób</button> niezdecydowanych',
];
$a->strings['<button type="button" %2$s>%1$d person</button> reshared this'] = [
	0 => '<button type="button" %2$s>%1$d osoba</button> przekazała to dalej',
	1 => '<button type="button" %2$s>%1$d osób</button> przekazało to dalej',
	2 => '<button type="button" %2$s>%1$d osób</button> przekazało to dalej',
	3 => '<button type="button" %2$s>%1$d osób</button> przekazało to dalej',
];
$a->strings['Visible to <strong>everybody</strong>'] = 'Widoczne dla <strong>wszystkich</strong>';
$a->strings['Please enter a image/video/audio/webpage URL:'] = 'Wprowadź URL obrazka/wideo/audio/witryny:';
$a->strings['Tag term:'] = 'Nazwa tagu:';
$a->strings['Save to Folder'] = 'Zapisz w katalogu';
$a->strings['Where are you right now?'] = 'Gdzie teraz jesteś?';
$a->strings['Delete item(s)?'] = 'Usunąć pozycję/-e?';
$a->strings['Created at'] = 'Utworzono';
$a->strings['New Post'] = 'Nowy wpis';
$a->strings['Share'] = 'Podziel się';
$a->strings['upload photo'] = 'wgraj zdjęcie';
$a->strings['Attach file'] = 'Załącz plik';
$a->strings['attach file'] = 'załącz plik';
$a->strings['Bold'] = 'Pogrubienie';
$a->strings['Italic'] = 'Kursywa';
$a->strings['Underline'] = 'Podkreślenie';
$a->strings['Quote'] = 'Cytat';
$a->strings['Add emojis'] = 'Emotikony';
$a->strings['Content Warning'] = 'Ostrzeżenie o treści';
$a->strings['Code'] = 'Kod';
$a->strings['Image'] = 'Obrazek';
$a->strings['Link'] = 'Link';
$a->strings['Link or Media'] = 'Link lub media';
$a->strings['Video'] = 'Wideo';
$a->strings['Set your location'] = 'Podaj swoją lokalizację';
$a->strings['set location'] = 'wybierz lokalizację';
$a->strings['Clear browser location'] = 'Wyczyść lokalizację przeglądarki';
$a->strings['clear location'] = 'wyczyść lokalizację';
$a->strings['Set title'] = 'Podaj tytuł';
$a->strings['Categories (comma-separated list)'] = 'Kategorie (oddzielone przecinkami)';
$a->strings['Scheduled at'] = 'Zaplanuj na';
$a->strings['Permission settings'] = 'Ustawienia dostępu';
$a->strings['Public post'] = 'Wpis publiczny';
$a->strings['Message'] = 'Wiadomość';
$a->strings['Open Compose page'] = 'Otwórz stronę tworzenia wpisu';
$a->strings['remove'] = 'usuń';
$a->strings['Delete Selected Items'] = 'Usuń wybrane obiekty';
$a->strings['You had been addressed (%s).'] = 'Zostałeś dodany jako adresat (%s).';
$a->strings['You are following %s.'] = 'Obserwujesz %s.';
$a->strings['You subscribed to %s.'] = 'Subskrybujesz %s.';
$a->strings['You subscribed to one or more tags in this post.'] = 'Subskrybujesz jeden lub więcej tagów w tym wpisie.';
$a->strings['%s reshared this.'] = '%s przekazuje dalej.';
$a->strings['Reshared'] = 'Przekazane dalej';
$a->strings['Reshared by %s <%s>'] = 'Przekazane dalej przez %s <%s>';
$a->strings['%s is participating in this thread.'] = '%s bierze udział w tym wątku.';
$a->strings['Stored for general reasons'] = 'Zachowano z ogólnych powodów';
$a->strings['Global post'] = 'Wpis globalny';
$a->strings['Sent via an relay server'] = 'Nadesłane przez przekaźnik';
$a->strings['Sent via the relay server %s <%s>'] = 'Nadesłane przez przekaźnik %s <%s>';
$a->strings['Fetched'] = 'Ściągnięto';
$a->strings['Fetched because of %s <%s>'] = 'Ściągnięto dzięki %s <%s>';
$a->strings['Stored because of a child post to complete this thread.'] = 'Zachowano w związku z wpisem podrzędnym w celu uzupełnienia wątku.';
$a->strings['Local delivery'] = 'Dostarczone lokalnie';
$a->strings['Stored because of your activity (like, comment, star, ...)'] = 'Zachowano z powodu Twojej aktywności (polubienie, komentarz, gwiazdka, ...)';
$a->strings['Distributed'] = 'Rozesłane';
$a->strings['Pushed to us'] = 'Przesłane do nas';
$a->strings['Pinned item'] = 'Przypięty obiekt';
$a->strings['View %s\'s profile @ %s'] = 'Pokaż profil %s @ %s';
$a->strings['Categories:'] = 'Kategorie:';
$a->strings['Filed under:'] = 'Umieszczono w:';
$a->strings['%s from %s'] = '%s od %s';
$a->strings['View in context'] = 'Zobacz kontekst';
$a->strings['For you'] = 'Dla Ciebie';
$a->strings['Posts from contacts you interact with and who interact with you'] = 'Wpisy od kontaktów, z którymi wchodzisz w interakcję i które wchodzą w interakcję z Tobą';
$a->strings['Discover'] = 'Odkryj';
$a->strings['Posts from accounts that you don\'t follow, but that you might like.'] = 'Wpisy profili, których nie obserwujesz, ale które mogą Ci się spodobać.';
$a->strings['What\'s Hot'] = 'Na czasie';
$a->strings['Posts with a lot of interactions'] = 'Wpisy z dużą liczbą interakcji';
$a->strings['Posts in %s'] = 'Wpisy w języku %s';
$a->strings['Posts from your followers that you don\'t follow'] = 'Wpisy profili, które obserwują Ciebie, ale Ty nie obserwujesz ich';
$a->strings['Sharers of sharers'] = 'Kontakty obserwowanych';
$a->strings['Posts from accounts that are followed by accounts that you follow'] = 'Wpisy profili obserwowanych przez konta, które obserwujesz';
$a->strings['Quiet sharers'] = 'Rzadko wpisujący';
$a->strings['Posts from accounts that you follow but who don\'t post very often'] = 'Wpisy profili, które obserwujesz, ale które rzadko publikują';
$a->strings['Images'] = 'Obrazki';
$a->strings['Posts with images'] = 'Wpisy z obrazkami';
$a->strings['Audio'] = 'Audio';
$a->strings['Posts with audio'] = 'Wpisy z muzyką';
$a->strings['Videos'] = 'Wideo';
$a->strings['Posts with videos'] = 'Wpisy z filmami';
$a->strings['Local Community'] = 'Lokalna społeczność';
$a->strings['Posts from local users on this server'] = 'Wpisy od lokalnych użytkowników na tym serwerze';
$a->strings['Global Community'] = 'Globalna społeczność';
$a->strings['Posts from users of the whole federated network'] = 'Wpisy od użytkowników całej sieci stowarzyszonej';
$a->strings['Latest Activity'] = 'Ostatnia Aktywność';
$a->strings['Sort by latest activity'] = 'Sortuj wg. ostatniej aktywności';
$a->strings['Latest Posts'] = 'Ostatnie wpisy';
$a->strings['Sort by post received date'] = 'Sortuj wg. daty otrzymania wpisu';
$a->strings['Latest Creation'] = 'Ostatnio utworzone';
$a->strings['Sort by post creation date'] = 'Sortuj wg. daty utworzenia wpisu';
$a->strings['Personal'] = 'Mój profil';
$a->strings['Posts that mention or involve you'] = 'Wpisy, które wspominają lub angażują Ciebie';
$a->strings['Starred'] = 'Oznaczone gwiazdką';
$a->strings['Favourite Posts'] = 'Ulubione wpisy';
$a->strings['General Features'] = 'Funkcje ogólne';
$a->strings['Photo Location'] = 'Lokalizacja zdjęcia';
$a->strings['Photo metadata is normally stripped. This extracts the location (if present) prior to stripping metadata and links it to a map.'] = 'Metadane zdjęć są domyślnie usuwane. Ta opcja wyodrębnie położenie (jeśli jest obecne) przed usunięciem metadanych i podpina je do mapy.';
$a->strings['Display the community in the navigation'] = 'Wyświetl społeczność w nawigacji';
$a->strings['If enabled, the community can be accessed via the navigation menu. Independent from this setting, the community timelines can always be accessed via the channels.'] = 'Jeśli włączone, dostęp do społeczności można uzyskać za pośrednictwem menu nawigacyjnego. Niezależnie od tego ustawienia, dostęp do osi czasu społeczności jest zawsze możliwy poprzez kanały.';
$a->strings['Post Composition Features'] = 'Funkcje tworzenia wpisu';
$a->strings['Explicit Mentions'] = 'Wyraźne wzmianki';
$a->strings['Add explicit mentions to comment box for manual control over who gets mentioned in replies.'] = 'Dodaj bezpośrednie wzmianki do pola komentarza, aby ręcznie kontrolować, kto zostanie wymieniony w odpowiedziach.';
$a->strings['Add an abstract from ActivityPub content warnings'] = 'Dodaj nagłówek z ostrzeżeniem o treści z ActivityPub';
$a->strings['Add an abstract when commenting on ActivityPub posts with a content warning. Abstracts are displayed as content warning on systems like Mastodon or Pleroma.'] = 'Dodaj nagłówek do komentarza dotyczącego wpisu w ActivityPub z ostrzeżeniem o treści. Nagłówek ten będzie wyświetlany jako ostrzeżenie o treści w systemach takich jak Mastodon czy Pleroma.';
$a->strings['Post/Comment Tools'] = 'Narzędzia wpisu/komentarza';
$a->strings['Post Categories'] = 'Kategorie wpisu';
$a->strings['Add categories to your posts'] = 'Umożliwia dodawanie kategorii do Twoich wpisów';
$a->strings['Network Widgets'] = 'Widżety Sieci';
$a->strings['Circles'] = 'Kręgi';
$a->strings['Display posts that have been created by accounts of the selected circle.'] = 'Wyświetl wpisy, które zostały utworzone przez konta z wybranego kręgu.';
$a->strings['Groups'] = 'Grupy';
$a->strings['Display posts that have been distributed by the selected group.'] = 'Wyświetl wpisy, które zostały rozpowszechnione przez wybraną grupę.';
$a->strings['Archives'] = 'Archiwa';
$a->strings['Display an archive where posts can be selected by month and year.'] = 'Wyświetl archiwum, w którym można przeglądać wpisy według miesiąca i roku.';
$a->strings['Protocols'] = 'Protokoły';
$a->strings['Display posts with the selected protocols.'] = 'Wyświetl wpisy z wybranych protokołów';
$a->strings['Account Types'] = 'Rodzaje kont';
$a->strings['Display posts done by accounts with the selected account type.'] = 'Wyświetl wpisy utworzone przez konta wybranego typu.';
$a->strings['Channels'] = 'Kanały';
$a->strings['Display posts in the system channels and user defined channels.'] = 'Wyświetl wpisy w kanałach systemowych i zdefiniowanych przez użytkownika.';
$a->strings['Saved Searches'] = 'Zapisane wyszukiwania';
$a->strings['Display posts that contain subscribed hashtags.'] = 'Wyświetl wpisy zawierające subskrybowane tagi.';
$a->strings['Saved Folders'] = 'Zapisane katalogi';
$a->strings['Display a list of folders in which posts are stored.'] = 'Wyświetl listę folderów, w których przechowywane są wpisy.';
$a->strings['Own Contacts'] = 'Twoje kontakty';
$a->strings['Include or exclude posts from subscribed accounts. This widget is not visible on all channels.'] = 'Dodaj lub wyklucz wpisy subskrybowanych kont. Ten widżet nie jest widoczny na wszystkich kanałach.';
$a->strings['Trending Tags'] = 'Popularne tagi';
$a->strings['Display a list of the most popular tags in recent public posts.'] = 'Wyświetl listę najpopularniejszych tagów w ostatnich publicznych wpisach.';
$a->strings['Advanced Profile Settings'] = 'Dodatkowe ustawienia profilu';
$a->strings['Tag Cloud'] = 'Chmura tagów';
$a->strings['Provide a personal tag cloud on your profile page'] = 'Wyświetl osobistą chmurę tagów na stronie swojego profilu';
$a->strings['Display Membership Date'] = 'Wyświetl datę członkostwa';
$a->strings['Display membership date in profile'] = 'Wyświetl datę utworzenia profilu';
$a->strings['Advanced Calendar Settings'] = 'Dodatkowe ustawienia kalendarza';
$a->strings['Allow anonymous access to your calendar'] = 'Zezwól na anonimowy dostęp do swojego kalendarza';
$a->strings['Allows anonymous visitors to consult your calendar and your public events. Contact birthday events are private to you.'] = 'Pozwala anonimowym odwiedzającym przeglądać Twój kalendarz i wydarzenia publiczne. Dni urodzin Twoich kontaktów pozostaną widoczne tylko dla Ciebie.';
$a->strings['External link to group'] = 'Zewnętrzny link do grupy';
$a->strings['show less'] = 'pokaż mniej';
$a->strings['show more'] = 'pokaż więcej';
$a->strings['Create new group'] = 'Utwórz nową grupę';
$a->strings['event'] = 'wydarzenie';
$a->strings['status'] = 'status';
$a->strings['photo'] = 'zdjęcie';
$a->strings['%1$s tagged %2$s\'s %3$s with %4$s'] = '%3$s od %2$s został otagowany przez %1$s jako %4$s';
$a->strings['Follow Thread'] = 'Obserwuj wątek';
$a->strings['View Status'] = 'Zobacz status';
$a->strings['View Profile'] = 'Zobacz profil';
$a->strings['View Photos'] = 'Zobacz zdjęcia';
$a->strings['Network Posts'] = 'Wpisy w Sieci';
$a->strings['View Contact'] = 'Zobacz kontakt';
$a->strings['Block'] = 'Zablokuj';
$a->strings['Ignore'] = 'Ignoruj';
$a->strings['Collapse'] = 'Zwiń';
$a->strings['Ignore %s server'] = 'Ignoruj serwer %s';
$a->strings['Connect/Follow'] = 'Połącz/Obserwuj';
$a->strings['Unable to fetch user.'] = 'Nie można pobrać informacji o użytkowniku.';
$a->strings['Undetermined'] = 'Nieokreślony';
$a->strings['%s (%s - %s): %s'] = '%s (%s - %s): %s';
$a->strings['%s (%s): %s'] = '%s (%s): %s';
$a->strings['Detected languages in this post:
%s'] = 'Wykryte języki w tym wpisie:
%s';
$a->strings['Nothing new here'] = 'Nic nowego';
$a->strings['Home'] = 'Dom';
$a->strings['Skip to main content'] = 'Przejdź do głównej zawartości';
$a->strings['Clear notifications'] = 'Wyczyść powiadomienia';
$a->strings['Sign out'] = 'Wyloguj';
$a->strings['End this session'] = 'Zakończ tę sesję';
$a->strings['Sign in'] = 'Zaloguj się';
$a->strings['Conversations'] = 'Wątki';
$a->strings['Conversations you started'] = 'Wątki rozpoczęte przez Ciebie';
$a->strings['Profile'] = 'Profil';
$a->strings['Your profile page'] = 'Strona Twojego profilu';
$a->strings['Photos'] = 'Zdjęcia';
$a->strings['Your photos'] = 'Twoje zdjęcia';
$a->strings['Media'] = 'Media';
$a->strings['Your postings with media'] = 'Twoje wpisy zawierające media';
$a->strings['Calendar'] = 'Kalendarz';
$a->strings['Your calendar'] = 'Twój kalendarz';
$a->strings['Personal notes'] = 'Notatki osobiste';
$a->strings['Your personal notes'] = 'Twoje osobiste notatki';
$a->strings['Home Page'] = 'Strona domowa';
$a->strings['Register'] = 'Zarejestruj';
$a->strings['Create an account'] = 'Utwórz konto';
$a->strings['Help'] = 'Pomoc';
$a->strings['Help and documentation'] = 'Pomoc i dokumentacja';
$a->strings['Apps'] = 'Aplikacje';
$a->strings['Addon applications, utilities, games'] = 'Dodatkowe aplikacje, narzędzia, gry';
$a->strings['Search'] = 'Szukaj';
$a->strings['Search site content'] = 'Przeszukaj zawartość strony';
$a->strings['Full Text'] = 'Pełny tekst';
$a->strings['Contacts'] = 'Kontakty';
$a->strings['Community'] = 'Społeczność';
$a->strings['Conversations on this and other servers'] = 'Wątki na tym i innych serwerach';
$a->strings['Directory'] = 'Katalog';
$a->strings['People directory'] = 'Katalog osób';
$a->strings['Information'] = 'Informacje';
$a->strings['Information about this friendica instance'] = 'Informacje o tej instancji friendica';
$a->strings['Terms of Service'] = 'Warunki usługi';
$a->strings['Terms of Service of this Friendica instance'] = 'Warunki świadczenia usług tej instancji Friendica';
$a->strings['Network'] = 'Sieć';
$a->strings['Conversations from your friends'] = 'Wątki Twoich kontaktów';
$a->strings['Your posts and conversations'] = 'Twoje wpisy i wątki';
$a->strings['Introductions'] = 'Zaproszenia do znajomych';
$a->strings['Friend Requests'] = 'Prośby o przyjęcie do grona znajomych';
$a->strings['Notifications'] = 'Powiadomienia';
$a->strings['Mark all system notifications as seen'] = 'Oznacz wszystkie powiadomienia jako wyświetlone';
$a->strings['Private mail'] = 'Prywatna poczta';
$a->strings['Inbox'] = 'Odebrane';
$a->strings['Outbox'] = 'Wysłane';
$a->strings['Accounts'] = 'Konta';
$a->strings['Settings'] = 'Ustawienia';
$a->strings['Account settings'] = 'Ustawienia konta';
$a->strings['Manage/edit friends and contacts'] = 'Zarządzaj znajomymi i kontaktami';
$a->strings['Admin'] = 'Administracja';
$a->strings['Site setup and configuration'] = 'Konfiguracja i ustawienia strony';
$a->strings['Moderation'] = 'Moderacja';
$a->strings['Content and user moderation'] = 'Moderacja treści i użytkowników';
$a->strings['Navigation'] = 'Nawigacja';
$a->strings['Site map'] = 'Mapa strony';
$a->strings['first'] = 'pierwsza';
$a->strings['prev'] = 'poprzednia';
$a->strings['next'] = 'następna';
$a->strings['last'] = 'ostatnia';
$a->strings['<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> %3$s'] = '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> %3$s';
$a->strings['Link to source'] = 'Link do źródła';
$a->strings['Click to open/close'] = 'Kliknij aby otworzyć/zamknąć';
$a->strings['$1 wrote:'] = '$1 napisał/-a:';
$a->strings['Encrypted content'] = 'Szyfrowana zawartość';
$a->strings['Invalid source protocol'] = 'Nieprawidłowy protokół źródła';
$a->strings['Invalid link protocol'] = 'Nieprawidłowy protokół linku';
$a->strings['Loading more entries...'] = 'Wczytywanie kolejnych pozycji...';
$a->strings['The end'] = 'Koniec';
$a->strings['@name, !group, #tags, content'] = '@nazwa, !grupa, #tag, treść';
$a->strings['Add New Contact'] = 'Dodaj nowy kontakt';
$a->strings['Enter address or web location'] = 'Wpisz adres lub lokalizację sieciową';
$a->strings['user@x.tld, x.tld/user'] = 'nazwa@x.tld, x.tld/nazwa';
$a->strings['Connect'] = 'Połącz';
$a->strings['%d invitation available'] = [
	0 => '%d zaproszenie dostępne',
	1 => '%d zaproszeń dostępnych',
	2 => '%d zaproszenia dostępne',
	3 => '%d zaproszenia dostępne',
];
$a->strings['Find People'] = 'Szukaj profili';
$a->strings['Enter name or interest'] = 'Wpisz nazwę/imię lub temat';
$a->strings['Examples: Robert Morgenstein, Fishing'] = 'Przykład: Jan Nowak, wędkarstwo';
$a->strings['Find'] = 'Szukaj';
$a->strings['Friend Suggestions'] = 'Sugestie znajomych';
$a->strings['Similar Interests'] = 'Podobne zainteresowania';
$a->strings['Random Profile'] = 'Losowy profil';
$a->strings['Invite Friends'] = 'Zaproś znajomych';
$a->strings['Global Directory'] = 'Katalog globalny';
$a->strings['Local Directory'] = 'Katalog lokalny';
$a->strings['Everyone'] = 'Wszyscy';
$a->strings['No relationship'] = 'Brak powiązania';
$a->strings['Relationships'] = 'Relacje';
$a->strings['All Contacts'] = 'Wszystkie kontakty';
$a->strings['All Protocols'] = 'Wszystkie protokoły';
$a->strings['Everything'] = 'Wszystko';
$a->strings['Categories'] = 'Kategorie';
$a->strings['%d contact in common'] = [
	0 => '%d wspólny kontakt',
	1 => '%d wspólne kontakty',
	2 => '%d wspólnych kontaktów',
	3 => '%d wspólnych kontaktów',
];
$a->strings['On this date'] = 'Tego dnia';
$a->strings['Persons'] = 'Osoby';
$a->strings['Organisations'] = 'Organizacje';
$a->strings['News'] = 'Wiadomości';
$a->strings['Relays'] = 'Przekaźniki';
$a->strings['All'] = 'Wszyscy';
$a->strings['Export'] = 'Eksport';
$a->strings['Export calendar as ical'] = 'Eksportuj kalendarz jako ical';
$a->strings['Export calendar as csv'] = 'Eksportuj kalendarz jako csv';
$a->strings['No contacts'] = 'Brak kontaktów';
$a->strings['%d Contact'] = [
	0 => '%d kontakt',
	1 => '%d kontaktów',
	2 => '%d kontakty',
	3 => '%d Kontakty',
];
$a->strings['View Contacts'] = 'Zobacz kontakty';
$a->strings['Remove term'] = 'Usuń hasło';
$a->strings['More Trending Tags'] = 'Więcej popularnych tagów';
$a->strings['Show More'] = 'Pokaż więcej';
$a->strings['Show Less'] = 'Pokaż mniej';
$a->strings['Post to group'] = 'Opublikuj w grupie';
$a->strings['Mention'] = 'Wzmianka';
$a->strings['XMPP:'] = 'XMPP:';
$a->strings['Matrix:'] = 'Matrix:';
$a->strings['Location:'] = 'Lokalizacja:';
$a->strings['Network:'] = 'Sieć:';
$a->strings['Follow'] = 'Obserwuj';
$a->strings['Unfollow'] = 'Nie obserwuj';
$a->strings['View group'] = 'Zobacz grupę';
$a->strings['Yourself'] = 'Ty';
$a->strings['Mutuals'] = 'Znajomi';
$a->strings['Post to Email'] = 'Prześlij e-mailem';
$a->strings['Public'] = 'Publiczny';
$a->strings['This content will be shown to all your followers and can be seen in the community pages and by anyone with its link.'] = 'Zawartość będzie widoczna dla wszystkich twoich obserwatorów i będzie dostępna na stronach społeczności oraz dla wszystkich osób posiadających link do niej.';
$a->strings['Limited/Private'] = 'Ograniczony/Prywatny';
$a->strings['This content will be shown only to the people in the first box, to the exception of the people mentioned in the second box. It won\'t appear anywhere public.'] = 'Zawartość zobaczą tylko osoby wskazane w polu "Widoczne dla", z wykluczeniem tych wymienionych w polu "Z wyjątkiem". Nie będzie ona widoczna publicznie.';
$a->strings['Start typing the name of a contact or a circle to show a filtered list. You can also mention the special circles "Followers" and "Mutuals".'] = 'Rozpocznij wpisywanie nazwy kontaktu lub kręgu, by wyświetlić wyfiltrowaną listę. Możesz również uwzględnić specialne kręgi jak "Obserwujący" czy "Znajomi".';
$a->strings['Show to:'] = 'Widoczne dla:';
$a->strings['Except to:'] = 'Z wyjątkiem:';
$a->strings['CC: email addresses'] = 'DW na e-mail:';
$a->strings['Example: bob@example.com, mary@example.com'] = 'Przykład: jan@kowalski.pl, anna@nowak.pl';
$a->strings['Connectors'] = 'Integracje';
$a->strings['The database configuration file "config/local.config.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.'] = 'Plik konfiguracyjny bazy danych "config/local.config.php" nie mógł zostać zapisany. Proszę użyć załączonego tekstu, aby utworzyć plik konfiguracyjny w katalogu głównym serwera.';
$a->strings['You may need to import the file "database.sql" manually using phpmyadmin or mysql.'] = 'Może być konieczne zaimportowanie pliku "database.sql" ręcznie, używając phpmyadmin lub mysql.';
$a->strings['Please see the file "doc/INSTALL.md".'] = 'Proszę sprawdzić plik "doc/INSTALL.md".';
$a->strings['Could not find a command line version of PHP in the web server PATH.'] = 'Nie można znaleźć wersji PHP dla wiersza poleceń w PATH serwera.';
$a->strings['If you don\'t have a command line version of PHP installed on your server, you will not be able to run the background processing. See <a href=\'https://github.com/friendica/friendica/blob/stable/doc/Install.md#set-up-the-worker\'>\'Setup the worker\'</a>'] = 'Jeśli nie masz zainstalowanej na serwerze wersji PHP działającej w wierszu poleceń, nie będziesz w stanie uruchomić procesów w tle. Sprawdź <a href=\'https://github.com/friendica/friendica/blob/stable/doc/Install.md#set-up-the-worker\'>„Ustawienie workera”</a>.';
$a->strings['PHP executable path'] = 'Ścieżka wykonywalna PHP';
$a->strings['Enter full path to php executable. You can leave this blank to continue the installation.'] = 'Podaj pełną ścieżkę do pliku wykonywalnego php. Możesz pozostawić to pole puste, aby kontynuować instalację.';
$a->strings['Command line PHP'] = 'Wiersz poleceń PHP';
$a->strings['PHP executable is not the php cli binary (could be cgi-fgci version)'] = 'Plik wykonywalny PHP nie jest php cli binarny (może być wersją cgi-fgci)';
$a->strings['Found PHP version: '] = 'Znaleziona wersja PHP: ';
$a->strings['PHP cli binary'] = 'PHP cli binarny';
$a->strings['The command line version of PHP on your system does not have "register_argc_argv" enabled.'] = 'Wersja PHP dla linii poleceń w twoim systemie nie ma włączonego "register_argc_argv".';
$a->strings['This is required for message delivery to work.'] = 'Jest to wymagane do prawidłowego dostarczania wiadomości.';
$a->strings['PHP register_argc_argv'] = 'PHP register_argc_argv';
$a->strings['Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys'] = 'Błąd: funkcja "openssl_pkey_new" w tym systemie nie jest w stanie wygenerować kluczy szyfrujących';
$a->strings['If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".'] = 'Jeśli korzystasz z Windowsa, sprawdź proszę "http://www.php.net/manual/en/openssl.installation.php".';
$a->strings['Generate encryption keys'] = 'Generuj klucze szyfrowania';
$a->strings['Error: Apache webserver mod-rewrite module is required but not installed.'] = 'Błąd: moduł Apache webserver mod-rewrite jest wymagany, ale nie jest zainstalowany.';
$a->strings['Apache mod_rewrite module'] = 'Moduł Apache mod_rewrite';
$a->strings['Error: PDO or MySQLi PHP module required but not installed.'] = 'Błąd: moduł PDO lub MySQLi PHP jest wymagany, ale nie zainstalowany.';
$a->strings['Error: The MySQL driver for PDO is not installed.'] = 'Błąd: Sterownik MySQL dla PDO nie jest zainstalowany.';
$a->strings['PDO or MySQLi PHP module'] = 'Moduł PDO lub MySQLi PHP';
$a->strings['Error: The IntlChar module is not installed.'] = 'Błąd: moduł IntlChar nie jest zainstalowany.';
$a->strings['IntlChar PHP module'] = 'Moduł IntlChar PHP';
$a->strings['Error, XML PHP module required but not installed.'] = 'Błąd, moduł XML PHP jest wymagany, ale nie zainstalowany.';
$a->strings['XML PHP module'] = 'Moduł XML PHP';
$a->strings['libCurl PHP module'] = 'Moduł PHP libCurl';
$a->strings['Error: libCURL PHP module required but not installed.'] = 'Błąd: moduł libCURL PHP jest wymagany, ale nie jest zainstalowany.';
$a->strings['GD graphics PHP module'] = 'Moduł PHP-GD';
$a->strings['Error: GD graphics PHP module with JPEG support required but not installed.'] = 'Błąd: moduł GP graphics dla PHP ze wsparciem JPEG jest wymagany, ale nie jest zainstalowany.';
$a->strings['OpenSSL PHP module'] = 'Moduł PHP OpenSSL';
$a->strings['Error: openssl PHP module required but not installed.'] = 'Błąd: moduł openssl PHP jest wymagany, ale nie jest zainstalowany.';
$a->strings['mb_string PHP module'] = 'Moduł PHP mb_string';
$a->strings['Error: mb_string PHP module required but not installed.'] = 'Błąd: moduł PHP mb_string jest wymagany, ale nie jest zainstalowany.';
$a->strings['iconv PHP module'] = 'Moduł PHP iconv';
$a->strings['Error: iconv PHP module required but not installed.'] = 'Błąd: moduł PHP iconv jest wymagany, ale nie jest zainstalowany.';
$a->strings['POSIX PHP module'] = 'Moduł POSIX PHP';
$a->strings['Error: POSIX PHP module required but not installed.'] = 'Błąd: moduł POSIX PHP jest wymagany, ale nie jest zainstalowany.';
$a->strings['Program execution functions'] = 'Funkcje wykonywania programu';
$a->strings['Error: Program execution functions (proc_open) required but not enabled.'] = 'Błąd: Funkcje wykonywania programu (proc_open) są wymagane, ale nie są włączone.';
$a->strings['JSON PHP module'] = 'Moduł PHP JSON';
$a->strings['Error: JSON PHP module required but not installed.'] = 'Błąd: moduł JSON PHP jest wymagany, ale nie jest zainstalowany.';
$a->strings['File Information PHP module'] = 'Informacje o pliku Moduł PHP';
$a->strings['Error: File Information PHP module required but not installed.'] = 'Błąd: moduł File Information dla PHP jest wymagany, ale nie jest zainstalowany.';
$a->strings['GNU Multiple Precision PHP module'] = 'Moduł GNU Multiple Precision PHP';
$a->strings['Error: GNU Multiple Precision PHP module required but not installed.'] = 'Błąd: moduł GNU Multiple Precision PHP jest wymagany, ale nie jest zainstalowany.';
$a->strings['IDN Functions PHP module'] = 'Moduł IDN Functions PHP';
$a->strings['Error: IDN Functions PHP module required but not installed.'] = 'Błąd: moduł IDN Functions PHP jest wymagany, ale nie jest zainstalowany.';
$a->strings['The web installer needs to be able to create a file called "local.config.php" in the "config" folder of your web server and it is unable to do so.'] = 'Instalator internetowy musi mieć możliwość utworzenia pliku o nazwie "local.config.php" w katalogu "config" serwera WWW i nie może tego zrobić.';
$a->strings['This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.'] = 'Jest to najczęściej ustawienie uprawnień, ponieważ serwer sieciowy może nie być w stanie zapisywać plików w katalogu - nawet jeśli Ty możesz.';
$a->strings['At the end of this procedure, we will give you a text to save in a file named local.config.php in your Friendica "config" folder.'] = 'Pod koniec tej procedury otrzymasz tekst do zapisania w pliku o nazwie local.config.php w katalogu "config" Friendica.';
$a->strings['You can alternatively skip this procedure and perform a manual installation. Please see the file "doc/INSTALL.md" for instructions.'] = 'Alternatywnie można pominąć tę procedurę i przeprowadzić instalację ręczną. Proszę zobaczyć plik "doc/INSTALL.md" z instrukcjami.';
$a->strings['config/local.config.php is writable'] = 'config/local.config.php jest zapisywalny';
$a->strings['Friendica uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.'] = 'Friendica używa silnika szablonów Smarty3 do renderowania swoich widoków. Smarty3 kompiluje szablony do PHP, aby przyspieszyć renderowanie.';
$a->strings['In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top level folder.'] = 'Aby przechowywać te skompilowane szablony, serwer WWW musi mieć dostęp do zapisu do katalogu view/smarty3/ w katalogu najwyższego poziomu Friendica.';
$a->strings['Please ensure that the user that your web server runs as (e.g. www-data) has write access to this folder.'] = 'Upewnij się, że użytkownik, na którym działa serwer WWW (np. www-data), ma prawo do zapisu do tego katalogu.';
$a->strings['Note: as a security measure, you should give the web server write access to view/smarty3/ only--not the template files (.tpl) that it contains.'] = 'Uwaga: jako środek bezpieczeństwa, powinieneś dać serwerowi dostęp do zapisu view/smarty3/ jedynie - nie do plików szablonów (.tpl), które zawiera.';
$a->strings['view/smarty3 is writable'] = 'view/smarty3 jest zapisywalny';
$a->strings['Url rewrite in .htaccess seems not working. Make sure you copied .htaccess-dist to .htaccess.'] = 'Adres URL zapisany w .htaccess wydaje się nie działać. Upewnij się, że skopiowano .htaccess-dist do .htaccess.';
$a->strings['In some circumstances (like running inside containers), you can skip this error.'] = 'W niektórych okolicznościach (np. uruchamianie wewnątrz kontenerów) możesz pominąć ten błąd.';
$a->strings['Error message from Curl when fetching'] = 'Komunikat o błędzie z Curl podczas pobierania';
$a->strings['Url rewrite is working'] = 'Działający adres URL';
$a->strings['The detection of TLS to secure the communication between the browser and the new Friendica server failed.'] = 'Wykrycie TLS w celu zabezpieczenia komunikacji między przeglądarką a nowym serwerem Friendica nie powiodło się.';
$a->strings['It is highly encouraged to use Friendica only over a secure connection as sensitive information like passwords will be transmitted.'] = 'Zachęcamy do korzystania z Friendica tylko przez bezpieczne połączenie, ponieważ przesyłane będą poufne informacje, takie jak hasła.';
$a->strings['Please ensure that the connection to the server is secure.'] = 'Upewnij się, że połączenie z serwerem jest bezpieczne.';
$a->strings['No TLS detected'] = 'Nie wykryto TLS';
$a->strings['TLS detected'] = 'Wykryto TLS';
$a->strings['ImageMagick PHP extension is not installed'] = 'Rozszerzenie PHP ImageMagick nie jest zainstalowane';
$a->strings['ImageMagick PHP extension is installed'] = 'Rozszerzenie PHP ImageMagick jest zainstalowane';
$a->strings['Database already in use.'] = 'Baza danych jest już w użyciu.';
$a->strings['Could not connect to database.'] = 'Nie można połączyć się z bazą danych.';
$a->strings['%s (%s)'] = '%s (%s)';
$a->strings['Monday'] = 'Poniedziałek';
$a->strings['Tuesday'] = 'Wtorek';
$a->strings['Wednesday'] = 'Środa';
$a->strings['Thursday'] = 'Czwartek';
$a->strings['Friday'] = 'Piątek';
$a->strings['Saturday'] = 'Sobota';
$a->strings['Sunday'] = 'Niedziela';
$a->strings['January'] = 'Styczeń';
$a->strings['February'] = 'Luty';
$a->strings['March'] = 'Marzec';
$a->strings['April'] = 'Kwiecień';
$a->strings['May'] = 'Maj';
$a->strings['June'] = 'Czerwiec';
$a->strings['July'] = 'Lipiec';
$a->strings['August'] = 'Sierpień';
$a->strings['September'] = 'Wrzesień';
$a->strings['October'] = 'Październik';
$a->strings['November'] = 'Listopad';
$a->strings['December'] = 'Grudzień';
$a->strings['Mon'] = 'Pon';
$a->strings['Tue'] = 'Wt';
$a->strings['Wed'] = 'Śr';
$a->strings['Thu'] = 'Czw';
$a->strings['Fri'] = 'Pt';
$a->strings['Sat'] = 'Sob';
$a->strings['Sun'] = 'Niedz';
$a->strings['Jan'] = 'Sty';
$a->strings['Feb'] = 'Lut';
$a->strings['Mar'] = 'Mar';
$a->strings['Apr'] = 'Kwi';
$a->strings['Jun'] = 'Cze';
$a->strings['Jul'] = 'Lip';
$a->strings['Aug'] = 'Sie';
$a->strings['Sep'] = 'Wrz';
$a->strings['Oct'] = 'Paź';
$a->strings['Nov'] = 'Lis';
$a->strings['Dec'] = 'Gru';
$a->strings['The logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'Plik dziennika „%s” nie nadaje się do użytku. Brak możliwości logowania (błąd: \'%s\')';
$a->strings['The debug logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'Plik dziennika debugowania „%s” nie nadaje się do użytku. Brak możliwości logowania (błąd: \'%s\')';
$a->strings['Friendica can\'t display this page at the moment, please contact the administrator.'] = 'Friendica nie może obecnie wyświetlić tej strony, skontaktuj się z administratorem.';
$a->strings['template engine cannot be registered without a name.'] = 'silnik szablonów nie może zostać zarejestrowany bez nazwy.';
$a->strings['template engine is not registered!'] = 'silnik szablonów nie jest zarejestrowany!';
$a->strings['Storage base path'] = 'Ścieżka do katalogu głównego pamięci masowej';
$a->strings['Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree'] = 'Katalog, w którym zapisywane są przesłane pliki. Dla maksymalnego bezpieczeństwa, powinna to być ścieżka poza drzewem katalogów serwera WWW.';
$a->strings['Enter a valid existing folder'] = 'Wprowadź poprawny istniejący katalog';
$a->strings['Updates from version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.'] = 'Aktualizacje z wersji %s nie są obsługiwane. Zaktualizuj co najmniej do wersji 2021.01 i poczekaj aż zakończy się postupdate wersji 1383.';
$a->strings['Updates from postupdate version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.'] = 'Aktualizacje z wersji postupdate %s nie są obsługiwane. Zaktualizuj przynajmniej do wersji 2021.01 i poczekaj aż zakończy się postupdate wersji 1383.';
$a->strings['%s: executing pre update %d'] = '%s: wykonywanie wstępnej aktualizacji %d';
$a->strings['%s: executing post update %d'] = '%s: wykonywanie czynności poaktualizacyjnych %d';
$a->strings['Update %s failed. See error logs.'] = 'Aktualizacja %s nie powiodła się. Zobacz dziennik błędów.';
$a->strings['
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can\'t do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.'] = '
				Deweloperzy friendica wydali niedawno aktualizację %s,
				ale podczas próby instalacji, coś poszło nie tak.
				Zostanie to naprawione wkrótce i nie mogę tego zrobić sam. Proszę skontaktować się z 
				programistami friendica, jeśli nie możesz mi pomóc na własną rękę. Moja baza danych może być nieprawidłowa.';
$a->strings['The error message is\n[pre]%s[/pre]'] = 'Komunikat o błędzie:\n[pre]%s[/pre]';
$a->strings['[Friendica Notify] Database update'] = '[Powiadomienie Friendica] Aktualizacja bazy danych';
$a->strings['
				The friendica database was successfully updated from %s to %s.'] = '
				Baza danych Friendica została pomyślnie zaktualizowana z %s do %s.';
$a->strings['The database version had been set to %s.'] = 'Wersja bazy danych została ustawiona na %s.';
$a->strings['The post update is at version %d, it has to be at %d to safely drop the tables.'] = 'Postupdate jest w wersji %d, musi być w %d, aby bezpiecznie usunąć tabele.';
$a->strings['No unused tables found.'] = 'Nie odnaleziono nieużywanych tabel';
$a->strings['These tables are not used for friendica and will be deleted when you execute "dbstructure drop -e":'] = 'Te tabele nie są używane w Friendica i zostaną usunięte po wykonaniu "dbstructure drop -e":';
$a->strings['There are no tables on MyISAM or InnoDB with the Antelope file format.'] = 'Brak tabel w MyISAM lub InnoDB z formatem pliku Antelope.';
$a->strings['
Error %d occurred during database update:
%s
'] = '
Wystąpił błąd %d podczas aktualizacji bazy danych:
%s
';
$a->strings['Errors encountered performing database changes: '] = 'Błędy napotkane podczas dokonywania zmian w bazie danych: ';
$a->strings['Another database update is currently running.'] = 'Trwa inna aktualizacja bazy danych.';
$a->strings['%s: Database update'] = '%s: Aktualizacja bazy danych';
$a->strings['%s: updating %s table.'] = '%s: aktualizowanie %s tabeli.';
$a->strings['Record not found'] = 'Rekord nie został odnaleziony';
$a->strings['Unprocessable Entity'] = 'Podmiot nieprzetwarzalny';
$a->strings['Unauthorized'] = 'Nieautoryzowane';
$a->strings['Token is not authorized with a valid user or is missing a required scope'] = 'Token nie jest autoryzowany z prawidłowym użytkownikiem lub nie ma wymaganego zakresu';
$a->strings['Internal Server Error'] = 'Wewnętrzny błąd serwera';
$a->strings['Legacy module file not found: %s'] = 'Nie znaleziono pliku modułu (legacy): %s';
$a->strings['A deleted circle with this name was revived. Existing item permissions <strong>may</strong> apply to this circle and any future members. If this is not what you intended, please create another circle with a different name.'] = 'Przywrócono wcześniej usunięty Krąg o tej nazwie. Istniejące wcześniej uprawnienia do obiektów <strong>mogą</strong> obowiązywać w tym kręgu i dla jego przyszłych członków. Jeśli nie o to Ci chodziło, utwórz Krąg pod inną nazwą.';
$a->strings['Everybody'] = 'Wszyscy';
$a->strings['edit'] = 'edytuj';
$a->strings['add'] = 'dodaj';
$a->strings['Edit circle'] = 'Edytuj krąg';
$a->strings['Contacts not in any circle'] = 'Kontakty spoza kręgów';
$a->strings['Create a new circle'] = 'Utwórz nowy krąg';
$a->strings['Circle Name: '] = 'Nazwa kręgu:';
$a->strings['Edit circles'] = 'Edytuj kręgi';
$a->strings['Approve'] = 'Zatwierdź';
$a->strings['%s has blocked you'] = '%s zablokował Cię';
$a->strings['Organisation'] = 'Organizacja';
$a->strings['Group'] = 'Grupa';
$a->strings['Relay'] = 'Przekaźnik';
$a->strings['Disallowed profile URL.'] = 'Nie dozwolony adres URL profilu.';
$a->strings['Blocked domain'] = 'Zablokowana domena';
$a->strings['Connect URL missing.'] = 'Brak adresu URL połączenia.';
$a->strings['The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.'] = 'Nie można dodać kontaktu. Sprawdź odpowiednie poświadczenia sieciowe na stronie Ustawienia -> Sieci społecznościowe.';
$a->strings['Expected network %s does not match actual network %s'] = 'Oczekiwana sieć %s nie odpowiada aktualnej sieci %s';
$a->strings['This seems to be a relay account. They can\'t be followed by users.'] = 'To konto wygląda na przekaźnik. Użytkownicy nie mogą go obserwować.';
$a->strings['The profile address specified does not provide adequate information.'] = 'Dany adres profilu nie dostarcza odpowiednich informacji.';
$a->strings['No compatible communication protocols or feeds were discovered.'] = 'Nie znaleziono żadnych kompatybilnych protokołów komunikacyjnych ani źródeł.';
$a->strings['An author or name was not found.'] = 'Autor lub nazwa nie zostało znalezione.';
$a->strings['No browser URL could be matched to this address.'] = 'Przeglądarka WWW nie może odnaleźć podanego adresu';
$a->strings['Unable to match @-style Identity Address with a known protocol or email contact.'] = 'Nie można dopasować @-stylu Adres identyfikacyjny ze znanym protokołem lub kontaktem e-mail.';
$a->strings['Use mailto: in front of address to force email check.'] = 'Użyj mailto: przed adresem, aby wymusić sprawdzanie poczty e-mail.';
$a->strings['Limited profile. This person will be unable to receive direct/personal notifications from you.'] = 'Profil ograniczony. Ta osoba będzie niezdolna do odbierania osobistych powiadomień od ciebie.';
$a->strings['Unable to retrieve contact information.'] = 'Nie można otrzymać informacji kontaktowych';
$a->strings['l F d, Y \@ g:i A \G\M\TP (e)'] = ' l d F Y \@ G:i \G\M\TP (e)';
$a->strings['Starts:'] = 'Rozpoczęcie:';
$a->strings['Finishes:'] = 'Zakończenie:';
$a->strings['all-day'] = 'cały dzień';
$a->strings['Sept'] = 'Wrz';
$a->strings['today'] = 'dziś';
$a->strings['month'] = 'miesiąc';
$a->strings['week'] = 'tydzień';
$a->strings['day'] = 'dzień';
$a->strings['No events to display'] = 'Brak wydarzeń do wyświetlenia';
$a->strings['Access to this profile has been restricted.'] = 'Dostęp do tego profilu został ograniczony.';
$a->strings['Event not found.'] = 'Nie znaleziono wydarzenia.';
$a->strings['l, F j'] = 'l, F j';
$a->strings['Edit event'] = 'Edytuj wydarzenie';
$a->strings['Duplicate event'] = 'Zduplikowane zdarzenie';
$a->strings['Delete event'] = 'Usuń wydarzenie';
$a->strings['l F d, Y \@ g:i A'] = 'l F d, Y \@ g:i A';
$a->strings['D g:i A'] = 'D g:i A';
$a->strings['g:i A'] = 'g:i A';
$a->strings['Show map'] = 'Pokaż mapę';
$a->strings['Hide map'] = 'Ukryj mapę';
$a->strings['%s\'s birthday'] = 'Urodziny %s';
$a->strings['Happy Birthday %s'] = 'Wszystkiego najlepszego %s';
$a->strings['activity'] = 'aktywność';
$a->strings['comment'] = 'komentarz';
$a->strings['post'] = 'wpis';
$a->strings['%s is blocked'] = '%s jest zablokowany';
$a->strings['%s is ignored'] = '%s jest ignorowany';
$a->strings['Content from %s is collapsed'] = 'Treści od %s są zwinięte';
$a->strings['Sensitive content'] = 'Wrażliwa zawartość';
$a->strings['bytes'] = 'bajty';
$a->strings['%2$s (%3$d%%, %1$d vote)'] = [
	0 => '%2$s (%3$d%%, %1$d głos)',
	1 => '%2$s (%3$d%%, %1$d głosów)',
	2 => '%2$s (%3$d%%, %1$d głosów)',
	3 => '%2$s (%3$d%%, %1$d głosów)',
];
$a->strings['%2$s (%1$d vote)'] = [
	0 => '%2$s (%1$d głos)',
	1 => '%2$s (%1$d głosów)',
	2 => '%2$s (%1$d głosów)',
	3 => '%2$s (%1$d głosów)',
];
$a->strings['%d voter. Poll end: %s'] = [
	0 => '%d głosujący. Koniec głosowania: %s',
	1 => '%d głosujących. Koniec głosowania: %s',
	2 => '%d głosujących. Koniec głosowania: %s',
	3 => '%d głosujących. Koniec głosowania: %s',
];
$a->strings['%d voter.'] = [
	0 => '%d głosujący.',
	1 => '%d głosujących.',
	2 => '%d głosujących.',
	3 => '%d głosujących.',
];
$a->strings['Poll end: %s'] = 'Koniec ankiety: %s';
$a->strings['View on separate page'] = 'Zobacz na oddzielnej stronie';
$a->strings['[no subject]'] = '[bez tematu]';
$a->strings['Wall Photos'] = 'Tablica zdjęć';
$a->strings['Homepage:'] = 'Strona główna:';
$a->strings['About:'] = 'O mnie:';
$a->strings['Atom feed'] = 'Kanał Atom';
$a->strings['This website has been verified to belong to the same person.'] = 'Zweryfikowano, że ta witryna należy do tej samej osoby.';
$a->strings['F d'] = 'F d';
$a->strings['[today]'] = '[dziś]';
$a->strings['Birthday Reminders'] = 'Przypomnienia o urodzinach';
$a->strings['Birthdays this week:'] = 'Urodziny w tym tygodniu:';
$a->strings['g A l F d'] = 'g A I F d';
$a->strings['[No description]'] = '[Brak opisu]';
$a->strings['Event Reminders'] = 'Przypominacze wydarzeń';
$a->strings['Upcoming events the next 7 days:'] = 'Nadchodzące wydarzenia w ciągu następnych 7 dni:';
$a->strings['Hometown:'] = 'Miasto rodzinne:';
$a->strings['Marital Status:'] = 'Stan cywilny:';
$a->strings['With:'] = 'Z:';
$a->strings['Since:'] = 'Od:';
$a->strings['Sexual Preference:'] = 'Preferencje seksualne:';
$a->strings['Political Views:'] = 'Poglądy polityczne:';
$a->strings['Religious Views:'] = 'Poglądy religijne:';
$a->strings['Likes:'] = 'Polubienia:';
$a->strings['Dislikes:'] = 'Nielubiane:';
$a->strings['Title/Description:'] = 'Tytuł/Opis:';
$a->strings['Summary'] = 'Podsumowanie';
$a->strings['Musical interests'] = 'Muzyka';
$a->strings['Books, literature'] = 'Literatura';
$a->strings['Television'] = 'Telewizja';
$a->strings['Film/dance/culture/entertainment'] = 'Film/taniec/kultura/rozrywka';
$a->strings['Hobbies/Interests'] = 'Zainteresowania';
$a->strings['Love/romance'] = 'Miłość/romans';
$a->strings['Work/employment'] = 'Praca/zatrudnienie';
$a->strings['School/education'] = 'Szkoła/edukacja';
$a->strings['Contact information and Social Networks'] = 'Dane kontaktowe i Sieci społecznościowe';
$a->strings['Responsible account: %s'] = 'Konto odpowiedzialne: %s';
$a->strings['SERIOUS ERROR: Generation of security keys failed.'] = 'POWAŻNY BŁĄD: niepowodzenie podczas tworzenia kluczy zabezpieczeń.';
$a->strings['Login failed'] = 'Logowanie nieudane';
$a->strings['Not enough information to authenticate'] = 'Za mało informacji do uwierzytelnienia';
$a->strings['Password can\'t be empty'] = 'Hasło nie może być puste';
$a->strings['Empty passwords are not allowed.'] = 'Puste hasła są niedozwolone.';
$a->strings['The new password has been exposed in a public data dump, please choose another.'] = 'Nowe hasło zostało ujawnione w publicznym zrzucie danych, wybierz inne.';
$a->strings['The password length is limited to 72 characters.'] = 'Długość hasła jest ograniczona do 72 znaków.';
$a->strings['The password can\'t contain white spaces nor accentuated letters'] = 'Hasło nie może zawierać spacji ani liter z akcentami';
$a->strings['Passwords do not match. Password unchanged.'] = 'Hasła nie pasują do siebie. Hasło niezmienione.';
$a->strings['An invitation is required.'] = 'Wymagane zaproszenie.';
$a->strings['Invitation could not be verified.'] = 'Zaproszenie niezweryfikowane.';
$a->strings['Invalid OpenID url'] = 'Nieprawidłowy adres url OpenID';
$a->strings['We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.'] = 'Napotkaliśmy problem podczas logowania z podanym przez nas identyfikatorem OpenID. Sprawdź poprawną pisownię identyfikatora.';
$a->strings['The error message was:'] = 'Komunikat o błędzie:';
$a->strings['Please enter the required information.'] = 'Wprowadź wymagane informacje.';
$a->strings['system.username_min_length (%s) and system.username_max_length (%s) are excluding each other, swapping values.'] = 'system.username_min_length (%s) i system.username_max_length (%s) wykluczają się nawzajem, zamieniając wartości.';
$a->strings['Username should be at least %s character.'] = [
	0 => 'Nazwa użytkownika powinna wynosić co najmniej %s znaków.',
	1 => 'Nazwa użytkownika powinna wynosić co najmniej %s znaków.',
	2 => 'Nazwa użytkownika powinna wynosić co najmniej %s znaków.',
	3 => 'Nazwa użytkownika powinna wynosić co najmniej %s znaków.',
];
$a->strings['Username should be at most %s character.'] = [
	0 => 'Nazwa użytkownika nie może mieć więcej niż %s znaków.',
	1 => 'Nazwa użytkownika nie może mieć więcej niż %s znaków.',
	2 => 'Nazwa użytkownika nie może mieć więcej niż %s znaków.',
	3 => 'Nazwa użytkownika nie może mieć więcej niż %s znaków.',
];
$a->strings['That doesn\'t appear to be your full (First Last) name.'] = 'Wydaje mi się, że to nie jest twoje pełne imię (pierwsze imię) i nazwisko.';
$a->strings['Your email domain is not among those allowed on this site.'] = 'Twoja domena internetowa nie jest obsługiwana na tej stronie.';
$a->strings['Not a valid email address.'] = 'Niepoprawny adres e-mail.';
$a->strings['The nickname was blocked from registration by the nodes admin.'] = 'Ten alias został zablokowany w rejestracji przez administratora instancji.';
$a->strings['Cannot use that email.'] = 'Nie można użyć tego e-maila.';
$a->strings['Your nickname can only contain a-z, 0-9 and _.'] = 'Twój alias może zawierać tylko a-z, 0-9 i _.';
$a->strings['Nickname is already registered. Please choose another.'] = 'Ten login jest zajęty. Wybierz inny.';
$a->strings['An error occurred during registration. Please try again.'] = 'Wystąpił bład podczas rejestracji, Spróbuj ponownie.';
$a->strings['An error occurred creating your default profile. Please try again.'] = 'Wystąpił błąd podczas tworzenia profilu. Spróbuj ponownie.';
$a->strings['An error occurred creating your self contact. Please try again.'] = 'Wystąpił błąd podczas tworzenia własnego kontaktu. Proszę spróbuj ponownie.';
$a->strings['An error occurred creating your default contact circle. Please try again.'] = 'Wystąpił błąd podczas tworzenia domyślnego kręgu kontaktów. Spróbuj ponownie.';
$a->strings['Profile Photos'] = 'Zdjęcia profilowe';
$a->strings['
		Dear %1$s,
			the administrator of %2$s has set up an account for you.'] = '
		Szanowna/y %1$s,
			administrator of %2$s założył dla Ciebie konto.';
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
		Dane logowania: 

		Adres witryny:	%1$s
		Login:		%2$s
		Hasło:		%3$s

		Możesz zmienić hasło w ustawieniach swojego konta po zalogowaniu.

		Poświęć proszę chwilę na przegląd innych ustawień konta na tej stronie.

		Możesz także dodać podstawowe informacje do Twojego domyślnego profilu
		(na stronie "Profile") aby ułatwić innym odnalezienie Cię.

		Rekomendujemy dodanie zdjęcia profilowego, dodanie słów kluczowych profilu
		(bardzo przydatne w nawiązywaniu nowych kontaktów) - i ewentualnie kraju zamieszkania;
		jeśli nie chcesz podawać bardziej szczegółowych informacji.

		W pełni szanujemy Twoje prawo do prywatności, dlatego podanie którejkolwiek 
		z tych informacji nie jest obowiązkowe. Jeśli jesteś tu nowy i nie znasz nikogo, 
		mogą one pomóc Tobie nawiązać nowe, interesujące znajomości.

		Jeśli kiedykolwiek będziesz chciał usunąć swoje konto, możesz to zrobić tutaj: 
		%1$s/settings/removeme

		Dziękujemy i witamy w %4$s!';
$a->strings['Registration details for %s'] = 'Szczegóły rejestracji w %s';
$a->strings['
			Dear %1$s,
				Thank you for registering at %2$s. Your account is pending for approval by the administrator.

			Your login details are as follows:

			Site Location:	%3$s
			Login Name:		%4$s
			Password:		%5$s
		'] = '
			Szanowny Użytkowniku %1$s,
				Dziękujemy za rejestrację na stronie %2$s. Twoje konto czeka na zatwierdzenie przez administratora.

			Twoje dane do logowania są następujące:

			Lokalizacja witryny:	%3$s
			Nazwa użytkownika:		%4$s
			Hasło:		%5$s
		';
$a->strings['Registration at %s'] = 'Rejestracja w %s';
$a->strings['
				Dear %1$s,
				Thank you for registering at %2$s. Your account has been created.
			'] = '
				Szanowna/y %1$s,
				Dziękujemy za rejestrację w %2$s. Twoje konto zostało utworzone.
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
		Dane logowania: 

		Adres witryny:	%3$s
		Login:		%1$s
		Hasło:		%5$s

		Możesz zmienić hasło w ustawieniach swojego konta po zalogowaniu.

		Poświęć proszę chwilę na przegląd innych ustawień konta na tej stronie.

		Możesz także dodać podstawowe informacje do Twojego domyślnego profilu
		(na stronie "Profile") aby ułatwić innym odnalezienie Cię.

		Rekomendujemy dodanie zdjęcia profilowego, dodanie słów kluczowych profilu
		(bardzo przydatne w nawiązywaniu nowych kontaktów) - i ewentualnie kraju zamieszkania;
		jeśli nie chcesz podawać bardziej szczegółowych informacji.

		W pełni szanujemy Twoje prawo do prywatności, dlatego podanie którejkolwiek 
		z tych informacji nie jest obowiązkowe. Jeśli jesteś tu nowy i nie znasz nikogo, 
		mogą one pomóc Tobie nawiązać nowe, interesujące znajomości.

		Jeśli kiedykolwiek będziesz chciał usunąć swoje konto, możesz to zrobić tutaj: 
		%3$s/settings/removeme

		Dziękujemy i witamy w %2$s!';
$a->strings['User with delegates can\'t be removed, please remove delegate users first'] = 'Nie można usunąć profilu, który ma przypisanych pełnomocników. Proszę najpierw wycofać pełnomocnictwa';
$a->strings['Addon not found.'] = 'Nie znaleziono dodatku.';
$a->strings['Addon %s disabled.'] = 'Dodatek %s wyłączony.';
$a->strings['Addon %s enabled.'] = 'Dodatek %s włączony.';
$a->strings['Disable'] = 'Wyłącz';
$a->strings['Enable'] = 'Zezwól';
$a->strings['Invalid Addon found.'] = 'Znaleziono nieprawidłowy Dodatek.';
$a->strings['Administration'] = 'Administracja';
$a->strings['Addons'] = 'Dodatki';
$a->strings['Toggle'] = 'Włącz';
$a->strings['Author: '] = 'Autor: ';
$a->strings['Maintainer: '] = 'Opiekun: ';
$a->strings['Addons reloaded'] = 'Dodatki zostały ponownie wczytane';
$a->strings['Addon %s failed to install.'] = 'Instalacja dodatku %s nie powiodła się.';
$a->strings['Save Settings'] = 'Zapisz ustawienia';
$a->strings['Reload active addons'] = 'Wczytaj ponownie aktywne dodatki';
$a->strings['There are currently no addons available on your node. You can find the official addon repository at %1$s.'] = 'Na Twojej instancji nie ma obecnie żadnych dodatków. Możesz skorzystać z oficjalnego repozytorium dodatków na %1$s , by dodać nowe.';
$a->strings['Update has been marked successful'] = 'Aktualizacja została oznaczona jako udana';
$a->strings['Database structure update %s was successfully applied.'] = 'Pomyślnie zastosowano aktualizację %s struktury bazy danych.';
$a->strings['Executing of database structure update %s failed with error: %s'] = 'Wykonanie aktualizacji %s struktury bazy danych nie powiodło się z powodu błędu:%s';
$a->strings['Executing %s failed with error: %s'] = 'Wykonanie %s nie powiodło się z powodu błędu:%s';
$a->strings['Update %s was successfully applied.'] = 'Aktualizacja %s została pomyślnie zastosowana.';
$a->strings['Update %s did not return a status. Unknown if it succeeded.'] = 'Aktualizacja %s nie zwróciła statusu. Nieznane, jeśli się udało.';
$a->strings['There was no additional update function %s that needed to be called.'] = 'Nie było dodatkowej funkcji %s aktualizacji, która musiała zostać wywołana.';
$a->strings['No failed updates.'] = 'Brak błędów aktualizacji.';
$a->strings['Check database structure'] = 'Sprawdź strukturę bazy danych';
$a->strings['Failed Updates'] = 'Błąd aktualizacji';
$a->strings['This does not include updates prior to 1139, which did not return a status.'] = 'Nie dotyczy to aktualizacji przed 1139, który nie zwrócił statusu.';
$a->strings['Mark success (if update was manually applied)'] = 'Oznacz sukces (jeśli aktualizacja została ręcznie zastosowana)';
$a->strings['Attempt to execute this update step automatically'] = 'Spróbuj automatycznie wykonać ten krok aktualizacji';
$a->strings['No'] = 'Nie';
$a->strings['Yes'] = 'Tak';
$a->strings['Locked'] = 'Zablokowane';
$a->strings['Manage Additional Features'] = 'Zarządzanie dodatkowymi funkcjami';
$a->strings['Other'] = 'Inne';
$a->strings['unknown'] = 'nieznany';
$a->strings['%2$s total system'] = [
	0 => '%2$s system łącznie',
	1 => '%2$s systemów łącznie.',
	2 => '%2$s systemów łącznie.',
	3 => '%2$s systemów łącznie.',
];
$a->strings['%2$s active user last month'] = [
	0 => '%2$s aktywny użytkownik w ostatnim miesiącu',
	1 => '%2$s aktywnych użytkowników w ostatnim miesiącu',
	2 => '%2$s aktywnych użytkowników w ostatnim miesiącu',
	3 => '%2$s aktywnych użytkowników w ostatnim miesiącu',
];
$a->strings['%2$s active user last six months'] = [
	0 => '%2$s aktywny użytkownik w ostatnich 6 miesiącach',
	1 => '%2$s aktywnych użytkowników w ostatnich 6 miesiącach',
	2 => '%2$s aktywnych użytkowników w ostatnich 6 miesiącach',
	3 => '%2$s aktywnych użytkowników w ostatnich 6 miesiącach',
];
$a->strings['%2$s registered user'] = [
	0 => '%2$s zarejestrowany użytkownik',
	1 => '%2$s zarejestrowanych użytkowników',
	2 => '%2$s zarejestrowanych użytkowników',
	3 => '%2$s zarejestrowanych użytkowników',
];
$a->strings['%2$s locally created post or comment'] = [
	0 => '%2$s lokalnie utworzony wpis lub komentarz',
	1 => '%2$s lokalnie utworzonych wpisów i komentarzy',
	2 => '%2$s lokalnie utworzonych wpisów i komentarzy',
	3 => '%2$s lokalnie utworzonych wpisów i komentarzy',
];
$a->strings['%2$s post per user'] = [
	0 => '%2$s wpis na użytkownika',
	1 => '%2$s wpisów na użytkownika',
	2 => '%2$s wpisów na użytkownika',
	3 => '%2$s wpisów na użytkownika',
];
$a->strings['%2$s user per system'] = [
	0 => '%2$s użytkownik na system',
	1 => '%2$s użytkowników na system',
	2 => '%2$s użytkowników na system',
	3 => '%2$s użytkowników na system',
];
$a->strings['This page offers you some numbers to the known part of the federated social network your Friendica node is part of. These numbers are not complete but only reflect the part of the network your node is aware of.'] = 'Ta strona zawiera dane liczbowe dotyczące znanej części federacyjnej sieci społecznościowej, której częścią jest Twoja instancja Friendica. Dane te nie są kompletne, ale odzwierciedlają tylko tę część sieci, którą zna Twoja instancja.';
$a->strings['Federation Statistics'] = 'Statystyki Federacji';
$a->strings['Currently this node is aware of %2$s node (%3$s active users last month, %4$s active users last six months, %5$s registered users in total) from the following platforms:'] = [
	0 => 'Obecnie ten węzeł jest świadomy %2$s węzła (%3$s aktywnych użytkowników w zeszłym miesiącu, %4$s aktywnych użytkowników w ciągu ostatnich sześciu miesięcy, %5$s łącznie zarejestrowanych użytkowników) z następujących platform:',
	1 => 'Obecnie ten węzeł jest świadomy %2$s węzłów (%3$s aktywnych użytkowników w zeszłym miesiącu, %4$s aktywnych użytkowników w ciągu ostatnich sześciu miesięcy, %5$s łącznie zarejestrowanych użytkowników) z następujących platform:',
	2 => 'Obecnie ten węzeł jest świadomy %2$s węzłów (%3$s aktywnych użytkowników w zeszłym miesiącu, %4$s aktywnych użytkowników w ciągu ostatnich sześciu miesięcy, %5$s łącznie zarejestrowanych użytkowników) z następujących platform:',
	3 => 'Aktualnie ta instancja zna %2$s instancji (%3$s aktywnych użytkowników w zeszłym miesiącu, %4$s aktywnych użytkowników w ciągu ostatnich sześciu miesięcy, %5$s łącznie zarejestrowanych użytkowników) następujących platform:',
];
$a->strings['The logfile \'%s\' is not writable. No logging possible'] = 'Plik dziennika \'%s\' nie jest zapisywalny. Brak możliwości logowania';
$a->strings['PHP log currently enabled.'] = 'Dziennik PHP jest obecnie włączony.';
$a->strings['PHP log currently disabled.'] = 'Dziennik PHP jest obecnie wyłączony.';
$a->strings['Logs'] = 'Dzienniki';
$a->strings['Clear'] = 'Wyczyść';
$a->strings['Enable Debugging'] = 'Włącz debugowanie';
$a->strings['<strong>Read-only</strong> because it is set by an environment variable'] = '<strong>Tylko do odczytu</strong> ponieważ jest ustawione przez zmienną środowiskową';
$a->strings['Log file'] = 'Plik logów';
$a->strings['Must be writable by web server. Relative to your Friendica top-level directory.'] = 'Musi być zapisywalny przez serwer sieciowy. W stosunku do katalogu najwyższego poziomu Friendica.';
$a->strings['Log level'] = 'Poziom logów';
$a->strings['PHP logging'] = 'Logowanie w PHP';
$a->strings['To temporarily enable logging of PHP errors and warnings you can prepend the following to the index.php file of your installation. The filename set in the \'error_log\' line is relative to the friendica top-level directory and must be writeable by the web server. The option \'1\' for \'log_errors\' and \'display_errors\' is to enable these options, set to \'0\' to disable them.'] = 'Aby tymczasowo włączyć rejestrowanie błędów i ostrzeżeń PHP, możesz dołączyć do pliku index.php swojej instalacji. Nazwa pliku ustawiona w linii \'error_log\' odnosi się do katalogu najwyższego poziomu friendiki i musi być zapisywalna przez serwer WWW. Opcja \'1\' dla \'log_errors\' i \'display_errors\' polega na włączeniu tych opcji, ustawieniu na \'0\', aby je wyłączyć.';
$a->strings['Error trying to open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s exist and is readable.'] = 'Błąd podczas próby otwarcia pliku dziennika <strong>%1$s</strong>. Sprawdź, czy plik %1$s istnieje i czy można go odczytać.';
$a->strings['Couldn\'t open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s is readable.'] = 'Nie udało się otworzyć pliku dziennika <strong>%1$s</strong>. Sprawdź, czy plik %1$s jest odczytywalny.';
$a->strings['View Logs'] = 'Zobacz rejestry';
$a->strings['Search in logs'] = 'Szukaj w dziennikach';
$a->strings['Show all'] = 'Pokaż wszystkie';
$a->strings['Date'] = 'Data';
$a->strings['Level'] = 'Poziom';
$a->strings['Context'] = 'Kontekst';
$a->strings['ALL'] = 'WSZYSTKO';
$a->strings['View details'] = 'Zobacz szczegóły';
$a->strings['Click to view details'] = 'Kliknij, aby zobaczyć szczegóły';
$a->strings['Event details'] = 'Szczegóły wydarzenia';
$a->strings['Data'] = 'Dane';
$a->strings['Source'] = 'Źródło';
$a->strings['File'] = 'Plik';
$a->strings['Line'] = 'Linia';
$a->strings['Function'] = 'Funkcja';
$a->strings['UID'] = 'UID';
$a->strings['Process ID'] = 'Identyfikator procesu';
$a->strings['Inspect Deferred Worker Queue'] = 'Sprawdź kolejkę odroczonych workerów';
$a->strings['This page lists the deferred worker jobs. This are jobs that couldn\'t be executed at the first time.'] = 'Ta strona zawiera listę zadań odroczonych workerów. Są to zadania, które nie mogą być wykonywane po raz pierwszy.';
$a->strings['Inspect Worker Queue'] = 'Sprawdź kolejkę workerów';
$a->strings['This page lists the currently queued worker jobs. These jobs are handled by the worker cronjob you\'ve set up during install.'] = 'Ta strona zawiera listę aktualnie ustawionych zadań dla workerów. Te zadania są obsługiwane przez cronjob workera, który skonfigurowałeś podczas instalacji.';
$a->strings['ID'] = 'ID';
$a->strings['Command'] = 'Polecenie';
$a->strings['Job Parameters'] = 'Parametry zadania';
$a->strings['Created'] = 'Utwórz';
$a->strings['Next Try'] = 'Kolejna próba';
$a->strings['Priority'] = 'Priorytet';
$a->strings['%s is no valid input for maximum media size'] = '%s jest nieprawidłową wartością dla maksymalnego rozmiaru mediów';
$a->strings['%s is no valid input for maximum image size'] = '%s jest nieprawidłową wartością dla maksymalnego rozmiaru obrazka';
$a->strings['No special theme for mobile devices'] = 'Brak specialnego motywu dla urządzeń mobilnych';
$a->strings['%s - (Experimental)'] = '%s- (Eksperymentalne)';
$a->strings['No community page'] = 'Brak strony społeczności';
$a->strings['No community page for visitors'] = 'Brak strony społeczności dla odwiedzających';
$a->strings['Public postings from users of this site'] = 'Publikacje publiczne od użytkowników tej strony';
$a->strings['Public postings from the federated network'] = 'Publikacje wpisy ze sfederowanej sieci';
$a->strings['Public postings from local users and the federated network'] = 'Publikacje publiczne od użytkowników lokalnych i sieci federacyjnej';
$a->strings['Multi user instance'] = 'Tryb wielu użytkowników';
$a->strings['Closed'] = 'Zamknięte';
$a->strings['Requires approval'] = 'Wymaga zatwierdzenia';
$a->strings['Open'] = 'Otwarta';
$a->strings['Don\'t check'] = 'Nie sprawdzaj';
$a->strings['check the stable version'] = 'sprawdź wersję stabilną';
$a->strings['check the development version'] = 'sprawdź wersję rozwojową';
$a->strings['none'] = 'brak';
$a->strings['Local contacts'] = 'Kontakty lokalne';
$a->strings['Interactors'] = 'Interaktorzy';
$a->strings['Site'] = 'Strona';
$a->strings['General Information'] = 'Ogólne informacje';
$a->strings['Republish users to directory'] = 'Ponownie opublikuj użytkowników w katalogu';
$a->strings['Registration'] = 'Rejestracja';
$a->strings['File upload'] = 'Wgrywanie plików';
$a->strings['Policies'] = 'Zasady';
$a->strings['Advanced'] = 'Zaawansowane';
$a->strings['Auto Discovered Contact Directory'] = 'Automatyczne odkrywanie kontaktów';
$a->strings['Performance'] = 'Wydajność';
$a->strings['Worker'] = 'Worker';
$a->strings['Message Relay'] = 'Przekaźnik wiadomości';
$a->strings['Use the command "console relay" in the command line to add or remove relays.'] = 'Użyj polecenia „console relay” w wierszu poleceń, aby dodać lub usunąć przekaźniki.';
$a->strings['The system is not subscribed to any relays at the moment.'] = 'System nie jest aktualnie objęty abonamentem na żadne przekaźniki.';
$a->strings['The system is currently subscribed to the following relays:'] = 'System jest obecnie objęty abonamentem na następujące przekaźniki:';
$a->strings['Relocate Node'] = 'Przenieś instancję';
$a->strings['Relocating your node enables you to change the DNS domain of this node and keep all the existing users and posts. This process takes a while and can only be started from the relocate console command like this:'] = 'Przeniesienie instancji umożliwia zmianę domeny DNS tej instancji i zachowanie wszystkich istniejących użytkowników i wpisów. Ten proces zajmuje trochę czasu i można go uruchomić tylko za pomocą polecenia relokacji w konsoli w następujący sposób:';
$a->strings['(Friendica directory)# bin/console relocate https://newdomain.com'] = '(Katalog Friendica)# bin/console relocate https://nowadomena.pl';
$a->strings['Site name'] = 'Nazwa strony';
$a->strings['Sender Email'] = 'E-mail nadawcy';
$a->strings['The email address your server shall use to send notification emails from.'] = 'Adres e-mail używany przez Twój serwer do wysyłania e-maili z powiadomieniami.';
$a->strings['Name of the system actor'] = 'Imię i nazwisko aktora systemu';
$a->strings['Name of the internal system account that is used to perform ActivityPub requests. This must be an unused username. If set, this can\'t be changed again.'] = 'Nazwa wewnętrznego konta systemowego, które jest używane do wykonywania żądań ActivityPub. Musi to być nieużywana nazwa użytkownika. Jeśli jest ustawiona, nie można jej zmienić ponownie.';
$a->strings['Banner/Logo'] = 'Baner/Logo';
$a->strings['Email Banner/Logo'] = 'Baner/logo e-maila';
$a->strings['Shortcut icon'] = 'Ikona skrótu';
$a->strings['Link to an icon that will be used for browsers.'] = 'Link do ikony, która będzie używana w przeglądarkach.';
$a->strings['Touch icon'] = 'Dołącz ikonę';
$a->strings['Link to an icon that will be used for tablets and mobiles.'] = 'Link do ikony, która będzie używana w tabletach i telefonach komórkowych.';
$a->strings['Additional Info'] = 'Dodatkowe informacje';
$a->strings['For public servers: you can add additional information here that will be listed at %s/servers.'] = 'W przypadku serwerów publicznych: możesz tu dodać dodatkowe informacje, które będą wymienione na %s/servers.';
$a->strings['System language'] = 'Język systemu';
$a->strings['System theme'] = 'Motyw systemowy';
$a->strings['Default system theme - may be over-ridden by user profiles - <a href="%s" id="cnftheme">Change default theme settings</a>'] = 'Domyślny motyw systemu - może być nadpisywany przez profile użytkowników - <a href="%s" id="cnftheme">Zmień domyślne ustawienia motywu</a>';
$a->strings['Mobile system theme'] = 'Mobilny motyw systemu';
$a->strings['Theme for mobile devices'] = 'Motyw na urządzenia mobilne';
$a->strings['Force SSL'] = 'Wymuś SSL';
$a->strings['Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.'] = 'Wymuszaj wszystkie żądania SSL bez SSL - Uwaga: w niektórych systemach może to prowadzić do niekończących się pętli.';
$a->strings['Show help entry from navigation menu'] = 'Pokaż wpis pomocy z menu nawigacyjnego';
$a->strings['Displays the menu entry for the Help pages from the navigation menu. It is always accessible by calling /help directly.'] = 'Wyświetla pozycję menu dla stron pomocy z menu nawigacyjnego. Jest zawsze dostępna, odwołując się bezpośrednio do /help.';
$a->strings['Single user instance'] = 'Tryb pojedynczego użytkownika';
$a->strings['Make this instance multi-user or single-user for the named user'] = 'Ustawia tryb dla wielu użytkowników lub pojedynczego użytkownika dla nazwanego użytkownika';
$a->strings['Maximum image size'] = 'Maksymalny rozmiar zdjęcia';
$a->strings['Maximum size in bytes of uploaded images. Default is 0, which means no limits. You can put k, m, or g behind the desired value for KiB, MiB, GiB, respectively.
													The value of <code>upload_max_filesize</code> in your <code>PHP.ini</code> needs be set to at least the desired limit.
													Currently <code>upload_max_filesize</code> is set to %s (%s byte)'] = 'Maksymalny rozmiar przesłanych obrazów wyrażony w bajtach. Domyślna wartość to 0, co oznacza brak ograniczeń. Możesz umieścić literę k, m lub g na końcu wprowadzonej wartości, co oznacza odpowiednio KiB, MiB lub GiB.
													Wartość <code>upload_max_filesize</code> w pliku <code>PHP.ini</code> musi być ustawiona co najmniej na żądany limit.
													Obecnie <code>upload_max_filesize</code> jest ustawiony na %s (%s bajtów)';
$a->strings['Maximum image length'] = 'Maksymalna długość obrazu';
$a->strings['Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.'] = 'Maksymalna długość w pikselach dłuższego boku przesyłanego obrazu. Wartością domyślną jest -1, co oznacza brak ograniczeń.';
$a->strings['JPEG image quality'] = 'Jakość obrazu JPEG';
$a->strings['Uploaded JPEGS will be saved at this quality setting [0-100]. Default is 100, which is full quality.'] = 'Przesłane pliki JPEG zostaną zapisane w tym ustawieniu jakości [0-100]. Domyślna wartość to 100, która jest pełną jakością.';
$a->strings['Maximum media file size'] = 'Maksymalny rozmiar pliku multimedialnego';
$a->strings['Maximum size in bytes of uploaded media files. Default is 0, which means no limits. You can put k, m, or g behind the desired value for KiB, MiB, GiB, respectively.
													The value of <code>upload_max_filesize</code> in your <code>PHP.ini</code> needs be set to at least the desired limit.
													Currently <code>upload_max_filesize</code> is set to %s (%s byte)'] = 'Maksymalny rozmiar przesłanych plików multimedialnych wyrażony w bajtach. Domyślna wartość to 0, co oznacza brak ograniczeń. Możesz umieścić literę k, m lub g na końcu wprowadzonej wartości, co oznacza odpowiednio KiB, MiB lub GiB.
													Wartość <code>upload_max_filesize</code> w pliku <code>PHP.ini</code> musi być ustawiona co najmniej na żądany limit.
													Obecnie <code>upload_max_filesize</code> jest ustawiony na %s (%s bajtów)';
$a->strings['Register policy'] = 'Zasady rejestracji';
$a->strings['Maximum Users'] = 'Maksymalna liczba użytkowników';
$a->strings['If defined, the register policy is automatically closed when the given number of users is reached and reopens the registry when the number drops below the limit. It only works when the policy is set to open or close, but not when the policy is set to approval.'] = 'Jeśli została zdefiniowana, rejestracja jest automatycznie zamykana po osiągnięciu określonej liczby użytkowników i ponownie otwierana, gdy liczba ta spadnie poniżej limitu. Działa to tylko wtedy, gdy rejestracja jest ustawiona na otwartą lub zamkniętą, ale nie działa, gdy rejestracja jest ustawiona na wymagającą zatwierdzenia.';
$a->strings['Maximum Daily Registrations'] = 'Maksymalna liczba rejestracji dziennie';
$a->strings['If registration is permitted above, this sets the maximum number of new user registrations to accept per day.  If register is set to closed, this setting has no effect.'] = 'Jeśli rejestracja jest otwarta, określa to maksymalną liczbę rejestracji nowych użytkowników na dzień. Jeśli rejestracja jest ustawiona na "Zamknięta", ustawienie to nie ma żadnego wpływu.';
$a->strings['Register text'] = 'Tekst rejestracji';
$a->strings['Will be displayed prominently on the registration page. You can use BBCode here.'] = 'Będzie wyświetlany na stronie rejestracji. Możesz użyć BBCode.';
$a->strings['Forbidden Nicknames'] = 'Niedozwolone aliasy';
$a->strings['Comma separated list of nicknames that are forbidden from registration. Preset is a list of role names according RFC 2142.'] = 'Lista oddzielonych przecinkami aliasów, których nie można rejestrować. Presetem jest lista nazw zgodna z RFC 2142.';
$a->strings['Accounts abandoned after x days'] = 'Konta porzucone po x dni';
$a->strings['Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.'] = 'Nie będzie marnować zasobów systemu wypytując zewnętrzne strony o opuszczone konta. Ustaw 0 dla braku limitu czasu.';
$a->strings['Allowed friend domains'] = 'Dozwolone domeny znajomych';
$a->strings['Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains'] = 'Lista domen oddzielonych przecinkami, które mogą nawiązywać znajomości z tą witryną. Symbole wieloznaczne (wildcards) są akceptowane. Pozostaw puste by zezwolić każdej domenie na kontakt.';
$a->strings['Allowed email domains'] = 'Dozwolone domeny e-mailowe';
$a->strings['Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains'] = 'Lista dopuszczonych do rejestracji domen adresów e-mail oddzielonych przecinkami. Symbole wieloznaczne (wildcards) są akceptowane. Puste pole oznacza zezwolenie na wszystkie domeny';
$a->strings['Disallowed email domains'] = 'Niedozwolone domeny e-mailowe';
$a->strings['Comma separated list of domains which are rejected as email addresses for registrations to this site. Wildcards are accepted.'] = 'Lista niedopuszczonych do rejestracji domen adresów e-mail oodzielonych przecinkami. Symbole wieloznaczne (wildcards) są akceptowane.';
$a->strings['Block public'] = 'Blokuj publicznie';
$a->strings['Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.'] = 'Zaznacz, aby zablokować publiczny dostęp do wszystkich publicznych stron prywatnych w tej witrynie, chyba że jesteś zalogowany.';
$a->strings['Force publish'] = 'Wymuś publikację';
$a->strings['Check to force all profiles on this site to be listed in the site directory.'] = 'Zaznacz, aby wymusić umieszczenie wszystkich profili w tej witrynie w katalogu witryny.';
$a->strings['Enabling this may violate privacy laws like the GDPR'] = 'Włączenie tego może naruszyć prawa ochrony prywatności, takie jak GDPR';
$a->strings['Global directory URL'] = 'Globalny adres URL katalogu';
$a->strings['URL to the global directory. If this is not set, the global directory is completely unavailable to the application.'] = 'Adres URL do katalogu globalnego. Jeśli nie zostanie to ustawione, katalog globalny jest całkowicie niedostępny dla aplikacji.';
$a->strings['Private posts by default for new users'] = 'Prywatne wpisy domyślne dla nowych użytkowników';
$a->strings['Set default post permissions for all new members to the default privacy circle rather than public.'] = 'Ustaw domyślne ustawienia publikacji wpisów dla nowych członków na krąg prywatny, zamiast publiczny.';
$a->strings['Don\'t include post content in email notifications'] = 'Nie dodawaj treści wpisu do powiadomień e-mailowych.';
$a->strings['Don\'t include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.'] = 'Ze względów prywatności, nie dołączaj treści wpisu/komentarza/wiadomości prywatnej itp. do powiadomień e-mail.';
$a->strings['Disallow public access to addons listed in the apps menu.'] = 'Nie zezwalaj na publiczny dostęp do dodatkowych wtyczek wyszczególnionych w menu aplikacji.';
$a->strings['Checking this box will restrict addons listed in the apps menu to members only.'] = 'Zaznaczenie tego pola spowoduje ograniczenie dodatków wymienionych w menu aplikacji tylko dla członków.';
$a->strings['Don\'t embed private images in posts'] = 'Nie umieszczaj prywatnych zdjęć we wpisach';
$a->strings['Don\'t replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.'] = 'Nie zastępuj lokalnie hostowanych zdjęć prywatnych we wpisach za pomocą osadzonej kopii obrazu. Oznacza to, że osoby, które odbierają wpisy zawierające prywatne zdjęcia, będą musiały uwierzytelnić i wczytać każdy obraz, co może trochę potrwać.';
$a->strings['Explicit Content'] = 'Treści dla dorosłych';
$a->strings['Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.'] = 'Zaznacz, aby ogłosić, że Twoja instancja jest używana głównie do publikacji nagich treści, które mogą nie być odpowiednie dla nieletnich. Informacje te zostaną opublikowane w informacjach o instancji i mogą zostać wykorzystane, np. w katalogu globalnym, aby filtrować instancję na listach instancji, do których można dołączyć. Dodatkowo, informacja o tym zostanie pokazana na stronie rejestracji.';
$a->strings['Only local search'] = 'Tylko wyszukiwanie lokalne';
$a->strings['Blocks search for users who are not logged in to prevent crawlers from blocking your system.'] = 'Blokuje dostęp do wyszukiwarki niezalogowanym, by zapobiec blokowaniu Twojego systemu przez crawlery.';
$a->strings['Blocked tags for trending tags'] = 'Zablokokowane tagi w popularnych tagach';
$a->strings['Comma separated list of hashtags that shouldn\'t be displayed in the trending tags.'] = 'Lista tagów oddzielonych przecinkami, które nie powinny być wyświetlane w popularnych tagach.';
$a->strings['Cache contact avatars'] = 'Buforuj awatary kontaktów';
$a->strings['Locally store the avatar pictures of the contacts. This uses a lot of storage space but it increases the performance.'] = 'Lokalnie przechowuj zdjęcia awatarów kontaktów. To zajmuje dużo miejsca, ale zwiększa wydajność.';
$a->strings['Allow Users to set remote_self'] = 'Zezwól użytkownikom na ustawienie remote_self';
$a->strings['With checking this, every user is allowed to mark every contact as a remote_self in the repair contact dialog. Setting this flag on a contact causes mirroring every posting of that contact in the users stream.'] = 'Po zaznaczeniu tej opcji każdy użytkownik może oznaczyć dowolny kontakt jako remote_self w ustawieiach kontaktu. Włączenie tej opcji przy kontakcie powoduje powielanie wszystkich jego wpisów w kanale użytkownika.';
$a->strings['Allow Users to set up relay channels'] = 'Pozwól użytkownikom tworzyć kanały przekaźnikowe';
$a->strings['If enabled, it is possible to create relay users that are used to reshare content based on user defined channels.'] = 'Jeśli opcja jest włączona, można tworzyć użytkowników przekaźnikowych, którzy służą do przekazywania dalej treści na podstawie kanałów zdefiniowanych przez użytkownika.';
$a->strings['Adjust the feed poll frequency'] = 'Dostosuj częstotliwość pobierania kanałów';
$a->strings['Automatically detect and set the best feed poll frequency.'] = 'Automatycznie wykryj i ustaw najlepszą częstotliwość pobierania kanałów';
$a->strings['Minimum poll interval'] = 'Minimalny interwał pobierania';
$a->strings['Minimal distance in minutes between two polls for mail and feed contacts. Reasonable values are between 1 and 59.'] = 'Minimalna przerwa między sprawdzeniami poczty i kanałów, wyrażona w minutach. Zalecane wartości od 1 do 59.';
$a->strings['Enable multiple registrations'] = 'Włącz wiele rejestracji';
$a->strings['Enable users to register additional accounts for use as pages.'] = 'Zezwól użytkownikom na rejestrowanie dodatkowych kont do użytku jako strony.';
$a->strings['Enable OpenID'] = 'Włącz OpenID';
$a->strings['Enable OpenID support for registration and logins.'] = 'Włącz obsługę OpenID dla rejestracji i logowania.';
$a->strings['Enable full name check'] = 'Włącz weryfikację pełnego imienia i nazwiska';
$a->strings['Prevents users from registering with a display name with fewer than two parts separated by spaces.'] = 'Nie zezwalaj na rejestrację użytkowników, których nazwa wyświetlana składa się z mniej niż dwóch części oddzielonych spacją.';
$a->strings['Email administrators on new registration'] = 'Wyślij e-mail do administratorów przy nowej rejestracji';
$a->strings['If enabled and the system is set to an open registration, an email for each new registration is sent to the administrators.'] = 'Jeśli ta opcja jest włączona, a system jest ustawiony na otwartą rejestrację, wiadomość e-mail dla każdej nowej rejestracji jest wysyłana do administratorów.';
$a->strings['Community pages for visitors'] = 'Strony społecznościowe dla odwiedzających';
$a->strings['Which community pages should be available for visitors. Local users always see both pages.'] = 'Które strony społeczności powinny być dostępne dla odwiedzających. Lokalni użytkownicy zawsze widzą obie strony.';
$a->strings['Posts per user on community page'] = 'Lista wpisów użytkownika na stronie społeczności';
$a->strings['The maximum number of posts per user on the local community page. This is useful, when a single user floods the local community page.'] = 'Maksymalna liczba wpisów na użytkownika na stronie lokalnej społeczności. Jest to przydatne, gdy pojedynczy użytkownik zalewa treściami stronę lokalnej społeczności.';
$a->strings['Posts per server on community page'] = 'Liczba wpisów na serwer na stronie społeczności';
$a->strings['The maximum number of posts per server on the global community page. This is useful, when posts from a single server flood the global community page.'] = 'Maksymalna liczba wpisów na serwer na stronie globalnej społeczności. Jest to przydatne, gdy wpisy z jednego serwera zalewają stronę globalnej społeczności.';
$a->strings['Enable Mail support'] = 'Włącz obsługę maili';
$a->strings['Enable built-in mail support to poll IMAP folders and to reply via mail.'] = 'Włącz wbudowaną obsługę poczty, aby odpytywać katalogi IMAP i odpowiadać pocztą.';
$a->strings['Mail support can\'t be enabled because the PHP IMAP module is not installed.'] = 'Nie można włączyć obsługi poczty, ponieważ moduł PHP IMAP nie jest zainstalowany.';
$a->strings['Diaspora support can\'t be enabled because Friendica was installed into a sub directory.'] = 'Obsługa Diaspory nie może być włączona, ponieważ Friendica została zainstalowana w podkatalogu.';
$a->strings['Enable Diaspora support'] = 'Włączyć obsługę Diaspory';
$a->strings['Enable built-in Diaspora network compatibility for communicating with diaspora servers.'] = 'Włącz wbudowaną kompatybilność sieci Diaspora do komunikacji z serwerami diaspory.';
$a->strings['Verify SSL'] = 'Weryfikacja SSL';
$a->strings['If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.'] = 'Jeśli chcesz, możesz włączyć ścisłe sprawdzanie certyfikatu. Oznacza to, że nie możesz połączyć się (w ogóle) z własnoręcznie podpisanymi stronami SSL.';
$a->strings['Proxy user'] = 'Użytkownik proxy';
$a->strings['User name for the proxy server.'] = 'Nazwa użytkownika serwera proxy.';
$a->strings['Proxy URL'] = 'URL pośrednika';
$a->strings['If you want to use a proxy server that Friendica should use to connect to the network, put the URL of the proxy here.'] = 'Jeśli chcesz używać serwera proxy, którego Friendica powinna używać do łączenia się z siecią, umieść tutaj adres URL proxy.';
$a->strings['Network timeout'] = 'Limit czasu sieci';
$a->strings['Value is in seconds. Set to 0 for unlimited (not recommended).'] = 'Wartość jest w sekundach. Ustaw na 0 dla nieograniczonej (niezalecane).';
$a->strings['Maximum Load Average'] = 'Maksymalne obciążenie średnie';
$a->strings['Maximum system load before delivery and poll processes are deferred - default %d.'] = 'Maksymalne obciążenie systemu przed dostarczeniem i procesami odpytywania jest odroczone - domyślnie %d.';
$a->strings['Minimal Memory'] = 'Minimalna pamięć';
$a->strings['Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).'] = 'Minimalna wolna pamięć w MB dla workera. Potrzebuje dostępu do /proc/ meminfo - domyślnie 0 (wyłączone).';
$a->strings['Periodically optimize tables'] = 'Okresowo optymalizuj tabele';
$a->strings['Periodically optimize tables like the cache and the workerqueue'] = 'Okresowo optymalizuj tabele, takie jak pamięć podręczna i kolejka workerów';
$a->strings['Discover followers/followings from contacts'] = 'Odkryj obserwujących / obserwowanych Twoich kontaktów';
$a->strings['If enabled, contacts are checked for their followers and following contacts.'] = 'Jeśli ta opcja jest włączona, kontakty są sprawdzane pod kątem ich obserwujących i śledzonych kontaktów.';
$a->strings['None - deactivated'] = 'Brak - dezaktywowany';
$a->strings['Local contacts - contacts of our local contacts are discovered for their followers/followings.'] = 'Kontakty lokalne - kontakty naszych lokalnych kontaktów są wykrywane dla ich obserwujących/obserwujących.';
$a->strings['Interactors - contacts of our local contacts and contacts who interacted on locally visible postings are discovered for their followers/followings.'] = 'Interaktorzy - kontakty naszych lokalnych kontaktów i kontakty, które wchodziły w interakcję z lokalnie widocznymi wpisami, są wykrywane dla ich obserwujących/obserwowanych.';
$a->strings['Only update contacts/servers with local data'] = 'Aktualizuj tylko kontakty/serwery dostępne lokalnie';
$a->strings['If enabled, the system will only look for changes in contacts and servers that engaged on this system by either being in a contact list of a user or when posts or comments exists from the contact on this system.'] = 'Jeśli aktywowano, system będzie aktualizował tylko te kontakty i serwery, które już wchodzą w interakcje z użytkownikami tej instancji, np. będąc na liście kontaktów użytkownika lub mając wpisy/komentarze w tej instancji.';
$a->strings['Only update contacts with relations'] = 'Aktualizuj tylko kontakty będące w relacji';
$a->strings['If enabled, the system will only look for changes in contacts that are in a contact list of a user on this system.'] = 'Jeśli aktywowano, system będzie wyszukiwał zmian tylko w tych kontaktach, które są na liście kontaktów użytkowników z tego systemu.';
$a->strings['Synchronize the contacts with the directory server'] = 'Synchronizuj kontakty z serwerem katalogowym';
$a->strings['if enabled, the system will check periodically for new contacts on the defined directory server.'] = 'jeśli ta opcja jest włączona, system będzie okresowo sprawdzać nowe kontakty na zdefiniowanym serwerze katalogowym.';
$a->strings['Discover contacts from other servers'] = 'Odkryj kontakty z innych serwerów';
$a->strings['Periodically query other servers for contacts and servers that they know of. The system queries Friendica, Mastodon and Hubzilla servers. Keep it deactivated on small machines to decrease the database size and load.'] = 'Okresowo odpytuj inne serwery o kontakty i serwery, które są im znane, System odpytuje serwery Friendica, Mastodona i Hubzilli. Nie aktywuj tego na mniejszych urządzeniach, by nie zwiększać obciążenia i rozmiaru bazy danych.';
$a->strings['Days between requery'] = 'Dni między żądaniem';
$a->strings['Number of days after which a server is requeried for their contacts and servers it knows of. This is only used when the discovery is activated.'] = 'Liczba dni, po których serwer jest ponownie odpytywany o kontakty i serwery, które zna. Opcja działa tylko w przypadku, gdy włączone jest odkrywanie.';
$a->strings['Search the local directory'] = 'Wyszukaj w lokalnym katalogu';
$a->strings['Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.'] = 'Wyszukaj lokalny katalog zamiast katalogu globalnego. Podczas wyszukiwania lokalnie każde wyszukiwanie zostanie wykonane w katalogu globalnym w tle. Poprawia to wyniki wyszukiwania, gdy wyszukiwanie jest powtarzane.';
$a->strings['Publish server information'] = 'Publikuj informacje o serwerze';
$a->strings['If enabled, general server and usage data will be published. The data contains the name and version of the server, number of users with public profiles, number of posts and the activated protocols and connectors. See <a href="http://the-federation.info/">the-federation.info</a> for details.'] = 'Po włączeniu, ogólne dane dotyczące serwera i użytkowania zostaną opublikowane. Dane zawierają nazwę i wersję serwera, liczbę użytkowników z profilami publicznymi, liczbę wpisów oraz aktywowane protokoły i integracje. Szczegółowe informacje można znaleźć na <a href="http://the-federation.info/">the-federation.info</a>.';
$a->strings['Check upstream version'] = 'Sprawdź wersję powyżej';
$a->strings['Enables checking for new Friendica versions at github. If there is a new version, you will be informed in the admin panel overview.'] = 'Umożliwia sprawdzenie nowych wersji Friendica na github. Jeśli pojawi się nowa wersja, zostaniesz o tym poinformowany w panelu administracyjnym.';
$a->strings['Suppress Tags'] = 'Pomiń tagi';
$a->strings['Suppress showing a list of hashtags at the end of the posting.'] = 'Wyłącz wyświetlanie listy tagów na końcu wpisu.';
$a->strings['Clean database'] = 'Wyczyść bazę danych';
$a->strings['Remove old remote items, orphaned database records and old content from some other helper tables.'] = 'Usuń stare treści pochodzące z innych instancji, osierocone rekordy bazy danych i stare dane z niektórych dodatkowych tabel pomocniczych.';
$a->strings['Lifespan of remote items'] = 'Okres ważności treści z innych instancji';
$a->strings['When the database cleanup is enabled, this defines the days after which remote items will be deleted. Own items, and marked or filed items are always kept. 0 disables this behaviour.'] = 'Jeśli włączono czyszczenie bazy, ta opcja określa liczbę dni, po których treści z innych instancji zostaną usunięte. Własne treści oraz oznaczone lub zapisane wpisy są zawsze zachowywane. Ustawienie 0 wyłącza to zachowanie.';
$a->strings['Lifespan of unclaimed items'] = 'Żywotność nieodebranych przedmiotów';
$a->strings['When the database cleanup is enabled, this defines the days after which unclaimed remote items (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general lifespan value of remote items if set to 0.'] = 'Jeśli włączono czyszczenie bazy danych, ta opcja określa liczbę dni, po których niepowiązane treści z innych instancji (głównie treści z przekaźnika) zostaną usunięte. Domyślna wartość to 90 dni. Jeśli ustawiono 0, zostanie użyta ogólna wartość terminu ważności treści z innych instancji.';
$a->strings['Lifespan of raw conversation data'] = 'Czas ważności surowych danych wątku';
$a->strings['The conversation data is used for ActivityPub, as well as for debug purposes. It should be safe to remove it after 14 days, default is 90 days.'] = 'Dane pojedynczego wątku są wykorzystywane m.in. przez ActivityPub oraz w celach diagnostycznych. Można je bezpiecznie usunąć po 14 dniach, domyślnie jest to 90 dni.';
$a->strings['Maximum numbers of comments per post'] = 'Maksymalna liczba komentarzy na wpis';
$a->strings['How much comments should be shown for each post? Default value is 100.'] = 'Ile komentarzy powinno być wyświetlanych dla każdego wpisu? Domyślna wartość to 100.';
$a->strings['Maximum numbers of comments per post on the display page'] = 'Maksymalna liczba komentarzy na wpis na wyświetlanej stronie';
$a->strings['How many comments should be shown on the single view for each post? Default value is 1000.'] = 'Ile komentarzy powinno być wyświetlanych w pojedynczym widoku dla każdego wpisu? Wartość domyślna to 1000.';
$a->strings['Items per page'] = 'Liczba elementów na stronie';
$a->strings['Number of items per page in stream pages (network, community, profile/contact statuses, search).'] = 'Liczba elementów na stronę na stronach strumienia (sieć, społeczność, status profilu/kontaktu, wyszukiwanie).';
$a->strings['Items per page for mobile devices'] = 'Liczba elementów na stronę dla urządzeń mobilnych';
$a->strings['Number of items per page in stream pages (network, community, profile/contact statuses, search) for mobile devices.'] = 'Liczba elementów na stronę na stronach strumienia (sieć, społeczność, status profilu/kontaktu, wyszukiwanie) dla urządzeń mobilnych.';
$a->strings['Temp path'] = 'Ścieżka do temp';
$a->strings['If you have a restricted system where the webserver can\'t access the system temp path, enter another path here.'] = 'Jeśli masz zastrzeżony system, w którym serwer internetowy nie może uzyskać dostępu do ścieżki temp systemu, wprowadź tutaj inną ścieżkę.';
$a->strings['Only search in tags'] = 'Szukaj tylko w tagach';
$a->strings['On large systems the text search can slow down the system extremely.'] = 'W dużych systemach wyszukiwanie tekstu może wyjątkowo spowolnić system.';
$a->strings['Limited search scope'] = 'Ograniczony zakres wyszukiwania';
$a->strings['If enabled, searches will only be performed in the data used for the channels and not in all posts.'] = 'Jeśli włączone, wyszukiwanie będzie obejmować tylko dane kanałów, a nie wszystkich wpisów.';
$a->strings['Maximum age of items in the search table'] = 'Maksymalny wiek elementów w tabeli wyszukiwania';
$a->strings['Maximum age of items in the search table in days. Lower values will increase the performance and reduce disk usage. 0 means no age restriction.'] = 'Maksymalny wiek elementów w tabeli wyszukiwania wyrażony w dniach. Mniejsze wartości zwiększą wydajność i zmniejszą użycie dysku. 0 oznacza brak ograniczenia.';
$a->strings['Generate counts per contact circle when calculating network count'] = 'Generuj statystyki per krąg kontaktów przy liczeniu wpisów w sieci';
$a->strings['On systems with users that heavily use contact circles the query can be very expensive.'] = 'W systemach, w których użytkownicy często używają kręgów kontaktów, ta opcja może znacznie obciążyć bazę danych.';
$a->strings['Process "view" activities'] = 'Przetwarzaj "wyświetlenia"';
$a->strings['"view" activities are mostly geberated by Peertube systems. Per default they are not processed for performance reasons. Only activate this option on performant system.'] = 'Wyświetlenia są generowane przede wszystkim przez instancje Peertube\'a. Domyślnie nie są przetwarzane z powodu wpływu na wydajność. Aktywuj tylko na bardzo wydajnym serwerze.';
$a->strings['Days, after which a contact is archived'] = 'Liczba dni, po których kontakt zostanie zarchiwizowany';
$a->strings['Number of days that we try to deliver content or to update the contact data before we archive a contact.'] = 'Liczba dni, przez które system spróbuje dostarczyć treści lub zaktualizować dane kontaktu zanim go zarchiwizuje.';
$a->strings['Maximum number of parallel workers'] = 'Maksymalna liczba równoległych workerów';
$a->strings['On shared hosters set this to %d. On larger systems, values of %d are great. Default value is %d.'] = 'Na udostępnionych usługach hostingowych ustaw tę opcję %d. W większych systemach wartości %dsą świetne . Wartość domyślna to %d.';
$a->strings['Maximum load for workers'] = 'Maksymalne obciążenie workerów.';
$a->strings['Maximum load that causes a cooldown before each worker function call.'] = 'Maksymalne obciążenie powodujące opóźnienie przed każdym wywołaniem funkcji workera.';
$a->strings['Enable fastlane'] = 'Włącz Fastlane';
$a->strings['When enabed, the fastlane mechanism starts an additional worker if processes with higher priority are blocked by processes of lower priority.'] = 'Po włączeniu, system Fastlane uruchamia dodatkowego workera, jeśli procesy o wyższym priorytecie są blokowane przez procesy o niższym priorytecie.';
$a->strings['Decoupled receiver'] = 'Asynchroniczny odbiornik treści';
$a->strings['Decouple incoming ActivityPub posts by processing them in the background via a worker process. Only enable this on fast systems.'] = 'Rozdziel przychodzące wpisy z ActivityPub, przetwarzając je asynchronicznie w tle przez proces workera. Włącz tylko na wydajnym serwerze.';
$a->strings['Cron interval'] = 'Interwał cron';
$a->strings['Minimal period in minutes between two calls of the "Cron" worker job.'] = 'Minimalny okres pomiędzy dwoma wywołaniami "Cron" workera, wyrażony w minutach.';
$a->strings['Worker defer limit'] = 'Limit odroczeń workera';
$a->strings['Per default the systems tries delivering for 15 times before dropping it.'] = 'Domyślnie zadanie zostaje porzucone po 15 nieudanych próbach wykonania go.';
$a->strings['Worker fetch limit'] = 'Liczba zadań na zapytanie workera';
$a->strings['Number of worker tasks that are fetched in a single query. Higher values should increase the performance, too high values will mostly likely decrease it. Only change it, when you know how to measure the performance of your system.'] = 'Liczba zadań workera pobieranych w jednym zapytaniu. Wyższe wartości powinny zwiększyć wydajność, zbyt wysokie prawdopodobnie ją obniżą. Zmieniaj tylko gdy wiesz jak mierzyć wydajność swojego serwera.';
$a->strings['Direct relay transfer'] = 'Bezpośredni transfer przekaźników';
$a->strings['Enables the direct transfer to other servers without using the relay servers'] = 'Umożliwia bezpośredni transfer do innych serwerów bez korzystania z przekaźników';
$a->strings['Relay scope'] = 'Zakres przekaźnika';
$a->strings['Can be "all" or "tags". "all" means that every public post should be received. "tags" means that only posts with selected tags should be received.'] = 'Mogą to być „wszystkie” lub „tagi”. „Wszystkie” oznacza, że ​​każdy publiczny wpis będzie mógł być odebrany. „Tagi” oznaczają, że będą odbierane tylko wpisy zawierające wpisane tagi.';
$a->strings['Disabled'] = 'Wyłączony';
$a->strings['all'] = 'wszystko';
$a->strings['tags'] = 'tagi';
$a->strings['Server tags'] = 'Tagi serwera';
$a->strings['Comma separated list of tags for the "tags" subscription.'] = 'Lista tagów oddzielonych przecinkami dla subskrypcji "tagi".';
$a->strings['Deny Server tags'] = 'Odrzuć tagi serwera';
$a->strings['Comma separated list of tags that are rejected.'] = 'Lista niedozwolonych tagów, oddzielonych przecinkami.';
$a->strings['Maximum amount of tags'] = 'Maksymalna liczba tagów';
$a->strings['Maximum amount of tags in a post before it is rejected as spam. The post has to contain at least one link. Posts from subscribed accounts will not be rejected.'] = 'Maksymalna liczba tagów we wpisie, powyżej której zostanie on odrzucony i uznany za spam. Wpis musi zawierać przynajmniej jeden link. Wpisy subskrybowanych kont nie będą odrzucane.';
$a->strings['Allow user tags'] = 'Zezwól na tagi użytkowników';
$a->strings['If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".'] = 'Jeśli ta opcja jest włączona, tagi z zapisanych wyszukiwań będą uznane za subskrybowane, jako uzupełnienie do "relay_server_tags".';
$a->strings['Deny undetected languages'] = 'Odrzuć niewykryte języki';
$a->strings['If enabled, posts with undetected languages will be rejected.'] = 'Gdy włączone, wpisy bez zidentyfikowanych języków zostaną odrzucone.';
$a->strings['Language Quality'] = 'Jakość języka';
$a->strings['The minimum language quality that is required to accept the post.'] = 'Minimalna jakość języka wymagana do akceptacji wpisu.';
$a->strings['Number of languages for the language detection'] = 'Maksymalna liczba wykrywanych języków';
$a->strings['The system detects a list of languages per post. Only if the desired languages are in the list, the message will be accepted. The higher the number, the more posts will be falsely detected.'] = 'System wykrywa listę języków dla każdego wpisu. Wpis zostanie zaakceptowany tylko jeśli pożądane języki znajdę się na tej liście. Im wyższa liczba, tym więcej wpisów zostanie nieprawidłowo wykrytych.';
$a->strings['Maximum age of channel'] = 'Maksymalny wiek kanału';
$a->strings['This defines the maximum age in hours of items that should be displayed in channels. This affects the channel performance.'] = 'Definiuje maksymalny wiek (w godzinach) dla elementów, które powinny być wyświetlane w kanałach. Wpływa to na wydajność kanału.';
$a->strings['Maximum number of channel posts'] = 'Maksymalna liczba wpisów na kanale';
$a->strings['For performance reasons, the channels use a dedicated table to store content. The higher the value the slower the channels.'] = 'Ze względu na wydajność, kanały korzystają z dedykowanej tabel do przechowywania treści. Im wyższa wartość, tym wolniejsze działanie kanałów.';
$a->strings['Interaction score days'] = 'Liczba dni dla statystyk interakcji';
$a->strings['Number of days that are used to calculate the interaction score.'] = 'Liczba dni używanych do obliczenia statystyk interakcji.';
$a->strings['Maximum number of posts per author'] = 'Maksymalna liczba wpisów autora';
$a->strings['Maximum number of posts per page by author if the contact frequency is set to "Display only few posts". If there are more posts, then the post with the most interactions will be displayed.'] = 'Maksymalna liczba wpisów na stronę jednego autora w przypadku korzystania z opcji "Wyświetlaj tylko kilka wpisów". Jeśli liczba wpisów przekroczy podaną wartość, zostaną wyświetlone tylko wpisy z największą ilością interakcji';
$a->strings['Sharer interaction days'] = 'Dni aktywności przekazujących dalej';
$a->strings['Number of days of the last interaction that are used to define which sharers are used for the "sharers of sharers" channel.'] = 'Liczba dni od ostatniej aktywności używana do określenia które przekazane dalej wpisy będą wyświetlane w kanale "Przekazane dalej".';
$a->strings['Start Relocation'] = 'Rozpocznij przenoszenie';
$a->strings['Storage backend, %s is invalid.'] = 'Zaplecze pamięci przechowywania, %s jest nieprawidłowe.';
$a->strings['Storage backend %s error: %s'] = 'Błąd zaplecza %s pamięci przechowywania: %s';
$a->strings['Invalid storage backend setting value.'] = 'Nieprawidłowa wartość ustawienia magazynu pamięci.';
$a->strings['Current Storage Backend'] = 'Bieżące zaplecze pamięci przechowywania';
$a->strings['Storage Configuration'] = 'Konfiguracja przechowywania';
$a->strings['Storage'] = 'Przechowywanie';
$a->strings['Save & Use storage backend'] = 'Zapisz i użyj backendu przechowywania';
$a->strings['Use storage backend'] = 'Użyj backendu pamięci przechowywania';
$a->strings['Save & Reload'] = 'Zapisz i wczytaj ponownie';
$a->strings['This backend doesn\'t have custom settings'] = 'Ten backend nie ma niestandardowych ustawień';
$a->strings['Changing the current backend is prohibited because it is set by an environment variable'] = 'Zmiana obecnego backendu jest zabroniona, ponieważ jest to ustawione przez zmienną środowiskową.';
$a->strings['Database (legacy)'] = 'Baza danych (legacy)';
$a->strings['Template engine (%s) error: %s'] = 'Silnik szablonów (%s) błąd: %s';
$a->strings['Your DB still runs with MyISAM tables. You should change the engine type to InnoDB. As Friendica will use InnoDB only features in the future, you should change this! See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = 'Twoja baza danych nadal używa tabel MyISAM. Powinieneś(-naś) zmienić typ silnika na InnoDB. Ponieważ Friendica będzie używać w przyszłości wyłącznie funkcji InnoDB, powinieneś(-naś) to zmienić! Zobacz <a href="%s">tutaj</a> przewodnik, który może być pomocny w konwersji silników tabel. Możesz także użyć polecenia <tt>php bin/console.php dbstructure toinnodb</tt> instalacji Friendica, aby dokonać automatycznej konwersji.<br />';
$a->strings['Your DB still runs with InnoDB tables in the Antelope file format. You should change the file format to Barracuda. Friendica is using features that are not provided by the Antelope format. See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = 'Twoja baza danych nadal działa z tabelami InnoDB w formacie pliku Antelope. Powinieneś zmienić format pliku na Barracuda. Friendica korzysta z funkcji, których nie zapewnia format Antelope. Zobacz <a href="%s">tutaj</a> przewodnik, który może być pomocny w konwersji silników tabel. Możesz również użyć polecenia <tt>php bin/console.php dbstructure toinnodb</tt> Twojej instalacji Friendica do automatycznej konwersji.<br />';
$a->strings['Your table_definition_cache is too low (%d). This can lead to the database error "Prepared statement needs to be re-prepared". Please set it at least to %d. See <a href="%s">here</a> for more information.<br />'] = 'Twoja pamięć podręczna w table_definition_cache jest zbyt niska (%d). Może to prowadzić do błędu bazy danych „Przygotowana instrukcja wymaga ponownego przygotowania”. Ustaw przynajmniej na %d. Zobacz <a href="%s">tutaj</a>, aby uzyskać więcej informacji.<br />';
$a->strings['There is a new version of Friendica available for download. Your current version is %1$s, upstream version is %2$s'] = 'Dostępna jest nowa wersja aplikacji Friendica. Twoja aktualna wersja to %1$s wyższa wersja to %2$s';
$a->strings['The database update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear.'] = 'Aktualizacja bazy danych nie powiodła się. Uruchom polecenie "php bin/console.php dbstructure update" z wiersza poleceń i sprawdź błędy, które mogą się pojawić.';
$a->strings['The last update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear. (Some of the errors are possibly inside the logfile.)'] = 'Ostatnia aktualizacja nie powiodła się. Uruchom polecenie "php bin/console.php dbstructure update" z wiersza poleceń i spójrz na błędy, które mogą się pojawić. (Niektóre błędy są prawdopodobnie w pliku dziennika).';
$a->strings['The system.url entry is missing. This is a low level setting and can lead to unexpected behavior. Please add a valid entry as soon as possible in the config file or per console command!'] = 'Brakuje wpisu system.url. Jest to podstawowe ustawienie, które może prowadzić do nieprzewidywalnego zachowania. Dodaj prawidłowy wpis jak najszybciej w pliku konfiguracyjnym lub porzez polecenie w konsoli!';
$a->strings['The worker was never executed. Please check your database structure!'] = 'Worker nigdy nie został wykonany. Sprawdź swoją strukturę bazy danych!';
$a->strings['The last worker execution was on %s UTC. This is older than one hour. Please check your crontab settings.'] = 'Ostatnie wykonanie workera było w %s UTC. To jest starsze niż jedna godzina. Sprawdź ustawienia crontab.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>.htconfig.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'Konfiguracja Friendiki jest teraz przechowywana w config/local.config.php, skopiuj config/local-sample.config.php i przenieś swoją konfigurację z <code>.htconfig.php</code>. Zobacz <a href="%s">stronę pomocy Config</a>, aby uzyskać pomoc dotyczącą przejścia.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>config/local.ini.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'Konfiguracja Friendiki jest teraz przechowywana w config/local.config.php, skopiuj config/local-sample.config.php i przenieś konfigurację z <code>config/local.ini.php</code>. Zobacz <a href="%s">stronę pomocy Config</a>, aby uzyskać pomoc dotyczącą przejścia.';
$a->strings['<a href="%s">%s</a> is not reachable on your system. This is a severe configuration issue that prevents server to server communication. See <a href="%s">the installation page</a> for help.'] = '<a href="%s">%s</a> nie jest osiągalny w twoim systemie. Jest to poważny problem z konfiguracją, który uniemożliwia komunikację między serwerami. Zobacz pomoc na <a href="%s">stronie instalacji</a>.';
$a->strings['Friendica\'s system.basepath was updated from \'%s\' to \'%s\'. Please remove the system.basepath from your db to avoid differences.'] = 'System.basepath Friendiki został zaktualizowany z \'%s\' do \'%s\'. Usuń system.basepath z bazy danych, aby uniknąć różnic.';
$a->strings['Friendica\'s current system.basepath \'%s\' is wrong and the config file \'%s\' isn\'t used.'] = 'Obecny system.basepath Friendiki \'%s\' jest nieprawidłowy i plik konfiguracyjny \'%s\' nie jest używany.';
$a->strings['Friendica\'s current system.basepath \'%s\' is not equal to the config file \'%s\'. Please fix your configuration.'] = 'Obecny system.basepath Friendiki \'%s\' nie jest równy plikowi konfiguracyjnemu \'%s\'. Napraw konfigurację.';
$a->strings['Message queues'] = 'Kolejki wiadomości';
$a->strings['Server Settings'] = 'Ustawienia serwera';
$a->strings['Version'] = 'Wersja';
$a->strings['Active addons'] = 'Aktywne dodatki';
$a->strings['Theme %s disabled.'] = 'Motyw %s wyłączony.';
$a->strings['Theme %s successfully enabled.'] = 'Motyw %s został pomyślnie włączony.';
$a->strings['Theme %s failed to install.'] = 'Nie udało się zainstalować motywu %s.';
$a->strings['Screenshot'] = 'Zrzut ekranu';
$a->strings['Themes'] = 'Wygląd';
$a->strings['Unknown theme.'] = 'Nieznany motyw.';
$a->strings['Themes reloaded'] = 'Motywy zostały ponownie wczytane';
$a->strings['Reload active themes'] = 'Wczytaj ponownie aktywne motywy';
$a->strings['No themes found on the system. They should be placed in %1$s'] = 'Nie znaleziono motywów w systemie. Powinny zostać umieszczone %1$s';
$a->strings['[Experimental]'] = '[Eksperymentalne]';
$a->strings['[Unsupported]'] = '[Niewspieralne]';
$a->strings['Display Terms of Service'] = 'Wyświetl Warunki korzystania z usługi';
$a->strings['Enable the Terms of Service page. If this is enabled a link to the terms will be added to the registration form and the general information page.'] = 'Włącz stronę Warunki świadczenia usług. Jeśli ta opcja jest włączona, link do warunków zostanie dodany do formularza rejestracyjnego i strony z informacjami ogólnymi.';
$a->strings['Display Privacy Statement'] = 'Wyświetl oświadczenie o prywatności';
$a->strings['Show some informations regarding the needed information to operate the node according e.g. to <a href="%s" target="_blank" rel="noopener noreferrer">EU-GDPR</a>.'] = 'Poinformuj o gromadzeniu danych wymaganych do obsługi instancji, zgodnie z np. <a href="%s" target="_blank" rel="noopener noreferrer">EU-RODO</a>.';
$a->strings['Privacy Statement Preview'] = 'Podgląd oświadczenia o prywatności';
$a->strings['The Terms of Service'] = 'Warunki świadczenia usług';
$a->strings['Enter the Terms of Service for your node here. You can use BBCode. Headers of sections should be [h2] and below.'] = 'Wprowadź tutaj Warunki świadczenia usług dla Twojej instancji. Możesz użyć BBCode. Używaj nagłówków [h2] i niższych.';
$a->strings['The rules'] = 'Zasady';
$a->strings['Enter your system rules here. Each line represents one rule.'] = 'Wprowadź tutaj swoje reguły systemowe. Każda linia reprezentuje jedną regułę.';
$a->strings['API endpoint %s %s is not implemented but might be in the future.'] = 'Punkt końcowy interfejsu API  %s %s nie jest obecnie zaimplementowany, ale może zostać w przyszłości.';
$a->strings['Missing parameters'] = 'Brakuje parametrów';
$a->strings['Only starting posts can be bookmarked'] = 'Tylko początkowe wpisy można dodawać do zakładek';
$a->strings['Only starting posts can be muted'] = 'Można wyciszyć tylko początkowe wpisy';
$a->strings['Posts from %s can\'t be shared'] = 'Wpisy od %s nie mogą być udostępniane';
$a->strings['Only starting posts can be unbookmarked'] = 'Tylko początkowe wpisy można usunąć z zakładek';
$a->strings['Only starting posts can be unmuted'] = 'Wyłączać wyciszenie można tylko we wpisach początkowych';
$a->strings['Posts from %s can\'t be unshared'] = 'Nie można cofnąć udostępniania wpisów %s';
$a->strings['Contact not found'] = 'Nie znaleziono kontaktu';
$a->strings['No installed applications.'] = 'Brak zainstalowanych aplikacji.';
$a->strings['Applications'] = 'Aplikacje';
$a->strings['Item was not found.'] = 'Nie znaleziono obiektu.';
$a->strings['Please login to continue.'] = 'Zaloguj się aby kontynuować.';
$a->strings['You don\'t have access to administration pages.'] = 'Nie masz dostępu do stron administracyjnych.';
$a->strings['Submanaged account can\'t access the administration pages. Please log back in as the main account.'] = 'Konto zarządzane podrzędnie nie ma dostępu do stron administracyjnych. Zaloguj się ponownie poprzez konto główne.';
$a->strings['Overview'] = 'Przegląd';
$a->strings['Configuration'] = 'Konfiguracja';
$a->strings['Additional features'] = 'Dodatkowe funkcje';
$a->strings['Database'] = 'Baza danych';
$a->strings['DB updates'] = 'Aktualizacje bazy danych';
$a->strings['Inspect Deferred Workers'] = 'Sprawdź odroczonych workerów';
$a->strings['Inspect worker Queue'] = 'Sprawdź kolejkę workerów';
$a->strings['Diagnostics'] = 'Diagnostyka';
$a->strings['PHP Info'] = 'Informacje o PHP';
$a->strings['probe address'] = 'adres probe';
$a->strings['check webfinger'] = 'sprawdź webfinger';
$a->strings['Babel'] = 'Babel';
$a->strings['ActivityPub Conversion'] = 'Konwersja ActivityPub';
$a->strings['Addon Features'] = 'Funkcje dodatkowe';
$a->strings['User registrations waiting for confirmation'] = 'Rejestracje użytkowników czekające na potwierdzenie';
$a->strings['Too Many Requests'] = 'Zbyt dużo próśb';
$a->strings['Daily posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Osiągnięto dzienny limit %d wpisu. Wpis został odrzucony.',
	1 => 'Osiągnięto dzienny limit %d wpisów. Wpis został odrzucony.',
	2 => 'Osiągnięto dzienny limit %d wpisów. Wpis został odrzucony.',
	3 => 'Osiągnięto dzienny limit %d wpisów. Wpis został odrzucony.',
];
$a->strings['Weekly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Osiągnięto tygodniowy limit %d wpisu. Wpis został odrzucony.',
	1 => 'Osiągnięto tygodniowy limit %d wpisów. Wpis został odrzucony.',
	2 => 'Osiągnięto tygodniowy limit %d wpisów. Wpis został odrzucony.',
	3 => 'Osiągnięto tygodniowy limit %d wpisów. Wpis został odrzucony.',
];
$a->strings['Monthly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Miesięczny limit %d wpisu został osiągnięty. Wpis został odrzucony.',
	1 => 'Miesięczny limit %d wpisów został osiągnięty. Wpis został odrzucony.',
	2 => 'Miesięczny limit %d wpisów został osiągnięty. Wpis został odrzucony.',
	3 => 'Miesięczny limit %d wpisów został osiągnięty. Wpis został odrzucony.',
];
$a->strings['You don\'t have access to moderation pages.'] = 'Nie masz dostępu do stron moderacji.';
$a->strings['Submanaged account can\'t access the moderation pages. Please log back in as the main account.'] = 'Konto zarządzane nie ma dostępu do stron moderacji. Zaloguj się ponownie na konto główne.';
$a->strings['Reports'] = 'Zgłoszenia';
$a->strings['Users'] = 'Użytkownicy';
$a->strings['Tools'] = 'Narzędzia';
$a->strings['Contact Blocklist'] = 'Lista zablokowanych kontaktów';
$a->strings['Server Blocklist'] = 'Lista zablokowanych serwerów';
$a->strings['Delete Item'] = 'Usuń przedmiot';
$a->strings['Item Source'] = 'Źródło obiektu';
$a->strings['Profile Details'] = 'Szczegóły profilu';
$a->strings['Conversations started'] = 'Rozpoczęte wątki';
$a->strings['Only You Can See This'] = 'Tylko ty możesz to zobaczyć';
$a->strings['Scheduled Posts'] = 'Zaplanowane wpisy';
$a->strings['Posts that are scheduled for publishing'] = 'Wpisy oczekujące na publikację';
$a->strings['Tips for New Members'] = 'Wskazówki dla nowych użytkowników';
$a->strings['More'] = 'Więcej';
$a->strings['People Search - %s'] = 'Szukaj osób - %s';
$a->strings['Group Search - %s'] = 'Wyszukaj w grupie - %s';
$a->strings['No results.'] = 'Brak wyników.';
$a->strings['%d result was filtered out because your node blocks the domain it is registered on. You can review the list of domains your node is currently blocking in the <a href="/friendica">About page</a>.'] = [
	0 => '%d wynik został odfiltrowany, ponieważ Twoja instancja blokuje domenę z nim powiązaną. Możesz przejrzeć listę domen, które aktualnie blokuje Twoja instancja na stronie <a href="/friendica">Informacje o instancji</a>.',
	1 => '%d wyników zostało odfiltrowanych, ponieważ Twoja instancja blokuje domeny z nimi powiązane. Możesz przejrzeć listę domen, które aktualnie blokuje Twoja instancja na stronie <a href="/friendica">Informacje o instancji</a>.',
	2 => '%d wyników zostało odfiltrowanych, ponieważ Twoja instancja blokuje domeny z nimi powiązane. Możesz przejrzeć listę domen, które aktualnie blokuje Twoja instancja na stronie <a href="/friendica">Informacje o instancji</a>.',
	3 => '%d wyników zostało odfiltrowanych, ponieważ Twoja instancja blokuje domeny z nimi powiązane. Możesz przejrzeć listę domen, które aktualnie blokuje Twoja instancja na stronie <a href="/friendica">Informacje</a>.',
];
$a->strings['Account'] = 'Konto';
$a->strings['Two-factor authentication'] = 'Uwierzytelnianie dwuskładnikowe';
$a->strings['Display'] = 'Wygląd';
$a->strings['Social Networks'] = 'Portale społecznościowe';
$a->strings['Manage Accounts'] = 'Zarządzanie kontami';
$a->strings['Connected apps'] = 'Powiązane aplikacje';
$a->strings['Remote servers'] = 'Inne instancje';
$a->strings['Import Contacts'] = 'Import kontaktów';
$a->strings['Export personal data'] = 'Eksportuj dane osobiste';
$a->strings['Remove account'] = 'Usuń konto';
$a->strings['This page is missing a url parameter.'] = 'Na tej stronie brakuje parametru url.';
$a->strings['The post was created'] = 'Wpis został utworzony';
$a->strings['Invalid Request'] = 'Nieprawidłowe żądanie';
$a->strings['Event id is missing.'] = 'Brak identyfikatora wydarzenia.';
$a->strings['Failed to remove event'] = 'Nie udało się usunąć wydarzenia';
$a->strings['Event can not end before it has started.'] = 'Wydarzenie nie może się zakończyć przed jego rozpoczęciem.';
$a->strings['Event title and start time are required.'] = 'Wymagany tytuł wydarzenia i czas rozpoczęcia.';
$a->strings['Starting date and Title are required.'] = 'Data rozpoczęcia i tytuł są wymagane.';
$a->strings['Event Starts:'] = 'Rozpoczęcie wydarzenia:';
$a->strings['Required'] = 'Wymagany';
$a->strings['Finish date/time is not known or not relevant'] = 'Data/czas zakończenia nie jest znana lub jest nieistotna';
$a->strings['Event Finishes:'] = 'Zakończenie wydarzenia:';
$a->strings['Title (BBCode not allowed)'] = 'Tytuł (BBCode niedozwolony)';
$a->strings['Description (BBCode allowed)'] = 'Opis (BBCode dozwolony)';
$a->strings['Location (BBCode not allowed)'] = 'Lokalizacja (BBCode niedozwolony)';
$a->strings['Share this event'] = 'Udostępnij to wydarzenie';
$a->strings['Submit'] = 'Prześlij';
$a->strings['Basic'] = 'Podstawowe';
$a->strings['This calendar format is not supported'] = 'Ten format kalendarza nie jest obsługiwany';
$a->strings['No exportable data found'] = 'Nie znaleziono danych do eksportu';
$a->strings['calendar'] = 'kalendarz';
$a->strings['Events'] = 'Wydarzenia';
$a->strings['View'] = 'Widok';
$a->strings['list'] = 'lista';
$a->strings['Could not create circle.'] = 'Nie można utworzyć kręgu.';
$a->strings['Circle not found.'] = 'Nie znaleziono kręgu.';
$a->strings['Circle name was not changed.'] = 'Nazwa kręgu nie została zmieniona.';
$a->strings['Unknown circle.'] = 'Nieznany krąg.';
$a->strings['Contact not found.'] = 'Nie znaleziono kontaktu.';
$a->strings['Invalid contact.'] = 'Nieprawidłowy kontakt.';
$a->strings['Contact is deleted.'] = 'Kontakt został usunięty.';
$a->strings['Unable to add the contact to the circle.'] = 'Nie można dodać kontaktu do kręgu.';
$a->strings['Contact successfully added to circle.'] = 'Kontakt pomyślnie dodany do kręgu.';
$a->strings['Unable to remove the contact from the circle.'] = 'Nie można usunąć kontaktu z kręgu.';
$a->strings['Contact successfully removed from circle.'] = 'Kontakt pomyślnie usunięty z kręgu.';
$a->strings['Bad request.'] = 'Błędne żądanie.';
$a->strings['Save Circle'] = 'Zapisz Krąg.';
$a->strings['Filter'] = 'Filtr';
$a->strings['Create a circle of contacts/friends.'] = 'Utwórz Krąg kontaktów/znajomych.';
$a->strings['Unable to remove circle.'] = 'Nie można usunąć Kręgu.';
$a->strings['Delete Circle'] = 'Usuń Krąg';
$a->strings['Edit Circle Name'] = 'Edytuj nazwę Kręgu';
$a->strings['Members'] = 'Członkowie';
$a->strings['Circle is empty'] = 'Krąg jest pusty';
$a->strings['Remove contact from circle'] = 'Usuń kontakt z Kręgu';
$a->strings['Click on a contact to add or remove.'] = 'Kliknij na kontakt w celu dodania lub usunięcia.';
$a->strings['Add contact to circle'] = 'Dodaj kontakt do Kręgu';
$a->strings['%d contact edited.'] = [
	0 => 'Zedytowano %d kontakt.',
	1 => 'Zedytowano %d kontakty.',
	2 => 'Zedytowano %d kontaktów.',
	3 => '%dedytuj kontakty.',
];
$a->strings['Show all contacts'] = 'Pokaż wszystkie kontakty';
$a->strings['Pending'] = 'Oczekujące';
$a->strings['Only show pending contacts'] = 'Pokaż tylko oczekujące kontakty';
$a->strings['Blocked'] = 'Zablokowane';
$a->strings['Only show blocked contacts'] = 'Pokaż tylko zablokowane kontakty';
$a->strings['Ignored'] = 'Ignorowane';
$a->strings['Only show ignored contacts'] = 'Pokaż tylko ignorowane kontakty';
$a->strings['Collapsed'] = 'Zwinięte';
$a->strings['Only show collapsed contacts'] = 'Pokaż tylko kontakty, których treści są zwinięte';
$a->strings['Archived'] = 'Zarchiwizowane';
$a->strings['Only show archived contacts'] = 'Pokaż tylko zarchiwizowane kontakty';
$a->strings['Hidden'] = 'Ukryte';
$a->strings['Only show hidden contacts'] = 'Pokaż tylko ukryte kontakty';
$a->strings['Organize your contact circles'] = 'Kontakty w Twoich kręgach';
$a->strings['Search your contacts'] = 'Wyszukaj w kontaktach';
$a->strings['Results for: %s'] = 'Wyniki dla: %s';
$a->strings['Update'] = 'Zaktualizuj';
$a->strings['Unblock'] = 'Odblokuj';
$a->strings['Unignore'] = 'Nie ignoruj';
$a->strings['Uncollapse'] = 'Nie zwijaj';
$a->strings['Batch Actions'] = 'Masowe działania';
$a->strings['Conversations started by this contact'] = 'Wątki rozpoczęte przez ten kontakt';
$a->strings['Posts and Comments'] = 'Wpisy i komentarze';
$a->strings['Individual Posts and Replies'] = 'Indywidualne wpisy i odpowiedzi';
$a->strings['Posts containing media objects'] = 'Wpisy zawierające obiekty multimedialne';
$a->strings['View all known contacts'] = 'Zobacz wszystkie znane kontakty';
$a->strings['Advanced Contact Settings'] = 'Zaawansowane ustawienia kontaktu';
$a->strings['Friend'] = 'Znajomy';
$a->strings['Pending outgoing contact request'] = 'Oczekujące żądanie kontaktu wychodzącego';
$a->strings['Pending incoming contact request'] = 'Oczekujące żądanie kontaktu przychodzącego';
$a->strings['Visit %s\'s profile [%s]'] = 'Obejrzyj %s\'s profil [%s]';
$a->strings['Contact update failed.'] = 'Nie udało się zaktualizować kontaktu.';
$a->strings['Return to contact editor'] = 'Wróć do edytora kontaktów';
$a->strings['Name'] = 'Nazwa';
$a->strings['Account Nickname'] = 'Nazwa konta';
$a->strings['Account URL'] = 'Adres URL konta';
$a->strings['Poll/Feed URL'] = 'Adres Ankiety/RSS';
$a->strings['New photo from this URL'] = 'Nowe zdjęcie z tego adresu URL';
$a->strings['No known contacts.'] = 'Brak znanych kontaktów.';
$a->strings['No common contacts.'] = 'Brak wspólnych kontaktów.';
$a->strings['Follower (%s)'] = [
	0 => 'Obserwujący (%s)',
	1 => 'Obserwujący (%s)',
	2 => 'Obserwujący (%s)',
	3 => 'Obserwujący (%s)',
];
$a->strings['Following (%s)'] = [
	0 => 'Obserwowani (%s)',
	1 => 'Obserwowani (%s)',
	2 => 'Obserwowani (%s)',
	3 => 'Obserwowani (%s)',
];
$a->strings['These contacts both follow and are followed by <strong>%s</strong>.'] = 'Te kontakty zarówno śledzą, jak i są śledzone przez <strong>%s</strong>.';
$a->strings['Common contact (%s)'] = [
	0 => 'Wspólne kontakty (%s)',
	1 => 'Wspólne kontakty (%s)',
	2 => 'Wspólne kontakty (%s)',
	3 => 'Wspólne kontakty (%s)',
];
$a->strings['Both <strong>%s</strong> and yourself have publicly interacted with these contacts (follow, comment or likes on public posts).'] = 'Zarówno <strong>%s</strong>, jak i Ty, nawiązaliście publiczną interakcję z tymi kontaktami (obserwujecie, komentujecie lub polubiliście publiczne wpisy).';
$a->strings['Contact (%s)'] = [
	0 => 'Kontakt (%s)',
	1 => 'Kontakty (%s)',
	2 => 'Kontaktów (%s)',
	3 => 'Kontaktów (%s)',
];
$a->strings['Access denied.'] = 'Brak dostępu.';
$a->strings['Submit Request'] = 'Wyślij zgłoszenie';
$a->strings['You already added this contact.'] = 'Już dodałeś ten kontakt.';
$a->strings['The network type couldn\'t be detected. Contact can\'t be added.'] = 'Nie można wykryć typu sieci. Kontakt nie może zostać dodany.';
$a->strings['Diaspora support isn\'t enabled. Contact can\'t be added.'] = 'Obsługa Diaspory nie jest włączona. Kontakt nie może zostać dodany.';
$a->strings['Please answer the following:'] = 'Proszę odpowiedzieć na następujące pytania:';
$a->strings['Your Identity Address:'] = 'Adres Twojego profilu:';
$a->strings['Profile URL'] = 'Adres URL profilu';
$a->strings['Tags:'] = 'Tagi:';
$a->strings['%s knows you'] = '%s zna cię';
$a->strings['Add a personal note:'] = 'Dodaj osobistą notkę:';
$a->strings['Posts and Replies'] = 'Wpisy i odpowiedzi';
$a->strings['The contact could not be added.'] = 'Nie można dodać kontaktu.';
$a->strings['Invalid request.'] = 'Nieprawidłowe żądanie.';
$a->strings['Profile Match'] = 'Dopasowanie profilu';
$a->strings['No keywords to match. Please add keywords to your profile.'] = 'Brak pasujących słów kluczowych. Dodaj słowa kluczowe do swojego profilu.';
$a->strings['No matches'] = 'Brak wyników';
$a->strings['Failed to update contact record.'] = 'Aktualizacja rekordu kontaktu nie powiodła się.';
$a->strings['Contact has been unblocked'] = 'Kontakt został odblokowany';
$a->strings['Contact has been blocked'] = 'Kontakt został zablokowany';
$a->strings['Contact has been unignored'] = 'Kontakt nie jest ignorowany';
$a->strings['Contact has been ignored'] = 'Kontakt jest ignorowany';
$a->strings['Contact has been uncollapsed'] = 'Treści kontaktu nie są już zwijane';
$a->strings['Contact has been collapsed'] = 'Treści kontaktu zostały zwinięte';
$a->strings['Private communications are not available for this contact.'] = 'Nie można nawiązać prywatnej rozmowy z tym kontaktem.';
$a->strings['This contact is on a server you ignored.'] = 'Ten kontakt znajduje się na instancji, którą ignorujesz.';
$a->strings['Never'] = 'Nigdy';
$a->strings['(Update was not successful)'] = '(Aktualizacja nie powiodła się)';
$a->strings['(Update was successful)'] = '(Aktualizacja przebiegła pomyślnie)';
$a->strings['Suggest friends'] = 'Sugeruj kontakty';
$a->strings['Network type: %s'] = 'Typ sieci: %s';
$a->strings['Communications lost with this contact!'] = 'Utracono komunikację z tym kontaktem!';
$a->strings['Fetch further information for feeds'] = 'Pobierz dalsze informacje dla kanałów';
$a->strings['Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.'] = 'Pobierz informacje takie jak podgląd zdjęć, tytuł i zajawkę z obiektu w kanale. Możesz aktywować tę opcję, jeśli kanał nie zawiera dużo tekstu. Słowa kluczowe są pobierane z nagłówka meta obiektu i wykorzystywane jako tagi.';
$a->strings['Fetch information'] = 'Pobierz informacje';
$a->strings['Fetch keywords'] = 'Pobierz słowa kluczowe';
$a->strings['Fetch information and keywords'] = 'Pobierz informacje i słowa kluczowe';
$a->strings['No mirroring'] = 'Nie powielaj';
$a->strings['Mirror as my own posting'] = 'Powielaj jako moje własne wpisy';
$a->strings['Native reshare'] = 'Przekazuj dalej w oryginalnej formie';
$a->strings['Contact Information / Notes'] = 'Informacje dot. kontaktu / Notatki';
$a->strings['Contact Settings'] = 'Ustawienia kontaktu';
$a->strings['Contact'] = 'Kontakt';
$a->strings['Their personal note'] = 'Ich osobista uwaga';
$a->strings['Edit contact notes'] = 'Edytuj notatki kontaktu';
$a->strings['Block/Unblock contact'] = 'Zablokuj/odblokuj kontakt';
$a->strings['Ignore contact'] = 'Ignoruj kontakt';
$a->strings['View conversations'] = 'Wyświetl wątki';
$a->strings['Last update:'] = 'Ostatnia aktualizacja:';
$a->strings['Update public posts'] = 'Zaktualizuj publiczne wpisy';
$a->strings['Update now'] = 'Aktualizuj teraz';
$a->strings['Awaiting connection acknowledge'] = 'Oczekiwanie na potwierdzenie połączenia';
$a->strings['Currently blocked'] = 'Obecnie zablokowany';
$a->strings['Currently ignored'] = 'Obecnie zignorowany';
$a->strings['Currently collapsed'] = 'Treści są obecnie zwinięte';
$a->strings['Currently archived'] = 'Obecnie zarchiwizowany';
$a->strings['Manage remote servers'] = 'Moderacja innych instancji';
$a->strings['Hide this contact from others'] = 'Ukryj ten kontakt przed innymi';
$a->strings['Replies/likes to your public posts <strong>may</strong> still be visible'] = 'Odpowiedzi/polubienia Twoich publicznych wpisów <strong>mogą</strong> wciąż być widoczne';
$a->strings['Notification for new posts'] = 'Powiadomienie o nowych wpisach';
$a->strings['Send a notification of every new post of this contact'] = 'Wyślij powiadomienie o każdym nowym wpisie tego kontaktu';
$a->strings['Keyword Deny List'] = 'Lista odrzuconych słów kluczowych';
$a->strings['Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected'] = 'Lista słów kluczowych rozdzielonych przecinkami, które nie powinny zostać przekonwertowane na tagi, gdy aktywna jest opcja \'Pobierz informacje i słowa kluczowe\'';
$a->strings['Actions'] = 'Akcja';
$a->strings['Status'] = 'Status';
$a->strings['Mirror postings from this contact'] = 'Powielaj wpisy tego kontaktu';
$a->strings['Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'] = 'Oznacz ten kontakt jako remote_self. To sprawi, że jego wpisy będą powielane przez Twój profil.';
$a->strings['Channel Settings'] = 'Ustawienia kanału';
$a->strings['Frequency of this contact in relevant channels'] = 'Częstotliwość wyświetlania treści tego kontaktu w kanałach';
$a->strings['Depending on the type of the channel not all posts from this contact are displayed. By default, posts need to have a minimum amount of interactions (comments, likes) to show in your channels. On the other hand there can be contacts who flood the channel, so you might want to see only some of their posts. Or you don\'t want to see their content at all, but you don\'t want to block or hide the contact completely.'] = 'W zależności od typu Kanału, nie wszystkie wpisy tego kontaktu są wyświetlane. Domyślnie, aby pojawić się w Twoich Kanałach, wpis musi zebrać minimalną liczbę interakcji (komentarze, polubienia). Z drugiej strony, zdarzają się kontakty zalewające Kanał treściami, więc możesz chcieć oglądać tylko część ich wpisów. Możesz też w ogóle nie chcieć widzieć ich treści, ale bez całkowitego blokowania czy ukrywania kontaktu.';
$a->strings['Default frequency'] = 'Domyślna częstotliwość';
$a->strings['Posts by this contact are displayed in the "for you" channel if you interact often with this contact or if a post reached some level of interaction.'] = 'Wpisy tego kontaktu są wyświetlane w kanale "Dla Ciebie" jeśli często wchodzisz w interakcję z tym kontaktem lub jeśli wpis uzyskał pewien poziom interakcji.';
$a->strings['Display all posts of this contact'] = 'Wyświetlaj wszystkie wpisy tego kontaktu';
$a->strings['All posts from this contact will appear on the "for you" channel'] = 'Wszystkie wpisy tego kontaktu będą wyświetlane w kanale "Dla Ciebie"';
$a->strings['Display only few posts'] = 'Wyświetlaj tylko kilka wpisów';
$a->strings['When a contact creates a lot of posts in a short period, this setting reduces the number of displayed posts in every channel.'] = 'Kiedy kontakt tworzy bardzo dużo wpisów w krótkim czasie, to ustawienie redukuje ich wyświetlanie w każdym kanale.';
$a->strings['Never display posts'] = 'Nigdy nie wyświetlaj wpisów';
$a->strings['Posts from this contact will never be displayed in any channel'] = 'Wpisy tego kontaktu nigdy nie będą wyświetlane w żadnym kanale';
$a->strings['Channel Only'] = 'Tylko kanały';
$a->strings['If enabled, posts from this contact will only appear in channels and network streams in circles, but not in the general network stream.'] = 'Po włączeniu, wpisy tego kontaktu pojawią się tylko w Kanałach i Kręgach znajomych, a nie w ogólnym Strumieniu Sieci.';
$a->strings['Refetch contact data'] = 'Pobierz ponownie dane kontaktowe';
$a->strings['Toggle Blocked status'] = 'Przełącz stan na Zablokowany';
$a->strings['Toggle Ignored status'] = 'Przełącz stan na Ignorowany';
$a->strings['Toggle Collapsed status'] = 'Zmień status zwijania treści';
$a->strings['Revoke Follow'] = 'Przestań obserwować';
$a->strings['Revoke the follow from this contact'] = 'Anuluj obserwację przez ten kontakt';
$a->strings['Bad Request.'] = 'Błędne zapytanie.';
$a->strings['Contact is being deleted.'] = 'Kontakt jest usuwany.';
$a->strings['Follow was successfully revoked.'] = 'Obserwacja została pomyślnie anulowana.';
$a->strings['Do you really want to revoke this contact\'s follow? This cannot be undone and they will have to manually follow you back again.'] = 'Czy na pewno chcesz cofnąć obserwowanie przez ten kontakt? Nie można tego cofnąć i przy chęci przywrócenia obserwacji będzie trzeba zrobić to ponownie ręcznie.';
$a->strings['No suggestions available. If this is a new site, please try again in 24 hours.'] = 'Brak dostępnych sugestii. Jeśli jest to nowa witryna, spróbuj ponownie za 24 godziny.';
$a->strings['You aren\'t following this contact.'] = 'Nie obserwujesz tego kontaktu.';
$a->strings['Unfollowing is currently not supported by your network.'] = 'Aktualnie Twoja sieć nie obsługuje funkcji „Nie obserwuj”.';
$a->strings['Disconnect/Unfollow'] = 'Rozłącz/Nie obserwuj';
$a->strings['Contact was successfully unfollowed'] = 'Kontakt pomyślnie przestał być obserwowany';
$a->strings['Unable to unfollow this contact, please contact your administrator'] = 'Nie można przestać obserwować tego kontaktu, skontaktuj się z administratorem';
$a->strings['Channel not available.'] = 'Kanał niedostępny';
$a->strings['This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.'] = 'Ten strumień społecznościowy pokazuje wszystkie publiczne wpisy odebrane przez tę instancję. Mogą one nie mieć związku z poglądami użytkowników tej instancji.';
$a->strings['Community option not available.'] = 'Opcja społeczności niedostępna.';
$a->strings['Not available.'] = 'Niedostępne.';
$a->strings['No such circle'] = 'Nie ma takiego kręgu';
$a->strings['Circle: %s'] = 'Krąg: %s';
$a->strings['Error %d (%s) while fetching the timeline.'] = 'Błąd %d (%s) podczas pobierania osi czasu.';
$a->strings['Network feed not available.'] = 'Kanał Sieci niedostępny.';
$a->strings['Include'] = 'Uwzględnij';
$a->strings['Hide'] = 'Nie uwzględniaj';
$a->strings['Credits'] = 'Zaufany';
$a->strings['Friendica is a community project, that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!'] = 'Friendica to projekt społecznościowy, który nie byłby możliwy bez pomocy wielu osób. Oto lista osób, które przyczyniły się do tworzenia kodu lub tłumaczenia Friendica. Dziękuję wam wszystkim!';
$a->strings['Formatted'] = 'Sformatowany';
$a->strings['Activity'] = 'Aktywność';
$a->strings['Object data'] = 'Dane obiektu';
$a->strings['Result Item'] = 'Pozycja wynikowa';
$a->strings['Error'] = [
	0 => 'Błąd',
	1 => 'Błędów',
	2 => 'Błędy',
	3 => 'Błędów',
];
$a->strings['Source activity'] = 'Aktywność źródła';
$a->strings['Babel Diagnostic'] = 'Diagnostyka Babel';
$a->strings['Twitter Source / Tweet URL (requires API key)'] = 'Źródło Twitter / URL Tweeta (wymaga klucza API)';
$a->strings['You must be logged in to use this module'] = 'Musisz być zalogowany, aby korzystać z tego modułu';
$a->strings['Source URL'] = 'Źródłowy adres URL';
$a->strings['Time Conversion'] = 'Zmiana czasu';
$a->strings['Friendica provides this service for sharing events with other networks and friends in unknown timezones.'] = 'Friendica udostępnia tę usługę do udostępniania wydarzeń innym sieciom i znajomym w nieznanych strefach czasowych.';
$a->strings['UTC time: %s'] = 'Czas UTC %s';
$a->strings['Current timezone: %s'] = 'Obecna strefa czasowa: %s';
$a->strings['Converted localtime: %s'] = 'Zmień strefę czasową: %s';
$a->strings['Please select your timezone:'] = 'Wybierz swoją strefę czasową:';
$a->strings['Only logged in users are permitted to perform a probing.'] = 'Tylko zalogowani użytkownicy mogą wykonywać sondowanie.';
$a->strings['Probe Diagnostic'] = 'Diagnostyka Probe';
$a->strings['Output'] = 'Wyjście';
$a->strings['Lookup address'] = 'Wyszukaj adres';
$a->strings['Webfinger Diagnostic'] = 'Diagnostyka Webfinger';
$a->strings['Lookup address:'] = 'Wyszukaj adres:';
$a->strings['No entries (some entries may be hidden).'] = 'Brak odwiedzin (niektóre odwiedziny mogą być ukryte).';
$a->strings['Find on this site'] = 'Znajdź na tej stronie';
$a->strings['Results for:'] = 'Wyniki dla:';
$a->strings['Site Directory'] = 'Katalog Witryny';
$a->strings['Item was not deleted'] = 'Obiekt nie został skasowany';
$a->strings['Item was not removed'] = 'Obiekt nie został usunięty';
$a->strings['Folder:'] = 'Folder:';
$a->strings['- select -'] = '- wybierz -';
$a->strings['Suggested contact not found.'] = 'Nie znaleziono sugerowanego kontaktu.';
$a->strings['Friend suggestion sent.'] = 'Wysłana propozycja dodania do znajomych.';
$a->strings['Suggest Friends'] = 'Zaproponuj znajomych';
$a->strings['Suggest a friend for %s'] = 'Zaproponuj znajomych dla %s';
$a->strings['Installed addons/apps:'] = 'Zainstalowane dodatki/aplikacje:';
$a->strings['No installed addons/apps'] = 'Brak zainstalowanych dodatków/aplikacji';
$a->strings['Read about the <a href="%1$s/tos">Terms of Service</a> of this node.'] = 'Przeczytaj o <a href="%1$s/tos">Warunkach świadczenia usług</a> tej instancji.';
$a->strings['On this server the following remote servers are blocked.'] = 'Na tym serwerze są blokowane następujące inne instancje.';
$a->strings['Reason for the block'] = 'Powód blokowania';
$a->strings['Download this list in CSV format'] = 'Pobierz tę listę w formacie CSV';
$a->strings['This is Friendica, version %s that is running at the web location %s. The database version is %s, the post update version is %s.'] = 'To jest wersja Friendica, %s która działa w lokalizacji internetowej %s. Wersja bazy danych to %s wersja po aktualizacji %s.';
$a->strings['Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.'] = 'Odwiedź stronę <a href="https://friendi.ca">Friendi.ca</a> aby dowiedzieć się więcej o projekcie Friendica.';
$a->strings['Bug reports and issues: please visit'] = 'Raporty o błędach i problemy: odwiedź stronę';
$a->strings['the bugtracker at github'] = 'śledzenie błędów na github';
$a->strings['Suggestions, praise, etc. - please email "info" at "friendi - dot - ca'] = 'Propozycje, pochwały itd. – napisz e-mail do „info” małpa „friendi” - kropka - „ca”';
$a->strings['No profile'] = 'Brak profilu';
$a->strings['Method Not Allowed.'] = 'Metoda nie akceptowana.';
$a->strings['Help:'] = 'Pomoc:';
$a->strings['Welcome to %s'] = 'Witamy w %s';
$a->strings['Friendica Communications Server - Setup'] = 'Friendica Communications Server - Instalator';
$a->strings['System check'] = 'Sprawdzanie systemu';
$a->strings['Requirement not satisfied'] = 'Wymaganie niespełnione';
$a->strings['Optional requirement not satisfied'] = 'Opcjonalne wymagania niespełnione';
$a->strings['OK'] = 'OK';
$a->strings['Next'] = 'Następny';
$a->strings['Check again'] = 'Sprawdź ponownie';
$a->strings['Base settings'] = 'Ustawienia podstawowe';
$a->strings['Base path to installation'] = 'Podstawowa ścieżka do instalacji';
$a->strings['If the system cannot detect the correct path to your installation, enter the correct path here. This setting should only be set if you are using a restricted system and symbolic links to your webroot.'] = 'Jeśli system nie może wykryć poprawnej ścieżki do instalacji, wprowadź tutaj poprawną ścieżkę. To ustawienie powinno być ustawione tylko wtedy, gdy używasz ograniczonego systemu i dowiązań symbolicznych do twojego webroota.';
$a->strings['The Friendica system URL'] = 'URL serwera Friendica';
$a->strings['Overwrite this field in case the system URL determination isn\'t right, otherwise leave it as is.'] = 'Nadpisz to pole w przypadku gdy określenie URL serwera nie jest prawidłowe, w innym przypadku zostaw je niezmienione.';
$a->strings['Database connection'] = 'Połączenie z bazą danych';
$a->strings['In order to install Friendica we need to know how to connect to your database.'] = 'W celu zainstalowania Friendica musimy wiedzieć jak połączyć się z twoją bazą danych.';
$a->strings['Please contact your hosting provider or site administrator if you have questions about these settings.'] = 'Proszę skontaktuj się ze swoim dostawcą usług hostingowych bądź administratorem strony jeśli masz pytania co do tych ustawień .';
$a->strings['The database you specify below should already exist. If it does not, please create it before continuing.'] = 'Wymieniona przez Ciebie baza danych powinna już istnieć. Jeżeli nie, utwórz ją przed kontynuacją.';
$a->strings['Database Server Name'] = 'Nazwa serwera bazy danych';
$a->strings['Database Login Name'] = 'Nazwa użytkownika bazy danych';
$a->strings['Database Login Password'] = 'Hasło logowania do bazy danych';
$a->strings['For security reasons the password must not be empty'] = 'Ze względów bezpieczeństwa hasło nie może być puste';
$a->strings['Database Name'] = 'Nazwa bazy danych';
$a->strings['Please select a default timezone for your website'] = 'Proszę wybrać domyślną strefę czasową dla swojej strony';
$a->strings['Site settings'] = 'Ustawienia strony';
$a->strings['Site administrator email address'] = 'Adres e-mail administratora strony';
$a->strings['Your account email address must match this in order to use the web admin panel.'] = 'Adres e-mail konta musi pasować do tego, aby móc korzystać z panelu administracyjnego.';
$a->strings['System Language:'] = 'Język systemu:';
$a->strings['Set the default language for your Friendica installation interface and to send emails.'] = 'Ustaw domyślny język dla interfejsu instalacyjnego Friendica i wysyłaj e-maile.';
$a->strings['Your Friendica site database has been installed.'] = 'Twoja baza danych witryny Friendica została zainstalowana.';
$a->strings['Installation finished'] = 'Instalacja zakończona';
$a->strings['<h1>What next</h1>'] = '<h1>Co dalej</h1>';
$a->strings['IMPORTANT: You will need to [manually] setup a scheduled task for the worker.'] = 'WAŻNE: Będziesz musiał [ręcznie] ustawić zadanie z harmonogramem dla workera.';
$a->strings['Go to your new Friendica node <a href="%s/register">registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.'] = 'Przejdź do <a href="%s/register">strony rejestracji</a> Twojej nowej instancji Friendica i zarejestruj się jako nowy użytkownik. Pamiętaj, aby użyć adresu e-mail wprowadzonego jako e-mail administratora. Dzięki temu uzyskasz dostęp do panelu administracyjnego strony.';
$a->strings['Total invitation limit exceeded.'] = 'Przekroczono limit zaproszeń ogółem.';
$a->strings['%s : Not a valid email address.'] = '%s : Nieprawidłowy adres e-mail.';
$a->strings['Please join us on Friendica'] = 'Dołącz do nas na Friendica';
$a->strings['Invitation limit exceeded. Please contact your site administrator.'] = 'Przekroczono limit zaproszeń. Skontaktuj się z administratorem witryny.';
$a->strings['%s : Message delivery failed.'] = '%s : Nie udało się dostarczyć wiadomości.';
$a->strings['%d message sent.'] = [
	0 => '%d wiadomość wysłana.',
	1 => '%d wiadomości wysłanych.',
	2 => '%d wiadomości wysłanych.',
	3 => '%d wiadomości wysłanych.',
];
$a->strings['You have no more invitations available'] = 'Nie masz już dostępnych zaproszeń';
$a->strings['Visit %s for a list of public sites that you can join. Friendica members on other sites can all connect with each other, as well as with members of many other social networks.'] = 'Odwiedź %s listę publicznych witryn, do których możesz dołączyć. Członkowie Friendica na innych stronach mogą łączyć się ze sobą, jak również z członkami wielu innych sieci społecznościowych.';
$a->strings['To accept this invitation, please visit and register at %s or any other public Friendica website.'] = 'Aby zaakceptować to zaproszenie, odwiedź i zarejestruj się %s lub w dowolnej innej publicznej witrynie internetowej Friendica.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks. See %s for a list of alternate Friendica sites you can join.'] = 'Strony Friendica łączą się ze sobą, tworząc ogromną sieć społecznościową o zwiększonej prywatności, która jest własnością i jest kontrolowana przez jej członków. Mogą również łączyć się z wieloma tradycyjnymi sieciami społecznościowymi. Zobacz %s listę alternatywnych witryn Friendica, do których możesz dołączyć.';
$a->strings['Our apologies. This system is not currently configured to connect with other public sites or invite members.'] = 'Przepraszamy. System nie jest obecnie skonfigurowany do łączenia się z innymi publicznymi witrynami lub zapraszania członków.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks.'] = 'Strony Friendica łączą się ze sobą, tworząc ogromną sieć społecznościową o zwiększonej prywatności, która jest własnością i jest kontrolowana przez jej członków. Mogą również łączyć się z wieloma tradycyjnymi sieciami społecznościowymi.';
$a->strings['To accept this invitation, please visit and register at %s.'] = 'Aby zaakceptować to zaproszenie, odwiedź stronę i zarejestruj się na stronie %s.';
$a->strings['Send invitations'] = 'Wyślij zaproszenie';
$a->strings['Enter email addresses, one per line:'] = 'Wprowadź adresy e-mail, po jednym w wierszu:';
$a->strings['You are cordially invited to join me and other close friends on Friendica - and help us to create a better social web.'] = 'Serdecznie zapraszam do przyłączenia się do mnie i innych bliskich znajomych na stronie Friendica - i pomóż nam stworzyć lepszą sieć społecznościową.';
$a->strings['You will need to supply this invitation code: $invite_code'] = 'Musisz podać ten kod zaproszenia: $invite_code';
$a->strings['Once you have registered, please connect with me via my profile page at:'] = 'Po rejestracji połącz się ze mną na stronie mojego profilu pod adresem:';
$a->strings['For more information about the Friendica project and why we feel it is important, please visit http://friendi.ca'] = 'Aby uzyskać więcej informacji na temat projektu Friendica i dlaczego uważamy, że jest to ważne, odwiedź http://friendi.ca';
$a->strings['Please enter a post body.'] = 'Podaj treść wpisu.';
$a->strings['This feature is only available with the frio theme.'] = 'Ta funkcja jest dostępna tylko z motywem Frio.';
$a->strings['Compose new personal note'] = 'Utwórz nową notatkę';
$a->strings['Compose new post'] = 'Utwórz nowy wpis';
$a->strings['Visibility'] = 'Widoczność';
$a->strings['Clear the location'] = 'Wyczyść lokalizację';
$a->strings['Location services are unavailable on your device'] = 'Usługi lokalizacyjne są niedostępne na twoim urządzeniu';
$a->strings['Location services are disabled. Please check the website\'s permissions on your device'] = 'Usługi lokalizacyjne są wyłączone. Sprawdź uprawnienia strony internetowej na swoim urządzeniu';
$a->strings['You can make this page always open when you use the New Post button in the <a href="/settings/display">Theme Customization settings</a>.'] = 'W <a href="/settings/display">ustawieniach Dostosowania Motywu</a> możesz włączyć opcję, dzięki której kliknięcie na przycisk Nowy wpis otworzy tę stronę zamiast okna.';
$a->strings['The feed for this item is unavailable.'] = 'Kanał dla tego obiektu jest niedostępny.';
$a->strings['Unable to follow this item.'] = 'Nie można obserwować tego elementu.';
$a->strings['System down for maintenance'] = 'System wyłączony w celu konserwacji';
$a->strings['This Friendica node is currently in maintenance mode, either automatically because it is self-updating or manually by the node administrator. This condition should be temporary, please come back in a few minutes.'] = 'Ta instancja Friendica jest obecnie w trybie konserwacji, który albo został uruchomiony automatycznie z powodu samodzielnej aktualizacji, albo ręcznie przez administratora instancji. Ten stan powinien być tymczasowy, proszę wrócić za kilka minut.';
$a->strings['A Decentralized Social Network'] = 'Zdecentralizowana sieć społecznościowa';
$a->strings['You need to be logged in to access this page.'] = 'Musisz być zalogowany, by uzyskać dostęp do tej strony.';
$a->strings['Files'] = 'Pliki';
$a->strings['Upload'] = 'Wyślij';
$a->strings['Sorry, maybe your upload is bigger than the PHP configuration allows'] = 'Przepraszam, Twój przesyłany plik jest większy niż pozwala konfiguracja PHP';
$a->strings['Or - did you try to upload an empty file?'] = 'Lub - czy próbowałeś przesłać pusty plik?';
$a->strings['File exceeds size limit of %s'] = 'Plik przekracza limit rozmiaru wynoszący %s';
$a->strings['File upload failed.'] = 'Przesyłanie pliku nie powiodło się.';
$a->strings['Unable to process image.'] = 'Przetwarzanie obrazu nie powiodło się.';
$a->strings['Image upload failed.'] = 'Przesyłanie obrazu nie powiodło się.';
$a->strings['List of all users'] = 'Lista wszystkich użytkowników';
$a->strings['Active'] = 'Aktywne';
$a->strings['List of active accounts'] = 'Lista aktywnych kont';
$a->strings['List of pending registrations'] = 'Lista oczekujących rejestracji';
$a->strings['List of blocked users'] = 'Lista zablokowanych użytkowników';
$a->strings['Deleted'] = 'Usunięte';
$a->strings['List of pending user deletions'] = 'Lista oczekujących na usunięcie użytkowników';
$a->strings['Normal Account Page'] = 'Normalna strona konta';
$a->strings['Soapbox Page'] = 'Strona Soapbox';
$a->strings['Public Group'] = 'Grupa publiczna';
$a->strings['Public Group - Restricted'] = 'Grupa publiczna - ograniczona';
$a->strings['Automatic Friend Page'] = 'Automatyczna strona znajomego';
$a->strings['Private Group'] = 'Prywatna grupa';
$a->strings['Personal Page'] = 'Strona osobista';
$a->strings['Organisation Page'] = 'Strona Organizacji';
$a->strings['News Page'] = 'Strona Wiadomości';
$a->strings['Community Group'] = 'Grupa społeczności';
$a->strings['You can\'t block a local contact, please block the user instead'] = 'Nie możesz zablokować lokalnego kontaktu, zamiast tego zablokuj użytkownika';
$a->strings['%s contact unblocked'] = [
	0 => '%s kontakt odblokowany',
	1 => '%s kontakty odblokowane',
	2 => '%s kontaktów odblokowanych',
	3 => '%s kontaktów odblokowanych',
];
$a->strings['Remote Contact Blocklist'] = 'Blocklista kontaktów z innych instancji';
$a->strings['This page allows you to prevent any message from a remote contact to reach your node.'] = 'Ta strona pozwala zablokować wszystkie wiadomości od kontaktu z innej instancji, tak aby nie docierały do Twojej instancji.';
$a->strings['Block Remote Contact'] = 'Zablokuj kontakt z innej instancji';
$a->strings['select all'] = 'zaznacz wszystko';
$a->strings['select none'] = 'wybierz brak';
$a->strings['No remote contact is blocked from this node.'] = 'Ta instancja nie blokuje żadnych kontaktów z innych instancji.';
$a->strings['Blocked Remote Contacts'] = 'Zablokowane kontakty z innych instancji';
$a->strings['Block New Remote Contact'] = 'Zablokuj nowy kontakt z innej instancji';
$a->strings['Photo'] = 'Zdjęcie';
$a->strings['Reason'] = 'Powód';
$a->strings['%s total blocked contact'] = [
	0 => 'łącznie %s zablokowany kontakt',
	1 => 'łącznie %s zablokowane kontakty',
	2 => 'łącznie %s zablokowanych kontaktów',
	3 => '%s całkowicie zablokowane kontakty',
];
$a->strings['URL of the remote contact to block.'] = 'URL kontaktu z innej instancji, którego chcesz zablokować.';
$a->strings['Also purge contact'] = 'Wyczyść również kontakt';
$a->strings['Removes all content related to this contact from the node. Keeps the contact record. This action cannot be undone.'] = 'Usuwa z instancji całą zawartość powiązaną z tym kontaktem. Zachowuje rekord kontaktu. Tej czynności nie można cofnąć.';
$a->strings['Block Reason'] = 'Powód blokady';
$a->strings['← Return to the list'] = '← Wróć do listy';
$a->strings['Upload file'] = 'Wgraj plik';
$a->strings['Server domain pattern added to the blocklist.'] = 'Do listy zablokowanych dodano wzorzec domeny serwera.';
$a->strings['%s server scheduled to be purged.'] = [
	0 => '%s serwer zaplanowany do usunięcia.',
	1 => '%s serwery zaplanowane do usunięcia.',
	2 => '%s serwerów zaplanowanych do usunięcia.',
	3 => '%s serwerów zaplanowanych do usunięcia.',
];
$a->strings['Block A New Server Domain Pattern'] = 'Zablokuj nowy wzorzec domeny serwera';
$a->strings['<p>The server domain pattern syntax is case-insensitive shell wildcard, comprising the following special characters:</p>
<ul>
	<li><code>*</code>: Any number of characters</li>
	<li><code>?</code>: Any single character</li>
</ul>'] = '<p>Składnia wzorca domeny serwera to symbol wieloznaczny powłoki bez rozróżniania wielkości liter, zawierający następujące znaki specjalne:</p>
<ul>
	<li><code>*</code>: Dowolna liczba znaków</li>
	<li><code>?</code>: Dowolny pojedynczy znak</li>
</ul>';
$a->strings['Check pattern'] = 'Sprawdź wzór';
$a->strings['Matching known servers'] = 'Dopasowanie znanych serwerów';
$a->strings['Server Name'] = 'Nazwa serwera';
$a->strings['Server Domain'] = 'Domena serwera';
$a->strings['Known Contacts'] = 'Znane kontakty';
$a->strings['%d known server'] = [
	0 => '%d znany serwer',
	1 => '%d znane serwery',
	2 => '%d znanych serwerów',
	3 => '%d znanych serwerów',
];
$a->strings['Add pattern to the blocklist'] = 'Dodaj wzór do listy blokad';
$a->strings['Server Domain Pattern'] = 'Wzorzec domeny serwera';
$a->strings['The domain pattern of the new server to add to the blocklist. Do not include the protocol.'] = 'Wzorzec domeny nowego serwera do dodania do listy blokad. Nie dołączaj protokołu.';
$a->strings['Purge server'] = 'Wyczyść serwer';
$a->strings['Also purges all the locally stored content authored by the known contacts registered on that server. Keeps the contacts and the server records. This action cannot be undone.'] = [
	0 => 'Usuwa również całą lokalnie przechowywaną zawartość stworzoną przez znane kontakty zarejestrowane na tych serwerach. Zachowuje ewidencję kontaktów i serwerów. Tej czynności nie można cofnąć.',
	1 => 'Usuwa również całą lokalnie przechowywaną zawartość stworzoną przez znane kontakty zarejestrowane na tych serwerach. Zachowuje ewidencję kontaktów i serwerów. Tej czynności nie można cofnąć.',
	2 => 'Usuwa również całą lokalnie przechowywaną zawartość stworzoną przez znane kontakty zarejestrowane na tych serwerach. Zachowuje ewidencję kontaktów i serwerów. Tej czynności nie można cofnąć.',
	3 => 'Usuwa również całą lokalnie przechowywaną zawartość stworzoną przez znane kontakty zarejestrowane na tych serwerach. Zachowuje ewidencję kontaktów i serwerów. Tej czynności nie można cofnąć.',
];
$a->strings['Block reason'] = 'Powód zablokowania';
$a->strings['The reason why you blocked this server domain pattern. This reason will be shown publicly in the server information page.'] = 'Powód, dla którego zablokowałeś ten wzorzec domeny serwera. Powód ten zostanie pokazany publicznie na stronie informacyjnej serwera.';
$a->strings['Error importing pattern file'] = 'Błąd importu pliku wzorca';
$a->strings['Local blocklist replaced with the provided file.'] = 'Lokalna lista zablokowanych zastąpiona przez dostarczony plik.';
$a->strings['%d pattern was added to the local blocklist.'] = [
	0 => '%d wzorzec został dodany do lokalnej listy zablokowanych.',
	1 => '%d wzorce zostały dodane do lokalnej listy zablokowanych.',
	2 => '%d wzorców zostało dodanych do lokalnej listy zablokowanych.',
	3 => '%d wzorców zostało dodanych do lokalnej listy zablokowanych.',
];
$a->strings['No pattern was added to the local blocklist.'] = 'Żaden wzorzec nie został dodany do lokalnej listy zablokowanych.';
$a->strings['Import a Server Domain Pattern Blocklist'] = 'Importuj listę zablokowanych wzorców domen serwerów';
$a->strings['<p>This file can be downloaded from the <code>/friendica</code> path of any Friendica server.</p>'] = '<p>Ten plik może być pobrany ze ścieżki <code>/friendica</code> każdego serwera Friendica.</p>';
$a->strings['Patterns to import'] = 'Wzorce do zaimportowania';
$a->strings['Domain Pattern'] = 'Wzorzec domeny';
$a->strings['Import Mode'] = 'Tryb importowania';
$a->strings['Import Patterns'] = 'Zaimportuj wzorce';
$a->strings['%d total pattern'] = [
	0 => 'Łącznie %d wzorzec',
	1 => 'Łącznie %d wzorce',
	2 => 'Łącznie %d wzorców',
	3 => 'Łącznie %d wzorców',
];
$a->strings['Server domain pattern blocklist CSV file'] = 'Plik CSV blocklisty wzorców domen serwerów';
$a->strings['Append'] = 'Dołącz';
$a->strings['Imports patterns from the file that weren\'t already existing in the current blocklist.'] = 'Importuje wzorce z pliku, których nie były dotychczas obecne na aktualnej blockliście.';
$a->strings['Replace'] = 'Zastąp';
$a->strings['Replaces the current blocklist by the imported patterns.'] = 'Zastępuje aktualną blocklistę zaimportowanymi wzorcami.';
$a->strings['Blocked server domain pattern'] = 'Zablokowany wzorzec domeny serwera';
$a->strings['Delete server domain pattern'] = 'Usuń wzorzec domeny serwera';
$a->strings['Check to delete this entry from the blocklist'] = 'Zaznacz, aby usunąć ten wpis z listy bloków';
$a->strings['Server Domain Pattern Blocklist'] = 'Lista bloków wzorców domen serwerów';
$a->strings['This page can be used to define a blocklist of server domain patterns from the federated network that are not allowed to interact with your node. For each domain pattern you should also provide the reason why you block it.'] = 'Możesz skorzystać z tej strony do zdefiniowania listy blokowanych wzorców domen z sieci federacyjnej, które chcesz wykluczyć z interakcji z Twoją instancją. Dla każdego wzorca domeny powinno się podać powód, dla którego jest on blokowany.';
$a->strings['The list of blocked server domain patterns will be made publically available on the <a href="/friendica">/friendica</a> page so that your users and people investigating communication problems can find the reason easily.'] = 'Lista zablokowanych wzorców domen serwera zostanie udostępniona publicznie na stronie <a href="/friendica">/friendica</a>, aby użytkownicy i osoby badające problemy z komunikacją mogły łatwo znaleźć przyczynę.';
$a->strings['Import server domain pattern blocklist'] = 'Zaimportuj blocklistę wzorców domen serwerów';
$a->strings['Add new entry to the blocklist'] = 'Dodaj nowy wpis do listy zablokowanych';
$a->strings['Save changes to the blocklist'] = 'Zapisz zmiany w liście zablokowanych';
$a->strings['Current Entries in the Blocklist'] = 'Aktualne wpisy na liście zablokowanych';
$a->strings['Delete entry from the blocklist'] = 'Usuń wpis z listy zablokowanych';
$a->strings['Delete entry from the blocklist?'] = 'Usunąć wpis z listy zablokowanych?';
$a->strings['Item marked for deletion.'] = 'Obiekt oznaczony do usunięcia.';
$a->strings['Delete this Item'] = 'Usuń ten przedmiot';
$a->strings['On this page you can delete an item from your node. If the item is a top level posting, the entire thread will be deleted.'] = 'Na tej stronie możesz usunąć obiekt ze swojej instancji. Jeśli obiekt jest wpisem najwyższego poziomu, cały wątek zostanie usunięty.';
$a->strings['You need to know the GUID of the item. You can find it e.g. by looking at the display URL. The last part of http://example.com/display/123456 is the GUID, here 123456.'] = 'Musisz znać identyfikator GUID tego przedmiotu. Możesz go znaleźć np. patrząc na wyświetlany adres URL. Ostatnia część http://example.com/display/123456 to GUID, tutaj 123456.';
$a->strings['GUID'] = 'GUID';
$a->strings['The GUID of the item you want to delete.'] = 'GUID obiektu, który chcesz usunąć.';
$a->strings['Item Id'] = 'ID obiektu';
$a->strings['Item URI'] = 'URI obiektu';
$a->strings['Terms'] = 'Zasady';
$a->strings['Tag'] = 'Tag';
$a->strings['Type'] = 'Typu';
$a->strings['Term'] = 'Zasada';
$a->strings['URL'] = 'URL';
$a->strings['Implicit Mention'] = 'Wzmianka niejawna';
$a->strings['Item not found'] = 'Nie znaleziono obiektu';
$a->strings['No source recorded'] = 'Żadne źródło nie zostało zapisane';
$a->strings['Please make sure the <code>debug.store_source</code> config key is set in <code>config/local.config.php</code> for future items to have sources.'] = 'Upewnij się, że klucz konfiguracji<code>debug.store_source</code> jest ustawiony w <code>config/local.config.php</code> aby przyszłe elementy miały przypisane źródła.';
$a->strings['Item Guid'] = 'GUID obiektu';
$a->strings['Contact not found or their server is already blocked on this node.'] = 'Nie znaleziono kontaktu lub jego serwer jest zablokowany na tej instancji.';
$a->strings['Please login to access this page.'] = 'Zaloguj się, by uzyskać dostęp do tej strony.';
$a->strings['Create Moderation Report'] = 'Utwórz zgłoszenie moderacyjne';
$a->strings['Pick Contact'] = 'Wybierz kontakt';
$a->strings['Please enter below the contact address or profile URL you would like to create a moderation report about.'] = 'Wpisz poniżej adres kontaktu lub URL profilu, dla którego chcesz utworzyć zgłoszenie.';
$a->strings['Contact address/URL'] = 'Adres/URL kontaktu';
$a->strings['Pick Category'] = 'Wybierz kategorię';
$a->strings['Please pick below the category of your report.'] = 'Poniżej wybierz kategorię Twojego zgłoszenia.';
$a->strings['Spam'] = 'Spam';
$a->strings['This contact is publishing many repeated/overly long posts/replies or advertising their product/websites in otherwise irrelevant conversations.'] = 'Ten profil publikuje wiele powtarzających się/zbyt długich wpisów/odpowiedzi lub reklamuje swoje produkty/strony internetowe w wątkach, które nie mają z tym nic wspólnego.';
$a->strings['Illegal Content'] = 'Nielegalne treści';
$a->strings['This contact is publishing content that is considered illegal in this node\'s hosting juridiction.'] = 'Ten kontakt publikuje treści, które są uznawane za nielegalne w jurysdykcji, w której znajduje się ta instancja.';
$a->strings['Community Safety'] = 'Bezpieczeństwo społeczności';
$a->strings['This contact aggravated you or other people, by being provocative or insensitive, intentionally or not. This includes disclosing people\'s private information (doxxing), posting threats or offensive pictures in posts or replies.'] = 'Ten kontakt zdenerwował Cię lub inne osoby poprzez prowokacyjne lub niedelikatne zachowanie, celowe lub nie. Obejmuje to ujawnianie prywatnych informacji ludzi (doxxing), publikowanie gróźb lub obraźliwych zdjęć we wpisach lub odpowiedziach.';
$a->strings['Unwanted Content/Behavior'] = 'Niechciane treści/zachowanie';
$a->strings['This contact has repeatedly published content irrelevant to the node\'s theme or is openly criticizing the node\'s administration/moderation without directly engaging with the relevant people for example or repeatedly nitpicking on a sensitive topic.'] = 'Ten kontakt wielokrotnie publikował treści nieodpowiednie dla tematyki instancji lub np. otwarcie krytykował administrację/moderację instancji, nie kontaktując się bezpośrednio z odpowiednimi osobami lub wielokrotnie uczepiał się drażliwych tematów.';
$a->strings['Rules Violation'] = 'Naruszenie zasad';
$a->strings['This contact violated one or more rules of this node. You will be able to pick which one(s) in the next step.'] = 'Ten kontakt naruszył jedną lub więcej zasad tej instancji. W następnym kroku będziesz mógł wybrać, które z nich.';
$a->strings['Please elaborate below why you submitted this report. The more details you provide, the better your report can be handled.'] = 'Opisz szczegółowo powód tego zgłoszenia. Więcej informacji pomoże nam sprawniej rozpatrzyć sprawę.';
$a->strings['Additional Information'] = 'Dodatkowe informacje';
$a->strings['Please provide any additional information relevant to this particular report. You will be able to attach posts by this contact in the next step, but any context is welcome.'] = 'Dodaj wszelkie inne informacje związane z tym zgłoszeniem. W następnym kroku możesz załączyć wpisy tego kontaktu, ale jakikolwiek kontekst jest mile widziany.';
$a->strings['Pick Rules'] = 'Wybierz zasady';
$a->strings['Please pick below the node rules you believe this contact violated.'] = 'Poniżej wybierz zasady instancji, które twierdzisz, że zostały naruszone przez ten kontakt.';
$a->strings['Pick Posts'] = 'Wybierz wpisy';
$a->strings['Please optionally pick posts to attach to your report.'] = 'Opcjonalnie, wybierz wpisy do dołączenia do Twojego zgłoszenia.';
$a->strings['Would you like to forward this report to the remote server?'] = 'Czy chcesz przekazać to zgłoszenie również do moderatorów instancji, z której pochodzi ten użytkownik?';
$a->strings['Would you ike to forward this report to the remote server?'] = 'Czy chcesz przekazać to zgłoszenie również do moderatorów instancji, z której pochodzi ten użytkownik?';
$a->strings['Submit Report'] = 'Wyślij zgłoszenie';
$a->strings['Further Action'] = 'Dalsze czynności';
$a->strings['You can also perform one of the following action on the contact you reported:'] = 'Możesz również wykonać jedną z następujących czynności dotyczącą zgłoszonego przez Ciebie kontaktu:';
$a->strings['Nothing'] = 'Nic';
$a->strings['Collapse contact'] = 'Zwiń treści kontaktu';
$a->strings['Their posts and replies will keep appearing in your Network page but their content will be collapsed by default.'] = 'Ich wpisy i odpowiedzi będą się Tobie wyświetlać na stronie Sieci, ale ich zawartość będzie domyślnie zwinięta.';
$a->strings['Their posts won\'t appear in your Network page anymore, but their replies can appear in forum threads. They still can follow you.'] = 'Wpisy tego profilu nie będą się już pojawiać na stronie Twojej Sieci, ale jego odpowiedzi mogą się pojawiać w wątkach na grupach. Nadal może Cię obserwować.';
$a->strings['Block contact'] = 'Zablokuj kontakt';
$a->strings['Their posts won\'t appear in your Network page anymore, but their replies can appear in forum threads, with their content collapsed by default. They cannot follow you but still can have access to your public posts by other means.'] = 'Ich wpisy nie będą widoczne na stronie Twojej Sieci, ale ich odpowiedzi mogą pojawiać się w wątkach na grupach z domyślnie zwiniętą treścią. Nie mogą Cię obserwować, ale wciąż mogą mieć dostęp do Twoich publicznych wpisów w inny sposób.';
$a->strings['Forward report'] = 'Przekaż zgłoszenie';
$a->strings['1. Pick a contact'] = '1. Wybierz kontakt';
$a->strings['2. Pick a category'] = '2. Wybierz kategorię';
$a->strings['2a. Pick rules'] = '2a. Wybierz zasady';
$a->strings['2b. Add comment'] = '2b. Dodaj komentarz';
$a->strings['3. Pick posts'] = '3. Wybierz wpisy';
$a->strings['List of reports'] = 'Lista zgłoszeń';
$a->strings['This page display reports created by our or remote users.'] = 'Ta strona wyświetla zgłoszenia utworzone przez użytkowników naszej lub innych instancji.';
$a->strings['No report exists at this node.'] = 'Na tej instancji nie istnieje żaden raport.';
$a->strings['Category'] = 'Kategoria';
$a->strings['%s total report'] = [
	0 => 'Łącznie %s zgłoszenie',
	1 => 'Łącznie %s zgłoszenia',
	2 => 'Łącznie %s zgłoszeń',
	3 => 'Łącznie %s zgłoszeń',
];
$a->strings['URL of the reported contact.'] = 'URL zgłoszonego kontaktu.';
$a->strings['Channel Relay'] = 'Przekaźnik kanału';
$a->strings['Registered users'] = 'Zarejestrowani użytkownicy';
$a->strings['Pending registrations'] = 'Oczekujące rejestracje';
$a->strings['%s user blocked'] = [
	0 => '%s użytkownik zablokowany',
	1 => '%s użytkowników zablokowanych',
	2 => '%s użytkowników zablokowanych',
	3 => '%s użytkownicy zablokowani',
];
$a->strings['You can\'t remove yourself'] = 'Nie możesz usunąć siebie';
$a->strings['%s user deleted'] = [
	0 => 'usunięto %s użytkownika',
	1 => 'usunięto %s użytkowników',
	2 => 'usunięto %s użytkowników',
	3 => '%s usuniętych użytkowników',
];
$a->strings['Register date'] = 'Data rejestracji';
$a->strings['Last login'] = 'Ostatnie logowanie';
$a->strings['Last public item'] = 'Ostatni element publiczny';
$a->strings['Active Accounts'] = 'Aktywne konta';
$a->strings['User blocked'] = 'Użytkownik zablokowany';
$a->strings['Site admin'] = 'Administracja stroną';
$a->strings['Account expired'] = 'Konto wygasło';
$a->strings['Create a new user'] = 'Utwórz nowego użytkownika';
$a->strings['Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'] = 'Zaznaczeni użytkownicy zostaną usunięci!\n\n Wszystko co zamieścili na tej stronie będzie trwale skasowane!\n\n Jesteś pewien?';
$a->strings['The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'] = 'Użytkownik {0} zostanie usunięty!\n\n Wszystko co zamieścił na tej stronie będzie trwale skasowane!\n\n Jesteś pewien?';
$a->strings['User "%s" deleted'] = 'Użytkownik "%s" usunięty';
$a->strings['User "%s" blocked'] = 'Użytkownik "%s" zablokowany';
$a->strings['%s user unblocked'] = [
	0 => '%s użytkownik odblokowany',
	1 => '%s użytkowników odblokowanych',
	2 => '%s użytkowników odblokowanych',
	3 => '%s użytkowników odblokowanych',
];
$a->strings['Blocked Users'] = 'Zablokowani użytkownicy';
$a->strings['User "%s" unblocked'] = 'Użytkownik "%s" odblokowany';
$a->strings['Nickname'] = 'Alias';
$a->strings['Users awaiting permanent deletion'] = 'Użytkownicy oczekujący na trwałe usunięcie';
$a->strings['Permanent deletion'] = 'Trwałe usunięcie';
$a->strings['User waiting for permanent deletion'] = 'Użytkownik czekający na trwałe usunięcie';
$a->strings['%s user approved'] = [
	0 => '%s użytkownik zatwierdzony',
	1 => '%s użytkowników zatwierdzonych',
	2 => '%s użytkowników zatwierdzonych',
	3 => '%s użytkowników zatwierdzonych',
];
$a->strings['%s registration revoked'] = [
	0 => '%s rejestrację cofnięto',
	1 => '%s rejestracje cofnięto',
	2 => '%s rejestracji cofnięto',
	3 => '%s rejestracji cofnięto ',
];
$a->strings['Account approved.'] = 'Konto zatwierdzone.';
$a->strings['Registration revoked'] = 'Rejestracja odwołana';
$a->strings['User registrations awaiting review'] = 'Rejestracje użytkowników oczekujące na sprawdzenie';
$a->strings['Request date'] = 'Data prośby';
$a->strings['No registrations.'] = 'Brak rejestracji.';
$a->strings['Note from the user'] = 'Uwaga od użytkownika';
$a->strings['Deny'] = 'Odmów';
$a->strings['Show Ignored Requests'] = 'Pokaż ignorowane prośby';
$a->strings['Hide Ignored Requests'] = 'Ukryj zignorowane prośby';
$a->strings['Notification type:'] = 'Typ powiadomienia:';
$a->strings['Suggested by:'] = 'Sugerowany przez:';
$a->strings['Remove'] = 'Usuń';
$a->strings['Claims to be known to you: '] = 'Twierdzi, że go/ją znasz: ';
$a->strings['No more %s notifications.'] = 'Brak kolejnych %s powiadomień.';
$a->strings['You must be logged in to show this page.'] = 'Musisz być zalogowany, aby zobaczyć tę stronę.';
$a->strings['Network Notifications'] = 'Powiadomienia z sieci';
$a->strings['System Notifications'] = 'Powiadomienia wewnętrzne';
$a->strings['Personal Notifications'] = 'Powiadomienia dotyczące mnie';
$a->strings['Home Notifications'] = 'Powiadomienia o moich wpisach';
$a->strings['Show unread'] = 'Pokaż nieprzeczytane';
$a->strings['{0} requested registration'] = '{0} wymagana rejestracja';
$a->strings['{0} and %d others requested registration'] = '{0} i %d innych poprosili o rejestrację';
$a->strings['Authorize application connection'] = 'Autoryzacja połączenia aplikacji';
$a->strings['Do you want to authorize this application to access your posts and contacts, and/or create new posts for you?'] = 'Czy chcesz upoważnić tę aplikację do dostępu do swoich wpisów i kontaktów oraz / lub tworzenia nowych wpisów?';
$a->strings['Unsupported or missing response type'] = 'Nieobsługiwany lub brakujący typ odpowiedzi';
$a->strings['Incomplete request data'] = 'Niekompletne dane żądania';
$a->strings['Please copy the following authentication code into your application and close this window: %s'] = 'Skopiuj następujący kod uwierzytelniający do swojej aplikacji i zamknij to okno: %s';
$a->strings['Invalid data or unknown client'] = 'Nieprawidłowe dane lub nieznany klient';
$a->strings['Unsupported or missing grant type'] = 'Nieobsługiwany lub brakujący typ dotacji';
$a->strings['Subscribing to contacts'] = 'Subskrybowanie kontaktów';
$a->strings['No contact provided.'] = 'Brak kontaktu.';
$a->strings['Couldn\'t fetch information for contact.'] = 'Nie można pobrać informacji o kontakcie.';
$a->strings['Couldn\'t fetch friends for contact.'] = 'Nie można pobrać znajomych do kontaktu.';
$a->strings['Couldn\'t fetch following contacts.'] = 'Nie udało się pobrać następujących kontaktów.';
$a->strings['Couldn\'t fetch remote profile.'] = 'Nie udało się pobrać profilu z innej instancji.';
$a->strings['Unsupported network'] = 'Sieć nieobsługiwana';
$a->strings['Done'] = 'Gotowe';
$a->strings['success'] = 'powodzenie';
$a->strings['failed'] = 'nie powiodło się';
$a->strings['ignored'] = 'ignorowany(-a)';
$a->strings['Keep this window open until done.'] = 'Pozostaw to okno otwarte, dopóki nie będzie gotowe.';
$a->strings['Search in Friendica %s'] = 'Wyszukaj w Friendica %s';
$a->strings['The Photo is not available.'] = 'Zdjęcie jest niedostępne.';
$a->strings['The Photo with id %s is not available.'] = 'Zdjęcie z identyfikatorem %s nie jest dostępne.';
$a->strings['Invalid external resource with url %s.'] = 'Nieprawidłowy zasób zewnętrzny z adresem URL %s.';
$a->strings['Invalid photo with id %s.'] = 'Nieprawidłowe zdjęcie z identyfikatorem %s.';
$a->strings['Post not found.'] = 'Nie znaleziono wpisu.';
$a->strings['Edit post'] = 'Edytuj wpis';
$a->strings['web link'] = 'link';
$a->strings['Insert video link'] = 'Wstaw link do filmu';
$a->strings['video link'] = 'link do filmu';
$a->strings['Insert audio link'] = 'Wstaw link do audio';
$a->strings['audio link'] = 'link do audio';
$a->strings['Remove Item Tag'] = 'Usuń tag obiektu';
$a->strings['Select a tag to remove: '] = 'Wybierz tag do usunięcia: ';
$a->strings['Wrong type "%s", expected one of: %s'] = 'Nieprawidłowy typ „%s”, oczekiwano jednego z:%s';
$a->strings['Model not found'] = 'Nie znaleziono modelu';
$a->strings['Unlisted'] = 'Niekatalogowany';
$a->strings['Remote privacy information not available.'] = 'Brak danych o prywatności z innej instancji.';
$a->strings['Visible to:'] = 'Widoczne dla:';
$a->strings['CC:'] = 'DW:';
$a->strings['BCC:'] = 'UDW:';
$a->strings['Audience:'] = 'Odbiorcy:';
$a->strings['Attributed To:'] = 'Przypisane do:';
$a->strings['Collection (%s)'] = 'Kolekcja (%s)';
$a->strings['Followers (%s)'] = 'Obserwujący (%s)';
$a->strings['%d more'] = '%d więcej';
$a->strings['No contacts.'] = 'Brak kontaktów.';
$a->strings['%s\'s posts'] = 'wpisy %s';
$a->strings['%s\'s comments'] = 'komentarze %s';
$a->strings['%s\'s timeline'] = 'oś czasu %s';
$a->strings['Image exceeds size limit of %s'] = 'Obraz przekracza limit rozmiaru wynoszący %s';
$a->strings['Image upload didn\'t complete, please try again'] = 'Przesyłanie zdjęć nie zostało zakończone, spróbuj ponownie';
$a->strings['Image file is missing'] = 'Brak pliku obrazu';
$a->strings['Server can\'t accept new file upload at this time, please contact your administrator'] = 'Serwer nie może teraz przyjąć nowego pliku, skontaktuj się z administratorem';
$a->strings['Image file is empty.'] = 'Plik obrazka jest pusty.';
$a->strings['View Album'] = 'Zobacz album';
$a->strings['Profile not found.'] = 'Nie znaleziono profilu.';
$a->strings['You\'re currently viewing your profile as <b>%s</b> <a href="%s" class="btn btn-sm pull-right">Cancel</a>'] = 'Obecnie przeglądasz swój profil jako <b>%s</b> <a href="%s" class="btn btn-sm pull-right">Anuluj</a>';
$a->strings['Display name:'] = 'Nazwa wyświetlana:';
$a->strings['Member since:'] = 'Członek od:';
$a->strings['j F, Y'] = 'd M, R';
$a->strings['j F'] = 'd M';
$a->strings['Birthday:'] = 'Urodziny:';
$a->strings['Age: '] = 'Wiek: ';
$a->strings['%d year old'] = [
	0 => '%d rok',
	1 => '%d lata',
	2 => '%d lat',
	3 => '%d lat',
];
$a->strings['Description:'] = 'Opis:';
$a->strings['Groups:'] = 'Grupy:';
$a->strings['View profile as:'] = 'Wyświetl profil jako:';
$a->strings['Edit profile'] = 'Edytuj profil';
$a->strings['View as'] = 'Zobacz jako';
$a->strings['Profile unavailable.'] = 'Profil niedostępny.';
$a->strings['Invalid locator'] = 'Nieprawidłowy lokalizator';
$a->strings['The provided profile link doesn\'t seem to be valid'] = 'Podany link profilu wydaje się być nieprawidłowy';
$a->strings['Remote subscription can\'t be done for your network. Please subscribe directly on your system.'] = 'Subskrypcja na innej instancji nie jest możliwa w Twojej sieci. Proszę subskrybować bezpośrednio na swojej instancji.';
$a->strings['Friend/Connection Request'] = 'Zaproszenie do kontaktu';
$a->strings['Enter your Webfinger address (user@domain.tld) or profile URL here. If this isn\'t supported by your system, you have to subscribe to <strong>%s</strong> or <strong>%s</strong> directly on your system.'] = 'Wpisz tutaj swój adres Webfinger (user@domain.tld) lub adres URL profilu. Jeśli nie jest to obsługiwane przez system, musisz subskrybować <strong>%s</strong> lub <strong>%s</strong> bezpośrednio w systemie.';
$a->strings['If you are not yet a member of the free social web, <a href="%s">follow this link to find a public Friendica node and join us today</a>.'] = 'Jeśli nie jesteś jeszcze członkiem darmowej sieci społecznościowej, <a href="%s">kliknij ten odnośnik, aby znaleźć publiczną instancję Friendica i dołącz do nas już dziś</a>.';
$a->strings['Your Webfinger address or profile URL:'] = 'Twój adres lub adres URL profilu Webfinger:';
$a->strings['Restricted profile'] = 'Ograniczony profil';
$a->strings['This profile has been restricted which prevents access to their public content from anonymous visitors.'] = 'Ten profil został ograniczony, co uniemożliwia dostęp do jego publicznych treści przez anonimowych odwiedzających.';
$a->strings['Private Message'] = 'Niepubliczna wiadomość';
$a->strings['via'] = 'przez';
$a->strings['Scheduled'] = 'Zaplanowano';
$a->strings['Content'] = 'Zawartość';
$a->strings['Remove post'] = 'Usuń wpis';
$a->strings['Only parent users can create additional accounts.'] = 'Tylko użytkownicy nadrzędni mogą tworzyć dodatkowe konta.';
$a->strings['This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.'] = 'Strona przekroczyła ilość dozwolonych rejestracji na dzień. Proszę spróbuj ponownie jutro.';
$a->strings['You may (optionally) fill in this form via OpenID by supplying your OpenID and clicking "Register".'] = 'Możesz (opcjonalnie) wypełnić ten formularz za pośrednictwem OpenID, podając swój OpenID i klikając "Register".';
$a->strings['If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.'] = 'Jeśli nie jesteś zaznajomiony z OpenID, zostaw to pole puste i uzupełnij resztę elementów.';
$a->strings['Your OpenID (optional): '] = 'Twój OpenID (opcjonalnie): ';
$a->strings['Include your profile in member directory?'] = 'Czy dołączyć twój profil do katalogu członków?';
$a->strings['Note for the admin'] = 'Uwaga dla administratora';
$a->strings['Leave a message for the admin, why you want to join this node'] = 'Przekaż administratorowi, dlaczego chcesz dołączyć do tej instancji';
$a->strings['Membership on this site is by invitation only.'] = 'Członkostwo na tej stronie możliwe tylko dzięki zaproszeniu.';
$a->strings['Your invitation code: '] = 'Twój kod zaproszenia: ';
$a->strings['Your Display Name (as you would like it to be displayed on this system):'] = 'Nazwa wyświetlana (imię i nazwisko lub pseudonim)';
$a->strings['Your Email Address (initial information will be sent there, so this must be a valid address):'] = 'Adres e-mail (otrzymasz na niego hasło do Twojego konta)';
$a->strings['Please repeat your e-mail address:'] = 'Powtórz swój adres e-mail:';
$a->strings['New Password:'] = 'Nowe hasło:';
$a->strings['Leave empty for an auto generated password.'] = 'Pozostaw puste dla wygenerowanego automatycznie hasła.';
$a->strings['Confirm:'] = 'Potwierdź:';
$a->strings['Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be "<strong>nickname@%s</strong>".'] = 'Wybierz alias profilu. Musi zaczynać się od znaku tekstowego. Adres Twojego profilu będzie w formacie "<strong>alias@%s</strong>".';
$a->strings['Choose a nickname: '] = 'Wybierz alias: ';
$a->strings['Import'] = 'Import';
$a->strings['Import your profile to this friendica instance'] = 'Zaimportuj swój profil do tej instancji friendica';
$a->strings['Note: This node explicitly contains adult content'] = 'Uwaga: Ta instancja zawiera jawne treści dla dorosłych';
$a->strings['Parent Password:'] = 'Hasło nadrzędne:';
$a->strings['Please enter the password of the parent account to legitimize your request.'] = 'Wprowadź hasło Twojego konta, aby potwierdzić utworzenie nowego profilu.';
$a->strings['Password doesn\'t match.'] = 'Hasło nie jest zgodne.';
$a->strings['Please enter your password.'] = 'Wprowadź hasło.';
$a->strings['You have entered too much information.'] = 'Podałeś za dużo informacji.';
$a->strings['Please enter the identical mail address in the second field.'] = 'Wpisz identyczny adres e-mail w drugim polu.';
$a->strings['Nickname cannot start with a digit.'] = 'Alias nie może rozpoczynać się od cyfry.';
$a->strings['Nickname can only contain US-ASCII characters.'] = 'Alias może zawierać jedynie znaki US-ASCII.';
$a->strings['The additional account was created.'] = 'Dodatkowe konto zostało utworzone.';
$a->strings['Registration successful. Please check your email for further instructions.'] = 'Rejestracja zakończona pomyślnie. Dalsze instrukcje zostały wysłane na twojego e-maila.';
$a->strings['Failed to send email message. Here your accout details:<br> login: %s<br> password: %s<br><br>You can change your password after login.'] = 'Nie udało się wysłać wiadomości e-mail. Tutaj szczegóły twojego konta:<br> login: %s<br>hasło: %s<br><br>Możesz zmienić swoje hasło po zalogowaniu.';
$a->strings['Registration successful.'] = 'Rejestracja udana.';
$a->strings['Your registration can not be processed.'] = 'Nie można przetworzyć Twojej rejestracji.';
$a->strings['You have to leave a request note for the admin.'] = 'Musisz zostawić notatkę z prośbą do administratora.';
$a->strings['An internal error occured.'] = 'Wystąpił błąd wewnętrzny.';
$a->strings['Your registration is pending approval by the site owner.'] = 'Twoja rejestracja oczekuje na zaakceptowanie przez właściciela witryny.';
$a->strings['You must be logged in to use this module.'] = 'Musisz być zalogowany, aby korzystać z tego modułu.';
$a->strings['Only logged in users are permitted to perform a search.'] = 'Tylko zalogowani użytkownicy mogą wyszukiwać.';
$a->strings['Only one search per minute is permitted for not logged in users.'] = 'Dla niezalogowanych użytkowników dozwolone jest tylko jedno wyszukiwanie na minutę.';
$a->strings['Items tagged with: %s'] = 'Elementy otagowane jako: %s';
$a->strings['Search term was not saved.'] = 'Wyszukiwane hasło nie zostało zapisane.';
$a->strings['Search term already saved.'] = 'Wyszukiwane hasło jest już zapisane.';
$a->strings['Search term was not removed.'] = 'Wyszukiwane hasło nie zostało usunięte.';
$a->strings['Your OpenID: '] = 'Twój OpenID: ';
$a->strings['Please enter your username and password to add the OpenID to your existing account.'] = 'Wprowadź nazwę użytkownika i hasło, aby dodać OpenID do istniejącego konta.';
$a->strings['Remember me'] = 'Zapamiętaj mnie';
$a->strings['Forgot your password?'] = 'Zapomniałeś swojego hasła?';
$a->strings['Website Terms of Service'] = 'Warunki korzystania z witryny';
$a->strings['terms of service'] = 'warunki użytkowania';
$a->strings['Website Privacy Policy'] = 'Polityka Prywatności Witryny';
$a->strings['privacy policy'] = 'polityka prywatności';
$a->strings['OpenID protocol error. No ID returned'] = 'Błąd protokołu OpenID. Nie zwrócono identyfikatora';
$a->strings['Account not found. Please login to your existing account to add the OpenID to it.'] = 'Konto nie znalezione. Zaloguj się do swojego istniejącego konta, aby dodać do niego OpenID.';
$a->strings['Account not found. Please register a new account or login to your existing account to add the OpenID to it.'] = 'Konto nie znalezione. Zarejestruj nowe konto lub zaloguj się na istniejące konto, aby dodać do niego OpenID.';
$a->strings['Passwords do not match.'] = 'Hasła nie pasują do siebie.';
$a->strings['Password does not need changing.'] = 'Hasło nie wymaga zmiany.';
$a->strings['Password unchanged.'] = 'Hasło niezmienione.';
$a->strings['Password Too Long'] = 'Hasło za długie';
$a->strings['Since version 2022.09, we\'ve realized that any password longer than 72 characters is truncated during hashing. To prevent any confusion about this behavior, please update your password to be fewer or equal to 72 characters.'] = 'Od wersji 2022.09, zdaliśmy sobie sprawę, że jakiekolwiek hasło dłuższe niż 72 znaki jest przycinane podczas szyfrowania. Aby zapobieć zamieszaniu z tym związanym, proszę zmienić swoje hasło na krótsze lub równe 72 znakom.';
$a->strings['Update Password'] = 'Zmień hasło';
$a->strings['Current Password:'] = 'Aktualne hasło:';
$a->strings['Your current password to confirm the changes'] = 'Wpisz aktualne hasło, aby potwierdzić zmiany';
$a->strings['Allowed characters are a-z, A-Z, 0-9 and special characters except white spaces and accentuated letters.'] = 'Dozwolone znaki to a-z, A-Z, 0-9 oraz znaki specjalne, z wyjątkiem białych znaków i liter akcentowanych.';
$a->strings['Password length is limited to 72 characters.'] = 'Długość hasła jest ograniczona do 72 znaków.';
$a->strings['Remaining recovery codes: %d'] = 'Pozostałe kody odzyskiwania: %d';
$a->strings['Invalid code, please retry.'] = 'Nieprawidłowy kod, spróbuj ponownie.';
$a->strings['Two-factor recovery'] = 'Odzyskiwanie dwuczynnikowe';
$a->strings['<p>You can enter one of your one-time recovery codes in case you lost access to your mobile device.</p>'] = '<p>Możesz wprowadzić jeden ze swoich jednorazowych kodów odzyskiwania w przypadku utraty dostępu do urządzenia mobilnego.</p>';
$a->strings['Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>'] = 'Nie masz telefonu? <a href="%s">Wprowadzić dwuetapowy kod przywracania </a>';
$a->strings['Please enter a recovery code'] = 'Wprowadź kod odzyskiwania';
$a->strings['Submit recovery code and complete login'] = 'Prześlij kod odzyskiwania i pełne logowanie';
$a->strings['Sign out of this browser?'] = 'Wylogować z tej przeglądarki?';
$a->strings['<p>If you trust this browser, you will not be asked for verification code the next time you sign in.</p>'] = '<p>Jeśli ufasz tej przeglądarce, przy następnym logowaniu nie zostaniesz poproszony o podanie kodu weryfikacyjnego.</p>';
$a->strings['Trust and sign out'] = 'Zaufaj i wyloguj';
$a->strings['Couldn\'t save browser to Cookie.'] = 'Nie można zapisać informacji o przeglądarce do ciasteczek.';
$a->strings['Trust this browser?'] = 'Ufać tej przeglądarce?';
$a->strings['<p>If you choose to trust this browser, you will not be asked for a verification code the next time you sign in.</p>'] = '<p>Jeśli zdecydujesz się zaufać tej przeglądarce, przy następnym logowaniu nie zostaniesz poproszony o podanie kodu weryfikacyjnego.</p>';
$a->strings['Not now'] = 'Nie teraz';
$a->strings['Don\'t trust'] = 'Nie ufaj';
$a->strings['Trust'] = 'Ufaj';
$a->strings['<p>Open the two-factor authentication app on your device to get an authentication code and verify your identity.</p>'] = '<p>Otwórz aplikację uwierzytelniania dwuskładnikowego na swoim urządzeniu, aby uzyskać kod uwierzytelniający i zweryfikować swoją tożsamość.</p>';
$a->strings['If you do not have access to your authentication code you can use a <a href="%s">two-factor recovery code</a>.'] = 'Jeśli nie masz dostępu do swojego kodu uwierzytelniającego, możesz użyć <a href="%s">dwuskładnikowego kodu odzyskiwania</a>.';
$a->strings['Please enter a code from your authentication app'] = 'Wprowadź kod z aplikacji uwierzytelniającej';
$a->strings['Verify code and complete login'] = 'Zweryfikuj kod i zakończ logowanie';
$a->strings['Please use a shorter name.'] = 'Użyj krótszej nazwy.';
$a->strings['Name too short.'] = 'Nazwa jest za krótka. ';
$a->strings['Wrong Password.'] = 'Nieprawidłowe hasło.';
$a->strings['Invalid email.'] = 'Niepoprawny e-mail.';
$a->strings['Cannot change to that email.'] = 'Nie można zmienić tego e-maila.';
$a->strings['Settings were not updated.'] = 'Ustawienia nie zostały zaktualizowane.';
$a->strings['Relocate message has been send to your contacts'] = 'Wiadomość o przeniesieniu konta została wysłana do Twoich kontaktów';
$a->strings['Unable to find your profile. Please contact your admin.'] = 'Nie można znaleźć Twojego profilu. Skontaktuj się z administratorem.';
$a->strings['Account for a service that automatically shares content based on user defined channels.'] = 'Konto bota/usługi, która automatycznie przekazuje treści z wybranych przez użytkownika źródeł.';
$a->strings['Personal Page Subtypes'] = 'Podtypy osobistych stron';
$a->strings['Community Group Subtypes'] = 'Podtypy grupy społeczności';
$a->strings['Account for a personal profile.'] = 'Konto dla profilu osobistego.';
$a->strings['Account for an organisation that automatically approves contact requests as "Followers".'] = 'Konto dla organizacji, która automatycznie zatwierdza prośby o kontakt jako "Obserwujący".';
$a->strings['Account for a news reflector that automatically approves contact requests as "Followers".'] = 'Konto dla reflektora wiadomości, który automatycznie zatwierdza prośby o kontakt jako "Obserwatorzy".';
$a->strings['Account for community discussions.'] = 'Konto do dyskusji w społeczności.';
$a->strings['Account for a regular personal profile that requires manual approval of "Friends" and "Followers".'] = 'Standardowy profil osobisty, który wymaga ręcznego zatwierdzania "Znajomych" i "Obserwatorów".';
$a->strings['Account for a public profile that automatically approves contact requests as "Followers".'] = 'Konto dla profilu publicznego, który automatycznie zatwierdza prośby o kontakt jako "Obserwatorzy".';
$a->strings['Automatically approves all contact requests.'] = 'Automatycznie zatwierdza wszystkie prośby o kontakt.';
$a->strings['Contact requests have to be manually approved.'] = 'Prośby kontaktowe muszą być zatwierdzone manualnie.';
$a->strings['Account for a popular profile that automatically approves contact requests as "Friends".'] = 'Profil popularny, które automatycznie zatwierdza prośby o kontakt jako "Znajomi".';
$a->strings['Private Group [Experimental]'] = 'Prywatna grupa [Eksperymentalne]';
$a->strings['Requires manual approval of contact requests.'] = 'Wymaga ręcznego zatwierdzania żądań kontaktów.';
$a->strings['OpenID:'] = 'OpenID:';
$a->strings['(Optional) Allow this OpenID to login to this account.'] = '(Opcjonalnie) Pozwól zalogować się na to konto przy pomocy OpenID.';
$a->strings['Publish your profile in your local site directory?'] = 'Czy opublikować Twój profil w lokalnym katalogu instancji?';
$a->strings['Your profile will be published in this node\'s <a href="%s">local directory</a>. Your profile details may be publicly visible depending on the system settings.'] = 'Twój profil zostanie opublikowany w <a href="%s">lokalnym katalogu</a> tej instancji. Dane Twojego profilu mogą być publicznie widoczne, w zależności od ustawień systemu.';
$a->strings['Your profile will also be published in the global friendica directories (e.g. <a href="%s">%s</a>).'] = 'Twój profil zostanie również opublikowany w globalnych katalogach Friendica (np. <a href="%s">%s</a>).';
$a->strings['Account Settings'] = 'Ustawienia konta';
$a->strings['Your Identity Address is <strong>\'%s\'</strong> or \'%s\'.'] = 'Adres Twojego profilu to <strong>\'%s\'</strong> lub \'%s\'.';
$a->strings['Password Settings'] = 'Zmiana hasła';
$a->strings['Leave password fields blank unless changing'] = 'Pozostaw pola hasła puste, jeżeli nie chcesz go zmieniać.';
$a->strings['Password:'] = 'Hasło:';
$a->strings['Your current password to confirm the changes of the email address'] = 'Wpisz swoje obecne hasło aby potwierdzić zmianę adresu e-mail';
$a->strings['Delete OpenID URL'] = 'Usuń adres URL OpenID';
$a->strings['Basic Settings'] = 'Podstawowe informacje';
$a->strings['Email Address:'] = 'Adres email:';
$a->strings['Your Timezone:'] = 'Twoja strefa czasowa:';
$a->strings['Your Language:'] = 'Twój język:';
$a->strings['Set the language we use to show you friendica interface and to send you emails'] = 'Wybierz język, ktory bedzie używany w interfejsie Friendica i w wysyłanych do Ciebie e-mailach';
$a->strings['Default Post Location:'] = 'Domyślna lokalizacja wpisów:';
$a->strings['Security and Privacy Settings'] = 'Prywatność i bezpieczeństwo';
$a->strings['Maximum Friend Requests/Day:'] = 'Maksymalna dzienna liczba zaproszeń do grona znajomych:';
$a->strings['(to prevent spam abuse)'] = '(aby zapobiec spamowaniu)';
$a->strings['Allow your profile to be searchable globally?'] = 'Umożliwić globalne wyszukiwanie Twojego profilu?';
$a->strings['Activate this setting if you want others to easily find and follow you. Your profile will be searchable on remote systems. This setting also determines whether Friendica will inform search engines that your profile should be indexed or not.'] = 'Włącz tę opcję, jeśli chcesz, aby inni mogli łatwo Cię znaleźć i obserwować. Twój profil będzie wyszukiwalny w innych instancjach. To ustawienie sprawi też, że Friendica poinformuje wyszukiwarki internetowe, że Twój profil powinien być indeksowany.';
$a->strings['Hide your contact/friend list from viewers of your profile?'] = 'Ukryć listę kontaktów/znajomych przed osobami przeglądającymi Twój profil?';
$a->strings['A list of your contacts is displayed on your profile page. Activate this option to disable the display of your contact list.'] = 'Lista kontaktów jest wyświetlana na stronie profilu. Aktywuj tę opcję, aby wyłączyć wyświetlanie listy kontaktów.';
$a->strings['Hide your public content from anonymous viewers'] = 'Ukryj swoje publiczne treści przed anonimowymi odwiedzającymi';
$a->strings['Anonymous visitors will only see your basic profile details. Your public posts and replies will still be freely accessible on the remote servers of your followers and through relays.'] = 'Anonimowi odwiedzający zobaczą tylko podstawowe informacje o profilu. Twoje publiczne wpisy i odpowiedzi nadal będą dowolnie dostępne na instancjach Twoich obserwujących oraz poprzez przekaźniki.';
$a->strings['Make public posts unlisted'] = 'Ustaw publiczne wpisy jako niepubliczne';
$a->strings['Your public posts will not appear on the community pages or in search results, nor be sent to relay servers. However they can still appear on public feeds on remote servers.'] = 'Twoje publiczne wpisy nie będą wyświetlane na stronach Społeczności, w wynikach wyszukiwania ani nie będą wysyłane do przekaźników. Jednak nadal mogą one pojawiać się w publicznych kanałach na innych instancjach.';
$a->strings['Make all posted pictures accessible'] = 'Udostępnij wszystkie opublikowane zdjęcia';
$a->strings['This option makes every posted picture accessible via the direct link. This is a workaround for the problem that most other networks can\'t handle permissions on pictures. Non public pictures still won\'t be visible for the public on your photo albums though.'] = 'Ta opcja powoduje, że każde opublikowane zdjęcie będzie dostępne poprzez bezpośredni link. Jest to obejście problemu polegającego na tym, że większość innych sieci nie obsługuje uprawnień do zdjęć. Jednak zdjęcia niepubliczne nadal nie będą widoczne publicznie.';
$a->strings['Allow friends to post to your profile page?'] = 'Zezwalać znajomym na publikowanie wpisów na stronie Twojego profilu?';
$a->strings['Your contacts may write posts on your profile wall. These posts will be distributed to your contacts'] = 'Twoi znajomi mogą publikować wpisy na stronie Twojego profilu. Wpisy te zostaną rozesłane do Twoich kontaktów.';
$a->strings['Allow friends to tag your posts?'] = 'Zezwolić na otagowywanie Twoich wpisów przez Twoich znajomych?';
$a->strings['Your contacts can add additional tags to your posts.'] = 'Twoje kontakty mogą dodawać tagi do Twoich wpisów.';
$a->strings['Default privacy circle for new contacts'] = 'Domyślny Krąg dla nowych kontaktów';
$a->strings['Default privacy circle for new group contacts'] = 'Domyślny krąg dla nowych grup';
$a->strings['Default Post Permissions'] = 'Domyślne prawa dostępu wiadomości';
$a->strings['Expiration settings'] = 'Ustawienia wygaszania treści';
$a->strings['Automatically expire posts after this many days:'] = 'Automatycznie wygaś wpisy po następującej liczbie dni:';
$a->strings['If empty, posts will not expire. Expired posts will be deleted'] = 'Zostaw puste, by nie wygaszać wpisów. Wpisy, które wygasły, zostaną usunięte';
$a->strings['Expire posts'] = 'Wygaszanie wpisów';
$a->strings['When activated, posts and comments will be expired.'] = 'Zaznacz, by wygaszać wpisy i komentarze';
$a->strings['Expire personal notes'] = 'Wygaszanie osobistych notatek';
$a->strings['When activated, the personal notes on your profile page will be expired.'] = 'Zaznacz, by wygaszać osobiste notatki na stronie Twojego profilu.';
$a->strings['Expire starred posts'] = 'Wygaszanie wpisów oznaczonych gwiazdką';
$a->strings['Starring posts keeps them from being expired. That behaviour is overwritten by this setting.'] = 'Oznaczanie wpisów gwiazdką domyślnie zapobiega ich wygaszaniu. To ustawienie nadpisuje to zachowanie.';
$a->strings['Only expire posts by others'] = 'Wygaszaj tylko wpisy innych osób';
$a->strings['When activated, your own posts never expire. Then the settings above are only valid for posts you received.'] = 'Zaznacz, by nigdy nie wygaszać Twoich własnych wpisów. Powyższe ustawienia będą wtedy dotyczyć tylko wpisów od innych profili.';
$a->strings['Notification Settings'] = 'Ustawienia powiadomień';
$a->strings['Send a notification email when:'] = 'Wysyłaj powiadmonienia na email, kiedy:';
$a->strings['You receive an introduction'] = 'Otrzymałeś zaproszenie';
$a->strings['Your introductions are confirmed'] = 'Twoje zaproszenia są potwierdzone';
$a->strings['Someone writes on your profile wall'] = 'Ktoś pisze na Twojej tablicy profilu';
$a->strings['Someone writes a followup comment'] = 'Ktoś pisze komentarz nawiązujący.';
$a->strings['You receive a private message'] = 'Otrzymałeś prywatną wiadomość';
$a->strings['You receive a friend suggestion'] = 'Otrzymałeś propozycję od znajomych';
$a->strings['You are tagged in a post'] = 'Zostałeś oznaczony we wpisie';
$a->strings['Create a desktop notification when:'] = 'Utwórz powiadomienia na pulpicie gdy:';
$a->strings['Someone tagged you'] = 'Ktoś Cię oznaczył';
$a->strings['Someone directly commented on your post'] = 'Ktoś bezpośrednio skomentował Twój wpis';
$a->strings['Someone liked your content'] = 'Ktoś polubił Twoje treści';
$a->strings['Can only be enabled, when the direct comment notification is enabled.'] = 'Można włączyć tylko wtedy, gdy włączone jest bezpośrednie powiadomienie o komentarzach.';
$a->strings['Someone shared your content'] = 'Ktoś udostępnił Twoje treści';
$a->strings['Someone commented in your thread'] = 'Ktoś skomentował w Twój wątek';
$a->strings['Someone commented in a thread where you commented'] = 'Ktoś skomentował wątek skomentowany przez Ciebie';
$a->strings['Someone commented in a thread where you interacted'] = 'Ktoś skomentował wątek, z którym masz interakcję';
$a->strings['Activate desktop notifications'] = 'Aktywuj powiadomienia na pulpicie';
$a->strings['Show desktop popup on new notifications'] = 'Pokazuj wyskakujące okienko gdy otrzymasz powiadomienie';
$a->strings['Text-only notification emails'] = 'E-maile z powiadomieniami tekstowymi';
$a->strings['Send text only notification emails, without the html part'] = 'Wysyłaj tylko e-maile z powiadomieniami tekstowymi, bez części html';
$a->strings['Show detailled notifications'] = 'Pokazuj szczegółowe powiadomienia';
$a->strings['Per default, notifications are condensed to a single notification per item. When enabled every notification is displayed.'] = 'Domyślne powiadomienia są skondensowane z jednym powiadomieniem dla każdego przedmiotu. Po włączeniu wyświetlane jest każde powiadomienie.';
$a->strings['Show notifications of ignored contacts'] = 'Pokaż powiadomienia od ignorowanych kontaktów';
$a->strings['You don\'t see posts from ignored contacts. But you still see their comments. This setting controls if you want to still receive regular notifications that are caused by ignored contacts or not.'] = 'Nie widzisz wpisów od ignorowanych kontaktów. Ale nadal widzisz ich komentarze. To ustawienie określa, czy chcesz nadal otrzymywać regularne powiadomienia, które są powodowane przez ignorowane kontakty, czy nie.';
$a->strings['Advanced Account/Page Type Settings'] = 'Typ profilu';
$a->strings['Change the behaviour of this account for special situations'] = 'Zmień zachowanie tego konta w sytuacjach specjalnych';
$a->strings['Relocate'] = 'Relokacja';
$a->strings['If you have moved this profile from another server, and some of your contacts don\'t receive your updates, try pushing this button.'] = 'Jeśli ten profil został przeniesiony z innego serwera, a niektóre z Twoich kontaktów nie otrzymają aktualizacji, spróbuj nacisnąć ten przycisk.';
$a->strings['Resend relocate message to contacts'] = 'Ponownie powiadom kontakty o przeniesieniu konta';
$a->strings['Addon Settings'] = 'Ustawienia dodatków';
$a->strings['This page can be used to define the channels that will automatically be reshared by your account.'] = 'Na tej stronie można zdefiniować kanały, z których treści będą automatycznie przekazywane dalej przez Twoje konto.';
$a->strings['This page can be used to define your own channels.'] = 'Tu możesz zdefiniować swoje własne kanały.';
$a->strings['Publish'] = 'Opublikuj';
$a->strings['When selected, the channel results are reshared. This only works for public ActivityPub posts from the public timeline or the user defined circles.'] = 'Po wybraniu tej opcji, wyniki kanału są przekazywane dalej. Działa to tylko w przypadku publicznych wpisów ActivityPub z publicznej osi czasu lub kręgów zdefiniowanych przez użytkownika.';
$a->strings['Label'] = 'Etykieta';
$a->strings['Description'] = 'Opis';
$a->strings['Access Key'] = 'Klucz dostępu';
$a->strings['Circle/Channel'] = 'Krąg/Kanał';
$a->strings['Include Tags'] = 'Uwzględnij tagi';
$a->strings['Exclude Tags'] = 'Wyklucz tagi';
$a->strings['Minimum Size'] = 'Minimalny rozmiar';
$a->strings['Maximum Size'] = 'Maksymalny rozmiar';
$a->strings['Full Text Search'] = 'Wyszukiwanie pełnotekstowe';
$a->strings['Languages'] = 'Języki';
$a->strings['Select all languages that you want to see in this channel. "Unspecified" describes all posts for which no language information was detected (e.g. posts with just an image or too little text to be sure of the language). If you want to see all languages, you will need to select all items in the list.'] = 'Wybierz wszystkie języki, które chcesz wyświetlać w tym kanale. "Niesprecyzowane" określa wszystkie wpisy, dla których nie wykryto informacji o użytym języku (np. wpisy z samym obrazkiem lub zbyt małą ilością tekstu, by być pewnym użytego języka). Jeśli chcesz wyświetlać wszystkie języki, musisz wybrać wszystkie elementy listy.';
$a->strings['Delete channel'] = 'Usuń kanał';
$a->strings['Check to delete this entry from the channel list'] = 'Zaznacz, by usunąć tę pozycję z listy kanałów';
$a->strings['Comma separated list of tags. If a post contain any of these tags, then it will not be part of this channel.'] = 'Lista tagów oddzielonych przecinkami. Jeśli wpis zawiera któryś z tych tagów, nie zostanie wyświetlony w tym kanale.';
$a->strings['Comma separated list of tags. If a post contain any of these tags, then it will not be part of nthis channel.'] = 'Lista tagów oddzielonych przecinkami. Jeśli wpis zawiera któryś z tych tagów, nie zostanie wyświetlony w tym kanale.';
$a->strings['Short name for the channel. It is displayed on the channels widget.'] = 'Krótka nazwa kanału. Będzie wyświetlona na widżecie kanałów.';
$a->strings['This should describe the content of the channel in a few word.'] = 'Opisz zawartość kanału w kilku słowach.';
$a->strings['When you want to access this channel via an access key, you can define it here. Pay attention to not use an already used one.'] = 'Jeśli chcesz uzyskiwać dostęp do tego kanału za pomocą klucza dostępu, zdefiniuj go tutaj. Uważaj, by nie korzystać z już używanego.';
$a->strings['Select a circle or channel, that your channel should be based on.'] = 'Wybierz Krąg lub Kanał, na którym powinien opierać się Twój nowy Kanał.';
$a->strings['Comma separated list of tags. A post will be used when it contains any of the listed tags.'] = 'Lista tagów oddzielonych przecinkami. Jeśli wpis zawiera któryś z tych tagów, zostanie wyświetlony w tym kanale.';
$a->strings['Minimum post size. Leave empty for no minimum size. The size is calculated without links, attached posts, mentions or hashtags.'] = 'Minimalna wielkość wpisu. Puste pole oznacza brak minimalnego rozmiaru. Do rozmiaru nie wliczają się linki, dołączone wpisy, wzmianki czy tagi.';
$a->strings['Maximum post size. Leave empty for no maximum size. The size is calculated without links, attached posts, mentions or hashtags.'] = 'Minimalna wielkość wpisu. Puste pole oznacza brak minimalnego rozmiaru. Do rozmiaru nie wliczają się linki, dołączone wpisy, wzmianki czy tagi.';
$a->strings['Search terms for the body, supports the "boolean mode" operators from MariaDB. See the help for a complete list of operators and additional keywords: %s'] = 'Terminy wyszukiwania dla treści. Możesz użyć operatorów logicznych "boolean mode" z MariaDB. Sprawdź pomoc, aby poznać pełną listę operatorów i dodatkowych słów kluczowych: %s';
$a->strings['Check to display images in the channel.'] = 'Zaznacz, by wyświetlać obrazki w Kanale.';
$a->strings['Check to display videos in the channel.'] = 'Zaznacz, by wyświetlać wideo w Kanale.';
$a->strings['Check to display audio in the channel.'] = 'Zaznacz, by wyświetlać audio w Kanale.';
$a->strings['Select all languages that you want to see in this channel.'] = 'Wybierz wszystkie języki, jakie chcesz wyświetlać w tym Kanale.';
$a->strings['Add new entry to the channel list'] = 'Dodaj nową pozycję do listy Kanałów';
$a->strings['Add'] = 'Dodaj';
$a->strings['Current Entries in the channel list'] = 'Obecne pozycje na liście Kanału';
$a->strings['Delete entry from the channel list'] = 'Usuń pozycję z listy Kanału';
$a->strings['Delete entry from the channel list?'] = 'Usunąć pozycję z listy Kanału?';
$a->strings['Failed to connect with email account using the settings provided.'] = 'Połączenie z kontem email używając wybranych ustawień nie powiodło się.';
$a->strings['Diaspora (Socialhome, Hubzilla)'] = 'Diaspora (Socialhome, Hubzilla)';
$a->strings['Built-in support for %s connectivity is enabled'] = 'Wbudowane wsparcie dla łączności %s jest włączone';
$a->strings['Built-in support for %s connectivity is disabled'] = 'Wbudowane wsparcie dla łączności %s jest wyłączone';
$a->strings['Email access is disabled on this site.'] = 'Dostęp do e-maila jest wyłączony na tej stronie.';
$a->strings['None'] = 'Brak';
$a->strings['Default (Mastodon will display the title and a link to the post)'] = 'Domyślne (Mastodon wyświetli tytuł i link do wpisu)';
$a->strings['Use the summary (Mastodon and some others will treat it as content warning)'] = 'Użyj podsumowania (Mastodon i inne niektóre serwisy potraktują je jako ostrzeżenie przed zawartością)';
$a->strings['Embed the title in the body'] = 'Umieść tytuł w treści';
$a->strings['General Social Media Settings'] = 'Ogólne ustawienia mediów społecznościowych';
$a->strings['Followed content scope'] = 'Obserwowany zakres treści';
$a->strings['By default, conversations in which your follows participated but didn\'t start will be shown in your timeline. You can turn this behavior off, or expand it to the conversations in which your follows liked a post.'] = 'Domyślnie na Twojej osi czasu będą pokazywane wątki, w których uczestniczyli Twoi obserwowani, ale które nie zostały przez nich rozpoczęte. Możesz wyłączyć tę funkcję lub rozszerzyć ją na wątki, w których Twoi obserwujący polubili dany wpis.';
$a->strings['Only conversations my follows started'] = 'Tylko wątki rozpoczęte przez moich obserwowanych';
$a->strings['Conversations my follows started or commented on (default)'] = 'Wątki, które rozpoczęli lub skomentowali moi obserwowani (domyślnie)';
$a->strings['Any conversation my follows interacted with, including likes'] = 'Wszelkie rozmowy, z którymi wchodziłem w interakcję, w tym polubienia';
$a->strings['Collapse sensitive posts'] = 'Zwijaj wrażliwe wpisy';
$a->strings['If a post is marked as "sensitive", it will be displayed in a collapsed state, if this option is enabled.'] = 'Jeśli wpis jest oznaczony jako "wrażliwy", będzie wyświetlany jako zwinięty, gdy ta opcja jest włączona.';
$a->strings['Enable intelligent shortening'] = 'Włącz inteligentne skracanie';
$a->strings['Normally the system tries to find the best link to add to shortened posts. If disabled, every shortened post will always point to the original friendica post.'] = 'Domyślnie system dobiera najlepszy link do dodania do skróconych wpisów. Po wyłączeniu tej opcji każdy skrócony wpis będzie kierował do oryginalnegu wpisu w Friendica.';
$a->strings['Enable simple text shortening'] = 'Włącz proste skracanie tekstu';
$a->strings['Normally the system shortens posts at the next line feed. If this option is enabled then the system will shorten the text at the maximum character limit.'] = 'Zwykle system skraca wpisy przy następnym wysunięciu wiersza. Jeśli ta opcja jest włączona, system skróci tekst do maksymalnego limitu znaków.';
$a->strings['Attach the link title'] = 'Dołącz tytuł linku';
$a->strings['When activated, the title of the attached link will be added as a title on posts to Diaspora. This is mostly helpful with "remote-self" contacts that share feed content.'] = 'Po aktywacji tytuł dołączonego linku będzie dodawany jako tytuł wpisu wysyłanego do Diaspory. Jest to szczególnie przydatne w przypadku profili „remote-self”, które przekazują dalej treści z kanału.';
$a->strings['API: Use spoiler field as title'] = 'API: Użyj pola "spojler" jako tytułu';
$a->strings['When activated, the "spoiler_text" field in the API will be used for the title on standalone posts. When deactivated it will be used for spoiler text. For comments it will always be used for spoiler text.'] = 'Po aktywacji pole „spoiler_text" w API będzie używane jako tytuł w samodzielnych wpisach. Po dezaktywacji będzie używane jako tekst spoilera. W przypadku komentarzy zawsze będzie używane jako tekst spoilera.';
$a->strings['API: Automatically links at the end of the post as attached posts'] = 'API: Automatycznie konwertuj linki na końcu wpisu na dołączone wpisy ';
$a->strings['When activated, added links at the end of the post react the same way as added links in the web interface.'] = 'Gdy funkcja jest włączona, linki dodane na końcu wpisu zachowują się tak samo jak linki dodane przez interfejs internetowy.';
$a->strings['Article Mode'] = 'Tryb artykułu';
$a->strings['Controls how posts with titles are transmitted. Mastodon and its forks don\'t display the content of these posts if the post is created in the correct (default) way.'] = 'Kontroluje sposób w jaki wpisy z tytułami są transmitowane. Mastodon i jego forki nie wyświetlą treści tych wpisów, jeśli wpis został utworzony w prawidłowy (domyślny) sposób.';
$a->strings['Email/Mailbox Setup'] = 'Ustawienia  emaila/skrzynki mailowej';
$a->strings['If you wish to communicate with email contacts using this service (optional), please specify how to connect to your mailbox.'] = 'Jeśli chcesz komunikować się z kontaktami e-mail za pomocą tej usługi (opcjonalnie), określ sposób łączenia się ze skrzynką pocztową.';
$a->strings['Last successful email check:'] = 'Ostatni sprawdzony e-mail:';
$a->strings['IMAP server name:'] = 'Nazwa serwera IMAP:';
$a->strings['IMAP port:'] = 'Port IMAP:';
$a->strings['Security:'] = 'Bezpieczeństwo:';
$a->strings['Email login name:'] = 'Nazwa logowania e-mail:';
$a->strings['Email password:'] = 'Hasło e-mail:';
$a->strings['Reply-to address:'] = 'Adres zwrotny:';
$a->strings['Send public posts to all email contacts:'] = 'Wyślij publiczny wpis do wszystkich kontaktów e-mail:';
$a->strings['Action after import:'] = 'Akcja po zaimportowaniu:';
$a->strings['Move to folder'] = 'Przenieś do katalogu';
$a->strings['Move to folder:'] = 'Przenieś do katalogu:';
$a->strings['Contact CSV file upload error'] = 'Kontakt z plikiem CSV błąd przekazywania plików';
$a->strings['Importing Contacts done'] = 'Importowanie kontaktów zakończone';
$a->strings['Upload a CSV file that contains the handle of your followed accounts in the first column you exported from the old account.'] = 'Prześlij plik CSV zawierający obsługę obserwowanych kont w pierwszej kolumnie wyeksportowanej ze starego konta.';
$a->strings['Upload File'] = 'Prześlij plik';
$a->strings['Your legacy ActivityPub/GNU Social account'] = 'Twoje stare konto ActivityPub/GNU Social';
$a->strings['If you enter your old account name from an ActivityPub based system or your GNU Social/Statusnet account name here (in the format user@domain.tld), your contacts will be added automatically. The field will be emptied when done.'] = 'Jeśli wprowadzisz tutaj swoją starą nazwę konta z systemu opartego na ActivityPub lub nazwę konta GNU Social/Statusnet (w formacie użytkownik@domena.tld), Twoje kontakty zostaną dodane automatycznie. Po zakończeniu pole zostanie opróżnione.';
$a->strings['Delegation successfully granted.'] = 'Pełnomocnictwo zostało pomyślnie nadane.';
$a->strings['Parent user not found, unavailable or password doesn\'t match.'] = 'Nie znaleziono użytkownika nadrzędnego, jest on niedostępny lub hasło nie pasuje.';
$a->strings['Delegation successfully revoked.'] = 'Pełnomocnictwo zostało pomyślnie odwołane.';
$a->strings['Delegated administrators can view but not change delegation permissions.'] = 'Pełnomocnicy mogą wyświetlić, ale nie mogą zmienić pełnomocnictwa';
$a->strings['Delegate user not found.'] = 'Nie znaleziono użytkownika, który nadał pełnomocnictwo';
$a->strings['No parent user'] = 'Brak nadrzędnego użytkownika';
$a->strings['Parent User'] = 'Użytkownik nadrzędny';
$a->strings['Additional Accounts'] = 'Dodatkowe konta';
$a->strings['Register additional accounts that are automatically connected to your existing account so you can manage them from this account.'] = 'Zarejestruj dodatkowe konta, które są automatycznie połączone z istniejącym kontem, aby móc nimi zarządzać z tego konta.';
$a->strings['Register an additional account'] = 'Zarejestruj dodatkowe konto';
$a->strings['Parent users have total control about this account, including the account settings. Please double check whom you give this access.'] = 'Użytkownicy nadrzędni mają pełną kontrolę nad tym kontem, w tym także ustawienia konta. Sprawdź dokładnie, komu przyznasz ten dostęp.';
$a->strings['Delegates'] = 'Pełnomocnicy';
$a->strings['Delegates are able to manage all aspects of this account/page except for basic account settings. Please do not delegate your personal account to anybody that you do not trust completely.'] = 'Pełnomocnicy mogą zarządzać wszystkimi aspektami tego profilu, z wyjątkiem podstawowych ustawień konta. Nie udzielaj pełnomocnictwa do swojego prywatnego profilu niezaufanym osobom.';
$a->strings['Existing Page Delegates'] = 'Aktualni pełnomocnicy';
$a->strings['Potential Delegates'] = 'Potencjalni pełnomocnicy';
$a->strings['No entries.'] = 'Brak wpisów.';
$a->strings['The theme you chose isn\'t available.'] = 'Wybrany motyw jest niedostępny.';
$a->strings['%s - (Unsupported)'] = '%s - (Nieobsługiwane)';
$a->strings['Color/Black'] = 'Kolor/Czarny';
$a->strings['Black'] = 'Czarny';
$a->strings['Color/White'] = 'Kolor/Biały';
$a->strings['White'] = 'Biały';
$a->strings['No preview'] = 'Brak podglądu';
$a->strings['No image'] = 'Brak obrazka';
$a->strings['Small Image'] = 'Mały obrazek';
$a->strings['Large Image'] = 'Duży obrazek';
$a->strings['Display Settings'] = 'Ustawienia wyglądu';
$a->strings['Content Settings'] = 'Ustawienia zawartości';
$a->strings['Theme settings'] = 'Ustawienia motywu';
$a->strings['Timelines'] = 'Osie czasu';
$a->strings['Number of items to display per page:'] = 'Liczba elementów do wyświetlenia na stronie:';
$a->strings['Maximum of 100 items'] = 'Maksymalnie 100 elementów';
$a->strings['Number of items to display per page when viewed from mobile device:'] = 'Liczba elementów do wyświetlenia na stronie podczas przeglądania z urządzenia mobilnego:';
$a->strings['Regularly update the page content'] = 'Regularnie aktualizuj treść strony';
$a->strings['When enabled, new content on network, community and channels are added on top.'] = 'Po aktywacji, nowe treści w Sieci, Społecznościach i Kanałach są dodawane na górze.';
$a->strings['Display emoticons'] = 'Wyświetl emotikony';
$a->strings['When enabled, emoticons are replaced with matching symbols.'] = 'Po włączeniu emotikony są zastępowane pasującymi symbolami.';
$a->strings['Infinite scroll'] = 'Nieskończone przewijanie';
$a->strings['Automatic fetch new items when reaching the page end.'] = 'Automatyczne pobieranie nowych elementów po osiągnięciu końca strony.';
$a->strings['Enable Smart Threading'] = 'Włącz inteligentne wątkowanie';
$a->strings['Enable the automatic suppression of extraneous thread indentation.'] = 'Włącz automatyczne tłumienie obcych wcięć wątku.';
$a->strings['Display the Dislike feature'] = 'Wyświetl funkcję "Nie lubię"';
$a->strings['Display the Dislike button and dislike reactions on posts and comments.'] = 'Wyświetlaj przycisk "Nie lubię" i reakcje na wpisy i komentarze.';
$a->strings['Display the resharer'] = 'Wyświetl osobę przekazującą dalej';
$a->strings['Display the first resharer as icon and text on a reshared item.'] = 'Wyświetlaj pierwszą osobę przekazującą dalej jako ikonę i tekst na przekazanym dalej elemencie.';
$a->strings['Stay local'] = 'Pozostań lokalny';
$a->strings['Don\'t go to a remote system when following a contact link.'] = 'Nie przechodź do innej instancji po kliknięciu w odnośnik do kontaktu.';
$a->strings['Show the post deletion checkbox'] = 'Pokaż pole wyboru usuwania wpisu';
$a->strings['Display the checkbox for the post deletion on the network page.'] = 'Wyświetlaj pole wyboru do usuwania wpisu na stronie Sieci.';
$a->strings['DIsplay the event list'] = 'Wyświetlaj listę wydarzeń';
$a->strings['Display the birthday reminder and event list on the network page.'] = 'Wyświetlaj przypomnienia o urodzinach i listę wydarzeń na stronie Sieci.';
$a->strings['Link preview mode'] = 'Tryb podglądu linków';
$a->strings['Appearance of the link preview that is added to each post with a link.'] = 'Dodanie podglądu linku do każdego wpisu zawierającego link.';
$a->strings['Hide pictures with empty alternative text'] = 'Ukryj obrazki bez tekstu alternatywnego';
$a->strings['Don\'t display pictures that are missing the alternative text.'] = 'Nie wyświetlaj zdjęć, które nie mają podanego tekstu alternatywnego.';
$a->strings['Hide custom emojis'] = 'Ukrywaj nietypowe emotikony';
$a->strings['Don\'t display custom emojis.'] = 'Nie wyświetlaj nietypowych emotikon.';
$a->strings['Platform icons style'] = 'Styl ikon platformy';
$a->strings['Style of the platform icons'] = 'Styl ikon platformy';
$a->strings['Enable timelines that you want to see in the channels widget. Bookmark timelines that you want to see in the top menu.'] = 'Włącz osie czasu, które chcesz oglądać na widżecie Kanałów. Dodaj do zakładek osie czasu, które mają być widoczne w górnym menu.';
$a->strings['Channel languages:'] = 'Języki kanałów:';
$a->strings['Select all the languages you want to see in your channels. "Unspecified" describes all posts for which no language information was detected (e.g. posts with just an image or too little text to be sure of the language). If you want to see all languages, you will need to select all items in the list.'] = 'Wybierz wszystkie języki, które chcesz wyświetlać w Twoich kanałach. "Niesprecyzowane" określa wszystkie wpisy, dla których nie wykryto informacji o użytym języku (np. wpisy z samym obrazkiem lub zbyt małą ilością tekstu, by być pewnym użytego języka). Jeśli chcesz wyświetlać wszystkie języki, musisz wybrać wszystkie elementy listy.';
$a->strings['Beginning of week:'] = 'Początek tygodnia:';
$a->strings['Default calendar view:'] = 'Domyślny widok kalendarza:';
$a->strings['Additional Features'] = 'Dodatkowe funkcje';
$a->strings['Connected Apps'] = 'Powiązane aplikacje';
$a->strings['Remove authorization'] = 'Odwołaj upoważnienie';
$a->strings['Display Name is required.'] = 'Nazwa wyświetlana jest wymagana.';
$a->strings['Profile couldn\'t be updated.'] = 'Profil nie mógł zostać zaktualizowany.';
$a->strings['Label:'] = 'Etykieta:';
$a->strings['Value:'] = 'Wartość:';
$a->strings['Field Permissions'] = 'Uprawnienia pola';
$a->strings['(click to open/close)'] = '(kliknij by otworzyć/zamknąć)';
$a->strings['Add a new profile field'] = 'Dodaj nowe pole profilu';
$a->strings['The homepage is verified. A rel="me" link back to your Friendica profile page was found on the homepage.'] = 'Strona główna została zweryfikowana. Na stronie głównej znaleziono link rel="me" prowadzący z powrotem do Twojego profilu Friendica.';
$a->strings['To verify your homepage, add a rel="me" link to it, pointing to your profile URL (%s).'] = 'Aby zweryfikować swoją stronę główną, dodaj do niej link rel="me", wskazujący na URL Twojego profilu (%s).';
$a->strings['Profile Actions'] = 'Akcje profilowe';
$a->strings['Edit Profile Details'] = 'Edytuj informacje o profilu';
$a->strings['Profile picture'] = 'Zdjęcie profilowe';
$a->strings['Location'] = 'Lokalizacja';
$a->strings['Miscellaneous'] = 'Pozostałe';
$a->strings['Custom Profile Fields'] = 'Niestandardowe pola profilu';
$a->strings['<p>Custom fields appear on <a href="%s">your profile page</a>.</p>
				<p>You can use BBCodes in the field values.</p>
				<p>Reorder by dragging the field title.</p>
				<p>Empty the label field to remove a custom field.</p>
				<p>Non-public fields can only be seen by the selected Friendica contacts or the Friendica contacts in the selected circles.</p>'] = '<p>Pola niestandardowe są widoczne na <a href="%s">stronie Twojego profilu</a>.</p>
				<p>Możesz użyć BBCode w treści pola.</p>
				<p>Zmień kolejność przeciągając tytuł pola.</p>
				<p>Opróżnij pole etykiety, by usunąć pole niestandardowe.</p>
				<p>Niepubliczne pola będą widoczne tylko dla wybranych kontaktów Friendica lub kontaktów Friendica w wybranych Kręgach.</p>';
$a->strings['Street Address:'] = 'Ulica:';
$a->strings['Locality/City:'] = 'Miasto:';
$a->strings['Region/State:'] = 'Województwo/Stan:';
$a->strings['Postal/Zip Code:'] = 'Kod pocztowy:';
$a->strings['Country:'] = 'Kraj:';
$a->strings['XMPP (Jabber) address:'] = 'Adres XMPP (Jabber):';
$a->strings['The XMPP address will be published so that people can follow you there.'] = 'Adres XMPP zostanie opublikowany, aby ludzie mogli Cię tam śledzić.';
$a->strings['Matrix (Element) address:'] = 'Adres Matrix (Element):';
$a->strings['The Matrix address will be published so that people can follow you there.'] = 'Adres Matrix zostanie opublikowany, aby ludzie mogli Cię tam śledzić.';
$a->strings['Homepage URL:'] = 'Adres URL strony domowej:';
$a->strings['Public Keywords:'] = 'Publiczne słowa kluczowe:';
$a->strings['(Used for suggesting potential friends, can be seen by others)'] = '(Używany do sugerowania potencjalnych znajomych, jest widoczny dla innych)';
$a->strings['Private Keywords:'] = 'Prywatne słowa kluczowe:';
$a->strings['(Used for searching profiles, never shown to others)'] = '(Używany do wyszukiwania profili, niepokazywany innym)';
$a->strings['Image size reduction [%s] failed.'] = 'Redukcja rozmiaru obrazka [%s] nie powiodła się.';
$a->strings['Shift-reload the page or clear browser cache if the new photo does not display immediately.'] = 'Ponownie załaduj stronę lub wyczyść pamięć podręczną przeglądarki, jeśli nowe zdjęcie nie pojawi się natychmiast.';
$a->strings['Unable to process image'] = 'Nie udało się przetworzyć obrazu';
$a->strings['Photo not found.'] = 'Nie znaleziono zdjęcia.';
$a->strings['Profile picture successfully updated.'] = 'Zdjęcie profilowe zostało pomyślnie zaktualizowane.';
$a->strings['Crop Image'] = 'Przytnij zdjęcie';
$a->strings['Please adjust the image cropping for optimum viewing.'] = 'Dostosuj kadrowanie obrazu, aby uzyskać optymalny obraz.';
$a->strings['Use Image As Is'] = 'Użyj obrazu takim, jaki jest';
$a->strings['Missing uploaded image.'] = ' Brak przesłanego obrazu.';
$a->strings['Profile Picture Settings'] = 'Ustawienia zdjęcia profilowego';
$a->strings['Current Profile Picture'] = 'Bieżące zdjęcie profilowe';
$a->strings['Upload Profile Picture'] = 'Prześlij zdjęcie profilowe';
$a->strings['Upload Picture:'] = 'Załaduj zdjęcie:';
$a->strings['or'] = 'lub';
$a->strings['skip this step'] = 'pomiń ten krok';
$a->strings['select a photo from your photo albums'] = 'wybierz zdjęcie z twojego albumu';
$a->strings['There was a validation error, please make sure you\'re logged in with the account you want to remove and try again.'] = 'Problem z walidacją. Upewnij się, że jesteś zalogowany do konta, które chcesz usunąć i spróbuj ponownie.';
$a->strings['If this error persists, please contact your administrator.'] = 'Jeśli ten błąd będzie się powtarzał, skontaktuj się z administratorem.';
$a->strings['[Friendica System Notify]'] = '[Powiadomienie Systemu Friendica]';
$a->strings['User deleted their account'] = 'Użytkownik usunął swoje konto';
$a->strings['On your Friendica node an user deleted their account. Please ensure that their data is removed from the backups.'] = 'Użytkownik usunął konto z Twojej instancji Friendica. Upewnij się, że jego dane zostały usunięte z kopii zapasowych.';
$a->strings['The user id is %d'] = 'Identyfikatorem użytkownika jest %d';
$a->strings['Your account has been successfully removed. Bye bye!'] = 'Twoje konto zostało pomyślnie usunięte. Do zobaczenia!';
$a->strings['Remove My Account'] = 'Usuń moje konto';
$a->strings['This will completely remove your account. Once this has been done it is not recoverable.'] = 'Spowoduje to całkowite usunięcie Twojego konta. Po wykonaniu tej czynności nie można jej cofnąć.';
$a->strings['Please enter your password for verification:'] = 'Wprowadź hasło w celu weryfikacji:';
$a->strings['Do you want to ignore this server?'] = 'Czy chcesz ignorować ten serwer?';
$a->strings['Do you want to unignore this server?'] = 'Czy chcesz przestać ignorować ten serwer?';
$a->strings['Remote server settings'] = 'Ustawienia innej instancji';
$a->strings['Server URL'] = 'URL serwera';
$a->strings['Settings saved'] = 'Zapisano ustawienia';
$a->strings['Here you can find all the remote servers you have taken individual moderation actions against. For a list of servers your node has blocked, please check out the <a href="friendica">Information</a> page.'] = 'W tym miejscu znajdziesz wszystkie inne instancje, wobec których podjąłeś indywidualne działania moderacyjne. Listę instancji zablokowanych przez Twoją instancję znajdziesz na stronie <a href="friendica">Informacje</a>.';
$a->strings['Delete all your settings for the remote server'] = 'Usuń wszystkie Twoje ustawienia dla innych instancji';
$a->strings['Please enter your password to access this page.'] = 'Wprowadź hasło, aby uzyskać dostęp do tej strony.';
$a->strings['App-specific password generation failed: The description is empty.'] = 'Generowanie hasła aplikacji nie powiodło się: Opis jest pusty.';
$a->strings['App-specific password generation failed: This description already exists.'] = 'Generowanie hasła aplikacji nie powiodło się: Opis ten już istnieje.';
$a->strings['New app-specific password generated.'] = 'Nowe hasło aplikacji zostało utworzone.';
$a->strings['App-specific passwords successfully revoked.'] = 'Hasła specyficzne dla aplikacji zostały pomyślnie cofnięte.';
$a->strings['App-specific password successfully revoked.'] = 'Hasło specyficzne dla aplikacji zostało pomyślnie odwołane.';
$a->strings['Two-factor app-specific passwords'] = 'Dwuskładnikowe hasła aplikacji';
$a->strings['<p>App-specific passwords are randomly generated passwords used instead your regular password to authenticate your account on third-party applications that don\'t support two-factor authentication.</p>'] = '<p>Hasła aplikacji to losowo generowane hasła używane zamiast zwykłego hasła do uwierzytelniania konta w aplikacjach innych firm, które nie obsługują uwierzytelniania dwuskładnikowego.</p>';
$a->strings['Make sure to copy your new app-specific password now. You won’t be able to see it again!'] = 'Pamiętaj, aby teraz skopiować nowe hasło aplikacji. Nie będziesz mógł go zobaczyć ponownie!';
$a->strings['Last Used'] = 'Ostatnio używane';
$a->strings['Revoke'] = 'Unieważnij';
$a->strings['Revoke All'] = 'Unieważnij wszyskie';
$a->strings['When you generate a new app-specific password, you must use it right away, it will be shown to you once after you generate it.'] = 'Gdy generujesz nowe hasło aplikacji, musisz go od razu użyć. Zostanie ono wyświetlone raz po wygenerowaniu.';
$a->strings['Generate new app-specific password'] = 'Wygeneruj nowe hasło specyficzne dla aplikacji';
$a->strings['Friendiqa on my Fairphone 2...'] = 'Friendiqa na moim Fairphone 2...';
$a->strings['Generate'] = 'Utwórz';
$a->strings['Two-factor authentication successfully disabled.'] = 'Autoryzacja dwuskładnikowa została pomyślnie wyłączona.';
$a->strings['<p>Use an application on a mobile device to get two-factor authentication codes when prompted on login.</p>'] = '<p>Użyj aplikacji na urządzeniu mobilnym, aby uzyskać dwuskładnikowe kody uwierzytelniające po wyświetleniu monitu o zalogowanie.</p>';
$a->strings['Authenticator app'] = 'Aplikacja Authenticator';
$a->strings['Configured'] = 'Skonfigurowane';
$a->strings['Not Configured'] = 'Nie skonfigurowane';
$a->strings['<p>You haven\'t finished configuring your authenticator app.</p>'] = '<p>Nie zakończyłeś konfigurowania aplikacji uwierzytelniającej.</p>';
$a->strings['<p>Your authenticator app is correctly configured.</p>'] = '<p>Twoja aplikacja uwierzytelniająca jest poprawnie skonfigurowana.</p>';
$a->strings['Recovery codes'] = 'Kody odzyskiwania';
$a->strings['Remaining valid codes'] = 'Pozostałe ważne kody';
$a->strings['<p>These one-use codes can replace an authenticator app code in case you have lost access to it.</p>'] = '<p>Te jednorazowe kody mogą zastąpić kod aplikacji uwierzytelniającej w przypadku utraty dostępu do niej.</p>';
$a->strings['App-specific passwords'] = 'Hasła specyficzne dla aplikacji';
$a->strings['Generated app-specific passwords'] = 'Wygenerowane hasła specyficzne dla aplikacji';
$a->strings['<p>These randomly generated passwords allow you to authenticate on apps not supporting two-factor authentication.</p>'] = '<p>Losowo generowane hasła umożliwiają uwierzytelnianie w aplikacjach nie obsługujących uwierzytelniania dwuskładnikowego.</p>';
$a->strings['Current password:'] = 'Aktualne hasło:';
$a->strings['You need to provide your current password to change two-factor authentication settings.'] = 'Musisz podać swoje aktualne hasło, aby zmienić ustawienia uwierzytelniania dwuskładnikowego.';
$a->strings['Enable two-factor authentication'] = 'Włącz uwierzytelnianie dwuskładnikowe';
$a->strings['Disable two-factor authentication'] = 'Wyłącz uwierzytelnianie dwuskładnikowe';
$a->strings['Show recovery codes'] = 'Pokaż kody odzyskiwania';
$a->strings['Manage app-specific passwords'] = 'Zarządzaj hasłami specyficznymi dla aplikacji';
$a->strings['Manage trusted browsers'] = 'Zarządzaj zaufanymi przeglądarkami';
$a->strings['Finish app configuration'] = 'Zakończ konfigurację aplikacji';
$a->strings['New recovery codes successfully generated.'] = 'Wygenerowano nowe kody odzyskiwania.';
$a->strings['Two-factor recovery codes'] = 'Dwuskładnikowe kody odzyskiwania';
$a->strings['<p>Recovery codes can be used to access your account in the event you lose access to your device and cannot receive two-factor authentication codes.</p><p><strong>Put these in a safe spot!</strong> If you lose your device and don’t have the recovery codes you will lose access to your account.</p>'] = '<p>Kody odzyskiwania mogą służyć do uzyskiwania dostępu do konta w przypadku utraty dostępu do urządzenia i braku możliwości otrzymania kodów uwierzytelniania dwuskładnikowego.</p><p><strong> Umieść je w bezpiecznym miejscu!</strong> Jeśli zgubisz urządzenie i nie będziesz mieć kodów odzyskiwania, utracisz dostęp do swojego konta.</p>';
$a->strings['When you generate new recovery codes, you must copy the new codes. Your old codes won’t work anymore.'] = 'Kiedy generujesz nowe kody odzyskiwania, musisz skopiować nowe kody. Twoje stare kody nie będą już działać.';
$a->strings['Generate new recovery codes'] = 'Wygeneruj nowe kody odzyskiwania';
$a->strings['Next: Verification'] = 'Następny: Weryfikacja';
$a->strings['Trusted browsers successfully removed.'] = 'Zaufane przeglądarki zostały pomyślnie usunięte.';
$a->strings['Trusted browser successfully removed.'] = 'Zaufana przeglądarka została pomyślnie usunięta.';
$a->strings['Two-factor Trusted Browsers'] = 'Zaufane przeglądarki dwuskładnikowe';
$a->strings['Trusted browsers are individual browsers you chose to skip two-factor authentication to access Friendica. Please use this feature sparingly, as it can negate the benefit of two-factor authentication.'] = 'Zaufane przeglądarki to indywidualne przeglądarki, które zostały wybrane, aby pominąć uwierzytelnianie dwuskładnikowe celem uzyskania dostępu do Friendica. Korzystaj z tej funkcji oszczędnie, ponieważ może ona negować korzyści płynące z uwierzytelniania dwuskładnikowego.';
$a->strings['Device'] = 'Urządzenie';
$a->strings['OS'] = 'System operacyjny';
$a->strings['Browser'] = 'Biblioteka';
$a->strings['Trusted'] = 'Zaufane';
$a->strings['Created At'] = 'Utworzono';
$a->strings['Last Use'] = 'Ostatnie użycie';
$a->strings['Remove All'] = 'Usuń wszystkie';
$a->strings['Two-factor authentication successfully activated.'] = 'Uwierzytelnienie dwuskładnikowe zostało pomyślnie aktywowane.';
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
</dl>'] = '<p>Możesz przesłać ustawienia uwierzytelniania ręcznie:</p>
<dl>
	<dt>Wystawc</dt>
	<dd>%s</dd>
	<dt>Nazwa konta</dt>
	<dd>%s</dd>
	<dt>Sekretny klucz</dt>
	<dd>%s</dd>
	<dt>Typ</dt>
	<dd>Oparte na czasie</dd>
	<dt>Liczba cyfr</dt>
	<dd>6</dd>
	<dt>Hashing algorytmu</dt>
	<dd>SHA-1</dd>
</dl>';
$a->strings['Two-factor code verification'] = 'Weryfikacja kodu dwuskładnikowego';
$a->strings['<p>Please scan this QR Code with your authenticator app and submit the provided code.</p>'] = '<p>Zeskanuj kod QR za pomocą aplikacji uwierzytelniającej i prześlij podany kod.</p>';
$a->strings['<p>Or you can open the following URL in your mobile device:</p><p><a href="%s">%s</a></p>'] = '<p>Możesz też otworzyć następujący adres URL w urządzeniu mobilnym:</p><p><a href="%s">%s</a></p>';
$a->strings['Verify code and enable two-factor authentication'] = 'Sprawdź kod i włącz uwierzytelnianie dwuskładnikowe';
$a->strings['Export account'] = 'Eksportuj konto';
$a->strings['Export your account info and contacts. Use this to make a backup of your account and/or to move it to another server.'] = 'Eksportuj informacje o swoim koncie i kontaktach. Użyj tego do utworzenia kopii zapasowej konta i/lub przeniesienia go na inny serwer.';
$a->strings['Export all'] = 'Eksportuj wszystko';
$a->strings['Export your account info, contacts and all your items as json. Could be a very big file, and could take a lot of time. Use this to make a full backup of your account (photos are not exported)'] = 'Wyeksportuj informacje o swoim koncie, kontakty i wszystkie swoje elementy jako json. Może to być bardzo duży plik i może zająć dużo czasu. Użyj tego, aby wykonać pełną kopię zapasową swojego konta (zdjęcia nie są eksportowane).';
$a->strings['Export Contacts to CSV'] = 'Eksportuj kontakty do CSV';
$a->strings['Export the list of the accounts you are following as CSV file. Compatible to e.g. Mastodon.'] = 'Wyeksportuj listę kont, które obserwujesz, jako plik CSV. Kompatybilny np. z Mastodon.';
$a->strings['The top-level post isn\'t visible.'] = 'Wpis najwyższego poziomu nie jest widoczny.';
$a->strings['The top-level post was deleted.'] = 'Wpis najwyższego poziomu został usunięty.';
$a->strings['This node has blocked the top-level author or the author of the shared post.'] = 'Ta instancja zablokowała autora oryginalnego wpisu lub autora udostępnionego wpisu.';
$a->strings['You have ignored or blocked the top-level author or the author of the shared post.'] = 'Ignorujesz lub zablokowałeś oryginalnego autora wpisu lub autora wpisu przekazanego dalej.';
$a->strings['You have ignored the top-level author\'s server or the shared post author\'s server.'] = 'Ignorujesz instancję oryginalnego autora wpisu lub instancję autora wpisu przekazanego dalej.';
$a->strings['Conversation Not Found'] = 'Nie znaleziono wątku';
$a->strings['Unfortunately, the requested conversation isn\'t available to you.'] = 'Niestety, żądany Wątek nie jest dla Ciebie dostępny.';
$a->strings['Possible reasons include:'] = 'Możliwe przyczyny:';
$a->strings['Go back'] = 'Wróć';
$a->strings['Stack trace:'] = 'Ślad stosu:';
$a->strings['Exception thrown in %s:%d'] = 'Zgłoszono wyjątek %s:%d';
$a->strings['At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name (pen name), an username (nickname) and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.'] = 'W momencie rejestracji oraz w celu zapewnienia komunikacji między kontem użytkownika, a jego kontaktami, użytkownik musi podać nazwę wyświetlaną (pseudonim), nazwę użytkownika (alias) i działający adres e-mail. Nazwy będą dostępne na stronie profilu konta dla każdego odwiedzającego stronę, nawet jeśli inne szczegóły profilu nie zostaną wyświetlone. Adres e-mail będzie używany tylko do wysyłania powiadomień użytkownika o interakcjach, nie będzie wyświetlany w profilu. Umieszczenie konta w katalogu użytkowników instancji lub globalnym katalogu jest opcjonalne i może być kontrolowane w ustawieniach użytkownika, nie jest to konieczne do komunikacji.';
$a->strings['This data is required for communication and is passed on to the nodes of the communication partners and is stored there. Users can enter additional private data that may be transmitted to the communication partners accounts.'] = 'Te dane są wymagane do komunikacji i są przekazywane do instancji partnerów komunikacyjnych i są tam przechowywane. Użytkownicy mogą wprowadzać dodatkowe prywatne dane, które mogą być przesyłane do kont partnerów komunikacyjnych.';
$a->strings['At any point in time a logged in user can export their account data from the <a href="%1$s/settings/userexport">account settings</a>. If the user wants to delete their account they can do so at <a href="%1$s/settings/removeme">%1$s/settings/removeme</a>. The deletion of the account will be permanent. Deletion of the data will also be requested from the nodes of the communication partners.'] = 'W każdym momencie zalogowany użytkownik może wyeksportować dane swojego konta w <a href="%1$s/settings/userexport">ustawieniach konta</a>. Jeśli użytkownik będzie chciał usunąć swoje konto, może to zrobić na stronie: <a href="%1$s/settings/removeme">%1$s/settings/removeme</a>. Usunięcie konta jest trwałe i ostateczne. Usunięcie danych będzie również zgłoszone przez instancje partnerów komunikacyjnych.';
$a->strings['Privacy Statement'] = 'Oświadczenie o prywatności';
$a->strings['Rules'] = 'Zasady';
$a->strings['Parameter uri_id is missing.'] = 'Brak parametru url_id.';
$a->strings['The requested item doesn\'t exist or has been deleted.'] = 'Żądany obiekt nie istnieje lub został usunięty.';
$a->strings['You are now logged in as %s'] = 'Jesteś teraz zalogowany jako %s';
$a->strings['Switch between your accounts'] = 'Przełącz się pomiędzy kontami';
$a->strings['Manage your accounts'] = 'Zarządzaj swoimi kontami';
$a->strings['Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions'] = 'Przełącz się na inny profil lub stronę grupy/społeczności utworzonej na Twoim koncie lub do której masz prawa administracyjne';
$a->strings['Select an identity to manage: '] = 'Wybierz profil: ';
$a->strings['User imports on closed servers can only be done by an administrator.'] = 'Import użytkowników na zamkniętych serwerach może być wykonywany tylko przez administratora.';
$a->strings['Move account'] = 'Przenieś konto';
$a->strings['You can import an account from another Friendica server.'] = 'Możesz zaimportować konto z innego serwera Friendica.';
$a->strings['You need to export your account from the old server and upload it here. We will recreate your old account here with all your contacts. We will try also to inform your friends that you moved here.'] = 'Musisz wyeksportować konto ze starego serwera i przesłać je tutaj. Odtworzymy twoje stare konto tutaj ze wszystkimi Twoimi kontaktami. Postaramy się również poinformować Twoich znajomych, że się tutaj przeniosłeś.';
$a->strings['This feature is experimental. We can\'t import contacts from the OStatus network (GNU Social/Statusnet) or from Diaspora'] = 'Ta funkcja jest eksperymentalna. Nie możemy importować kontaktów z sieci OStatus (GNU Social/Statusnet) lub z Diaspory';
$a->strings['Account file'] = 'Pliki konta';
$a->strings['To export your account, go to "Settings->Export your personal data" and select "Export account"'] = 'Aby eksportować konto, wejdź w "Ustawienia->Eksport danych osobistych" i wybierz "Eksportuj konto"';
$a->strings['Error decoding account file'] = 'Błąd podczas odczytu pliku konta';
$a->strings['Error! No version data in file! This is not a Friendica account file?'] = 'Błąd! Brak danych wersji w pliku! To nie jest plik konta Friendica?';
$a->strings['User \'%s\' already exists on this server!'] = 'Użytkownik \'%s\' już istnieje na tym serwerze!';
$a->strings['User creation error'] = 'Błąd tworzenia użytkownika';
$a->strings['%d contact not imported'] = [
	0 => 'Nie zaimportowano %d kontaktu',
	1 => 'Nie zaimportowano %d kontaktów',
	2 => 'Nie zaimportowano %d kontaktów',
	3 => '%d kontakty nie zostały zaimportowane ',
];
$a->strings['User profile creation error'] = 'Błąd tworzenia profilu użytkownika';
$a->strings['Done. You can now login with your username and password'] = 'Gotowe. Możesz teraz zalogować się z użyciem nazwy użytkownika i hasła';
$a->strings['Welcome to Friendica'] = 'Witamy na Friendica';
$a->strings['New Member Checklist'] = 'Lista nowych członków';
$a->strings['We would like to offer some tips and links to help make your experience enjoyable. Click any item to visit the relevant page. A link to this page will be visible from your home page for two weeks after your initial registration and then will quietly disappear.'] = 'Chcielibyśmy zaoferować kilka wskazówek i linków, które pomogą Ci w przyjemnym korzystaniu z serwisu. Kliknij dowolny obiekt, aby przejść do odpowiedniej strony. Link do tej strony będzie widoczny na Twojej stronie głównej przez dwa tygodnie od momentu rejestracji, po czym zniknie po cichu.';
$a->strings['Getting Started'] = 'Pierwsze kroki';
$a->strings['Friendica Walk-Through'] = 'Friendica Przejdź-Przez';
$a->strings['Go to Your Settings'] = 'Idź do swoich ustawień';
$a->strings['On your <em>Settings</em> page -  change your initial password. Also make a note of your Identity Address. This looks just like an email address - and will be useful in making friends on the free social web.'] = 'Na stronie Ustawienia - zmień swoje początkowe hasło. Zanotuj także swój adres tożsamości. Wygląda to jak adres e-mail - będzie przydatny w nawiązywaniu znajomości w bezpłatnej sieci społecznościowej.';
$a->strings['Review the other settings, particularly the privacy settings. An unpublished directory listing is like having an unlisted phone number. In general, you should probably publish your listing - unless all of your friends and potential friends know exactly how to find you.'] = 'Przejrzyj pozostałe ustawienia, w szczególności ustawienia prywatności. Niepublikowany wykaz katalogów jest podobny do niepublicznego numeru telefonu. Ogólnie rzecz biorąc, powinieneś opublikować swój wpis - chyba, że wszyscy twoi znajomi i potencjalni znajomi dokładnie wiedzą, jak Cię znaleźć.';
$a->strings['Upload Profile Photo'] = 'Wyślij zdjęcie profilowe';
$a->strings['Upload a profile photo if you have not done so already. Studies have shown that people with real photos of themselves are ten times more likely to make friends than people who do not.'] = 'Dodaj swoje zdjęcie profilowe jeśli jeszcze tego nie zrobiłeś. Twoje szanse na zwiększenie liczby znajomych rosną dziesięciokrotnie, kiedy na tym zdjęciu jesteś ty.';
$a->strings['Edit Your Profile'] = 'Edytuj własny profil';
$a->strings['Edit your <strong>default</strong> profile to your liking. Review the settings for hiding your list of friends and hiding the profile from unknown visitors.'] = 'Edytuj swój domyślny profil do swoich potrzeb. Przejrzyj ustawienia ukrywania listy znajomych i ukrywania profilu przed nieznanymi użytkownikami.';
$a->strings['Profile Keywords'] = 'Słowa kluczowe profilu';
$a->strings['Set some public keywords for your profile which describe your interests. We may be able to find other people with similar interests and suggest friendships.'] = 'Ustaw kilka publicznych słów kluczowych dla swojego profilu, które opisują Twoje zainteresowania. Możemy znaleźć inne osoby o podobnych zainteresowaniach i zasugerować przyjaźnie.';
$a->strings['Connecting'] = 'Łączenie';
$a->strings['Importing Emails'] = 'Importowanie e-maili';
$a->strings['Enter your email access information on your Connector Settings page if you wish to import and interact with friends or mailing lists from your email INBOX'] = 'Wprowadź swoje dane dostępowe do emaila na stronie ustawień integracji, by zaimportować i wchodzić w interakcję ze znajomymi z listy mailingowej z Twojej skrzynki przychodzącej na poczcie.';
$a->strings['Go to Your Contacts Page'] = 'Idź do strony z Twoimi kontaktami';
$a->strings['Your Contacts page is your gateway to managing friendships and connecting with friends on other networks. Typically you enter their address or site URL in the <em>Add New Contact</em> dialog.'] = 'Strona Kontakty jest twoją bramą do zarządzania znajomościami i łączenia się ze znajomymi w innych sieciach. Wystarczy podać ich adres lub URL strony w oknie dialogowym <em>Dodaj nowy kontakt</em>.';
$a->strings['Go to Your Site\'s Directory'] = 'Idż do twojej strony';
$a->strings['The Directory page lets you find other people in this network or other federated sites. Look for a <em>Connect</em> or <em>Follow</em> link on their profile page. Provide your own Identity Address if requested.'] = 'Strona Katalog umożliwia znalezienie innych osób w tej sieci lub innych witrynach stowarzyszonych. Poszukaj łącza <em>Połącz</em> lub <em>Śledź</em> na stronie profilu. Jeśli chcesz, podaj swój własny adres tożsamości.';
$a->strings['Finding New People'] = 'Znajdowanie nowych osób';
$a->strings['On the side panel of the Contacts page are several tools to find new friends. We can match people by interest, look up people by name or interest, and provide suggestions based on network relationships. On a brand new site, friend suggestions will usually begin to be populated within 24 hours.'] = 'Na bocznym panelu strony Kontaktów znajduje się kilka narzędzi do wyszukiwania nowych znajomych. Możesz znaleźć osoby dopasowane według Twoich zainteresowań, wyszukiwać osoby po imieniu/pseudonimie i zainteresowaniach oraz odnaleźć sugestie znajomości oparte na relacjach sieciowych. Na zupełnie świeżym profilu sugestie znajomych zaczynają być uzupełniane w ciągu 24 godzin.';
$a->strings['Add Your Contacts To Circle'] = 'Dodaj swoje kontakty do Kręgu';
$a->strings['Once you have made some friends, organize them into private conversation circles from the sidebar of your Contacts page and then you can interact with each circle privately on your Network page.'] = 'Gdy zdobędziesz kilku znajomych, uporządkuj ich za pomocą prywatnych Kręgów na pasku bocznym na stronie Kontakty. Dzięki temu będziesz mógł wchodzić w interakcje z każdym Kręgiem prywatnie na stronie Twojej Sieci.';
$a->strings['Why Aren\'t My Posts Public?'] = 'Dlaczego moje wpisy nie są publiczne?';
$a->strings['Friendica respects your privacy. By default, your posts will only show up to people you\'ve added as friends. For more information, see the help section from the link above.'] = 'Friendica szanuje Twoją prywatność. Domyślnie Twoje wpisy będą wyświetlane tylko osobom, które dodałeś jako znajomi. Aby uzyskać więcej informacji, zobacz sekcję pomocy na powyższym łączu.';
$a->strings['Getting Help'] = 'Otrzymaj pomoc';
$a->strings['Go to the Help Section'] = 'Przejdź do sekcji pomocy';
$a->strings['Our <strong>help</strong> pages may be consulted for detail on other program features and resources.'] = 'Na naszych stronach <strong>pomocy</strong> można znaleźć szczegółowe informacje na temat innych funkcji programu i zasobów.';
$a->strings['{0} wants to follow you'] = '{0} chce Cię obserwować';
$a->strings['{0} has started following you'] = '{0} zaczął Cię obserwować';
$a->strings['%s liked %s\'s post'] = '%s polubił wpis %s';
$a->strings['%s disliked %s\'s post'] = '%s nie lubi wpisów %s';
$a->strings['%s is attending %s\'s event'] = '%s uczestniczy w wydarzeniu %s';
$a->strings['%s is not attending %s\'s event'] = '%s nie uczestniczy w wydarzeniu %s';
$a->strings['%s may attending %s\'s event'] = '%s może uczestniczyć w wydarzeniu %s';
$a->strings['%s is now friends with %s'] = '%s jest teraz znajomym %s';
$a->strings['%s commented on %s\'s post'] = '%s skomentował wpis %s';
$a->strings['%s created a new post'] = '%s dodał nowy wpis';
$a->strings['Friend Suggestion'] = 'Propozycja znajomych';
$a->strings['Friend/Connect Request'] = 'Prośba o kontakt/znajomość';
$a->strings['%1$s wants to follow you'] = '%1$s chce Cię obserwować';
$a->strings['%1$s has started following you'] = '%1$s zaczął Cię obserwować';
$a->strings['%1$s liked your comment on %2$s'] = '%1$s polubił Twój komentarz o %2$s';
$a->strings['%1$s liked your post %2$s'] = '%1$s polubił Twój wpis %2$s';
$a->strings['%1$s disliked your comment on %2$s'] = '%1$s nie lubi Twojego komentarza o %2$s';
$a->strings['%1$s disliked your post %2$s'] = '%1$s nie lubi Twojego wpisu %2$s';
$a->strings['%1$s shared your comment %2$s'] = '%1$s udostępnił Twój komentarz %2$s';
$a->strings['%1$s shared your post %2$s'] = '%1$s udostępnił Twój wpis %2$s';
$a->strings['%1$s shared the post %2$s from %3$s'] = '%1$s udostępnił wpis %2$s z %3$s';
$a->strings['%1$s shared a post from %3$s'] = '%1$s udostępnił wpis z %3$s';
$a->strings['%1$s shared the post %2$s'] = '%1$s udostępnił wpis %2$s';
$a->strings['%1$s shared a post'] = '%1$s udostępnił wpis';
$a->strings['%1$s wants to attend your event %2$s'] = '%1$s chce uczestniczyć w Twoim wydarzeniu %2$s';
$a->strings['%1$s does not want to attend your event %2$s'] = '%1$s nie chce uczestniczyć w Twoim wydarzeniu %2$s';
$a->strings['%1$s maybe wants to attend your event %2$s'] = '%1$s może chcieć wziąć udział w Twoim wydarzeniu %2$s';
$a->strings['%1$s tagged you on %2$s'] = '%1$s oznaczył Cię na %2$s';
$a->strings['%1$s replied to you on %2$s'] = '%1$s odpowiedział Ci na %2$s';
$a->strings['%1$s commented in your thread %2$s'] = '%1$s skomentował/-a Twój wątek %2$s';
$a->strings['%1$s commented on your comment %2$s'] = '%1$s skomentował Twój komentarz %2$s';
$a->strings['%1$s commented in their thread %2$s'] = '%1$s skomentował/-a swój wątek %2$s';
$a->strings['%1$s commented in their thread'] = '%1$s skomentował/-a swój wątek';
$a->strings['%1$s commented in the thread %2$s from %3$s'] = '%1$s skomentował/-a wątek %2$s od %3$s';
$a->strings['%1$s commented in the thread from %3$s'] = '%1$s skomentował/-a wątek od %3$s';
$a->strings['%1$s commented on your thread %2$s'] = '%1$s skomentował Twój wątek %2$s';
$a->strings['[Friendica:Notify]'] = '[Friendica: Powiadomienie]';
$a->strings['%s New mail received at %s'] = '%s Nowa poczta otrzymana o %s';
$a->strings['%1$s sent you a new private message at %2$s.'] = '%1$s wysłał(-a) ci nową prywatną wiadomość na %2$s.';
$a->strings['a private message'] = 'prywatna wiadomość';
$a->strings['%1$s sent you %2$s.'] = '%1$s wysłał(-a) ci %2$s.';
$a->strings['Please visit %s to view and/or reply to your private messages.'] = 'Odwiedź %s, aby zobaczyć i/lub odpowiedzieć na twoje prywatne wiadomości.';
$a->strings['%1$s commented on %2$s\'s %3$s %4$s'] = '%1$s skomentował %2$s\'s %3$s %4$s';
$a->strings['%1$s commented on your %2$s %3$s'] = '%1$s skomentował Twój %2$s %3$s';
$a->strings['%1$s commented on their %2$s %3$s'] = '%1$s skomentował swój %2$s %3$s';
$a->strings['%1$s Comment to conversation #%2$d by %3$s'] = '%1$s Komentarz do wątku #%2$d autora %3$s';
$a->strings['%s commented on an item/conversation you have been following.'] = '%s skomentował(-a) wątek który obserwujesz.';
$a->strings['Please visit %s to view and/or reply to the conversation.'] = 'Odwiedź %s, aby wyświetlić i/lub odpowiedzieć w wątku.';
$a->strings['%s %s posted to your profile wall'] = '%s %s opublikował na Twojej tablicy profilu';
$a->strings['%1$s posted to your profile wall at %2$s'] = '%1$s opublikował(-a) wpis na Twojej tablicy o %2$s';
$a->strings['%1$s posted to [url=%2$s]your wall[/url]'] = '%1$s opublikował(-a) na [url=%2$s]Twojej tablicy[/url]';
$a->strings['%s Introduction received'] = 'Otrzymano %s zaproszenie';
$a->strings['You\'ve received an introduction from \'%1$s\' at %2$s'] = 'Otrzymałeś wstęp od \'%1$s\' z %2$s';
$a->strings['You\'ve received [url=%1$s]an introduction[/url] from %2$s.'] = 'Zostałeś [url=%1$s] przyjęty [/ url] z %2$s.';
$a->strings['You may visit their profile at %s'] = 'Możesz odwiedzić ich profil na stronie %s';
$a->strings['Please visit %s to approve or reject the introduction.'] = 'Odwiedż %s aby zatwierdzić lub odrzucić przedstawienie.';
$a->strings['%s You have a new follower'] = '%s Masz nowego obserwującego';
$a->strings['You have a new follower at %2$s : %1$s'] = 'Masz nowego obserwatora na %2$s : %1$s';
$a->strings['%s Friend suggestion received'] = '%s Otrzymano sugestię znajomego';
$a->strings['You\'ve received a friend suggestion from \'%1$s\' at %2$s'] = 'Otrzymałeś od znajomego sugestię \'%1$s\' na %2$s';
$a->strings['You\'ve received [url=%1$s]a friend suggestion[/url] for %2$s from %3$s.'] = 'Otrzymałeś [url=%1$s] sugestię znajomego [/url] dla %2$s od %3$s.';
$a->strings['Name:'] = 'Imię:';
$a->strings['Photo:'] = 'Zdjęcie:';
$a->strings['Please visit %s to approve or reject the suggestion.'] = 'Odwiedź stronę %s, aby zatwierdzić lub odrzucić sugestię.';
$a->strings['%s Connection accepted'] = '%s Połączenie zaakceptowane';
$a->strings['\'%1$s\' has accepted your connection request at %2$s'] = '\'%1$s\' zaakceptował Twoją prośbę o połączenie na %2$s';
$a->strings['%2$s has accepted your [url=%1$s]connection request[/url].'] = '%2$s zaakceptował twoją [url=%1$s] prośbę o połączenie [/url].';
$a->strings['Please visit %s if you wish to make any changes to this relationship.'] = 'Odwiedź stronę %s jeśli chcesz wprowadzić zmiany w tym związku.';
$a->strings['\'%1$s\' has chosen to accept you a fan, which restricts some forms of communication - such as private messaging and some profile interactions. If this is a celebrity or community page, these settings were applied automatically.'] = '\'%1$s\' zdecydował się zaakceptować Cię jako fana, który ogranicza niektóre formy komunikacji - takie jak prywatne wiadomości i niektóre interakcje w profilu. Jeśli jest to strona celebrytów lub społeczności, ustawienia te zostały zastosowane automatycznie.';
$a->strings['\'%1$s\' may choose to extend this into a two-way or more permissive relationship in the future.'] = '\'%1$s\' możesz zdecydować o przedłużeniu tego w dwukierunkową lub bardziej ścisłą relację w przyszłości. ';
$a->strings['Please visit %s  if you wish to make any changes to this relationship.'] = 'Odwiedź stronę %s, jeśli chcesz wprowadzić zmiany w tej relacji.';
$a->strings['registration request'] = 'prośba o rejestrację';
$a->strings['You\'ve received a registration request from \'%1$s\' at %2$s'] = 'Otrzymałeś wniosek rejestracyjny od \'%1$s\' na %2$s';
$a->strings['You\'ve received a [url=%1$s]registration request[/url] from %2$s.'] = 'Otrzymałeś [url=%1$s] żądanie rejestracji [/url] od %2$s.';
$a->strings['Display Name:	%s
Site Location:	%s
Login Name:	%s (%s)'] = 'Nazwa wyświetlana:	%s
Adres witryny:	%s
Login:	%s (%s)';
$a->strings['Please visit %s to approve or reject the request.'] = 'Odwiedź stronę %s, aby zatwierdzić lub odrzucić wniosek.';
$a->strings['new registration'] = 'nowa rejestracja';
$a->strings['You\'ve received a new registration from \'%1$s\' at %2$s'] = 'Otrzymałeś nową rejestrację od \'%1$s\' na %2$s';
$a->strings['You\'ve received a [url=%1$s]new registration[/url] from %2$s.'] = 'Otrzymałeś [url=%1$s]nową rejestrację[/url] od%2$s.';
$a->strings['Please visit %s to have a look at the new registration.'] = 'Odwiedź %s aby zobaczyć nową rejestrację.';
$a->strings['%s %s tagged you'] = '%s %s oznaczył Cię';
$a->strings['%s %s shared a new post'] = '%s %s udostępnił nowy wpis';
$a->strings['%1$s %2$s liked your post #%3$d'] = '%1$s %2$s lubi Twój wpis #%3$d';
$a->strings['%1$s %2$s liked your comment on #%3$d'] = '%1$s %2$s lubi Twój komentarz do #%3$d';
$a->strings['This message was sent to you by %s, a member of the Friendica social network.'] = 'Wiadomość została wysłana do ciebie od %s, członka sieci społecznościowej Friendica.';
$a->strings['You may visit them online at %s'] = 'Możesz odwiedzić ich online pod adresem %s';
$a->strings['Please contact the sender by replying to this post if you do not wish to receive these messages.'] = 'Jeśli nie chcesz otrzymywać tych wiadomości kontaktuj się z nadawcą odpowiadając na ten wpis.';
$a->strings['%s posted an update.'] = '%s zaktualizował wpis.';
$a->strings['Public Message'] = 'Publiczna wiadomość';
$a->strings['Unlisted Message'] = 'Wiadomość nieindeksowana';
$a->strings['This entry was edited'] = 'Edytowano';
$a->strings['Connector Message'] = 'Wiadomość integracji';
$a->strings['Delete globally'] = 'Usuń globalnie';
$a->strings['Remove locally'] = 'Usuń lokalnie';
$a->strings['Block %s'] = 'Zablokuj %s';
$a->strings['Ignore %s'] = 'Ignoruj %s';
$a->strings['Collapse %s'] = 'Zwiń %s';
$a->strings['Report post'] = 'Zgłoś wpis';
$a->strings['Save to folder'] = 'Zapisz w katalogu';
$a->strings['I will attend'] = 'Będę uczestniczyć';
$a->strings['I will not attend'] = 'Nie będę uczestniczyć';
$a->strings['I might attend'] = 'Mogę wziąć udział';
$a->strings['Ignore thread'] = 'Zignoruj ​​wątek';
$a->strings['Unignore thread'] = 'Przestań ignorować ​​wątek';
$a->strings['Toggle ignore status'] = 'Przełącz stan ignorowania';
$a->strings['Add star'] = 'Oznacz gwiazdką';
$a->strings['Remove star'] = 'Usuń gwiazdkę';
$a->strings['Toggle star status'] = 'Przełącz stan gwiazdy';
$a->strings['Pin'] = 'Przypnij';
$a->strings['Unpin'] = 'Odepnij';
$a->strings['Toggle pin status'] = 'Przełącz stan podpięcia';
$a->strings['Pinned'] = 'Przypięty';
$a->strings['Add tag'] = 'Dodaj znacznik';
$a->strings['Quote share this'] = 'Zacytuj to';
$a->strings['Quote Share'] = 'Zacytuj';
$a->strings['Reshare this'] = 'Przekaż dalej';
$a->strings['Reshare'] = 'Przekaż dalej';
$a->strings['Cancel your Reshare'] = 'Anuluj Twoje przekazanie dalej';
$a->strings['Unshare'] = 'Przestań udostępniać';
$a->strings['%s (Received %s)'] = '%s (Otrzymano %s)';
$a->strings['Comment this item on your system'] = 'Skomentuj ten obiekt w Twoim systemie';
$a->strings['Remote comment'] = 'Komentarz z innej instancji';
$a->strings['Share via ...'] = 'Udostępnij poprzez...';
$a->strings['Share via external services'] = 'Udostępnij za pośrednictwem usług zewnętrznych';
$a->strings['Unknown parent'] = 'Nieznany element nadrzędny';
$a->strings['in reply to %s'] = 'W odpowiedzi do %s';
$a->strings['Parent is probably private or not federated.'] = 'Element nadrzędny jest prawdopodobnie prywatny lub niezfederowany.';
$a->strings['to'] = 'do';
$a->strings['Wall-to-Wall'] = 'Tablica-w-Tablicę';
$a->strings['via Wall-To-Wall:'] = 'przez Tablica-w-Tablicę:';
$a->strings['Reply to %s'] = 'Odpowiedź %s';
$a->strings['Notifier task is pending'] = 'Zadanie Notifier jest w toku';
$a->strings['Delivery to remote servers is pending'] = 'Oczekuje na przesłanie do innych instancji';
$a->strings['Delivery to remote servers is underway'] = 'Trwa przesyłanie do innych instancji';
$a->strings['Delivery to remote servers is mostly done'] = 'Przesyłanie do innych instancji prawie zakończone';
$a->strings['Delivery to remote servers is done'] = 'Przesłano na inne instancje';
$a->strings['%d comment'] = [
	0 => '%d komentarz',
	1 => '%d komentarze',
	2 => '%d komentarzy',
	3 => '%d komentarzy',
];
$a->strings['Show more'] = 'Pokaż więcej';
$a->strings['Show fewer'] = 'Pokaż mniej';
$a->strings['Reshared by: %s'] = 'Przekazane dalej przez: %s';
$a->strings['Viewed by: %s'] = 'Obejrzane przez: %s';
$a->strings['Read by: %s'] = 'Przeczytane przez: %s';
$a->strings['Liked by: %s'] = 'Polubione przez: %s';
$a->strings['Disliked by: %s'] = 'Nielubiane przez: %s';
$a->strings['Attended by: %s'] = 'Wzięli udział: %s';
$a->strings['Maybe attended by: %s'] = 'Być może wzięli udział: %s';
$a->strings['Not attended by: %s'] = 'Nie wzięli udziału: %s';
$a->strings['Commented by: %s'] = 'Skomentowane przez: %s';
$a->strings['Reacted with %s by: %s'] = 'Zareagowane %s przez: %s';
$a->strings['Quote shared by: %s'] = 'Zacytowane przez: %s';
$a->strings['Chat'] = 'Czat';
$a->strings['(no subject)'] = '(bez tematu)';
$a->strings['The folder %s must be writable by webserver.'] = 'Folder %s musi być zapisywalny przez serwer.';
$a->strings['Login failed.'] = 'Logowanie nieudane.';
$a->strings['Login failed. Please check your credentials.'] = 'Logowanie nie powiodło się. Sprawdź swoje dane uwierzytelniające.';
$a->strings['Login failed because your account is blocked.'] = 'Logowanie nieudane z powodu blokady Twojego konta.';
$a->strings['Welcome %s'] = 'Witaj %s';
$a->strings['Please upload a profile photo.'] = 'Proszę dodać zdjęcie profilowe.';
$a->strings['OpenWebAuth: %1$s welcomes %2$s'] = 'OpenWebAuth: %1$s wita %2$s';
$a->strings['Friendica Notification'] = 'Powiadomienia Friendica';
$a->strings['%1$s, %2$s Administrator'] = '%1$s, %2$s Administrator';
$a->strings['%s Administrator'] = '%s Administrator';
$a->strings['thanks'] = 'dziękuję';
$a->strings['YYYY-MM-DD or MM-DD'] = 'RRRR-MM-DD lub MM-DD';
$a->strings['Time zone: <strong>%s</strong> <a href="%s">Change in Settings</a>'] = 'Strefa czasowa: <strong>%s</strong> <a href="%s">Zmień w ustawieniach</a>';
$a->strings['never'] = 'nigdy';
$a->strings['less than a second ago'] = 'mniej niż sekundę temu';
$a->strings['year'] = 'rok';
$a->strings['years'] = 'lata';
$a->strings['months'] = 'miesięcy';
$a->strings['weeks'] = 'tygodni';
$a->strings['days'] = 'dni';
$a->strings['hour'] = 'godzinę';
$a->strings['hours'] = 'godzin';
$a->strings['minute'] = 'minutę';
$a->strings['minutes'] = 'minut';
$a->strings['second'] = 'sekundę';
$a->strings['seconds'] = 'sekund';
$a->strings['in %1$d %2$s'] = 'za %1$d %2$s';
$a->strings['%1$d %2$s ago'] = '%1$d %2$s temu';
$a->strings['Notification from Friendica'] = 'Powiadomienia od Friendica';
$a->strings['Empty Post'] = 'Pusty wpis';
$a->strings['Variations'] = 'Wariacje';
$a->strings['Note'] = 'Uwaga';
$a->strings['Check image permissions if all users are allowed to see the image'] = 'Sprawdź ustawienia dostępu do obrazka, czy wszyscy użytkownicy mogą go zobaczyć';
$a->strings['Appearance'] = 'Wygląd';
$a->strings['Accent color'] = 'Kolor akcentu';
$a->strings['Copy or paste schemestring'] = 'Skopiuj lub wklej ciąg schematu';
$a->strings['You can copy this string to share your theme with others. Pasting here applies the schemestring'] = 'Możesz skopiować ten ciąg, aby podzielić się swoimi ustawieniami z innymi. Wklejanie ciągu w tym miejscu zastosuje nowe ustawienia';
$a->strings['Navigation bar background color'] = 'Kolor tła paska nawigacyjnego';
$a->strings['Navigation bar icon color '] = 'Kolor ikon na pasku nawigacyjnym';
$a->strings['Link color'] = 'Kolor linków';
$a->strings['Set the background color'] = 'Kolor tła';
$a->strings['Content background opacity'] = 'Przezroczystość tła zawartości';
$a->strings['Set the background image'] = 'Obraz tła';
$a->strings['Background image style'] = 'Styl obrazu tła';
$a->strings['Always open Compose page'] = 'Zawsze otwieraj stronę tworzenia wpisu';
$a->strings['The New Post button always open the <a href="/compose">Compose page</a> instead of the modal form. When this is disabled, the Compose page can be accessed with a middle click on the link or from the modal.'] = 'Przycisk Nowy Wpis zawsze otworzy <a href="/compose">stronę tworzenia</a> zamiast okna modalnego. Kiedy ta opcja jest wyłączona, stronę tworzenia wpisu można otworzyć za pomocą środkowego przycisku myszy lub z poziomu okna modalnego.';
$a->strings['Login page background image'] = 'Obraz tła strony logowania';
$a->strings['Login page background color'] = 'Kolor tła strony logowania';
$a->strings['Leave background image and color empty for theme defaults'] = 'Pozostaw pusty obraz tła i kolor dla domyślnych ustawień motywu';
$a->strings['Top Banner'] = 'Górny baner';
$a->strings['Resize image to the width of the screen and show background color below on long pages.'] = 'Dopasuj rozmiar obrazu do szerokości ekranu i wyświetlaj pod nim kolor tła na długich stronach.';
$a->strings['Full screen'] = 'Pełny ekran';
$a->strings['Resize image to fill entire screen, clipping either the right or the bottom.'] = 'Dopasuj rozmiar obrazu, aby wypełnić cały ekran, przycinając go przy tym od prawej lub od dołu.';
$a->strings['Single row mosaic'] = 'Mozaika jednorzędowa';
$a->strings['Resize image to repeat it on a single row, either vertical or horizontal.'] = 'Dopasuj rozmiar obrazu, powtarzając go w jednym wierszu, w pionie lub w poziomie.';
$a->strings['Mosaic'] = 'Mozaika';
$a->strings['Repeat image to fill the screen.'] = 'Powielaj obraz, aby wypełnić ekran.';
$a->strings['Back to top'] = 'Powrót na górę';
$a->strings['Light'] = 'Jasny';
$a->strings['Dark'] = 'Ciemny';
$a->strings['Custom'] = 'Niestandardowy';
$a->strings['Guest'] = 'Gość';
$a->strings['Visitor'] = 'Odwiedzający';
$a->strings['Alignment'] = 'Wyrównanie';
$a->strings['Left'] = 'Do lewej';
$a->strings['Center'] = 'Do środka';
$a->strings['Color scheme'] = 'Schemat kolorystyczny';
$a->strings['Posts font size'] = 'Rozmiar fontu wpisów';
$a->strings['Textareas font size'] = 'Rozmiar fontu obszarów tekstowych';
$a->strings['Comma separated list of helper groups'] = 'Nazwy grup pomocy oddzielone przecinkami';
$a->strings['don\'t show'] = 'ukryj';
$a->strings['show'] = 'pokaż';
$a->strings['Set style'] = 'Ustaw styl';
$a->strings['Community Pages'] = 'Strony społeczności';
$a->strings['Community Profiles'] = 'Profile społeczności';
$a->strings['Help or @NewHere ?'] = 'Pomoc lub @NowyTutaj ?';
$a->strings['Connect Services'] = 'Integracje z serwisami';
$a->strings['Find Friends'] = 'Znajdź znajomych';
$a->strings['Last users'] = 'Ostatni użytkownicy';
$a->strings['Quick Start'] = 'Szybki start';
