<?php

var_dump($_POST);

// 1. IMPORTANTE: Cargar el autoloader de Composer para que Render reconozca MongoDB
require_once __DIR__ . '/../vendor/autoload.php';

// Variables para verificar que se guarde en ambos lados
$postgres_ok = false;
$mongodb_ok = false;

// ==========================================
// TU CONEXIÓN Y REGISTRO EN POSTGRESQL
// ==========================================
try {
    $conexion = new PDO('pgsql:host=dpg-d8f39bl53gjs739kr1c0-a.oregon-postgres.render.com;dbname=sena_4gjt','sena_4gjt_user','MdEyvmMNdVywTNkoijpetMBysaRHzQxD');
    $registrar = $conexion->prepare("INSERT INTO aprendices (nombre,telefono,detalles) VALUES (?, ?, ?)");
    $registrar->execute([$_POST["nom"], $_POST["tel"] , $_POST["det"] ]);
    
    $postgres_ok = true; // Se guardó en Postgres
} catch (Exception $e) {
    echo "<p style='color:white;background-color:red;text-align:center'>Error en Postgres: " . $e->getMessage() . "</p>";
}

// ==========================================
// NUEVA CONEXIÓN Y REGISTRO EN MONGO DB ATLAS
// ==========================================
try {
    // Usamos tu URI oficial con la contraseña que acabas de crear
    $mongoUri = "mongodb+srv://prueba:y2Ji6GgMKU47UFbf@cluster0.7dy1rur.mongodb.net/?appName=Cluster0";
    $client = new MongoDB\Client($mongoUri);
    
    // Selecciona la base de datos "sena_respaldo" y la colección "estudiantes"
    $coleccion = $client->sena_respaldo->estudiantes;
    
    // Insertamos el documento de respaldo
    $resultadoMongo = $coleccion->insertOne([
        'nombre'   => $_POST["nom"],
        'telefono' => $_POST["tel"],
        'detalles' => $_POST["det"],
        'fecha'    => date('Y-m-d H:i:s')
    ]);
    
    if ($resultadoMongo->getInsertedCount() > 0) {
        $mongodb_ok = true; // Se guardó en Mongo
    }
} catch (Exception $e) {
    echo "<p style='color:white;background-color:red;text-align:center'>Error en MongoDB: " . $e->getMessage() . "</p>";
}

// ==========================================
// VALIDACIÓN DE DOBLE SOPORTE (REQUISITO DEL TALLER)
// ==========================================
if ($postgres_ok && $mongodb_ok) {
    echo "<p style='color:white;background-color:green;font-family:calibri,arial;font-size:24px;text-align:center'>Registro exitoso en Ambos Soportes (PostgreSQL y MongoDB Atlas)</p>";
} else {
    echo "<p style='color:white;background-color:orange;font-family:calibri,arial;font-size:24px;text-align:center'>Atención: No se pudo respaldar en ambas bases de datos</p>";
}

// ==========================================
// TU CONSULTA DE TABLA (POSTGRESQL)
// ==========================================
$consulta = $conexion->prepare("SELECT * FROM aprendices order by id desc"); // Le puse "desc" para que veas el nuevo arriba siempre
$consulta->execute();
$tabla = $consulta->fetchAll(PDO::FETCH_ASSOC);     
$conexion = null;

echo "<table border='1' cellpadding='5' style='border-collapse:collapse; margin-top:20px;'>
        <tr>
            <th>Codigo</th>
            <th>Nombre completo</th>
            <th>Contacto</th>
        </tr>";
foreach($tabla as $fila){       
    echo "<tr>      
            <td>$fila[id]</td>
            <td>$fila[nombre]</td>
            <td>$fila[telefono]</td>
          </tr>";
}
echo "</table>";

// Botón para regresar al formulario cómodamente
echo "<br><a href='../index.html'>Volver al formulario</a>";
?>
