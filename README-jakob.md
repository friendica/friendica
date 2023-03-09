# Anleitung für die Erweiterungen 

Ich habe ein paar Verbesserungen an Friendica vorgenommen, die mich schon lange gestört haben. 

## Activity-Buttons

Liken und Sharen führt im Standard-UI von Friendica oft dazu, dass die Timeline irgendwo hin hüpft, und man findet den soeben gelikten Beitrag oder Kommentar nicht mehr.
Meine Änderungen führen ein Live-Update am Server aus, mit einer optischen Rückmeldung auf dem Button, der geklickt wurde, der nach erfolgreichem Request dann seinen Status ändert. Sonst eben nicht.
Es wird nur dieser eine Button aktualisiert. Damit gibt es kein Hüpfen der Timeline mehr, aber auch kein Unterbrechen eines Videos, das man sich ansieht und zwischendrin mal liken möchte.
