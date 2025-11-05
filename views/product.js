

// Scripts de controller


// Gestiona visibilidad de select en override  orderHistory
document.addEventListener('DOMContentLoaded', () => {

    const capaProductosRelacionados =  document.querySelector('#bloque-relacionados');
    const relacionados = capaProductosRelacionados.querySelectorAll('.elementor-widget-pkproductaccessories article.product-miniature');
    const totalRelacionados = relacionados.length;
    console.log({ totalRelacionados });

    if (!totalRelacionados)
        capaProductosRelacionados.style.display = 'none';

});