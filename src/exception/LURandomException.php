<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-25 19:18
 * update               : 
 * project              : luphp
 * official website     : xyysd.cn
 * official name        : 小应用商店
 * official email       : 1211806667@qq.com
 * official wechat      : 1211806667
 * description          : 
 * read me              : 感谢您使用 小应用商店 的产品。您的支持，是我们最大的动力；您的反对，是我们最大的阻力
 * remind               ：使用盗版，存在风险；支持正版，将会有跟多的产品与您见面
 */

namespace MAOSIJI\LU\EXCEPTION;

class LURandomException extends LUBaseException
{
    /**
     * 系统环境不支持安全随机数
     * */
    const CODE_SYSTEM_ERROR = 100100;
    /**
     * 参数长度不是偶数（严格模式）
     * */
    const CODE_INVALID_LENGTH = 100101;
    /**
     * 字符池配置错误
     * */
    const CODE_INVALID_POOL = 100103;

    private $length;

    /**
     * @param string $message 错误描述
     * @param int $code 错误码
     * @param \Throwable|null $previous 原始异常（用于异常链）
     * @param int $length 导致错误的长度值（如不是偶数时）
     */
    public function __construct(
        string $message = '随机数生成失败',
        int $code = 0,
        \Throwable $previous = null,
        int $length = 0
    )
    {
        $this->length = $length;
        parent::__construct($message, $code, $previous);
    }

    public function getLogContext(): array
    {
        return array_merge(
            parent::getLogContext(),
            [
                'length' => $this->length
            ]
        );
    }

    public function getLength(): int
    {
        return $this->length;
    }
}