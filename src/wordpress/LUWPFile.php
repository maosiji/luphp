<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * date                 : 2024-09-20 17:50
 * update               :
 * project              : luphp
 */

namespace MAOSIJI\LU\WP;

use MAOSIJI\LU\EXCEPTION\LUWPFileException;
use MAOSIJI\LU\LURandom;

if ( ! defined( 'ABSPATH' ) ) { die; }
class LUWPFile
{
    private $allowed_mime_types = [];

    public function __construct()
    {
        // 加载 WordPress 必要文件
        $this->loadRequiredFiles();
        $this->allowed_mime_types = get_allowed_mime_types(); // 缓存允许的 MIME 类型
    }

    private function __clone() {}

    /**
     * 批量上传文件到 WordPress /uploads/ 目录
     *
     * @param string $inputName 文件输入字段名称
     * @param string $newFileNamePrefix 新文件名称前缀（不含扩展名）
     * @param array $allowFileExtensions 允许的文件类型数组（如 ['jpg', 'png', 'pdf']）
     * @return array 返回上传文件的 URL 列表
     *
     *                      - success: bool 是否成功
     *                      - attachment_id: int|null 成功时的附件ID
     *                      - url: string|null 成功时的URL
     *                      - error: string|null 失败时的错误信息
     *                      - original_file_name: string 原始文件名
     */
    public function upload(string $inputName, string $newFileNamePrefix, array $allowFileExtensions): array
    {
        $results = [];

        // 检查是否有文件上传
        if (empty($_FILES[$inputName]['name']) || !is_array($_FILES[$inputName]['name'])) {
            return $results;
        }

        foreach ($_FILES[$inputName]['name'] as $index => $originalFileName) {

            $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
            try {
                // 生成新文件名
                $newFileName = $this->_generate_unique_filename($newFileNamePrefix, $fileExtension);

                // 调用单个文件上传方法
                $result = $this->_upload_one($inputName, $index, $newFileName, $allowFileExtensions);
                $attachmentID = key($result);
                $url = current($result);
                $results[] = [
                    'success'           => true,
                    'attachment_id'     => $attachmentID,
                    'url'               => $url,
                    'error'             => null,
                    'original_file_name'=> $originalFileName
                ];
            } catch ( LUWPFileException $e ) {
                $results[] = [
                    'success'           => false,
                    'attachment_id'     => null,
                    'url'               => null,
                    'error'             => $e->getMessage(),
                    'original_file_name'=> $originalFileName
                ];
            }
        }

        return $results;
    }

    /**
     * 单个文件上传到 WordPress /uploads/ 目录
     *
     * @param string $inputName 文件输入字段的键（如 'files'）
     * @param int $index 文件索引（用于处理多文件上传时的 $_FILES 结构）
     * @param string $newFileName 新文件名称（不含扩展名）
     * @param array $allowFileExtensions 允许的文件类型（如 ['jpg', 'png', 'pdf']）
     * @return array 返回上传结果 [attachment_id => url]
     * @throws LUWPFileException
     */
    private function _upload_one(string $inputName, int $index, string $newFileName, array $allowFileExtensions): array
    {
        // 获取文件数据
        $file_data = $this->getFileData($inputName, $index);
        // 验证文件扩展名和 MIME 类型
        $this->validateFile($file_data, $allowFileExtensions);
        // 重命名文件
        $renamed_file = $this->renameFile($file_data, $newFileName);
        if (empty($renamed_file)) {
            throw new LUWPFileException(
                '文件重命名失败',
                LUWPFileException::CODE_FILE_RENAME_FAILED,
                null,
                $file_data['name'],
                $file_data['tmp_name']
            );
        }

        // 调用 WordPress 的 media_handle_sideload 函数处理上传
        $attachment_id = media_handle_sideload($renamed_file, 0); // 第二个参数为父文章 ID，0 表示无关联文章

        // 检查上传是否成功
        if (is_wp_error($attachment_id)) {
            throw new LUWPFileException(
                'WordPress 媒体文件上传处理失败'. $attachment_id->get_error_message(),
                LUWPFileException::CODE_MEDIA_SIDELOAD_FAILED,
                null,
                $renamed_file['name'],
                $renamed_file['tmp_name']
            );
        }

        // 获取附件 URL
        $attachment_url = wp_get_attachment_url($attachment_id);
        if (!$attachment_url) {
            throw new LUWPFileException(
                '获取附件 URL 失败',
                LUWPFileException::CODE_FILE_GET_URL_FAILED,
                null,
                $renamed_file['name'],
                $renamed_file['tmp_name']
            );
        }

        return [$attachment_id=>$attachment_url];
    }

    /**
     * 加载 WordPress 必要文件
     */
    private function loadRequiredFiles()
    {
        if (!function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }
    }

    /**
     * 获取文件数据
     *
     * @param string $inputName 文件输入字段名称
     * @param int $index 文件索引
     * @return array 返回文件数据
     * @throws LUWPFileException
     */
    private function getFileData(string $inputName, int $index): array
    {
        // 检查文件是否完整
        if ( !isset($_FILES[$inputName]['error'][$index]) ) {
            throw new LUWPFileException(
                '未找到文件上传数据',
                LUWPFileException::CODE_INVALID_FILE,
                null
            );
        }

        $errorCode = $_FILES[$inputName]['error'][$index];
        if ( $errorCode!==UPLOAD_ERR_OK ) {
            $errorMessage = [
                UPLOAD_ERR_INI_SIZE   => '文件超过服务器 php.ini 限制',
                UPLOAD_ERR_FORM_SIZE  => '文件超过表单 MAX_FILE_SIZE 限制',
                UPLOAD_ERR_PARTIAL    => '文件只有部分被上传',
                UPLOAD_ERR_NO_FILE    => '没有文件被上传',
                UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
                UPLOAD_ERR_CANT_WRITE => '文件写入磁盘失败',
                UPLOAD_ERR_EXTENSION  => 'PHP 扩展阻止了文件上传'
            ];
            $msg = $errorMessage[$errorCode] ?? '未知上传错误';
            $exceptionCode = constant(LUWPFileException::class.'::CODE_UPLOAD_ERR_'.$errorCode) ?? LUWPFileException::CODE_INVALID_FILE;
            throw new LUWPFileException(
                $msg,
                $exceptionCode,
                null,
                $_FILES[$inputName]['name'][$index] ?? '',
                $_FILES[$inputName]['tmp_name'][$index] ?? ''
                );
        }

        return [
            'name'     => $_FILES[$inputName]['name'][$index],
            'type'     => $_FILES[$inputName]['type'][$index],
            'tmp_name' => $_FILES[$inputName]['tmp_name'][$index],
            'error'    => $errorCode,
            'size'     => $_FILES[$inputName]['size'][$index],
        ];
    }

    /**
     * 验证文件扩展名和 MIME 类型
     *
     * @param array $file_data 文件数据
     * @param array $allowFileExtensions 允许的文件类型
     * @return bool 返回验证结果
     */
    private function validateFile(array $file_data, array $allowFileExtensions): bool
    {
        // 检查文件扩展名
        $file_extension = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowFileExtensions)) {
            throw new LUWPFileException(
                '文件不符合允许的上传类型',
                LUWPFileException::CODE_INVALID_FILE_TYPE,
                null,
                $file_data['name'],
                $file_data['tmp_name']
            );
        }

        // 检查 MIME 类型
        $is_valid_type = false;
        foreach ($this->allowed_mime_types as $mime_pattern) {
            if (fnmatch($mime_pattern, $file_data['type'])) {
                $is_valid_type = true;
                break;
            }
        }

        if (!$is_valid_type) {
            throw new LUWPFileException(
                '文件 MIME 类型不符合 WordPress 允许的上传类型',
                LUWPFileException::CODE_INVALID_WP_FILE_TYPE,
                null,
                $file_data['name'],
                $file_data['tmp_name']
            );
        }

        return true;
    }

    /**
     * 重命名文件
     *
     * @param array $file_data 文件数据
     * @param string $new_name 新文件名称（不含扩展名）
     * @return array|false 返回重命名后的文件数据或 false
     */
    private function renameFile(array $file_data, string $new_name): array
    {
        if (empty($new_name)) {
            return $file_data;
        }

        $file_extension = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
        $new_file_name = $new_name . '.' . $file_extension;
        $temp_file_path = $file_data['tmp_name'];

        // 移动临时文件到新的文件名
        $renamed_temp_file = dirname($temp_file_path) . '/' . $new_file_name;
        if (!rename($temp_file_path, $renamed_temp_file)) {
            return [];
        }

        return [
            'name'     => $new_file_name,
            'type'     => $file_data['type'],
            'tmp_name' => $renamed_temp_file,
            'error'    => $file_data['error'],
            'size'     => $file_data['size'],
        ];
    }

    /**
     * 生成唯一的文件名
     *
     * @param string $prefix 文件名前缀
     * @param string $extension 文件扩展名
     * @return string 唯一的文件名
     * @throws LUWPFileException
     */
    private function _generate_unique_filename( string $prefix, string $extension ): string
    {
        try {
            $randomPart = (new LURandom())->generateSecureBytes(6);
        } catch (LUWPFileException $e) {
            throw new LUWPFileException(
                '生成文件名失败：随机数不可用',
                LUWPFileException::CODE_LURANDOM_SYSTEM_ERROR,
                $e
            );
        }

        return sprintf(
            '%s-%d-%s.%s',
            $prefix,
            time(),
            $randomPart,
            $extension
        );
    }

}