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
namespace MAOSIJI\LUPHP;
if (!class_exists('LUFile')) {
    class LUFile
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
         * WP批量上传
         *
         * @param string $uploadDir 上传文件的绝对路径
         * @param string $uploadUrl 上传文件的 URL 相对路径 /wp-content/uploads/shanhu/
         * @param string $files_key $_FILES 的 key
         * @param string $name 上传文件的新名称
         * @param string $type 上传文件的类型，默认 all
         * @return array 返回上传文件的 URL 列表
         */
        public function upload(string $uploadDir, string $uploadUrl, string $files_key, string $name, string $type = 'all'): array
        {
            $backArr = [];

            if (empty($_FILES[$files_key])) {
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
            foreach ($_FILES[$files_key]['tmp_name'] as $k => $fileTmp) {
                $fileName = $_FILES[$files_key]['name'][$k];
                $fileType = $_FILES[$files_key]['type'][$k];

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
         * @param string $allowedType 允许的文件类型
         * @return bool 是否有效
         */
        private function is_valid_file_type(string $fileName, string $fileType, string $allowedType): bool
        {
            // 获取文件扩展名
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // 允许的扩展名映射
            $allowedExtensions = [
                'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg'], // 图片文件
                'pdf'   => ['pdf'],                                                      // PDF 文件
                'doc'   => ['doc', 'docx', 'odt'],                                       // 文档文件
                'xls'   => ['xls', 'xlsx', 'csv', 'ods'],                                // 表格文件
                'ppt'   => ['ppt', 'pptx', 'odp'],                                       // 幻灯片文件
                'zip'   => ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz'],              // 压缩文件
                'audio' => ['mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a', 'wma'],          // 音频文件
                'video' => ['mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm', 'mpeg'],  // 视频文件
                'text'  => ['txt', 'log', 'ini', 'conf', 'md', 'json', 'xml', 'yaml'],  // 文本文件
                'font'  => ['ttf', 'otf', 'woff', 'woff2', 'eot'],                      // 字体文件
            ];

            // 如果是自定义 MIME 类型，直接匹配
            if ($allowedType === 'all' || strpos($fileType, $allowedType) !== false) {
                return true;
            }

            // 如果是扩展名类型，检查扩展名
            return isset($allowedExtensions[$allowedType]) && in_array($extension, $allowedExtensions[$allowedType]);
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