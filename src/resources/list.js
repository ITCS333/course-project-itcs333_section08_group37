const apiUrl = 'index.php';

function loadResources() {
    fetch(apiUrl)
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('resourcesList');
            list.innerHTML = '';
            if(data.success) {
                data.data.forEach(res => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <a href="Details.html?id=${res.id}">${res.title}</a>
                        <p>${res.description}</p>
                    `;
                    list.appendChild(li);
                });
            } else {
                list.innerHTML = '<li>No resources available</li>';
            }
        });
}


loadResources();
