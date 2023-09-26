<?php
/**
 * バッチ処理 Amazonメイン画像仮作成処理処理
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use Doctrine\ORM\EntityManager;
use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\ProductImagesAmazon;
use MiscBundle\Entity\Repository\ProductImagesRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbLog;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class Misc20160824CreateAmazonMainImagesCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('misc:20160824-create-amazon-main-images')
      ->setDescription('Amazonメイン画像仮作成処理処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('Amazonメイン画像仮作成処理処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    try {

      $dbMain = $this->getDb('main');
      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
          'message' => null
      ];

      $logExecTitle = sprintf('Amazonメイン画像仮作成処理処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始')->setLogLevel(TbLog::DEBUG));

      $sql = <<<EOD
        SELECT
          m.daihyo_syohin_code
        FROM tb_mainproducts m
        LEFT JOIN (
          SELECT
              pci.daihyo_syohin_code
            , SUM(pl.stock) AS stock
          FROM tb_product_location pl
          INNER JOIN tb_productchoiceitems pci ON pl.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
          WHERE pl.position >= 0
            AND pl.stock > 0
          GROUP BY pci.daihyo_syohin_code
        ) T3 ON m.daihyo_syohin_code = T3.daihyo_syohin_code
        LEFT JOIN (
          SELECT
            DISTINCT pci.daihyo_syohin_code
          FROM tb_individualorderhistory id
          INNER JOIN tb_productchoiceitems pci ON id.`商品コード` = pci.ne_syohin_syohin_code
          WHERE id.`注残計` > 0
        ) T2 ON m.daihyo_syohin_code = T2.daihyo_syohin_code
        LEFT JOIN (
          SELECT
            DISTINCT a.daihyo_syohin_code
          FROM tb_sales_detail_analyze a
          WHERE a.受注日 >= DATE_ADD(CURRENT_DATE, INTERVAL -3 MONTH)
        ) T ON m.daihyo_syohin_code = T.daihyo_syohin_code
        WHERE T3.stock > 0
           OR T.daihyo_syohin_code IS NOT NULL
           OR T2.daihyo_syohin_code IS NOT NULL
        ORDER BY m.daihyo_syohin_code
EOD;
      $stmt = $dbMain->query($sql);

      /** @var ProductImagesRepository $repoImage */
      $repoImage = $this->getDoctrine()->getRepository('MiscBundle:ProductImages');
      $imageDir = $container->getParameter('product_image_original_dir');
      $amazonImageDir = $container->getParameter('product_image_amazon_dir');

      $fs = new FileSystem();
      /** @var EntityManager $em */
      $em = $this->getDoctrine()->getManager('main');

      while($code = $stmt->fetchColumn(0)) {

        // 画像データの取得
        /** @var ProductImages $image */
        $image = $repoImage->findOneBy(['daihyo_syohin_code' => $code, 'code' => 'p001']);
        if (!$image) {
          $logger->error('no image !! : ' . $code);
          continue;
        }
        $imagePath = sprintf('%s/%s', $imageDir, $image->getFileDirPath());

        $logger->info('code : ' . $code);
        $logger->info('image: ' . $imagePath);
        $logger->info('exists: ' . ($fs->exists($imagePath) ? 'true' : 'false'));

        // データ作成
        $amazonImage = new ProductImagesAmazon();
        $amazonImage->setDaihyoSyohinCode($code);
        $amazonImage->setCode('amazonMain');
        $amazonImage->setDirectory('');
        $amazonImage->setFilename(strtolower($image->getFilename()));
        $amazonImage->setAddress(sprintf('https://%s/amazon_images/%s', $this->getContainer()->getParameter('host_plusnao'), $amazonImage->getFilename()));

        // ファイルのコピー
        $amazonImagePath = sprintf('%s/%s', $amazonImageDir, $amazonImage->getFileDirPath());
        $fs->copy($imagePath, $amazonImagePath);
        $logger->info('image: ' . $amazonImagePath);

        // 類似画像チェック用 文字列作成・格納（上書き） → 不要

        $em->persist($amazonImage);
      }
      $em->flush();

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setLogLevel(TbLog::DEBUG));
      $logger->logTimerFlush();

      $logger->info('Amazonメイン画像仮作成処理処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('Amazonメイン画像仮作成処理処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('Amazonメイン画像仮作成処理処理 エラー', 'Amazonメイン画像仮作成処理処理 エラー', 'エラー終了')->setInformation($e->getMessage())->setLogLevel(TbLog::DEBUG)
        , true, 'Amazonメイン画像仮作成処理処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


