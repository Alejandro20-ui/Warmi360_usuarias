<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
$user_id = $_SESSION['user_id'];
require_once __DIR__ . '/../backend/conexion.php';
$stmt = $conn->prepare("SELECT nombre, apellidos FROM usuarios WHERE id = :id");
$stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die('Usuario no encontrado');
}

$nombreCompleto = $usuario['nombre'] . ' ' . $usuario['apellidos'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anillo Inteligente - WARMI360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="icon" href="../img/image.png" type="image/jpeg">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@7.5.1/ol.css">

</head>

<body class="custom-bg-light">

    <header>
        <img src="../img/warmilogo.png" class="logo" onclick="location.href='principal.php'" alt="Logo de WARMI360">
        <nav>
            <ul>
                <li><a href="principal.php">Inicio</a></li>
                <li><a href="../evidencias.html">Evidencias</a></li>
                <li><a class="dropdown-item" href="logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>

    <main class="container my-5">

        <h1 class="text-center fw-light custom-text-dark">💜 Compra tu Anillo Inteligente</h1>

        <div class="row justify-content-center mt-4">
            <div class="col-md-6">
                <div class="card custom-bg-info border-0 shadow-lg">
                    <div class="row g-0">
                        <div class="col-5 p-3 d-flex align-items-center justify-content-center">
                            <img src="../img/anillo.png" class="img-fluid" alt="Anillo WARMI360">
                        </div>
                        <div class="col-7 p-3">
                            <h5 class="fw-bold custom-text-dark">Anillo WARMI360</h5>
                            <p class="fs-5 custom-text-purple">Precio unitario: S/. 40.00</p>

                            <div class="d-flex align-items-center mb-3">
                                <label class="form-label me-3">Cantidad:</label>
                                <div class="input-group input-cantidad">
                                    <button class="btn custom-btn-purple" type="button" data-action="minus">
                                        <i class="fas fa-minus text-white"></i>
                                    </button>
                                    <input type="number" id="cantidad" class="form-control text-center" value="1"
                                        readonly>
                                    <button class="btn custom-btn-purple" type="button" data-action="plus">
                                        <i class="fas fa-plus text-white"></i>
                                    </button>
                                </div>
                            </div>

                            <p class="fs-5">Total:
                                <span id="total" class="fw-bold custom-text-purple">S/. 40.00</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-4 custom-bg-info rounded shadow-lg">
                    <h4 class="custom-text-dark border-bottom pb-2">Datos de Entrega</h4>
                    <form id="form-pago">
                        <div class="mb-3">
                            <label class="form-label">Nombre completo:</label>
                            <input type="text" id="nombre" class="form-control"
                                value="<?= htmlspecialchars($nombreCompleto) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dirección:</label>
                            <input type="text" id="direccion" class="form-control" required
                                placeholder="Ingresa tu dirección">

                            <input type="hidden" id="lat">
                            <input type="hidden" id="lon">

                            <!-- Lista de sugerencias -->
                            <ul id="suggestions" class="list-group position-absolute w-100" style="z-index: 1000;"></ul>

                        </div>

                        <div id="map" style="width:100%; height:300px;"></div>

                        <div class="mb-3">
                            <label class="form-label">Método de pago:</label>
                            <select id="metodo-pago" class="form-select" required>
                                <option value="" disabled selected>Selecciona una opción</option>
                                <option value="yape">Yape</option>
                                <option value="plin">Plin</option>
                                <option value="tarjeta">Tarjeta</option>
                            </select>
                        </div>

                        <button class="btn custom-btn-purple w-100" id="btnPagar">
                            <i class="fas fa-qrcode me-2"></i> Generar Pago
                        </button>
                    </form>

                    <div class="mt-4">
                        <p class="fw-bold">Detalle:</p>
                        <pre id="qr" class="p-3 border rounded custom-bg-light"></pre>
                    </div>
                </div>
            </div>
        </div>

    </main>
    <div class="modal fade" id="modalYape" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pago con Yape</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p><strong>Escanea el código QR</strong></p>
                    <img src="../img/qryape.jpeg" alt="QR Yape" class="img-fluid my-3" style="max-width:200px;">
                    <p><small>Titular: WARMI360 SAC</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn custom-btn-purple btn-generar-pago" data-metodo="yape">Generar
                        Pago</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalPlin" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pago con Plin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p><strong>Escanea el código QR</strong></p>
                    <img src="../img/qrplin.jpeg" alt="QR Plin" class="img-fluid my-3" style="max-width:200px;">
                    <p><small>Titular: WARMI360 SAC</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn custom-btn-purple btn-generar-pago" data-metodo="plin">Generar
                        Pago</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalTarjeta" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pago con Tarjeta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Número de tarjeta</label>
                        <input type="text" id="numTarjeta" class="form-control" placeholder="1234 5678 9012 3456">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label class="form-label">Vencimiento (MM/AA)</label>
                            <input type="text" id="expTarjeta" class="form-control" placeholder="12/28">
                        </div>
                        <div class="col-6">
                            <label class="form-label">CVV</label>
                            <input type="text" id="cvvTarjeta" class="form-control" placeholder="123">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn custom-btn-purple btn-generar-pago" data-metodo="tarjeta">Generar
                        Pago</button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 WARMI360 - Todos los derechos reservados</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/ol@7.5.1/dist/ol.js"></script>


    <script src="../js/anillo.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
