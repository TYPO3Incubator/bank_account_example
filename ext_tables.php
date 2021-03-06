<?php
defined('TYPO3_MODE') || die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    \H4ck3r31\BankAccountExample\Common::KEY_EXTENSION,
    'Configuration/TypoScript',
    'BankAccountExample'
);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'H4ck3r31.BankAccountExample',
    'Management',
    'BankDto Account Management'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages(
    'tx_bankaccountexample_domain_model_account'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages(
    'tx_bankaccountexample_domain_model_transaction'
);
