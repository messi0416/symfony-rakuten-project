<?php
namespace MiscBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * 各種設定 更新
 * ・ NextEngine APIアクセストークン、リフレッシュトークン削除（※これをしないとしれっと本番につながってるため注意）
 */
class UpdateSetting implements FixtureInterface, ContainerAwareInterface
{
  /**
   * @var ContainerInterface
   */
  private $container;

  /**
   * @param ObjectManager $manager
   */
  public function load(ObjectManager $manager)
  {
    /** @var Connection $dbMain */
    $dbMain = $this->container->get('doctrine')->getConnection('main');

    $sql = <<<EOD
      UPDATE tb_setting
      SET setting_val = ''
      WHERE setting_key IN (
          'NE_API_ACCESS_TOKEN'
        , 'NE_API_REFRESH_TOKEN'
      )
EOD;
    $dbMain->exec($sql);
  }


  /**
   */
  public function setContainer(ContainerInterface $container = null)
  {
    $this->container = $container;
  }
}
