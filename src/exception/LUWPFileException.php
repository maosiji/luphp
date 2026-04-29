<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-25 19:19
 * update               : 
 * project              : luphp
 */

namespace MAOSIJI\LU\EXCEPTION;

class LUWPFileException extends LUBaseException
{
    /**
     * 上传失败
     * */
    const CODE_FILE_UPLOAD_FAILED = 310101;
    /**
     * 无效的文件
     * */
    const CODE_INVALID_FILE = 310102;
    /**
     * 无效的文件类型
     * */
    const CODE_INVALID_FILE_TYPE = 310103;
    /**
     * 无效的WordPress文件类型
     * */
    const CODE_INVALID_WP_FILE_TYPE = 310104;
    /**
     * 文件重命名失败
     * */
    const CODE_FILE_RENAME_FAILED = 310105;
    /**
     * 文件获取附件 URL 失败
     * */
    const CODE_FILE_GET_URL_FAILED = 310106;
    /**
     * 系统环境不支持安全随机数
     * */
    const CODE_LURANDOM_SYSTEM_ERROR = 310100100;
    const CODE_MEDIA_SIDELOAD_FAILED = 310108;

    // 上传错误码（与PHP UPLOAD_ERR_*对应）
    const CODE_UPLOAD_ERR_INI_SIZE   = 310201;
    const CODE_UPLOAD_ERR_FORM_SIZE  = 310202;
    const CODE_UPLOAD_ERR_PARTIAL    = 310203;
    const CODE_UPLOAD_ERR_NO_FILE    = 310204;
    const CODE_UPLOAD_ERR_NO_TMP_DIR = 310206;
    const CODE_UPLOAD_ERR_CANT_WRITE = 310207;
    const CODE_UPLOAD_ERR_EXTENSION  = 310208;

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