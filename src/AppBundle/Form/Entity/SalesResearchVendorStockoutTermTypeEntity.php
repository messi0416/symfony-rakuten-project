<?php
namespace AppBundle\Form\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SalesResearchVendorStockoutTermTypeEntity
{
  /**
   * @Assert\Callback
   * @param ExecutionContextInterface $context
   */
  public function validateCategory(ExecutionContextInterface $context)
  {
    if (!$this->dateStart || !$this->dateEnd) {
      $context->buildViolation('取得期間を指定してください')
        ->addViolation()
      ;
    }
  }

  /**
   * 取得期間
   * @var \DateTime
   */
  public $dateStart;
  /** @var \DateTime */
  public $dateEnd;

  /** @var integer */
  public $moveDays;

}
