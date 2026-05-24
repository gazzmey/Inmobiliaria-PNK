/**
 * PNK Inmobiliaria — Script principal compartido
 * Utilidades generales, SweetAlert2 y validaciones
 */

const API_BASE = 'api';

// ═══════════════════════════════════════════
// UTILIDADES API
// ═══════════════════════════════════════════

async function apiCall(endpoint, options = {}) {
  const url = `${API_BASE}/${endpoint}`;
  const defaults = {
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
  };
  if (options.body instanceof FormData) {
    delete defaults.headers['Content-Type'];
  }
  const config = { ...defaults, ...options };
  if (config.body && !(config.body instanceof FormData) && typeof config.body === 'object') {
    config.body = JSON.stringify(config.body);
  }
  try {
    const response = await fetch(url, config);
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error en API:', error);
    return { ok: false, message: 'Error de conexión con el servidor.' };
  }
}

// ═══════════════════════════════════════════
// SWEETALERT2 — MENSAJES
// ═══════════════════════════════════════════

/**
 * Toast notification (esquina superior derecha)
 */
function showToast(message, type = 'success') {
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.onmouseenter = Swal.stopTimer;
      toast.onmouseleave = Swal.resumeTimer;
    }
  });
  Toast.fire({ icon: type, title: message });
}

/**
 * Alerta modal (centro de pantalla)
 */
function showAlert(title, text, icon = 'success') {
  return Swal.fire({
    title,
    text,
    icon,
    confirmButtonColor: '#c9922a',
    confirmButtonText: 'Aceptar',
  });
}

/**
 * Confirmación con SweetAlert2 (reemplaza confirm() nativo)
 */
async function confirmAction(message, title = '¿Estás seguro?') {
  const result = await Swal.fire({
    title: title,
    text: message,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#c9922a',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Sí, confirmar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
  });
  return result.isConfirmed;
}

// ═══════════════════════════════════════════
// VALIDACIONES
// ═══════════════════════════════════════════

/**
 * Validar RUT chileno (formato y dígito verificador — módulo 11)
 */
function validarRUT(rut) {
  if (!rut) return false;
  let clean = rut.replace(/\./g, '').replace(/-/g, '').toUpperCase();
  if (clean.length < 2) return false;
  const body = clean.slice(0, -1);
  const dv = clean.slice(-1);
  if (!/^\d+$/.test(body)) return false;
  if (parseInt(body) < 1000000) return false;
  let sum = 0;
  let multiplier = 2;
  for (let i = body.length - 1; i >= 0; i--) {
    sum += parseInt(body[i]) * multiplier;
    multiplier = multiplier === 7 ? 2 : multiplier + 1;
  }
  const remainder = sum % 11;
  let expected;
  if (remainder === 0) expected = '0';
  else if (remainder === 1) expected = 'K';
  else expected = String(11 - remainder);
  return dv === expected;
}

/**
 * Auto-formato RUT mientras escribe
 */
function formatearRUT(value) {
  let clean = value.replace(/[^0-9kK]/g, '').toUpperCase();
  if (clean.length < 2) return clean;
  const dv = clean.slice(-1);
  let body = clean.slice(0, -1);
  body = body.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  return body + '-' + dv;
}

/**
 * Validar formato de email
 */
function validarEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/**
 * Validar contraseña robusta
 */
function validarPasswordRobusta(password) {
  const errors = [];
  if (password.length < 8) errors.push('Mínimo 8 caracteres');
  if (!/[A-Z]/.test(password)) errors.push('Al menos 1 letra mayúscula');
  if (!/[a-z]/.test(password)) errors.push('Al menos 1 letra minúscula');
  if (!/[0-9]/.test(password)) errors.push('Al menos 1 número');
  return errors;
}

/**
 * Validar teléfono chileno
 */
function validarTelefono(tel) {
  const clean = tel.replace(/[\s\-\(\)\+]/g, '');
  return clean.length >= 9;
}

// ═══════════════════════════════════════════
// FORMATO
// ═══════════════════════════════════════════

function formatCLP(amount) {
  return '$' + Number(amount).toLocaleString('es-CL');
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-CL');
}

function closeModal() {
  window.location.hash = '#top-page';
}
