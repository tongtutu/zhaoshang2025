<?php
/**
 * 需求
 *
 * @author        shuguang <5565907@qq.com>
 * @copyright     © 2007-2099 cookman.cn. All rights reserved.
 */

namespace app\controllers;

use Yii;
use Exception;
use yii\helpers\Url;
use yii\web\Response;
use yii\data\Pagination;
use bagesoft\helpers\Utils;
use bagesoft\models\Demand;
use bagesoft\constant\System;
use bagesoft\functions\DemandFunc;
use bagesoft\functions\InvestFunc;
use bagesoft\functions\UploadFunc;
use bagesoft\functions\CustomerFunc;
use bagesoft\models\DemandWorks;
use bagesoft\communication\Dccomm;
use bagesoft\models\DemandUserMap;
use bagesoft\models\DemandTask;
use yii\web\NotFoundHttpException;
use bagesoft\constant\ProjectConst;
use bagesoft\constant\MessageQueueConst;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class DemandController extends \bagesoft\common\controllers\app\Base
{
    private $exportFields = [
        'id' => 'ID',
        'partner_name' => '合作者名称',
        'worker_name' => '创作者名称',
        'worker_accept' => '创作接受状态',
        'project_name' => '项目名称',
        'project_location' => '项目所在地',
        'hope_time' => '期望首次提交时间',
        'content' => '项目介绍',
        'state' => '状态',
        'produce_num' => '创作次数',
        'created_at' => '入库时间', 
        'retask' => '任务备注',
    ];

    /**
     * 列表
     */
    public function actionIndex()
    {
        parent::acl();
        $request = Yii::$app->request;
        $uid = (int) $this->session['uid'];
        $projectName = trim($request->get('projectName'));
        $username = trim($request->get('username'));
        $workername = trim($request->get('workername'));
        $location = trim($request->get('location'));
        $state = intval($request->get('state'));
        $dateAt = trim($request->get('date'));
        $dateAt = Utils::dateRange($dateAt);

        $model = DemandUserMap::find()->alias('userMap');
        $model->leftJoin(Demand::tableName() . 'demand', 'userMap.demand_id=demand.id');
        $model->leftJoin(DemandTask::tableName() . 'task','demand.id = task.demand_id AND task.worker_uid = :currentUid',['currentUid' => $uid]);
        $model->select('userMap.uid,userMap.role_type ,userMap.demand_id,demand.*,task.worker_accept as task_worker_accept,task.worker_uid as task_worker_uid,task.state as task_state,task.worker_name as task_worker_name,task.produce_num as task_produce_num');
        $model->where('userMap.uid=:uid ', ['uid' => $uid]);
        if ($projectName) {
            $model->andWhere(['like', 'demand.project_name', $projectName]);
        }
        if ($username) {
            $model->andWhere(['like', 'demand.username', $username]);
        }
        if ($workername) {
            $model->andWhere(['like', 'demand.worker_name', $workername]);
        }
        if ($location) {
            $model->andWhere(['like', 'demand.project_location', $location]);
        }
        if ($state) {
            $model->andWhere('demand.state=:state', ['state' => $state]);
        }
        if ($dateAt['start'] > 0 && $dateAt['end'] > 0) {
            $model->andWhere(['between', 'created_at', $dateAt['start'], $dateAt['end']]);
        }

        $count = $model->count();
        $pagination = new Pagination(['totalCount' => $count, 'pageSize' => '20', 'defaultPageSize' => 20]);
        $datalist = $model->offset($pagination->offset)->limit($pagination->limit)->orderBy('demand.id DESC')->asArray()->all();

        return $this->render(
            'index',
            [
                'count' => $count,
                'pagination' => $pagination,
                'datalist' => $datalist,
            ]
        );
    }

    /**
     * 录入
     */
    public function actionCreate()
    {
        parent::acl();
        $request = Yii::$app->request;
        $source = intval($request->get('source'));
        $projectId = intval($request->get('projectId'));
        $uid = (int) $this->session['uid'];
        if ($source == System::SOURCE_CUSTOMER) {
            $customer = CustomerFunc::getItemByUserMap($projectId, $uid);
            $project = $customer->customer;
            $callback = Url::toRoute(['customer/index']);
        } else {
            $project = InvestFunc::getItemByIdAndUid($projectId, $uid);
            $callback = Url::toRoute(['invest/index']);
        }
        if (false == $project) {
            throw new NotFoundHttpException('项目不存在');
        }

        $model = new Demand();
        if ($model->load(Yii::$app->request->post())) {
            if ($model->validate()) {
                $model->uid = intval($project->uid);
                $model->username = trim($project->username);
                $model->partner_uid = intval($project->partner_uid);
                $model->partner_name = trim($project->partner_name);
                $model->project_id = intval($project->id);
                $model->project_name = $project->project_name;
                $model->state = ProjectConst::DEMAND_STATUS_WAIT_PARTNER;
                $model->source = intval($source);
                if ($model->save()) {
                    // 发送消息
                    (new Dccomm([
                        'taskName' => '创建需求',
                        'act' => MessageQueueConst::DEMAND_CREATE,
                    ]))->run([
                                'demandId' => $model->id,
                                'source' => $model->source,
                                'uid' => $model->uid,
                            ]);
                    return $this->redirect($callback);
                }
            }
        }

        return $this->render(
            'create',
            [
                'model' => $model,
                'project' => $project,
                'source' => $source,
            ]
        );
    }

    /**
     * 更新
     */
    public function actionUpdate($id)
    {
        parent::acl();
        $model = DemandFunc::getItemByUserMap($id, (int) $this->session['uid']);
        $demand = $model->demand;

        if (false == $demand) {
            throw new NotFoundHttpException('需求不存在');
        }

        if ($demand->load(Yii::$app->request->post()) && $demand->validate()) {
            if ($demand->save()) {
                return $this->redirect('index');
            }
        }
        if ($demand->source == System::SOURCE_CUSTOMER) {
            $project = CustomerFunc::getItemById($demand->project_id);
        } else {
            $project = InvestFunc::getItemById($demand->project_id);
        }
        return $this->render(
            'update',
            [
                'project' => $project,
                'model' => $demand,
                'source' => $demand->source,
            ]
        );
    }

    /**
     * 详情
     */
    public function actionItem($id)
    {
        parent::acl();
        $model = DemandFunc::getItemByUserMap($id, (int) $this->session['uid']);
        if (false == $model) {
            throw new NotFoundHttpException('需求记录不存在');
        }
        $demand = $model->demand;
        if ($demand->source == System::SOURCE_CUSTOMER) {
            $project = CustomerFunc::getItemById($demand->project_id);
        } else {
            $project = InvestFunc::getItemById($demand->project_id);
        }
        if (false == $project) {
            throw new NotFoundHttpException('项目不存在');
        }

        $worksModel = new DemandWorks();
        $worksList = $worksModel->find()->where('demand_id=:demandId', ['demandId' => $model->id])->orderBy('id DESC')->all();

        $getWaitWorks = DemandFunc::getWaitWorks($demand->id);
        return $this->render(
            'item',
            [
                'model' => $demand,
                'project' => $project,
                'worksList' => $worksList,
                'worksModel' => $worksModel,
                'getWaitWorks' => $getWaitWorks,
                'attachFiles' => UploadFunc::getlist(System::UPLOAD_SOURCE_DEMAND, $demand->id),
            ]
        );
    }

    /**
     * 作品审核
     *
     * @return void
     */
    public function actionWorksAuditSave()
    {
        parent::acl();
        $request = Yii::$app->request;
        $worksId = intval($request->post('worksId'));
        $uid = (int) $this->session['uid'];

        try {
            $demandWorks = DemandFunc::getWorksItemByIdAndUidPartnerUid($worksId, $uid);
            if (false == $demandWorks) {
                throw new Exception('创作记录不存在');
            }
            $demand = DemandFunc::getItemById($demandWorks->demand_id);
            if (false == $demand) {
                throw new Exception('需求记录不存在');
            }
            if ($demandWorks->load(Yii::$app->request->post()) && $demandWorks->validate()) {
                if ($demandWorks->state == ProjectConst::DEMAND_WORKS_AUDIT_PASS) {
                    $demand->state = ProjectConst::DEMAND_STATUS_SUCCESS;
                    $action = MessageQueueConst::DEMAND_WORKS_PASS;
                    $demandWorks->state = ProjectConst::DEMAND_WORKS_AUDIT_PASS;
                    $demand->worker_succ_at = time();
                    $taskName = '作品审核通过';
                } else {
                    $action = MessageQueueConst::DEMAND_WORKS_REJECT;
                    $demand->state = $demandWorks->produce_num;
                    $demandWorks->state = ProjectConst::DEMAND_WORKS_AUDIT_REJECT;
                    $taskName = '作品审核拒绝';
                }
                $demandWorks->audit_at = time();
                $demandWorks->audit_uid = $uid;
                $demandWorks->audit_name = $this->session['username'];

                if ($demandWorks->save()) {
                    $demand->produce_num = $demandWorks->produce_num;
                    $demand->worker_state = $demandWorks->state;//同步作品状态
                    $demand->save();

                    //发送消息
                    (new Dccomm([
                        'taskName' => $taskName,
                        'act' => $action,
                    ]))->run([
                                'uid' => $demand->partner_uid,
                                'projectName' => $demand->project_name,
                            ]);

                } else {
                    throw new Exception('作品审核失败');
                }
            } else {
                throw new Exception('检查输入内容是否正确');
            }

            parent::renderSuccessJson([], '审核完成');
        } catch (\Throwable $th) {
            parent::renderErrorJson($th->getMessage());
        }
    }

    /**
     * 删除
     *
     * @return void
     */
    public function actionDelete($id)
    {
        try {
            $model = DemandFunc::getItemByIdAndUid($id, (int) $this->session['uid']);
            //已经接受只能伙伴通过后才能删除
            if ($model->worker_accept == ProjectConst::WORKS_ACCEPT_APPROVE) {
                $model->deleted = System::DELETE_LEVEL_2;
            } else {
                $model->deleted = System::DELETE_LEVEL_2;
            }
            $model->save();
            parent::renderSuccessJson([], '请求成功');
            // $this->redirect('index');
        } catch (\Throwable $th) {
            parent::renderErrorJson($th->getMessage());
        }

    }

    /**
     * 导出下载
     * @return mixed
     */
    public function actionExportDownload()
    {
        parent::acl();
        $headerKeys = array_keys($this->exportFields);
        $request = Yii::$app->request;
        $uid = (int) $this->session['uid'];
        $projectName = trim($request->get('projectName'));
        $username = trim($request->get('username'));
        $workername = trim($request->get('workername'));
        $location = trim($request->get('location'));
        $state = intval($request->get('state'));

        $dateAt = trim($request->get('date'));
        $dateAt = Utils::dateRange($dateAt);

        $model = DemandUserMap::find()->alias('userMap');
        $model->limit(System::EXPORT_EXCEL_NUM);
        $model->leftJoin(Demand::tableName() . 'demand', 'userMap.demand_id=demand.id');
        $model->select('demand.id,demand.partner_name,demand.worker_name,demand.worker_accept,demand.project_name,demand.project_location,demand.hope_time,demand.state,demand.produce_num,demand.created_at');
        $model->where('userMap.uid=:uid ', ['uid' => $uid]);
        if ($projectName) {
            $model->andWhere(['like', 'demand.project_name', $projectName]);
        }
        if ($username) {
            $model->andWhere(['like', 'demand.username', $username]);
        }
        if ($workername) {
            $model->andWhere(['like', 'demand.worker_name', $workername]);
        }
        if ($location) {
            $model->andWhere(['like', 'demand.project_location', $location]);
        }
        if ($state) {
            $model->andWhere('demand.state=:state', ['state' => $state]);
        }
        if ($dateAt['start'] > 0 && $dateAt['end'] > 0) {
            $model->andWhere(['between', 'demand.created_at', $dateAt['start'], $dateAt['end']]);
        }

        $datalist = $model->orderBy('demand.id DESC')->asArray()->all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 设置表头
        $header = array_values($this->exportFields);
        $sheet->fromArray([$header], null, 'A1');
        $rowIndex = 2;
        foreach ($datalist as $rowData) {
            $colIndex = 0;
            $content = strip_tags($rowData['content']);
            foreach ($rowData as $cellData) {
                $fieldName = $headerKeys[$colIndex];
                switch ($fieldName) {
                    case 'worker_accept':
                        $cellValue = ProjectConst::WORKS_STATUS[$cellData];
                        break;
                    case 'state':
                        $cellValue = ProjectConst::DEMAND_STATUS[$cellData];
                        break;
                    case 'content':
                        $cellValue = $content;
                        break;
                    case 'created_at':
                        $cellValue = date('Y-m-d H:i:s', $cellData);
                        break;
                    default:
                        $cellValue = $cellData;
                        break;
                }
                $sheet->setCellValueExplicitByColumnAndRow($colIndex + 1, $rowIndex, $cellValue, DataType::TYPE_STRING);
                $colIndex++;
            }
            $rowIndex++;
        }

        // 导出Excel文件
        $filename = 'export_demand_' . date('YmdHis') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        // 输出Excel文件
        ob_end_clean();
        ob_start();
        $writer->save('php://output');
        Yii::$app->end();
    }

}
