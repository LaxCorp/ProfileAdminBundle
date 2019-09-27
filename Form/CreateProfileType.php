<?php

namespace LaxCorp\ProfileAdminBundle\Form;

use App\Helper\AppFlagsInterface;
use LaxCorp\ProfileAdminBundle\Model\ExtarFields;
use Sonata\CoreBundle\Form\Type\DatePickerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @inheritdoc
 */
class CreateProfileType extends AbstractProfileType
{

    /**
     * @var string
     */
    public $customerExpirationDateTo;

    /**
     * @inheritdoc
     */
    public function __construct(
        ?bool $catalogHostingEnabled,
        string $customerExpirationDateTo,
        AppFlagsInterface $appFlags
    ) {
        parent::__construct($catalogHostingEnabled, $appFlags);
        $this->customerExpirationDateTo = $customerExpirationDateTo;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        if ($this->appFlags->isZenith()) {
            $builder
                ->add('jobs', NumberType::class, [
                    'label'       => 'label.number_of_workplaces',
                    'data'        => 1,
                    'mapped'      => false,
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Range(['min' => 1, 'max' => 100])
                    ]
                ]);
        }

        $builder->remove('name');

        $builder
            ->add('name', null, [
                'label' => 'label.profile_name',
                'trim'  => true
            ]);

        $builder
            ->add('toDate', DatePickerType::class, [
                'label'       => 'label.expiration_date_to',
                'data'        => new \DateTime($this->customerExpirationDateTo),
                'mapped'      => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\DateTime()
                ]
            ])
            ->add('state', ChoiceType::class, [
                'label'                     => 'label.customer_state',
                'choices'                   => $this->getStateTypes(),
                'choice_translation_domain' => true,
                'mapped'                    => false
            ]);

        $this->addExtraFields($builder);
    }

    /**
     * @inheritdoc
     */
    private function addExtraFields(FormBuilderInterface $builder)
    {
        $extraFields = new ExtarFields();

        foreach ($extraFields as $key => $val) {
            $builder->add($key, CollectionType::class, [
                'label'              => false,
                'allow_add'          => true,
                'allow_delete'       => true,
                'mapped'             => false,
                'translation_domain' => false
            ]);
        }
    }

}
