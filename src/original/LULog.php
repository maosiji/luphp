<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-07-25 01:05
 * update               : 
 * project              : luphp
 * official website     : xiaoyingyong.cn
 * official name        : 小应用商店
 * official email       : 1211806667@qq.com
 * official WeChat      : 1211806667
 * description          : 
 * read me              : 感谢您使用 小应用商店 的产品。您的支持，是我们最大的动力；您的反对，是我们最大的阻力
 * remind               ：使用盗版，存在风险；支持正版，将会有跟多的产品与您见面
 */

namespace MAOSIJI\LU;
if (!class_exists('LULog')) {
    class LULog
    {
        public function __construct()
        {
        }
        private function __clone()
        {
        }
        private function __wakeup()
        {
        }

        public function print($message,$file) {

            //将日志文件放在根目录下/log/日期的文件夹名
            $log_dir=$_SERVER['DOCUMENT_ROOT']."/log/".date('Ymd')."/";
            //判断是否存在文件夹，没有则创建
            if(!is_dir($log_dir)){
                @mkdir($log_dir,0777,true);
            }
            //将错误日志记录写入文件中
            $file=$log_dir.$file;
            if(is_array($message)){
                $arr=explode(".",$file);
                if($arr[1]=='php'){
                    error_log("<?php \n return ".var_export($message, true)."\n", 3,$file);
                }else{
                    error_log(var_export($message, true)."\n", 3,$file);
                }
            }else{
                error_log($message."\n\n", 3,$file);
            }
        }



    }
}