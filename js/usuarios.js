/**
 * PNK Inmobiliaria — CRUD Usuarios (Panel Admin)
 */

let currentPage = 1;
let currentFilter = {};
let editingUserId = null;

async function initUsuarios() {
  const user = await requireAuth();
  if (!user || user.rol !== 'admin') return;
  updateUIWithUser(user);
  await loadUsuarios();
  initFilters();
  initSearch();
  initFormUsuario();
  initEditButtons();
}

async function loadUsuarios(page = 1) {
  currentPage = page;
  const params = new URLSearchParams({ page, limit: 10, ...currentFilter });
  const result = await apiCall('usuarios/listar.php?' + params);
  if (!result.ok) { showToast(result.message, 'error'); return; }

  const { usuarios, total, pages, conteos } = result.data;
  renderTable(usuarios);
  renderPagination(total, pages, page);
  renderConteos(conteos);
}

function renderTable(usuarios) {
  const tbody = document.getElementById('tbody-usuarios');
  if (!tbody) return;
  if (!usuarios.length) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:#9a9080;">No se encontraron usuarios</td></tr>'; return; }

  tbody.innerHTML = usuarios.map(u => {
    const nombre = u.nombres + ' ' + u.apellido_paterno + ' ' + (u.apellido_materno || '');
    const rolBadge = { admin: 'badge-admin', propietario: 'badge-propietario', gestor: 'badge-gestor' };
    const rolLabel = { admin: 'Administrador', propietario: 'Propietario', gestor: 'Gestor Free' };
    const estadoBadge = { activo: 'badge-activo', pendiente: 'badge-pendiente', inactivo: 'badge-inactivo' };

    let actions = '<a href="#modalUsuario" class="btn-editar" title="Editar" onclick="editUser('+u.id+')"><i class="fas fa-edit"></i></a>';
    if (u.estado === 'pendiente') {
      actions = '<button class="btn-activar" title="Activar" onclick="changeUserStatus('+u.id+',\'activo\')"><i class="fas fa-check"></i></button>' + actions;
      actions += '<button class="btn-eliminar" title="Rechazar" onclick="changeUserStatus('+u.id+',\'inactivo\')"><i class="fas fa-times"></i></button>';
    } else if (u.estado === 'inactivo') {
      actions = '<button class="btn-activar" title="Reactivar" onclick="changeUserStatus('+u.id+',\'activo\')"><i class="fas fa-redo"></i></button>' + actions;
      actions += '<button class="btn-eliminar" title="Eliminar" onclick="deleteUser('+u.id+')"><i class="fas fa-trash"></i></button>';
    } else {
      actions += '<button class="btn-eliminar" title="Desactivar" onclick="changeUserStatus('+u.id+',\'inactivo\')"><i class="fas fa-ban"></i></button>';
    }

    return '<tr>' +
      '<td><strong>' + u.rut + '</strong></td>' +
      '<td>' + nombre.trim() + '</td>' +
      '<td>' + u.email + '</td>' +
      '<td>' + (u.telefono || '—') + '</td>' +
      '<td><span class="badge ' + (rolBadge[u.rol]||'') + '">' + (rolLabel[u.rol]||u.rol) + '</span></td>' +
      '<td><span class="badge ' + (estadoBadge[u.estado]||'') + '">' + u.estado.charAt(0).toUpperCase() + u.estado.slice(1) + '</span></td>' +
      '<td>' + formatDate(u.fecha_registro) + '</td>' +
      '<td class="action-buttons">' + actions + '</td></tr>';
  }).join('');
}

function renderPagination(total, pages, current) {
  const container = document.querySelector('.crud-container > div:last-child');
  if (!container) return;
  let html = '<span>Mostrando ' + ((current-1)*10+1) + '–' + Math.min(current*10,total) + ' de ' + total + ' usuarios</span><div style="display:flex;gap:6px;">';
  html += '<button style="padding:7px 12px;border:1px solid #e0d8cd;background:white;border-radius:5px;cursor:pointer;" onclick="loadUsuarios('+(current>1?current-1:1)+')">←</button>';
  for (let i = 1; i <= Math.min(pages, 5); i++) {
    const active = i === current;
    html += '<button style="padding:7px 12px;border:1px solid '+(active?'#c9922a':'#e0d8cd')+';background:'+(active?'#c9922a':'white')+';color:'+(active?'#0e0d0b':'inherit')+';border-radius:5px;'+(active?'font-weight:700;':'')+' cursor:pointer;" onclick="loadUsuarios('+i+')">'+i+'</button>';
  }
  html += '<button style="padding:7px 12px;border:1px solid #e0d8cd;background:white;border-radius:5px;cursor:pointer;" onclick="loadUsuarios('+(current<pages?current+1:pages)+')">→</button></div>';
  container.innerHTML = html;
}

function renderConteos(c) {
  const labels = ['Todos ('+c.total+')', 'Admins ('+c.admins+')', 'Propietarios ('+c.propietarios+')', 'Gestores ('+c.gestores+')', '⚠ Pendientes ('+c.pendientes+')'];
  const filters = [null, 'admin', 'propietario', 'gestor', '__pendientes'];
  const spans = document.querySelectorAll('[id^="filtro-"]');
  spans.forEach((s, i) => { if (labels[i]) s.textContent = labels[i]; });
}

function initFilters() {
  const filterMap = { 'filtro-todos': {}, 'filtro-admin': {rol:'admin'}, 'filtro-prop': {rol:'propietario'}, 'filtro-gestor': {rol:'gestor'}, 'filtro-pend': {estado:'pendiente'} };
  Object.keys(filterMap).forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.cursor = 'pointer';
    if (el) el.addEventListener('click', () => {
      currentFilter = filterMap[id];
      document.querySelectorAll('[id^="filtro-"]').forEach(s => { s.style.background='white'; s.style.color='#9a9080'; s.style.fontWeight='400'; });
      el.style.background='#c9922a'; el.style.color='#0e0d0b'; el.style.fontWeight='700';
      loadUsuarios(1);
    });
  });
}

function initSearch() {
  const input = document.getElementById('search-usuarios');
  if (!input) return;
  let timeout;
  input.addEventListener('input', () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      currentFilter.buscar = input.value.trim();
      if (!currentFilter.buscar) delete currentFilter.buscar;
      loadUsuarios(1);
    }, 400);
  });
}

function initFormUsuario() {
  const form = document.getElementById('form-usuario-modal');
  if (!form) return;

  // Auto-formato RUT en modal
  const rutInput = form.querySelector('#modal-u-rut');
  if (rutInput) rutInput.addEventListener('input', (e) => { e.target.value = formatearRUT(e.target.value); });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const orig = btn.innerHTML;

    const rut = form.querySelector('#modal-u-rut').value.trim();
    const nombres = form.querySelector('#modal-u-nombres').value.trim();
    const apPaterno = form.querySelector('#modal-u-ap-paterno').value.trim();
    const email = form.querySelector('#modal-u-email').value.trim();
    const rol = form.querySelector('#modal-u-rol').value;
    const password = form.querySelector('#modal-u-password').value;

    // Validaciones con SweetAlert2
    if (!rut || !nombres || !apPaterno || !email || !rol) {
      return Swal.fire({ icon:'warning', title:'Campos incompletos', text:'RUT, Nombres, Apellido Paterno, Email y Rol son obligatorios.', confirmButtonColor:'#c9922a' });
    }
    if (!validarRUT(rut)) {
      return Swal.fire({ icon:'error', title:'RUT inválido', text:'El RUT ingresado no es válido. Verifica el formato y dígito verificador.', confirmButtonColor:'#c9922a' });
    }
    if (!validarEmail(email)) {
      return Swal.fire({ icon:'error', title:'Email inválido', text:'Ingresa un correo electrónico válido.', confirmButtonColor:'#c9922a' });
    }
    if (!editingUserId && !password) {
      return Swal.fire({ icon:'warning', title:'Contraseña requerida', text:'La contraseña es obligatoria para nuevos usuarios.', confirmButtonColor:'#c9922a' });
    }
    if (password) {
      const passErrors = validarPasswordRobusta(password);
      if (passErrors.length > 0) {
        return Swal.fire({ icon:'error', title:'Contraseña débil', html:'La contraseña debe cumplir:<br>• ' + passErrors.join('<br>• '), confirmButtonColor:'#c9922a' });
      }
    }

    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;

    const data = {
      id: editingUserId || 0,
      rut,
      nombres,
      apellido_paterno: apPaterno,
      apellido_materno: form.querySelector('#modal-u-ap-materno').value,
      email,
      telefono: form.querySelector('#modal-u-telefono').value,
      fecha_nacimiento: form.querySelector('#modal-u-fecha').value,
      sexo: form.querySelector('#modal-u-sexo').value,
      rol,
      estado: form.querySelector('#modal-u-estado').value,
      nro_bienes: form.querySelector('#modal-u-bienes').value,
      penka_id: form.querySelector('#modal-u-penka').value,
      password,
    };

    const result = await apiCall('usuarios/guardar.php', { method:'POST', body:data });
    if (result.ok) {
      showToast(result.message,'success');
      closeModal(); form.reset(); editingUserId=null; loadUsuarios(currentPage);
    } else {
      Swal.fire({ icon:'error', title:'Error', text: result.message, confirmButtonColor:'#c9922a' });
    }
    btn.innerHTML = orig; btn.disabled = false;
  });

  // Reset al abrir modal para nuevo
  const btnNuevo = document.getElementById('btn-nuevo-usuario');
  if (btnNuevo) btnNuevo.addEventListener('click', () => {
    editingUserId = null;
    document.getElementById('modal-usuario-title').innerHTML = '<i class="fas fa-user-plus"></i> Nuevo Usuario';
    form.reset();
  });
}

async function editUser(id) {
  const result = await apiCall('usuarios/obtener.php?id=' + id);
  if (!result.ok) { showToast(result.message,'error'); return; }
  const u = result.data;
  editingUserId = u.id;
  document.getElementById('modal-usuario-title').innerHTML = '<i class="fas fa-user-edit"></i> Editar Usuario';
  document.getElementById('modal-u-rut').value = u.rut;
  document.getElementById('modal-u-nombres').value = u.nombres;
  document.getElementById('modal-u-ap-paterno').value = u.apellido_paterno;
  document.getElementById('modal-u-ap-materno').value = u.apellido_materno || '';
  document.getElementById('modal-u-email').value = u.email;
  document.getElementById('modal-u-telefono').value = u.telefono || '';
  document.getElementById('modal-u-fecha').value = u.fecha_nacimiento || '';
  document.getElementById('modal-u-sexo').value = u.sexo || '';
  document.getElementById('modal-u-rol').value = u.rol;
  document.getElementById('modal-u-estado').value = u.estado;
  document.getElementById('modal-u-bienes').value = u.nro_bienes_raices || '';
  document.getElementById('modal-u-penka').value = u.penka_id || '';
  document.getElementById('modal-u-password').value = '';
  document.getElementById('modal-u-confirm').value = '';
}

async function changeUserStatus(id, estado) {
  const labels = { activo:'activar', inactivo:'desactivar' };
  const ok = await confirmAction('¿Seguro que deseas ' + (labels[estado]||estado) + ' este usuario?');
  if (!ok) return;
  const result = await apiCall('usuarios/cambiar-estado.php', { method:'PUT', body:{ id, estado } });
  if (result.ok) { showToast(result.message,'success'); loadUsuarios(currentPage); }
  else { Swal.fire({ icon:'error', title:'Error', text: result.message, confirmButtonColor:'#c9922a' }); }
}

async function deleteUser(id) {
  const ok = await confirmAction('¿Eliminar este usuario permanentemente? Esta acción no se puede deshacer.', '¡Cuidado!');
  if (!ok) return;
  const result = await apiCall('usuarios/eliminar.php', { method:'DELETE', body:{ id } });
  if (result.ok) { showToast(result.message,'success'); loadUsuarios(currentPage); }
  else { Swal.fire({ icon:'error', title:'Error', text: result.message, confirmButtonColor:'#c9922a' }); }
}

function initEditButtons() {
  document.querySelectorAll('[id^="btn-edit-u"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const row = btn.closest('tr');
      // Los datos se cargan via API en editUser()
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('tabla-usuarios')) initUsuarios();
});
