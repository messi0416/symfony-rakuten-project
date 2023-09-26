<?php
namespace Plusnao\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChouchouClairStockListUploadCsvType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    // アップロードファイル
    $builder->add('uploaded', 'file');

    // 絞込引き継ぎ用
    $builder->add('searchTarget', 'hidden');
    $builder->add('code', 'hidden');
    $builder->add('keyword', 'hidden');
  }

  public function getName()
  {
    return 'plusnao_chouchou_clair_product_upload_csv';
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    // フォームに関連付けられたエンティティ
    $resolver->setDefaults([
      'data_class' => 'Plusnao\MainBundle\Form\Entity\ChouchouClairStockListUploadCsvTypeEntity'
    ]);
  }

}
