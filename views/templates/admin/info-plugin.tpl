{*
 * Bloque informativo de la pantalla de configuración.
 * Debe reflejar overrides, hooks y funciones realmente activos en este módulo.
 * Las listas van dentro de panel-body: como hijas directas del panel,
 * PrestaShop les quita bordes y se cruzan con el panel-heading.
 *}
<div class="panel" id="sjboverrides-info">

    <div class="panel-heading"><i class="icon-info"></i> {l s='Info de modulo' mod='sjboverrides'}</div>
    <div class="panel-body">
        <p>{l s='Este plugin centraliza overrides de PrestaShop y activa funciones extra (hooks, widgets y listados propios del back-office).' mod='sjboverrides'}</p>

        <h4><i class="icon-puzzle-piece"></i> {l s='Overrides soportados' mod='sjboverrides'}</h4>
        <ul class="list-group">
            <li class="list-group-item">Override de <strong>ImageManager</strong>: recorte de miniaturas (rama <strong>_sjb</strong> y <strong>posición de recorte</strong> configurable) y aceptación de <strong>WebP</strong>. El interruptor enciende o apaga las dos cosas. Si la tienda ya tiene otro override de ImageManager, queda bloqueado.</li>
            <li class="list-group-item">Override de <strong>Manufacturer</strong>: se pueden ocultar marcas en el frontend si su meta-título coincide con el texto definido en la configuración. Si la tienda ya tiene otro override de Manufacturer, el interruptor queda bloqueado.</li>
            <li class="list-group-item">Override de <strong>ProductController</strong>: si el producto no tiene stock y tiene la característica configurada, se muestra un formulario de consulta en lugar del mensaje de stock agotado. Si la tienda ya tiene otro override de ProductController, el interruptor queda bloqueado.</li>
            <li class="list-group-item">Override de <strong>OrderHistory</strong>: al pasar el pedido al estado facturado se asigna la fecha de factura (la del momento de facturar, no la del pedido). Hay que indicar el <strong>Estado facturado</strong> en esta misma pantalla. Si la tienda ya tiene otro override de OrderHistory, el interruptor queda bloqueado.</li>
            <li class="list-group-item">Override de <strong>FrontController</strong>: con la opción activa, la página de mantenimiento responde <strong>200</strong> en lugar de 503. Si la tienda ya tiene otro override de FrontController, el interruptor queda bloqueado.</li>
            <li class="list-group-item">Overrides de <strong>HTMLTemplate</strong> y <strong>HTMLTemplateInvoice</strong>: plantillas PDF de factura del módulo (incluye reglas de carrito / cupones en la factura).</li>
        </ul>

        <h4><i class="icon-cogs"></i> {l s='Otras funciones' mod='sjboverrides'}</h4>
        <ul class="list-group">
            <li class="list-group-item"><strong>Sistema SJB de clientes / direcciones</strong>: sustituye los listados nativos del back-office por los de SJB (filtro de direcciones, invertir selección, acciones agrupadas, autocomplete de cliente al alta de dirección).</li>
        </ul>

        <h4><i class="icon-anchor"></i> {l s='Hooks incluidos' mod='sjboverrides'}</h4>
        <ul class="list-group">
            <li class="list-group-item">Hook <strong>actionOrderStatusPostUpdate</strong>: refuerza la fecha de factura al cambiar al estado facturado (complementa el override de OrderHistory).</li>
            <li class="list-group-item">Hook <strong>displayBackOfficeHeader</strong>: muestra la versión del módulo junto al ítem de menú.</li>
            <li class="list-group-item">Hook <strong>actionAdminControllerSetMedia</strong>: carga CSS/JS del back-office (listados SJB, autocomplete de direcciones, etc.).</li>
        </ul>
    </div>
</div>
