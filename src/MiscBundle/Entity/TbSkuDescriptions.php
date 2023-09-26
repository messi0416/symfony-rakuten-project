<?php

namespace MiscBundle\Entity;

/**
 * TbSkuDescriptions
 */
class TbSkuDescriptions
{
    /**
   * @var integer
   */
    private $id;

    /**
   * @var string
   */
    private $descriptionEn;

    /**
     * @var boolean
     */
    private $descriptionDeleteFlg;


    /**
   * @var string
   */
    private $descriptionCn;

    /**
   * @var string
   */
    private $hintJa;

    /**
   * @var string
   */
    private $hintCn;

    /**
     * @var boolean
     */
    private $hintDeleteFlg;

    /**
   * @var \DateTime
   */
    private $created;

    /**
   * @var \DateTime
   */
    private $updated;

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
   * Set description_en
   *
   * @param string $descriptionEn
   * @return TbSkuDescriptions
   */
    public function setDescriptionEn($descriptionEn)
    {
        $this->descriptionEn = $descriptionEn;

        return $this;
    }

    /**
    * Get description_en
    *
    * @return string 
    */
    public function getDescriptionEn()
    {
        return $this->descriptionEn;
    }

    /**
   * Set description_cn
   *
   * @param string $descriptionCn
   * @return TbSkuDescriptions
   */
    public function setDescriptionCn($descriptionCn)
    {
        $this->descriptionCn = $descriptionCn;

        return $this;
    }

    /**
    * Get description_cn
    *
    * @return string 
    */
    public function getDescriptionCn()
    {
        return $this->descriptionCn;
    }

    /**
     * Set description_delete_flg
     *
     * @param string $descriptionDeleteFlg
     * @return TbSkuDescriptions
     */
    public function setDescriptionDeleteFlg($descriptionDeleteFlg)
    {
        $this->descriptionDeleteFlg = $descriptionDeleteFlg;

        return $this;
    }

    /**
     * get description_delete_flg
     *
     * @return boolean
     */
    public function getDescriptionDeleteFlg()
    {
        return $this->descriptionDeleteFlg;
    }

    /**
   * Set hint_ja
   *
   * @param string $hintJa
   * @return TbSkuDescriptions
   */
    public function setHintJa($hintJa)
    {
        $this->hintJa = $hintJa;

        return $this;
    }

    /**
    * Get hint_ja
    *
    * @return string 
    */
    public function getHintJa()
    {
        return $this->hintJa;
    }

    /**
   * Set hint_cn
   *
   * @param string $hintCn
   * @return TbSkuDescriptions
   */
    public function setHintCn($hintCn)
    {
        $this->hintCn = $hintCn;

        return $this;
    }

    /**
    * Get hint_cn
    *
    * @return string 
    */
    public function getHintCn()
    {
        return $this->hintCn;
    }

    /**
     * Set hint_delete_flg
     *
     * @param string $descriptionDeleteFlg
     * @return TbSkuDescriptions
     */
    public function setHintDeleteFlg($hintDeleteFlg)
    {
        $this->hintDeleteFlg = $hintDeleteFlg;

        return $this;
    }

    /**
     * get hint_delete_flg
     *
     * @return boolean
     */
    public function getHintDeleteFlg()
    {
        return $this->hintDeleteFlg;
    }



    /**
   * Set created
   *
   * @param \DateTime $created
   * @return TbSkuDescriptions
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
    * Set updated
    *
    * @param \DateTime $updated
    * @return TbSkuDescriptions
    */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
    * Get updated
    *
    * @return \DateTime 
    */
    public function getUpdated()
    {
        return $this->updated;
    }

    public function toArray()
  {
    return [
        'id' => $this->id
      , 'description_en' => $this->descriptionEn
      , 'description_cn' => $this->descriptionCn
      , 'description_delete_flg' => $this->descriptionDeleteFlg
      , 'hint_ja' => $this->hintJa
      , 'hint_cn' => $this->hintCn
      , 'hint_delete_flg' => $this->hintDeleteFlg
      , 'created' => $this->created ? $this->created->format('Y-m-d H:i:s') : null
      , 'updated' => $this->updated ? $this->updated->format('Y-m-d H:i:s') : null
    ];
  }
}
