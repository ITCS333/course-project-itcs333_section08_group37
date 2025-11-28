// --- Element Selections ---
const listSection = document.getElementById("assignment-list-section");

// --- Functions ---

function createAssignmentArticle(assignment) {
  const article = document.createElement("article");

  article.innerHTML = `
      <h2>${assignment.title}</h2>
      <p>Due: ${assignment.due}</p>
      <p>${assignment.description}</p>
      <a href="details.html?id=${assignment.id}">
        View Details & Discussion
      </a>
  `;

  return article;
}

async function loadAssignments() {
  try {
    const response = await fetch("assignments.json");
    const assignments = await response.json();

    listSection.innerHTML = "";

    assignments.forEach(assignment => {
      const article = createAssignmentArticle(assignment);
      listSection.appendChild(article);
    });

  } catch (err) {
    console.error(err);
    listSection.textContent = "Failed to load assignments.";
  }
}

// --- Initial Page Load ---
loadAssignments();
