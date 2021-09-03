# Oxrun

[![oxrun ci](https://github.com/OXIDprojects/oxrun/actions/workflows/oxrun.yml/badge.svg?branch=master)](https://github.com/OXIDprojects/oxrun/actions/workflows/oxrun.yml)
[![Coverage Status](https://coveralls.io/repos/github/OXIDprojects/oxrun/badge.svg?branch=master)](https://coveralls.io/github/OXIDprojects/oxrun?branch=master)

Oxrun provides a cli toolset for the OXID eShop Community Edition.

# Documentation

* [Fully Documentation](http://oxidprojects.github.io/oxrun/)
* Documentation for [contributing](README_DEV.md)
* [Changelog](READY_CONVERED_TO_v6.2.md) of command between v4.x to v5.x


## Installation

`composer require oxidprojects/oxrun`.

- PHP >=7.1 is required.
- OXID eShop >= CE v6.5 is required.

After installation manually clear the cache via `rm -rf source/tmp/*` to make all oxrun commands available.

# Usage

In your Installation Direction `./vendor/bin/oe-console`

---

* Copyright (c) 2021 Tobias Matthaiou http://www.tobimat.eu/
* Copyright (c) 2018 Stefan Moises https://www.rent-a-hero.de/
* Copyright (c) 2015 Marc Harding http://www.marcharding.de (https://github.com/marcharding/oxrun)

Available commands
==================

##### cache
  - [cache:clear](#cacheclear)   Clear OXID cache
##### config
  - [config:get](#configget)   Gets a config value
  - [config:multiset](#configmultiset)   
  - [config:set](#configset)   Sets a config value
  - [config:shop:get](#configshopget)   Gets a shop config value
  - [config:shop:set](#configshopset)   Sets a shop config value
##### db
  - [db:anonymize](#dbanonymize)   Anonymize relevant OXID db tables
  - [db:dump](#dbdump)   Create a dump, with mysqldump
  - [db:import](#dbimport)   Import a sql file
  - [db:info](#dbinfo)   Show a Table with size of all Tables
  - [db:list](#dblist)   List of all Tables
  - [db:query](#dbquery)   Executes a query
##### deploy
  - [deploy:config](#deployconfig)   Sets multiple configuration values that are not in module settings
  - [deploy:generate:configuration](#deploygenerateconfiguration)   Generate a yaml with configuration from Database. For command `deploy:config`
  - [deploy:generate:module-activator](#deploygeneratemodule-activator)   Generate a yaml file for command `deploy:module-activator`
  - [deploy:link:environment](#deploylinkenvironment)   Links the environment configration files. Ideal for CI/CD
  - [deploy:module-activator](#deploymodule-activator)   Activates multiple modules, based on a YAML file
  - [deploy:module-apply-configuration-light](#deploymodule-apply-configuration-light)   It the same as `oe:module:apply-configuration` but faster.
  - [deploy:update-module-config](#deployupdate-module-config)   Update the module configuration yaml with the data from the database
##### misc
  - [misc:generate:yaml:config](#miscgenerateyamlconfig)   
  - [misc:generate:yaml:module](#miscgenerateyamlmodule)   
  - [misc:phpstorm:metadata](#miscphpstormmetadata)   Generate a PhpStorm metadata file for auto-completion and a oxid module chain.Ideal for psalm or phpstan
  - [misc:register:command](#miscregistercommand)   Extends the service.yaml file with the commands. So that they are found in oe-console.
##### module
  - [module:generate](#modulegenerate)   Generates a module skeleton
  - [module:list](#modulelist)   Lists all modules
  - [module:multiactivator](#modulemultiactivator)   
  - [module:reload](#modulereload)   Deactivate and activate a module
##### oxid
  - [oxid:shops](#oxidshops)   Lists the shops
##### route
  - [route:debug](#routedebug)   Returns the route. Which controller and parameters are called.
##### user
  - [user:create](#usercreate)   Creates a new user
  - [user:password](#userpassword)   Sets a new password
##### views
  - [views:update](#viewsupdate)   Updates the views


`cache:clear`
-------------

Clear OXID cache

### Usage

* `cache:clear [-f|--force]`

Clear OXID cache

### Options

#### `--force|-f`

Try to delete the cache anyway. [danger or permission denied]

* Accept value: no
* Is value required: no
* Default: `false`

`config:get`
------------

Gets a config value

### Usage

* `config:get [--moduleId [MODULEID]] [--json] [--yaml] [--] <variableName>`

Gets a config value

### Arguments

#### `variableName`

Variable name


### Options

#### `--moduleId`

* Is value required: no
* Default: `''`

#### `--json`

Output as json

* Accept value: no
* Is value required: no
* Default: `false`

#### `--yaml`

Output as YAML (default)

* Accept value: no
* Is value required: no
* Default: `false`

`deploy:config`
---------------

Sets multiple configuration values that are not in module settings

### Usage

* `deploy:config [-f|--force-db] [--production] [--staging] [--development] [--testing] [--] <configfile>`
* `config:multiset`

This command can import settings into the database that are not found in the module settings.
If they are module settings, they are stored in the module configuration yaml, not in the database.

The file path is relative to the shop installation_root_path/var/oxrun_config/.
You can also pass a YAML string on the command line.

To create YAML use command `oe-console deploy:generate:configration --help`

YAML example:
```yaml
environment:
  - "production"
  - "staging"
  - "development"
  - "testing"
config:
  1:
    blReverseProxyActive:
      variableType: bool
      variableValue: false
    sMallShopURL: http://myshop.dev.local
    sMallSSLShopURL: http://myshop.dev.local
  2:
    blReverseProxyActive:
    ...
```
[Example: malls.yml.dist](example/malls.yml.dist)

If you want, you can also specify __a YAML string on the command line instead of a file__, e.g.:

```bash
../vendor/bin/oe-console deploy:config $'config:
  1:
    foobar: barfoo
' --shop-id=1
```

### Arguments

#### `configfile`

The file containing the config values, see example/malls.yml.dist. (e.g. dev.yml, stage.yml, prod.yml)


### Options

#### `--force-db|-f`

Still write everything into the database.

* Accept value: no
* Is value required: no
* Default: `false`

#### `--production`

For "production" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--staging`

For "staging" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--development`

For "development" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--testing`

For "testing" system

* Accept value: no
* Is value required: no
* Default: `false`

`config:set`
------------

Sets a config value

### Usage

* `config:set [--variableType VARIABLETYPE] [--moduleId [MODULEID]] [--] <variableName> <variableValue>`

Sets a config value

### Arguments

#### `variableName`

Variable name


#### `variableValue`

Variable value


### Options

#### `--variableType`

Variable type

* Is value required: yes

#### `--moduleId`

* Is value required: no

`config:shop:get`
-----------------

Gets a shop config value

### Usage

* `config:shop:get <variableName>`

Gets a shop config value

### Arguments

#### `variableName`

Variable name

`config:shop:set`
-----------------

Sets a shop config value

### Usage

* `config:shop:set <variableName> <variableValue>`

Sets a shop config value

### Arguments

#### `variableName`

Variable name


#### `variableValue`

Variable value

`db:anonymize`
--------------

Anonymize relevant OXID db tables

### Usage

* `db:anonymize [--debug] [-d|--domain [DOMAIN]] [-k|--keepdomain [KEEPDOMAIN]]`

Anonymizes user relevant data in the OXID database.
Relevant tables are:
Array
(
    [0] => oxnewssubscribed
    [1] => oxuser
    [2] => oxvouchers
    [3] => oxaddress
    [4] => oxorder
)


### Options

#### `--debug`

Debug SQL queries generated

* Accept value: no
* Is value required: no
* Default: `false`

#### `--domain|-d`

Domain to use for all anonymized usernames /email addresses, default is "@oxrun.com"

* Is value required: no

#### `--keepdomain|-k`

Domain which should NOT be anonymized, default is "@foobar.com". Data with this domain in the email address will NOT be anonymized.

* Is value required: no

`db:dump`
---------

Create a dump, with mysqldump

### Usage

* `db:dump [--file FILE] [-t|--table TABLE] [-i|--ignoreViews] [-a|--anonymous] [-w|--withoutTableData WITHOUTTABLEDATA]`

Create a dump from the current database.

usage:

    oe-console db:dump --withoutTableData oxseo,oxvou%
    - To dump all Tables, but `oxseo`, `oxvoucher`, and `oxvoucherseries` without data.
      possibilities: oxseo%,oxuser,%logs%

    oe-console db:dump --table %user%
    - to dump only those tables `oxuser` `oxuserbasketitems` `oxuserbaskets` `oxuserpayments`

    oe-console db:dump --anonymous # Perfect for Stage Server
    - Those table without data: `oxseo`, `oxseologs`, `oxseohistory`, `oxuser`, `oxuserbasketitems`, `oxuserbaskets`, `oxuserpayments`, `oxnewssubscribed`, `oxremark`, `oxvouchers`, `oxvoucherseries`, `oxaddress`, `oxorder`, `oxorderarticles`, `oxorderfiles`, `oepaypal_order`, `oepaypal_orderpayments`.

    oe-console db:dump -v
    - With verbose mode you will see the mysqldump command
      (`mysqldump -u 'root' -h 'oxid_db' -p ... `)

    oe-console db:dump --file dump.sql 
    - Put the Output into a File

** Only existing tables will be exported. No matter what was required.

## System requirement:

    * php
    * MySQL CLI tools.


### Options

#### `--file`

Save dump at this location.

* Is value required: yes

#### `--table|-t`

Only names of tables are dumped. Default all tables. Use comma separated list and or pattern e.g. %voucher%

* Is value required: yes

#### `--ignoreViews|-i`

Ignore views

* Accept value: no
* Is value required: no
* Default: `false`

#### `--anonymous|-a`

Export not table with person related data.

* Accept value: no
* Is value required: no
* Default: `false`

#### `--withoutTableData|-w`

Export tables only with their CREATE statement. So without content. Use comma separated list and or pattern e.g. %voucher%

* Is value required: yes

`db:import`
-----------

Import a sql file

### Usage

* `db:import <file>`

Imports an SQL file on the current shop database.

Requires php exec and MySQL CLI tools installed on your system.

### Arguments

#### `file`

The sql file which is to be imported

`db:info`
---------

Show a Table with size of all Tables

### Usage

* `db:info [--tableSize] [--databaseSize]`

Show a Table with size of all Tables

### Options

#### `--tableSize`

Size of all Tables

* Accept value: no
* Is value required: no
* Default: `false`

#### `--databaseSize`

Size of the Databases

* Accept value: no
* Is value required: no
* Default: `false`

`db:list`
---------

List of all Tables

### Usage

* `db:list [-p|--plain] [-t|--pattern PATTERN]`

List Tables

usage:
    oe-console db:list --pattern oxseo%,oxuser
    - To dump all Tables, but `oxseo`, `oxvoucher`, and `oxvoucherseries` without data.
      possibilities: oxseo%,oxuser,%logs%



### Options

#### `--plain|-p`

print list as comma separated.

* Accept value: no
* Is value required: no
* Default: `false`

#### `--pattern|-t`

table name pattern test. e.g. oxseo%,oxuser

* Is value required: yes

`db:query`
----------

Executes a query

### Usage

* `db:query [--raw] [--] <query>`

Executes an SQL query on the current shop database. Wrap your SQL in quotes.

If your query produces a result (e.g. a SELECT statement), the output will be returned via the table component. Add the raw option for raw output.

Requires php exec and MySQL CLI tools installed on your system.

### Arguments

#### `query`

The query which is to be executed


### Options

#### `--raw`

Raw output

* Accept value: no
* Is value required: no
* Default: `false`

`deploy:config`
---------------

Sets multiple configuration values that are not in module settings

### Usage

* `deploy:config [-f|--force-db] [--production] [--staging] [--development] [--testing] [--] <configfile>`
* `config:multiset`

This command can import settings into the database that are not found in the module settings.
If they are module settings, they are stored in the module configuration yaml, not in the database.

The file path is relative to the shop installation_root_path/var/oxrun_config/.
You can also pass a YAML string on the command line.

To create YAML use command `oe-console deploy:generate:configration --help`

YAML example:
```yaml
environment:
  - "production"
  - "staging"
  - "development"
  - "testing"
config:
  1:
    blReverseProxyActive:
      variableType: bool
      variableValue: false
    sMallShopURL: http://myshop.dev.local
    sMallSSLShopURL: http://myshop.dev.local
  2:
    blReverseProxyActive:
    ...
```
[Example: malls.yml.dist](example/malls.yml.dist)

If you want, you can also specify __a YAML string on the command line instead of a file__, e.g.:

```bash
../vendor/bin/oe-console deploy:config $'config:
  1:
    foobar: barfoo
' --shop-id=1
```

### Arguments

#### `configfile`

The file containing the config values, see example/malls.yml.dist. (e.g. dev.yml, stage.yml, prod.yml)


### Options

#### `--force-db|-f`

Still write everything into the database.

* Accept value: no
* Is value required: no
* Default: `false`

#### `--production`

For "production" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--staging`

For "staging" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--development`

For "development" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--testing`

For "testing" system

* Accept value: no
* Is value required: no
* Default: `false`

`deploy:generate:configuration`
-------------------------------

Generate a yaml with configuration from Database. For command `deploy:config`

### Usage

* `deploy:generate:configuration [-u|--update] [-c|--configfile CONFIGFILE] [--oxvarname OXVARNAME] [--oxmodule OXMODULE] [-d|--no-descriptions] [-l|--language LANGUAGE] [--list] [--production] [--staging] [--development] [--testing]`
* `misc:generate:yaml:config`

Configration that is not included in the modules can be saved. With the command: deploy:config they can be read again

### Options

#### `--update|-u`

Update an exited config file, with data from DB

* Accept value: no
* Is value required: no
* Default: `false`

#### `--configfile|-c`

The config file to update or create if not exits

* Is value required: yes
* Default: `'dev_config.yml'`

#### `--oxvarname`

Dump configs by oxvarname. One name or as comma separated List

* Is value required: yes

#### `--oxmodule`

Dump configs by oxmodule. One name or as comma separated List

* Is value required: yes

#### `--no-descriptions|-d`

No descriptions are added.

* Accept value: no
* Is value required: no
* Default: `false`

#### `--language|-l`

Speech selection of the descriptions.

* Is value required: yes
* Default: `0`

#### `--list`

list all saved configrationen

* Accept value: no
* Is value required: no
* Default: `false`

#### `--production`

For "production" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--staging`

For "staging" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--development`

For "development" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--testing`

For "testing" system

* Accept value: no
* Is value required: no
* Default: `false`

`deploy:generate:module-activator`
----------------------------------

Generate a yaml file for command `deploy:module-activator`

### Usage

* `deploy:generate:module-activator [-c|--configfile CONFIGFILE] [-w|--whitelist] [-b|--blacklist]`
* `misc:generate:yaml:module`

Generate a yaml file for command `deploy:module-activator`

### Options

#### `--configfile|-c`

The Config file to change or create if not exits

* Is value required: yes
* Default: `'dev_module.yml'`

#### `--whitelist|-w`

Takes modules that are always activated. All others remain deactive.

* Accept value: no
* Is value required: no
* Default: `false`

#### `--blacklist|-b`

Takes modules that always need to be disabled. All others are activated.

* Accept value: no
* Is value required: no
* Default: `false`

`deploy:link:environment`
-------------------------

Links the environment configration files. Ideal for CI/CD

### Usage

* `deploy:link:environment [--rm] [--production] [--staging] [--development] [--testing]`

In files structure you has multiple files per shop in var/configuration/environment directory. e.g. production.1.yaml, staging.1.yaml
This might be useful when deploying files to some specific environment.
@see: [Modules configuration deployment](https://docs.oxid-esales.com/developer/en/6.2/development/modules_components_themes/project/module_configuration/modules_configuration_deployment.html#dealing-with-environment-files)

### Options

#### `--rm`

Remove the links

* Accept value: no
* Is value required: no
* Default: `false`

#### `--production`

For "production" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--staging`

For "staging" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--development`

For "development" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--testing`

For "testing" system

* Accept value: no
* Is value required: no
* Default: `false`

`deploy:module-activator`
-------------------------

Activates multiple modules, based on a YAML file

### Usage

* `deploy:module-activator [-s|--skipDeactivation] [-d|--clearModuleData] [--] <yaml>`
* `module:multiactivator`

usage:
oe-console deploy:module-activator modules.yml
- to activate all modules defined in the YAML file based
on a white- or blacklist

Example:

```yaml
whitelist:
  1:
    - ocb_cleartmp
    - moduleinternals
   #- ddoevisualcms
   #- ddoewysiwyg
  2:
    - ocb_cleartmp
priorities:
  1:
    moduleinternals:
      1200
   ocb_cleartmp:
      950
```

Supports either a __"whitelist"__ and or a __"blacklist"__ entry with multiple shop ids and the desired module ids to activate (whitelist) or to exclude from activation (blacklist).

With "priorities", you can define the order (per subshop) in which the modules will be activated.

If you want, you can also specify __a YAML string on the command line instead of a file__, e.g.:

```bash
oe-console deploy:module-activator $'whitelist:
  1:
    - oepaypal
' --shop-id=1
```

### Arguments

#### `yaml`

YAML module list filename or YAML string. The file path is relative to /var/www/oxid-esale/var/oxrun_config/


### Options

#### `--skipDeactivation|-s`

Skip deactivation of modules, only activate.

* Accept value: no
* Is value required: no
* Default: `false`

#### `--clearModuleData|-d`

Clear module data in oxconfig.

* Accept value: no
* Is value required: no
* Default: `false`

`deploy:module-apply-configuration-light`
-----------------------------------------

It the same as `oe:module:apply-configuration` but faster.

### Usage

* `deploy:module-apply-configuration-light`

The module configurations will ONLY written into the database.
- Without deactivating or activating the modules
- Without rewrite module configration yaml's

WARNING: If you make changes on metadata.php::controllers|::extend then this command doesn't work.

That automatic activate or deactive module with the param `configured: true|false`.
It the same as `oe:module:apply-configuration` but faster!

`deploy:update-module-config`
-----------------------------

Update the module configuration yaml with the data from the database

### Usage

* `deploy:update-module-config [--production] [--staging] [--development] [--testing]`

Is the reverse command from `oe:module:apply-configuration`.

### Options

#### `--production`

For "production" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--staging`

For "staging" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--development`

For "development" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--testing`

For "testing" system

* Accept value: no
* Is value required: no
* Default: `false`

`deploy:generate:configuration`
-------------------------------

Generate a yaml with configuration from Database. For command `deploy:config`

### Usage

* `deploy:generate:configuration [-u|--update] [-c|--configfile CONFIGFILE] [--oxvarname OXVARNAME] [--oxmodule OXMODULE] [-d|--no-descriptions] [-l|--language LANGUAGE] [--list] [--production] [--staging] [--development] [--testing]`
* `misc:generate:yaml:config`

Configration that is not included in the modules can be saved. With the command: deploy:config they can be read again

### Options

#### `--update|-u`

Update an exited config file, with data from DB

* Accept value: no
* Is value required: no
* Default: `false`

#### `--configfile|-c`

The config file to update or create if not exits

* Is value required: yes
* Default: `'dev_config.yml'`

#### `--oxvarname`

Dump configs by oxvarname. One name or as comma separated List

* Is value required: yes

#### `--oxmodule`

Dump configs by oxmodule. One name or as comma separated List

* Is value required: yes

#### `--no-descriptions|-d`

No descriptions are added.

* Accept value: no
* Is value required: no
* Default: `false`

#### `--language|-l`

Speech selection of the descriptions.

* Is value required: yes
* Default: `0`

#### `--list`

list all saved configrationen

* Accept value: no
* Is value required: no
* Default: `false`

#### `--production`

For "production" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--staging`

For "staging" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--development`

For "development" system

* Accept value: no
* Is value required: no
* Default: `false`

#### `--testing`

For "testing" system

* Accept value: no
* Is value required: no
* Default: `false`

`deploy:generate:module-activator`
----------------------------------

Generate a yaml file for command `deploy:module-activator`

### Usage

* `deploy:generate:module-activator [-c|--configfile CONFIGFILE] [-w|--whitelist] [-b|--blacklist]`
* `misc:generate:yaml:module`

Generate a yaml file for command `deploy:module-activator`

### Options

#### `--configfile|-c`

The Config file to change or create if not exits

* Is value required: yes
* Default: `'dev_module.yml'`

#### `--whitelist|-w`

Takes modules that are always activated. All others remain deactive.

* Accept value: no
* Is value required: no
* Default: `false`

#### `--blacklist|-b`

Takes modules that always need to be disabled. All others are activated.

* Accept value: no
* Is value required: no
* Default: `false`

`misc:phpstorm:metadata`
------------------------

Generate a PhpStorm metadata file for auto-completion and a oxid module chain.Ideal for psalm or phpstan

### Usage

* `misc:phpstorm:metadata [-o|--output-dir OUTPUT-DIR]`

Generate a PhpStorm metadata file for auto-completion and a oxid module chain.Ideal for psalm or phpstan

### Options

#### `--output-dir|-o`

Writes the metadata for PhpStorm to the specified directory.

* Is value required: yes

`misc:register:command`
-----------------------

Extends the service.yaml file with the commands. So that they are found in oe-console.

### Usage

* `misc:register:command [--isModule] [-s|--service-yaml SERVICE-YAML] [-y|--yaml-inline YAML-INLINE] [--] <command-dir>`

Extends the service.yaml file with the commands. So that they are found in oe-console.

### Arguments

#### `command-dir`

The folder where the commands are located or Module with option --isModule


### Options

#### `--isModule`

Just write the Module and the path and the service-yaml will be found automatically.

* Accept value: no
* Is value required: no
* Default: `false`

#### `--service-yaml|-s`

The service.yaml file that will be updated (default: var/configuration/configurable_services.yaml)

* Is value required: yes

#### `--yaml-inline|-y`

The level where you switch to inline YAML

* Is value required: yes
* Default: `4`

`module:generate`
-----------------

Generates a module skeleton

### Usage

* `module:generate [-s|--skeleton SKELETON] [--name NAME] [--vendor VENDOR] [--description DESCRIPTION] [--author AUTHOR] [--email EMAIL]`

Generates a module skeleton

### Options

#### `--skeleton|-s`

Zip of a Oxid Module Skeleton

* Is value required: yes
* Default: `'https://github.com/OXIDprojects/oxid-module-skeleton/archive/v6_module.zip'`

#### `--name`

Module name

* Is value required: yes

#### `--vendor`

Vendor

* Is value required: yes

#### `--description`

Description of your Module: OXID eShop Module ...

* Is value required: yes

#### `--author`

Author of Module

* Is value required: yes

#### `--email`

Email of Author

* Is value required: yes

`module:list`
-------------

Lists all modules

### Usage

* `module:list`

Lists all modules

`deploy:module-activator`
-------------------------

Activates multiple modules, based on a YAML file

### Usage

* `deploy:module-activator [-s|--skipDeactivation] [-d|--clearModuleData] [--] <yaml>`
* `module:multiactivator`

usage:
oe-console deploy:module-activator modules.yml
- to activate all modules defined in the YAML file based
on a white- or blacklist

Example:

```yaml
whitelist:
  1:
    - ocb_cleartmp
    - moduleinternals
   #- ddoevisualcms
   #- ddoewysiwyg
  2:
    - ocb_cleartmp
priorities:
  1:
    moduleinternals:
      1200
   ocb_cleartmp:
      950
```

Supports either a __"whitelist"__ and or a __"blacklist"__ entry with multiple shop ids and the desired module ids to activate (whitelist) or to exclude from activation (blacklist).

With "priorities", you can define the order (per subshop) in which the modules will be activated.

If you want, you can also specify __a YAML string on the command line instead of a file__, e.g.:

```bash
oe-console deploy:module-activator $'whitelist:
  1:
    - oepaypal
' --shop-id=1
```

### Arguments

#### `yaml`

YAML module list filename or YAML string. The file path is relative to /var/www/oxid-esale/var/oxrun_config/


### Options

#### `--skipDeactivation|-s`

Skip deactivation of modules, only activate.

* Accept value: no
* Is value required: no
* Default: `false`

#### `--clearModuleData|-d`

Clear module data in oxconfig.

* Accept value: no
* Is value required: no
* Default: `false`

`module:reload`
---------------

Deactivate and activate a module

### Usage

* `module:reload [-f|--force-cache] [-s|--skip-cache-clear] [-c|--based-on-config BASED-ON-CONFIG] [--] <module>`

Deactivate and activate a module

### Arguments

#### `module`

Module name


### Options

#### `--force-cache|-f`

cache:clear with --force option

* Accept value: no
* Is value required: no
* Default: `false`

#### `--skip-cache-clear|-s`

skip cache:clear command

* Accept value: no
* Is value required: no
* Default: `false`

#### `--based-on-config|-c`

Checks if module is allowed to be reloaded based on the deploy:module-activator yaml file.

* Is value required: yes

`oxid:shops`
------------

Lists the shops

### Usage

* `oxid:shops [-i|--only-ids]`

Lists the shops

### Options

#### `--only-ids|-i`

show only Shop id's. eg. "oe-console oxid:shops --only-ids | xargs -tn1 oe-console ... --shop-id "

* Accept value: no
* Is value required: no
* Default: `false`

`route:debug`
-------------

Returns the route. Which controller and parameters are called.

### Usage

* `route:debug [-c|--copy] [--] <url>`

Returns the route. Which controller and parameters are called.

### Arguments

#### `url`

Website URL. Full or Path


### Options

#### `--copy|-c`

Copy file path from the class to the clipboard (only MacOS)

* Accept value: no
* Is value required: no
* Default: `false`

`user:create`
-------------

Creates a new user

### Usage

* `user:create`

Creates a new user

`user:password`
---------------

Sets a new password

### Usage

* `user:password <username> <password>`

Sets a new password

### Arguments

#### `username`

Username


#### `password`

New password

`views:update`
--------------

Updates the views

### Usage

* `views:update`

Updates the views

