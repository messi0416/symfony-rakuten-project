<?php
namespace MiscBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use MiscBundle\Entity\SymfonyUserYahooAgent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Yahoo 代理店テストデータ読み込み
 */
class LoadYahooAgentUserData implements FixtureInterface, ContainerAwareInterface
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
    /** @var \Doctrine\DBAL\Connection $db */
    $db = $this->container->get('doctrine')->getConnection('main');
    $db->query("TRUNCATE symfony_user_yahoo_agent");

    // テストアカウント
    $user = new SymfonyUserYahooAgent();
    $user->setUsername('yahoo_forest');
    $user->setPassword('');
    $user->setClientName('ヤフーフォレストテスト');
    $user->setEmail('');
    $user->setIsActive('-1');
    $user->setRoles('ROLE_YAHOO_AGENT');
    $user->setShopCode('hirai');
    $user->setAppId('dj0zaiZpPXJtMGtqT3BEY0hrdyZzPWNvbnN1bWVyc2VjcmV0Jng9MmY-');
    $user->setAppSecret('5017b7a714560e3e5648949baac3a3104018e934');
    $user->setFtpUser('');
    $user->setFtpPassword('');
    $manager->persist($user);

    // おとりよせ.com テストアカウント
    $user = new SymfonyUserYahooAgent();
    $user->setUsername('otoriyose');
    $user->setPassword('');
    $user->setClientName('おとりよせ.comテスト');
    $user->setEmail('');
    $user->setIsActive('-1');
    $user->setRoles('ROLE_YAHOO_AGENT');
    $user->setShopCode('otoriyose');
    $user->setAppId('dj0zaiZpPXJtMGtqT3BEY0hrdyZzPWNvbnN1bWVyc2VjcmV0Jng9MmY-');
    $user->setAppSecret('5017b7a714560e3e5648949baac3a3104018e934');
    $user->setFtpUser('');
    $user->setFtpPassword('');
    $manager->persist($user);

    $manager->flush();
  }

  /**
   */
  public function setContainer(ContainerInterface $container = null)
  {
    $this->container = $container;
  }
}
