<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Migration process to upgrade to module version 1.0.3
 * @param  [Object] $module  [description]
 * @return [boolean]         [description]
 */
function upgrade_module_1_0_3($module)
{
    $res = true;
    $tableName = $module->getFmPrestashop()->getTableName(FmUtils::MODULE_NAME, '_orders', true);
    $sql = 'ALTER TABLE ' . $tableName . '
           ADD COLUMN status INT(10) DEFAULT 1,
           ADD COLUMN body TEXT DEFAULT null,
           ADD COLUMN created timestamp DEFAULT CURRENT_TIMESTAMP';
    $res &= $module->getFmPrestashop()->dbGetInstance()->Execute($sql, false);

    $sql = 'DROP INDEX orderIndex ON ' . $tableName . ';';
    $res &= $module->getFmPrestashop()->dbGetInstance()->Execute($sql);

    $sql = 'CREATE INDEX orderIndexNew ON ' . $tableName . ' (fyndiq_orderid);';
    $res &= $module->getFmPrestashop()->dbGetInstance()->Execute($sql);

    return (bool) $res;
}
