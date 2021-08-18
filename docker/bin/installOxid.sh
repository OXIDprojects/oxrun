#!/usr/bin/env bash

ln -fs ${DOCKER_DOCUMENT_ROOT}"/vendor/bin/oe-console" /usr/local/bin

if [ ! -f "${DOCKER_DOCUMENT_ROOT}/source/config.inc.php" ]; then

    echo "[INSTALL] Shop";

    install_dir=${DOCKER_DOCUMENT_ROOT}
    source_dir=${DOCKER_DOCUMENT_ROOT}"/source"
    oxidfolder=$(basename $install_dir)
    workspace=${GITHUB_WORKSPACE:-/oxrun}
    composer=$(which composer)

    if [ ! -d ${install_dir} ]; then
        mkdir -p ${install_dir};
    fi

    echo "[INSTALL] Download 'oxid-esales/oxideshop-project:${COMPILATION_VERSION}'";
    php -d memory_limit=4G $composer create-project --no-dev --keep-vcs --working-dir=${install_dir}/.. \
        oxid-esales/oxideshop-project ${oxidfolder} \
        ${COMPILATION_VERSION}

    echo "[INSTALL] ${install_dir}"
    chown -R www-data: ${DOCKER_DOCUMENT_ROOT}

    echo "[INSTALL] set oxrun a version"
    cd ${workspace};
    $composer config version "0.1@RC"

    echo "[INSTALL] composer require oxidprojects/oxrun:^0.1@RC"
    cd ${install_dir}
    $composer config --file=${install_dir}'/composer.json' repositories.oxrun path $workspace && \
    php -d memory_limit=4G $composer require --update-no-dev --no-interaction oxidprojects/oxrun:^0.1@RC
    cd -;

    echo "[INSTALL] Configure OXID eShop ...";
    sed -i "s/<dbHost>/${MYSQL_HOST}/" ${source_dir}/config.inc.php && \
    sed -i "s/<dbName>/${MYSQL_DATABASE}/" ${source_dir}/config.inc.php && \
    sed -i "s/<dbUser>/${MYSQL_USER}/" ${source_dir}/config.inc.php && \
    sed -i "s/<dbPwd>/${MYSQL_PASSWORD}/" ${source_dir}/config.inc.php && \
    sed -i "s|<sShopURL>|${OXID_SHOP_URL}|" ${source_dir}/config.inc.php && \
    sed -i "s/'<sShopDir>'/__DIR__ . '\/'/" ${source_dir}/config.inc.php && \
    sed -i "s/'<sCompileDir>'/__DIR__ . '\/tmp'/" ${source_dir}/config.inc.php

    echo "[INSTALL] Create mysql database schema ...";
    mysql -h ${MYSQL_HOST} -u ${MYSQL_USER} -p${MYSQL_PASSWORD} ${MYSQL_DATABASE} < ${install_dir}/vendor/oxid-esales/oxideshop-ce/source/Setup/Sql/database_schema.sql && \
    mysql -h ${MYSQL_HOST} -u ${MYSQL_USER} -p${MYSQL_PASSWORD} ${MYSQL_DATABASE} < ${install_dir}/vendor/oxid-esales/oxideshop-demodata-ce/src/demodata.sql && \
    rm -Rf ${source_dir}/Setup

    echo "[INSTALL] Copy demo asset ...";
    ${install_dir}/vendor/bin/oe-eshop-demodata_install

    echo "[INSTALL] Oxid eshop migration ...";
    ${install_dir}/vendor/bin/oe-eshop-doctrine_migration migrations:migrate

    echo "[INSTALL] Create OXID views ...";
    ${install_dir}/vendor/bin/oe-eshop-db_views_generate

    echo "[INSTALL] Install PHPUnit and Co."
    cd ${workspace};
    $composer remove --no-scripts --no-plugins oxid-esales/oxideshop-ce

    echo "[INSTALL] reset composer.json"
    git checkout composer.json

    echo "[INSTALL] coverage for ${PHP_VERSION}"
    dpkg --compare-versions "8.0" "le" ${PHP_VERSION}
    if [ $? == 0 ]; then
        echo "[INSTALL] install pcov"
        pecl install pcov > /dev/null
        docker-php-ext-enable pcov.so
    else
        echo "[INSTALL] install xdebug"
        docker-php-ext-enable xdebug.so
    fi
fi

echo "";
echo "WebSeite: ${OXID_SHOP_URL}";
echo "";

isRunCi=${CI:-"no"};

if [ ${isRunCi} == "no" ]; then
 docker-php-entrypoint php-fpm
fi
