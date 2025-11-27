document.addEventListener("DOMContentLoaded", function () {
    let cantidad = 1;
    const PRECIO = 40;
    const inputCantidad = document.getElementById('cantidad');
    const totalSpan = document.getElementById('total');

    function actualizarTotal() {
        const total = cantidad * PRECIO;
        totalSpan.textContent = `S/. ${total.toFixed(2)}`;
        inputCantidad.value = cantidad;
    }

    document.querySelector('[data-action="plus"]').addEventListener('click', () => {
        cantidad++;
        actualizarTotal();
    });

    document.querySelector('[data-action="minus"]').addEventListener('click', () => {
        if (cantidad > 1) {
            cantidad--;
            actualizarTotal();
        }
    });

    const form = document.getElementById('form-pago');
    const modalYape = new bootstrap.Modal(document.getElementById('modalYape'));
    const modalPlin = new bootstrap.Modal(document.getElementById('modalPlin'));
    const modalTarjeta = new bootstrap.Modal(document.getElementById('modalTarjeta'));
    const qrOutput = document.getElementById('qr');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const nombre = document.getElementById('nombre').value.trim();
        const direccion = document.getElementById('direccion').value.trim();
        const metodo = document.getElementById('metodo-pago').value;

        if (!nombre || !direccion || !metodo) {
            alert('Completa todos los campos');
            return;
        }
        if (metodo === 'yape') {
            modalYape.show();
        } else if (metodo === 'plin') {
            modalPlin.show();
        } else if (metodo === 'tarjeta') {
            modalTarjeta.show();
        }
    });
    document.querySelectorAll('.btn-generar-pago').forEach(btn => {
        btn.addEventListener('click', async () => {
            const metodo = btn.dataset.metodo;
            const nombre = document.getElementById('nombre').value.trim();
            const direccion = document.getElementById('direccion').value.trim();

            let datosAdicionales = {};
            if (metodo === 'tarjeta') {
                const num = document.getElementById('numTarjeta').value;
                const exp = document.getElementById('expTarjeta').value;
                const cvv = document.getElementById('cvvTarjeta').value;
                if (!num || !exp || !cvv) {
                    alert('Completa los datos de la tarjeta');
                    return;
                }
                datosAdicionales = { num, exp, cvv };
            }
            try {
                const res = await fetch('../php/guardar_pedido.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        cantidad,
                        nombre_completo: nombre,
                        direccion,
                        metodo_pago: metodo,
                        ...datosAdicionales
                    })
                });

                const data = await res.json();

                if (data.success) {
                    modalYape.hide();
                    modalPlin.hide();
                    modalTarjeta.hide();
                    if (metodo === 'yape') {
                        qrOutput.textContent = `Escanea el código\n\nNombre: WARMI360\nMonto: S/. ${(cantidad * PRECIO).toFixed(2)}`;
                    } else if (metodo === 'plin') {
                        qrOutput.textContent = `Escanea el código Plin\n\nMonto: S/. ${(cantidad * PRECIO).toFixed(2)}`;
                    } else if (metodo === 'tarjeta') {
                        qrOutput.textContent = `Pago con tarjeta procesado.\nMonto: S/. ${(cantidad * PRECIO).toFixed(2)}\nEstado: Pendiente de confirmación`;
                    }

                    alert('Pedido registrado correctamente');
                } else {
                    alert('Error al guardar el pedido: ' + (data.message || 'Desconocido'));
                }
            } catch (err) {
                console.error('Error completo:', err);
                alert('Error de conexión. Revisa la consola del navegador.');
            }
        });
    });
});