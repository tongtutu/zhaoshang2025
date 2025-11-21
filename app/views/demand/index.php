<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use bagesoft\constant\System;
use bagesoft\constant\ProjectConst;
use kartik\daterange\DateRangePicker;

$this->title = '需求管理';
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['index']];
$this->params['breadcrumbs'][] = '列表';
$request = Yii::$app->request;
$defaultStartDate = date('Y-m-d', strtotime('-7 days'));
$defaultEndDate = date('Y-m-d');
?>

<div class="row">
    <div class="col-lg-12">
        <div class="collapse show" id="searchCollapse">
            <div class="card">
                <div class="card-body">
                    <?php echo Html::beginForm(['index'], 'get', ['class' => '']); ?>
                    <div class="row">
                        <div class="col-lg-3 col-md-3 col-sm-4">
                            <div class="input-group mb-3 input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">项目名称</span>
                                </div>
                                <?php echo Html::textInput('projectName', $request->get('projectName'), ['class' => 'form-control form-control-sm', 'placeholder' => '', 'id' => 'projectName']); ?>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-4">
                            <div class="input-group mb-3 input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">提交人</span>
                                </div>
                                <?php echo Html::textInput('username', $request->get('username'), ['class' => 'form-control form-control-sm', 'placeholder' => '', 'id' => 'username']); ?>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-4">
                            <div class="input-group mb-3 input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">创作者</span>
                                </div>
                                <?php echo Html::textInput('workername', $request->get('workername'), ['class' => 'form-control form-control-sm', 'placeholder' => '', 'id' => 'workername']); ?>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-4  col-xs-4">
                            <div class="input-group mb-3 input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">项目所在地</span>
                                </div>
                                <?php echo Html::textInput('location', $request->get('location'), ['class' => 'form-control form-control-sm ', 'placeholder' => '', 'id' => 'location']); ?>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-2 col-sm-4">
                            <div class="input-group mb-3 input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">提交时间</span>
                                </div> <?php
                                echo DateRangePicker::widget([
                                    'name' => 'date',
                                    'convertFormat' => true,
                                    'value' => $request->get('date'),
                                    'pluginOptions' => [
                                        'timePicker' => false,
                                        'timePickerIncrement' => 15,
                                        'locale' => [
                                            'format' => 'Y-m-d'
                                        ],
                                        'maxDate' => date('Y-m-d', strtotime('+1 days')),
                                        'autoApply' => true,          // 选择日期后自动应用
                                        'autoUpdateInput' => true,    // 自动更新输入框的值
                                
                                    ],
                                    'options' => [
                                        'class' => 'form-control form-control-sm custom-select',
                                        'readonly' => true,
                                    ]
                                ]); ?>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-2 col-sm-4 col-xs-4">
                            <div class="input-group mb-3 input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">状态</span>
                                </div>
                                <?php echo Html::dropDownList('state', $request->get('state'), ProjectConst::DEMAND_WORKS_UPLOAD, ['class' => 'form-control  form-control-sm custom-select', 'prompt' => '-']); ?>
                            </div>
                        </div>


                        <div class="col-lg-3 col-md-3 col-sm-4   col-xs-4">
                            <?php echo Html::hiddenInput('opt', 'search'); ?>
                            <button type="submit" class="btn btn-default btn-sm"><i class="fa fa-search"></i>搜索</button>
                            <a href="<?php echo Url::to(['index']); ?>" class="btn btn-xs btn-link"> <i
                                    class="fa fa-undo"></i>取消</a>
                        </div>
                    </div>
                    <?php echo Html::endForm(); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="card ">
            <?php if ($count > 0): ?>
                <div class="card-header">
                    <div class="card-title"><a class="btn btn-default" href="<?php echo Url::to(array_merge(['export-download'], Yii::$app->request->get())); ?>">
                            <i class="fa fa-file-o"></i> 导出 </a></div>
                    <div class="card-tools">
                        共 <kbd> <?php echo $count; ?></kbd> 条记录
                    </div>
                </div>
            <?php endif; ?>
            <div class="card-body p-0">
                <div class="row">

                    <div class="col-md-12">
                        <form id="list-form" name="list-form" method="POST">
                            <div class="table-responsive mailbox-messages"
                                style="overflow-x: auto; min-width: 1px;  min-height:20vh">
                                <table class="table table-hover" style="width: max-content; min-width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>项目名称</th>
                                            <th style="min-width: 80px">提交人</th>
                                            <th style="min-width: 80px">审批人</th>
                                            <th>项目所在地</th>
                                            <th>期望交付时间</th>
                                            <th style="min-width: 80px">创作者</th>
                                            <th style="min-width: 80px">状态</th>
                                            <th style="min-width: 80px">提交时间</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($datalist): ?>
                                            <?php foreach ($datalist as $key => $row): ?>
                                                <tr>
                                                    <td>
                                                        <?php echo $row['id']; ?>
                                                    </td>
                                                    <td>
                                                        <kbd><?php echo System::SOURCE[$row['source']]; ?></kbd>
                                                        <a href="<?php echo Url::to(['item', 'id' => $row['id']]); ?>">
                                                            <?php echo Html::encode($row['project_name']); ?></a>
                                                    </td>
                                                    <td>
                                                        <?php echo Html::encode($row['username']); ?>
                                                    </td>
                                                    <td>
                                                        <?php echo Html::encode($row['operator_name']); ?>
                                                    </td>
                                                    <td>
                                                        <?php echo Html::encode($row['project_location']); ?>
                                                    </td>
                                                    <td><?php echo $row['hope_time']; ?></td>
                                                    <td><?php if ($row['task_worker_uid']): ?><?php echo Html::encode($row['task_worker_name']); ?><?php else: ?>-<?php endif; ?>
                                                    </td>
                                                    <td><?php if ($row['deleted'] <= System::DELETE_LEVEL_1): ?><small><?php if ($row['task_produce_num'] > 0 && $row['task_produce_num'] <= ProjectConst::DEMAND_STATUS_N): ?><?php echo ProjectConst::DEMAND_WORKS_UPLOAD[$row['task_produce_num']]; ?>,<?php endif ?><?php echo ProjectConst::DEMAND_STATUS[$row['task_state']]; ?><br><?php echo ProjectConst::WORKS_STATUS[$row['task_worker_accept']]; ?><?php else: ?><small
                                                                    class="text-danger">删除中</small><?php endif ?></small>
                                                    </td>
                                                    <td><?php echo date('Y-m-d H:i', $row['created_at']); ?>
                                                    </td>
                                                    <td>

                                                        <div class="btn-group "> <a type="button" class="btn btn-default btn-sm"
                                                                href="<?php echo Url::to(['item', 'id' => $row['id']]); ?>">详情</a>
                                                            <a type="button" class="btn btn-default btn-sm dropdown-toggle"
                                                                data-toggle="dropdown" aria-haspopup="true"
                                                                aria-expanded="false"> <span class="caret"></span></a>
                                                            <ul class="dropdown-menu dropdown-menu-right">

                                                                <?php if ($row['uid'] == $this->context->user->id || $row['partner_uid'] == $this->context->user->id): ?>
                                                                    <!---- 所有者合作伙伴操作按钮 -- 开始-->
                                                                    <?php if ($row['task_state'] != ProjectConst::DEMAND_STATUS_SUCCESS && $row['deleted'] <= System::DELETE_LEVEL_1): ?>
                                                                        <li><a class="dropdown-item"
                                                                                href="<?php echo Url::to(['update', 'id' => $row['id']]); ?>"
                                                                                role="button">编辑</a></li>
                                                                    <?php endif; ?>
                                                                    <?php if ($row['task_state'] != ProjectConst::DEMAND_STATUS_SUCCESS && $row['task_worker_accept'] != ProjectConst::PARTNER_ACCEPT_WAIT && $row['deleted'] == System::DELETE_LEVEL_1): ?>
                                                                        <li> <a class="dropdown-item bg-danger ajaxLink"
                                                                                data-url="<?php echo Url::to(['delete', 'id' => $row['id']]); ?>"
                                                                                role="button">删除</a></li>
                                                                    <?php endif; ?>

                                                                    <!---- 所有者合作伙伴操作按钮-- 结束 ---->
                                                                <?php endif ?>

                                                                <?php if ($row['task_worker_uid'] == $this->context->user->id): ?>
                                                                    <!---- 创作者 -- 开始-->
                                                                    <?php if ($row['task_worker_accept'] == ProjectConst::WORKS_ACCEPT_WAIT): ?>
                                                                        <li> <a class="dropdown-item bg-success ajaxLink"
                                                                                data-url="<?php echo Url::to(['demand/partner/works-accept', 'id' => $row['id'], 'status' => ProjectConst::WORKS_ACCEPT_APPROVE]); ?>"
                                                                                role="button" data-message="确定同意创作吗？">接受创作</a></li>
                                                                        <li><a class="dropdown-item bg-danger ajaxLink"
                                                                                data-url="<?php echo Url::to(['demand/partner/works-accept', 'id' => $row['id'], 'status' => ProjectConst::WORKS_ACCEPT_REJECT]); ?>"
                                                                                role="button" data-message="确定拒绝创作吗？">拒绝创作</a></li>
                                                                    <?php endif; ?>

                                                                    <?php if ($row['deleted'] == System::DELETE_LEVEL_2): ?>
                                                                        <li><a class="dropdown-item bg-danger ajaxLink"
                                                                                data-url="<?php echo Url::to(['demand/partner/delete', 'id' => $row['id']]); ?>"
                                                                                role="button">确认删除</a></li>
                                                                    <?php endif; ?>
                                                                    <!---- 创作者 -- 结束-->
                                                                <?php endif; ?>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <?php $opt = ' style="display:none"'; ?>
                                            <td colspan="11" class="text-center text-primary">暂无记录</td>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-footer p-0">
                <div class="mailbox-controls">
                    <div class="float-right">
                        <?php echo LinkPager::widget(
                            [
                                'pagination' => $pagination,
                                'options' => ['class' => 'pagination'],
                                'linkOptions' => ['class' => 'page-link'],
                                'activePageCssClass' => ' page-item',
                                'disabledPageCssClass' => ' active',
                                'disabledListItemSubTagOptions' => ['tag' => 'a', 'class' => 'page-link'],
                                'disableCurrentPageButton' => true,
                            ]
                        ); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>