<?php
namespace App\Model;
use Think\Model;

class UserSkillModel extends CommonModel{

	/*
	 * 根据类型查询发布/需求,距离用php循环来算
	 */
	public function get_skill_demand_by_type_id($type_id, $offset = 0, $page_size=10){

		if(count(explode(',', $type_id)) > 1){
			$type_id_where = ' in (' . $type_id . ')';
		}else{
			$type_id_where = ' = ' . $type_id;
		}

		$sql = '(select s.id,skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,img,"skill" as type from user_skill s left join users u on s.user_id = u.id where s.status =1 and s.type_id '. $type_id_where . ')';

		$sql .= ' union ';

		$sql .= '(select d.id,title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img,"demand" as type from user_demand as d left join users u on d.user_id = u.id  where status=1  and end_time > '.time().' and type_id ' . $type_id_where . ')';

		$sql .= ' order by add_time desc limit ' . $offset . ',' . $page_size;

		return $this->query($sql);
	}


	/*
	 * 根据距离远近排序算
	 */
	public function get_skill_demand_order_by_distance($lat, $lng, $offset = 0, $page_size=10){

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

		$sql .= '((select s.id,skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,img,"skill" as type from user_skill s left join users u on s.user_id = u.id where s.status =1)';

		$sql .= ' union ';

		$sql .= '(select d.id,title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img,"demand" as type from user_demand as d left join users u on d.user_id = u.id  where status=1  and end_time > '.time(). ')) as tmp';

		$sql .= ' order by distance limit ' . $offset . ',' . $page_size;

		return $this->query($sql);
	}

	/*
	 * 人气最高
	 */
	public function get_skill_demand_by_view($offset = 0, $page_size=10){

		$sql = '(select s.id,skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,img,"skill" as type, reservation_count as r_count from user_skill s left join users u on s.user_id = u.id where s.status =1)';

		$sql .= ' union ';

		$sql .= '(select d.id,title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img,"demand" as type,(LENGTH(`applicants`) - LENGTH(REPLACE(`applicants`,",", "")))  as r_count from user_demand as d left join users u on d.user_id = u.id  where status=1  and end_time > '.time().')';

		$sql .= 'order by r_count desc limit ' . $offset . ',' . $page_size;

		return $this->query($sql);
	}

	/*
	 * 查询发布/需求,距离用php循环来算
	 */
	public function get_skill_demand_all($offset = 0, $page_size=10){

		$sql = '(select s.id,skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,img,"skill" as type from user_skill s left join users u on s.user_id = u.id where s.status =1)';

		$sql .= ' union ';

		$sql .= '(select d.id,title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img,"demand" as type from user_demand as d left join users u on d.user_id = u.id  where status=1  and end_time > ' . time(). ')';

		$sql .= ' order by add_time desc limit ' . $offset . ',' . $page_size;

		return $this->query($sql);
	}

	/*
	 * 首页按技能获取结果
	 */
	public function get_skill_list_home($offset = 0, $page_size=10){

		$sql = '(select s.id,skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,img,"skill" as type, reservation_count as r_count from user_skill s left join users u on s.user_id = u.id where s.status =1)';

		$sql .= 'order by add_time desc limit ' . $offset . ',' . $page_size;

		return $this->query($sql);
	}

	/*
	 * 首页按需求获取结果
	 */
	public function get_demand_list_home($offset = 0, $page_size=10){

		$sql = '(select d.id,title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img,"demand" as type from user_demand as d left join users u on d.user_id = u.id  where status=1  and end_time > ' . time(). ')';

		$sql .= 'order by add_time desc limit ' . $offset . ',' . $page_size;

		return $this->query($sql);
	}

	/*
	 * 获取列表
	 */
	public function get_skill_list($where, $page = 1, $page_size = 3){

		return $this->field('s.*, t.type_name')
			->alias('s')
			->join('skill_type as t on s.type_id = t.id', 'left')
			->where($where)
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
	public function get_skill_demand_by_keyword($keyword, $offset = 0, $page_size=10){

		$sql = '(select s.id,skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,img,"skill" as type from user_skill s left join users u on s.user_id = u.id where s.status =1 and s.skill_name like "%'. $keyword . '%")';

		$sql .= ' union ';

		$sql .= '(select d.id,title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img,"demand" as type from user_demand as d left join users u on d.user_id = u.id  where status=1  and end_time > '.time().' and d.title like "%' . $keyword . '%")';

		$sql .= ' order by add_time desc limit ' . $offset . ',' . $page_size;

		return $this->query($sql);
	}
}