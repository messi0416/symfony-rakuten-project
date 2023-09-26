<?php
namespace Plusnao\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VendorOrderListDownloadCsvType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $dateOptions = [
        'input'  => 'datetime'
      , 'widget' => 'single_text'
      , 'required' => false
    ];

    // 日付範囲 初期値
    $today = new \DateTimeImmutable();
    $dateStart = $today->setDate($today->format('Y'), $today->format('m'), 1);

    $builder->add('dateStart', 'date', $dateOptions + ['label' => '取得開始日', 'data' => $dateStart]);
    $builder->add('dateEnd'  , 'date', $dateOptions + ['label' => '取得終了日', 'data' => $today]);

    // 絞込引き継ぎ用
    $builder->add('searchTarget', 'hidden');
    $builder->add('code', 'hidden');
    $builder->add('keyword', 'hidden');
  }

  public function getName()
  {
    return 'vendor_order_list_download_csv';
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    // フォームに関連付けられたエンティティ
    $resolver->setDefaults([
      'data_class' => 'Plusnao\MainBundle\Form\Entity\VendorOrderListDownloadCsvTypeEntity'
    ]);
  }

}
