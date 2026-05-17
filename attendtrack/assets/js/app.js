/* ── AttendTrack Main JS ── */
const API = 'api.php';

// ── Utility ────────────────────────────────────────────────────────────────
async function post(action, data = {}) {
  const fd = new FormData();
  fd.append('action', action);
  for (const [k, v] of Object.entries(data)) fd.append(k, v);
  const r = await fetch(API, { method: 'POST', body: fd });
  return r.json();
}
async function get(action, params = {}) {
  const qs = new URLSearchParams({ action, ...params });
  const r = await fetch(`${API}?${qs}`);
  return r.json();
}

function toast(msg, type = 'success') {
  const icons = { success: 'ti-circle-check', error: 'ti-alert-circle', info: 'ti-info-circle' };
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `<i class="ti ${icons[type] || icons.info}"></i><span>${msg}</span>`;
  document.getElementById('toast-container').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

function confirm_dialog(msg) {
  return new Promise(res => {
    const ov = document.createElement('div');
    ov.className = 'modal-overlay';
    ov.innerHTML = `
      <div class="modal" style="max-width:360px">
        <div class="modal-header">
          <span class="modal-title"><i class="ti ti-alert-triangle" style="color:var(--warning);margin-right:8px"></i>Confirm</span>
        </div>
        <p style="font-size:14px;color:var(--muted);margin-bottom:1.5rem">${msg}</p>
        <div class="modal-footer">
          <button class="btn btn-ghost" id="cd-no">Cancel</button>
          <button class="btn btn-danger" id="cd-yes">Delete</button>
        </div>
      </div>`;
    document.body.appendChild(ov);
    ov.querySelector('#cd-no').onclick  = () => { ov.remove(); res(false); };
    ov.querySelector('#cd-yes').onclick = () => { ov.remove(); res(true); };
  });
}

function openModal(html) {
  const ov = document.createElement('div');
  ov.className = 'modal-overlay';
  ov.id = 'active-modal';
  ov.innerHTML = html;
  document.body.appendChild(ov);
  ov.querySelector('.modal-close')?.addEventListener('click', closeModal);
  return ov;
}
function closeModal() {
  document.getElementById('active-modal')?.remove();
}

function showSection(id) {
  document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
  document.getElementById(id)?.classList.add('active');
  document.querySelectorAll('.sidebar-item').forEach(s => {
    s.classList.toggle('active', s.dataset.section === id);
  });
}

function escHtml(s) {
  const d = document.createElement('div');
  d.textContent = s || '';
  return d.innerHTML;
}

function today() {
  return new Date().toISOString().split('T')[0];
}

// ── State ─────────────────────────────────────────────────────────────────
const State = { teacherName: '', currentYL: null, currentSection: null };

// ── Auth ────────────────────────────────────────────────────────────────────
async function checkSession() {
  const r = await get('check_session');
  if (r.loggedIn) {
    State.teacherName = r.name;
    showApp();
  }
}

function showApp() {
  document.getElementById('auth-screen').style.display = 'none';
  const app = document.getElementById('app');
  app.classList.add('visible');
  document.getElementById('teacher-name').textContent = State.teacherName;
  loadDashboard();
  showSection('sec-dashboard');
}

document.getElementById('form-login').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = e.target.querySelector('[type=submit]');
  btn.disabled = true;
  const r = await post('login', {
    email: e.target.email.value,
    password: e.target.password.value
  });
  btn.disabled = false;
  if (r.error) return toast(r.error, 'error');
  State.teacherName = r.name;
  showApp();
});

document.getElementById('form-register').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = e.target.querySelector('[type=submit]');
  btn.disabled = true;
  const r = await post('register', {
    full_name: e.target.full_name.value,
    email: e.target.email.value,
    password: e.target.password.value
  });
  btn.disabled = false;
  if (r.error) return toast(r.error, 'error');
  toast('Account created! Please log in.');
  switchAuthTab('login');
  e.target.reset();
});

document.querySelectorAll('.auth-tab').forEach(t => {
  t.addEventListener('click', () => switchAuthTab(t.dataset.tab));
});
function switchAuthTab(tab) {
  document.querySelectorAll('.auth-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
  document.getElementById('form-login').style.display   = tab === 'login'    ? 'block' : 'none';
  document.getElementById('form-register').style.display = tab === 'register' ? 'block' : 'none';
}

document.getElementById('btn-logout').addEventListener('click', async () => {
  await post('logout');
  location.reload();
});

// ── Sidebar nav ─────────────────────────────────────────────────────────────
document.querySelectorAll('.sidebar-item').forEach(item => {
  item.addEventListener('click', () => {
    const sec = item.dataset.section;
    if (!sec) return;
    showSection(sec);
    if (sec === 'sec-dashboard')   loadDashboard();
    if (sec === 'sec-year-levels') loadYearLevels();
    if (sec === 'sec-attendance')  initAttendance();
  });
});

// ── Dashboard ────────────────────────────────────────────────────────────────
async function loadDashboard() {
  const [yls, ] = await Promise.all([get('get_year_levels')]);
  let totalSections = 0, totalStudents = 0;
  const ylNames = [];
  for (const yl of yls) {
    totalSections += parseInt(yl.section_count);
    ylNames.push(yl.name);
  }
  document.getElementById('dash-yl-count').textContent  = yls.length;
  document.getElementById('dash-sec-count').textContent = totalSections;

  const ul = document.getElementById('dash-yl-list');
  ul.innerHTML = yls.length === 0
    ? '<p class="text-muted" style="font-size:14px">No year levels yet. Create one to get started.</p>'
    : yls.map(y => `
        <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border)">
          <div style="width:36px;height:36px;border-radius:9px;background:rgba(124,106,247,0.15);display:flex;align-items:center;justify-content:center;font-size:16px;color:var(--accent2)">
            <i class="ti ti-school"></i>
          </div>
          <div>
            <div style="font-weight:500;font-size:14px">${escHtml(y.name)}</div>
            <div style="font-size:12px;color:var(--muted)">${y.section_count} section${y.section_count != 1 ? 's' : ''}</div>
          </div>
        </div>`).join('');
}

// ── Year Levels ───────────────────────────────────────────────────────────────
async function loadYearLevels() {
  const container = document.getElementById('yl-cards');
  container.innerHTML = '<div class="loading"><i class="ti ti-loader-2"></i></div>';
  const rows = await get('get_year_levels');
  if (!rows.length) {
    container.innerHTML = `<div class="empty-state"><i class="ti ti-school-off"></i><h3>No year levels yet</h3><p>Create a year level to start organizing your classes.</p></div>`;
    return;
  }
  container.innerHTML = rows.map(yl => `
    <div class="card" onclick="openYearLevel(${yl.id},'${escHtml(yl.name)}')">
      <div class="card-badge">${yl.section_count} section${yl.section_count != 1 ? 's' : ''}</div>
      <div class="card-icon"><i class="ti ti-school"></i></div>
      <div class="card-title">${escHtml(yl.name)}</div>
      <div class="card-meta">Click to manage sections</div>
      <div class="card-actions" onclick="event.stopPropagation()">
        <button class="btn btn-ghost btn-sm" onclick="editYearLevel(${yl.id},'${escHtml(yl.name)}')"><i class="ti ti-edit"></i> Edit</button>
        <button class="btn btn-danger btn-sm" onclick="deleteYearLevel(${yl.id})"><i class="ti ti-trash"></i> Delete</button>
      </div>
    </div>`).join('');
}

document.getElementById('btn-add-yl').addEventListener('click', () => {
  const modal = openModal(`
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Add Year Level</span>
        <button class="modal-close"><i class="ti ti-x"></i></button>
      </div>
      <form id="form-add-yl">
        <div class="form-group">
          <label>Year Level Name</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Grade 7, Year 1, Kindergarten" required autofocus>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost modal-close-btn">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-plus"></i> Add</button>
        </div>
      </form>
    </div>`);
  modal.querySelector('.modal-close-btn').onclick = closeModal;
  modal.querySelector('#form-add-yl').addEventListener('submit', async e => {
    e.preventDefault();
    const r = await post('add_year_level', { name: e.target.name.value });
    if (r.error) return toast(r.error, 'error');
    toast('Year level added!');
    closeModal();
    loadYearLevels();
    loadDashboard();
  });
});

async function editYearLevel(id, name) {
  const modal = openModal(`
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Edit Year Level</span>
        <button class="modal-close"><i class="ti ti-x"></i></button>
      </div>
      <form id="form-edit-yl">
        <div class="form-group">
          <label>Year Level Name</label>
          <input type="text" name="name" class="form-control" value="${escHtml(name)}" required autofocus>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost modal-close-btn">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Save</button>
        </div>
      </form>
    </div>`);
  modal.querySelector('.modal-close-btn').onclick = closeModal;
  modal.querySelector('#form-edit-yl').addEventListener('submit', async e => {
    e.preventDefault();
    const r = await post('edit_year_level', { id, name: e.target.name.value });
    if (r.error) return toast(r.error, 'error');
    toast('Year level updated!');
    closeModal();
    loadYearLevels();
  });
}

async function deleteYearLevel(id) {
  if (!await confirm_dialog('Delete this year level? All sections and students inside will be permanently deleted.')) return;
  const r = await post('delete_year_level', { id });
  if (r.error) return toast(r.error, 'error');
  toast('Year level deleted.', 'info');
  loadYearLevels();
  loadDashboard();
}

// ── Sections ─────────────────────────────────────────────────────────────────
function openYearLevel(ylId, ylName) {
  State.currentYL = { id: ylId, name: ylName };
  showSection('sec-sections');
  loadSections(ylId, ylName);
}

async function loadSections(ylId, ylName) {
  document.getElementById('sec-sections-breadcrumb').innerHTML =
    `<a onclick="showSection('sec-year-levels');loadYearLevels()"><i class="ti ti-home"></i> Year Levels</a>
     <i class="ti ti-chevron-right"></i> <span>${escHtml(ylName)}</span>`;
  document.getElementById('sec-sections-title').textContent = ylName + ' — Sections';
  document.getElementById('btn-add-section').dataset.ylid = ylId;

  const container = document.getElementById('sections-cards');
  container.innerHTML = '<div class="loading"><i class="ti ti-loader-2"></i></div>';
  const rows = await get('get_sections', { year_level_id: ylId });
  if (!rows.length) {
    container.innerHTML = `<div class="empty-state"><i class="ti ti-layout-list"></i><h3>No sections yet</h3><p>Add a section with a subject name to get started.</p></div>`;
    return;
  }
  container.innerHTML = rows.map(s => `
    <div class="card" onclick="openSection(${s.id},'${escHtml(s.name)}','${escHtml(s.subject)}')">
      <div class="card-badge">${s.student_count} student${s.student_count != 1 ? 's' : ''}</div>
      <div class="card-icon green"><i class="ti ti-users"></i></div>
      <div class="card-title">${escHtml(s.name)}</div>
      <div class="card-meta"><i class="ti ti-book" style="font-size:13px;vertical-align:-2px;margin-right:4px"></i>${escHtml(s.subject)}</div>
      <div class="card-actions" onclick="event.stopPropagation()">
        <button class="btn btn-ghost btn-sm" onclick="editSection(${s.id},'${escHtml(s.name)}','${escHtml(s.subject)}',${ylId},'${escHtml(ylName)}')"><i class="ti ti-edit"></i> Edit</button>
        <button class="btn btn-danger btn-sm" onclick="deleteSection(${s.id},${ylId},'${escHtml(ylName)}')"><i class="ti ti-trash"></i> Delete</button>
      </div>
    </div>`).join('');
}

document.getElementById('btn-add-section').addEventListener('click', function() {
  const ylId = this.dataset.ylid;
  const modal = openModal(`
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Add Section</span>
        <button class="modal-close"><i class="ti ti-x"></i></button>
      </div>
      <form id="form-add-sec">
        <div class="form-group">
          <label>Section Name</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Section A, Narra, Einstein" required autofocus>
        </div>
        <div class="form-group">
          <label>Subject Name</label>
          <input type="text" name="subject" class="form-control" placeholder="e.g. Mathematics, English, Science" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost modal-close-btn">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-plus"></i> Add</button>
        </div>
      </form>
    </div>`);
  modal.querySelector('.modal-close-btn').onclick = closeModal;
  modal.querySelector('#form-add-sec').addEventListener('submit', async e => {
    e.preventDefault();
    const r = await post('add_section', { year_level_id: ylId, name: e.target.name.value, subject: e.target.subject.value });
    if (r.error) return toast(r.error, 'error');
    toast('Section added!');
    closeModal();
    loadSections(ylId, State.currentYL.name);
  });
});

async function editSection(id, name, subject, ylId, ylName) {
  const modal = openModal(`
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Edit Section</span>
        <button class="modal-close"><i class="ti ti-x"></i></button>
      </div>
      <form id="form-edit-sec">
        <div class="form-group">
          <label>Section Name</label>
          <input type="text" name="name" class="form-control" value="${escHtml(name)}" required autofocus>
        </div>
        <div class="form-group">
          <label>Subject Name</label>
          <input type="text" name="subject" class="form-control" value="${escHtml(subject)}" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost modal-close-btn">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Save</button>
        </div>
      </form>
    </div>`);
  modal.querySelector('.modal-close-btn').onclick = closeModal;
  modal.querySelector('#form-edit-sec').addEventListener('submit', async e => {
    e.preventDefault();
    const r = await post('edit_section', { id, name: e.target.name.value, subject: e.target.subject.value });
    if (r.error) return toast(r.error, 'error');
    toast('Section updated!');
    closeModal();
    loadSections(ylId, ylName);
  });
}

async function deleteSection(id, ylId, ylName) {
  if (!await confirm_dialog('Delete this section? All students and attendance records will be permanently deleted.')) return;
  const r = await post('delete_section', { id });
  if (r.error) return toast(r.error, 'error');
  toast('Section deleted.', 'info');
  loadSections(ylId, ylName);
}

// ── Students (inside a section) ───────────────────────────────────────────────
function openSection(secId, secName, secSubject) {
  State.currentSection = { id: secId, name: secName, subject: secSubject };
  showSection('sec-students');
  loadStudents(secId, secName, secSubject);
}

async function loadStudents(secId, secName, secSubject) {
  const yl = State.currentYL;
  document.getElementById('sec-students-breadcrumb').innerHTML =
    `<a onclick="showSection('sec-year-levels');loadYearLevels()"><i class="ti ti-home"></i> Year Levels</a>
     <i class="ti ti-chevron-right"></i>
     <a onclick="openYearLevel(${yl.id},'${escHtml(yl.name)}')">${escHtml(yl.name)}</a>
     <i class="ti ti-chevron-right"></i>
     <span>${escHtml(secName)}</span>`;
  document.getElementById('sec-students-title').textContent = secName;
  document.getElementById('sec-students-subject').textContent = secSubject;
  document.getElementById('btn-add-student').dataset.secid = secId;
  document.getElementById('btn-take-attendance').dataset.secid = secId;
  document.getElementById('btn-view-report').dataset.secid = secId;

  const tbody = document.getElementById('students-tbody');
  tbody.innerHTML = '<tr><td colspan="4" class="loading"><i class="ti ti-loader-2"></i> Loading...</td></tr>';
  const rows = await get('get_students', { section_id: secId });
  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="4"><div class="empty-state"><i class="ti ti-user-off"></i><h3>No students yet</h3><p>Add students to this section.</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML = rows.map((s, i) => `
    <tr>
      <td style="color:var(--muted);font-size:13px">${i + 1}</td>
      <td>
        <div style="font-weight:500">${escHtml(s.full_name)}</div>
        ${s.student_no ? `<div style="font-size:12px;color:var(--muted)">${escHtml(s.student_no)}</div>` : ''}
      </td>
      <td style="color:var(--muted);font-size:13px">${new Date(s.created_at).toLocaleDateString()}</td>
      <td>
        <div class="td-actions">
          <button class="btn btn-ghost btn-sm btn-icon" title="Edit" onclick="editStudent(${s.id},'${escHtml(s.full_name)}','${escHtml(s.student_no)}')"><i class="ti ti-edit"></i></button>
          <button class="btn btn-danger btn-sm btn-icon" title="Remove" onclick="deleteStudent(${s.id},${secId})"><i class="ti ti-trash"></i></button>
        </div>
      </td>
    </tr>`).join('');
}

document.getElementById('btn-add-student').addEventListener('click', function() {
  const secId = this.dataset.secid;
  const modal = openModal(`
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Add Student</span>
        <button class="modal-close"><i class="ti ti-x"></i></button>
      </div>
      <form id="form-add-student">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="full_name" class="form-control" placeholder="Last name, First name MI." required autofocus>
        </div>
        <div class="form-group">
          <label>Student Number <span style="color:var(--muted2)">(optional)</span></label>
          <input type="text" name="student_no" class="form-control" placeholder="e.g. 2024-00123">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost modal-close-btn">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-plus"></i> Add Student</button>
        </div>
      </form>
    </div>`);
  modal.querySelector('.modal-close-btn').onclick = closeModal;
  modal.querySelector('#form-add-student').addEventListener('submit', async e => {
    e.preventDefault();
    const r = await post('add_student', { section_id: secId, full_name: e.target.full_name.value, student_no: e.target.student_no.value });
    if (r.error) return toast(r.error, 'error');
    toast('Student added!');
    closeModal();
    loadStudents(secId, State.currentSection.name, State.currentSection.subject);
  });
});

async function editStudent(id, name, sno) {
  const modal = openModal(`
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Edit Student</span>
        <button class="modal-close"><i class="ti ti-x"></i></button>
      </div>
      <form id="form-edit-student">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="full_name" class="form-control" value="${escHtml(name)}" required autofocus>
        </div>
        <div class="form-group">
          <label>Student Number</label>
          <input type="text" name="student_no" class="form-control" value="${escHtml(sno)}">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost modal-close-btn">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Save</button>
        </div>
      </form>
    </div>`);
  modal.querySelector('.modal-close-btn').onclick = closeModal;
  modal.querySelector('#form-edit-student').addEventListener('submit', async e => {
    e.preventDefault();
    const r = await post('edit_student', { id, full_name: e.target.full_name.value, student_no: e.target.student_no.value });
    if (r.error) return toast(r.error, 'error');
    toast('Student updated!');
    closeModal();
    const s = State.currentSection;
    loadStudents(s.id, s.name, s.subject);
  });
}

async function deleteStudent(id, secId) {
  if (!await confirm_dialog('Remove this student from the section? Their attendance records will also be deleted.')) return;
  const r = await post('delete_student', { id });
  if (r.error) return toast(r.error, 'error');
  toast('Student removed.', 'info');
  const s = State.currentSection;
  loadStudents(s.id, s.name, s.subject);
}

// ── Take Attendance ─────────────────────────────────────────────────────────
document.getElementById('btn-take-attendance').addEventListener('click', function() {
  const secId = this.dataset.secid;
  const s = State.currentSection;
  State.attSection = { id: secId, name: s.name, subject: s.subject };
  showSection('sec-attendance');
  initAttendanceForSection(secId, s.name, s.subject);
});

async function initAttendance() {
  // Called from sidebar – just show picker UI
  document.getElementById('att-section-picker-area').style.display = 'block';
  document.getElementById('att-main-area').style.display = 'none';
  populateYLPicker();
}

async function populateYLPicker() {
  const sel = document.getElementById('att-pick-yl');
  sel.innerHTML = '<option value="">— Select Year Level —</option>';
  const yls = await get('get_year_levels');
  yls.forEach(y => {
    const o = document.createElement('option');
    o.value = y.id; o.textContent = y.name;
    sel.appendChild(o);
  });
}

document.getElementById('att-pick-yl').addEventListener('change', async function() {
  const sel2 = document.getElementById('att-pick-section');
  sel2.innerHTML = '<option value="">— Select Section —</option>';
  if (!this.value) return;
  const secs = await get('get_sections', { year_level_id: this.value });
  secs.forEach(s => {
    const o = document.createElement('option');
    o.value = s.id; o.dataset.name = s.name; o.dataset.subject = s.subject;
    o.textContent = `${s.name} — ${s.subject}`;
    sel2.appendChild(o);
  });
});

document.getElementById('btn-go-attendance').addEventListener('click', function() {
  const secSel = document.getElementById('att-pick-section');
  if (!secSel.value) return toast('Please select a section', 'error');
  const opt = secSel.options[secSel.selectedIndex];
  initAttendanceForSection(secSel.value, opt.dataset.name, opt.dataset.subject);
});

async function initAttendanceForSection(secId, secName, secSubject) {
  document.getElementById('att-section-picker-area').style.display = 'none';
  document.getElementById('att-main-area').style.display = 'block';
  document.getElementById('att-section-name').textContent = `${secName} — ${secSubject}`;
  document.getElementById('att-date').value = today();
  document.getElementById('att-date').dataset.secid = secId;

  await loadAttendanceForm(secId, today());

  document.getElementById('att-date').onchange = function() {
    loadAttendanceForm(secId, this.value);
  };
  document.getElementById('btn-mark-all-present').onclick = () => {
    document.querySelectorAll('.att-radio[value="present"]').forEach(r => r.checked = true);
  };
  document.getElementById('btn-mark-all-absent').onclick = () => {
    document.querySelectorAll('.att-radio[value="absent"]').forEach(r => r.checked = true);
  };
  document.getElementById('btn-save-attendance').onclick = () => saveAttendance(secId);
}

async function loadAttendanceForm(secId, date) {
  const tbody = document.getElementById('att-form-tbody');
  tbody.innerHTML = '<tr><td colspan="6" class="loading"><i class="ti ti-loader-2"></i> Loading students...</td></tr>';

  const [students, existing] = await Promise.all([
    get('get_students', { section_id: secId }),
    get('get_session', { section_id: secId, date })
  ]);

  const savedMap = {};
  if (existing.records) {
    existing.records.forEach(r => { savedMap[r.student_id] = r.status; });
  }

  if (!students.length) {
    tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="ti ti-users-minus"></i><h3>No students in this section</h3></div></td></tr>';
    return;
  }

  const statuses = ['present','absent','late','excused'];
  const labels   = { present: 'P', absent: 'A', late: 'L', excused: 'E' };

  tbody.innerHTML = students.map((s, i) => {
    const defaultStatus = savedMap[s.id] || 'present';
    const radios = statuses.map(st => `
      <input class="att-radio" type="radio" name="att_${s.id}" id="att_${s.id}_${st}" value="${st}" ${defaultStatus === st ? 'checked' : ''}>
      <label for="att_${s.id}_${st}" title="${st.charAt(0).toUpperCase()+st.slice(1)}">${labels[st]}</label>
    `).join('');
    return `<tr data-sid="${s.id}">
      <td style="color:var(--muted);font-size:13px">${i+1}</td>
      <td>
        <div style="font-weight:500;font-size:14px">${escHtml(s.full_name)}</div>
        ${s.student_no ? `<div style="font-size:12px;color:var(--muted)">${escHtml(s.student_no)}</div>` : ''}
      </td>
      <td><div class="att-radio-group">${radios}</div></td>
    </tr>`;
  }).join('');

  if (existing.session) {
    toast(`Loaded saved attendance for ${date}`, 'info');
  }
}

async function saveAttendance(secId) {
  const date = document.getElementById('att-date').value;
  if (!date) return toast('Please set a date', 'error');

  const records = [];
  document.querySelectorAll('#att-form-tbody tr[data-sid]').forEach(row => {
    const sid = row.dataset.sid;
    const checked = row.querySelector(`.att-radio:checked`);
    if (checked) records.push({ student_id: sid, status: checked.value });
  });

  if (!records.length) return toast('No students to record', 'error');

  const btn = document.getElementById('btn-save-attendance');
  btn.disabled = true;
  const r = await post('save_attendance', { section_id: secId, date, records: JSON.stringify(records) });
  btn.disabled = false;
  if (r.error) return toast(r.error, 'error');
  toast(`Attendance saved for ${date}!`);
}

// ── Reports ─────────────────────────────────────────────────────────────────
document.getElementById('btn-view-report').addEventListener('click', function() {
  const secId = this.dataset.secid;
  const s = State.currentSection;
  showSection('sec-report');
  loadReport(secId, s.name, s.subject);
});

async function loadReport(secId, secName, secSubject) {
  const yl = State.currentYL;
  document.getElementById('sec-report-breadcrumb').innerHTML =
    `<a onclick="showSection('sec-year-levels');loadYearLevels()"><i class="ti ti-home"></i> Year Levels</a>
     <i class="ti ti-chevron-right"></i>
     <a onclick="openYearLevel(${yl.id},'${escHtml(yl.name)}')">${escHtml(yl.name)}</a>
     <i class="ti ti-chevron-right"></i>
     <a onclick="openSection(${secId},'${escHtml(secName)}','${escHtml(secSubject)}')">${escHtml(secName)}</a>
     <i class="ti ti-chevron-right"></i> Report`;
  document.getElementById('rep-section-name').textContent = `${secName} — ${secSubject}`;

  const [report, log] = await Promise.all([
    get('get_report', { section_id: secId }),
    get('get_attendance_log', { section_id: secId })
  ]);

  // Stats
  const totalSessions = log.length;
  const totalStudents = report.length;
  const avgRate = totalStudents > 0
    ? (report.reduce((a, r) => a + parseFloat(r.attendance_rate || 0), 0) / totalStudents).toFixed(1)
    : 0;
  const dropCount = report.filter(r => parseFloat(r.attendance_rate) < 70 && r.total_sessions > 0).length;

  document.getElementById('rep-stat-sessions').textContent = totalSessions;
  document.getElementById('rep-stat-students').textContent = totalStudents;
  document.getElementById('rep-stat-avg').textContent      = avgRate + '%';
  document.getElementById('rep-stat-drops').textContent    = dropCount;

  // Table
  const tbody = document.getElementById('rep-tbody');
  if (!report.length) {
    tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="ti ti-chart-off"></i><h3>No data yet</h3><p>Record attendance first to see reports.</p></div></td></tr>';
  } else {
    // Sort descending by rate to determine rankings
    const sorted = [...report].sort((a,b) => parseFloat(b.attendance_rate||0) - parseFloat(a.attendance_rate||0));

    tbody.innerHTML = report.map((s, i) => {
      const rate = parseFloat(s.attendance_rate || 0);
      const rank = sorted.findIndex(x => x.id === s.id);
      let statusBadge, rowClass = '';
      if (!s.total_sessions) {
        statusBadge = '<span class="badge" style="background:rgba(255,255,255,0.05);color:var(--muted2)">No data</span>';
      } else if (rate < 70) {
        statusBadge = '<span class="badge badge-dropped"><i class="ti ti-alert-triangle"></i> For Dropping</span>';
        rowClass = 'dropped-row';
      } else if (rate < 80) {
        statusBadge = '<span class="badge badge-at-risk"><i class="ti ti-alert-circle"></i> At Risk</span>';
      } else {
        statusBadge = '<span class="badge badge-good"><i class="ti ti-circle-check"></i> Good Standing</span>';
      }

      const barColor = rate >= 80 ? 'green' : rate >= 70 ? 'amber' : 'red';
      const isTop = rank === 0 && s.total_sessions > 0;
      const isBot = rank === sorted.length - 1 && s.total_sessions > 0;

      return `<tr class="${rowClass}">
        <td style="color:var(--muted);font-size:13px">${i+1}</td>
        <td>
          <div class="${isTop ? 'top-student' : isBot ? 'bot-student' : ''}">${escHtml(s.full_name)}</div>
          ${s.student_no ? `<div style="font-size:12px;color:var(--muted)">${escHtml(s.student_no)}</div>` : ''}
          ${isTop ? '<div style="font-size:11px;color:var(--success)"><i class="ti ti-crown"></i> Most Present</div>' : ''}
          ${isBot && s.total_sessions > 0 ? '<div style="font-size:11px;color:var(--danger)"><i class="ti ti-mood-sad"></i> Most Absent</div>' : ''}
        </td>
        <td style="color:var(--success)">${s.present_count||0}</td>
        <td style="color:var(--danger)">${s.absent_count||0}</td>
        <td style="color:var(--late)">${s.late_count||0}</td>
        <td style="color:var(--info)">${s.excused_count||0}</td>
        <td>
          <div style="display:flex;align-items:center;gap:8px;min-width:120px">
            <span style="font-weight:600;font-size:14px;min-width:40px;color:var(--${barColor === 'green' ? 'success' : barColor === 'amber' ? 'warning' : 'danger'})">${rate}%</span>
            <div class="progress-bar" style="flex:1"><div class="progress-fill ${barColor}" style="width:${rate}%"></div></div>
          </div>
        </td>
        <td>${statusBadge}</td>
      </tr>`;
    }).join('');
  }

  // Attendance log
  const logTbody = document.getElementById('log-tbody');
  if (!log.length) {
    logTbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:2rem;font-size:14px">No sessions recorded yet.</td></tr>';
  } else {
    logTbody.innerHTML = log.map(l => `
      <tr>
        <td style="font-weight:500">${new Date(l.session_date + 'T00:00:00').toLocaleDateString('en-PH', { weekday:'short', year:'numeric', month:'short', day:'numeric' })}</td>
        <td style="color:var(--muted)">${l.total||0}</td>
        <td style="color:var(--success)">${l.present||0}</td>
        <td style="color:var(--danger)">${l.absent||0}</td>
        <td style="color:var(--late)">${l.late||0}</td>
        <td style="color:var(--info)">${l.excused||0}</td>
      </tr>`).join('');
  }
}

// ── Init ─────────────────────────────────────────────────────────────────────
checkSession();
