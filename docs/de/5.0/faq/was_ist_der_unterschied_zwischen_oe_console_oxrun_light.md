---
layout: post
title: Was ist der Unterschied zwischen `oe-console`, `oxrun-light` und `oxrun`?
---

## oe-console

`./vendor/bin/oe-console`

Ist das das offizelle command line tool von OXID eSale. Das oxrun package erweitert diese oe-console.
Daher sind alle oxrun command in `./vendor/bin/oe-console` enthalten.

## oxrun-light

`./vendor/bin/oxrun-light`

Die `oe-console` funktioniert nur wenn eine aktive Datenbank eingerichtet und richtig konfiguriert ist.
Sollte dies nicht der fall sein, können gar keine Befehle mehr ausgeführt werden.

`oxrun-light` beinhaltet Befehle die von der Datenbank verbindung unabhängig sind. Zum Beispiel das 
`cache:clear` tool. Es funktioniert auch dann, wenn es fehler beim Bauen des DI Container gibt.
(Weil man evt. eine `__construct()` funktion verändert hat, von einem Service. 
Ja dann muss der Cache gelehrt werden. Da in diesem Moment die `oe-console` nicht funktioniert)

Daher kann es manchmal hilfreich sein `./vendor/bin/oxrun-light cache:clear` auszuführen.
Sollte wieder mal die `oe-console` nicht funktionieren.

Für Enterprise Setups zu wissen: ./vendor/bin/oxrun-light hat keine Verbindung zur Datenbank,
daher kann mit diesem Tool den hauseigen EE-Cache nicht lehren. 
Das Geht dann wieder nur über den `oe-console cache:clear`.

## oxrun

`./vendor/bin/oxrun`

war der frühere Befehl in `< v4.x.x` aus BC gründe, ist es nun ein alias auf `./vendor/bin/oe-console`
Somit sind `oe-console` und `oxrun` identisch.
