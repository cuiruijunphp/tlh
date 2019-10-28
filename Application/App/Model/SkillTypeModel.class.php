<?php
namespace App\Model;
use Think\Model;

class SkillTypeModel extends CommonModel{

	/*
	 * 通过parent_id 获取type数据
	 */
	public function get_type_list_by_parent_id($parent_id = 0){

		if($parent_id == 0){
			// 查询所有的
			$data_list = $this->get_list();
		}else{
			// 查询相应的
			$where['id'] = $parent_id;
			$where['parent_id'] = $parent_id;
			$where['_logic'] = 'or';
			$map['_complex'] = $where;

			$data_list = $this->get_list($map);
		}

		$return_data = [];
		foreach($data_list as $k => $v){
			if($v['parent_id']){
				$return_data['son'][$v['parent_id']][] = $v;
			}else{
				$return_data['parent'][] = $v;
			}
		}

		return $return_data;
	}
}