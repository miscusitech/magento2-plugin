<?php

namespace Satispay\Satispay\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Satispay\Satispay\Model\Resolver\ResolverBase;

class PaymentStatus extends ResolverBase implements ResolverInterface
{
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
        if (!isset($args['payment_id'])) {
            throw new GraphQlInputException(__('Required parameter "payment_id" is missing'));
        }
        $paymentId = $args['payment_id'];

        $satispayPayment = \SatispayGBusiness\Payment::get($paymentId);
        return [
            'id' => $satispayPayment->id,
            'amount' => $satispayPayment->amount_unit / 100,
            'status' => $satispayPayment->status
        ];
    }
}
