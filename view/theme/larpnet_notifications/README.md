# Larpnet Notifications

Motyw LARPnet z obsługą Web Push, powiadomień przeglądarkowych i PWA. Wymaga serwera ntfy.

## Wymagania

- Nowoczesna przeglądarka z włączonym JavaScript
- Działający serwer ntfy skonfigurowany w panelu administracyjnym
- Po aktualizacji motywu zalecane jest jego wyłączenie i ponowne włączenie w panelu administracyjnym

## Changelog

### 1.3
- Walidacja schematu URL banera zdalnego profilu (bezpieczeństwo: akceptowane tylko http/https)
- Przełącznik banera na poziomie użytkownika w ustawieniach motywu
- Poprawka zapisu ustawień motywu dla zwykłych użytkowników (widoczny przycisk Zapisz)

### 1.2
- Wyświetlanie banera (header image) na stronie profilu
- Baner widoczny dla wszystkich profili (lokalnych i zdalnych z ActivityPub)
- Ikona edycji banera dla właściciela profilu (wymaga dodatku larpnet_banner)

### 1.1
- Uniezależnienie motywu od larpnet (własne ścieżki do zasobów)
- Aktualizacja metadanych i zrzutu ekranu

### 1.0
- Pierwsza wersja oparta na frio z obsługą Web Push i PWA

## Credits

Bazuje na motywie [frio](https://github.com/friendica/friendica/tree/stable/view/theme/frio) autorstwa Rabuzarusa i Hypolite Petovana.

## Licencja

AGPL-3.0-or-later
