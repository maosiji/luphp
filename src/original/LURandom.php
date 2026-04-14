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

if (!class_exists('LURandom')) {
    class LURandom
    {
        const MSG_SUCCESS = '随机数 生成成功';
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
         * 使用 mt_rand 生成随机奇数
         *
         * @deprecated {@see generateOdd() }
         *
         * @return int 随机奇数
         */
        public function rand_odd(): int
        {
            trigger_error(
                'LURandom::rand_odd() 已废弃，将在 2.0.0 版本移除，请使用 generateOdd() 替代',
                E_USER_DEPRECATED
            );

            return (mt_rand(0, 4) * 2) + 1;
        }

        /**
         * 生成随机奇数
         *
         * @param int $length:长度
         * @return LUResult
         */
        public function generateOdd( int $length = 6 ): LUResult
        {
            $str = '';
            $charPool = self::CHAR_POOL['odd'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                $index = mt_rand( 0, $charPoolLength-1 );
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }

        /**
         * 生成随机偶数
         *
         * @param int $length:长度
         * @param bool $isFirstCharNotZero:开头是否排除 0，默认 false
         * @return LUResult
         */
        public function generateEven( int $length = 6, bool $isFirstCharNotZero=false ): LUResult
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

            return LUResult::success( $str, self::MSG_SUCCESS );
        }

        /**
         * 生成随机小写字母
         *
         * @param int $length:长度
         * @return LUResult
         */
        public function generateLowercase( int $length = 6 ): LUResult
        {
            $str = '';
            $charPool = self::CHAR_POOL['lowercase'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                $index = mt_rand( 0, $charPoolLength-1 );
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }

        /**
         * 生成随机大写字母
         *
         * @param int $length:长度
         * @return LUResult
         */
        public function generateUppercase( int $length = 6 ): LUResult
        {
            $str = '';
            $charPool = self::CHAR_POOL['uppercase'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                $index = mt_rand( 0, $charPoolLength-1 );
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }

        /**
         * 生成随机字母
         *
         * @param int $length:长度
         * @return LUResult
         */
        public function generateCase( int $length = 6 ): LUResult
        {
            $str = '';
            $charPool = self::CHAR_POOL['uppercase'].self::CHAR_POOL['lowercase'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                $index = mt_rand( 0, $charPoolLength-1 );
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }

        /**
         * 生成随机数字
         *
         * @param int $length:长度
         * @param bool $isFirstCharNotZero:开头是否排除 0，默认 false
         * @return LUResult
         */
        public function generateNumber( int $length = 6, bool $isFirstCharNotZero=false ): LUResult
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

            return LUResult::success( $str, self::MSG_SUCCESS );
        }

        /**
         * 生成随机字母 + 数字
         *
         * @param int $length:长度
         * @param bool $isFirstCharNotZero:开头是否排除 0，默认 false
         * @return LUResult
         */
        public function generateCaseAndNumber( int $length = 6, bool $isFirstCharNotZero=false ): LUResult
        {
            $str = '';

            if ( $isFirstCharNotZero ) {
                $firstCharPool = self::CHAR_POOL['uppercase'].self::CHAR_POOL['lowercase'].self::CHAR_POOL['number_not_0'];
                $firstCharPoolLength = strlen($firstCharPool);
                $str .= $firstCharPool[mt_rand( 0, $firstCharPoolLength-1 )];
                $length = $length - 1;
            }

            $charPool = self::CHAR_POOL['uppercase'].self::CHAR_POOL['lowercase'].self::CHAR_POOL['number'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                $index = mt_rand( 0, $charPoolLength-1 );
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }

        /**
         * 生成随机特殊符号
         *
         * @param int $length:长度
         * @return LUResult
         */
        public function generateSpecial( int $length = 6 ): LUResult
        {
            $str = '';
            $charPool = self::CHAR_POOL['special'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                $index = mt_rand( 0, $charPoolLength-1 );
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }

        /**
         * 生成随机字母 + 数字 + 特殊字符
         *
         * @param int $length:长度
         * @param bool $isFirstCharNotZero:开头是否排除 0，默认 false
         * @return LUResult
         */
        public function generateCaseAndNumberAndSpecial( int $length = 6, bool $isFirstCharNotZero=false ): LUResult
        {
            $str = '';

            if ( $isFirstCharNotZero ) {
                $firstCharPool = self::CHAR_POOL['uppercase'].self::CHAR_POOL['lowercase'].self::CHAR_POOL['number_not_0'].self::CHAR_POOL['special'];
                $firstCharPoolLength = strlen($firstCharPool);
                $str .= $firstCharPool[mt_rand( 0, $firstCharPoolLength-1 )];
                $length = $length - 1;
            }

            $charPool = self::CHAR_POOL['uppercase'].self::CHAR_POOL['lowercase'].self::CHAR_POOL['number'].self::CHAR_POOL['special'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                $index = mt_rand( 0, $charPoolLength-1 );
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }

        /**
         * 生成随机自定义字符
         *
         * @param string $custom:自定义字符
         * @param int $length:长度
         * @return LUResult
         */
        public function generateCustom( string $custom, int $length = 6 ): LUResult
        {
            if (empty($custom)) return LUResult::error(1000, '自定义字符不可为空');

            $str = '';
            $charPool = $custom;
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                $index = mt_rand( 0, $charPoolLength-1 );
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }

        /**
         * 生成随机自定义字符 + 已有字符
         *
         * @param string $custom:自定义字符
         * @param array $charPools:已有字符数组，可选值：lowercase/uppercase/number/number_not_0/odd/even/even_not_0/special
         * @param int $length:长度
         * @return LUResult
         */
        public function generateCustomAdd( string $custom, array $charPools, int $length = 6 ): LUResult
        {
            if (empty($custom)) return LUResult::error(1000, '自定义字符不可为空');

            $charPool = '';
            if (!empty($charPools)) {
                foreach ( $charPools as $cp ) {
                    $charPool .= self::CHAR_POOL[$cp] ?? '';
                }
            }

            $str = '';
            $charPool .= $custom;
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                $index = mt_rand( 0, $charPoolLength-1 );
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }

        /*********************************************************
        ************安全 random_int 游戏逻辑、随机抽取、ID 生成**********************************
         * *********************************************************/

        public function generateSecureOdd( int $length = 6 ): LUResult
        {
            $str = '';
            $charPool = self::CHAR_POOL['odd'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
                }
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }
        public function generateSecureEven( int $length = 6, bool $isFirstCharNotZero=false ): LUResult
        {
            $str = '';

            if ( $isFirstCharNotZero ) {
                $firstCharPool = self::CHAR_POOL['even_not_0'];
                $firstCharPoolLength = strlen($firstCharPool);
                try {
                    $str .= $firstCharPool[random_int(0, $firstCharPoolLength - 1)];
                } catch (\Exception $e) {
                    throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
                }
                $length = $length - 1;
            }

            $charPool = self::CHAR_POOL['even'];
            $charPoolLength = strlen($charPool);
            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
                }
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }
        public function generateSecureLowercase( int $length = 6 ): LUResult
        {
            $str = '';
            $charPool = self::CHAR_POOL['lowercase'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
                }
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }
        public function generateSecureUppercase( int $length = 6 ): LUResult
        {
            $str = '';
            $charPool = self::CHAR_POOL['uppercase'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
                }
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }
        public function generateSecureCase( int $length = 6 ): LUResult
        {
            $str = '';
            $charPool = self::CHAR_POOL['uppercase'].self::CHAR_POOL['lowercase'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
                }
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }
        public function generateSecureNumber( int $length = 6, bool $isFirstCharNotZero=false ): LUResult
        {
            $str = '';

            if ( $isFirstCharNotZero ) {
                $firstCharPool = self::CHAR_POOL['number_not_0'];
                $firstCharPoolLength = strlen($firstCharPool);
                try {
                    $str .= $firstCharPool[random_int(0, $firstCharPoolLength - 1)];
                } catch (\Exception $e) {
                    throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
                }
                $length = $length - 1;
            }

            $charPool = self::CHAR_POOL['number'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
                }
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }
        public function generateSecureCaseAndNumber( int $length = 6, bool $isFirstCharNotZero=false ): LUResult
        {
            $str = '';

            if ( $isFirstCharNotZero ) {
                $firstCharPool = self::CHAR_POOL['uppercase'].self::CHAR_POOL['lowercase'].self::CHAR_POOL['number_not_0'];
                $firstCharPoolLength = strlen($firstCharPool);
                try {
                    $str .= $firstCharPool[random_int(0, $firstCharPoolLength - 1)];
                } catch (\Exception $e) {
                    throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
                }
                $length = $length - 1;
            }

            $charPool = self::CHAR_POOL['uppercase'].self::CHAR_POOL['lowercase'].self::CHAR_POOL['number'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
                }
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }
        public function generateSecureSpecial( int $length = 6 ): LUResult
        {
            $str = '';
            $charPool = self::CHAR_POOL['special'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
                }
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }
        public function generateSecureCaseAndNumberAndSpecial( int $length = 6, bool $isFirstCharNotZero=false ): LUResult
        {
            $str = '';

            if ( $isFirstCharNotZero ) {
                $firstCharPool = self::CHAR_POOL['uppercase'].self::CHAR_POOL['lowercase'].self::CHAR_POOL['number_not_0'].self::CHAR_POOL['special'];
                $firstCharPoolLength = strlen($firstCharPool);
                try {
                    $str .= $firstCharPool[random_int(0, $firstCharPoolLength - 1)];
                } catch (\Exception $e) {
                    throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
                }
                $length = $length - 1;
            }

            $charPool = self::CHAR_POOL['uppercase'].self::CHAR_POOL['lowercase'].self::CHAR_POOL['number'].self::CHAR_POOL['special'];
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
                }
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }
        public function generateSecureCustom( string $custom, int $length = 6 ): LUResult
        {
            if (empty($custom)) return LUResult::error(1000, '自定义字符不可为空');

            $str = '';
            $charPool = $custom;
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
                }
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }
        public function generateSecureCustomAdd( string $custom, array $charPools, int $length = 6 ): LUResult
        {
            if (empty($custom)) return LUResult::error(1000, '自定义字符不可为空');

            $charPool = '';
            if ( !empty($charPools) ) {
                foreach ( $charPools as $cp ) {
                    $charPool .= self::CHAR_POOL[$cp] ?? '';
                }
            }

            $str = '';
            $charPool .= $custom;
            $charPoolLength = strlen($charPool);

            for( $i=0; $i < $length; $i++ ) {
                try {
                    $index = random_int(0, $charPoolLength - 1);
                } catch (\Exception $e) {
                    throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
                }
                $str .= $charPool[$index];
            }

            return LUResult::success( $str, self::MSG_SUCCESS );
        }

        /*********************************************************
         ************random_bytes 安全令牌、密钥、盐值、IV**********************************
         * *********************************************************/

        /**
         * 生成偶数位随机字符（只包含 0-9 和 a-f）用于 安全令牌、密钥、盐值、IV
         *
         * 用 random_bytes 生成的十六进制字符串只包含 0-9 和 a-f
         *
         * @param int $length:长度，必须是偶数位
         * @param bool $isUseStrict:是否启用严格模式，默认 true，即 $length 必须为偶数。
         *                          若为 false，则计算长度的结果直接舍弃小数部分。
         * */
        public function generateSecureStr( int $length = 32, bool $isUseStrict=true ): LUResult
        {
            if ( $isUseStrict && $length % 2 !== 0 ) {
                return LUResult::error(1000, '$length 不是偶数', $length );
            }

            try {
                $str = bin2hex(random_bytes(intdiv($length, 2)));
            } catch (\Exception $e) {
                throw new \RuntimeException('安全随机数生成失败，请检查系统环境', 0, $e);
            }

            return LUResult::success($str, self::MSG_SUCCESS);
        }

        /*********************************************************
         **********************************************
         * *********************************************************/

        /**
         * 生成随机字符串（先用 random_bytes，如果不可用，直接用 mt_rand，保证有有效返回值）
         *
         * @param int $length:长度
         * @param bool $isUseStrict:是否启用严格模式，默认 true，即 $length 必须为偶数。
         * */
        public function generateStr( int $length = 16, bool $isUseStrict=true ): LUResult
        {
            try {
                $return = $this->generateSecureStr( $length, $isUseStrict );
                if ( $return->isSuccess() ) {
                    return $return->getData();
                }

                // 如果 generateSecureStr 返回了错误（理论上它只抛异常或返回成功），此处作为兜底
                error_log('generateSecureStr 返回错误: ' . $return->getMsg());

            } catch (\Exception $e) {
                // 捕获 random_bytes 失败等异常，记录日志
                error_log('安全随机数生成失败，降级使用普通随机: ' . $e->getMessage());
            }

            return $this->generateCaseAndNumber( $length );
        }

    }
}