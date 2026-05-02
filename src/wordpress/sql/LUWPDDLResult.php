<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-30 02:24
 * update               : 
 * project              : luphp
 * description          : DDL 创建表操作的结果对象
 *
 *      明确表达两种正常结果：
 *          - 表被新创建
 *          - 表已存在（幂等）
 *      任何真正的失败都通过异常抛出。
 */

namespace MAOSIJI\LU\WP\SQL;
class LUWPDDLResult
{
    /** @var string 状态：'created' 或 'already_exists' */
    private $status;
    /** @var string 完整的表名（含前缀） */
    private $tableName;

    private function __construct( $status, $tableName )
    {
        $this->status = $status;
        $this->tableName = $tableName;
    }

    /**
     * 创建“表已新建”结果
     *
     * @param string $tableName
     * @return self
     */
    public static function created($tableName ): LUWPDDLResult
    {
        // return new self('created', $tableName);
        return new self('表已创建', $tableName);
    }


    /**
     * 创建“表已存在”结果
     *
     * @param string $tableName
     * @return self
     */
    public static function alreadyExists($tableName ): LUWPDDLResult
    {
        // return new self('already_exists', $tableName);
        return new self('表已存在', $tableName);
    }

    /**
     * 判断本次操作是否真正创建了表
     *
     * @return bool
     */
    public function isCreated(): bool
    {
        return $this->status === 'created';
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }
}