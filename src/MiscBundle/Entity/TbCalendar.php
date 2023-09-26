<?php

namespace MiscBundle\Entity;

/**
 * TbCalendar
 */
class TbCalendar
{
    /**
     * @var \DateTime
     */
    private $calendarDate;

    /**
     * @var integer
     */
    private $workingday;


    /**
     * Get calendarDate
     *
     * @return \DateTime
     */
    public function getCalendarDate()
    {
        return $this->calendarDate;
    }

    /**
     * Set workingday
     *
     * @param integer $workingday
     *
     * @return TbCalendar
     */
    public function setWorkingday($workingday)
    {
        $this->workingday = $workingday;

        return $this;
    }

    /**
     * Get workingday
     *
     * @return integer
     */
    public function getWorkingday()
    {
        return $this->workingday;
    }
}

