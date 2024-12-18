<?php 
    class Libre{
        protected $server;
        protected $user;
        protected $pass;
        protected $dbname;
        protected $database;

        public function __construct(){
            //Se crea la base de datos    
            $this-> server = "localhost";
            $this-> user = "DBUSER2024";
            $this-> pass = "DBPSWD2024";
            $this-> dbname = "libre";

            try {
                
                $this->database = new PDO("mysql:host=".$this->server, $this->user, $this->pass);
                $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Crear la base de datos si no existe
                $this->database->exec("CREATE DATABASE IF NOT EXISTS $this->dbname");
                $this->database->exec("USE $this->dbname");

            } catch (PDOException $e) {
                echo "<script>alert('Fallo al conectar: " . addslashes($e->getMessage()) . "');</script>";
                exit();
            }

        }
        //Método para crear las tablas y dejar la bd en su estado inicial
        public function preparaTablasDeLaBaseDeDatos() {
            try {
                // Esto ye para crear las tablas al estado inicial.
                $creacionTodasTablas = "
                        CREATE TABLE IF NOT EXISTS drivers (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            nombre VARCHAR(255) NOT NULL,
                            equipo VARCHAR(255),
                            país VARCHAR(255),
                            número_de_campeonatos INT DEFAULT 0
                        );

                        CREATE TABLE IF NOT EXISTS penalties (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            id_driver INT,
                            tipo_penalización VARCHAR(255),
                            fecha DATE,
                            carrera VARCHAR(255),
                            cantidad INT,
                            FOREIGN KEY (id_driver) REFERENCES drivers(id)
                        );

                        CREATE TABLE IF NOT EXISTS suspensions (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            id_driver INT,
                            fecha_inicio DATE,
                            fecha_fin DATE,
                            razón VARCHAR(255),
                            FOREIGN KEY (id_driver) REFERENCES drivers(id)
                        );
                        CREATE TABLE IF NOT EXISTS penalty_impact (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            id_penalty INT,
                            impacto_en_carrera VARCHAR(255),
                            posición_final INT,
                            FOREIGN KEY (id_penalty) REFERENCES penalties(id)
                        );

                        CREATE TABLE IF NOT EXISTS team_penalties (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            id_team INT,
                            id_penalty INT,
                            temporada INT,
                            impacto_equipo VARCHAR(255),
                            FOREIGN KEY (id_penalty) REFERENCES penalties(id)
                        );
                    ";
                $this->database->exec($creacionTodasTablas);
            } catch (PDOException $e) {
                echo "<script>alert('Error en la creación de tablas: " . $e->getMessage()."');</script>";
            }
        }
        //Método para rellenar un poco la base de datos para las pruebas
        public function importarDatosPredefinidosParaLaBaseDeDatos(){
            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, 'r');
            $header = fgetcsv($handle); // Leer el encabezado (opcional)

            while (($data = fgetcsv($handle)) !== false) {
                $table = $data[0];  // El primer campo indica el nombre de l tabla 
                array_shift($data);  // Eliminamos ese campo para que solo queden los datos
                if (!isset($data[0])) {
                    //Si la fila no tienen nada, se la salta
                    continue; 
                }
                $this->insertarDatosDeManeraGenerica($table, $data);
            }

            fclose($handle);
        }
        function insertarDatosDeManeraGenerica($table, $data) {
            // Obtener las columnas de la tabla
            $columns = $this->sacaColumnasDeLaTabla($table);
            if($columns==null){
                //Si no hay columnas se la salta
                return;
            }
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES ($placeholders)";
            $stmt = $this->database->prepare($sql);
            $stmt->execute($data); 
        }
        function sacaColumnasDeLaTabla($table) {
            // Consultar las columnas de la tabla
            if($table=="tabla"){
                return;
            }
            $sql = "DESCRIBE $table";
            $stmt = $this->database->query($sql);
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            // Filtrar las columnas para eliminar la columna 'id'
            $filteredColumns = array_filter($columns, function($col) {
                return $col['Field'] !== 'id'; // Excluye la columna 'id'
            });
        
            // Extraer solo los nombres de las columnas, ya sin 'id'
            return array_map(function($col) { return $col['Field']; }, $filteredColumns);
        }

        public function exportarDatosQueTenMetidosEnLaBaseDeDatos() {
            // Limpia el búfer de salida antes de generar el CSV porque me sacaba el HTML también
            if (ob_get_contents()) {
                ob_end_clean();
            }
            // Inicia el envío directo al navegador
            //Lo uso para evitar que se genere un archivo intermedio 
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="datosExportados.csv"');
        
            $output = fopen('php://output', 'w');
            
            // Obtener todas las tablas de la base de datos
            $tables = $this->sacaTodasLasTablasDeLaBaseDeDatos();
        
            if (empty($tables)) {
                // Si no hay tablas, escribe un mensaje y detén la ejecución
                fputcsv($output, ['No se encontraron tablas en la base de datos.']);
                fclose($output);
                exit;
            }
        
            foreach ($tables as $table) {
                // Escribir un título para cada tabla
                fputcsv($output, ["Tabla: $table"]);
                $this->sacaTodosLosDatosDeCadaTablaAlCsv($table, $output);
                fputcsv($output, []); // Línea vacía entre tablas (opcional)
            }
        
            fclose($output);
            exit;
        }
        function sacaTodasLasTablasDeLaBaseDeDatos() {

            // Consultar todas las tablas
            $sql = "SHOW TABLES";
            $stmt = $this->database->query($sql);
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return $tables;
        }
        function sacaTodosLosDatosDeCadaTablaAlCsv($table, $file) {
            
            // Obtener las columnas de la tabla
            $columns = $this->sacaTodasLasTablasDeLaBaseDeDatos($table);
            
            // Escribir encabezado (nombre de columnas)
            fputcsv($file, array_merge(['Tabla'], $columns));
        
            // Consultar los datos de la tabla
            $sql = "SELECT * FROM $table";
            $stmt = $this->database->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            // Escribir los datos en el CSV
            foreach ($data as $row) {
                // Añadir el nombre de la tabla al inicio de cada fila
                array_unshift($row, $table);
                fputcsv($file, $row);
            }
        }
    }
    ?>

<!DOCTYPE HTML>
<html lang="es">
<head>
    <!-- Datos que describen el documento -->
    <meta charset="UTF-8" />
    <title>F1Desktop</title>
    <meta name ="author" content ="Jorge" />
    <meta name ="description" content ="Proyecto de F1 para la asignatura SEW" />
    <meta name ="keywords" content ="f1" />
    <meta name ="viewport" content ="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" type="text/css" href="../estilo/estilo.css" />
    <link rel="stylesheet" type="text/css" href="../estilo/layout.css" />
    <link rel="icon" href="multimedia/imagenes/favicon.ico" type="image/x-icon">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
</head>

<body>
    <header>
        <a href="../index.html" title="Enlace a la pagina de inicio"><h1>F1 Desktop</h1></a>
        <nav>
        <a href="libre.php" title="Enlace a la pagina de inicio">Libre</a>  
        <a href="Infractores.php" title="Enlace a la pagina de consulta de datos">Infractores</a>     
        
        </nav>
    </header>
    <p>Estás en: <a href="libre.php" title="Enlace a la pagina de inicio">Inicio</a> >> <a href="../juegos.html" title="Enlace a la pagina de juegos">Juegos</a> >> Libre</p>
	<h2>Aplicación libre de PHP: </h2>
    <h3>Funcionalidad básica que se pide: </h3>
    <?php
        $libre = new Libre();
        $libre->preparaTablasDeLaBaseDeDatos();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['action']) && $_POST['action'] == 'import') {
                $libre->importarDatosPredefinidosParaLaBaseDeDatos();
            } else if (isset($_POST['action']) && $_POST['action'] == 'export') {
                $libre->exportarDatosQueTenMetidosEnLaBaseDeDatos();
                exit();
            }
        }
    ?>
    <h4>Importacion desde csv:</h4>
    <form method="POST" enctype="multipart/form-data">
        <label>
            Selecciona un archivo CSV para importar:
            <input type="file" name="csv_file" required>
        </label>
        <input type="hidden" name="action" value="import">
        <label >
            Importar CSV:
            <button type="submit">Importar CSV</button>
        </label>
        
    </form>
    <h4>Exportacion a un csv: </h4>
    <form method="POST">
    <input type="hidden" name="action" value="export">
    <label>
        Exportar a CSV:
        <button type="submit">Exportar Todo a CSV</button>
    </label>
    </form>
   
</body>
</html>


