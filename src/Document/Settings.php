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
    #[MongoDB\Field(type: 'int')]
    protected int $dias_mes = 30;
    #[MongoDB\Field(type: 'float')]
    protected float $monto_usd_dia = 0.0;

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

    public function getDiasMes(): int
    {
        return $this->dias_mes > 0 ? $this->dias_mes : 30;
    }

    public function setDiasMes(?int $dias_mes): void
    {
        $this->dias_mes = ($dias_mes !== null && $dias_mes > 0) ? $dias_mes : 30;
    }

    public function getMontoUsdDia(): float
    {
        return $this->monto_usd_dia;
    }

    public function setMontoUsdDia(?float $monto_usd_dia): void
    {
        $this->monto_usd_dia = $monto_usd_dia ?? 0.0;
    }
}
