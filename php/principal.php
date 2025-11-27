<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>WARMI360 - Inicio</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="icon" href="../img/image.png" type="image/jpeg">
</head>
<body class="custom-bg-light">

<header>
    <img src="../img/warmilogo.png" class="logo" onclick="location.href='principal.php'">
    <nav>
        <ul>
            <li><a href="principal.php">Inicio</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Mi Perfil</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../evidencias.html">Evidencias</a></li>
                        <li><a class="dropdown-item" href="anillo.php">Anillo</a></li>
                        <li><a class="dropdown-item" href="logout.php">Cerrar sesión</a></li>
                    </ul>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<main>
    <!-- Hero Section -->
    <section class="hero-section text-white py-5 position-relative">
        <img src="../img/fondo_principal.jpeg" alt="Fondo principal" class="position-absolute w-100 h-100 top-0 start-0" style="object-fit: cover; z-index: -1;">
        <div class="container text-center position-relative" style="z-index: 2;">
            <h1 class="display-3 fw-bold mb-4">Bienvenidas a WARMI360</h1>
            <p class="fs-5 text-light mb-5">
                La violencia no solo son golpes, es el intento de callarte. <br>No dejes que te silencien. Todas las mujeres<br>merecen vivir libres, sin miedo y sin cadenas.
            </p>
            <a href="#que-es" class="btn custom-btn-secondary btn-lg fw-bold shadow">Descubre cómo funciona</a>
        </div>
    </section>
    <section id="que-es" class="py-5 custom-bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center">
                    <img src="../img/warmilogo.png" alt="Logo WARMI 360" class="img-fluid rounded-3 shadow" style="max-width: 18rem;">
                </div>
                <div class="col-md-6">
                    <h2 class="display-6 fw-bold custom-text-dark mb-4">¿Qué es WARMI 360?</h2>
                    <p class="text-secondary mb-4">
                        WARMI360 es una plataforma diseñada para proteger a las mujeres peruanas mediante tres componentes: un anillo inteligente, una aplicación móvil y una plataforma web.
                    </p>
                    <p>
                        Ofrecemos herramientas accesibles y empáticas para la conexión con redes de apoyo y la identificación temprana de la violencia.
                    </p>
                </div>
            </div>
        </div>
    </section>
    <section id="equipo" class="container py-5">
        <h2 class="text-center mb-4 fw-bold custom-text-dark">Nuestro equipo</h2>
        <div class="row g-2 justify-content-center">
            <div class="col-md-3 text-center">
                <div class="card border-0 shadow-sm p-4">
                    <img src="../img/alejandro.jpg" class="rounded-circle mb-3" width="200" alt="Miembro del equipo">
                    <h6 class="fw-bold">Alejandro De La Cruz Escate</h6>
                    <small class="text-muted">Líder de proyecto</small>
                    <p class="text-muted small">Líder de proyecto y Co-creador de la app móvil WARMI360</p>
                </div>
            </div>

            <div class="col-md-3 text-center">
                <div class="card border-0 shadow-sm p-4">
                    <img src="../img/Lynn.jpg" class="rounded-circle mb-3" width="200" alt="Miembro del equipo">
                    <h6 class="fw-bold">Lyn Jhong Donayre</h6>
                    <small class="text-muted">Desarrolladora Desktop</small>
                    <p class="text-muted small">Desarrolladora de la plataforma de escritorio</p>
                </div>
            </div>

            <div class="col-md-3 text-center">
                <div class="card border-0 shadow-sm p-4">
                    <img src="../img/Alyssa.jpg" class="rounded-circle mb-3" width="200" alt="Miembro del equipo">
                    <h6 class="fw-bold">Alyssa Lévano Hernández</h6>
                    <small class="text-muted">Co-creadora App Móvil</small>
                    <p class="text-muted small">Co-creadora de la app móvil WARMI360</p>
                </div>
            </div>
        </div>
    </section>
</main>

<footer>
    <p>&copy; 2025 WARMI360</p>
</footer>

<script src="../js/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
