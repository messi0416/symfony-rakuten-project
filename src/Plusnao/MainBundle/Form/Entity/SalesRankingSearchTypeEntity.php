<?php
namespace Plusnao\MainBundle\Form\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SalesRankingSearchTypeEntity
{
  /**
   * @Assert\Callback
   * @param ExecutionContextInterface $context
   */
  public function validateCategory(ExecutionContextInterface $context)
  {
    if (!$this->dateBStart || !$this->dateBEnd) {
      $context->buildViolation('取得期間を指定してください')
        ->addViolation()
      ;
    }
  }

  /**
   * 取得期間 （B が基準）
   * @var \DateTime
   */
  public $dateBStart;
  /** @var \DateTime */
  public $dateBEnd;

  /** @var \DateTime */
  public $dateAStart;
  /** @var \DateTime */
  public $dateAEnd;

  public $buyerID;
  public $bigCategory;
  public $midCategory;
  public $keyword;

  public $rankingTarget;
  public $moveDays;


}
