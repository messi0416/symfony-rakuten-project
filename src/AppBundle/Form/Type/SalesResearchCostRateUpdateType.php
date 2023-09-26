<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SalesResearchCostRateUpdateType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $inputOptions = [];

    $data = $options['data'];
    foreach($data['a'] as $code => $row) {
      $options = $inputOptions + [ 'data' => $row['cost_rate_average'] ];
      $builder->add($code, 'text', $options);
    }
  }

  public function getName()
  {
    return 'app_sales_research_cost_rate_update';
  }
}
