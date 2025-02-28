<?php

namespace App\Lib\Payments\Nmi\Classes;

use App\Lib\Payments\Nmi\Interfaces\NmiOrderProvider;
use App\Lib\Payments\Nmi\Interfaces\NmiOrderItemProvider;

class NmiOrder implements NmiOrderProvider
{

    /**
     * The merchant's internal order identifier
     * @var string|null
     */
    protected ?string $orderId = null;

    /**
     * The merchant's internal order description
     * @var string|null
     */
    protected ?string $orderDescription = null;

    /**
     * Order template ID.
     * @var string|null
     */
    protected ?string $orderTemplate = null;

    /**
     * Purchase order date, defaults to the date of the transaction.
     * Format: YYMMDD
     * @var string|null
     */
    protected ?string $orderDate = null;

    /**
     * @var string|null
     */
    protected ?string $poNumber = null;

    /**
     * @var NmiOrderItemProvider[]
     */
    protected array $items = [];

    /**
     * @return string|null
     */
    public function getOrderid(): ?string
    {
        return $this->orderId;
    }

    /**
     * @param string|null $orderId
     * @return NmiOrderProvider
     */
    public function setOrderid(?string $orderId): NmiOrderProvider
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOrderDescription(): ?string
    {
        return $this->orderDescription;
    }

    /**
     * @param string|null $orderDescription
     * @return NmiOrderProvider
     */
    public function setOrderDescription(?string $orderDescription): NmiOrderProvider
    {
        $this->orderDescription = $orderDescription;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOrderTemplate(): ?string
    {
        return $this->orderTemplate;
    }

    /**
     * @param string|null $orderTemplate
     * @return NmiOrderProvider
     */
    public function setOrderTemplate(?string $orderTemplate): NmiOrderProvider
    {
        $this->orderTemplate = $orderTemplate;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOrderDate(): ?string
    {
        return $this->orderDate;
    }

    /**
     * @param string|null $orderDate
     * @return NmiOrderProvider
     */
    public function setOrderDate(?string $orderDate): NmiOrderProvider
    {
        $this->orderDate = $orderDate;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPoNumber(): ?string
    {
        return $this->poNumber;
    }

    /**
     * @param string|null $poNumber
     * @return NmiOrderProvider
     */
    public function setPoNumber(?string $poNumber): NmiOrderProvider
    {
        $this->poNumber = $poNumber;

        return $this;
    }

    /**
     * @return NmiOrderItemProvider[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param NmiOrderItemProvider[] $items
     * @return NmiOrderProvider
     */
    public function setItems(array $items): NmiOrderProvider
    {
        // before we set the items, make sure all of them have an index
        // to force unique keys for the output of each items props in the
        // final flattened array later
        $newItems = [];
        foreach($items as $index => $item) {
            if ($item instanceof NmiOrderItemProvider) {
                $item->setIndex($item->getIndex() ?: $index);
                $newItems[]= $item;
            }
        }
        $this->items = $newItems;

        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'orderid'           => $this->orderId,
            'order_description' => $this->orderDescription,
            'order_template'    => $this->orderTemplate,
            'order_date'        => $this->orderDate,
        ];
        foreach ($this->items as $item) {
            $data = array_merge($data, $item->toArray());
        }
        return $data;
    }

}