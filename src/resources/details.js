const apiUrl = 'index.php';


const urlParams = new URLSearchParams(window.location.search);
const resourceId = urlParams.get('id');

if (resourceId) {
    const hiddenId = document.getElementById('resourceId');
    if (hiddenId) hiddenId.value = resourceId;
}


function loadResource() {
    if (!resourceId) return;

    fetch(`${apiUrl}?id=${resourceId}`)
        .then(res => res.json())
        .then(data => {
            if (data && data.success && data.data) {
                const r = data.data;
                document.getElementById('resTitle').textContent = r.title;
                document.getElementById('resDescription').textContent = r.description ?? '';
                document.getElementById('resLink').href = r.link;
            }
        })
        .catch(err => console.error(err));
}


function loadComments() {
    if (!resourceId) return;

    fetch(`${apiUrl}?action=comments&resource_id=${resourceId}`)
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('commentsList');
            list.innerHTML = '';

            if (data && data.success && Array.isArray(data.data)) {
                data.data.forEach(c => {
                    const li = document.createElement('li');
                    const date = c.created_at ? ` (${c.created_at})` : '';
                    li.innerHTML = `<strong>${c.author}:</strong> ${c.text}${date}`;
                    list.appendChild(li);
                });
            }
        })
        .catch(err => console.error(err));
}


document
.getElementById('commentForm')
.addEventListener('submit', function (e) {
    e.preventDefault();

    if (!resourceId) return;

    const author = document.getElementById('author').value.trim();
    const text = document.getElementById('text').value.trim();

    if (!author || !text) return;

    fetch(`${apiUrl}?action=comment`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            resource_id: resourceId,
            author,
            text
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.success) {
            document.getElementById('commentForm').reset();
            loadComments();
        }
    })
    .catch(err => console.error(err));
});

loadResource();
loadComments();
