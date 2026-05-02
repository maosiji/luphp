<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-26 01:59
 * update               : 
 * project              : luphp
 */

namespace MAOSIJI\LU\EXCEPTION;

class LUVerifiableNumber19Exception extends LUBaseException
{
    /**
     * 系统环境不支持安全随机数
     * */
    const CODE_SYSTEM_ERROR = 140100;

    /**
     * 前缀必须是纯数字
     * */
    const CODE_INVALID_PREFIX = 140101;

    /**
     * 传入的19位号码不是有效字符串（长度不足/非全数字）
     * */
    const CODE_INVALID_NUMBER = 140102;

    private $numberChar;

    public function __construct(
        string $message = '随机数生成失败',
        int $code = 0,
        \Throwable $previous = null,
        string $numberChar = ''
    )
    {
        $this->numberChar = $numberChar;
        parent::__construct($message, $code, $previous);
    }

    public function getNumberChar(): string
    {
        return $this->numberChar;
    }

    public function getLogContext(): array
    {
        return array_merge(
            parent::getLogContext(),
            [
                'numberChar' => $this->getNumberChar(),
            ]
        );
    }
}