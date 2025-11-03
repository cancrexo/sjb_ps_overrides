
// Scripts de controller


// Gestiona visibilidad de select en override  orderHistory
document.addEventListener('DOMContentLoaded', ()=> {


    // ESTADO FACTURADO 
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
    
    // si o hook esta activa, amosa select  (tras carga)
    const orderhistoryHook = document.querySelector('input[name="general_hookorderhistoryactive"]:checked')?.value;
    if (Number(orderhistoryHook))select_estado_facturado.classList.remove('hidden');



    // PREGUNTAR POR PRODUCTO
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

    // si o hook esta activa, amosa select  (tras carga)
    const preguntarProductoHook = document.querySelector('input[name="general_hookpreguntarproducto"]:checked')?.value;
    if (Number(preguntarProductoHook)) select_id_feature.classList.remove('hidden');
    // console.log(preguntarProductoHook);
    


    // MANUFACTURER

    const switch_activar_hook_manufacturer = document.querySelectorAll('input[name="general_hookocultarmanufacturer"]');
    const bloque_inputmanufacturerhiddenflag = document.querySelector('.form-group.grupo-ocultar-manufacturer');

    // Iterar sobre cada botón de radio y agregar el detector de eventos
    switch_activar_hook_manufacturer.forEach((radioButton) => {
        radioButton.addEventListener('change', (event) => {
            // Acción a realizar cuando un botón de radio cambia, por ejemplo, imprimir el valor seleccionado
            let valor = Number(event.target.value);
            valor ? bloque_inputmanufacturerhiddenflag.classList.remove('hidden') : bloque_inputmanufacturerhiddenflag.classList.add('hidden');
        });
    });

    // si o hook esta activa,amosa input  (tras carga)
    const manufacturerHook = document.querySelector('input[name="general_manufacturerhiddenflag"]')?.value;
    if (Number(manufacturerHook))bloque_inputmanufacturerhiddenflag.classList.remove('hidden');




});

//   if (document.readyState === "interactive" || document.readyState === "complete" ) {
        // baseAdminDir -->variable prestahop


