<?php

namespace LaxCorp\ProfileAdminBundle\Controller;

use App\Entity\Client;
use App\Helper\AppFlagsInterface;
use App\Helper\BillingNotificationHelper;
use App\Helper\ClientHelper;
use App\Helper\ProfileHelper;
use LaxCorp\BillingPartnerBundle\Helper\CustomerHelper;
use LaxCorp\BillingPartnerBundle\Helper\CustomerTariffHelper;
use LaxCorp\CatalogHostingBundle\Helper\CatalogCustomerHelper;
use LaxCorp\ProfileAdminBundle\Exception\ClientNotFoundException;
use LaxCorp\ProfileAdminBundle\Exception\ProfileNotFoundException;
use LaxCorp\ProfileAdminBundle\Model\ExtarFields;
use LaxCorp\ProfileAdminBundle\Model\TariffRequest;
use Sonata\AdminBundle\Action\DashboardAction;
use Sonata\AdminBundle\Admin\Pool;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

/**
 * @inheritdoc
 */
abstract class AbstractProfileController extends AbstractController
{

    /**
     * @var Pool
     */
    protected $pool;


    /**
     * @var DashboardAction
     */
    protected $dashboardAction;

    /**
     * @var ClientHelper
     */
    protected $clientHelper;

    /**
     * @var ProfileHelper
     */
    protected $profileHelper;

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    /**
     * @var CustomerTariffHelper
     */
    protected $customerTariffHelper;

    /**
     * @var AppFlagsInterface
     */
    protected $appFlags;

    /**
     * @var Environment
     */
    protected $templating;

    /**
     * @var BillingNotificationHelper
     */
    protected $billingNotificationHelper;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var CatalogCustomerHelper
     */
    protected $catalogCustomerHelper;

    /**
     * @var iterable
     */
    protected $blocks;

    /**
     * @inheritdoc
     */
    public function __construct(
        Pool $pool,
        DashboardAction $dashboardAction,
        ClientHelper $clientHelper,
        ProfileHelper $profileHelper,
        CustomerHelper $customerHelper,
        AppFlagsInterface $appFlags,
        Environment $templating,
        CustomerTariffHelper $customerTariffHelper,
        BillingNotificationHelper $billingNotificationHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        CatalogCustomerHelper $catalogCustomerHelper,
        iterable $blocks
    ) {
        $this->pool                      = $pool;
        $this->dashboardAction           = $dashboardAction;
        $this->clientHelper              = $clientHelper;
        $this->profileHelper             = $profileHelper;
        $this->customerHelper            = $customerHelper;
        $this->appFlags                  = $appFlags;
        $this->templating                = $templating;
        $this->customerTariffHelper      = $customerTariffHelper;
        $this->billingNotificationHelper = $billingNotificationHelper;
        $this->authorizationChecker      = $authorizationChecker;
        $this->catalogCustomerHelper     = $catalogCustomerHelper;
        $this->blocks                    = $blocks;
    }

    /**
     * @inheritdoc
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @inheritdoc
     */
    protected function getClientById(int $clientId)
    {
        if (!$client = $this->clientHelper->getClientById($clientId)) {
            throw new ClientNotFoundException();
        }

        return $client;
    }

    /**
     * @inheritdoc
     */
    protected function getProfile(int $profileId, Client $client)
    {
        if (!$profile = $this->profileHelper->getProfile($profileId, $client)) {
            throw new ProfileNotFoundException();
        }

        return $profile;
    }

    /**
     * @inheritdoc
     */
    protected function getProfileByAdminRequest(TariffRequest $adminRequest)
    {
        $client = $this->getClientById($adminRequest->getClientId());

        return $this->getProfile($adminRequest->getProfileId(), $client);
    }

    /**
     * @inheritdoc
     */
    protected function getCustomerByAdminRequest(TariffRequest $adminRequest)
    {
        $profile = $this->getProfileByAdminRequest($adminRequest);

        return $profile->getCustomer();
    }

    /**
     * @return array
     */
    protected function getBaseParameters()
    {
        return [
            'base_template' => $this->pool->getTemplate('layout'),
            'admin_pool'    => $this->pool,
            'blocks'        => $this->blocks,
            'isZenith'      => $this->appFlags->isZenith()
        ];
    }

    /**
     * @inheritdoc
     */
    public function getNewCount(Client $client)
    {
        $profilesCount = 0;

        $repository = $this->getDoctrine()->getRepository('App:Profiles');

        $profiles = $repository->findBy(['client' => $client, 'deleted' => false]);

        if ($profiles) {
            $profilesCount = count($profiles);
        }

        return $profilesCount + 1;
    }

    /**
     * @inheritdoc
     * @throws
     */
    public function generateName(Client $client, bool $for1c)
    {
        $parameters = [
            'name'  => ($for1c) ? $this->profileHelper->getDefaultName1c() : $this->profileHelper->getDefaultName(),
            'count' => $this->getNewCount($client)
        ];

        return $this->templating->render('@ProfileAdmin/profile/name.html.twig', $parameters);
    }

    /**
     * @inheritdoc
     */
    public function clearExtraFields(FormView &$formView)
    {
        $extraFields = new ExtarFields();

        foreach ($extraFields as $key => $val) {
            unset($formView->children[$key]);
        }
    }

    /**
     * @inheritdoc
     */
    public function getCustomerTariffsByForm(FormInterface $form)
    {
        $customerTariffs = [];

        $post = $form->all();

        $extraFields = new ExtarFields();
        $data        = clone $extraFields;

        foreach ($extraFields as $key => $val) {
            $data->{$key} = $post[$key]->getData();
        }

        if (!$data->tariffs) {
            return $customerTariffs;
        }

        foreach ($data->tariffs as $key => $tariffId) {
            $customerTariff = $this->customerTariffHelper->getDefaultByTemplateTariffId($tariffId);
            $customerTariff->setAutoRenewal($data->autoRenewals[$key]);
            $customerTariff->setMultiplier($data->multipliers[$key]);
            $customerTariffs[] = $customerTariff;
        }

        return $customerTariffs;
    }


}