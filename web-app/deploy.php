<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") { //si el método es POST
    $nameInput = $_POST["name"] ?? "";
    $selectedImage = $_POST["image"] ?? "";
    $customImage = $_POST["customImage"] ?? "";

    // Limpiar y validar nombre
    $name = escapeshellarg($nameInput);
    $name = trim($name, "'"); 

    // Obtener imagen
    if ($selectedImage === "otro") {
        $image = trim($customImage);
        $tipo = "custom";
    } else {
        $image = trim($selectedImage);
        $tipo = $image; // ya incluye 'wordpress:latest', 'mysql:latest', ...
    }

    if (empty($name) || empty($image)) {
        echo "Faltan datos necesarios.";
        exit;
    }

    if ($tipo === "nginx:latest") { //Si la imagen es nginx
$yaml = <<<YAML
apiVersion: apps/v1
kind: Deployment
metadata:
  name: {$name}
spec:
  replicas: 5
  selector:
    matchLabels:
      app: {$name}
  template:
    metadata:
      labels:
        app: {$name}
    spec:
      containers:
      - name: {$name}
        image: {$image}
        ports:
        - containerPort: 80
---
apiVersion: v1
kind: Service
metadata:
  name: {$name}-servicio
spec:
  type: NodePort
  selector:
    app: {$name}
  ports:
  - protocol: TCP
    port: 80           # Puerto accesible desde dentro del cluster
    targetPort: 80       # Puerto en el contenedor

YAML;

    } elseif ($tipo === "mysql:latest") { //si la imágen es mysql
        $yaml = <<<YAML
apiVersion: apps/v1
kind: Deployment
metadata:
  name: {$name}
spec:
  replicas: 5
  selector:
    matchLabels:
      app: {$name}
  template:
    metadata:
      labels:
        app: {$name}
    spec:
      containers:
      - name: {$name}
        image: {$image}
        env:
        - name: MYSQL_ROOT_PASSWORD
          value: "123456"
        ports:
        - containerPort: 3306
---
apiVersion: v1
kind: Service
metadata:
  name: {$name}-servicio
spec:
  type: NodePort
  selector:
    app: {$name}
  ports:
  - protocol: TCP
    port: 3306           # Puerto accesible desde dentro del cluster
    targetPort: 3306       # Puerto en el contenedor
YAML;
}elseif($tipo === "delfer/alpine-ftp-server:latest"){ //si es ftp
    $yaml = <<<YAML
apiVersion: apps/v1
kind: Deployment
metadata:
  name: {$name}
spec:
  replicas: 5
  selector:
    matchLabels:
      app: {$name}
  template:
    metadata:
      labels:
        app: {$name}
    spec:
      containers:
      - name: {$name}
        image: {$image}
        ports:
        - containerPort: 21
        volumeMounts:
        - name: ftp-volume
          mountPath: /home/ftp
        env:
        - name: USERS
          value: "ftp|"
        - name: ANONYMOUS_ENABLE
          value: "YES"
        - name: WRITE_ENABLE
          value: "YES"
        - name: ANON_UPLOAD_ENABLE
          value: "YES"
        - name: ANON_MKDIR_WRITE_ENABLE
          value: "YES"
        - name: ANON_OTHER_WRITE_ENABLE
          value: "YES"
      volumes:
      - name: ftp-volume
        emptyDir: {}
---
apiVersion: v1
kind: Service
metadata:
  name: {$name}-servicio
spec:
  type: NodePort
  selector:
    app: {$name}
  ports:
  - protocol: TCP
    port: 21           # Puerto accesible desde dentro del cluster
    targetPort: 21       # Puerto en el contenedor
      
YAML;


  }else { //si es otra
        $yaml = <<<YAML
apiVersion: apps/v1
kind: Deployment
metadata:
  name: {$name}
spec:
  replicas: 1
  selector:
    matchLabels:
      app: {$name}
  template:
    metadata:
      labels:
        app: {$name}
    spec:
      containers:
      - name: {$name}
        image: {$image}
YAML;
    }
    $descriptorspec = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ];

    // Ejecutar el comando 'kubectl apply -f -' que aplica la configuración YAML desde la entrada estándar
    $process = proc_open('kubectl apply -f -', $descriptorspec, $pipes);

    if (is_resource($process)) { // Si el proceso se inicia correctamente
        // Enviar el el YAML generado al proceso
        fwrite($pipes[0], $yaml);
        fclose($pipes[0]);
                                
        // Leer la salida del proceso 
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        // Cerrar el proceso y obtener su código de salida
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode === 0) { //si es correcto
            echo "<p>Servicio levantado correctamente:</p><pre>$output</pre>";
        } else { //si no lo es
            echo "<p>Error al desplegar:</p><pre>$errors</pre>";
        }
    } else { //si no se inicia correctamente
        echo "Error al ejecutar el comando.";
    }
}
?>
