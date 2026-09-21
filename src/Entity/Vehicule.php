<?php

declare(strict_types=1);
namespace App\Entity;
use Symfony\Component\Validator\Constraints\Uuid;

class Vehicule
{
    public ?string $uuid;
    public ?string $title;
    public ?string $description;
    public ?string $amount;
    public ?string $currency;
    public ?string $fromtImage;
    public ?string $reference;
    public ?string $model;
    public ?string $mailTo;
    public ?string $fromClient;
    public ?string $emailAddress;
    public ?string $password;
    public ?string $otp;
    public ?string $kilometrage;
    public ?string $transmission;
    public ?string $year;
    public ?string $brand;
    public ?string $transactionReference;
    public ?string $paymentMethod;
    public \DateTime $paidAt;
    public bool $auth = true;
    public ?string $energie;

    public static function new(){
        $new = new self('','','','','','','','','','','','','','');
        $new->auth = false;
        $new->mailTo = "manitou.gailler872@gmail.com";
        return $new;
    }

    public function __construct(string $title, string $description, string $amount, 
    string $currency, string $frontImage, string $reference, string $model, string $year, string $brand,
    string $transactionReference, string $kilometrage, 
    string $transmission, ?string $energie, ?string $mailTo)
    {
        $this->paidAt = new \DateTime('now');
        $this->title = $title;
        $this->paymentMethod = 'Paiement sécurisée Leboncoin';
        $this->description = $description;
        $this->amount = $amount;
        $this->kilometrage = $kilometrage;
        $this->currency = $currency;
        $this->fromtImage = $frontImage;
        $this->reference = $reference;
        $this->transmission = $transmission;
        $this->model = $model;
        $this->energie = $energie;
        $this->mailTo = $mailTo;
        $this->auth = true;
        $this->year = $year;
        $this->transactionReference = $transactionReference;
        $this->brand = $brand;
    }
}
