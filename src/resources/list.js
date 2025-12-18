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

async function loadResources() {
    const list = document.getElementById('resourcesList');
    list.innerHTML = '';

    try {
        const res = await fetch(apiUrl);
        const data = await res.json();

        if (data.success && data.data.length > 0) {
            data.data.forEach(resource => {
                const li = createResourceArticle(resource);
                list.appendChild(li);
            });
        } else {
            list.innerHTML = '<li>No resources available</li>';
        }
    } catch (err) {
        console.error('Error loading resources:', err);
        list.innerHTML = '<li>Error loading resources</li>';
    }
}

window.loadResources = loadResources;


loadResources();

