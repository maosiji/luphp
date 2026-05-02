<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-30 02:32
 * update               : 2026-05-01 (重构，format 必须显式传入)
 * project              : luphp
 * description          : 动态 WHERE 条件构造器
 *
 *      提供流畅接口构建安全的查询条件，避免手动拼接数组容易出错的问题。
 *      支持 AND、OR、NOT 分组，自动生成占位符和参数绑定数组。
 *      format 参数必须显式传入，不再自动推断。
 */

namespace MAOSIJI\LU\WP\SQL;

class LUWPSQLWhereCondition
{
    private $nodes = [];

    /**
     * 添加一个 AND 条件
     *
     * @param string $column   字段名
     * @param string $operator 运算符：=, >, <, >=, <=, <>, !=, LIKE, BETWEEN, IN, NOT IN 等
     * @param mixed  $value    值。BETWEEN 时传 [min, max]，IN/NOT IN 时传数组
     * @param string|array $format 占位符格式，如 '%s', '%d', '%f'。
     *                              普通运算符传字符串；BETWEEN 传两个元素的数组；
     *                              IN/NOT IN 可传统一占位符字符串或与值一一对应的数组。
     * @return $this
     */
    public function and(string $column, string $operator, $value, $format): self
    {
        return $this->condition('AND', $column, $operator, $value, $format);
    }

    /**
     * 添加一个 OR 条件
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param string|array $format
     * @return $this
     */
    public function or(string $column, string $operator, $value, $format): self
    {
        return $this->condition('OR', $column, $operator, $value, $format);
    }

    /**
     * 添加一个 AND 分组（括号）
     *
     * @param callable $callback 接收新的 WhereCondition 实例
     * @return $this
     */
    public function andGroup(callable $callback): self
    {
        return $this->groupInternal('AND', false, $callback);
    }

    /**
     * 添加一个 OR 分组（括号）
     *
     * @param callable $callback
     * @return $this
     */
    public function orGroup(callable $callback): self
    {
        return $this->groupInternal('OR', false, $callback);
    }

    /**
     * 添加一个 AND NOT 分组（括号前带 NOT）
     *
     * @param callable $callback
     * @return $this
     */
    public function andNotGroup(callable $callback): self
    {
        return $this->groupInternal('AND', true, $callback);
    }

    /**
     * 添加一个 OR NOT 分组（括号前带 NOT，并用 OR 连接前面的条件）
     *
     * @param callable $callback
     * @return $this
     */
    public function orNotGroup(callable $callback): self
    {
        return $this->groupInternal('OR', true, $callback);
    }

    /**
     * 构建最终的 SQL 片段和参数数组
     *
     * @return array ['sql' => string, 'values' => array]
     */
    public function build(): array
    {
        if (empty($this->nodes)) {
            return ['sql' => '', 'values' => []];
        }

        return $this->buildNodes($this->nodes);
    }

    //------------------------- 内部实现 -------------------------

    private function condition(string $logic, string $column, string $operator, $value, $format): self
    {
        $op = strtoupper($operator);
        $this->nodes[] = [
            'type'     => 'condition',
            'logic'    => $logic,
            'column'   => $column,
            'operator' => $op,
            'value'    => $value,
            'format'   => $format,
        ];
        return $this;
    }

    private function groupInternal(string $logic, bool $not, callable $callback): self
    {
        $sub = new self();
        $callback($sub);
        if (!empty($sub->nodes)) {
            $this->nodes[] = [
                'type'     => 'group',
                'logic'    => $logic,
                'not'      => $not,
                'children' => $sub->nodes,
            ];
        }
        return $this;
    }

    private function buildNodes(array $nodes, bool $outer = true): array
    {
        $parts  = [];
        $values = [];
        $isFirst = true;

        foreach ($nodes as $node) {
            if ($node['type'] === 'condition') {
                $condition = $this->buildCondition($node);
                if ($condition === null) {
                    continue;
                }
                $prefix = $isFirst ? ($outer ? ' WHERE ' : '') : (' ' . $node['logic'] . ' ');
                $parts[] = $prefix . $condition['sql'];
                $values = array_merge($values, $condition['values']);
                $isFirst = false;
            } elseif ($node['type'] === 'group') {
                $groupResult = $this->buildNodes($node['children'], false);
                if (empty($groupResult['sql'])) {
                    continue;
                }
                $innerSql = preg_replace('/^\s*WHERE\s+/i', '', $groupResult['sql']);
                $notPrefix = $node['not'] ? 'NOT ' : '';
                $prefix = $isFirst ? ($outer ? ' WHERE ' : '') : (' ' . $node['logic'] . ' ');
                $parts[] = $prefix . $notPrefix . '(' . $innerSql . ')';
                $values = array_merge($values, $groupResult['values']);
                $isFirst = false;
            }
        }

        return [
            'sql'    => implode('', $parts),
            'values' => $values,
        ];
    }

    /**
     * 构建单个条件的 SQL 片段和值
     * @return array|null
     */
    private function buildCondition(array $node)
    {
        $column   = $node['column'];
        $operator = $node['operator'];
        $value    = $node['value'];
        $format   = $node['format'];

        switch ($operator) {
            case 'BETWEEN':
                LUWPSQLParamValidator::between($value, $format);
                $sql = "$column BETWEEN {$format[0]} AND {$format[1]}";
                return ['sql' => $sql, 'values' => [$value[0], $value[1]]];

            case 'IN':
            case 'NOT IN':
                LUWPSQLParamValidator::inAndNotin($value, $format);
                // 兼容 format 为统一字符串或与 value 等长的数组
                if (is_array($format)) {
                    $placeholders = $format;
                } else {
                    // 统一占位符
                    $placeholders = array_fill(0, count($value), $format);
                }
                $inStr = implode(',', $placeholders);
                $sql = "$column $operator ($inStr)";
                // values 保持原始顺序，与占位符一一对应
                return ['sql' => $sql, 'values' => array_values($value)];

            default:
                // 普通比较运算符
                LUWPSQLParamValidator::formatIsString($format);

                $sql = "$column $operator $format";
                return ['sql' => $sql, 'values' => [$value]];
        }
    }
}

// ========================== 详细使用示例 ==========================

/*
 * 示例 1：简单 AND 条件，多种比较运算符
 */
//$where = (new LUWPSQLWhereCondition())
//    ->and('id', '=', 1, '%d')
//    ->and('age', '>', 18, '%d')
//    ->and('name', 'LIKE', '%猫%', '%s')
//    ->and('status', '!=', 'deleted', '%s');
//$result = $where->build();
// sql    : " WHERE id = %d AND age > %d AND name LIKE %s AND status != %s"
// values : [1, 18, '%猫%', 'deleted']

/*
 * 示例 2：OR 条件混合
 */
//$where = (new LUWPSQLWhereCondition())
//    ->and('type', '=', 'post', '%s')
//    ->or('type', '=', 'page', '%s')
//    ->or('type', '=', 'attachment', '%s');
//$result = $where->build();
// sql    : " WHERE type = %s OR type = %s OR type = %s"
// values : ['post', 'page', 'attachment']

/*
 * 示例 3：BETWEEN（要求 format 为两个元素的数组）
 */
//$where = (new LUWPSQLWhereCondition())
//    ->and('price', 'BETWEEN', [100, 200], ['%d', '%d']);
//$result = $where->build();
// sql    : " WHERE price BETWEEN %d AND %d"
// values : [100, 200]

// BETWEEN 也可以使用不同占位符
//$where2 = (new LUWPSQLWhereCondition())
//    ->and('created_at', 'BETWEEN', ['2026-01-01', '2026-01-31'], ['%s', '%s']);
// sql : " WHERE created_at BETWEEN %s AND %s"

/*
 * 示例 4：IN 条件，统一占位符
 */
//$where = (new LUWPSQLWhereCondition())
//    ->and('status', 'IN', ['active', 'pending', 'draft'], '%s');
//$result = $where->build();
// sql    : " WHERE status IN (%s,%s,%s)"
// values : ['active', 'pending', 'draft']

/*
 * 示例 5：IN 条件，每个值指定独立占位符（混合类型）
 */
//$where = (new LUWPSQLWhereCondition())
//    ->and('id', 'IN', [1, 2, 3], ['%d', '%d', '%d']);
//$result = $where->build();
// sql    : " WHERE id IN (%d,%d,%d)"
// values : [1, 2, 3]

// 混合类型示例
//$where2 = (new LUWPSQLWhereCondition())
//    ->and('mixed_col', 'IN', [1, 'hello', 3.14], ['%d', '%s', '%f']);
// 生成 " WHERE mixed_col IN (%d,%s,%f)"

/*
 * 示例 6：NOT IN 条件
 */
//$where = (new LUWPSQLWhereCondition())
//    ->and('user_id', 'NOT IN', [10, 20, 30], '%d');
//$result = $where->build();
// sql    : " WHERE user_id NOT IN (%d,%d,%d)"
// values : [10, 20, 30]

/*
 * 示例 7：AND 分组 (group)
 */
//$where = (new LUWPSQLWhereCondition())
//    ->and('status', '=', 'published', '%s')
//    ->andGroup(function ($sub) {
//        $sub->and('category_id', '=', 5, '%d')
//            ->or('tag_id', '=', 10, '%d');
//    })
//    ->and('date', '>=', '2026-01-01', '%s');
//$result = $where->build();
// sql    : " WHERE status = %s AND (category_id = %d OR tag_id = %d) AND date >= %s"
// values : ['published', 5, 10, '2026-01-01']

/*
 * 示例 8：OR 分组 (orGroup)
 */
//$where = (new LUWPSQLWhereCondition())
//    ->and('type', '=', 'product', '%s')
//    ->orGroup(function ($sub) {
//        $sub->and('discount', '>', 0, '%d')
//            ->and('featured', '=', 1, '%d');
//    });
//$result = $where->build();
// sql    : " WHERE type = %s OR (discount > %d AND featured = %d)"
// values : ['product', 0, 1]

/*
 * 示例 9：AND NOT 分组 (notGroup)
 */
//$where = (new LUWPSQLWhereCondition())
//    ->and('active', '=', 1, '%d')
//    ->andNotGroup(function ($sub) {
//        $sub->and('role', '=', 'banned', '%s')
//            ->or('expired', '=', 1, '%d');
//    });
//$result = $where->build();
// sql    : " WHERE active = %d AND NOT (role = %s OR expired = %d)"
// values : [1, 'banned', 1]

/*
 * 示例 10：OR NOT 分组 (orNotGroup)
 */
//$where = (new LUWPSQLWhereCondition())
//    ->and('type', '=', 'event', '%s')
//    ->orNotGroup(function ($sub) {
//        $sub->and('capacity', '>=', 100, '%d')
//            ->and('location', '=', 'online', '%s');
//    });
//$result = $where->build();
// sql    : " WHERE type = %s OR NOT (capacity >= %d AND location = %s)"
// values : ['event', 100, 'online']

/*
 * 示例 11：复杂嵌套组合
 * 需求：WHERE (a=1 AND b=2) OR (c=3 AND NOT (d=4 OR e=5))
 */
//$where = (new LUWPSQLWhereCondition())
//    ->andGroup(function ($sub) {
//        $sub->and('a', '=', 1, '%d')
//            ->and('b', '=', 2, '%d');
//    })
//    ->orGroup(function ($sub) {
//        $sub->and('c', '=', 3, '%d')
//            ->andNotGroup(function ($sub2) {
//                $sub2->and('d', '=', 4, '%d')
//                    ->or('e', '=', 5, '%d');
//            });
//    });
//$result = $where->build();
// sql    : " WHERE (a = %d AND b = %d) OR (c = %d AND NOT (d = %d OR e = %d))"
// values : [1, 2, 3, 4, 5]

/*
 * 示例 12：与 WordPress $wpdb->prepare 配合使用
 */
//global $wpdb;
//$where = (new LUWPSQLWhereCondition())
//    ->and('post_type', '=', 'post', '%s')
//    ->and('post_status', 'IN', ['publish', 'draft'], '%s');
//$result = $where->build();
//$sql = "SELECT * FROM {$wpdb->posts} " . $result['sql'];
//$prepared = $wpdb->prepare($sql, $result['values']);
// 实际执行：$wpdb->get_results($prepared);

/*
 * 示例 13：空条件（无任何添加）
 */
//$where = new LUWPSQLWhereCondition();
//$result = $where->build();
// sql    : ''
// values : []

/*
 * 示例 14：错误处理 —— BETWEEN 的 format 不是数组
 */
//try {
//    $where = (new LUWPSQLWhereCondition())
//        ->and('col', 'BETWEEN', [1, 2], '%d');  // 应抛异常
//} catch (\InvalidArgumentException $e) {
//    echo $e->getMessage();  // "BETWEEN requires format as an array of two placeholder strings..."
//}

/*
 * 示例 15：错误处理 —— IN 的 format 数组长度不匹配
 */
//try {
//    $where = (new LUWPSQLWhereCondition())
//        ->and('id', 'IN', [1, 2, 3], ['%d', '%d']); // 长度不匹配
//} catch (\InvalidArgumentException $e) {
//    echo $e->getMessage();
//}