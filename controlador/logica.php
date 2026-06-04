<?php
// 1. Cargar dependencias de Composer e ignorar alertas si es necesario
require_once __DIR__ . '/../vendor/autoload.php';

$postgres_ok = false;
$mongodb_ok = false;

// Variables de datos del formulario
$nombre   = $_POST["nom"] ?? '';
$telefono = $_POST["tel"] ?? '';
$detalles = $_POST["det"] ?? '';

// ==========================================
// REGISTRO EN POSTGRESQL (PDO)
// ==========================================
try {
    $conexion = new PDO('pgsql:host=dpg-d8f39bl53gjs739kr1c0-a.oregon-postgres.render.com;dbname=sena_4gjt','sena_4gjt_user','MdEyvmMNdVywTNkoijpetMBysaRHzQxD');
    if (!empty($nombre)) {
        $registrar = $conexion->prepare("INSERT INTO aprendices (nombre,telefono,detalles) VALUES (?, ?, ?)");
        $registrar->execute([$nombre, $telefono, $detalles]);
        $postgres_ok = true;
    }
} catch (Exception $e) {
    $error_pg = $e->getMessage();
}

// ==========================================
// REGISTRO EN MONGO DB ATLAS
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
} catch (Exception $e) {
    $error_mg = $e->getMessage();
}

// ==========================================
// CONSULTA DE DATOS (Para armar las tablas)
// ==========================================
// Consulta Postgres
$consultaPG = $conexion->query("SELECT * FROM aprendices ORDER BY id DESC");
$tablaPG = $consultaPG->fetchAll(PDO::FETCH_ASSOC);
$conexion = null;

// Consulta MongoDB Atlas
$tablaMG = $coleccion->find([], ['sort' => ['_id' => -1]]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Doble Soporte</title>
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
                                <h4 class="alert-heading mb-1">¡Sincronización Exitosa!</h4>
                                <p class="mb-0">El estudiante <strong><?php echo htmlspecialchars($nombre); ?></strong> fue registrado en <strong>PostgreSQL (Relacional)</strong> y respaldado en <strong>MongoDB Atlas (NoSQL)</strong> simultáneamente.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger shadow-sm" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Error en la persistencia de doble soporte.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <h2><i class="bi bi-database-fill-gear text-primary"></i> Consola de Datos del Sistema</h2>
            <a href="../index.html" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver al Formulario</a>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-6">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-dark text-white d-flex align-items-center">
                        <i class="bi bi-database me-2"></i>
                        <h5 class="mb-0">Base de Datos PostgreSQL (Render)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre Completo</th>
                                        <th>Contacto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($tablaPG as $fila): ?>
                                        <tr>
                                            <td><span class="badge bg-secondary"><?php echo $fila['id']; ?></span></td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($fila['nombre']); ?></td>
                                            <td><i class="bi bi-telephone text-muted me-1"></i> <?php echo htmlspecialchars($fila['telefono']); ?></td>
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
                    <div class="card-header bg-success text-white d-flex align-items-center">
                        <i class="bi bi-file-earmark-code me-2"></i>
                        <h5 class="mb-0">Respaldo NoSQL MongoDB Atlas (Nube)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID Documento (Mongo)</th>
                                        <th>Nombre</th>
                                        <th>Detalles/Observación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($tablaMG as $doc): ?>
                                        <tr>
                                            <td><small class="text-muted font-monospace"><?php echo $doc['_id']; ?></small></td>
                                            <td class="fw-semibold text-success"><?php echo htmlspecialchars($doc['nombre']); ?></td>
                                            <td><span class="text-wrap"><?php echo htmlspecialchars($doc['detalles'] ?? 'Sin detalles'); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
