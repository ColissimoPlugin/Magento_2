<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Prices;

use LaPoste\Colissimo\Logger\Colissimo;
use LaPoste\Colissimo\Model\Prices;
use LaPoste\Colissimo\Api\Data\PricesInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\File\Csv;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\ObjectManagerInterface;

class ImportAction extends Action
{
    const FILE_DELIMITER = ',';

    /**
     * @var Csv
     */
    protected $csvParser;
    /**
     * @var \LaPoste\Colissimo\Logger\Colissimo
     */
    protected $logger;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    /**
     * @var RedirectFactory
     */
    protected $redirectFactory;
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Import constructor.
     *
     * @param Context                $context
     * @param Csv                    $csvParser
     * @param Colissimo              $logger
     * @param ManagerInterface       $messageManager
     * @param RedirectFactory        $redirectFactory
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Context $context,
        Csv $csvParser,
        Colissimo $logger,
        ManagerInterface $messageManager,
        RedirectFactory $redirectFactory,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct($context);

        $this->csvParser = $csvParser;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->redirectFactory = $redirectFactory;
        $this->objectManager = $objectManager;

        $this->csvParser->setDelimiter(self::FILE_DELIMITER);
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        /** @var Http $request */
        $request = $this->getRequest();
        $importFile = $request->getFiles('import_file');
        $resultRedirect = $this->redirectFactory->create()->setPath('laposte_colissimo/prices/index');

        if (empty($importFile['type']) || 'text/csv' !== $importFile['type']) {
            $this->onError(__('File missing or wrong format (CSV expected)'));

            return $resultRedirect;
        }

        try {
            $content = $this->csvParser->getData($importFile['tmp_name']);
        } catch (\Exception $e) {
            $this->onError($e->getMessage());

            return $resultRedirect;
        }

        $headers = array_shift($content);
        $headersPositions = [
            PricesInterface::METHOD     => array_search(PricesInterface::METHOD, $headers),
            PricesInterface::AREA       => array_search(PricesInterface::AREA, $headers),
            PricesInterface::WEIGHT_MIN => array_search(PricesInterface::WEIGHT_MIN, $headers),
            PricesInterface::WEIGHT_MAX => array_search(PricesInterface::WEIGHT_MAX, $headers),
            PricesInterface::PRICE_MIN  => array_search(PricesInterface::PRICE_MIN, $headers),
            PricesInterface::PRICE_MAX  => array_search(PricesInterface::PRICE_MAX, $headers),
            PricesInterface::PRICE      => array_search(PricesInterface::PRICE, $headers),
        ];

        $newModelPrices = $this->objectManager->create(Prices::class)->load(null);
        foreach ($content as $line) {
            if (count($line) !== 7) {
                $this->logger->warning(
                    __('The following line does not have the correct fields'),
                    $line
                );
                continue;
            }

            try {
                $data = [
                    PricesInterface::METHOD     => $line[$headersPositions[PricesInterface::METHOD]],
                    PricesInterface::AREA       => $line[$headersPositions[PricesInterface::AREA]],
                    PricesInterface::WEIGHT_MIN => $line[$headersPositions[PricesInterface::WEIGHT_MIN]],
                    PricesInterface::WEIGHT_MAX => $line[$headersPositions[PricesInterface::WEIGHT_MAX]],
                    PricesInterface::PRICE_MIN  => $line[$headersPositions[PricesInterface::PRICE_MIN]],
                    PricesInterface::PRICE_MAX  => $line[$headersPositions[PricesInterface::PRICE_MAX]],
                    PricesInterface::PRICE      => $line[$headersPositions[PricesInterface::PRICE]],
                ];

                $newModelPrices->setData($data);
                $newModelPrices->save();
            } catch (\Exception $e) {
                $this->onError($e->getMessage());
            }
        }

        $this->messageManager->addSuccessMessage(__('Import is done'));

        return $resultRedirect;
    }

    /**
     * @param $message
     *
     * @return void
     */
    public function onError($message)
    {
        $this->messageManager->addErrorMessage(__('An error occurred during import: %1', $message));
        $this->logger->error($message);
    }
}
