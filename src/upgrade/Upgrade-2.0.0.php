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
    $tableName = $module->getFmPrestashop()->getTableName(FmUtils::MODULE_NAME, '_orders', true);
    $sql = 'ALTER TABLE ' . $tableName . '
           ADD COLUMN status INT(10) DEFAULT 1,
           ADD COLUMN body TEXT DEFAULT null,
           ADD COLUMN created timestamp DEFAULT CURRENT_TIMESTAMP';
    return $module->getFmPrestashop()->dbGetInstance()->Execute($sql, false);
}
