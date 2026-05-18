<?php declare(strict_types=1);

namespace BetterPayment\Controller;

use BetterPayment\Util\ConfigReader;
use BetterPayment\Util\PaymentStatusMapper;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api'], 'auth_required' => false])]
class WebhookController extends AbstractController
{
    private PaymentStatusMapper $paymentStatusMapper;
    private ConfigReader $configReader;
    private EntityRepository $orderTransactionRepository;

    public function __construct(
        PaymentStatusMapper $paymentStatusMapper,
        ConfigReader $configReader,
        EntityRepository $orderTransactionRepository
    ){
        $this->paymentStatusMapper = $paymentStatusMapper;
        $this->configReader = $configReader;
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    #[Route(path: '/api/betterpayment/webhook', name: 'api.betterpayment.webhook', methods: ['POST'])]
    public function handle(Request $request, Context $context): Response
    {
        try {
            $salesChannelId = $this->resolveSalesChannelIdFromRequest($request, $context);

            if ($this->checksumIsValidated($request, $salesChannelId)) {
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
    private function checksumIsValidated(Request $request, ?string $salesChannelId): bool
    {
        $params = $request->request->all();
        unset($params['checksum']);
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC1738);
        $checksum = sha1($query . $this->configReader->getIncomingKey($salesChannelId));

        return $checksum == $request->get('checksum');
    }

    // Locate the sales channel that owns the transaction the webhook refers to,
    // so per-channel credentials can be used for checksum validation.
    private function resolveSalesChannelIdFromRequest(Request $request, Context $context): ?string
    {
        $betterPaymentTransactionId = $request->get('transaction_id');
        if (!$betterPaymentTransactionId) {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.better_payment_transaction_id', $betterPaymentTransactionId));
        $criteria->addAssociation('order');

        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        return $orderTransaction?->getOrder()?->getSalesChannelId();
    }
}
