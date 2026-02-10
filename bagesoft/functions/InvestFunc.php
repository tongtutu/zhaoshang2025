<?php
/**
 * 招商
 * @author        shuguang <5565907@qq.com>
 * @copyright     (c) 2007-2099 cookman.cn. All rights reserved.
 * @link          http://www.cookman.cn
 */

namespace bagesoft\functions;

use Yii;
use bagesoft\constant\System;
use bagesoft\constant\UserConst;
use bagesoft\models\Invest;
use bagesoft\models\InvestExt;
use bagesoft\models\InvestUserMap;
use bagesoft\models\InvestTransfer;

class InvestFunc
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
        return Invest::find()->where('id=:id AND uid=:uid', ['id' => $id, 'uid' => $uid])->limit(1)->one();
    }
    /**
     * 根据UID或管理者ID获取信息
     *
     * @param integer $id
     * @param integer $uid
     * @return object
     */
    public static function getItemByUidOrMgrId($id, $uid)
    {
        return Invest::find()->where('id=:id AND (uid=:uid OR manager_uid=:managerUid OR vice_manager_uid=:vicemanagerUid )', ['id' => $id, 'uid' => $uid, 'managerUid' => $uid , 'vicemanagerUid' => $uid])->limit(1)->one();
    }

    /**
     * 根据ID获取信息
     *
     * @param integer $id
     * @return object
     */
    public static function getItemById($id)
    {
        return Invest::find()->where('id=:id', ['id' => $id])->limit(1)->one();
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
        return InvestUserMap::find()->where('project_id=:project_id AND role_type=:role_type', ['project_id' => $projectId, 'role_type' => $roleType])->one();
    }

    /**
     * 设置招投标需求
     * @param $id
     * @param string $field 字段名
     * @param string $type set:设置招投标需求,unset:取消招投标需求
     */
    public static function setExtVal($id, $field = 'bt_request', $type = 'set')
    {
        $getExt = InvestExt::find()->where('project_id=:id', ['id' => $id])->limit(1)->one();
        if (false == $getExt) {
            $getExt = new InvestExt();
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
     * 删除关联关系
     * @param integer $projectId
     * @param integer $uid
     * @param integer $roleType
     * @return void
     */
    public static function mapDestory($projectId, $roleType)
    {
        InvestUserMap::deleteAll('project_id=:project_id AND role_type=:role_type', ['project_id' => $projectId, 'role_type' => $roleType]);
    }

    /**
     * 过户
     * @param object $project
     * @param object $newUser
     * @return void
     */
    public static function transfer($project, $newUser, $operator = [])
    {
        $olduser = [
            'uid' => $project->uid,
            'username' => $project->username
        ];
        
        if ($project->manager_uid == $newUser->id) {
            //如果新用户是当前项目经理，则删除原项目经理的管理关系
            self::mapDestory($project->id, System::MANAGER);
            $project->manager_uid = 0;
            $project->manager_name = '';
            $project->uid = $newUser->id;
            $project->username = $newUser->username;
        } else {
            //替换项目所有者、替换MAP所有者
            $owner = self::getMap($project->id, System::OWNER);
            if ($owner) {
                $owner->uid = $newUser->id;
                $owner->save();
                $project->uid = $newUser->id;
                $project->username = $newUser->username;
            }
        }
        // 使用update方法只更新必要的字段，提高性能
        $updateData = [
            'uid' => $newUser->id,
            'username' => $newUser->username,
        ];
        
        // 如果是转移给项目经理的情况，还需要更新manager相关字段
        if ($project->manager_uid == $newUser->id) {
            $updateData['manager_uid'] = 0;
            $updateData['manager_name'] = '';
        } 
            
        $result = $project->updateAttributes($updateData);
        
        // 如果过户成功，记录过户历史
        if ($result) {
            self::recordTransferHistory($project,$olduser, $newUser,$operator);
        }
    }
     
    /**
     * 获取过户记录
     * @param integer $projectId 项目ID
     * @return array
     */
    public static function getTransferList($projectId)
    {
        return InvestTransfer::find()
            ->where('project_id=:project_id', ['project_id' => $projectId])
            ->orderBy('id DESC')
            ->all();
    }
    /**
     * 记录过户历史
     * @param object $project 项目对象
     * @param object $newUser 新用户对象
     * @return void
     */
    private static function recordTransferHistory($project,$olduser=[], $newUser, $operator = [])
    {
        $transfer = new InvestTransfer();
        $transfer->project_id = $project->id;
        $transfer->operation_uid = $operator['uid'];
        $transfer->operation_name = $operator['username'];
        $transfer->source_uid = $olduser['uid']; // 原用户ID
        $transfer->source_name = $olduser['username']; // 原用户名
        $transfer->target_uid = $newUser->id;
        $transfer->target_name = $newUser->username;
        $transfer->time = time();
        $saveResult = $transfer->save();
        if ($saveResult) {
            Yii::info('过户记录保存成功'. ($saveResult ? '成功' : '失败'), 'invest.transfer');
        } else {
            $errors = $transfer->getErrors();
            $errorMsg = json_encode($errors, JSON_UNESCAPED_UNICODE);
            Yii::warning('过户记录保存失败: ' . $errorMsg . ' 数据: ' . json_encode($transfer->toArray(), JSON_UNESCAPED_UNICODE), 'invest.transfer');
            // 额外的调试信息
            if (empty($errors)) {
                Yii::warning('模型验证无错误但保存失败，可能原因：数据库连接问题、权限问题等', 'invest.transfer');
            }
        }
    }
}
