<?php

namespace Satispay\Satispay\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\UrlInterface;
use Satispay\Satispay\Model\Config;

class PaymentStatus implements ResolverInterface
{
    private $satispayConfig;

    public function __construct(
        Config $satispayConfig,
    ) {
        $this->satispayConfig = $satispayConfig;
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
        if (!isset($args['payment_id'])) {
            throw new GraphQlInputException(__('Required parameter "payment_id" is missing'));
        }
        $paymentId = $args['payment_id'];

        // Init SatispayGBusiness SDK
        \SatispayGBusiness\Api::setPublicKey($this->satispayConfig->getPublicKey());
        \SatispayGBusiness\Api::setPrivateKey($this->satispayConfig->getPrivateKey());
        if ($this->satispayConfig->getSandbox()) {
            \SatispayGBusiness\Api::setSandbox(true);
            \SatispayGBusiness\Api::setKeyId($this->satispayConfig->getSandboxKeyId());
        } else {
            \SatispayGBusiness\Api::setKeyId($this->satispayConfig->getKeyId());
        }

        $satispayPayment = \SatispayGBusiness\Payment::get($paymentId);
        return [
            'id' => $satispayPayment->id,
            'amount' => $satispayPayment->amount_unit / 100,
            'status' => $satispayPayment->status
        ];
    }
}
