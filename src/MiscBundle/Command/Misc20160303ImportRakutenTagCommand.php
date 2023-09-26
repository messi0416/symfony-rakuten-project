<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use forestlib\GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use MiscBundle\Entity\BatchLock;
use MiscBundle\Entity\Repository\BatchLockRepository;
use MiscBundle\Entity\Repository\TbDiscountListRepository;
use MiscBundle\Entity\Repository\TbPlusnaoproductdirectoryRepository;
use MiscBundle\Entity\Repository\TbRakutenCategoryForSalesRankingRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbDiscountList;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileLogger;
use MiscBundle\Util\MultiInsertUtil;
use MiscBundle\Util\StringUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;


class Misc20160303ImportRakutenTagCommand extends ContainerAwareCommand
{
  /** @var InputInterface */
  protected $input;

  /** @var OutputInterface */
  protected $output;

  /** @var BatchLogger */
  protected $logger;

  /** @var DbCommonUtil */
  protected $commonUtil;

  protected function configure()
  {
    $this
      ->setName('misc:20160303-import-rakuten-tag')
      ->setDescription('楽天タグ取込処理');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->input = $input;
    $this->output = $output;

    $this->logger = $this->getContainer()->get('misc.util.batch_logger');

    $container = $this->getContainer();

    $doctrine = $container->get('doctrine');
    // var_dump(get_class($doctrine));

    $this->commonUtil = $container->get('misc.util.db_common');

    $fileUtil = $container->get('misc.util.file');
    $output->writeln($fileUtil->getRootDir());

    // 商品用タグ
    $this->importProductTag();
    // 選択肢用タグ
    $this->importChoiceTag();

    $output->writeln('done!');
  }

  private function importProductTag()
  {
    $db = $this->getContainer()->get('doctrine')->getConnection('main');

    $sql = <<<EOD
      SELECT
          m.daihyo_syohin_code
        , d.`楽天ディレクトリID`
        , tag.`タグID`
      FROM tmp_rakuten_import_tag_product tag
      INNER JOIN tb_mainproducts m ON tag.商品番号 = m.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory d ON m.`NEディレクトリID` = d.`NEディレクトリID`
      WHERE tag.`タグID` <> ''
EOD;
    $stmt = $db->query($sql);

    // 一括insert
    $total = 0;
    $count = 0;
    $originalInsertBuilder = new MultiInsertUtil("tb_rakuten_tag_mainproducts", [
      'fields' => [
          'daihyo_syohin_code' => \PDO::PARAM_STR
        , 'ディレクトリID' => \PDO::PARAM_STR
        , 'タグID' => \PDO::PARAM_STR
      ]
      , 'prefix' => "INSERT IGNORE INTO"
    ]);

    foreach($stmt as $row) {

      if (!isset($builder)) {
        $builder = clone $originalInsertBuilder;
      }

      $tags = explode('/', $row['タグID']);
      // もし配列以外が帰ってきたらスキップ
      if (!is_array($tags) || !$tags) {
        continue;
      }

      foreach($tags as $tag) {
        $item =  [
            'daihyo_syohin_code'    => $row['daihyo_syohin_code']
          , 'ディレクトリID'         => $row['楽天ディレクトリID']
          , 'タグID'                => $tag
        ];

        $builder->bindRow($item);
        $total++;
      }

      // 分割 INSERT（を利用したUPDATE） (1000件ずつ)
      if (++$count >= 1000) {
        if (count($builder->binds())) {
          $stmt = $db->prepare($builder->toQuery());
          $builder->bindValues($stmt);
          $stmt->execute();
        } else {
          throw new \RuntimeException('something wrong. aborted');
        }

        unset($builder);
        $count = 0;
      }
    }

    // INSERT 残り
    if ($count && isset($builder) && count($builder->binds())) {
      $stmt = $db->prepare($builder->toQuery());
      $builder->bindValues($stmt);
      $stmt->execute();
    }
  }

  private function importChoiceTag()
  {
    $db = $this->getContainer()->get('doctrine')->getConnection('main');

    $sql = <<<EOD
      SELECT
          m.daihyo_syohin_code
        , p.ne_syohin_syohin_code
        , d.`楽天ディレクトリID`
        , c.`タグID`
      FROM tmp_rakuten_import_tag_choice c
      INNER JOIN tb_productchoiceitems p ON c.`商品コード` = p.ne_syohin_syohin_code
      INNER JOIN tb_mainproducts m ON p.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory d ON m.`NEディレクトリID` = d.`NEディレクトリID`
      WHERE c.`タグID` <> ''
EOD;
    $stmt = $db->query($sql);

    // 一括insert
    $total = 0;
    $count = 0;
    $originalInsertBuilder = new MultiInsertUtil("tb_rakuten_tag_productchoiceitems", [
      'fields' => [
          'ne_syohin_syohin_code' => \PDO::PARAM_STR
        , 'ディレクトリID' => \PDO::PARAM_STR
        , 'タグID' => \PDO::PARAM_STR
        , 'daihyo_syohin_code' => \PDO::PARAM_STR
      ]
      , 'prefix' => "INSERT IGNORE INTO"
    ]);

    foreach($stmt as $row) {

      if (!isset($builder)) {
        $builder = clone $originalInsertBuilder;
      }

      $tags = explode('/', $row['タグID']);
      // もし配列以外が帰ってきたらスキップ
      if (!is_array($tags) || !$tags) {
        continue;
      }

      foreach($tags as $tag) {
        $item =  [
            'ne_syohin_syohin_code' => $row['ne_syohin_syohin_code']
          , 'ディレクトリID'         => $row['楽天ディレクトリID']
          , 'タグID'                => $tag
          , 'daihyo_syohin_code'    => $row['daihyo_syohin_code']
        ];

        $builder->bindRow($item);
        $total++;
      }

      // 分割 INSERT（を利用したUPDATE） (1000件ずつ)
      if (++$count >= 1000) {
        if (count($builder->binds())) {
          $stmt = $db->prepare($builder->toQuery());
          $builder->bindValues($stmt);
          $stmt->execute();
        } else {
          throw new \RuntimeException('something wrong. aborted');
        }

        unset($builder);
        $count = 0;
      }
    }

    // INSERT 残り
    if ($count && isset($builder) && count($builder->binds())) {
      $stmt = $db->prepare($builder->toQuery());
      $builder->bindValues($stmt);
      $stmt->execute();
    }
  }

}