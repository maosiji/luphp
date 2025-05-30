# luphp

封装一些常用功能，便于快速开发（测试中...）

## Original

### 1、LuUrl
 
````php
// 获取当前网页链接
get(): string
// 更新指定参数后的链接（追加 + 更新值）
update_params( array $arr, string $url = '' ): string
// 删除指定参数后的链接
delete_params( array $arr, string $url = '' ): string
````

### 2、LUSend

````php
// 发送数组数据
send_array( int $code, string $msg, $data='', string $reload='0', array $newArr=array() ): array
// 发送json数据并终止程序
send_json( int $code, string $msg, $data='', string $reload='0', array $newArr=array() )
````

### 5、LuCurl

````php
get(string $url, array $headers = [], bool $overwrite = false): array
post(string $url, array $data, array $headers = [], bool $overwrite = false): array
put(string $url, array $data, array $headers = [], bool $overwrite = false): array
delete(string $url, array $data = [], array $headers = [], bool $overwrite = false): array
patch(string $url, array $data, array $headers = [], bool $overwrite = false): array
````

### 6、LuSafe

````php
// 断是否连续点击 ajax 按钮，并禁止
check_too_many_requests(int $timediff = 5)
````

### 7、LuTime

````php
// 返回时间间隔数组 array('day'=>'', 'hour'=>'', 'min'=>'', 'sec=>'')
calculate_timediff ( int $begin_time, int $end_time ): array
````

### 8、LURandom

````php
// 获取指定位数的随机数
rand_number(int $length = 6, bool $is_first_zero = true): string
// 返回0到9之间的奇数
rand_odd(): int
// 返回0到9之间的偶数
rand_even(): int
````

### 9、LuVersion

````php
// 检测版本号格式是否正确
check_version ( string $version ): bool
````

### 10、LUNo19

````php
create( $prefix='0755', int $pos=2, int $sex=0 ): string
verify( $id19Number, int $pos=2 ): bool
````

### 11、LUPrice

````php
format( $price ): float
````

### 12、LUIdcard
````php
// 是否是合法身份证号
is(string $idCard): bool
// 返回性别，1 男，2 女
sex(string $idCard): int
// 返回生日，1980-02-03
birthday(string $idCard): string
// 返回省级，北京市、台湾省、香港特别行政区
province(string $idCard): string
````

## wordpress


### 1、LUWPSend
````php
// 用 wp_send_json 发送数据
send_json(int $code, string $msg, $data = '', string $reload = '', array $newArr = [], int $flags = 0)
````

### 2、LUWPNonce

````php
create_nonce( string $str ): string
verify_nonce( string $nonce, string $str ): bool
````
