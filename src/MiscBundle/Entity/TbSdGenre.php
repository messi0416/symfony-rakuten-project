<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbSdGenre
 */
class TbSdGenre
{
    /**
     * @var string
     */
    private $category1;

    /**
     * @var string
     */
    private $category2;

    /**
     * @var string
     */
    private $genreBunrui;

    /**
     * @var string
     */
    private $genreName;

    /**
     * @var integer
     */
    private $genreCode;

    /**
     * @var string
     */
    private $kaigaiHanbai;


    /**
     * Set category1
     *
     * @param string $category1
     * @return TbSdGenre
     */
    public function setCategory1($category1)
    {
        $this->category1 = $category1;

        return $this;
    }

    /**
     * Get category1
     *
     * @return string 
     */
    public function getCategory1()
    {
        return $this->category1;
    }

    /**
     * Set category2
     *
     * @param string $category2
     * @return TbSdGenre
     */
    public function setCategory2($category2)
    {
        $this->category2 = $category2;

        return $this;
    }

    /**
     * Get category2
     *
     * @return string 
     */
    public function getCategory2()
    {
        return $this->category2;
    }

    /**
     * Set genreBunrui
     *
     * @param string $genreBunrui
     * @return TbSdGenre
     */
    public function setGenreBunrui($genreBunrui)
    {
        $this->genreBunrui = $genreBunrui;

        return $this;
    }

    /**
     * Get genreBunrui
     *
     * @return string 
     */
    public function getGenreBunrui()
    {
        return $this->genreBunrui;
    }

    /**
     * Set genreName
     *
     * @param string $genreName
     * @return TbSdGenre
     */
    public function setGenreName($genreName)
    {
        $this->genreName = $genreName;

        return $this;
    }

    /**
     * Get genreName
     *
     * @return string 
     */
    public function getGenreName()
    {
        return $this->genreName;
    }

    /**
     * Set genreCode
     *
     * @param integer $genreCode
     * @return TbSdGenre
     */
    public function setGenreCode($genreCode)
    {
        $this->genreCode = $genreCode;

        return $this;
    }

    /**
     * Get genreCode
     *
     * @return integer 
     */
    public function getGenreCode()
    {
        return $this->genreCode;
    }

    /**
     * Set kaigaiHanbai
     *
     * @param string $kaigaiHanbai
     * @return TbSdGenre
     */
    public function setKaigaiHanbai($kaigaiHanbai)
    {
        $this->kaigaiHanbai = $kaigaiHanbai;

        return $this;
    }

    /**
     * Get kaigaiHanbai
     *
     * @return string 
     */
    public function getKaigaiHanbai()
    {
        return $this->kaigaiHanbai;
    }
}
