const apiUrl = 'index.php';


function loadResources() {
    fetch(apiUrl)
        .then(res => res.json())
        .then(data => {
            const tbody = document.querySelector('#resourcesTable tbody');
            tbody.innerHTML = '';
            if(data.success) {
                data.data.forEach(res => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${res.id}</td>
                        <td>${res.title}</td>
                        <td>${res.description}</td>
                        <td><a href="${res.link}" target="_blank">Link</a></td>
                        <td>
                            <button onclick="editResource(${res.id})">Edit</button>
                            <button onclick="deleteResource(${res.id})">Delete</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        });
}


document.getElementById('resourceForm').addEventListener('submit', function(e){
    e.preventDefault();
    const id = document.getElementById('resourceId').value;
    const title = document.getElementById('title').value;
    const description = document.getElementById('description').value;
    const link = document.getElementById('link').value;

    const method = id ? 'PUT' : 'POST';
    const body = JSON.stringify({id, title, description, link});

    fetch(apiUrl, {
        method: method,
        headers: {'Content-Type':'application/json'},
        body: body
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if(data.success) {
            document.getElementById('resourceForm').reset();
            document.getElementById('resourceId').value = '';
            loadResources();
        }
    });
});


function editResource(id) {
    fetch(`${apiUrl}?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                const res = data.data;
                document.getElementById('resourceId').value = res.id;
                document.getElementById('title').value = res.title;
                document.getElementById('description').value = res.description;
                document.getElementById('link').value = res.link;
            } else {
                alert('Resource not found');
            }
        });
}


function deleteResource(id) {
    if(!confirm('Are you sure you want to delete this resource?')) return;
    fetch(`${apiUrl}?id=${id}`, {method: 'DELETE'})
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if(data.success) loadResources();
        });
}


document.getElementById('cancelEdit').addEventListener('click', function(){
    document.getElementById('resourceForm').reset();
    document.getElementById('resourceId').value = '';
});


loadResources();
