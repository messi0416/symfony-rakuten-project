<?php
/**
 * バッチ処理 輸出書類出力処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\EntityInterface\SymfonyUserInterface;
use MiscBundle\Entity\JobRequest;
use MiscBundle\Entity\Repository\TbDeliveryPickingBlockRepository;
use MiscBundle\Entity\Repository\TbDeliveryStatementDetailNumOrderListInfoRepository;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\SymfonyUserClient;
use MiscBundle\Entity\SymfonyUserYahooAgent;
use MiscBundle\Entity\TbDeliveryPickingBlockDetail;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Border;
use PHPExcel_Style_Fill;
use PHPExcel_Worksheet_MemoryDrawing;
use PHPExcel_Style_Alignment;
use PHPExcel_Writer_Excel2007;
use MiscBundle\Util\ImageUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\Repository\ProductImagesRepository;
use MiscBundle\Entity\Repository\TbIndividualorderhistoryRepository;
use MiscBundle\Entity\TbIndividualorderhistory;
use MiscBundle\Util\BatchLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use MiscBundle\Entity\TbOrderListExport;
use MiscBundle\Entity\Repository\TbOrderListExportRepository;
use MiscBundle\Entity\Repository\BaseRepository;
use MiscBundle\Entity\PurchasingAgent;
use MiscBundle\Entity\Repository\ProductImagesVariationRepository;

class ExportOrderListToExcelCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var SymfonyUserInterface */
  private $account;

  private $doChangeLocationOrder = false;

  /** @var JobRequest */
  private $jobRequest;

  /** @var TbOrderListExport */
  private $orderListExport;

  private $pathFileExcelName;
  private $fileName;

  private $em;


  protected function configure()
  {
    $this
      ->setName('batch:export-order-list-to-excel')
      ->setDescription('輸出書類作成')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('job-request', null, InputOption::VALUE_OPTIONAL, '（JobRequestからの実行時）jobKey ※進捗更新に利用')
      ->addOption('agent-id', null, InputOption::VALUE_OPTIONAL, 'AgentID')
      ->addOption('conditions', null, InputOption::VALUE_OPTIONAL, 'Conditions')
      ->addOption('isForestStaff', false, InputOption::VALUE_OPTIONAL, '実行アカウントがフォレストスタッフアカウントかどうか')
      ->addOption('isClient', false, InputOption::VALUE_OPTIONAL, '実行アカウントが取引先アカウントかどうか')
      ->addOption('isYahooAgent', false, InputOption::VALUE_OPTIONAL, '実行アカウントがYahoo代理店アカウントかどうか')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $pid = getmypid();
    $this->fileName = sprintf('order_list_%s_%s.xlsx', (new \DateTime())->format('YmdHis'), $pid);

    $this->em = $this->getContainer()->get('doctrine')->getManager();

    $container = $this->getContainer();
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->info('Export order list to excel');

    $this->setInput($input);
    $this->setOutput($output);

    $usersTarget = 'MiscBundle:SymfonyUsers';
    if(boolval($input->getOption('isClient'))) {
      $usersTarget = 'MiscBundle:SymfonyUserClient';
    }
    if(boolval($input->getOption('isYahooAgent'))) {
      $usersTarget = 'MiscBundle:SymfonyUserYahooAgent';
    }

    $logger->info($input->getOption('account'));
    $logger->info($input->getOption('isClient'));
    $logger->info($input->getOption('isYahooAgent'));
    $logger->info($usersTarget);

    $agentId = intval($input->getOption('agent-id'));

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository($usersTarget)->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // JobRequest 登録
    // 進捗保存
    if ($jobKey = $input->getOption('job-request')) {
      /** @var JobRequest $account */
      $jobRequest = $container->get('doctrine')->getRepository('MiscBundle:JobRequest')->find($jobKey);
      if ($jobRequest) {
        $this->jobRequest = $jobRequest;
      }
    }

    try {

      $conditions = $input->getOption('conditions');
      $conditions = json_decode($conditions, true);
      $logExecTitle = sprintf('輸出書類出力処理');
      $logger->info('輸出書類出力処理開始');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $this->updateJobRequestMessage([
          'caption' => $logExecTitle
        , 'message' => '処理を開始しました。'
        , 'messageType' => 'info'
      ]);

      $this->insertDataToOrderListExport([
          'vendor'         => $agentId,
          'total_products' => null,
          'account'        => $accountId,
          'isForestStaff'  => $input->getOption('isForestStaff'),
          'isClient'       => $input->getOption('isClient'),
          'isYahooAgent'   => $input->getOption('isYahooAgent'),
          'last_download'  => null,
          'file'           => '',
          'message'        => ''
      ]);

      $this->updateTbOrderListExportStatus(TbOrderListExport::EXPORT_STATUS_FIND_DATA, null);

      /** @var TbIndividualorderhistoryRepository $repo */
      $logger->addDbLog($logger->makeDbLog(null, '対象データ取得', '開始'));
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbIndividualorderhistory');

      $data = $repo->getDataExportToExcelByAgentCode($agentId, $conditions);
      $totalData = 0;
      /** @var BaseRepository $repo */
      $baseRepository = $this->getDoctrine()->getRepository('MiscBundle:PurchasingAgent');
      $agent = $baseRepository->find($agentId);
      $this->exportOrderListToExcel($data, $conditions['is_empty_shipping_number'], $agent, $totalData, $logger);
      $pathFileName = (!is_null($this->pathFileExcelName)) ? $this->pathFileExcelName : '';
      $this->updateJobRequestMessage([
          'message' => '全ての処理を完了しました。'
        , 'messageType' => 'success'
        , 'fileName' => $pathFileName
      ]);
      /** @var FileUtil $fileUtil */
      $fileUtil = $this->getContainer()->get('misc.util.file');
      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog(null, $logExecTitle, '終了'));
      $this->updateTbOrderListExportStatus(TbOrderListExport::EXPORT_STATUS_FINISH, null);
      $logger->logTimerFlush();

      $logger->info('輸出書類出力処理終了');

    } catch (\Exception $e) {

      $logger->error('輸出書類出力処理 エラー:' . $e->getMessage());
      $logger->error($e->getTraceAsString());

      $this->updateTbOrderListExportStatus(TbOrderListExport::EXPORT_STATUS_ERROR, $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog(null, '輸出書類出力処理', 'エラー終了')->setInformation($e->getMessage())
        , true, '輸出書類出力処理 でエラーが発生しました。', 'error'
      );

      $this->updateJobRequestMessage([
          'message' => 'エラーが発生しました。' . $e->getMessage()
        , 'messageType' => 'danger'
      ]);

      return 1;
    }

    return 0;

  }

  public function exportOrderListToExcel($data, $filterByShippingNumberEmpty, $agent, &$totalData, $logger)
  {
      ini_set('memory_limit', '-1');
      ini_set('max_execution_time', 60 * 60);
      set_time_limit(60 * 60);
      $page = 1;
      // $limit = 50000;
      $commonUtil = $this->getContainer()->get('misc.util.db_common');
      $imageVariationDir = $this->getContainer()->getParameter('product_image_variation_dir');
      $imageOriginalDir = $this->getContainer()->getParameter('product_image_original_dir');
      $rateUSD = floatval($commonUtil->getSettingValue('EXCHANGE_RATE_USD'));
      $rateUSD = !is_null($rateUSD) ? $rateUSD : 0;
      // 商品説明は商品コード・description(en)・商品説明(ja)が一致しているものをマージする。
      // Packingは伝票番号・商品コード・description(en)・商品説明(ja)が一致しているものをマージする。数量は加算、それ以外は先頭を採用
      // Invoiceは商品コード・description(en)・商品説明(ja)が一致しているものをマージする。数量は加算、それ以外は先頭を採用
      $listData    = []; // 商品説明シート
      $packingData = []; // Packingシート
      $invoiceData = []; // Invoiceシート
      $randomKey = 1;
      $total = 0;
      foreach($data as $item) {

        // Packingシート
        if (!empty($item['shipping_number'])) {
          if (empty($packingData[$item['shipping_number']][$item['daihyo_syohin_code'].'_'.$item['description_en'].'_'.$item['hint_ja']])) {
            $packingData[$item['shipping_number']][$item['daihyo_syohin_code'].'_'.$item['description_en'].'_'.$item['hint_ja']] = $item;
          } else {
            $packingData[$item['shipping_number']][$item['daihyo_syohin_code'].'_'.$item['description_en'].'_'.$item['hint_ja']]['remain_num'] += (!is_null($item['remain_num'])) ? $item['remain_num'] : 0;
          }
        // 伝票番号が空かつ取得のフラグが立っている場合、empty_xxxのキーで取得
        } else if ($filterByShippingNumberEmpty == 1) {
          $packingData['empty_' . $randomKey][] = $item;
          $randomKey++;
        }

        // 商品説明シート
        $listData[$item['daihyo_syohin_code'].'_'.$item['description_en'].'_'.$item['hint_ja']] = $item;

        // invoiceシート
        if (empty($invoiceData[$item['daihyo_syohin_code'].'_'.$item['description_en'].'_'.$item['hint_ja']])) {
          $invoiceData[$item['daihyo_syohin_code'].'_'.$item['description_en'].'_'.$item['hint_ja']] = $item;
        } else {
          $invoiceData[$item['daihyo_syohin_code'].'_'.$item['description_en'].'_'.$item['hint_ja']]['remain_num'] += (!is_null($item['remain_num'])) ? $item['remain_num'] : 0;
        }

        $total++;
      }
      
      ksort($packingData ,SORT_STRING );
      $totalData = $total;
      $logger->addDbLog($logger->makeDbLog(null, '対象データ取得', "対象件数: $total 件"));
      $this->orderListExport->setTotalProducts($total);
      $this->updateTbOrderListExportStatus(TbOrderListExport::EXPORT_STATUS_CREATE_EXCEL, null);

      /** @var ImageUtil $imageUtil */
      $imageUtil = $this->getContainer()->get('misc.util.image');
      $imageDir = $imageUtil->getImageDir();

      /** @var FileUtil $fileUtil */
      $fileUtil = $this->getContainer()->get('misc.util.file');
      $templatePath = sprintf('%s/templates/Orders/list_order_template.xlsx', $fileUtil->getDataDir());
      $fs = new Filesystem();
      if (!$fs->exists($templatePath)) {
        throw new \RuntimeException('no template file [' . $templatePath . ']');
      }

      $logger->addDbLog($logger->makeDbLog(null, 'Excel生成', '初期化'));
      /** @var PHPExcel $objPHPExcel */
      $objPHPExcel = $this->getContainer()->get('phpexcel')->createPHPExcelObject($templatePath);

      // Set document properties
      $objPHPExcel->getProperties()->setCreator("Forest Inc.")
        ->setLastModifiedBy("Forest Inc.");

        $borderStyle = [
          'borders' => [
              'top' => ['style' => PHPExcel_Style_Border::BORDER_THIN]
            , 'right' => ['style' => PHPExcel_Style_Border::BORDER_THIN]
            , 'bottom' => ['style' => PHPExcel_Style_Border::BORDER_THIN]
            , 'left' => ['style' => PHPExcel_Style_Border::BORDER_THIN]
          ]
        ];
        $backgroundStyle = [
          'fill' => [
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => ['rgb' => 'FFFF00']
          ]
        ];
        $centerStyle = [
          'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
          'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
        ];
        $columns = [
          'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'
        ];
        $columnsSetBackground = [
          'A', 'C', 'E'
        ];
        $columnsSetWrap = [
          'D', 'E', 'F', 'H'
        ];
        $line = 2;

        $logger->addDbLog($logger->makeDbLog(null, 'Excel生成', 'シート1生成'));
        $this->updateTbOrderListExportStatus(TbOrderListExport::EXPORT_STATUS_CREATE_SHEET1, null);
        $workSheet = $objPHPExcel->setActiveSheetIndex(0);
        foreach ($columns as $col) {
          $workSheet->getStyle($col.'')->getAlignment()->applyFromArray($centerStyle);
        }
        // 折返し設定
        foreach ($columnsSetWrap as $col) {
          $workSheet->getStyle($col.'')->getAlignment()->setWrapText(true);
        }
        $count = 2;
        $shipping_number_count = 0;
        foreach($listData as $key => $itemData) {
          $workSheet->getRowDimension( $line )->setRowHeight(120);

          $workSheet->setCellValue(sprintf('A%d', $line), $itemData['description_en']);
          $workSheet->setCellValue(sprintf('B%d', $line), $itemData['description_cn']);
          $workSheet->setCellValue(sprintf('D%d', $line), $itemData['hint_ja']);
          $workSheet->setCellValue(sprintf('E%d', $line), $itemData['hint_cn']);
          $workSheet->setCellValue(sprintf('F%d', $line), 'plusnao');
          $workSheet->setCellValue(sprintf('G%d', $line), $itemData['daihyo_syohin_code']);
          $workSheet->setCellValue(sprintf('H%d', $line), $itemData['note']);
          $filenamePVI = isset($itemData['filename']) ? $itemData['filename'] : null;
          $imgDirectory = isset($itemData['folder']) ? $itemData['folder'] : null;
          $imgFileImage = isset($itemData['fileimage']) ? $itemData['fileimage'] : null;
          $imageUrl = isset($itemData['image_url']) ? $itemData['image_url'] : null;
          $imagePath = null;
          if (!is_null($filenamePVI)) {
            if ($imgDirectory && $imgFileImage) {
              $imagePath = sprintf('%s/%s/%s', $imageVariationDir, $imgDirectory, $imgFileImage);
            }
          } elseif(!is_null($imageUrl)) {
            $extension = pathinfo($imageUrl, PATHINFO_EXTENSION);
            $extension = !is_null($extension) ? $extension : 'jpg';
            $imagePath = sprintf('%s/tmp/tmp.%s', $imageDir, $extension);
            if(!file_exists(dirname($imagePath))) {
              @mkdir(dirname($imagePath), 0777, true);
            }
            copy($imageUrl, $imagePath);
          }

          if (is_null($imagePath) || !$fs->exists($imagePath)) {
            /** @var ProductImagesVariationRepository $repoColorImages */
            $repoColorImages = $this->getDoctrine()->getRepository('MiscBundle:ProductImagesVariation');
            $variationImage = $repoColorImages->findByNeSyohinSyohinCode($itemData['syohin_code']);
            if ($variationImage) {
              $imagePath = sprintf('%s/variation_images/', $imageVariationDir) . $variationImage->getFileDirPath();
            } else {
              $imagePath = sprintf('%s/%s/%s', $imageOriginalDir, $itemData['image_dir'], $itemData['image_name']);
            }
          }

          if (!is_null($imagePath) && $fs->exists($imagePath)) {
            $im = new \Imagick($imagePath);
            $im->stripImage(); // EXIF削除

            // リサイズ処理
            $height = $im->getImageHeight();
            $width  = $im->getImageWidth();
            if ($height > 300 || $width > 300) {
              $im->resizeImage(300, 300, \Imagick::FILTER_POINT, 0, true);
            }

            $objDrawing = new PHPExcel_Worksheet_MemoryDrawing();
            $objDrawing->setName($itemData['daihyo_syohin_code']);
            $objDrawing->setDescription('');
            $objDrawing->setImageResource(imagecreatefromstring($im->getImageBlob()));
            $objDrawing->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
            $objDrawing->setMimeType(PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
            $objDrawing->setHeight(120);
            $objDrawing->setOffsetX(5);
            $objDrawing->setOffsetY(5);
            $objDrawing->setCoordinates(sprintf('C%d', $line));
            $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
          }
          foreach ($columns as $col) {
            $cellName = sprintf('%s%d', $col, $line);
            $style = $workSheet->getCell($cellName)->getStyle();
            $style->applyFromArray($borderStyle);
          }
          foreach ($columnsSetBackground as $col) {
            $cellName = sprintf('%s%d', $col, $line);
            $style = $workSheet->getCell($cellName)->getStyle();
            $style->applyFromArray($backgroundStyle);
          }
          $line++;
        }

        $logger->addDbLog($logger->makeDbLog(null, 'Excel生成', 'シート2生成'));
        $this->updateTbOrderListExportStatus(TbOrderListExport::EXPORT_STATUS_CREATE_SHEET2, null);
        $workSheet = $objPHPExcel->setActiveSheetIndex(1);
        $columnsSheet2 = [
          'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'
        ];
        $line = $count = 5;
        foreach ($columnsSheet2 as $col) {
          $workSheet->getStyle($col.'')->getAlignment()->applyFromArray($centerStyle);
        }
        $shipping_number_count = 0;
        foreach($packingData as $rowData2) {
          $countCellMerge = count($rowData2);
          if ($countCellMerge > 1) {
            $numberMerge = ($count + $countCellMerge - 1);
            $workSheet->mergeCellsByColumnAndRow(0, $count, 0, $numberMerge);
            $workSheet->mergeCellsByColumnAndRow(3, $count, 3, $numberMerge);
            $workSheet->mergeCellsByColumnAndRow(4, $count, 4, $numberMerge);
            $workSheet->mergeCellsByColumnAndRow(5, $count, 5, $numberMerge);
            $workSheet->mergeCellsByColumnAndRow(6, $count, 6, $numberMerge);
            $count = $numberMerge + 1;
          } else {
            $count++;
          }
          $workSheet->getRowDimension( $line )->setRowHeight( 20 );
          $j = 0;
          $firstLine = $line;
          $totalNW = $totalMEAS = 0;

          foreach($rowData2 as $key => $itemData) {
            $weight = (isset($itemData['weight']) && !is_null($itemData['weight'])) ? $itemData['weight'] : 0;
            $width = (isset($itemData['width']) && !is_null($itemData['width'])) ? $itemData['width'] : 0;
            $height = (isset($itemData['height']) && !is_null($itemData['height'])) ? $itemData['height'] : 0;
            $depth = (isset($itemData['depth']) && !is_null($itemData['depth'])) ? $itemData['depth'] : 0;
            $quantity = (!is_null($itemData['remain_num'])) ? $itemData['remain_num'] : 0;
            $width = $width/10;
            $height = $height/10;
            $depth = $depth/10;
            $totalNW += ($weight * $quantity) / 1000;
            $totalMEAS += (($width * $height * $depth) / 1000000) * $quantity;
            if ($j == 0) {
              $workSheet->setCellValue(sprintf('A%d', $line), $itemData['shipping_number']);
              $workSheet->setCellValue(sprintf('D%d', $line), 1);
            }
            $workSheet->setCellValue(sprintf('B%d', $line), $itemData['description_en']);
            $workSheet->setCellValue(sprintf('C%d', $line), $quantity);
            $workSheet->setCellValue(sprintf('H%d', $line), $itemData['daihyo_syohin_code']);
            foreach ($columnsSheet2 as $col) {
              $cellName = sprintf('%s%d', $col, $line);
              $style = $workSheet->getCell($cellName)->getStyle();
              $style->applyFromArray($borderStyle);
            }

            if ($j == ($countCellMerge - 1)) {
              if(!is_null($itemData['checklist_nw']) && $itemData['checklist_nw'] > 0.00){
                $totalNW = $itemData['checklist_nw'];
              }
              if(!is_null($itemData['checklist_meas']) && $itemData['checklist_meas'] > 0.00){
                $totalMEAS = $itemData['checklist_meas'];
              }

              $workSheet->setCellValue(sprintf('E%d', $firstLine), $totalNW + 0.2);
              $workSheet->setCellValue(sprintf('F%d', $firstLine), $totalNW);
              $workSheet->setCellValue(sprintf('G%d', $firstLine), $totalMEAS);
            }
            $j++;
            $line++;
          }
        }

        $logger->addDbLog($logger->makeDbLog(null, 'Excel生成', 'シート3生成'));
        $this->updateTbOrderListExportStatus(TbOrderListExport::EXPORT_STATUS_CREATE_SHEET3, null);
        $workSheet = $objPHPExcel->setActiveSheetIndex(2);
        $workSheet->setCellValue('D2', date('Y.m.d'));
        $columnsSheet3 = [
          'A', 'B', 'C', 'D', 'E', 'F'
        ];
        $backgroundStyle3 = [
          'fill' => [
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => ['rgb' => 'DCE6F1']
          ]
        ];

        foreach ($columnsSheet3 as $col) {
          $workSheet->getStyle($col.'')->getAlignment()->applyFromArray($centerStyle);
        }
        $line = $count = 15;
        $shipping_number_count = 0;
        foreach($invoiceData as $key => $itemData) {
          $quantity = (!is_null($itemData['remain_num'])) ? $itemData['remain_num'] : 0;
          $up = round($itemData['cost'] / $rateUSD, 2);
          $total = $quantity * $up;
          $descriptionArray = array();
          
          $itemData['description_en'] = str_replace('＆','&',$itemData['description_en']);
          // description_en に & が含まれるのは複数セットの商品。Invoiceには商品ごとに、価格も分割して出力する。
          // & が含まれる商品
          if(!empty($itemData['description_en']) && strpos($itemData['description_en'],'&') !== false){
            $descriptionArray = explode('&', $itemData['description_en']);
            $tempUp = floor($up * 100.0 / count($descriptionArray)) / 100;
            $diffUp = $up - ($tempUp * count($descriptionArray));
            
            foreach($descriptionArray as $descKey => $description){
              if($descKey === 0){
                $workSheet->setCellValue(sprintf('A%d', $line), trim($description));
                $workSheet->setCellValue(sprintf('B%d', $line), $quantity);
                $workSheet->setCellValue(sprintf('C%d', $line), 'pcs');
                $workSheet->setCellValue(sprintf('D%d', $line), $tempUp + $diffUp);
                $workSheet->setCellValue(sprintf('E%d', $line), $quantity * ($tempUp + $diffUp));
                $workSheet->setCellValue(sprintf('F%d', $line), $itemData['daihyo_syohin_code']);
                $workSheet->getStyle('E')->getNumberFormat()->applyFromArray(
                    array(
                        'code' => '$#,##0.00;$-#,##0.00'
                    )
                );
              } else {
                $workSheet->setCellValue(sprintf('A%d', $line), trim($description));
                $workSheet->setCellValue(sprintf('D%d', $line), $tempUp);
                $workSheet->setCellValue(sprintf('E%d', $line), $quantity * $tempUp);
                $workSheet->setCellValue(sprintf('F%d', $line), $itemData['daihyo_syohin_code']);
                $workSheet->getStyle('E')->getNumberFormat()->applyFromArray(
                    array(
                        'code' => '$#,##0.00;$-#,##0.00'
                    )
                );
              }
              foreach ($columnsSheet3 as $col) {
                $cellName = sprintf('%s%d', $col, $line);
                $style = $workSheet->getCell($cellName)->getStyle();
                $style->applyFromArray($borderStyle);
                if($line % 2 === 1){
                  $style->applyFromArray($backgroundStyle3);
                }
              }
              $line++;
            }
          // & が含まれない商品
          } else {
            $workSheet->setCellValue(sprintf('A%d', $line), $itemData['description_en']);
            $workSheet->setCellValue(sprintf('B%d', $line), $quantity);
            $workSheet->setCellValue(sprintf('C%d', $line), 'pcs');
            $workSheet->setCellValue(sprintf('D%d', $line), $up);
            $workSheet->setCellValue(sprintf('E%d', $line), $total);
            $workSheet->setCellValue(sprintf('F%d', $line), $itemData['daihyo_syohin_code']);
            $workSheet->getStyle('E')->getNumberFormat()->applyFromArray(
                array(
                    'code' => '$#,##0.00;$-#,##0.00'
                )
            );
            foreach ($columnsSheet3 as $col) {
              $cellName = sprintf('%s%d', $col, $line);
              $style = $workSheet->getCell($cellName)->getStyle();
              $style->applyFromArray($borderStyle);
              if($line % 2 === 1){
                $style->applyFromArray($backgroundStyle3);
              }
            }
            $line++;
          }
        }
        $cellSum = sprintf('E%d', $line);
        $workSheet->setCellValue($cellSum, sprintf('=SUM(E15:E%d)', $line - 1));

      $pathFileName = sprintf('%s/excel/%s/%s', $fileUtil->getDataDir(), $agent->getLoginName(), $this->fileName);
      $this->orderListExport->setFile(str_replace($fileUtil->getDataDir(), '', $pathFileName));
      if(!file_exists(dirname($pathFileName))) {
        @mkdir(dirname($pathFileName), 0777, true);
      }
      $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
      $objWriter->save($pathFileName);
      $this->pathFileExcelName = $pathFileName;
  }

  public function setDataForSheetOne($rowData, $workSheet, $line)
  {
    $workSheet->getRowDimension( $line )->setRowHeight( 90 );
    // A: 日付
    $workSheet->setCellValue(sprintf('A%d', $line), $rowData['shipping_number']);
    $workSheet->setCellValue(sprintf('B%d', $line), $rowData['description_en']);
    $workSheet->setCellValue(sprintf('C%d', $line), $rowData['remain_num']);
    $workSheet->setCellValue(sprintf('D%d', $line), 'imageSKU');
    $workSheet->setCellValue(sprintf('E%d', $line), $rowData['hint_cn']);
    $workSheet->setCellValue(sprintf('F%d', $line), $rowData['description_cn']);
    $workSheet->setCellValue(sprintf('G%d', $line), 'plusnao');
    $workSheet->setCellValue(sprintf('H%d', $line), 'CODE');
    return $workSheet;
  }

  /**
   * JobRequest メッセージ更新
   * @param $data
   */
  private function updateJobRequestMessage($data)
  {
    if (!$this->jobRequest) {
      return;
    }

    $this->jobRequest->setInfoMerge($data);
    $em = $this->getDoctrine()->getManager('main');
    $em->flush();

    usleep(100000); // 0.1秒休憩。
  }

  /**
   * 指定されたデータでTbOrderListExportを生成し、このクラスのプロパティに紐づけます。
   * ステータスは処理開始となります。
   * @param unknown $data
   */
  private function insertDataToOrderListExport($data)
  {
      $orderListExport = new TbOrderListExport();
      $orderListExport->setVendor($data['vendor']);
      $orderListExport->setExportStatus(TbOrderListExport::EXPORT_STATUS_START);
      $orderListExport->setTotalProducts($data['total_products']);
      $orderListExport->setAccount($data['account']);
      $orderListExport->setIsForestStaff($data['isForestStaff']);
      $orderListExport->setIsClient($data['isClient']);
      $orderListExport->setIsYahooAgent($data['isYahooAgent']);
      $orderListExport->setLastDownload($data['last_download']);
      $orderListExport->setFile($data['file']);
      $orderListExport->setMessage($data['message']);
      $orderListExport->setCreated(new \DateTime());
      $orderListExport->setUpdated(new \DateTime());
      $this->em->persist($orderListExport);
      $this->orderListExport = $orderListExport;
      $this->em->flush();
  }

  /**
   * TbOrderListExportのステータスとメッセージを更新します。
   * ここまでに溜まった更新情報も同時にDBへ反映します。
   * @param unknown $status
   * @param string $message
   */
  private function updateTbOrderListExportStatus($exportStatus, $message = '') {
    $this->orderListExport->setExportStatus($exportStatus);
    $this->orderListExport->setMessage($message);
    $this->em->flush();
  }

}


