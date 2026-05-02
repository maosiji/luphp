<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-25 19:26
 * update               : 
 * project              : luphp
 */

namespace MAOSIJI\LU\EXCEPTION;

class LUIpException extends LUBaseException
{
    /**
     * IP 地址获取失败
     * */
    const CODE_GET_IP_FAILED = 130100;

    public function __construct( string $message = "", int $code = 0, \Throwable $previous = null )
    {
        parent::__construct($message, $code, $previous);
    }

}