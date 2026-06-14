<?php

return [

    'slug' => [
        'placeholder' => 'twoj-adres-slug',
        'permalink' => 'Bezposredni link',
        'badge_auto' => 'Auto',
        'badge_custom' => 'Reczny',
        'edit' => 'Edytuj',
        'confirm' => 'OK',
        'cancel' => 'Anuluj',
        'reset' => 'Przywroc',
        'regenerate' => 'Regeneruj',
        'copy' => 'Kopiuj',
        'copied' => 'Skopiowano',
        'visit' => 'Odwiedz',
        'changed' => 'Zmieniono',
    ],

    'dual_listbox' => [
        'available' => 'Dostepne',
        'selected' => 'Wybrane',
        'search_available' => 'Szukaj dostepnych…',
        'search_selected' => 'Szukaj wybranych…',
        'empty_available' => 'Brak dostepnych pozycji',
        'empty_selected' => 'Nic nie wybrano',
        'move_all_right' => 'Przenies wszystko do wybranych',
        'move_selected_right' => 'Przenies zaznaczone w prawo',
        'move_selected_left' => 'Przenies zaznaczone w lewo',
        'move_all_left' => 'Przenies wszystko do dostepnych',
        'swap_lists' => 'Zamien listy',
        'move_up' => 'Przenies w gore',
        'move_down' => 'Przenies w dol',
    ],

    'price_range' => [
        'min' => 'Cena minimalna',
        'max' => 'Cena maksymalna',
    ],

    'credit_card' => [
        'mark' => 'Bez nazwy.',
        'number' => 'Numer karty',
        'name' => 'Imie i nazwisko na karcie',
        'expiry' => 'Waznosc',
        'cvv' => 'CVV',
        'cardholder' => 'Posiadacz karty',
        'valid_thru' => 'Wazna do',
        'authorized_signature' => 'Podpis upowazniony — niewazny bez podpisu',
        'flip' => 'Obroc karte',
    ],

    'cover_card' => [
        'action' => 'Akcja',
    ],

    'video' => [
        'play' => 'Odtwarzaj',
        'pause' => 'Pauza',
        'mute' => 'Wycisz',
        'unmute' => 'Wlacz dzwiek',
        'rewind' => 'Cofnij',
        'forward' => 'Przewin do przodu',
        'progress' => 'Postep odtwarzania',
        'volume' => 'Glosnosc',
        'fullscreen' => 'Pelny ekran',
        'exit_fullscreen' => 'Wyjdz z pelnego ekranu',
        'picture_in_picture' => 'Obraz w obrazie',
        'exit_picture_in_picture' => 'Wyjdz z obrazu w obrazie',
    ],

    'audio' => [
        'play' => 'Odtwarzaj',
        'pause' => 'Pauza',
        'progress' => 'Postęp audio',
        'record_start' => 'Rozpocznij nagrywanie',
        'record_label' => 'Nagraj notatkę głosową',
        'cancel' => 'Anuluj',
        'save' => 'Zapisz',
        'uploading' => 'Przesyłanie...',
        'uploading_on_submit' => 'Przygotowywanie nagrania do zapisu...',
        'delete' => 'Usuń nagranie',
    ],

    'address_autocomplete' => [
        'search_placeholder' => 'Szukaj adresu…',
        'missing_token' => 'Brak tokenu Mapbox. Ustaw MAPBOX_ACCESS_TOKEN w pliku .env.',
        'search_loading' => 'Wyszukiwanie adresow…',
        'search_min_chars' => 'Wpisz co najmniej 2 znaki, aby zobaczyc podpowiedzi.',
        'search_no_results' => 'Nie znaleziono adresow. Sprobuj innego wyszukiwania.',
        'clear' => 'Wyczysc adres',
        'street_address_required' => 'Wybierz pelny adres ulicy. Miasta, regiony i inne obszary nie sa dozwolone.',
        'fields' => [
            'street' => 'ulica',
            'city' => 'miasto',
            'region' => 'region',
            'postcode' => 'kod pocztowy',
            'country' => 'kod kraju',
            'country_name' => 'kraj',
            'place_name' => 'nazwa miejsca',
        ],
    ],

    'validation' => [
        'dual_listbox' => [
            'invalid_option' => 'Jedna lub wiecej wybranych opcji jest nieprawidlowa.',
            'exact' => 'Wybierz dokladnie :count pozycji.',
            'min' => 'Wybierz co najmniej :count pozycji.',
            'max' => 'Wybierz nie wiecej niz :count pozycji.',
        ],
        'price_range' => [
            'invalid' => 'Podaj prawidlowy zakres cen.',
            'out_of_bounds' => 'Zakres cen musi miescic sie w dozwolonych limitach.',
            'min_greater_than_max' => 'Cena minimalna nie moze byc wieksza od maksymalnej.',
        ],
        'credit_card' => [
            'invalid_number' => 'Podaj prawidlowy numer karty.',
            'invalid_expiry' => 'Podaj prawidlowa date waznosci w formacie MM/RR.',
            'expired' => 'Ta karta wygasla.',
            'invalid_cvv' => 'Podaj prawidlowy kod zabezpieczajacy.',
        ],
        'phone' => [
            'invalid' => 'Podaj prawidlowy numer telefonu.',
            'mobile_only' => 'Podaj prawidlowy numer telefonu komorkowego.',
            'fixed_line_only' => 'Podaj prawidlowy numer telefonu stacjonarnego.',
        ],
        'currency' => [
            'negative' => 'Kwota nie moze byc ujemna.',
            'min' => 'Kwota musi wynosic co najmniej :min.',
            'max' => 'Kwota nie moze przekraczac :max.',
        ],
        'media' => [
            'invalid_url' => 'Podaj prawidlowy adres URL medium.',
        ],
        'address_autocomplete' => [
            'required_field' => 'Pole :field jest wymagane.',
            'country_not_allowed' => 'Ten kraj nie jest dozwolony dla wybranego adresu.',
            'street_address_required' => 'Wybierz pelny adres ulicy z nazwa ulicy.',
        ],
        'slug' => [
            'invalid' => 'Podaj prawidlowy slug.',
            'pattern' => 'Slug moze zawierac tylko male litery, cyfry i myslniki.',
            'unique' => 'Ten adres URL jest juz zajety. Wybierz inny slug.',
            'inline_edit_pending' => 'Zapisz slug (przycisk OK), aby kontynuowac.',
        ],
    ],

    'phone' => [
        'country' => 'Kraj',
        'timezone' => 'Strefa czasowa',
        'placeholder' => 'Numer telefonu',
        'search_countries' => 'Szukaj krajow…',
    ],

    'currency' => [
        'currency' => 'Waluta',
        'placeholder' => '0',
        'search_currencies' => 'Szukaj walut…',
    ],

    'country' => [
        'country' => 'Kraj',
        'placeholder' => 'Wybierz kraj',
        'search_countries' => 'Szukaj krajow…',
    ],

    'timezone' => [
        'placeholder' => 'Wybierz strefe czasowa',
        'search_timezones' => 'Szukaj strefy czasowej…',
    ],

    'file_upload' => [
        'summary' => ':count plik(ow), lacznie :size KB',
        'remaining_slots' => 'Pozostalo :remaining z :max miejsc',
        'replace_confirmation' => 'Zastapic obecny plik?',
        'validation' => [
            'documents_only' => 'Dozwolone sa tylko pliki dokumentow.',
            'images_only' => 'Dozwolone sa tylko pliki graficzne.',
            'spreadsheets_only' => 'Dozwolone sa tylko arkusze kalkulacyjne.',
            'extension_not_allowed' => 'Dozwolone rozszerzenia: :extensions.',
            'executable_blocked' => 'Pliki wykonywalne lub skrypty serwerowe sa niedozwolone.',
            'max_total_size' => 'Laczny rozmiar plikow nie moze przekraczac :max KB.',
            'image_dimensions' => 'Wymiary obrazu sa nieprawidlowe.',
        ],
    ],

];
