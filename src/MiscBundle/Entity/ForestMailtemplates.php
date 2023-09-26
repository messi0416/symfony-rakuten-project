<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * ForestMailtemplates
 */
class ForestMailtemplates
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @var integer
   */
  private $id;

  /**
   * @var string
   */
  private $choices1;

  /**
   * @var string
   */
  private $choices2;

  /**
   * @var string
   */
  private $choices3;

  /**
   * @var string
   */
  private $choices4;

  /**
   * @var string
   */
  private $choices5;

  /**
   * @var string
   */
  private $choices6;

  /**
   * @var string
   */
  private $choices7;

  /**
   * @var string
   */
  private $choices8;

  /**
   * @var string
   */
  private $choices9;

  /**
   * @var string
   */
  private $title;

  /**
   * @var string
   */
  private $body;


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
   * Set choices1
   *
   * @param string $choices1
   *
   * @return ForestMailtemplates
   */
  public function setChoices1($choices1)
  {
    $this->choices1 = $choices1;

    return $this;
  }

  /**
   * Get choices1
   *
   * @return string
   */
  public function getChoices1()
  {
    return $this->choices1;
  }

  /**
   * Set choices2
   *
   * @param string $choices2
   *
   * @return ForestMailtemplates
   */
  public function setChoices2($choices2)
  {
    $this->choices2 = $choices2;

    return $this;
  }

  /**
   * Get choices2
   *
   * @return string
   */
  public function getChoices2()
  {
    return $this->choices2;
  }

  /**
   * Set choices3
   *
   * @param string $choices3
   *
   * @return ForestMailtemplates
   */
  public function setChoices3($choices3)
  {
    $this->choices3 = $choices3;

    return $this;
  }

  /**
   * Get choices3
   *
   * @return string
   */
  public function getChoices3()
  {
    return $this->choices3;
  }

  /**
   * Set choices4
   *
   * @param string $choices4
   *
   * @return ForestMailtemplates
   */
  public function setChoices4($choices4)
  {
    $this->choices4 = $choices4;

    return $this;
  }

  /**
   * Get choices4
   *
   * @return string
   */
  public function getChoices4()
  {
    return $this->choices4;
  }

  /**
   * Set choices5
   *
   * @param string $choices5
   *
   * @return ForestMailtemplates
   */
  public function setChoices5($choices5)
  {
    $this->choices5 = $choices5;

    return $this;
  }

  /**
   * Get choices5
   *
   * @return string
   */
  public function getChoices5()
  {
    return $this->choices5;
  }

  /**
   * Set choices6
   *
   * @param string $choices6
   *
   * @return ForestMailtemplates
   */
  public function setChoices6($choices6)
  {
    $this->choices6 = $choices6;

    return $this;
  }

  /**
   * Get choices6
   *
   * @return string
   */
  public function getChoices6()
  {
    return $this->choices6;
  }

  /**
   * Set choices7
   *
   * @param string $choices7
   *
   * @return ForestMailtemplates
   */
  public function setChoices7($choices7)
  {
    $this->choices7 = $choices7;

    return $this;
  }

  /**
   * Get choices7
   *
   * @return string
   */
  public function getChoices7()
  {
    return $this->choices7;
  }

  /**
   * Set choices8
   *
   * @param string $choices8
   *
   * @return ForestMailtemplates
   */
  public function setChoices8($choices8)
  {
    $this->choices8 = $choices8;

    return $this;
  }

  /**
   * Get choices8
   *
   * @return string
   */
  public function getChoices8()
  {
    return $this->choices8;
  }

  /**
   * Set choices9
   *
   * @param string $choices9
   *
   * @return ForestMailtemplates
   */
  public function setChoices9($choices9)
  {
    $this->choices9 = $choices9;

    return $this;
  }

  /**
   * Get choices9
   *
   * @return string
   */
  public function getChoices9()
  {
    return $this->choices9;
  }

  /**
   * Set title
   *
   * @param string $title
   *
   * @return ForestMailtemplates
   */
  public function setTitle($title)
  {
    $this->title = $title;

    return $this;
  }

  /**
   * Get title
   *
   * @return string
   */
  public function getTitle()
  {
    return $this->title;
  }

  /**
   * Set body
   *
   * @param string $body
   *
   * @return ForestMailtemplates
   */
  public function setBody($body)
  {
    $this->body = $body;

    return $this;
  }

  /**
   * Get body
   *
   * @return string
   */
  public function getBody()
  {
    return $this->body;
  }
  /**
   * @var integer
   */
  private $active = -1;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;



  /**
   * Set active
   *
   * @param integer $active
   * @return ForestMailtemplates
   */
  public function setActive($active)
  {
    $this->active = $active;

    return $this;
  }

  /**
   * Get active
   *
   * @return integer 
   */
  public function getActive()
  {
    return $this->active;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return ForestMailtemplates
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
   * @return ForestMailtemplates
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

  /**
   * @var string
   */
  private $type = 'a';


  /**
   * Set type
   *
   * @param string $type
   * @return ForestMailtemplates
   */
  public function setType($type)
  {
    $this->type = $type;

    return $this;
  }

  /**
   * Get type
   *
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }
}
