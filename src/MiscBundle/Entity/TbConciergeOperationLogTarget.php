<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbConciergeOperationLogTarget
 */
class TbConciergeOperationLogTarget
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $conciergeOperationLogId;

    /**
     * @var boolean
     */
    private $targetType;

    /**
     * @var string
     */
    private $targetValue;


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
     * Set conciergeOperationLogId
     *
     * @param boolean $conciergeOperationLogId
     * @return TbConciergeOperationLogTarget
     */
    public function setConciergeOperationLogId($conciergeOperationLogId)
    {
        $this->conciergeOperationLogId = $conciergeOperationLogId;

        return $this;
    }

    /**
     * Get conciergeOperationLogId
     *
     * @return boolean
     */
    public function getConciergeOperationLogId()
    {
        return $this->conciergeOperationLogId;
    }

    /**
     * Set targetType
     *
     * @param boolean $targetType
     * @return TbConciergeOperationLogTarget
     */
    public function setTargetType($targetType)
    {
        $this->targetType = $targetType;

        return $this;
    }

    /**
     * Get targetType
     *
     * @return boolean
     */
    public function getTargetType()
    {
        return $this->targetType;
    }

    /**
     * Set targetValue
     *
     * @param string $targetValue
     * @return TbConciergeOperationLogTarget
     */
    public function setTargetValue($targetValue)
    {
        $this->targetValue = $targetValue;

        return $this;
    }

    /**
     * Get targetValue
     *
     * @return string
     */
    public function getTargetValue()
    {
        return $this->targetValue;
    }
}
