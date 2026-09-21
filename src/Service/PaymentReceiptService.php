<?php

namespace App\Service;

use App\Entity\Product;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Twig\Environment;

final readonly class PaymentReceiptService
{
    public function __construct(
        private Environment $twig,
        private MailerInterface $mailer,
    ) {
    }

    public function send(
        object $entity,
        string $recipientEmail,
        float $amount,
        \DateTimeInterface $paidAt,
        string $paymentMethod,
        string $transactionReference,
    ): void {
        /*
         * 1. On prépare toutes les données utilisées
         * dans le HTML / PDF.
         */
        $context = [
            'vehicule' => $entity,
            'amount' => $amount,
            'paidAt' => $paidAt,
            'paymentMethod' => $paymentMethod,
            'transactionReference' => $transactionReference,
        ];

        /*
         * 2. Génération du HTML du PDF avec Twig
         */
        $html = $this->twig->render(
            'pdf/payment_receipt.html.twig',
            $context
        );

        /*
         * 3. Génération du PDF
         */
        $options = new Options();

        $options->set('defaultFont', 'DejaVu Sans');

        /*
         * Nécessaire si tes images utilisent
         * une URL https://...
         */
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html, 'UTF-8');

        $dompdf->setPaper(
            'A4',
            'portrait'
        );

        $dompdf->render();

        /*
         * Contenu binaire du PDF
         */
        $pdfContent = $dompdf->output();

        /*
         * Nom du PDF
         */
        $filename = sprintf(
            'bordereau-paiement-%s.pdf',
            $transactionReference
        );

        /*
         * 4. Contenu HTML du mail
         */
        $emailHtml = $this->twig->render(
            'emails/payment_receipt.html.twig',
            $context
        );

        /*
         * 5. Création du mail
         */
        $email = (new Email())
            ->from(
                new Address(
                    'paiement@nexotrade.fr',
                    'NexoTrade'
                )
            )
            ->to($recipientEmail)
            ->subject(
                'Confirmation de votre paiement - '
                . $transactionReference
            )
            ->html($emailHtml)

            /*
             * PDF en pièce jointe
             */
            ->addPart(
                new DataPart(
                    $pdfContent,
                    $filename,
                    'application/pdf'
                )
            );

        /*
         * 6. Envoi
         */
        $this->mailer->send($email);
    }
}