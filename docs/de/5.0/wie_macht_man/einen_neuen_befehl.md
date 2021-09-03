---
layout: post
title: Wie wird ein neuer Befehl erstellt?
---

Mit OXID 6.2 wurde der [service container](https://docs.oxid-esales.com/developer/en/6.2/development/tell_me_about/service_container.html)
eingeführt. Darüber wird der neue Befehl registriert.

## Befehl

`oe-console misc:register:command`

### Grundlage

Eine dieser möglichkeiten gibt es, um deinen Befehl zu registieren.

1. Im Projekt die `var/configuration/configurable_services.yaml` erweitern.
1. oder in deinem Modul die `services.yaml` erweitern.
1. oder eine [OXID eShop Component](https://docs.oxid-esales.com/developer/en/6.2/development/modules_components_themes/component.html) erstellen.

In der passende YAML datei wird folgender code, als Beispiel, hinterlegt:

```yaml
    services:
      OxidEsales\DemoComponent\Command\HelloWorldCommand:
        tags:
          - { name: 'console.command' }
```

Vorlage für einen Befehl: [HelloWorldCommand.php](https://gist.github.com/TumTum/3cc3ef5b79bbe2baca2ad4532beea592#file-helloworldcommand-php)


### Erklärung

Dieser oxrun befehl `misc:register:command` durchsucht einen Ordner, wo die PHP Scripte hinterlegt
sind, die als `Command` dienen.
Dann werden die analysiert und in der Yaml hinzugefügt.

**WICHTIG: Cache löschen danach, um den Command zu sehen.**


#### Beispiel 1

Man definiert im Projekt einen Ort wo die Befehle liegen können z.B. `./var/projectCommands/`

```yaml
/oxid-esale
├── source
├── var
│ └── projectCommands
│     ├── MeinCommand.php
│     ├── ZweiterCommand.php
│     └── ...
└── vendor
```

Dann lautet der Befehl:
> `oe-console misc:register:command var/projectCommands/`

Dieser würde `MeinCommand.php` und `ZweiterCommand.php` in der `configurable_services.yaml` hinterlegen.

#### Beispiel 2

Die Befehle können in einem Modul liegen. Voraussetzung die Klassen liegen im
Ordner `Commands/` zum beispiel: `source/modules/tm/MeinModul/Commands/`

```
/oxid-esale
├── source
│ └── modules
│     └── tm
│         └── MeinModul
│             ├── Commands
│             │ ├── MeinCommand.php
│             │ └── ZweiterCommand.php
│             └── services.yaml
├── var
└── vendor
```

Dann lautet der Befehl:
> `oe-console misc:register:command --isModule tm/MeinModul`

Dieser würde `MeinCommand.php` und `ZweiterCommand.php` 
in der `source/modules/tm/MeinModul/services.yaml` hinterlegen.
