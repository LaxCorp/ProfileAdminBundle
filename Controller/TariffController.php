<?php

namespace LaxCorp\ProfileAdminBundle\Controller;

use App\Entity\Profiles;
use LaxCorp\BillingPartnerBundle\Model\Customer;
use LaxCorp\ProfileAdminBundle\Model\ActionRoles;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * @inheritdoc
 * @Security("has_role('ROLE_CLIENT_PROFILE_ADMIN') or has_role('ROLE_SUPER_ADMIN')")
 */
class TariffController extends AbstractTariffController
{

    /**
     * @inheritdoc
     * @Method({"POST"})
     * @Route(
     *     "/client/{clientId}/profile/{profileId}/tariff/{tariffId}/change/undo",
     *     requirements={ "clientId": "\d+" , "profileId": "\d+", "tariffId": "\d+"},
     *     name="profile_admin__undo_change_tariff"
     * )
     */
    public function undoChangeTariff(int $clientId, int $profileId, int $tariffId)
    {
        $customer     = $this->getCustomer($clientId, $profileId);
        $tariff       = $this->customerTariffHelper->getCustomerTariff($customer, $tariffId);
        $replaceChild = $tariff->getReplaceChild();

        if ($replaceChild) {
            $this->customerTariffHelper->deleteCustomerTariff($customer, $replaceChild);
        }

        return $this->tariffCard($clientId, $profileId, $tariffId);
    }

    /**
     * @inheritdoc
     * @Method({"POST"})
     * @Route(
     *     "/client/{clientId}/profile/{profileId}/tariff/{tariffId}/auto_renewal/{action}",
     *     requirements={ "clientId": "\d+" , "profileId": "\d+", "tariffId": "\d+", "action": "on|off"},
     *     name="profile_admin__auto_renewal_on"
     * )
     */
    public function autoRenewal(int $clientId, int $profileId, int $tariffId, string $action)
    {
        $customer       = $this->getCustomer($clientId, $profileId);
        $customerTariff = $this->customerTariffHelper->getCustomerTariff($customer, $tariffId);
        $replaceChild   = $customerTariff->getReplaceChild();
        $tariff         = ($replaceChild) ? $replaceChild : $customerTariff;
        $state          = ['on' => true, 'off' => false];

        $tariff->setAutoRenewal($state[$action]);

        $this->customerTariffHelper->updateCustomerTariff($customer, $tariff);

        return $this->tariffCard($clientId, $profileId, $tariffId);
    }

    /**
     * @inheritdoc
     * @Method({"POST"})
     * @Route(
     *     "/client/{clientId}/profile/{profileId}/tariff/{tariffId}/delete",
     *     requirements={ "clientId": "\d+" , "profileId": "\d+", "tariffId": "\d+"},
     *     name="profile_admin__delete_tariff"
     * )
     */
    public function deleteTariff(int $clientId, int $profileId, int $tariffId)
    {
        $customer       = $this->getCustomer($clientId, $profileId);
        $customerTariff = $this->customerTariffHelper->getCustomerTariff($customer, $tariffId);

        $this->customerTariffHelper->deleteCustomerTariff($customer, $customerTariff);

        return $this->render('@ProfileAdmin/tariff/deleted.html.twig', []);
    }

    /**
     * @inheritdoc
     * @Method({"POST"})
     * @Route(
     *     "/client/{clientId}/profile/{profileId}/tariff/{tariffId}/change/choose",
     *     requirements={ "clientId": "\d+" , "profileId": "\d+", "tariffId": "\d+"},
     *     name="profile_admin__change_tariff_choose"
     * )
     */
    public function changeTariffChoose(int $clientId, int $profileId, int $tariffId)
    {
        $profile = $this->getProfile($clientId, $profileId);

        $customer        = $profile->getCustomer();
        $account         = $customer->getAccount();
        $currency        = $account->getCurrency();
        $customerTariff  = $this->customerTariffHelper->getCustomerTariff($customer, $tariffId);
        $templateTariffs = $this->templateTariffHelper->getTemplateTariffsByCurrencyCode($currency->getCode());
        $profile1c       = $this->profileHelper->detect1c($customer);
        $jobs            = $customerTariff->getMultiplier();

        $chooseTariffs = [];

        foreach ($templateTariffs as $templateTariff) {
            $tariffOptions = $this->tariffHelper->getTariffOptions($templateTariff->getId());

            if (!$tariffOptions && $profile1c) {
                continue;
            }

            if ($tariffOptions && $profile1c && !$tariffOptions->getTariff1c()) {
                continue;
            }

            if ($tariffOptions && !$profile1c && $tariffOptions->getTariff1c()) {
                continue;
            }

            $chooseTariff = clone $customerTariff;
            $chooseTariff->setReplaceChild($this->multiplierEmulation($templateTariff, $jobs));

            $chooseTariffs[] = $chooseTariff;
        }

        return $this->render('@ProfileAdmin/tariff/change_tariff_choose.html.twig', [
            'profile'  => $profile,
            'customer' => $customer,
            'tariffs'  => $chooseTariffs,
            'isZenith' => $this->appFlags->isZenith()
        ]);
    }

    /**
     * @inheritdoc
     * @Method({"POST"})
     * @Route(
     *     "/client/{clientId}/profile/{profileId}/tariff/{tariffId}/change/preview/{replaceTariffId}",
     *     requirements={ "clientId": "\d+" , "profileId": "\d+", "tariffId": "\d+", "replaceTariffId" : "\d+" },
     *     name="profile_admin__change_tariff_preview"
     * )
     */
    public function changeTariffPreview(
        Request $request, int $clientId, int $profileId, int $tariffId, int $replaceTariffId
    ) {
        $adminRequest = $this->getAdminRequest($request);
        $profile      = $this->getProfile($clientId, $profileId);
        $customer     = $profile->getCustomer();
        $tariff       = $this->customerTariffHelper->getCustomerTariff($customer, $tariffId);

        $jobs = (int)$adminRequest->getJobs();
        $jobs = (!$jobs) ? 1 : abs($jobs);

        $templateTariff = $this->templateTariffHelper->getTemplateTariff($replaceTariffId);

        $tariff->setReplaceChild($this->multiplierEmulation($templateTariff, $jobs));

        return $this->render('@ProfileAdmin/tariff/change_tariff_preview.html.twig', [
            'tariff'   => $tariff,
            'customer' => $customer,
            'profile'  => $profile,
            'isZenith' => $this->appFlags->isZenith()
        ]);
    }

    /**
     * @inheritdoc
     * @Method({"POST"})
     * @Route(
     *     "/client/{clientId}/profile/{profileId}/tariff/{tariffId}/change/{replaceTariffId}",
     *     requirements={ "clientId": "\d+" , "profileId": "\d+", "tariffId": "\d+", "replaceTariffId" : "\d+" },
     *     name="profile_admin__change_tariff"
     * )
     */
    public function changeTariff(Request $request, int $clientId, int $profileId, int $tariffId, int $replaceTariffId)
    {
        $adminRequest = $this->getAdminRequest($request);
        $profile      = $this->getProfile($clientId, $profileId);
        $customer     = $profile->getCustomer();
        $profile1c    = $this->profileHelper->detect1c($customer);

        $jobs = (int)$adminRequest->getJobs();
        $jobs = (!$jobs) ? 1 : abs($jobs);

        if ($profile1c) {
            $this->replaceTariffHelper->replace(
                $customer,
                $tariffId,
                $replaceTariffId,
                $adminRequest->getAutoRenewal(),
                $jobs
            );
        } else {
            $this->replaceTariffHelper->replace(
                $customer,
                $tariffId,
                $replaceTariffId,
                $adminRequest->getAutoRenewal()
            );
        }

        return $this->tariffCard($clientId, $profileId, $tariffId);
    }

    /**
     * @inheritdoc
     * @Method({"POST"})
     * @Route(
     *     "/client/{clientId}/profile/{profileId}/add/tariff/choose",
     *     requirements={ "clientId": "\d+" , "profileId": "\d+"},
     *     name="profile_admin__add_tariff_choose"
     * )
     */
    public function addTariffChoose(int $clientId, int $profileId)
    {
        $profile = $this->getProfile($clientId, $profileId);

        $customer        = $profile->getCustomer();
        $account         = $customer->getAccount();
        $currency        = $account->getCurrency();
        $templateTariffs = $this->templateTariffHelper->getTemplateTariffsByCurrencyCode($currency->getCode());
        $profile1c       = $profile->getFor1C();
        $jobs            = 1;

        $chooseTariffs = [];

        foreach ($templateTariffs as $templateTariff) {
            $tariffOptions = $this->tariffHelper->getTariffOptions($templateTariff->getId());

            if (!$tariffOptions && $profile1c) {
                continue;
            }

            if ($tariffOptions && $profile1c && !$tariffOptions->getTariff1c()) {
                continue;
            }

            if ($tariffOptions && !$profile1c && $tariffOptions->getTariff1c()) {
                continue;
            }

            $chooseTariffs[] = $this->multiplierEmulation($templateTariff, $jobs);
        }

        return $this->render('@ProfileAdmin/tariff/add_tariff_choose.html.twig', [
            'profile'  => $profile,
            'customer' => $customer,
            'tariffs'  => $chooseTariffs,
            'isZenith' => $this->appFlags->isZenith()
        ]);
    }

    /**
     * @inheritdoc
     * @Method({"POST"})
     * @Route(
     *     "/client/{clientId}/profile/{profileId}/add/tariff/preview/{tariffId}",
     *     requirements={ "clientId": "\d+" , "profileId": "\d+", "tariffId": "\d+" },
     *     name="profile_admin__add_tariff_preview"
     * )
     */
    public function addTariffPreview(Request $request, int $clientId, int $profileId, int $tariffId)
    {
        $adminRequest = $this->getAdminRequest($request);
        $profile      = $this->getProfile($clientId, $profileId);
        $customer     = $profile->getCustomer();

        $jobs = (int)$adminRequest->getJobs();
        $jobs = (!$jobs) ? 1 : abs($jobs);

        $templateTariff = $this->templateTariffHelper->getTemplateTariff($tariffId);

        $tariff = $this->multiplierEmulation($templateTariff, $jobs);

        return $this->render('@ProfileAdmin/tariff/add_tariff_preview.html.twig', [
            'tariff'   => $tariff,
            'customer' => $customer,
            'profile'  => $profile,
            'isZenith' => $this->appFlags->isZenith()
        ]);
    }

    /**
     * @inheritdoc
     * @Method({"POST"})
     * @Route(
     *     "/client/{clientId}/profile/{profileId}/add/tariff/{tariffId}",
     *     requirements={ "clientId": "\d+" , "profileId": "\d+", "tariffId": "\d+" },
     *     name="profile_admin__add_tariff"
     * )
     */
    public function addTariff(Request $request, int $clientId, int $profileId, int $tariffId)
    {
        $adminRequest = $this->getAdminRequest($request);
        $profile      = $this->getProfile($clientId, $profileId);
        $customer     = $profile->getCustomer();
        $profile1c    = $profile->getFor1C();

        $jobs = (int)$adminRequest->getJobs();
        $jobs = (!$jobs) ? 1 : abs($jobs);

        $customerTariff = $this->customerTariffHelper->getDefaultByTemplateTariffId($tariffId);
        $customerTariff->setAutoRenewal($adminRequest->getAutoRenewal());

        if ($profile1c) {
            $customerTariff->setMultiplier($jobs);
        }

        $this->customerTariffHelper->createCustomerTariff($customer, $customerTariff);

        $profile = $this->getProfile($clientId, $profileId);

        return $this->render('@ProfileAdmin/tariff/added.html.twig', [
            'profile'  => $profile,
            'isGrantedEdit' => $this->authorizationChecker->isGranted(ActionRoles::editRoles()),
            'isZenith' => $this->appFlags->isZenith(),
        ]);
    }

    /**
     * @inheritdoc
     * @Method({"POST"})
     * @Route(
     *     "/client/{clientId}/account/{accountId}/profile/create/template/tariff_choose",
     *     requirements={ "clientId": "\d+", "accountId": "\d+" },
     *     name="profile_admin__create_tariff_choose"
     * )
     */
    public function templateTariffChoose(Request $request, int $clientId, int $accountId)
    {
        $adminRequest    = $this->getAdminRequest($request);
        $client          = $this->getClientById($clientId);
        $account         = $this->clientHelper->getAccountById($accountId);
        $remoteAccount   = $this->clientHelper->getClientRemoteAccount($client, $accountId);
        $currency        = $account->getCurrency();
        $templateTariffs = $this->templateTariffHelper->getTemplateTariffsByCurrencyCode($currency->getCode());
        $profile1c       = $adminRequest->isFor1c();

        $customer = new Customer();
        $customer->setAccount($account);
        $customer->setCustomerTariffs([]);

        $profile = new Profiles();
        $profile->setRemoteAccount($remoteAccount);
        $profile->setFor1C($profile1c);
        $profile->setClient($client);
        $profile->setCustomer($customer);

        $jobs = 1;

        $chooseTariffs = [];

        foreach ($templateTariffs as $templateTariff) {
            $tariffOptions = $this->tariffHelper->getTariffOptions($templateTariff->getId());

            if (!$tariffOptions && $profile1c) {
                continue;
            }

            if ($tariffOptions && $profile1c && !$tariffOptions->getTariff1c()) {
                continue;
            }

            if ($tariffOptions && !$profile1c && $tariffOptions->getTariff1c()) {
                continue;
            }

            $chooseTariffs[] = $this->multiplierEmulation($templateTariff, $jobs);;
        }

        return $this->render('@ProfileAdmin/tariff/add_tariff_choose.html.twig', [
            'profile'  => $profile,
            'customer' => $customer,
            'tariffs'  => $chooseTariffs,
            'isZenith' => $this->appFlags->isZenith()
        ]);
    }

    /**
     * @inheritdoc
     * @Method({"POST"})
     * @Route(
     *     "/client/{clientId}/account/{accountId}/profile/create/template/tariff/preview/{tariffId}",
     *     requirements={ "clientId": "\d+" , "accountId": "\d+" , "tariffId": "\d+" },
     *     name="profile_admin__create_template_tariff_preview"
     * )
     */
    public function tempalateTariffPreview(Request $request, int $clientId, int $accountId, int $tariffId)
    {
        $parameters = $this->getTempalateTariffParameters($request, $clientId, $accountId, $tariffId);

        return $this->render('@ProfileAdmin/tariff/add_tariff_preview.html.twig', $parameters);
    }

    /**
     * @inheritdoc
     * @Method({"POST"})
     * @Route(
     *     "/client/{clientId}/account/{accountId}/profile/create/template/tariff/add/{tariffId}",
     *     requirements={ "clientId": "\d+", "accountId": "\d+" , "tariffId": "\d+" },
     *     name="profile_admin__create_template_tariff_add"
     * )
     */
    public function addTemplateTariff(Request $request, int $clientId, int $accountId, int $tariffId)
    {
        $parameters = $this->getTempalateTariffParameters($request, $clientId, $accountId, $tariffId);

        return $this->render('@ProfileAdmin/tariff/add_template_tariff.html.twig', $parameters);
    }

}
