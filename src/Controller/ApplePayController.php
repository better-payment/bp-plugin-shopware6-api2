<?php declare(strict_types=1);

namespace BetterPayment\Controller;

use BetterPayment\Util\ConfigReader;
use BetterPayment\Util\PaymentStatusMapper;
use Shopware\Core\Framework\Context;
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
//    private PaymentStatusMapper $paymentStatusMapper;
//    private ConfigReader $configReader;
//
//    public function __construct(
//        PaymentStatusMapper $paymentStatusMapper,
//        ConfigReader $configReader
//    ){
//        $this->paymentStatusMapper = $paymentStatusMapper;
//        $this->configReader = $configReader;
//    }

    #[Route(path: 'validate-merchant', name: 'frontend.betterpayment.apple-pay.validate-merchant', methods: ['POST'])]
    public function validateMerchant(Request $request, Context $context): Response
    {
        try {

        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: 'process-payment', name: 'frontend.betterpayment.apple-pay.process-payment', methods: ['POST'])]
    public function processPayment(Request $request, Context $context): Response
    {
        try {

        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
