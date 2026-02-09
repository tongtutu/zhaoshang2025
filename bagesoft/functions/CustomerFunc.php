<?php
/**
 * 市场
 * @author        shuguang <5565907@qq.com>
 * @copyright     (c) 2007-2099 cookman.cn. All rights reserved.
 * @link          http://www.cookman.cn
 */

namespace bagesoft\functions;

use bagesoft\constant\System;
use bagesoft\models\Customer;
use bagesoft\models\CustomerExt;
use bagesoft\models\CustomerUserMap;

class CustomerFunc
{
    /**
     * 根据ID和UID获取信息
     *
     * @param integer $id
     * @param integer $uid
     * @return object
     */
    public static function getItemByIdAndUid($id, $uid)
    {
        return Customer::find()->where('id=:id AND uid=:uid', ['id' => $id, 'uid' => $uid])->limit(1)->one();
    }

    /**
     * 根据ID和parterUid获取信息
     *
     * @param integer $id
     * @param integer $uid
     * @return object
     */
    public static function getItemByIdAndParterUid($id, $uid)
    {
        return Customer::find()->where('id=:id AND partner_uid=:parterUid', ['id' => $id, 'parterUid' => $uid])->limit(1)->one();
    }

    /**
     * 通过map查询customer
     *
     * @param integer $id
     * @param integer $uid
     * @return mixed
     */
    public static function getItemByUserMap($id, $uid)
    {
        return CustomerUserMap::find()->alias('userMap')->joinWith('customer AS customer')->select('customer.*,userMap.uid,userMap.project_id')->where('customer.id=:id AND userMap.uid=:uid', ['id' => $id, 'uid' => $uid])->limit(1)->one();
    }

    /**
     * 根据ID获取信息
     *
     * @param integer $id
     * @return object
     */
    public static function getItemById($id)
    {
        return Customer::find()->where('id=:id', ['id' => $id])->limit(1)->one();
    }

    /**
     * 设置招投标需求
     * @param $id
     * @param string $field 字段名
     * @param string $type set:设置招投标需求,unset:取消招投标需求
     */
    public static function setExtVal($id, $field = 'bt_request', $type = 'set')
    {
        $getExt = CustomerExt::find()->where('project_id=:id', ['id' => $id])->limit(1)->one();
        if (false == $getExt) {
            $getExt = new CustomerExt();
            $getExt->project_id = $id;
        }
        switch ($type) {
            case 'set':
                $value = System::YES;
                break;
            default:
                $value = System::NO;
                break;
        }
        $getExt->$field = $value;
        $getExt->save();
    }

    /**
     *
     * 添加关联记录
     *
     * @param mixed $projectId
     * @param mixed $uid
     * @param mixed $roleType
     * @return void
     */
    public static function addUserMap($projectId, $uid, $roleType = System::BT_MANAGER)
    {
        $map = new CustomerUserMap();
        $map->project_id = $projectId;
        $map->uid = $uid;
        $map->role_type = $roleType;
        $map->save();
    }

    /**
     *
     * 更新关联记录
     *
     * @param mixed $projectId
     * @param mixed $uid
     * @param mixed $roleType
     * @return void
     */
    public static function updateUserMap($projectId, $uid, $roleType = System::BT_MANAGER)
    {
        $map = CustomerUserMap::find()->where('project_id=:project_id AND role_type=:role_type', ['project_id' => $projectId, 'role_type' => $roleType])->one();
        if ($map) {
            $map->uid = $uid;
            $map->save();
        }
    }

    /**
     * 获取MAP信息
     * 
     * @param integer $projectId
     * @param integer $uid
     * @param integer $roleType
     * @return array|\yii\db\ActiveRecord|null
     */
    public static function getMap($projectId, $roleType)
    {
        return CustomerUserMap::find()->where('project_id=:project_id AND role_type=:role_type', ['project_id' => $projectId, 'role_type' => $roleType])->one();
    }

    /**
     * 根据项目ID和用户ID获取招投标经理信息
     *
     * @param mixed $projectId 项目ID
     * @param mixed $uid 用户ID
     * @return object|null 返回符合条件的 CustomerUserMap 模型实例，如果未找到则返回 null
     */
    public static function getBtManager($projectId, $uid)
    {
        return CustomerUserMap::find()->where('uid=:uid AND project_id=:project_id AND role_type=:role_type', ['project_id' => $projectId, 'role_type' => System::BT_MANAGER, 'uid' => $uid])->one();
    }

    /**
     * 过户
     * @param object $project
     * @param object $newUser
     * @return void
     */
    public static function transfer($project, $newUser)
    {
        if ($project->uid == $newUser->id) {
            throw new \Exception('不能过户给自己');
        } elseif ($project->partner_uid == $newUser->id) {
            throw new \Exception('当前用户是该信息的合作伙伴，过户冲突');
        }
        $ower = self::getMap($project->id, System::OWNER);
        if ($ower) {
            $ower->uid = $newUser->id;
            $ower->save();
        }
        $updateData = [
            'uid' => $newUser->id,
            'username' => $newUser->username,
        ];
        $result = $project->updateAttributes($updateData);
    }
}
