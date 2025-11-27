// --- Global Data Store ---
let resources = [];
let editResourceId = null;

// --- Element Selections ---
const resourceForm = document.getElementById('resource-form');
const resourcesTableBody = document.getElementById('resources-tbody');

// --- Functions ---

function createResourceRow(resource) {
  const tr = document.createElement('tr');

  // Title
  const tdTitle = document.createElement('td');
  tdTitle.textContent = resource.title;
  tr.appendChild(tdTitle);

  // Description
  const tdDesc = document.createElement('td');
  tdDesc.textContent = resource.description;
  tr.appendChild(tdDesc);

  // Actions
  const tdActions = document.createElement('td');

  const editBtn = document.createElement('button');
  editBtn.textContent = 'Edit';
  editBtn.classList.add('edit-btn');
  editBtn.dataset.id = resource.id;

  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.classList.add('delete-btn');
  deleteBtn.dataset.id = resource.id;

  tdActions.appendChild(editBtn);
  tdActions.appendChild(deleteBtn);
  tr.appendChild(tdActions);

  return tr;
}

function renderTable() {
  resourcesTableBody.innerHTML = '';
  resources.forEach(resource => {
    const row = createResourceRow(resource);
    resourcesTableBody.appendChild(row);
  });
}

function handleAddResource(event) {
  event.preventDefault();

  const title = document.getElementById('resource-title').value.trim();
  const description = document.getElementById('resource-description').value.trim();
  const link = document.getElementById('resource-link').value.trim();

  if (!title || !link) return;

  if (editResourceId) {
    // Edit existing resource
    const index = resources.findIndex(r => r.id === editResourceId);
    if (index !== -1) {
      resources[index].title = title;
      resources[index].description = description;
      resources[index].link = link;
    }
    editResourceId = null;
    document.getElementById('add-resource').textContent = "Add Resource";
  } else {
    // Add new resource
    const newResource = {
      id: `res_${Date.now()}`,
      title,
      description,
      link
    };
    resources.push(newResource);
  }

  renderTable();
  resourceForm.reset();
}

function handleTableClick(event) {
  const target = event.target;
  const id = target.dataset.id;

  if (target.classList.contains('delete-btn')) {
    resources = resources.filter(r => r.id !== id);
    renderTable();
  } else if (target.classList.contains('edit-btn')) {
    const resource = resources.find(r => r.id === id);
    if (resource) {
      document.getElementById('resource-title').value = resource.title;
      document.getElementById('resource-description').value = resource.description;
      document.getElementById('resource-link').value = resource.link;
      editResourceId = id;
      document.getElementById('add-resource').textContent = "Update Resource";
    }
  }
}

async function loadAndInitialize() {
  try {
    const response = await fetch('resources.json');
    resources = await response.json();
  } catch (error) {
    console.error("Error loading resources.json:", error);
    resources = [];
  }

  renderTable();

  // Event listeners
  resourceForm.addEventListener('submit', handleAddResource);
  resourcesTableBody.addEventListener('click', handleTableClick);
}

// --- Initial Page Load ---
loadAndInitialize();
