<?php
// Cargar el autoload real de las librerías
require_once __DIR__ . '/../vendor/autoload.php';

$postgres_ok = false;
$mongodb_ok = false;

$nombre   = $_POST["nom"] ?? '';
$telefono = $_POST["tel"] ?? '';
$detalles = $_POST["det"] ?? '';

// ==========================================
// 1. CONEXIÓN Y REGISTRO EN POSTGRESQL (Real)
// ==========================================
try {
    $conexion = new PDO('pgsql:host=dpg-d8f39bl53gjs739kr1c0-a.oregon-postgres.render.com;dbname=sena_4gjt','sena_4gjt_user','MdEyvmMNdVywTNkoijpetMBysaRHzQxD');
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (!empty($nombre)) {
        $registrar = $conexion->prepare("INSERT INTO aprendices (nombre,telefono,detalles) VALUES (?, ?, ?)");
        $registrar->execute([$nombre, $telefono, $detalles]);
        $postgres_ok = true;
    }
    
    // Consultar datos de Postgres
    $consultaPG = $conexion->query("SELECT * FROM aprendices ORDER BY id DESC");
    $tablaPG = $consultaPG->fetchAll(PDO::FETCH_ASSOC);
    $conexion = null;
} catch (Exception $e) {
    $error_pg = $e->getMessage();
}

// ==========================================
// 2. CONEXIÓN Y REGISTRO EN MONGO DB ATLAS (Real)
// ==========================================
try {
    $mongoUri = "mongodb+srv://prueba:y2Ji6GgMKU47UFbf@cluster0.7dy1rur.mongodb.net/?appName=Cluster0";
    $client = new MongoDB\Client($mongoUri);
    $coleccion = $client->sena_respaldo->estudiantes;
    
    if (!empty($nombre)) {
        $resultadoMongo = $coleccion->insertOne([
            'nombre'   => $nombre,
            'telefono' => $telefono,
            'detalles' => $detalles,
            'fecha'    => date('Y-m-d H:i:s')
        ]);
        if ($resultadoMongo->getInsertedCount() > 0) {
            $mongodb_ok = true;
        }
    }
    
    // Consultar datos reales de MongoDB Atlas
    $tablaMG = $coleccion->find([], ['sort' => ['_id' => -1]]);
} catch (Exception $e) {
    $error_mg = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel - Doble Conexión Real</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container my-5">
        
        <div class="row mb-4">
            <div class="col-12">
                <?php if (!empty($nombre)): ?>
                    <?php if ($postgres_ok && $mongodb_ok): ?>
                        <div class="alert alert-success d-flex align-items-center shadow-sm" role="alert">
                            <i class="bi bi-check-circle-fill fs-3 me-3"></i>
                            <div>
                                <h4 class="alert-heading mb-1">¡Registro Exitoso en Ambos Soportes!</h4>
                                <p class="mb-0">Estudiante guardado en <strong>PostgreSQL (Relacional)</strong> y <strong>MongoDB Atlas (NoSQL)</strong> de forma real.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger shadow-sm" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Error. Postgres: <?php echo $postgres_ok?'OK':'FALLÓ'; ?> | Mongo: <?php echo $mongodb_ok?'OK':'FALLÓ'; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <h2><i class="bi bi-database-fill-gear text-primary"></i> Consola de Datos Multibase de Datos</h2>
            <a href="../index.html" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-6">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-dark text-white"><i class="bi bi-database me-2"></i>PostgreSQL (Render)</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light"><tr><th>ID</th><th>Nombre</th><th>Contacto</th></tr></thead>
                                <tbody>
                                    <?php foreach(($tablaPG ?? []) as $fila): ?>
                                        <tr>
                                            <td><span class="badge bg-secondary"><?php echo $fila['id']; ?></span></td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($fila['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($fila['telefono']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-success text-white"><i class="bi bi-leaf me-2"></i>MongoDB Atlas (Nube Real)</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light"><tr><th>ID Documento (Mongo)</th><th>Nombre</th><th>Detalles</th></tr></thead>
                                <tbody>
                                    <?php foreach(($tablaMG ?? []) as $doc): ?>
                                        <tr>
                                            <td><small class="text-muted font-monospace"><?php echo $doc['_id']; ?></small></td>
                                            <td class="fw-semibold text-success"><?php echo htmlspecialchars($doc['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($doc['detalles'] ?? 'Sin detalles'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div> </div>
</body>
</html>
