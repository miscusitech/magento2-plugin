<?php

namespace Satispay\Satispay\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\UrlInterface;
use Satispay\Satispay\Model\Config;

class PaymentRequest extends ResolverBase implements ResolverInterface
{
    private $getCartForUser;
    private $urlBuilder;

    public function __construct(
        Config $satispayConfig,
        GetCartForUser $getCartForUser,
        UrlInterface $urlBuilder,
    ) {
        parent::__construct($satispayConfig);
        $this->getCartForUser = $getCartForUser;
        $this->urlBuilder = $urlBuilder;
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
        if (!isset($args['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }
        $maskedCartId = $args['cart_id'];
        $currentUserId = $context->getUserId();
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart = $this->getCartForUser->execute($maskedCartId, $currentUserId, $storeId);

        if (!$cart->getIsActive()) {
            throw new GraphQlInputException(__('The cart is not active.'));
        }

        $cart->reserveOrderId();
        $orderId = $cart->getReservedOrderId();
        $callbackUrl = $this->urlBuilder->getUrl('satispay/callback/', [
            '_query' => 'payment_id={uuid}'
        ]);

        $totalAmount = $cart->getGrandTotal() * 100;
        $satispayPayment = \SatispayGBusiness\Payment::create([
            'flow' => 'MATCH_CODE',
            'amount_unit' => $totalAmount,
            'currency' => 'EUR',
            'external_code' => $orderId,
            'metadata' => [
                'cart_id' => $cart->getId(),
                'order_id' => $orderId,
            ],
            'callback_url' => $callbackUrl,
        ]);
        $payment = $cart->getPayment();
        if (isset($payment)) {
            $payment->setAdditionalInformation('satispay_payment_id', $satispayPayment->id);
        } else {
            throw new GraphQlInputException(__('Couldn\'t save transaction id for order.'));
        }
        return [
            'payment_id' => $satispayPayment->id,
            'amount' => $satispayPayment->amount_unit / 100,
            'status' => $satispayPayment->status,
        ];
    }
}
