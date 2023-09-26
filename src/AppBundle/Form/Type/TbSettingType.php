<?php

namespace AppBundle\Form\Type;

use MiscBundle\Entity\TbSetting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TbSettingType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('settingKey')
            ->add('settingVal', 'text', array(
                'constraints' => array(
                  new Assert\Length(array(
                    'max' => 255,
                    'maxMessage' => '値は{{ limit }}文字以内で入力してください',
                  ))
                )
            ))
            ->add('settingDesc')
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'MiscBundle\Entity\TbSetting'
            , 'csrf_protection' => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'miscbundle_tbsetting';
    }
}
