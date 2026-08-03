<?php
/**
 * Listado SJB de clientes.
 *
 * @author Daniel "Cancrexo" Prol <cancrexo@gmail.com>
 */

class AdminSjbClientesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'customer';
        $this->className = 'Customer';
        $this->identifier = 'id_customer';
        $this->lang = false;
        $this->explicitSelect = true;
        $this->_defaultOrderBy = 'id_customer';
        $this->_defaultOrderWay = 'DESC';
        $this->allow_export = false;
        $this->list_no_link = true;

        parent::__construct();

        $this->meta_title = $this->toolbar_title = $this->l('SJB clientes');

        $this->_select = 'a.`id_customer`, a.`firstname`, a.`lastname`, a.`email`, a.`active`, a.`date_add`,'
            . ' (SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'address` adr'
            . ' WHERE adr.`id_customer` = a.`id_customer` AND adr.`deleted` = 0) AS `nb_addresses`';

        $this->_where = ' AND a.`deleted` = 0';

        if (Shop::isFeatureActive() && Shop::getContext() === Shop::CONTEXT_SHOP) {
            $this->_where .= ' AND a.`id_shop` = ' . (int) $this->context->shop->id;
        }

        $this->fields_list = [
            'id_customer' => [
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ],
            'firstname' => [
                'title' => $this->l('Nombre'),
                'filter_key' => 'a!firstname',
            ],
            'lastname' => [
                'title' => $this->l('Apellidos'),
                'filter_key' => 'a!lastname',
            ],
            'email' => [
                'title' => $this->l('Email'),
                'filter_key' => 'a!email',
            ],
            'active' => [
                'title' => $this->l('Activo'),
                'align' => 'text-center',
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-sm',
                'orderby' => false,
            ],
            'date_add' => [
                'title' => $this->l('Fecha registro'),
                'type' => 'datetime',
                'filter_key' => 'a!date_add',
            ],
            'nb_addresses' => [
                'title' => $this->l('Direcciones'),
                'align' => 'text-center',
                'class' => 'fixed-width-sm',
                'search' => false,
                'callback' => 'displayNbAddressesBadge',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Eliminar seleccionados'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('¿Eliminar los clientes seleccionados?'),
            ],
        ];
    }

    public function renderList()
    {
        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('viewaddresses');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    /**
     * Badge nativo: success si hay direcciones, danger si no.
     *
     * @param mixed $value
     * @param array $tr
     * @return string
     */
    public function displayNbAddressesBadge($value, $tr)
    {
        $n = (int) $value;
        $class = $n > 0 ? 'badge-success' : 'badge-danger';

        return '<span class="badge ' . $class . '">' . $n . '</span>';
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $href = $this->context->link->getAdminLink('AdminCustomers', true, [
                'route' => 'admin_customers_create',
            ]);
            $this->page_header_toolbar_btn['new_customer'] = [
                'href' => $this->withBackToSjbList($href),
                'desc' => $this->l('Añadir cliente'),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Enlace Ver -> ficha nativa Symfony
     */
    public function displayViewLink($token = null, $id = 0, $name = null)
    {
        $href = $this->context->link->getAdminLink('AdminCustomers', true, [
            'route' => 'admin_customers_view',
            'customerId' => (int) $id,
        ]);
        $href = $this->withBackToSjbList($href);

        return '<a class="btn btn-default" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" title="'
            . $this->l('Ver') . '"><i class="icon-search-plus"></i> ' . $this->l('Ver') . '</a>';
    }

    /**
     * Enlace Editar -> edición nativa Symfony (con back a SJB clientes)
     */
    public function displayEditLink($token = null, $id = 0, $name = null)
    {
        $href = $this->context->link->getAdminLink('AdminCustomers', true, [
            'route' => 'admin_customers_edit',
            'customerId' => (int) $id,
        ]);
        $href = $this->withBackToSjbList($href);

        return '<a class="btn btn-default" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" title="'
            . $this->l('Editar') . '"><i class="icon-pencil"></i> ' . $this->l('Editar') . '</a>';
    }

    /**
     * Enlace Ver direcciones -> listado SJB filtrado por cliente
     */
    public function displayViewaddressesLink($token = null, $id = 0, $name = null)
    {
        $href = $this->context->link->getAdminLink('AdminSjbDirecciones')
            . '&id_customer=' . (int) $id;

        return '<a class="btn btn-default" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" title="'
            . $this->l('Ver direcciones') . '"><i class="icon-map-marker"></i> '
            . $this->l('Ver direcciones') . '</a>';
    }

    /**
     * Añade ?back= al listado SJB para que el BO nativo vuelva aquí tras guardar/cancelar.
     *
     * @param string $url
     * @return string
     */
    private function withBackToSjbList($url)
    {
        $back = $this->context->link->getAdminLink('AdminSjbClientes');
        $sep = (strpos($url, '?') !== false) ? '&' : '?';

        return $url . $sep . 'back=' . rawurlencode($back);
    }
}
