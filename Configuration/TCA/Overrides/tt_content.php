<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use WebVision\UnitySchema\UserFunctions\FormEngine\StructuredDataTypes;

defined('TYPO3') || die();

(static function (): void {
    ExtensionManagementUtility::addTCAcolumns('tt_content', [
        'tx_schema_type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:unity_schema/Resources/Private/Language/locallang_db.xlf:tt_content.tx_schema_type',
            'description' => 'LLL:EXT:unity_schema/Resources/Private/Language/locallang_db.xlf:tt_content.tx_schema_type.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => '',
                        'value' => '',
                    ],
                ],
                'itemsProcFunc' => StructuredDataTypes::class . '->get',
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'tx_schema_is_main_entity' => [
            'exclude' => true,
            'label' => 'LLL:EXT:unity_schema/Resources/Private/Language/locallang_db.xlf:tt_content.tx_schema_is_main_entity',
            'description' => 'LLL:EXT:unity_schema/Resources/Private/Language/locallang_db.xlf:tt_content.tx_schema_is_main_entity.description',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'tx_schema_properties' => [
            'exclude' => true,
            'label' => 'LLL:EXT:unity_schema/Resources/Private/Language/locallang_db.xlf:tt_content.tx_schema_properties',
            'description' => 'LLL:EXT:unity_schema/Resources/Private/Language/locallang_db.xlf:tt_content.tx_schema_properties.description',
            'config' => [
                'type' => 'json',
            ],
        ],
    ]);

    ExtensionManagementUtility::addFieldsToPalette(
        'tt_content',
        'tx_schema_structureddata',
        'tx_schema_type, tx_schema_is_main_entity, tx_schema_properties',
    );

    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        '--palette--;LLL:EXT:unity_schema/Resources/Private/Language/locallang_db.xlf:tt_content.palette.tx_schema_structureddata;tx_schema_structureddata',
    );
})();
