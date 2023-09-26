<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TbDeliverySplitRuleType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id')
            ->add('rulename')
            ->add('checkorder')
            ->add('prefectureCheckColumn')
            ->add('longlength')
            ->add('middlelength')
            ->add('shortlength')
            ->add('totallength')
            ->add('volume')
            ->add('weight')
            ->add('sizecheck')
            ->add('maxflg')
            ->add('deliveryId')
            ->add('groupid')
            ->add('groupname')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'MiscBundle\Entity\TbDeliverySplitRule'
            , 'csrf_protection' => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'miscbundle_tbdeliverysplitrule';
    }
}
