<?php

namespace LaxCorp\ProfileAdminBundle\Form;

use App\Entity\Profiles;
use App\Helper\AppFlagsInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @inheritdoc
 */
abstract class AbstractProfileType extends AbstractType
{

    /**
     * @var bool
     */
    public $catalogHostingEnabled;

    /**
     * @var AppFlagsInterface
     */
    public $appFlags;

    /**
     * @inheritdoc
     */
    public function __construct(?bool $catalogHostingEnabled, AppFlagsInterface $appFlags)
    {
        $this->catalogHostingEnabled = boolval($catalogHostingEnabled);
        $this->appFlags              = $appFlags;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Profiles $profiles */
        $profiles = $builder->getData();

        $builder
            ->add('name', null, [
                'label'       => 'label.profile_name',
                'trim'        => true,
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ]);

        if ($profiles->getFor1C() || $this->appFlags->isZenith()) {
            // skip
        } else {
            $builder->add('domainName', null, [
                'label'       => 'label.site_domain',
                'attr'        => ['placeholder' => 'placeholder.site_domain'],
                'trim'        => true,
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ]);
        }

        if ($profiles->getFor1C() || $this->appFlags->isZenith()) {
            // skip
        } elseif ($this->catalogHostingEnabled) {
            $builder
                ->add('hostingType', ChoiceType::class, [
                    'label'                     => 'label.catalog_hosting',
                    'choices'                   => $this->getHostingTypes(),
                    'choice_translation_domain' => true
                ])
                ->add('domainType', ChoiceType::class, [
                    'label'                     => 'label.catalog_domain',
                    'choices'                   => $this->getDomainTypes(),
                    'choice_translation_domain' => true
                ])
                ->add('fqdn', null, [
                    'label' => 'label.fqdn',
                    'trim'  => true,
                ])
                ->add('backUrl', null, [
                    'label' => 'label.backurl',
                    'attr'  => ['placeholder' => 'placeholder.url_template']
                ])
                ->add('backUrlNewWindow', CheckboxType::class, [
                    'label' => 'label.open_new_window',
                    'attr'  => ['placeholder' => 'placeholder.url_template']
                ]);
        }

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'         => Profiles::class,
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
        return 'Profile';
    }

    /**
     * @inheritdoc
     */
    public function getHostingTypes()
    {
        return [
            'choice.hosting_main' => 0,
            'choice.hosting_own'  => 1
        ];
    }

    /**
     * @inheritdoc
     */
    public function getDomainTypes()
    {
        return [
            'choice.domain_main' => 0,
            'choice.domain_own'  => 1
        ];
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
