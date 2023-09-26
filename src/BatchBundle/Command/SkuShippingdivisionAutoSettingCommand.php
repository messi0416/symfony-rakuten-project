<?php
/**
 * バッチ処理 SKU別送料設定自動設定
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\TbShippingdivision;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * バッチ処理 SKU別送料設定自動設定。
 * 購入履歴（tb_sales_detail）から、単品購入されたデータを抜き出し、SKU別自動設定を送料設定へ反映を行う。
 * <p>
 * 出荷確定日　開始日・終了日、または開始伝票番号・終了伝票番号の指定を行う事が出来る。
 * いずれの指定もない場合は、前日＆当日分（0時～0時）の処理を行う。 *
 * @author a-jinno
 *
 */
class SkuShippingdivisionAutoSettingCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  private $results;

  protected function configure()
  {
    $this
    ->setName('batch:sku-shippingdivision-auto-setting')
    ->setDescription('SKU別送料設定自動設定処理。パラメータなしなら前日＆当日分を処理')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('from_date', null, InputOption::VALUE_OPTIONAL, '処理対象の購入情報の出荷確定日（開始）。この日を含む、これより後の履歴を処理する。未指定の場合前日')
    ->addOption('to_date', null, InputOption::VALUE_OPTIONAL, '処理対象の購入情報の出荷確定日（終了）。この日を含む、これより前の履歴を処理する。from_date必須。未指定の場合本日')
    ->addOption('from_no', null, InputOption::VALUE_OPTIONAL, '処理対象の購入情報の伝票番号（開始）。この伝票を含む、これより後の履歴を処理する')
    ->addOption('to_no', null, InputOption::VALUE_OPTIONAL, '処理対象の購入情報の伝票番号（終了）。この伝票を含む、これより前の履歴を処理する。from_no必須')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('SKU別送料設定自動設定処理を開始しました。');

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
    $logExecTitle = 'SKU別送料設定自動設定';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));
    $logger->setExecTitle($logExecTitle);

    try {
      $this->validate($input);

      $pciRepository = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');
      $fromDate = new \Datetime('yesterday');
      if (! empty($input->getOption('from_date'))) {
        $fromDate = new \DateTime($input->getOption('from_date'));
      }
      $toDate = new \Datetime();
      if (! empty($input->getOption('to_date'))) {
        $toDate = new \DateTime($input->getOption('to_date'));
      }
      $toDate->setTime(23, 59, 59);

      // 伝票番号が指定されている場合は、日付は指定しない
      $fromNo = null;
      if (! empty($input->getOption('from_no'))) {
        $fromNo = $input->getOption('from_no');
        $fromDate = null;
        $toDate = null;
      }
      $endNo = null;
      if (! empty($input->getOption('to_no'))) {
        $endNo = $input->getOption('to_no');
      }

      // ログ出力
      if ($fromDate) {
        $logger->addDbLog($logger->makeDbLog(null, 'パラメータ確認', '対象範囲種別：日付', '開始日[' . $fromDate->format('Y-m-d') . ']', '終了日[' . $toDate->format('Y-m-d') . ']'));
      } else {
        $logger->addDbLog($logger->makeDbLog(null, 'パラメータ確認', '対象範囲種別：伝票番号', "開始No[$fromNo]", "終了No[" . ($endNo ? $endNo : '指定なし') . ']'));
      }

      $logInfo = $pciRepository->updateProductchoiceitemsShippingdivisions($fromDate, $toDate, $fromNo, $endNo, $logger);

      if (!empty($logInfo['data']) && count($logInfo['data']) > 0) {
        $logger->addDbLog($logger->makeDbLog(null, '正常終了', '設定が不適切なデータが含まれています')->setInformation($logInfo));
      } else {
        $logger->addDbLog($logger->makeDbLog(null, '正常終了', '終了'));
      }
      $logger->logTimerFlush();
      return 0;

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog(null, 'エラー終了', 'エラー終了')->setInformation(['error' => $e->getMessage(), 'stacktrace' => $e->getTraceAsString()])
          , true, $logExecTitle . "でエラーが発生しました。", 'error'
          );
      $logger->logTimerFlush();
      return 1;
    }
  }

  /**
   * パラメータが適切かどうかチェックする。
   */
  function validate(InputInterface $input) {
    if (! empty($input->getOption('to_date')) && empty($input->getOption('from_date'))) {
      throw new \RuntimeException('出荷確定日（終了）を指定する場合は出荷確定日（開始）も指定が必要です');
    }
    if (! empty($input->getOption('to_no')) && empty($input->getOption('from_no'))) {
      throw new \RuntimeException('伝票番号（終了）を指定する場合は伝票番号（開始）も指定が必要です');
    }
    if (! empty($input->getOption('from_date')) && ! empty($input->getOption('from_no'))) {
      throw new \RuntimeException('出荷確定日と伝票番号は、どちらか片方だけを指定してください');
    }
    if (!empty($input->getOption('from_date')) && ! strptime($input->getOption('from_date'), '%Y-%m-%d')) {
      throw new \RuntimeException('出荷確定日（開始）の形式がyyyy-mm-ddではありません[' . $input->getOption('from_date') . ']');
    }
    if (!empty($input->getOption('to_date')) && ! strptime($input->getOption('to_date'), '%Y-%m-%d')) {
      throw new \RuntimeException('出荷確定日（終了）の形式がyyyy-mm-ddではありません[' . $input->getOption('to_date') . ']');
    }
  }
}