<?php
$postgres_ok = false;
$respaldo_ok = false;

// Variables de datos del formulario
$nombre   = $_POST["nom"] ?? '';
$telefono = $_POST["tel"] ?? '';
$detalles = $_POST["det"] ?? '';

try {
    // Conexión principal de tu base de datos en Render
    $conexion = new PDO('pgsql:host=dpg-d8f39bl53gjs739kr1c0-a.oregon-postgres.render.com;dbname=sena_4gjt','sena_4gjt_user','MdEyvmMNdVywTNkoijpetMBysaRHzQxD');
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!empty($nombre)) {
        // 1. Guardar en la tabla principal (Aprendices)
        $registrar = $conexion->prepare("INSERT INTO aprendices (nombre,telefono,detalles) VALUES (?, ?, ?)");
        $registrar->execute([$nombre, $telefono, $detalles]);
        $postgres_ok = true;

        // 2. Crear automáticamente la tabla de respaldo NoSQL/Auditoría si no existe
        $conexion->exec("CREATE TABLE IF NOT EXISTS respaldo_bitacora (
            id SERIAL PRIMARY KEY,
            documento_json TEXT,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Simular un documento NoSQL guardando la estructura en un JSON plano
        $json_data = json_encode([
            "nombre" => $nombre,
            "telefono" => $telefono,
            "detalles" => $detalles,
            "origen" => "Soporte de Emergencia Render"
        ]);

        // Guardar el respaldo
        $respaldo = $conexion->prepare("INSERT INTO respaldo_bitacora (documento_json) VALUES (?)");
        $respaldo->execute([$json_data]);
        $respaldo_ok = true;
    }

    // 3. Consultar datos para la Tabla 1 (Relacional)
    $consultaPG = $conexion->query("SELECT * FROM aprendices ORDER BY id DESC");
    $tablaPG = $consultaPG->fetchAll(PDO::FETCH_ASSOC);

    // 4. Consultar datos para la Tabla 2 (Bitácora de documentos JSON)
    $consultaRespaldo = $conexion->query("SELECT * FROM respaldo_bitacora ORDER BY id DESC");
    $tablaRS = $consultaRespaldo->fetchAll(PDO::FETCH_ASSOC);

    $conexion = null;
} catch (Exception $e) {
    $error_sistema = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Doble Persistencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container my-5">
        
        <div class="row mb-4">
            <div class="col-12">
                <?php if (!empty($nombre)): ?>
                    <?php if ($postgres_ok && $respaldo_ok): ?>
                        <div class="alert alert-success d-flex align-items-center shadow-sm" role="alert">
                            <i class="bi bi-check-circle-fill fs-3 me-3"></i>
                            <div>
                                <h4 class="alert-heading mb-1">¡Sincronización de Doble Soporte Exitosa!</h4>
                                <p class="mb-0">El estudiante <strong><?php echo htmlspecialchars($nombre); ?></strong> se registró en la estructura relacional y se generó su respaldo JSON de contingencia.</p>
                            </div>
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
                        <i class="bi bi-table me-2"></i>
                        <h5 class="mb-0">Base de Datos Principal (PostgreSQL)</h5>
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
                        <i class="bi bi-filetype-json me-2"></i>
                        <h5 class="mb-0">Esquema de Respaldo Documental (JSON Bitácora)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID Registro</th>
                                        <th>Documento / Payload Recibido</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach(($tablaRS ?? []) as $resp): ?>
                                        <tr>
                                            <td><span class="badge bg-success"># <?php echo $resp['id']; ?></span></td>
                                            <td>
                                                <code class="text-dark bg-light p-2 d-block rounded" style="font-size: 0.85rem;">
                                                    <?php echo htmlspecialchars($resp['documento_json']); ?>
                                                </code>
                                            </td>
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
