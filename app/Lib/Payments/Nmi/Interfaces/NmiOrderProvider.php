<?php

namespace App\Lib\Payments\Nmi\Interfaces;

use Illuminate\Contracts\Support\Arrayable;

interface NmiOrderProvider extends Arrayable
{
    /**
     * @return string|null
     */
    public  function getOrderid(): ?string;

    /**
     * @param string|null $orderId
     * @return self
     */
    public  function setOrderid(?string $orderId): self;

    /**
     * @return string|null
     */
    public  function getOrderDescription(): ?string;

    /**
     * @param string|null $orderDescription
     * @return self
     */
    public  function setOrderDescription(?string $orderDescription): self;

    /**
     * @return string|null
     */
    public  function getOrderTemplate(): ?string;

    /**
     * @param string|null $orderTemplate
     * @return self
     */
    public  function setOrderTemplate(?string $orderTemplate): self;

    /**
     * @return string|null
     */
    public  function getOrderDate(): ?string;

    /**
     * @param string|null $orderDate
     * @return self
     */
    public  function setOrderDate(?string $orderDate): self;

    /**
     * @return string|null
     */
    public  function getPonumber(): ?string;

    /**
     * @param string|null $poNumber
     * @return self
     */
    public  function setPonumber(?string $poNumber): self;

    /**
     * @return NmiOrderItemProvider[]
     */
    public  function getItems(): array;

    /**
     * @param NmiOrderItemProvider[] $items
     * @return self
     */
    public function setItems(array $items): self;
}