<?php
namespace Plusnao\MainBundle\Form\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ChouchouClairStockListUploadCsvTypeEntity extends ChouchouClairStockListSearchTypeEntity
{
  public $uploaded;

  public function getUploaded()
  {
    return $this->uploaded;
  }
}
