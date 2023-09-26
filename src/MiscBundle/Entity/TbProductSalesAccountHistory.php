<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbProductSalesAccountHistory
 */
class TbProductSalesAccountHistory
{
    use ArrayTrait;
    use FillTimestampTrait;

    /** 処理: 追加 */
    const PROCESS_TYPE_ADD = 1;
    /** 処理: 削除 */
    const PROCESS_TYPE_DELETE = 2;
    /** 処理: 変更 */
    const PROCESS_TYPE_CHANGE = 3;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $processType;

    /**
     * @var string
     */
    private $note;

    /**
     * @var \DateTime
     */
    private $updated;

    /**
     * @var integer
     */
    private $updateAccountId;

    /**
     * @var \MiscBundle\Entity\SymfonyUsers
     */
    private $updateAccount;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productSalesAccounts;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->productSalesAccounts = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set processType
     *
     * @param integer $processType
     * @return TbProductSalesAccountHistory
     */
    public function setProcessType($processType)
    {
        $this->processType = $processType;

        return $this;
    }

    /**
     * Get processType
     *
     * @return integer
     */
    public function getProcessType()
    {
        return $this->processType;
    }

    /**
     * Set note
     *
     * @param integer $note
     * @return TbProductSalesAccountHistory
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return integer
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return TbProductSalesAccountHistory
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
     * Set updateAccountId
     *
     * @param integer $updateAccountId
     * @return TbProductSalesAccountHistory
     */
    public function setUpdateAccountId($updateAccountId)
    {
        $this->updateAccountId = $updateAccountId;

        return $this;
    }

    /**
     * Get updateAccountId
     *
     * @return integer 
     */
    public function getUpdateAccountId()
    {
        return $this->updateAccountId;
    }

    /**
     * Set updateAccount
     *
     * @param \MiscBundle\Entity\SymfonyUsers $updateAccount
     * @return TbProductSalesAccountHistory
     */
    public function setUpdateAccount(\MiscBundle\Entity\SymfonyUsers $updateAccount = null)
    {
        $this->updateAccount = $updateAccount;

        return $this;
    }

    /**
     * Get updateAccount
     *
     * @return \MiscBundle\Entity\SymfonyUsers 
     */
    public function getUpdateAccount()
    {
        return $this->updateAccount;
    }

    /**
     * Add productSalesAccounts
     *
     * @param \MiscBundle\Entity\TbProductSalesAccount $productSalesAccounts
     * @return TbProductSalesAccountHistory
     */
    public function addProductSalesAccount(\MiscBundle\Entity\TbProductSalesAccount $productSalesAccounts)
    {
        $this->productSalesAccounts[] = $productSalesAccounts;

        return $this;
    }

    /**
     * Remove productSalesAccounts
     *
     * @param \MiscBundle\Entity\TbProductSalesAccount $productSalesAccounts
     */
    public function removeProductSalesAccount(\MiscBundle\Entity\TbProductSalesAccount $productSalesAccounts)
    {
        $this->productSalesAccounts->removeElement($productSalesAccounts);
    }

    /**
     * Get productSalesAccounts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getProductSalesAccounts()
    {
        return $this->productSalesAccounts;
    }
}
