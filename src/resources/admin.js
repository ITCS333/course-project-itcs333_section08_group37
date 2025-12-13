const apiUrl = 'index.php';

function loadResources() {
    fetch(apiUrl)
        .then(res => res.json())
        .then(data => {
            const tbody = document.querySelector('#resourcesTable tbody');
            tbody.innerHTML = '';

            if (data && data.success && Array.isArray(data.data)) {
                data.data.forEach(resource => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${resource.id}</td>
                        <td>${resource.title}</td>
                        <td>${resource.description ?? ''}</td>
                        <td>
                            <a href="${resource.link}" target="_blank">
                                Link
                            </a>
                        </td>
                        <td>
                            <button onclick="editResource(${resource.id})">Edit</button>
                            <button onclick="deleteResource(${resource.id})">Delete</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(err => console.error(err));
}

document
.getElementById('resourceForm')
.addEventListener('submit', function (e) {
    e.preventDefault();

    const id = document.getElementById('resourceId').value;
    const title = document.getElementById('title').value.trim();
    const description = document.getElementById('description').value.trim();
    const link = document.getElementById('link').value.trim();

    const method = id ? 'PUT' : 'POST';

    fetch(apiUrl, {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: id || undefined,
            title,
            description,
            link
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.success) {
            resetForm();
            loadResources();
        }
    })
    .catch(err => console.error(err));
});

function editResource(id) {
    fetch(`${apiUrl}?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data && data.success && data.data) {
                const r = data.data;
                document.getElementById('resourceId').value = r.id;
                document.getElementById('title').value = r.title;
                document.getElementById('description').value = r.description ?? '';
                document.getElementById('link').value = r.link;
            }
        })
        .catch(err => console.error(err));
}


function deleteResource(id) {
    if (!confirm('Are you sure you want to delete this resource?')) return;

    fetch(apiUrl, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.success) {
            loadResources();
        }
    })
    .catch(err => console.error(err));
}


function resetForm() {
    document.getElementById('resourceForm').reset();
    document.getElementById('resourceId').value = '';
}

document
.getElementById('cancelEdit')
.addEventListener('click', resetForm);


loadResources();
