<?php

namespace LaPoste\Colissimo\Block\System\Config;

class ColorPicker extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * CSS for the color picker selector
     */
    const CSSSELECTOR = 'width:25px; height: 25px; border: 1px solid #CCCCCC; border-radius: 5px;';

    /**
     * Add color picker in admin configuration fields
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string script
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $idField = $element->getHtmlId();
        $nameField = $element->getName();
        $value = empty($element->getData('value')) ? '#000000' : $element->getData('value');

        $html = '<input type="hidden" id="' . $idField . '" name="' . $nameField . '" value="' . $value . '">
        <div id="' . $idField . '_colorPicker" style="' . self::CSSSELECTOR . '"></div>';

        $html .= '<script type="text/javascript">
            require(["jquery","jquery/colorpicker/js/colorpicker"], function ($) {
                $(document).ready(function () {
                    var $el = $("#' . $idField . '_colorPicker");
                    $el.css("backgroundColor", "' . $value . '");

                    // Attach the color picker
                    $el.ColorPicker({
                        color: "' . $value . '",
                        onChange: function (hsb, hex, rgb) {
                            $el.css("backgroundColor", "#" + hex).val("#" + hex);
                            var $fieldinput = $("#' . $idField . '");
                            $fieldinput.val("#" + hex);
                        }
                    });
                });
            });
            </script>';

        return $html;
    }
}
