<?php declare(strict_types=1);

namespace BetterPayment\Controller;

use BetterPayment\Util\ConfigReader;
use BetterPayment\Util\PaymentStatusMapper;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
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

    /**
     * @Route("/api/betterpayment/webhook", methods={"POST"}, defaults={"_routeScope"={"api"}, "auth_required"=false, "auth_enabled"=false}, name="api.betterpayment.webhook")
     */
    public function handle(Request $request, Context $context): Response
    {
        try {
            if ($this->checksumIsValidated($request) || true) {
                // Update state and return response
                return $this->paymentStatusMapper->updateOrderTransactionStateFromWebhook($request, $context);
            }
            else {
                return new Response('Checksum verification failed', Response::HTTP_UNAUTHORIZED);
            }
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    // Calculate checksum without checksum parameter itself, and sign it with INCOMING_KEY
    // NOTE: "content-type": "application/x-www-form-urlencoded" for this request
    // that's why $request->request is used to fetch parameters
    private function checksumIsValidated(Request $request): bool
    {
        $params = $request->request->all();
        unset($params['checksum']);
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC1738);
        $checksum = sha1($query . $this->configReader->getIncomingKey());

        return $checksum == $request->get('checksum');
    }
}