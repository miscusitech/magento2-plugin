<?php
namespace Satispay\Satispay\Model\Resolver;

use Satispay\Satispay\Model\Config;

class ResolverBase
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->initializeSdk();
    }

    private function initializeSdk()
    {
        \SatispayGBusiness\Api::setPublicKey($this->config->getPublicKey());
        \SatispayGBusiness\Api::setPrivateKey($this->config->getPrivateKey());

        if ($this->config->getSandbox()) {
            \SatispayGBusiness\Api::setSandbox(true);
            \SatispayGBusiness\Api::setKeyId($this->config->getSandboxKeyId());
        } else {
            \SatispayGBusiness\Api::setKeyId($this->config->getKeyId());
        }
    }
}
