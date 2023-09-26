<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MallDesignType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', 'text', ['label' => 'モールデザインコード', 'attr' => array('class' => 'form-control')])
            ->add('name', 'text', ['label' => 'モールデザイン名', 'attr' => array('class' => 'form-control')])
            ->add('designHtml', 'textarea', ['label' => 'モールデザインHTML', 'attr' => array('class' => 'form-control', 'rows' => '10')])
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\MallDesign'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'appbundle_malldesign';
    }
}
