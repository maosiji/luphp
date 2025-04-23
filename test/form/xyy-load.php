<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-02-28 10:09
 * update               : 
 * project              : 内部 - 心理咨询记录 - ‌珊瑚
 * official website     : xiaoyingyong.cn
 * official name        : 小应用商店
 * official email       : 1211806667@qq.com
 * official WeChat      : 1211806667
 * description          : 
 * read me              : 感谢您使用 小应用商店 的产品。您的支持，是我们最大的动力；您的反对，是我们最大的阻力
 * remind               ：使用盗版，存在风险；支持正版，将会有跟多的产品与您见面
 */

// form里的数据类型转换成mysql里的格式
function xyysd_form_convert_mysql_format( string $str ): string
{
    if ( $str==='string' || $str==='html' ) {
        return '%s';
    }
    if ( $str==='int' ) {
        return '%d';
    }
    if ( $str==='float' ) {
        return '%f';
    }

    return false;
}

require_once __DIR__ . '/FormHtml.php';
require_once __DIR__ . '/FormVerify.php';
require_once __DIR__ . '/FormData.php';
//require_once __DIR__ . '/FormDataFactory.php';
require_once __DIR__ . '/FormRoute.php';
