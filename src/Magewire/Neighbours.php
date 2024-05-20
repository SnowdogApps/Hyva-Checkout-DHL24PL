<?php

namespace Snowdog\Hyva\Checkout\DHL24PL\Magewire;

use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component;

class Neighbours extends Component
{
    public bool $active = false;

    public string $fullName = '';

    public string $postcode = '';

    public string $city = '';

    public string $street = '';

    public string $houseNumber = '';

    public string $apartmentNumber = '';

    public string $phoneNumber = '';

    public string $email = '';

    public function __construct(
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly SessionCheckout         $sessionCheckout
    ) {
    }

    public function mount()
    {
        $data = $this->sessionCheckout->getDHL24Neighbor();
        if (is_array($data)) {
            $this->active = $data['is_neighbours'] === 'true';
            $this->fullName = $data['neighbor_name'] ?? '';
            $this->postcode = $data['neighbor_postcode'] ?? '';
            $this->city = $data['neighbor_city'] ?? '';
            $this->street = $data['neighbor_street'] ?? '';
            $this->houseNumber = $data['neighbor_houseNumber'] ?? '';
            $this->apartmentNumber = $data['neighbor_apartmentNumber'] ?? '';
            $this->phoneNumber = $data['neighbor_phoneNumber'] ?? '';
            $this->email = $data['neighbor_emailAddress'] ?? '';
        }
    }

    public function updated($value, string $name)
    {
        $data = [
            'is_neighbours' => $this->active ? "true" : "false",
            'neighbor_name' => $this->fullName,
            'neighbor_postcode' => $this->postcode,
            'neighbor_city' => $this->city,
            'neighbor_street' => $this->street,
            'neighbor_houseNumber' => $this->houseNumber,
            'neighbor_apartmentNumber' => $this->apartmentNumber,
            'neighbor_phoneNumber' => $this->phoneNumber,
            'neighbor_emailAddress' => $this->email,
        ];

        $quote = $this->sessionCheckout->getQuote();
        $quote->getResource()->getConnection()->update(
            $quote->getResource()->getMainTable(),
            [
                'dhlpl_neighbor' => json_encode($data),
            ],
            ['entity_id = ? ' => $quote->getId()]
        );

        $this->sessionCheckout->setDHL24Neighbor($data);

        return $value;
    }
}