Circles und Privatsphäre
==================

* [Zur Startseite der Hilfe](help)

Circles sind nur eine Ansammlung von Freunden.
Aber Friendica nutzt diese, um sehr mächtige Features zur Verfügung zu stellen.

**Circles erstellen**

Um eine Circle zu erstellen, besuche deine "Kontakte"-Seite und wähle "Neue Circle erstellen" (je nach Design nur als Pluszeichen angezeigt).
Gib deiner Circle einen Namen.

Das führt dich zu einer Seite, auf der du die Gruppenmitglieder auswählen kannst.

Du hast zwei Boxen auf der Seite.
Die obere Box ist die Übersicht der aktuellen Mitglieder.
Die untere beinhaltet alle Freunde, die *nicht* Mitglied dieser Circle sind.

Wenn du auf das Foto einer Person klickst, die nicht in der Circle ist, wird diese in die Circle verschoben.
Wenn du auf das Foto einer Person klickst, die bereits in der Circle ist, dann wird diese Person daraus entfernt.

**Zugriffskontrolle**

Sobald du eine Circle erstellt hast, kannst du diese auf jeder Zugriffsrechteliste nutzen.
Damit ist das kleine Schloss neben deinem Statuseditor auf deiner Startseite gemeint.
Wenn du darauf klickst, kannst du auswählen, wer deinen Beitrag sehen kann und wer *nicht*.
Dabei kann es sich um eine einzelne Person oder eine ganze Circle handeln.

Auf deiner "Netzwerk"-Seite ("Unterhaltungen deiner Kontakte") findest du Beiträge und Gespräche aller deiner Kontakte in deinem Netzwerk.
Du kannst aber auch eine einzelne Circle auswählen und nur Beiträge dieser Gruppenmitglieder anzeigen lassen.

Aber stopp, es gibt noch mehr...

Wenn du auf deiner "Netzwerk"-Seite eine bestimmte Circle ausgewählt hast, dann findest du im Statuseditor neben dem Schloss ein Ausrufezeichen.
Dies dient dazu, deine Aufmerksamkeit auf das Schloss zu richten.
Klicke auf das Schloss.
Dort siehst du, dass dein Status-Update in dieser Ansicht standardmäßig nur für diese Circle freigegeben ist.
Das hilft dir, deinen zukünftigen Mitarbeitern nicht das Gleiche zu schreiben wie deinen Trinkfreunden.
Du kannst diese Einstellung natürlich auch überschreiben.

**Standardmäßige Zugriffsrechte von Beiträgen**

Standardmäßig geht Friendica davon aus, dass alle deine Beiträge privat sein sollen.
Aus diesem Grund erstellt Friendica nach der Anmeldung eine Circle, in die automatisch alle deine Kontakte hinzugefügt werden.
Alle deine Beiträge sind nur auf diese Circle beschränkt.

Beachte, dass diese Einstellung von deinem Seiten-Administrator überschrieben werden kann, was bedeutet, dass alle deine Beiträge standardmäßig "öffentlich" sind (bspw. für das gesamte Internet).

Wenn du deine Beiträge standardmäßig "öffentlich" haben willst, dann kannst du deine Standardzugriffsrechte auf deiner Einstellungseite ändern.
Dort kannst du außerdem festlegen, welchen Circles standardmäßig deine Beiträge erhalten oder in welche Circle deine neuen Kontakte standardmäßig eingeordnet werden.

**Fragen der Privatssphäre, die zu beachten sind**

Diese privaten Gespräche funktionieren am besten, wenn deine Freunde Friendica-Mitglieder sind.
So wissen wir, wer sonst noch deine Gespräche sehen kann - niemand, *solange* deine Freunde deine Nachrichten nicht kopieren und an andere verschicken.

Dies ist eine Vertrauensfrage, die du beachten musst.
Keine Software der Welt kann deine Freunde davon abhalten, die privaten Unterhaltungen zu veröffentlichen.
Nur eine gute Auswahl deiner Freunde.

Bei GNu Social und anderen Netzwerk-Anbietern ist es nicht so gesichert.
Du musst **sehr** vorsichtig sein, wenn du Mitglieder anderer Netzwerke in einer deiner Circles hast, da es möglich ist, dass deine privaten Nachrichten in einem öffentlichen Stream enden.
Wenn du auf die "Kontakt bearbeiten"-Seite einer Person gehst, zeigen wir dir, ob sie Mitglied eines unsicheren Netzwerks ist oder nicht.

Sobald du einen Post erstellt hast, kannst du die Zugriffsrechte nicht mehr ändern.
Innerhalb von Sekunden ist dieser an viele verschiedene Personen verschickt worden - möglicherweise bereits an alle Addressierten.
Wenn du versehentlich eine Nachricht erstellt hast und sie zurücknehmen willst, dann ist es das beste, diese zu löschen.
Wir senden eine Löschmitteilung an jeden, der deine Nachricht erhalten hat - und das sollte die Nachricht genauso schnell löschen, wie sie zunächst erstellt wurde.
In vielen Fällen wird sie in weniger als einer Minute aus dem Internet gelöscht.
Nochmals: das gilt für Friendica-Netzwerke.
Sobald eine Nachricht an ein anderes Netzwerk geschickt wurde, kann es nicht mehr so schnell gelöscht werden und in manchen Fällen auch gar nicht mehr.

Wenn du das bisher noch nicht wusstest, dann empfehlen wir dir, deine Freunde dazu zu ermutigen, auch Friendica zu nutzen, da alle diese Privatsphären-Einstellungen innerhalb eines privatsphärenbewussten Netzwerk viel besser funktionieren.
Viele andere Netzwerke, mit denen sich Friendica verbinden kann, bieten keine Kontrolle über die Privatsphäre.


Profile, Fotos und die Privatsphäre
=============================

Die dezentralisierte Natur von Friendica (statt eine Webseite zu haben, die alles kontrolliert, gibt es viele Webseiten, die Information austauschen) hat in der Kommunikation mit anderen Seiten einige Konsequenzen.
Du solltest dir über einige Dinge bewusst sein, um am besten entscheiden zu können, wie du mit deiner Privatsphäre umgehst.

**Fotos**

Fotos privat zu verteilen ist ein Problem.
Wir können Fotos nur mit Friendica-Nutzern __privat__ austauschen.
Um mit anderen Leuten Fotos zu teilen, müssen wir erkennen, wer sie sind.
Wir können die Identität von Friendica-Nutzern prüfen, da es hierfür einen Mechanismus gibt.
Deine Freunde anderer Netzwerke werden deine privaten Fotos nicht sehen können, da wir deren Identität nicht überprüfen können.

Unsere Entwickler arbeiten an einer Lösung, um deinen Freunden den Zugriff zu ermöglichen - unabhängig, zu welchem Netzwerk sie gehören.
Wir nehmen hingegen Privatsphäre ernst und agieren nicht wie andere Netzwerke, die __nur so tun__ als ob deine Fotos privat sind, sie aber trotzdem anderen ohne Identitätsprüfung zeigen.

**Profile**

Dein Profil und deine "Wall" sollen vielleicht auch von Freunden anderer Netzwerke besucht werden können.
Wenn du diese Seiten allerdings für Webbesucher sperrst, die Friendica nicht kennt, kann das auch Freunde anderer Netzwerke blockieren.

Das kann möglicherweise ungewollte Ergebnisse produzieren, wenn du lange Statusbeiträge z.B. für Twitter oder Facebook schreibst.
Wenn Friendica einen Beitrag an diese Netzwerke schickt und nur eine bestimmte Nachrichtenlänge erlaubt ist, dann verkürzen wir diesen und erstellen einen Link, der zum Originalbeitrag führt.
Der Originallink führt zurück zu deinem Friendica-Profil.
Da Friendica nicht bestätigen kann, um wen es sich handelt, kann es passieren, dass diese Leute den Beitrag nicht komplett lesen können.

Für Leute, die davon betroffen sind, schlagen wir vor, eine Zusammenfassung in Twitter-Länge zu erstellen mit mehr Details für Freunde, die den ganzen Beitrag sehen können.

Dein Profil oder deine gesamte Friendica-Seite zu blockieren, hat außerdem ernsthafte Einflüsse auf deine Kommunikation mit GNU Social-Nutzern.
Diese Netzwerke kommunizieren mit anderen über öffentliche Protokolle, die nicht authentifiziert werden.
Um deine Beiträge zu sehen, müssen diese Netzwerke deine Beiträge als "unbekannte Webbesucher" ansehen.
Wenn wir das erlauben, würde es dazu führen, das absolut jeder deine Beiträge sehen.
Und du hast Friendica so eingestellt, das nicht zuzulassen.
Beachte also, dass das Blockieren von unbekannten Besuchern auch dazu führen kann, dass öffentliche Netzwerke (wie GNU Social) und Newsfeed-Reader auch geblockt werden.
