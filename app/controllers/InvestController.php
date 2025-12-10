<?php
/**
 * 招商信息
 *
 * @author        shuguang <5565907@qq.com>
 * @copyright     © 2007-2099 cookman.cn. All rights reserved.
 */

namespace app\controllers;

use Yii;
use yii\helpers\Html;
use yii\web\Response;
use yii\data\Pagination;
use bagesoft\functions\TagsFunc;
use bagesoft\functions\UserFunc;
use bagesoft\helpers\Utils;
use bagesoft\models\Invest;
use bagesoft\functions\VisitFunc;
use bagesoft\constant\System;
use bagesoft\functions\InvestFunc;
use bagesoft\functions\UploadFunc;
use bagesoft\models\Maintain;
use bagesoft\functions\ProjectFunc;
use bagesoft\functions\MaintainFunc;
use bagesoft\constant\UserConst;
use bagesoft\models\InvestUserMap;
use yii\web\NotFoundHttpException;
use bagesoft\constant\ProjectConst;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use bagesoft\models\form\maintain\BtMgrForm;
use bagesoft\models\form\maintain\NormalForm;

class InvestController extends \bagesoft\common\controllers\app\Base
{
    private $exportFields = [
        'id' => 'ID',
        'project_name' => '项目名称',
        'manager_name' => '项目经理姓名',
        'username' => '创建人',
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
        $steps = intval($request->get('steps'));
        $channelId = intval($request->get('channelId'));
        $projectAssess = intval($request->get('projectAssess'));
        $dateAt = trim($request->get('date'));
        $dateAt = Utils::dateRange($dateAt);

        $model = InvestUserMap::find()->alias('userMap');
        $model->leftJoin(Invest::tableName() . 'invest', 'userMap.project_id=invest.id');
        $model->select('userMap.uid,userMap.project_id,userMap.role_type,invest.*');
        $model->where('userMap.uid=:uid AND invest.deleted=:deleted', ['uid' => $this->session['uid'], 'deleted' => System::DELETE_LEVEL_1]);

        if ($projectName) {
            $model->andWhere(['like', 'project_name', $projectName]);
        }
        if ($companyName) {
            $model->andWhere(['like', 'company_name', $companyName]);
        }
        if ($realname) {
            $model->andWhere(['like', 'realname', $realname]);
        }
        if ($steps) {
            $model->andWhere('steps=:steps', ['steps' => $steps]);
        }
        if ($channelId) {
            $model->andWhere('channel_id=:channelId', ['channelId' => $channelId]);
        }
        if ($projectAssess) {
            $model->andWhere('project_assess=:projectAssess', ['projectAssess' => $projectAssess]);
        }
        if ($dateAt['start'] > 0 && $dateAt['end'] > 0) {
            $model->andWhere(['between', 'created_at', $dateAt['start'], $dateAt['end']]);
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
            ]
        );
    }

    /**
     * 字段检测
     *
     * @return mixed
     */
    public function actionValueCheck()
    {
        try {
            $request = Yii::$app->request;
            $inputValue = trim($request->get('inputValue'));
            $fieldName = trim($request->get('fieldName'));

            $model = Invest::find();
            switch ($fieldName) {
                case 'usciCode':
                    $model->where('usci_code=:usciCode', ['usciCode' => $inputValue]);
                    break;
                case 'phone':
                    $model->where('contact_phone=:contactPhone', ['contactPhone' => $inputValue]);
                    break;
                case 'address':
                    $model->where('address=:address', ['address' => $inputValue]);
                    break;
                case 'contactName':
                    $model->where('contact_name=:contactName', ['contactName' => $inputValue]);
                    break;
                default:
                    throw new \Exception('字段不在检测范围内');
                    break;
            }
            $error = '请注意：' . Html::encode($inputValue) . ' 系统中已存在';

            $count = $model->count();
            if ($count > 0) {
                throw new \Exception($error);
            }
        } catch (\Throwable $th) {
            parent::renderErrorJson($th->getMessage());
        }
    }

    /**
     * 录入
     */
    public function actionCreate()
    {
        parent::acl();
        $model = new Invest();
        $model->scenario = 'create';
        //显示项目经理
        $hasManager = UserFunc::hasManager($this->session['gid']);
        if ($model->load(Yii::$app->request->post())) {
            $model->uid = $this->session['uid'];
            $model->username = $this->session['username'];
            $model->hasManager = $hasManager;
            if ($model->validate()) {
                if ($model->save()) {
                    Yii::$app->session->setFlash('publishSuccess', '发布成功');
                    return $this->redirect(['index']);
                }
            }
        }
        if ($model->vice_manager_uid > 0) {
            $viceManager = UserFunc::getUserById($model->vice_manager_uid);
            if ($viceManager) {
                $model->vice_manager_name = $viceManager->username;
            }
        }
        $model->loadDefaultValues();

        $model->tags = $model->tags ?: [];
        return $this->render(
            'create',
            [
                'model' => $model,
                'hasManager' => $hasManager,
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
        //显示项目经理
        $hasManager = UserFunc::hasManager($this->session['gid']);
        if ($model->load(Yii::$app->request->post())) {
            $model->hasManager = $hasManager;
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
                'hasManager' => $hasManager,
            ]
        );
    }

    /**
     * 查看详情
     *
     * @param integer $id
     * @return string
     */
    public function actionItem($id)
    {
        parent::acl();
        $model = InvestFunc::getItemByUidOrMgrId($id, $this->session['uid']);
        if (false == $model) {
            throw new NotFoundHttpException('记录不存在');
        }
        VisitFunc::log($this->session, $model, System::SOURCE_INVEST);
        $model->views += 1;
        $model->save();

        return $this->render(
            'item',
            [
                'model' => $model,
                'maintain' => new NormalForm(),
                'maintains' => MaintainFunc::getMaintianListById($model->id, System::SOURCE_INVEST),
                'attachFiles' => UploadFunc::getlist(System::UPLOAD_SOURCE_INVEST, $model->id),
                'manager' => UserFunc::getUserById($model->manager_uid),
            ]
        );
    }

    /**
     * 内容删除
     */
    public function actionDelete($id)
    {

        try {
            parent::acl();
            $model = $this->findModel($id);
            if ($model->is_demand == ProjectConst::DEMAND_APPLY_SUBMIT) {
                $model->deleted = System::DELETE_LEVEL_2;
                $model->save();
                $msg = '删除请求已发送';
            } else {
                $model->delete();
                $msg = '删除成功';
            }
            parent::renderSuccessJson([], $msg);
        } catch (\Exception $e) {
            parent::renderErrorJson($e->getMessage());
        }
    }

    /**
     * 加载模型
     */
    protected function findModel($id)
    {
        $model = Invest::find()->where('id=:id AND uid=:uid', ['uid' => $this->session['uid'], 'id' => $id])->limit(1)->one();
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
        parent::acl();
        $uid = $this->session['uid'];
        $headerKeys = array_keys($this->exportFields);
        $model = Invest::find();
        $model->limit(System::EXPORT_EXCEL_NUM);
        $request = Yii::$app->request;
        $projectName = trim($request->get('projectName'));
        $companyName = trim($request->get('companyName'));
        $realname = trim($request->get('realname'));
        $stars = intval($request->get('stars'));
        $steps = intval($request->get('steps'));
        $dateAt = trim($request->get('date'));
        $dateAt = Utils::dateRange($dateAt);

        $model->where('uid=:uid AND deleted=:deleted OR manager_uid=:managerUid', ['uid' => $uid, 'managerUid' => $this->session['uid'], 'deleted' => System::DELETE_LEVEL_1]);

        if ($projectName) {
            $model->andWhere(['like', 'project_name', $projectName]);
        }
        if ($companyName) {
            $model->andWhere(['like', 'company_name', $companyName]);
        }
        if ($realname) {
            $model->andWhere(['like', 'realname', $realname]);
        }
        if ($stars) {
            $model->andWhere('stars=:stars', ['stars' => $stars]);
        }
        if ($steps) {
            $model->andWhere('steps=:steps', ['steps' => $steps]);
        }

        if ($dateAt['start'] > 0 && $dateAt['end'] > 0) {
            $model->andWhere(['between', 'created_at', $dateAt['start'], $dateAt['end']]);
        }
        array_push($headerKeys, 'uid');
        $model->select($headerKeys);
        $datalist = $model->orderBy('id DESC')->asArray()->all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 设置表头
        $header = array_values($this->exportFields);
        $sheet->fromArray([$header], null, 'A1');
        $rowIndex = 2;

        foreach ($datalist as $rowData) {
            $userId = $rowData['uid'];
            $content = strip_tags($rowData['content']);
            $username = $rowData['username'];
            $contactName = ProjectFunc::hideName($userId, $uid, $rowData['contact_name']);
            $contactPhone = ProjectFunc::hideName($userId, $uid, $rowData['contact_phone']);
            $address = ProjectFunc::hideName($userId, $uid, $rowData['address']);
            $companyName = ProjectFunc::hideName($userId, $uid, $rowData['company_name']);
            $usciCode = ProjectFunc::hideName($userId, $uid, $rowData['usci_code']);
            $colIndex = 0;


            foreach ($headerKeys as $headerKey) {

                switch ($headerKey) {
                    case 'contact_name':
                        $cellValue = $contactName;
                        break;
                    case 'contact_phone':
                        $cellValue = $contactPhone;
                        break;
                    case 'address':
                        $cellValue = $address;
                        break;
                    case 'content':
                        $cellValue = $content;
                        break;
                    case 'username':
                        $cellValue = $username;
                        break;
                    case 'company_name':
                        $cellValue = $companyName;
                        break;
                    case 'usci_code':
                        $cellValue = $usciCode;
                        break;
                    case 'channel_id':
                        $cellValue = ProjectConst::CHANNEL[$rowData['channel_id']];
                        break;
                    case 'created_at':
                        $cellValue = date('Y-m-d H:i:s', $rowData['created_at']);
                        break;
                    case 'steps':
                        $cellValue = ProjectConst::STEPS[$rowData['steps']];
                        break;
                    case 'tags':
                        $v = TagsFunc::unformat($rowData['tags'], 'v');
                        $cellValue = implode('，', $v);
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
