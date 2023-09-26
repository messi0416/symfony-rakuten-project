<?php

namespace MiscBundle\Form;

use MiscBundle\Util\DbCommonUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TbMainproductsSimpleType extends AbstractType
{
  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('daihyoSyohinCode', 'text', [
          'label' => '代表商品コード'
        , 'required' => true
        , 'max_length' => 30
        , 'data' => 'mnn-'
      ])
      ->add('daihyo_syohin_name', 'text', [
          'label' => '商品名'
        , 'required' => true
        , 'max_length' => 85
      ])
      ->add('genka_tnk', 'integer', [
          'label' => '仕入原価'
        , 'required' => true
      ])
      ->add('col_type_name', 'text', [
          'label' => '横軸項目名'
        , 'required' => true
        , 'data' => 'サイズ'
      ])
      ->add('row_type_name', 'text', [
          'label' => '縦軸項目名'
        , 'required' => true
        , 'data' => 'カラー'
      ])
    ;
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'MiscBundle\Entity\TbMainproducts'
    ));
  }

  /**
   * @return string
   */
  public function getName()
  {
    return 'miscbundle_tbmainproductssimpletype';
  }
}
