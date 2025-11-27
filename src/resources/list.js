// --- Element Selections ---
const listSection = document.getElementById('resource-list-section');

// --- Functions ---
function createResourceArticle(resource) {
  const article = document.createElement('article');

  const h3 = document.createElement('h3');
  h3.textContent = resource.title;

  const p = document.createElement('p');
  p.textContent = resource.description;

  const a = document.createElement('a');
  a.href = `details.html?id=${resource.id}`;
  a.textContent = "View Resource & Discussion";
  a.target = "_blank";

  article.appendChild(h3);
  article.appendChild(p);
  article.appendChild(a);

  return article;
}

async function loadResources() {
  try {
    const response = await fetch('resources.json');
    const resources = await response.json();

    listSection.innerHTML = '';
    resources.forEach(resource => {
      const article = createResourceArticle(resource);
      listSection.appendChild(article);
    });
  } catch (error) {
    console.error("Error loading resources:", error);
    listSection.textContent = "Failed to load resources.";
  }
}

// --- Initial Page Load ---
loadResources();
