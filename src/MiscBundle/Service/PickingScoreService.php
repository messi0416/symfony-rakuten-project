<?php

namespace MiscBundle\Service;

use MiscBundle\Entity\Repository\SymfonyUsersRepository;
use MiscBundle\Entity\Repository\TbPickingScoreRepository;
use MiscBundle\Entity\Repository\TbProductLocationLogRepository;
use MiscBundle\Entity\TbPickingScore;
use MiscBundle\Entity\TbSetting;
use Doctrine\DBAL\DBALException;

class PickingScoreService
{
  use ServiceBaseTrait;

  /**
   * Myピッキングスコア情報の取得
   * @param $userId
   * @return array[]
   * @throws DBALException
   */
  public function fetchUserPickingScore($userId)
  {
    $result = [
      'pickingScore' => [
        'SC' => [
          'firstColumnAverageTime'  => 0,
          'secondColumnAverageTime' => 0,
          'thirdColumnAverageTime'  => 0
        ],
        'V' => [
          'firstColumnAverageTime'  => 0,
          'secondColumnAverageTime' => 0,
          'thirdColumnAverageTime'  => 0
        ],
        'OTHERS' => [
          'firstColumnAverageTime'  => 0,
          'secondColumnAverageTime' => 0,
          'thirdColumnAverageTime'  => 0
        ]
      ],
      'averageTime' => ['SC' => 0, 'V' => 0, 'OTHERS' => 0],
      'fastestTime' => ['SC' => 0, 'V' => 0, 'OTHERS' => 0],
    ];

    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbPickingScore');
    $myPickingScore = $repo->find($userId);
    if ($myPickingScore !== null) {
      $result['pickingScore'] = $this->generatePickingScoreList($myPickingScore);
    }

    $pickingScoreList = $repo->fetchPickingScoreList();
    $result['averageTime']['SC'] = $this->solveOverallAverageTime($pickingScoreList, 'scThirdScore');
    $result['averageTime']['V'] = $this->solveOverallAverageTime($pickingScoreList, 'vThirdScore');
    $result['averageTime']['OTHERS'] = $this->solveOverallAverageTime($pickingScoreList, 'othersThirdScore');

    $result['fastestTime']['SC'] = $this->getFastestTime($pickingScoreList, 'scThirdScore');
    $result['fastestTime']['V'] = $this->getFastestTime($pickingScoreList, 'vThirdScore');
    $result['fastestTime']['OTHERS'] = $this->getFastestTime($pickingScoreList, 'othersThirdScore');

    return $result;
  }

  /**
   * 全体の平均時間を取得
   * @param array $pickingScoreList
   * @param string $targetColumn
   * @return int
   */
  private function solveOverallAverageTime($pickingScoreList, $targetColumn)
  {
    $validLogCount = count($pickingScoreList);
    $totalAverageTime = 0;
    // 全体平均を取得
    foreach ($pickingScoreList as $scores) {
      if ($scores[$targetColumn]) {
        $totalAverageTime += $scores[$targetColumn] / 1000; // 秒
      } else {
        $validLogCount--;
      }
    }

    $averageTime = 0;
    if ($validLogCount > 0) {
      $averageTime = $totalAverageTime / $validLogCount;
    }
    return $averageTime;
  }

  /**
   * 最速時間を取得
   * @param array $pickingScoreList
   * @param string $targetColumn
   * @return int
   */
  private function getFastestTime($pickingScoreList, $targetColumn)
  {
    // あり得る最遅の秒数 + 1
    $minTime = 60 * 10 + 1;
    // 最速スコアを取得
    foreach ($pickingScoreList as $scores) {
      if ($scores[$targetColumn] !== 0) {
        $minTime = min($minTime, $scores[$targetColumn] / 1000);
      }
    }

    // レコード不足のため最速の計測不可(同じになることはあり得ないので)
    if ($minTime === 60 * 10 + 1) {
      return 0;
    }
    return $minTime;
  }

  /**
   * 全体ピッキングスコア情報の取得
   * @return array[]
   * @throws DBALException
   */
  public function fetchPickingScoreList()
  {
    /** @var TbPickingScoreRepository $repo */
    $pickingScoreRepo = $this->getDoctrine()->getRepository('MiscBundle:TbPickingScore');
    $pickingScoreList = $pickingScoreRepo->findAll();

    $list = [];
    /** @var SymfonyUsersRepository $userRepo */
    $userRepo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
    foreach ($pickingScoreList as $scores) {
      $user = $userRepo->find($scores->getUserId());
      if (! $user) {
        continue;
      }

      $username = $user->getUsername();
      $list[$username] = $this->generatePickingScoreList($scores);
    }
    return $list;
  }

  /**
   * ピッキングスコアから表示データリストの作成
   * @param $scores
   * @return array<string, array<string, int>>
   */
  private function generatePickingScoreList($scores)
  {
    $list = [
      'SC' => [
        'firstColumnAverageTime'  => 0,
        'secondColumnAverageTime' => 0,
        'thirdColumnAverageTime'  => 0
      ],
      'V' => [
        'firstColumnAverageTime'  => 0,
        'secondColumnAverageTime' => 0,
        'thirdColumnAverageTime'  => 0
      ],
      'OTHERS' => [
        'firstColumnAverageTime'  => 0,
        'secondColumnAverageTime' => 0,
        'thirdColumnAverageTime'  => 0
      ]
    ];

    $list['SC']['firstColumnAverageTime'] = $scores->getScFirstScore() / 1000; // 秒
    $list['SC']['secondColumnAverageTime'] = $scores->getScSecondScore() / 1000; // 秒
    $list['SC']['thirdColumnAverageTime'] = $scores->getScThirdScore() / 1000; // 秒

    $list['V']['firstColumnAverageTime'] = $scores->getVFirstScore() / 1000; // 秒
    $list['V']['secondColumnAverageTime'] = $scores->getVSecondScore() / 1000; // 秒
    $list['V']['thirdColumnAverageTime'] = $scores->getVThirdScore() / 1000; // 秒

    $list['OTHERS']['firstColumnAverageTime'] = $scores->getOthersFirstScore() / 1000; // 秒
    $list['OTHERS']['secondColumnAverageTime'] = $scores->getOthersSecondScore() / 1000; // 秒
    $list['OTHERS']['thirdColumnAverageTime'] = $scores->getOthersThirdScore() / 1000; // 秒

    return $list;
  }


  /**
   * ピッキングスコアの集計処理
   * @param string $targetDate
   * @return array[]
   * @throws DBALException
   */
  public function aggregatePickingScore($targetDate)
  {
    $list = [];
    /** @var TbProductLocationLogRepository $repo */
    $repo = $this->getDoctrine()->getRepository("MiscBundle:TbProductLocationLog");
    $usernameList = $repo->fetchPickingScoreTargetUserList($targetDate);

    foreach ($usernameList as $username) {
      $list[$username] = $this->listUsersPickingScore($username, $targetDate);
    }
    return $list;
  }

  /**
   * ユーザーのピッキングログを取得しピッキングスコアを算出
   * @param string $username
   * @param string $targetDate
   * @return array[]
   * @throws DBALException
   */
  private function listUsersPickingScore($username, $targetDate)
  {
    $userLogsList = [
      'SC' => [],
      'V' => [],
      'OTHERS' => []
    ];

    /** @var TbProductLocationLogRepository $repo */
    $repo = $this->getDoctrine()->getRepository("MiscBundle:TbProductLocationLog");
    $userLogs = $repo->fetchPickingLog($username, $targetDate);
    if (! empty($userLogs)) {
      $userLogsList = array_merge($userLogsList, $this->calculatePickingScore($userLogs));
    }
    return $userLogsList;
  }

  /**
   * ユーザーのピッキングログからピッキングスコアを算出
   * @param $userLogs array
   * @return array
   */
  private function calculatePickingScore($userLogs)
  {
    // なぜ tb_setting の値を取るのに、DBCommonUtil を使わず直接取っているのか思い出せないが、Doctrineもキャッシュしてくれるのでこのままで
    $settingRepo = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbSetting');
    $firstLatestRecordCount  = (int)$settingRepo->find(TbSetting::KEY_PICKING_RECORD_LIMIT_1)->getSettingVal();
    $secondLatestRecordCount = (int)$settingRepo->find(TbSetting::KEY_PICKING_RECORD_LIMIT_2)->getSettingVal();
    $thirdLatestRecordCount  = (int)$settingRepo->find(TbSetting::KEY_PICKING_RECORD_LIMIT_3)->getSettingVal();
    // これより間隔が長いものだけ集計（これより短いものは通常と異なる操作）
    $limitMinSecond          = (int)$settingRepo->find(TbSetting::KEY_PICKING_RECORD_SECOND_MIN)->getSettingVal();
    // これより間隔が短いものだけ集計（これより長いものは休憩や別作業）
    $limitMaxSecond          = (int)$settingRepo->find(TbSetting::KEY_PICKING_RECORD_SECOND_MAX)->getSettingVal();
    // ○秒以内の打刻が○回連続した場合は集計から除外
    $continueLimitTime       = (int)$settingRepo->find(TbSetting::KEY_PICKING_RECORD_CONTINUE_TIME)->getSettingVal();
    $continueLimitCount      = (int)$settingRepo->find(TbSetting::KEY_PICKING_RECORD_CONTINUE_COUNT)->getSettingVal();

    $userStatistics = [
      'SC' => [
        'firstColumnAverageTime'  => 0,
        'secondColumnAverageTime' => 0,
        'thirdColumnAverageTime'  => 0
      ],
      'V' => [
        'firstColumnAverageTime'  => 0,
        'secondColumnAverageTime' => 0,
        'thirdColumnAverageTime'  => 0
      ],
      'OTHERS' => [
        'firstColumnAverageTime'  => 0,
        'secondColumnAverageTime' => 0,
        'thirdColumnAverageTime'  => 0
      ],
    ];

    $totalTime = [
      'SC' => 0,
      'V' => 0,
      'OTHERS' => 0
    ];
    $validLogCount = [
      'SC' => 0,
      'V' => 0,
      'OTHERS' => 0
    ];

    // ピッキング処理
    // 同一キーは計測外にする処理
    $temp = array();
    $previousActionKey = $userLogs[0]['action_key'];
    $temp[] = $userLogs[0];
    for ($i = 1; $i < count($userLogs); $i++) {
      $userLog = $userLogs[$i];

      if ($previousActionKey === $userLog['action_key']) {
        continue;
      }
      $previousActionKey = $userLog['action_key'];
      $temp[] = $userLog;
    }
    $userLogs = $temp;

    if (count($userLogs) > 0) $userLogs[0]['diff'] = 0;
    for ($i = 1; $i < count($userLogs); $i++) {
      $userLogs[$i]['diff']  = strtotime($userLogs[$i - 1]['created']) - strtotime($userLogs[$i]['created']);
    }

    for ($i = 1;$i < count($userLogs);$i++) {
      $userLog = $userLogs[$i];

      // ピッキング処理
      // 箱Aから商品をピッキングしてそこからその商品の在庫がなくなるとポジション(ピッキングの優先順位)の更新も行われる
      // このポジション更新LogとピッキングLogは同一のaction_keyが割り振られる

      // tb_product_location_record_log (r) と tb_product_location_log (l) をaction_keyで結合すると
      // 上記のとおりaction_keyが同じなため、ポジション更新Log(l側)と結合してしまう
      // 必要な値は同じなので最初のレコードだけ利用
      if ($limitMinSecond >= $userLog['diff'] || $limitMaxSecond <= $userLog['diff']) {
        continue;
      }

      // ○秒以内の打刻が○回連続した場合は集計から除外
      if ($this->isNotContinueLog($userLogs, $i, $continueLimitTime, $continueLimitCount)) continue;

      $boxType = $this->determineBoxType($userLog['location_code']);

      $validLogCount[$boxType]++;
      $totalTime[$boxType] += $userLog['diff'];

      if ($validLogCount[$boxType] === $firstLatestRecordCount) {
        $userStatistics[$boxType]['firstColumnAverageTime']  = $totalTime[$boxType] * 1000 / $firstLatestRecordCount; // ミリ秒
      } else if ($validLogCount[$boxType] === $secondLatestRecordCount) {
        $userStatistics[$boxType]['secondColumnAverageTime'] = $totalTime[$boxType] * 1000 / $secondLatestRecordCount; // ミリ秒
      } else if ($validLogCount[$boxType] === $thirdLatestRecordCount) {
        $userStatistics[$boxType]['thirdColumnAverageTime']  = $totalTime[$boxType] * 1000 / $thirdLatestRecordCount; // ミリ秒

        // 全ての箱が最大まで達する可能性は低いので、if文のコストを考慮して各箱が最大数になった時だけ確認する
        if ($thirdLatestRecordCount <= $validLogCount['SC']
            && $thirdLatestRecordCount <= $validLogCount['V']
            && $thirdLatestRecordCount <= $validLogCount['OTHERS']) {
          break;
        }
      }

    }
    return $userStatistics;
  }

  /**
   * ## ロケーションコードから箱種別を抽出
   * 全体を「-」で分割し、後ろから見ていく
   * コードの基本フォーマットは 列番号-棚番号-箱番号
   * 箱番号の後ろに「-NEW~」と続く場合もある
   *
   * ### 抽出例
   * - I308-6L-S34656 -> SC
   * - H1000-PC-C5864 -> SC
   * - J015-1-V02079 -> V
   *
   * - UEDA-9-NEW_210618 -> OTHERS
   * - H1000-ROJI-V1285-NEW_20121 -> V
   *
   * - S27975 -> SC
   * - P882 -> OTHERS
   * - IDO30 -> OTHERS
   *
   * - H1000-V8669 -> V
   * - H1000-S49038 -> SC
   *
   * @param string $locationCode
   * @return string 'SC' | 'V' | 'OTHERS'
   */
  private function determineBoxType($locationCode)
  {
    $sc = 'SC';
    $v = 'V';
    $others = 'OTHERS';

    if (! is_string($locationCode)) {
      return $others;
    }

    if (mb_strlen($locationCode) === 0) {
      return $others;
    }

    $locationCodeSeparatedByHyphen = explode('-', $locationCode);

    $checkWord = array_pop($locationCodeSeparatedByHyphen);
    // 最後の要素がNEWから始まっていた場合、その次の要素を利用
    if (preg_match('/^NEW/', $checkWord) === 1) {
      $checkWord = array_pop($locationCodeSeparatedByHyphen);
    }

    $firstChar = $checkWord[0];
    if ($firstChar === 'S' || $firstChar === 'C') {
      return $sc;
    }
    if ($firstChar === 'V') {
      return $v;
    }
    return $others;
  }

  /**
   * @param array $pickingScoreList
   */
  public function storePickingScore($pickingScoreList) {
    $list = [];
    /** @var SymfonyUsersRepository $userRepo */
    $userRepo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
    foreach ($pickingScoreList as $username => $scores) {
      $score = new TbPickingScore();
      $user = $userRepo->findOneBy(['username' => $username]);
      if ($user === null) {
        throw new \RuntimeException('ユーザーIDの取得に失敗しました。:' . $username);
      }

      $score->setUserId($user->getId());
      $score->setScFirstScore($scores['SC']['firstColumnAverageTime']);
      $score->setScSecondScore($scores['SC']['secondColumnAverageTime']);
      $score->setScThirdScore($scores['SC']['thirdColumnAverageTime']);
      $score->setVFirstScore($scores['V']['firstColumnAverageTime']);
      $score->setVSecondScore($scores['V']['secondColumnAverageTime']);
      $score->setVThirdScore($scores['V']['thirdColumnAverageTime']);
      $score->setOthersFirstScore($scores['OTHERS']['firstColumnAverageTime']);
      $score->setOthersSecondScore($scores['OTHERS']['secondColumnAverageTime']);
      $score->setOthersThirdScore($scores['OTHERS']['thirdColumnAverageTime']);
      $list[] = $score;
    }
    /** @var TbPickingScoreRepository $pickingScoreRepo */
    $pickingScoreRepo = $this->getDoctrine()->getRepository('MiscBundle:TbPickingScore');
    $pickingScoreRepo->overwrite($list);
  }

  /**
   * ○秒以内の打刻が○回連続した場合は集計から除外
   * @param array $userLogs
   * @param int $current
   * @param int $limitTime
   * @param int $limitCount
   * 
   * @return bool
   */
  private function isNotContinueLog($userLogs, $current, $limitTime, $limitCount)
  {
    $start = $current - ($limitCount - 1);
    $start = $start < 0 ? 0 : $start;
    $end = $current + ($limitCount - 1);
    $end = $end >= count($userLogs) ? count($userLogs) - 1 : $end;

    $invalidCount = 0;
    for ($i = $current; $i >= $start; $i--) {
      if ($userLogs[$i]['diff'] > $limitTime) break;

      $invalidCount++;
    }
    for ($i = $current; $i <= $end; $i++) {
      if ($userLogs[$i]['diff'] > $limitTime) break;

      $invalidCount++;
    }

    return $invalidCount >= $limitCount;
  }
}
