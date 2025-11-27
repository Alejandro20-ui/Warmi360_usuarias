document.addEventListener('DOMContentLoaded', async () => {
    const tabla = document.querySelector('#tablaEvidencias tbody');

    try {
        const res = await fetch('php/listar_evidencias.php');
        if (!res.ok) throw new Error('Error en la red');

        const data = await res.json();

        if (data.error) {
            tabla.innerHTML = `<tr><td colspan="5">${data.error}</td></tr>`;
            return;
        }

        if (data.message) {
            tabla.innerHTML = `<tr><td colspan="5">${data.message}</td></tr>`;
            return;
        }

        tabla.innerHTML = '';

        data.forEach(ev => {
            const preview = ev.tipo === 'foto'
                ? `<img src="php/descargar.php?id=${ev.id}" alt="Evidencia">`
                : `<video src="php/descargar.php?id=${ev.id}" controls width="150"></video>`;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${ev.id}</td>
                <td>${ev.tipo}</td>
                <td>${preview}</td>
                <td>${ev.fecha_captura}</td>
                <td><a href="php/descargar.php?id=${ev.id}&download=1" class="btn-descarga">Descargar</a></td>
            `;
            tabla.appendChild(tr);
        });

    } catch (err) {
        console.error('Error al cargar evidencias:', err);
        tabla.innerHTML = '<tr><td colspan="5">Error al cargar evidencias</td></tr>';
    }
});