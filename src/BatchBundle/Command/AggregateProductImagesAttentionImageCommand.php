<?php
namespace BatchBundle\Command;

use MiscBundle\Entity\TbProcessExecuteLog;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * 商品画像アテンション画像集計処理。
 * 商品画像アテンション画像テーブルを更新する。
 *
 * 各代表商品ごとに、登録された商品画像の最後の1件をアテンション画像候補として、そのハッシュを取得する。
 * ハッシュ値を元に、同一画像が使用されている回数などを集計し、ProductImagesAttentionテーブルに登録・更新する。
 * （既にハッシュ値が登録されていれば、件数のみ更新。登録されていなければ登録）
 *
 * 主に2つの使いかたがある。
 *  (1) 全ての末尾画像のmd5ハッシュを取得し、まずそれで 商品画像テーブルを更新し、その後それを元に商品画像アテンション画像テーブルを更新する。
 *      （初回実行や、直接サーバ画像が編集された、通常の画像アップロード時のmd5ハッシュ登録に間違いがあるなど、全ファイルに再チェックを行う場合）
 *      （パラメータ指定なしで実行）
 *  (2) 既に商品画像テーブルに入っているmd5ハッシュを元に、商品画像アテンション画像テーブルを更新する（--attention-table-update-only=1）。
 *      （通常のデイリー実行を想定）
 *
 * その他、デバッグ用のパラメータをいくつか用意している。
 */
class AggregateProductImagesAttentionImageCommand extends PlusnaoBaseCommand
{


  protected function configure()
  {
    $this
      ->setName('batch:aggregate-product-images-attention')
      ->setDescription('商品画像アテンション画像集計処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
      ->addOption('tsv-file-path', null, InputOption::VALUE_OPTIONAL, "生成するTSVファイルパス。ファイルを残す場合や、存在するファイルを読み込む場合は指定。\n"
          . "指定がなければテンポラリファイルを利用する。\n"
          . "db-update-onlyと併用しない場合、このファイルは上書きされる")
      ->addOption('file-create-only', 0, InputOption::VALUE_OPTIONAL, 'デバッグ用。ファイル生成のみで終了するか。1の場合ファイルの生成のみ行い、DBは更新しない')
      ->addOption('db-update-only', 0, InputOption::VALUE_OPTIONAL, 'デバッグ用。DBデータ更新のみで終了するか。1の場合ファイル生成は行わない。その場合 --tsv-file-path と併用すること ')
      ->addOption('attention-table-update-only', 0, InputOption::VALUE_OPTIONAL, 'DBデータ更新のみの中でも、既に登録済みのproduct_imagesのmd5hashを元に、アテンション画像テーブルの更新のみを行う')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
    ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = '商品画像アテンション画像集計処理';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    /** @var $dbMain Doctrine\DBAL\Connection */
    $dbMain = $this->getDb('main');

    $tsvFilePath = $input->getOption('tsv-file-path');
    $fileCreateOnly = (bool) $input->getOption('file-create-only');
    $dbUpdateOnly = (bool) $input->getOption('db-update-only');
    $attentionTableUpdateOnly = (bool) $input->getOption('attention-table-update-only');

    // ファイル生成を行う場合
    if (!$dbUpdateOnly && !$attentionTableUpdateOnly) {

      // 書き込みのため、ファイルポインタを取得する。またテンポラリファイルの場合、登録用のファイルパスを更新する
      $fp = null;
      if ($tsvFilePath) {
        $fp = fopen($tsvFilePath, 'wb');
      } else {
        $fp = tmpfile();
        $tsvFilePath = stream_get_meta_data($fp)['uri'];
      }
      $this->getLogger()->debug($this->commandName . " ファイルパス:" . $tsvFilePath);

      $sql = <<<EOD
        SELECT images.*, cal.deliverycode
        FROM product_images images
        JOIN (
            SELECT daihyo_syohin_code, MAX(code) as code FROM product_images
            GROUP BY daihyo_syohin_code
          ) max_image_code ON images.daihyo_syohin_code = max_image_code.daihyo_syohin_code AND images.code = max_image_code.code
        JOIN tb_mainproducts_cal cal ON images.daihyo_syohin_code = cal.daihyo_syohin_code
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();

      // 画像データの収集には一定の時間がかかる。
      // だらだらとDBと接続するのを避けるため、いったんTSVに出力する。
      $this->aggregteImageData($fp, $stmt);
    }

    // DB更新を行う場合
    if (!$fileCreateOnly) {

      // TSVを元にDB更新を行う場合
      if (!$attentionTableUpdateOnly) {
        $this->getLogger()->debug($this->commandName . " Importファイル:" . $tsvFilePath);

        // TSVの内容をテンポラリテーブルへ登録
        $this->loadTmpTable($tsvFilePath, $dbMain);

        // 商品画像テーブルへ反映
        $this->updateProductImagesHash($dbMain);
      }

      // テンポラリテーブルの内容を集計し、本体テーブルへ反映
      $this->getLogger()->debug($this->commandName . "Update product_images_attention_image");
      $this->registAndUpdateAttentionImages($dbMain);
    }
  }

  /**
   * 指定されたファイルポインタに、TSV形式で商品ごとの末尾画像データを出力する
   * @param ファイルポインタ $fp
   * @param 実行済みのステートメント
   */
  private function aggregteImageData($fp, $stmt) {
    // 画像ディレクトリ
    $originalImageDir = $this->getContainer()->getParameter('product_image_original_dir');

    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $imagePath = sprintf('%s/%s', $row['directory'], $row['filename']);
      $imageFullPath = $originalImageDir . '/' . $imagePath;
      $fs = new FileSystem();
      $tsvData = null;
      if ($fs->exists($imageFullPath)) { // 存在しなければ無視
        list($width, $height, $type, $attr) = getimagesize($imageFullPath);
        $tsvData = [
            $row['daihyo_syohin_code']
            , $row['code']
            , $row['deliverycode']
            , $imagePath
            , hash_file('md5', $imageFullPath) // md5
        ];
        $line = implode("\t", $tsvData) . "\r\n";
        fputs($fp, $line);
      }
    }
  }

  /**
   * 指定されたTSVファイルのデータを、tmp_product_image_lastに登録
   * @param unknown $fp
   */
  private function loadTmpTable($tsvFilePath, $dbMain) {
    $dbTmpName = $this->getDb('tmp')->getDatabase();
    $dbMain->query("DROP TABLE IF EXISTS ${dbTmpName}.tmp_product_image_last");
    $sql = <<<EOD
      CREATE TABLE ${dbTmpName}.tmp_product_image_last (
        `daihyo_syohin_code` varchar(30) NOT NULL DEFAULT '' COMMENT '代表商品コード',
        `code` varchar(10) NOT NULL COMMENT 'コード',
        `deliverycode` int(11) NOT NULL DEFAULT 0 COMMENT 'deliverycode',
        `image_path` varchar(128) NOT NULL DEFAULT '' COMMENT '画像パス',
        `md5hash` varchar(128) NOT NULL DEFAULT '' COMMENT 'md5',
        PRIMARY KEY (`daihyo_syohin_code`),
        KEY `index_md5hash` (`md5hash`)
      ) ENGINE=InnoDB COMMENT='代表商品末尾画像管理';
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
          LOAD DATA LOCAL INFILE :filePath
          INTO TABLE ${dbTmpName}.tmp_product_image_last
          FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY '\t'
          LINES TERMINATED BY '\r\n'
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':filePath', $tsvFilePath);
    $stmt->execute();
  }

  /**
   * 商品画像（ProductImages）テーブルのMD5ハッシュカラムを更新する。
   *
   * 収集したデータを元に、md5が異なるものを更新する。
   */
  private function updateProductImagesHash($dbMain) {
    $dbTmpName = $this->getDb('tmp')->getDatabase();
    $sql = <<<EOD
      UPDATE product_images pi
      JOIN ${dbTmpName}.tmp_product_image_last l ON pi.daihyo_syohin_code = l.daihyo_syohin_code AND pi.code = l.code
      SET pi.md5hash = l.md5hash
      WHERE pi.md5hash <> l.md5hash
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
  }

  /**
   * アテンション画像本体テーブルを登録・更新する。
   * @param unknown $dbMain
   */
  private function registAndUpdateAttentionImages($dbMain) {
    // 新規画像を登録
    $sql = <<<EOD
      INSERT INTO product_images_attention_image
      SELECT
        base.md5hash
        , base.daihyo_syohin_code
        , CONCAT(base.directory, '/', base.filename) AS image_path
        , 0 as use_product_num_onsale -- 次で更新するのでいったん0
        , product_images_pickiup.total as use_product_num_all
        , 0 as is_attention_flg
        , now() as created
        , 0 as update_account_id
        , now() as updated
      FROM product_images base -- 3. 2で取得した代表商品画像をもとに、imagePathを登録。件数はこの時点で全件は入れるが、この次のクエリで更新される
      JOIN
      (
        SELECT pickup.daihyo_syohin_code, MAX(code) as code, total -- 2. 1で選んだ代表商品の最終画像のコードを取得
        FROM product_images pickup
        JOIN (
          SELECT images.daihyo_syohin_code, count(*) as total -- 1. md5ハッシュごとに、ランダムに1件の代表商品コードを選択
          FROM product_images images
          JOIN (
              SELECT daihyo_syohin_code, MAX(code) as code FROM product_images
              GROUP BY daihyo_syohin_code
            ) max_image_code ON images.daihyo_syohin_code = max_image_code.daihyo_syohin_code AND images.code = max_image_code.code
          WHERE images.md5hash <> ''
          GROUP BY images.md5hash
        ) allproducts ON pickup.daihyo_syohin_code = allproducts.daihyo_syohin_code
        GROUP BY allproducts.daihyo_syohin_code
      ) product_images_pickiup ON base.daihyo_syohin_code = product_images_pickiup.daihyo_syohin_code AND base.code = product_images_pickiup.code
      LEFT JOIN product_images_attention_image registered ON base.md5hash = registered.md5hash
      WHERE registered.md5hash IS NULL
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    // 登録済みハッシュの更新
    $sql = <<<EOD
      UPDATE
        product_images_attention_image a
        LEFT JOIN (
          SELECT images.md5hash, count(*) as total
          FROM product_images images
          JOIN (
              SELECT daihyo_syohin_code, MAX(code) as code FROM product_images
              GROUP BY daihyo_syohin_code
            ) max_image_code ON images.daihyo_syohin_code = max_image_code.daihyo_syohin_code AND images.code = max_image_code.code
          GROUP BY images.md5hash
        ) allproducts ON a.md5hash = allproducts.md5hash
        LEFT JOIN (
          SELECT images.md5hash, count(*) as total
          FROM product_images images
          JOIN (
              SELECT daihyo_syohin_code, MAX(code) as code FROM product_images
              GROUP BY daihyo_syohin_code
            ) max_image_code ON images.daihyo_syohin_code = max_image_code.daihyo_syohin_code AND images.code = max_image_code.code
          JOIN tb_mainproducts_cal cal ON images.daihyo_syohin_code = cal.daihyo_syohin_code
          WHERE cal.deliverycode IN (0, 1, 2)
          GROUP BY md5hash
        ) onsale ON a.md5hash = onsale.md5hash
      SET a.use_product_num_onsale = IFNULL(onsale.total, 0)
          , a.use_product_num_all = IFNULL(allproducts.total, 0)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
  }
}


