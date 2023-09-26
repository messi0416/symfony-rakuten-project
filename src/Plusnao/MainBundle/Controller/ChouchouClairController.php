<?php

namespace Plusnao\MainBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use MiscBundle\Entity\ChouchouClairProduct;
use MiscBundle\Entity\TbRakutenReviews;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\StringUtil;
use Plusnao\MainBundle\Form\Type\ChouchouClairStockListDownloadCsvType;
use Plusnao\MainBundle\Form\Type\ChouchouClairStockListSearchHiddenType;
use Plusnao\MainBundle\Form\Type\ChouchouClairStockListUploadCsvType;
use Plusnao\MainBundle\Form\Type\SalesRankingSearchType;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Plusnao\MainBundle\Form\Type\ChouchouClairStockListSearchType;

use MiscBundle\Entity;
use Symfony\Component\HttpFoundation\Response;

class ChouchouClairController extends BaseController
{
  const LIST_LIMIT = 200;

  /**
   * トップ画面
   */
  public function indexAction(Request $request)
  {
    $account = $this->getLoginUser();
    $count = 0;
    $data = [];

    $form = $this->createForm(new ChouchouClairStockListSearchType(), null, ['method' => 'GET']);
    $form->handleRequest($request);

    if ($form->isValid()) {
      /** @var Entity\Repository\ChouchouClairProductRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:ChouchouClairProduct');
      $count = $repo->getProductCount($form->getData());
      $data = $repo->getProducts($form->getData(), self::LIST_LIMIT);
    }

    // アップロードフォーム
    $uploadForm = $this->createForm(new ChouchouClairStockListUploadCsvType())->createView();

    // ダウンロードフォーム
    $type = new ChouchouClairStockListDownloadCsvType();
    // 最終取得修正日時を取得
    /** @var Entity\Repository\ChouchouClairProductLogRepository $repoLog */
    $repoLog = $this->getDoctrine()->getRepository('MiscBundle:ChouchouClairProductLog');
    $downloadedMaxStockModifiedLog = $repoLog->getDownloadedMaxStockModifiedLog();
    if ($downloadedMaxStockModifiedLog) {
      $type->setDownloadedMaxStockModified($downloadedMaxStockModifiedLog->getLastStockModified());
    }

    $downloadForm = $this->createForm($type)->createView();



    // 画面表示
    return $this->render('PlusnaoMainBundle:ChouchouClair:index.html.twig', [
        'account' => $account
      , 'searchForm' => $form->createView()
      , 'uploadForm' => $uploadForm
      , 'downloadForm' => $downloadForm
      , 'totalCount' => $count
      , 'dataCount' => count($data)
      , 'data' => json_encode($data)
      , 'submitted' => $form->isSubmitted()
    ]);
  }

  /**
   * CSVアップロード・商品リスト更新処理
   */
  public function uploadCsvAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $account = $this->getLoginUser();

    // データ取得処理
    $form = $this->createForm(new ChouchouClairStockListUploadCsvType());
    $form->handleRequest($request);

    if ($form->isValid()) {

      $data = $form->getData();
      /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
      $file = $data->getUploaded();

      $logger->info(print_r($file, true));
      try {
        if (!$file || $file->getMimeType() !== 'application/zip') {
          throw new \RuntimeException('アップロードされたファイルがzipファイルではありません。');
        }

        $now = new \DateTime();

        /** @var FileUtil $fileUtil */
        $fileUtil = $this->get('misc.util.file');
        $dir = $fileUtil->getWebCsvDir() . '/ChouchouClair/Import';
        $filename = sprintf('import_%s.zip', $now->format('YmdHis'));
        $path = sprintf('%s/%s', $dir, $filename);

        $file->move($dir, $filename);
        $logger->info($path);

        // インポート処理
        $commandArgs = [
            'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          , $path // インポートファイル
          , sprintf('--account=%d', $account->getId())
          , sprintf('--account-type=%s', $account->getAccountType())
        ];

        $input = new ArgvInput($commandArgs);
        $output = new ConsoleOutput();

        $command = $this->get('plusnao_main.import_chouchou_clair_product');
        $exitCode = $command->run($input, $output);
        $logger->info('シュシュクレール在庫連携 CSVインポート done. [' . $exitCode . ']');

        if ($exitCode != 0) { // コマンドが異常終了した
          $message = 'CSVファイルの取込に失敗しました。';
          if ($exitCode == 10) {
            $message = 'ヘッダ行が正しくないCSVファイルが含まれています。処理を中断しました。';
          }
          throw new \RuntimeException($message);
        }

        // アップロード履歴保存
        $log = new Entity\ChouchouClairProductLog();
        $log->setLogDate($now);
        $log->setUserType($account->getAccountType());
        $log->setUser($account->getId());
        $log->setOperation('CSVアップロード');
        $log->setTarget($path);

        $em = $this->getDoctrine()->getManager('main');
        $em->persist($log);
        $em->flush();

        $this->addFlash('success', 'CSVファイルのアップロードおよび取込処理を完了しました。');
        $logger->info('success');

      } catch (\Exception $e) {
        $this->addFlash('danger', $e->getMessage());
        $logger->error($e->getMessage());
      }

    } else {
      $this->addFlash('warning', 'CSVファイルのアップロードに失敗しました。');
      $logger->info('error');

    }

    // 完了
    $searchForm = $this->createForm(new ChouchouClairStockListSearchType());

    return $this->redirectToRoute(
        'plusnao_chouchou_clair'
      , [ $searchForm->getName() => $form->getData()->getSearchConditions() ]
    );

  }

  /**
   * CSVダウンロード処理
   */
  public function downloadCsvAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $account = $this->getLoginUser();

    // データ取得処理
    $form = $this->createForm(new ChouchouClairStockListDownloadCsvType());
    $form->handleRequest($request);
    $conditions = $form->getData();
    $logger->info(print_r($conditions, true));

    try {
      if ($form->isValid()) {

        // ダウンロードファイル出力
        $now = new \DateTime();

        /** @var Entity\Repository\ChouchouClairProductRepository $repo */
        $repo = $this->getDoctrine()->getRepository('MiscBundle:ChouchouClairProduct');
        $stmt = $repo->getDownloadCsvDataStmt($conditions);

        // 出力
        if (!$stmt->rowCount()) {
          $logger->info("シュシュクレール在庫連携 件数が0のためファイルは作成しませんでした。");
          throw new \RuntimeException('件数が0のためファイルは作成しませんでした。');
        }

        /** @var FileUtil $fileUtil */
        $fileUtil = $this->get('misc.util.file');
        $dir = $fileUtil->getWebCsvDir() . '/ChouchouClair/Export';
        $filename = sprintf('export_%s.csv', $now->format('YmdHis'));
        $path = sprintf('%s/%s', $dir, $filename);

        $logger->info($path);

        /** @var StringUtil $stringUtil */
        $stringUtil = $this->get('misc.util.string');

        // ヘッダ
        $headers = $repo->getCsvFields();
        $headerLine = $stringUtil->convertArrayToCsvLine($headers);
        $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

        // データ
        $num = 0;
        $lastStockModified = null;

        $fp = fopen($path, 'wb');
        fputs($fp, $headerLine);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
          $line = $stringUtil->convertArrayToCsvLine($row, $headers);
          $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
          fputs($fp, $line);

          if (strlen($row['stock_modified'])) {
            $last = new \DateTime($row['stock_modified']);
            if (!$lastStockModified || $lastStockModified < $last) {
              $lastStockModified = $last;
            }
          }

          $num++;
        }
        fclose($fp);

        $logger->info("シュシュクレール在庫連携 CSV出力 {$path}: $num 件");

        // ダウンロード履歴保存
        $log = new Entity\ChouchouClairProductLog();
        $log->setLogDate($now);
        $log->setUserType($account->getAccountType());
        $log->setUser($account->getId());
        $log->setOperation('CSVダウンロード');
        $log->setTarget($path);
        $log->setLastStockModified($lastStockModified);

        $em = $this->getDoctrine()->getManager('main');
        $em->persist($log);
        $em->flush();

        $response = new Response();
        $response->headers->set('Content-type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="fixed_stock_%s.csv";', $now->format('YmdHis')));
        $response->sendHeaders();
        $response->setContent(file_get_contents($path));

        return $response;

      } else {
        throw new \RuntimeException('ダウンロードする日付の範囲が正しく指定されていません。');
      }

    } catch (\Exception $e) {

      // エラー時
      $logger->error($e->getMessage());
      $this->addFlash('danger', $e->getMessage());

      $searchForm = $this->createForm(new ChouchouClairStockListSearchType());
      return $this->redirectToRoute(
        'plusnao_chouchou_clair'
        , [ $searchForm->getName() => $form->getData()->getSearchConditions() ]
      );
    }
  }

  /**
   * 在庫更新処理
   */
  public function updateStockAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $account = $this->getLoginUser();

    $params = $request->request->all();

    $result = [
        'valid' => 0
      , 'message' => ''
      , 'data' => null
    ];

    try {
      if (isset($params['code']) && isset($params['branch_code']) && isset($params['action']) && $account) {

        /** @var Entity\Repository\ChouchouClairProductRepository $repo */
        $repo = $this->getDoctrine()->getRepository('MiscBundle:ChouchouClairProduct');
        /** @var ChouchouClairProduct $data */
        $data = $repo->findOneBy([
            'code' => $params['code']
          , 'branch_code' => $params['branch_code']
        ]);

        if (!$data) {
          throw new \RuntimeException('該当のデータが取得できませんでした。');
        }

        if ($params['action'] == 'to_zero') {
          $data->setStock(0);
          $data->setModifiedUser($account->getId());
          $data->setModifiedUserType($account->getAccountType());
          $data->setStockModified(new \DateTime());

        } else if ($params['action'] == 'undo') {
          $data->setStock($data->getPreStock());
          $data->setModifiedUser(0);
          $data->setModifiedUserType('');
          $data->setStockModified(null);

        } else {
          throw new \RuntimeException('パラメータが正しくありません。(action)');
        }

        $em = $this->getDoctrine()->getManager('main');
        $em->flush();

        $result['valid'] = 1;
        $result['data'] = $data->toScalarArray();

      } else {
        throw new \RuntimeException('パラメータが正しくありません。');
      }

    } catch (\Exception $e) {
      $result['valid'] = 0;
      $result['message'] = $e->getMessage();
    }

    $logger->info(print_r($result, true));

    return new JsonResponse($result);
  }

}

