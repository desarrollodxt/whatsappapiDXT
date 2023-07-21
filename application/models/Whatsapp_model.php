<?php
class Whatsapp_model extends CI_Model
{
    private $tabla = 'whatsapp_message';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function salvarMensajeRecibido($body)
    {
        $data = ["mensaje" => json_encode($body)];
        $this->db->insert($this->tabla, $data);
        return $this->db->insert_id();
    }
}
