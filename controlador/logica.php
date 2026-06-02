<?php

var_dump($_POST);

$conexion = new PDO('pgsql:host=@dpg-d8f39bl53gjs739kr1c0-a.oregon-postgres.render.com;dbname=sena_4gjt_user','sena_4gjt','MdEyvmMNdVywTNkoijpetMBysaRHzQxD');
$registrar = $conexion->prepare("INSERT INTO aprendices (nombre,telefono,detalles) VALUES (?, ?)");
$registrar->execute([$_POST["nom"], $_POST["tel"] ]);
echo "<p style='color:white;background-color:green;font-family:calibri,arial;font-size:24px;text-align:center'>Registro exitoso</p>";

$consulta = $conexion->prepare("SELECT * FROM aprendices order by id");
$consulta->execute();
$tabla = $consulta->fetchAll(PDO::FETCH_ASSOC);	      //PDO::FETCH_NUM
$conexion = null;

echo "<table><tr><th>Codigo</th>
                 <th>Nombre completo</th>
                 <th>Contacto</th>
			     	</tr>";
foreach($tabla as $fila){		//Recorre el arreglo $tabla como FETCH_NUM
    echo "<tr>		<td>$fila[id]</td>
            		<td>$fila[nombre]</td>
            		<td>$fila[telefono]</td>
            			</tr>";
}
echo "</table>";



?>
