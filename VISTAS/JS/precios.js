document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('pricing-plans-container');

    if (container) {
        fetch('../AJAX/precios_ajax.php?action=listar')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.planes.length > 0) {
                    container.innerHTML = ''; // Limpiar el contenedor
                    data.planes.forEach((plan, index) => {
                        const planElement = document.createElement('div');
                        planElement.className = 'col-lg-4 col-md-6 mb-4';
                        planElement.setAttribute('data-aos', 'fade-up');
                        planElement.setAttribute('data-aos-delay', (index + 1) * 100);

                        let caracteristicasHtml = '';
                        const caracteristicas = JSON.parse(plan.caracteristicas);
                        caracteristicas.forEach(c => {
                            caracteristicasHtml += `<li><i class="fas fa-check-circle me-2"></i>${escapeHTML(c)}</li>`;
                        });

                        planElement.innerHTML = `
                            <div class="card pricing-card h-100 ${plan.recomendado == 1 ? 'recommended' : ''}">
                                ${plan.recomendado == 1 ? '<div class="recommended-badge">Recomendado</div>' : ''}
                                <div class="card-header pricing-header" style="background-color: ${escapeHTML(plan.color_header)};">
                                    <h3 class="plan-name"><i class="${escapeHTML(plan.icono)} me-2"></i>${escapeHTML(plan.nombre)}</h3>
                                    <div class="price-display">
                                        <span class="price-amount">$${escapeHTML(plan.precio_mensual)}</span>
                                        <span class="price-period">/mes</span>
                                    </div>
                                    <p class="price-anual">o $${escapeHTML(plan.precio_anual)} al año</p>
                                </div>
                                <div class="card-body">
                                    <p class="plan-description">${escapeHTML(plan.descripcion)}</p>
                                    <ul class="list-unstyled plan-features">
                                        ${caracteristicasHtml}
                                    </ul>
                                </div>
                                <div class="card-footer">
                                    <a href="registro.php?plan=${escapeHTML(plan.nombre.toLowerCase())}" class="btn btn-primary w-100 btn-elegir-plan">
                                        Elegir Plan
                                    </a>
                                </div>
                            </div>
                        `;
                        container.appendChild(planElement);
                    });
                    // Re-inicializar AOS para que las animaciones se apliquen a los nuevos elementos
                    AOS.init({
                        duration: 800,
                        once: true
                    });
                } else {
                    container.innerHTML = '<div class="col-12 text-center"><p>No se encontraron planes de precios disponibles.</p></div>';
                }
            })
            .catch(error => {
                console.error('Error al cargar los planes de precios:', error);
                container.innerHTML = '<div class="col-12 text-center"><p>Error al cargar los planes. Por favor, intente de nuevo más tarde.</p></div>';
            });
    }
});

// Función para escapar HTML y prevenir XSS
function escapeHTML(str) {
    if (str === null || str === undefined) {
        return '';
    }
    return str.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
