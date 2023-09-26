<?php
namespace Plusnao\MainBundle\Form\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class VendorOrderListDownloadCsvTypeEntity
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
