# VAT ID Info for Greek Business

This class implements the latest version of Greek Tax Registry VAT ID
information service. This service provides information for VAT IDs registered as business **only** (no information for private individuals) and you have to get some extra credentials first from [this url](https://www.aade.gr/epicheireseis/phorologikes-yperesies/metroo/anazetese-basikon-stoicheion-metrooy-epicheireseon).

Two features were added:

1. Abilty to check VAT Status (normal or exempt), useful for Article 39A
2. Abilty to check status in some specific date in the past (max 3 years)

## Installation

Install the package using Composer:

```
composer require liagkos/vatidinfo
```

## Usage

```php
try {
    $client = new Liagkos\Taxis\Vatidinfo('Username-Token', 'Password-Token');
} catch (Exception $e) {
    echo $e->getMessage();  // Or whatever for SOAP error (NOT authentication error)
    die($e->getCode());     // Or whatever for SOAP error (NOT authentication error)
}

$params = [
    'method'    => 'query',
    'afmFor'    => '123456789',
    'afmFrom'   => '999999999',
    'lookDate'  => '2017-12-31',
    'type'      => 'array',
    'separator' => '-'
];

$reply = $client->exec($params);
```

## Parameters explained
- method
    - query: Normal operation, ask for VAT ID information `(default)`
    - info: Get some service related information
- afmFor: If method is `query` this is the VAT ID you **must** pass and the one you will get information for
- afmFrom: VAT ID of the final user using the service. If omitted, the service will think that the user is the user who has logged in to the service in the construcor `(default)`. Otherwise, if a person or a company has **authorised** the user to make queries on their behalf (a company by itself is not allowed to make queries), then you **must** pass here the VAT ID of the authorisee.
- lookDate: Reference date in format YYYY-MM-DD. If omitted, the service will just return the latest information `(default)`.
- separator: Activities are additionally formatted in groups of 2 digits separated by this separator. If no value is passed, a dot will be used `(default)` like 11.22.33.44.
- type: `Default` reply type is `json` formatted. If you prefer an associative array, set this value to `array` or anything else.

## Reply example

```json
{
  "success":true,
  "data":{
    "found":true,
    "queryid":"863209758",
    "errors":false,
    "caller":{
      "user":{
        "username":"USERNAME",
        "fullname":"ΠΑΠ*** ΓΕΩ*** του ΙΩΑ***",
        "vatid":"123456789"
      },
      "owner":{
        "fullname":"ΠΑΠ*** ΓΕΩ*** του ΙΩΑ***",
        "vatid":"999999999"
      }
    },
    "data":{
      "dateShown":{
        "date":"2018-07-11 00:00:00.000000",
        "timezone_type":3,
        "timezone":"UTC"
      },
      "name":"ΔΗΜΟΣΙΑ ΕΠΙΧΕΙΡΗΣΗ ΗΛΕΚΤΡΙΣΜΟΥ ΑΝΩΝΥΜΗ ΕΤΑΙΡΙΑ",
      "title":"Δ.Ε.Η. Α.Ε.   -  Δ.Ε.Η.",
      "vatid":"090000045",
      "doyID":"1159",
      "doyName":"Φ.Α.Ε. ΑΘΗΝΩΝ",
      "address":{
        "street":"ΧΑΛΚΟΚΟΝΔΥΛΗ",
        "number":"30",
        "city":"ΑΘΗΝΑ",
        "zip":"10432"
      },
      "isWhat":"ΜΗ ΦΠ",
      "isCompany":true,
      "companyType":"ΑΕ",
      "isActive":true,
      "isActiveTxt":"ΕΝΕΡΓΟΣ ΑΦΜ",
      "type":"ΕΠΙΤΗΔΕΥΜΑΤΙΑΣ",
      "regDate":{
        "date":"1900-01-01 00:00:00.000000",
        "timezone_type":3,
        "timezone":"UTC"
      },
      "stopDate":false,
      "normalVat":true,
      "activities":{
        "1":{
          "descr":"ΚΥΡΙΑ",
          "items":[
            {
              "code":35141000,
              "descr":"ΥΠΗΡΕΣΙΕΣ ΕΜΠΟΡΙΟΥ (ΠΩΛΗΣΗΣ) ΗΛΕΚΤΡΙΚΟΥ ΡΕΥΜΑΤΟΣ",
              "formatted":"35.14.10.00"
            }
          ]
        },
        "2":{
          "descr":"ΔΕΥΤΕΡΕΥΟΥΣΑ",
          "items":[
            {
              "code":5200000,
              "descr":"ΕΞΟΡΥΞΗ ΛΙΓΝΙΤΗ",
              "formatted":"52.00.00.0"
            },
            {
              "code":35111000,
              "descr":"ΠΑΡΑΓΩΓΗ ΗΛΕΚΤΡΙΚΟΥ ΡΕΥΜΑΤΟΣ",
              "formatted":"35.11.10.00"
            },
            {
              "code":35111001,
              "descr":"ΠΑΡΑΓΩΓΗ ΗΛΕΚΤΡΙΚΗΣ ΕΝΕΡΓΕΙΑΣ ΑΠΟ ΑΕΡΟΣΤΡΟΒΙΛΙΚΕΣ ΜΟΝΑΔΕΣ ΠΕΤΡΕΛΑΙΟΥ",
              "formatted":"35.11.10.01"
            },
            {
              "code":35111002,
              "descr":"ΠΑΡΑΓΩΓΗ ΗΛΕΚΤΡΙΚΗΣ ΕΝΕΡΓΕΙΑΣ ΑΠΟ ΛΙΓΝΙΤΙΚΕΣ ΜΟΝΑΔΕΣ",
              "formatted":"35.11.10.02"
            },
            {
              "code":35111005,
              "descr":"ΠΑΡΑΓΩΓΗ ΗΛΕΚΤΡΙΚΗΣ ΕΝΕΡΓΕΙΑΣ ΑΠΟ ΜΟΝΑΔΕΣ ΦΥΣΙΚΟΥ ΑΕΡΙΟΥ",
              "formatted":"35.11.10.05"
            },
            {
              "code":35111007,
              "descr":"ΠΑΡΑΓΩΓΗ ΗΛΕΚΤΡΙΚΗΣ ΕΝΕΡΓΕΙΑΣ ΑΠΟ ΣΤΑΘΜΟΥΣ ΕΣΩΤΕΡΙΚΗΣ ΚΑΥΣΗΣ",
              "formatted":"35.11.10.07"
            },
            {
              "code":35111008,
              "descr":"ΠΑΡΑΓΩΓΗ ΗΛΕΚΤΡΙΚΗΣ ΕΝΕΡΓΕΙΑΣ ΑΠΟ ΥΔΡΟΗΛΕΚΤΡΙΚΟΥΣ ΣΤΑΘΜΟΥΣ",
              "formatted":"35.11.10.08"
            },
            {
              "code":35121000,
              "descr":"ΥΠΗΡΕΣΙΕΣ ΜΕΤΑΔΟΣΗΣ ΗΛΕΚΤΡΙΚΟΥ ΡΕΥΜΑΤΟΣ",
              "formatted":"35.12.10.00"
            },
            {
              "code":35131000,
              "descr":"ΥΠΗΡΕΣΙΕΣ ΔΙΑΝΟΜΗΣ ΗΛΕΚΤΡΙΚΟΥ ΡΕΥΜΑΤΟΣ",
              "formatted":"35.13.10.00"
            },
            {
              "code":77401901,
              "descr":"ΥΠΗΡΕΣΙΕΣ ΜΕΤΑΒΙΒΑΣΗΣ Η ΠΑΡΑΧΩΡΗΡΗΣ ΧΡΗΣΗΣ ΑΥΛΩΝ ΑΓΑΘΩΝ (ΔΙΚΑΙΩΜΑΤΩΝ ΠΝΕΥΜΑΤΙΚΗΣ ΙΔΙΟΚΤΗΣΙΑΣ, ΔΙΚΑΙΩΜΑΤΩΝ ΕΚΠΟΜΠΗΣ ΑΕΡΙΩΝ ΘΕΡΜΟΚΗΠΙΟΥ, ΔΙΠΛΩΜΑΤΩΝ ΕΥΡΕΣΙΤΕΧΝΙΑΣ, ΑΔΕΙΩΝ ΕΚΜΕΤΑΛΛΕΥΣΗΣ ΒΙΟΜΗΧΑΝΙΚΩΝ ΚΑΙ ΕΜΠΟΡΙΚΩΝ ΣΗΜΑΤΩΝ ΚΑΙ ΠΑΡΟΜΟΙΩΝ ΔΙΚΑΙΩΜΑΤΩΝ",
              "formatted":"77.40.19.01"
            }
          ]
        }
      }
    }
  }
}
```

Most fields are self explaining, but keep in mind that:
- `success` true means that the SOAP request was completed successfully, no moatter if the VAT ID was found, or the credentials were correct
- `found` true means that we actually got information for this VAT ID
- `errors` is false if no errors or array with keys `code` and `msg` if there was a service error
- `dateShown`, `regDate` and `stopDate` are `DateTime` objects
- `stopDate` will be false if the VAT ID has not stopped its business
- Activities are sorted per type and then per code

## That's all!
I hope you find it useful like I did. If you have any proposals or problems, feel free to contact me!
