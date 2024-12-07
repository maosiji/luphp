<?php
namespace MAOSIJI\luphp;
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : code@maosiji.cn
 * date                 : 2024-09-20 17:50
 * update               :
 * project              : luphp
 */
if ( !class_exists( 'LUUrl' ) ) {
	class LUUrl
	{
		
		function __construct ()
		{
		}
		
		
		/**
         * @param bool $isFilterParam :  是否过滤掉参数
		 * @return string : 当前网页链接
		 */
		public function getCurrentUrl ( bool $isFilterParam=false ): string
		{
			
			$sys_protocal = isset( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
			$php_self = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'];
			$path_info = $_SERVER['PATH_INFO'] ?? '';
			$relate_url = $_SERVER['REQUEST_URI'] ?? $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
			
			return $sys_protocal . ($_SERVER['HTTP_HOST'] ?? '') . ($isFilterParam?'':$relate_url);
		}
		
		/**
		 * 给指定链接删除参数
		 *
		 * @param $url :指定链接。为空，则默认当前链接
		 * @param $arr :需要删除的参数数组。为空，则全部删除
		 *
		 * @return string 返回删除指定参数后的链接
		 */
		public function deleteUrlParam ( array $arr, string $url='' ): string
		{
			$p = !empty( $arr ) ? $arr : array();
			if ( count( $p ) == 0 ) {
				return $url;
			}
			
			$url = !empty( $url ) ? $url : $this->getCurrentUrl();
			
			parse_str( parse_url( $url, PHP_URL_QUERY ), $params );
			
			foreach ( $p as $pkey=>$pval ) {
				unset( $params[$pkey] );
			}
			
			$query = http_build_query( $params );
			
			if ( strpos( $url, '?' ) ) {
				$this_url = strstr( $url, '?', TRUE );
			} else {
				$this_url = $url;
			}
			
			return $this_url . '?' . $query;
		}
		
		/**
		 * @param $url: 指定链接。为空，则默认当前链接
		 * @param $arr: 需要添加的参数数组。为空，则返回链接
		 *
		 * @return string    添加指定参数后的链接
		 */
		public function addUrlParam ( array $arr, string $url='' ): string
		{
			$p = !empty( $arr ) ? $arr : array();
			if ( count( $p ) == 0 ) {
				return $url;
			}
			
			$url = !empty( $url ) ? $url : $this->getCurrentUrl();
			
			parse_str( parse_url( $url, PHP_URL_QUERY ), $params );
			
			foreach ( $p as $pkey => $pvalue ) {
				$params[$pkey] = $pvalue;
			}
			
			$query = http_build_query( $params );
			
			if ( strpos( $url, '?' ) ) {
				$this_url = strstr( $url, '?', TRUE );
			} else {
				$this_url = $url;
			}
			
			return $this_url . '?' . $query;
		}
		
		
	}

//    $luurl = new LUUrl();
//    echo $luurl->getCurrentUrl( true );
//    echo '<br>';
//    echo $luurl->deleteUrlParam( array('d'=>'3') );
//    echo '<br>';
//    echo $luurl->addUrlParam( array('a'=>'66') );
}
