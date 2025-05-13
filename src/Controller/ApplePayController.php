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
    path: '/betterpayment/apple-pay/',
    options: ['seo' => false],
    defaults: [
        'XmlHttpRequest' => true,
        '_routeScope' => ['storefront'],
        '_loginRequired' => true,
        '_loginRequiredAllowGuest' => true,
    ],
)]
class ApplePayController extends AbstractController
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

    #[Route(path: 'validate-merchant', name: 'frontend.betterpayment.apple-pay.validate-merchant', methods: ['POST'])]
    public function validateMerchant(): Response
    {
        $client = new Client([
            'base_uri' => $this->configReader->getApiUrl(),
        ]);

        $headers = [
            'Authorization' => 'Basic '.base64_encode($this->configReader->getAPIKey().':'.$this->configReader->getOutgoingKey()),
            'Content-Type' => 'application/json',
        ];

        $body = json_encode([
            'initiative_context' => parse_url($this->configReader->getAppUrl(), PHP_URL_HOST),
        ]);

        try {
            $responseBody = $client->post('db_apple_pay_merchants', [
                'headers' => $headers,
                'body' => $body,
            ]);

            return new Response($responseBody->getBody()->getContents(), Response::HTTP_OK);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: 'process-payment', name: 'frontend.betterpayment.apple-pay.process-payment', methods: ['POST'])]
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
        $data['payment_type'] = 'applepay';
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
