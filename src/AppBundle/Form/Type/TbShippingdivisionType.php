<?php

namespace AppBundle\Form\Type;

use MiscBundle\Entity\TbShippingdivision;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type as InputType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TbShippingdivisionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id')
            ->add('name', 'text', array(
                'constraints' => array(
                    new Assert\NotBlank(array(
                        'message' => '送料設定名が入力されていません'
                    ))
                )
            ))
            ->add('price', 'text', array(
                'constraints' => array(
                    new Assert\NotBlank(array(
                        'message' => '価格が入力されていません'
                    ))
                    , new Assert\Type(array(
                        'type' => 'numeric',
                        'message' => '価格が不正です'
                    ))
                )
            ))
            ->add('maxThreeEdgeSum', 'text', array(
                'constraints' => array(
                    new Assert\Type(array(
                        'type' => 'numeric',
                        'message' => '3辺計上限が不正です'
                    ))
                )
            ))
            ->add('maxThreeEdgeIndividual','text', array(
                'constraints' => array(
                    //正規表現マッチング
                    new Assert\Regex(array(
                        'pattern' => '/^[0-9]+,[0-9]+,[0-9]+$/',
                        'message' => '使用可能サイズ3辺個別値上限は、縦・横・高さ(cm)をカンマ区切りで入力してください'
                    ))
                ),
            ))
            ->add('maxWeight', 'text', array(
                'constraints' => array(
                    new Assert\Type(array(
                        'type' => 'numeric',
                        'message' => '重量上限が不正です'
                    ))
                )
            ))
            ->add('shippingGroupCode', 'text', array( // プルダウンだがFormのレンダリングはしないので受け取りはテキスト扱いで
                'constraints' => array(
                    new Assert\NotBlank(array(
                        'message' => '送料グループ種別が選択されていません'
                    )),
                    //正規表現マッチング
                    new Assert\Regex(array(
                        'pattern' => '/^[1-8]{1}$/', // 現時点では1-8のいずれか
                        'message' => '送料グループ種別が正しくありません'
                    ))
                ),
            ))
            ->add('note')
            ->add('terminateFlg')
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'MiscBundle\Entity\TbShippingdivision'
            , 'csrf_protection' => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'miscbundle_tbshippingdivision';
    }
}
