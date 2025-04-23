<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-03-14 19:01
 * update               : 
 * project              : luphp
 */
$dbsql = \WUTI\XYYSD\SHANHU\DBPsyDetail::getObj();
/*
 * 【get_results】
 * */
$results1 = $dbsql->get_results(
    array(
        'meta'      => array(
            'a_name'        => '张三',
        ),
        'compare'   => array(
            '=',
        ),
        'format'    => array(
            '%s',
        )
    )
);
//print_r( count($results1['data']) );

$results2 = $dbsql->get_results(
    array(
        'meta'      => array(
            'zx_start_time' => array(
                '2025-02-19',
                '2025-02-20'
            ),
            'a_name'        => '张三',
        ),
        'compare'   => array(
            array( '>','<' ),
            '=',
        ),
        'format'    => array(
            array( '%s', '%s' ),
            '%s',
        )
    )
);
//print_r($results2);

$results3 = $dbsql->get_results(
    array(
        'meta'      => array(
            'a_name'        => '张三',
            'zx_start_time' => array(
                '2025-02-19',
                '2025-02-20'
            ),
        ),
        'compare'   => array(
            '=',
            array( '>','<' ),
        ),
        'format'    => array(
            '%s',
            array( '%s', '%s' ),
        )
    )
);
//print_r($results3);

$results4 = $dbsql->get_results(
    array(
        'meta'      => array(
            'a_name'        => '张三',
            'zx_start_time' => array(
                '2025-02-19',
                '2025-02-20'
            ),
        ),
        'compare'   => array(
            '=',
            array( '>','<' ),
        ),
        'format'    => array(
            '%s',
            array( '%s', '%s' ),
        )
    ),
    array(),
    2,
    'f_name',
    'ARRAY_A'
);
//print_r($results4);

$results5 = $dbsql->get_results(
    array(
        'meta'      => array(
            'a_name'        => '张三',
            'zx_start_time' => array(
                '2025-02-19',
                '2025-02-20'
            ),
        ),
        'compare'   => array(
            '=',
            array( '>','<' ),
        ),
        'format'    => array(
            '%s',
            array( '%s', '%s' ),
        )
    ),
    array(
        'orderby' => 'zx_start_time',
        'sort' => 'ASC'
    ),
    2,
    'f_name',
    'ARRAY_A'
);
//print_r($results5);

$results6 = $dbsql->get_results(
    array(
        'meta'      => array(
            'a_name'        => '张三',
            'zx_start_time' => array(
                '2025-02-18',
                '2025-02-19'
            ),
        ),
        'compare'   => array(
            '=',
            array( '>','<' ),
        ),
        'format'    => array(
            '%s',
            array( '%s', '%s' ),
        )
    ),
    array(
        'orderby' => 'zx_start_time',
        'sort' => 'ASC'
    ),
    2,
    'f_name',
    'ARRAY_A'
);
print_r($results6);

$row1 = $dbsql->get_row(
    array(
        'meta'      => array(
            'a_name'        => '张三',
        ),
        'compare'   => array(
            '=',
        ),
        'format'    => array(
            '%s',
        )
    )
);
//print_r( $row1 );

$row2 = $dbsql->get_row(
    array(
        'meta'      => array(
            'zx_start_time' => array(
                '2025-02-19',
                '2025-02-20'
            ),
            'a_name'        => '张三',
        ),
        'compare'   => array(
            array( '>','<' ),
            '=',
        ),
        'format'    => array(
            array( '%s', '%s' ),
            '%s',
        )
    )
);
//print_r($row2);

$row3 = $dbsql->get_row(
    array(
        'meta'      => array(
            'a_name'        => '张三',
            'zx_start_time' => array(
                '2025-02-19',
                '2025-02-20'
            ),
        ),
        'compare'   => array(
            '=',
            array( '>','<' ),
        ),
        'format'    => array(
            '%s',
            array( '%s', '%s' ),
        )
    )
);
//print_r($row3);

$row4 = $dbsql->get_row(
    array(
        'meta'      => array(
            'a_name'        => '张三',
            'zx_start_time' => array(
                '2025-02-19',
                '2025-02-20'
            ),
        ),
        'compare'   => array(
            '=',
            array( '>','<' ),
        ),
        'format'    => array(
            '%s',
            array( '%s', '%s' ),
        )
    ),
    array(),
    'f_name',
    'ARRAY_A'
);
//print_r($row4);

$row5 = $dbsql->get_row(
    array(
        'meta'      => array(
            'a_name'        => '张三',
            'zx_start_time' => array(
                '2025-02-19',
                '2025-02-20'
            ),
        ),
        'compare'   => array(
            '=',
            array( '>','<' ),
        ),
        'format'    => array(
            '%s',
            array( '%s', '%s' ),
        )
    ),
    array(
        'orderby' => 'zx_start_time',
        'sort' => 'ASC'
    ),
    'f_name',
    'ARRAY_A'
);
//print_r($row5);

// 未查询到的情况
$row6 = $dbsql->get_row(
    array(
        'meta'      => array(
            'a_name'        => '张三',
            'zx_start_time' => array(
                '2025-02-18',
                '2025-02-19'
            ),
        ),
        'compare'   => array(
            '=',
            array( '>','<' ),
        ),
        'format'    => array(
            '%s',
            array( '%s', '%s' ),
        )
    ),
    array(
        'orderby' => 'zx_start_time',
        'sort' => 'ASC'
    ),
    'f_name',
    'ARRAY_A'
);
//print_r($row6);

$var1 = $dbsql->get_var(
    array(
        'meta'      => array(
            'a_name'        => '张三',
        ),
        'compare'   => array(
            '=',
        ),
        'format'    => array(
            '%s',
        )
    )
);
//print_r( $var1 );

$var2 = $dbsql->get_var(
    array(
        'meta'      => array(
            'zx_start_time' => array(
                '2025-02-19',
                '2025-02-20'
            ),
            'a_name'        => '张三',
        ),
        'compare'   => array(
            array( '>','<' ),
            '=',
        ),
        'format'    => array(
            array( '%s', '%s' ),
            '%s',
        )
    )
);
//print_r($var2);

$var3 = $dbsql->get_var(
    array(
        'meta'      => array(
            'a_name'        => '张三',
            'zx_start_time' => array(
                '2025-02-19',
                '2025-02-20'
            ),
        ),
        'compare'   => array(
            '=',
            array( '>','<' ),
        ),
        'format'    => array(
            '%s',
            array( '%s', '%s' ),
        )
    )
);
//print_r($var3);

$var4 = $dbsql->get_var(
    array(
        'meta'      => array(
            'a_name'        => '张三',
            'zx_start_time' => array(
                '2025-02-19',
                '2025-02-20'
            ),
        ),
        'compare'   => array(
            '=',
            array( '>','<' ),
        ),
        'format'    => array(
            '%s',
            array( '%s', '%s' ),
        )
    ),
    'f_num',
    'MAX'
);
//print_r($var4);

$var5 = $dbsql->get_var(
    array(
        'meta'      => array(
            'a_name'        => '张三',
            'zx_start_time' => array(
                '2025-02-18',
                '2025-02-19'
            ),
        ),
        'compare'   => array(
            '=',
            array( '>','<' ),
        ),
        'format'    => array(
            '%s',
            array( '%s', '%s' ),
        )
    ),
    'f_num',
    'SUM'
);
//print_r($var5);

$col1 = $dbsql->get_col(
    array(
        'meta'      => array(
            'a_name'        => '张三',
        ),
        'compare'   => array(
            '=',
        ),
        'format'    => array(
            '%s',
        )
    ),
    'f_name'
);
//print_r( $col1 );

$col2 = $dbsql->get_col(
    array(
        'meta'      => array(
            'zx_start_time' => array(
                '2025-02-19',
                '2025-02-20'
            ),
            'a_name'        => '张三',
        ),
        'compare'   => array(
            array( '>','<' ),
            '=',
        ),
        'format'    => array(
            array( '%s', '%s' ),
            '%s',
        )
    ),
    'f_name'
);
//print_r($col2);

$col3 = $dbsql->get_col(
    array(
        'meta'      => array(
            'a_name'        => '张三',
            'zx_start_time' => array(
                '2025-02-19',
                '2025-02-20'
            ),
        ),
        'compare'   => array(
            '=',
            array( '>','<' ),
        ),
        'format'    => array(
            '%s',
            array( '%s', '%s' ),
        )
    ),
    'f_name'
);
//print_r($col3);

$col4 = $dbsql->get_col(
    array(
        'meta'      => array(
            'a_name'        => '张三',
            'zx_start_time' => array(
                '2025-02-18',
                '2025-02-19'
            ),
        ),
        'compare'   => array(
            '=',
            array( '>','<' ),
        ),
        'format'    => array(
            '%s',
            array( '%s', '%s' ),
        )
    ),
    'f_name'
);
//print_r($col4);