<?php

namespace MAOSIJI\luphp;
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : code@maosiji.cn
 * date                 : 2024-09-20 17:50
 * update               :
 * project              : luphp
 * description          : 随机数与随机字符串的生成
 *
 * random_int()
 * random_bytes()
 */
if ( !class_exists( 'LURandom' ) ) {
	class LURandom
	{
		
		function __construct ()
		{
		}
		
		/**
		 * @param $length    int 随机数
         * @param bool $is_first_zero bool 首位是否可以为0，默认 true
		 *
		 * @return string		指定位数的随机数，适用于生成较小的数字
		 */
		public function getRandNumber( int $length = 6, bool $is_first_zero=true ): string
		{
            $is_first_not = $is_first_zero?'':'0';
            $str = '';

            switch ( mt_rand(0,2) ) {
                case 0:
                    $str .= $this->getRandNumberByMtRand($length);
                    break;
                case 1:
                    $str .= $this->getRandStrByStrShuffle($length, array('is_first_not' => $is_first_not, 'type' => array('number')));
                    break;
                case 2:
                    $str .= $this->getRandStrByShuffle($length, array('is_first_not' => $is_first_not, 'type' => array('number')));
                    break;
            }

            return $str;
		}

        /**
         * @param int $length 长度
         * @return int 使用 mt_rand 生成指定长度的随机整数，首位永远不会是 0
         */
        public function getRandNumberByMtRand( int $length=6 ): int
        {
            return mt_rand( pow( 10, $length - 1 ), pow( 10, $length ) - 1 );
        }
        /**
         * @return int 使用 mt_rand 生成随机奇数
         */
        public function getRandOddByMtRand(): int
        {
            return (mt_rand(0, 4) * 2) + 1;
        }
        /**
         * @return int 使用 mt_rand 生成随机偶数
         */
        public function getRandEvenByMtRand(): int
        {
            return mt_rand(0, 4) * 2;
        }

        /**
         * @param int $length 长度
         * @param array $params
         *              'type' 生成随机字符串的类型。默认 array('lowercase', 'uppercase', 'number')
         *                      'number' 数字
         *                      'lowercase' 小写字母
         *                      'uppercase' 大写字母
         *                      'special' 特殊符号
         *              'is_first_not' 首位不能是什么。示例：如不能是 0 或 a，那么就写 '0a'
         *              'custom' 自定义字符
         *              'custom_type' 自定义字符的添加方式
         *                      'add' type + custom，追加
         *                      'override' custom，覆盖
         *
         * @return string 使用 str_shuffle 生成指定长度的随机字符串
         */
        public function getRandStrByStrShuffle( int $length=6, array $params=array('type'=>array('lowercase', 'uppercase', 'number'), 'is_first_not'=>'', 'custom'=>'', 'custom_type'=>'') ): string
        {
            $params = array_merge(array(
                'type'              => array('lowercase', 'uppercase', 'number'),
                'is_first_not'      => '',
                'custom'            => '',
                'custom_type'       => '',
            ), $params);

            $str = $this->getEndStr($params);
            $randFirst = '';

            if ( $params['is_first_not']!=='' ) {
                $firstNot = $params['is_first_not'];
                $randFirst = implode('', array_map(function ($value) use ($firstNot) {
                    if ( strpos($firstNot, $value)===false ) {
                        return $value;
                    }
                }, str_split($str)));

                $randFirst = str_shuffle($randFirst);
                $randFirst = substr($randFirst,0,1);

                $length = $length-1;
            }

            $randDigits = str_repeat( $str, ceil( $length / strlen($str)) );
            $shuffled = str_shuffle( $randDigits );

            return $randFirst.substr($shuffled, 0, $length);
        }

        /**
         * @param int $length 长度
         * @param array $params
         *              'type' 生成随机字符串的类型。默认 array('lowercase', 'uppercase', 'number')
         *                      'number' 数字
         *                      'lowercase' 小写字母
         *                      'uppercase' 大写字母
         *                      'special' 特殊符号
         *              'is_first_not' 首位不能是什么。示例：如不能是 0 或 a，那么就写 '0a'
         *              'custom' 自定义字符
         *              'custom_type' 自定义字符的添加方式
         *                      'add' type + custom，追加
         *                      'override' custom，覆盖
         *
         * @return string 使用 shuffle 生成指定长度的随机整数字符串
         */
        public function getRandStrByShuffle( int $length=6, array $params=array('type'=>array('lowercase', 'uppercase', 'number'), 'is_first_not'=>'', 'custom'=>'', 'custom_type'=>'') ): string
        {
            $params = array_merge(array(
                'type'              => array('lowercase', 'uppercase', 'number'),
                'is_first_not'      => '',
                'custom'            => '',
                'custom_type'       => '',
            ), $params);

            $str = str_split($this->getEndStr($params));
            $result = '';

            while ( strlen( $result ) < $length ) {

                if ( $params['is_first_not']!=='' && strlen($result)===0 ) {
                    $firstNot = $params['is_first_not'];
                    $randFirst = array_map(function ($value) use ($firstNot) {
                        if ( strpos($firstNot, $value)===false ) {
                            return $value;
                        }
                    }, $str);

                    $randFirst = array_values(array_filter($randFirst, function($value) {
                        return !is_null($value) && $value !== '';
                    }));
                    shuffle( $randFirst );
                    $oneNum = $randFirst[mt_rand(0, count($randFirst)-1)];
                } else {
                    shuffle( $str );
                    $oneNum = $str[mt_rand(0, count($str)-1)];
                }

                $result .= $oneNum;
            }

            return $result;
        }

        /**
         * @param array $params
         * @return string 获取最终的字符串组合
         */
        private function getEndStr( array $params ): string
        {
            $str = '';

            if ( !empty($params['type']) ) {
                $str = implode('', array_map(function ($value){
                    return $this->getStr()[$value];
                }, $params['type']));
            }

            if ( !empty($params['custom']) ) {
                if ( $params['custom_type']==='add' ) {
                    $str .= $params['custom'];
                }
                if ( $params['custom_type']==='override' ) {
                    $str = $params['custom'];
                }
            }

            return $str;
        }

        /**
         * @return array{lowercase: string, uppercase: string, number: string, special: string}
         */
        private function getStr(): array
        {
            return array(
                'lowercase' => 'abcdefghijklmnopqrstuvwxyz',
                'uppercase' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                'number' => '0123456789',
                'special' => '~!@#$%^&*_+?',
            );
        }

	}
}
