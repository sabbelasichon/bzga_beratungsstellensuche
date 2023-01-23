<?php

use Bzga\BzgaBeratungsstellensuche\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Bzga\BzgaBeratungsstellensuche\Controller\EntryController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use Bzga\BzgaBeratungsstellensuche\Backend\FormDataProvider\BeratungsstellensucheFlexFormManipulation;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess;
use Bzga\BzgaBeratungsstellensuche\Hooks\DataHandlerProcessor;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Cache\Backend\FileBackend;
use Bzga\BzgaBeratungsstellensuche\Cache\CachedClassLoader;
use Bzga\BzgaBeratungsstellensuche\Factories\CacheFactory;
use Bzga\BzgaBeratungsstellensuche\Property\TypeConverter\ImageLinkConverter;
use Bzga\BzgaBeratungsstellensuche\Property\TypeConverter\StringConverter;
use Bzga\BzgaBeratungsstellensuche\Property\TypeConverter\AbstractEntityConverter;
use Bzga\BzgaBeratungsstellensuche\Property\TypeConverter\ObjectStorageConverter;
use Bzga\BzgaBeratungsstellensuche\Property\TypeConverter\BoolConverter;
use Bzga\BzgaBeratungsstellensuche\Updates\CreateImageUploadFolderUpdate;
use Bzga\BzgaBeratungsstellensuche\Updates\ImportCountryZonesUpdate;
use TYPO3\CMS\Extbase\Object\Container\Container;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Bzga\BzgaBeratungsstellensuche\Persistence\QueryResult;
if (! defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(function ($packageKey) {
    ExtensionManagementUtility::registerExtensionKey($packageKey, 100);

    // Plugin configuration
    ExtensionUtility::configurePlugin(
        'BzgaBeratungsstellensuche',
        'Pi1',
        [EntryController::class => 'list,show,form,autocomplete'],
        [EntryController::class => 'list,form,autocomplete']
    );

    if (TYPO3_MODE === 'BE') {
        $icons = [
            'ext-bzgaberatungsstellensuche-wizard-icon' => 'plugin_wizard.svg',
        ];
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        foreach ($icons as $identifier => $path) {
            if (!$iconRegistry->isRegistered($identifier)) {
                $iconRegistry->registerIcon(
                    $identifier,
                    SvgIconProvider::class,
                    ['source' => 'EXT:bzga_beratungsstellensuche/Resources/Public/Icons/' . $path]
                );
            }
        }
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:bzga_beratungsstellensuche/Configuration/TSconfig/ContentElementWizard.txt">');

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][BeratungsstellensucheFlexFormManipulation::class] = [
        'depends' => [
            TcaFlexPrepare::class,
        ],
        'before' => [
            TcaFlexProcess::class,
        ],
    ];

    // Page module hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['bzgaberatungsstellensuche_pi1']['bzga_beratungsstellensuche'] =
        'Bzga\\BzgaBeratungsstellensuche\\Hooks\\PageLayoutView->getExtensionSummary';

    // Command controllers for scheduler
    if (TYPO3_MODE === 'BE') {
        // hooking into TCE Main to monitor record updates that may require deleting documents from the index
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][]  = DataHandlerProcessor::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = DataHandlerProcessor::class;
    }

    // Register cache to extend the models of this extension
    if (! is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$packageKey])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$packageKey]           = [];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$packageKey]['groups'] = ['all'];
    }
    if (! isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$packageKey]['frontend'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$packageKey]['frontend'] = PhpFrontend::class;
    }
    if (! isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$packageKey]['backend'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$packageKey]['backend'] = FileBackend::class;
    }
    // Configure clear cache post processing for extended domain models
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][$packageKey] = 'Bzga\\BzgaBeratungsstellensuche\\Cache\\ClassCacheManager->reBuild';

    // Register cached domain model classes autoloader
    require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($packageKey) . 'Classes/Cache/CachedClassLoader.php';
    CachedClassLoader::registerAutoloader();

    // Names of entities which can be overriden
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$packageKey]['entities'] = [
        'Entry',
        'Category',
        'Dto/Demand',
    ];

    // Caching of user requests
    if (! is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][CacheFactory::CACHE_KEY])
    ) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][CacheFactory::CACHE_KEY] = [
            'frontend' => '\TYPO3\CMS\Core\Cache\Frontend\VariableFrontend',
            'backend'  => '\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend',
            'options'  => [],
        ];
    }

    // Register some type converters so we can prepare everything for the data handler to import the xml
    ExtensionManagementUtility::registerTypeConverter(ImageLinkConverter::class);
    ExtensionManagementUtility::registerTypeConverter(StringConverter::class);
    ExtensionManagementUtility::registerTypeConverter(AbstractEntityConverter::class);
    ExtensionManagementUtility::registerTypeConverter(ObjectStorageConverter::class);
    ExtensionManagementUtility::registerTypeConverter(BoolConverter::class);

    // Linkvalidator
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('linkvalidator')) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:bzga_beratungsstellensuche/Configuration/TSconfig/Page/mod.linkvalidator.txt">');
    }

    // Upgrade wizards
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][CreateImageUploadFolderUpdate::class] = CreateImageUploadFolderUpdate::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][ImportCountryZonesUpdate::class] = ImportCountryZonesUpdate::class;

}, 'bzga_beratungsstellensuche');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(trim('
    config.pageTitleProviders {
        beratungsstelle {
            provider = Bzga\BzgaBeratungsstellensuche\PageTitle\PageTitleProvider
            before = record
            after = altPageTitle
        }
    }
'));

GeneralUtility::makeInstance(Container::class)->registerImplementation(QueryResultInterface::class, QueryResult::class);
