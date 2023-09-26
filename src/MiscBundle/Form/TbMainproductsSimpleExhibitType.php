<?php

namespace MiscBundle\Form;

use MiscBundle\Util\DbCommonUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TbMainproductsSimpleExhibitType extends AbstractType
{
  /** @var TbShoppingMall[] $mallList */
  protected $sireCodeList = [];

  public function setSireCodeList($sireCodeList)
  {
    $this->sireCodeList = $sireCodeList;
  }

  /** @var TbShoppingMall[] $mallList */
  protected $companyList = [];

  public function setCompanyList($companyList)
  {
    $this->companyList = $companyList;
  }


  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $sire_code = [];
    foreach($this->sireCodeList as $key => $val) {
      $sire_code[$key] = $val;
    }

    $company = [];
    foreach($this->companyList as $key => $val) {
      $company[$key] = $val;
    }

    $builder
      ->add('daihyoSyohinCode', 'text', [
          'label' => '代表商品コード'
        , 'required' => true
        , 'max_length' => 30
        , 'data' => ''
      ])
      ->add('daihyo_syohin_name', 'text', [
          'label' => '商品名'
        , 'required' => true
        , 'max_length' => 85
      ])
      ->add('genka_tnk', 'integer', [
          'label' => '仕入原価'
        , 'required' => true
      ])
      ->add('sire_code', 'choice', [
        'choices' => $sire_code
        , 'label' => '仕入先'
        , 'required' => true
      ])
      ->add('col_type_name', 'text', [
          'label' => '横軸項目名'
        , 'required' => true
        , 'data' => 'サイズ'
      ])
      ->add('row_type_name', 'text', [
          'label' => '縦軸項目名'
        , 'required' => true
        , 'data' => 'カラー'
      ])
      ->add('company', 'choice', [
        'choices' => $company
        , 'label' => '会社名'
        , 'required' => true
      ])
    ;
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'MiscBundle\Entity\TbMainproducts'
    ));
  }

  /**
   * @return string
   */
  public function getName()
  {
    return 'miscbundle_tbmainproductssimpleexhibittype';
  }
}
