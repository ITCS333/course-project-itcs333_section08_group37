// --- Global Data Store ---
let weeks = [];

// --- Element Selections ---
const weekForm = document.querySelector('#week-form');
const weeksTableBody = document.querySelector('#weeks-tbody');

// --- Functions ---

// إنشاء صف جديد في الجدول
function createWeekRow(week) {
  const tr = document.createElement('tr');

  // عنوان الأسبوع
  const tdTitle = document.createElement('td');
  tdTitle.textContent = week.title;

  // الوصف
  const tdDesc = document.createElement('td');
  tdDesc.textContent = week.description;

  // أزرار الأكشن
  const tdActions = document.createElement('td');
  const editBtn = document.createElement('button');
  editBtn.textContent = "Edit";
  editBtn.classList.add("edit-btn");
  editBtn.dataset.id = week.id;

  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = "Delete";
  deleteBtn.classList.add("delete-btn");
  deleteBtn.dataset.id = week.id;

  tdActions.appendChild(editBtn);
  tdActions.appendChild(deleteBtn);

  // إضافة الأعمدة للصف
  tr.appendChild(tdTitle);
  tr.appendChild(tdDesc);
  tr.appendChild(tdActions);

  return tr;
}

// عرض الجدول
function renderTable() {
  weeksTableBody.innerHTML = "";
  weeks.forEach(week => {
    const row = createWeekRow(week);
    weeksTableBody.appendChild(row);
  });
}

// إضافة أسبوع جديد
function handleAddWeek(event) {
  event.preventDefault();

  const title = document.querySelector('#week-title').value.trim();
  const startDate = document.querySelector('#week-start-date').value;
  const description = document.querySelector('#week-description').value.trim();
  const linksText = document.querySelector('#week-links').value.trim();

  if (!title || !startDate) return;

  const links = linksText ? linksText.split("\n") : [];

  const newWeek = {
    id: `week_${Date.now()}`,
    title,
    startDate,
    description,
    links
  };

  weeks.push(newWeek);
  renderTable();
  weekForm.reset();
}

// حذف أسبوع
function handleTableClick(event) {
  if (event.target.classList.contains("delete-btn")) {
    const id = event.target.dataset.id;
    weeks = weeks.filter(week => week.id !== id);
    renderTable();
  }
  // مبدئياً زر Edit مش مفعل، ممكن نضيفه لاحقاً
}

// تحميل البيانات من ملف weeks.json
async function loadAndInitialize() {
  try {
    const res = await fetch('weeks.json');
    const data = await res.json();
    weeks = data;
    renderTable();

    weekForm.addEventListener('submit', handleAddWeek);
    weeksTableBody.addEventListener('click', handleTableClick);
  } catch (err) {
    console.error("Error loading weeks:", err);
  }
}

// --- Initial Page Load ---
loadAndInitialize();
