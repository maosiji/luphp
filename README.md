# luphp

封装一些常用功能，便于快速开发（测试中...）


## LuFormat

````php
// ajax 发送数据
send_array( int $code, string $msg, mixed $data='', string $reload='', array $newArr=array() ): array
````

## LuCurl

````php
runGet ( string $url, int $isOverWriteHeader = 0, array $headerNewArray = array() )
runPost ( string $url, array $data, int $isOverWriteHeader = 0, array $headerNewArray = array() )
runPut ( string $url, array $data, int $isOverWriteHeader = 0, array $headerNewArray = array() )
runDelete ( string $url, array $data, int $isOverWriteHeader = 0, array $headerNewArray = array() )
runPatch ( string $url, array $data, int $isOverWriteHeader = 0, array $headerNewArray = array() )
````

## LuSafe

````php
// 断是否连续点击 ajax 按钮，并禁止
checkTooManyRequests( int $timediff=5 )
// 为 session或cookie 设置一组键值对，可用于验证码
setKeyValue( string $key, string $value, string $type='all', int $timediff=600 ): bool
// 验证设置的session或cookie
checkKeyValue( string $key, string $value, string $type='all', int $isDelete=1 ): bool
// 返回请求名称对应的值
GetKeyValue( string $key, string $type='all', int $isDelete=0 ): string
````

# LuTime

````php
// 返回时间间隔数组 array('day'=>'', 'hour'=>'', 'min'=>'', 'sec=>'')
calculateTimeDiff ( int $begin_time, int $end_time ): array
````

# LuTool

````php
// 获取指定位数的随机数
getRandNumber ( $length = 6 )
````

# LuUrl

````php
// 获取当前网页链接
getUrl (): string
// 删除指定参数后的链接
deleteParam ( string $url, array $arr ): string
// 添加指定参数后的链接
addParam ( $url, $arr ): string
````

# LuVersion

````php
// 检测版本号格式是否正确
checkVersion ( $version ): bool
````

