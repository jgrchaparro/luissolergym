<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'settings')]
class Settings
{
    #[MongoDB\Id(strategy: 'auto')]
    protected ?string $id = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $email_soporte = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $whatsapp = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $instagram = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $facebook = null;
    #[MongoDB\Field(type: 'float')]
    protected float $monto_usd_mes = 0.0;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEmailSoporte(): ?string
    {
        return $this->email_soporte;
    }

    public function setEmailSoporte(?string $email_soporte): void
    {
        $this->email_soporte = $email_soporte;
    }

    public function getWhatsapp(): ?string
    {
        return $this->whatsapp;
    }

    public function setWhatsapp(?string $whatsapp): void
    {
        $this->whatsapp = $whatsapp;
    }

    public function getInstagram(): ?string
    {
        return $this->instagram;
    }

    public function setInstagram(?string $instagram): void
    {
        $this->instagram = $instagram;
    }

    public function getFacebook(): ?string
    {
        return $this->facebook;
    }

    public function setFacebook(?string $facebook): void
    {
        $this->facebook = $facebook;
    }

    public function getMontoUsdMes(): float
    {
        return $this->monto_usd_mes;
    }

    public function setMontoUsdMes(float $monto_usd_mes): void
    {
        $this->monto_usd_mes = $monto_usd_mes;
    }
}
