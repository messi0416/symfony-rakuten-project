<?php

namespace Plusnao\MainBundle\Controller;

use MiscBundle\Entity\PurchasingAgent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BaseAgentController extends BaseController
{
  /** @var PurchasingAgent */
  protected $agent;

  /**
   * 操作対象Agentアカウント
   * @return PurchasingAgent|null
   */
  public function getAgent()
  {
    return $this->agent;
  }

  /**
   * 操作対象Agentアカウント
   * BeforeFilterControllerEventListener により自動セット
   * @param PurchasingAgent
   */
  public function setAgent($agent)
  {
    $this->agent = $agent;
  }
}
