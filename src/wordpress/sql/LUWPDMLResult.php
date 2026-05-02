<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-30 02:29
 * update               : 
 * project              : luphp
 * description          : DML 插入操作的结果对象
 *
 *      封装插入后返回的自增 ID 和受影响行数
 */

namespace MAOSIJI\LU\WP\SQL;
class LUWPDMLResult
{
    /** @var int 新插入行的自增 ID */
    private $insertId;
    /** @var int 受影响的行数（通常为 1） */
    private $affectedRows;

    public function __construct( $insertId, $affectedRows )
    {
        $this->insertId = $insertId;
        $this->affectedRows = $affectedRows;
    }

    /**
     * 获取插入记录的自增 ID
     *
     * @return int
     */
    public function getInsertId(): int
    {
        return $this->insertId;
    }

    /**
     * 获取受影响的行数
     *
     * @return int
     */
    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }
}