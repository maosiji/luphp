<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-29 15:58
 * update               : 
 * project              : luphp
 */

namespace MAOSIJI\LU\EXCEPTION;

class LUURLException extends LUBaseException
{
    /**
     * url不合法
     * */
    const CODE_INVALID_URL = 150100;

    private $url;

    public function __construct( string $message = "", int $code = 0, \Throwable $previous = null, $url=null )
    {
        $this->url = $url;
        parent::__construct($message, $code, $previous);
    }

    public function getURL()
    {
        return $this->url;
    }

    public function getLogContext(): array
    {
        return array_merge(
            parent::getLogContext(),
            [
                'url' => $this->getURL(),
            ]
        );
    }

}