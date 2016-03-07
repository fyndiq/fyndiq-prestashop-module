<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Migration process to upgrade new module version 2.0.0
 * @param  [Object] $module  [description]
 * @return [boolean]         [description]
 */
function upgrade_module_2_0_0($module)
{
    $tableName = $module->getFmPrestashop()->getTableName(FmUtils::MODULE_NAME, '_products', true);
    $sql = 'ALTER TABLE ' . $tableName . '
           ADD COLUMN name VARCHAR(128) DEFAULT null,
           ADD COLUMN description TEXT DEFAULT null';
    return (bool) $module->getFmPrestashop()->dbGetInstance()->Execute($sql, false);
}
