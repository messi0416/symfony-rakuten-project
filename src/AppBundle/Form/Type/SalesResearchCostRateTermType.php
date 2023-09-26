<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SalesResearchCostRateTermType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $dateOptions = [
        'input' => 'datetime'
      , 'widget' => 'single_text'
    ];

    $oneDayAgo = (new \DateTIme())->modify('-1 days');
    $twoDayAgo = (new \DateTIme())->modify('-2 days');

    $builder->add('dateAStart', 'date', $dateOptions + ['label' => 'A期間 開始', 'data' => $twoDayAgo]);
    $builder->add('dateAEnd', 'date', $dateOptions + ['label' => '終了', 'data' => $twoDayAgo]);
    $builder->add('dateBStart', 'date', $dateOptions + ['label' => 'B期間 開始', 'data' => $oneDayAgo]);
    $builder->add('dateBEnd', 'date', $dateOptions + ['label' => '終了', 'data' => $oneDayAgo]);

    $builder->add('moveDays', 'number', ['label' => '', 'data' => 1]);

  }

  public function getName()
  {
    return 'app_sales_research_cost_rate_term';
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'csrf_protection' => false
    ));
  }

}
