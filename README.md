# Szamlazz.hu SzamlaAgent API (Laravel/Lumen)

TODO: composer package.

Documentation: https://docs.szamlazz.hu

Changelog: https://docs.szamlazz.hu/changelog.html

## Install

Edit composer.json

```json
"repositories": [
    {
        "type": "package",
        "package": {
            "name": "versenyhajo/szamla-agent",
            "version": "0.1.0",
            "source": {
                "url": "https://github.com/versenyhajo/szamla-agent.git",
                "type": "git",
                "reference": "master"
            }
        }
    }
],
"require": {
    "versenyhajo/szamla-agent": "@dev"
},
"autoload": {
    "psr-4": {
        "SzamlaAgent\\": "vendor/versenyhajo/szamla-agent/src"
    },
},
```

## Config


Create the following directories and check their permissions and add new config file to your laravel/lumen project:

```php
# config/szamlazzHu.php
<?php

return [
    'pdfFilePath' => storage_path() . '/app/invoices',
    'logFilePath' => storage_path() . '/logs/szamlazz_hu/logs/',
    'xmlFilePath' => storage_path() . '/logs/szamlazz_hu/xmls/',
];
```

## Usage

```php
use SzamlaAgent\SzamlaAgentAPI;

$szamlaAgentAPI = SzamlaAgentAPI::create($apiKey);
$szamlaAgentResponse = $szamlaAgentAPI->generateInvoice($szamlaAgentInvoice);

return $szamlaAgentResponse->getDocumentNumber();
```