<?php

namespace MiscBundle\Entity;

/**
 * TbUpdaterecord
 */
class TbUpdaterecord
{
    /**
     * @var integer
     */
    private $updaterecordno;

    /**
     * @var \DateTime
     */
    private $datetime;


    /**
     * Get updaterecordno
     *
     * @return integer
     */
    public function getUpdaterecordno()
    {
        return $this->updaterecordno;
    }

    /**
     * Set datetime
     *
     * @param \DateTime $datetime
     *
     * @return TbUpdaterecord
     */
    public function setDatetime($datetime)
    {
        $this->datetime = $datetime;

        return $this;
    }

    /**
     * Get datetime
     *
     * @return \DateTime
     */
    public function getDatetime()
    {
        return $this->datetime;
    }
}

