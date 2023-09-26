<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * MallDesign
 *
 * @ORM\Table(name="tb_mall_design")
 * @ORM\Entity
 * @UniqueEntity("code")
 */
class MallDesign
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=30)
     * @Assert\NotBlank()
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="design_html", type="string")
     */
    private $designHtml;


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
     * Set code
     *
     * @param string $code
     * @return MallDesign
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return MallDesign
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
     * Set designHtml
     *
     * @param string $designHtml
     * @return MallDesign
     */
    public function setDesignHtml($designHtml)
    {
        $this->designHtml = $designHtml;

        return $this;
    }

    /**
     * Get designHtml
     *
     * @return string 
     */
    public function getDesignHtml()
    {
        return $this->designHtml;
    }
}
