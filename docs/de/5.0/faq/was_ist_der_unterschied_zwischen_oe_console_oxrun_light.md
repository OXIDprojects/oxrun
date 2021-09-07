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
Sollte dies nicht der fall sein können gar keine Befehle mehr ausgeführt werden.

`oxrun-light` beinhaltet Befehle die von der Datenbank verbindung unabhängig sind. Zum Beispiel das 
`cache:clear` tool. Es funktioniert auch dann, wenn es fehler beim bauen des DI Container gibt und
dieser muss neu gebaut werden.

Daher kann es manchmal hilfreich sein `./vendor/bin/oxrun-light cache:clear` auszuführen,
wenn `oe-console` mal nicht funktioniert.

Mit anderen worten `oxrun-light` hat tools drinnen die ohne eine aktive Datenbank funktionieren.

## oxrun

`./vendor/bin/oxrun`

war der frühere Befehl in <v4.x aus BC gründe, ist es nun ein alias auf `./vendor/bin/oe-console`
Somit sind `oe-console` und `oxrun` identisch.
