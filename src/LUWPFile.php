<?php

namespace MAOSIJI\LUPHP;

/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * date                 : 2024-09-20 17:50
 * update               :
 * project              : luphp
 */

if (!class_exists('LUWPFile')) {
    class LUWPFile
    {
        public function __construct()
        {
        }
        private function __clone()
        {
        }
        private function __wakeup()
        {
        }

        /**
         * 单个文件上传到 WordPress /uploads/ 目录
         *
         * @param array  $file       单个文件数据
         * @param string $input_name 文件输入字段名称
         * @return array 返回上传结果（仅 id 和 url）
         */
        private function uploadSingleFile(array $file, string $input_name): array
        {
            // 检查是否提供了有效的文件数据
            if (empty($file['name'])) {
                return [];
            }

            // 检查文件类型是否符合 WordPress 允许的 MIME 类型
            $file_type = $file['type'];
            $allowed_mime_types = get_allowed_mime_types(); // 获取 WordPress 允许的 MIME 类型
            $is_valid_type = false;

            foreach ($allowed_mime_types as $mime_type) {
                if (strpos($file_type, $mime_type) !== false) {
                    $is_valid_type = true;
                    break;
                }
            }

            if (!$is_valid_type) {
                return [];
            }

            // 调用 WordPress 的 media_handle_upload 函数处理上传
            $attachment_id = media_handle_upload($input_name, 0); // 第二个参数为父文章 ID，0 表示无关联文章

            // 检查上传是否成功
            if (is_wp_error($attachment_id)) {
                return [];
            }

            // 获取附件 URL
            $attachment_url = wp_get_attachment_url($attachment_id);

            if ($attachment_url) {
                return [
                    'id'  => $attachment_id,
                    'url' => $attachment_url,
                ];
            } else {
                return [];
            }
        }

        /**
         * 批量上传文件到 WordPress /uploads/ 目录
         *
         * @param array $files $_FILES 数组
         * @return array 返回批量上传结果（仅 id 和 url 的数组）
         */
        public function uploadMultipleFiles(array $files): array
        {
            // 加载 WordPress 媒体处理相关文件（只加载一次）
            if (!function_exists('media_handle_upload')) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
            }

            // 检查是否提供了有效的文件数据
            if (empty($files)) {
                return [];
            }

            $results = [];
            foreach ($files as $input_name => $file_data) {
                // 如果是多文件上传，则循环处理每个文件
                if (is_array($file_data['name'])) {
                    foreach ($file_data['name'] as $index => $file_name) {
                        $single_file = [
                            'name'     => $file_name,
                            'type'     => $file_data['type'][$index],
                            'tmp_name' => $file_data['tmp_name'][$index],
                            'error'    => $file_data['error'][$index],
                            'size'     => $file_data['size'][$index],
                        ];

                        // 调用单个文件上传方法
                        $result = $this->uploadSingleFile($single_file, $input_name);
                        if (!empty($result)) {
                            $results[] = $result;
                        }
                    }
                } else {
                    // 单文件上传
                    $result = $this->uploadSingleFile($file_data, $input_name);
                    if (!empty($result)) {
                        $results[] = $result;
                    }
                }
            }

            return $results;
        }


    }
}