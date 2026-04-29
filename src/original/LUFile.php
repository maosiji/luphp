<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-03-11 10:31
 * update               :
 * project              : luphp
 */

namespace MAOSIJI\LU;

use MAOSIJI\LU\EXCEPTION\LUFileException;
use MAOSIJI\LU\EXCEPTION\LURandomException;
use MAOSIJI\LU\EXCEPTION\LUWPFileException;

class LUFile
{
    /**
     * 是否启用严格的 MIME 类型验证（需要开启 fileinfo 扩展）
     * @var bool
     */
    private $strictMimeCheck = false;

    public function __construct( bool $strictMimeCheck = false )
    {
        $this->strictMimeCheck = $strictMimeCheck;
    }
    private function __clone()
    {
    }

    /**
     * 批量上传文件到指定目录（一个文件出错，不会中断其他文件上传；但会在返回数组中以内部数组的形式返回错误信息）
     *
     * @param string $uploadDir 上传文件的绝对路径（末尾可带或不带斜杠）
     * @param string $uploadUrl 上传文件的 URL 相对路径 （末尾可带或不带斜杠）/wp-content/uploads/shanhu/
     * @param string $inputName $_FILES 中的字段名 input name
     * @param string $newFileNamePrefix 上传文件的新名称前缀
     * @param array $allowFileExtensions 允许上传的文件扩展名数组，默认 空数组，都可上传（如 ['jpg', 'png']）
     * @return array 返回结果数组，每个元素包含：
     *
     *              - success: bool 是否成功
     *              - url: string|null 成功时的访问 URL
     *              - file_path: string|null 成功时的服务器绝对路径
     *              - error: string|null 失败时的错误信息
     *              - original_file_name: string 原始文件名
     */
    public function upload( string $uploadDir, string $uploadUrl, string $inputName, string $newFileNamePrefix, array $allowFileExtensions=[] ): array
    {
        $results = [];

        // 检查是否有文件上传
        if (empty($_FILES[$inputName]['tmp_name']) || !is_array($_FILES[$inputName]['tmp_name'])) {
            return $results;
        }

        // 格式化路径
        $uploadDir = rtrim($uploadDir, '/') . '/';
        $uploadUrl = rtrim($uploadUrl, '/') . '/';
        $this->_prepareUploadDirectory($uploadDir);


        // 遍历上传文件
        foreach ($_FILES[$inputName]['tmp_name'] as $index => $fileTmp) {

            $originalFileName = $_FILES[$inputName]['name'][$index];
            $errorCode = $_FILES[$inputName]['error'][$index] ?? UPLOAD_ERR_NO_FILE;

            try {
                // 验证 PHP 上传错误
                $this->_validateUploadError($errorCode, $originalFileName, $fileTmp);
                // 验证文件扩展名 和 MIME 类型
                $this->_validateFileType($originalFileName, $fileTmp, $allowFileExtensions);
                // 生成唯一新文件名
                $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
                $newFileName = $this->_generate_unique_filename($newFileNamePrefix, $fileExtension);
                // 执行单文件上传
                $uploadResult = $this->_upload_one($uploadDir, $uploadUrl, $originalFileName, $fileTmp, $newFileName);

                $results[] = [
                    'success'           => true,
                    'url'               => $uploadResult['url'],
                    'file_path'         => $uploadResult['file_path'],
                    'error'             => null,
                    'original_file_name' => $originalFileName,
                ];
            } catch (LUFileException $e) {
                $results[] = [
                    'success'           => false,
                    'url'               => null,
                    'file_path'         => null,
                    'error'             => $e->getMessage(),
                    'original_file_name' => $originalFileName,
                ];
            }
        }

        return $results;
    }

    /**
     * 单个文件上传
     *
     * @param string $uploadDir 上传文件的绝对路径
     * @param string $uploadUrl 上传文件的 URL 相对路径
     * @param string $oldFileName 原始文件名
     * @param string $fileTmp 临时文件路径
     * @param string $newFileName 新文件名
     * @return array ['url' => string, 'file_path' => string]
     * @throws LUWPFileException
     */
    private function _upload_one( string $uploadDir, string $uploadUrl, string $oldFileName, string $fileTmp, string $newFileName ): array
    {
        $targetPath = $uploadDir.$newFileName;
        if (!move_uploaded_file($fileTmp, $targetPath)) {
            $error = error_get_last();
            $msg = $error ? '上传失败'.$error['message'] : '移动文件失败，可能是目录权限或磁盘空间不足';
            throw new LUFileException(
                $msg,
                LUFileException::CODE_MOVE_UPLOAD_FAILED,
                null,
                $oldFileName,
                $fileTmp
            );
        }

        return [
            'url'       => $uploadUrl.$newFileName,
            'file_path' => $targetPath,
        ];
    }

    /**
     * 生成唯一的文件名
     *
     * @param string $prefix 文件名前缀
     * @param string $extension 文件扩展名
     * @return string 唯一的文件名
     */
    private function _generate_unique_filename( string $prefix, string $extension ): string
    {
        try {
            $randomPart = (new LURandom())->generateSecureBytes(6);
        } catch (LURandomException $e) {
            throw new LUFileException( '生成文件名失败：随机数不可用', LUFileException::CODE_LURANDOM_SYSTEM_ERROR, $e );
        }

        return sprintf(
            '%s-%d-%s.%s',
            $prefix,
            time(),
            $randomPart,
            $extension
        );
    }

    /**
     * 准备上传目录：如果不存在则尝试递归创建，并检查可写性
     *
     * @param string $uploadDir
     * @throws LUFileException
     */
    private function _prepareUploadDirectory( string $uploadDir )
    {
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new LUFileException(
                    "无法创建上传目录：{$uploadDir}",
                    LUFileException::CODE_DIRECTORY_CREATE_FAILED
                );
            }
        }
        if (!is_writable($uploadDir)) {
            throw new LUFileException(
                "上传目录不可写：{$uploadDir}",
                LUFileException::CODE_DIRECTORY_NOT_WRITABLE
            );
        }
    }

    /**
     * 验证 PHP 上传错误码
     *
     * @param int $errorCode
     * @param string $originalFileName
     * @param string $fileTmp
     * @throws LUFileException
     */
    private function _validateUploadError(int $errorCode, string $originalFileName, string $fileTmp)
    {
        if ($errorCode === UPLOAD_ERR_OK) {
            return;
        }

        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => '文件超过服务器 php.ini 限制',
            UPLOAD_ERR_FORM_SIZE  => '文件超过表单 MAX_FILE_SIZE 限制',
            UPLOAD_ERR_PARTIAL    => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE    => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入磁盘失败',
            UPLOAD_ERR_EXTENSION  => 'PHP扩展阻止了文件上传',
        ];
        $msg = $errorMessages[$errorCode] ?? '未知上传错误';
        $exceptionCode = constant(LUFileException::class . '::CODE_UPLOAD_ERR_' . $errorCode) ?? LUFileException::CODE_INVALID_FILE;
        throw new LUFileException($msg, $exceptionCode, null, $originalFileName, $fileTmp);
    }

    /**
     * 验证文件扩展名和 MIME 类型（如果启用严格检查）
     *
     * @param string $originalFileName
     * @param string $fileTmp
     * @param array $allowFileExtensions
     * @throws LUFileException
     */
    private function _validateFileType(string $originalFileName, string $fileTmp, array $allowFileExtensions)
    {
        // 扩展名验证
        $extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        if (!empty($allowFileExtensions) && !in_array($extension, $allowFileExtensions)) {
            throw new LUFileException(
                "不允许的文件扩展名：{$extension}",
                LUFileException::CODE_INVALID_FILE_TYPE,
                null,
                $originalFileName,
                $fileTmp
            );
        }

        // 可选：严格 MIME 类型检查
        if ($this->strictMimeCheck && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fileTmp);
            finfo_close($finfo);

            // 简单 MIME 与扩展名映射（可根据需要扩展）
            $mimeMap = [
                'jpg' => 'image/jpeg',
                'jpeg'=> 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'pdf' => 'application/pdf',
                'txt' => 'text/plain',
                'zip' => 'application/zip',
            ];
            if (isset($mimeMap[$extension]) && $mimeMap[$extension] !== $mimeType) {
                throw new LUFileException(
                    "文件 MIME 类型 ({$mimeType}) 与扩展名 ({$extension}) 不匹配",
                    LUFileException::CODE_INVALID_MIME_TYPE,
                    null,
                    $originalFileName,
                    $fileTmp
                );
            }
        }
    }

}

