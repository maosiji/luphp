<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-09-30 12:26
 * update               :
 * project              : luphp
 */
namespace MAOSIJI\LU\WP;
if ( ! defined( 'ABSPATH' ) ) { die; }
if ( ! class_exists( 'LUWPSend' ) ) {
    class LUWPSend
    {
        function __construct()
        {

        }
        private function __clone()
        {
        }
        private function __wakeup()
        {
        }

        /**
         * WP原生 发送 格式化数组，并终止程序
         * @param int       $code   : 状态码
         * @param string    $msg    : 提示信息
         * @param Mixed    	$data   : 数据
         * @param string    $reload : 是否刷新页面 （0 不跳转，1 刷新当前页面，'https://maosiji.com' 跳转到该链接）
         * @param array  	$newArr : 需要合并的数组
         */
        public function send_json(int $code, string $msg, $data = '', string $reload = '', array $newArr = [], int $flags = 0)
        {
            // 格式化响应数据
            $response = (new \MAOSIJI\LU\LUSend())->send_array($code, $msg, $data, $reload, $newArr);

            // 根据状态码选择不同的 WordPress 方法2
//            if ($code === 1) {
//                wp_send_json_success(array_merge($response, ['success' => true]), 200, $flags);
//            } elseif ($code === 0) {
//                wp_send_json_error(array_merge($response, ['success' => false]), 100, $flags);
//            } else {
                wp_send_json($response, 200, $flags);
//            }
        }




    }
}