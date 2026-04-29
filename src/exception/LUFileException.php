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

class LUFileException extends LUBaseException
{
    const CODE_FILE_UPLOAD_FAILED = 300101;
    const CODE_INVALID_FILE = 300102;
    const CODE_INVALID_FILE_TYPE = 300103;
    const CODE_INVALID_MIME_TYPE = 300104;
    const CODE_DIRECTORY_CREATE_FAILED = 300105;
    const CODE_DIRECTORY_NOT_WRITABLE = 300106;
    const CODE_FILE_RENAME_FAILED = 300107;
    /**
     * 系统环境不支持安全随机数
     * */
    const CODE_LURANDOM_SYSTEM_ERROR = 300108100;
    const CODE_MOVE_UPLOAD_FAILED = 300109;

    // PHP 上传错误码映射 (move_uploaded_file 相关)
    const CODE_UPLOAD_ERR_INI_SIZE   = 300201;
    const CODE_UPLOAD_ERR_FORM_SIZE  = 300202;
    const CODE_UPLOAD_ERR_PARTIAL    = 300203;
    const CODE_UPLOAD_ERR_NO_FILE    = 300204;
    const CODE_UPLOAD_ERR_NO_TMP_DIR = 300206;
    const CODE_UPLOAD_ERR_CANT_WRITE = 300207;
    const CODE_UPLOAD_ERR_EXTENSION  = 300208;
    
    private $fileName;
    private $fileTemp;

    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null, string $fileName = '', string $fileTemp = '')
    {
        $this->fileName = $fileName;
        $this->fileTemp = $fileTemp;
        parent::__construct($message, $code, $previous);
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFileTemp(): string
    {
        return $this->fileTemp;
    }

    public function getLogContext(): array
    {
        return array_merge(
            parent::getLogContext(),
            [
                'file_name' => $this->getFileName(),
                'file_temp' => $this->getFileTemp(),
            ]
        );
    }

}