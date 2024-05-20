<?php

namespace Snowdog\Hyva\Checkout\DHL24PL\Magewire;

use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Framework\Url;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component;

class ParcelShop extends Component implements EvaluationInterface
{
    public string $sap = '';

    public string $postcode = '';

    public string $city = '';

    public string $desc = '';

    public function __construct(
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly SessionCheckout         $sessionCheckout,
        private readonly Url                     $url
    ) {
    }

    public function evaluateCompletion(EvaluationResultFactory $resultFactory): EvaluationResultInterface
    {
        if (
            !in_array(
                $this->sessionCheckout->getQuote()->getShippingAddress()->getShippingMethod(),
                ['dhl_dhl24pl_parcelshop', 'dhl_dhl24pl_parcelshop_cod']
            )
        ) {
            return $resultFactory->createSuccess();
        }

        if (empty($this->sap)) {
            return $resultFactory->createBlocking();
        }

        $quote = $this->sessionCheckout->getQuote();
        $quote->getResource()->getConnection()->update(
            $quote->getResource()->getMainTable(),
            [
                'dhlpl_parcelshop' => json_encode(
                    [
                        'sap' => $this->sap,
                        'postcode' => $this->postcode,
                        'city' => $this->city,
                    ]
                ),
            ],
            ['entity_id = ? ' => $quote->getId()]
        );

        return $resultFactory->createSuccess();
    }

    public function mount()
    {
        $data = $this->sessionCheckout->getDHL24Parcelshop();
        if (is_array($data)) {
            $this->sap = $data['sap'];
            $this->postcode = $data['postcode'];
            $this->city = $data['city'];
            $this->desc = $data['desc'];
        }
    }

    public function parcelshop(string $sap, string $postcode, string $city, string $desc)
    {
        $this->sap = $sap;
        $this->postcode = $postcode;
        $this->city = $city;
        $this->desc = $desc;

        $this->sessionCheckout->setDHL24Parcelshop(
            [
                'sap' => $sap,
                'postcode' => $postcode,
                'city' => $city,
                'desc' => $desc,
            ]
        );
    }

    public function getMapUrl(): string
    {
        $country = $this->sessionCheckout->getQuote()->getShippingAddress()->getCountryId();
        $params = [];

        if ($country != 'PL') {
            $params['country'] = $country;
            $params['type'] = $country == 'DE' ? 'packStation' : 'parcelShop';
        }

        if ($this->id == 'checkout.shipping.method.dhl_dhl24pl_parcelshop_cod') {
            $params['type'] = 'lmcod';
        }

        return $this->url->getUrl('dhl/shipment/lmmap', $params);
    }
}