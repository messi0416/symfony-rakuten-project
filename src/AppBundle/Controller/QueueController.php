<?php

namespace AppBundle\Controller;

use BatchBundle\Job\BaseJob;
use BatchBundle\Job\MainJob;
use BatchBundle\Job\NonExclusiveJob;
use BatchBundle\Job\NextEngineUploadJob;
use BatchBundle\Job\RakutenCsvUploadJob;
use BatchBundle\Job\PpmCsvUploadJob;
use BCC\ResqueBundle\Queue;
use MiscBundle\Entity\TbRunning;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DateTimeUtil;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * 梱包・出荷関連
 * @package AppBundle\Controller
 */
class QueueController extends BaseController
{
  /**
   * 処理一覧取得API
   */
  public function getJobListAction(Request $request)
  {
    $result = [
        'message'          => null
      , 'jobs'             => []
      , 'runningProcesses' => []
    ];

    try {
      $resque = $this->getResque();
      /** @var Queue[] $queues */
      $queues = $resque->getQueues();

      $jobList = [];
      foreach($queues as $queue) {
        if ($queue->getName() === 'alibabaApi') { // Alibaba APIキューは（件数が多いので）除外
          continue;
        }

        $jobList[$queue->getName()] = [];
        /** @var BaseJob $job */
        foreach($queue->getJobs() as $job) {
          $jobArray = [];
          $jobArray['name'] = $job->getCurrentCommandName();
          $jobArray['queueDatetime'] = $job->getQueueDateTime()->format('Y-m-d H:i:s');
          $jobArray['account'] = $job->getAccount() ? $job->getAccount()->getUsername() : null;
          $jobList[$queue->getName()][] = $jobArray;
        }
      }

      $result['jobs'] = $jobList;

      // 現在実行中の排他処理
      $now = new \DateTime();
      /** @var DateTimeUtil $dateUtil */
      $dateUtil = $this->get('misc.util.datetime');

      // ログ出力しないように改修
      $this->getDoctrine()->getConnection('main')->getConfiguration()->setSQLLogger(null);
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRunning');
      $running = $repo->findAll();
      /** @var TbRunning $process */
      foreach($running as $process) {
        $running = $process->toScalarArray();

        // 経過時間
        /** @var \DateTime $start */
        if ($process->getStartDatetime()) {
          $start = new \DateTime($process->getStartDatetime());
          $running['startDatetime'] = $start->format('Y-m-d H:i:s'); // 書式変換

          $diff = $start->diff($now);
          $running['runningTime'] = $dateUtil->formatIntervalJp($diff) . ' 経過';
        }

        $result['runningProcesses'][] = $running;
      }

    } catch (\Exception $e) {
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);

  }

  /**
   * 処理ロック解除
   * @param Request $request
   * @return JsonResponse
   */
  public function removeProcessLockAction(Request $request)
  {
    $id = $request->get('id');

    $result = [
        'status' => 'ok'
      , 'message' => null
    ];

    try {
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRunning');

      /** @var TbRunning $running */
      $running = $repo->find($id);
      if (!$running) {
        throw new \RuntimeException('該当するロックがありませんでした。');
      }

      $em = $this->getDoctrine()->getManager('main');
      $em->remove($running);
      $em->flush();

      $result['message'] = sprintf('%s (%s ～) のロックを解除しました。', $running->getProc(), $running->getStartDatetime());

    } catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }
    return new JsonResponse($result);
  }


  /**
   * キューのジョブ順変更　データ取得
   * @param Request $request
   * @return JsonResponse
   */
  public function queueChangePlacesAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok'
      , 'message'          => null
      , 'jobs'             => []
    ];

    try {
      $resque = $this->getResque();
      /** @var Queue[] $queues */
      $queues = $resque->getQueues();

      // キュー内容取得
      $jobList = [];
      foreach ($queues as $queue) {
        if ($queue->getName() === 'alibabaApi') { // Alibaba APIキューは（件数が多いので）除外
          continue;
        }
        $jobList[$queue->getName()] = [];
        /** @var BaseJob $job */
        foreach ($queue->getJobs() as $job) {
          $jobArray = [];
          $jobArray['name'] = $job->getCurrentCommandName();
          $jobArray['queueDatetime'] = $job->getQueueDateTime()->format('Y-m-d H:i:s');
          $jobArray['args'] = $job->args;
          $jobList[$queue->getName()][] = $jobArray;
        }
      }
      $result['jobs'] = $jobList;

    }catch (\Exception $e) {
      $result['status'] = 'ng';
      $result['message'] = 'エラー:'.$e->getMessage();

    }
    return new JsonResponse($result);
  }


  /**
   * @param Request $request
   * @return JsonResponse
   */
  public function isStopWorkerAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok'
      , 'message' => null
    ];

    try{

      $workerName = $request->get('workerName');

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $sql = <<<EOD
      SELECT * 
        FROM tb_stop_worker sw
        WHERE sw.stop_worker = :workerName
        AND sw.is_active = 0
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':workerName', $workerName, \PDO::PARAM_INT);
      $stmt->execute();
      $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
      if(!$row){
        $result['status'] = 'ng';
        $result['message'] = 'このキューは停止していないため変更できません。';
      }

    }catch (\Exception $e){
      $logger->error($e->getMessage());
      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);
  }


  /**
   * キューの保存 一つのキューに対して保存する
   * @param Request $request
   * @return JsonResponse
   */
  public function saveQueueChangedPlacesAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    $result = [
      'status' => 'ok'
      , 'message' => null
    ];

    try {
      $resque = $this->getResque();
      $queueName = $request->get('selectedQueue');
      // 保存対象のキューにあるジョブをクリア
      $resque->clearQueue($queueName);
      $jobs = $request->get('jobs');

      if(!empty($jobs)) {
        // キューに追加されたジョブの時刻 次のジョブも同じ時刻ならば入れ替え不可のものと判断
        $queueDatetime = current($jobs)['queueDatetime'];
        foreach ($jobs as $job) {
          // ジョブをエンキュー
          switch ($queueName) {
            case 'main':
              if($queueDatetime !== $job['queueDatetime']){
                $queueDatetime = $job['queueDatetime'];
                sleep(1);
              }
              $enqueueJob = new MainJob();
              $enqueueJob->queue = "main"; // キュー名
              $enqueueJob->args = $job["args"];
              $resque->enqueue($enqueueJob); // リトライなし
              break;

            case 'nonExclusive':
              if($queueDatetime !== $job['queueDatetime']){
                $queueDatetime = $job['queueDatetime'];
                sleep(1);
              }
              $enqueueJob = new NonExclusiveJob();
              $enqueueJob->queue = "nonExclusive"; // キュー名
              $enqueueJob->args = $job["args"];
              $resque->enqueue($enqueueJob); // リトライなし
              break;

            case 'neUpload':
              if($queueDatetime !== $job['queueDatetime']){
                $queueDatetime = $job['queueDatetime'];
                sleep(1);
              }
              $enqueueJob = new NextEngineUploadJob();
              $enqueueJob->queue = "neUpload"; // キュー名
              $enqueueJob->args = $job["args"];
              $resque->enqueue($enqueueJob); // リトライなし
              break;

            case 'rakutenCsvUpload':
              if($queueDatetime !== $job['queueDatetime']){
                $queueDatetime = $job['queueDatetime'];
                sleep(1);
              }
              $enqueueJob = new RakutenCsvUploadJob();
              $enqueueJob->queue = "rakutenCsvUpload"; // キュー名
              $enqueueJob->args = $job["args"];
              $resque->enqueue($enqueueJob); // リトライなし
              break;

            case 'ppmCsvUpload':
              if($queueDatetime !== $job['queueDatetime']){
                $queueDatetime = $job['queueDatetime'];
                sleep(1);
              }
              $enqueueJob = new PpmCsvUploadJob();
              $enqueueJob->queue = "ppmCsvUpload"; // キュー名
              $enqueueJob->args = $job["args"];
              $resque->enqueue($enqueueJob); // リトライなし
              break;

            default:
              break;
          }
        }
        $result['message'] = '保存に成功しました。';
      }else{
        $result['message'] = '保存に成功しました。';
      }
    }catch (\Exception $e){
      $result['status'] = 'ng';
      $result['message'] = 'エラー:'.$e->getMessage();
    }
    return new JsonResponse($result);
  }


  /**
   * コマンドのキュー存在チェックAPI。
   * 指定されたコマンドが、キューに実行中か、登録済みかをチェックし、ステータスを返却する。
   * コマンドは'command'というパラメータ配下に配列で、複数指定可能。
   *
   * パラメータは以下の形式で指定する。
   * （JS側）
   * data: {
   *     "queue": "main"
   *   , "command": ["export_csv_rakuten", "export_csv_yahoo" ...]
   * }
   *
   * 返り値は以下の形式となる。
   * $result[
   *   'status' => 'ok/ng' // エラー、例外が発生しなければOK
   *   'command' => [
   *     '指定されたコマンド1' => [
   *         isExistence => 'true/false' -- キュー登録済みならば true。実行中は false
   *         isRunning => 'true/false' -- 実行中ならば true。
   *       ]
   *     , '指定されたコマンド2' => [
   *         isExistence => 'true/false'
   *         isRunning => 'true/false'
   *       ]
   *     , ...
   *   ]
   * ]
   */
  public function checkExistenceQueueAction(Request $request)
  {
    $logger = $this->get('misc.util.batch_logger');
    try {
      $result = [
          'status' => 'ok'
          , 'command' => []
      ];

      // キュー登録中処理（実行中は含まれない）
      $commands = $request->get('command'); // 配列
      $logger->debug("キュー存在チェック：対象コマンド：" . print_r($commands, true));
      $queue = $request->get('queue');
      foreach ($commands as $command) {
        $jobs = $this->findQueuesByCommandName($queue, $command);
        $logger->debug("");
        if (!empty($jobs)) {
          $result['command'][$command] = [
              'isExistence' => true
            , 'isRunning' => false
          ];
        } else {
          $result['command'][$command] = [
              'isExistence' => false
            , 'isRunning' => false
          ];
        }
      }

      // 現在実行中処理
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRunning');
      $runnings = $repo->findBy(['queueName' => $queue], ['id' => 'ASC']);
      /** @var TbRunning $running */
      foreach($runnings as $running) {
        $commandName = array_search($running->getProc(), BaseJob::$COMMAND_NAMES); // array_searchは最初の1つしか返さないので、同じ和名のコマンドがあると動かないがとりあえずこれで
        if (isset($result['command'][$commandName])) {
          $result['command'][$commandName]['isRunning'] = true;
        }
      }
    } catch (\Exception $e){
      $result['status'] = 'ng';
      $result['message'] = 'エラー:'.$e->getMessage();
    }
    return new JsonResponse($result);
  }
}
