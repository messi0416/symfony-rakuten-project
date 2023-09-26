<?php
/**
 * バッチ処理 SKU別送料設定を商品マスタ（TbMainproducts）へ反映する。
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\TbShippingdivision;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * バッチ処理 SKU別送料設定の商品マスタ反映処理。
 * SKUの送料設定を取得し、最も高いものを商品全体の送料として反映する。
 * <p>
 * SKUの更新日時が指定期間内、または指定された代表商品コードのものを対象とする。
 *
 * デフォルトの対象期間が前日・当日なのは、SkuShippingdivisionAutoSettingCommandとの連続実行を想定しているため。
 * 前日のみとすると、直前で実行されたSkuShippingdivisionAutoSettingCommandで更新されたものが反映されるまで1日かかる。
 * 一方当日のみとすると、この処理の実行後、手動で設定されたものが反映されない。
 * このため、前日・当日とする。
 *
 * この処理では、送料設定のterminate_flgは考慮せず設定する。
 * これは、以下のような状態を考慮してである。
 * 例）送料設定
 * 　1) 送料 500円
 * 　2) 送料 700円　廃止
 * 　3) 送料 720円　2の移行先として新設
 * ある商品のSKUが、これまで 1 と 2を利用しており、2を廃止、これまで2を利用していたサイズは新たに3を利用することになった。
 * ただし3での発送実績はまだない。
 * このような時、terminate_flgが立っているものは設定しないとなると、3での配送実績が出来るまで、
 * これまで送料700円で計算していたものが、急に500円で計算されてしまう（本来は720円としたい）。
 * これを避けるため、ここでは terminate_flg は考慮しない。
 * tb_productchoiceitems 側に設定する際は、terminate_flgが設定されているものは利用しないため、
 * 配送実績が積まれれば自然と terminate_flg が設定されている送料は使用されなくなる。
 *
 * terminate_flgが設定されている送料を一気に除去したい場合は、手作業（SQL実行、または SKU別送料設定画面からの更新）で処理を行う。
 *
 * @author a-jinno
 */
class SkuShippingdivisionReflectMainproductCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  private $results;

  protected function configure()
  {
    $this
    ->setName('batch:sku-shippingdivision-reflect-mainproduct')
    ->setDescription('SKU別送料設定の商品マスタ反映処理。パラメータなしなら前日・当日分を処理')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('from_date', null, InputOption::VALUE_OPTIONAL, '処理対象のSKU（tb_productchoiceitems）の更新日（開始）。YYYY-MM-DD。この日を含む、これより後の更新日のSKUを処理する。未指定の場合前日')
    ->addOption('to_date', null, InputOption::VALUE_OPTIONAL, '処理対象のSKU（tb_productchoiceitems）の更新日（終了）。YYYY-MM-DD。この日を含む、これより前の更新日のSKUを処理する。from_date必須。未指定の場合当日')
    ->addOption('daihyo_syohin_code', null, InputOption::VALUE_OPTIONAL, '処理対象の代表商品コード。')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('SKU別送料設定の商品マスタ反映処理を開始しました。');

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
    $logExecTitle = 'SKU別送料設定商品マスタ反映処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));
    $logger->setExecTitle($logExecTitle);

    try {
      $this->validate($input);

      $fromDate = new \Datetime('yesterday');
      if (! empty($input->getOption('from_date'))) {
        $fromDate = new \DateTime($input->getOption('from_date'));
      }
      $toDate = new \Datetime('today');
      if (! empty($input->getOption('to_date'))) {
        $toDate = new \DateTime($input->getOption('to_date'));
      }
      $toDate->setTime(23, 59, 59);

      // 代表商品コードが指定されている場合は、日付は指定しない
      $daihyoSyohinCode = null;
      if (! empty($input->getOption('daihyo_syohin_code'))) {
        $daihyoSyohinCode = $input->getOption('daihyo_syohin_code');
        $fromDate = null;
        $toDate = null;
      }

      // ログ出力
      if ($fromDate) {
        $logger->addDbLog($logger->makeDbLog(null, 'パラメータ確認', '対象範囲種別：日付', '開始日[' . $fromDate->format('Y-m-d') . ']', '終了日[' . $toDate->format('Y-m-d') . ']'));
      } else {
        $logger->addDbLog($logger->makeDbLog(null, 'パラメータ確認', "対象範囲種別：代表商品コード[$daihyoSyohinCode]"));
      }
      $productsRepository = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      $productsRepository->updateShippingdivisionFromSku($fromDate, $toDate, $daihyoSyohinCode, $logger);

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog(null, 'エラー終了', 'エラー終了')->setInformation(['error' => $e->getMessage(), 'stacktrace' => $e->getTraceAsString()])
          , true, $logExecTitle . "でエラーが発生しました。", 'error'
          );
      $logger->logTimerFlush();
      return 1;
    }

    $logger->addDbLog($logger->makeDbLog(null, '正常終了', '終了'));
    $logger->logTimerFlush();
    return 0;
  }

  /**
   * パラメータが適切かどうかチェックする。
   */
  function validate(InputInterface $input) {
    if (! empty($input->getOption('to_date')) && empty($input->getOption('from_date'))) {
      throw new \RuntimeException('更新日（終了）を指定する場合は更新日（開始）も指定が必要です');
    }
    if (! empty($input->getOption('from_date')) && ! empty($input->getOption('daihyo_syohin_code'))) {
      throw new \RuntimeException('更新日と代表商品コードは、どちらか片方だけを指定してください');
    }
    if (!empty($input->getOption('from_date')) && ! strptime($input->getOption('from_date'), '%Y-%m-%d')) {
      throw new \RuntimeException('更新日（開始）の形式がyyyy-mm-ddではありません[' . $input->getOption('from_date') . ']');
    }
    if (!empty($input->getOption('to_date')) && ! strptime($input->getOption('to_date'), '%Y-%m-%d')) {
      throw new \RuntimeException('更新日（終了）の形式がyyyy-mm-ddではありません[' . $input->getOption('to_date') . ']');
    }
  }
}