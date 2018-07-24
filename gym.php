<?php
// +----------------------------------------------------------------------
// | Constructed by Jokin [ Think & Do & To Be Better ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2018 Jokin All rights reserved.
// +----------------------------------------------------------------------
// | Author: Jokin <Jokin@twocola.com>
// +----------------------------------------------------------------------

// 参数定义
define('STARTTIME', microtime(true));
// 获取场地数据库
get_field($field);
// 订票
$seat = booker($field, 5, 5);
if ($seat === false){
  echo '订票失败：余票不足或超出订票最大数量限制';
}else{
  var_dump($seat);
  var_dump(rest_num($field));
  define('ENDTIME'  , microtime(true));
  echo '共订票' . count($seat) . '张，耗时' . round(ENDTIME-STARTTIME, 5) .'秒';
}

/**
 * 预订函数
 * @param  array    field   场地数组
 * @param  int      getNum  订票数量
 * @param  int      maxNum  最大订票限制
 * @return mixed
 */
function booker(array &$field, int $getNum, int $maxNum = 5) {
  if ($getNum > $maxNum || $getNum <= 0) {
    return false; // 每次票数限制
  }
  // 判断余票是否充足
  if (rest_num($field) < $getNum) {
    return false;
  }
  $seat = array();
  for ($i=0; $i < $getNum; $i++) {
    $seat[] = book($field);
  }
  save($field);
  return $seat;
}

/**
 * 预订函数
 * @param  array    field
 * @return string
 */
function book(array &$field) : string {
  // 取chunk
  $chunk = getRandChunkWithTickets($field);
  if ($chunk === false) {
    return false; // 无余票
  }
  $chunk = array_rand($chunk);
  // 取row
  $row = getRandRowWithTickets($field, $chunk);
  if ($row === false) {
    return false; // 无余票
  }
  $row = array_rand($row);
  // 取seat
  $seat = getRandSeatWithTickets($field, $chunk, $row);
  if ($seat === false) {
    return false; // 无余票
  }
  $seat = $seat[array_rand($seat)];
  // 处理数据
  $field[$chunk][$row][$seat] = 1; // 设置就坐
  $seat = $chunk.'-'.$row.'-'.$seat;
  return $seat;
}
/**
 * 剩票统计
 * @param  array  field
 * @return int
 */
function rest_num(array $field) : int{
  $num = 0;
  for ($i=0; $i < count($field); $i++) {
    $num += chunk_rest($field, $i);
  }
  return $num;
}

/**
 * chunk余票统计
 * @param  array  field
 * @param  int    chunk
 * @return int
 */
function chunk_rest(array $field, int $chunk = 0) : int {
  $num = 0;
  if (!isset($field[$chunk])) {
    return 0;
  }
  for ($j=0; $j < count($field[$chunk]); $j++) {
    $num += row_rest($field, $chunk, $j);
  }
  return $num;
}

/**
 * row余票统计
 * @param  array  field
 * @param  int    chunk
 * @param  int    row
 * @return int
 */
 function row_rest(array $field, int $chunk = 0, int $row = 0) : int {
   $num = 0;
   for ($k=0; $k < count($field[$chunk][$row]); $k++) {
     if ($field[$chunk][$row][$k] === 0) {
       $num ++;
     }
   }
   return $num;
 }

/**
 * 取含有余票的chunk
 * @param  array  field
 * @return mixed
 */
function getRandChunkWithTickets(array $field) {
  $chunk = array();
  for ($i=0; $i < count($field); $i++) {
    if (($num = chunk_rest($field, $i)) !== 0) {
      $chunk[$i] = $num;
    }
  }
  $chunk = (count($chunk) === 0) ? false : $chunk;
  return $chunk;
}

/**
 * 取含有余票的chunk
 * @param  array  field
 * @param  int    chunk
 * @return mixed
 */
function getRandRowWithTickets(array $field, int $chunk) {
  $row = array();
  for ($i=0; $i < count($field[$chunk]); $i++) {
    if (($num = row_rest($field, $chunk, $i)) !== 0) {
      $row[$i] = $num;
    }
  }
  $row = (count($row) === 0) ? false : $row;
  return $row;
}


/**
 * 获取seat
 * @param  array  field
 * @param  int    chunk
 * @param  int    row
 * @return mixed
 */
function getRandSeatWithTickets(array $field, int $chunk = 0, int $row = 0) {
  $seat = array();
  for ($i=0; $i < count($field[$chunk][$row]); $i++) {
    if ($field[$chunk][$row][$i] === 0) {
      $seat[] = $i;
    }
  }
  $seat = (count($seat) === 0) ? false : $seat;
  return $seat;
}

/**
 * 获取场地数据库
 * @param  array  field
 * @return void
 */
function get_field(&$field) : void {
  if (!is_file('./field.db')) {
    create_field(4, 50, 100, $field, 2);
  }else{
    $field = file_get_contents('./field.db');
    $field = json_decode($field, true);
  }
}

/**
 * 场地创建函数
 * @param  int   chunk 区块数量
 * @param  int   min   每个区块最前排数量
 * @param  int   max   每个区块最后排数量
 * @param  array field 场地数组
 * @param  int   delta 行间增量
 * @return void
 */
function create_field(int $chunk, int $min, int $max, &$field, int $delta = 2) : void {
  $field = array();
  for ($i=0; $i < $chunk; $i++) {
    for ($j=0; $j < ($max-$min)/$delta+1; $j++) {
      for ($k=0; $k < $min+$delta*$j; $k++) {
        $field[$i][$j][$k] = 0; // 空座
      }
    }
  }
  $cache = array();
  for ($i=0; $i < $chunk; $i++) {
    for ($j=0; $j < ($max-$min)/$delta+1; $j++) {
      $cache[$i][$j][$k] = $min+$delta*$j; // 空座数量
    }
  }
}

/**
 * 保存场地数组
 * @param  array  field
 * @return void
 */
function save(array $field) : void {
  $field = json_encode($field);
  file_put_contents('./field.db', $field);
}
?>
