{*
 *
*}
<div class="panel" id="sjboverrides-info">

	<div class="panel-heading"><i class="icon-info"></i>{l s='Info de modulo' mod='sjboverrides'}</div>
	<div class="toggle">{l s='Este plugin permite añadir overrides de forma centralizada. Manejar con precauçao' mod='sjboverrides'}
    <p style="margin:1em 0; font-weight:bold; font-size:15px;">Override soportados:</p>
    <ol class="cancrexolist">
    <li>Override de <strong>clase  Imagemanager</strong> para recorte preciso de las imágenes. Añade funcionalidad _sjb para imagenes con fondo transparente.</li>
     <li>Override de <strong>clase  Order</strong> para asignar Fecha de factura cuado esta se genere y no con la fecha del pedido. Necesario definir variable <strong>{$moduleprefix}ESTADO_FACTURADO</strong></li>
    {* <li>Override de <strong>clase CartRule</strong>: solo 1 cupon de desconto por pedido!!</li>
    <li>Override de <strong>controller  IndexController</strong> para redirixir usuarios de Catering a categoria catering cando acceden a INDEX</li> *}
    {* <li>Override de clase Customers: incluir TELÉFONO e EMAIL en columna de listado así como exportación a excel. </li> *}
    </ol>

    </div>
</div>
