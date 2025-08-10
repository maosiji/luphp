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
if ( ! defined( 'ABSPATH' ) ) { die; }
if (!class_exists('LUWPFile')) {
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
         * @param string $input_name 文件输入字段名称
         * @param string $new_name_prefix 新文件名称前缀（不含扩展名）
         * @param array $allowed_types 允许的文件类型（如 ['jpg', 'png', 'pdf']）
         * @return array 返回批量上传结果（code, msg, id, url 的数组）
         */
        public function upload(string $input_name, string $new_name_prefix, array $allowed_types): array
        {
            $results = [];

            // 检查是否有多个文件上传
            if (isset($_FILES[$input_name]['name']) && is_array($_FILES[$input_name]['name'])) {
                foreach ($_FILES[$input_name]['name'] as $index => $file_name) {
                    // 生成新文件名
                    $new_name = $new_name_prefix . '-' . time() . '-' . mt_rand(0, 999);

                    // 调用单个文件上传方法
                    $result = $this->_upload_file($input_name, $index, $new_name, $allowed_types);
                    if ($result['code'] === 1) {
                        $results[] = $result;
                    }
                }
            }

            return $results;
        }

        /**
         * 单个文件上传到 WordPress /uploads/ 目录
         *
         * @param string $input_name 文件输入字段的键（如 'files'）
         * @param int $index 文件索引（用于处理多文件上传时的 $_FILES 结构）
         * @param string $new_name 新文件名称（不含扩展名）
         * @param array $allowed_types 允许的文件类型（如 ['jpg', 'png', 'pdf']）
         * @return array 返回上传结果（code, msg, id, url）
         */
        private function _upload_file(string $input_name, int $index, string $new_name, array $allowed_types): array
        {
            // 获取文件数据
            $file_data = $this->getFileData($input_name, $index);
            if (empty($file_data)) {
                return $this->errorResponse('无效的文件数据');
            }

            // 验证文件扩展名和 MIME 类型
            $validation_result = $this->validateFile($file_data, $allowed_types);
            if ($validation_result['code'] === 0) {
                return $validation_result;
            }

            // 如果指定了新文件名，则重命名文件
            $renamed_file = $this->renameFile($file_data, $new_name);
            if (empty($renamed_file)) {
                return $this->errorResponse($file_data['name'] . ' 文件重命名失败');
            }

            // 调用 WordPress 的 media_handle_sideload 函数处理上传
            $attachment_id = media_handle_sideload($renamed_file, 0); // 第二个参数为父文章 ID，0 表示无关联文章

            // 检查上传是否成功
            if (is_wp_error($attachment_id)) {
                return $this->errorResponse($file_data['name'] . ' 文件上传失败：' . $attachment_id->get_error_message());
            }

            // 获取附件 URL
            $attachment_url = wp_get_attachment_url($attachment_id);
            if (!$attachment_url) {
                return $this->errorResponse($file_data['name'] . ' 文件获取附件 URL 失败');
            }

            return [
                'code' => 1,
                'msg'  => $file_data['name'] . ' 文件上传成功',
                'id'   => $attachment_id,
                'url'  => $attachment_url,
            ];
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
         * @param string $input_name 文件输入字段名称
         * @param int $index 文件索引
         * @return array|false 返回文件数据或 false
         */
        private function getFileData(string $input_name, int $index): array
        {
            if (
                empty($_FILES[$input_name]['name'][$index]) ||
                empty($_FILES[$input_name]['tmp_name'][$index]) ||
                !empty($_FILES[$input_name]['error'][$index])
            ) {
                return [];
            }

            return [
                'name'     => $_FILES[$input_name]['name'][$index],
                'type'     => $_FILES[$input_name]['type'][$index],
                'tmp_name' => $_FILES[$input_name]['tmp_name'][$index],
                'error'    => $_FILES[$input_name]['error'][$index],
                'size'     => $_FILES[$input_name]['size'][$index],
            ];
        }

        /**
         * 验证文件扩展名和 MIME 类型
         *
         * @param array $file_data 文件数据
         * @param array $allowed_types 允许的文件类型
         * @return array 返回验证结果（code, msg）
         */
        private function validateFile(array $file_data, array $allowed_types): array
        {
            // 检查文件扩展名
            $file_extension = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
            if (!in_array($file_extension, $allowed_types)) {
                return $this->errorResponse($file_data['name'] . ' 文件不符合允许的上传类型');
            }

            // 检查 MIME 类型
            $is_valid_type = false;
            foreach ($this->allowed_mime_types as $mime_type) {
                if (strpos($file_data['type'], $mime_type) !== false) {
                    $is_valid_type = true;
                    break;
                }
            }

            if (!$is_valid_type) {
                return $this->errorResponse($file_data['name'] . ' 文件不符合 WordPress 允许的上传类型');
            }

            return [
                'code' => 1,
                'msg'  => '文件验证通过',
            ];
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
         * 统一错误响应格式
         *
         * @param string $message 错误信息
         * @return array 返回错误响应
         */
        private function errorResponse(string $message): array
        {
            return [
                'code' => 0,
                'msg'  => $message,
            ];
        }
    }
}