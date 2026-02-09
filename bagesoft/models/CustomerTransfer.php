<?php

namespace bagesoft\models;

use bagesoft\functions\TagsFunc;
use bagesoft\constant\System;
use bagesoft\functions\UploadFunc;
use bagesoft\models\CustomerUserMap;
/**
 * This is the model class for table "{{%customer}}".
 *
 * @property int $id 主键
 * @property int $project_id 项目ID
 * @property int $operation_uid 操作者ID
 * @property string $operation_name 操作者名称
 * @property int $source_uid 过户源用户UID
 * @property string $source_name 过户源用户名称
 * @property int $target_uid 过户目标用户UID
 * @property string $target_name 过户目标用户名称
 * @property int $time 过户时间
 */
class CustomerTransfer extends \bagesoft\common\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%customer_transfer}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['project_id', 'operation_uid', 'source_uid', 'target_uid', 'time'], 'integer'],
            [['operation_name', 'source_name', 'target_name'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
       
    }

    /**
     * 存前操作
     *
     * @param [type] $insert
     * @param [type] $changedAttributes
     * @return void
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
    }

    /**
     * 存前操作
     *
     * @param [type] $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
      
    }

    /**
     * 删前操作
     *
     * @param [type] $insert
     * @param [type] $changedAttributes
     */
    public function beforeDelete()
    {
        
    }
}
