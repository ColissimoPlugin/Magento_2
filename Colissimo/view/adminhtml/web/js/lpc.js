define([
    'jquery',
    'Magento_Ui/js/modal/confirm'
], function ($, confirmation) {
    let lpcGoogleMap,
        lpcMap,
        lpcOpenedInfoWindow,
        lpcMarkersArray = [],
        lpcModePR,
        lpcRelayId,
        lpcRelayType,
        lpcRelayName,
        lpcRelayAddress,
        lpcRelayCity,
        lpcRelayZipcode,
        lpcRelayCountry,
        lpcAjaxSetRelayInformationUrl,
        lpcWidgetRelayCountries,
        lpcMapType,
        lpcMapMarker,
        lpcAjaxUrlLoadRelay,
        lpcAutoSelectRelay,
        lowestLatitude = 999,
        lowestLongitude = 999,
        highestLatitude = -999,
        highestLongitude = -999;

    // Entry point to display map and markers (WS)
    const lpcShowRelaysMap = function (addressData) {
        lpcClearMarkers();

        const markers = $('.lpc_layer_relay');
        if (markers.length === 0) {
            return;
        }

        const address = `${addressData.countryId} ${addressData.city} ${addressData.zipCode} ${addressData.address}`;
        const colissimoPositionMarker = 'https://ws.colissimo.fr/widget-colissimo/images/ionic-md-locate.svg';

        if ('gmaps' === lpcMapType) {
            const bounds = new google.maps.LatLngBounds();
            const gmapsIcon = {
                url: lpcMapMarker,
                size: new google.maps.Size(36, 58),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(9, 32),
                scaledSize: new google.maps.Size(18, 32)
            };

            markers.each(function (index, element) {
                const relayPosition = new google.maps.LatLng($(this).find('.lpc_layer_relay_latitude').text(),
                    $(this).find('.lpc_layer_relay_longitude').text()
                );

                const markerLpc = new google.maps.Marker({
                    map: lpcGoogleMap,
                    position: relayPosition,
                    title: $(this).find('.lpc_layer_relay_name').text(),
                    icon: gmapsIcon
                });

                const infowindowLpc = new google.maps.InfoWindow({
                    content: lpcGetRelayInfo($(this)),
                    pixelOffset: new google.maps.Size(-9, -5)
                });
                lpcAttachClickInfoWindow(markerLpc, infowindowLpc, index);
                lpcAttachClickChooseRelay(element);

                lpcMarkersArray.push(markerLpc);
                bounds.extend(relayPosition);
            });

            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({'address': address}, function (results, status) {
                if (status !== google.maps.GeocoderStatus.OK) {
                    return;
                }

                lpcMarkersArray.push(new google.maps.Marker({
                    map: lpcGoogleMap,
                    position: new google.maps.LatLng(results[0].geometry.location.lat(), results[0].geometry.location.lng()),
                    icon: {
                        url: colissimoPositionMarker,
                        size: new google.maps.Size(25, 25),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(12, 12)
                    }
                }));
            });

            lpcGoogleMap.fitBounds(bounds);
        } else if ('leaflet' === lpcMapType) {
            const markerIcon = L.icon({
                iconUrl: lpcMapMarker,
                iconSize: [
                    18,
                    32
                ],
                iconAnchor: [
                    9,
                    32
                ],
                popupAnchor: [
                    0,
                    -34
                ]
            });

            let markerLowestLatitude = 999;
            let markerLowestLongitude = 999;
            let markerHighestLatitude = -999;
            let markerHighestLongitude = -999;

            markers.each(function (index, element) {
                const latitude = $(element).find('.lpc_layer_relay_latitude').text();
                const longitude = $(element).find('.lpc_layer_relay_longitude').text();

                markerLowestLatitude = Math.min(latitude, markerLowestLatitude);
                markerLowestLongitude = Math.min(longitude, markerLowestLongitude);
                markerHighestLatitude = Math.max(latitude, markerHighestLatitude);
                markerHighestLongitude = Math.max(longitude, markerHighestLongitude);

                let marker = L.marker([
                    latitude,
                    longitude
                ], {icon: markerIcon}).addTo(lpcMap);

                // Add the information window on each marker
                marker.bindPopup(lpcGetRelayInfo($(this)));
                lpcMarkersArray.push(marker);
                lpcLeafletAttachClickInfoWindow(marker, index);
                lpcAttachClickChooseRelay(element);
            });
            lowestLatitude = markerLowestLatitude;
            lowestLongitude = markerLowestLongitude;
            highestLatitude = markerHighestLatitude;
            highestLongitude = markerHighestLongitude;

            $.get('https://nominatim.openstreetmap.org/search?format=json&q=' + address, function (data) {
                if (data.length === 0) {
                    return;
                }

                let addressMarker = L.marker([
                    data[0].lat,
                    data[0].lon
                ], {
                    icon: L.icon({
                        iconUrl: colissimoPositionMarker,
                        iconSize: [
                            25,
                            25
                        ],
                        iconAnchor: [
                            12,
                            12
                        ]
                    })
                }).addTo(lpcMap);
                lpcMarkersArray.push(addressMarker);
            });

            lpcMap.fitBounds([
                [
                    lowestLatitude,
                    lowestLongitude
                ],
                [
                    highestLatitude,
                    highestLongitude
                ]
            ]);
        }
    };

    // Clean old markers (WS)
    const lpcClearMarkers = function () {
        if ('gmaps' === lpcMapType) {
            lpcMarkersArray.forEach(function (element) {
                element.setMap(null);
            });
        } else if ('leaflet' === lpcMapType) {
            lpcMarkersArray.forEach(function (element) {
                element.removeFrom(lpcMap);
            });
        }

        lpcMarkersArray.length = 0;
    };

    // Create marker popup content (WS)
    const lpcGetRelayInfo = function (relay) {
        const indexRelay = relay.find('.lpc_relay_choose').attr('data-relayindex');

        let contentString = '<div class="info_window_lpc">';
        contentString += '<span class="lpc_store_name">' + relay.find('.lpc_layer_relay_name').text() + '</span>';
        contentString += '<span class="lpc_store_address">' + relay.find('.lpc_layer_relay_address_street').text() + '<br>' + relay.find(
            '.lpc_layer_relay_address_zipcode').text() + ' ' + relay.find('.lpc_layer_relay_address_city').text() + '</span>';
        contentString += '<span class="lpc_store_schedule">' + relay.find('.lpc_layer_relay_schedule').html() + '</span>';
        contentString += '<div class="lpc_relay_choose lpc_relay_popup_choose" data-relayindex=' + indexRelay + '>' + $.mage.__('Choose this relay') + '</div>';
        contentString += '</div>';

        return contentString;
    };

    // Add display relay detail click event (Gmaps)
    const lpcAttachClickInfoWindow = function (marker, infoWindow, index) {
        marker.addListener('click', function () {
            lpcGmapsClickHandler(marker, infoWindow);
        });

        $('#lpc_layer_relay_' + index).click(function () {
            lpcGmapsClickHandler(marker, infoWindow);
        });
    };

    // Display details on markers (Gmaps)
    const lpcGmapsClickHandler = function (marker, infoWindow) {
        // Display map if we are in list only display
        displayMapOnDisplayRelayDetails();

        if (lpcOpenedInfoWindow) {
            lpcOpenedInfoWindow.close();
            lpcOpenedInfoWindow = null;
            return;
        }

        infoWindow.open(lpcGoogleMap, marker);
        lpcOpenedInfoWindow = infoWindow;
    };

    // Add action on click choose relay (WS)
    const lpcAttachClickChooseRelay = function (element) {
        const divChooseRelay = $(element).find('.lpc_relay_choose');
        const relayIndex = divChooseRelay.attr('data-relayindex');

        jQuery(document).off('click', '.lpc_relay_choose[data-relayindex=' + relayIndex + ']');
        jQuery(document).on('click', '.lpc_relay_choose[data-relayindex=' + relayIndex + ']', function () {
            lpcAttachOnclickConfirmationRelay(relayIndex);
        });
    };

    // Add display relay detail click event
    const lpcLeafletAttachClickInfoWindow = function (marker, index) {
        marker.on('click', function () {
            lpcLeafletClickHandler(marker);
        });
        $('#lpc_layer_relay_' + index + ' .lpc_show_relay_details').on('click', function () {
            lpcLeafletClickHandler(marker);
        });
    };

    // Display details on markers
    const lpcLeafletClickHandler = function (marker) {
        // Display map if we are in list only display
        displayMapOnDisplayRelayDetails();

        // Display or hide relay info
        if (lpcOpenedInfoWindow) {
            let tmpId = lpcOpenedInfoWindow._leaflet_id;
            lpcOpenedInfoWindow.closePopup();
            lpcOpenedInfoWindow = null;
            if (marker._leaflet_id === tmpId) {
                return;
            }
        }
        marker.openPopup();
        lpcOpenedInfoWindow = marker;
    };

    const lpcMapResize = function () {
        if ('gmaps' === lpcMapType) {
            if (typeof google !== 'undefined') {
                google.maps.event.trigger(lpcGoogleMap, 'center_changed');
            } else {
                console.error(
                    'Google is not defined. Please check if an API key is set in the configuration (Stores->Configuration->Sales->La Poste Colissimo Advanced Setup)');
            }
        } else if ('leaflet' === lpcMapType) {
            lpcMap.invalidateSize();
        }
    };

    const displayMapOnDisplayRelayDetails = function () {
        let $imgList = $('.lpc_layer_list');
        const $button = $('#lpc_layer_relay_switch_mobile');
        if ($button && $imgList.css('display') !== 'none') {
            // If list mode, display the map
            $imgList.toggleClass('lpc_layer_list_inactive lpc_layer_list_active');
            $('.lpc_layer_map').toggleClass('lpc_layer_list_inactive lpc_layer_list_active');
            $('#lpc_left').toggleClass('lpc_mobile_display_none');
            lpcMapResize();
            if ('leaflet' === lpcMapType) {
                lpcMap.fitBounds([
                    [
                        lowestLatitude,
                        lowestLongitude
                    ],
                    [
                        highestLatitude,
                        highestLongitude
                    ]
                ]);
            }
        }
        if ($button) {
            $('#lpc_layer_relays').closest('.modal-content')[0].scrollTop = 0;
        }
    };

    // Confirm relay choice (WS)
    const lpcAttachOnclickConfirmationRelay = function (relayIndex) {
        const relayClicked = $('#lpc_layer_relay_' + relayIndex);

        if (relayClicked === null) {
            return;
        }

        const lpcRelayIdTmp = relayClicked.find('.lpc_layer_relay_id').text();
        const lpcRelayNameTmp = relayClicked.find('.lpc_layer_relay_name').text();
        const lpcRelayAddressTmp = relayClicked.find('.lpc_layer_relay_address_street').text();
        const lpcRelayCityTmp = relayClicked.find('.lpc_layer_relay_address_city').text();
        const lpcRelayZipcodeTmp = relayClicked.find('.lpc_layer_relay_address_zipcode').text();
        const lpcRelayTypeTmp = relayClicked.find('.lpc_layer_relay_type').text();
        const lpcRelayCountryTmp = relayClicked.find('.lpc_layer_relay_country_code').text();
        const lpcRelayDistanceTmp = relayClicked.find('.lpc_layer_relay_distance_nb').text();
        const lpcRelayHourTmp = relayClicked.find('.lpc_layer_relay_hour').html();

        lpcChooseRelay(lpcRelayIdTmp,
            lpcRelayNameTmp,
            lpcRelayAddressTmp,
            lpcRelayZipcodeTmp,
            lpcRelayCityTmp,
            lpcRelayTypeTmp,
            lpcRelayCountryTmp,
            lpcRelayDistanceTmp,
            lpcRelayHourTmp
        );
    };

    // Apply chosen relay after user confirmation
    const lpcChooseRelay = function (lpcRelayId, lpcRelayName, lpcRelayAddress, lpcRelayZipcode, lpcRelayCity, lpcRelayType, lpcRelayCountry, lpcRelayDistance, lpcRelayHour) {
        lpcSetRelayData(lpcRelayId, lpcRelayName, lpcRelayAddress, lpcRelayCity, lpcRelayZipcode, lpcRelayType, lpcRelayCountry);
        lpcSetSessionRelayInformation(lpcRelayId, lpcRelayName, lpcRelayAddress, lpcRelayZipcode, lpcRelayCity, lpcRelayType, lpcRelayCountry);
        lpcAppendChosenRelay(lpcRelayName, lpcRelayAddress, lpcRelayZipcode, lpcRelayCity, lpcRelayDistance, lpcRelayHour);

        let container = $('#lpc_widget_container');
        if (container.length > 0) {
            try {
                container.frameColissimoClose();
            } catch (e) {
            }
        }
        $('#' + lpcModePR).modal('closeModal');
    };

    // Add relay information in session to use them when validating order
    const lpcSetSessionRelayInformation = function (relayId, relayName, relayAddress, relayZipCode, relayCity, lpcRelayType, relayCountry) {
        if (relayId.length === 0) {
            return;
        }

        $.ajax({
            url: lpcAjaxSetRelayInformationUrl,
            type: 'GET',
            dataType: 'json',
            data: {
                relayId: relayId,
                relayName: relayName,
                relayAddress: relayAddress,
                relayPostCode: relayZipCode,
                relayCity: relayCity,
                relayCountry: relayCountry,
                relayType: lpcRelayType
            },
            complete: function (response) {
            }
        });
    };

    // Add relay information under the shipping method choice
    const lpcAppendChosenRelay = function (nameRelay, addressRelay, zipcodeRelay, cityRelay, distanceRelay, hourRelay, autoSelected = false) {
        let distanceInfo = `<img class="lpc_relay_marker" src="${lpcMapMarker}" alt="" /><p class="lpc_selected_relay_distance">${$.mage.__('At')} ${distanceRelay} m</p>`;
        let openHour = `<div class="tooltip-box" style="margin-left: 5px;"><div class="tooltip-text">${hourRelay}</div></div>`;
        let chosenRelay = `${distanceInfo}<div class="lpc_selected_relay_name">${nameRelay}${openHour}</div>`;
        chosenRelay = `${chosenRelay}<span class="lpc_selected_relay_line">${addressRelay}<br>${zipcodeRelay} ${cityRelay}</span>`;
        if (autoSelected) {
            chosenRelay = `<p class="lpc_closest_relay">${$.mage.__('Closest pickup point:')}</p>${chosenRelay}`;
        }

        $('#lpc_chosen_relay').html(chosenRelay);
        $('#lpc_change_my_relay').text($.mage.__('Modify my relay'));
    };

    // Set relay values
    const lpcSetRelayData = function (lpcRelayIdTmp, lpcRelayNameTmp, lpcRelayAddressTmp, lpcRelayCityTmp, lpcRelayZipcodeTmp, lpcRelayTypeTmp, lpcRelayCountryTmp) {
        lpcRelayId = lpcRelayIdTmp;
        lpcRelayName = lpcRelayNameTmp;
        lpcRelayAddress = lpcRelayAddressTmp;
        lpcRelayCity = lpcRelayCityTmp;
        lpcRelayZipcode = lpcRelayZipcodeTmp;
        lpcRelayType = lpcRelayTypeTmp;
        lpcRelayCountry = lpcRelayCountryTmp;
    };

    return {
        getAutoSelectRelay: function () {
            return lpcAutoSelectRelay;
        },

        setAutoSelectRelay: function (autoSelectRelay) {
            lpcAutoSelectRelay = autoSelectRelay;
        },

        lpcSetMapType: function (type) {
            lpcMapType = type;
        },

        lpcSetMapMarker: function (url) {
            lpcMapMarker = url;
        },

        intiSwitchMobileLayout: function () {
            const $mapContainer = $('#lpc_left');
            const $button = $('#lpc_layer_relay_switch_mobile');

            if (!$button) {
                return;
            }

            $button.on('click', function () {
                let $contentZone = $('#lpc_layer_relays').closest('.modal-content');
                let $imgList = $('.lpc_layer_list');
                let $imgMap = $('.lpc_layer_map');
                $mapContainer.toggleClass('lpc_mobile_display_none');
                $contentZone[0].scrollTop = 0;

                $imgList.toggleClass('lpc_layer_list_inactive lpc_layer_list_active');
                $imgMap.toggleClass('lpc_layer_list_inactive lpc_layer_list_active');

                lpcMapResize();
                if ('leaflet' === lpcMapType) {
                    lpcMap.fitBounds([
                        [
                            lowestLatitude,
                            lowestLongitude
                        ],
                        [
                            highestLatitude,
                            highestLongitude
                        ]
                    ]);
                }
            });
        },

        lpcLoadMap: function () {
            if ('gmaps' === lpcMapType) {
                lpcGoogleMap = new google.maps.Map(document.getElementById('lpc_map'), {
                    zoom: 10,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    center: {
                        lat: 48.866667,
                        lng: 2.333333
                    },
                    disableDefaultUI: true
                });
            } else if ('leaflet' === lpcMapType) {
                lpcMap = L.map('lpc_map').setView([
                    48.866667,
                    2.333333
                ], 14);
                // Default map for open street map: https://tile.openstreetmap.org/{z}/{x}/{y}.png
                L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>'
                }).addTo(lpcMap);
            }
        },

        // create modal to display relay choice
        lpcOpenPopupAndMap: function (modal) {
            const shippingAddress = {
                street: $('#order-shipping_address_street0').length === 0 ? '' : $('#order-shipping_address_street0').val(),
                postcode: $('#order-shipping_address_postcode').length === 0 ? '' : $('#order-shipping_address_postcode').val(),
                city: $('#order-shipping_address_city').length === 0 ? '' : $('#order-shipping_address_city').val(),
                countryId: $('#order-shipping_address_country_id').length === 0 ? '' : $('#order-shipping_address_country_id').val()
            };

            if ($('#lpc_layer_relays').length) {
                lpcModePR = 'lpc_layer_relays';

                const modalOptions = {
                    buttons: [],
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'lpc_modal_relays'
                };

                const $divPopupLpc = $('#lpc_layer_relays');
                const popup = modal(modalOptions, $divPopupLpc);

                popup.openModal();

                $('#lpc_modal_relays_search_address').val(shippingAddress.street);
                $('#lpc_modal_relays_search_zipcode').val(shippingAddress.postcode);
                $('#lpc_modal_relays_search_city').val(shippingAddress.city);

                $('#lpc_layer_button_search').click();

                lpcMapResize();
            } else if ($('#lpc_layer_widget').length) {
                lpcModePR = 'lpc_layer_widget';

                const modalOptions = {
                    buttons: [],
                    responsive: true,
                    wrapperClass: 'modals-wrapper lpc_modals-wrapper',
                    modalVisibleClass: '_show _show_lpc_relay',
                    closed: function (e) {
                        let container = $('#lpc_widget_container');
                        if (container.length > 0) {
                            try {
                                container.frameColissimoClose();
                            } catch (e) {
                            }
                        }
                    }
                };

                const $divPopupLpc = $('#lpc_layer_widget');
                const popup = modal(modalOptions, $divPopupLpc);

                popup.openModal();

                const widgetOptions = {
                    'ceLang': 'FR',
                    'ceCountryList': lpcWidgetRelayCountries,
                    'ceCountry': shippingAddress.countryId,
                    'dyPreparationTime': $('#lpc_average_preparation_delay').val() == '' ? '1' : $('#lpc_average_preparation_delay').val(),
                    'ceAddress': shippingAddress.street,
                    'ceZipCode': shippingAddress.postcode,
                    'ceTown': shippingAddress.city,
                    'token': $('#lpc_token_widget').val(),
                    'URLColissimo': 'https://ws.colissimo.fr',
                    'callBackFrame': 'lpcCallBackFrame',
                    'dyWeight': '19000'
                };

                if ($('#lpc_color_1').length > 0) {
                    widgetOptions.couleur1 = $('#lpc_color_1').val();
                    widgetOptions.couleur2 = $('#lpc_color_2').val();
                    widgetOptions.font = $('#lpc_font').val();
                }

                $('#lpc_widget_container').frameColissimoOpen(widgetOptions);
            } else if ($('#lpc_error_pr').length) {
                lpcModePR = 'lpc_error_pr';

                const modalOptions = {
                    buttons: [],
                    responsive: true
                };

                const $divPopupLpcError = $('#lpc_error_pr');
                const popup = modal(modalOptions, $divPopupLpcError);

                popup.openModal();
            }
        },

        setWebserviceRelayUrl: function (ajaxUrlLoadRelay) {
            lpcAjaxUrlLoadRelay = ajaxUrlLoadRelay;
        },

        // Load relays for an address
        lpcLoadRelaysList: function (loadForPopup, loadMore = false) {
            let address, zipcode, city, countryId;

            if (loadForPopup) {
                address = $('#lpc_modal_relays_search_address').val();
                zipcode = $('#lpc_modal_relays_search_zipcode').val();
                city = $('#lpc_modal_relays_search_city').val();
                countryId = $('#order-shipping_address_country_id').length === 0 ? '' : $('#order-shipping_address_country_id').val();
            } else {
                address = $('#order-shipping_address_street0').length === 0 ? '' : $('#order-shipping_address_street0').val();
                zipcode = $('#order-shipping_address_postcode').length === 0 ? '' : $('#order-shipping_address_postcode').val();
                city = $('#order-shipping_address_city').length === 0 ? '' : $('#order-shipping_address_city').val();
                countryId = $('#order-shipping_address_country_id').length === 0 ? '' : $('#order-shipping_address_country_id').val();
            }

            const $errorDiv = $('#lpc_layer_error_message');
            const $listRelaysDiv = $('#lpc_layer_list_relays');
            const $loader = $('#lpc_layer_relays_loader');

            const addressData = {
                address: address,
                zipCode: zipcode,
                city: city,
                countryId: countryId,
                loadMore: loadMore ? 1 : 0
            };

            $.ajax({
                url: lpcAjaxUrlLoadRelay,
                type: 'GET',
                dataType: 'json',
                data: addressData,
                beforeSend: function () {
                    if ($loader) {
                        $errorDiv.hide();
                        $listRelaysDiv.hide();
                        $loader.show();
                    }
                },
                success: function (response) {
                    if (loadForPopup) {
                        $loader.hide();
                        if (response.success == '1') {
                            $listRelaysDiv.html(response.html);
                            $listRelaysDiv.show();
                            lpcShowRelaysMap(addressData);
                            lpcMapResize();
                        } else {
                            $errorDiv.html(response.error);
                            $errorDiv.show();
                        }

                        const $loadMoreButton = $('#lpc_modal_relays_display_more');
                        if (response.loadMore && $loadMoreButton.length !== 0) {
                            $loadMoreButton.hide();
                        } else {
                            $loadMoreButton.show();
                        }
                    } else {
                        $('<div>')
                            .attr('id', 'lpc_default_relay_factory')
                            .css('display', 'none')
                            .html(response.html)
                            .appendTo('#colissimo_pickup_button_section .lpc_add_data_button');
                        const $firstRelay = $('#lpc_default_relay_factory #lpc_layer_relay_0');

                        if ($firstRelay.length) {
                            const relayId = $firstRelay.find('.lpc_layer_relay_id').text();
                            const relayType = $firstRelay.find('.lpc_layer_relay_type').text();
                            const relayName = $firstRelay.find('.lpc_layer_relay_name').text();
                            const relayAddress = $firstRelay.find('.lpc_layer_relay_address_street').text();
                            const relayZipcode = $firstRelay.find('.lpc_layer_relay_address_zipcode').text();
                            const relayCity = $firstRelay.find('.lpc_layer_relay_address_city').text();
                            const relayCountry = $firstRelay.find('.lpc_layer_relay_country_code').text();
                            const relayDistance = $firstRelay.find('.lpc_layer_relay_distance_nb').text();
                            const relayHour = $firstRelay.find('.lpc_layer_relay_hour').html();

                            lpcSetRelayData(relayId, relayName, relayAddress, relayCity, relayZipcode, relayType, relayCountry);
                            lpcSetSessionRelayInformation(relayId, relayName, relayAddress, relayZipcode, relayCity, relayType, relayCountry);
                            lpcAppendChosenRelay(relayName, relayAddress, relayZipcode, relayCity, relayDistance, relayHour, true);
                        }

                        $('#lpc_default_relay_factory').remove();
                    }
                }
            });
        },

        lpcSetAjaxSetRelayInformationUrl: function (AjaxSetRelayInformationUrl) {
            lpcAjaxSetRelayInformationUrl = AjaxSetRelayInformationUrl;
        },

        lpcPublicSetRelayId: function (relayId) {
            lpcRelayId = relayId;
        },

        lpcGetRelayId: function () {
            return lpcRelayId;
        },

        lpcGetRelayName: function () {
            return lpcRelayName;
        },

        lpcGetRelayCity: function () {
            return lpcRelayCity;
        },

        lpcGetRelayAddress: function () {
            return lpcRelayAddress;
        },

        lpcGetRelayZipcode: function () {
            return lpcRelayZipcode;
        },

        lpcGetRelayCountry: function () {
            return lpcRelayCountry;
        },

        // Apply relay chosen with widget method
        lpcCallBackFrame: function (point) {
            const lpcRelayIdTmp = point['identifiant'];
            const lpcRelayNameTmp = point['nom'];
            const lpcRelayAddressTmp = point['adresse1'];
            const lpcRelayZipcodeTmp = point['codePostal'];
            const lpcRelayCityTmp = point['localite'];
            const lpcRelayTypeTmp = point['typeDePoint'];
            const lpcRelayCountryTmp = point['codePays'];

            lpcChooseRelay(lpcRelayIdTmp, lpcRelayNameTmp, lpcRelayAddressTmp, lpcRelayZipcodeTmp, lpcRelayCityTmp, lpcRelayTypeTmp, lpcRelayCountryTmp);
        },

        lpcSetWidgetRelayCountries: function (relayCountries) {
            lpcWidgetRelayCountries = relayCountries;
        }
    };
});
