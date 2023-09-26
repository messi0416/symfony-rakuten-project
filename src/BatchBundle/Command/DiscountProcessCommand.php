<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\BatchLogger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class DiscountProcessCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('batch:discount-process')
      ->setDescription('商品価格 値下確定処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('値下確定処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // DB記録＆通知処理
    $logExecTitle = '値下確定';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));
    $logger->setExecTitle($logExecTitle);

    try {

      $dbMain = $this->getDb('main');

      // 設定は、前回の再計算のものを利用しここでは更新しない。
      // ※Access流に、画面に表示されているもの（= 一時テーブルの内容）で更新

      $dbMain->beginTransaction(); // トランザクション

      $logger->addDbLog($logger->makeDbLog(null, '明細毎の処理'));

      // -------------------------------------
      // 一時テーブルに登録されていない商品も含め、全商品の「受発注可能フラグ退避F」をOFFに設定
      // -------------------------------------
      $sql = <<<EOD
        UPDATE tb_mainproducts_cal
        SET 受発注可能フラグ退避F = 0
EOD;
      $dbMain->query($sql);

      // -------------------------------------
      // 一時テーブルに登録されていない（＝ フリー在庫のない）、「値下げ許可フラグon」の商品を更新
      // -------------------------------------
      //     m.価格非連動チェック = 0
      //     m.手動ゲリラSALE = 0
      $sql = <<<EOD
        UPDATE tb_mainproducts m
        INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        LEFT JOIN tb_discount_list d ON m.daihyo_syohin_code = d.daihyo_syohin_code
        SET m.価格非連動チェック = 0
          , m.手動ゲリラSALE = 0
        WHERE cal.pricedown_flg <> 0
          AND d.daihyo_syohin_code IS NULL
EOD;
      $dbMain->query($sql);

      // -------------------------------------
      // 一時テーブルに登録されている商品（＝ フリー在庫のある商品） 更新
      // -------------------------------------
      // cal.pricedown_flg 更新
      $sql = <<<EOD
        UPDATE tb_mainproducts_cal cal
        INNER JOIN tb_discount_list d ON cal.daihyo_syohin_code = d.daihyo_syohin_code
        SET cal.pricedown_flg = d.pricedown_flg
EOD;
      $dbMain->query($sql);

      // ------------------------------
      // * 値下げチェック ON => OFF
      // ------------------------------
      //     m.価格非連動チェック = 0
      //     m.手動ゲリラSALE = 0
      $sql = <<<EOD
        UPDATE tb_mainproducts m
        INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_discount_list d ON m.daihyo_syohin_code = d.daihyo_syohin_code
        SET m.価格非連動チェック = 0
          , m.手動ゲリラSALE = 0
        WHERE d.pricedown_flg = 0
          AND d.pricedown_flg <> d.pricedown_flg_pre
EOD;
      $dbMain->query($sql);

      // ------------------------------
      // * 値下げチェックON かつ 値段変動あり
      // ------------------------------
      //       cal.baika_tnk = 改定価格
      //       m.価格非連動チェック = -1
      //       m.手動ゲリラSALE = -1
      //
      //     * 赤字 =>
      //         受発注可能フラグ退避F = -1
      //
      //     * 黒字 =>
      //         受発注可能フラグ退避F = 0
      $sql = <<<EOD
        UPDATE tb_mainproducts m
        INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_discount_list d ON m.daihyo_syohin_code = d.daihyo_syohin_code
        SET cal.baika_tnk = d.discount_price
          , m.価格非連動チェック = -1
          , m.手動ゲリラSALE = -1

          /* 受発注可能フラグ退避F: 赤字ならON、黒字ならOFFを維持 */
          , cal.受発注可能フラグ退避F =
                CASE WHEN d.discount_price < d.cost_total
                  THEN -1
                  ELSE 0
                END

          /* red_flg: 赤字ならON、黒字ならOFF ※利用箇所不明 */
          , cal.red_flg =
                CASE WHEN d.discount_price < d.cost_total
                  THEN -1
                  ELSE 0
                END
        WHERE d.pricedown_flg <> 0
          AND d.base_price <> d.discount_price
EOD;
      $dbMain->query($sql);

      // ------------------------------
      // * 値下げチェックON かつ値段変動なし
      //       m.価格非連動チェック = 0
      //       m.手動ゲリラSALE = 0
      // ------------------------------
      $sql = <<<EOD
        UPDATE tb_mainproducts m
        INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_discount_list d ON m.daihyo_syohin_code = d.daihyo_syohin_code
        SET cal.baika_tnk = d.discount_price
          , m.価格非連動チェック = 0
          , m.手動ゲリラSALE = 0
        WHERE d.pricedown_flg <> 0
          AND d.base_price = d.discount_price
EOD;
      $dbMain->query($sql);

      // '現状赤字販売の商品について赤字販売中の情報を元に戻す
      $sql = <<<EOD
        UPDATE tb_mainproducts AS m
        INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        LEFT JOIN tb_discount_list     AS d   ON cal.daihyo_syohin_code = d.daihyo_syohin_code
        SET cal.red_flg = 0
        WHERE d.daihyo_syohin_code IS NULL
          AND cal.pricedown_flg <> 0
          AND m.価格非連動チェック <> 0
          AND m.手動ゲリラSALE <> 0
          AND cal.red_flg <> 0
EOD;
      $dbMain->query($sql);

      $dbMain->commit();

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, "値下確定処理でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

}
