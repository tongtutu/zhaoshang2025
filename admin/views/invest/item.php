<?php

use yii\bootstrap5\Tabs;

echo Tabs::widget([
    'items' => [
        [
            'label' => '项目详情',
            'content' => $this->render('_include/infomation', [
                'model' => $model,
                'maintain' => $maintain,
                'maintains' => $maintains,
                'attachFiles' => $attachFiles,
                'manager' => $manager,
                'transfers' => $transfers
            ]), // 引用_tab1视图文件的内容
            'active' => true,
        ],
    ],
]);
