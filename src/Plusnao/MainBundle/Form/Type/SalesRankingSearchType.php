<?php
namespace Plusnao\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SalesRankingSearchType extends AbstractType
{
  // ランキング対象
  const RANKING_TARGET_SALES_AMOUNT = 'sales_amount';
  const RANKING_TARGET_ITEM_NUM = 'item_num';
  const RANKING_TARGET_VOUCHER_NUM = 'voucher_num';

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $dateOptions = [
        'input'  => 'datetime'
      , 'widget' => 'single_text'
    ];

    // 日付範囲 初期値
    $oneDayAgo = (new \DateTIme())->modify('-1 days');
    $oneWeekAgo = clone $oneDayAgo;
    $oneWeekAgo->modify('-6 days');

    $aEnd = clone $oneWeekAgo;
    $aEnd->modify('-1 days');
    $aStart = clone $aEnd;
    $aStart->modify('-6 days');

    $builder->add('dateAStart', 'date', $dateOptions + ['label' => 'A期間', 'data' => $aStart]);
    $builder->add('dateAEnd'  , 'date', $dateOptions + ['label' => ''     , 'data' => $aEnd]);
    $builder->add('dateBStart', 'date', $dateOptions + ['label' => 'B期間', 'data' => $oneWeekAgo]);
    $builder->add('dateBEnd'  , 'date', $dateOptions + ['label' => ''     , 'data' => $oneDayAgo]);

    $builder->add('rankingTarget', 'choice', array(
      'choices'  => array(
          self::RANKING_TARGET_SALES_AMOUNT => '販売金額'
        , self::RANKING_TARGET_ITEM_NUM => '販売個数'
        , self::RANKING_TARGET_VOUCHER_NUM => '伝票数'
      )
      , 'choices_as_values' => false // *this line is important*

      , 'expanded' => true
      , 'multiple' => false
      , 'required' => true
      , 'data' => self::RANKING_TARGET_SALES_AMOUNT
      , 'choice_attr' => function($val, $key, $index) {
        return ['v-model' => 'rankingTarget'];
      }
    ));

    // カテゴリ プルダウンだがJavaScriptでoptionを追加するため、(Symfonyのchoiceの)煩雑さを避けるためhidden。
    $builder->add('buyerID', 'hidden');
    $builder->add('bigCategory', 'hidden');
    $builder->add('midCategory', 'hidden');

    $builder->add('keyword', 'text', ['label' => 'キーワード', 'required' => false]);

    // 値保持用
    $builder->add('moveDays', 'number', ['label' => '', 'data' => 7]);
  }

  public function getName()
  {
    return 'plusnao_sales_ranking_search';
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    // フォームに関連付けられたエンティティ
    $resolver->setDefaults([
      'data_class' => 'Plusnao\MainBundle\Form\Entity\SalesRankingSearchTypeEntity'
    ]);
  }

}
