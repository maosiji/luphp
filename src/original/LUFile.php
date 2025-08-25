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
if (!class_exists('LUFile')) {
    class LUFile
    {
        public function __construct()
        {
        }
        private function __clone()
        {
        }

        /**
         * WP批量上传
         *
         * @param string $uploadDir 上传文件的绝对路径
         * @param string $uploadUrl 上传文件的 URL 相对路径 /wp-content/uploads/shanhu/
         * @param string $input_name $_FILES 的 input name
         * @param string $name 上传文件的新名称
         * @param array $type 上传文件的后缀，默认 空数组，全部类型都可以
         * @return array 返回上传文件的 URL 列表
         */
        public function upload( string $uploadDir, string $uploadUrl, string $input_name, string $name, array $type=[] ): array
        {
            $backArr = [];

            if (empty($_FILES[$input_name])) {
                return $backArr; // 如果没有文件上传，直接返回空数组
            }

            // 格式化路径
            $uploadDir = rtrim($uploadDir, '/') . '/';
            $uploadUrl = rtrim($uploadUrl, '/') . '/';

            // 确保上传目录存在
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir);
            }

            // 遍历上传文件
            foreach ($_FILES[$input_name]['tmp_name'] as $k => $fileTmp) {
                $fileName = $_FILES[$input_name]['name'][$k];
                $fileType = $_FILES[$input_name]['type'][$k];

                // 文件类型验证
                if ($type !== 'all' && !$this->is_valid_file_type($fileName, $fileType, $type)) {
                    continue; // 跳过不符合类型的文件
                }

                // 上传文件
                $backUrl = $this->_upload_file($uploadDir, $uploadUrl, $fileName, $fileTmp, $name);
                if (!empty($backUrl)) {
                    $backArr[] = $backUrl;
                }
            }

            return $backArr;
        }

        /**
         * 单个文件上传
         *
         * @param string $uploadDir 上传文件的绝对路径
         * @param string $uploadUrl 上传文件的 URL 相对路径
         * @param string $fileName 原始文件名
         * @param string $fileTmp 临时文件路径
         * @param string $name 新文件名前缀
         * @return string 返回上传文件的 URL
         */
        private function _upload_file(string $uploadDir, string $uploadUrl, string $fileName, string $fileTmp, string $name): string
        {
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $fileNameNew = $this->generate_unique_filename($name, $fileExtension);

            $uploadDirFull = $uploadDir . $fileNameNew;

            // 移动文件到目标目录
            if (move_uploaded_file($fileTmp, $uploadDirFull)) {
                return $uploadUrl . $fileNameNew;
            }

            return '';
        }

        /**
         * 验证文件类型是否符合要求
         *
         * @param string $fileName 文件名
         * @param string $fileType 文件 MIME 类型
         * @param array $allowedType 允许的文件后缀数组
         * @return bool 是否有效
         */
        private function is_valid_file_type( string $fileName, string $fileType, array $allowedType ): bool
        {
            if ( empty($fileName) || empty($fileType) ) { return false; }
            if ( empty($allowedType) ) { return true; }

            // 获取文件扩展名
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // 定义扩展名与 MIME 类型的映射表
            $mimeMapping = [
                // 图片格式
                'jpg'   => 'image/jpeg',
                'jpeg'  => 'image/jpeg',
                'png'   => 'image/png',
                'gif'   => 'image/gif',
                'bmp'   => 'image/bmp',
                'webp'  => 'image/webp',
                'svg'   => 'image/svg+xml',
                'ico'   => 'image/x-icon',

                // 文档格式
                'pdf'   => 'application/pdf',
                'txt'   => 'text/plain',
                'doc'   => 'application/msword',
                'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls'   => 'application/vnd.ms-excel',
                'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'ppt'   => 'application/vnd.ms-powerpoint',
                'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'odt'   => 'application/vnd.oasis.opendocument.text',
                'ods'   => 'application/vnd.oasis.opendocument.spreadsheet',
                'odp'   => 'application/vnd.oasis.opendocument.presentation',

                // 压缩文件格式
                'zip'   => 'application/zip',
                'rar'   => 'application/x-rar-compressed',
                '7z'    => 'application/x-7z-compressed',
                'tar'   => 'application/x-tar',
                'gz'    => 'application/gzip',

                // 音频格式
                'mp3'   => 'audio/mpeg',
                'wav'   => 'audio/wav',
                'ogg'   => 'audio/ogg',
                'flac'  => 'audio/flac',
                'aac'   => 'audio/aac',
                'm4a'   => 'audio/mp4',

                // 视频格式
                'mp4'   => 'video/mp4',
                'webm'  => 'video/webm',
                'mkv'   => 'video/x-matroska',
                'avi'   => 'video/x-msvideo',
                'mov'   => 'video/quicktime',
                'flv'   => 'video/x-flv',
                'wmv'   => 'video/x-ms-wmv',

                // 字体格式
                'ttf'   => 'font/ttf',
                'otf'   => 'font/otf',
                'woff'  => 'font/woff',
                'woff2' => 'font/woff2',
                'eot'   => 'application/vnd.ms-fontobject',

                // 其他常见格式
                'json'  => 'application/json',
                'xml'   => 'application/xml',
                'csv'   => 'text/csv',
                'html'  => 'text/html',
                'css'   => 'text/css',
                'js'    => 'application/javascript',
                'php'   => 'application/x-httpd-php',
                'exe'   => 'application/x-msdownload',
                'iso'   => 'application/octet-stream',
            ];

            $me = $mimeMapping[$extension] ?? '';

            // 扩展名是否允许
            // 检查 MIME 类型是否匹配
            return in_array($extension, $allowedType) && $fileType===$me;
        }

        /**
         * 生成唯一的文件名
         *
         * @param string $prefix 文件名前缀
         * @param string $extension 文件扩展名
         * @return string 唯一的文件名
         */
        private function generate_unique_filename(string $prefix, string $extension): string
        {
            return sprintf('%s-%d-%03d.%s', $prefix, time(), mt_rand(0, 999), $extension);
        }



    }
}