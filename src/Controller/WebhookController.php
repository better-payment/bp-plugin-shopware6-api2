<?php declare(strict_types=1);

namespace BetterPayment\Controller;

use BetterPayment\Util\PaymentStatusMapper;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
{
    private PaymentStatusMapper $paymentStatusMapper;
    private EntityRepositoryInterface $orderTransactionRepository;

    public function __construct(
        PaymentStatusMapper $paymentStatusMapper,
        EntityRepositoryInterface $orderTransactionRepository
    ){
        $this->paymentStatusMapper = $paymentStatusMapper;
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    /**
     * @Route("/api/betterpayment/webhook", methods={"POST"}, defaults={"_routeScope"={"api"}, "auth_required"=false, "auth_enabled"=false}, name="api.betterpayment.webhook")
     */
    public function handle(Request $request, Context $context): Response
    {
        // TODO verify by $request->get('checksum')
        if (true) {
            $betterPaymentTransactionID = $request->get('transaction_id');
            $betterPaymentTransactionState = $request->get('status');
            $orderTransactionID = $this->getOrderTransactionByBetterPaymentTransactionID($betterPaymentTransactionID, $context)->getId();

            $this->paymentStatusMapper->updateOrderTransactionState($orderTransactionID, $betterPaymentTransactionState, $context);

            return new Response($request->get('message'), 200);
        }
        else {
            return new Response('checksum verification failed', 401);
        }
    }

    private function getOrderTransactionByBetterPaymentTransactionID(string $betterPaymentTransactionID, Context $context): OrderTransactionEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.better_payment_transaction_id', $betterPaymentTransactionID));

        return $this->orderTransactionRepository->search($criteria, $context)->first();
    }
}