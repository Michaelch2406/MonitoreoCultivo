<?php
session_start();
require_once '../CONFIG/roles.php';
require_once '../MODELOS/usuarios_m.php';

// Verificar que el usuario esté logueado
requiereLogin('login.php');

$usuario_modelo = new Usuario();
$usuario_actual = obtenerUsuarioActual();
$usuario_datos = $usuario_modelo->obtenerUsuario($usuario_actual['id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Sistema de Cultivos</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="CSS/dashboard.css" rel="stylesheet">
    <link href="CSS/perfil.css" rel="stylesheet">
</head>
<body>
    <?php include('partials/navbar.php'); ?>

    <div class="container-fluid mt-4">
        <!-- Header del perfil -->
        <div class="profile-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <img src="../PUBLIC/Img/user.png" alt="Avatar" class="profile-avatar me-4">
                            <div>
                                <h2 class="mb-1"><?php echo htmlspecialchars($usuario_datos['user']['nombre'] . ' ' . $usuario_datos['user']['apellido']); ?></h2>
                                <p class="mb-1 fs-5"><?php echo htmlspecialchars($usuario_datos['user']['email']); ?></p>
                                <span class="badge <?php echo obtenerColorRol($usuario_datos['user']['rol']); ?> fs-6">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    <?php echo obtenerTextoRol($usuario_datos['user']['rol']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="stats-card">
                            <h5 class="mb-0">Miembro desde</h5>
                            <p class="mb-0 fs-4"><?php echo date('d/m/Y', strtotime($usuario_datos['user']['fecha_creacion'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Información personal -->
            <div class="col-lg-8">
                <div class="card profile-card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-edit me-2"></i>
                            Información Personal
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="formActualizarPerfil">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="nombre" name="nombre" 
                                               value="<?php echo htmlspecialchars($usuario_datos['user']['nombre']); ?>" required>
                                        <label for="nombre">Nombre</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="apellido" name="apellido" 
                                               value="<?php echo htmlspecialchars($usuario_datos['user']['apellido']); ?>" required>
                                        <label for="apellido">Apellido</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($usuario_datos['user']['email']); ?>" required readonly>
                                        <label for="email">Email</label>
                                        <div class="form-text">El email no se puede modificar. Contacta al administrador si necesitas cambiarlo.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="tel" class="form-control" id="telefono" name="telefono" 
                                               value="<?php echo htmlspecialchars($usuario_datos['user']['telefono'] ?: ''); ?>">
                                        <label for="telefono">Teléfono</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Cambiar contraseña -->
                <div class="card profile-card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-lock me-2"></i>
                            Seguridad
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="formCambiarPassword">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="passwordActual" name="password_actual" required>
                                        <label for="passwordActual">Contraseña Actual</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="passwordNueva" name="password_nueva" required>
                                        <label for="passwordNueva">Nueva Contraseña</label>
                                        <div class="password-strength" id="passwordStrength"></div>
                                        <div class="form-text" id="passwordHelp">
                                            Mínimo 8 caracteres, una mayúscula, una minúscula y un número
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="passwordConfirmar" name="password_confirmar" required>
                                        <label for="passwordConfirmar">Confirmar Contraseña</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key me-2"></i>Cambiar Contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Panel lateral -->
            <div class="col-lg-4">
                <!-- Foto de perfil -->
                <div class="card profile-card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-image me-2"></i>
                            Foto de Perfil
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="../PUBLIC/Img/user.png" alt="Avatar" class="profile-avatar mb-3" id="avatarPreview">
                        <div class="mb-3">
                            <input type="file" class="form-control" id="avatarUpload" accept="image/*" style="display: none;">
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('avatarUpload').click();">
                                <i class="fas fa-upload me-2"></i>Cambiar Foto
                            </button>
                        </div>
                        <p class="text-muted small">Formatos: JPG, PNG. Tamaño máximo: 2MB</p>
                    </div>
                </div>

                <!-- Estadísticas del usuario -->
                <?php if ($usuario_actual['rol'] == 'agricultor'): ?>
                <div class="card profile-card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Mis Estadísticas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Fincas Registradas</span>
                            <span class="badge bg-primary">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Siembras Activas</span>
                            <span class="badge bg-success">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Monitoreos Este Mes</span>
                            <span class="badge bg-info">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Cosechas Registradas</span>
                            <span class="badge bg-warning">0</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Actividad reciente -->
                <div class="card profile-card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>
                            Actividad Reciente
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary rounded-circle p-2 me-3">
                                <i class="fas fa-sign-in-alt text-white"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Inicio de Sesión</h6>
                                <small class="text-muted">Hoy - <?php echo date('H:i'); ?></small>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success rounded-circle p-2 me-3">
                                <i class="fas fa-user-edit text-white"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Perfil Actualizado</h6>
                                <small class="text-muted">Última actualización del perfil</small>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <a href="#" class="btn btn-outline-primary btn-sm">Ver Todo el Historial</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../PUBLIC/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="JS/perfil.js"></script>
</body>
</html>