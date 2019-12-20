<?php
namespace Manage\Model;
use Think\Model;

class SkillTypeModel extends CommonModel{

	/*
	 * 获取类型列表
	 */
	public function get_skill_type_list($page = 1, $page_size = 10){
		return $this->field('t.*, p.type_name as parent_name')
			->alias('t')
			->join('skill_type as p on t.parent_id=p.id', 'left')
			->page($page, $page_size)
			->order('t.add_time asc')
			->select();
	}
}