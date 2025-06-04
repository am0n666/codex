# Zaawansowany kreator e-booków PDF (PHP 8.x)

Konfigurowalny kreator e-booków PDF z szablonami, stylami, obsługą okładki, rozdziałami i zaawansowanymi ustawieniami PDF.

## Funkcje

- Szablony: classic/modern
- Style: jasny/ciemny
- Okładka: dowolny plik graficzny
- Rozdziały: dowolna liczba, dynamiczne dodawanie/edycja
- Spis treści generowany automatycznie
- Zaawansowane PDF: wybór formatu, marginesów, orientacji
- Podgląd PDF na żywo i pobieranie
- Zapis/wczytywanie projektu (LocalStorage)
- Eksport/import projektu jako plik JSON
- Wczytywanie rozdziałów z plików Markdown

## Instalacja

```bash
composer install
```

## Uruchomienie

```bash
php -S localhost:8000 -t public
```

Otwórz [http://localhost:8000](http://localhost:8000) w przeglądarce.

## Eksport/Import projektu

- Eksportuj projekt jako plik JSON (przycisk "Eksportuj projekt").
- Importuj projekt wybierając plik JSON (przycisk "Importuj projekt").

## Rozszerzanie

Projekt łatwo rozbudować o spis ilustracji, stopki/nagłówki, itp.