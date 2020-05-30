<?php
namespace App\Model;
use Think\Model;

class UserSkillModel extends CommonModel{

	/*
	 * 根据类型查询发布/需求,距离用php循环来算
	 */
	public function get_skill_demand_by_type_id($uid, $type_id, $offset = 0, $page_size=10){

		if(count(explode(',', $type_id)) > 1){
			$type_id = trim($type_id, ',');
			$type_id_where = ' in (' . $type_id . ')';
		}else{
			$type_id_where = ' = ' . $type_id;
		}

		$sql = '(select s.id,skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,img,"skill" as type from user_skill s left join users u on s.user_id = u.id where s.user_id != '. $uid .' and s.status =1 and s.type_id '. $type_id_where . ')';

		$sql .= ' union ';

		$sql .= '(select d.id,title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img,"demand" as type from user_demand as d left join users u on d.user_id = u.id  where d.user_id != ' . $uid . ' and status=1  and end_time > '.time().' and type_id ' . $type_id_where . ')';

		$sql .= ' order by add_time desc limit ' . $offset . ',' . $page_size;

		return $this->query($sql);
	}


	/*
	 * 根据距离远近排序算
	 */
	public function get_skill_demand_order_by_distance($uid, $lat, $lng, $offset = 0, $page_size=10){

		$sql = 'select tmp.*, 6378.138 * 2 * ASIN(SQRT(POW(SIN(
            (
              '.$lat.' * PI() / 180 - latitude * PI() / 180
            ) / 2
          ), 2
        ) + COS('.$lat.' * PI() / 180) * COS(latitude * PI() / 180) * POW(
          SIN(
            (
              '.$lng.' * PI() / 180 - longitude * PI() / 180
            ) / 2
          ), 2
        )
      )
    ) *1000 as distance from ';

		$sql .= '((select s.id,skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,img,"skill" as type from user_skill s left join users u on s.user_id = u.id where s.user_id != ' . $uid . ' and s.status =1)';

		$sql .= ' union ';

		$sql .= '(select d.id,title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img,"demand" as type from user_demand as d left join users u on d.user_id = u.id  where d.user_id != ' . $uid . ' and  status=1  and end_time > '.time(). ')) as tmp';

		$sql .= ' order by distance limit ' . $offset . ',' . $page_size;

		return $this->query($sql);
	}

	/*
	 * 人气最高
	 */
	public function get_skill_demand_by_view($uid, $offset = 0, $page_size=10){

		$sql = '(select s.id,skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,img,"skill" as type, reservation_count as r_count from user_skill s left join users u on s.user_id = u.id where s.user_id != ' . $uid . ' and s.status =1)';

		$sql .= ' union ';

		$sql .= '(select d.id,title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img,"demand" as type,(LENGTH(`applicants`) - LENGTH(REPLACE(`applicants`,",", "")))  as r_count from user_demand as d left join users u on d.user_id = u.id  where d.user_id != ' . $uid . ' and status=1  and end_time > '.time().')';

		$sql .= 'order by r_count desc limit ' . $offset . ',' . $page_size;

		return $this->query($sql);
	}

	/*
	 * 查询发布/需求,距离用php循环来算
	 */
	public function get_skill_demand_all($uid, $offset = 0, $page_size=10){

		$sql = '(select s.id,skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,img,"skill" as type from user_skill s left join users u on s.user_id = u.id where s.user_id != ' . $uid . ' and s.status =1)';

		$sql .= ' union ';

		$sql .= '(select d.id,title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img,"demand" as type from user_demand as d left join users u on d.user_id = u.id  where d.user_id != ' . $uid . ' and status=1  and end_time > ' . time(). ')';

		$sql .= ' order by add_time desc limit ' . $offset . ',' . $page_size;

		return $this->query($sql);
	}

	/*
	 * 首页按技能获取结果
	 */
	public function get_skill_list_home($uid, $offset = 0, $page_size=10){

		$sql = '(select s.id,skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,img,"skill" as type, reservation_count as r_count from user_skill s left join users u on s.user_id = u.id where s.user_id != ' . $uid . ' and s.status =1)';

		$sql .= ' order by add_time desc limit ' . $offset . ',' . $page_size;

		return $this->query($sql);
	}

	/*
	 * 首页按需求获取结果
	 */
	public function get_demand_list_home($uid, $offset = 0, $page_size=10){

		$sql = '(select d.id,title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img,"demand" as type from user_demand as d left join users u on d.user_id = u.id  where d.user_id != ' . $uid . ' and status=1  and end_time > ' . time(). ')';

		$sql .= ' order by add_time desc limit ' . $offset . ',' . $page_size;

		return $this->query($sql);
	}

	/*
	 * 获取列表
	 */
	public function get_skill_list($where, $page = 1, $page_size = 3){

		return $this->field('s.*, t.type_name')
			->alias('s')
			->join('skill_type as t on s.type_id = t.id', 'left')
			->join('skill_reserve as v on s.id = v.skill_id', 'left')
			->where($where)
			->order('v.update_time desc, s.add_time desc')
			->page($page, $page_size)
			->select();
	}

	/*
	 * 获取今天/查询条件下发布的条数
	 */
	public function get_pulish_count($where){
		return $this->where($where)->count();
	}

	/*
	 * 根据关键词查询需求标题/技能名称,距离用php循环来算
	 */
	public function get_skill_demand_by_keyword($uid, $keyword, $offset = 0, $page_size=10){

		$sql = '(select s.id,skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,img,"skill" as type from user_skill s left join users u on s.user_id = u.id where s.user_id != ' . $uid . ' and s.status =1 and s.skill_name like "%'. $keyword . '%")';

		$sql .= ' union ';

		$sql .= '(select d.id,title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img,"demand" as type from user_demand as d left join users u on d.user_id = u.id  where d.user_id != ' . $uid . ' and status=1  and end_time > '.time().' and d.title like "%' . $keyword . '%")';

		$sql .= ' order by add_time desc limit ' . $offset . ',' . $page_size;

		return $this->query($sql);
	}

	/*
	 * 首页列表公共方法抽取
	 * $uid 当前登录的uid
	 * $type_id 技能类型id,逗号分隔的
	 * $lat/$lng 当前登录用户的经纬度
	 * $keyword 查询关键词
	 * $is_hot 是否按热度排序
	 * $is_distance 按距离排序
	 * $is_demand 只取需求
	 * $is_skill 只取技能
	 * $free_type 1-付费,2-农林
	 * $offset 偏移量
	 * $page_size 每页多少条
	 */
	public function get_common_list($uid, $type_id, $lat, $lng, $is_distance, $is_hot, $is_demand, $is_skill, $keyword, $free_type, $offset = 0, $page_size=10){

		$s_where = ' and s.user_id != ' . $uid;
		$d_where = ' and d.user_id != ' . $uid;
		// 如果有type_id
		if($type_id){
			if(count(explode(',', $type_id)) > 1){
				$type_id = trim($type_id, ',');
				$s_where .= ' and s.type_id in (' . $type_id . ')';
				$d_where .= ' and d.type_id in (' . $type_id . ')';
			}else{
				$s_where .= ' and s.type_id = ' . $type_id;
				$d_where .= ' and d.type_id = ' . $type_id;
			}
		}

		//关键词
		if($keyword){
			$s_where .= ' and s.skill_name like "%'. $keyword . '%"';
			$d_where .= ' and d.title  like "%'. $keyword . '%"';
		}

		// 农林模块或者付费模块
		if($free_type){
			$s_where .= ' and p.free_type ='. $free_type;
			$d_where .= ' and p.free_type ='. $free_type;
		}

		// 公用的查询字段和排序方式
		//技能表获取的字段
		$s_field = ' s.id,skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,s.img,"skill" as type';

		$d_field = ' d.id,title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img,"demand" as type';

		$order = ' add_time desc';

		// 如果按热度排序
		if($is_hot){
			// 技能表获取的字段,多了个访问总数字段
			$s_field = ' s.id,skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,s.img,"skill" as type, reservation_count as r_count';

			// 需求表字段,多了个访问总数字段
			$d_field = ' d.id,title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img,"demand" as type,(LENGTH(`applicants`) - LENGTH(REPLACE(`applicants`,",", "")))  as r_count';

			$order = ' r_count desc';
		}

		// 按距离排序
		if($is_distance){

			// 如果是按照距离排序,则需要用mysql算距离,要拼接取字段的sql
			$sql_view_field = 'select tmp.*, 6378.138 * 2 * ASIN(SQRT(POW(SIN(
            (
              '.$lat.' * PI() / 180 - latitude * PI() / 180
            ) / 2
          ), 2
        ) + COS('.$lat.' * PI() / 180) * COS(latitude * PI() / 180) * POW(
          SIN(
            (
              '.$lng.' * PI() / 180 - longitude * PI() / 180
            ) / 2
          ), 2
        )
      )
    ) *1000 as distance from ';

			$order = ' distance asc';
		}

		// 将sql 分开,便于后面拼接
		$sql_skill = '(select ' . $s_field . ' from user_skill s left join users u on s.user_id = u.id left join skill_type as p on s.type_id = p.id where s.status =1 ' . $s_where . ')';

		$sql_union = ' union ';

		$sql_demand = '(select ' . $d_field . ' from user_demand as d left join users u on d.user_id = u.id left join skill_type as p on d.type_id = p.id  where status=1 ' . $d_where . ' and end_time > ' . time(). ')';

		$sql_order = ' order by ' . $order . ' limit ' . $offset . ',' . $page_size;


		if($is_demand){
			//单独获取需求
			$sql = $sql_demand . $sql_order;
		}elseif ($is_skill){
			//单独获取技能列表
			$sql = $sql_skill . $sql_order;
		}elseif($is_distance){
			// 按照距离排序
			$sql = $sql_view_field . '(' . $sql_skill . $sql_union . $sql_demand . ') as tmp ' . $sql_order;
		}else{
			//通用
			$sql = $sql_skill . $sql_union . $sql_demand . $sql_order;
		}

		return $this->query($sql);
	}
}