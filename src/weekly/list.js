/*
  Requirement: Populate the "Weekly Course Breakdown" list page.
*/

// --- Element Selections ---
const listSection = document.querySelector("#week-list-section");

// --- Functions ---

/**
 * Create an <article> for a single week.
 */
function createWeekArticle(week) {
  const article = document.createElement("article");

  const titleEl = document.createElement("h2");
  titleEl.textContent = week.title || "Untitled week";

  const dateEl = document.createElement("p");
  if (week.startDate) {
    dateEl.textContent = "Starts on: " + week.startDate;
  } else {
    dateEl.textContent = "Starts on: N/A";
  }

  const descEl = document.createElement("p");
  descEl.textContent = week.description || "";

  const linkEl = document.createElement("a");
  linkEl.href = details.html?id=${week.id};
  linkEl.textContent = "View Details & Discussion";

  article.appendChild(titleEl);
  article.appendChild(dateEl);
  article.appendChild(descEl);
  article.appendChild(linkEl);

  return article;
}

/**
 * Load weeks from weeks.json and render them.
 */
async function loadWeeks() {
  if (!listSection) return;

  try {
    const response = await fetch("weeks.json");
    const weeksData = await response.json();

    listSection.innerHTML = "";

    if (Array.isArray(weeksData)) {
      weeksData.forEach((week) => {
        const article = createWeekArticle(week);
        listSection.appendChild(article);
      });
    }
  } catch (error) {
    console.error("Error loading weeks:", error);
    // ممكن نحط رسالة بسيطة في الصفحة لو حبيت
    const errorMsg = document.createElement("p");
    errorMsg.textContent = "Could not load weekly breakdown.";
    listSection.innerHTML = "";
    listSection.appendChild(errorMsg);
  }
}

// --- Initial Page Load ---
loadWeeks();
