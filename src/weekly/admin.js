/*
  Requirement: Make the "Manage Weekly Breakdown" page interactive.
*/

// --- Global Data Store ---
let weeks = [];

// --- Element Selections ---
const weekForm = document.querySelector("#week-form");
const weeksTableBody = document.querySelector("#weeks-tbody");

// --- Functions ---

/**
 * Create a <tr> for a week.
 */
function createWeekRow(week) {
  const row = document.createElement("tr");

  const titleTd = document.createElement("td");
  titleTd.textContent = week.title || "";

  const descTd = document.createElement("td");
  descTd.textContent = week.description || "";

  const actionsTd = document.createElement("td");

  const editBtn = document.createElement("button");
  editBtn.textContent = "Edit";
  editBtn.classList.add("edit-btn");
  editBtn.dataset.id = week.id;

  const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.classList.add("delete-btn");
  deleteBtn.dataset.id = week.id;

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);

  row.appendChild(titleTd);
  row.appendChild(descTd);
  row.appendChild(actionsTd);

  return row;
}

/**
 * Re-render the table body based on the weeks array.
 */
function renderTable() {
  // clear current rows
  weeksTableBody.innerHTML = "";

  // render each week
  weeks.forEach((week) => {
    const row = createWeekRow(week);
    weeksTableBody.appendChild(row);
  });
}

/**
 * Handle adding a new week from the form.
 */
function handleAddWeek(event) {
  event.preventDefault();

  const titleInput = document.querySelector("#week-title");
  const dateInput = document.querySelector("#week-start-date");
  const descInput = document.querySelector("#week-description");
  const linksInput = document.querySelector("#week-links");

  const title = titleInput.value.trim();
  const startDate = dateInput.value;
  const description = descInput.value.trim();
  const linksRaw = linksInput.value.trim();

  const links = linksRaw === "" ? [] : linksRaw.split("\n");

  if (!title) {
    // just a simple check so we don't add empty weeks
    return;
  }

  const newWeek = {
    id: week_${Date.now()},
    title,
    startDate,
    description,
    links
  };

  weeks.push(newWeek);
  renderTable();
  weekForm.reset();
}

/**
 * Handle clicks inside the table (delete using event delegation).
 */
function handleTableClick(event) {
  const target = event.target;

  if (target.classList.contains("delete-btn")) {
    const id = target.dataset.id;

    // keep all weeks except the one with this id
    weeks = weeks.filter((week) => week.id !== id);

    renderTable();
  }

  // Edit button could be implemented later if needed
}

/**
 * Load initial data and wire up event listeners.
 */
async function loadAndInitialize() {
  try {
    const response = await fetch("weeks.json");
    if (response.ok) {
      const data = await response.json();
      // assuming the JSON file is just an array of weeks
      weeks = Array.isArray(data) ? data : [];
    }
  } catch (err) {
    // if the file is missing or something goes wrong, just start with an empty list
    console.error("Could not load weeks.json", err);
    weeks = [];
  }

  renderTable();

  if (weekForm) {
    weekForm.addEventListener("submit", handleAddWeek);
  }

  if (weeksTableBody) {
    weeksTableBody.addEventListener("click", handleTableClick);
  }
}

// --- Initial Page Load ---
loadAndInitialize();
