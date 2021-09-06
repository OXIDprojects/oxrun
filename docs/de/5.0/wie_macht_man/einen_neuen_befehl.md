---
layout: post
title: Wie wird ein neuer Befehl erstellt?
---

Mit OXID 6.2 wurde der [service container](https://docs.oxid-esales.com/developer/en/6.2/development/tell_me_about/service_container.html)
eingeführt. Darüber wird der neue Befehl registriert.

## Befehl

`oe-console misc:register:command` [--help](https://github.com/OXIDprojects/oxrun#miscregistercommand)

### Vorlage

Mit diesem Code Beispiel kann man einen neuen Befehl programmieren: [example/HelloWorldCommand.php](https://github.com/OXIDprojects/oxrun/blob/master/example/HelloWorldCommand.php)


### Grundlage

Eine dieser drei möglichkeiten gibt es, um deinen neuen Befehl zu registieren.

1. Im Projekt die `var/configuration/configurable_services.yaml` bearbeiten.
1. oder in deinem Modul die `services.yaml` bearbeiten.
1. oder mit eine [OXID eShop Component](https://docs.oxid-esales.com/developer/en/6.2/development/modules_components_themes/component.html).

In der passende YAML datei wird folgender code, als Beispiel, hinterlegt:

```yaml
    services:
      OxidEsales\DemoComponent\Command\HelloWorldCommand:
        tags:
          - { name: 'console.command' }
```

### Erklärung

Dieser oxrun befehl `misc:register:command` durchsucht einen Ordner, wo die PHP Scripte hinterlegt
sind, die als `Command` dienen.
Dann werden die analysiert und in der Yaml hinzugefügt.

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
