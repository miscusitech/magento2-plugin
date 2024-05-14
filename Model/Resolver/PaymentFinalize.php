<?php

namespace Satispay\Satispay\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Satispay\Satispay\Model\Resolver\ResolverBase;
use Magento\Sales\Model\Order;

class PaymentFinalize extends ResolverBase implements ResolverInterface
{
    private $order;
    private $orderRepository;
    private $orderSender;

    public function __construct(
        \Satispay\Satispay\Model\Config $satispayConfig,
        Order $order,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
    ) {
        parent::__construct($satispayConfig);
        $this->order = $order;
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['order_id'])) {
            throw new GraphQlInputException(__('Required parameter "order_id" is missing'));
        }
        $orderId = $args['order_id'];

        if (!isset($args['payment_id'])) {
            throw new GraphQlInputException(__('Required parameter "payment_id" is missing'));
        }
        $paymentId = $args['payment_id'];

        // $order = $this->orderRepository->get($orderId);
        $order = $this->order->loadByIncrementId($orderId);

        try {
            $satispayPayment = \SatispayGBusiness\Payment::get($paymentId);
        } catch (\Exception $e) {
            throw new GraphQlInputException(__('Payment not found'));
        }

        if ($satispayPayment->status != 'ACCEPTED') {
            throw new GraphQlInputException(__('Payment not accepted'));
        }

        $payment = $order->getPayment();
        $payment->setTransactionId($satispayPayment->id);
        $payment->setCurrencyCode($satispayPayment->currency);
        $payment->setIsTransactionClosed(true);
        $payment->registerCaptureNotification($satispayPayment->amount_unit / 100, true);

        $order->setState($order::STATE_PROCESSING);
        $order->setStatus($order::STATE_PROCESSING);
        $this->orderRepository->save($order);

        if (!$order->getEmailSent()) {
            $this->orderSender->send($order);
        }
        $order->setState($order::STATE_COMPLETE);
        $order->setStatus($order::STATE_COMPLETE);
        $this->orderRepository->save($order);
        return [
            'registered' => true,
            'payment' => [
                'id' => $satispayPayment->id,
                'amount' => $satispayPayment->amount_unit / 100,
                'status' => $satispayPayment->status
            ]
        ];
    }
}
