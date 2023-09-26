<?php

namespace MiscBundle\Entity;

/**
 * ProductNgWord
 */
class ProductNgWord
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var \DateTime
     */
    protected $created;

    /**
     * Set id
     *
     * @param int $id
     *
     * @return ProductNgWord
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set content
     *
     * @param string $content
     *
     * @return ProductNgWord
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set created
     *
     * @param \Datetime $created
     *
     * @return ProductNgWord
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \Datetime
     */
    public function getCreated()
    {
        return $this->created;
    }
}
