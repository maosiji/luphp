<?php
/*
 * author  : 猫斯基
 * url     : maosiji.com
 * email   : 1394846666@qq.com
 * wechat  : maosiji-com
 * date    : 2025-07-31 21:10
 * update  :
 * project : luphp
 */

namespace MAOSIJI\LU;
if (!class_exists('LUEncryptor')) {
    /**
     * 对称加密类
     *
     * 提供 AES 加密解密功能，支持 AES-128 和 AES-256 两种算法
     * 兼容 PHP 7.0 及以上版本，无需安装额外扩展
     *
     * @package MAOSIJI\LU
     * @author 猫斯基
     * @version 1.1
     */
    class LUEncryptor
    {
        /**
         * AES-256-CBC 加密算法
         *
         * @var string
         */
        const CIPHER = 'AES-256-CBC';

        /**
         * AES-128-CBC 加密算法（速度更快）
         *
         * @var string
         */
        const CIPHER_FAST = 'AES-128-CBC';

        /**
         * 加密密钥
         *
         * @var string
         */
        private $key;

        /**
         * 是否使用快速加密算法
         *
         * @var bool
         */
        private $useFastCipher = false;

        /**
         * IV 长度缓存
         *
         * @var int
         */
        private $ivLength;

        /**
         * 构造函数
         *
         * @param string $key 加密密钥，可以是任意长度的字符串
         * @param bool $useFastCipher 是否使用更快的 AES-128 算法
         *                             true: 使用 AES-128-CBC (速度更快)
         *                             false: 使用 AES-256-CBC (安全性更高)
         */
        public function __construct( string $key, bool $useFastCipher = false )
        {
            $this->useFastCipher = $useFastCipher;
            $cipher = $this->getCipher();
            $this->ivLength = openssl_cipher_iv_length($cipher);

            // 根据选择的算法确定密钥长度
            $keyLength = $this->useFastCipher ? 16 : 32;

            // 密钥生成逻辑
            if (strlen($key) >= $keyLength) {
                // 如果原始密钥长度足够，直接截取
                $this->key = substr($key, 0, $keyLength);
            } else {
                // 如果原始密钥长度不足，使用填充方式补足
                $this->key = str_pad($key, $keyLength, 'luphp-e78c8be696afe59fba-gfi5986u-kdfskfd0934kf65sa');
            }
        }

        /**
         * 禁止克隆
         *
         * @return void
         */
        private function __clone()
        {
        }

        /**
         * 获取当前使用的加密算法
         *
         * @return string 加密算法名称
         */
        private function getCipher(): string
        {
            return $this->useFastCipher ? self::CIPHER_FAST : self::CIPHER;
        }

        /**
         * 加密数据
         *
         * @param string $data 要加密的明文数据
         * @return string Base64 编码的密文（包含 IV + 密文）
         * @throws \RuntimeException 加密失败时抛出异常
         */
        public function encrypt(string $data): string
        {
            $cipher = $this->getCipher();

            // 生成随机 IV
            $iv = openssl_random_pseudo_bytes($this->ivLength);

            // 执行加密
            $encrypted = openssl_encrypt(
                $data,
                $cipher,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv
            );

            // 检查加密是否成功
            if ($encrypted === false) {
                throw new \RuntimeException('加密失败: ' . openssl_error_string());
            }

            // 拼接 IV 和密文，然后进行 Base64 编码
            return base64_encode($iv . $encrypted);
        }

        /**
         * 解密数据
         *
         * @param string $encryptedData Base64 编码的加密数据
         * @return string 解密后的明文数据
         * @throws \InvalidArgumentException 输入数据无效时抛出异常
         * @throws \RuntimeException 解密失败时抛出异常
         */
        public function decrypt(string $encryptedData): string
        {
            $cipher = $this->getCipher();

            // Base64 解码
            $combined = base64_decode($encryptedData);
            if ($combined === false || strlen($combined) < $this->ivLength) {
                throw new \InvalidArgumentException('无效的加密数据：Base64 解码失败或数据长度不足');
            }

            // 分离 IV 和密文
            $iv = substr($combined, 0, $this->ivLength);
            $ciphertext = substr($combined, $this->ivLength);

            // 执行解密
            $decrypted = openssl_decrypt(
                $ciphertext,
                $cipher,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv
            );

            // 检查解密是否成功
            if ($decrypted === false) {
                throw new \RuntimeException('解密失败: ' . openssl_error_string());
            }

            return $decrypted;
        }

        /**
         * 批量加密数据
         *
         * 用于同时加密多个数据，内部会为每个数据生成独立的 IV
         *
         * @param array $dataArray 要加密的明文数据数组
         * @return array 加密后的数据数组，每个元素都是 Base64 编码的密文
         */
        public function encryptBatch(array $dataArray): array
        {
            $cipher = $this->getCipher();
            $results = [];

            foreach ($dataArray as $data) {
                // 为每个数据生成独立的 IV
                $iv = openssl_random_pseudo_bytes($this->ivLength);

                $encrypted = openssl_encrypt(
                    $data,
                    $cipher,
                    $this->key,
                    OPENSSL_RAW_DATA,
                    $iv
                );

                if ($encrypted !== false) {
                    $results[] = base64_encode($iv . $encrypted);
                }
            }

            return $results;
        }

        /**
         * 获取当前使用的加密算法信息
         *
         * @return array 包含算法名称和密钥长度的数组
         */
        public function getCipherInfo(): array
        {
            return [
                'cipher' => $this->getCipher(),
                'key_length' => $this->useFastCipher ? 16 : 32,
                'iv_length' => $this->ivLength
            ];
        }



    }
}