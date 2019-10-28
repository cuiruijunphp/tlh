<?php
namespace Manage\Model;
use Think\Model;

class CommonModel extends Model{

	public function _before_insert(&$data, $options)
	{
		$data['update_time'] = time();
		$data['add_time'] = time();
	}

	public function _before_update(&$data, $options)
	{
		$data['update_time'] = time();
	}

	/*
	 * 获取一条记录
	 */
	public function get_one($where, $order = NULL){

		return $this->where($where)->order($order)->limit(1)->find();
	}

	/*
	 * 插入一条记录
	 */
	public function insert_one($data){
		return $this->add($data);
	}

	/*
	 * 更新
	 */
	public function update_data($where, $data){
		return $this->where($where)->save($data);
	}

	/*
	 * 获取多条记录
	 */
	public function get_list($where = null, $limit = null, $order = NULL){

		return $this->where($where)->order($order)->limit($limit)->select();
	}

	/*
	 * 删除数据
	 */
	public function del_data($where){
		return $this->where($where)->delete();
	}

	/*
	 * 统计数量
	 */
	public function get_count($where = null){
		return $this->where($where)->count();
	}

	/*
	 * 查询分页数据
	 */
	public function get_page_list($where = null, $page = 1, $page_size = 10){
		return $this->where($where)->limit($page_size)->page($page)->select();
	}

	/*
	 * 删除数据
	 */
	public function delete_data($where){
		return $this->where($where)->delete();
	}
}