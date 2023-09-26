<?php
namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

class Misc202301CreateXmlTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
    ->setName('misc:misc-202301-create-xml-test')
    ->setDescription('XML生成テスト')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ;
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    /** @var $logger BatchLogger */
    $logger = $this->getLogger();   
    $commonUtil = $this->getDbCommonUtil();
    
    $sample = ["receiveorder" => []];
    for ($i = 1; $i < 4; $i++) {
      $sample["receiveorder"][$i] = [];
      $sample["receiveorder"][$i]["@receive_order_id"] = $i;
      $sample["receiveorder"][$i]["@receive_order_last_modified_date"] = "2023-01-19 12:34:56";
      $sample["receiveorder"][$i]["receive_order_label_print_flag"] = "1";
    }
    
    $encoders = array(new XmlEncoder(), new XmlEncoder());
    $normalizers = array(new GetSetMethodNormalizer());
    $serializer = new Serializer($normalizers, $encoders);
    
    $context = [
      'xml_root_node_name' => 'root'
      , 'xml_format_output' => true
      , 'xml_encoding' => 'UTF-8'
    ];
    
    $sampleXml = $serializer->serialize($sample, 'xml', $context);
    var_dump($sampleXml);
  }
}