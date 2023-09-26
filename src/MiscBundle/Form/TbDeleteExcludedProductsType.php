<?php

namespace MiscBundle\Form;

use MiscBundle\Entity\TbShoppingMall;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TbDeleteExcludedProductsType extends AbstractType
{
  /** @var TbShoppingMall[] $mallList */
  protected $mallList = [];

  public function setMallList($mallList)
  {
    $this->mallList = $mallList;
  }
  
  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $mallOptions = [];
    foreach($this->mallList as $mall) {
      $mallOptions[$mall->getMallId()] = $mall->getMallName();
    }

    $builder
      ->add('mall_id', 'choice', [
            'label' => 'モール'
          , 'choices'  => $mallOptions
        ]
      )
      ->add('syohin_code', 'text', ['label' => '商品コード'])
      ->add('comment', 'textarea', ['label' => '備考', 'required' => false])
      // ->add('display_order')
    ;
  }
  
  /**
   * @param OptionsResolverInterface $resolver
   */
  public function setDefaultOptions(OptionsResolverInterface $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'MiscBundle\Entity\TbDeleteExcludedProducts'
    ));
  }

  /**
   * @return string
   */
  public function getName()
  {
    return 'miscbundle_tbdeleteexcludedproducts';
  }
}
