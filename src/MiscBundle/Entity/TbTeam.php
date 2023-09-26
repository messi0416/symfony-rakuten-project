<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbTeam
 */
class TbTeam
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $teamName;

    /**
     * @var string
     */
    private $comment;

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
     * Set teamName
     *
     * @param string $teamName
     * @return TbTeam
     */
    public function setTeamName($teamName)
    {
        $this->teamName = $teamName;

        return $this;
    }

    /**
     * Get teamName
     *
     * @return string 
     */
    public function getTeamName()
    {
        return $this->teamName;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return TbTeam
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set deleteFlg
     *
     * @param boolean $deleteFlg
     * @return TbTeam
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
