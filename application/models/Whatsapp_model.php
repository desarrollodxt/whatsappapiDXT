8<?php
    class Whatsapp_model extends CI_Model
    {
        private $tabla = 'whatsapp_messages';

        public function __construct()
        {
            parent::__construct();
            $this->load->database();
        }

        public function getMessageByWpId($whatsapp_id)
        {
            $query = $this->db->from($this->tabla)->where("id_whatsapp_message", $whatsapp_id)->get();
            return $query->row();
        }

        public function salvarMensajeRecibido($body)
        {
            $this->db->trans_begin();
            $queryChat = $this->db->from("chats_whatsapp")->where("from", $body["from"])->get();
            $chat = $queryChat->row();
            if (empty($chat)) {
                $this->db->insert("chats_whatsapp", ["from" => $body["from"]]);
                $chat_id = $this->db->insert_id();
            } else {
                $chat_id = $chat->id;
            }

            $this->db->update("whatsapp_messages", ["last_message" => 0], ["id_chat" => $chat_id]);

            $newMessage = [
                "id_chat" => $chat_id,
                "from" => $body["from"],
                "author" => $body["author"],
                "filename" => $body["filename"],
                "time" => $body["time"],
                "isGroup" => $body["isGroup"],
                "pushname" => $body["pushname"],
                "mimetype" => $body["mimetype"],
                "content" => $body["content"],
                "in" => $body["inM"],
                "id_whatsapp_message" => $body["id"],
                "es_respuesta" => $body["es_respuesta"],

            ];

            if ($body["es_respuesta"] == 1) {
                $chat_a_responder =  $this->getMessageByWpId($body["id_respuesta"]);
                $newMessage["id_respondido"] = $chat_a_responder->id;
            }
            $this->db->insert("whatsapp_messages", $newMessage);

            $this->db->trans_commit();
            try {
            } catch (\Throwable $th) {
                $this->db->trans_rollback();
                return false;
            }
        }

        public function getChats()
        {
            $select = "cw.id,
            case 
                when wm.pushname is null then 'nombre temporal'
                else wm.pushname
            end nombre, cw.from,
            case 
                when date(wm.created_at) = date(now()) then 'Hoy'
                else date(wm.created_at)
            end created_at,
            case 
                when wm.content is null then ''
                else wm.content
            end lastMessage";
            $query = $this->db->select($select)->from("chats_whatsapp cw")->join("whatsapp_messages wm", "wm.id_chat = cw.id and wm.last_message = 1 and cw.`from` = wm.`from`", "left")->get();
            return $query->result_array();
        }

        public function getChat($chat_id)
        {
            $select = "*";
            $query = $this->db->select($select)->from("chats_whatsapp cw")
                ->where("cw.id", $chat_id)->get();
            return $query->row();
        }

        public function getMensajesPorChat($chat_id, $limit, $lastMessage)
        {
            $this->db->from($this->tabla)->where("id_chat", $chat_id)->order_by("id", "desc")->limit($limit);
            if ($lastMessage) {
                $this->db->where("id <=", $lastMessage);
            }

            $query = $this->db->get();
            return $query->result_array();
        }


        public function newMessage($body, $messageId)
        {
            $chat_id = null;
            if (!$body["id_chat"]) {
                $queryChat = $this->db->from("chats_whatsapp")->where("from", $body["from"])->get();
                $chat = $queryChat->row();
                if (empty($chat)) {
                    $this->db->insert("chats_whatsapp", ["from" => $body["from"]]);
                    $chat_id = $this->db->insert_id();
                } else {
                    $chat_id = $chat->id;
                }
            }
            $from = $body["from"];
            $mensaje = $body["mensaje"];

            $newMessage = [
                "content" => $mensaje,
                "in" => false,
                "mimetype" => "",
                "from" => $from,
                "author" => "Administrador",
                "filename" => "",
                "pushname" => "Administrador",
                "time" => time(),
                "isGroup" => true,
                "id_chat" => $chat_id,
                "last_message" => 1,
                "id_whatsapp_message" => $messageId,
            ];

            $newMessage["filename"] = isset($body["filename"]) ? $body["filename"] : "";
            $newMessage["mimetype"] = isset($body["mimetype"]) ? $body["mimetype"] : "";

            $this->db->update("whatsapp_messages", ["last_message" => 0], ["id_chat" => $chat_id]);
            $this->db->insert("whatsapp_messages", $newMessage);
            $id = $this->db->insert_id();
            $newMessage["id"] = $id;
            $newMessage["in"] = false;

            return $newMessage;
        }
    }
