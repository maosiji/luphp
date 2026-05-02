<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-30 14:19
 * update               : 
 * project              : luphp
 */

namespace MAOSIJI\LU\WP\SQL;

use MAOSIJI\LU\EXCEPTION\LUDatabaseException;

trait LUWPSQLPublic
{
    /**
     * 确保表存在，否则抛出异常
     *
     * @param string $tableName 完整表名（含前缀）
     * @throws LUDatabaseException
     */
    private function checkTableExists( string $tableName )
    {
        LUWPSQLParamValidator::tableName($tableName);

        global $wpdb;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$tableName'") === $tableName;
        if (!$exists) {
            throw new LUDatabaseException(
                "表 $tableName 不存在",
                LUDatabaseException::CODE_TABLE_NOT_FOUND
            );
        }
    }



}