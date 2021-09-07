# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
## [v5.1.0] 2021-09-07
### Changed
  - `oxrun-light` is now officially documented.
  - Command cache:clear works without bootstrap.php so can now used in `oxrun-light`.

## [v5.0.1] 2021-09-06
### Fixed
  - Command misc:register:command had some runtime errors and tests were added.

## [v5.0.0] 2021-08-18
### Added
  - New Command `misc:register:command` to Register Command to service.yaml files.
  - New Command `db:info` Database Table Size (Issue #37).
  - New Command `deploy:link:environment` links the environment configration files. Ideal for CI/CD.
  - New Command `deploy:update-module-config` update the module configuration yaml with the data from the database.
  - ./bin/generate_service_yaml.php to generate `services.yaml` for `oe-console`
  - ./bin/oxrun-light.php small application with one command `misc:register:command`
  - Command `deploy:generate:configration` has new Option `--list` to show all configrations
  - Command `deploy:generate:configration` has new Options `--production` `--staging` `--development` `--testing`
  - Command `deploy:generate:configration` can now update an exited config file `--update`
  - Command `deploy:generate:configration` save in the firstline the command unix call of oe-console
  - Command `deploy:config` has a Option `--force-db` that save module configration into yaml and database, too.
  - Command `deploy:module-apply-configuration-light` that is faster as the origin as make this same.
  - Command `module:reload` has new option `--skip-cache-clear` to skip cache clear
  - Command `module:reload` has new option `--based-on-config` to reload a module only if that allow in a deploy:module-activator configration yaml.
  - Command `oxid:shops` has new option `--only-ids` with that will be all Shop Id's are listed. With this can work well with xargs `oe-console oxid:shops --only-ids | xargs -tn1 oe-console ... --shop-id`
  - New Tool `oxrun-light` has commands that are not need a OXID Database connection.
  - The output from command `misc:phpstorm:metadata` are ideal for `phpstan` or `psalm`.

### Changed
  - Oxrun is now a [OXID eShop Component](https://docs.oxid-esales.com/developer/en/6.2/development/modules_components_themes/component.html)
  - Moved `INSTALLATION_ROOT_PATH/oxrun_config/` to `var/` (`INSTALLATION_ROOT_PATH/var/oxrun_config/`)
  - Command `module:activate` is now `oe:module:activate`
  - Command `module:deactivate` is now `oe:module:deactivate`
  - Command `misc:phpstorm:metadata` updated to oxid namespace style and fill Module parent classes
  - Command `misc:phpstorm:metadata` make two files. 1. `oxid_esale.meta.php` for autocomplete oxNew() and Registry::get() 2. `oxid_module_chain.meta.php` create a module chain
  - See more [details](READY_CONVERED_TO_v6.2.md) which command refactored
  - the option `--shopId` is changed to `--shop-id` and the shortcut `-m` is removed
  - Command `module:multiactivate` renamed to `deploy:module-activator` is copy from `proudcommerce/oxid-console-moduleactivator`
     - and updated with feature:
        - "Auto module installation into oe:module:configuration:yaml"
        - "Check is module active in DB and in oe:module:configuration:yaml"
        - "In one yaml can use blacklist and whitelist"
        - "work in EE with all subshop or with one shop id"
        - "Bug fix in blacklist mode, will module deactive if tha on blacklist"
        - "the list will be saved var/oxrun_config/"
  - Command `deploy:config` updates the [module configuration](https://docs.oxid-esales.com/developer/en/6.2/development/modules_components_themes/project/module_configuration/modules_configuration_deployment.html) includes also the environments.
  - Commands made functional under CE.
  - Create a new command group `deploy:` with commands that are needed for a deployment.
    - the following commands have been renamed there
    - `config:multiset` is now `deploy:config`
    - `misc:generate:yaml:config` is now `deploy:generate:configration`
    - `misc:generate:yaml:module` is now `deploy:generate:module-activator`
    - `module:multiactivator` is now `deploy:module-activator`
  - Change package for unzip from gbeushausen/distill to nelexa/zip
  - Command `misc:generate:documentation` is moved from `oe-console` to `oxrun-light`

### Removed
  - `oxrun.phar` use ./vendor/bin/oe-console
  - Command `cms:update`
  - Command `log:exceptionlog` the log - output has be changed
  - Command `list` don't show message with database errors

## [v4.3.1] 2021-02-18

### Change

  - Command config:oxid62:modules-configuration filter exists module setting
  - Command config:oxid62:modules-configuration save variable type

## [v4.3.0] 2021-02-18

### Add

  - New command (`config:oxid62:modules-configuration`) to create modules configration files in the OXID version 6.1 in preparation to use them later in 6.2

### Change

  - Update local docker-composer

## [v4.2.1] 2020-06-19

### Fixed

- fix composer autoload from vendor/bin dir
- never anonymize malladmin users

## [v4.2.0] 2020-05-25

### Fixed

- fix minor spelling issue in activate and deactivate command

### Add

- add module sorting "priorities" and an option to clear the module entries in oxconfig for MultiActivateCommand

## [v4.1.1] 2019-09-26

### Fixed

- Fix cache clearing failing on sCompileDir with trailing slash. [#46]

## [v4.1.0] 2019-05-06

### Add
- Configuration YAML is more readable, through the description texts. [see](https://github.com/OXIDprojects/oxrun/blob/master/tests/Oxrun/Helper/testData/translated_config.yml#L9)
- The directory where the Oxid eShop is located can be determined with the environment variable `OXID_SHOP_DIR`. Ideal for Docker Images.
- oxrun finds the bootstrap file if you are in the installation directory of the oxid eshop.
- When calling the absolute path, e.g. `/var/www/html/vendor/bin/oxrun`, bootstrap is also found. This undoes the --shopDir option. Ideal for cronjobs or continuous integration.
- Now will make the error immediately in the console. The Oxid eShop error handling has been deactivated.

### Changed

- Option `--oxmodule` of command `misc:generate:yaml:config` can only take the module name or completely written out.
  `--oxmodule=module:myModule` and `--oxmodule=myModule` are same.
- Rename command `misc:generate:yaml:multiset` to `misc:generate:yaml:config` to better distinguish
  between `yaml:modules` and `yaml:multiset`.
- Find now several ways to find the oxid eshop directory.

### Fixed

- If there are errors in the DI container. It will be tried to recreate it automatically.
- Modules that extend the OxidEsales\Eshop\Core\Cache\Generic\Cache class are considered.

### Deprecated

- command `misc:generate:yaml:multiset` was replaced by `misc:generate:yaml:config`

## [v4.0.0] 2019-03-24

### Added new commands

1. `module:multiactivate` activate Module by a Yaml File
2. `db:anonymize` anonymize relevant OXID db tables
3. `config:multiset` Sets multiple config values from yaml file
4. `log:exceptionlog` show you oxid exceptionlog in a table format
5. `user:create` create a new User
6. `oxid:shops` show all shops. (EE only)
7. `misc:generate:yaml:multiset` create a YAML file for command `config:multiset`.
8. `misc:generate:yaml:module` create a YAML file for command `module:multiactivate`.

### Added

- There are three methods to add your own command.
- new option for every command `--shopId` or `-m` select a shop for oxrun
- command `cache:clear` can now clear the GenericCache and DynamicContentCache in a EE version.

### Changed

- When generating a module, the Composer.json file is now edited with the original classe.
- oxrun can now use in EE
- The file docker-compose.yml has been prepared to install an EE. You have to deposit the access data and change it to ee manuel.
- Security risk: Better keep the config files outside of the public `source/` folder.
  The YAML files are searched under the directory: `INSTALLATION_ROOT_PATH/oxrun_config/`. In the same level as `source/` and `vendor/` folder.
- Deployment Docker. The OXID eSale source code is outside of the Container.
- Now starts 2x faster. The first start will collect the command and save it as a DI container in `oxide-esale/vendor/oxideprojects/OxrunCommands.php`.
- README.md has now a "table of content" a list of commands. And will autogenerate by travis.

### Fixed

- The oxrun::component can now be integrated via composer require.
- Oxrun can now work within a composing environment
- Oxrun::commands are not added again, from service.yaml
- Commands `module:multiactivate` and `config:multiset` run through all defined subshops without shopId.

## 3.3.0 - 2018-12-02

### Added

- oxrun can share his command with other cli tools like [ps:console](https://github.com/OXIDprojects/oxid-console), [oxid:console](https://github.com/OXID-eSales/oxideshop_ce/tree/b-6.x-introduce_console-OXDEV-1580)

### Removed

- Remove OXID version switch. Command module:activate and module:deactivate works >v6.x

## 3.2.0 - 2018-11-28

### Added

- It can now take more commands from other packages. With a services.yml

### Changed

- development environment with docker for OXID v6x

### Removed

- Fix Command. Is now write by [oxid-module-internals]https://github.com/OXIDprojects/oxid-module-internals
- Install Command. Because this could only install the v4x


## 3.0.1 - 2018-11-13

### Added

- Registered at packagist.org

### Changed

- Hotfix will be required


## 3.0.0 - 2018-11-13

### Added

- New Command `module:generate`

### Changed

- Oxrun will only be developed for OXID eShop v6.x
- Test change to PHPUnit v6

### Removed

- Ioly Command
