<?php declare(strict_types=1);

namespace BetterPayment\PaymentMethod;

abstract class PaymentMethod
{
    protected string $id;
    protected string $handler;
    protected string $name;
    protected string $shortname;
    protected string $technicalName;
    protected string $description;
    protected string $icon;
    protected array $translations;


    public function getId(): string
    {
        return $this->id;
    }

    public function getTechnicalName(): string
    {
        return 'betterpayment_' . $this->shortname;
    }

    public function getHandler(): string
    {
        return $this->handler;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getShortname(): string
    {
        return $this->shortname;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }
}