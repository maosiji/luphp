<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-09-20 17:50
 * update               :
 * project              : luphp
 */

namespace MAOSIJI\LU;

class LUVersion
{
    function __construct (  )
    {

    }

    /**
     * 检测版本号格式是否正确
     * @param string $version	: 版本号，空值则返回false
     *
     * @return bool	:检测版本号格式是否正确。检测结果：true 是，false 否
     *
     *               版本号格式一：10.0.24.458
     *  			 版本号格式一：10.0.24
     *  			 版本号格式一：10.0
     *  			 版本号格式一：10
     */
    public function isVersion( string $version ): bool
    {
        // 1. 去除首尾空格，防止 " 1.0 " 这种输入导致误判
        $version = trim($version);

        if ($version === '') {
            return false;
        }

        /**
         * 正则解析：
         * ^                : 开头
         * \d+              : 第一段必须至少一位数字
         * (?:\.\d+){0,3}   : 后面跟着 0 到 3 组 ".数字" 的组合
         *                    - 0组 -> 总共1段 (10)
         *                    - 1组 -> 总共2段 (10.0)
         *                    - 2组 -> 总共3段 (10.0.24)
         *                    - 3组 -> 总共4段 (10.0.24.458)
         * $                : 结尾
         */
        return preg_match('/^\d+(?:\.\d+){0,3}$/', $version) === 1;
    }




}
