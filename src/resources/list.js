// --- Element Selections ---
const listSection = document.querySelector('#resource-list-section');

// --- Functions ---

/**
 * Create an <article> element for a single resource.
 * @param {Object} resource - {id, title, description}
 * @returns {HTMLElement} - The article element.
 */
function createResourceArticle(resource) {
    const article = document.createElement('article');
    article.classList.add('resource');

    const title = document.createElement('h2');
    title.textContent = resource.title;

    const desc = document.createElement('p');
    desc.textContent = resource.description;

    const link = document.createElement('a');
    link.href = `details.html?id=${resource.id}`;
    link.target = '_blank'; // يفتح في تبويب جديد
    link.textContent = 'View Resource & Discussion';

    article.appendChild(title);
    article.appendChild(desc);
    article.appendChild(link);

    return article;
}

/**
 * Load resources from JSON and render them in the section.
 */
async function loadResources() {
    try {
        const response = await fetch('resources.json');
        const resources = await response.json();

        // Clear existing content
        listSection.innerHTML = '';

        resources.forEach(resource => {
            const article = createResourceArticle(resource);
            listSection.appendChild(article);
        });
    } catch (error) {
        console.error('Error loading resources:', error);
        listSection.innerHTML = '<p>Failed to load resources.</p>';
    }
}

// --- Initial Page Load ---
loadResources();
