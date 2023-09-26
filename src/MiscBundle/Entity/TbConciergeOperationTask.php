<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbConciergeOperationTask
 */
class TbConciergeOperationTask
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $displayOrder;

    /**
     * @var boolean
     */
    private $deleteFlg;


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
     * Set name
     *
     * @param string $name
     * @return TbConciergeOperationTask
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set displayOrder
     *
     * @param integer $displayOrder
     * @return TbConciergeOperationTask
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder
     *
     * @return integer
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }

    /**
     * Set deleteFlg
     *
     * @param boolean $deleteFlg
     * @return TbConciergeOperationTask
     */
    public function setDeleteFlg($deleteFlg)
    {
        $this->deleteFlg = $deleteFlg;

        return $this;
    }

    /**
     * Get deleteFlg
     *
     * @return boolean
     */
    public function getDeleteFlg()
    {
        return $this->deleteFlg;
    }
}
