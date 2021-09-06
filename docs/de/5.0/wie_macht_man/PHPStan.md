---
layout: post
title: Wie richtet man PHPStan ein?
---

Damit PHPStan funktioniert muss dieses Tool wissen, von den
Modulen, wie die `_parent` Klassen erweitert werden. 

## Befehl

`oe-console misc:phpstorm:metadata` [--help](https://github.com/OXIDprojects/oxrun#miscphpstormmetadata)

#### Erklärung

Dieser Befehl liest alle `metadata.php` und baut die Klassen zusammen. Dies wird gespeichert
in `.phpstorm.meta.php/oxid_module_chain.meta.php`

Neben Effect: die Autocompletion von der IDE funktioniert beim Schreiben der Klassen.

#### Tip: `./vendor/bin/oxrun-light misc:phpstorm:metadata` funktioniert ohne Datenbank.

## PHPStan Einrichten

1. Erstmal wird eine PHP Bootstrap Datei, für PHPstan, gebraucht. Diese liest die vererbungen ein.
1. Zudem muss PHPstan gewisse Ordner vorher durchsuchen.

### Schritt 1. PHP Bootstrap Datei

Dateiname: `.phpstan/oxid-module-chain.php`

```php
<?php

declare(strict_types=1);

//OXID Module Chain erstellen.
$oxidModuleExtend = __DIR__ . '/../.phpstorm.meta.php/oxid_module_chain.meta.php';
if (!file_exists($oxidModuleExtend)) {
    throw new Exception("Bitte starte `oe-console misc:phpstorm:metadata`");
}

include $oxidModuleExtend;

```

### Schritt 2. PHPstan einrichten

Dateiname: `phpstan.neon`

```yaml
includes:
    - "vendor/jangregor/phpstan-prophecy/extension.neon"
    - "vendor/phpstan/phpstan-phpunit/extension.neon"
    - "vendor/phpstan/phpstan-phpunit/rules.neon"

parameters:
    level: max
    bootstrapFiles:
        - .phpstan/oxid-module-chain.php
    paths:
        - "source/modules/"
    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
    reportUnmatchedIgnoredErrors: false
    scanFiles:
        - .ide-helper.php
        - source/modules/functions.php
        - vendor/oxid-esales/oxideshop-ce/source/oxfunctions.php
        - vendor/oxid-esales/oxideshop-ce/source/overridablefunctions.php
        - vendor/oxid-esales/oxideshop-ce/source/Core/Model/BaseModel.php
    scanDirectories:
        - vendor/oxid-esales/oxideshop-ce/source/Core/Smarty/Plugin
        - vendor/oxid-esales/oxideshop-unified-namespace-generator/generated/
        # - vendor/oxid-esales/oxideshop-ee/Core/Smarty/Plugin # (Optional)
```
