<?php

namespace Plusnao\MainBundle\Controller;

use Knp\Bundle\SnappyBundle\Snappy\LoggableGenerator;
use MiscBundle\Entity\Repository\BaseRepository;
use MiscBundle\Entity\Repository\SymfonyUserClientRepository;
use MiscBundle\Entity\Repository\TbPlusnaoproductdirectoryRepository;
use MiscBundle\Entity\Repository\TbProductSalesAccountRepository;
use MiscBundle\Entity\Repository\TbRakutenCategoryForSalesRankingRepository;
use MiscBundle\Entity\Repository\TbSalesDetailAnalyzeRepository;
use MiscBundle\Entity\SymfonyUserYahooAgent;
use MiscBundle\Entity\TbProductCode;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\ImageUtil;
use MiscBundle\Util\StringUtil;
use MiscBundle\Util\WebAccessUtil;
use mysql_xdevapi\Exception;
use Plusnao\MainBundle\Form\Type\SalesRankingSearchType;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends BaseAgentController
{
  /**
   * ログイン画面
   * @param Request $request
   * @param string $agentName
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
   */
  public function loginAction(Request $request)
  {
    $agentName = $request->get('agentName');
    // var_dump($request->cookies->get('agentName'));
    // exit;

    // cookieをセットし直してリダイレクト
    if (strlen($agentName)) {

      if ($request->cookies->get('agentName') != $agentName) {
        $response = $this->redirectToRoute('plusnao_agent_login', [ 'agentName' => $agentName ]);
        $response->headers->setCookie(new Cookie(
          'agentName'
          , $agentName
          , 0
          , '/'
          , $this->getParameter('auth_cookie_host')
          , (new \DateTime())->modify('+ 10 year')
        ));

        return $response;
      }

    } else {
      $agentName = $request->cookies->get('agentName');
    }
    if (!strlen($agentName)) {
      return new Response('Log in from valid url, please.');
    }

    $isAgentLogin = ($agentName !== SymfonyUserClientRepository::NON_AGENT_LOGIN_NAME); // 初期画面の遷移先の分岐のみ

    /** @var \Symfony\Component\Security\Http\Authentication\AuthenticationUtils $authenticationUtils */
    $authenticationUtils = $this->get('security.authentication_utils');
    $error = $authenticationUtils->getLastAuthenticationError();
    $lastUsername = $authenticationUtils->getLastUsername();

    return $this->render('PlusnaoMainBundle:Default:login.html.twig', array(
        'last_username' => $lastUsername
      , 'error' => $error

      , 'isAgentLogin' => $isAgentLogin
    ));
  }

  // トップページ
  public function indexAction()
  {
    $account = $this->getLoginUser();

    // Yahoo代理店
    if ($account && $account->isYahooAgent()) {
      /** @var SymfonyUserYahooAgent $account */
      return $this->redirectToRoute('yahoo_homepage', ['shopCode' => $account->getShopCode()]);

    // 注残一覧
    } else if ($account && $account->isClient() && $this->getAgent()) {
      return $this->redirectToRoute('plusnao_vendor_order_list', ['_locale' => 'ja', 'agentName' => $this->agent->getLoginName() ]);

    // その他クライアント
    } else {
      return $this->redirectToRoute('plusnao_sales_ranking');
    }
  }

  /**
   * 売れ筋ランキング 表示
   */
  public function salesRankingAction()
  {
    $account = $this->getLoginUser();

    // カテゴリ取得
    /** @var TbRakutenCategoryForSalesRankingRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRakutenCategoryForSalesRanking');
    $categories = $repo->getAllForPullDown();

    // 商品売上担当者取得
    /** @var TbProductSalesAccountRepository $aRepo */
    $aRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesAccount');
    $productSalesAccounts = $aRepo->getUserList();

    // 画面表示
    return $this->render('PlusnaoMainBundle:Default:sales_ranking.html.twig', [
      'account' => $account,
      'agent' => $this->getAgent(),
      'categories' => json_encode($categories),
      'productSalesAccounts' => json_encode($productSalesAccounts),
      'bigCategories' => json_encode(array_keys($categories)),
    ]);
  }

  /**
   * 売れ筋ランキング 検索
   * @param Request $request
   * @return JsonResponse
   */
  public function salesRankingSearchAction(Request $request)
  {
    $result = [
      'status' => 'ok',
      'message' => null,
      'salesRankingList' => [],
    ];

    try {
      $requestData = $request->get('requestData');
      /** @var TbSalesDetailAnalyzeRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetailAnalyze');
      $account = $this->getLoginUser();
      $addSireName = $account->isForestStaff(); // 仕入先名を取得するかどうか
      $salesRankingList = $repo->getSalesRankingList($requestData, $addSireName);
      $result['salesRankingList'] = $salesRankingList;
    } catch (\Exception $e) {
      /** @var BatchLogger $logger */
      $logger = $this->get('misc.util.batch_logger');
      $logger->error('売れ筋ランキング 検索でエラー発生:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * ラベルシール印刷用PDF出力
   */
  public function downloadLabelPrintPdfAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    try {
      $now = new \DateTimeImmutable();

      /** @var LoggableGenerator $pdf */
      $pdf = $this->get('knp_snappy.pdf');
      $options = [
          'encoding' => 'utf-8'
        , 'page-size' => 'A4'
        , 'margin-top' => '0mm'
        , 'margin-bottom' => '0mm'
        , 'margin-left' => '0mm'
        , 'margin-right' => '0mm'
      ];
      foreach($options as $k => $v) {
        $pdf->setOption($k, $v);
      }

      $data = $request->get('data', []);
      $logger->info('data : ' . (is_array($data) ? count($data) : 'null'));

      // バーコードSVG作成
      $barcodeList = [];
      $barcodeSVG = [];

      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');
      /** @var ImageUtil $imageUtil */
      $imageUtil = $this->get('misc.util.image');

      // バーコード用コードを取得（1件ずつガチャコンガチャコン取得）
      /** @var BaseRepository $repoCode */
      $repoCode = $this->getDoctrine()->getRepository('MiscBundle:TbProductCode');
      foreach($data as $row) {
        $syohinCode = $row['productCode'];

        /** @var TbProductCode $code */
        $code = $repoCode->findOneBy(['ne_syohin_syohin_code' => $syohinCode]);
        if ($code) {
          $barcode = $stringUtil->convertNumToJan13($code->getId());
          $barcodeList[$syohinCode] = $barcode;
          $barcodeSVG[$syohinCode] = $imageUtil->getBarcodeSVG($barcode, 'EAN13', false, 1.2, 24);
        }
      }

      // 注残数分に水増し。
      $tmp = [];
      foreach($data as $row) {
        for ($i = 0; $i < $row['remainNum']; $i++) {
          $tmp[] = $row;
        }
      }
      $data = $tmp;

//      // FOR DEBUG
//      $testSyohinCode = [
//          'BS300BS301BS302BS303-230-BS301'
//        , 'swim-hjklapx-XXXL-8010RBLUxGR'
//        , 'swim-6qxn6ly1-MENXXXL-NAGABLU'
//        , 'hw-0633-0634-0635-0636-No1-WH-HOGE-MOGE'
//        , 'HL-9278-2-h-225KUROKOPK-NASHI'
//        , 'HL-9278-2-i-245KUROKOBK-GRYJ'
//        , 'swim-helzqxu-M-8615ORxBLUDOT'
//        , 'hw-0633-0634-0635-0636-No2-WH'
//      ];
//      foreach($testSyohinCode as $i => $code) {
//        if (isset($data[$i])) {
//          $data[$i]['productCode'] = $code;
//        }
//      }

      // ページ行・列に整形
      $fixedData = [];
      $page = 1;
      $maxLine = 10;
      $maxCol = 4;

      // 用紙印刷余白指定によるスキップ数
      $printSkipNum = $request->get('print_start_position', 1) - 1;

      while (count($data)) {
        $fixedData[$page] = [];
        for ($line = 1; $line <= $maxLine; $line++) {
          $fixedData[$page][$line] = [];
          for ($col = 1; $col <= $maxCol; $col++) {

            if ($printSkipNum > 0) {
              $fixedData[$page][$line][$col] = null;
              $printSkipNum--;
            } else {
              $fixedData[$page][$line][$col] = count($data) ? array_shift($data) : null;
            }
          }
        }

        $page++;
      }

      $html = $this->renderView('PlusnaoMainBundle:Default:parts/label-print-pdf.html.twig', [
          'data' => $fixedData
        , 'barcodeSVG' => $barcodeSVG
      ]);

      // FOR DEBUG
      $tmpDir = dirname($this->get('kernel')->getRootDir()) . '/data/dev_test';
      file_put_contents(sprintf('%s/label_pdf_%s.html', $tmpDir, (new \DateTime())->format('YmdHis')), $html);

      $fileName = sprintf('label_%s.pdf', $now->format('YmdHis'));
      $pdfGenerator = $this->get('knp_snappy.pdf');
      $pdfGenerator->setTimeout(1800);
      
      return new Response(
          $pdfGenerator->getOutputFromHtml($html)
        , 200
        , array(
            'Content-Type'          => 'application/pdf'
          , 'Content-Disposition'   => sprintf('attachment; filename="%s"', $fileName)
        )
      );

    } catch (\Exception $e) {
      // エラー時
      $logger->error("ラベル印刷でエラー発生:" . $e->getMessage() . ':' . $e->getTraceAsString());
      $this->addFlash('danger', 'label pdf error.');

      if ($request->get('redirect')) {
        return $this->redirect($request->get('redirect'));
      } else {
        return $this->redirectToRoute('plusnao_label_pdf_list');
      }
    }

  }


  /**
   * 商品ロケーション詳細画面へのリダイレクト
   * ※ ドメイン starlight.plusnao.co.jp を隠すためにリダイレクト。
   * つまりは、ここで、starlightへのログインチェックをしなければいけない。（starlightへ飛んでチェックしてはいけない）
   * あまり意味のない隠蔽だが、やるからにはちゃんとやる
   * @param $syohinCode
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
   */
  public function redirectProductLocationDetailAction($syohinCode)
  {
    $account = $this->getLoginUser();
    if (!$account || !$account->isForestStaff()) {
      return new Response('ログインが必要です。');
    }

    return $this->redirectToRoute('location_product_detail', [ 'syohinCode' => $syohinCode ]);

  }



}
