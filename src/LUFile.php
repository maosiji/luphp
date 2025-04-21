<?php
namespace MAOSIJI\LUPHP;
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-11-25 17:45
 * update               : 
 * project              : luphp
 */
if ( !class_exists( 'LUFile' ) ) {
    class LUFile
    {
        function __construct(){}

        /**
         * @param string $filePath  文件路径（包含后缀）
         * @return bool             true 成功，false 失败
         */
        public function delete( string $filePath ): bool
        {
            if ( file_exists($filePath) && unlink($filePath) ) {
                return true;
            }

            return false;
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
        public function uploadFile( string $folderPath, array $filename_arr, $files, string $url='' ): array
        {

            $file_url_arr = array();

            if ( empty($url) ) {
                $url = (new LUUrl())->get();
            }

            if ( !file_exists($folderPath) ) {
                if ( !mkdir($folderPath, 0777, TRUE) ) {
                    return (new LUSend())->send_array(0, '目录创建失败');
                }
            }

            foreach ( $filename_arr as $key=>$value ) {
                $filePath = $folderPath.$value;
                if ( move_uploaded_file( $files[$key]['tmp_name'], $filePath ) ) {
                    $file_url_arr[$key] = $url.$value;
                } else {
                    return (new LUSend())->send_array(0, '上传失败');
                }
            }

            return (new LUSend())->send_array(1, '上传成功', $file_url_arr);
        }



    }
}