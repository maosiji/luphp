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
 *      支持 ORDER BY 、 LIMIT / OFFSET 、 GROUP BY 、 HAVING（含分组）
 *      不支持 联合查询 多表连接 子查询
 */

namespace MAOSIJI\LU\WP\SQL;

class LUWPSQLWhereCondition
{
    private $nodes       = [];
    private $havingNodes = [];
    private $orderBy     = [];
    private $groupBy     = [];
    private $limit       = null;
    private $offset      = 0;

    // =========================== WHERE 条件方法 ===========================

    public function and(string $column, string $operator, $value, $format): self
    {
        $this->addConditionTo($this->nodes, 'AND', $column, $operator, $value, $format);
        return $this;
    }

    public function or(string $column, string $operator, $value, $format): self
    {
        $this->addConditionTo($this->nodes, 'OR', $column, $operator, $value, $format);
        return $this;
    }

    public function andGroup(callable $callback): self
    {
        $this->addGroupTo($this->nodes, 'AND', false, $callback);
        return $this;
    }

    public function orGroup(callable $callback): self
    {
        $this->addGroupTo($this->nodes, 'OR', false, $callback);
        return $this;
    }

    public function andNotGroup(callable $callback): self
    {
        $this->addGroupTo($this->nodes, 'AND', true, $callback);
        return $this;
    }

    public function orNotGroup(callable $callback): self
    {
        $this->addGroupTo($this->nodes, 'OR', true, $callback);
        return $this;
    }

    // =========================== HAVING 条件方法 ===========================

    /**
     * 添加一个 HAVING 条件（AND 逻辑）
     */
    public function having(string $column, string $operator, $value, $format): self
    {
        $this->addConditionTo($this->havingNodes, 'AND', $column, $operator, $value, $format);
        return $this;
    }

    /**
     * 添加一个 HAVING 条件（OR 逻辑）
     */
    public function orHaving(string $column, string $operator, $value, $format): self
    {
        $this->addConditionTo($this->havingNodes, 'OR', $column, $operator, $value, $format);
        return $this;
    }

    /**
     * 添加 HAVING AND 分组（括号）
     */
    public function havingGroup(callable $callback): self
    {
        $this->addGroupTo($this->havingNodes, 'AND', false, $callback);
        return $this;
    }

    /**
     * 添加 HAVING OR 分组
     */
    public function orHavingGroup(callable $callback): self
    {
        $this->addGroupTo($this->havingNodes, 'OR', false, $callback);
        return $this;
    }

    /**
     * 添加 HAVING AND NOT 分组
     */
    public function havingNotGroup(callable $callback): self
    {
        $this->addGroupTo($this->havingNodes, 'AND', true, $callback);
        return $this;
    }

    /**
     * 添加 HAVING OR NOT 分组
     */
    public function orHavingNotGroup(callable $callback): self
    {
        $this->addGroupTo($this->havingNodes, 'OR', true, $callback);
        return $this;
    }

    // =========================== 排序 / 分页 / 分组 ===========================

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new \InvalidArgumentException('Order direction must be ASC or DESC.');
        }
        $column = preg_replace('/[^a-zA-Z0-9_\.]/', '', $column);
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    public function groupBy(string $column): self
    {
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        $this->groupBy[] = $column;
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit  = max(0, $limit);
        $this->offset = max(0, $offset);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = max(0, $offset);
        return $this;
    }

    // =========================== 构建方法 ===========================

    public function build(): array
    {
        // WHERE
        $whereResult = $this->buildClauseSQL($this->nodes);
        $whereSql = !empty($whereResult['sql']) ? ' WHERE ' . $whereResult['sql'] : '';
        $values   = $whereResult['values'];

        // GROUP BY
        $groupSql = '';
        if (!empty($this->groupBy)) {
            $groupSql = ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        // HAVING
        $havingSql = '';
        if (!empty($this->havingNodes)) {
            $havingResult = $this->buildClauseSQL($this->havingNodes);
            if (!empty($havingResult['sql'])) {
                $havingSql = ' HAVING ' . $havingResult['sql'];
                $values = array_merge($values, $havingResult['values']);
            }
        }

        // ORDER BY
        $orderSql = '';
        if (!empty($this->orderBy)) {
            $orderSql = ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        // LIMIT / OFFSET
        $limitSql = '';
        if ($this->limit !== null) {
            $limitSql = ' LIMIT %d';
            $values[] = $this->limit;
            if ($this->offset > 0) {
                $limitSql .= ' OFFSET %d';
                $values[] = $this->offset;
            }
        }

        $sql = $whereSql . $groupSql . $havingSql . $orderSql . $limitSql;

        return [
            'sql'    => $sql,
            'value'  => $values,
        ];
    }

    // =========================== 内部实现 ===========================

    private function addConditionTo(array &$target, string $logic, string $column, string $operator, $value, $format)
    {
        $op = strtoupper($operator);
        $target[] = [
            'type'     => 'condition',
            'logic'    => $logic,
            'column'   => $column,
            'operator' => $op,
            'value'    => $value,
            'format'   => $format,
        ];
    }

    private function addGroupTo(array &$target, string $logic, bool $not, callable $callback)
    {
        $sub = new self();
        $callback($sub);
        if (!empty($sub->nodes)) {
            $target[] = [
                'type'     => 'group',
                'logic'    => $logic,
                'not'      => $not,
                'children' => $sub->nodes,
            ];
        }
    }

    /**
     * 将节点数组转换为不带关键字的 SQL 条件片段和参数
     * @return array ['sql' => string, 'values' => array]
     */
    private function buildClauseSQL(array $nodes): array
    {
        $parts   = [];
        $values  = [];
        $isFirst = true;

        foreach ($nodes as $node) {
            if ($node['type'] === 'condition') {
                $condition = $this->buildCondition($node);
                if ($condition === null) {
                    continue;
                }
                $prefix = $isFirst ? '' : (' ' . $node['logic'] . ' ');
                $parts[] = $prefix . $condition['sql'];
                $values = array_merge($values, $condition['values']);
                $isFirst = false;
            } elseif ($node['type'] === 'group') {
                // 递归构建子分组（不带关键字）
                $groupResult = $this->buildClauseSQL($node['children']);
                if (empty($groupResult['sql'])) {
                    continue;
                }
                $notPrefix = $node['not'] ? 'NOT ' : '';
                $prefix = $isFirst ? '' : (' ' . $node['logic'] . ' ');
                $parts[] = $prefix . $notPrefix . '(' . $groupResult['sql'] . ')';
                $values = array_merge($values, $groupResult['values']);
                $isFirst = false;
            }
        }

        return [
            'sql'    => implode('', $parts),
            'values' => $values,
        ];
    }

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
                if (is_array($format)) {
                    $placeholders = $format;
                } else {
                    $placeholders = array_fill(0, count($value), $format);
                }
                $inStr = implode(',', $placeholders);
                $sql = "$column $operator ($inStr)";
                return ['sql' => $sql, 'values' => array_values($value)];

            default:
                LUWPSQLParamValidator::format($format);
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

//// 带 ORDER BY 和 LIMIT
//$where = (new LUWPSQLWhereCondition())
//    ->and('status', '=', 'published', '%s')
//    ->orderBy('post_date', 'DESC')
//    ->orderBy('id', 'ASC')
//    ->limit(10, 5);   // LIMIT 10 OFFSET 5
//
//$result = $where->build();
//// 按 status 筛选出特定状态的记录，先按发布日期倒序、相同日期再按 id 正序排列，然后分页取指定范围的行（跳过若干条，取若干条）。
//// $result['sql']    => " WHERE status = %s ORDER BY post_date DESC, id ASC LIMIT %d OFFSET %d"
//// $result['values'] => ['published', 10, 5]

//// 无 WHERE 只有排序和分页
//$where2 = (new LUWPSQLWhereCondition())
//    ->orderBy('name')
//    ->limit(20);
//$r2 = $where2->build();
//// sql    : " ORDER BY name ASC LIMIT %d"
//// values : [20]

//$where = (new LUWPSQLWhereCondition())
//    ->and('status', '=', 'published', '%s')
//    ->groupBy('gender')
//    ->orderBy('post_date', 'DESC')
//    ->limit(10, 5);
//
//$result = $where->build();
//// 筛选出状态为指定字符串的记录，按性别分组，按发布日期降序排列，然后分页取第 6 ~ 15 条结果
//// sql    : " WHERE status = %s GROUP BY gender ORDER BY post_date DESC LIMIT %d OFFSET %d"
//// values : ['published', 10, 5]

//// 基本 HAVING 用法：统计每个分类下文章数，只保留大于5的分类
//$where = (new LUWPSQLWhereCondition())
//    ->and('post_type', '=', 'post', '%s')
//    ->groupBy('category_id')
//    ->having('COUNT(*)', '>', 5, '%d')
//    ->orderBy('category_id');
//$result = $where->build();
//// sql    : " WHERE post_type = %s GROUP BY category_id HAVING COUNT(*) > %d ORDER BY category_id ASC"
//// values : ['post', 5]

// 复杂 HAVING 分组：同时筛选两个聚合条件
//$where = new LUWPSQLWhereCondition();
//$where->groupBy('author_id')
//    ->having('COUNT(*)', '>=', 10, '%d')
//    ->orHavingGroup(function ($sub) {
//        $sub->having('SUM(view_count)', '>', 1000, '%d')
//            ->having('AVG(rating)', '>=', 4.5, '%f');
//    });
//// 结果: " GROUP BY author_id HAVING COUNT(*) >= %d OR (SUM(view_count) > %d AND AVG(rating) >= %f)"
