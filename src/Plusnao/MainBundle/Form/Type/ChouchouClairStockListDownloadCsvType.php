<?php
namespace Plusnao\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChouchouClairStockListDownloadCsvType extends AbstractType
{
  /** @var  \DateTime */
  protected $downloadedMaxStockModified;

  /**
   * 最終ダウンロード上限日 セット
   * @param \DateTime $downloadedMaxStockModified
   */
  public function setDownloadedMaxStockModified($downloadedMaxStockModified)
  {
    $this->downloadedMaxStockModified = $downloadedMaxStockModified;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $dateOptions = [
        'input'  => 'datetime'
      , 'widget' => 'single_text'
      , 'required' => false
    ];

    // 日付範囲 初期値
    $today = new \DateTime();
    $dateStart = $this->downloadedMaxStockModified;

    $builder->add('dateStart', 'date', $dateOptions + ['label' => '取得開始日', 'data' => $dateStart]);
    $builder->add('dateEnd'  , 'date', $dateOptions + ['label' => '取得終了日', 'data' => $today]);

    // 絞込引き継ぎ用
    $builder->add('searchTarget', 'hidden');
    $builder->add('code', 'hidden');
    $builder->add('keyword', 'hidden');
  }

  public function getName()
  {
    return 'plusnao_chouchou_clair_product_download_csv';
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    // フォームに関連付けられたエンティティ
    $resolver->setDefaults([
      'data_class' => 'Plusnao\MainBundle\Form\Entity\ChouchouClairStockListDownloadCsvTypeEntity'
    ]);
  }

}
