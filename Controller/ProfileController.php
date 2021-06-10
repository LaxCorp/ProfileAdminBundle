<?php

namespace LaxCorp\ProfileAdminBundle\Controller;

use App\Entity\Client;
use App\Entity\Profiles;
use LaxCorp\ProfileAdminBundle\Exception\ClientNotFoundException;
use LaxCorp\ProfileAdminBundle\Form\CreateProfileType;
use LaxCorp\ProfileAdminBundle\Form\EditProfileType;
use LaxCorp\ProfileAdminBundle\Model\ActionRoles;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @inheritdoc
 */
class ProfileController extends AbstractProfileController
{

    /**
     * @inheritdoc
     *
     * @Route(
     *     "/client/{clientId}/account/{accountId}/profile/create",
     *     requirements={ "clientId": "\d+", "accountId": "\d+" },
     *     name="profile_admin__create_profile"
     * )
     * @Route(
     *     "/client/{clientId}/account/{accountId}/profile/create/{for1c}",
     *      requirements={ "clientId": "\d+", "accountId": "\d+", "for1c": "for1c" },
     *      name="profile_admin__create_profile_for1c"
     * )
     */
    public function createProfile(Request $request, int $clientId, int $accountId, ?string $for1c)
    {
        $for1c = boolval($for1c);

        $isZenith = $this->appFlags->isZenith();

        $parameters = $this->getBaseParameters();

        $parameters['account_id'] = $accountId;

        if (!$this->authorizationChecker->isGranted(ActionRoles::createRoles())) {
            return $this->render('@ProfileAdmin/Expection/access_denied.html.twig', $parameters);
        }

        $catalogDisabled = (!$this->profileHelper->isCatalogHostingEnabled() || $isZenith || $for1c) ? true : false;

        $parameters['catalogDisabled'] = $catalogDisabled;

        try {
            $client  = $this->getClientById($clientId);
            $remoteAccount = $this->clientHelper->getClientRemoteAccount($client, $accountId);
            $account = $this->clientHelper->getAccountById($accountId);
            $email = $client->getUser()->getEmail();
        } catch (ClientNotFoundException $exception) {
            return $this->render('@ProfileAdmin/Expection/client_not_found.html.twig', $parameters);
        }

        $parameters['client'] = $client;

        $customer = $this->customerHelper->getDefault();
        $customer->setEmail($email);
        $customer->setAccount($account);
        $customer->setCustomerTariffs([]); // по началу пустой

        $profile = new Profiles();
        $profile->setRemoteAccount($remoteAccount);
        $profile->setClient($client);
        $profile->setCustomer($customer);
        $profile->setFor1C($for1c);

        if (!$catalogDisabled && !$for1c) {
            $profile->setFqdn($this->profileHelper->generateFqdn());
        }

        $parameters['profile'] = $profile;

        $form = $this->createForm(CreateProfileType::class, $profile);
        $form->handleRequest($request);
        $formView = $form->createView();
        $this->clearExtraFields($formView);

        $parameters['form'] = $formView;

        $customerTariffs = $this->getCustomerTariffsByForm($form);

        $parameters['tariffs'] = $customerTariffs;

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->all();
            $jobs = (isset($post['jobs'])) ? $post['jobs']->getData() : 1;

            for ($i = 0; $i < $jobs; $i++) {

                $newProfile  = clone $profile;
                $newCustomer = clone $customer;

                $newCustomer->setPrognosePeriod($this->billingNotificationHelper->getClientPrognosePeriod($client));
                $customerName = $this->templating->render(
                    'customer_profile/default_customer_name.txt.twig', [
                    'for1c'      => $for1c,
                    'domainName' => $newProfile->getDomainName()
                ]);

                $newCustomer->setName($customerName);
                $newCustomer->setFromDate(new \DateTime());
                $newCustomer->setToDate($post['toDate']->getData());
                $newCustomer->setState($post['state']->getData());
                $newCustomer->setPassword($this->profileHelper->generatePassword());
                $newCustomer = $this->customerHelper->createCustomer($newCustomer);

                foreach ($customerTariffs as $customerTariff) {
                    $newCustomer = $this->customerTariffHelper->createCustomerTariff($newCustomer, $customerTariff);
                }

                if (!$newProfile->getName()) {
                    $newProfile->setName($this->generateName($client, $for1c));
                }

                if ($newProfile->getHostingType() === 1) {
                    $newProfile->setFqdn(null);
                    $newProfile->setDomainType(0);

                }

                $newProfile->setCustomerId($newCustomer->getId());

                $client = $this->clientHelper->addProfile($client, $newProfile);

                if (!$catalogDisabled && !$for1c && $client && $newProfile->getFqdn()) {
                    $this->profileHelper->catalogHostingUpdate($newProfile);
                }

                $flashMessage = $this->templating->render(
                    '@ProfileAdmin/profile/message_success_create.html.twig', [
                    'isZenith' => $isZenith,
                    'name'     => $newProfile->getName()
                ]);

                $this->addFlash('sonata_flash_info', $flashMessage);
            }

            return $this->redirectToRoute('profile_admin__profile_list', ['clientId' => $client->getId()]);
        }

        return $this->render('@ProfileAdmin/profile/create.html.twig', $parameters);
    }

    /**
     * @inheritdoc
     *
     * @Route(
     *     "/client/{clientId}/profile/{profileId}/{action}",
     *      requirements={ "clientId": "\d+" , "profileId": "\d+", "action" : "edit|show|enable|disable|delete" },
     *      name="profile_admin__profile_action"
     * )
     */
    public function profileAction(Request $request, int $clientId, int $profileId, string $action)
    {

        $parameters = $this->getBaseParameters();

        $parameters['action'] = $action;

        $isZenith = $this->appFlags->isZenith();

        if (!$this->authorizationChecker->isGranted(ActionRoles::byAction($action))) {
            return $this->render('@ProfileAdmin/Expection/access_denied.html.twig', $parameters);
        }

        $parameters['isGrantedEdit'] = $this->authorizationChecker->isGranted(ActionRoles::editRoles());

        try {
            $client  = $this->getClientById($clientId);
        } catch (ClientNotFoundException $exception) {
            return $this->render('@ProfileAdmin/Expection/client_not_found.html.twig', $parameters);
        }

        $parameters['client'] = $client;

        $profile = $this->profileHelper->getProfile($profileId, $client);

        if (!$profile) {
            return $this->render('@ProfileAdmin/Expection/profile_not_found.html.twig', $parameters);
        }

        if ($action === 'enable' || $action === 'disable') {

            $customer = ($action === 'enable')
                ? $this->customerHelper->enableCustomer($profile->getCustomer())
                : $this->customerHelper->disableCustomer($profile->getCustomer());

            $parameters['profile'] = $profile->setCustomer($customer);

            return $this->render('@ProfileAdmin/profile/card_content.html.twig', $parameters);
        }

        if ($action === 'delete') {
            $this->profileHelper->deleteProfile($profileId, $client);

            return $this->render('@ProfileAdmin/profile/message_success_delete.html.twig', [
                'isZenith' => $isZenith,
                'name'     => $profile->getName()
            ]);
        }

        $realCustomer = clone $profile->getCustomer();

        $for1c = $profile->getFor1C();

        $catalogDisabled = (!$this->profileHelper->isCatalogHostingEnabled() || $isZenith || $for1c) ? true : false;

        $parameters['catalogDisabled'] = $catalogDisabled;

        $currentCatalogDomain = null;

        if (!$catalogDisabled) {
            $catalogCustomer      = $this->catalogCustomerHelper->getCustomer($profile->getCustomer()->getId());
            $currentCatalogDomain = $catalogCustomer->getServerName();
        }

        $parameters['currentCatalogDomain'] = $currentCatalogDomain;

        if (!$catalogDisabled && !$for1c && !$profile->getFqdn()) {
            $profile->setFqdn($this->profileHelper->generateFqdn());
        }

        $form = $this->createForm(EditProfileType::class, $profile);
        $form->handleRequest($request);
        $formView = $form->createView();
        $this->clearExtraFields($formView);

        if ($form->isSubmitted() && $form->isValid()) {
            $customer = $profile->getCustomer();

            if (serialize($realCustomer) !== serialize($customer)) {
                $customer = $this->customerHelper->updateCustomer($customer);
                $profile->setCustomer($customer);
            }

            if ($profile->getHostingType() === 1) {
                $profile->setFqdn(null);
                $profile->setDomainType(0);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($profile);
            $em->flush();

            return $this->redirectToRoute('profile_admin__profile_action', [
                'clientId'  => $clientId,
                'profileId' => $profileId,
                'action'    => 'show'
            ]);
        }

        $parameters['profile'] = $profile;
        $parameters['form']    = $formView;

        return $this->render('@ProfileAdmin/profile/action.html.twig', $parameters);
    }

    /**
     * @inheritdoc
     *
     * @Route(
     *     "/client/{clientId}/profile/list",
     *      requirements={ "clientId": "\d+" },
     *      name="profile_admin__profile_list"
     * )
     */
    public function profileList(int $clientId)
    {
        $parameters = $this->getBaseParameters();

        if (!$this->authorizationChecker->isGranted(ActionRoles::listRoles())) {
            return $this->render('@ProfileAdmin/Expection/access_denied.html.twig', $parameters);
        }

        try {
            $client  = $this->getClientById($clientId);
            // todo переделать
            //$account = $this->clientHelper->getClientAccount($client);
            //$client->setAccount($account);
        } catch (ClientNotFoundException $exception) {
            return $this->render('@ProfileAdmin/Expection/client_not_found.html.twig', $parameters);
        }

        $parameters['isGrantedEdit'] = $this->authorizationChecker->isGranted(ActionRoles::editRoles());

        $parameters['client'] = $client;
        $parameters['client_accounts'] = $this->clientHelper->getClientAccounts($client);

        $accountProfiles = $this->profileHelper->getAccountsProfiles($client);

        $parameters['account_profiles'] = $accountProfiles;

        return $this->render('@ProfileAdmin/profile/tab_list.html.twig', $parameters);
    }

    /**
     * @inheritdoc
     */
    public function shortListAction(Client $client)
    {
        $parameters = $this->getBaseParameters();

        $parameters['isGrantedEdit'] = $this->authorizationChecker->isGranted(ActionRoles::editRoles());

        $parameters['client'] = $client;

        $profiles = $this->profileHelper->getAccountsProfiles($client);

        $parameters['account_profiles'] = $profiles;

        return $this->render('@ProfileAdmin/profile/short_list.html.twig', $parameters);
    }

}
