
function createResourceArticle(resource) {
    const li = document.createElement('li');
    li.innerHTML = `
        <a href="Details.html?id=${resource.id}">${resource.title}</a>
        <p>${resource.description}</p>
    `;
    return li;
}


window.createResourceArticle = createResourceArticle;

const apiUrl = 'index.php';

function loadResources() {
    const list = document.getElementById('resourcesList');
    list.innerHTML = '';

    fetch(apiUrl)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                data.data.forEach(res => {
                    const li = createResourceArticle(res);
                    list.appendChild(li);
                });
            } else {
                list.innerHTML = '<li>No resources available</li>';
            }
        })
        .catch(err => {
            console.error('Error loading resources:', err);
            list.innerHTML = '<li>Error loading resources</li>';
        });
}

loadResources();
