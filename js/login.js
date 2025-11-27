document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('#loginForm');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();  // Evita que el formulario se envíe de forma tradicional

        const nombre = document.querySelector('#nombre').value;
        const apellidos = document.querySelector('#apellidos').value;
        const correo = document.querySelector('#correo').value;

        // Verificar si los campos están completos
        if (!nombre || !apellidos || !correo) {
            alert('Por favor completa todos los campos');
            return;
        }

        try {
            // Enviar los datos al servidor
            const res = await fetch('php/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',  // Asegúrate de que este header es correcto
                },
                body: `nombre=${nombre}&apellidos=${apellidos}&correo=${correo}`  // Enviar datos con formulario tradicional
            });

            const data = await res.text();  // Recibimos la respuesta como texto

            if (data.includes('Credenciales incorrectas')) {
                alert('Error: Credenciales incorrectas');
            } else {
                // Si todo fue correcto, redirigimos al usuario
                window.location.href = 'php/index.php';  // Cambié la redirección para que apunte a php/index.php
            }
        } catch (error) {
            console.error('Error al enviar la solicitud:', error);
            alert('Hubo un problema al procesar tu solicitud.');
        }
    });
});
