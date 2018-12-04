<?php

namespace LaxCorp\ProfileAdminBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * @inheritdoc
 */
class EditProfileType extends AbstractProfileType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('customer', CustomerType::class, ['label' => false]);
    }

}
