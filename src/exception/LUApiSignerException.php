<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-25 19:19
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

class LUApiSignerException extends LUBaseException
{
    /**
     * 系统环境不支持安全随机数
     * */
    const CODE_SYSTEM_ERROR = 400100100;

    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}