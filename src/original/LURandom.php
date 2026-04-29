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

use MAOSIJI\LU\EXCEPTION\LURandomException;

if (!class_exists('LURandom')) {
    class LURandom
    {
        const CHAR_POOL = [
            'lowercase'         => 'abcdefghijklmnopqrstuvwxyz',
            'uppercase'         => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'number'            => '0123456789',
            'number_not_0'      => '123456789',
            'odd'               => '13579',
            'even'              => '02468',
            'even_not_0'        => '2468',
            'special'           => '!@#$%&*()_+-=[]'
        ];

        /**
         * 构造函数
         */
        public function __construct()
        {
            // 可以在这里初始化必要的逻辑（如果需要）
        }

        /**
         * 防止克隆实例
         */
        private function __clone()
        {
        }

        /**
         * 生成随机字符串
         * @param 'lowercase'|'uppercase'|'case'|'number'|'odd'|'even'|'special'| ...$charPools
         * @param int $length:长度
         * @param bool $isFirstCharNotZero:开头是否排除 0，默认 false
         * @param string $customChar:自定义字符
         * @return string
         */
        public function generateString( array $charPools, int $length=6, bool $isFirstCharNotZero=false, string $customChar='' ): string
        {
            $str = '';
            $char = '';

            if (!empty($charPools)) {
                foreach ( $charPools as $charPool ) {
                    if ( $isFirstCharNotZero ) {
                        if ( $charPool==='number' ) {
                            $charPool = 'number_not_0';
                        }
                        if ( $charPool==='even' ) {
                            $charPool = 'even_not_0';
                        }
                    }
                    $char .= self::CHAR_POOL[$charPool] ?? '';
                }
            }

            // 去除空格
            $customChar = str_replace( " ", "", $customChar );
            $firstChar = '';
            if ( !empty($customChar) && $isFirstCharNotZero ) {
                // 去除 0
                $customCharFirst = str_replace( "0", "", $customChar );
                // 首字母的字符串池（是否去 0）
                $charFirst = $char.$customCharFirst;
                $firstChar = $customCharFirst[mt_rand( 0, strlen($charFirst)-1 )];
            }

            // 字符串池
            $char = $char.$customChar;
            $charLength = strlen($char);

            for( $i=0; $i < $length-strlen($firstChar); $i++ ) {
                $index = mt_rand( 0, $charLength-1 );
                $str .= $char[$index];
            }

            return $firstChar.$str;
        }

        /**
         * 生成随机奇数
         *
         * @param int $length:长度
         * @return string
         */
        public function generateOdd( int $length = 6 ): string
        {
            $str = '';
            $charPool = self::CHAR_POOL['odd'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                $index = mt_rand( 0, $charPoolLength-1 );
                $str .= $charPool[$index];
            }

            return $str;
        }

        /**
         * 生成随机偶数
         *
         * @param int $length:长度
         * @param bool $isFirstCharNotZero:开头是否排除 0，默认 false
         * @return string
         */
        public function generateEven( int $length = 6, bool $isFirstCharNotZero=false ): string
        {
            $str = '';

            if ( $isFirstCharNotZero ) {
                $firstCharPool = self::CHAR_POOL['even_not_0'];
                $firstCharPoolLength = strlen($firstCharPool);
                $str .= $firstCharPool[mt_rand( 0, $firstCharPoolLength-1 )];
                $length = $length - 1;
            }

            $charPool = self::CHAR_POOL['even'];
            $charPoolLength = strlen($charPool);
            for( $i=0; $i < $length; $i++ ) {
                $index = mt_rand( 0, $charPoolLength-1 );
                $str .= $charPool[$index];
            }

            return $str;
        }

        /**
         * 生成随机小写字母
         *
         * @param int $length:长度
         * @return string
         */
        public function generateLowercase( int $length = 6 ): string
        {
            $str = '';
            $charPool = self::CHAR_POOL['lowercase'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                $index = mt_rand( 0, $charPoolLength-1 );
                $str .= $charPool[$index];
            }

            return $str;
        }

        /**
         * 生成随机大写字母
         *
         * @param int $length:长度
         * @return string
         */
        public function generateUppercase( int $length = 6 ): string
        {
            $str = '';
            $charPool = self::CHAR_POOL['uppercase'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                $index = mt_rand( 0, $charPoolLength-1 );
                $str .= $charPool[$index];
            }

            return $str;
        }

        /**
         * 生成随机字母
         *
         * @param int $length:长度
         * @return string
         */
        public function generateCase( int $length = 6 ): string
        {
            $str = '';
            $charPool = self::CHAR_POOL['uppercase'].self::CHAR_POOL['lowercase'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                $index = mt_rand( 0, $charPoolLength-1 );
                $str .= $charPool[$index];
            }

            return $str;
        }

        /**
         * 生成随机数字
         *
         * @param int $length:长度
         * @param bool $isFirstCharNotZero:开头是否排除 0，默认 false
         * @return string
         */
        public function generateNumber( int $length = 6, bool $isFirstCharNotZero=false ): string
        {
            $str = '';

            if ( $isFirstCharNotZero ) {
                $firstCharPool = self::CHAR_POOL['number_not_0'];
                $firstCharPoolLength = strlen($firstCharPool);
                $str .= $firstCharPool[mt_rand( 0, $firstCharPoolLength-1 )];
                $length = $length - 1;
            }

            $charPool = self::CHAR_POOL['number'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                $index = mt_rand( 0, $charPoolLength-1 );
                $str .= $charPool[$index];
            }

            return $str;
        }


        /*********************************************************
        ************安全 random_int 游戏逻辑、随机抽取、ID 生成**********************************
         * *********************************************************/

        /**
         * 生成安全随机字符串
         * @param 'lowercase'|'uppercase'|'case'|'number'|'odd'|'even'|'special'| ...$charPools
         * @param int $length:长度
         * @param bool $isFirstCharNotZero:开头是否排除 0，默认 false
         * @param string $customChar:自定义字符
         * @return string
         */
        public function generateSecureString( array $charPools, int $length=6, bool $isFirstCharNotZero=false, string $customChar='' ): string
        {
            $str = '';
            $char = '';

            if (!empty($charPools)) {
                foreach ( $charPools as $charPool ) {
                    if ( $isFirstCharNotZero ) {
                        if ( $charPool==='number' ) {
                            $charPool = 'number_not_0';
                        }
                        if ( $charPool==='even' ) {
                            $charPool = 'even_not_0';
                        }
                    }
                    $char .= self::CHAR_POOL[$charPool] ?? '';
                }
            }

            // 去除空格
            $customChar = str_replace( " ", "", $customChar );
            $firstChar = '';
            if ( !empty($customChar) && $isFirstCharNotZero ) {
                // 去除 0
                $customCharFirst = str_replace( "0", "", $customChar );
                // 首字母的字符串池（是否去 0）
                $charFirst = $char.$customCharFirst;
                try {
                    $firstChar = $customCharFirst[random_int(0, strlen($charFirst) - 1)];
                } catch (\Exception $e) {
                    throw new LURandomException( '安全随机数生成失败，请检查系统环境', LURandomException::CODE_SYSTEM_ERROR, $e );
                }
            }

            // 字符串池
            $char = $char.$customChar;
            $charLength = strlen($char);

            for( $i=0; $i < $length-strlen($firstChar); $i++ ) {
                try {
                    $index = random_int(0, $charLength - 1);
                } catch (\Exception $e) {
                    throw new LURandomException( '安全随机数生成失败，请检查系统环境', LURandomException::CODE_SYSTEM_ERROR, $e );
                }
                $str .= $char[$index];
            }

            return $firstChar.$str;
        }

        public function generateSecureOdd( int $length = 6 ): string
        {
            $str = '';
            $charPool = self::CHAR_POOL['odd'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new LURandomException( '安全随机数生成失败，请检查系统环境', LURandomException::CODE_SYSTEM_ERROR, $e );
                }
                $str .= $charPool[$index];
            }

            return $str;
        }
        public function generateSecureEven( int $length = 6, bool $isFirstCharNotZero=false ): string
        {
            $str = '';

            if ( $isFirstCharNotZero ) {
                $firstCharPool = self::CHAR_POOL['even_not_0'];
                $firstCharPoolLength = strlen($firstCharPool);
                try {
                    $str .= $firstCharPool[random_int(0, $firstCharPoolLength - 1)];
                } catch (\Exception $e) {
                    throw new LURandomException( '安全随机数生成失败，请检查系统环境', LURandomException::CODE_SYSTEM_ERROR, $e );
                }
                $length = $length - 1;
            }

            $charPool = self::CHAR_POOL['even'];
            $charPoolLength = strlen($charPool);
            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new LURandomException( '安全随机数生成失败，请检查系统环境', LURandomException::CODE_SYSTEM_ERROR, $e );
                }
                $str .= $charPool[$index];
            }

            return $str;
        }
        public function generateSecureLowercase( int $length = 6 ): string
        {
            $str = '';
            $charPool = self::CHAR_POOL['lowercase'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new LURandomException( '安全随机数生成失败，请检查系统环境', LURandomException::CODE_SYSTEM_ERROR, $e );
                }
                $str .= $charPool[$index];
            }

            return $str;
        }
        public function generateSecureUppercase( int $length = 6 ): string
        {
            $str = '';
            $charPool = self::CHAR_POOL['uppercase'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new LURandomException( '安全随机数生成失败，请检查系统环境', LURandomException::CODE_SYSTEM_ERROR, $e );
                }
                $str .= $charPool[$index];
            }

            return $str;
        }
        public function generateSecureCase( int $length = 6 ): string
        {
            $str = '';
            $charPool = self::CHAR_POOL['uppercase'].self::CHAR_POOL['lowercase'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new LURandomException( '安全随机数生成失败，请检查系统环境', LURandomException::CODE_SYSTEM_ERROR, $e );
                }
                $str .= $charPool[$index];
            }

            return $str;
        }
        public function generateSecureNumber( int $length = 6, bool $isFirstCharNotZero=false ): string
        {
            $str = '';

            if ( $isFirstCharNotZero ) {
                $firstCharPool = self::CHAR_POOL['number_not_0'];
                $firstCharPoolLength = strlen($firstCharPool);
                try {
                    $str .= $firstCharPool[random_int(0, $firstCharPoolLength - 1)];
                } catch (\Exception $e) {
                    throw new LURandomException( '安全随机数生成失败，请检查系统环境', LURandomException::CODE_SYSTEM_ERROR, $e );
                }
                $length = $length - 1;
            }

            $charPool = self::CHAR_POOL['number'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new LURandomException( '安全随机数生成失败，请检查系统环境', LURandomException::CODE_SYSTEM_ERROR, $e );
                }
                $str .= $charPool[$index];
            }

            return $str;
        }


        /*********************************************************
         ************random_bytes 安全令牌、密钥、盐值、IV**********************************
         * *********************************************************/

        /**
         * 生成偶数位随机字符（只包含 0-9 和 a-f）用于 安全令牌、密钥、盐值、IV
         *
         * 用 random_bytes 生成的十六进制字符串只包含 0-9 和 a-f
         *
         * @param int $length:长度，必须是偶数
         * @param bool $isUseStrict:是否启用严格模式，默认 true，即 $length 必须为偶数。
         *                          若为 false，则计算长度的结果直接舍弃小数部分。
         *
         * @throws LURandomException( $length, '严格模式下，长度不是偶数。' );
         * */
        public function generateSecureBytes( int $length = 32, bool $isUseStrict=true ): string
        {
            if ( $isUseStrict && $length % 2 !== 0 ) {
                throw new LURandomException( '严格模式下，长度必须是偶数。', LURandomException::CODE_INVALID_LENGTH, null, $length );
            }

            try {
                $str = bin2hex(random_bytes(intdiv($length, 2)));
            } catch (\Exception $e) {
                throw new LURandomException( '安全随机数生成失败，请检查系统环境', LURandomException::CODE_SYSTEM_ERROR, $e );
            }

            return $str;
        }


    }
}