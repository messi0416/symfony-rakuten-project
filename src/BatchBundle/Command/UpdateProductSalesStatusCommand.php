<?php

namespace BatchBundle\Command;

use MiscBundle\Entity\TbProcessExecuteLog;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 代表商品販売ステータス更新処理
 * 集計中も利用できるように、更新用テーブルで更新して表示用テーブルに切り替え利用する
 * 更新中テーブルのロックが発生するため、ピッキングが行われる時間は実行しない
 */
class UpdateProductSalesStatusCommand extends PlusnaoBaseCommand
{
  protected function configure()
  {
    $this
      ->setName('batch:update-product-sales-status')
      ->setDescription('代表商品販売ステータス更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL, '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN);
  }

  protected function initializeProcess(InputInterface $input)
  {
    $this->commandName = '代表商品販売ステータス更新処理';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');
    $dbMain->query("TRUNCATE tmp_aggregate_mainproducts_sales_status");

    $sql = <<<EOD
        INSERT INTO
            tmp_aggregate_mainproducts_sales_status
        SELECT
            m.daihyo_syohin_code,
            COALESCE(P.orderable_flg, 0) AS orderable_flg,
            COALESCE(P.active_flg, 0) AS active_flg,
            CASE
                WHEN P2.max_zaiko_teisu = 0 THEN 0
                ELSE 1
            END AS zaiko_teisu_exist_flg,
            r.baika_tanka,
            COALESCE(SUBSTRING_INDEX(d.rakutencategories_1, '\\\\', 1), ''),
            COALESCE(
                REPLACE (
                    SUBSTRING(
                        SUBSTRING_INDEX(d.rakutencategories_1, '\\\\', 2),
                        CHAR_LENGTH(
                        SUBSTRING_INDEX(d.rakutencategories_1, '\\\\', 1)
                        ) + 1
                    ),
                    '\\\\',
                    ''
                ),
                ''
            ),
            vm.sire_code,
            vm.sire_name
        FROM
            tb_mainproducts m
            LEFT JOIN (
                /* 紐づくSKUの状態から、代表商品として稼働中か否かを判定 */
                SELECT
                    pci.daihyo_syohin_code,
                    MAX(CASE WHEN COALESCE(pci.受発注可能フラグ, 0) <> 0 THEN 1
                             ELSE 0 END) AS orderable_flg,
                    MAX(CASE WHEN pci.zaiko_teisu <> 0 THEN 1
                             WHEN pci.在庫数 <> 0 THEN 1
                             WHEN pci.発注残数 <> 0 THEN 1
                             ELSE 0 END) AS active_flg
                FROM
                    tb_productchoiceitems pci
                GROUP BY
                    pci.daihyo_syohin_code
            ) P
                ON m.daihyo_syohin_code = P.daihyo_syohin_code
            LEFT JOIN (
                SELECT
                    pci.daihyo_syohin_code,
                    MAX(pci.zaiko_teisu) AS max_zaiko_teisu
                FROM
                    tb_productchoiceitems pci
                GROUP BY
                    pci.daihyo_syohin_code
            ) P2
                ON m.daihyo_syohin_code = P2.daihyo_syohin_code
            INNER JOIN tb_rakuteninformation r ON m.daihyo_syohin_code = r.daihyo_syohin_code
            LEFT JOIN tb_plusnaoproductdirectory d ON m.NEディレクトリID = d.NEディレクトリID
            LEFT JOIN tb_vendormasterdata vm ON m.sire_code = vm.sire_code
        GROUP BY
            m.daihyo_syohin_code;
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
        /* セット商品の時は、更に紐づくセットSKUで稼働しているものが1件でも有れば稼働中とする */
        /* セットSKUは、全構成品を、各在庫数、在庫定数、発注残数から作成可能な状態であれば、稼働していると考える。 */
        UPDATE
            tmp_aggregate_mainproducts_sales_status s
            INNER JOIN (
                /* セット代表商品として、稼働中かどうか */
                SELECT
                    s2.daihyo_syohin_code,
                    CASE WHEN MAX(T.active_flg) <> 0 THEN 1
                         ELSE 0 END AS active_flg
                FROM
                    tmp_aggregate_mainproducts_sales_status s2
                    INNER JOIN (
                        /* セットSKU毎に、稼働しているか判定 */
                        SELECT
                            pci.daihyo_syohin_code,
                            pci.ne_syohin_syohin_code AS set_sku,
                            MIN(CASE
                                    WHEN (pci_detail.`在庫数` + pci_detail.zaiko_teisu + pci_detail.`発注残数`) >= d.num THEN 1
                                    ELSE 0
                                END) AS active_flg
                        FROM
                            tb_productchoiceitems pci
                            INNER JOIN tb_mainproducts m
                                ON pci.daihyo_syohin_code = m.daihyo_syohin_code
                            LEFT JOIN tb_set_product_detail d
                                ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
                            LEFT JOIN tb_productchoiceitems pci_detail
                                ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
                        WHERE
                            m.set_flg <> 0
                        GROUP BY
                            pci.daihyo_syohin_code, set_sku
                    ) T
                        ON s2.daihyo_syohin_code = T.daihyo_syohin_code
                GROUP BY
                    s2.daihyo_syohin_code
            ) A
                ON s.daihyo_syohin_code = A.daihyo_syohin_code
        SET
            s.active_flg = A.active_flg
        WHERE
            s.active_flg <> A.active_flg;
EOD;
    $dbMain->query($sql);

    // テーブル名入れ替え (RENAME スワップ)
    // 最終的に残るのは tb_mainproducts_sales_status と tmp_aggregate_mainproducts_sales_status
    $sql = <<<EOD
        RENAME TABLE
          tb_mainproducts_sales_status TO tmp_mainproducts_sales_status,
          tmp_aggregate_mainproducts_sales_status TO tb_mainproducts_sales_status,
          tmp_mainproducts_sales_status TO tmp_aggregate_mainproducts_sales_status;
EOD;
    $dbMain->query($sql);
  }
}
