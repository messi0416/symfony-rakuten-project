<?php

namespace MiscBundle\Form;

use MiscBundle\Util\DbCommonUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TbMainproductsDeliveryInfoType extends AbstractType
{
  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
     -> add('shippingdivision', 'choice', [
          'label' => '送料設定'
        , 'required' => true
        , 'choices' => array_flip(DbCommonUtil::$DELIVERY_METHOD_LIST)
        , 'choices_as_values' => true
      ])
      ->add('weight', 'integer', [
          'label' => '重量(g)'
        , 'required' => true
      ])
      ->add('mail_send_nums', 'number', [
          'label' => 'メール便枚数'
        , 'required' => false
        , 'precision' => 2
        , 'rounding_mode' => NumberToLocalizedStringTransformer::ROUND_HALF_DOWN
      ])
      ->add('weight_check_need_flg', 'checkbox', [
          'label' => '重厚計測'
        , 'required' => false
        , 'value' => -1
      ])
      ->add('compress_flg', 'checkbox', [
          'label' => '圧縮商品'
        , 'required' => false
        , 'value' => -1
      ])
      ->add('depth', 'integer', [
          'label' => '縦(mm)'
        , 'required' => true
      ])
      ->add('width', 'integer', [
          'label' => '横(mm)'
        , 'required' => true
      ])
      ->add('height', 'integer', [
          'label' => '高さ(mm)'
        , 'required' => true
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
    return 'miscbundle_tbmainproductsdeliveryinfotype';
  }
}
