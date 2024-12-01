<?php
namespace MAOSIJI\luphp;
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-11-23 20:27
 * update               : 
 * project              : luphp
 */
if ( ! defined( 'ABSPATH' ) ) { die; }
if ( ! class_exists( 'LUFormat' ) ) {
    class LUFormat {

        function __construct() {}

        /**
         * 发送的数组格式化
         * @param int       $code   : 状态码
         * @param string    $msg    : 提示信息
         * @param Mixed    	$data   : 数据
         * @param string    $reload : 是否刷新页面 （0 不跳转，1 刷新当前页面，'https://maosiji.com' 跳转到该链接）
         * @param array  	$newArr : 需要合并的数组
         *
         * @return array
         */
        public function sendArray( int $code, string $msg, $data='', string $reload='', array $newArr=array() ): array
        {
            return array_merge( array(
                'code' => $code,
                'msg'  => $msg,
                'data' => $data,
                'reload' => $reload,
            ), $newArr);
        }

    }
}