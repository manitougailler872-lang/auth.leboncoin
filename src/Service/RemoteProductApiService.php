<?php

namespace App\Service;

use App\Entity\Vehicule;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

final readonly class RemoteProductApiService
{
    private const API_URLS = [
        'o' => 'http://autopro-reservation/api/borderau/%s',
        'utilitaire' => 'https://utilitaire-reservation.fr/api/utilitaire/%s',
        'l' => 'http://127.0.0.1:8000/api/borderau/%s',
        'bmx' => 'https://velo-reservation.fr/api/bmx/%s',
        'electro' => 'https://electro-reservation.fr/api/electro/%s',
        'appartement' => 'https://appartement-reservation.fr/api/appartement/%s',
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProduct(
        string $fromClient,
        string $reference,
    ): ?array {
        /*
         * Vérifie que le client existe.
         */
        if (!isset(self::API_URLS[$fromClient])) {
            return null;
        }

        /*
         * Construction de l'URL.
         */
        $url = sprintf(
            self::API_URLS[$fromClient],
            rawurlencode($reference)
        );

        try {
            $response = $this->httpClient->request(
                'GET',
                $url,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 5000,
                ]
            );
            /*
             * On évite que toArray() lance automatiquement
             * une exception pour les HTTP 4xx / 5xx.
             */
            if ($response->getStatusCode() !== 200) {
                return null;
            }
            return $response->toArray(true);
        } catch (ExceptionInterface) {
            return null;
        }
    }

    public function getEntity(string $fromClient, array $result){
        $entity = null;
        switch($fromClient){
            case 'o':
            case 'l':
                    $entity = new Vehicule(
                        $result['title'],
                        $result['description'],
                        $result['amount'],
                        $result['currency'],
                        $result['frontImage'],
                        $result['reference'],
                        $result['model'],
                        $result['year'],
                        $result['model'],
                        $result['transactionReference'],
                        $result['kilometrage'],
                        $result['transmission'],
                        '',
                     $result['mailTo']);
                return $entity;
        }
    }
}