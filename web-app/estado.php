<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Estado de Pods - Kubernetes</title>
  <style>
    header {
        background-color: #f4f4f4;
        padding: 10px 20px;
        text-align: left; /* Cambiado de center a left */
        margin-bottom: 20px;
        border-bottom: 1px solid #ccc;
      }

      header button {
        margin-right: 10px;
        padding: 10px 20px;
        font-size: 16px;
        cursor: pointer;
      }
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
    }

    h1 {
      text-align: center;
    }

    pre {
      background-color: #f4f4f4;
      padding: 15px;
      border: 1px solid #ccc;
      overflow-x: auto;
    }

    .error {
      color: red;
      font-weight: bold;
    }
  </style>
  <script>
      function ir(page) {
        window.location.href = page;
      }
    </script>
</head>
<body>
    <header>
      <button onclick="ir('index.html')">Desplegar</button>
      <button onclick="ir('servicios.php')">Ver Servicios</button>
      <button onclick="ir('estado.php')">Ver estado de los Pods</button>
    </header>
  <h1>Estado de los Pods en Kubernetes</h1>

  <?php
    $podsOutput = shell_exec('kubectl get pods -o wide 2>&1'); //obtener pods

    if ($podsOutput === null || str_contains($podsOutput, 'Error')) { //Si es null o erroneo
      echo '<p class="error">Error al ejecutar <code>kubectl get pods</code>.</p>';
    } else { //si es correcto
      echo '<pre>' . htmlspecialchars($podsOutput) . '</pre>'; //mostrar salida con formato
    }
  ?>
</body>
</html>
