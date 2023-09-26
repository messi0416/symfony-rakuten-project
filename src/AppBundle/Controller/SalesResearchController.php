<?php

namespace AppBundle\Controller;

use AppBundle\Form\Type\SalesResearchCostRateTermType;
use AppBundle\Form\Type\SalesResearchCostRateUpdateType;
use AppBundle\Form\Type\SalesResearchVendorStockoutTermType;
use AppBundle\Form\Type\UncombinedOrderListSearchType;
use MiscBundle\Entity\Repository\TbSalesDetailAnalyzeRepository;
use MiscBundle\Entity\Repository\TbVendorCostRateListSettingRepository;
use MiscBundle\Entity\Repository\TbVendorCostRateLogRepository;
use MiscBundle\Entity\Repository\TbRakutenSearchKeywordRankingRepository;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use DateTimeInterface;

class SalesResearchController extends BaseController
{
  /**
   * 仕入先別原価率・粗利 一覧表
   */
  public function costRateListAction(Request $request)
  {
    $form = $this->createForm(new SalesResearchCostRateTermType());

    // 設定値取得
    /** @var TbVendorCostRateListSettingRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbVendorCostRateListSetting');
    $setting = $repo->getCurrentSetting();

    // 画面表示
    return $this->render('AppBundle:SalesResearch:vendor_cost_rate.html.twig', array(
        'termForm' => $form->createView()
      , 'setting' => $setting
    ));
  }

  /**
   * 仕入先別原価率 一括更新
   */
  public function costRateListUpdateAction(Request $request)
  {
    $formType = new SalesResearchCostRateUpdateType();
    $formName = $formType->getName();
    if ($formData = $request->get($formName)) {

      // ひとまずベタベタにSQLで更新処理（Entityとか後回し）
      /** @var \Doctrine\DBAL\Connection $db */
      $db = $this->getDoctrine()->getConnection();
      $sql = " UPDATE tb_vendormasterdata ";
      $sql .= " SET cost_rate = :costRate";
      $sql .= " WHERE sire_code = :sireCode ";
      $sql .= " LIMIT 1 "; // 念のため
      $stmt = $db->prepare($sql);

      foreach ($formData as $sireCode => $costRate) {
        $stmt->bindValue(':costRate', intval($costRate), \PDO::PARAM_INT);
        $stmt->bindValue(':sireCode', $sireCode, \PDO::PARAM_STR);
        $stmt->execute();
      }

      $this->addFlash('info', '一括更新を完了しました。');
    } else {
      $this->addFlash('warning', '一括更新情報がありません。');
    }

    return $this->redirectToRoute('sales_research_cost_rate_list');
  }

  /**
   * 仕入先別原価率一覧 データ取得処理
   * @param Request $request
   * @param $mode
   * @return JsonResponse
   */
  public function costRateListLoadDataAction(Request $request, $mode)
  {
    $logger = $this->get('misc.util.batch_logger');

    $data = [
        'message' => null
      , 'status' => 'ok'
      , 'a' => []
      , 'b' => []
      , 'costRateChanges' => null
    ];

    try {
      $form = $this->createForm(new SalesResearchCostRateTermType());

      // データ集計取得処理
      if ($request->get($form->getName())) {
        $form->submit($request);

        if ($form->isValid()) {
          /** @var TbVendorCostRateLogRepository $repo */
          $repo = $this->getDoctrine()->getRepository('MiscBundle:TbVendorCostRateLog');
          $data['a'] = $repo->calculateCostRateHistoryList($form['dateAStart']->getData(), $form['dateAEnd']->getData(), $mode);
          $data['b'] = $repo->calculateCostRateHistoryList($form['dateBStart']->getData(), $form['dateBEnd']->getData(), $mode);

          // 原価率変動累積値 取得
          if ($mode == 'costRate') {
            $costRateChangesA = $repo->getCostRateChangeSum($form['dateAStart']->getData(), $form['dateAEnd']->getData());
            $costRateChangesB = $repo->getCostRateChangeSum($form['dateBStart']->getData(), $form['dateBEnd']->getData());

            // 最大値取得のためソート
            arsort($costRateChangesA, SORT_NUMERIC);
            arsort($costRateChangesB, SORT_NUMERIC);

            $costRateChanges = [
              'a' => [
                'max' => round(reset($costRateChangesA), 2)
                , 'average' => $costRateChangesA ? round(array_sum($costRateChangesA) / count($costRateChangesA), 2) : 0
              ]
              , 'b' => [
                'max' => round(reset($costRateChangesB), 2)
                , 'average' => $costRateChangesB ? round(array_sum($costRateChangesB) / count($costRateChangesB), 2) : 0
              ]
            ];

            $data['costRateChanges'] = $costRateChanges;
          }

        } else {
          $logger->info($form->getErrors()->current()->getMessage());
          throw new \RuntimeException('期間の指定が正しくありません。');
        }
      } else {
        throw new \RuntimeException('期間の指定が正しくありません。');
      }

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $data['status'] = 'ng';
      $data['message'] = $e->getMessage();
    }

    return new JsonResponse($data);
  }


  /**
   * 楽天キーワード一覧 初期表示
   */
  public function rakutenKeywordRankingIndexAction(Request $request)
  {
    return $this->render('AppBundle:SalesResearch:RakutenKeywordRanking/index.html.twig', array());
  }

  /**
   * 楽天キーワード一覧 日付比較検索
   */
  public function rakutenKeywordDateComparisonSearchAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok',
      'message' => null
    ];
    try {
      $conditions = $request->get('conditions');
      // 入力チェック
      $conditions['target_date'] = isset($conditions['target_date']) ? trim($conditions['target_date']) : '';
      $conditions['diff_target_date'] = isset($conditions['diff_target_date']) ? trim($conditions['diff_target_date']) : '';
      $conditions['limit'] = isset($conditions['limit']) ? trim($conditions['limit']) : '';
      if (! strlen($conditions['target_date'])) {
        throw new \RuntimeException('検索対象日を入力してください。');
      }
      if (! strlen($conditions['diff_target_date'])) {
        throw new \RuntimeException('比較対象日を入力してください。');
      }
      if (! strlen($conditions['limit'])) {
        throw new \RuntimeException('検索件数を選択してください。');
      }
      // 日付変換
      if (isset($conditions['target_date']) && strlen($conditions['target_date'])) {
        $conditions['target_date'] = (new \DateTimeImmutable($conditions['target_date']))->setTime(0, 0, 0);
      }
      if (isset($conditions['diff_target_date']) && strlen($conditions['diff_target_date'])) {
        $conditions['diff_target_date'] = (new \DateTimeImmutable($conditions['diff_target_date']))->setTime(0, 0, 0);
      }
      // 一覧情報取得
      /** @var TbRakutenSearchKeywordRankingRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRakutenSearchKeywordRanking');
      $targetDateList = $repo->getRakutenKeywordTargetDayList($conditions['target_date'] ,$conditions['limit']); // 検索対象日リスト
      $diffTargetDateList = $repo->getRakutenKeywordTargetDayList($conditions['diff_target_date'] ,$conditions['limit']); // 比較対象日リスト
      if (count($targetDateList) !== 0 && count($diffTargetDateList) === 0) {
        // 検索対象日のデータしか存在しない場合、一覧データは検索対象日リストのみ返却
        $list = $targetDateList;
        $result['resultListStatus'] = 'TARGET_DATE_ONLY';
      } else if (count($targetDateList) === 0 && count($diffTargetDateList) !== 0) {
        // 比較対象日のデータしか存在しない場合、一覧データは比較対象日リストのみ返却
        $list = $diffTargetDateList;
        $result['resultListStatus'] = 'DIFF_TARGET_DATE_ONLY';
      } else {
        // 検索対象日リストも比較対象日リストも存在する場合は、比較した結果のリストを返却
        $list = $repo->getRakutenKeywordDateComparisonSearchList($conditions);
        $result['resultListStatus'] = 'ALL';
      }
      $result['list'] = $list;
    } catch (\RuntimeException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 楽天キーワード一覧 キーワード検索
   */
  public function rakutenKeywordRankingKeywordSearchAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok'
      , 'message' => null
    ];
    try {
      $conditions = $request->get('conditions');
      // 入力チェック
      $conditions['target_date_from'] = isset($conditions['target_date_from']) ? trim($conditions['target_date_from']) : '';
      $conditions['target_date_to'] = isset($conditions['target_date_to']) ? trim($conditions['target_date_to']) : '';
      $conditions['keyword'] = isset($conditions['keyword']) ? trim($conditions['keyword']) : '';
      if (! strlen($conditions['target_date_from'])) {
        throw new \RuntimeException('検索期間FROMを入力してください。');
      }
      if (! strlen($conditions['target_date_to'])) {
        throw new \RuntimeException('検索期間TOを入力してください。');
      }
      if (! strlen($conditions['keyword'])) {
        throw new \RuntimeException('キーワードを入力してください。');
      }
      // 表示日数間隔を算出
      // 差分を求める
      $targetDateFrom = new \DateTime($conditions['target_date_from']);
      $targetDateTo = new \DateTime($conditions['target_date_to']);
      if ($targetDateFrom->diff($targetDateTo)->invert === 1) {
        throw new \RuntimeException('検索期間TOには検索期間FROMと同日または未来日を入力してください。');
      }
      $diff = $targetDateFrom->diff($targetDateTo)->days + 1; // diff関数では同日だと0が返却される。今回は1日間でカウントしたいので1日足す
      // 表示日数間隔計算方法：検索期間FROMと検索期間TOの日数差に対して、31(表示日数上限)で割る(小数点以下切り上げ)
      $interval = ceil($diff / 31);
      if ($interval >= 2) {
        $result['infoMessage'] ='期間が'.$diff.'日だったため、'.$interval.'日間隔で表示しています';
      }
      // 検索期間TOを含め、検索期間TOから日数差を逆算した日付リストを生成
      $targetDateList = [];
      $targetDate = $targetDateTo;
      $targetDateList[] = $targetDate->format('Y-m-d'); // 検索期間TOはあらかじめセット
      for ($i = 0; $i < ceil($diff / $interval); $i++) {
        $targetDate = $targetDate->modify('- '.$interval.' day')->setTime(0, 0, 0);
        if ($targetDateFrom->diff($targetDate)->invert === 1) {
          // 検索期間FROMより過去日になった時点で処理終了
          break;
        }
        $targetDateList[] = $targetDate->format('Y-m-d'); 
      }
      $targetDateList = array_reverse($targetDateList); // 検索期間FROMから開始に入れ替える
      $conditions['target_date_list'] = $targetDateList;
      /** @var TbRakutenSearchKeywordRankingRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRakutenSearchKeywordRanking');
      $list = $repo->getRakutenKeywordRankingKeywordSearchList($conditions);
      $result['targetDateList'] = $targetDateList;
      $result['list'] = $list;
    } catch (\RuntimeException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error($e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);

  }

  /**
   * 欠品キャンセル率 実装
   */
  public function vendorStockoutListAction(Request $request)
  {
    $data = [];

    $form = $this->createForm(new SalesResearchVendorStockoutTermType());
    if ($request->get($form->getName())) {
      $form->submit($request);
      if ($form->isValid()) {
        /** @var TbSalesDetailAnalyzeRepository $repo */
        $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetailAnalyze');

        $data = $repo->getVendorStockoutList($form->getData());
      }
    }

    // 画面表示
    return $this->render('AppBundle:SalesResearch:vendor_stockout_list.html.twig', array(
        'termForm' => $form->createView()
      , 'dataCount' => count($data)
      , 'data' => json_encode($data)
    ));
  }

}
