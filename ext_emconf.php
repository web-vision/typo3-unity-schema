<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'schema.org to Unity connector',
    'description' => 'Enrichs TYPO3 Unity for Magento with schema.org data',
    'category' => 'services',
    'author' => 'web-vision Team',
    'author_email' => 'hello@web-vision.de',
    'author_company' => 'web-vision GmbH',
    'state' => 'beta',
    'version' => '0.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
            'wv_t3unity' => '*',
            'schema' => '*',
        ],
    ],
];
