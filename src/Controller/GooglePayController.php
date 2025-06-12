<?php declare(strict_types=1);

namespace BetterPayment\Controller;

use BetterPayment\Util\ConfigReader;
use BetterPayment\Util\PaymentStatusMapper;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/betterpayment/google-pay/',
    options: ['seo' => false],
    defaults: [
        'XmlHttpRequest' => true,
        '_routeScope' => ['storefront'],
        '_loginRequired' => true,
        '_loginRequiredAllowGuest' => true,
    ],
)]
class GooglePayController extends AbstractController
{
    private PaymentStatusMapper $paymentStatusMapper;
    private ConfigReader $configReader;

    public function __construct(
        PaymentStatusMapper $paymentStatusMapper,
        ConfigReader $configReader
    ){
        $this->paymentStatusMapper = $paymentStatusMapper;
        $this->configReader = $configReader;
    }

    #[Route(path: 'process-payment', name: 'frontend.betterpayment.google-pay.process-payment', methods: ['POST'])]
    public function processPayment(Request $request): Response
    {
        $client = new Client([
            'base_uri' => $this->configReader->getApiUrl(),
        ]);

        $headers = [
            'Authorization' => 'Basic '.base64_encode($this->configReader->getAPIKey().':'.$this->configReader->getOutgoingKey()),
            'Content-Type' => 'application/json',
        ];

        $data = $request->request->all();
        $data['payment_type'] = 'googlepay';
        $body = json_encode($data);

        try {
            $responseBody = $client->post('/rest/payment', [
                'headers' => $headers,
                'body' => $body,
            ]);

            return new Response($responseBody->getBody()->getContents(), Response::HTTP_OK);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
