<?php
/**
 * 需求
 *
 * @author        shuguang <5565907@qq.com>
 * @copyright     © 2007-2099 cookman.cn. All rights reserved.
 */

namespace admin\controllers;

use Yii;
use yii\web\Response;
use yii\data\Pagination;
use bagesoft\models\User;
use bagesoft\functions\UserFunc;
use bagesoft\helpers\Utils;
use bagesoft\models\Demand;
use bagesoft\constant\System;
use bagesoft\functions\DemandFunc;
use bagesoft\functions\InvestFunc;
use bagesoft\functions\UploadFunc;
use bagesoft\functions\CustomerFunc;
use bagesoft\models\DemandWorks;
use bagesoft\models\DemandUserMap;
use bagesoft\models\DemandTask;
use bagesoft\communication\Dccomm;
use yii\web\NotFoundHttpException;
use bagesoft\constant\ProjectConst;
use bagesoft\constant\MessageQueueConst;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class DemandController extends \bagesoft\common\controllers\admin\Base
{
    private $exportFields = [
        'id' => 'ID',
        'project_name' => '项目名称',
        'username' => '需求提交人',
        'operator_name' => '分配者名称',
        'worker_name' => '创作者名称',
        'worker_accept' => '创作接受状态',
        'project_location' => '项目所在地',
        'hope_time' => '期望首次提交时间',
        'content' => '项目介绍',
        'state' => '状态',
        'produce_num' => '创作次数',
        'worker_first_at' => '首次提交',
        'worker_succ_at' => '完成时间',
        'created_at' => '提交需求时间',
        'retask' => '任务备注',
    ];


    /**
     * 列表
     */
    public function actionIndex()
    {
        parent::acl();
        $request = Yii::$app->request;
        $projectName = trim($request->get('projectName'));
        $username = trim($request->get('username'));
        $workername = trim($request->get('workername'));
        $location = trim($request->get('location'));
        $state = intval($request->get('state'));
        $workerUid = trim($request->get('workerUid'));

        $demand = new Demand();

        $model = $demand->find();
        $model->alias('demand');
        $model->leftJoin(User::tableName() . 'user', 'demand.uid=user.id');
        $model->select('user.id,user.username,demand.*');
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
        if ($workerUid) {
            $model->andWhere('demand.worker_uid=0');
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
                'demand' => $demand,
                'workers' => UserFunc::getWorkers(),
            ]
        );
    }

    /**
     * 更新
     */
    public function actionUpdate($id)
    {
        parent::acl();
        $model = DemandFunc::getItemById($id);
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->save()) {
                return $this->redirect('index');
            }
        }
        if ($model->source == System::SOURCE_CUSTOMER) {
            $project = CustomerFunc::getItemById($model->project_id);
        } else {
            $project = InvestFunc::getItemById($model->project_id);
        }
        return $this->render(
            'update',
            [
                'project' => $project,
                'model' => $model,
                'source' => $model->source,
            ]
        );
    }

    /**
     * 详情
     */
    public function actionItem($id)
    {
        parent::acl();
        $model = DemandFunc::getItemById($id);
        if (false == $model) {
            throw new NotFoundHttpException('记录不存在');
        }
        if ($model->source == System::SOURCE_CUSTOMER) {
            $project = CustomerFunc::getItemById($model->project_id);
        } else {
            $project = InvestFunc::getItemById($model->project_id);
        }
        if (false == $project) {
            throw new NotFoundHttpException('项目不存在');
        }

        $worksModel = new DemandWorks();
        $worksList = $worksModel->find()->where('demand_id=:demandId', ['demandId' => $model->id])->orderBy('id DESC')->all();

        return $this->render(
            'item',
            [
                'model' => $model,
                'project' => $project,
                'worksList' => $worksList,
                'worksModel' => $worksModel,
                'attachFiles' => UploadFunc::getlist(System::UPLOAD_SOURCE_DEMAND, $model->id),
            ]
        );
    }
    /**
     * 获取已分配的创作者ID（从 demand_user_map 查询）
     */
    public function actionTaskWorkers()
    {   
        //parent::acl('demand/task-workers'); 
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $demandId = intval($request->post('demandId'));
        $demand = demand::findOne($demandId);
        // 从 demand_user_map 查询该需求已分配的创作者（角色类型为 worker）
        $workerUids = DemandUserMap::find()
            ->where([
                'demand_id' => $demandId,
                'role_type' => System::WORKER // 创作者角色类型，与添加时一致
            ])
            ->select('uid')
            ->column(); // 返回用户ID数组
        
        return [
            'success' => true,
            'workerUids' => $workerUids,
            'retask' => $demand->retask ?? ''
        ];
    }
    /**
     * 分配售前技术方案
     *
     * @param integer $id
     * @return void
     */
    public function actionAssignWorker()
    {
        parent::acl('demand/assign-worker');
        try {
            $request = Yii::$app->request;
            $demandId = intval($request->post('demandId'));
            $model = $this->findModel($demandId);
            //$oldWorkerUid = $model->worker_uid;
            //赋值
            $postData = Yii::$app->request->post('Demand', []);
            $retask = trim($postData['retask'] ?? '');
            $workerUids = isset($postData['worker_uid']) ? array_filter(array_map('intval', $postData['worker_uid'])) : [];
            if (empty($workerUids)) {
                throw new \Exception('请选择至少一个研究员');
            }
            foreach ($workerUids as $uid) {
                $user = UserFunc::getUserById($uid);
                if (!$user) {
                    throw new \Exception("研究员ID:{$uid}不存在");
                }
            }
            // 3. 查询该需求已有的用户关联记录（用于存在性判断）
            $existingUids = DemandUserMap::find()
                ->where([
                    'demand_id' => $demandId,
                    'role_type' => System::WORKER // 创作者角色，根据实际业务调整
                ])
                ->select('uid')
                ->column(); // 返回已存在的用户ID数组
            $deleteUids = array_diff($existingUids, $workerUids);
            if (!empty($deleteUids)) {
            // 批量删除差异记录
                DemandUserMap::deleteAll([
                    'demand_id' => $demandId,
                    'uid' => $deleteUids,
                    'role_type' => System::WORKER
                ]);
                // 可选：删除后发送取消分配的消息通知
                //foreach ($deleteUids as $uid) {
                //    (new Dccomm([
                //        'taskName' => '取消分配售前技术方案',
                //        'act' => MessageQueueConst::DEMAND_UNASSIGN,
                //    ]))->run([
                //        'workerUid' => $uid,
                //        'adminName' => $this->admin->username,
                //    ]);
                //}
            }
            $newUids = array_diff($workerUids, $existingUids);
            foreach ($newUids as $uid) {
                $user = UserFunc::getUserById($uid);
                DemandFunc::addMap($demandId, $uid); 
            }
            $existingTaskUids = DemandTask::find()
                ->where([
                    'demand_id' => $demandId
                ])
                ->select('worker_uid')
                ->column(); // 返回已存在的用户ID数组
            $newTaskUids = array_diff($workerUids, $existingTaskUids);
            if (!empty($newTaskUids)) {
                foreach ($newTaskUids as $workerUid) {
                    $user = UserFunc::getUserById($workerUid);
                    $workername = $user->username;
                    // 仅传入需求ID和创作者UID，worker_name由工具方法自动获取
                    DemandFunc::addTask($demandId, $workerUid,$workername);
                }    
            }
            
            $isMessage = false;

            

            if ($isMessage == true) {
                //发送消息
                (new Dccomm([
                    'taskName' => '分配售前技术方案',
                    'act' => MessageQueueConst::DEMAND_ASSIGN,
                ]))->run([
                            'workerUid' => $workerUid,
                            'adminName' => $this->admin->username,
                            'uid' => $model->uid,
                        ]);
            }
            $model->retask = $retask;
            $model->state = ProjectConst::DEMAND_STATUS_WAIT_WORKS;
            $model->worker_accept = ProjectConst::WORKS_ACCEPT_WAIT;
            $model->operator_name = $this->admin->username;
            $model->operator_uid = $this->admin->id;
            $model->save();

            parent::renderSuccessJson([], '分配成功');
        } catch (\Throwable $th) {
            parent::renderErrorJson($th->getMessage());
        }

    }

    /**
     * 加载模型
     */
    public function findModel($id)
    {
        if (($model = Demand::findOne($id)) !== null) {
            return $model;
        } else {
            if (Yii::$app->request->isAjax) {
                parent::renderErrorJson('所请求的记录不存在');
            } else {
                throw new NotFoundHttpException('所请求的记录不存在');
            }
        }
    }

    /**
     * 删除
     *
     * @return void
     */
    public function actionDelete($id)
    {
        $model = DemandFunc::getItemById($id);
        if (false == $model) {
            throw new \Exception('记录不存在');
        }

        //发送消息
        (new Dccomm([
            'taskName' => '需求删除',
            'act' => MessageQueueConst::DEMAND_DELETE,
        ]))->run([
                    'projectId' => $model->project_id,
                    'source' => $model->source,
                    'partnerUid' => $model->partner_uid,
                    'adminName' => $this->admin->username,
                ]);
        $model->delete();
        $this->redirect('index');
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
        $projectName = trim($request->get('projectName'));
        $username = trim($request->get('username'));
        $workername = trim($request->get('workername'));
        $location = trim($request->get('location'));
        $state = intval($request->get('state'));

        $dateAt = trim($request->get('date'));
        $dateAt = Utils::dateRange($dateAt);

        $model = Demand::find()->alias('demand');
        //$model->limit(System::EXPORT_EXCEL_NUM);
        //$model->select('*');
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
            foreach ($headerKeys as $headerKey) {
                switch ($headerKey) {
                    case 'worker_accept':
                        $cellValue = ProjectConst::WORKS_STATUS[$rowData['worker_accept']];
                        break;
                    case 'state':
                        $cellValue = ProjectConst::DEMAND_STATUS[$rowData['state']];
                        break;
                    case 'content':
                        $cellValue = $content;
                        break;
                    case 'worker_first_at':
                        $cellValue = $rowData['worker_first_at'] > 0 ? date('Y-m-d H:i:s', $rowData['worker_first_at']) : '';
                        break;
                    case 'worker_succ_at':
                        $cellValue = $rowData['worker_succ_at'] > 0 ? date('Y-m-d H:i:s', $rowData['worker_succ_at']) : '';
                        break;
                    case 'created_at':
                        $cellValue = date('Y-m-d H:i:s', $rowData['created_at']);
                        break;
                    default:
                        $cellValue = $rowData[$headerKey];
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
        $response->data = ob_get_clean(); // 捕获缓冲区内容作为响应数据
        return $response; 
        Yii::$app->end();
    }
}
