<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-12-18 16:00
 * update               : 
 * project              : luphp
 */

namespace MAOSIJI\LU;
class LUPHP {
    public function __construct()
    {
    }
    public function __clone()
    {
    }

    /**
     * 规格化字符串的换行符（不管哪个系统发来的字符串，里面的换行符都转换成当前系统的换行符）
     *
     * @param string $str
     * @return string
     */
    public static function normalize_str_eol( string $str ):string
    {
        return str_replace("\n", PHP_EOL, str_replace( ["\r\n", "\r"], "\n", $str ) );
    }




}