# luphp

封装一些常用功能，便于快速开发（测试中...）

## LuUrl
 
````php
// 获取当前网页链接
get(): string
// 更新指定参数后的链接（追加 + 更新值）
update_params( array $arr, string $url = '' ): string
// 删除指定参数后的链接
delete_params( array $arr, string $url = '' ): string
````

## LUSend

````php
// 发送数组数据
send_array( int $code, string $msg, $data='', string $reload='0', array $newArr=array() ): array
// 发送json数据并终止程序
send_json( int $code, string $msg, $data='', string $reload='0', array $newArr=array() )
````

## LUWPSend
````php
// 用 wp_send_json 发送数据
send_json(int $code, string $msg, $data = '', string $reload = '', array $newArr = [], int $flags = 0)
````

## LUWPNonce

````php
create_nonce( string $str ): string
verify_nonce( string $nonce, string $str ): bool
````

## LuCurl

````php
get(string $url, array $headers = [], bool $overwrite = false): array
post(string $url, array $data, array $headers = [], bool $overwrite = false): array
put(string $url, array $data, array $headers = [], bool $overwrite = false): array
delete(string $url, array $data = [], array $headers = [], bool $overwrite = false): array
patch(string $url, array $data, array $headers = [], bool $overwrite = false): array
````

## LuSafe

````php
// 断是否连续点击 ajax 按钮，并禁止
check_too_many_requests(int $timediff = 5)
````

## LuTime

````php
// 返回时间间隔数组 array('day'=>'', 'hour'=>'', 'min'=>'', 'sec=>'')
calculate_timediff ( int $begin_time, int $end_time ): array
````

## LURandom

````php
// 获取指定位数的随机数
rand_number(int $length = 6, bool $is_first_zero = true): string
// 返回0到9之间的奇数
rand_odd(): int
// 返回0到9之间的偶数
rand_even(): int
````

## LuVersion

````php
// 检测版本号格式是否正确
check_version ( string $version ): bool
````

## LUNo19

````php
create( $prefix='0755', int $pos=2, int $sex=0 ): string
verify( $id19Number, int $pos=2 ): bool
````

