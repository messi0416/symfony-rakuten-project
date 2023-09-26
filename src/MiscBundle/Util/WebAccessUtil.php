<?php
namespace MiscBundle\Util;
use Doctrine\ORM\EntityManager;
use Goutte\Client as WebClient;
use InvalidArgumentException;
use MiscBundle\Entity\BatchLock;
use MiscBundle\Entity\BatchLockException;
use MiscBundle\Entity\Repository\BatchLockRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\SymfonyUserYahooAgent;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\TbShoppingMall;
use RuntimeException;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use YConnect\Credential\ClientCredential;
use YConnect\Exception\TokenException;
use YConnect\YConnectClient;

/**
 * 共通処理
 */
class WebAccessUtil
{
  /** @var ContainerInterface */
  private $container;

  /** @var BatchLogger */
  private $logger;

  /** @var SymfonyUsers */
  private $account;

  /**
   * RMSログイン 試行回数上限
   */
  const RMS_LOGIN_RETRY_MAX_COUNT = 3;

  /**
   * PPMログイン 試行回数上限
   */
  const PPM_LOGIN_RETRY_MAX_COUNT = 60;


  public function __construct()
  {
  }

  /**
   * @param ContainerInterface $container
   */
  public function setContainer(ContainerInterface $container)
  {
    $this->container = $container;
  }

  /**
   * @param BatchLogger $logger
   */
  public function setLogger(BatchLogger $logger)
  {
    $this->logger = $logger;
  }

  /**
   * @param SymfonyUsers $account
   */
  public function setAccount(SymfonyUsers $account)
  {
    $this->account = $account;
  }


  /**
   * @param WebClient $client
   * @param String $accountName
   * @param string $targetEnv prod|test
   * @return Crawler
   */
  public function neLogin($client, $accountName, $targetEnv = 'prod')
  {
    $container = $this->container;
    $logger = $this->logger;
    if (!$container || !$logger) {
      throw new RuntimeException('no container and no logger.');
    }

    // ログイン情報
    $loginUrl = $container->getParameter('ne_site_login_url');

    $accountInfo = $container->getParameter('ne_site_account');
    $account = $accountInfo[$accountName]['account'];
    $password = $accountInfo[$accountName]['password'];

    // symfony_users テーブルへのNEアカウント登録があればそちらを優先で利用
    if (
         $this->account
      && $this->account->getNeAccount()
      && $this->account->getNePassword()
    ) {
      $account = $this->account->getNeAccount();
      $password = $this->account->getNePassword();

      $logger->info('ログインユーザのNEアカウント: ' . $account);
    } else {
      $logger->info('デフォルトのNEアカウント: ' . $account);
    }

    $logger->info('NEログイン画面アクセス');

    $crawler = $client->request('get', $loginUrl);
    $status = $client->getResponse()->getStatus();
    if ($status !== 200) {
      throw new RuntimeException('access error!! [' . $status . ']');
    }

    $logger->info('NEログイン試行');

    $form = $crawler->selectButton('ログイン')->form();

    $form['user[login_code]'] = $account;
    $form['user[password]'] = $password;

    $crawler = $client->submit($form); // ログイン

    $status = $client->getResponse()->getStatus();
    $uri = $client->getRequest()->getUri();
    if ($status !== 200 || $uri !== 'https://base.next-engine.org/') {
      throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
    }
    $logger->info('NEログイン成功');

    // ------------------------------------------
    // メインページへの移動
    $logger->info('メイン機能へ遷移');

    // メイン機能へ遷移（サーバ名が連番っぽいので、トップから順にたどる必要がありそうなための実装）
    if ($targetEnv == 'prod') {
      // 本番用
      $mainLink = $crawler->filter('div.app-launcher-container li[title="メイン機能"] a')->first()->link();
    } else {
      // テスト環境
      $mainLink = $crawler->filter('div.app-launcher-container li[title="メイン機能（テスト環境）"] a')->first()->link();
    }

    $crawler = $client->click($mainLink);
    $status = $client->getResponse()->getStatus();
    $uri = $client->getRequest()->getUri();

    if ($status !== 200) {
      throw new RuntimeException('move to main top page error!! [' . $status . '][' . $uri . ']');
    }

    if (!preg_match('!.next-engine.(?:org|com)/Usertop$!', $uri)) {
      // お知らせ画面など、他の画面へ遷移していれば、トップページを直接指定して移動してみる
      if (preg_match('!^(http.*\.next-engine.(?:org|com))!', $uri, $match)) {

        $uri = $match[1] . '/Usertop';
        $crawler = $client->request('get', $uri);

        $status = $client->getResponse()->getStatus();
        $uri = $client->getRequest()->getUri();

        if ($status !== 200 || !preg_match('!.next-engine.(?:org|com)/Usertop$!', $uri)) {
          throw new RuntimeException('メイン画面に遷移できませんでした。 [' . $status . '][' . $uri . ']');
        }
      } else {
        throw new RuntimeException('move to main top page error (unknown url)!! [' . $status . '][' . $uri . ']');
      }
    }

    $logger->info('メイン機能へ遷移成功');

    return $crawler;
  }


  /**
   * 楽天 RMS ログイン
   *
   * @param WebClient $client
   * @param String $accountName
   * @param String $targetShop 対象店舗[rakuten|motto|laforest|dolcissimo|gekipla]
   * @return Crawler
   * @throws \Exception
   */
  public function rmsLogin($client, $accountName, $targetShop = 'rakuten')
  {
    $container = $this->container;
    $logger = $this->logger;
    if (!$container || !$logger) {
      throw new RuntimeException('no container and no logger.');
    }
    // バッチロックのキー　店舗ごとに切り替える
    $batchLockCode = BatchLock::BATCH_CODE_RMS_LOGIN;
    if ($targetShop === 'motto') {
      $batchLockCode = BatchLock::BATCH_CODE_RMS_MOTTO_LOGIN;
    }
    if ($targetShop === 'laforest') {
      $batchLockCode = BatchLock::BATCH_CODE_RMS_LAFOREST_LOGIN;
    }
    if ($targetShop === 'dolcissimo') {
      $batchLockCode = BatchLock::BATCH_CODE_RMS_DOLCISSIMO_LOGIN;
    }
    if ($targetShop === 'gekipla') {
      $batchLockCode = BatchLock::BATCH_CODE_RMS_GEKIPLA_LOGIN;
    }

    try {
      /** @var \MiscBundle\Util\DbCommonUtil $dbCommonUtil */
      $dbCommonUtil = $container->get('misc.util.db_common');

      // ログイン情報
      $loginUrl = $container->getParameter('rms_site_login_url');

      $reload = true; // 必ずDBから再読み込みする。
      $rLoginAccount = null;
      $rLoginPassword = null;
      if ($targetShop === 'rakuten') {
        $rLoginAccount  = $dbCommonUtil->getSettingValue('RAKUTEN_R_LOGIN_ACCOUNT', null, $reload);
        $rLoginPassword = $dbCommonUtil->getSettingValue('RAKUTEN_R_LOGIN_PASSWORD', null, $reload);
      } else if ($targetShop === 'motto') {
        $rLoginAccount  = $dbCommonUtil->getSettingValue('MOTTO_R_LOGIN_ACCOUNT', null, $reload);
        $rLoginPassword = $dbCommonUtil->getSettingValue('MOTTO_R_LOGIN_PASSWORD', null, $reload);
      } else if ($targetShop === 'laforest') {
        $rLoginAccount  = $dbCommonUtil->getSettingValue('LAFOREST_R_LOGIN_ACCOUNT', null, $reload);
        $rLoginPassword = $dbCommonUtil->getSettingValue('LAFOREST_R_LOGIN_PASSWORD', null, $reload);
      } else if ($targetShop === 'dolcissimo') {
        $rLoginAccount  = $dbCommonUtil->getSettingValue('DOLCISSIMO_R_LOGIN_ACCOUNT', null, $reload);
        $rLoginPassword = $dbCommonUtil->getSettingValue('DOLCISSIMO_R_LOGIN_PASSWORD', null, $reload);
      } else if ($targetShop === 'gekipla') {
        $rLoginAccount  = $dbCommonUtil->getSettingValue('GEKIPLA_R_LOGIN_ACCOUNT', null, $reload);
        $rLoginPassword = $dbCommonUtil->getSettingValue('GEKIPLA_R_LOGIN_PASSWORD', null, $reload);
      } else {
        throw new \RuntimeException("店舗コードの指定が不正です。rakuten, motto, laforest, dolcissimo, gekipla のいずれかを指定してください。現在の値：[$targetShop]");
      }

      // ログイン失敗の場合、処理を繰り返さないようにバッチ処理ロックをかける。
      /** @var BatchLockRepository $repo */
      $repo = $container->get('doctrine')->getRepository('MiscBundle:BatchLock');
      $lock = $repo->findByBatchCode($batchLockCode);
      if ($lock) {
        // もしパスワードが変更されていれば、もう一度やってみる。
        if ($lock->getLockKey() !== $rLoginPassword) {
          $repo->unlock($batchLockCode);
          unset($lock);

        // 同じままなら処理中止。
        } else {

          // 試行回数オーバーなら例外送出
          if ($lock->isOverRetryMax()) {
            $e = new BatchLockException("RMSへのログインに失敗しました。パスワードの設定を確認してください。");
            $e->setLock($lock);
            throw $e;
          // 試行回数内ならスルーしてチャレンジ
          } else {
            // do nothing
          }
        }
      }

      $logger->info('RMSログイン画面アクセス');

      $crawler = $client->request('get', $loginUrl);
      $status = $client->getResponse()->getStatus();
      if ($status !== 200) {
        throw new RuntimeException('access error!! [' . $status . ']');
      }

      $logger->info('RMSログイン試行');

      $form = $crawler->selectButton('次へ')->form();

      $form['login_id'] = $rLoginAccount;
      $form['passwd'] = $rLoginPassword;

      $logger->info('RMSログイン試行 R-Login実行');
      $crawler = $client->submit($form); // R-Login 実行

      $response = $client->getResponse();
      $status = $response->getStatus();
      $uri = $client->getRequest()->getUri();
      if ($status !== 200 || $uri !== 'https://glogin.rms.rakuten.co.jp/') {
        throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
      }
      $logger->info('R-Login ログイン成功');

      // 楽天会員ログイン
      $logger->info('楽天会員の認証 試行');

      $accountInfo = $container->getParameter('rms_site_account');
      $account = $accountInfo[$accountName]['account'];
      $password = $accountInfo[$accountName]['password'];

      // symfony_users テーブルへのRMSアカウント登録があればそちらを優先で利用
      // ※未実装
      /*
      if (
        $this->account
        && $this->account->getNeAccount()
        && $this->account->getNePassword()
      ) {
        $account = $this->account->getNeAccount();
        $password = $this->account->getNePassword();

        $logger->info('ログインユーザのNEアカウント: ' . $account . ' / ' . $password);
      } else {
        $logger->info('デフォルトのNEアカウント: ' . $account . ' / ' . $password);
      }
      */

      $form = $crawler->selectButton('ログイン')->form();
      $form['user_id'] = $account;
      $form['user_passwd'] = $password;


      $logger->info('楽天会員の認証試行 実行');
      $crawler = $client->submit($form); // R-Login 実行

      $response = $client->getResponse();
      $status = $response->getStatus();
      $uri = $client->getRequest()->getUri();

      if ($status !== 200 || $uri !== 'https://glogin.rms.rakuten.co.jp/') {
        throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
      }
      $logger->info('楽天会員の認証 成功');

      // 「お知らせ」画面の場合「次へ」をクリック
      try {

        $title = $crawler->filter('title');
        if (strpos($title->text(), 'R-Login 楽天からのお知らせ') !== false) {

          $logger->info('楽天からのお知らせ ページ');

          $form = $crawler->selectButton('次へ')->form();
          $crawler = $client->submit($form); // R-Login 実行

          $response = $client->getResponse();
          $status = $response->getStatus();
          $uri = $client->getRequest()->getUri();

          if ($status !== 200 || strpos($uri, 'https://mainmenu.rms.rakuten.co.jp/') === false) {
            throw new RuntimeException('page move error (information) !! [' . $status . '][' . $uri . ']');
          }
        }

      } catch (\Exception $e) {

        $logger->info(get_class($e));
        $logger->info($e->getMessage());

        throw $e;
      }

      $logger->info('楽天市場出店規約・ルール・ガイドラインの遵守のお願い ページ遷移 チェック');
      try {

        // $button = $crawler->selectButton('上記を遵守していることを確認の上、RMSを利用します');
        $button = $crawler->selectButton('RMSを利用します');
        $form = $button->form();
        $crawler = $client->submit($form); // R-Login 実行

        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();

        if ($status !== 200 || strpos($uri, 'https://mainmenu.rms.rakuten.co.jp/') === false) {
          throw new RuntimeException('page move error (information) !! [' . $status . '][' . $uri . ']');
        }

      // ノードが見つからなければそれでOK
      } catch (\InvalidArgumentException $e) {
        if ($e->getMessage() == 'The current node list is empty.') {
          // OK
        } else {
          throw new RuntimeException($e->getMessage());
        }

      } catch (\Exception $e) {
        $logger->info(get_class($e));
        $logger->info($e->getMessage());

        throw $e;
      }
      
      $logger->info('楽天市場重要なお知らせ チェック');
      try {

        // $button = $crawler->selectButton('上記を遵守していることを確認の上、RMSを利用します');
        $button = $crawler->selectButton('RMSメインメニューへ進む');
        $form = $button->form();
        $crawler = $client->submit($form); // R-Login 実行

        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();

        if ($status !== 200 || strpos($uri, 'https://mainmenu.rms.rakuten.co.jp/') === false) {
          throw new RuntimeException('page move error (information) !! [' . $status . '][' . $uri . ']');
        }

      // ノードが見つからなければそれでOK
      } catch (\InvalidArgumentException $e) {
        if ($e->getMessage() == 'The current node list is empty.') {
          // OK
        } else {
          throw new RuntimeException($e->getMessage());
        }

      } catch (\Exception $e) {
        $logger->info(get_class($e));
        $logger->info($e->getMessage());

        throw $e;
      }

      $logger->info('楽天ログイン メインページ遷移成功');

      // 1px 画像のsrcアクセスによる認証（2015/10/27, 2016/10/16 時点の楽天実装）
      // $logger->info(print_r($crawler->html(), true));

      // rdatatool ログイン処理？を探して実行
      // https://rdatatool.rms.rakuten.co.jp/auth/
      try {
        $loginImg = $crawler->filter('img[src^="https://datatool.rms.rakuten.co.jp/auth/"]');
        $logger->info('img auth: ' . $loginImg->attr('src'));
        $client->request('get', $loginImg->attr('src'));
      } catch (\InvalidArgumentException $e) {


        $logger->info($uri);
        $logger->info($crawler->html());

        throw new RuntimeException('ログイン認証画像が見つかりませんでした。(rdatatool)');
      }
      // review ログイン処理？を探して実行
      try {
        $loginImg = $crawler->filter('img[src^="https://review.rms.rakuten.co.jp/auth/login/"]');
        $logger->info('img auth: ' . $loginImg->attr('src'));
        $client->request('get', $loginImg->attr('src'));
      } catch (\InvalidArgumentException $e) {
        throw new RuntimeException('ログイン認証画像が見つかりませんでした。(review)');
      }
      // item ログイン処理？を探して実行
      try {
        $loginImg = $crawler->filter('img[src^="https://item.rms.rakuten.co.jp/rms/mall/rsf/item/login"]');
        $logger->info('img auth: ' . $loginImg->attr('src'));
        $client->request('get', $loginImg->attr('src'));
      } catch (\InvalidArgumentException $e) {
        throw new RuntimeException('ログイン認証画像が見つかりませんでした。(item)');
      }
      // 広告 ログイン処理？を探して実行
      try {
        $loginImg = $crawler->filter('img[src^="https://ad.rms.rakuten.co.jp/auth/"]');
        $logger->info('img auth: ' . $loginImg->attr('src'));
        $client->request('get', $loginImg->attr('src'));
      } catch (\InvalidArgumentException $e) {
        throw new RuntimeException('ログイン認証画像が見つかりませんでした。(広告)');
      }

      // 成功すれば、かかっていたロックは解除
      $repo->unlock($batchLockCode);

      $logger->info('楽天ログイン 各ページ img読み込み認証？成功');

      return $crawler;

    // すでにブロック済みの場合はそのまま例外を投げる
    } catch (BatchLockException $e) {
      throw $e;

    } catch (\Exception $e) {
      // ログイン失敗の場合はロックをかける
      /** @var BatchLockRepository $repo */
      $repo = $container->get('doctrine')->getRepository('MiscBundle:BatchLock');

      if (!isset($lock) || !$lock) {
        $lock = $repo->lock($batchLockCode, null, (isset($rLoginPassword) ? $rLoginPassword : null), '', self::RMS_LOGIN_RETRY_MAX_COUNT);
      } else {
        // 試行回数 更新
        $lock->increaseRetryCount();

        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager('main');
        $em->flush();
      }

      $e = new \RuntimeException(sprintf("RMSへのログインに失敗しました。試行回数を超えるとRMSログイン処理をロックします。( %d / %d ) : %s", $lock->getRetryCount(), $lock->getRetryCountMax(), $e->getMessage()));
      throw $e;
    }
  }



  /**
   * PPM ログイン
   *
   * @param WebClient $client
   * @param String $accountName
   * @return Crawler
   * @throws \Exception
   */
  public function ppmLogin($client, $accountName = 'api')
  {
    $container = $this->container;
    $logger = $this->logger;
    if (!$container || !$logger) {
      throw new RuntimeException('no container and no logger.');
    }

    try {
      /** @var \MiscBundle\Util\DbCommonUtil $dbCommonUtil */
      $dbCommonUtil = $container->get('misc.util.db_common');

      // ログイン情報
      $loginUrl = $container->getParameter('ppm_site_login_url');
      $loginAccount  = $dbCommonUtil->getSettingValue('PPM_SHOP_LOGIN_ACCOUNT');
      $loginPassword = $dbCommonUtil->getSettingValue('PPM_SHOP_LOGIN_PASSWORD');

      // ログイン失敗の場合、処理を繰り返さないようにバッチ処理ロックをかける。
      /** @var BatchLockRepository $repo */
      $repo = $container->get('doctrine')->getRepository('MiscBundle:BatchLock');
      $lock = $repo->findByBatchCode(BatchLock::BATCH_CODE_PPM_LOGIN);
      if ($lock) {
        // もしパスワードが変更されていれば、もう一度やってみる。
        if ($lock->getLockKey() !== $loginPassword) {
          $repo->unlock(BatchLock::BATCH_CODE_PPM_LOGIN);
          unset($lock);

          // 同じままなら処理中止。
        } else {

          // 試行回数オーバーなら例外送出
          if ($lock->isOverRetryMax()) {
            $e = new BatchLockException("PPMへのログインに失敗しました。パスワードの設定を確認してください。");
            $e->setLock($lock);
            throw $e;
            // 試行回数内ならスルーしてチャレンジ
          } else {
            // do nothing
          }
        }
      }

      $logger->info('PPMログイン画面アクセス');

      $crawler = $client->request('get', $loginUrl);
      $status = $client->getResponse()->getStatus();
      if ($status !== 200) {
        throw new RuntimeException('access error!! [' . $status . ']');
      }

      $logger->info('PPMログイン試行');

      $form = $crawler->selectButton('ユーザログインIDの認証へ')->form();

      $form['shopLoginId'] = $loginAccount;
      $form['shopPassword'] = $loginPassword;

      $logger->info('PPMログイン試行 Login実行');
      $crawler = $client->submit($form); // R-Login 実行

      /** @var Response $response */
      $response = $client->getResponse();
      $status = $response->getStatus();
      $uri = $client->getRequest()->getUri();

      $logger->info(sprintf('status : %s / uri : %s', $status, $uri));
      if ($status !== 200 || $uri !== 'https://menu.ponparemall.com/shopauth/login/shopUser/') {
        throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
      }
      // $logger->info($response->getContent());

      $logger->info('PPM ログイン成功');

      // PPM会員ログイン
      $logger->info('PPM会員の認証試行');

      $accountInfo = $container->getParameter('ppm_site_account');
      $account = $accountInfo[$accountName]['account'];
      $password = $accountInfo[$accountName]['password'];
      if ($accountName == 'api') {
        /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
        $commonUtil = $container->get('misc.util.db_common');
        $account = $commonUtil->getSettingValue(TbSetting::KEY_PPM_SITE_API_ACCOUNT); 
        $password = $commonUtil->getSettingValue(TbSetting::KEY_PPM_SITE_API_PASSWORD); 
      }

      $logger->info(sprintf('account: %s / password: %s', $account, $password));

      $form = $crawler->selectButton('ログインする')->form();
      $form['userLoginId'] = $account;
      $form['userPassword'] = $password;

      $logger->info('PPM会員の認証試行 実行');
      $crawler = $client->submit($form); // Login 実行

      $response = $client->getResponse();
      $status = $response->getStatus();
      $uri = $client->getRequest()->getUri();

      $logger->info($status);
      $logger->info($uri);
      // $logger->info($response->getContent());

      if ($status !== 200 || $uri !== 'https://menu.ponparemall.com/shopauth/login/notifications/') {
        throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
      }
      $logger->info('PPM会員の認証 成功');

      // 「お知らせ」画面の場合「次へ」をクリック
      try {
        if ($crawler->filter('input[type="submit"][name="toMainMenu"]')->count()) {

          $form = $crawler->selectButton('メインメニューへ進む')->form();
        } else {
          $logger->info(print_r($crawler->filter('input[type="submit"][name="toMainMenu"]')->count(), true));
        }

        if (isset($form)) {

          $logger->info('お知らせ ページ');

          $crawler = $client->submit($form); // R-Login 実行

          $response = $client->getResponse();
          $status = $response->getStatus();
          $uri = $client->getRequest()->getUri();

          $logger->info(sprintf('%s / %s', $status, $uri));
          // $logger->info($response->getContent());

          if ($status !== 200 || strpos($uri, 'https://menu.ponparemall.com/shopmenu/') === false) {
            throw new RuntimeException('page move error (information) !! [' . $status . '][' . $uri . ']');
          }
        }

      } catch (\Exception $e) {

        $logger->info(get_class($e));
        $logger->info($e->getMessage());

        throw $e;
      }

      // ログイン成功確認のため、再度アクセス
      $crawler = $client->request('GET', 'https://menu.ponparemall.com/shopmenu/');
      $response = $client->getResponse();
      $status = $response->getStatus();
      $uri = $client->getRequest()->getUri();

      $logger->info(sprintf('%s / %s', $status, $uri));
      if ($status !== 200 || strpos($uri, 'https://menu.ponparemall.com/shopmenu/') === false) {
        throw new RuntimeException('page move error (shop menu) !! [' . $status . '][' . $uri . ']');
      }

      $logger->info('PPMログイン メインページ遷移成功');

      // 成功すれば、かかっていたロックは解除
      $repo->unlock(BatchLock::BATCH_CODE_PPM_LOGIN);

      return $crawler;

      // すでにブロック済みの場合はそのまま例外を投げる
    } catch (BatchLockException $e) {
      throw $e;

    } catch (\Exception $e) {
      // ログイン失敗の場合はロックレコードの試行回数を増やす。
      /** @var BatchLockRepository $repo */
      $repo = $container->get('doctrine')->getRepository('MiscBundle:BatchLock');

      if (!isset($lock) || !$lock) {
        $lock = $repo->lock(BatchLock::BATCH_CODE_PPM_LOGIN, null, (isset($loginPassword) ? $loginPassword : ''), '', self::PPM_LOGIN_RETRY_MAX_COUNT);
      } else {
        // 試行回数 更新
        $lock->increaseRetryCount();

        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager('main');
        $em->flush();
      }

      $e = new \RuntimeException(sprintf("PPMへのログインに失敗しました。試行回数を超えるとPPMログイン処理をロックします。( %d / %d ) : %s", $lock->getRetryCount(), $lock->getRetryCountMax(), $e->getMessage()));
      throw $e;
    }
  }





  /**
   * @param WebClient $client
   * @return Crawler
   */
  public function shoplistLogin($client)
  {
    $container = $this->container;
    $logger = $this->logger;
    if (!$container || !$logger) {
      throw new RuntimeException('no container and no logger.');
    }

    // ログイン情報
    $loginUrl = $container->getParameter('shoplist_login_url');

    $accountInfo = $container->getParameter('shoplist_account');
    $shopCode = $accountInfo['shop_code'];
    $account  = $accountInfo['account'];
    /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
    $commonUtil = $container->get('misc.util.db_common');
    $password = $commonUtil->getSettingValue(TbSetting::KEY_SHOPLIST_PASSWORD); 

    $logger->info($loginUrl);
    $logger->info(print_r($accountInfo, true));

    $logger->info('SHOPLISTログイン画面アクセス');

    $crawler = $client->request('get', $loginUrl);
    $status = $client->getResponse()->getStatus();
    if ($status !== 200) {
      throw new RuntimeException('access error!! [' . $status . ']');
    }

    $logger->info('SHOPLISTログイン試行');

    $form = $crawler->selectButton('login')->form();
    $form['directory']  = $shopCode;
    $form['login_id']   = $account;
    $form['login_pass'] = $password;

    $crawler = $client->submit($form); // ログイン

    $status = $client->getResponse()->getStatus();
    $uri = $client->getRequest()->getUri();
    $logger->info('status: ' . $status);
    $logger->info('uri: ' . $uri);
    if ($status !== 200) {
      throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
    }

    // 注意書きページ
    if($uri == 'https://service.shop-list.com/shopadmin/login/Alert') {
      $form = $crawler->selectButton('同意して次へ進む')->form();
      $crawler = $client->submit($form); // 次へ進む

      $status = $client->getResponse()->getStatus();
      $uri = $client->getRequest()->getUri();
      $logger->info('status: ' . $status);
      $logger->info('uri: ' . $uri);

      $logger->info('status (alert): ' . $status);
      $logger->info('uri (alert): ' . $uri);

      if ($status !== 200) {
        throw new RuntimeException('alert page error!! [' . $status . '][' . $uri . ']');
      }
    }

    // ログイン後遷移チェック
    $brandName = $crawler->filter('#header_brand_name');
    if (!$brandName->count() || strpos($brandName->text(), 'PlusNao') === false) {
      throw new RuntimeException('post login page move error!! [' . $status . '][' . $uri . ']');
    }

    $logger->info('SHOPLISTログイン成功');

    return $crawler;
  }


  /**
   * @param WebClient $client
   * @return Crawler
   */
  public function yabuyoshiLogin($client)
  {
    $container = $this->container;
    $logger = $this->logger;
    if (!$container || !$logger) {
      throw new RuntimeException('no container and no logger.');
    }

    // ログイン情報
    $loginUrl = $container->getParameter('yabuyoshi_login_url');

    $accountInfo = $container->getParameter('yabuyoshi_site_account');
    $account  = $accountInfo['account'];
    $password = $accountInfo['password'];

    $logger->info($loginUrl);
    $logger->info(print_r($accountInfo, true));

    $logger->info('藪吉倉庫 ログイン画面アクセス');

    $crawler = $client->request('get', $loginUrl);
    $status = $client->getResponse()->getStatus();
    if ($status !== 200) {
      throw new RuntimeException('access error!! [' . $status . ']');
    }

    $logger->info('藪吉倉庫 ログイン試行');

    // ログイン処理
    $form = $crawler->selectButton('ログイン')->form();
    $form['userId'] = $account;
    $form['userPass'] = $password;
    $form['accountId'] = '';
    $form['url'] = '';
    $form['loginSubmit'] = 'true';

    $crawler = $client->submit($form); // ログイン

    $status = $client->getResponse()->getStatus();
    $uri = $client->getRequest()->getUri();
    $logger->info('status: ' . $status);
    $logger->info('uri: ' . $uri);
    if ($status !== 200) {
      throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
    }

    if ($status !== 200 || $uri !== 'https://webat101.lisa-c.jp/yabuyoshi/menuItem.html') {
      throw new \RuntimeException(sprintf('ログインに失敗しました。[%s][%s][%s]', $status, $uri, $client->getResponse()->getContent()));
    }

    $logger->info('藪吉倉庫ログイン成功');

    return $crawler;
  }



  /**
   * NETSEA ログイン
   * @param WebClient $client
   * @param String $accountName
   * @return Crawler
   */
  public function netseaLogin($client, $accountName)
  {
    $container = $this->container;
    $logger = $this->logger;
    if (!$container || !$logger) {
      throw new RuntimeException('no container and no logger.');
    }

    // ログイン情報
    $loginUrl = $container->getParameter('netsea_site_login_url');

    $accountInfo = $container->getParameter('netsea_site_account');
    $account = $accountInfo[$accountName]['account'];
    $password = $accountInfo[$accountName]['password'];

    $logger->info('NETSEAログイン画面アクセス');

    $crawler = $client->request('get', $loginUrl);
    $status = $client->getResponse()->getStatus();
    if ($status !== 200) {
      throw new RuntimeException('access error!! [' . $status . ']');
    }

    // すでにログイン中ならOK
    if ($this->isNetseaLoggedIn($crawler)) {
      $logger->info('NETSEAログイン中。ログアウト誤判定。');
      return $crawler;
    }

    $logger->info('NETSEAログイン試行');

    try {
      $form = $crawler->selectButton('ログインする')->form();
    } catch (\InvalidArgumentException $e) {

      $now = new \DateTime();
      @file_put_contents('/tmp/netsea_login_error_' . $now->format('YmdHis') . '.html', $crawler->html()); // FOR DEBUG
      // FOR DEBUG
      throw new \RuntimeException('ログインフォームが見つかりませんでした。 ' . $e->getMessage() . '[' . $loginUrl . ']');
    }

    $form['login_id'] = $account;
    $form['password'] = $password;

    $crawler = $client->submit($form); // ログイン

    /** @var Response $response */
    $response = $client->getResponse();
    $status = $response->getStatus();
    $uri = $client->getRequest()->getUri();
    if ($status !== 200 || ($uri !== 'https://www.netsea.jp/dap/sv/LoginProc' && $uri !== 'https://www.netsea.jp')) {
      throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
    }

    // リダイレクト先 取得
    try {
      $aTag = $crawler->filter('a');
      $url = $aTag->attr('href');

      // リダイレクト
      if ($url && preg_match('|//www.netsea.jp|', $url)) {
        $crawler = $client->request('GET', $url);
      }

    } catch (\InvalidArgumentException $e) {
      $logger->error($response->getContent());
      throw new RuntimeException('ログイン後の遷移が正しくありません（ログイン失敗？）。 ' . $e->getMessage());
    }

    /** @var Response $response */
    $response = $client->getResponse();
    $status = $response->getStatus();
    $uri = $client->getRequest()->getUri();
    if ($status !== 200) {
      // この状況がよくわからないので、一旦スルーしてみる。
      // throw new RuntimeException('login error (リダイレクト先エラー)!! [' . $status . '][' . $uri . ']');
      $logger->warning('NETSEA ログイン時のリダイレクト先でstatusが200じゃない。正常？ [' . $status . ']' . '[' . $uri . ']' );
    }

    $logger->info('NETSEAログイン成功');

    return $crawler;
  }

  /**
   * NETSEA ログアウト
   *
   * @param WebClient $client
   * @return Crawler
   */
  public function netseaLogout($client)
  {
    return $client->request('get', 'http://www.netsea.jp/slink/dap/sv/Logout');
  }

  /**
   * NETSEA ログイン中判定
   * @param Crawler $crawler
   * @return bool
   */
  public function isNetseaLoggedIn($crawler)
  {
    return strpos($crawler->text(), 'バイヤーID：12630') !== false;
  }




  /**
   * SUPER DELIVERY ログイン
   * @param WebClient $client
   * @param String $accountName
   * @return Crawler
   */
  public function superDeliveryLogin($client, $accountName)
  {
    $container = $this->container;
    $logger = $this->logger;
    if (!$container || !$logger) {
      throw new \RuntimeException('no container and no logger.');
    }

    // ログイン情報
    $loginUrl = $container->getParameter('super_delivery_site_login_url');

    $accountInfo = $container->getParameter('super_delivery_site_account');
    $account = $accountInfo[$accountName]['account'];
    $password = $accountInfo[$accountName]['password'];

    $logger->info('SUPER DELIVERYログイン画面アクセス');

    $crawler = $client->request('get', $loginUrl);
    $status = $client->getResponse()->getStatus();
    if ($status !== 200) {
      throw new RuntimeException('access error!! [' . $status . ']');
    }

    $logger->info('SUPER DELIVERYログイン試行');

    try {
      $form = $crawler->filter('#jsp-tiles-certification-c form input[type="image"]')->form();
    } catch (\InvalidArgumentException $e) {
      throw new \RuntimeException('ログインフォームが見つかりませんでした。 ' . $e->getMessage());
    }

    $form['identification'] = $account;
    $form['password'] = $password;

    $crawler = $client->submit($form); // ログイン

    /** @var Response $response */
    $response = $client->getResponse();
    $status = $response->getStatus();
    $uri = $client->getRequest()->getUri();
    if ($status !== 200 || $uri !== 'http://www.superdelivery.com/' || strpos($crawler->filter('#headerMemberPanel .com-name')->text(), 'Ｐｌｕｓ　Ｎａｏ' === false) ) {
      throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
    }

    $logger->info('SUPER DELIVERYログイン成功 [' . $crawler->filter('#headerMemberPanel .com-name')->text() . ']');

    return $crawler;
  }


  /**
   * Vivica Duo ログイン
   * @param WebClient $client
   * @param String $accountName
   * @return Crawler
   */
  public function vivicaDuoLogin($client, $accountName)
  {
    $container = $this->container;
    $logger = $this->logger;
    if (!$container || !$logger) {
      throw new RuntimeException('no container and no logger.');
    }

    // ログイン情報
    $loginUrl = $container->getParameter('vivica_duo_site_login_url');

    $accountInfo = $container->getParameter('vivica_duo_site_account');
    $account = $accountInfo[$accountName]['account'];
    $password = $accountInfo[$accountName]['password'];

    $logger->info('Vivica Duoログイン画面アクセス');

    $crawler = $client->request('get', $loginUrl);
    $status = $client->getResponse()->getStatus();
    if ($status !== 200) {
      throw new RuntimeException('access error!! [' . $status . ']');
    }

    $logger->info('Vivica Duoログイン試行');

    try {
      $form = $crawler->selectButton('ログイン')->form();
    } catch (\InvalidArgumentException $e) {
      throw new \RuntimeException('ログインフォームが見つかりませんでした。 ' . $e->getMessage());
    }

    // 先方では、何故かJavaScriptで差し込む実装になっている。
    $form->getNode()->setAttribute('action', 'https://members.shop-pro.jp/?mode=members_login');

    $form['login_email'] = $account;
    $form['login_password'] = $password;

    $crawler = $client->submit($form); // ログイン

    /** @var Response $response */
    $response = $client->getResponse();
    $status = $response->getStatus();
    $uri = $client->getRequest()->getUri();
    if ($status !== 200 || $uri !== 'http://www.vsvivica.com/' || strpos($crawler->filter('#btn_members_logout')->text(), '石田慶子' === false) ) {
      throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
    }

    $logger->info('Vivica Duoログイン成功 [' . $crawler->filter('#btn_members_logout')->text() . ']');

    return $crawler;
  }


  /**
   * AKF ログイン
   * @param WebClient $client
   * @param String $accountName
   * @return Crawler
   */
  public function akfLogin($client, $accountName)
  {
    $container = $this->container;
    $logger = $this->logger;
    if (!$container || !$logger) {
      throw new RuntimeException('no container and no logger.');
    }

    // ログイン情報
    $loginUrl = $container->getParameter('akf_site_login_url');

    $accountInfo = $container->getParameter('akf_site_account');
    $account = $accountInfo[$accountName]['account'];
    $password = $accountInfo[$accountName]['password'];

    $logger->info('AKFログイン画面アクセス');

    $logger->info($loginUrl);
    $logger->info(print_r($accountInfo, true));

    $crawler = $client->request('get', $loginUrl);
    $status = $client->getResponse()->getStatus();
    if ($status !== 200) {
      throw new RuntimeException('access error!! [' . $status . ']');
    }

    $logger->info('AKFログイン試行');

    try {
      $form = $crawler->selectButton('ログイン')->form();
    } catch (\InvalidArgumentException $e) {
      throw new \RuntimeException('ログインフォームが見つかりませんでした。 ' . $e->getMessage());
    }

    $form['email'] = $account;
    $form['password'] = $password;

    $crawler = $client->submit($form); // ログイン

    /** @var Response $response */
    $response = $client->getResponse();
    $status = $response->getStatus();
    $uri = $client->getRequest()->getUri();

    // ログイン成功時のリダイレクト先を取得(meta)
    $refreshMeta = $crawler->filterXpath('//meta[@http-equiv="refresh"]');
    if ($refreshMeta->count() && preg_match('/url=([^\s]+)/', $refreshMeta->attr('content'), $m)) {
      $refreshUrl = $m[1];
      $logger->info('akf web check: meta refresh: ' . $refreshUrl);

      if ($status !== 200 || !preg_match('!http://www.akf-japan.jp(?:/index.php)?(?:/member-login)?!', $uri) || !strlen($refreshUrl) ) {
        throw new RuntimeException('login error!! [' . $status . '][' . $uri . ']');
      }

      $crawler = $client->request('GET', $refreshUrl);
      /** @var Response $response */
      $response = $client->getResponse();
      $status = $response->getStatus();
      $uri = $client->getRequest()->getUri();

    // 直接リダイレクトできている場合
    } else {
      // do nothing
    }

    $logger->info($uri);
    $logger->info($status);

    $signInName = '';
    $signInBox = $crawler->filter('li.h_signout span.nav_label');
    if ($signInBox->count()) {
      $signInName = $signInBox->text();
    }
//    if ($status !== 200 || !preg_match('|^http://www.akf-japan.jp/?$|', $uri) || strpos($signInName, '石田　奈緒哉') === false ) {
    if ($status !== 200 || !preg_match('|^https://www.akf-japan.jp/?$|', $uri) || strpos($signInName, 'ログアウト') === false ) {
      throw new RuntimeException('login redirect error!! [' . $status . '][' . $uri . '][' . $signInName . ']');
    }

    $logger->info('AKFログイン成功 [' . $signInName . ']');

    return $crawler;
  }

  /**
   * Yahoo API 利用可否チェック
   * @return boolean
   */
  public function isEnabledYahooApi()
  {
    $lastAuth = $this->getYahooAccessTokenWithRefresh();
    return ! (is_null($lastAuth));
  }

  /**
   * Yahoo代理店用 Yahoo API 利用可否チェック
   * @param SymfonyUserYahooAgent $yahooAgent
   * @return bool
   */
  public function isEnabledYahooAgentYahooApi($yahooAgent)
  {
    $lastAuth = $this->getYahooAccessTokenWithRefresh($yahooAgent);
    return ! (is_null($lastAuth));
  }


  /**
   * Yahoo 有効なアクセストークンを取得（というか毎回リフレッシュ）
   * 1. 有効なアクセストークンの有無 -> 省略。（残り時間のチェック等、微妙になる可能性があるため）
   * 2. リフレッシュトークンによるアクセストークン要求 ※ここから開始
   * @param SymfonyUserYahooAgent $yahooAgent
   * @return \MiscBundle\Entity\TbYahooApiAuth|null
   */
  public function getYahooAccessTokenWithRefresh($yahooAgent = null)
  {
    $container = $this->container;
    $logger = $this->logger;
    if (!$container || !$logger) {
      throw new RuntimeException('no container and no logger.');
    }

    if ($yahooAgent) {
      $client = $this->getYahooApiClient($yahooAgent);

      /** @var \MiscBundle\Entity\Repository\TbYahooAgentApiAuthRepository $repo */
      $repo = $container->get('doctrine')->getRepository('MiscBundle:TbYahooAgentApiAuth');
      $lastAuth = $repo->findLatestAuth($yahooAgent);
    } else {
      $client = $this->getYahooApiClient();

      /** @var \MiscBundle\Entity\Repository\TbYahooApiAuthRepository $repo */
      $repo = $container->get('doctrine')->getRepository('MiscBundle:TbYahooApiAuth');
      $lastAuth = $repo->findLatestAuth();
    }

    // リフレッシュトークンによるアクセストークン要求
    if ($lastAuth) {
      try {

        $logger->info('Yahoo API: refresh token ... : ' . $lastAuth->getRefreshToken());

        $client->refreshAccessToken($lastAuth->getRefreshToken());

        // 認証情報更新
        $lastAuth->setAccessToken($client->getAccessToken());
        $lastAuth->setExpirationWithSecondsTerm($client->getAccessTokenExpiration());
        $container->get('doctrine')->getManager()->flush();

      } catch ( TokenException $te ) {

        $lastAuth = null;

        $logger->error($te->getMessage());
        $logger->error($te->getTraceAsString());
        if( $te->invalidGrant() ) {
          $logger->error('リフレッシュトークンの有効期限切れです。');
        }
      }
    }

    return $lastAuth;
  }


  /**
   * @param array $config
   * @param \Symfony\Component\BrowserKit\CookieJar $cookies
   * @return GoutteClientCustom
   */
  public function getWebClient($config = array(), $cookies = null)
  {
    $config = array_merge([
      // 'useragent' => 'forest batch ua/1.0'
        'useragent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.71 Safari/537.36'
      , 'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.71 Safari/537.36'
    ], $config);

    $client = new GoutteClientCustom($config, null, $cookies);
    return $client;
  }
  
  /**
   * Yahooショッピング（プロ）API用の、アクセストークン付きクライアントを返却する
   * @param string|null $targetShop　対象店舗。
   *     TbShoppingMall::BATCH_SHOP_CODE_YAHOO_PLUSNAO, TbShoppingMall::BATCH_SHOP_CODE_YAHOO_KAWAEMON, BATCH_SHOP_CODE_YAHOO_OTORIYOSE。
   * @param array $goutteClientConfig goutte側でClientを生成する設定
   * @param unknown $cookies
   */
  public function getClientWithYahooAccessToken($targetShop, $goutteClientConfig = array(), $cookies = null) {
    $client = $this->getWebClient($goutteClientConfig, $cookies);
    
    // Yahoo API アクセストークン取得
    $auth = null;
    // おとりよせ
    if ($targetShop == TbShoppingMall::BATCH_SHOP_CODE_YAHOO_OTORIYOSE){
      
      $container = $this->container;
      /** @var SymfonyUserYahooAgentRepository $repo */
      $repo = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUserYahooAgent');
      
      /** @var SymfonyUserYahooAgent $shopAccount */
      $shopAccount = $repo->getActiveShopAccountByShopCode($targetShop);
      if (!$shopAccount) {
        throw new \RuntimeException("no account[$targetShop]");
      }
      $auth = $this->getYahooAccessTokenWithRefresh($shopAccount);
      
    // 通常
    } else {
      $auth = $this->getYahooAccessTokenWithRefresh();
    }
    if (!$auth) {
      throw new \RuntimeException('Yahoo API のアクセストークンが取得できませんでした(WEBからの認証が必要です)。処理を終了します。');
    }
    $client->setHeader('Authorization', sprintf('Bearer %s', $auth->getAccessToken()));
    return $client;
  }
  
  /**
   * NextEngine APIを実行するためのクライアントを生成して返す。
   * 
   * @return \ForestNeApiClient $apiClient NE APIクライアント
   */
  public function getForestNeApiClient() {
    $container = $this->container;
    $logger = $this->logger;
    if (!$container || !$logger) {
      throw new RuntimeException('no container and no logger.');
    }
        
    $apiInfo = $container->getParameter('ne_api');
    $clientId = $apiInfo['client_id'];
    $clientSecret = $apiInfo['client_secret'];
    $redirectUrl = $apiInfo['redirect_url'];
    
    /** @var \MiscBundle\Util\DbCommonUtil $commonUtil */
    $commonUtil = $container->get('misc.util.db_common');
    $accessToken = $commonUtil->getSettingValue('NE_API_ACCESS_TOKEN');
    if (!$accessToken) {
      $accessToken = null;
    }
    $refreshToken = $commonUtil->getSettingValue('NE_API_REFRESH_TOKEN');
    if (!$refreshToken) {
      $refreshToken = null;
    }
    
    $apiClient = new \ForestNeApiClient($clientId, $clientSecret, $redirectUrl, $accessToken, $refreshToken);
    $apiClient->setLogger($logger);
    
    $loginId = $commonUtil->getSettingValue('NE_API_LOGIN_ID');
    $loginPassword = $commonUtil->getSettingValue('NE_API_LOGIN_PASSWORD');
    
    $apiClient->setUserAccount($loginId, $loginPassword);
    return $apiClient;
  }

  /**
   * YConnectクライアントインスタンス生成
   * @param SymfonyUserYahooAgent $yahooAgent
   * @return YConnectClient
   */
  public function getYahooApiClient($yahooAgent = null)
  {
    return new YConnectClient( $this->getYahooCredential($yahooAgent) );
  }

  /**
   * @param SymfonyUserYahooAgent $yahooAgent
   * @return ClientCredential
   */
  private function getYahooCredential($yahooAgent = null)
  {
    // アプリケーションID, シークレット
    if ($yahooAgent) {
      $clientId     = $yahooAgent->getAppId();
      $clientSecret = $yahooAgent->getAppSecret();

    } else {
      $clientId     = $this->container->getParameter('yahoo_app_id');
      $clientSecret = $this->container->getParameter('yahoo_app_secret');
    }

    return new ClientCredential( $clientId, $clientSecret );
  }

  /**
   * Redmine REST API アクセス
   * @param string $method
   * @param string $url 「/」から始まる
   * @param array $data
   * @return string JOSN or XML
   */
  public function requestRedmineApi($method, $url, $data = [])
  {
    $apiKey = $this->container->getParameter('redmine_api_key');
    $baseUrl = $this->container->getParameter('redmine_api_url');

    $client = $this->getWebClient();

    // 認証用ヘッダをセット
    $client->setHeader('X-Redmine-API-Key', $apiKey);
    $client->setHeader('Content-Type', 'application/json');

    if (strtoupper($method) == 'POST') {
      $content = json_encode($data);
      $client->request($method, $baseUrl . $url, [], [], [], $content);
    } else {
      $client->request($method, $baseUrl . $url, $data);
    }


    $response = $client->getResponse();
    if (!preg_match('/^20/', $response->getStatus())) {
      throw new \RuntimeException(sprintf('%s: %s', $response->getStatus(), $response->getContent()));
    }

    return $response->getContent();
  }

  /**
   * スマレジ API URL取得
   */
  public function getSmaregiApiUrl()
  {
    return $this->container->getParameter('smaregi_api_url');
  }


  /**
   * スマレジ APIアクセスクライアント取得処理
   */
  public function getSmaregiApiClient()
  {
    $contractId = $this->container->getParameter('smaregi_api_contract_id');
    $accessToken = $this->container->getParameter('smaregi_api_access_token');

    $client = $this->getWebClient();

    $client->setHeader('Content-Type', 'application/x-www-form-urlencoded;charset=UTF-8');
    $client->setHeader('X_contract_id', $contractId);
    $client->setHeader('X_access_token', $accessToken);

    return $client;
  }

  /**
   * NextEngine 印刷件数差異取得
   */
  public function getNePrintCount($client)
  {
    $crawler = $client->request('get', 'https://main.next-engine.com/Userjyuchu/index');
    $isInvalidAccess = $this->isNeInvalidAccess($client->getResponse());
    if ($isInvalidAccess) {
      $logger->error("https://main.next-engine.com/Userjyuchu/index 不正アクセスエラー。");
    }
    
    // 件数取得
    $page = $crawler->filter('#accordion8 div.accordion-inner a');
    return intval(preg_replace('/\D/','',$page->text()));
  }
  
  /**
   * NextEngineの不正アクセスに該当しているのかをチェックし、不正アクセスエラーであれば true、それ以外であれば falseを返却する。
   * @param Response $response
   * @return boolean 不正アクセスエラーであれば true、それ以外であれば false
   */
  public function isNeInvalidAccess($response) {
    
    // ひとまずレスポンスから不正アクセス時のタイトル文字列を抽出するだけの簡単処理
    if (strpos($response->getContent(), '<title>不正なアクセスを検知しました') === false 
        && strpos($response->getContent(), '<title>Webページの有効期限が切れています') === false) {
      return false;
    } else {
      return true;
    }
  }
  
  /**
   * NextEngineの画面からCSRF情報を取得し、ヘッダ名と値の配列として返却する。
   * CSRF情報のない画面は、ヘッダ名・値に空文字が設定された配列を返却する。
   * 戻り値の形式は以下の通り。
   *   [
   *     'headerName' => 'X-CSRF-TOKEN'
   *     , 'value' => 'csrf_token_11215fbcb8dde0d2c78bd87c34c1ee6ca49d:049e4d0a4483e35bdbf8b0f8306a3b13'
   *   ];
   * NextEngineの仕様変更で、CSRF情報の渡し方が変わったり、不要になることもあり得るので
   * なくても、すくなくともUtil内ではエラーとしない。
   * CSRF情報が取れているかを確認するには、どちらかの値が空文字かをチェックすること。
   */
  public function getNeCsrfTokenInfo($crawler) {
    $csrfTokenInfo = [
      'headerName' => ''
      , 'value' => ''
    ];
    try {
      $tokenTag = $crawler->filter('#csrf-token-info');
      $tokenValue = $tokenTag->attr('data-value');
      $headerName = $tokenTag->attr('data-header-name');
      $csrfTokenInfo = [
        'headerName' => $headerName
        , 'value' => $tokenValue
      ];
    } catch (\Exception $e) {
      // do nothing
    }
    return $csrfTokenInfo;
  }
  
}
