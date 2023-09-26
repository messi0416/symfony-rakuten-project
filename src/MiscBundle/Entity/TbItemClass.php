<?php

namespace MiscBundle\Entity;

/**
 * TbItemClass
 */
class TbItemClass
{
    /**
     * @var integer
     */
    private $classCd;

    /**
     * @var string
     */
    private $className;


    /**
     * Get classCd
     *
     * @return integer
     */
    public function getClassCd()
    {
        return $this->classCd;
    }

    /**
     * Set className
     *
     * @param string $className
     *
     * @return TbItemClass
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * Get className
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
}

