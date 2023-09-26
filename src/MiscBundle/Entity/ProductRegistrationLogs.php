<?php

namespace MiscBundle\Entity;

/**
 * ProductRegistrationLogs
 */
class ProductRegistrationLogs
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var string
     */
    private $contents;

    /**
     * @var string
     */
    private $mainId;

    /**
     * @var integer
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $modified;


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
     * Set userId
     *
     * @param integer $userId
     *
     * @return ProductRegistrationLogs
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set contents
     *
     * @param string $contents
     *
     * @return ProductRegistrationLogs
     */
    public function setContents($contents)
    {
        $this->contents = $contents;

        return $this;
    }

    /**
     * Get contents
     *
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Set mainId
     *
     * @param string $mainId
     *
     * @return ProductRegistrationLogs
     */
    public function setMainId($mainId)
    {
        $this->mainId = $mainId;

        return $this;
    }

    /**
     * Get mainId
     *
     * @return string
     */
    public function getMainId()
    {
        return $this->mainId;
    }

    /**
     * Set dateAdded
     *
     * @param integer $dateAdded
     *
     * @return ProductRegistrationLogs
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return integer
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return ProductRegistrationLogs
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

    /**
     * Set modified
     *
     * @param \DateTime $modified
     *
     * @return ProductRegistrationLogs
     */
    public function setModified($modified)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * Get modified
     *
     * @return \DateTime
     */
    public function getModified()
    {
        return $this->modified;
    }
}

