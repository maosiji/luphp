<?php

namespace MAOSIJI\LUPHP\upload;
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : code@maosiji.cn
 * date                 : 2024-09-20 17:50
 * update               :
 * project              : phpsuda
 * official website     : maosiji.com
 * official name        : PHP速搭
 * description          : 这家伙很懒，没有写介绍
 * read me              :
 * remind               ：
 */
if ( !class_exists( 'MaoWpUpload' ) ) {
	class MaoWpUpload
	{
		function __construct ()
		{
		
		}
		
		/*
	 * 上传图片到 uploads
	 *
	 * $file			$_FILES
	 * $input_name		文件标识
	 * */
		public function uploadFile( $file, $input_name ) {
			
			if( $file ) {
				
				if ( empty($file[$input_name]['name']) ) {
					return array( 'status'=>'0', 'msg'=>'未找到 input_name 附件！' );
				}
				
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				require_once( ABSPATH . 'wp-admin/includes/media.php' );
				
				$attachment_id  = media_handle_upload( $input_name, '' );
				$attachment_url = wp_get_attachment_image_src( $attachment_id, 'full' )[0];
				
				if ( $attachment_url ) {
					return array( 'status'=>'1', 'msg'=>array('id'=>$attachment_id, 'url'=>$attachment_url) );
				} else {
					return array( 'status'=>'0', 'msg'=>'上传失败，请重试！' );
				}
				
			} else {
				return array( 'status'=>'0', 'msg'=>'FILES 为空！' );
			}
		}
		
		
		
		
		
	}
}
