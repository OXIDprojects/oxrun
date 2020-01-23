# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- fix minor spelling issue in activate and deactivate command

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
