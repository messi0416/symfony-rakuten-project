<?php
namespace Plusnao\MainBundle\Form\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ChouchouClairStockListSearchTypeEntity
{
  // 取得対象
  const LIST_TARGET_ALL = 'all';
  const LIST_TARGET_MODIFIED = 'modified';

  /**
   * @Assert\Callback
   * @param ExecutionContextInterface $context
   */
  public function validateCategory(ExecutionContextInterface $context)
  {
    /*
    if (!$this->dateBStart || !$this->dateBEnd) {
      $context->buildViolation('取得期間を指定してください')
        ->addViolation()
      ;
    }
    */
  }

  public $searchTarget;
  public $code;
  public $keyword;

  /**
   * 検索条件 取得
   */
  public function getSearchConditions()
  {
    return [
        'code' => $this->code
      , 'keyword' => $this->keyword
      , 'searchTarget' => $this->searchTarget
    ];

  }
}
