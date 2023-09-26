<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UncombinedOrderListSearchType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $dateOptions = [
        'input' => 'datetime'
      , 'widget' => 'single_text'
    ];

    $today = new \DateTime();

    $builder->add('from', 'date', $dateOptions + ['label' => '開始', 'data' => $today]);
    $builder->add('to', 'date', $dateOptions + ['label' => '終了', 'data' => $today]);
  }

  public function getName()
  {
    return 'app_uncombined_order_list_search';
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'csrf_protection' => false
    ));
  }

}
