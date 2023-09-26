<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbConciergeOperationLog
 */
class TbConciergeOperationLog
{
    use FillTimestampTrait;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $tbConciergeOperationTaskId;

    /**
     * @var string
     */
    private $note;

    /**
     * @var integer
     */
    private $createAccountId;

    /**
     * @var \DateTime
     */
    private $created;


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
     * Set tbConciergeOperationTaskId
     *
     * @param integer $tbConciergeOperationTaskId
     * @return TbConciergeOperationLog
     */
    public function setTbConciergeOperationTaskId($tbConciergeOperationTaskId)
    {
        $this->tbConciergeOperationTaskId = $tbConciergeOperationTaskId;

        return $this;
    }

    /**
     * Get tbConciergeOperationTaskId
     *
     * @return integer
     */
    public function getTbConciergeOperationTaskId()
    {
        return $this->tbConciergeOperationTaskId;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return TbConciergeOperationLog
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
     * Set createAccountId
     *
     * @param integer $createAccountId
     * @return TbConciergeOperationLog
     */
    public function setCreateAccountId($createAccountId)
    {
        $this->createAccountId = $createAccountId;

        return $this;
    }

    /**
     * Get createAccountId
     *
     * @return integer
     */
    public function getCreateAccountId()
    {
        return $this->createAccountId;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return TbConciergeOperationLog
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
}
