<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Job;

use BCC\ResqueBundle\ContainerAwareJob;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;


class CheckEnvJob extends ContainerAwareJob
{

  public function run($args)
  {
    $logger = $this->getLogger();
    $logger->info('args: ' . "\n" . print_r($args, true));

    /** @var \Doctrine\DBAL\Connection $db */
    $db = $this->getContainer()->get('doctrine')->getConnection('main');
    $logger->info('(job)' . $db->getHost());
    $logger->info('(job)' . $db->getDatabase());
    $logger->info('(job)' . $db->getUsername());

    $db = $this->getContainer()->get('doctrine')->getConnection('log');
    $logger->info('(job)' . $db->getHost());
    $logger->info('(job)' . $db->getDatabase());
    $logger->info('(job)' . $db->getUsername());

    $input = new ArgvInput(array(
        // 引数を並べていく。最初の引数は何でもよい。
        'dummy'
    ));
    $output = new ConsoleOutput();

    $command = $this->getContainer()->get('misc.db_connection_test');
    $exitCode = $command->run($input, $output);
    if ($exitCode !== 0) { // コマンドが異常終了した
      return $this->exitError($exitCode);
    }

    $this->getLogger()->info('コマンド成功');

    return 0;
  }

  private function exitError($exitCode)
  {
    // TODO エラー処理
    $this->getLogger()->error('コマンド失敗');
    return $exitCode;
  }

  /**
   * @return LoggerInterface
   */
  private function getLogger()
  {
    // TODO カスタムロガー 作成
    // http://qiita.com/emegane/items/c6960957f61d5eb32849
    /** @var $logger LoggerInterface */
    $logger = $this->getContainer()->get('logger');
    return $logger;
  }


}
