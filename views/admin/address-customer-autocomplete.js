/**
 * Mejoras SJB en ficha nativa de dirección:
 * - Autocomplete cliente (email oculto)
 * - Autocomplete provincia (select estado oculto)
 * - Alias por defecto en alta
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        initSjbCustomerAutocomplete();
        initSjbStateAutocomplete();
        initSjbDefaultAlias();
        initSjbHideAddress2();
    });

    function initSjbCustomerAutocomplete() {
        var $email = $('#customer_address_customer_email');
        if (!$email.length || typeof sjbAddressCustomerSearch === 'undefined') {
            return;
        }

        var cfg = sjbAddressCustomerSearch;
        var $formGroup = $email.closest('.form-group');
        $formGroup.addClass('sjb-customer-email-hidden');

        var $block = buildSearchWidget({
            id: 'sjb_customer_search',
            resultsId: 'sjb_customer_results',
            label: cfg.label,
            labelIcon: 'person_search',
            placeholder: cfg.placeholder,
            help: cfg.help
        });
        $formGroup.before($block);

        bindRemoteSearch($block, {
            inputId: 'sjb_customer_search',
            resultsId: 'sjb_customer_results',
            minChars: 2,
            search: function (q, done, fail) {
                $.getJSON(cfg.searchUrl, { customer_search: q, sf2: 1 })
                    .done(function (data) {
                        var list = [];
                        if (data && typeof data === 'object') {
                            Object.keys(data).forEach(function (key) {
                                list.push(data[key]);
                            });
                        }
                        done(list);
                    })
                    .fail(fail);
            },
            renderItem: function (c) {
                var name = $.trim((c.firstname || '') + ' ' + (c.lastname || ''));
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
                $item.find('.sjb-ac-item-email').text(c.email || '');
                $item.find('.sjb-ac-item-badge').text('idCliente: ' + c.id_customer);
                return $item;
            },
            onSelect: function (c, $input, $blockEl, $addonIcon) {
                $email.val(c.email || '').trigger('change');
                $input.val(
                    $.trim((c.firstname || '') + ' ' + (c.lastname || '')) + ' — ' + (c.email || '')
                );
                $blockEl.addClass('sjb-ac-selected');
                $addonIcon.text('check_circle');
            },
            emptyText: cfg.noResults,
            errorText: cfg.error
        });
    }

    function initSjbStateAutocomplete() {
        var $state = $('#customer_address_id_state');
        if (!$state.length || typeof sjbAddressStateSearch === 'undefined') {
            return;
        }

        var cfg = sjbAddressStateSearch;
        // En PS, .js-address-state-select está no propio form-group do select:
        // non podemos meter o widget dentro e ocultar o grupo (ocultaríase tamén).
        var $nativeGroup = $state.closest('.form-group');

        var $block = buildSearchWidget({
            id: 'sjb_state_search',
            resultsId: 'sjb_state_results',
            label: cfg.label,
            labelIcon: 'map',
            placeholder: cfg.placeholder,
            help: cfg.help
        });
        $nativeGroup.before($block);
        $nativeGroup.addClass('sjb-state-native-hidden');

        var $input = $('#sjb_state_search');
        var $addonIcon = $block.find('.sjb-ac-search-icon');

        function hasStateOptions() {
            return $state.find('option').filter(function () {
                return $(this).val() !== '' && $(this).val() != null;
            }).length > 0;
        }

        function refreshVisibility() {
            if (hasStateOptions()) {
                $block.show();
            } else {
                $block.hide();
                $input.val('');
                $block.removeClass('sjb-ac-selected');
                $addonIcon.text('search');
                $('#sjb_state_results').hide().empty();
            }
        }

        refreshVisibility();
        syncStateInputFromSelect($state, $input, $block, $addonIcon);

        bindLocalSearch($block, {
            inputId: 'sjb_state_search',
            resultsId: 'sjb_state_results',
            minChars: 0,
            getItems: function () {
                var items = [];
                $state.find('option').each(function () {
                    var id = $(this).val();
                    var name = $.trim($(this).text());
                    if (id && name) {
                        items.push({ id: id, name: name });
                    }
                });
                return items;
            },
            match: function (item, q) {
                if (!q) {
                    return true;
                }
                return item.name.toLowerCase().indexOf(q.toLowerCase()) !== -1;
            },
            renderItem: function (item) {
                var $item = $(
                    '<a href="#" class="list-group-item list-group-item-action sjb-ac-item">' +
                        '<span class="sjb-ac-item-main">' +
                            '<span class="sjb-ac-item-name"></span>' +
                        '</span>' +
                    '</a>'
                );
                $item.find('.sjb-ac-item-name').text(item.name);
                return $item;
            },
            onSelect: function (item, $inp, $blockEl, $icon) {
                $state.val(item.id).trigger('change');
                $inp.val(item.name);
                $blockEl.addClass('sjb-ac-selected');
                $icon.text('check_circle');
            },
            emptyText: cfg.noResults
        });

        // Al cambiar país, PS recarga opciones do select
        $('#customer_address_id_country').on('change', function () {
            $input.val('');
            $block.removeClass('sjb-ac-selected');
            $addonIcon.text('search');
            $('#sjb_state_results').hide().empty();

            var tries = 0;
            var waitOptions = setInterval(function () {
                tries++;
                if (tries >= 3 || tries > 20) {
                    clearInterval(waitOptions);
                    refreshVisibility();
                    syncStateInputFromSelect($state, $input, $block, $addonIcon);
                }
            }, 200);
        });
    }

    function initSjbDefaultAlias() {
        if (typeof sjbAddressAliasDefault === 'undefined') {
            return;
        }
        var $alias = $('#customer_address_alias');
        if (!$alias.length) {
            return;
        }
        var isNew = /\/sell\/addresses\/new/.test(window.location.href)
            || /\/addresses\/new/.test(window.location.pathname);
        if (isNew && !$.trim($alias.val())) {
            $alias.val(sjbAddressAliasDefault);
        }
    }

    function initSjbHideAddress2() {
        var $address2 = $('#customer_address_address2');
        if ($address2.length) {
            $address2.closest('.form-group').addClass('sjb-address2-hidden');
        }
    }

    function syncStateInputFromSelect($state, $input, $block, $addonIcon) {
        var val = $state.val();
        if (!val) {
            return;
        }
        var text = $.trim($state.find('option:selected').text());
        if (text) {
            $input.val(text);
            $block.addClass('sjb-ac-selected');
            $addonIcon.text('check_circle');
        }
    }

    function buildSearchWidget(opts) {
        return $(
            '<div class="form-group row sjb-ac-widget">' +
                '<label class="form-control-label" for="' + opts.id + '">' +
                    '<i class="material-icons sjb-ac-label-icon">' + opts.labelIcon + '</i> ' + opts.label +
                '</label>' +
                '<div class="col-sm">' +
                    '<div class="input-group sjb-ac-input-group">' +
                        '<input type="text" class="form-control sjb-ac-input" id="' + opts.id + '" ' +
                            'autocomplete="off" placeholder="' + opts.placeholder + '">' +
                        '<div class="input-group-append">' +
                            '<span class="input-group-text sjb-ac-addon" aria-hidden="true">' +
                                '<i class="material-icons sjb-ac-search-icon">search</i>' +
                            '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div id="' + opts.resultsId + '" class="list-group sjb-customer-results" style="display:none;"></div>' +
                    '<small class="form-text text-muted sjb-ac-help">' +
                        '<i class="material-icons sjb-ac-help-icon">info</i> ' + opts.help +
                    '</small>' +
                '</div>' +
            '</div>'
        );
    }

    function bindRemoteSearch($block, opts) {
        var $input = $('#' + opts.inputId);
        var $results = $('#' + opts.resultsId);
        var $addonIcon = $block.find('.sjb-ac-search-icon');
        var timer = null;

        $input.on('input', function () {
            clearTimeout(timer);
            var q = $.trim($input.val());
            $block.removeClass('sjb-ac-selected');
            $addonIcon.text('search');
            if (q.length < opts.minChars) {
                $results.hide().empty();
                return;
            }
            timer = setTimeout(function () {
                $block.addClass('sjb-ac-loading');
                $addonIcon.text('hourglass_empty');
                opts.search(
                    q,
                    function (list) {
                        renderList($results, list, opts, $input, $block, $addonIcon);
                        $block.removeClass('sjb-ac-loading');
                        $addonIcon.text('search');
                    },
                    function () {
                        $results.empty().append(
                            '<div class="list-group-item sjb-ac-error">' +
                                '<i class="material-icons">error_outline</i> ' + opts.errorText +
                            '</div>'
                        ).show();
                        $block.removeClass('sjb-ac-loading');
                        $addonIcon.text('search');
                    }
                );
            }, 300);
        });

        bindFocusAndOutsideClick($block, $input, $results);
    }

    function bindLocalSearch($block, opts) {
        var $input = $('#' + opts.inputId);
        var $results = $('#' + opts.resultsId);
        var $addonIcon = $block.find('.sjb-ac-search-icon');
        var timer = null;

        function runFilter() {
            var q = $.trim($input.val());
            var list = opts.getItems().filter(function (item) {
                return opts.match(item, q);
            });
            renderList($results, list, opts, $input, $block, $addonIcon);
        }

        $input.on('input', function () {
            clearTimeout(timer);
            $block.removeClass('sjb-ac-selected');
            $addonIcon.text('search');
            timer = setTimeout(runFilter, 150);
        });

        $input.on('focus', function () {
            runFilter();
        });

        bindFocusAndOutsideClick($block, $input, $results);
    }

    function renderList($results, list, opts, $input, $block, $addonIcon) {
        $results.empty();
        if (!list.length) {
            $results.append(
                '<div class="list-group-item sjb-ac-empty">' +
                    '<i class="material-icons">search_off</i> ' + opts.emptyText +
                '</div>'
            ).show();
            return;
        }
        list.forEach(function (item) {
            var $item = opts.renderItem(item);
            $item.on('click', function (e) {
                e.preventDefault();
                opts.onSelect(item, $input, $block, $addonIcon);
                $results.hide().empty();
            });
            $results.append($item);
        });
        $results.show();
    }

    function bindFocusAndOutsideClick($block, $input, $results) {
        $input.on('focus', function () {
            if ($results.children().length) {
                $results.show();
            }
        });
        $(document).on('click.sjbAc' + $input.attr('id'), function (e) {
            if (!$(e.target).closest($block).length) {
                $results.hide();
            }
        });
    }
})(window.jQuery);
