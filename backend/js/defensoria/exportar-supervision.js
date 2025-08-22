/**
 * Módulo de Exportación para Supervisión - DIGI
 * exportar-supervision.js - Versión corregida para descarga directa
 */

/**
 * Función principal de exportación
 */
function exportarSupervision() {
  const filasVisibles = document.querySelectorAll(
    'tbody tr.fila-documento[data-id]:not([style*="display: none"]):not(.mensaje-resultados)'
  );
  const cantidad = filasVisibles.length;

  if (cantidad === 0) {
    Swal.fire({
      icon: "warning",
      title: "Sin datos para exportar",
      confirmButtonColor: "#6c5ce7",
    });
    return;
  }

  Swal.fire({
    title: "Exportar Reporte de Supervisión",
    html: `
            <div style="text-align: left; padding: 1rem;">
                <div style="margin: 1rem 0;">
                    <label style="display: flex; align-items: center; margin-bottom: 1rem; cursor: pointer; padding: 0.5rem; border-radius: 8px; transition: all 0.2s; border: 2px solid transparent;" onmouseover="this.style.borderColor='#6c5ce7'; this.style.background='#f8f9fa'" onmouseout="this.style.borderColor='transparent'; this.style.background='white'">
                        <input type="radio" name="formato" value="excel" checked style="margin-right: 1rem;"> 
                        <i class="fas fa-file-excel" style="color: #10B981; margin-right: 0.5rem; font-size: 1.2rem;"></i>
                        <div>
                            <strong>Excel (.xlsx)</strong>
                            <div style="font-size: 0.8rem; color: #666;">Formato profesional con estilos</div>
                        </div>
                    </label>
                    <label style="display: flex; align-items: center; margin-bottom: 1rem; cursor: pointer; padding: 0.5rem; border-radius: 8px; transition: all 0.2s; border: 2px solid transparent;" onmouseover="this.style.borderColor='#6c5ce7'; this.style.background='#f8f9fa'" onmouseout="this.style.borderColor='transparent'; this.style.background='white'">
                        <input type="radio" name="formato" value="pdf" style="margin-right: 1rem;"> 
                        <i class="fas fa-file-pdf" style="color: #EF4444; margin-right: 0.5rem; font-size: 1.2rem;"></i>
                        <div>
                            <strong>PDF</strong>
                            <div style="font-size: 0.8rem; color: #666;">Para impresión y visualización</div>
                        </div>
                    </label>
                    <label style="display: flex; align-items: center; cursor: pointer; padding: 0.5rem; border-radius: 8px; transition: all 0.2s; border: 2px solid transparent;" onmouseover="this.style.borderColor='#6c5ce7'; this.style.background='#f8f9fa'" onmouseout="this.style.borderColor='transparent'; this.style.background='white'">
                        <input type="radio" name="formato" value="csv" style="margin-right: 1rem;"> 
                        <i class="fas fa-file-csv" style="color: #3B82F6; margin-right: 0.5rem; font-size: 1.2rem;"></i>
                        <div>
                            <strong>CSV</strong>
                            <div style="font-size: 0.8rem; color: #666;">Datos básicos separados por comas</div>
                        </div>
                    </label>
                </div>
                <div style="background: linear-gradient(135deg, #e8f5e8, #d4edda); padding: 1rem; border-radius: 8px; border-left: 4px solid #28a745; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <p style="margin: 0; font-size: 0.9rem; color: #155724;">
                        <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i> 
                        Se exportarán <strong style="color: #0d4f1c;">${cantidad}</strong> documento${
      cantidad !== 1 ? "s" : ""
    }
                    </p>
                </div>
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-download"></i> Generar Archivo',
    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
    confirmButtonColor: "#6c5ce7",
    cancelButtonColor: "#6c757d",
    width: "450px",
    preConfirm: () =>
      document.querySelector('input[name="formato"]:checked').value,
  }).then((result) => {
    if (result.isConfirmed) {
      procesarExportacion(result.value, cantidad);
    }
  });
}

/**
 * Procesar exportación con animación mejorada
 */
function procesarExportacion(formato, cantidad) {
  let progreso = 0;
  const incremento = formato === "pdf" ? 8 : 12;

  const timer = setInterval(() => {
    progreso += incremento;
    if (progreso > 100) progreso = 100;

    const iconos = {
      excel: "fas fa-file-excel",
      pdf: "fas fa-file-pdf",
      csv: "fas fa-file-csv",
    };

    const colores = {
      excel: "#10B981",
      pdf: "#EF4444",
      csv: "#3B82F6",
    };

    Swal.update({
      html: `
                <div style="text-align: center; padding: 2rem;">
                    <div style="margin-bottom: 2rem;">
                        <i class="${
                          iconos[formato]
                        }" style="font-size: 4rem; color: ${
        colores[formato]
      }; animation: pulse 2s infinite;"></i>
                    </div>
                    
                    <h3 style="margin-bottom: 1rem; color: #333;">Generando archivo ${formato.toUpperCase()}</h3>
                    
                    <div style="background: #f0f0f0; border-radius: 15px; overflow: hidden; margin: 1.5rem 0; height: 20px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="background: linear-gradient(90deg, ${
                          colores[formato]
                        }, #6c5ce7); height: 100%; width: ${progreso}%; transition: all 0.4s ease;">
                        </div>
                    </div>
                    
                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.5rem;">
                        ${
                          progreso < 30
                            ? "Recopilando datos..."
                            : progreso < 60
                            ? `Procesando ${cantidad} documentos...`
                            : progreso < 90
                            ? "Aplicando formato profesional..."
                            : "Finalizando archivo..."
                        }
                    </p>
                    
                    <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 1rem;">
                        <div class="dot" style="width: 8px; height: 8px; background: ${
                          colores[formato]
                        }; border-radius: 50%; animation: bounce 1.4s infinite ease-in-out; animation-delay: -0.32s;"></div>
                        <div class="dot" style="width: 8px; height: 8px; background: ${
                          colores[formato]
                        }; border-radius: 50%; animation: bounce 1.4s infinite ease-in-out; animation-delay: -0.16s;"></div>
                        <div class="dot" style="width: 8px; height: 8px; background: ${
                          colores[formato]
                        }; border-radius: 50%; animation: bounce 1.4s infinite ease-in-out;"></div>
                    </div>
                </div>
            `,
    });

    if (progreso >= 100) {
      clearInterval(timer);

      setTimeout(() => {
        const datos = obtenerDatos();
        const stats = calcularStats(datos);

        // ENVIAR AL BACKEND PARA USAR LAS LIBRERÍAS REALES
        fetch('../../backend/php/exportar-supervision.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            formato: formato,
            datos: datos,
            stats: stats
          })
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('Error en la exportación');
          }
          
          // Obtener el nombre del archivo desde el header
          const contentDisposition = response.headers.get('Content-Disposition');
          let filename = `supervision_documentos_${new Date().toISOString().split('T')[0]}.${formato === 'excel' ? 'xlsx' : formato}`;
          
          if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
            if (filenameMatch) {
              filename = filenameMatch[1];
            }
          }

          return response.blob().then(blob => ({ blob, filename }));
        })
        .then(({ blob, filename }) => {
          // Crear enlace de descarga
          const url = window.URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = filename;
          link.style.display = 'none';
          
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          
          // Limpiar URL
          setTimeout(() => window.URL.revokeObjectURL(url), 1000);

          // Mostrar éxito con la misma animación
          const iconos = {
            excel: "fas fa-file-excel",
            pdf: "fas fa-file-pdf", 
            csv: "fas fa-file-csv",
          };

          const colores = {
            excel: "#10B981",
            pdf: "#EF4444",
            csv: "#3B82F6",
          };

          Swal.fire({
            icon: "success",
            title: "Archivo descargado correctamente",
            html: `
              <div style="text-align: center;">
                <i class="${iconos[formato]}" style="font-size: 3rem; color: ${colores[formato]}; margin-bottom: 1rem;"></i>
                <p>El archivo <strong>${formato.toUpperCase()}</strong> con ${cantidad} registro${cantidad !== 1 ? "s" : ""} ha sido descargado.</p>
                <p style="color: #666; font-size: 0.9rem;">
                  El archivo se ha guardado en tu carpeta de descargas.
                </p>
              </div>
            `,
            timer: 3000,
            showConfirmButton: false,
          });
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            icon: "error",
            title: "Error en la exportación",
            text: "Hubo un problema al generar el archivo. Intenta nuevamente.",
            confirmButtonColor: "#d33",
          });
        });
      }, 500);
    }
  }, 150);

  // Agregar estilos de animación
  if (!document.getElementById("export-animations")) {
    const animationStyles = document.createElement("style");
    animationStyles.id = "export-animations";
    animationStyles.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
            @keyframes bounce {
                0%, 80%, 100% { transform: scale(0); }
                40% { transform: scale(1); }
            }
        `;
    document.head.appendChild(animationStyles);
  }

  Swal.fire({
    title: "Preparando exportación...",
    html: `
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-cog fa-spin" style="font-size: 3rem; color: #6c5ce7; margin-bottom: 1rem;"></i>
                <p style="color: #666;">Iniciando proceso de exportación...</p>
            </div>
        `,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
  });
}



/**
 * Obtener datos de la tabla
 */
function obtenerDatos() {
  const filas = document.querySelectorAll(
    'tbody tr.fila-documento[data-id]:not([style*="display: none"]):not(.mensaje-resultados)'
  );
  const datos = [];

  filas.forEach((fila, index) => {
    const celdas = fila.querySelectorAll("td");
    datos.push({
      numero: index + 1,
      documento: celdas[1]?.textContent.trim() || "",
      asunto: celdas[2]?.textContent.trim() || "",
      estado: celdas[3]?.textContent.trim() || "",
      fecha: celdas[4]?.textContent.trim() || "",
      area: celdas[5]?.textContent.trim() || "",
      dias: celdas[6]?.textContent.trim() || "",
      observacion: celdas[7]?.textContent.trim() || "",
      recibido: celdas[8]?.textContent.trim() || "",
    });
  });

  return datos;
}

/**
 * Calcular estadísticas basadas en los datos reales de la tabla
 */
function calcularStats(datos) {
  const stats = {
    total: datos.length,
    enTiempo: 0,
    atencion: 0,
    urgentes: 0,
  };

  datos.forEach((item) => {
    const diasTexto = item.dias.toLowerCase();
    const numDias = parseInt(diasTexto.match(/\d+/)?.[0] || 0);

    if (diasTexto.includes("en tiempo") || numDias <= 3) {
      stats.enTiempo++;
    } else if (
      diasTexto.includes("atención") ||
      (numDias >= 4 && numDias <= 6)
    ) {
      stats.atencion++;
    } else if (diasTexto.includes("urgente") || numDias >= 7) {
      stats.urgentes++;
    }
  });

  return stats;
}

// Hacer función global
window.exportarSupervision = exportarSupervision;