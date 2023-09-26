<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SalesResearchVendorStockoutTermType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $dateOptions = [
        'input' => 'datetime'
      , 'widget' => 'single_text'
    ];

    $oneDayAgo = (new \DateTime())->modify('-1 days');
    $oneWeekAgo = clone $oneDayAgo;
    $oneWeekAgo->modify('-7 days');

    $builder->add('dateStart', 'date', $dateOptions + ['label' => '開始', 'data' => $oneWeekAgo]);
    $builder->add('dateEnd', 'date', $dateOptions + ['label' => '終了', 'data' => $oneDayAgo]);

    $builder->add('moveDays', 'number', ['label' => '', 'data' => 7]);

  }

  public function getName()
  {
    return 'app_sales_research_vendor_stockout_term';
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    // フォームに関連付けられたエンティティ
    $resolver->setDefaults([
      'data_class' => 'AppBundle\Form\Entity\SalesResearchVendorStockoutTermTypeEntity'
    ]);
  }

}
