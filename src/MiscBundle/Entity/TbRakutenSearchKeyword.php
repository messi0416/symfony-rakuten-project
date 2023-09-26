<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbRakutenSearchKeyword
 */
class TbRakutenSearchKeyword
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $keyword;


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
     * Set keyword
     *
     * @param string $keyword
     * @return TbRakutenSearchKeyword
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;

        return $this;
    }

    /**
     * Get keyword
     *
     * @return string 
     */
    public function getKeyword()
    {
        return $this->keyword;
    }
}
