<?php

namespace Satispay\Satispay\Observer;

use Satispay\Satispay\Model\Config;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Sales\Model\Order;
use Satispay\Satispay\Model\Resolver\ResolverBase;

class OrderObserver extends AbstractDataAssignObserver
{
    private $finalizeUnhandledOrders;
    private $finalizePayment;
    private $order;

    public function __construct(
        // Config $config,
        Config $satispayConfig,
        ResolverBase $resolverBase,
        \Satispay\Satispay\Model\FinalizeUnhandledOrders $finalizeUnhandledOrders,
        \Satispay\Satispay\Model\FinalizePayment $finalizePayment,
        Order $order,
    )
    {
        $this->finalizeUnhandledOrders = $finalizeUnhandledOrders;
        $this->finalizePayment = $finalizePayment;
        $this->order = $order;
        new $resolverBase($satispayConfig);
    }

    /**
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();
        $payment = $order->getPayment();
        $extraData = $payment->getAdditionalInformation();
        if (!isset($extraData["satispay_payment_id"])) {
            return;
        }

        $satispayPayment = \SatispayGBusiness\Payment::get($extraData["satispay_payment_id"]);
        $this->finalizePayment->finalizePayment($satispayPayment, $order);
    }
}
