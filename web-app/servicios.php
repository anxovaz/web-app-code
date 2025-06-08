<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Gestión de Servicios</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
    }

    h1 {
      text-align: center;
    }

    pre {
      background-color: #f4f4f4;
      border: 1px solid #ccc;
      overflow-x: auto;
      padding: 15px;
    }
    .error {
      color: red;
    }

    header {
      background-color:white;
      padding: 10px 20px;
      text-align: left;
      margin-bottom: 20px;
      border-bottom: 1px solid #ccc;
    }

    header button {
      margin-right: 10px;
      padding: 10px 20px;
      font-size: 16px;
      cursor: pointer;
    }

    .deployment-row {
      align-items: center;
      display: flex;
      margin-bottom: 10px;
    }

    .deployment-name {
      flex: 1;
      font-weight: bold;
    }

    .btn {
      padding: 5px 10px;
      margin-right: 5px;
      border: none;
      color: white;
      cursor: pointer;
      border-radius: 4px;
    }

    .restart {
      background-color:rgb(28, 100, 177); /* Azul */
    }

    .delete {
      background-color:rgb(145, 11, 24); /* Rojo */
    }

    .logs {
      background-color:rgb(238, 185, 27); /* Amarillo */
      color: black;
    }

    form {
      display: inline;
    }
  </style>
  <!-- Función que acciona los botones -->
  <script>
    function ir(page) { //Función que recive como parámetro la ruta de dentro de /var/ww/localhost/htdocs
      window.location.href = page;
    }
  </script>
</head>
<body>
<header> <!-- Botones del menú superior -->
  <button onclick="ir('index.html')">Desplegar</button>
  <button onclick="ir('servicios.php')">Ver Servicios</button>
  <button onclick="ir('estado.php')">Ver estado de los Pods</button>
</header>

<h1>Servicios en Ejecución</h1>
<!-- Servicios en ejecución -->
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $nombre = escapeshellarg($_POST['name']);
    $accion = $_POST['action'] ?? null; //recoge la accion con el metodo post
    $accion2 = $_POST['action2'] ?? null;

    if ($accion === 'restart') {
        //Con 2>$1 indico que la salida de errores salga por la salida normal, permitiendo así mostrar la salida tanto si es correcta como incorrecta de una única forma
        $resultado = shell_exec("kubectl rollout restart deployment $nombre 2>&1");
        echo "<pre>Reinicio de $nombre:\n" . htmlspecialchars($resultado) . "</pre>";  //htmlspecialchars($result) -> Muestra la salida de la ejecución de shell_exec modificandola con htmlspecialchars para que aparezca en texto plano

    } elseif ($accion === 'delete') {
        $resultado = shell_exec("kubectl delete deployment $nombre 2>&1");
        echo "<pre>Eliminación de $nombre:\n" . htmlspecialchars($resultado) . "</pre>"; 

    } elseif ($accion === 'logs') {
        $pods = shell_exec("kubectl get pods -l app=$nombre -o jsonpath='{.items[0].metadata.name}' 2>&1");
        $logs = shell_exec("kubectl logs $pods 2>&1");
        echo "<pre>Logs de $nombre:\n" . htmlspecialchars($logs) . "</pre>";
    }

    if ($accion2 === "delete") {
        $resultado = shell_exec("kubectl delete svc $nombre 2>&1");
        echo "<pre>Eliminación de $nombre:\n" . htmlspecialchars($resultado) . "</pre>"; 

    }
}

// Mostrar servicios
$salida_svc = shell_exec('kubectl get svc -o custom-columns=NAME:.metadata.name --no-headers 2>&1'); //Muestra todos los servicios en ejecución en el clúster
if ($salida_svc === null || str_contains($salida_svc, 'Error')) { //si la variable es null (comando ejecutado erróneo)
  echo '<p class="error">Error al ejecutar kubectl get svc, contacte con el administrador, este error puede deberse a que no existen servicios en ejecución</p>';
} else {
    //trim elimina espacios en blanco y tabulaciones
    $servicios = explode("\n", trim($salida_svc)); //separa cada espacio (enter) en elementos para ser usado por el foreach
    foreach ($servicios as $servicio) { //por cada servicio
        $nombre = trim($servicio); 
        if ($nombre === '') continue;
        echo '<div class="deployment-row">';
        echo '<div class="deployment-name">' . htmlspecialchars($nombre) . '</div>';
        //Por cada salida mostrará los siguientes botones
        // Botón Eliminar
        echo '<form method="POST">
                <input type="hidden" name="name" value="' . htmlspecialchars($nombre) . '">
                <input type="hidden" name="action2" value="delete">
                <button class="btn delete" type="submit">Eliminar</button>
              </form>';
        echo '</div>';
    }
}

// Mostrar Deployments con botones
echo '<h1>Deployments</h1>';
$salida_despliegues = shell_exec('kubectl get deployments -o custom-columns=NAME:.metadata.name --no-headers 2>&1'); //Personaliza la salida para mostrar únicamente el campo .metadata.name

if ($salida_despliegues === null || str_contains($salida_despliegues, 'Error')) { //si la variable es null (comando ejecutado erróneo)
    echo '<p class="error">Error al ejecutar kubectl get deployments, contacte con el administrador, este error puede deberse a que no existen servicios en ejecución</p>';
} else {
    //trim elimina espacios en blanco y tabulaciones
    $despliegues = explode("\n", trim($salida_despliegues)); //separa cada espacio (enter) en elementos para ser usado por el foreach
    foreach ($despliegues as $despliegue) { //por cada deployment
        $nombre = trim($despliegue); 
        if ($nombre === '') continue;
        echo '<div class="deployment-row">';
        echo '<div class="deployment-name">' . htmlspecialchars($nombre) . '</div>';

        //Por cada salida mostrará los siguientes botones
        
        // Botón Reiniciar
        echo '<form method="POST">
                <input type="hidden" name="name" value="' . htmlspecialchars($nombre) . '">
                <input type="hidden" name="action" value="restart">
                <button class="btn restart" type="submit">Reiniciar</button>
              </form>';

        // Botón Eliminar
        echo '<form method="POST">
                <input type="hidden" name="name" value="' . htmlspecialchars($nombre) . '">
                <input type="hidden" name="action" value="delete">
                <button class="btn delete" type="submit">Eliminar</button>
              </form>';

        // Botón Ver logs
        echo '<form method="POST">
                <input type="hidden" name="name" value="' . htmlspecialchars($nombre) . '">
                <input type="hidden" name="action" value="logs">
                <button class="btn logs" type="submit">Ver Logs</button>
              </form>';

        echo '</div>';
    }
}
?>
</body>
</html>
