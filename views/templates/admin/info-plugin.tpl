{*
 * Bloque informativo de la pantalla de configuración.
 * Debe reflejar overrides, hooks y funciones realmente activos en este módulo.
 *}
<div class="panel" id="sjboverrides-info">

    <div class="panel-heading"><i class="icon-info"></i>{l s='Info de modulo' mod='sjboverrides'}</div>
    <div class="toggle">{l s='Este plugin centraliza overrides de PrestaShop y activa funciones extra (hooks, widgets y listados propios del back-office).' mod='sjboverrides'}
        <ul class="cancrexolist">
            <li>Overrides soportados:<br>
                <ol>
                    <li>Override de <strong>ImageManager</strong>: recorte preciso de miniaturas, rama <strong>_sjb</strong> para imágenes con fondo transparente y <strong>posición de recorte</strong> configurable (top center / center / bottom center).</li>
                    <li>Override de <strong>Manufacturer</strong>: se pueden ocultar marcas en el frontend si su meta-título coincide con el texto definido en la configuración.</li>
                    <li>Override de <strong>ProductController</strong>: si el producto no tiene stock y tiene la característica configurada, se muestra un formulario de consulta en lugar del mensaje de stock agotado.</li>
                    <li>Override de <strong>OrderHistory</strong>: al pasar el pedido al estado facturado se asigna la fecha de factura (la del momento de facturar, no la del pedido). Hay que indicar el <strong>Estado facturado</strong> en esta misma pantalla.</li>
                    <li>Overrides de <strong>HTMLTemplate</strong> y <strong>HTMLTemplateInvoice</strong>: plantillas PDF de factura del módulo (incluye reglas de carrito / cupones en la factura).</li>
                </ol>
            </li>
            <li>Otras funciones:<br>
                <ol>
                    <li><strong>Sistema SJB de clientes / direcciones</strong>: sustituye los listados nativos del back-office por los de SJB (filtro de direcciones, invertir selección, acciones agrupadas, autocomplete de cliente al alta de dirección).</li>
                </ol>
            </li>
            <li>Hooks incluidos:<br>
                <ol>
                    <li>Hook <strong>actionOrderStatusPostUpdate</strong>: refuerza la fecha de factura al cambiar al estado facturado (complementa el override de OrderHistory).</li>
                    <li>Hook <strong>displayBackOfficeHeader</strong>: muestra la versión del módulo junto al ítem de menú.</li>
                    <li>Hook <strong>actionAdminControllerSetMedia</strong>: carga CSS/JS del back-office (listados SJB, autocomplete de direcciones, etc.).</li>
                </ol>
            </li>
        </ul>
    </div>
</div>
