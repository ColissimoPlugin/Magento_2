<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Configuration;

use LaPoste\Colissimo\Helper\Data;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;

class FeedbackDismiss extends Action
{
    private RedirectFactory $redirectFactory;
    private UrlInterface $urlBuilder;
    private Data $helperData;

    /**
     * @param Context         $context
     * @param RedirectFactory $redirectFactory
     * @param UrlInterface    $urlBuilder
     * @param WriterInterface $configWriter
     * @param Data            $helperData
     */
    public function __construct(
        Context $context,
        RedirectFactory $redirectFactory,
        UrlInterface $urlBuilder,
        Data $helperData
    ) {
        $this->redirectFactory = $redirectFactory;
        $this->urlBuilder = $urlBuilder;
        $this->helperData = $helperData;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @throws LocalizedException|\Exception
     */
    public function execute()
    {
        $markers = $this->helperData->getMarkers();
        $this->helperData->setMarker('feedback', true);

        if (!empty($markers['feedback_dismiss'])) {
            return $this->redirectFactory->create()->setPath($markers['feedback_dismiss']);
        }

        return $this->redirectFactory->create()->setPath($this->urlBuilder->getUrl('adminhtml/system_config/edit/section/lpc_advanced'));
    }
}
