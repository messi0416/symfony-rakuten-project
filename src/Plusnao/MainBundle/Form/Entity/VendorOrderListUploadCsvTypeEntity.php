<?php
namespace Plusnao\MainBundle\Form\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class VendorOrderListUploadCsvTypeEntity
{
  public $uploaded;

  public function getUploaded()
  {
    return $this->uploaded;
  }
}
