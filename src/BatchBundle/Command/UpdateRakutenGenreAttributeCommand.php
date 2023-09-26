<?php

namespace BatchBundle\Command;

use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\Repository\TbRakutenGenreAttributeRepository;
use MiscBundle\Entity\Repository\TbPlusnaoproductdirectoryRepository;
use MiscBundle\Service\RakutenService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * バッチ処理 楽天ジャンル別商品属性項目マスタ更新処理
 */
class UpdateRakutenGenreAttributeCommand extends PlusnaoBaseCommand
{
  protected function configure()
  {
    $this
      ->setName('batch:update-rakuten-genre-attribute')
      ->setDescription('楽天ジャンル別商品属性項目マスタ更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('genre-id', null, InputOption::VALUE_OPTIONAL, '楽天ジャンルID。未指定の場合販売中商品分全て')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL, '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN);
  }

  protected function initializeProcess(InputInterface $input)
  {
    $this->commandName = '楽天ジャンル別商品属性項目マスタ更新';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    /** @var TbRakutenGenreAttributeRepository $gaRepo */
    $gaRepo = $this->getDoctrine()->getRepository('MiscBundle:TbRakutenGenreAttribute');
    /** @var TbPlusnaoproductdirectoryRepository $tdRepo */
    $tdRepo = $this->getDoctrine()->getRepository('MiscBundle:TbPlusnaoproductdirectory');
    /** @var RakutenService $service */
    $service = $this->getContainer()->get('misc.service.rakuten');

    $inputGenreId = $input->getOption('genre-id');
    $genreIds = $inputGenreId ? [$inputGenreId] : $tdRepo->findGenreIdsForRakutenPlusnaoAndNewProducts();

    $attributesList = $service->findGenresAttributesListByApi($genreIds);
    $updateCount = $gaRepo->upsertRakutenGenreAttribute($attributesList);

    // 処理実行ログの登録
    $this->processExecuteLog->setProcessNumber1($updateCount);
    $this->processExecuteLog->setVersion(1.0);
  }
}
