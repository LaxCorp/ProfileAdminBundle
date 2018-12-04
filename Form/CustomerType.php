<?php

namespace LaxCorp\ProfileAdminBundle\Form;

use LaxCorp\BillingPartnerBundle\Model\Customer;
use Sonata\CoreBundle\Form\Type\DatePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @inheritdoc
 */
class CustomerType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('toDate', DatePickerType::class, [
                'label'       => 'label.expiration_date_to',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\DateTime()
                ]
            ])
            ->add('state', ChoiceType::class, [
                'label'                     => 'label.customer_state',
                'choices'                   => $this->getStateTypes(),
                'choice_translation_domain' => true
            ])
            ->add('password', TextType::class, [
                'label'       => 'label.customer_password',
                'attr' => ['minlength' => 6, 'maxlength' => 20],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 6, 'max' => 20])
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'         => Customer::class,
            'csrf_protection'    => false,
            'translation_domain' => 'profile_admin'
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
        return 'Customer';
    }

    /**
     * @inheritdoc
     */
    public function getStateTypes()
    {
        return [
            'choice.state_enabled'  => 'ENABLED',
            'choice.state_disabled' => 'DISABLED'
        ];
    }

}
