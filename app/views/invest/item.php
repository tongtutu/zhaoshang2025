<?php

use yii\helpers\Url;
use yii\helpers\Html;
use bagesoft\functions\TagsFunc;
use bagesoft\constant\System;
use bagesoft\functions\ProjectFunc;
use bagesoft\constant\UserConst;
use bagesoft\constant\ProjectConst;

$this->title = '招商信息';
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['index']];
$this->params['breadcrumbs'][] = '详情';
?>
<div class="row">
    <div class="col-lg-9">

        <div class="row">
            <div class="col-lg-12">
                <div class="card">

                    <div class="card-body">
                        <div class="col-12 table-responsive">
                            <table class="table table-">
                                <tbody>
                                    <tr>
                                        <th style="width:15%">信息编号</th>
                                        <td><?php echo $model->id; ?> <small>访问:<?php echo $model->views; ?></small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>发布时间</th>
                                        <td><?php echo date('Y-m-d H:i:s', $model->created_at); ?></td>
                                    </tr>
                                    <tr>
                                        <th>项目名称:</th>
                                        <td><?php echo Html::encode($model->project_name); ?></td>
                                    </tr>
                                    <tr>
                                        <th>创建人:</th>
                                        <td><?php echo Html::encode($model->username); ?></td>
                                    </tr>
                                    <tr>
                                        <th>考核项目:</th>
                                        <td><?php echo TagsFunc::getTagsName($model->project_assess); ?></td>
                                    </tr>
                                    <tr>
                                        <th>项目阶段</th>
                                        <td><?php echo ProjectConst::STEPS[$model->steps]; ?>
                                            <?php if ($model->maintain_at > 0): ?>
                                                <small>最后跟进(<?php echo date('Y-m-d H:i:s', $model->maintain_at); ?>)</small><?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>项目标签</th>
                                        <td><?php echo TagsFunc::render($model->tags); ?></td>
                                    </tr>
                                    <tr>
                                        <th>公司全称:</th>
                                        <td><?php echo Html::encode(ProjectFunc::hideName($model->uid, $this->context->user->id, $model->company_name)); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>统一信用代码:</th>
                                        <td><?php echo Html::encode(ProjectFunc::hideName($model->uid, $this->context->user->id, $model->usci_code)); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>客户姓名:</th>
                                        <td><?php echo Html::encode(ProjectFunc::hideName($model->uid, $this->context->user->id, $model->contact_name)); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>联系方式（手机）:</th>
                                        <td><?php echo Html::encode(ProjectFunc::hidePhone($model->uid, $this->context->user->id, $model->contact_phone)); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>其他联系方式:</th>
                                        <td><?php echo Html::encode(ProjectFunc::hidePhone($model->uid, $this->context->user->id, $model->contact_other)); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>联系地址:</th>
                                        <td><?php if ($model->province): ?><?php echo Html::encode($model->province); ?><?php endif; ?><?php if ($model->province): ?>
                                                <?php echo Html::encode($model->city); ?><?php endif; ?><?php if ($model->province): ?>
                                                <?php echo Html::encode($model->area); ?><?php endif; ?>
                                            <?php echo Html::encode(ProjectFunc::hidePhone($model->uid, $this->context->user->id, $model->address)); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>附件</th>
                                        <td>
                                            <div class="attachFile">
                                                <ul>
                                                    <?php foreach ($attachFiles as $key => $file): ?>
                                                        <li> <a href="<?php echo Url::toRoute(['upload/getfile', 'id' => $file->id]); ?>"
                                                                target="_blank"><i class="fa fa-paperclip"></i>
                                                                <?php echo Html::encode($file->real_name); ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>项目介绍</th>
                                        <td colspan="2"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"> <?php echo $model->content; ?></td>
                                    </tr>
                                    <tr>
                                        <th>项目过户记录</th>
                                        <td colspan="2"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"> <?php if ($transfers): ?>
                                            <?php foreach ($transfers as $transfer): ?>
                                               <li>
                                                    【<?php echo date('Y-m-d H:i:s', $transfer->time); ?>】   项目由【<?php echo Html::encode($transfer->operation_name); ?>】从【<?php echo Html::encode($transfer->source_name); ?>】过户至【<?php echo Html::encode($transfer->target_name); ?>】
                                               </li>
                                            <?php endforeach; ?>
                                            <?php else: ?>
                                                <li>
                                                    暂无过户记录
                                                </li>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php echo $this->render('/_include/maintainForm', [
            'maintain' => $maintain,
            'model' => $model,
            'source' => System::SOURCE_INVEST,
        ]);
?>


        <?php echo $this->render('/_include/maintainList', [
    'maintain' => $maintain,
    'maintains' => $maintains,
    'model' => $model,
    'source' => System::SOURCE_INVEST,
]);
?>

    </div>
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <h5 class="m-0">项目经理</h5>
            </div>
            <div class="card-body">
                <div class="col-12 table-responsive">
                    <table class="table table-striped">
                        <tbody>
                        <tr>
                                <th>姓名</th>
                                <td><?php echo Html::encode($manager->username); ?></td>
                            </tr>

                            <tr>
                                <th>手机</th>
                                <td><?php echo Html::encode($manager->mobile); ?></td>
                            </tr>

                            <tr>
                                <th>状态</th>
                                <td><?php echo UserConst::STATUS[$manager->state]; ?></td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h5 class="m-0">招商管理岗</h5>
            </div>
            <div class="card-body">
                <div class="col-12 table-responsive">
                    <table class="table table-striped">
                        <tbody>
                        <tr>
                                <th>姓名</th>
                                <td><?php echo Html::encode($vice_manager->username); ?></td>
                            </tr>

                            <tr>
                                <th>手机</th>
                                <td><?php echo Html::encode($vice_manager->mobile); ?></td>
                            </tr>

                            <tr>
                                <th>状态</th>
                                <td><?php echo UserConst::STATUS[$vice_manager->state]; ?></td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


