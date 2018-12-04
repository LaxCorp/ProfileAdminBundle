<?php

namespace LaxCorp\ProfileAdminBundle\Model;

/**
 * @inheritdoc
 */
class TariffRequest {

    /**
     * @var int|null
     */
    private $clientId;

    /**
     * @var int|null
     */
    private $profileId;

    /**
     * @var int|null
     */
    private $tariffId;

    /**
     * @var string|null
     */
    private $resultContainer;

    /**
     * @var int|null
     */
    private $replaceTariffId;

    /**
     * @var bool|null
     */
    private $autoRenewal;

    /**
     * @var int|null
     */
    private $jobs;

    /**
     * @var bool|null
     */
    private $for1c;

    /**
     * @return int|null
     */
    public function getClientId(): ?int
    {
        return $this->clientId;
    }

    /**
     * @param int|null $clientId
     *
     * @return TariffRequest
     */
    public function setClientId(?int $clientId): TariffRequest
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getProfileId(): ?int
    {
        return $this->profileId;
    }

    /**
     * @param int|null $profileId
     *
     * @return TariffRequest
     */
    public function setProfileId(?int $profileId): TariffRequest
    {
        $this->profileId = $profileId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getTariffId(): ?int
    {
        return $this->tariffId;
    }

    /**
     * @param int|null $tariffId
     *
     * @return TariffRequest
     */
    public function setTariffId(?int $tariffId): TariffRequest
    {
        $this->tariffId = $tariffId;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getResultContainer(): ?string
    {
        return $this->resultContainer;
    }

    /**
     * @param null|string $resultContainer
     *
     * @return TariffRequest
     */
    public function setResultContainer(?string $resultContainer): TariffRequest
    {
        $this->resultContainer = $resultContainer;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getReplaceTariffId(): ?int
    {
        return $this->replaceTariffId;
    }

    /**
     * @param int|null $replaceTariffId
     *
     * @return TariffRequest
     */
    public function setReplaceTariffId(?int $replaceTariffId): TariffRequest
    {
        $this->replaceTariffId = $replaceTariffId;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getAutoRenewal(): ?bool
    {
        return $this->autoRenewal;
    }

    /**
     * @param bool|null $autoRenewal
     *
     * @return TariffRequest
     */
    public function setAutoRenewal(?bool $autoRenewal): TariffRequest
    {
        $this->autoRenewal = $autoRenewal;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getJobs(): ?int
    {
        return $this->jobs;
    }

    /**
     * @param int|null $jobs
     *
     * @return TariffRequest
     */
    public function setJobs(?int $jobs): TariffRequest
    {
        $this->jobs = $jobs;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isFor1c(): ?bool
    {
        return ($this->for1c);
    }

    /**
     * @param bool|null $for1c
     *
     * @return TariffRequest
     */
    public function setFor1c(?bool $for1c): TariffRequest
    {
        $this->for1c = ($for1c);

        return $this;
    }

}