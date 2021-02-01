#!/usr/bin/env php
<?php

$argv = [
    'misc:register:command',
    '--service-yaml',
    escapeshellarg(__DIR__ . '/../services.yaml'),
    escapeshellarg(__DIR__ . '/../src/Oxrun/Command'),
];

passthru( __DIR__ . '/oxrun-light.php ' . join(' ', $argv));
