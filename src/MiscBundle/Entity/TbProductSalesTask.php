<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbProductSalesTask
 */
class TbProductSalesTask
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $taskName;

    /**
     * @var boolean
     */
    private $multiProductRegisterFlg;

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
     * Set taskName
     *
     * @param string $taskName
     * @return TbProductSalesTask
     */
    public function setTaskName($taskName)
    {
        $this->taskName = $taskName;

        return $this;
    }

    /**
     * Get taskName
     *
     * @return string 
     */
    public function getTaskName()
    {
        return $this->taskName;
    }

    /**
     * Set multiProductRegisterFlg
     *
     * @param boolean $multiProductRegisterFlg
     * @return TbProductSalesTask
     */
    public function setMultiProductRegisterFlg($multiProductRegisterFlg)
    {
        $this->multiProductRegisterFlg = $multiProductRegisterFlg;

        return $this;
    }

    /**
     * Get multiProductRegisterFlg
     *
     * @return boolean 
     */
    public function getMultiProductRegisterFlg()
    {
        return $this->multiProductRegisterFlg;
    }

    /**
     * Set deleteFlg
     *
     * @param boolean $deleteFlg
     * @return TbProductSalesTask
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
