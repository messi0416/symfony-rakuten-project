<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbYahooDataAdd
 */
class TbYahooDataAdd
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $sub_code;

    /**
     * @var integer
     */
    private $original_price;

    /**
     * @var integer
     */
    private $price;

    /**
     * @var integer
     */
    private $price_add_10per;

    /**
     * @var integer
     */
    private $sale_price;

    /**
     * @var string
     */
    private $options;

    /**
     * @var string
     */
    private $headline;

    /**
     * @var string
     */
    private $caption;

    /**
     * @var string
     */
    private $abstract;

    /**
     * @var string
     */
    private $explanation;

    /**
     * @var string
     */
    private $additional1;

    /**
     * @var string
     */
    private $additional2;

    /**
     * @var string
     */
    private $additional3;

    /**
     * @var string
     */
    private $relevant_links;

    /**
     * @var string
     */
    private $ship_weight;

    /**
     * @var string
     */
    private $taxable;

    /**
     * @var string
     */
    private $release_date;

    /**
     * @var string
     */
    private $temporary_point_term;

    /**
     * @var string
     */
    private $point_code;

    /**
     * @var string
     */
    private $meta_key;

    /**
     * @var string
     */
    private $meta_desc;

    /**
     * @var string
     */
    private $template;

    /**
     * @var string
     */
    private $sale_period_start;

    /**
     * @var string
     */
    private $sale_period_end;

    /**
     * @var string
     */
    private $sale_limit;

    /**
     * @var string
     */
    private $sp_code;

    /**
     * @var string
     */
    private $brand_code;

    /**
     * @var string
     */
    private $person_code;

    /**
     * @var string
     */
    private $yahoo_product_code;

    /**
     * @var string
     */
    private $product_code;

    /**
     * @var string
     */
    private $jan;

    /**
     * @var string
     */
    private $isbn;

    /**
     * @var string
     */
    private $delivery;

    /**
     * @var string
     */
    private $astk_code;

    /**
     * @var string
     */
    private $condition;

    /**
     * @var string
     */
    private $taojapan;

    /**
     * @var string
     */
    private $product_category;

    /**
     * @var string
     */
    private $spec1;

    /**
     * @var string
     */
    private $spec2;

    /**
     * @var string
     */
    private $spec3;

    /**
     * @var string
     */
    private $spec4;

    /**
     * @var string
     */
    private $spec5;

    /**
     * @var string
     */
    private $display;

    /**
     * @var string
     */
    private $sort;

    /**
     * @var string
     */
    private $sp_additional;

    /**
     * @var integer
     */
    private $lead_time_instock;

    /**
     * @var integer
     */
    private $lead_time_outstock;

    /**
     * @var string
     */
    private $pr_rate;

    /**
     * @var integer
     */
    private $postage_set;


    /**
     * Set code
     *
     * @param string $code
     * @return TbYahooDataAdd
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return TbYahooDataAdd
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return TbYahooDataAdd
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set sub_code
     *
     * @param string $subCode
     * @return TbYahooDataAdd
     */
    public function setSubCode($subCode)
    {
        $this->sub_code = $subCode;

        return $this;
    }

    /**
     * Get sub_code
     *
     * @return string 
     */
    public function getSubCode()
    {
        return $this->sub_code;
    }

    /**
     * Set original_price
     *
     * @param integer $originalPrice
     * @return TbYahooDataAdd
     */
    public function setOriginalPrice($originalPrice)
    {
        $this->original_price = $originalPrice;

        return $this;
    }

    /**
     * Get original_price
     *
     * @return integer 
     */
    public function getOriginalPrice()
    {
        return $this->original_price;
    }

    /**
     * Set price
     *
     * @param integer $price
     * @return TbYahooDataAdd
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set price_add_10per
     *
     * @param integer $priceAdd10per
     * @return TbYahooDataAdd
     */
    public function setPriceAdd10per($priceAdd10per)
    {
        $this->price_add_10per = $priceAdd10per;

        return $this;
    }

    /**
     * Get price_add_10per
     *
     * @return integer 
     */
    public function getPriceAdd10per()
    {
        return $this->price_add_10per;
    }

    /**
     * Set sale_price
     *
     * @param integer $salePrice
     * @return TbYahooDataAdd
     */
    public function setSalePrice($salePrice)
    {
        $this->sale_price = $salePrice;

        return $this;
    }

    /**
     * Get sale_price
     *
     * @return integer 
     */
    public function getSalePrice()
    {
        return $this->sale_price;
    }

    /**
     * Set options
     *
     * @param string $options
     * @return TbYahooDataAdd
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get options
     *
     * @return string 
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set headline
     *
     * @param string $headline
     * @return TbYahooDataAdd
     */
    public function setHeadline($headline)
    {
        $this->headline = $headline;

        return $this;
    }

    /**
     * Get headline
     *
     * @return string 
     */
    public function getHeadline()
    {
        return $this->headline;
    }

    /**
     * Set caption
     *
     * @param string $caption
     * @return TbYahooDataAdd
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;

        return $this;
    }

    /**
     * Get caption
     *
     * @return string 
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * Set abstract
     *
     * @param string $abstract
     * @return TbYahooDataAdd
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;

        return $this;
    }

    /**
     * Get abstract
     *
     * @return string 
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * Set explanation
     *
     * @param string $explanation
     * @return TbYahooDataAdd
     */
    public function setExplanation($explanation)
    {
        $this->explanation = $explanation;

        return $this;
    }

    /**
     * Get explanation
     *
     * @return string 
     */
    public function getExplanation()
    {
        return $this->explanation;
    }

    /**
     * Set additional1
     *
     * @param string $additional1
     * @return TbYahooDataAdd
     */
    public function setAdditional1($additional1)
    {
        $this->additional1 = $additional1;

        return $this;
    }

    /**
     * Get additional1
     *
     * @return string 
     */
    public function getAdditional1()
    {
        return $this->additional1;
    }

    /**
     * Set additional2
     *
     * @param string $additional2
     * @return TbYahooDataAdd
     */
    public function setAdditional2($additional2)
    {
        $this->additional2 = $additional2;

        return $this;
    }

    /**
     * Get additional2
     *
     * @return string 
     */
    public function getAdditional2()
    {
        return $this->additional2;
    }

    /**
     * Set additional3
     *
     * @param string $additional3
     * @return TbYahooDataAdd
     */
    public function setAdditional3($additional3)
    {
        $this->additional3 = $additional3;

        return $this;
    }

    /**
     * Get additional3
     *
     * @return string 
     */
    public function getAdditional3()
    {
        return $this->additional3;
    }

    /**
     * Set relevant_links
     *
     * @param string $relevantLinks
     * @return TbYahooDataAdd
     */
    public function setRelevantLinks($relevantLinks)
    {
        $this->relevant_links = $relevantLinks;

        return $this;
    }

    /**
     * Get relevant_links
     *
     * @return string 
     */
    public function getRelevantLinks()
    {
        return $this->relevant_links;
    }

    /**
     * Set ship_weight
     *
     * @param string $shipWeight
     * @return TbYahooDataAdd
     */
    public function setShipWeight($shipWeight)
    {
        $this->ship_weight = $shipWeight;

        return $this;
    }

    /**
     * Get ship_weight
     *
     * @return string 
     */
    public function getShipWeight()
    {
        return $this->ship_weight;
    }

    /**
     * Set taxable
     *
     * @param string $taxable
     * @return TbYahooDataAdd
     */
    public function setTaxable($taxable)
    {
        $this->taxable = $taxable;

        return $this;
    }

    /**
     * Get taxable
     *
     * @return string 
     */
    public function getTaxable()
    {
        return $this->taxable;
    }

    /**
     * Set release_date
     *
     * @param string $releaseDate
     * @return TbYahooDataAdd
     */
    public function setReleaseDate($releaseDate)
    {
        $this->release_date = $releaseDate;

        return $this;
    }

    /**
     * Get release_date
     *
     * @return string 
     */
    public function getReleaseDate()
    {
        return $this->release_date;
    }

    /**
     * Set temporary_point_term
     *
     * @param string $temporaryPointTerm
     * @return TbYahooDataAdd
     */
    public function setTemporaryPointTerm($temporaryPointTerm)
    {
        $this->temporary_point_term = $temporaryPointTerm;

        return $this;
    }

    /**
     * Get temporary_point_term
     *
     * @return string 
     */
    public function getTemporaryPointTerm()
    {
        return $this->temporary_point_term;
    }

    /**
     * Set point_code
     *
     * @param string $pointCode
     * @return TbYahooDataAdd
     */
    public function setPointCode($pointCode)
    {
        $this->point_code = $pointCode;

        return $this;
    }

    /**
     * Get point_code
     *
     * @return string 
     */
    public function getPointCode()
    {
        return $this->point_code;
    }

    /**
     * Set meta_key
     *
     * @param string $metaKey
     * @return TbYahooDataAdd
     */
    public function setMetaKey($metaKey)
    {
        $this->meta_key = $metaKey;

        return $this;
    }

    /**
     * Get meta_key
     *
     * @return string 
     */
    public function getMetaKey()
    {
        return $this->meta_key;
    }

    /**
     * Set meta_desc
     *
     * @param string $metaDesc
     * @return TbYahooDataAdd
     */
    public function setMetaDesc($metaDesc)
    {
        $this->meta_desc = $metaDesc;

        return $this;
    }

    /**
     * Get meta_desc
     *
     * @return string 
     */
    public function getMetaDesc()
    {
        return $this->meta_desc;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return TbYahooDataAdd
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return string 
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set sale_period_start
     *
     * @param string $salePeriodStart
     * @return TbYahooDataAdd
     */
    public function setSalePeriodStart($salePeriodStart)
    {
        $this->sale_period_start = $salePeriodStart;

        return $this;
    }

    /**
     * Get sale_period_start
     *
     * @return string 
     */
    public function getSalePeriodStart()
    {
        return $this->sale_period_start;
    }

    /**
     * Set sale_period_end
     *
     * @param string $salePeriodEnd
     * @return TbYahooDataAdd
     */
    public function setSalePeriodEnd($salePeriodEnd)
    {
        $this->sale_period_end = $salePeriodEnd;

        return $this;
    }

    /**
     * Get sale_period_end
     *
     * @return string 
     */
    public function getSalePeriodEnd()
    {
        return $this->sale_period_end;
    }

    /**
     * Set sale_limit
     *
     * @param string $saleLimit
     * @return TbYahooDataAdd
     */
    public function setSaleLimit($saleLimit)
    {
        $this->sale_limit = $saleLimit;

        return $this;
    }

    /**
     * Get sale_limit
     *
     * @return string 
     */
    public function getSaleLimit()
    {
        return $this->sale_limit;
    }

    /**
     * Set sp_code
     *
     * @param string $spCode
     * @return TbYahooDataAdd
     */
    public function setSpCode($spCode)
    {
        $this->sp_code = $spCode;

        return $this;
    }

    /**
     * Get sp_code
     *
     * @return string 
     */
    public function getSpCode()
    {
        return $this->sp_code;
    }

    /**
     * Set brand_code
     *
     * @param string $brandCode
     * @return TbYahooDataAdd
     */
    public function setBrandCode($brandCode)
    {
        $this->brand_code = $brandCode;

        return $this;
    }

    /**
     * Get brand_code
     *
     * @return string 
     */
    public function getBrandCode()
    {
        return $this->brand_code;
    }

    /**
     * Set person_code
     *
     * @param string $personCode
     * @return TbYahooDataAdd
     */
    public function setPersonCode($personCode)
    {
        $this->person_code = $personCode;

        return $this;
    }

    /**
     * Get person_code
     *
     * @return string 
     */
    public function getPersonCode()
    {
        return $this->person_code;
    }

    /**
     * Set yahoo_product_code
     *
     * @param string $yahooProductCode
     * @return TbYahooDataAdd
     */
    public function setYahooProductCode($yahooProductCode)
    {
        $this->yahoo_product_code = $yahooProductCode;

        return $this;
    }

    /**
     * Get yahoo_product_code
     *
     * @return string 
     */
    public function getYahooProductCode()
    {
        return $this->yahoo_product_code;
    }

    /**
     * Set product_code
     *
     * @param string $productCode
     * @return TbYahooDataAdd
     */
    public function setProductCode($productCode)
    {
        $this->product_code = $productCode;

        return $this;
    }

    /**
     * Get product_code
     *
     * @return string 
     */
    public function getProductCode()
    {
        return $this->product_code;
    }

    /**
     * Set jan
     *
     * @param string $jan
     * @return TbYahooDataAdd
     */
    public function setJan($jan)
    {
        $this->jan = $jan;

        return $this;
    }

    /**
     * Get jan
     *
     * @return string 
     */
    public function getJan()
    {
        return $this->jan;
    }

    /**
     * Set isbn
     *
     * @param string $isbn
     * @return TbYahooDataAdd
     */
    public function setIsbn($isbn)
    {
        $this->isbn = $isbn;

        return $this;
    }

    /**
     * Get isbn
     *
     * @return string 
     */
    public function getIsbn()
    {
        return $this->isbn;
    }

    /**
     * Set delivery
     *
     * @param string $delivery
     * @return TbYahooDataAdd
     */
    public function setDelivery($delivery)
    {
        $this->delivery = $delivery;

        return $this;
    }

    /**
     * Get delivery
     *
     * @return string 
     */
    public function getDelivery()
    {
        return $this->delivery;
    }

    /**
     * Set astk_code
     *
     * @param string $astkCode
     * @return TbYahooDataAdd
     */
    public function setAstkCode($astkCode)
    {
        $this->astk_code = $astkCode;

        return $this;
    }

    /**
     * Get astk_code
     *
     * @return string 
     */
    public function getAstkCode()
    {
        return $this->astk_code;
    }

    /**
     * Set condition
     *
     * @param string $condition
     * @return TbYahooDataAdd
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Get condition
     *
     * @return string 
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Set taojapan
     *
     * @param string $taojapan
     * @return TbYahooDataAdd
     */
    public function setTaojapan($taojapan)
    {
        $this->taojapan = $taojapan;

        return $this;
    }

    /**
     * Get taojapan
     *
     * @return string 
     */
    public function getTaojapan()
    {
        return $this->taojapan;
    }

    /**
     * Set product_category
     *
     * @param string $productCategory
     * @return TbYahooDataAdd
     */
    public function setProductCategory($productCategory)
    {
        $this->product_category = $productCategory;

        return $this;
    }

    /**
     * Get product_category
     *
     * @return string 
     */
    public function getProductCategory()
    {
        return $this->product_category;
    }

    /**
     * Set spec1
     *
     * @param string $spec1
     * @return TbYahooDataAdd
     */
    public function setSpec1($spec1)
    {
        $this->spec1 = $spec1;

        return $this;
    }

    /**
     * Get spec1
     *
     * @return string 
     */
    public function getSpec1()
    {
        return $this->spec1;
    }

    /**
     * Set spec2
     *
     * @param string $spec2
     * @return TbYahooDataAdd
     */
    public function setSpec2($spec2)
    {
        $this->spec2 = $spec2;

        return $this;
    }

    /**
     * Get spec2
     *
     * @return string 
     */
    public function getSpec2()
    {
        return $this->spec2;
    }

    /**
     * Set spec3
     *
     * @param string $spec3
     * @return TbYahooDataAdd
     */
    public function setSpec3($spec3)
    {
        $this->spec3 = $spec3;

        return $this;
    }

    /**
     * Get spec3
     *
     * @return string 
     */
    public function getSpec3()
    {
        return $this->spec3;
    }

    /**
     * Set spec4
     *
     * @param string $spec4
     * @return TbYahooDataAdd
     */
    public function setSpec4($spec4)
    {
        $this->spec4 = $spec4;

        return $this;
    }

    /**
     * Get spec4
     *
     * @return string 
     */
    public function getSpec4()
    {
        return $this->spec4;
    }

    /**
     * Set spec5
     *
     * @param string $spec5
     * @return TbYahooDataAdd
     */
    public function setSpec5($spec5)
    {
        $this->spec5 = $spec5;

        return $this;
    }

    /**
     * Get spec5
     *
     * @return string 
     */
    public function getSpec5()
    {
        return $this->spec5;
    }

    /**
     * Set display
     *
     * @param string $display
     * @return TbYahooDataAdd
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display
     *
     * @return string 
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set sort
     *
     * @param string $sort
     * @return TbYahooDataAdd
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return string 
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set sp_additional
     *
     * @param string $spAdditional
     * @return TbYahooDataAdd
     */
    public function setSpAdditional($spAdditional)
    {
        $this->sp_additional = $spAdditional;

        return $this;
    }

    /**
     * Get sp_additional
     *
     * @return string 
     */
    public function getSpAdditional()
    {
        return $this->sp_additional;
    }

    /**
     * Set lead_time_instock
     *
     * @param integer $leadTimeInstock
     * @return TbYahooDataAdd
     */
    public function setLeadTimeInstock($leadTimeInstock)
    {
        $this->lead_time_instock = $leadTimeInstock;

        return $this;
    }

    /**
     * Get lead_time_instock
     *
     * @return integer 
     */
    public function getLeadTimeInstock()
    {
        return $this->lead_time_instock;
    }

    /**
     * Set lead_time_outstock
     *
     * @param integer $leadTimeOutstock
     * @return TbYahooDataAdd
     */
    public function setLeadTimeOutstock($leadTimeOutstock)
    {
        $this->lead_time_outstock = $leadTimeOutstock;

        return $this;
    }

    /**
     * Get lead_time_outstock
     *
     * @return integer 
     */
    public function getLeadTimeOutstock()
    {
        return $this->lead_time_outstock;
    }

    /**
     * Set pr_rate
     *
     * @param string $prRate
     * @return TbYahooDataAdd
     */
    public function setPrRate($prRate)
    {
        $this->pr_rate = $prRate;

        return $this;
    }

    /**
     * Get pr_rate
     *
     * @return string 
     */
    public function getPrRate()
    {
        return $this->pr_rate;
    }

    /**
     * Set postage_set
     *
     * @param integer $postageSet
     * @return TbYahooDataAdd
     */
    public function setPostageSet($postageSet)
    {
        $this->postage_set = $postageSet;

        return $this;
    }

    /**
     * Get postage_set
     *
     * @return integer 
     */
    public function getPostageSet()
    {
        return $this->postage_set;
    }
}
