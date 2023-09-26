<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbProcess
 */
class TbProcess
{
    /** 処理種別：納品書印刷待ち伝票一覧再集計処理（通常） */
    const PROCESS_ID_REFRESH_STATEMENT_DETAIL_PRODUCT_NUM_LIST_NORMAL = '120';

    /** 処理種別：納品書印刷待ち伝票一覧再集計処理（移動在庫抽出用） */
    const PROCESS_ID_REFRESH_STATEMENT_DETAIL_PRODUCT_NUM_LIST_MOVING = '121';

    /** 処理種別：納品書印刷待ち伝票一覧再集計処理（SHOPLIST） */
    const PROCESS_ID_REFRESH_STATEMENT_DETAIL_PRODUCT_NUM_LIST_SHOPLIST = '122';

    /** 処理種別：商品売上実績集計処理（通常） */
    const PROCESS_ID_AGGREGATE_PRODUCT_SALES_ACCOUNT_RESULT_HISTORY_NORMAL = 143;

    /** 処理種別：商品売上実績集計処理（担当者更新分の集計） */
    const PROCESS_ID_AGGREGATE_PRODUCT_SALES_ACCOUNT_RESULT_HISTORY_RESERVED = 144;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $processCode;

    /**
     * @var string
     */
    private $note;

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
     * Set name
     *
     * @param string $name
     * @return TbProcess
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
     * Set processCode
     *
     * @param string $processCode
     * @return TbProcess
     */
    public function setProcessCode($processCode)
    {
        $this->processCode = $processCode;

        return $this;
    }

    /**
     * Get processCode
     *
     * @return string
     */
    public function getProcessCode()
    {
        return $this->processCode;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return TbProcess
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return TbProcess
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
}
