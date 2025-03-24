
document.onreadystatechange = function () {
    if (document.readyState == "complete") {
        el_Llonatan();
    }
}

const el_Llonatan = () => {

    if(sjboverride_links['sjboverrides']) menuLink_sjboverrides();
    if(sjboverride_links['ps_faceted']) menuLink_Faceted();
    if(sjboverride_links['catalogo_etiquetas']) menuLink_Etiquetas();
    // if(sjboverride_links['ps_themesettings'])menuLink_Themesettings();

}

// engadinos elementos de menu segun sexa a config
const menuLink_sjboverrides = ()=>{
    console.log('es bien');
    const url = sjboverride_links['sjboverrides']; // cerada no modulo
    const li = document.createElement('li');
    li.classList.add(...['link-leveltwo', 'sjboverride']);
    li.innerHTML = `<a class="sjboverride-link link-leveltwo" id="sjboverrides-images" href="${url}"><span>SJB Images</span></a>`;
    const ul = document.querySelector('#subtab-AdminParentThemes > ul');
    console.group(ul);
    const li_de_imaxes = document.querySelector('#subtab-AdminImages');
    //element.parentNode.insertBefore(newElement, element);
    //element.parentNode.insertBefore(newElement, element.nextSibling);
    li_de_imaxes.parentNode.insertBefore(li, li_de_imaxes.nextSibling);

}
// engadinos elementos de menu segun sexa a config
const menuLink_Faceted = ()=>{
    /*
        //token backend PS
        const token = document.querySelector('body').dataset.token; // por exemplo
        // baseAdminDir -->variable prestahop1.7, .p.e., '/adminweb/'
        url = `${baseAdminDir}index.php/improve/modules/manage/action/configure/ps_facetedsearch?_token=${token}`;
        url= ${baseAdminDir}index.php/index.php?controller=AdminModules&configure=ps_facetedsearch&token=${token}`;
     */
    const url = sjboverride_links['ps_faceted']; // cerada no modulo
    const li = document.createElement('li');
    li.classList.add(...['link-leveltwo', 'sjboverride']);
    li.innerHTML = `<a class="sjboverride-link link-leveltwo" id="faceted-link" href="${url}"><span>Filtros Busqueda</span></a>`;
    const ul = document.querySelector('#subtab-AdminCatalog > ul');
    console.group(ul);
    ul.appendChild(li);
}

// Etiquetas de busqueda
const menuLink_Etiquetas = ()=>{
    //https://deportesomar.com/pantalan/index.php?controller=AdminFeatures&updatefeature=&id_feature=8&token=54b077dc49d2b61ef7330830d7590de7
    //http://ps17.lan/adminweb/index.php?controller=AdminFeatures&updatefeature=&id_feature=1&token=f1673f5d5fade39c3af971ced91a2221
    // http://ps17.lan/adminweb/index.php?controller=AdminAttributesGroups&token=533ed22c1653c655eadb11b2f6268059
    //http://ps17.lan/adminweb/index.php?controller=AdminAttributesGroups&token=533ed22c1653c655eadb11b2f6268059

    const url = sjboverride_links['catalogo_etiquetas'];
    const li = document.createElement('li');
    li.classList.add('link-leveltwo');
    li.innerHTML = `<a class="sjboverride-link  link-leveltwo" id="Etiquetas-link" href="${url}"><span>Etiquetas</span></a>`;
    const ul = document.querySelector('#subtab-AdminCatalog > ul');
    console.group(ul);
    ul.appendChild(li);
}
