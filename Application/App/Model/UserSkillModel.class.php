<?php
namespace App\Model;
use Think\Model;

class UserSkillModel extends CommonModel{

	/*
	 * 根据类型查询发布/需求,距离用php循环来算
	 */
	public function get_skill_demand_by_type_id($type_id, $lat, $long, $offset = 0, $page_size=10){

		$sql = '(select skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,img from user_skill s  left join users u on s.user_id = u.id where s.type_id='.$type_id . ')';

		$sql .= ' union ';

		$sql .= '(select title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img from user_demand as d left join users u on d.user_id = u.id  where status=1  and end_time > '.time().' and type_id = ' . $type_id . ')';

		$sql .= ' limit ' . $offset . ',' . $page_size;

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

		$sql .= '((select skill_name as title,user_id,is_online,head_img,user_name,type_id,s.add_time,longitude,latitude,img from user_skill s  left join users u on s.user_id = u.id)';

		$sql .= ' union ';

		$sql .= '(select title,user_id,is_online,head_img,user_name,type_id,d.add_time,longitude,latitude,null as img from user_demand as d left join users u on d.user_id = u.id  where status=1  and end_time > '.time(). ')) as tmp';

		$sql .= ' order by distance limit ' . $offset . ',' . $page_size;

		return $this->query($sql);
	}
}