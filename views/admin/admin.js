
// Scripts de controller

document.addEventListener('DOMContentLoaded', ()=> {

   const select_estado_facturado = document.querySelector('.form-group.grupo-estadofacturado');
   const switch_estado_facturado = document.querySelectorAll('input[name="order_history"]');
   // Iterar sobre cada botón de radio y agregar el detector de eventos
   switch_estado_facturado.forEach((radioButton) => {
      radioButton.addEventListener('change', (event) => {
         // Acción a realizar cuando un botón de radio cambia, por ejemplo, imprimir el valor seleccionado
         let valor = Number(event.target.value);
         valor ? select_estado_facturado.classList.remove('hidden') : select_estado_facturado.classList.add('hidden');
      });
   });



   console.log(switch_estado_facturado);
   console.log(select_estado_facturado);

});

//   if (document.readyState === "interactive" || document.readyState === "complete" ) {
        // baseAdminDir -->variable prestahop


