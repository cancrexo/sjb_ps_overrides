{*
 *
*}
<div class="panel" id="sjboverrides-info">

	<div class="panel-heading"><i class="icon-info"></i>{l s='Info de modulo' mod='sjboverrides'}</div>
	<div class="toggle">{l s='Este plugin permite añadir overrides de forma centralizada. Adicionalmente activa algunas funciones chulas mediante hooks.' mod='sjboverrides'}
      <ul class="cancrexolist">
      <li>Override soportados:<br>
         <ol >
         <li>Override de <strong>clase  Imagemanager</strong> para recorte preciso de las imágenes. Añade funcionalidad _sjb para imagenes con fondo transparente.</li>
          <li>Override de <strong>clase DISPATCHER</strong> en <strong>categorías de productos</strong>, <strong>productos</strong>, <strong>fabricantes</strong>, <strong>proveedores</strong> y <stromng>paginas cms</stromng> para eliminar los [id] de las URL.</li>
         {* <li>Override de <strong>clase CartRule</strong>: solo 1 cupon de desconto por pedido!!</li>
         <li>Override de <strong>controller  IndexController</strong> para redirixir usuarios de Catering a categoria catering cando acceden a INDEX</li> *}
         {* <li>Override de clase Customers: incluir TELÉFONO e EMAIL en columna de listado así como exportación a excel. </li> *}
         </ol>
      </li>
      <li>Hooks incluidos<br>
         <ol >
          <li>Hook <strong>hookActionOrderStatusPostUpdate</strong> para asignar Fecha de factura cuado esta se genere y no con la fecha del pedido. Necesario definir variable <strong>{$moduleprefix}ESTADO_FACTURADO</strong></li>
         </ol>
         </li>
      </ul>

   </div>
</div>
