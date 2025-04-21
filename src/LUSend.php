<?php
namespace MAOSIJI\LUPHP;
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-09-30 12:26
 * update               :
 * project              : luphp
 */
if ( ! defined( 'ABSPATH' ) ) { die; }
if ( ! class_exists( 'LUSend' ) ) {
    class LUSend
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
         * 将数组格式化
         * @param int       $code   : 状态码
         * @param string    $msg    : 提示信息
         * @param Mixed    	$data   : 数据
         * @param string    $reload : 是否刷新页面 （0 不跳转，1 刷新当前页面，'https://maosiji.com' 跳转到该链接）
         * @param array  	$newArr : 需要合并的数组
         *
         * @return array
         */
        public function send_array( int $code, string $msg, $data='', string $reload='0', array $newArr=array() ): array
        {
            return array_merge( array(
                'code' => $code,
                'msg'  => $msg,
                'data' => $data,
                'reload' => $reload,
            ), $newArr);
        }

        /**
         * 发送 格式化数组，并终止程序
         * @param int       $code   : 状态码
         * @param string    $msg    : 提示信息
         * @param Mixed    	$data   : 数据
         * @param string    $reload : 是否刷新页面 （0 不跳转，1 刷新当前页面，'https://maosiji.com' 跳转到该链接）
         * @param array  	$newArr : 需要合并的数组
         */
        public function send_json( int $code, string $msg, $data='', string $reload='0', array $newArr=array() )
        {
            // 设置响应头为 JSON 格式
            header('Content-Type: application/json');
            echo json_encode( $this->send_array( $code, $msg, $data, $reload, $newArr ) );
            exit();
        }




    }
}