# Oxrun

[![Build Status](https://travis-ci.org/OXIDprojects/oxrun.svg?branch=master)](https://travis-ci.org/OXIDprojects/oxrun)
[![Coverage Status](https://coveralls.io/repos/github/OXIDprojects/oxrun/badge.svg?branch=master)](https://coveralls.io/github/OXIDprojects/oxrun?branch=master)

Oxrun provides a cli toolset for the OXID eShop Community Edition.

Thanks to the [netz98 magerun](https://github.com/netz98/n98-magerun) project which heavily inspired oxrun.

Copyright (c) 2015 Marc Harding http://www.marcharding.de (https://github.com/marcharding/oxrun)

Copyright (c) 2019 Tobias Matthaiou http://www.tobimat.eu

Copyright (c) 2018 Stefan Moises https://www.rent-a-hero.de/

## Installation

`composer require oxidprojects/oxrun`.

- PHP >=7.1 is required.
- OXID eShop >= CE v6.5 is required.

# Usage

In your Installation Direction `./vendor/bin/oe-console`

# Defining your own command

OXID has now published the [service container](https://docs.oxid-esales.com/developer/en/6.2/development/tell_me_about/service_container.html).
In a `services.yaml` can a commands be registered.

There are several ways to do this.

1. create your own [OXID eShop Component](https://docs.oxid-esales.com/developer/en/6.2/development/modules_components_themes/component.html),
1. or use the `services.yaml` in your module,
1. or in the `var/configuration/configurable_services.yaml`

With the command `misc:register:command` you can edit the service yaml.

That's how looks

```yaml
    services:
      OxidEsales\DemoComponent\Command\HelloWorldCommand:
        tags:
          - { name: 'console.command' }
```

[Template for your command](example/HelloWorldCommand.php)

Example: [services.yaml](services.yaml)

Available commands
==================
