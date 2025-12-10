<?php
/**
 * 项目
 * @author        shuguang <5565907@qq.com>
 * @copyright     (c) 2007-2099 cookman.cn. All rights reserved.
 * @link          http://www.cookman.cn
 */

namespace bagesoft\functions;

use bagesoft\constant\UserConst;
use bagesoft\helpers\Utils;
use bagesoft\models\User;
use bagesoft\models\UserGroup;
use yii\helpers\ArrayHelper;

class UserFunc
{
    /**
     * uid获取用户
     *
     * @param integer $uid
     * @return object
     */
    public static function getUserById($uid)
    {
        return User::findOne($uid);
    }

    /**
     * username获取用户
     *
     * @param string $username
     * @return object
     */
    public static function getUserByUsername($username)
    {
        return User::find()->where("username=:username", ['username' => $username])->limit(1)->one();
    }

    /**
     * mobile获取用户
     *
     * @param string $mobile
     * @return object
     */
    public static function getUserByMobile($mobile)
    {
        return User::find()->where("mobile=:mobile", ['mobile' => $mobile])->limit(1)->one();
    }

    /**
     * 获取合作伙伴
     *
     * @param integer $currentUid 当前用户 UID
     * @return array
     */
    public static function getPartners($currentUid)
    {
        $model = User::find();
        $model->alias('user');
        $model->where('user.state=:state', ['state' => 1]);
        $model->leftJoin(UserGroup::tableName() . ' group', 'user.gid = group.id');
        $model->orderBy('user.gid');
        $users = $model->all();

        $datalist = [];
        $title = ['0' => '请选择'];
        $datalist = self::makeInfo($users, $currentUid);
        return $title + $datalist;
    }

    /**
     * 获取创作者
     *
     * @param integer $gid
     * @return array
     */
    public static function getWorkers()
    {
        $model = User::find();
        $model->alias('user');
        $model->where('user.state=:state', ['state' => UserConst::STATUS_ACTIVE]);
        $model->leftJoin(UserGroup::tableName() . ' group', 'user.gid = group.id');
        $model->orderBy('user.gid');
        $datalist = $model->all();
        return self::makeInfo($datalist);

    }

    /**
     * 获取招投标经理
     *
     * @param integer $gid
     * @return array
     */
    public static function getBTManagers($gid = UserConst::BT_MANAGER_GID)
    {
        $model = User::find();
        $model->alias('user');
        $model->where('user.state=:state AND user.gid=:gid', ['state' => UserConst::STATUS_ACTIVE, 'gid' => $gid]);
        $model->leftJoin(UserGroup::tableName() . ' group', 'user.gid = group.id');
        $model->orderBy('user.gid');
        $datalist = $model->all();
        return self::makeInfo($datalist);
    }

    /**
     * 获取项目经理
     *
     * @param integer $currentUid 当前用户 UID
     * @return array
     */
    public static function getManagers($currentUid)
    {
        $model = User::find();
        $model->alias('user');
        $model->where('user.state=:state AND user.gid=:gid', ['state' => 1, 'gid' => UserConst::MANAGER_GID]);
        $model->leftJoin(UserGroup::tableName() . ' group', 'user.gid = group.id');
        $model->orderBy('user.gid');
        $users = $model->all();
        $datalist = [];
        $title = ['0' => '请选择'];
        $datalist = self::makeInfo($users);
        return $title + $datalist;
    }
     /**
     * 获取招商总监
     *
     * @param integer $currentUid 当前用户 UID
     * @return array
     */
    public static function getViceManagers($currentUid)
    {
        $model = User::find();
        $model->alias('user');
        $model->where('user.state=:state AND user.gid=:gid', ['state' => 1, 'gid' => UserConst::VICE_MANAGER_GID]);
        $model->leftJoin(UserGroup::tableName() . ' group', 'user.gid = group.id');
        $model->orderBy('user.gid');
        $users = $model->all();
        $datalist = [];
        $title = ['0' => '请选择'];
        $datalist = self::makeInfo($users);
        return $title + $datalist;
    }
    /**
     * 是否需要选择项目经理
     *
     * @param integer $gid
     * @return boolean
     */
    public static function hasManager($gid)
    {
        if ($gid == UserConst::MANAGER_GID) {
            return 2;
        } elseif ($gid == UserConst::VICE_MANAGER_GID) {
            return 3;
        } else {
            return 1;
        }
    }

    /**
     * 获取合作伙伴UID
     * @param mixed $uid
     * @param object $project
     */
    public static function getPartnerUid($uid, $project)
    {
        if ($uid == $project->uid) {
            return $project->partner_uid;
        } else {
            return $project->uid;
        }
    }

    private static function makeInfo($datalist, $currentUid = 0)
    {
        $users = [];
        if (empty($datalist)) {
            return $users;
        }
        foreach ($datalist as $key => $row) {
            if ($currentUid > 0 && $row->id == $currentUid) {
                continue;
            }
            $users[$row->id] = '【' . $row->group->group_name . '】 ' . $row->username . ' ， ' . $row->mobile;
        }

        return $users;
    }

}
