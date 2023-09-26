<?php
namespace AppBundle\Controller;

use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbStockChangeHistoryExcludeProductRepository;
use MiscBundle\Entity\Repository\TbStockRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\TbStock;
use MiscBundle\Exception\ValidationException;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DateTimeUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class StockController extends BaseController
{
  /**
   * 倉庫一覧
   */
  public function historyAction()
  {
    $account = $this->getLoginUser();

    $dbMain = $this->getDoctrine()->getConnection('main');

    set_time_limit(120);

    $sql = <<<EOD
      SELECT
          sh.在庫日時
        , FORMAT(sh.現在庫数,0) AS 現在庫数
        , FORMAT(sh.現在庫数 + sh.移動中在庫数,0) AS 総在庫数
        , FORMAT(sh.フリー在庫数,0) AS フリー在庫数
        , FORMAT(sh.現在庫金額,0) AS 現在庫金額
        , FORMAT(sh.現在庫金額 + sh.移動中在庫金額,0) AS 総在庫金額
        , FORMAT(sh.フリー在庫金額,0) AS フリー在庫金額
        , FORMAT(sh.販売不可在庫数,0) AS 販売不可在庫数
        , FORMAT(sh.販売不可在庫金額,0) AS 販売不可在庫金額
        , FORMAT(sh.移動中在庫数,0) AS 移動中在庫数
        , FORMAT(sh.移動中在庫金額,0) AS 移動中在庫金額
        , FORMAT(sh.季節内在庫数,0) AS 季節内在庫数
        , FORMAT(sh.季節内在庫金額,0) AS 季節内在庫金額
        , FORMAT(sh.季節外在庫数,0) AS 季節外在庫数
        , FORMAT(sh.季節外在庫金額,0) AS 季節外在庫金額
        , FORMAT(shioh.発注済在庫数,0) AS 発注済在庫数
        , FORMAT(shioh.発注済在庫金額,0) AS 発注済在庫金額
        , FORMAT(shioh.入荷済在庫数,0) AS 入荷済在庫数
        , FORMAT(shioh.入荷済在庫金額,0) AS 入荷済在庫金額
        , FORMAT(shioh.出荷待在庫数,0) AS 出荷待在庫数
        , FORMAT(shioh.出荷待在庫金額,0) AS 出荷待在庫金額
        , FORMAT(shioh.出荷済在庫数,0) AS 出荷済在庫数
        , FORMAT(shioh.出荷済在庫金額,0) AS 出荷済在庫金額
        , FORMAT(shl.南京終在庫数,0) AS 南京終在庫数
        , FORMAT(shl.南京終在庫金額,0) AS 南京終在庫金額
        , FORMAT(shl.舞台在庫数,0) AS 舞台在庫数
        , FORMAT(shl.舞台在庫金額,0) AS 舞台在庫金額
        , FORMAT(shl.詰替前在庫数,0) AS 詰替前在庫数
        , FORMAT(shl.詰替前在庫金額,0) AS 詰替前在庫金額
        , FORMAT(shl.FBA在庫数,0) AS FBA在庫数
        , FORMAT(shl.FBA在庫金額,0) AS FBA在庫金額
        , FORMAT(shl.藪吉出荷在庫数,0) AS 藪吉出荷在庫数
        , FORMAT(shl.藪吉出荷在庫金額,0) AS 藪吉出荷在庫金額
        , FORMAT(shl.藪吉ストック在庫数,0) AS 藪吉ストック在庫数
        , FORMAT(shl.藪吉ストック在庫金額,0) AS 藪吉ストック在庫金額
        , FORMAT(shl.RSL在庫数,0) AS RSL在庫数
        , FORMAT(shl.RSL在庫金額,0) AS RSL在庫金額
        , FORMAT(shl.SHOPLIST在庫数,0) AS SHOPLIST在庫数
        , FORMAT(shl.SHOPLIST在庫金額,0) AS SHOPLIST在庫金額
        , FORMAT(shl.古市在庫数,0) AS 古市在庫数
        , FORMAT(shl.古市在庫金額,0) AS 古市在庫金額
        , FORMAT(shl.詰替古市在庫数,0) AS 詰替古市在庫数
        , FORMAT(shl.詰替古市在庫金額,0) AS 詰替古市在庫金額
        , FORMAT(shl.舞台2在庫数,0) AS 舞台2在庫数 -- 現南京終
        , FORMAT(shl.舞台2在庫金額,0) AS 舞台2在庫金額 -- 現南京終
        , FORMAT(shl.白毫寺在庫数,0) AS 白毫寺在庫数
        , FORMAT(shl.白毫寺在庫金額,0) AS 白毫寺在庫金額
        , FORMAT(shl.布目在庫数,0) AS 布目在庫数
        , FORMAT(shl.布目在庫金額,0) AS 布目在庫金額
        , FORMAT(shl.山田川在庫数,0) AS 山田川在庫数
        , FORMAT(shl.山田川在庫金額,0) AS 山田川在庫金額
        , FORMAT(shl.旧ムカイ在庫数,0) AS 旧ムカイ在庫数
        , FORMAT(shl.旧ムカイ在庫金額,0) AS 旧ムカイ在庫金額
        , FORMAT(shl.帯解在庫数,0) AS 帯解在庫数
        , FORMAT(shl.帯解在庫金額,0) AS 帯解在庫金額
        , FORMAT(sho.３ヶ月以内在庫数,0) AS 在庫数_３ヶ月以内
        , FORMAT(sho.３ヶ月以内在庫金額,0) AS 在庫金額_３ヶ月以内
        , FORMAT(sho.６ヶ月以内在庫数,0) AS 在庫数_６ヶ月以内
        , FORMAT(sho.６ヶ月以内在庫金額,0) AS 在庫金額_６ヶ月以内
        , FORMAT(sho.１年以内在庫数,0) AS 在庫数_１年以内
        , FORMAT(sho.１年以内在庫金額,0) AS 在庫金額_１年以内
        , FORMAT(sho.２年以内在庫数,0) AS 在庫数_２年以内
        , FORMAT(sho.２年以内在庫金額,0) AS 在庫金額_２年以内
        , FORMAT(sho.３年以内在庫数,0) AS 在庫数_３年以内
        , FORMAT(sho.３年以内在庫金額,0) AS 在庫金額_３年以内
        , FORMAT(sho.４年以内在庫数,0) AS 在庫数_４年以内
        , FORMAT(sho.４年以内在庫金額,0) AS 在庫金額_４年以内
        , FORMAT(sho.５年以内在庫数,0) AS 在庫数_５年以内
        , FORMAT(sho.５年以内在庫金額,0) AS 在庫金額_５年以内
        , FORMAT(sho.６年以内在庫数,0) AS 在庫数_６年以内
        , FORMAT(sho.６年以内在庫金額,0) AS 在庫金額_６年以内
        , FORMAT(sho.７年以内在庫数,0) AS 在庫数_７年以内
        , FORMAT(sho.７年以内在庫金額,0) AS 在庫金額_７年以内
        , FORMAT(sho.８年以内在庫数,0) AS 在庫数_８年以内
        , FORMAT(sho.８年以内在庫金額,0) AS 在庫金額_８年以内
      FROM tb_stock_history sh
      LEFT JOIN tb_stock_history_ioh shioh ON sh.ID = shioh.history_id
      LEFT JOIN tb_stock_history_location shl ON sh.ID = shl.history_id
      LEFT JOIN tb_stock_history_order sho ON sh.ID = sho.history_id
      INNER JOIN tb_company c ON sh.company_code = c.code AND c.id = {$account->getCompanyId()}
      ORDER BY sh.ID DESC
EOD;
    $data = $dbMain->query($sql)->fetchAll(\PDO::FETCH_ASSOC);


    // 画面表示
    return $this->render('AppBundle:Stock:history.html.twig', [
        'account' => $account
      , 'dataJson' => json_encode($data)
    ]);
  }

  /**
   * 在庫数手動変更履歴 表示
   */
  public function manualChangeHistoryAction()
  {
    return $this->render('AppBundle:Stock:manual-change-history.html.twig', [
      'account' => $this->getLoginUser(),
    ]);
  }

  /**
   * 在庫数手動変更履歴 検索
   */
  public function manualChangeHistorySearchAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok',
      'histories' => null,
    ];
    try {
      $conditions = $request->get('conditions');
      $sortKey = $request->get('sortKey');
      $sortDesc = $request->get('sortDesc');
      $this->validateManualChangeHistoryConditions($conditions);
      /** @var TbLocationRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');
      $result['histories'] = $repo->findManualChangeStockNumber($conditions, $sortKey, $sortDesc);
    } catch (ValidationException $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    } catch (\Exception $e) {
      $logger->error('在庫数手動変更履歴 検索でエラー発生:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 在庫数手動変更履歴 除外商品設定取得
   */
  public function manualChangeHistoryExcludeProductsFindAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok',
      'excludeProducts' => [],
    ];
    try {
      /** @var TbStockChangeHistoryExcludeProductRepository $repo */
      $excludeRepo = $this->getDoctrine()->getRepository('MiscBundle:TbStockChangeHistoryExcludeProduct');
      $result['excludeProducts'] = array_map(function ($product) {
        return $product->getDaihyoSyohinCode();
      }, $excludeRepo->findBy([], ['daihyoSyohinCode' => 'asc']));
    } catch (\Exception $e) {
      $logger->error('在庫数手動変更履歴 除外商品設定取得でエラー発生:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 在庫数手動変更履歴 除外商品設定保存
   */
  public function manualChangeHistoryExcludeProductsSaveAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok',
    ];
    try {
      $excludeProducts = $request->get('excludeProducts');
      /** @var TbStockChangeHistoryExcludeProductRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbStockChangeHistoryExcludeProduct');
      $repo->saveExcludeProducts($excludeProducts);
    } catch (\Exception $e) {
      $logger->error('在庫数手動変更履歴 除外商品設定保存でエラー発生:' . $e->getMessage() . $e->getTraceAsString());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }

  /**
   * 在庫数手動変更履歴の検索条件の妥当性を確認する。
   * @param string $conditions
   */
  private function validateManualChangeHistoryConditions($conditions)
  {
    if ($conditions['targetDateFrom']) {
      /** @var DateTimeUtil $dateUtil */
      $dateUtil = $this->get('misc.util.datetime');
      $dateUtil->validateYmdDate($conditions['targetDateFrom'], '対象日From');
    } else {
      throw new ValidationException('対象日Fromは必ず指定してください');
    }
    if ($conditions['targetDateTo']) {
      $dateUtil->validateYmdDate($conditions['targetDateTo'], '対象日To');
    }
  }
}
