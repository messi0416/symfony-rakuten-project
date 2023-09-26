<?php
namespace Plusnao\MainBundle\Form\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ChouchouClairStockListDownloadCsvTypeEntity extends ChouchouClairStockListSearchTypeEntity
{
  /**
   * @var \DateTime
   */
  public $dateStart;

  /**
   * @var \DateTime
   */
  public $dateEnd;
}
