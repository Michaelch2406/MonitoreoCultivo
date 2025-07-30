<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planes de Precios - AgroMonitor</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link href="CSS/precios.css" rel="stylesheet">
</head>
<body>
    <!-- Incluir Navbar -->
    <?php include 'partials/navbar.php'; ?>

    <!-- Header de la página de precios -->
    <header class="precios-header">
        <div class="container text-center">
            <h1 class="display-4 fw-bold" data-aos="fade-down">Nuestros Planes</h1>
            <p class="lead" data-aos="fade-up" data-aos-delay="100">
                Elige el plan que mejor se adapte a las necesidades de tu campo.
            </p>
        </div>
    </header>

    <!-- Sección de Planes de Precios -->
    <section class="precios-section py-5">
        <div class="container">
            <div id="pricing-plans-container" class="row justify-content-center">
                <!-- Los planes de precios se cargarán aquí dinámicamente -->
                <div class="text-center">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando planes...</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Incluir Footer -->
    <?php include 'partials/footer.php'; ?>

    <!-- Scripts -->
    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../PUBLIC/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="JS/global.js"></script>
    <script src="partials/JS/navbar.js"></script>
    <script src="JS/precios.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
    </script>
</body>
</html>
