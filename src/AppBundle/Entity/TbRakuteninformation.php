<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbRakuteninformation
 *
 * @ORM\Table(name="tb_rakuteninformation")
 * @ORM\Entity
 */
class TbRakuteninformation
{
    /**
     * @var string
     *
     * @ORM\Column(name="daihyo_syohin_code", type="string", length=30)
     */
    private $daihyoSyohinCode;

    /**
     * @var integer
     * @ORM\Id
     *
     * @ORM\Column(name="baika_tanka", type="integer")
     */
    private $baikaTanka;


    /**
     * Set daihyoSyohinCode
     *
     * @param string $daihyoSyohinCode
     * @return TbRakuteninformation
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
     * Set baikaTanka
     *
     * @param integer $baikaTanka
     * @return TbRakuteninformation
     */
    public function setBaikaTanka($baikaTanka)
    {
        $this->baikaTanka = $baikaTanka;

        return $this;
    }

    /**
     * Get baikaTanka
     *
     * @return integer 
     */
    public function getBaikaTanka()
    {
        return $this->baikaTanka;
    }
}
