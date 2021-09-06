---
layout: post
title: Wie erhält man Module im griff?
---

Wie wird man Herr über alle Module im Shop?

Zum Beispiel möchte man:

- neue Module registrieren in der Shop-Konfigurationen.
- Module nur in bestimmt Umgebungen (Dev/Stage/Prod) aktiviert haben.
- Module sollen nur in bestimmte Shops/Mandate aktive sein in der EE Version.
- Nicht alle Module sollen aktive sein der Rest schon.
- Sicherstellen das nur diese beschrieben Module aktive bzw. deaktiv sind.
- eine Priorisierung einstellen.

## Befehl

`oe-console deploy:module-activator` [--help](https://github.com/OXIDprojects/oxrun#deploymodule-activator)

## Beschreibung

Mit dem `deploy:module-activator` kann man über eine Yaml Datei die Module steuern und 
bestimmen welches Modul wo aktiviert werden soll.

Bei jedem aufruf wird überprüft, ob die Module so entsprechend wie es in der Yaml hinterlegt ist.
Dann werden die Module automatisch aktiv bsw. deaktiviert, damit es passt. Aber auch automatisch registriert 
in der Shop-Konfiguration (_var/configuration/shops/*.yaml_).

_Ideal für automatisches deployment._

## Warum braucht man diesen Befehl?

Jetzt fragt man sich ja: aber warum braucht man diesen Befehl, da es seit v6.2 die `var/configuration/shops/*.yaml`
gibt. In dieser wird beschrieben, ob ein Modul aktiv sein soll.

### Vorteile:

* Mit einer Zeile kann ein Modul aktiviert werden. Dadurch hat man einen guten überblick.
* Ist das Modul nicht in `var/configuration/shops/*.yaml` aufgenommen wird es automatisch hinzugefügt.
* man kann dadurch sicherstellen, dass die richtigen Module aktive sind. Besonderes interessant für die EE Version.
* Es kann gesteuert werden in welcher Umgebung die Module aktiv sein sollen.

## Beispiel

So sieht die Yaml Datei aus. 
Im Shop 1 sind zwei Module aktiv und in Shop 2 nur eines. Bei einer OXID EE Version.

```yaml
whitelist:
    1:
        - ocb_cleartmp
        - moduleinternals
    2:
        - ocb_cleartmp
```

Es geht auch anderes herum. Dieses bedeutet das alle Module aktiviert werden sollen, jedoch __nicht__
das Module `moduleinternals`.

```yaml
blacklist:
    1:
        - moduleinternals
```

## Anwendung

1. Mit dem `oe-console deploy:generate:module-activator` kann man den aktuellen stand
    in der besagten YAML Datei speichern lassen.
   
1. und mit `oe-console deploy:module-activator` wird diese wieder eingelesen..

Die erzeugte YAML datei darf man manuel bearbeiten. Wenn man neue Module hinzufügen möchte oder 
deaktivieren möchte.

#### Speicher Ort

Die erzeugte YAML dateien werden unter `var/oxrun_config/` gespeichert. Wobei `dev_module.yml`
der default Name ist.

### Best Praxis

Man kann mit [deploy:generate:module-activator](https://github.com/OXIDprojects/oxrun#deploygeneratemodule-activator) jeweils eine Konfiguration abspeichern für 
die umgebungen _prod_, _stage_ und _dev_. Dann in den Umgebungen die passende YAML wieder einlesen.

```shell
oe-console deploy:generate:module-activator --configfile=prod_module.yml && \
oe-console deploy:generate:module-activator --configfile=stage_module.yml && \
oe-console deploy:generate:module-activator --configfile=dev_module.yml
```

In der **Prod Umgebung**  ruft man `oe-console deploy:module-activator prod_module.yml` auf.
Genauso macht man es auch in den anderen umgebungen, mit jeweils `stage_module.yml` oder `dev_module.yml`.
