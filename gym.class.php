<?php
// +----------------------------------------------------------------------
// | Constructed by Jokin [ Think & Do & To Be Better ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2018 Jokin All rights reserved.
// +----------------------------------------------------------------------
// | Author: Jokin <Jokin@twocola.com>
// +----------------------------------------------------------------------

/**
 * Ticketing System Class
 * @author  Jokin
 * @version 1.0.0
 */

class gym {

  /**
   * 记录场地生成所需时间
   */
  private $_FIELDGENERATETIME;

  /**
   * 场地数据
   */
  private $field = null;

  /**
   * 初始化函数
   * @param  int   chunk            区块数量
   * @param  int   min              每个区块最前排数量
   * @param  int   max              每个区块最后排数量
   * @param  int   delta            行间增量
   * @return array
   */
  public function __construct(int $chunk=4, int $min=50, int $max=100, int $delta=2) {
    // 生成场地数据
    $this->get_field($chunk, $min, $max, $delta);
  }

  /**
   * 预订函数
   * @param  int      getNum  订票数量
   * @param  int      maxNum  最大订票限制
   * @return mixed
   */
  public function booker(int $getNum, int $maxNum = 5) {
    if ($getNum > $maxNum || $getNum <= 0) {
      return false; // 每次票数限制
    }
    // 判断余票是否充足
    if ($this->rest_num($this->field) < $getNum) {
      return false;
    }
    $seat = array();
    for ($i=0; $i < $getNum; $i++) {
      $seat[] = $this->book($this->field);
    }
    $this->save($this->field);
    return $seat;
  }

  /**
   * 预订函数
   * @return string
   */
  public function book() : string {
    // 取chunk
    $chunk = $this->getRandChunkWithTickets();
    if ($chunk === false) {
      return false; // 无余票
    }
    $chunk = array_rand($chunk);
    // 取row
    $row = $this->getRandRowWithTickets($chunk);
    if ($row === false) {
      return false; // 无余票
    }
    $row = array_rand($row);
    // 取seat
    $seat = $this->getRandSeatWithTickets($chunk, $row);
    if ($seat === false) {
      return false; // 无余票
    }
    $seat = $seat[array_rand($seat)];
    // 处理数据
    $this->field[$chunk][$row][$seat] = 1; // 设置就坐
    $seat = $chunk.'-'.$row.'-'.$seat;
    return $seat;
  }
  /**
   * 剩票统计
   * @return int
   */
  public function rest_num() : int{
    $num = 0;
    for ($i=0; $i < count($this->field); $i++) {
      $num += $this->chunk_rest($i);
    }
    return $num;
  }

  /**
   * chunk余票统计
   * @param  int    chunk
   * @return int
   */
  public function chunk_rest(int $chunk = 0) : int {
    $num = 0;
    if (!isset($this->field[$chunk])) {
      return 0;
    }
    for ($j=0; $j < count($this->field[$chunk]); $j++) {
      $num += $this->row_rest($chunk, $j);
    }
    return $num;
  }

  /**
   * row余票统计
   * @param  int    chunk
   * @param  int    row
   * @return int
   */
   public function row_rest(int $chunk = 0, int $row = 0) : int {
     $num = 0;
     for ($k=0; $k < count($this->field[$chunk][$row]); $k++) {
       if ($this->field[$chunk][$row][$k] === 0) {
         $num ++;
       }
     }
     return $num;
   }

  /**
   * 取含有余票的chunk
   * @return mixed
   */
  public function getRandChunkWithTickets() {
    $chunk = array();
    for ($i=0; $i < count($this->field); $i++) {
      if (($num = $this->chunk_rest($i)) !== 0) {
        $chunk[$i] = $num;
      }
    }
    $chunk = (count($chunk) === 0) ? false : $chunk;
    return $chunk;
  }

  /**
   * 取含有余票的chunk
   * @param  int    chunk
   * @return mixed
   */
  public function getRandRowWithTickets(int $chunk) {
    $row = array();
    for ($i=0; $i < count($this->field[$chunk]); $i++) {
      if (($num = $this->row_rest($chunk, $i)) !== 0) {
        $row[$i] = $num;
      }
    }
    $row = (count($row) === 0) ? false : $row;
    return $row;
  }


  /**
   * 获取seat
   * @param  int    chunk
   * @param  int    row
   * @return mixed
   */
  public function getRandSeatWithTickets(int $chunk = 0, int $row = 0) {
    $seat = array();
    for ($i=0; $i < count($this->field[$chunk][$row]); $i++) {
      if ($this->field[$chunk][$row][$i] === 0) {
        $seat[] = $i;
      }
    }
    $seat = (count($seat) === 0) ? false : $seat;
    return $seat;
  }

  /**
   * 获取场地数据库
   * @param  int    chunk   区块数量
   * @param  int    min     每个区块最前排数量
   * @param  int    max     每个区块最后排数量
   * @param  int    delta   行间增量
   * @return void
   */
  public function get_field(int $chunk=4, int $min=50, int $max=100, int $delta=2) : void {
    // 尝试从文件读入
    if (!is_file('./field.db')) {
      $this->create_field($chunk, $min, $max, $delta);
    }else{
      $field = file_get_contents('./field.db');
      $this->field = json_decode($field, true);
    }
  }

  /**
   * 场地创建函数
   * @param  int   chunk 区块数量
   * @param  int   min   每个区块最前排数量
   * @param  int   max   每个区块最后排数量
   * @param  int   delta 行间增量
   * @return void
   */
  public function create_field(int $chunk, int $min, int $max, int $delta = 2) : void {
    $start_time = microtime(true);
    $this->field = array();
    for ($i=0; $i < $chunk; $i++) {
      for ($j=0; $j < ($max-$min)/$delta+1; $j++) {
        for ($k=0; $k < $min+$delta*$j; $k++) {
          $this->field[$i][$j][$k] = 0; // 空座
        }
      }
    }
    $end_time = microtime(true);
    $this->_FIELDGENERATETIME = $end_time - $start_time;
  }

  /**
   * 保存场地数组
   * @return void
   */
  public function save() : void {
    $field = json_encode($this->field);
    file_put_contents('./field.db', $field);
  }

}

?>
