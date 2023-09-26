<?php

namespace MiscBundle\Entity;

/**
 * TbSetting
 */
class Setting
{
    /**
     * @var string
     */
    private $settingKey;

    /**
     * @var string
     */
    private $settingVal;

    /**
     * @var string
     */
    private $settingDesc;


    /**
     * Get settingKey
     *
     * @return string
     */
    public function getSettingKey()
    {
        return $this->settingKey;
    }

    /**
     * Set settingVal
     *
     * @param string $settingVal
     *
     * @return TbSetting
     */
    public function setSettingVal($settingVal)
    {
        $this->settingVal = $settingVal;

        return $this;
    }

    /**
     * Get settingVal
     *
     * @return string
     */
    public function getSettingVal()
    {
        return $this->settingVal;
    }

    /**
     * Set settingDesc
     *
     * @param string $settingDesc
     *
     * @return TbSetting
     */
    public function setSettingDesc($settingDesc)
    {
        $this->settingDesc = $settingDesc;

        return $this;
    }

    /**
     * Get settingDesc
     *
     * @return string
     */
    public function getSettingDesc()
    {
        return $this->settingDesc;
    }

    /**
     * Set settingKey
     *
     * @param string $settingKey
     *
     * @return TbSetting
     */
    public function setSettingKey($settingKey)
    {
        $this->settingKey = $settingKey;

        return $this;
    }
}
