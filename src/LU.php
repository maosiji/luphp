<?php
namespace MAOSIJI\luphp;
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-11-25 22:24
 * update               : 
 * project              : luphp
 */
if ( !class_exists( 'LU' ) ) {
    class LU {

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
        public static function send_array( int $code, string $msg, $data='', string $reload='', array $newArr=array() ): array
        {
            return (new LUFormat())->sendArray( $code, $msg, $data, $reload, $newArr );
        }

        /**
         * @param string    $url	            链接
         * @param int	    $isOverWriteHeader	1 覆盖，0 合并
         * @param array     $headerNewArray	    Header数组
         *
         * @return array    返回的信息
         * */
        public static function curl_get( string $url, int $isOverWriteHeader = 0, array $headerNewArray = array() ): array
        {
            return (new LUCurl())->runGet( $url, $isOverWriteHeader, $headerNewArray );
        }

        public static function curl_post( string $url, array $data, int $isOverWriteHeader = 0, array $headerNewArray = array() ): array {
            return (new LUCurl())->runPost($url, $data, $isOverWriteHeader, $headerNewArray );
        }

        public static function curl_put( string $url, array $data, int $isOverWriteHeader = 0, array $headerNewArray = array() ): array
        {
            return (new LUCurl())->runPut($url, $data, $isOverWriteHeader, $headerNewArray );
        }

        public static function curl_delete( string $url, array $data, int $isOverWriteHeader = 0, array $headerNewArray = array() ): array
        {
            return (new LUCurl())->runDelete( $url, $data, $isOverWriteHeader, $headerNewArray );
        }

        public static function curl_Patch( string $url, array $data, int $isOverWriteHeader = 0, array $headerNewArray = array() ): array
        {
            return (new LUCurl())->runPatch( $url, $data, $isOverWriteHeader, $headerNewArray );
        }

        /*
         * 删除文件（在服务器上）
         *
         * $filepath 包含后缀的文件路径
         * */
        public static function delete_file( $filepath ): bool
        {
            return (new LUFile())->deleteFile( $filepath );
        }

        /*
         * 上传图片到自定义目录（非wp-content/uploads/2022/05/）
         *
         * @param string $folderPath		文件夹路径
         * @param array $filename_arr	文件名数组  key 为 input name，value 为自定义的文件名，注意与上传时的后缀一致
         * 					array(
         * 						'sfz_a' => 1.jpg
         * 						'sfz_b' => 2.jpg
         * 					)
         * @param $files			即 $_FILES
         * @param string $url		图片url前缀地址，即不包括图片文件名及后缀的链接
         * 					如：http://maosiji.com/wp-content/uploads/myfolder/
         * @return			LUFormat->sendArray
         *                  array(
         * 						'status'=>'1',
         * 						'msg'	=> array(
         * 							'sfz_a' => http://maosiji.com/wp-content/uploads/myfolder/1.jpg
         * 							'sfz_b' => http://maosiji.com/wp-content/uploads/myfolder/2.jpg
         * 						),
         * 					)
         * */
        public static function upload_file( string $folderPath, array $filename_arr, $files, string $url='' ): array
        {
            return (new LUFile())->uploadFile( $folderPath, $filename_arr, $files, $url );
        }

        /**
         * @param int $timediff :自定义时间间隔，默认5秒
         *
         * @return void :判断是否连续点击 ajax 按钮，并禁止
         */
        public static function check_too_many_requests( int $timediff )
        {
            (new LUSafe())->checkTooManyRequests( $timediff );
        }

        /**
         * 为 session 或 cookie 设置一组键值对，可用于验证码
         *
         * @param string $key	        : 键
         * @param string $value	        : 值
         * @param string $type		 	: 保存在 session 还是 cookie。默认值 all，全都保存；可选 session、cookie，只保存在其中一个。
         * @param int    $timediff		: 时间间隔，默认600秒，即10分钟。单位是 秒。用于cookie的设置。
         *
         */
        public static function set_session_and_cookie_key_value( string $key, string $value, string $type='all', int $timediff=600 )
        {
            if ( $type === 'all' ) {
                (new LUSession())->setKeyValue( $key, $value );
                (new LUCookie())->setKeyValue( $key, $value, $timediff );
            }
            if ( $type === 'session' ) {
                (new LUSession())->setKeyValue( $key, $value );
            }
            if ( $type === 'cookie' ) {
                (new LUCookie())->setKeyValue( $key, $value, $timediff );
            }
        }

        /**
         * @param string $key : 键
         * @param string $type : 默认 all。保存在 session 还是 cookie。默认值 all，全都保存；可选 session、cookie，只保存在其中一个。
         * @return string : 返回对应的值，若未返回，则1、没有该key，2、session与cookie得到的value不一致。
         */
        public static function get_session_and_cookie_key_value( string $key, string $type='all' ): string
        {
            if ( $type === 'all' ) {
                $sessionValue = (new LUSession())->getKeyValue( $key );
                $cookieValue = (new LUCookie())->getKeyValue( $key );
                if ( $sessionValue===$cookieValue ) {
                    return $sessionValue;
                }
            }
            else if ( $type==='session' ) {
                return (new LUSession())->getKeyValue( $key );
            }
            else if ( $type==='cookie' ) {
                return (new LUCookie())->getKeyValue( $key );
            }

            return '';
        }

        /**
         * @param string $key           : 键
         * @param string $value	        : 值
         * @param string $type          : 默认 all。保存在 session 还是 cookie。默认值 all，全都保存；可选 session、cookie，只保存在其中一个。
         * @return bool                 :
         */
        public static function check_session_and_cookie_key_value( string $key, string $value, string $type='all' ): bool
        {
            if ( $type==='all' ) {
                $sessionValue = (new LUSession())->checkKeyValue( $key, $value );
                $cookieValue = (new LUCookie())->checkKeyValue( $key, $value );

                if ( $sessionValue===$cookieValue ) {
                    return true;
                }
            }
            else if ( $type==='session' ) {
                return (new LUSession())->checkKeyValue( $key, $value );
            }
            else if ( $type==='cookie' ) {
                return (new LUCookie())->checkKeyValue( $key, $value );
            }

            return false;
        }

        /**
         * @param string $key           : 键
         * @param string $type          : 默认 all。保存在 session 还是 cookie。默认值 all，全都保存；可选 session、cookie，只保存在其中一个。
         */
        public static function delete_session_and_cookie_key_value( string $key, string $type='all' )
        {
            if ( $type==='all' ) {
                (new LUSession())->deleteKeyValue( $key );
                (new LUCookie())->deleteKeyValue( $key );
            }
            if ( $type==='session' ) {
                (new LUSession())->deleteKeyValue( $key );
            }
            if ( $type==='cookie' ) {
                (new LUCookie())->deleteKeyValue( $key );
            }
        }

        /**
         * @param int $begin_time		: 时间戳 开始时间
         * @param int $end_time        	: 时间戳 结束时间
         *
         * @return array        返回时间间隔数组 array('day'=>'', 'hour'=>'', 'min'=>'', 'sec=>'')
         */
        public static function calculate_timediff( int $begin_time, int $end_time ): array
        {
            return (new LUTime())->calculateTimediff( $begin_time, $end_time );
        }

        /**
         * @param $length    int 随机数位数
         *
         * @return int		指定位数的随机数
         */
        public static function get_rand_number( int $length = 6 ): int
        {
            return (new LUTool())->getRandNumber( $length );
        }


        /**
         * @param bool $isFilterParam :  是否过滤掉参数
         * @return string 当前网页链接
         */
        public static function get_current_url( bool $isFilterParam ): string
        {
            return (new LUUrl())->getCurrentUrl($isFilterParam);
        }

        /**
         * 给指定链接删除参数
         *
         * @param $url :指定链接。为空，则默认当前链接
         * @param $arr :需要删除的参数数组。为空，则全部删除
         *
         * @return string 返回删除指定参数后的链接
         */
        public static function delete_url_param( array $arr, string $url='' ): string
        {
            return (new LUUrl())->deleteUrlParam( $arr, $url );
        }

        /**
         * @param $url: 指定链接。为空，则默认当前链接
         * @param $arr: 需要添加的参数数组。为空，则返回链接
         *
         * @return string    添加指定参数后的链接
         */
        public static function add_url_param( array $arr, string $url='' ): string
        {
            return (new LUUrl())->addUrlParam( $arr, $url );
        }

        /**
         * @param string $version	: 版本号，空值则返回false
         *
         * @return bool		检测版本号格式是否正确。检测结果：true 是，false 否
         *               版本号格式一：10.0.24.458
         *  			 版本号格式一：10.0.24
         *  			 版本号格式一：10.0
         *  			 版本号格式一：10
         */
        public static function check_version( string $version ): bool
        {
            return (new LUVersion())->checkVersion( $version );
        }



    }

}