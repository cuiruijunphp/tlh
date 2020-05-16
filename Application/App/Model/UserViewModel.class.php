<?php
namespace App\Model;
use Think\Model;

class UserViewModel extends CommonModel{

    /*
     * 查询用户的访客列表
     */
    public function get_view_user_info($user_id, $limit = 3, $page = 1){
        return $this->field('v.*,u.head_img,u.is_online,u.birthday,u.desc,u.sex,u.user_name,l.latitude,l.longitude')
            ->alias('v')
            ->where(['v.user_id' => $user_id])
            ->join('users u on v.view_user_id = u.id', 'left')
            ->join('user_location l on v.view_user_id = l.user_id', 'left')
            ->order('v.update_time desc')
            ->limit(($page - 1) * $limit, $limit)
            ->select();
    }
}