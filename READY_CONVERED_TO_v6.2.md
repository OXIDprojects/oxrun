Successfully tested commands
============================

Commands that are successfully tested in the oxide v6.2.3.

| Command                      | v6.2        | Extra                             |
|------------------------------|-------------|-----------------------------------|
|`cache:clear`                 |  [x]        |                                   |
|`cms:update`                  |  [removed]  | Not used                          |
|`config:get`                  |  [x]        |                                   |
|`config:multiset`             |  [rename]   | deploy:config                     |
|`config:set`                  |  [x]        |                                   |
|`config:shop:get`             |  [x]        |                                   |
|`config:shop:set`             |  [x]        |                                   |
|`db:anonymize`                |  [x]        |                                   |
|`db:dump`                     |  [x]        |                                   |
|`db:import`                   |  [x]        |                                   |
|`db:list`                     |  [x]        |                                   |
|`db:query`                    |  [x]        |                                   |
|`log:exceptionlog`            |  [removed]  | need refactoring                  |
|`misc:generate:documentation` |  [moved]    | find in ./vendor/bin/oxrun-light  |
|`misc:generate:yaml:config`   |  [rename]   | deploy:generate:configration      |
|`misc:generate:yaml:module`   |  [rename]   | deploy:generate:module-activator  |
|`misc:phpstorm:metadata`      |  [x]        |                                   |
|`module:generate`             |  [x]        |                                   |
|`module:activate`             |  [replaced] | oe:module:activate                |
|`module:deactivate`           |  [replaced] | oe:module:deactivate              |
|`module:list`                 |  [x]        |                                   |
|`module:multiactivate`        |  [renamed]  | deploy:module-activator           |
|`module:reload`               |  [x]        |                                   |
|`oxid:shops`                  |  [x]        |                                   |
|`route:debug`                 |  [x]        |                                   |
|`user:create`                 |  [x]        |                                   |
|`user:password`               |  [x]        |                                   |
|`views:update`                |  [x]        |                                   |
