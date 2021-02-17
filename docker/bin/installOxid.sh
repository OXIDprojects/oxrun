cd #!/usr/bin/env bash

ln -fs ${DOCKER_DOCUMENT_ROOT}"/vendor/bin/oe-console" /usr/local/bin


if [ ! -f "${DOCKER_DOCUMENT_ROOT}/source/config.inc.php" ]; then

    echo "Install Shop";

    install_dir=${DOCKER_DOCUMENT_ROOT}
    source_dir=${DOCKER_DOCUMENT_ROOT}"/source"
    composer=$(which composer1)

    echo "Download 'oxid-esales/oxideshop-project:${COMPILATION_VERSION}'";

    php -d memory_limit=4G $composer create-project --no-dev --keep-vcs --working-dir=/tmp \
        oxid-esales/oxideshop-project /tmp/preinstall \
        ${COMPILATION_VERSION}

    echo "Install ${install_dir}"
    chown -R www-data: "/tmp/preinstall" && \
    rsync -ap /tmp/preinstall/ ${install_dir} && \
    rm -rf /tmp/preinstall

    echo "composer require oxidprojects/oxrun:^0.1@RC"
    cd ${install_dir}
    $composer --no-plugins config --file=${install_dir}'/composer.json' repositories.oxrun path '/oxrun' && \
    php -d memory_limit=4G  $composer require --no-interaction oxidprojects/oxrun:^0.1@RC
    cd -;

    echo "Configure OXID eShop ...";
    sed -i "s/<dbHost>/${MYSQL_HOST}/" ${source_dir}/config.inc.php && \
    sed -i "s/<dbName>/${MYSQL_DATABASE}/" ${source_dir}/config.inc.php && \
    sed -i "s/<dbUser>/${MYSQL_USER}/" ${source_dir}/config.inc.php && \
    sed -i "s/<dbPwd>/${MYSQL_PASSWORD}/" ${source_dir}/config.inc.php && \
    sed -i "s|<sShopURL>|${OXID_SHOP_URL}|" ${source_dir}/config.inc.php && \
    sed -i "s/'<sShopDir>'/__DIR__ . '\/'/" ${source_dir}/config.inc.php && \
    sed -i "s/'<sCompileDir>'/__DIR__ . '\/tmp'/" ${source_dir}/config.inc.php

    echo "Create mysql database schema ...";
    mysql -h ${MYSQL_HOST} -u ${MYSQL_USER} -p${MYSQL_PASSWORD} ${MYSQL_DATABASE} < ${install_dir}/vendor/oxid-esales/oxideshop-ce/source/Setup/Sql/database_schema.sql && \
    mysql -h ${MYSQL_HOST} -u ${MYSQL_USER} -p${MYSQL_PASSWORD} ${MYSQL_DATABASE} < ${install_dir}/vendor/oxid-esales/oxideshop-demodata-ce/src/demodata.sql && \
    rm -Rf ${source_dir}/Setup

    echo "Copy demo asset ...";
    ${install_dir}/vendor/bin/oe-eshop-demodata_install

    echo "Oxid eshop migration ...";
    ${install_dir}/vendor/bin/oe-eshop-doctrine_migration migrations:migrate

    echo "Create OXID views ...";
    ${install_dir}/vendor/bin/oe-eshop-db_views_generate
fi

echo "";
echo "WebSeite: ${OXID_SHOP_URL}";
echo "";

docker-php-entrypoint php-fpm
