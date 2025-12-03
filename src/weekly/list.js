// --- Element Selections ---
const listSection = document.querySelector('#week-list-section');

// --- Functions ---

function createWeekArticle(week) {
  const article = document.createElement('article');

  const h2 = document.createElement('h2');
  h2.textContent = week.title;

  const pDate = document.createElement('p');
  pDate.textContent = "Starts on: " + week.startDate;

  const pDesc = document.createElement('p');
  pDesc.textContent = week.description;

  const link = document.createElement('a');
  link.href = `details.html?id=${week.id}`;
  link.textContent = "View Details & Discussion";

  article.appendChild(h2);
  article.appendChild(pDate);
  article.appendChild(pDesc);
  article.appendChild(link);

  return article;
}

async function loadWeeks() {
  try {
    const res = await fetch('weeks.json');
    const weeks = await res.json();

    listSection.innerHTML = "";

    weeks.forEach(week => {
      const article = createWeekArticle(week);
      listSection.appendChild(article);
    });
  } catch (err) {
    console.error("Error loading weeks:", err);
    listSection.textContent = "Failed to load weeks.";
  }
}

// --- Initial Page Load ---
loadWeeks();
