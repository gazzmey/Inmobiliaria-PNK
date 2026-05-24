/**
 * PNK Inmobiliaria — Módulo de Autenticación
 * Login, logout, sesiones y registro con validaciones + SweetAlert2
 */

// ═══════════════════════════════════════════
// LOGIN
// ═══════════════════════════════════════════

function initLogin() {
  const formLogin = document.getElementById('form-login');
  if (formLogin) {
    formLogin.addEventListener('submit', async (e) => {
      e.preventDefault();
      await handleLogin(formLogin);
    });
  }
  const formAuthHome = document.getElementById('form-auth-home');
  if (formAuthHome) {
    formAuthHome.addEventListener('submit', async (e) => {
      e.preventDefault();
      await handleLogin(formAuthHome);
    });
  }
}

async function handleLogin(form) {
  const btn = form.querySelector('button[type="submit"]');
  const orig = btn.innerHTML;
  const email = form.querySelector('input[name="email"]').value.trim();
  const password = form.querySelector('input[name="password"]').value;

  if (!email || !password) {
    return Swal.fire({ icon:'warning', title:'Campos vacíos', text:'Email y contraseña son obligatorios.', confirmButtonColor:'#c9922a' });
  }
  if (!validarEmail(email)) {
    return Swal.fire({ icon:'error', title:'Email inválido', text:'Ingresa un correo electrónico con formato válido.', confirmButtonColor:'#c9922a' });
  }

  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
  btn.disabled = true;

  const rememberCb = form.querySelector('input[name="remember"]');
  const remember = rememberCb ? rememberCb.checked : false;
  const result = await apiCall('auth/login.php', { method:'POST', body:{ email, password } });

  if (result.ok) {
    const storage = remember ? localStorage : sessionStorage;
    storage.setItem('pnk_user', JSON.stringify(result.data));
    storage.setItem('pnk_remember', remember ? '1' : '0');
    await Swal.fire({ icon:'success', title:'¡Bienvenido!', text:'Hola, ' + result.data.nombre + '. Redirigiendo...', timer:1500, showConfirmButton:false, timerProgressBar:true });
    window.location.href = 'dashboard.html';
  } else {
    Swal.fire({ icon:'error', title:'Error de autenticación', text: result.message || 'Credenciales inválidas.', confirmButtonColor:'#c9922a' });
    btn.innerHTML = orig;
    btn.disabled = false;
  }
}

// ═══════════════════════════════════════════
// LOGOUT
// ═══════════════════════════════════════════

function initLogout() {
  document.querySelectorAll('#nav-salir, #sb-salir').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const ok = await confirmAction('Se cerrará tu sesión actual.', '¿Cerrar sesión?');
      if (!ok) return;
      await apiCall('auth/logout.php', { method:'POST' });
      sessionStorage.removeItem('pnk_user');
      sessionStorage.removeItem('pnk_remember');
      localStorage.removeItem('pnk_user');
      localStorage.removeItem('pnk_remember');
      await Swal.fire({ icon:'info', title:'Sesión cerrada', text:'Has cerrado sesión correctamente.', timer:1200, showConfirmButton:false, timerProgressBar:true });
      window.location.href = 'index.html';
    });
  });
}

// ═══════════════════════════════════════════
// SESIÓN
// ═══════════════════════════════════════════

async function requireAuth() {
  const result = await apiCall('auth/session.php');
  if (!result.ok) { window.location.href = 'login.html'; return null; }
  return result.data;
}

function updateUIWithUser(user) {
  const h = document.querySelector('.dashboard-header p');
  if (h && user) {
    const fecha = new Date().toLocaleDateString('es-CL', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    const roles = { admin:'Administrador', propietario:'Propietario', gestor:'Gestor Freelance' };
    h.innerHTML = 'Bienvenido, <strong>' + user.nombres + ' ' + user.apellido_paterno + '</strong> (' + (roles[user.rol]||user.rol) + ') — ' + fecha;
  }
}

// ═══════════════════════════════════════════
// REGISTRO PROPIETARIO
// ═══════════════════════════════════════════

function initRegistroPropietario() {
  const form = document.getElementById('form-registro-propietario');
  if (!form) return;

  // Auto-formato RUT
  const rutInput = form.querySelector('#prop-rut');
  if (rutInput) rutInput.addEventListener('input', (e) => { e.target.value = formatearRUT(e.target.value); });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const orig = btn.innerHTML;

    const rut = form.querySelector('#prop-rut').value.trim();
    const nombres = form.querySelector('#prop-nombres').value.trim();
    const apPaterno = form.querySelector('#prop-apellido-paterno').value.trim();
    const email = form.querySelector('#prop-email').value.trim();
    const telefono = form.querySelector('#prop-telefono').value.trim();
    const fechaNac = form.querySelector('#prop-fecha-nac').value;
    const sexo = form.querySelector('#prop-sexo').value;
    const nroBienes = form.querySelector('#prop-nro-bienes').value.trim();
    const pass = form.querySelector('#prop-password').value;
    const confirmPass = form.querySelector('#prop-confirm-pass').value;
    const terms = form.querySelector('#prop-terms').checked;

    // Validaciones con SweetAlert2
    if (!rut || !nombres || !apPaterno || !email || !telefono || !fechaNac || !sexo || !nroBienes || !pass || !confirmPass) {
      return Swal.fire({ icon:'warning', title:'Campos incompletos', text:'Todos los campos obligatorios (*) deben ser completados.', confirmButtonColor:'#c9922a' });
    }
    if (!validarRUT(rut)) {
      return Swal.fire({ icon:'error', title:'RUT inválido', text:'El RUT ingresado no es válido. Verifica el formato (ej: 12.345.678-5) y el dígito verificador.', confirmButtonColor:'#c9922a' });
    }
    if (!validarEmail(email)) {
      return Swal.fire({ icon:'error', title:'Email inválido', text:'Ingresa un correo electrónico válido (ej: nombre@correo.cl).', confirmButtonColor:'#c9922a' });
    }
    if (!validarTelefono(telefono)) {
      return Swal.fire({ icon:'error', title:'Teléfono inválido', text:'Ingresa un número de teléfono válido (ej: +56 9 1234 5678).', confirmButtonColor:'#c9922a' });
    }
    const passErrors = validarPasswordRobusta(pass);
    if (passErrors.length > 0) {
      return Swal.fire({ icon:'error', title:'Contraseña débil', html:'La contraseña debe cumplir:<br>• ' + passErrors.join('<br>• '), confirmButtonColor:'#c9922a' });
    }
    if (pass !== confirmPass) {
      return Swal.fire({ icon:'error', title:'Contraseñas no coinciden', text:'La contraseña y su confirmación deben ser iguales.', confirmButtonColor:'#c9922a' });
    }
    if (!terms) {
      return Swal.fire({ icon:'warning', title:'Términos requeridos', text:'Debes aceptar los términos y condiciones para registrarte.', confirmButtonColor:'#c9922a' });
    }

    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
    btn.disabled = true;

    const data = {
      rut, nombres, apellido_paterno: apPaterno,
      apellido_materno: form.querySelector('#prop-apellido-materno').value.trim(),
      email, telefono, fecha_nacimiento: fechaNac, sexo,
      nro_bienes_raices: nroBienes, password: pass, confirm_password: confirmPass,
    };
    const result = await apiCall('usuarios/registrar-propietario.php', { method:'POST', body:data });

    if (result.ok) {
      await Swal.fire({ icon:'success', title:'¡Registro exitoso!', text: result.message, confirmButtonColor:'#c9922a' });
      form.reset();
      window.location.href = 'login.html';
    } else {
      Swal.fire({ icon:'error', title:'Error en el registro', text: result.message || 'Intenta nuevamente.', confirmButtonColor:'#c9922a' });
    }
    btn.innerHTML = orig; btn.disabled = false;
  });
}

// ═══════════════════════════════════════════
// REGISTRO GESTOR
// ═══════════════════════════════════════════

function initRegistroGestor() {
  const form = document.getElementById('form-registro-gestor');
  if (!form) return;

  const rutInput = form.querySelector('#gestor-rut');
  if (rutInput) rutInput.addEventListener('input', (e) => { e.target.value = formatearRUT(e.target.value); });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const orig = btn.innerHTML;

    const rut = form.querySelector('#gestor-rut').value.trim();
    const nombres = form.querySelector('#gestor-nombres').value.trim();
    const apPaterno = form.querySelector('#gestor-ap-paterno').value.trim();
    const email = form.querySelector('#gestor-email').value.trim();
    const telefono = form.querySelector('#gestor-telefono').value.trim();
    const fechaNac = form.querySelector('#gestor-fecha-nac').value;
    const sexo = form.querySelector('#gestor-sexo').value;
    const certificado = form.querySelector('#gestor-certificado').files[0];
    const pass = form.querySelector('#gestor-password').value;
    const confirmPass = form.querySelector('#gestor-confirm-pass').value;
    const terms = form.querySelector('#gestor-terms').checked;

    if (!rut || !nombres || !apPaterno || !email || !telefono || !fechaNac || !sexo || !pass || !confirmPass) {
      return Swal.fire({ icon:'warning', title:'Campos incompletos', text:'Todos los campos obligatorios (*) deben ser completados.', confirmButtonColor:'#c9922a' });
    }
    if (!validarRUT(rut)) {
      return Swal.fire({ icon:'error', title:'RUT inválido', text:'El RUT ingresado no es válido. Verifica el formato y el dígito verificador.', confirmButtonColor:'#c9922a' });
    }
    if (!validarEmail(email)) {
      return Swal.fire({ icon:'error', title:'Email inválido', text:'Ingresa un correo electrónico válido.', confirmButtonColor:'#c9922a' });
    }
    if (!validarTelefono(telefono)) {
      return Swal.fire({ icon:'error', title:'Teléfono inválido', text:'Ingresa un teléfono válido.', confirmButtonColor:'#c9922a' });
    }
    if (!certificado) {
      return Swal.fire({ icon:'warning', title:'Certificado requerido', text:'Debes adjuntar tu Certificado de Antecedentes Penales.', confirmButtonColor:'#c9922a' });
    }
    const passErrors = validarPasswordRobusta(pass);
    if (passErrors.length > 0) {
      return Swal.fire({ icon:'error', title:'Contraseña débil', html:'La contraseña debe cumplir:<br>• ' + passErrors.join('<br>• '), confirmButtonColor:'#c9922a' });
    }
    if (pass !== confirmPass) {
      return Swal.fire({ icon:'error', title:'Contraseñas no coinciden', text:'La contraseña y su confirmación deben ser iguales.', confirmButtonColor:'#c9922a' });
    }
    if (!terms) {
      return Swal.fire({ icon:'warning', title:'Términos requeridos', text:'Debes aceptar los términos y condiciones.', confirmButtonColor:'#c9922a' });
    }

    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    btn.disabled = true;

    const formData = new FormData(form);
    formData.set('confirm_password', confirmPass);
    const result = await apiCall('usuarios/registrar-gestor.php', { method:'POST', body:formData });

    if (result.ok) {
      await Swal.fire({ icon:'success', title:'¡Postulación enviada!', text: result.message, confirmButtonColor:'#c9922a' });
      form.reset();
      window.location.href = 'login.html';
    } else {
      Swal.fire({ icon:'error', title:'Error en la postulación', text: result.message || 'Intenta nuevamente.', confirmButtonColor:'#c9922a' });
    }
    btn.innerHTML = orig; btn.disabled = false;
  });
}

// ═══════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {
  initLogin();
  initLogout();
  initRegistroPropietario();
  initRegistroGestor();
});
