<?php

namespace bagesoft\models;

use bagesoft\models\User;
use bagesoft\functions\TagsFunc;
use bagesoft\models\Demand;
use bagesoft\constant\System;
use bagesoft\functions\UploadFunc;
use bagesoft\models\Maintain;
use bagesoft\models\DemandWorks;
use bagesoft\models\DemandUserMap;
use bagesoft\models\InvestUserMap;
use bagesoft\models\MaintainUserMap;
/**
 * This is the model class for table "{{%invest}}".
 *
 * @property int $id 主键
 * @property int $uid 用户UID
 * @property string $username 用户名
 * @property int $partner_uid 伙伴UID(未启用)
 * @property string $partner_name 伙伴名称(未启用)
 * @property int $project_id 所属项目
 * @property string $project_name 项目名称
 * @property int $project_assess 考核项目
 * @property int $manager_uid 项目经理
 * @property string $manager_name 项目经理姓名
 * @property int $bt_manager_uid 招投标经理UID
 * @property string $bt_manager_name 招投标经理姓名
 * @property string $company_name 公司名称
 * @property string $usci_code 统一信用代码
 * @property string|null $tags 所属分类
 * @property int $channel_id 渠道
 * @property string $channel_name 渠道名称
 * @property string $contact_name 联系人
 * @property string $contact_phone 联系方式
 * @property string $province 省份
 * @property string|null $city 城市
 * @property string $area 区域
 * @property string $address 联系地址
 * @property string|null $content 项目介绍
 * @property int $steps 项目阶段
 * @property int $views 查看次数
 * @property int $is_demand 需求提交
 * @property string $attach_file 附件上传
 * @property int $deleted 删除状态
 * @property int $maintain_at 最后跟进
 * @property int $updated_at 更新时间
 * @property int $created_at 入库时间
 */
class Invest extends \bagesoft\common\models\Base
{
    public $hasManager;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%invest}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'partner_uid', 'project_id', 'project_assess', 'manager_uid', 'vice_manager_uid','bt_manager_uid', 'channel_id', 'steps', 'views', 'is_demand', 'deleted', 'maintain_at', 'updated_at', 'created_at'], 'integer'],
            [['project_name', 'company_name', 'usci_code', 'contact_name', 'contact_phone', 'province', 'address', 'attach_file'], 'required'],
            [['content'], 'string'],
            [['username', 'partner_name', 'manager_name','vice_manager_name', 'bt_manager_name', 'usci_code', 'province', 'city', 'area'], 'string', 'max' => 50],
            [['project_name', 'company_name'], 'string', 'max' => 100],
            [['channel_name', 'attach_file'], 'string', 'max' => 255],
            [['contact_name'], 'string', 'max' => 20],
            [['contact_phone'], 'string', 'max' => 11],
            [['address'], 'string', 'max' => 200],
            [
                'manager_uid',
                'required',
                'when' => function ($model) {
                    return $model->hasManager == 1;
                },
                'whenClient' => "function (attribute, value) {
                return $('#hasManager').val() == 1;
            }",
            ],
            [
                'manager_uid',
                'vice_manager_uid',
                'compare',
                'compareValue' => 1,
                'operator' => '>',
                'when' => function ($model) {
                    return $model->hasManager == 1;
                },
                'whenClient' => "function (attribute, value) {
                return $('#hasManager').val() == 1;
            }",
                'message' => '项目经理必须选择'
            ],
            [['tags'], 'required', 'message' => '项目标签必须选择', 'on' => ['create', 'update']],
            [['tags'], 'validateTags', 'on' => ['create', 'update']],
        ];
    }

    public function validateTags($attribute, $params)
    {
        if (empty($this->tags) || !is_array($this->tags) || count($this->tags) < 1) {
            $this->addError($attribute, '至少要选择1个项目标签');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '主键',
            'uid' => '用户UID',
            'username' => '用户名',
            'partner_uid' => '伙伴UID(未启用)',
            'partner_name' => '伙伴名称(未启用)',
            'project_id' => '所属项目',
            'project_name' => '项目名称',
            'project_assess' => '考核项目',
            'manager_uid' => '项目经理',
            'manager_name' => '项目经理姓名',
            'vice_manager_uid' => '招商总监',
            'vice_manager_name' => '招商总监姓名',
            'bt_manager_uid' => '招投标经理UID',
            'bt_manager_name' => '招投标经理姓名',
            'company_name' => '公司名称',
            'usci_code' => '统一信用代码',
            'tags' => '所属分类',
            'channel_id' => '渠道',
            'channel_name' => '渠道名称',
            'contact_name' => '联系人',
            'contact_phone' => '联系方式',
            'province' => '省份',
            'city' => '城市',
            'area' => '区域',
            'address' => '联系地址',
            'content' => '项目介绍',
            'steps' => '项目阶段',
            'views' => '查看次数',
            'is_demand' => '需求提交',
            'attach_file' => '附件上传',
            'deleted' => '删除状态',
            'maintain_at' => '最后跟进',
            'updated_at' => '更新时间',
            'created_at' => '入库时间',
        ];
    }
    /**
     * 删前操作
     *
     * @param [type] $insert
     * @param [type] $changedAttributes
     */
    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        //删除用户与项目的关联
        InvestUserMap::deleteAll('project_id=:projectId', ['projectId' => $this->id]);
        //删除需求
        $demands = Demand::find()->where(['project_id' => $this->id])->all();
        if ($demands) {
            foreach ($demands as $demand) {
                //作品删除
                DemandWorks::deleteAll('demand_id=:demandId', ['demandId' => $demand->id]);

                //用户映射删除
                DemandUserMap::deleteAll('demand_id=:demandId', ['demandId' => $demand->id]);
                $demand->delete();
            }
        }

        //删除项目维护
        $maintains = Maintain::find()->where(['project_id' => $this->id])->all();
        if ($maintains) {
            foreach ($maintains as $maintain) {
                //用户映射删除
                MaintainUserMap::deleteAll('maintain_id=:maintainId', ['maintainId' => $maintain->id]);
                $maintain->delete();
            }
        }
        return true;
    }


    /**
     * 存前操作
     *
     * @param [type] $insert
     * @param [type] $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert) {
            //发布者
            $source = new InvestUserMap();
            $source->uid = intval($this->uid);
            $source->project_id = $this->id;
            $source->role_type = System::OWNER;
            $source->save();

            //项目经理
            $target = new InvestUserMap();
            $target->uid = intval($this->manager_uid);
            $target->project_id = $this->id;
            $target->role_type = System::MANAGER;
            $target->save();
            
            //项目经理
            $target = new InvestUserMap();
            $target->uid = intval($this->vice_manager_uid);
            $target->project_id = $this->id;
            $target->role_type = System::MANAGER;
            $target->save();
        }
        //关联项目
        UploadFunc::relateProject($this->attach_file, $this->id);
    }

    /**
     * 存前操作
     *
     * @param [type] $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $user = User::findOne($this->manager_uid);
        if ($user) {
            $this->manager_name = $user->username;
        }
        $vice_user = User::findOne($this->vice_manager_uid);
        if ($vice_user) {
            $this->vice_manager_name = $vice_user->username;
        }
        $this->attach_file = str_replace(' ', '', $this->attach_file);
        if (in_array($this->scenario, ['create', 'update'])) {
            $this->tags = TagsFunc::format($this->tags);
        }
        return true;
    }

}
