/**
 * Autocomplete de cliente en alta de dirección (ficha nativa).
 * Oculta #customer_address_customer_email y lo rellena al elegir un resultado.
 */
(function ($) {
    'use strict';

    function initSjbCustomerAutocomplete() {
        var $email = $('#customer_address_customer_email');
        if (!$email.length || typeof sjbAddressCustomerSearch === 'undefined') {
            return;
        }

        var cfg = sjbAddressCustomerSearch;
        var $formGroup = $email.closest('.form-group');
        $formGroup.addClass('sjb-customer-email-hidden');

        var $block = $(
            '<div class="form-group row sjb-customer-autocomplete">' +
                '<label class="form-control-label" for="sjb_customer_search">' +
                    '<i class="material-icons sjb-ac-label-icon">person_search</i> ' + cfg.label +
                '</label>' +
                '<div class="col-sm">' +
                    '<div class="input-group sjb-ac-input-group">' +
                        '<input type="text" class="form-control sjb-ac-input" id="sjb_customer_search" ' +
                            'autocomplete="off" placeholder="' + cfg.placeholder + '">' +
                        '<div class="input-group-append">' +
                            '<span class="input-group-text sjb-ac-addon" aria-hidden="true">' +
                                '<i class="material-icons sjb-ac-search-icon">search</i>' +
                            '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div id="sjb_customer_results" class="list-group sjb-customer-results" style="display:none;"></div>' +
                    '<small class="form-text text-muted sjb-ac-help">' +
                        '<i class="material-icons sjb-ac-help-icon">info</i> ' + cfg.help +
                    '</small>' +
                '</div>' +
            '</div>'
        );
        $formGroup.before($block);

        var $input = $('#sjb_customer_search');
        var $results = $('#sjb_customer_results');
        var $addonIcon = $block.find('.sjb-ac-search-icon');
        var timer = null;

        $input.on('input', function () {
            clearTimeout(timer);
            var q = $.trim($input.val());
            $block.removeClass('sjb-ac-selected');
            if (q.length < 2) {
                $results.hide().empty();
                setSearching(false);
                return;
            }
            timer = setTimeout(function () {
                searchCustomers(q);
            }, 300);
        });

        $input.on('focus', function () {
            if ($results.children().length) {
                $results.show();
            }
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.sjb-customer-autocomplete').length) {
                $results.hide();
            }
        });

        function setSearching(on) {
            $block.toggleClass('sjb-ac-loading', !!on);
            $addonIcon.text(on ? 'hourglass_empty' : 'search');
        }

        function searchCustomers(q) {
            setSearching(true);
            $.getJSON(cfg.searchUrl, {
                customer_search: q,
                sf2: 1
            })
                .done(function (data) {
                    $results.empty();
                    var list = [];
                    if (data && typeof data === 'object') {
                        Object.keys(data).forEach(function (key) {
                            list.push(data[key]);
                        });
                    }
                    if (!list.length) {
                        $results.append(
                            '<div class="list-group-item sjb-ac-empty">' +
                                '<i class="material-icons">search_off</i> ' + cfg.noResults +
                            '</div>'
                        ).show();
                        return;
                    }
                    list.forEach(function (c) {
                        var name = $.trim((c.firstname || '') + ' ' + (c.lastname || ''));
                        var email = c.email || '';
                        var $item = $(
                            '<a href="#" class="list-group-item list-group-item-action sjb-ac-item">' +
                                '<span class="sjb-ac-item-main">' +
                                    '<span class="sjb-ac-item-name"></span>' +
                                    '<span class="sjb-ac-item-email"></span>' +
                                '</span>' +
                                '<span class="badge badge-info sjb-ac-item-badge"></span>' +
                            '</a>'
                        );
                        $item.find('.sjb-ac-item-name').text(name);
                        $item.find('.sjb-ac-item-email').text(email);
                        $item.find('.sjb-ac-item-badge').text('idCliente: ' + c.id_customer);
                        $item.on('click', function (e) {
                            e.preventDefault();
                            selectCustomer(c);
                        });
                        $results.append($item);
                    });
                    $results.show();
                })
                .fail(function () {
                    $results.empty().append(
                        '<div class="list-group-item sjb-ac-error">' +
                            '<i class="material-icons">error_outline</i> ' + cfg.error +
                        '</div>'
                    ).show();
                })
                .always(function () {
                    setSearching(false);
                });
        }

        function selectCustomer(c) {
            $email.val(c.email || '').trigger('change');
            $input.val(
                $.trim((c.firstname || '') + ' ' + (c.lastname || '')) + ' — ' + (c.email || '')
            );
            $block.addClass('sjb-ac-selected');
            $addonIcon.text('check_circle');
            $results.hide().empty();
        }
    }

    $(document).ready(initSjbCustomerAutocomplete);
})(window.jQuery);
