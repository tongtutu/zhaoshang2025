<?php

//use Yii;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap5\Modal;
use kartik\file\FileInput;
use yii\widgets\LinkPager;
use bagesoft\constant\System;
use yii\bootstrap5\ActiveForm;
use bagesoft\constant\ProjectConst;
use kartik\datetime\DateTimePicker;
use kartik\daterange\DateRangePicker;
use kartik\select2\Select2;

$this->title = '需求管理';
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['index']];
$this->params['breadcrumbs'][] = '列表';
$request = Yii::$app->request;
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
                                <?php echo Html::textInput('location', $request->get('location'), ['class' => 'form-control form-control-sm', 'placeholder' => '', 'id' => 'location']); ?>
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
                                <?php echo Html::dropDownList('state', $request->get('state'), ProjectConst::DEMAND_WORKS_UPLOAD, ['class' => 'form-control form-control-sm  custom-select', 'prompt' => '-']); ?>
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
                    <div class="card-title">
                        <a class="btn btn-default" href="<?php echo Url::to(array_merge(['export-download'], Yii::$app->request->get())); ?>">
                            <i class="fa fa-file-o"></i> 导出 </a>
                    </div>
                    <div class="card-tools">
                        共 <kbd> <?php echo $count; ?></kbd> 条记录
                    </div>
                </div>
            <?php endif; ?>
            <div class="card-body p-0">
                <div class="row">

                    <div class="col-md-12">
                        <form id="list-form" name="list-form" method="POST">
                            <div class="table-responsive mailbox-messages">
                                <table class="table table-hover ">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>项目名称</th>
                                            <th>提交人</th>
                                            <th>分配人</th>
                                            <th>项目所在地</th>
                                            <th>期望交付时间</th>
                                            <th>创作者</th>
                                            <th>状态</th>
                                            <th>提交时间</th>
                                            <th></th>
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
                                                            <?php echo mb_substr(Html::encode($row['project_name']),0,20); ?></a>
                                                    </td>
                                                    <td> <?php echo Html::encode($row['username']); ?></td>
                                                    <td> <?php echo Html::encode($row['operator_name']); ?></td>
                                                    <td>
                                                        <?php echo Html::encode($row['project_location']); ?>
                                                    </td>
                                                    <td><?php echo $row['hope_time']; ?></td>
                                                    <td><?php if ($row['worker_uid']): ?><?php echo Html::encode($row['worker_name']); ?><?php else: ?>待分配<?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['produce_num'] > 0 && $row['produce_num'] <= ProjectConst::DEMAND_STATUS_N): ?>
                                                            <?php echo ProjectConst::DEMAND_WORKS_UPLOAD[$row['produce_num']]; ?>,<?php endif ?><?php echo ProjectConst::DEMAND_STATUS[$row['state']]; ?>
                                                        <br><?php echo ProjectConst::WORKS_STATUS[$row['worker_accept']]; ?>
                                                    </td>
                                                    <td><?php echo date('Y-m-d H:i:s', $row['created_at']); ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-width-sm"> <a type="button"
                                                                class="btn btn-default btn-sm"
                                                                href="<?php echo Url::to(['item', 'id' => $row['id']]); ?>">详情</a>
                                                            <a type="button" class="btn btn-default btn-sm dropdown-toggle"
                                                                data-toggle="dropdown" aria-haspopup="true"
                                                                aria-expanded="false"> <span class="caret"></span></a>
                                                            <ul class="dropdown-menu dropdown-menu-right">
                                                                <li> <a class="dropdown-item"
                                                                        href="<?php echo Url::to(['update', 'id' => $row['id']]); ?>">编辑</a>
                                                                </li>
                                                                <li> <a class="dropdown-item open-modal"
                                                                        data-demand-id='<?php echo $row['id'] ?>'>分配售前技术方案</a>
                                                                </li>

                                                                <li> <a class="dropdown-item ajaxLink"
                                                                        data-url="<?php echo Url::to(['delete', 'id' => $row['id']]); ?>">删除</a>
                                                                </li>
                                                            </ul>

                                                        </div>
                                                    </td>

                                                </tr>

                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <?php $opt = ' style="display:none"'; ?>
                                            <td colspan="9" class="text-center text-primary">暂无记录</td>
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
<?php
// 模态框
Modal::begin([
    'title' => '分配售前技术方案',
    'id' => 'demandModal',
    'size' => 'modal-lg', // 设置模态框大小为大尺寸
    'clientOptions' => ['backdrop' => 'static', 'keyboard' => false],
]);

$form = ActiveForm::begin([
    'id' => 'demandForm',
    'action' => ['demand/assign-worker'],
]);
?>


<div class="card card-primary ">
    <div class="card-body">
        <div class="row">
            <div class="col-lg-6">
                <?php echo $form->field($demand, 'worker_uid')->widget(Select2::class, [
                    'data' => $workers, 
                    // 选项数据（与原下拉框一致）
                    'options' => [
                        'multiple' => true, // 开启多选
                        //'name' => 'worker_uid[]', // 数组形式提交
                        'placeholder' => '请选择',
                        'style' => 'width: 160px !important;' // 宽度占满父容器，最大高度限制
                    ],
                    'pluginOptions' => [
                        'allowClear' => true, // 允许清空选择
                        'tags' => false // 不允许自定义输入（仅使用提供的选项）
                    ]
                ])->label('请选择创作者（可多选）'); ?>
            </div>
            <div class="col-lg-12">
                <?php echo $form->field($demand, 'retask')->textarea([
                    'rows' => 3, 
                    'placeholder' => '请输入备注信息（选填）'
                ])->label('备注信息'); ?>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <?php echo Html::hiddenInput('demandId', '', ['id' => 'demandId']); ?>
        <?php echo Html::submitButton('确定提交 ', ['class' => 'submitBtn btn  btn-primary']); ?>
    </div>
</div>

<?php
ActiveForm::end();

Modal::end();


$script = <<<JS
$(document).ready(function() {
    $('.open-modal').click(function() {
        var demandId = $(this).data('demand-id');
        $('#demandId').val(demandId);
        $('#demandForm')[0].reset(); // 重置表单内容
        $('#demand-worker_uid').val(null).trigger('change');
        $.ajax({
            url: '/demand/task-workers', // 新增接口
            type: 'post',
            data: { demandId: demandId },
            dataType: 'json',
            success: function(data) {
                if (data.success && data.workerUids.length > 0) {
                    // 回显已选人员到 Select2
                    $('#demand-retask').val(data.retask);
                    $('#demand-worker_uid').val(data.workerUids).trigger('change');
                }
            }
        });
        $('#demandModal').modal('show');
    });

    $('#demandForm').on('beforeSubmit', function(e) {
        e.preventDefault();
        var form =$(this);
        var formData = form.serialize();
        //console.log('提交的分配数据：', formData);
        $.ajax({
            url: $(this).attr('action'),
            type: 'post',
            data: $(this).serialize(),
            success: function(data) {
                // 在这里处理成功提交后的逻辑
                if(data.state=='error'){
                    new $.Zebra_Dialog(data.message, {
                        modal: false,
                    });
                }else{
                    new $.Zebra_Dialog(data.message, {
                        modal: false,
                    });
                    $('#demandModal').modal('hide');
                    //window.location.reload();
                }
            },
            error: function(xhr, status, error) {
                new $.Zebra_Dialog('提交失败，请重试', {
                    modal: false,
                });
                return false;
            }
        });
    }).on('submit', function(e){
        e.preventDefault();
    });

  
});
JS;

$this->registerJs($script);
?>