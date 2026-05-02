<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-25 19:19
 * update               : 
 * project              : luphp
 * official website     : xyysd.cn
 * official name        : 小应用商店
 * official email       : 1211806667@qq.com
 * official wechat      : 1211806667
 * description          : 
 * read me              : 感谢您使用 小应用商店 的产品。您的支持，是我们最大的动力；您的反对，是我们最大的阻力
 * remind               ：使用盗版，存在风险；支持正版，将会有跟多的产品与您见面
 */

namespace MAOSIJI\LU\EXCEPTION;

class LUEncryptorException extends LUBaseException
{
    /**
     * 加密失败
     * */
    const CODE_ENCRYPTION_FAILED = 110100;
    /**
     * 解密失败
     * */
    const CODE_DECRYPTION_FAILED = 110101;
    /**
     * 无效的加密数据
     **/
    const CODE_INVALID_DATA = 110103;

    /**
     * 加密数据
     * */
    private $encryptedData;

    /**
     * 是否显示敏感数据到日志
     * */
    private $isDataInLog = false;

    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null, string $encryptedData = '', bool $isDataInLog = false)
    {
        $this->encryptedData = $encryptedData;
        $this->isDataInLog = $isDataInLog;
        parent::__construct($message, $code, $previous);
    }

    public function getLogContext(): array
    {
        $data = [];
        if ($this->isDataInLog) {
            $data = ['encrypted_data' => $this->encryptedData];
        }

        return array_merge(
            parent::getLogContext(),
            $data
        );
    }

    public function getEncryptedData(): string
    {
        return $this->encryptedData;
    }
}