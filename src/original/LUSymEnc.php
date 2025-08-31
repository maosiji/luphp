<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-07-31 21:10
 * update               : 
 * project              : luphp
 */

namespace MAOSIJI\LU;
if ( !class_exists( 'LUSymEnc' ) ) {
    class LUSymEnc
    {
        const CIPHER = 'AES-256-CBC';
        private $key;

        /**
         * @param string $key :可以写任意长度的字符串，不足的话，自动补足
         */
        public function __construct( string $key )
        {
            $this->key = substr( $key.'luphp-e78c8be696afe59fba-gfi5986u', 0, 32 );
        }

        private function __clone()
        {
        }

        /**
         * 加密数据
         *
         * @param string $data 明文数据
         * @return string Base64 编码的密文（包含 IV + 密文）
         */
        public function encrypt(string $data): string
        {
            // 生成随机 IV
            $ivLength = openssl_cipher_iv_length(self::CIPHER);
            $iv = random_bytes($ivLength);

            // 执行加密
            $encrypted = openssl_encrypt(
                $data,
                self::CIPHER,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($encrypted === false) {
                throw new \RuntimeException('加密失败: ' . openssl_error_string());
            }

            // 拼接 IV 和密文
            $combined = $iv . $encrypted;

            // 返回 Base64 编码结果
            return base64_encode($combined);
        }

        /**
         * 解密数据
         *
         * @param string $encryptedData Base64 编码的加密数据
         * @return string 解密后的明文
         */
        public function decrypt(string $encryptedData): string
        {
            // Base64 解码（严格模式）
            $combined = base64_decode($encryptedData, true);
            if ($combined === false) {
                throw new \InvalidArgumentException('Base64 解码失败：输入不是有效的 Base64 字符串');
            }

            $ivLength = openssl_cipher_iv_length(self::CIPHER);
            if (strlen($combined) < $ivLength) {
                throw new \InvalidArgumentException('密文过短，缺少 IV 或数据已损坏');
            }

            $iv = substr($combined, 0, $ivLength);
            $ciphertext = substr($combined, $ivLength);

            $decrypted = openssl_decrypt(
                $ciphertext,
                self::CIPHER,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                throw new \RuntimeException('解密失败: ' . openssl_error_string());
            }

            return $decrypted;
        }





    }
}
