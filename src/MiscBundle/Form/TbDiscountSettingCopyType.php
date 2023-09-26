<?php

namespace MiscBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TbDiscountSettingCopyType extends AbstractType
{
  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
        ->add('id', 'hidden')
        ->add('discount_excluded_days', 'integer')
        ->add('sales_term_days', 'integer')
        ->add('sales_sampling_days', 'integer')
        ->add('sell_out_days', 'integer')
        ->add('allowed_sell_out_over_days', 'integer')
        ->add('max_discount_rate', 'integer')
        ->add('limit_within_days1', 'integer')
        ->add('limit_rate_for_cost1', 'integer')
        ->add('limit_within_days2', 'integer')
        ->add('limit_rate_for_cost2', 'integer')
        ->add('limit_within_days3', 'integer')
        ->add('limit_rate_for_cost3', 'integer')
        ;
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    // フォームに関連付けられたエンティティ
    $resolver->setDefaults([
      'data_class' => 'MiscBundle\Entity\TbDiscountSetting'
    ]);
  }

  /**
   * @return string
   */
  public function getName()
  {
    return 'miscbundle_tbdiscountsetting';
  }
}
