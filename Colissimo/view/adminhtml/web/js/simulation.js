define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return function (config, element) {
        const $root = $(element),
            $form = $root.find('#lpc_simulation_form'),
            $optionsContainer = $root.find('#lpc_simulation_options'),
            $result = $root.find('#lpc_simulation_result'),
            $submit = $root.find('#lpc_simulation_submit');
        let optionIndex = 0;

        /**
         * Build the <option> tags for the available rate option codes.
         */
        function optionCodeChoices() {
            const codes = config.optionCodes || [];

            return codes
                .slice()
                .sort(function (a, b) {
                    return a.label.localeCompare(b.label);
                })
                .map(function (option) {
                    return '<option value="' + option.value + '">' + option.label + '</option>';
                })
                .join('');
        }

        /**
         * Human-readable label for an option code, falling back to the raw code.
         */
        function optionCodeLabel(code) {
            const match = (config.optionCodes || []).find(function (option) {
                return option.value === code;
            });

            return match ? match.label : code;
        }

        /**
         * Expected value type for a given option code (defaults to free text).
         */
        function valueType(code) {
            return (config.optionValueTypes || {})[code] || 'text';
        }

        /**
         * Build the "choix" control matching the selected option type.
         */
        function valueControl(type, index) {
            const name = `optionsValorisees[${index}][choix]`;

            switch (type) {
                // Flag options: value is forced to "true", no visible input needed.
                case 'boolean':
                    return `<input type="hidden" name="${name}" value="true">`;
                // Insured value: a positive number.
                case 'amount':
                    return `<input class="admin__control-text" type="number" min="0" step="0.01"
                                name="${name}" placeholder="${$t('Amount')}">`;
                // Registered mail level: R1, R2 or R3 only.
                case 'recommendation':
                    return `<select class="admin__control-select" name="${name}">
                                <option value="R1">R1</option>
                                <option value="R2">R2</option>
                                <option value="R3">R3</option>
                            </select>`;
                default:
                    return `<input class="admin__control-text" type="text" name="${name}">`;
            }
        }

        /**
         * Render the typed "choix" control for a row, based on its selected code.
         */
        function applyValueControl($row) {
            const index = $row.data('index'), type = valueType($row.find('.lpc-option-code').val());

            $row.find('.lpc-option-value')
                .html(valueControl(type, index))
                .toggleClass('lpc-option-value--hidden', type === 'boolean');
        }

        /**
         * Append a new "rate option" row (Remove button, codeOption dropdown, typed value).
         */
        function addOptionRow() {
            const index = optionIndex++;
            const html = `<div class="lpc-option-row" data-index="${index}">
                        <div class="lpc-option-controls">
                            <button type="button" class="action-secondary lpc-option-remove">
                                ${$t('Remove')}
                            </button>
                            <select class="admin__control-select lpc-option-code"
                                name="optionsValorisees[${index}][codeOption]">
                                ${optionCodeChoices()}
                            </select>
                            <span class="lpc-option-value"></span>
                        </div>
                    </div>`;

            $optionsContainer.append(html);
            applyValueControl($optionsContainer.find('.lpc-option-row').last());
        }

        /**
         * Format an amount as a monetary value.
         */
        function money(value) {
            if (value === null || typeof value === 'undefined' || value === '') {
                return '-';
            }

            return parseFloat(value).toFixed(2) + ' €';
        }

        /**
         * Fields the web service requires before it can return a price.
         */
        function requiredFields() {
            return [
                {name: 'codePaysExpediteur', label: $t('sender country')},
                {name: 'codePostalExpediteur', label: $t('sender zipcode')},
                {name: 'codePaysDestinataire', label: $t('addressee country')},
                {name: 'codePostalDestinataire', label: $t('addressee zipcode')},
                {name: 'poids', label: $t('parcel weight')}
            ];
        }

        /**
         * Return the label of the first missing required field, or null when the form is complete.
         */
        function firstMissingField() {
            const fields = requiredFields();

            for (let i = 0 ; i < fields.length ; i++) {
                const value = $.trim(String($form.find('[name="' + fields[i].name + '"]').val() || ''));

                if ('' === value) {
                    return fields[i].label;
                }
            }

            return null;
        }

        /**
         * Display an error message block in the result area.
         */
        function showError(message) {
            $result.html('<div class="message message-error error">' + $('<div></div>')
                .text(message)
                .html() + '</div>').show();
        }

        /**
         * Render the blocking errors (errorType other than INFO) returned by the web service.
         * Returns true when at least one blocking error was rendered.
         */
        function renderErrors(data) {
            const errors = (data.errors || []).filter(function (error) {
                return error.errorType && error.errorType !== 'INFO';
            });

            if (!errors.length) {
                return false;
            }

            let html = '<ul class="messages">';
            errors.forEach(function (error) {
                html += '<li class="message message-error error">' + $('<div></div>')
                    .text(error.errorMessage || error.errorCode || $t('Unknown error'))
                    .html() + '</li>';
            });
            html += '</ul>';

            $result.html(html).show();

            return true;
        }

        function renderResult(data) {
            let html = '', options, i;

            // A blocking error means there is no price to display: show the message only.
            if (renderErrors(data)) {
                return;
            }

            html += '<table class="admin__table-primary lpc-simulation-table">';
            html += '<tr><th>' + $t('Transport (excl. tax)') + '</th><td>' + money(data.montantTransportHT) + '</td></tr>';

            if (data.montantDesOptions && data.montantDesOptions.tarifDesOptions) {
                options = data.montantDesOptions.tarifDesOptions;
                for (i = 0 ; i < options.length ; i++) {
                    html += `<tr>
                                <th>
                                    ${$t('Option')} ${$('<div></div>').text(options[i].option ? optionCodeLabel(options[i].option.codeOption) : '').html()}
                                </th>
                                <td>
                                    ${money(options[i].montantHT)}
                                </td>
                            </tr>`;
                }
                html += '<tr><th>' + $t('Total options (excl. tax)') + '</th><td>' + money(data.montantDesOptions.montantTotalOptions) + '</td></tr>';
            }

            if (typeof data.montantRetourHT !== 'undefined' && data.montantRetourHT !== null) {
                html += '<tr><th>' + $t('Return transport (excl. tax)') + '</th><td>' + money(data.montantRetourHT) + '</td></tr>';
            }

            if (typeof data.montantSupplements !== 'undefined' && data.montantSupplements !== null) {
                html += '<tr><th>' + $t('Surcharges') + '</th><td>' + money(data.montantSupplements) + '</td></tr>';
            }

            html += '<tr class="lpc-simulation-total"><th>' + $t('Total (excl. tax)') + '</th><td>' + money(data.montantTotalHT) + '</td></tr>';
            html += '</table>';

            $result.html(html).show();
        }

        $root.on('click', '#lpc_simulation_add_option', function () {
            addOptionRow();
        });

        $root.on('click', '.lpc-option-remove', function () {
            $(this).closest('.lpc-option-row').remove();
        });

        $root.on('change', '.lpc-option-code', function () {
            applyValueControl($(this).closest('.lpc-option-row'));
        });

        $form.on('submit', function (event) {
            event.preventDefault();

            // Client-side check: don't send an incomplete request to the web service.
            const missingField = firstMissingField();
            if (missingField) {
                showError($t('Please fill in the "%1" field.').replace('%1', missingField));

                return;
            }

            $submit.prop('disabled', true);
            $result.html('<div class="lpc-simulation-loading">' + $t('Computing…') + '</div>').show();

            $.ajax({
                url: config.calculateUrl,
                type: 'POST',
                dataType: 'json',
                data: $form.serialize(),
                showLoader: true
            }).done(function (response) {
                if (response && response.success) {
                    renderResult(response.result || {});
                } else {
                    showError(response && response.error ? response.error : $t('Unknown error'));
                }
            }).fail(function () {
                showError($t('The request failed.'));
            }).always(function () {
                $submit.prop('disabled', false);
            });
        });
    };
});
