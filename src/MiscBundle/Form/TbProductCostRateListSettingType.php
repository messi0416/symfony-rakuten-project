<?php

namespace MiscBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TbProductCostRateListSettingType extends AbstractType
{
  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('id', 'hidden')
      ->add('threshold_voucher_num', 'text')
      ->add('threshold_voucher_term', 'integer')
      ->add('sampling_days', 'integer')
      ->add('move_threshold_rate', 'integer')
      ->add('shake_border', 'integer')
      ->add('change_amount_up', 'integer')
      ->add('change_amount_down', 'integer')
      ->add('change_amount_additional', 'integer')
    ;
  }
  
  /**
   * @param OptionsResolverInterface $resolver
   */
  public function setDefaultOptions(OptionsResolverInterface $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'MiscBundle\Entity\TbProductCostRateListSetting'
    ));
  }

  /**
   * @return string
   */
  public function getName()
  {
    return 'miscbundle_tbproductcostratelistsetting';
  }
}
