<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbMainproductsCal
 *
 * @ORM\Table(name="tb_mainproducts_cal")
 * @ORM\Entity
 */
class TbMainproductsCal
{
    /**
     * @var string
     *
     * @ORM\Column(name="daihyo_syohin_code", type="string", length=30)
     * @ORM\Id
     */
    private $daihyoSyohinCode;

    /**
     * @var string
     *
     * @ORM\Column(name="受発注可能フラグ退避F", type="integer")
     */
    private $orderingAvoidFlg;


    /**
     * Set daihyoSyohinCode
     *
     * @param string $daihyoSyohinCode
     * @return TbMainproductsCal
     */
    public function setDaihyoSyohinCode($daihyoSyohinCode)
    {
        $this->daihyoSyohinCode = $daihyoSyohinCode;

        return $this;
    }

    /**
     * Get daihyoSyohinCode
     *
     * @return string 
     */
    public function getDaihyoSyohinCode()
    {
        return $this->daihyoSyohinCode;
    }

    /**
     * Set orderingAvoidFlg
     *
     * @param integer $orderingAvoidFlg
     * @return TbMainproductsCal
     */
    public function setOrderingAvoidFlg($orderingAvoidFlg)
    {
        $this->orderingAvoidFlg = $orderingAvoidFlg;

        return $this;
    }

    /**
     * Get orderingAvoidFlg
     *
     * @return integer 
     */
    public function getOrderingAvoidFlg()
    {
        return $this->orderingAvoidFlg;
    }
}
