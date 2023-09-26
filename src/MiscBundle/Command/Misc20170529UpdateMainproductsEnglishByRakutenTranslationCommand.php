<?php
/**
 * 商品英語情報 楽天自動翻訳 流し込み 暫定処理
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\MultiInsertUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Misc20170529UpdateMainproductsEnglishByRakutenTranslationCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  protected function configure()
  {
    $this
      ->setName('misc:20170529-update-mainproducts-english-by-rakuten-translation')
      ->setDescription('商品英語情報 楽天自動翻訳 流し込み 暫定処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('商品英語情報 楽天自動翻訳 流し込み 暫定処理を開始しました。');

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

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $commonUtil = $this->getDbCommonUtil();


      // 商品メイン情報 （tb_mainproducts_english 流し込み）

      $dbMain->exec("DROP TEMPORARY TABLE IF EXISTS tmp_work_mainproducts_english_convert;");
      $sql = <<<EOD
        CREATE TEMPORARY TABLE tmp_work_mainproducts_english_convert (
            daihyo_syohin_code VARCHAR(30) NOT NULL PRIMARY KEY
          , title VARCHAR(255) NOT NULL DEFAULT ''
          , description TEXT
        ) Engine=InnoDB DEFAULT CHARACTER SET utf8;
EOD;
      $dbMain->exec($sql);

      $sql = <<<EOD
        SELECT
             m.daihyo_syohin_code  AS daihyo_syohin_code
           , re.name               AS title
           , re.description_1      AS description
        FROM tb_mainproducts m
        INNER JOIN tb_product_rakuten_translation_english re ON m.daihyo_syohin_code = re.item_url
        LEFT JOIN tb_mainproducts_english e ON m.daihyo_syohin_code = e.daihyo_syohin_code
        WHERE re.row_type = ''
          AND ( e.daihyo_syohin_code IS NULL OR e.title = '' )
EOD;
      $stmt = $dbMain->query($sql);

      // 一括insert
      $insertBuilder = new MultiInsertUtil("tmp_work_mainproducts_english_convert", [
        'fields' => [
            'daihyo_syohin_code' => \PDO::PARAM_STR
          , 'title' => \PDO::PARAM_STR
          , 'description' => \PDO::PARAM_STR
        ]
      ]);

      $commonUtil->multipleInsert($insertBuilder, $dbMain, $stmt, function ($row) {

        $item = $row;
        // HTMLを除去し、実体参照を戻す
        $item['title'] = html_entity_decode($item['title'], ENT_QUOTES|ENT_HTML401, 'UTF-8');
        $item['description'] = html_entity_decode(strip_tags($item['description']), ENT_QUOTES|ENT_HTML401, 'UTF-8');

        return $item;

      }, 'foreach');


      $sql = <<<EOD
        INSERT INTO tb_mainproducts_english (
            daihyo_syohin_code
          , title
          , description
        )
        SELECT
             m.daihyo_syohin_code
           , re.title
           , re.description
        FROM tb_mainproducts m
        INNER JOIN tmp_work_mainproducts_english_convert re ON m.daihyo_syohin_code = re.daihyo_syohin_code
        ORDER BY re.daihyo_syohin_code
        ON DUPLICATE KEY UPDATE
              title = VALUES(title)
            , description = VALUES(description)
EOD;
      $dbMain->exec($sql);

      // 商品SKU情報 （tb_productchoiceitems 流し込み）
      $sql = <<<EOD
        UPDATE
        tb_productchoiceitems pci
        INNER JOIN tb_product_rakuten_translation_english e ON pci.daihyo_syohin_code = e.item_url
                                                           AND pci.colname = e.variant_x_value_ja
                                                           AND pci.rowname = e.variant_y_value_ja
        SET pci.colname_en = e.variant_x_value
        WHERE pci.colname_en = ''
EOD;
      $dbMain->exec($sql);

      $sql = <<<EOD
        UPDATE
        tb_productchoiceitems pci
        INNER JOIN tb_product_rakuten_translation_english e ON pci.daihyo_syohin_code = e.item_url
                                                           AND pci.colname = e.variant_x_value_ja
                                                           AND pci.rowname = e.variant_y_value_ja
        SET pci.rowname_en = e.variant_y_value
        WHERE pci.rowname_en = ''
EOD;
      $dbMain->exec($sql);


      $logger->info('商品英語情報 楽天自動翻訳 流し込み 暫定処理を終了しました。');

    } catch (\Exception $e) {

      $logger->error('商品英語情報 楽天自動翻訳 流し込み 暫定処理 エラー:' . $e->getMessage());

      throw $e;
    }

    return 0;

  }
}


