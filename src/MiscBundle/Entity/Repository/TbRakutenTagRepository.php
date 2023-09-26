<?php

namespace MiscBundle\Entity\Repository;
use MiscBundle\Entity\TbMainproducts;
use MiscBundle\Entity\TbPlusnaoproductdirectory;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Entity\TbRakutenTag;

/**
 * TbRakutenTagRepository
 *
 */
class TbRakutenTagRepository extends BaseRepository
{
  /**
   * 商品コードで、指定可能タグ一覧取得
   * @param TbMainproducts $product
   * @return TbRakutenTag[]
   */
  public function findByProduct($product)
  {
    $result = [];

    if (!$product) {
      return $result;
    }

    // NEディレクトリ情報取得
    /** @var TbPlusnaoproductdirectory $neDirectory */
    $neDirectory = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbPlusnaoproductdirectory')->find($product->getNeDirectoryId());
    if (!$neDirectory || ! $neDirectory->getRakutenDirectoryId()) {
      return $result;
    }

    // タグ一覧取得
    $result = $this->findBy(
        ['directory_id' => $neDirectory->getRakutenDirectoryId()]
      , ['classification' => 'ASC', 'tag_id' => 'ASC']
    );

    return $result;
  }

  /**
   * 商品に設定されているタグID取得
   * @param TbMainproducts $product
   * @return TbRakutenTag[]
   */
  public function findProductRelatedTags($product)
  {
    if (!$product || !$product->getNeDirectoryId()) {
      return [];
    }

    // NEディレクトリ情報取得
    /** @var TbPlusnaoproductdirectory $neDirectory */
    $neDirectory = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbPlusnaoproductdirectory')->find($product->getNeDirectoryId());
    if (!$neDirectory || ! $neDirectory->getRakutenDirectoryId()) {
      return [];
    }

    $dbMain = $this->getConnection('main');

    // このタイミングで、別ディレクトリに紐付いたタグ（ゴミ？）は掃除しておく。
    $sql = <<<EOD
      DELETE FROM tb_rakuten_tag_mainproducts
      WHERE daihyo_syohin_code = :daihyoSyohinCode
        AND ディレクトリID <> :rakutenDirectoryId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $product->getDaihyoSyohinCode());
    $stmt->bindValue(':rakutenDirectoryId', $neDirectory->getRakutenDirectoryId());
    $stmt->execute();

    $qb = $this->createQueryBuilder('t');
    $qb->innerJoin('MiscBundle\Entity\TbRakutenTagMainproducts', 'tm', \Doctrine\ORM\Query\Expr\Join::WITH, 't.directory_id = tm.directory_id AND t.tag_id = tm.tag_id');
    $qb->andWhere('tm.daihyo_syohin_code = :daihyoSyohinCode')->setParameter('daihyoSyohinCode', $product->getDaihyoSyohinCode());

    $tags = $qb->getQuery()->getResult();

    return $tags;
  }

  /**
   * 商品SKUに設定されているタグ一覧取得
   * @param TbMainproducts $product
   * @return array
   */
  public function findProductSkuRelatedTags($product)
  {
    // このタイミングで、別ディレクトリに紐付いたタグ（ゴミ？）は掃除しておく。
    if (!$product || !$product->getNeDirectoryId()) {
      return [];
    }

    // NEディレクトリ情報取得
    /** @var TbPlusnaoproductdirectory $neDirectory */
    $neDirectory = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbPlusnaoproductdirectory')->find($product->getNeDirectoryId());
    if (!$neDirectory || ! $neDirectory->getRakutenDirectoryId()) {
      return [];
    }

    $dbMain = $this->getConnection('main');

    // このタイミングで、別ディレクトリに紐付いたタグ（ゴミ？）は掃除しておく。
    $sql = <<<EOD
      DELETE FROM tb_rakuten_tag_productchoiceitems
      WHERE daihyo_syohin_code = :daihyoSyohinCode
        AND ディレクトリID <> :rakutenDirectoryId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $product->getDaihyoSyohinCode());
    $stmt->bindValue(':rakutenDirectoryId', $neDirectory->getRakutenDirectoryId());
    $stmt->execute();

    /** @var BaseRepository $repo */
    $repo = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbProductchoiceitems');
    $choices = $repo->findBy(
        ['daihyoSyohinCode' => $product->getDaihyoSyohinCode()]
      , ['displayOrder' => 'ASC']
    );

    $result = [];

    // 綺麗にEntityで取るにはなかなか大変そう。ひとまずベターっとループ（最大choice件数は現時点で143件）
    /** @var TbProductchoiceitems $choice */
    foreach($choices as $choice) {
      $qb = $this->createQueryBuilder('t');
      $qb->innerJoin('MiscBundle\Entity\TbRakutenTagProductchoiceitems', 'tp', \Doctrine\ORM\Query\Expr\Join::WITH, 't.directory_id = tp.directory_id AND t.tag_id = tp.tag_id');
      $qb->andWhere('tp.ne_syohin_syohin_code = :neSyohinSyohinCode')->setParameter('neSyohinSyohinCode', $choice->getNeSyohinSyohinCode());

      $tags = $qb->getQuery()->getResult();
      $result[$choice->getNeSyohinSyohinCode()] = $tags;
    }

    return $result;
  }

}
