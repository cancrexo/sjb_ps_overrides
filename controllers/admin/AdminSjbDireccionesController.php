<?php
/**
 * Listado SJB de direcciones.
 *
 * @author Daniel "Cancrexo" Prol <cancrexo@gmail.com>
 */

class AdminSjbDireccionesController extends ModuleAdminController
{
    /** @var int Filtro opcional desde «Ver direcciones» */
    protected $idCustomerFilter = 0;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'address';
        $this->className = 'Address';
        $this->identifier = 'id_address';
        $this->lang = false;
        $this->explicitSelect = true;
        $this->_defaultOrderBy = 'id_address';
        $this->_defaultOrderWay = 'DESC';
        $this->allow_export = false;
        $this->list_no_link = true;

        parent::__construct();

        $this->idCustomerFilter = (int) Tools::getValue('id_customer');

        // Mantener el filtro en paginación / búsqueda del HelperList
        if ($this->idCustomerFilter) {
            self::$currentIndex .= '&id_customer=' . $this->idCustomerFilter;
        }

        $this->meta_title = $this->toolbar_title = $this->l('SJB direcciones');
        if ($this->idCustomerFilter) {
            $this->toolbar_title = sprintf(
                $this->l('SJB direcciones — cliente %d'),
                $this->idCustomerFilter
            );
            $this->meta_title = $this->toolbar_title;
        }

        $idLang = (int) $this->context->language->id;

        // customer_filter: busca por nombre cliente, nombre dirección, email e id_customer
        // destinatario: busca por nombre dirección, NIF y VAT
        // telefono: busca por phone y phone_mobile
        // direccion: busca por address1 y postcode
        $this->_select = 'a.`id_address`, a.`id_customer`,'
            . ' a.`firstname` AS `address_firstname`, a.`lastname` AS `address_lastname`,'
            . ' a.`dni`, a.`vat_number`, a.`phone`, a.`phone_mobile`,'
            . ' a.`address1`, a.`postcode`, a.`city`,'
            . ' c.`firstname` AS `customer_firstname`, c.`lastname` AS `customer_lastname`,'
            . ' c.`email` AS `customer_email`,'
            . ' cl.`name` AS `country_name`,'
            . ' CONCAT('
            . ' IFNULL(a.`firstname`, \'\'), \' \','
            . ' IFNULL(a.`lastname`, \'\'), \' \','
            . ' IFNULL(a.`dni`, \'\'), \' \','
            . ' IFNULL(a.`vat_number`, \'\')'
            . ' ) AS `destinatario`,'
            . ' CONCAT(IFNULL(a.`phone`, \'\'), \' \', IFNULL(a.`phone_mobile`, \'\')) AS `telefono`,'
            . ' CONCAT(IFNULL(a.`address1`, \'\'), \' \', IFNULL(a.`postcode`, \'\')) AS `direccion`,'
            . ' CONCAT('
            . ' IFNULL(c.`firstname`, \'\'), \' \','
            . ' IFNULL(c.`lastname`, \'\'), \' \','
            . ' IFNULL(a.`firstname`, \'\'), \' \','
            . ' IFNULL(a.`lastname`, \'\'), \' \','
            . ' IFNULL(c.`email`, \'\'), \' \','
            . ' a.`id_customer`'
            . ' ) AS `customer_filter`';

        $this->_join = ' LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.`id_customer` = a.`id_customer`)'
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl'
            . ' ON (cl.`id_country` = a.`id_country` AND cl.`id_lang` = ' . $idLang . ')';

        $this->_where = ' AND a.`deleted` = 0 AND a.`id_customer` != 0';

        if ($this->idCustomerFilter) {
            $this->_where .= ' AND a.`id_customer` = ' . $this->idCustomerFilter;
        }

        if (Shop::isFeatureActive() && Shop::getContext() === Shop::CONTEXT_SHOP) {
            $this->_where .= ' AND c.`id_shop` = ' . (int) $this->context->shop->id;
        }

        $this->fields_list = [
            'id_address' => [
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ],
            'customer_filter' => [
                'title' => $this->l('Cliente'),
                'callback' => 'displayCustomerInfo',
                'havingFilter' => true,
            ],
            'destinatario' => [
                'title' => $this->l('Destinatario'),
                'callback' => 'displayDestinatarioInfo',
                'havingFilter' => true,
            ],
            'telefono' => [
                'title' => $this->l('Teléfono'),
                'callback' => 'displayTelefonoInfo',
                'havingFilter' => true,
            ],
            'direccion' => [
                'title' => $this->l('Dirección'),
                'callback' => 'displayDireccionInfo',
                'havingFilter' => true,
            ],
            'city' => [
                'title' => $this->l('Ciudad'),
                'filter_key' => 'a!city',
            ],
            'country_name' => [
                'title' => $this->l('País'),
                'filter_key' => 'cl!name',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Eliminar seleccionadas'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('¿Eliminar las direcciones seleccionadas?'),
            ],
        ];
    }

    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $href = $this->context->link->getAdminLink('AdminAddresses', true, [
                'route' => 'admin_addresses_create',
            ]);
            if ($this->idCustomerFilter) {
                $sep = (strpos($href, '?') !== false) ? '&' : '?';
                $href .= $sep . 'id_customer=' . $this->idCustomerFilter;
            }

            $this->page_header_toolbar_btn['new_address'] = [
                'href' => $this->withBackToSjbList($href),
                'desc' => $this->l('Añadir dirección'),
                'icon' => 'process-icon-new',
            ];

            if ($this->idCustomerFilter) {
                $this->page_header_toolbar_btn['back_customers'] = [
                    'href' => $this->context->link->getAdminLink('AdminSjbClientes'),
                    'desc' => $this->l('Volver a SJB clientes'),
                    'icon' => 'process-icon-back',
                ];
            }
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Columna Cliente: nombre, email y badge con ID.
     * $value es el alias customer_filter (CONCAT); el ID va en $tr.
     *
     * @param mixed $value
     * @param array $tr
     * @return string
     */
    public function displayCustomerInfo($value, $tr)
    {
        $id = isset($tr['id_customer']) ? (int) $tr['id_customer'] : 0;
        $nombre = trim(
            (isset($tr['customer_firstname']) ? $tr['customer_firstname'] : '') . ' '
            . (isset($tr['customer_lastname']) ? $tr['customer_lastname'] : '')
        );
        $email = isset($tr['customer_email']) ? $tr['customer_email'] : '';

        $html = '<strong>' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '</strong>';
        if ($email !== '') {
            $html .= '<br><small>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</small>';
        }
        $html .= '<br><span class="badge badge-info">idCliente: ' . $id . '</span>';

        return $html;
    }

    /**
     * Columna Destinatario: nombre dirección, NIF y VAT.
     *
     * @param mixed $value
     * @param array $tr
     * @return string
     */
    public function displayDestinatarioInfo($value, $tr)
    {
        $nombre = trim(
            (isset($tr['address_firstname']) ? $tr['address_firstname'] : '') . ' '
            . (isset($tr['address_lastname']) ? $tr['address_lastname'] : '')
        );
        $dni = isset($tr['dni']) ? trim($tr['dni']) : '';
        $vat = isset($tr['vat_number']) ? trim($tr['vat_number']) : '';

        $html = '<strong>' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '</strong>';
        if ($dni !== '') {
            $html .= '<br><span class="badge badge-success" style="display:inline-block;margin-top:3px;">NIF: '
                . htmlspecialchars($dni, ENT_QUOTES, 'UTF-8') . '</span>';
        }
        if ($vat !== '') {
            $html .= '<br><span class="badge badge-primary" style="display:inline-block;margin-top:3px;">VAT: '
                . htmlspecialchars($vat, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        return $html;
    }

    /**
     * Columna Teléfono: fijo y móvil de la dirección.
     *
     * @param mixed $value
     * @param array $tr
     * @return string
     */
    public function displayTelefonoInfo($value, $tr)
    {
        $phone = isset($tr['phone']) ? trim($tr['phone']) : '';
        $mobile = isset($tr['phone_mobile']) ? trim($tr['phone_mobile']) : '';
        $lineas = [];

        if ($phone !== '') {
            $lineas[] = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
        }
        if ($mobile !== '') {
            $lineas[] = htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8');
        }

        return $lineas ? implode('<br>', $lineas) : '';
    }

    /**
     * Columna Dirección: calle y código postal.
     *
     * @param mixed $value
     * @param array $tr
     * @return string
     */
    public function displayDireccionInfo($value, $tr)
    {
        $calle = isset($tr['address1']) ? trim($tr['address1']) : '';
        $cp = isset($tr['postcode']) ? trim($tr['postcode']) : '';

        $html = htmlspecialchars($calle, ENT_QUOTES, 'UTF-8');
        if ($cp !== '') {
            $html .= ($html !== '' ? '<br>' : '')
                . '<span class="badge badge-primary">CP: '
                . htmlspecialchars($cp, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        return $html;
    }

    /**
     * Editar -> ficha nativa Symfony (con back a SJB direcciones).
     */
    public function displayEditLink($token = null, $id = 0, $name = null)
    {
        $href = $this->context->link->getAdminLink('AdminAddresses', true, [
            'route' => 'admin_addresses_edit',
            'addressId' => (int) $id,
        ]);
        $href = $this->withBackToSjbList($href);

        return '<a class="btn btn-default" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" title="'
            . $this->l('Editar') . '"><i class="icon-pencil"></i> ' . $this->l('Editar') . '</a>';
    }

    /**
     * Añade ?back= al listado SJB (conserva id_customer si hay filtro).
     *
     * @param string $url
     * @return string
     */
    private function withBackToSjbList($url)
    {
        $back = $this->context->link->getAdminLink('AdminSjbDirecciones');
        if ($this->idCustomerFilter) {
            $back .= '&id_customer=' . $this->idCustomerFilter;
        }
        $sep = (strpos($url, '?') !== false) ? '&' : '?';

        return $url . $sep . 'back=' . rawurlencode($back);
    }
}
