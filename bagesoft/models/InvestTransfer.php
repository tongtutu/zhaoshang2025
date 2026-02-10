<?php

namespace bagesoft\models;

/**
 * This is the model class for table "{{%invest_transfer}}".
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
class InvestTransfer extends \bagesoft\common\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%invest_transfer}}';
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
        return [
            'id' => '主键',
            'project_id' => '项目id',
            'operation_uid' => '操作用户id',
            'operation_name' => '操作用户名称',
            'source_uid' => '过户源用户UID',
            'source_name' => '过户源名称',
            'target_uid' => '过户目标用户UID',
            'target_name' => '过户目标用户名称',
            'time' => '过户时间',
        ];
    }

    /**
     * 存前操作
     *
     * @param [type] $insert
     * @param [type] $changedAttributes
     * @return void
     */

    /**
     * 存前操作
     *
     * @param [type] $insert
     * @return bool
     */


    /**
     * 删前操作
     *
     * @param [type] $insert
     * @param [type] $changedAttributes
     */
   
}
