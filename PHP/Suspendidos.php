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
                $this->database = new PDO("mysql:host=".$this->server.";dbname=".$this->dbname, $this->user, $this->pass);
                $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                echo "<script>alert('Fallo al conectar: " . addslashes($e->getMessage()) . "');</script>";
                exit();
            }

        }

        public function dameLosInfractores(){
            $sql = "SELECT DISTINCT
                    d.id AS id_piloto,
                    d.nombre AS nombre_piloto,
                    d.equipo,
                    d.país,
                    d.número_de_campeonatos AS campeonatos,
                    CASE 
                        WHEN p.id IS NOT NULL THEN 'Penalización'
                        ELSE NULL
                    END AS tipo_infracción,
                    (
                        SELECT COUNT(*) FROM penalties p2 WHERE p2.id_driver = d.id
                    ) AS numero_infracciones
                    FROM
                    drivers d
                    LEFT JOIN penalties p ON d.id = p.id_driver
                    WHERE
                    p.id IS NOT NULL;";
            $stmt = $this->database->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<section>";
            echo "<h3>Pilotos que realizaron infracciones</h3>";
            foreach ($result as $row) {
                echo "<section>";
                echo "<h4>" . htmlspecialchars($row['nombre_piloto']) . "</h4>";
                echo "<p><strong>Equipo:</strong> " . htmlspecialchars($row['equipo']) . "</p>";
                echo "<p><strong>País:</strong> " . htmlspecialchars($row['país']) . "</p>";
                echo "<p><strong>Infracciones:</strong> " . htmlspecialchars($row['numero_infracciones']) . "</p>";
                echo "<p><strong>Campeonatos:</strong> " . htmlspecialchars($row['campeonatos']) . "</p>";
                echo "</section>";
            }
            echo "</section>";
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
    <link rel="stylesheet" type="text/css" href="../estilo/libre.css" />
    <link rel="icon" href="multimedia/imagenes/favicon.ico" type="image/x-icon">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
</head>

<body>
    <header>
        <a href="index.html" title="Enlace a la pagina de inicio"><h1>F1 Desktop</h1></a>
        <nav>
                <a href="libre.php" title="Enlace a la pagina de inicio">Libre</a>  
                <a href="Infractores.php" title="Enlace a la pagina de consulta de datos">Infractores</a>     
        </nav>
    </header>
    <p>Estás en: <a href="libre.php" title="Enlace a la pagina de inicio">Libre</a> >> Infractores</p>
	<h2>Infractores de la formula 1: </h2>
    <?php
        $libre = new Libre();
        $libre->dameLosInfractores();
    ?>
   
</body>
</html>


