<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * TbProductSeason
 */
class TbProductSeason
{

  use ArrayTrait;
  use FillTimestampTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="daihyo_syohin_code", type="string", length=30, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $daihyo_syohin_code;

    /**
     * @var boolean
     *
     * @ORM\Column(name="m1", type="boolean", nullable=false)
     */
    private $m1;

    /**
     * @var boolean
     *
     * @ORM\Column(name="m2", type="boolean", nullable=false)
     */
    private $m2;

    /**
     * @var boolean
     *
     * @ORM\Column(name="m3", type="boolean", nullable=false)
     */
    private $m3;

    /**
     * @var boolean
     *
     * @ORM\Column(name="m4", type="boolean", nullable=false)
     */
    private $m4;

    /**
     * @var boolean
     *
     * @ORM\Column(name="m5", type="boolean", nullable=false)
     */
    private $m5;

    /**
     * @var boolean
     *
     * @ORM\Column(name="m6", type="boolean", nullable=false)
     */
    private $m6;

    /**
     * @var boolean
     *
     * @ORM\Column(name="m7", type="boolean", nullable=false)
     */
    private $m7;

    /**
     * @var boolean
     *
     * @ORM\Column(name="m8", type="boolean", nullable=false)
     */
    private $m8;

    /**
     * @var boolean
     *
     * @ORM\Column(name="m9", type="boolean", nullable=false)
     */
    private $m9;

    /**
     * @var boolean
     *
     * @ORM\Column(name="m10", type="boolean", nullable=false)
     */
    private $m10;

    /**
     * @var boolean
     *
     * @ORM\Column(name="m11", type="boolean", nullable=false)
     */
    private $m11;

    /**
     * @var boolean
     *
     * @ORM\Column(name="m12", type="boolean", nullable=false)
     */
    private $m12;

    /**
     * @var boolean
     *
     * @ORM\Column(name="s1", type="boolean", nullable=false)
     */
    private $s1;

    /**
     * @var boolean
     *
     * @ORM\Column(name="s2", type="boolean", nullable=false)
     */
    private $s2;

    /**
     * @var boolean
     *
     * @ORM\Column(name="s3", type="boolean", nullable=false)
     */
    private $s3;

    /**
     * @var boolean
     *
     * @ORM\Column(name="s4", type="boolean", nullable=false)
     */
    private $s4;

    /**
     * @var boolean
     *
     * @ORM\Column(name="s5", type="boolean", nullable=false)
     */
    private $s5;

    /**
     * @var boolean
     *
     * @ORM\Column(name="s6", type="boolean", nullable=false)
     */
    private $s6;

    /**
     * @var boolean
     *
     * @ORM\Column(name="s7", type="boolean", nullable=false)
     */
    private $s7;

    /**
     * @var boolean
     *
     * @ORM\Column(name="s8", type="boolean", nullable=false)
     */
    private $s8;

    /**
     * @var boolean
     *
     * @ORM\Column(name="s9", type="boolean", nullable=false)
     */
    private $s9;

    /**
     * @var boolean
     *
     * @ORM\Column(name="s10", type="boolean", nullable=false)
     */
    private $s10;

    /**
     * @var boolean
     *
     * @ORM\Column(name="s11", type="boolean", nullable=false)
     */
    private $s11;

    /**
     * @var boolean
     *
     * @ORM\Column(name="s12", type="boolean", nullable=false)
     */
    private $s12;



    /**
     * Get daihyoSyohinCode
     *
     * @return string 
     */
    public function getDaihyoSyohinCode()
    {
        return $this->daihyo_syohin_code;
    }

    /**
     * Set m1
     *
     * @param boolean $m1
     * @return TbProductSeason
     */
    public function setM1($m1)
    {
        $this->m1 = $m1;

        return $this;
    }

    /**
     * Get m1
     *
     * @return boolean 
     */
    public function getM1()
    {
        return $this->m1;
    }

    /**
     * Set m2
     *
     * @param boolean $m2
     * @return TbProductSeason
     */
    public function setM2($m2)
    {
        $this->m2 = $m2;

        return $this;
    }

    /**
     * Get m2
     *
     * @return boolean 
     */
    public function getM2()
    {
        return $this->m2;
    }

    /**
     * Set m3
     *
     * @param boolean $m3
     * @return TbProductSeason
     */
    public function setM3($m3)
    {
        $this->m3 = $m3;

        return $this;
    }

    /**
     * Get m3
     *
     * @return boolean 
     */
    public function getM3()
    {
        return $this->m3;
    }

    /**
     * Set m4
     *
     * @param boolean $m4
     * @return TbProductSeason
     */
    public function setM4($m4)
    {
        $this->m4 = $m4;

        return $this;
    }

    /**
     * Get m4
     *
     * @return boolean 
     */
    public function getM4()
    {
        return $this->m4;
    }

    /**
     * Set m5
     *
     * @param boolean $m5
     * @return TbProductSeason
     */
    public function setM5($m5)
    {
        $this->m5 = $m5;

        return $this;
    }

    /**
     * Get m5
     *
     * @return boolean 
     */
    public function getM5()
    {
        return $this->m5;
    }

    /**
     * Set m6
     *
     * @param boolean $m6
     * @return TbProductSeason
     */
    public function setM6($m6)
    {
        $this->m6 = $m6;

        return $this;
    }

    /**
     * Get m6
     *
     * @return boolean 
     */
    public function getM6()
    {
        return $this->m6;
    }

    /**
     * Set m7
     *
     * @param boolean $m7
     * @return TbProductSeason
     */
    public function setM7($m7)
    {
        $this->m7 = $m7;

        return $this;
    }

    /**
     * Get m7
     *
     * @return boolean 
     */
    public function getM7()
    {
        return $this->m7;
    }

    /**
     * Set m8
     *
     * @param boolean $m8
     * @return TbProductSeason
     */
    public function setM8($m8)
    {
        $this->m8 = $m8;

        return $this;
    }

    /**
     * Get m8
     *
     * @return boolean 
     */
    public function getM8()
    {
        return $this->m8;
    }

    /**
     * Set m9
     *
     * @param boolean $m9
     * @return TbProductSeason
     */
    public function setM9($m9)
    {
        $this->m9 = $m9;

        return $this;
    }

    /**
     * Get m9
     *
     * @return boolean 
     */
    public function getM9()
    {
        return $this->m9;
    }

    /**
     * Set m10
     *
     * @param boolean $m10
     * @return TbProductSeason
     */
    public function setM10($m10)
    {
        $this->m10 = $m10;

        return $this;
    }

    /**
     * Get m10
     *
     * @return boolean 
     */
    public function getM10()
    {
        return $this->m10;
    }

    /**
     * Set m11
     *
     * @param boolean $m11
     * @return TbProductSeason
     */
    public function setM11($m11)
    {
        $this->m11 = $m11;

        return $this;
    }

    /**
     * Get m11
     *
     * @return boolean 
     */
    public function getM11()
    {
        return $this->m11;
    }

    /**
     * Set m12
     *
     * @param boolean $m12
     * @return TbProductSeason
     */
    public function setM12($m12)
    {
        $this->m12 = $m12;

        return $this;
    }

    /**
     * Get m12
     *
     * @return boolean 
     */
    public function getM12()
    {
        return $this->m12;
    }

    /**
     * Set s1
     *
     * @param boolean $s1
     * @return TbProductSeason
     */
    public function setS1($s1)
    {
        $this->s1 = $s1;

        return $this;
    }

    /**
     * Get s1
     *
     * @return boolean 
     */
    public function getS1()
    {
        return $this->s1;
    }

    /**
     * Set s2
     *
     * @param boolean $s2
     * @return TbProductSeason
     */
    public function setS2($s2)
    {
        $this->s2 = $s2;

        return $this;
    }

    /**
     * Get s2
     *
     * @return boolean 
     */
    public function getS2()
    {
        return $this->s2;
    }

    /**
     * Set s3
     *
     * @param boolean $s3
     * @return TbProductSeason
     */
    public function setS3($s3)
    {
        $this->s3 = $s3;

        return $this;
    }

    /**
     * Get s3
     *
     * @return boolean 
     */
    public function getS3()
    {
        return $this->s3;
    }

    /**
     * Set s4
     *
     * @param boolean $s4
     * @return TbProductSeason
     */
    public function setS4($s4)
    {
        $this->s4 = $s4;

        return $this;
    }

    /**
     * Get s4
     *
     * @return boolean 
     */
    public function getS4()
    {
        return $this->s4;
    }

    /**
     * Set s5
     *
     * @param boolean $s5
     * @return TbProductSeason
     */
    public function setS5($s5)
    {
        $this->s5 = $s5;

        return $this;
    }

    /**
     * Get s5
     *
     * @return boolean 
     */
    public function getS5()
    {
        return $this->s5;
    }

    /**
     * Set s6
     *
     * @param boolean $s6
     * @return TbProductSeason
     */
    public function setS6($s6)
    {
        $this->s6 = $s6;

        return $this;
    }

    /**
     * Get s6
     *
     * @return boolean 
     */
    public function getS6()
    {
        return $this->s6;
    }

    /**
     * Set s7
     *
     * @param boolean $s7
     * @return TbProductSeason
     */
    public function setS7($s7)
    {
        $this->s7 = $s7;

        return $this;
    }

    /**
     * Get s7
     *
     * @return boolean 
     */
    public function getS7()
    {
        return $this->s7;
    }

    /**
     * Set s8
     *
     * @param boolean $s8
     * @return TbProductSeason
     */
    public function setS8($s8)
    {
        $this->s8 = $s8;

        return $this;
    }

    /**
     * Get s8
     *
     * @return boolean 
     */
    public function getS8()
    {
        return $this->s8;
    }

    /**
     * Set s9
     *
     * @param boolean $s9
     * @return TbProductSeason
     */
    public function setS9($s9)
    {
        $this->s9 = $s9;

        return $this;
    }

    /**
     * Get s9
     *
     * @return boolean 
     */
    public function getS9()
    {
        return $this->s9;
    }

    /**
     * Set s10
     *
     * @param boolean $s10
     * @return TbProductSeason
     */
    public function setS10($s10)
    {
        $this->s10 = $s10;

        return $this;
    }

    /**
     * Get s10
     *
     * @return boolean 
     */
    public function getS10()
    {
        return $this->s10;
    }

    /**
     * Set s11
     *
     * @param boolean $s11
     * @return TbProductSeason
     */
    public function setS11($s11)
    {
        $this->s11 = $s11;

        return $this;
    }

    /**
     * Get s11
     *
     * @return boolean 
     */
    public function getS11()
    {
        return $this->s11;
    }

    /**
     * Set s12
     *
     * @param boolean $s12
     * @return TbProductSeason
     */
    public function setS12($s12)
    {
        $this->s12 = $s12;

        return $this;
    }

    /**
     * Get s12
     *
     * @return boolean 
     */
    public function getS12()
    {
        return $this->s12;
    }
}
