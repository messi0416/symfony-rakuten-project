<?php
/**
 * Alibaba API 商品巡回 キュー対応ジョブ
 * 専用キューのため、排他チェックは不要
 * User: hirai
 * Date: 2017/07/18
 */

namespace BatchBundle\Job;

use BatchBundle\MallProcess\AlibabaMallProcess;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;


/**
 * Class AlibabaProductCheckJob
 * @package BatchBundle\Job
 */
class AlibabaProductCheckJob extends BaseJob
{
  public $queue = 'alibabaApi';

  const API_LIMIT_PAR_DAY = 4000; // 5,000回が上限だが、店舗・未取得商品取得、および開発用に余裕を見る。
  const WAIT = 10;
  const API_LIMIT_WAIT = 600; // 10分ごとにチェック
  const API_LIMIT_WAIT_MAX = 60 * 60 * 24 / self::API_LIMIT_WAIT; // 1日待ちぼうけならおかしい。

  public function run($args)
  {
    try {
      $logger = $this->getLogger();

      $this->args['command'] = self::COMMAND_KEY_ALIBABA_PRODUCT_CHECK;

      $offerId = $this->getArgv('offerId');
      $this->runningJobName = sprintf('%s : %s', $this->getCurrentCommandName(), $offerId);

      // 件数チェック、加算処理
      // API実行件数を超過している場合は、条件が整うまではスリープ。（通常は日付の変更まで）
      /** @var AlibabaMallProcess $alibabaProcess */
      $alibabaProcess = $this->getContainer()->get('batch.mall_process.alibaba');
      $loopCount = 0;
      do {
        $callCount = $alibabaProcess->getApiCallCount();
        if ($callCount <= self::API_LIMIT_PAR_DAY) {
          break;
        }

        sleep(self::API_LIMIT_WAIT);
      } while ($loopCount++ < self::API_LIMIT_WAIT_MAX); // 無限ループよけ

      if ($loopCount >= self::API_LIMIT_WAIT_MAX) {
        throw new \RuntimeException('API利用回数制限での待ち回数が制限値を超えました。処理を終了します。');
      }

      sleep(self::WAIT); // API実行のWAITは必要（怒られる）

      $commandArgs = [
          'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
        , $offerId
      ];
      if (!is_null($this->getArgv('account'))) {
        $commandArgs[] = sprintf('--account=%d', $this->getArgv('account'));
      }
      if (!is_null($this->getArgv('retryLimit'))) {
        $commandArgs[] = sprintf('--retry-limit=%d', $this->getArgv('retryLimit'));
      }

      $logger->info('alibaba product check job: ' . print_r($commandArgs, true));
      $input = new ArgvInput($commandArgs);
      $output = new ConsoleOutput();

      $command = $this->getContainer()->get('batch.fetch_update_1688_products');
      $exitCode = $command->run($input, $output);

      if ($exitCode !== 0) { // コマンドが異常終了した
        $this->exitError($exitCode, 'app/console がエラー終了');
      }

      $this->runningJobName = null;

      return 0;

    } catch (JobException $e) {
      $logger->error('AlibabaProductCheckJobで例外発生:' . $e->getTraceAsString());
      throw $e; // through

    } catch (\Exception $e) {
      $this->exitError(1, $e->getMessage());
    }

    return 0;
  }
}
