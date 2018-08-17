<?php
// +----------------------------------------------------------------------
// | Constructed by Jokin [ Think & Do & To Be Better ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2018 Jokin All rights reserved.
// +----------------------------------------------------------------------
// | Author: Jokin <Jokin@twocola.com>
// +----------------------------------------------------------------------

/**
 * GYM class dirver
 * @author    Jokin
 * @version   1.0.0
 */

// 载入核心类
include './gym.class.php';

// 初始化类
$field = new gym();
$result = $field->booker(10, 10);
var_dump($result);
var_dump($field->rest_num($field));
?>
