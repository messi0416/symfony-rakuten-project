<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Extend\Doctrine\ORM\ContainerAwareEntityRepository;
use Psr\Log\LoggerInterface;

/**
 * Class BaseRepository
 */
class BaseRepository extends ContainerAwareEntityRepository
{
  /// DB接続取得
  /**
   * @param string $name 接続名
   * @return \Doctrine\DBAL\Connection
   */
  protected function getConnection($name = null)
  {
    // EntityManger::getConnection() はRepository作成時の固定のDB接続しか返さない。なのでContainerから取得
    // ただし、この getContainer() は BccResqueのJobからはうまく動かない。
    // → ContainerAwareEntityRepository 実装で大丈夫になったか？
    // → 大丈夫な模様。
    $db = $this->getContainer()->get('doctrine')->getConnection($name);

    return $db;
  }

  /// @obsolete
  public function setLogger($logger)
  {
    // getContainer が利用できるようになったため不要。
    // おそらく利用されていないので削除予定。
    if ($logger instanceof LoggerInterface) {
      $logger->warning('BaseRepository::setLogger is obsolete.');
    }
    // $this->logger = $logger;
  }

  public function getLogger()
  {
    return $this->getContainer()->get('misc.util.batch_logger');
  }

}


