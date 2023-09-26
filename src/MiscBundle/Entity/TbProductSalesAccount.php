<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbProductSalesAccount
 */
class TbProductSalesAccount
{
    use FillTimestampTrait;

    /** ステータス: 登録 */
    const STATUS_REGISTRATION = 1;
    /** ステータス: 削除 */
    const STATUS_DELETE = 2;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $daihyoSyohinCode;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var integer
     */
    private $teamId;

    /**
     * @var integer
     */
    private $productSalesTaskId;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var string
     */
    private $workAmount;

    /**
     * @var string
     */
    private $detail;

    /**
     * @var \Date
     */
    private $applyStartDate;

    /**
     * @var \Date
     */
    private $applyEndDate;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $updated;

    /**
     * @var \MiscBundle\Entity\TbTeam
     */
    private $team;

    /**
     * @var \MiscBundle\Entity\TbProductSalesTask
     */
    private $productSalesTask;

    /**
     * @var \MiscBundle\Entity\SymfonyUsers
     */
    private $user;

    /**
     * @var \MiscBundle\Entity\TbMainproducts
     */
    private $product;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productSalesAccountHistory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->productSalesAccountHistory = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set daihyoSyohinCode
     *
     * @param string $daihyoSyohinCode
     * @return TbProductSalesAccount
     */
    public function setDaihyoSyohinCode($daihyoSyohinCode)
    {
        $this->daihyoSyohinCode = $daihyoSyohinCode;

        return $this;
    }

    /**
     * Get daihyoSyohinCode
     *
     * @return string 
     */
    public function getDaihyoSyohinCode()
    {
        return $this->daihyoSyohinCode;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return TbProductSalesAccount
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set teamId
     *
     * @param integer $teamId
     * @return TbProductSalesAccount
     */
    public function setTeamId($teamId)
    {
        $this->teamId = $teamId;

        return $this;
    }

    /**
     * Get teamId
     *
     * @return integer 
     */
    public function getTeamId()
    {
        return $this->teamId;
    }

    /**
     * Set productSalesTaskId
     *
     * @param integer $productSalesTaskId
     * @return TbProductSalesAccount
     */
    public function setProductSalesTaskId($productSalesTaskId)
    {
        $this->productSalesTaskId = $productSalesTaskId;

        return $this;
    }

    /**
     * Get productSalesTaskId
     *
     * @return integer 
     */
    public function getProductSalesTaskId()
    {
        return $this->productSalesTaskId;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return TbProductSalesAccount
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set workAmount
     *
     * @param string $workAmount
     * @return TbProductSalesAccount
     */
    public function setWorkAmount($workAmount)
    {
        $this->workAmount = $workAmount;

        return $this;
    }

    /**
     * Get workAmount
     *
     * @return string 
     */
    public function getWorkAmount()
    {
        return $this->workAmount;
    }

    /**
     * Set detail
     *
     * @param string $detail
     * @return TbProductSalesAccount
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * Get detail
     *
     * @return string 
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * Set applyStartDate
     *
     * @param \Date $applyStartDate
     * @return TbProductSalesAccount
     */
    public function setApplyStartDate($applyStartDate)
    {
        $this->applyStartDate = $applyStartDate;

        return $this;
    }

    /**
     * Get applyStartDate
     *
     * @return \Date
     */
    public function getApplyStartDate()
    {
        return $this->applyStartDate;
    }

    /**
     * Set applyEndDate
     *
     * @param \Date $applyEndDate
     * @return TbProductSalesAccount
     */
    public function setApplyEndDate($applyEndDate)
    {
        $this->applyEndDate = $applyEndDate;

        return $this;
    }

    /**
     * Get applyEndDate
     *
     * @return \Date
     */
    public function getApplyEndDate()
    {
        return $this->applyEndDate;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return TbProductSalesAccount
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return TbProductSalesAccount
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime 
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set team
     *
     * @param \MiscBundle\Entity\TbTeam $team
     * @return TbProductSalesAccount
     */
    public function setTeam(\MiscBundle\Entity\TbTeam $team = null)
    {
        $this->team = $team;

        return $this;
    }

    /**
     * Get team
     *
     * @return \MiscBundle\Entity\TbTeam 
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * Set productSalesTask
     *
     * @param \MiscBundle\Entity\TbProductSalesTask $productSalesTask
     * @return TbProductSalesAccount
     */
    public function setProductSalesTask(\MiscBundle\Entity\TbProductSalesTask $productSalesTask = null)
    {
        $this->productSalesTask = $productSalesTask;

        return $this;
    }

    /**
     * Get productSalesTask
     *
     * @return \MiscBundle\Entity\TbProductSalesTask 
     */
    public function getProductSalesTask()
    {
        return $this->productSalesTask;
    }

    /**
     * Set user
     *
     * @param \MiscBundle\Entity\SymfonyUsers $user
     * @return TbProductSalesAccount
     */
    public function setUser(\MiscBundle\Entity\SymfonyUsers $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \MiscBundle\Entity\SymfonyUsers 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set product
     *
     * @param \MiscBundle\Entity\TbMainproducts $product
     * @return TbProductSalesAccount
     */
    public function setProduct(\MiscBundle\Entity\TbMainproducts $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \MiscBundle\Entity\TbMainproducts 
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Add productSalesAccountHistory
     *
     * @param \MiscBundle\Entity\TbProductSalesAccountHistory $productSalesAccountHistory
     * @return TbProductSalesAccount
     */
    public function addProductSalesAccountHistory(\MiscBundle\Entity\TbProductSalesAccountHistory $productSalesAccountHistory)
    {
        $this->productSalesAccountHistory[] = $productSalesAccountHistory;

        return $this;
    }

    /**
     * Remove productSalesAccountHistory
     *
     * @param \MiscBundle\Entity\TbProductSalesAccountHistory $productSalesAccountHistory
     */
    public function removeProductSalesAccountHistory(\MiscBundle\Entity\TbProductSalesAccountHistory $productSalesAccountHistory)
    {
        $this->productSalesAccountHistory->removeElement($productSalesAccountHistory);
    }

    /**
     * Get productSalesAccountHistory
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getProductSalesAccountHistory()
    {
        return $this->productSalesAccountHistory;
    }
}
