<?php
namespace Plusnao\MainBundle\Form\Type;

use Plusnao\MainBundle\Form\Entity\ChouchouClairStockListSearchTypeEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChouchouClairStockListSearchType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $builder->add('searchTarget', 'choice', array(
      'choices'  => array(
          ChouchouClairStockListSearchTypeEntity::LIST_TARGET_ALL => '全て'
        , ChouchouClairStockListSearchTypeEntity::LIST_TARGET_MODIFIED => '修正済のみ'
      )
      , 'choices_as_values' => false // *this line is important*

      , 'expanded' => true
      , 'multiple' => false
      , 'required' => true
      , 'data' => ChouchouClairStockListSearchTypeEntity::LIST_TARGET_ALL
      , 'choice_attr' => function($val, $key, $index) {
        return ['v-model' => 'searchConditions.target'];
      }
    ));

    $builder->add('code', 'text', ['label' => '商品管理番号', 'required' => false]);
    $builder->add('keyword', 'text', ['label' => '商品名', 'required' => false]);
  }

  public function getName()
  {
    return 's';
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    // フォームに関連付けられたエンティティ
    $resolver->setDefaults([
        'data_class' => 'Plusnao\MainBundle\Form\Entity\ChouchouClairStockListSearchTypeEntity'
      , 'csrf_protection' => false
    ]);
  }

}
