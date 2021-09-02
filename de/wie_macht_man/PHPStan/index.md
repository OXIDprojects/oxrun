---
title: Wie richtet man PHPStan ein?
---

Damit PHPStan funktioniert muss dieses Tool wissen wie die `_parent` klassen lauten von den
Modulen

## Befehl

`oe-console misc:phpstorm:metadata`

#### Erklärung

Dieser Befehl liest alle `metadata.php` und baut die Klassen zusammen. Diese werden gespeichert
in `.phpstorm.meta.php/oxid_module_chain.meta.php`

Neben Effect: die Autocompletion von der IDE funktioniert bei diesen Klassen.

#### Tip: `./vendor/bin/oxid-ligth misc:phpstorm:metadata` funktioniert ohne Datenbank.

## PHPStan Einrichten

1. Einmal wird eine PHP Bootstrap Datei, für PHPstan, gebraucht.
1. PHPstan muss gewisse Ordner vorher durchsuchen

### Schritt 1.

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
    bootstrapFiles:
        - .phpstan/oxid-module-chain.php
    paths:
        - "source/modules/"
    level: max
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
        - vendor/oxid-esales/oxideshop-ee/Core/Smarty/Plugin
        - vendor/oxid-esales/oxideshop-unified-namespace-generator/generated/
```
