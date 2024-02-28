<?php
defined('BASEPATH') or exit('No direct script access allowed');


$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;


// AJAX
$route["getCatalogosOgs"] = "ogs/index";
$route["getCatRutasCliente/(:num)"] = "Ogs/getCatRutasCliente/$1";
$route["getCatalogosPorCliente/(:num)"] = "Ogs/getCatalogosPorCliente/$1";
$route["crearOrden"] = "Ogs/crearOrden";
$route["agregarCarga"] = "Ogs/addCarga";
$route["agregarCargaLead"] = "Ogs/addCargaLead";
$route["getBitacora/(:any)"] = "Bitacora/getBitacora/$1";
$route["Bitacora/(:any)"] = "Bitacora/nuevo_movimiento/$1";
$route["getCvsActivos"] = "Bitacora/getCvsActivos";
$route["getCvsActivosTest"] = "Bitacora/getCvsActivosTest";
$route["generarCvOnePage/(:any)"] = "Bitacora/generarCvOnePage/$1";

$route["solicitarCostos"] = "Cotizaciones/solicitarCostos";
$route["solicitarCostosToggle"] = "Cotizaciones/solicitarCostosToggle";
$route["altaCotizacion"] = "Cotizaciones/altaCotizacion";
$route["guardarCosto"] = "Cotizaciones/guardarCosto";
$route["guardarCotizacionLn"] = "Cotizaciones/guardarCotizacionLn";

$route["getRolesYPermisos"] = "Roles/getRolesYPermisos";
$route["crearRol"] = "Roles/CrearRol";
$route["actualizarPermisos"] = "Roles/actualizarPermisos";
$route["getCatalogosProveedor/(:num)"] = "Proveedor/getCatalogosProveedor/$1";

$route["actualizarPermisos"] = "Roles/actualizarPermisos";
$route["altaContactosProveedor"] = "Proveedor/altaContactosProveedor";
$route["getProveedores"] = "Proveedor/getProveedores";
$route["getUsuariosProveedores"] = "Proveedor/getUsuariosProveedores";
$route["getInfoProveedor/(:num)"] = "Proveedor/getInfoProveedor/$1";
$route["generarExportCotizacion"] = "Cotizaciones/generarExportCotizacion";
$route["guardarAltacliente"] = "CRM/guardarAltacliente";

$route["altaGrupoContacto"] = "Proveedor/altaGrupoContacto";

// -leads
$route["getEntidades"] = "CRM/getEntidades";
$route["cambiarFase"] = "CRM/cambiarFase";
$route["getComentarios"] = "CRM/getComentarios";
$route["getInformacionGeneral"] = "CRM/getInformacionGeneral";
$route["actualizarLead"] = "CRM/actualizarLead";
$route["sendComentario"] = "CRM/sendComentario";
$route["getCotizacionesLead"] = "CRM/getCotizacionesLead";
$route["getCotizacionAnterior/(:any)"] = "CRM/getCotizacionAnterior/$1";
$route["guardarArchivoEntidad"] = "CRM/guardarArchivoEntidad";
$route["guardarContacto"] = "CRM/guardarContacto";
$route["actualizarContacto"] = "CRM/actualizarContacto";
$route["guardarActividad"] = "CRM/guardarActividad";
$route["uploadProfilePictura"] = "CRM/uploadProfilePictura";
// AJAX

// api
$route["whatsapp/getChats"] = "Whatsapp/getChats";
$route["whatsapp/recibirMensaje"] = "Whatsapp/recibirMensaje";
$route["whatsapp/getChatMessage/(:num)"] = "Whatsapp/getMensajesPorChat/$1";
$route["whatsapp/enviarMensaje"] = "Whatsapp/enviarMensaje";
$route["whatsapp/enviarMensajeFile"] = "Whatsapp/enviarMensajeFile";
$route["whatsapp/programarMensaje/(:any)"] = "Whatsapp/programarMensaje/$1";
$route["whatsapp/enviarMensajeProgramado"] = "Whatsapp/enviarMensajeProgramado";
$route["enviarMasivo"] = "Proveedor/enviarMasivo";



$route["convertLeadsInEmpresasClientes"] = "OneOperacion/convertLeadsInEmpresasClientes";
$route["setVendedor"] = "OneOperacion/setVendedor";


$route["generarToken"] = "CRM/generarToken";
