// --- Global Data Store ---
let weeks = [];

// --- Element Selections ---
const weekForm = document.querySelector('#week-form');
const weeksTableBody = document.querySelector('#weeks-tbody');
const titleInput = document.querySelector('#week-title');
const startDateInput = document.querySelector('#week-start-date');
const descriptionInput = document.querySelector('#week-description');
const linksInput = document.querySelector('#week-links');

// --- Functions ---
function createWeekRow(week) {
  const row = document.createElement('tr');

  const tdTitle = document.createElement('td');
  tdTitle.textContent = week.title;

  const tdDesc = document.createElement('td');
  tdDesc.textContent = week.description;

  const tdActions = document.createElement('td');

  const editBtn = document.createElement('button');
  editBtn.textContent = "Edit";
  editBtn.className = "edit-btn";
  editBtn.setAttribute("data-id", week.id);

  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = "Delete";
  deleteBtn.className = "delete-btn";
  deleteBtn.setAttribute("data-id", week.id);

  tdActions.appendChild(editBtn);
  tdActions.appendChild(deleteBtn);

  row.appendChild(tdTitle);
  row.appendChild(tdActions);
  row.appendChild(tdDesc);

  return row;
}

function renderTable() {
  weeksTableBody.innerHTML = "";

  for (let i = 0; i < weeks.length; i++) {
    const newRow = createWeekRow(weeks[i]);
    weeksTableBody.appendChild(newRow);
  }
}

function handleAddWeek(event) {
  event.preventDefault();

  const title = titleInput.value;
  const startDate = startDateInput.value;
  const description = descriptionInput.value;
  const linksText = linksInput.value;
  const linksArray = linksText.split("\n");

  const newWeek = {
    id: `week_${Date.now()}`,
    title: title,
    startDate: startDate,
    description: description,
    links: linksArray
  };

  weeks.push(newWeek);

  renderTable();

  weekForm.reset();
}

function handleTableClick(event) {
  if (event.target.classList.contains("delete-btn")) {
    const weekId = event.target.getAttribute("data-id");
    weeks = weeks.filter(week => week.id !== weekId);

    renderTable();
  }
}

async function loadAndInitialize() {
  const response = await fetch('weeks.json');
  weeks = await response.json();

  renderTable();

  weekForm.addEventListener('submit', handleAddWeek);
  weeksTableBody.addEventListener('click', handleTableClick);
  weeksTableBody.addEventListener('click', handleTableClick);
  weeksTableBody.addEventListener('click', handleTableClick);
  weeksTableBody.addEventListener('click', handleAddWeek);
  weeksTableBody.addEventListener('click', renderTable);
  weeksTableBody.addEventListener('click', renderComments);
  weeksTableBody.addEventListener('click', handleTableClick);

  }
}

loadAndInitialize();
