<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbIndividualorderComment
 */
class TbIndividualorderComment
{
    /**
     * @var integer
     */
    private $order_voucher_number;

    /**
     * @var integer
     */
    private $agent_id;

    /**
     * @var string
     */
    private $note = '';

    /**
     * @var integer
     */
    private $update_account_id = 0;

    /**
     * @var \DateTime
     */
    private $updated;


    /**
     * Set order_voucher_number
     *
     * @param integer $orderVoucherNumber
     * @return TbIndividualorderComment
     */
    public function setOrderVoucherNumber($orderVoucherNumber)
    {
        $this->order_voucher_number = $orderVoucherNumber;

        return $this;
    }

    /**
     * Get order_voucher_number
     *
     * @return integer 
     */
    public function getOrderVoucherNumber()
    {
        return $this->order_voucher_number;
    }

    /**
     * Set agent_id
     *
     * @param integer $agentId
     * @return TbIndividualorderComment
     */
    public function setAgentId($agentId)
    {
        $this->agent_id = $agentId;

        return $this;
    }

    /**
     * Get agent_id
     *
     * @return integer 
     */
    public function getAgentId()
    {
        return $this->agent_id;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return TbIndividualorderComment
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return string 
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set update_account_id
     *
     * @param integer $updateAccountId
     * @return TbIndividualorderComment
     */
    public function setUpdateAccountId($updateAccountId)
    {
        $this->update_account_id = $updateAccountId;

        return $this;
    }

    /**
     * Get update_account_id
     *
     * @return integer 
     */
    public function getUpdateAccountId()
    {
        return $this->update_account_id;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return TbIndividualorderComment
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
}
