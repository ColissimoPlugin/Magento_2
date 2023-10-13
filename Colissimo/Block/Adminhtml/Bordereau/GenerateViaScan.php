<?php

namespace LaPoste\Colissimo\Block\AdminHtml\Bordereau;

use Magento\Framework\View\Element\Template;


class GenerateViaScan extends Template
{
    /**
     * @param Template\Context $context
     * @param array            $data
     */
    public function __construct(Template\Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
    }

    /**
     * Returns action url for contact form
     *
     * @return string
     */
    public function getFormAction()
    {
        return $this->getUrl('laposte_colissimo/bordereau/generatebordereau', ['_secure' => true]);
    }


    /**
     * @return string
     */
    public function getConfigJson()
    {
        $allowedPrefix = ["6A", "9L", "6C", "9V", "6H", "6M", "8R", "6G", "6V", "7R", "8Q", "7Q", "9W", "5R", "CF", "EY", "EN", "CM", "CG", "CA", "CB", "CI"];
        $codeLength = 13;

        return json_encode(['allowedPrefix' => $allowedPrefix, 'codeLength' => $codeLength]);
    }
}
