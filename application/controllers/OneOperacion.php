<?php
defined('BASEPATH') or exit('No direct script access allowed');

class OneOperacion extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $_body = file_get_contents('php://input');
    }
    public function convertLeadsInEmpresasClientes()
    {
        $this->load->model('OperacionesVarios_model');
        $empresas = $this->OperacionesVarios_model->getLeads();

        $this->OperacionesVarios_model->insertEmpresa($empresas);
    }

    public function setVendedor()
    {
        $this->load->model('OperacionesVarios_model');
        $vendedoresYEntidades = $this->OperacionesVarios_model->getVendedoresYEntidades();
        $this->OperacionesVarios_model->setVendedor($vendedoresYEntidades);
    }
}
