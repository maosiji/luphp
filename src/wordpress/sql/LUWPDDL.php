<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-12-03 23:57
 * update               :
 * project              : luphp
 *
 * 主要用于定义或修改数据库结构，包括创建、删除和修改数据库中的对象如表、索引、视图等
 *
 *  CREATE：用于创建数据库或数据库中的对象（例如表、视图）。
    ALTER：用于修改现有的数据库结构。
    DROP：用于删除数据库对象。
    TRUNCATE：用于删除表中的所有数据但保留表结构。
    COMMENT：用于向数据字典添加注释。
    RENAME：用于重命名一个对象。
 */

namespace MAOSIJI\LU\WP\SQL;

use MAOSIJI\LU\LUResult;

if ( ! defined( 'ABSPATH' ) ) { die; }
class LUWPDDL
{
    use LUWPSQLPublic;
    function __construct()
    {
    }
    private function __clone() {}

    /**
     * 创建表
     *
     * @param string $tableNameNoPrefix 不带 WordPress 表前缀的表名
     * @param string $colSQL        列定义 SQL 片段，如 "id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100)"
     *
     * @return LUResult
     */
    public function createTable( string $tableNameNoPrefix, string $colSQL ): LUResult
    {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;

        $validTableName = LUWPSQLParamValidator::tableName($tableName);
        if ( $validTableName->isError() ) {
            return $validTableName;
        }

        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isSuccess() ) {
            return $checkTableExist;
        }

        // 1. 表已存在则直接返回幂等结果
//        if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") === $tableName) {
//            return LUResult::success(['table_name'=>$tableName], '表已存在');
//        }

        $charsetCollate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $tableName ($colSQL) ENGINE=InnoDB $charsetCollate";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $result = dbDelta($sql);

        // 2. 再次检查表是否存在，避免依赖 dbDelta 的文字输出
        $tableNowExists = $wpdb->get_var("SHOW TABLES LIKE '$tableName'") === $tableName;
        if ($tableNowExists) {
            return LUResult::success(['table_name'=>$tableName, 'data'=>$result], '表已创建');
        }

        // 3. 如果仍未创建，视为失败
        return LUResult::error( 1000, '表创建失败', ['table_name'=>$tableName, 'data'=>$result] );
    }

    /**
     * 修改表名
     *
     * @param string $tableNameNoPrefix
     * @param string $newTableNameNoPrefix
     * @return LUResult
     * */
    public function renameTableName( string $tableNameNoPrefix, string $newTableNameNoPrefix ): LUResult
    {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;
        $newTableName = $wpdb->prefix . $newTableNameNoPrefix;

        $validTableName = LUWPSQLParamValidator::tableName($tableName);
        if ( $validTableName->isError() ) {
            return $validTableName;
        }

        $validNewTableName = LUWPSQLParamValidator::tableName($newTableName);
        if ( $validNewTableName->isError() ) {
            return $validNewTableName;
        }

        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        $sql = "RENAME TABLE `{$tableName}` TO `{$newTableName}`";
        $result = $this->_query($sql);

        // 二次验证
        $tableNowExists = $wpdb->get_var("SHOW TABLES LIKE '$newTableName'") === $newTableName;
        if ($tableNowExists) {
            return LUResult::success(['table_name'=>$tableName, 'new_table_name'=>$newTableName], '表名已更改');
        }

        return LUResult::error(1000, '表名更改失败', ['table_name'=>$tableName, 'new_table_name'=>$newTableName]);
    }

    /**
     * 清空表数据，保留结构
     *
     * @param string $tableNameNoPrefix
     * @return LUResult
     * */
    public function truncateTable( string $tableNameNoPrefix ): LUResult
    {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;

        $validTableName = LUWPSQLParamValidator::tableName($tableName);
        if ( $validTableName->isError() ) {
            return $validTableName;
        }

        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        $sql = "TRUNCATE TABLE `{$tableName}`";
        $result = $this->_query($sql);

        // 二次验证：查询行数
        $count = $wpdb->get_var("SELECT COUNT(*) FROM `{$tableName}`");
        if ( (int) $count === 0 ) {
            return LUResult::success(['table_name' => $tableName], '表已清空');
        }

        return LUResult::error(1000, '清空表失败', ['table_name' => $tableName, 'data' => $count]);
    }

    /**
     * 修改表注释
     *
     * @param string $tableNameNoPrefix
     * @param string $comment
     * @return LUResult
     */
    public function commentTable( string $tableNameNoPrefix, string $comment ): LUResult
    {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;

        $validTableName = LUWPSQLParamValidator::tableName($tableName);
        if ( $validTableName->isError() ) {
            return $validTableName;
        }

        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        $commentClause = '';
        if ( $comment !== '' ) {
            $commentClause = $wpdb->prepare('COMMENT %s', $comment);
        }

        $sql = "ALTER TABLE `{$tableName}` {$commentClause}";
        $result = $this->_query($sql);

        // 二次验证
        $tableStatus = $wpdb->get_row("SHOW TABLE STATUS LIKE '{$tableName}'");
        $currentComment = $tableStatus->Comment ?? '';

        if ($currentComment === $comment) {
            return LUResult::success(['table_name' => $tableName, 'comment' => $currentComment], '表注释已更新');
        }

        return LUResult::error(1000, '表注释更新失败', [
            'table_name'    => $tableName,
            'comment'       => $comment,
            'old_comment'   => $currentComment
        ]);
    }

    /**
     * 删除表
     *
     * @param string $tableNameNoPrefix
     * @return LUResult
     */
    public function deleteTable( string $tableNameNoPrefix ): LUResult
    {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;

        $validTableName = LUWPSQLParamValidator::tableName($tableName);
        if ( $validTableName->isError() ) {
            return $validTableName;
        }

        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        $sql = "DROP TABLE IF EXISTS `{$tableName}`";
        $result = $this->_query($sql);

        // 2. 再次检查表是否存在，避免依赖 dbDelta 的文字输出
        $tableNowExists = $wpdb->get_var("SHOW TABLES LIKE '$tableName'") === $tableName;
        if (!$tableNowExists) {
            return LUResult::success(['table_name'=>$tableName], '表已删除');
        }

        // 3. 如果仍未创建，视为失败
        return LUResult::error( 1000, '表删除失败', ['table_name'=>$tableName] );
    }

    /**
     * 修改表 添加列
     *
     * @param string $tableNameNoPrefix 无前缀表名
     * @param string $columnName 列名
     * @param string $columnType 列类型
     * @param string $columnFormat 占位符
     * @param string|null $columnDefault 默认值
     * @param string $columnComment 备注、说明
     *
     * @return LUResult
     * */
    public function alterTableAddColumn( string $tableNameNoPrefix, string $columnName, string $columnType='', string $columnFormat='%s', $columnDefault='', string $columnComment='' ): LUResult
    {
        $validColName = LUWPSQLParamValidator::columnName($columnName);
        if ( $validColName->isError() ) {
            return $validColName;
        }

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;

        $validTableName = LUWPSQLParamValidator::tableName($tableName);
        if ( $validTableName->isError() ) {
            return $validTableName;
        }

        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        $safeColName = "`{$columnName}`";

        // 幂等处理：若列已存在则直接返回成功
        $columnExists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE %s", $columnName));
        if ( !empty( $columnExists ) ) {
            return LUResult::success(['table_name' => $tableName, 'column_name' => $columnName], '列已存在');
        }

        // 处理 DEFAULT 子句
        if ($columnDefault === null || strtoupper((string)$columnDefault) === 'NULL') {
            $defaultClause = 'DEFAULT NULL';
        } else {
            $defaultClause = $wpdb->prepare('DEFAULT '.$columnFormat, $columnDefault);
        }

        // 处理 COMMENT 子句
        $commentClause = '';
        if ( $columnComment !== '' ) {
            $commentClause = $wpdb->prepare('COMMENT %s', $columnComment);
        }

        // 构建并执行 ALTER 语句
        $sql = "ALTER TABLE `{$tableName}` ADD COLUMN {$safeColName} {$columnType} {$defaultClause} {$commentClause}";
        $result = $this->_query($sql);

        // 二次验证列是否添加成功
        $columnNowExists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE %s", $columnName));
        if ( ! empty( $columnNowExists ) ) {
            return LUResult::success(['table_name' => $tableName, 'column_name' => $columnName], '列已添加');
        }

        return LUResult::error(1000, '添加列失败', [
            'table_name'        => $tableName,
            'column_name'       => $columnName,
            'column_type'       => $columnType,
            'column_default'    => $columnDefault,
            'column_comment'    => $columnComment
        ]);
    }

    /**
     * 修改表 修改列，不能修改列名【注意：更改之前已经存在的内容不会变】
     *
     * @param string $tableNameNoPrefix 无前缀表名
     * @param string $columnName 列名
     * @param string $columnType 列类型
     * @param string $columnFormat 占位符
     * @param string|null $columnDefault 默认值
     * @param string $columnComment 备注、说明
     *
     * @return LUResult
     */
    public function alterTableModifyColumn( string $tableNameNoPrefix, string $columnName, string $columnType, string $columnFormat='%s', $columnDefault='', string $columnComment='' ): LUResult
    {
        $validColName = LUWPSQLParamValidator::columnName($columnName);
        if ( $validColName->isError() ) {
            return $validColName;
        }

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;

        $validTableName = LUWPSQLParamValidator::tableName($tableName);
        if ( $validTableName->isError() ) {
            return $validTableName;
        }

        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        $safeColName = "`{$columnName}`";

        // 幂等处理：若列的信息和修改的信息一致，则直接返回成功
        $currentColumn = $wpdb->get_row($wpdb->prepare(
            "SHOW COLUMNS FROM `{$tableName}` LIKE %s",
            $columnName
        ));
        if (empty($currentColumn)) {
            return LUResult::error(1000, '要修改的列不存在', [
                'table_name'  => $tableName,
                'column_name' => $columnName
            ]);
        }

        $currentType = strtolower(trim($currentColumn->Type));
        $expectedType = strtolower(trim($columnType));
        $typeMatch = ($currentType === $expectedType);

        // 比较默认值
        if ($columnDefault === null || strtoupper((string)$columnDefault) === 'NULL') {
            $defaultMatch = ($currentColumn->Default === null);
        } else {
            $defaultMatch = ((string)$currentColumn->Default === (string)$columnDefault);
        }

        // 仅当指定了注释时才比较注释
        $commentMatch = true;
        if ($columnComment !== '') {
            $commentMatch = ((string)($currentColumn->Comment ?? '') === $columnComment);
        }

        if ($typeMatch && $defaultMatch && $commentMatch) {
            return LUResult::success([
                'table_name'  => $tableName,
                'column_name' => $columnName
            ], '列定义未变化');
        }

        // 处理 DEFAULT 子句
        if ($columnDefault === null || strtoupper((string)$columnDefault) === 'NULL') {
            $defaultClause = 'DEFAULT NULL';
        } else {
            $defaultClause = $wpdb->prepare('DEFAULT '.$columnFormat, $columnDefault);
        }

        // 处理 COMMENT 子句
        $commentClause = '';
        if ( $columnComment !== '' ) {
            $commentClause = $wpdb->prepare('COMMENT %s', $columnComment);
        }

        // 构建并执行 ALTER 语句
        $sql = "ALTER TABLE `{$tableName}` MODIFY COLUMN {$safeColName} {$columnType} {$defaultClause} {$commentClause}";
        $result = $this->_query($sql);

        return LUResult::error(1000,'',[
            'data'  => $result,
            'sql'   => $sql
        ]);

        // 二次验证列是否修改成功
        $updatedColumn = $wpdb->get_row($wpdb->prepare(
            "SHOW COLUMNS FROM `{$tableName}` LIKE %s",
            $columnName
        ));
        if ($updatedColumn) {
            $newType = strtolower(trim($updatedColumn->Type));
            $typeOk = ($newType === $expectedType);

            $defaultOk = false;
            if ($columnDefault === null || strtoupper((string)$columnDefault) === 'NULL') {
                $defaultOk = ($updatedColumn->Default === null);
            } else {
                $defaultOk = ((string)$updatedColumn->Default === (string)$columnDefault);
            }

            $commentOk = true;
            if ($columnComment !== '') {
                $commentOk = ((string)($updatedColumn->Comment ?? '') === $columnComment);
            }

            if ($typeOk && $defaultOk && $commentOk) {
                return LUResult::success([
                    'table_name'  => $tableName,
                    'column_name' => $columnName
                ], '列已修改');
            }
        }

        return LUResult::error(1000, '修改列失败', [
            'table_name'        => $tableName,
            'column_name'       => $columnName,
            'column_type'       => $columnType,
            'column_default'    => $columnDefault,
            'column_comment'    => $columnComment
        ]);
    }

    /**
     * 修改表 修改列，可以修改列名【注意：更改之前已经存在的内容不会变】
     *
     * @param string $tableNameNoPrefix 无前缀表名
     * @param string $columnName 列名
     * @param string $newColumnName 新列名
     * @param string $columnType 列类型
     * @param string $columnFormat 占位符
     * @param string|null $columnDefault 默认值
     * @param string $columnComment 备注、说明
     *
     * @return LUResult
     */
    public function alterTableChangeColumn( string $tableNameNoPrefix, string $columnName, string $newColumnName, string $columnType, string $columnFormat='%s', $columnDefault='', string $columnComment='' ): LUResult
    {
        $validColName = LUWPSQLParamValidator::columnName($columnName);
        if ( $validColName->isError() ) {
            return $validColName;
        }
        $validNewColName = LUWPSQLParamValidator::columnName($newColumnName);
        if ( $validNewColName->isError() ) {
            return $validNewColName;
        }

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;

        $validTableName = LUWPSQLParamValidator::tableName($tableName);
        if ( $validTableName->isError() ) {
            return $validTableName;
        }

        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        $safeColName = "`{$columnName}`";
        $safeNewColName = "`{$newColumnName}`";

        // 幂等处理：若列的信息和修改的信息一致，则直接返回成功
        $newColumn = $wpdb->get_row($wpdb->prepare(
            "SHOW COLUMNS FROM `{$tableName}` LIKE %s",
            $newColumnName
        ));
        if (!empty($newColumn)) {
            return LUResult::success([
                'table_name'        => $tableName,
                'column_name'       => $columnName,
                'new_column_name'   => $newColumnName
            ], '新列名已存在，无法更改');
        }

        // 处理 DEFAULT 子句
        if ($columnDefault === null || strtoupper((string)$columnDefault) === 'NULL') {
            $defaultClause = 'DEFAULT NULL';
        } else {
            $defaultClause = $wpdb->prepare('DEFAULT '.$columnFormat, $columnDefault);
        }

        // 处理 COMMENT 子句
        $commentClause = '';
        if ( $columnComment !== '' ) {
            $commentClause = $wpdb->prepare('COMMENT %s', $columnComment);
        }

        // 构建并执行 ALTER 语句
        $sql = "ALTER TABLE `{$tableName}` CHANGE COLUMN {$safeColName} {$safeNewColName} {$columnType} {$defaultClause} {$commentClause}";
        $result = $this->_query($sql);

        // 二次验证列是否修改成功
        $updatedColumn = $wpdb->get_row($wpdb->prepare(
            "SHOW COLUMNS FROM `{$tableName}` LIKE %s",
            $newColumnName
        ));
        if (!empty($updatedColumn)) {
            return LUResult::success([
                'table_name'        => $tableName,
                'column_name'       => $columnName,
                'new_column_name'   => $newColumnName
            ], '列已更改成功');
        }

        return LUResult::error(1000, '修改列失败', [
            'table_name'        => $tableName,
            'column_name'       => $columnName,
            'column_type'       => $columnType,
            'column_default'    => $columnDefault,
            'column_comment'    => $columnComment
        ]);
    }

    /**
     * 修改表 删除列
     *
     * @param string $tableNameNoPrefix
     * @param string $columnName:要删除的列名
     * @return LUResult
     */
    public function alterTableDeleteColumn( string $tableNameNoPrefix, string $columnName ): LUResult
    {
        $validColName = LUWPSQLParamValidator::columnName($columnName);
        if ( $validColName->isError() ) {
            return $validColName;
        }

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;

        $validTableName = LUWPSQLParamValidator::tableName($tableName);
        if ( $validTableName->isError() ) {
            return $validTableName;
        }

        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        $safeColName = "`{$columnName}`";

        // 幂等处理：若列不存在则直接返回成功
        $columnExists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE %s", $columnName));
        if ( empty( $columnExists ) ) {
            return LUResult::success(['table_name' => $tableName, 'column_name' => $columnName], '列不存在');
        }

        // 构建并执行 ALTER 语句
        $sql = "ALTER TABLE `{$tableName}` DROP COLUMN {$safeColName}";
        $result = $this->_query($sql);

        // 二次验证列是否添加成功
        $columnNowExists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE %s", $columnName));
        if ( empty( $columnNowExists ) ) {
            return LUResult::success(['table_name' => $tableName, 'column_name' => $columnName], '列已删除');
        }

        return LUResult::error(1000, '删除列失败', ['table_name' => $tableName, 'column_name' => $columnName]);
    }

    /**
     * 修改表 添加索引
     *
     * @param string $tableNameNoPrefix 无前缀表名
     * @param string $indexName 索引名
     * @param string $columnName 列名
     * @return LUResult
     */
    public function alterTableAddIndex( string $tableNameNoPrefix, string $indexName, string $columnName ): LUResult
    {
        $validColName = LUWPSQLParamValidator::columnName($columnName);
        if ( $validColName->isError() ) {
            return $validColName;
        }

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;

        $validTableName = LUWPSQLParamValidator::tableName($tableName);
        if ( $validTableName->isError() ) {
            return $validTableName;
        }

        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        $safeColName = "`{$columnName}`";
        $safeIndexName = "`{$indexName}`";

        // 幂等处理：检查索引是否已存在
        $indexExists = $wpdb->get_results( $wpdb->prepare("SHOW INDEX FROM `{$tableName}` WHERE Key_name = %s", $indexName ));
        if ( !empty( $indexExists ) ) {
            return LUResult::success([
                'table_name' => $tableName,
                'index_name' => $indexName,
                'column_name' => $columnName
            ], '索引已存在');
        }

        // 构建 ADD INDEX 语句
        $sql = "ALTER TABLE `{$tableName}` ADD INDEX {$safeIndexName} ({$safeColName})";
        $result = $this->_query($sql);

        // 二次验证
        $indexNowExists = $wpdb->get_results($wpdb->prepare( "SHOW INDEX FROM `{$tableName}` WHERE Key_name = %s", $indexName ));
        if ( ! empty( $indexNowExists ) ) {
            return LUResult::success([
                'table_name' => $tableName,
                'index_name' => $indexName,
                'column_name' => $columnName
            ], '索引已添加');
        }

        return LUResult::error(1000, '添加索引失败', [
            'table_name' => $tableName,
            'index_name' => $indexName,
            'column_name' => $columnName
        ]);
    }

    /**
     * 修改表 添加唯一约束（创建唯一索引）
     *
     * @param string $tableNameNoPrefix 无前缀表名
     * @param string $indexName 唯一索引名
     * @param string $columnName 列名
     * @return LUResult
     */
    public function alterTableAddUnique( string $tableNameNoPrefix, string $indexName, string $columnName ): LUResult
    {
        // 校验列名
        $validColName = LUWPSQLParamValidator::columnName($columnName);
        if ($validColName->isError()) {
            return $validColName;
        }

        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;

        $validTableName = LUWPSQLParamValidator::tableName($tableName);
        if ($validTableName->isError()) {
            return $validTableName;
        }

        $checkTableExist = $this->checkTableExist($tableName);
        if ($checkTableExist->isError()) {
            return $checkTableExist;
        }

        $safeColName = "`{$columnName}`";
        $safeIndexName = "`{$indexName}`";

        // 幂等处理：检查同名索引是否已存在
        $indexExists = $wpdb->get_results($wpdb->prepare(
            "SHOW INDEX FROM `{$tableName}` WHERE Key_name = %s",
            $indexName
        ));
        if (!empty($indexExists)) {
            return LUResult::success([
                'table_name' => $tableName,
                'index_name' => $indexName,
                'column_name' => $columnName
            ], '唯一约束已存在');
        }

        // 构建 ADD UNIQUE 语句
        $sql = "ALTER TABLE `{$tableName}` ADD UNIQUE {$safeIndexName} ({$safeColName})";
        $result = $this->_query($sql);

        // 二次验证
        $indexNowExists = $wpdb->get_results($wpdb->prepare(
            "SHOW INDEX FROM `{$tableName}` WHERE Key_name = %s",
            $indexName
        ));
        if (!empty($indexNowExists)) {
            return LUResult::success([
                'table_name' => $tableName,
                'index_name' => $indexName,
                'column_name' => $columnName
            ], '唯一约束已添加');
        }

        return LUResult::error(1000, '添加唯一约束失败', [
            'table_name' => $tableName,
            'index_name' => $indexName,
            'column_name' => $columnName
        ]);
    }

    /**
     * 删除索引 或 唯一索引
     *
     * @param string $tableNameNoPrefix
     * @param string $indexName
     * @return LUResult
     */
    public function alterTableDeleteIndex( string $tableNameNoPrefix, string $indexName ): LUResult
    {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableNameNoPrefix;

        $validTableName = LUWPSQLParamValidator::tableName($tableName);
        if ( $validTableName->isError() ) {
            return $validTableName;
        }

        $checkTableExist = $this->checkTableExist($tableName);
        if ( $checkTableExist->isError() ) {
            return $checkTableExist;
        }

        $safeIndexName = "`{$indexName}`";

        // 幂等处理：检查索引是否已存在
        $indexExists = $wpdb->get_results( $wpdb->prepare("SHOW INDEX FROM `{$tableName}` WHERE Key_name = %s", $indexName ));
        if ( empty( $indexExists ) ) {
            return LUResult::success([
                'table_name' => $tableName,
                'index_name' => $indexName
            ], '索引不存在');
        }

        // 构建 ADD INDEX 语句
        $sql = "ALTER TABLE `{$tableName}` DROP INDEX {$safeIndexName}";
        $result = $this->_query($sql);

        // 二次验证
        $indexNowExists = $wpdb->get_results($wpdb->prepare( "SHOW INDEX FROM `{$tableName}` WHERE Key_name = %s", $indexName ));
        if ( empty( $indexNowExists ) ) {
            return LUResult::success([
                'table_name' => $tableName,
                'index_name' => $indexName
            ], '索引已删除');
        }

        return LUResult::error(1000, '删除索引失败', [
            'table_name' => $tableName,
            'index_name' => $indexName
        ]);
    }

    /**
     * 自写SQL语句，操作前、操作后的验证也需要自行写
     *
     * @param string $sql
     * @param array $value
     * @return LUResult
     */
    public function query( string $sql, array $value ): LUResult
    {
        global $wpdb;
        $newSQL = $wpdb->prepare($sql, ...$value);

        $result = $this->_query($newSQL);
        if ( $result===false ) {
            return LUResult::error(1000, '操作失败', [
                'sql' => $sql,
                'new_sql' => $newSQL
            ]);
        }

        return LUResult::success([
            'sql'           => $sql,
            'result'        => $result
        ], '操作成功');
    }

    /** 内部使用*/
    private function _query( string $sql )
    {
        global $wpdb;
        return $wpdb->query( $sql );
    }


}
