<?php declare(strict_types=1);

namespace BetterPayment\Controller;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
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
    private OrderTransactionStateHandler $orderTransactionStateHandler;
    private EntityRepositoryInterface $orderTransactionRepository;

    public function __construct(
        OrderTransactionStateHandler $orderTransactionStateHandler,
        EntityRepositoryInterface $orderTransactionRepository
    ){
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
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

            $this->updateOrderTransactionState($orderTransactionID, $betterPaymentTransactionState, $context);

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

    private function updateOrderTransactionState(string $orderTransactionID, string $betterPaymentTransactionState, Context $context): void
    {
        switch ($betterPaymentTransactionState) {
            case 'started':
                $this->orderTransactionStateHandler->process($orderTransactionID, $context);
                break;
            case 'authorized':
                $this->orderTransactionStateHandler->authorize($orderTransactionID, $context);
                break;
            case 'canceled':
                $this->orderTransactionStateHandler->cancel($orderTransactionID, $context);
                break;
            case 'chargeback':
                $this->orderTransactionStateHandler->chargeback($orderTransactionID, $context);
                break;
            case 'declined':
            case 'error':
                $this->orderTransactionStateHandler->fail($orderTransactionID, $context);
                break;
            case 'pending': // TODO check whether it is process in shopware or not
                $this->orderTransactionStateHandler->process($orderTransactionID, $context);
                break;
            case 'completed':
                // TODO check for amount left
                if (true)
                    $this->orderTransactionStateHandler->paid($orderTransactionID, $context);
                else
                    $this->orderTransactionStateHandler->payPartially($orderTransactionID, $context);
                break;
            case 'refunded':
                // TODO check for amount left
                if (true)
                    $this->orderTransactionStateHandler->refund($orderTransactionID, $context);
                else
                    $this->orderTransactionStateHandler->refundPartially($orderTransactionID, $context);
                break;
            default:
                $this->orderTransactionStateHandler->reopen($orderTransactionID, $context);
        }
    }
}