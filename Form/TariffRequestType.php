<?php

namespace LaxCorp\ProfileAdminBundle\Form;

use LaxCorp\ProfileAdminBundle\Model\TariffRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @inheritdoc
 */
class TariffRequestType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('clientId')
            ->add('profileId')
            ->add('tariffId')
            ->add('resultContainer')
            ->add('replaceTariffId')
            ->add('autoRenewal')
            ->add('jobs')
            ->add('for1c');

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'      => TariffRequest::class,
            'csrf_protection' => false,

        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'TariffRequest';
    }


}
