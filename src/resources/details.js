const apiUrl = 'index.php';


const urlParams = new URLSearchParams(window.location.search);
const resourceId = urlParams.get('id');
document.getElementById('resourceId').value = resourceId;

function loadResource() {
    fetch(`${apiUrl}?id=${resourceId}`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                const res = data.data;
                document.getElementById('resTitle').textContent = res.title;
                document.getElementById('resDescription').textContent = res.description;
                document.getElementById('resLink').href = res.link;
            } else {
                alert('Resource not found');
            }
        });
}


function loadComments() {
    fetch(`${apiUrl}?action=comments&resource_id=${resourceId}`)
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('commentsList');
            list.innerHTML = '';
            if(data.success) {
                data.data.forEach(c => {
                    const li = document.createElement('li');
                    li.innerHTML = `<strong>${c.author}:</strong> ${c.text} <em>(${c.created_at})</em>`;
                    list.appendChild(li);
                });
            }
        });
}


document.getElementById('commentForm').addEventListener('submit', function(e){
    e.preventDefault();
    const author = document.getElementById('author').value;
    const text = document.getElementById('text').value;

    fetch(`${apiUrl}?action=comment`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({resource_id: resourceId, author, text})
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if(data.success) {
            document.getElementById('commentForm').reset();
            loadComments();
        }
    });
});


loadResource();
loadComments();
