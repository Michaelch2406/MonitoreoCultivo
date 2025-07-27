<?php
session_start();
require_once('../../CONFIG/roles.php');
require_once('../../MODELOS/usuarios_m.php');

// Verificar que sea administrador
requiereAdmin('../login.php');

$usuario_modelo = new Usuario();
$usuarios = $usuario_modelo->listarUsuarios();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Sistema de Cultivos</title>
    <link href="../../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../DataTables/datatables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../CSS/dashboard.css" rel="stylesheet">
    <link href="../CSS/admin_usuarios.css" rel="stylesheet">
</head>
<body>
    <?php include('../partials/navbar.php'); ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-users"></i> Gestión de Usuarios</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
                        <i class="fas fa-plus"></i> Nuevo Usuario
                    </button>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="filtroRol" class="form-label">Filtrar por Rol:</label>
                                <select class="form-select" id="filtroRol">
                                    <option value="">Todos los roles</option>
                                    <option value="administrador">Administrador</option>
                                    <option value="agricultor">Agricultor</option>
                                    <option value="supervisor">Supervisor</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filtroEstado" class="form-label">Filtrar por Estado:</label>
                                <select class="form-select" id="filtroEstado">
                                    <option value="">Todos los estados</option>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-secondary" id="btnLimpiarFiltros">
                                    <i class="fas fa-broom"></i> Limpiar Filtros
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de usuarios -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaUsuarios" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Fecha Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($usuarios['success']): ?>
                                        <?php foreach ($usuarios['usuarios'] as $usuario): ?>
                                            <tr data-rol="<?php echo $usuario['rol']; ?>" data-estado="<?php echo $usuario['estado']; ?>">
                                                <td><?php echo $usuario['usuario_id']; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="../../PUBLIC/Img/user.png" alt="Avatar" class="user-avatar me-2">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></strong>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['telefono'] ?: '-'); ?></td>
                                                <td>
                                                    <span class="badge <?php echo obtenerColorRol($usuario['rol']); ?>">
                                                        <?php echo obtenerTextoRol($usuario['rol']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge status-badge <?php echo $usuario['estado'] == 'activo' ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo ucfirst($usuario['estado']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                                                <td>
                                                    <div class="btn-group-actions">
                                                        <button type="button" class="btn btn-sm btn-outline-primary btn-editar" 
                                                                data-id="<?php echo $usuario['usuario_id']; ?>"
                                                                data-bs-toggle="modal" data-bs-target="#modalEditarUsuario">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        
                                                        <?php if ($usuario['estado'] == 'activo'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-warning btn-cambiar-estado" 
                                                                    data-id="<?php echo $usuario['usuario_id']; ?>" 
                                                                    data-estado="inactivo"
                                                                    title="Desactivar">
                                                                <i class="fas fa-user-slash"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-outline-success btn-cambiar-estado" 
                                                                    data-id="<?php echo $usuario['usuario_id']; ?>" 
                                                                    data-estado="activo"
                                                                    title="Activar">
                                                                <i class="fas fa-user-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <button type="button" class="btn btn-sm btn-outline-info btn-resetear-password" 
                                                                data-id="<?php echo $usuario['usuario_id']; ?>"
                                                                title="Resetear Contraseña">
                                                            <i class="fas fa-key"></i>
                                                        </button>
                                                        
                                                        <?php if ($usuario['rol'] != 'administrador' || $usuario['usuario_id'] != $_SESSION['user_id']): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar" 
                                                                    data-id="<?php echo $usuario['usuario_id']; ?>"
                                                                    data-nombre="<?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Usuario -->
    <div class="modal fade" id="modalNuevoUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formNuevoUsuario">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nuevoNombre" class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="nuevoNombre" name="nombre" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nuevoApellido" class="form-label">Apellido *</label>
                                    <input type="text" class="form-control" id="nuevoApellido" name="apellido" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="nuevoEmail" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="nuevoEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="nuevoTelefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="nuevoTelefono" name="telefono">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nuevoRol" class="form-label">Rol *</label>
                                    <select class="form-select" id="nuevoRol" name="rol" required>
                                        <option value="">Seleccionar rol</option>
                                        <option value="administrador">Administrador</option>
                                        <option value="agricultor">Agricultor</option>
                                        <option value="supervisor">Supervisor</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nuevoEstado" class="form-label">Estado *</label>
                                    <select class="form-select" id="nuevoEstado" name="estado" required>
                                        <option value="activo">Activo</option>
                                        <option value="inactivo">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="nuevoPassword" class="form-label">Contraseña *</label>
                            <input type="password" class="form-control" id="nuevoPassword" name="password" required>
                            <div class="form-text">Mínimo 8 caracteres, al menos una mayúscula, una minúscula y un número.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmarPassword" class="form-label">Confirmar Contraseña *</label>
                            <input type="password" class="form-control" id="confirmarPassword" name="confirmar_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div class="modal fade" id="modalEditarUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit"></i> Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarUsuario">
                    <input type="hidden" id="editarUsuarioId" name="usuario_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editarNombre" class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="editarNombre" name="nombre" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editarApellido" class="form-label">Apellido *</label>
                                    <input type="text" class="form-control" id="editarApellido" name="apellido" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editarEmail" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="editarEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="editarTelefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="editarTelefono" name="telefono">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editarRol" class="form-label">Rol *</label>
                                    <select class="form-select" id="editarRol" name="rol" required>
                                        <option value="administrador">Administrador</option>
                                        <option value="agricultor">Agricultor</option>
                                        <option value="supervisor">Supervisor</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editarEstado" class="form-label">Estado *</label>
                                    <select class="form-select" id="editarEstado" name="estado" required>
                                        <option value="activo">Activo</option>
                                        <option value="inactivo">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../PUBLIC/jquery-3.7.1.min.js"></script>
    <script src="../../DataTables/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../JS/admin_usuarios.js"></script>
</body>
</html>