<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-29 22:23
 * update               : 
 * project              : luphp
 * description          : 数据库操作异常类
 *
 *      统一管理所有数据库层可能抛出的异常，继承自定义的基础异常 LUBaseException，
 *      并附加上下文信息便于调试和日志记录
 */

namespace MAOSIJI\LU\EXCEPTION;

/**
 * Class LUDatabaseException
 *
 * 常用错误码常量：
 *
 *          CODE_TABLE_NOT_FOUND  - 表不存在
 *          CODE_QUERY_FAILED     - 查询/执行失败
 *          CODE_INVALID_PARAM    - 参数无效
 *          CODE_DUPLICATE_ENTRY  - 重复条目
 *          CODE_CREATE_FAILED    - 创建表失败
 */
class LUDatabaseException extends LUBaseException
{
    const CODE_TABLE_NOT_FOUND = 900100;
    const CODE_QUERY_FAILED    = 900101;
    const CODE_INVALID_PARAM   = 900102;
    const CODE_DUPLICATE_ENTRY = 900103;
    const CODE_CREATE_FAILED   = 900104;
    const CODE_INVALID_SQL     = 900105;
    const CODE_INVALID_TABLE_NAME = 900106;

    /**
     * 附加上下文信息（如原始 SQL、参数等）
     * @var array
     */
    private $context;
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null, array $context = [])
    {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    public function getLogContext(): array
    {
        return array_merge(
            parent::getLogContext(),
            [
                "db_context" => $this->context
            ]
        );
    }

    public function getContext(): array
    {
        return $this->context;
    }


}