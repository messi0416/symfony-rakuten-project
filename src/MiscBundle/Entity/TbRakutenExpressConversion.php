<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbRakutenExpressConversion
 */
class TbRakutenExpressConversion
{
    /**
     * @var string
     */
    private $conversion_date;

    /**
     * @var string
     */
    private $converted_csv_name;

    /**
     * @var integer
     */
    private $saved_count = '0';

    /**
     * @var string
     */
    private $error_voucher_numbers;

    /**
     * Set conversion_date
     *
     * @param string $conversionDate
     * @return TbRakutenExpressConversion
     */
    public function setConversionDate($conversionDate)
    {
      $this->conversion_date = $conversionDate;

      return $this;
    }

    /**
     * Get conversion_date
     *
     * @return string
     */
    public function getConversionDate()
    {
      return $this->conversion_date;
    }

    /**
     * Set converted_csv_name
     *
     * @param string $convertedCsvName
     * @return TbRakutenExpressConversion
     */
    public function setConvertedCsvName($convertedCsvName)
    {
      $this->converted_csv_name = $convertedCsvName;

      return $this;
    }

    /**
     * Get converted_csv_name
     *
     * @return string
     */
    public function getConvertedCsvName()
    {
      return $this->converted_csv_name;
    }

    /**
     * Set saved_count
     *
     * @param integer $savedCount
     * @return TbRakutenExpressConversion
     */
    public function setSavedCount($savedCount)
    {
      $this->saved_count = $savedCount;

      return $this;
    }

    /**
     * Get saved_count
     *
     * @return integer
     */
    public function getSavedCount()
    {
      return $this->saved_count;
    }

    /**
     * Set error_voucher_numbers
     * @param string $error_voucher_numbers
     * @return TbRakutenExpressConversion
     */
    public function setError_voucher_numbers($error_voucher_numbers)
    {
      $this->error_voucher_numbers = $error_voucher_numbers;

      return $this;
    }

    /**
     * Get error_voucher_numbers
     * @return string
     */
    public function getError_voucher_numbers()
    {
      return $this->error_voucher_numbers;
    }


}
