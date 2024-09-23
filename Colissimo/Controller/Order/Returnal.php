<?php

namespace LaPoste\Colissimo\Controller\Order;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Sales\Controller\OrderInterface;

class Returnal extends \Magento\Sales\Controller\AbstractController\View implements OrderInterface, HttpGetActionInterface
{
}
