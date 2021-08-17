#!/usr/bin/env bash

cd $(dirname $0);

BASE_DIR=${GITHUB_WORKSPACE};
README="$BASE_DIR/README.md";
oxrun_light=${1:-"/var/www/oxid-esale/vendor/bin/oxrun-light"};

if [[ ! -f $oxrun_light ]]; then
    echo "$oxrun_light not found" >&2
    exit 2
fi

if [[ ! -f $README ]]; then
    echo "$README not found" >&2
    exit 2
fi

LINE=$(grep -n "Available commands" $README  | head -n 1 | cut -d: -f1);
if [ ! $LINE ]; then
    echo "'Available commands' not found in README.md" >&2
    exit 2;
fi

echo "Keep header of README.md";
LINE=$(expr $LINE - 1);
sed -i "${LINE}q" $README;

echo "Generate documentation";
$oxrun_light misc:generate:documentation >> $README

