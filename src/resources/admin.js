// --- Global Data Store ---
let resources = [];

// --- Element Selections ---
const resourceForm = document.querySelector('#resource-form');
const resourcesTableBody = document.querySelector('#resources-tbody');

// --- Functions ---

// Create a <tr> for a resource
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
    editBtn.className = 'edit-btn';
    editBtn.dataset.id = resource.id;

    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Delete';
    deleteBtn.className = 'delete-btn';
    deleteBtn.dataset.id = resource.id;

    tdActions.appendChild(editBtn);
    tdActions.appendChild(deleteBtn);
    tr.appendChild(tdActions);

    return tr;
}

// Render the table
function renderTable() {
    resourcesTableBody.innerHTML = '';
    resources.forEach(resource => {
        const tr = createResourceRow(resource);
        resourcesTableBody.appendChild(tr);
    });
}

// Handle adding a new resource
function handleAddResource(event) {
    event.preventDefault();

    const title = document.querySelector('#resource-title').value.trim();
    const description = document.querySelector('#resource-description').value.trim();
    const link = document.querySelector('#resource-link').value.trim();

    if (!title || !link) {
        alert('Title and Link are required!');
        return;
    }

    const newResource = {
        id: `res_${Date.now()}`,
        title,
        description,
        link
    };

    resources.push(newResource);
    renderTable();
    resourceForm.reset();
}

// Handle clicks on table (delegation)
function handleTableClick(event) {
    if (event.target.classList.contains('delete-btn')) {
        const idToDelete = event.target.dataset.id;
        resources = resources.filter(r => r.id !== idToDelete);
        renderTable();
    }

    if (event.target.classList.contains('edit-btn')) {
        const idToEdit = event.target.dataset.id;
        const resource = resources.find(r => r.id === idToEdit);
        if (resource) {
            document.querySelector('#resource-title').value = resource.title;
            document.querySelector('#resource-description').value = resource.description;
            document.querySelector('#resource-link').value = resource.link;

            // Remove the old resource from the array
            resources = resources.filter(r => r.id !== idToEdit);
            renderTable();
        }
    }
}

// Load initial data and setup event listeners
async function loadAndInitialize() {
    try {
        const response = await fetch('resources.json');
        if (!response.ok) throw new Error('Failed to load resources.json');

        resources = await response.json();
        renderTable();

        resourceForm.addEventListener('submit', handleAddResource);
        resourcesTableBody.addEventListener('click', handleTableClick);
    } catch (error) {
        console.error(error);
        alert('Error loading resources.');
    }
}

// --- Initial Page Load ---
loadAndInitialize();
