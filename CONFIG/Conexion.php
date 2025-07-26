<?php
  require_once "global.php";
  class Conexion{ // Corregido: Nombre de clase con mayúscula inicial
    private $mysqli; // Almacenar la conexión

    function __construct() { // Conectar al instanciar
        $this->mysqli = new MySQLi(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($this->mysqli->connect_error) {
            error_log("Error conectando a la base de datos: " . $this->mysqli->connect_error);
            // En un entorno de producción, no harías echo aquí, solo log.
            // Para desarrollo, puedes dejarlo o lanzar una excepción.
            // echo "Error conectando a la base de datos";
            // exit();
            throw new Exception("Error de conexión a la BD: " . $this->mysqli->connect_error);
        }
        $this->mysqli->set_charset(DB_ENCODE);
    }

    function conecta(){ // Devuelve la instancia mysqli existente
      return $this->mysqli;
    }

    function ejecutarSP($sql){ 
        if (!$this->mysqli || $this->mysqli->connect_error) {
            error_log("Intento de ejecutar SP sin conexión válida.");
            return false;
        }
        // Limpiar resultados anteriores si los hay, crucial para múltiples llamadas a SPs
        while($this->mysqli->more_results() && $this->mysqli->next_result()){
            if($res = $this->mysqli->store_result()){
                $res->free();
            }
        }

        $query = $this->mysqli->query($sql);
        if (!$query) {
            error_log("Error en la consulta SQL: " . $this->mysqli->error . " (SQL: " . $sql . ")");
            return false; // Devolver false en caso de error
        }
        return $query; // Devolver el objeto mysqli_result o true/false para DML
    }

    // Es buena práctica tener un método para cerrar explícitamente si es necesario,
    // aunque PHP lo hace al final del script.
    function cerrarConexion() {
        if ($this->mysqli) {
            $this->mysqli->close();
        }
    }

    // Para obtener la conexión mysqli directamente si se necesitan transacciones, etc.
    function getMysqli() {
        return $this->mysqli;
    }
  }
?>