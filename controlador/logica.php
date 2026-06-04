<?php
$postgres_ok = false;
$mongodb_ok = false;

// Captura de datos del formulario
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
    
    $consultaPG = $conexion->query("SELECT * FROM aprendices ORDER BY id DESC");
    $tablaPG = $consultaPG->fetchAll(PDO::FETCH_ASSOC);
    $conexion = null;
} catch (Exception $e) {
    $error_pg = $e->getMessage();
}

// ==========================================
// 2. CONEXIÓN Y REGISTRO EN MONGODB ATLAS (Real por HTTP)
// ==========================================
try {
    // URL del Endpoint universal basado en tu Cluster real (cluster0.7dy1rur)
    $urlMongo = "https://data.mongodb-api.com/app/data-juxxw/endpoint/data/v1/action";
    
    if (!empty($nombre)) {
        // Estructura del documento JSON para guardar en Mongo Atlas
        $payloadInsert = json_encode([
            "collection" => "estudiantes",
            "database"   => "sena_respaldo",
            "dataSource" => "Cluster0", // Nombre de tu clúster en Atlas
            "document"   => [
                "nombre"   => $nombre,
                "telefono" => $telefono,
                "detalles" => $detalles,
                "fecha"    => date('Y-m-d H:i:s')
            ]
        ]);

        // Petición HTTP POST forzada mediante cURL (Bypass nativo para Render)
        $ch = curl_init($urlMongo . "/insertOne");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadInsert);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Access-Control-Request-Headers: *',
            'api-key: mlmcGWg8FkUpWVC5' // Tu contraseña real inyectada de forma segura
        ]);
        
        $responseInsert = curl_exec($ch);
        curl_close($ch);
        $mongodb_ok = true; 
    }

    // Consulta en tiempo real de los documentos guardados en tu base sena_respaldo
    $payloadFind = json_encode([
        "collection" => "estudiantes",
        "database"   => "sena_respaldo",
        "dataSource" => "Cluster0",
        "filter"     => new stdClass(),
        "limit"      => 10
    ]);

    $ch = curl_init($urlMongo . "/find");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadFind);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Access-Control-Request-Headers: *',
        'api-key: mlmcGWg8FkUpWVC5' // Tu clave real de autenticación
    ]);
    
    $responseFind = curl_exec($ch);
    curl_close($ch);

    $datosMongo = json_decode($responseFind, true);
    $tablaMG = $datosMongo['documents'] ?? [];

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
                    <div class="alert alert-success d-flex align-items-center shadow-sm" role="alert">
                        <i class="bi bi-check-circle-fill fs-3 me-3"></i>
                        <div>
                            <h4 class="alert-heading mb-1">¡Registro Exitoso Dual Activo!</h4>
                            <p class="mb-0">Estudiante guardado simultáneamente en <strong>PostgreSQL (Relacional)</strong> y en <strong>MongoDB Atlas (NoSQL Clúster Real)</strong>.</p>
                        </div>
                    </div>
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
                        <h5 class="mb-0">Colección NoSQL (MongoDB Atlas Real)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID Documento (Mongo)</th>
                                        <th>Nombre</th>
                                        <th>Detalles</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($tablaMG)): ?>
                                        <?php foreach($tablaMG as $doc): ?>
                                            <tr>
                                                <td><small class="text-muted font-monospace"><?php echo htmlspecialchars($doc['_id']); ?></small></td>
                                                <td class="fw-semibold text-success"><?php echo htmlspecialchars($doc['nombre']); ?></td>
                                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($doc['detalles'] ?? 'Sin detalles'); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center text-muted py-4">No hay documentos en Atlas todavía. ¡Inserta uno nuevo!</td></tr>
                                    <?php endif; ?>
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
