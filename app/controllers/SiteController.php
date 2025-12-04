<?php
/**
 * 系统首页
 *
 * @author        shuguang <5565907@qq.com>
 * @copyright     (c) 2007-2099 cookman.cn. All rights reserved.
 */

namespace app\controllers;

use Yii;
use yii\web\UploadedFile;
use bagesoft\functions\HashFunc;
use bagesoft\helpers\Utils;
use bagesoft\models\Demand;
use bagesoft\models\Invest;
use bagesoft\constant\System;
use bagesoft\models\Customer;
use bagesoft\models\Maintain;
use bagesoft\constant\UserConst;
use bagesoft\models\DemandWorks;
use bagesoft\constant\ProjectConst;
use bagesoft\models\CustomerUserMap;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class SiteController extends \bagesoft\common\controllers\app\Base
{
    public $enableCsrfValidation = false;
    public function actionIndex()
    {
        $uid = $this->session['uid'];
        $customerNum = Customer::find()->where('uid=:uid', ['uid' => $uid])->count();//市场信息数

        $customerWaitAcceptNum = Customer::find()->where('partner_uid=:uid and partner_accept=:partnerAccept', ['uid' => $uid, 'partnerAccept' => ProjectConst::PARTNER_ACCEPT_WAIT])->count();//市场信息数_待审核

        $investNum = Invest::find()->where('uid=:uid', ['uid' => $uid])->count();//招商信息数

        $demandNum = Demand::find()->where('uid=:uid', ['uid' => $uid])->count();//需求信息数

        $demandWorksWaitAcceptNum = DemandWorks::find()->where('uid=:uid AND state=:state', ['uid' => $uid, 'state' => ProjectConst::DEMAND_WORKS_AUDIT_WAIT])->count();//待审创作数

        $maintainNum = Maintain::find()->where('uid=:uid', ['uid' => $uid])->count();//跟进维护信息数

        $maintainWaitAuditNum = Maintain::find()->where('partner_uid=:partnerUid AND state=:state', ['partnerUid' => $uid, 'state' => ProjectConst::MAINTAIN_STATUS_WAIT])->count();//待审核维护信息数

        $workerNum = DemandWorks::find()->where('worker_uid=:workerUid', ['workerUid' => $uid])->count();//分配创作信息数

        $workerWaitAcceptNum = Demand::find()->where('worker_uid=:workerUid AND worker_accept=:workerAccept', ['workerUid' => $uid, 'workerAccept' => ProjectConst::WORKS_ACCEPT_WAIT])->count();//待审创作数

        $btMgrNum = CustomerUserMap::find()->where('uid=:uid AND role_type=:roleType', ['uid' => $uid, 'roleType' => System::BT_MANAGER])->count();


        return $this->render(
            'index',
            [
                'customerNum' => $customerNum,
                'customerWaitAcceptNum' => $customerWaitAcceptNum,
                'investNum' => $investNum,
                'demandNum' => $demandNum,
                'demandWorksWaitAcceptNum' => $demandWorksWaitAcceptNum,
                'maintainNum' => $maintainNum,
                'maintainWaitAuditNum' => $maintainWaitAuditNum,
                'user' => $this->user,
                'workerNum' => $workerNum,
                'workerWaitAcceptNum' => $workerWaitAcceptNum,
                'btMgrNum' => $btMgrNum,
            ]
        );

    }

    public function actionFile()
    {

        return $this->render(
            'file',
            [

            ]
        );
    }
    public function actionUpload()
    {

        $uploadedFile = UploadedFile::getInstanceByName('file');
        // Utils::dump($uploadedFile);

        if ($uploadedFile) {
            $uploadPath = '/Volumes/data/developer/cs/zhaoshang/app/web/uploads/';
            $fileName = $uploadPath . $uploadedFile->name;

            if ($uploadedFile->saveAs($fileName)) {
                return json_encode(['status' => 'success', 'filename' => $fileName]);
            }
        }

        return json_encode(['status' => 'error', 'message' => 'Failed to save the uploaded file.']);

    }

    public function actionTest2()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $data = [
            ['name' => 'John Doe', 'email' => 'john.doe@example.com', 'mobile' => '123456789', 'area' => 'New York', 'country' => 'USA'],
            ['name' => 'Jane Smith', 'email' => 'jane.smith@example.com', 'mobile' => '987654321', 'area' => 'London', 'country' => 'UK'],
        ];

        // 设置表头
        $header = array_keys($data[0]);
        $sheet->fromArray([$header], null, 'A1');

        // 将数据写入电子表格并设置单元格数据类型为文本
        $rowIndex = 2;
        foreach ($data as $rowData) {
            $colIndex = 0;
            foreach ($rowData as $cellData) {
                $sheet->setCellValueExplicitByColumnAndRow($colIndex + 1, $rowIndex, $cellData, DataType::TYPE_STRING);
                $colIndex++;
            }
            $rowIndex++;
        }

        // 导出Excel文件
        $filename = 'exported_data_' . date('YmdHis') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        Yii::$app->end();
    }

}
