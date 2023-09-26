<?php

namespace MiscBundle\Entity;

/**
 * TbPlusnaoproductdirectory
 */
class TbPlusnaoproductdirectory
{

    /** フィールド1:ジュエリー・腕時計の場合の値 */
    const FIELD1_VALUE_JEWELRY_WATCH = "ジュエリー・腕時計";

    /**
     * @var string
     */
    private $id;


    /**
     * Get id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @var string
     */
    private $ne_directory_id;

    /**
     * @var string
     */
    private $field01 = '';

    /**
     * @var string
     */
    private $field02 = '';

    /**
     * @var string
     */
    private $field03 = '';

    /**
     * @var string
     */
    private $field04 = '';

    /**
     * @var string
     */
    private $field05 = '';

    /**
     * @var string
     */
    private $field06 = '';

    /**
     * @var string
     */
    private $rakuten_directory_id;


    /**
     * Get neDirectoryId
     *
     * @return string
     */
    public function getNeDirectoryId()
    {
        return $this->ne_directory_id;
    }

    /**
     * Set field01
     *
     * @param string $field01
     *
     * @return TbPlusnaoproductdirectory
     */
    public function setField01($field01)
    {
        $this->field01 = $field01;

        return $this;
    }

    /**
     * Get field01
     *
     * @return string
     */
    public function getField01()
    {
        return $this->field01;
    }

    /**
     * Set field02
     *
     * @param string $field02
     *
     * @return TbPlusnaoproductdirectory
     */
    public function setField02($field02)
    {
        $this->field02 = $field02;

        return $this;
    }

    /**
     * Get field02
     *
     * @return string
     */
    public function getField02()
    {
        return $this->field02;
    }

    /**
     * Set field03
     *
     * @param string $field03
     *
     * @return TbPlusnaoproductdirectory
     */
    public function setField03($field03)
    {
        $this->field03 = $field03;

        return $this;
    }

    /**
     * Get field03
     *
     * @return string
     */
    public function getField03()
    {
        return $this->field03;
    }

    /**
     * Set field04
     *
     * @param string $field04
     *
     * @return TbPlusnaoproductdirectory
     */
    public function setField04($field04)
    {
        $this->field04 = $field04;

        return $this;
    }

    /**
     * Get field04
     *
     * @return string
     */
    public function getField04()
    {
        return $this->field04;
    }

    /**
     * Set field05
     *
     * @param string $field05
     *
     * @return TbPlusnaoproductdirectory
     */
    public function setField05($field05)
    {
        $this->field05 = $field05;

        return $this;
    }

    /**
     * Get field05
     *
     * @return string
     */
    public function getField05()
    {
        return $this->field05;
    }

    /**
     * Set field06
     *
     * @param string $field06
     *
     * @return TbPlusnaoproductdirectory
     */
    public function setField06($field06)
    {
        $this->field06 = $field06;

        return $this;
    }

    /**
     * Get field06
     *
     * @return string
     */
    public function getField06()
    {
        return $this->field06;
    }

    /**
     * Set rakutenDirectoryId
     *
     * @param string $rakutenDirectoryId
     *
     * @return TbPlusnaoproductdirectory
     */
    public function setRakutenDirectoryId($rakutenDirectoryId)
    {
        $this->rakuten_directory_id = $rakutenDirectoryId;

        return $this;
    }

    /**
     * Get rakutenDirectoryId
     *
     * @return string
     */
    public function getRakutenDirectoryId()
    {
        return $this->rakuten_directory_id;
    }
}
