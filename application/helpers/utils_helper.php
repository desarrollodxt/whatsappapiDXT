<?php


if (!function_exists("validarRol")) {
    function validarRol($rolesUsuario, $rolesPermitidos)
    {
        $valido = false;
        foreach ($rolesUsuario as $rolUsuario) {
            foreach ($rolesPermitidos as $rolPermitido) {
                if ($rolUsuario == $rolPermitido) {
                    $valido = true;
                    break;
                }
            }
        }
        return $valido;
    }
}


if (!function_exists("dd")) {
    function dd(&$data)
    {
        echo "<pre>";
        var_dump($data);
        echo "</pre>";
        exit();
    }
}
