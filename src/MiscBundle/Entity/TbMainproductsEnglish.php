<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbMainproductsEnglish
 */
class TbMainproductsEnglish
{
  /**
   * 手入力か
   */
  public function isManualInput()
  {
    return $this->getManualInput() != 0;
  }

  /**
   * チェック済みか
   */
  public function isChecked()
  {
    return $this->getCheckFlg() != 0;
  }

  // -----------------------------------
  // properties
  // -----------------------------------

  /**
   * @var string
   */
  private $title;

  /**
   * @var string
   */
  private $description;

  /**
   * @var string
   */
  private $about_size;

  /**
   * @var string
   */
  private $about_color;

  /**
   * @var string
   */
  private $about_material;

  /**
   * @var string
   */
  private $about_brand;

  /**
   * @var string
   */
  private $usage_note;

  /**
   * @var string
   */
  private $supplemental_explanation;

  /**
   * @var string
   */
  private $short_description;

  /**
   * @var string
   */
  private $short_supplemental_explanation;

  /**
   * @var \MiscBundle\Entity\TbMainproducts
   */
  private $product;


  /**
   * Set title
   *
   * @param string $title
   * @return TbMainproductsEnglish
   */
  public function setTitle($title)
  {
    $this->title = $title;

    return $this;
  }

  /**
   * Get title
   *
   * @return string 
   */
  public function getTitle()
  {
    return $this->title;
  }

  /**
   * Set description
   *
   * @param string $description
   * @return TbMainproductsEnglish
   */
  public function setDescription($description)
  {
    $this->description = $description;

    return $this;
  }

  /**
   * Get description
   *
   * @return string 
   */
  public function getDescription()
  {
    return $this->description;
  }

  /**
   * Set about_size
   *
   * @param string $aboutSize
   * @return TbMainproductsEnglish
   */
  public function setAboutSize($aboutSize)
  {
    $this->about_size = $aboutSize;

    return $this;
  }

  /**
   * Get about_size
   *
   * @return string 
   */
  public function getAboutSize()
  {
    return $this->about_size;
  }

  /**
   * Set about_color
   *
   * @param string $aboutColor
   * @return TbMainproductsEnglish
   */
  public function setAboutColor($aboutColor)
  {
    $this->about_color = $aboutColor;

    return $this;
  }

  /**
   * Get about_color
   *
   * @return string 
   */
  public function getAboutColor()
  {
    return $this->about_color;
  }

  /**
   * Set about_material
   *
   * @param string $aboutMaterial
   * @return TbMainproductsEnglish
   */
  public function setAboutMaterial($aboutMaterial)
  {
    $this->about_material = $aboutMaterial;

    return $this;
  }

  /**
   * Get about_material
   *
   * @return string 
   */
  public function getAboutMaterial()
  {
    return $this->about_material;
  }

  /**
   * Set about_brand
   *
   * @param string $aboutBrand
   * @return TbMainproductsEnglish
   */
  public function setAboutBrand($aboutBrand)
  {
    $this->about_brand = $aboutBrand;

    return $this;
  }

  /**
   * Get about_brand
   *
   * @return string 
   */
  public function getAboutBrand()
  {
    return $this->about_brand;
  }

  /**
   * Set usage_note
   *
   * @param string $usageNote
   * @return TbMainproductsEnglish
   */
  public function setUsageNote($usageNote)
  {
    $this->usage_note = $usageNote;

    return $this;
  }

  /**
   * Get usage_note
   *
   * @return string 
   */
  public function getUsageNote()
  {
    return $this->usage_note;
  }

  /**
   * Set supplemental_explanation
   *
   * @param string $supplementalExplanation
   * @return TbMainproductsEnglish
   */
  public function setSupplementalExplanation($supplementalExplanation)
  {
    $this->supplemental_explanation = $supplementalExplanation;

    return $this;
  }

  /**
   * Get supplemental_explanation
   *
   * @return string 
   */
  public function getSupplementalExplanation()
  {
    return $this->supplemental_explanation;
  }

  /**
   * Set short_description
   *
   * @param string $shortDescription
   * @return TbMainproductsEnglish
   */
  public function setShortDescription($shortDescription)
  {
    $this->short_description = $shortDescription;

    return $this;
  }

  /**
   * Get short_description
   *
   * @return string 
   */
  public function getShortDescription()
  {
    return $this->short_description;
  }

  /**
   * Set short_supplemental_explanation
   *
   * @param string $shortSupplementalExplanation
   * @return TbMainproductsEnglish
   */
  public function setShortSupplementalExplanation($shortSupplementalExplanation)
  {
    $this->short_supplemental_explanation = $shortSupplementalExplanation;

    return $this;
  }

  /**
   * Get short_supplemental_explanation
   *
   * @return string 
   */
  public function getShortSupplementalExplanation()
  {
    return $this->short_supplemental_explanation;
  }

  /**
   * Set product
   *
   * @param \MiscBundle\Entity\TbMainproducts $product
   * @return TbMainproductsEnglish
   */
  public function setProduct(\MiscBundle\Entity\TbMainproducts $product = null)
  {
    $this->product = $product;

    return $this;
  }

  /**
   * Get product
   *
   * @return \MiscBundle\Entity\TbMainproducts 
   */
  public function getProduct()
  {
    return $this->product;
  }
  /**
   * @var string
   */
  private $daihyo_syohin_code;


  /**
   * Set daihyo_syohin_code
   *
   * @param string $daihyoSyohinCode
   * @return TbMainproductsEnglish
   */
  public function setDaihyoSyohinCode($daihyoSyohinCode)
  {
    $this->daihyo_syohin_code = $daihyoSyohinCode;

    return $this;
  }

  /**
   * Get daihyo_syohin_code
   *
   * @return string 
   */
  public function getDaihyoSyohinCode()
  {
    return $this->daihyo_syohin_code;
  }
    /**
     * @var integer
     */
    private $manual_input = 0;

    /**
     * @var integer
     */
    private $check_flg = 0;


    /**
     * Set manual_input
     *
     * @param integer $manualInput
     * @return TbMainproductsEnglish
     */
    public function setManualInput($manualInput)
    {
        $this->manual_input = $manualInput;

        return $this;
    }

    /**
     * Get manual_input
     *
     * @return integer 
     */
    public function getManualInput()
    {
        return $this->manual_input;
    }

    /**
     * Set check_flg
     *
     * @param integer $checkFlg
     * @return TbMainproductsEnglish
     */
    public function setCheckFlg($checkFlg)
    {
        $this->check_flg = $checkFlg;

        return $this;
    }

    /**
     * Get check_flg
     *
     * @return integer 
     */
    public function getCheckFlg()
    {
        return $this->check_flg;
    }
}
