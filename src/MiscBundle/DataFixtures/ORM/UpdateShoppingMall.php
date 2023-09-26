<?php
namespace MiscBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use MiscBundle\Entity\Repository\TbShoppingMallRepository;
use MiscBundle\Entity\SymfonyUserYahooAgent;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * モール基本設定 更新
 * ・ NextEngine受注一括登録パターンID更新
 */
class UpdateShoppingMall implements FixtureInterface, ContainerAwareInterface
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
    if ($manager instanceof EntityManager) {
      /** @var TbShoppingMallRepository $repo */
      $repo = $manager->getRepository(TbShoppingMall::class);
    } else {
      /** @var TbShoppingMallRepository $repo */
      $repo = $this->container->get('doctrine')->getRepository(TbShoppingMall::class);
    }

    // NextEngine 受注一括登録パターンID テスト環境設定値
    $testUploadPatternIds = [
        DbCommonUtil::MALL_ID_RAKUTEN         => 37 /* 汎用CSV。オリジナル:5 */
      , DbCommonUtil::MALL_ID_BIDDERS         => 33 /* 汎用CSV */
      , DbCommonUtil::MALL_ID_AMAZON          => 38 /* 汎用CSV。 オリジナル:17 */
      , DbCommonUtil::MALL_ID_YAHOO           => 34 /* 汎用CSV。 オリジナル:19 */
      , DbCommonUtil::MALL_ID_Q10             => 32 /* 汎用CSV */
      , DbCommonUtil::MALL_ID_YAHOOKAWA       => 35 /* 汎用CSV。 オリジナル:20 */
      , DbCommonUtil::MALL_ID_PPM             => 31 /* 汎用CSV */
      , DbCommonUtil::MALL_ID_SHOPLIST        => 0
      , DbCommonUtil::MALL_ID_YAHOO_OTORIYOSE => 36 /* 汎用CSV。 オリジナル:25 */
      , DbCommonUtil::MALL_ID_EC01            => 26 /* 汎用CSV */
      , DbCommonUtil::MALL_ID_EC02            => 27 /* 汎用CSV */
      , DbCommonUtil::MALL_ID_RAKUTEN_MINNA   => 41 /* 汎用CSV オリジナル:52 */
    ];

    /** @var TbShoppingMall[] $malls */
    $malls = $repo->findAll();
    foreach($malls as $mall) {
      if (isset($testUploadPatternIds[$mall->getMallId()])) {
        $mall->setNeOrderUploadPatternId($testUploadPatternIds[$mall->getMallId()]);
      }
    }

    $manager->flush();
  }

  /**
   */
  public function setContainer(ContainerInterface $container = null)
  {
    $this->container = $container;
  }
}
