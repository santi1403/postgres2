<?php
// Desactivar reporte de errores de tipos estrictos para el driver nativo
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// Cargar librerías de Composer
require_once __DIR__ . '/../vendor/autoload.php';

$postgres_ok = false;
$mongodb_ok = false;

$nombre   = $_POST["nom"] ?? '';
$telefono = $_POST["tel"] ?? '';
$detalles = $_POST["det"] ?? '';

// ==========================================
// 1. CONEXIÓN Y REGISTRO EN POSTGRESQL
// ==========================================
try {
    $conexion = new PDO('pgsql:host=dpg-d8f39bl53gjs739kr1c0-a.oregon-postgres.render.com;dbname=sena_4gjt','sena_4gjt_user','MdEyvmMNdVywTNkoijpetMBysaRHzQxD');
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (!empty($nombre)) {
        $registrar = $conexion->prepare("INSERT INTO aprendices (nombre,telefono,detalles) VALUES (?, ?, ?)");
        $registrar->execute([$nombre, $telefono, $detalles]);
        $postgres_ok = true;
    }
    
    $consultaPG = $conexion->query("SELECT * FROM aprendices ORDER BY id DESC");
    $tablaPG = $consultaPG->fetchAll(PDO::FETCH_ASSOC);
    $conexion = null;
} catch (Exception $e) {
    $error_pg = $e->getMessage();
}

// ==========================================
// 2. CONEXIÓN Y REGISTRO EN MONGO DB ATLAS
// ==========================================
try {
    $mongoUri = "mongodb+srv://prueba:y2Ji6GgMKU47UFbf@cluster0.7dy1rur.mongodb.net/?appName=Cluster0";
    // Usamos el cliente estándar compatible de la librería
    $client = new MongoDB\Client($mongoUri, [], ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]);
    $coleccion = $client->selectCollection('sena_respaldo', 'estudiantes');
    
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
    
    // Consulta limpia de documentos
    $cursor = $coleccion->find([], ['sort' => ['_id' => -1], 'limit' => 10]);
    $tablaMG = iterator_to_array($cursor);
} catch (Exception $e) {
    $error_mg = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Doble Soporte Real</title>
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
                                <h4 class="alert-heading mb-1">¡Registro Exitoso Dual!</h4>
                                <p class="mb-0">Estudiante guardado simultáneamente en <strong>PostgreSQL</strong> y en <strong>MongoDB Atlas (Nube)</strong>.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning shadow-sm" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> 
                            Estado de Persistencia -> Postgres: <?php echo $postgres_ok ? 'CONECTADO' : 'FALLÓ'; ?> | Mongo Atlas: <?php echo $mongodb_ok ? 'CONECTADO' : 'FALLÓ/TIMEOUT'; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <h2><i class="bi bi-database-fill-gear text-primary"></i> Panel de Control Multibase de Datos</h2>
            <a href="../index.html" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver al Formulario</a>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-6">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-dark text-white d-flex align-items-center">
                        <i class="bi bi-database me-2"></i>
                        <h5 class="mb-0">Estructura Relacional (PostgreSQL)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre Completo</th>
                                        <th>Teléfono</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach(($tablaPG ?? []) as $fila): ?>
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
                        <i class="bi bi-leaf me-2"></i>
                        <h5 class="mb-0">Colección NoSQL (MongoDB Atlas)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID Documento</th>
                                        <th>Nombre</th>
                                        <th>Detalles</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach(($tablaMG ?? []) as $doc): ?>
                                        <tr>
                                            <td><small class="text-muted font-monospace"><?php echo (string)$doc['_id']; ?></small></td>
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

        </div> </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
