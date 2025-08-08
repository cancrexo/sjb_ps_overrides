
// Scripts de controller


// Gestiona visibilidad de select en override  orderHistory
document.addEventListener('DOMContentLoaded', ()=> {

    const switch_activar_hook_facturado = document.querySelectorAll('input[name="general_hookorderhistoryactive"]');
    const select_estado_facturado = document.querySelector('.form-group.grupo-estadofacturado');

    // Iterar sobre cada botón de radio y agregar el detector de eventos
    switch_activar_hook_facturado.forEach((radioButton) => {
        radioButton.addEventListener('change', (event) => {
            // Acción a realizar cuando un botón de radio cambia, por ejemplo, imprimir el valor seleccionado
            let valor = Number(event.target.value);
         valor ? select_estado_facturado.classList.remove('hidden') : select_estado_facturado.classList.add('hidden');
        });
    });

    const orderhistoryHook = document.querySelector('input[name="general_hookorderhistoryactive"]:checked')?.value;
    if (Number(orderhistoryHook))select_estado_facturado.classList.remove('hidden');







    const switch_activar_hook_preguntar_porproducto = document.querySelectorAll('input[name="general_hookpreguntarproducto"]');
    const select_id_feature = document.querySelector('.form-group.grupo-preguntar-porproducto ');

    // Iterar sobre cada botón de radio y agregar el detector de eventos
    switch_activar_hook_preguntar_porproducto.forEach((radioButton) => {
        radioButton.addEventListener('change', (event) => {
            // Acción a realizar cuando un botón de radio cambia, por ejemplo, imprimir el valor seleccionado
            let valor = Number(event.target.value);
            valor ? select_id_feature.classList.remove('hidden') : select_id_feature.classList.add('hidden');
        });
    });

    const preguntarProductoHook = document.querySelector('input[name="general_hookpreguntarproducto"]:checked')?.value;
    console.log(preguntarProductoHook);
    if (Number(preguntarProductoHook))select_id_feature.classList.remove('hidden');
});

//   if (document.readyState === "interactive" || document.readyState === "complete" ) {
        // baseAdminDir -->variable prestahop


