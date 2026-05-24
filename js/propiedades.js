/**
 * PNK Inmobiliaria — CRUD Propiedades (Panel Admin)
 */

let propPage = 1;
let propFilter = {};
let editingPropId = null;

async function initPropiedades() {
  const user = await requireAuth();
  if (!user || user.rol !== 'admin') return;
  updateUIWithUser(user);
  await loadPropiedades();
  initPropFilters();
  initPropSearch();
  initFormPropiedad();
}

async function loadPropiedades(page = 1) {
  propPage = page;
  const params = new URLSearchParams({ page, limit: 10, ...propFilter });
  const result = await apiCall('propiedades/listar.php?' + params);
  if (!result.ok) { showToast(result.message, 'error'); return; }
  const { propiedades, total, pages, conteos } = result.data;
  renderPropTable(propiedades);
  renderPropPagination(total, pages, page);
  renderPropConteos(conteos);
}

function renderPropTable(props) {
  const tbody = document.getElementById('tbody-propiedades');
  if (!tbody) return;
  if (!props.length) { tbody.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:40px;color:#9a9080;">No se encontraron propiedades</td></tr>'; return; }

  const tipoBadge = { casa:'badge-tipo-casa', departamento:'badge-tipo-depto', terreno:'badge-tipo-terreno' };
  const tipoLabel = { casa:'Casa', departamento:'Departamento', terreno:'Terreno' };
  const estadoBadge = { activo:'badge-activo', inactivo:'badge-inactivo', vendida:'badge-vendida' };

  tbody.innerHTML = props.map(p => {
    return '<tr>' +
      '<td><strong>' + p.codigo + '</strong></td>' +
      '<td><span class="badge ' + (tipoBadge[p.tipo]||'') + '">' + (tipoLabel[p.tipo]||p.tipo) + '</span></td>' +
      '<td>' + p.sector + ', ' + p.comuna + '<br><small style="color:#9a9080;">' + p.provincia + '</small></td>' +
      '<td>' + (p.dormitorios || '—') + '</td>' +
      '<td>' + (p.banos || '—') + '</td>' +
      '<td>' + p.area_terreno + ' m²</td>' +
      '<td>' + formatCLP(p.precio_pesos) + '</td>' +
      '<td>' + Number(p.precio_uf).toLocaleString('es-CL') + ' UF</td>' +
      '<td><span class="badge ' + (estadoBadge[p.estado]||'') + '">' + p.estado.charAt(0).toUpperCase() + p.estado.slice(1) + '</span></td>' +
      '<td>' + formatDate(p.fecha_publicacion) + '</td>' +
      '<td class="action-buttons">' +
        '<a href="detalle-propiedad.html?id=' + p.id + '" class="btn-ver-table" title="Ver"><i class="fas fa-eye"></i></a>' +
        '<a href="#modalPropiedad" class="btn-editar" title="Editar" onclick="editProp(' + p.id + ')"><i class="fas fa-edit"></i></a>' +
        '<button class="btn-eliminar" title="Eliminar" onclick="deleteProp(' + p.id + ')"><i class="fas fa-trash"></i></button>' +
      '</td></tr>';
  }).join('');
}

function renderPropPagination(total, pages, current) {
  const containers = document.querySelectorAll('.crud-container > div:last-child');
  const container = containers[containers.length - 1];
  if (!container) return;
  let html = '<span>Mostrando ' + ((current-1)*10+1) + '–' + Math.min(current*10,total) + ' de ' + total + ' propiedades</span><div style="display:flex;gap:6px;">';
  html += '<button style="padding:7px 12px;border:1px solid #e0d8cd;background:white;border-radius:5px;cursor:pointer;" onclick="loadPropiedades('+(current>1?current-1:1)+')">←</button>';
  for (let i = 1; i <= Math.min(pages, 5); i++) {
    const active = i === current;
    html += '<button style="padding:7px 12px;border:1px solid '+(active?'#c9922a':'#e0d8cd')+';background:'+(active?'#c9922a':'white')+';color:'+(active?'#0e0d0b':'inherit')+';border-radius:5px;'+(active?'font-weight:700;':'')+' cursor:pointer;" onclick="loadPropiedades('+i+')">'+i+'</button>';
  }
  html += '<button style="padding:7px 12px;border:1px solid #e0d8cd;background:white;border-radius:5px;cursor:pointer;" onclick="loadPropiedades('+(current<pages?current+1:pages)+')">→</button></div>';
  container.innerHTML = html;
}

function renderPropConteos(c) {
  if (!c) return;
  const map = {
    'pf-todas':    c.total,
    'pf-casas':    c.casas,
    'pf-deptos':   c.departamentos,
    'pf-terrenos': c.terrenos,
    'pf-activas':  c.activas,
    'pf-vendidas': c.vendidas,
  };
  Object.keys(map).forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      const cnt = el.querySelector('.cnt');
      if (cnt) cnt.textContent = map[id] || 0;
    }
  });
}

function initPropFilters() {
  const filterMap = {
    'pf-todas':    {},
    'pf-casas':    { tipo: 'casa' },
    'pf-deptos':   { tipo: 'departamento' },
    'pf-terrenos': { tipo: 'terreno' },
    'pf-activas':  { estado: 'activo' },
    'pf-vendidas': { estado: 'vendida' },
  };
  const container = document.getElementById('prop-filters');
  if (!container) return;
  const spans = container.querySelectorAll('span[id]');

  spans.forEach(s => {
    s.addEventListener('click', () => {
      propFilter = filterMap[s.id] || {};
      // Reset estilos
      spans.forEach(sp => {
        sp.style.background = 'white';
        sp.style.borderColor = '#e0d8cd';
        sp.style.fontWeight = '400';
      });
      // Activo
      s.style.background = '#c9922a';
      s.style.borderColor = '#c9922a';
      s.style.color = '#0e0d0b';
      s.style.fontWeight = '700';
      loadPropiedades(1);
    });
  });
}

function initPropSearch() {
  const input = document.getElementById('search-props');
  if (!input) return;
  let timeout;
  input.addEventListener('input', () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      propFilter.buscar = input.value.trim();
      if (!propFilter.buscar) delete propFilter.buscar;
      loadPropiedades(1);
    }, 400);
  });
}

function initFormPropiedad() {
  const form = document.getElementById('form-propiedad-modal');
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;

    const formData = new FormData(form);
    if (editingPropId) formData.set('id', editingPropId);

    const result = await apiCall('propiedades/guardar.php', { method:'POST', body:formData });
    if (result.ok) { showToast(result.message,'success'); closeModal(); form.reset(); editingPropId=null; loadPropiedades(propPage); }
    else { showToast(result.message,'error'); }
    btn.innerHTML = orig; btn.disabled = false;
  });

  const btnNuevo = document.getElementById('btn-nueva-propiedad');
  if (btnNuevo) btnNuevo.addEventListener('click', () => {
    editingPropId=null;
    document.getElementById('modal-prop-title').innerHTML='<i class="fas fa-plus-circle"></i> Nueva Propiedad';
    form.reset();
  });

  // Load propietarios dropdown from API
  loadPropietarios();
}

async function loadPropietarios() {
  const result = await apiCall('usuarios/listar.php?rol=propietario&estado=activo&limit=50');
  if (!result.ok) return;
  const select = document.getElementById('mp-propietario');
  if (!select) return;
  select.innerHTML = '<option value="">Selecciona propietario</option>';
  result.data.usuarios.forEach(u => {
    select.innerHTML += '<option value="'+u.id+'">'+u.nombres+' '+u.apellido_paterno+' ('+u.rut+')</option>';
  });
}

async function editProp(id) {
  const result = await apiCall('propiedades/obtener.php?id=' + id);
  if (!result.ok) { showToast(result.message,'error'); return; }
  const p = result.data;
  editingPropId = p.id;
  document.getElementById('modal-prop-title').innerHTML = '<i class="fas fa-edit"></i> Editar Propiedad ' + p.codigo;
  document.getElementById('mp-tipo').value = p.tipo;
  document.getElementById('mp-provincia').value = p.provincia;
  document.getElementById('mp-comuna').value = p.comuna;
  document.getElementById('mp-sector').value = p.sector;
  document.getElementById('mp-dormitorios').value = p.dormitorios;
  document.getElementById('mp-banos').value = p.banos;
  document.getElementById('mp-area-terreno').value = p.area_terreno;
  document.getElementById('mp-area-construida').value = p.area_construida || '';
  document.getElementById('mp-precio-clp').value = p.precio_pesos;
  document.getElementById('mp-precio-uf').value = p.precio_uf;
  document.getElementById('mp-descripcion').value = p.descripcion || '';
  document.getElementById('mp-estado').value = p.estado;
  document.getElementById('mp-fecha-pub').value = p.fecha_publicacion || '';
  document.getElementById('mp-propietario').value = p.propietario_id || '';
  // Amenidades
  ['bodega','estacionamiento','logia','cocina_amoblada','antejardin','patio_trasero','piscina'].forEach(a => {
    const el = document.querySelector('[name="'+a+'"]');
    if (el) el.checked = p[a] == 1;
  });
}

async function deleteProp(id) {
  const ok = await confirmAction('¿Eliminar esta propiedad permanentemente? Esta acción no se puede deshacer.', '¡Cuidado!');
  if (!ok) return;
  const result = await apiCall('propiedades/eliminar.php', { method:'DELETE', body:{ id } });
  if (result.ok) { showToast(result.message,'success'); loadPropiedades(propPage); }
  else { Swal.fire({ icon:'error', title:'Error', text: result.message, confirmButtonColor:'#c9922a' }); }
}


// Búsqueda pública (homepage)
function initBuscadorPublico() {
  const btn = document.getElementById('btn-buscar-props');
  if (!btn) return;
  btn.addEventListener('click', async (e) => {
    e.preventDefault();
    const tipo = document.getElementById('buscar-tipo').value;
    const provincia = document.getElementById('buscar-provincia').value;
    const comuna = document.getElementById('buscar-comuna').value;
    const sector = document.getElementById('buscar-sector').value;
    const params = new URLSearchParams();
    if (tipo) params.set('tipo', tipo.toLowerCase());
    if (provincia) params.set('provincia', provincia.toLowerCase());
    if (comuna) params.set('comuna', comuna);
    if (sector) params.set('sector', sector);
    const result = await apiCall('propiedades/buscar.php?' + params);
    if (result.ok) {
      showToast(result.data.length + ' propiedad(es) encontrada(s).', result.data.length ? 'success' : 'info');
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('tabla-propiedades')) initPropiedades();
  if (document.getElementById('btn-buscar-props')) initBuscadorPublico();
});
