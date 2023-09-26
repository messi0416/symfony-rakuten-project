<?php

namespace MiscBundle\Entity;

/**
 * TbRakutenCategoryForSalesRanking
 */
class TbRakutenCategoryForSalesRanking
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $big_category;

    /**
     * @var string
     */
    private $mid_category;

    /**
     * @var integer
     */
    private $display_order;


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
     * Set bigCategory
     *
     * @param string $bigCategory
     *
     * @return TbRakutenCategoryForSalesRanking
     */
    public function setBigCategory($bigCategory)
    {
        $this->big_category = $bigCategory;

        return $this;
    }

    /**
     * Get bigCategory
     *
     * @return string
     */
    public function getBigCategory()
    {
        return $this->big_category;
    }

    /**
     * Set midCategory
     *
     * @param string $midCategory
     *
     * @return TbRakutenCategoryForSalesRanking
     */
    public function setMidCategory($midCategory)
    {
        $this->mid_category = $midCategory;

        return $this;
    }

    /**
     * Get midCategory
     *
     * @return string
     */
    public function getMidCategory()
    {
        return $this->mid_category;
    }

    /**
     * Set displayOrder
     *
     * @param integer $displayOrder
     *
     * @return TbRakutenCategoryForSalesRanking
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->display_order = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder
     *
     * @return integer
     */
    public function getDisplayOrder()
    {
        return $this->display_order;
    }
}
