<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;


use BatchBundle\Command\CommandBaseTrait;
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


class Misc20160331UpdateProductPriceLogCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('misc:20160331-update-product-price-log')
      ->setDescription('商品価格履歴ログ 更新処理:仕入先原価率補完');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->input = $input;
    $this->output = $output;
    $logger = $this->getLogger();

    $dbLog = $this->getDb('log');
    $mainDbName = $this->getDb('main')->getDatabase();


    // やたらデータ量が多いため、処理のブロックを避けるため1日ずつ実行
    $sql = <<<EOD
      SELECT
          log_date
      FROM
        tb_product_price_log
      GROUP BY
        log_date
      ORDER BY
        log_date DESC
EOD;
    $stmtDate = $dbLog->query($sql);
    while($date = $stmtDate->fetchColumn(0)) {
      $logger->info(sprintf('商品価格履歴更新バッチ %s 開始', $date));

      $sql = <<<EOD
        UPDATE tb_product_price_log pl
        INNER JOIN {$mainDbName}.tb_mainproducts m ON pl.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_vendor_cost_rate_log vl ON pl.log_date = vl.log_date AND m.sire_code = vl.sire_code
        SET pl.vendor_cost_rate = vl.cost_rate
        WHERE pl.log_date = :logDate
EOD;
      $stmt = $dbLog->prepare($sql);
      $stmt->bindValue(':logDate', $date);
      $stmt->execute();

      $logger->info(sprintf('商品価格履歴更新バッチ %s 終了', $date));
      sleep(10);
    }

    $output->writeln('done!');
  }
}
