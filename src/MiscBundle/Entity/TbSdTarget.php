<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbSdTarget
 */
class TbSdTarget
{
    /**
     * @var string
     */
    private $genreName;

    /**
     * @var string
     */
    private $target;

    /**
     * @var integer
     */
    private $code;


    /**
     * Set genreName
     *
     * @param string $genreName
     * @return TbSdTarget
     */
    public function setGenreName($genreName)
    {
        $this->genreName = $genreName;

        return $this;
    }

    /**
     * Get genreName
     *
     * @return string 
     */
    public function getGenreName()
    {
        return $this->genreName;
    }

    /**
     * Set target
     *
     * @param string $target
     * @return TbSdTarget
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target
     *
     * @return string 
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set code
     *
     * @param integer $code
     * @return TbSdTarget
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return integer 
     */
    public function getCode()
    {
        return $this->code;
    }
}
