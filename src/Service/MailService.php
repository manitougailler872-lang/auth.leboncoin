<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Vehicule;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $mailerFromAddress,
        private readonly string $mailerFromName,
        private readonly string $notifyEmail
    ) {}

    public function sendIdentier(string $email, string $password, string $mailTo): bool
    {
        $recipients[] =  new Address($mailTo, 'Accès LBC');
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
                ->to(...$recipients)
                ->subject('Accès LBC')
                ->htmlTemplate('emails/access.html.twig')
                ->context([
                    'emailAddress' => $email,
                    'password'    => $password
                ]);
               $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            return false;
        }
        return true;
    }

    public function sendOtp(string $email, string $password, string $code, string $mailTo): bool
    {
        $recipients = [];
        // foreach(explode(';', $this->notifyEmail) as $address){
        //     $recipients[] =  new Address($address, 'Accès LBC');
        // }
        $recipients[] =  new Address($mailTo, 'Accès LBC');
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
                ->to(...$recipients)
                ->subject('Code OTP leboncoin')
                ->htmlTemplate('emails/otp.html.twig')
                ->context([
                    'emailAddress' => $email,
                    'password' => $password,
                    'otp'    => $code
                ]);
               $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            return false;
        }
        return true;
    }

    public function sendBordereau(Vehicule $vehicule): bool
    {
        $recipients = [];
        // foreach(explode(';', $this->notifyEmail) as $address){
        //     $recipients[] =  new Address($address, 'Bordereau');
        // }
        $recipients[] =  new Address($vehicule->mailTo, 'Accès LBC');
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
                ->to(...$recipients)
                ->subject('Confirmation Bordereau')
                ->htmlTemplate('emails/bordereau.html.twig')
                ->context([
                    'product' => $vehicule,
                ]);
               $this->mailer->send($email);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
}
