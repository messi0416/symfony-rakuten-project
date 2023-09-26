<?php
/**
 * バッチ処理 代表商品サイズ更新処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\TbProcessExecuteLog;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * バッチ処理 代表商品サイズ更新処理。
 * SKUの最大サイズ（容積率最大）のものを代表商品のサイズとして設定する。
 * SKU内に、サイズ未設定（縦・横・奥行きいずれかが0）のものがあれば、不明サイズありとして、代表商品のサイズも0とする。
 */
class UpdateProductSizeCommand extends PlusnaoBaseCommand {

  private $results;

  protected function configure()
  {
    $this
    ->setName('batch:update-product-size')
    ->setDescription('代表商品サイズ更新処理。パラメータなしなら前日・当日分を処理')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('from_date', null, InputOption::VALUE_OPTIONAL, '処理対象のSKU（tb_productchoiceitems）の更新日（開始）。YYYY-MM-DD。この日を含む、これより後の更新日のSKUを処理する。未指定の場合前日')
    ->addOption('to_date', null, InputOption::VALUE_OPTIONAL, '処理対象のSKU（tb_productchoiceitems）の更新日（終了）。YYYY-MM-DD。この日を含む、これより前の更新日のSKUを処理する。from_date必須。未指定の場合当日')
    ->addOption('daihyo_syohin_code', null, InputOption::VALUE_OPTIONAL, '処理対象の代表商品コード。')
    ->addOption('all', null, InputOption::VALUE_OPTIONAL, '全件更新の場合1')
    ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)

    ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = '代表商品サイズ更新処理';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getLogger();

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

    // all=1、または代表商品コードが指定されている場合は、日付は指定しない
    $daihyoSyohinCode = null;
    if (! empty($input->getOption('daihyo_syohin_code'))) {
      $daihyoSyohinCode = $input->getOption('daihyo_syohin_code');
      $fromDate = null;
      $toDate = null;
    } else if (! empty($input->getOption('all')) && $input->getOption('all') === '1') {
      $fromDate = null;
      $toDate = null;
    }

    // ログ出力
    if ($fromDate) {
      $logger->addDbLog($logger->makeDbLog(null, 'パラメータ確認', '対象範囲種別：日付', '開始日[' . $fromDate->format('Y-m-d') . ']', '終了日[' . $toDate->format('Y-m-d') . ']'));
    } else if ($daihyoSyohinCode) {
      $logger->addDbLog($logger->makeDbLog(null, 'パラメータ確認', "対象範囲種別：代表商品コード[$daihyoSyohinCode]"));
    } else if (! empty($input->getOption('all')) && $input->getOption('all') === '1') {
      $logger->addDbLog($logger->makeDbLog(null, 'パラメータ確認', "対象範囲種別：全件"));
    }
    $productsRepository = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
    $updateCount = $productsRepository->updateMainproductsSizeFromSku($fromDate, $toDate, $daihyoSyohinCode, $logger);

    // 処理実行ログの登録
    $this->processExecuteLog->setProcessNumber1($updateCount);
    $this->processExecuteLog->setVersion(1.0);
  }

  /**
   * パラメータが適切かどうかチェックする。
   */
  function validate(InputInterface $input) {
    if (! empty($input->getOption('to_date')) && empty($input->getOption('from_date'))) {
      throw new \RuntimeException('更新日（終了）を指定する場合は更新日（開始）も指定が必要です');
    }
    if (! empty($input->getOption('from_date')) && ! empty($input->getOption('daihyo_syohin_code')) && (! empty($input->getOption('all')) && $input->getOption('all') === '1')) {
      throw new \RuntimeException('更新日と代表商品コード、allは、いずれか1件だけを指定してください');
    }
    if (!empty($input->getOption('from_date')) && ! strptime($input->getOption('from_date'), '%Y-%m-%d')) {
      throw new \RuntimeException('更新日（開始）の形式がyyyy-mm-ddではありません[' . $input->getOption('from_date') . ']');
    }
    if (!empty($input->getOption('to_date')) && ! strptime($input->getOption('to_date'), '%Y-%m-%d')) {
      throw new \RuntimeException('更新日（終了）の形式がyyyy-mm-ddではありません[' . $input->getOption('to_date') . ']');
    }
  }
}

