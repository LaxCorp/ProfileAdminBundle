<?php

namespace LaxCorp\ProfileAdminBundle\Controller;

use App\Entity\Profiles;
use App\Helper\AppFlagsInterface;
use App\Helper\ClientHelper;
use App\Helper\ProfileHelper;
use App\Helper\TariffHelper;
use LaxCorp\BillingPartnerBundle\Helper\CustomerTariffHelper;
use LaxCorp\BillingPartnerBundle\Helper\MappingHelper;
use LaxCorp\BillingPartnerBundle\Helper\ReplaceTariffHelper;
use LaxCorp\BillingPartnerBundle\Helper\TemplateTariffHelper;
use LaxCorp\BillingPartnerBundle\Model\Customer;
use LaxCorp\BillingPartnerBundle\Model\CustomerTariff;
use LaxCorp\BillingPartnerBundle\Model\ServiceTariffs;
use LaxCorp\BillingPartnerBundle\Model\TemplateTariff;
use LaxCorp\ProfileAdminBundle\Exception\ClientNotFoundException;
use LaxCorp\ProfileAdminBundle\Exception\ProfileNotFoundException;
use LaxCorp\ProfileAdminBundle\Form\TariffRequestType;
use LaxCorp\ProfileAdminBundle\Model\ActionRoles;
use LaxCorp\ProfileAdminBundle\Model\TariffRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @inheritdoc
 */
abstract class AbstractTariffController extends AbstractController
{

    /**
     * @var ClientHelper
     */
    protected $clientHelper;

    /**
     * @var CustomerTariffHelper
     */
    protected $customerTariffHelper;

    /**
     * @var ProfileHelper
     */
    protected $profileHelper;

    /**
     * @var TemplateTariffHelper
     */
    protected $templateTariffHelper;

    /**
     * @var TariffHelper
     */
    protected $tariffHelper;

    /**
     * @var ReplaceTariffHelper
     */
    protected $replaceTariffHelper;

    /**
     * @var MappingHelper
     */
    public $mappingHelper;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var AppFlagsInterface
     */
    public $appFlags;

    /**
     * @inheritdoc
     */
    public function __construct(
        ClientHelper $clientHelper,
        CustomerTariffHelper $customerTariffHelper,
        ProfileHelper $profileHelper,
        TemplateTariffHelper $templateTariffHelper,
        TariffHelper $tariffHelper,
        ReplaceTariffHelper $replaceTariffHelper,
        MappingHelper $mappingHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        AppFlagsInterface $appFlags
    ) {
        $this->clientHelper         = $clientHelper;
        $this->customerTariffHelper = $customerTariffHelper;
        $this->profileHelper        = $profileHelper;
        $this->templateTariffHelper = $templateTariffHelper;
        $this->tariffHelper         = $tariffHelper;
        $this->replaceTariffHelper  = $replaceTariffHelper;
        $this->mappingHelper        = $mappingHelper;
        $this->authorizationChecker = $authorizationChecker;
        $this->appFlags             = $appFlags;
    }

    /**
     * @inheritdoc
     */
    protected function getAdminRequest(Request $request)
    {

        $adminRequest = new TariffRequest();

        $form = $this->createForm(TariffRequestType::class, $adminRequest);
        $form->handleRequest($request);

        return $adminRequest;
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
    protected function getProfile(int $clientId, int $profileId)
    {
        $client = $this->getClientById($clientId);

        if (!$profile = $this->profileHelper->getProfile($profileId, $client)) {
            throw new ProfileNotFoundException();
        }

        return $profile;
    }

    /**
     * @inheritdoc
     */
    protected function getCustomer(int $clientId, int $profileId)
    {
        $profile = $this->getProfile($clientId, $profileId);

        return $profile->getCustomer();
    }

    /**
     * @inheritdoc
     */
    protected function tariffCard(int $clientId, int $profileId, int $tariffId)
    {

        $profile  = $this->getProfile($clientId, $profileId);
        $customer = $profile->getCustomer();
        $tariff   = $this->customerTariffHelper->getCustomerTariff($customer, $tariffId);

        return $this->render('@ProfileAdmin/tariff/card.html.twig', [
            'profile'       => $profile,
            'customer'      => $customer,
            'tariff'        => $tariff,
            'isGrandedEdit' => $this->authorizationChecker->isGranted(ActionRoles::editRoles()),
            'isZenith'      => $this->appFlags->isZenith()
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function getServiceCodes($serviceTariffs)
    {
        $codes = [];

        /** @var ServiceTariffs $serviceTariff */
        foreach ($serviceTariffs as $serviceTariff) {
            $service      = $serviceTariff->getService();
            $code         = $service->getCode();
            $codes[$code] = true;
        }

        return $codes;
    }

    /**
     * Change multiplier emulation
     *
     * @inheritdoc
     */
    protected function multiplierEmulation(TemplateTariff $templateTariff, int $multiplier)
    {

        // этот изврат нужен чтоб превратить TemplateTariff в CustomerTariff
        $json = $this->mappingHelper->serialize($templateTariff);
        /** @var CustomerTariff $customerTariff */
        $customerTariff = $this->mappingHelper->deserialize($json, CustomerTariff::class);

        unset($json, $templateTariff);

        $customerTariff->setMultiplier($multiplier);

        $serviceTariffs = $customerTariff->getServiceTariffs();

        foreach ($serviceTariffs as $serviceTariff) {
            $serviceTariff->setClicksCount($serviceTariff->getClicksCount());
        }

        return $customerTariff;
    }

    /**
     * @inheritdoc
     */
    protected function getTempalateTariffParameters(Request $request, int $clientId, int $tariffId)
    {
        $parameters = [];

        $adminRequest = $this->getAdminRequest($request);
        $client       = $this->getClientById($clientId);
        $account      = $this->clientHelper->getClientAccount($client);
        $profile1c    = $adminRequest->isFor1c();

        $customer = new Customer();
        $customer->setAccount($account);

        $parameters['customer'] = $customer;

        $jobs = (int)$adminRequest->getJobs();
        $jobs = (!$jobs) ? 1 : abs($jobs);

        $templateTariff = $this->templateTariffHelper->getTemplateTariff($tariffId);

        $customerTariff = $this->multiplierEmulation($templateTariff, $jobs);
        $customerTariff->setAutoRenewal($adminRequest->getAutoRenewal());
        $customerTariff->setState('DISABLED');

        $parameters['tariff'] = $customerTariff;

        $profile = new Profiles();
        $profile->setFor1C($profile1c);
        $profile->setClient($client);
        $profile->setCustomer($customer);

        $parameters['profile']  = $profile;
        $parameters['isZenith'] = $this->appFlags->isZenith();

        return $parameters;
    }

}