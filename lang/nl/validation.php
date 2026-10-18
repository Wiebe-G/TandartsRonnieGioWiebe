<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validatieregels
    |--------------------------------------------------------------------------
    |
    | Nederlandse foutmeldingen voor de validatieregels van Laravel.
    |
    */

    'accepted' => ':Attribute moet geaccepteerd worden.',
    'active_url' => ':Attribute is geen geldige URL.',
    'after' => ':Attribute moet een datum na :date zijn.',
    'after_or_equal' => ':Attribute moet een datum op of na :date zijn.',
    'alpha' => ':Attribute mag alleen letters bevatten.',
    'alpha_dash' => ':Attribute mag alleen letters, cijfers, streepjes en underscores bevatten.',
    'alpha_num' => ':Attribute mag alleen letters en cijfers bevatten.',
    'array' => ':Attribute moet een lijst zijn.',
    'before' => ':Attribute moet een datum voor :date zijn.',
    'before_or_equal' => ':Attribute moet een datum op of voor :date zijn.',
    'between' => [
        'array' => ':Attribute moet tussen :min en :max items bevatten.',
        'file' => ':Attribute moet tussen :min en :max kilobytes zijn.',
        'numeric' => ':Attribute moet tussen :min en :max liggen.',
        'string' => ':Attribute moet tussen :min en :max tekens lang zijn.',
    ],
    'boolean' => ':Attribute moet ja of nee zijn.',
    'confirmed' => 'De bevestiging van :attribute komt niet overeen.',
    'current_password' => 'Het wachtwoord is onjuist.',
    'date' => ':Attribute is geen geldige datum.',
    'date_equals' => ':Attribute moet gelijk zijn aan :date.',
    'date_format' => ':Attribute moet het formaat :format hebben.',
    'different' => ':Attribute en :other moeten verschillend zijn.',
    'digits' => ':Attribute moet uit :digits cijfers bestaan.',
    'digits_between' => ':Attribute moet tussen :min en :max cijfers bevatten.',
    'email' => ':Attribute moet een geldig e-mailadres zijn.',
    'ends_with' => ':Attribute moet eindigen met een van de volgende waarden: :values.',
    'exists' => 'De gekozen :attribute bestaat niet.',
    'file' => ':Attribute moet een bestand zijn.',
    'filled' => ':Attribute moet ingevuld zijn.',
    'gt' => [
        'array' => ':Attribute moet meer dan :value items bevatten.',
        'file' => ':Attribute moet groter zijn dan :value kilobytes.',
        'numeric' => ':Attribute moet groter zijn dan :value.',
        'string' => ':Attribute moet meer dan :value tekens bevatten.',
    ],
    'gte' => [
        'array' => ':Attribute moet :value items of meer bevatten.',
        'file' => ':Attribute moet :value kilobytes of groter zijn.',
        'numeric' => ':Attribute moet :value of groter zijn.',
        'string' => ':Attribute moet :value tekens of meer bevatten.',
    ],
    'image' => ':Attribute moet een afbeelding zijn.',
    'in' => 'De gekozen :attribute is ongeldig.',
    'integer' => ':Attribute moet een geheel getal zijn.',
    'ip' => ':Attribute moet een geldig IP-adres zijn.',
    'json' => ':Attribute moet geldige JSON zijn.',
    'lowercase' => ':Attribute mag alleen kleine letters bevatten.',
    'lt' => [
        'array' => ':Attribute moet minder dan :value items bevatten.',
        'file' => ':Attribute moet kleiner zijn dan :value kilobytes.',
        'numeric' => ':Attribute moet kleiner zijn dan :value.',
        'string' => ':Attribute moet minder dan :value tekens bevatten.',
    ],
    'lte' => [
        'array' => ':Attribute mag niet meer dan :value items bevatten.',
        'file' => ':Attribute moet :value kilobytes of kleiner zijn.',
        'numeric' => ':Attribute moet :value of kleiner zijn.',
        'string' => ':Attribute mag niet meer dan :value tekens bevatten.',
    ],
    'max' => [
        'array' => ':Attribute mag niet meer dan :max items bevatten.',
        'file' => ':Attribute mag niet groter zijn dan :max kilobytes.',
        'numeric' => ':Attribute mag niet groter zijn dan :max.',
        'string' => ':Attribute mag niet langer zijn dan :max tekens.',
    ],
    'mimes' => ':Attribute moet een bestand zijn van het type: :values.',
    'min' => [
        'array' => ':Attribute moet minimaal :min items bevatten.',
        'file' => ':Attribute moet minimaal :min kilobytes zijn.',
        'numeric' => ':Attribute moet minimaal :min zijn.',
        'string' => ':Attribute moet minimaal :min tekens lang zijn.',
    ],
    'not_in' => 'De gekozen :attribute is ongeldig.',
    'not_regex' => 'Het formaat van :attribute is ongeldig.',
    'numeric' => ':Attribute moet een getal zijn.',
    'password' => [
        'letters' => ':Attribute moet minimaal één letter bevatten.',
        'mixed' => ':Attribute moet minimaal één hoofdletter en één kleine letter bevatten.',
        'numbers' => ':Attribute moet minimaal één cijfer bevatten.',
        'symbols' => ':Attribute moet minimaal één leesteken bevatten.',
        'uncompromised' => 'Dit :attribute komt voor in een datalek. Kies een ander :attribute.',
    ],
    'present' => ':Attribute moet aanwezig zijn.',
    'regex' => 'Het formaat van :attribute is ongeldig.',
    'required' => ':Attribute is verplicht.',
    'required_if' => ':Attribute is verplicht als :other :value is.',
    'required_unless' => ':Attribute is verplicht tenzij :other in :values staat.',
    'required_with' => ':Attribute is verplicht als :values is ingevuld.',
    'required_with_all' => ':Attribute is verplicht als :values zijn ingevuld.',
    'required_without' => ':Attribute is verplicht als :values niet is ingevuld.',
    'required_without_all' => ':Attribute is verplicht als geen van :values is ingevuld.',
    'same' => ':Attribute en :other moeten overeenkomen.',
    'size' => [
        'array' => ':Attribute moet :size items bevatten.',
        'file' => ':Attribute moet :size kilobytes zijn.',
        'numeric' => ':Attribute moet :size zijn.',
        'string' => ':Attribute moet :size tekens lang zijn.',
    ],
    'starts_with' => ':Attribute moet beginnen met een van de volgende waarden: :values.',
    'string' => ':Attribute moet tekst zijn.',
    'timezone' => ':Attribute moet een geldige tijdzone zijn.',
    'unique' => 'Dit :attribute is al in gebruik.',
    'uploaded' => 'Het uploaden van :attribute is mislukt.',
    'uppercase' => ':Attribute mag alleen hoofdletters bevatten.',
    'url' => ':Attribute moet een geldige URL zijn.',
    'uuid' => ':Attribute moet een geldige UUID zijn.',

    /*
    |--------------------------------------------------------------------------
    | Eigen meldingen
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'email' => [
            'unique' => 'Er bestaat al een account met dit e-mailadres. Log in of kies "Wachtwoord vergeten".',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Veldnamen
    |--------------------------------------------------------------------------
    |
    | Nederlandse namen voor de velden, zodat er bijvoorbeeld "E-mailadres is
    | verplicht" staat in plaats van "email is verplicht".
    |
    */

    'attributes' => [
        'firstname' => 'voornaam',
        'lastname' => 'achternaam',
        'email' => 'e-mailadres',
        'password' => 'wachtwoord',
        'password_confirmation' => 'wachtwoordbevestiging',
        'current_password' => 'huidig wachtwoord',
        'phonenumber' => 'telefoonnummer',
        'birthday' => 'geboortedatum',
        'employee_id' => 'tandarts',
        'street' => 'straat',
        'house_number' => 'huisnummer',
        'postcode' => 'postcode',
        'city' => 'plaats',
        'code' => 'code',
        'recovery_code' => 'herstelcode',
    ],

];
