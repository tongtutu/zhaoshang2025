<?php
/**
 * 招商信息
 *
 * @author        shuguang <5565907@qq.com>
 * @copyright     © 2007-2099 cookman.cn. All rights reserved.
 */

namespace admin\controllers;

use bagesoft\functions\InvestFunc;
use Yii;
use yii\web\Response;
use yii\data\Pagination;
use bagesoft\functions\TagsFunc;
use bagesoft\functions\UserFunc;
use bagesoft\helpers\Utils;
use bagesoft\models\Invest;
use bagesoft\functions\VisitFunc;
use bagesoft\constant\System;
use bagesoft\functions\UploadFunc;
use bagesoft\models\Maintain;
use bagesoft\models\InvestExt;
use bagesoft\functions\ProjectFunc;
use bagesoft\functions\MaintainFunc;
use bagesoft\communication\Dccomm;
use yii\web\NotFoundHttpException;
use bagesoft\constant\ProjectConst;
use bagesoft\constant\MessageQueueConst;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class InvestController extends \bagesoft\common\controllers\admin\Base
{
    private $exportFields = [
        'id' => 'ID',
        'username' => '创建人',
        'project_name' => '项目名称',
        'manager_name' => '项目经理姓名',
        'vice_manager_name' => '招商管理岗姓名',
        'company_name' => '公司名称',
        'usci_code' => '统一信用代码',
        'tags' => '所属分类',
        'channel_id' => '渠道',
        'channel_name' => '渠道名称',
        'contact_name' => '联系人',
        'contact_phone' => '联系方式',
        'project_assess' => '所属考核项目',
        'province' => '省份',
        'city' => '城市',
        'area' => '区域',
        'address' => '联系地址',
        'content' => '项目介绍',
        'steps' => '项目阶段',
        'attach_file' => '附件',
        'created_at' => '入库时间',
    ];

    /**
     * 列表
     */
    public function actionIndex()
    {
        parent::acl();

        $request = Yii::$app->request;
        $projectName = trim($request->get('projectName'));
        $companyName = trim($request->get('companyName'));
        $realname = trim($request->get('realname'));
        $username = trim($request->get('username'));
        $assess = intval($request->get('assess'));
        $stars = intval($request->get('stars'));
        $steps = intval($request->get('steps'));

        $channelId = intval($request->get('channelId'));
        $projectAssess = intval($request->get('projectAssess'));
        $dateAt = trim($request->get('date'));
        $dateAt = Utils::dateRange($dateAt);

        $invest = new Invest();
        $model = $invest->find()->alias('invest');
        $model->leftJoin(InvestExt::tableName() . ' ext', 'ext.project_id=invest.id');
        $model->select('invest.*,ext.bt_request,ext.bt_request_respond');


        if ($projectName) {
            $model->andWhere(['like', 'invest.project_name', $projectName]);
        }
        if ($companyName) {
            $model->andWhere(['like', 'invest.company_name', $companyName]);
        }
        if ($realname) {
            $model->andWhere(['like', 'invest.realname', $realname]);
        }
        if ($username) {
            $model->andWhere('invest.username=:username', ['username' => $username]);
        }
        if ($assess) {
            $model->andWhere('invest.project_assess=:assess', ['assess' => $assess]);
        }
        if ($stars) {
            $model->andWhere('invest.stars=:stars', ['stars' => $stars]);
        }
        if ($steps) {
            $model->andWhere('invest.steps=:steps', ['steps' => $steps]);
        }

        if ($projectAssess) {
            $model->andWhere('project_assess=:projectAssess', ['projectAssess' => $projectAssess]);
        }

        if ($channelId) {
            $model->andWhere('channel_id=:channelId', ['channelId' => $channelId]);
        }
        if ($dateAt['start'] > 0 && $dateAt['end'] > 0) {
            $model->andWhere(['between', 'invest.created_at', $dateAt['start'], $dateAt['end']]);
        }
        $count = $model->count();
        $pagination = new Pagination(['totalCount' => $count, 'pageSize' => '20', 'defaultPageSize' => 20]);
        $datalist = $model->offset($pagination->offset)->limit($pagination->limit)->orderBy('invest.id DESC')->asArray()->all();
        return $this->render(
            'index',
            [
                'count' => $count,
                'pagination' => $pagination,
                'datalist' => $datalist,
                'project' => $invest,
            ]
        );
    }

    /**
     * 更新
     */
    public function actionUpdate($id)
    {
        parent::acl();
        $model = $this->findModel($id);
        $model->scenario = 'update';
        if ($model->load(Yii::$app->request->post())) {
            if ($model->validate()) {
                if ($model->save()) {
                    return $this->redirect(['index']);
                }
            }
        }
        if (!is_array($model->tags)) {
            $model->tags = TagsFunc::unformat($model->tags, 'k');
        } elseif (empty($model->tags)) {
            $model->tags = [];
        }
        return $this->render(
            'update',
            [
                'model' => $model,
            ]
        );
    }

    /**
     * 查看详情
     *
     * @param integer $id
     * @return mixed
     */
    public function actionItem($id)
    {
        parent::acl();
        $model = $this->findModel($id);
        VisitFunc::log($this->session, $model, System::SOURCE_INVEST);
        $model->views += 1;
        $model->save();
        return $this->render(
            'item',
            [
                'model' => $model,
                'maintain' => new Maintain(),
                'maintains' => MaintainFunc::getMaintianListById($model->id, System::SOURCE_INVEST),
                'attachFiles' => UploadFunc::getlist(System::UPLOAD_SOURCE_INVEST, $model->id),
                'manager' => UserFunc::getUserById($model->manager_uid),
                'vice_manager' => UserFunc::getUserById($model->vice_manager_uid),
                'transfers' => InvestFunc::getTransferList($model->id),
            ]
        );
    }


    /**
     * 分配招投标
     *
     * @param integer $id
     * @return void
     */
    // public function actionAssignBtManager()
    // {
    //     parent::acl('invest/assign-bt-manager');
    //     try {
    //         $request = Yii::$app->request;
    //         $projectId = intval($request->post('projectId'));
    //         $model = $this->findModel($projectId);
    //         $oldManagerId = $model->bt_manager_uid;
    //         $model->load(Yii::$app->request->post());
    //         ProjectFunc::assignBtManager($model, $oldManagerId);
    //         parent::renderSuccessJson([], '分配完成');
    //     } catch (\Throwable $th) {
    //         parent::renderErrorJson($th->getMessage());
    //     }

    // }


    /**
     * 过户
     *
     * @param integer $id
     * @return void
     */
    public function actionTransfer()
    {
        parent::acl();
        try {
            $request = Yii::$app->request;
            $post = $request->post();
            $operator =[
                'uid' => $this->session['uid'],
                'username' => $this->session['username']
                ];
            $transferId = intval($post['transferId']);
            $newUid = intval($post['Invest']['uid']);
            $model = $this->findModel($transferId);
            $newUser = UserFunc::getUserById($newUid);
            if (false == $newUser) {
                throw new NotFoundHttpException('新员工不存在');
            } elseif ($newUid->id == $model->uid) {
                throw new \Exception('不能转移给自己');
            }
            InvestFunc::transfer($model, $newUser,$operator);
            parent::renderSuccessJson([], '过户完成');
        } catch (\Throwable $th) {
            parent::renderErrorJson($th->getMessage());
        }

    }


    /**
     * 内容删除
     */
    public function actionDelete($id)
    {
        try {
            parent::acl();
            $model = $this->findModel($id);

            (new Dccomm([
                'taskName' => '删除招商信息',
                'act' => MessageQueueConst::PROJECT_DELETE,
            ]))->run([
                        'projectId' => $model->id,
                        'projectName' => $model->project_name,
                        'source' => System::SOURCE_INVEST,
                        'adminName' => $this->admin->username,
                        'uid' => $model->uid,
                    ]);

            $model->delete();
            return $this->redirect(['index']);
        } catch (\Exception $e) {

        }
    }

    /**
     * 加载模型
     */
    protected function findModel($id)
    {
        $model = Invest::findOne($id);
        if ($model) {
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
     * 导出下载
     * @return mixed
     */
    public function actionExportDownload()
    {   
        ini_set('memory_limit', '512M');
        set_time_limit(300);
        parent::acl();
        $request = Yii::$app->request;
        $projectName = trim($request->get('projectName'));
        $username = trim($request->get('username'));
        $channelId = intval($request->get('channelId'));
        $assess = intval($request->get('assess'));
        $steps = intval($request->get('steps'));
        $dateAt = trim($request->get('date'));
        $dateAt = Utils::dateRange($dateAt);
        $invest = new Invest();
        $model = $invest->find()->alias('invest');
        $model->select(array_keys($this->exportFields));

        if ($projectName) {
            $model->andWhere(['like', 'invest.project_name', $projectName]);
        }

        if ($username) {
            $model->andWhere('invest.username=:username', ['username' => $username]);
        }
        if ($assess) {
            $model->andWhere('invest.project_assess=:assess', ['assess' => $assess]);
        }

        if ($steps) {
            $model->andWhere('invest.steps=:steps', ['steps' => $steps]);
        }

        if ($channelId) {
            $model->andWhere('channel_id=:channelId', ['channelId' => $channelId]);
        }
        if ($dateAt['start'] > 0 && $dateAt['end'] > 0) {
            $model->andWhere(['between', 'invest.created_at', $dateAt['start'], $dateAt['end']]);
        }
        $datalist = $model->orderBy('invest.id DESC')->asArray()->all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 设置表头
        $header = array_values($this->exportFields);
        $headerKeys = array_keys($this->exportFields);
        $sheet->fromArray([$header], null, 'A1');

        // 将数据写入电子表格并设置单元格数据类型为文本
        $rowIndex = 2;
        foreach ($datalist as $rowData) {
            $colIndex = 0;
            $content = strip_tags($rowData['content']);
            foreach ($headerKeys as $headerKey) {
                $id = $rowData['id'];
                $attachFiles = UploadFunc::getLinkText(System::UPLOAD_SOURCE_INVEST, $id);
                switch ($headerKey) {
                    case 'channel_id':
                        $cellValue = ProjectConst::CHANNEL[$rowData['channel_id']];
                        break;
                    case 'created_at':
                        $cellValue = date('Y-m-d H:i:s', $rowData['created_at']);
                        break;
                    case 'steps':
                        $cellValue = ProjectConst::STEPS[$rowData['steps']];
                        break;
                    case 'content':
                        $cellValue = $content;
                        break;
                    case 'project_assess':
                        $cellValue = TagsFunc::getTagsName($rowData['project_assess']);
                        break;
                    case 'attach_file':
                        $cellValue = $attachFiles;
                        break;
                    case 'company_name':
                        $cellValue = ProjectFunc::adminHideAll($this->admin, $rowData[$headerKey]);
                        break;
                    case 'contact_name':
                        $cellValue = ProjectFunc::adminHideAll($this->admin, $rowData[$headerKey]);
                        break;
                    case 'contact_phone':
                        $cellValue = ProjectFunc::adminHideAll($this->admin, $rowData[$headerKey]);
                        break;
                    case 'usci_code':
                        $cellValue = ProjectFunc::adminHideAll($this->admin, $rowData[$headerKey]);
                        break;
                    case 'address':
                        $cellValue = ProjectFunc::adminHideAll($this->admin, $rowData[$headerKey]);
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
        $filename = 'export_invest_' . date('YmdHis') . '.xlsx';
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
