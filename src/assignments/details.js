// --- Global Data Store ---
let currentAssignmentId = null;
let currentComments = [];

// --- Element Selections ---
const assignmentTitle = document.getElementById("assignment-title");
const assignmentDueDate = document.getElementById("assignment-due-date");
const assignmentDescription = document.getElementById("assignment-description");
const assignmentFilesList = document.getElementById("assignment-files-list");

const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentText = document.getElementById("new-comment-text");

// --- Functions ---

// Extract assignment ID from URL
function getAssignmentIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

// Render assignment details on page
function renderAssignmentDetails(assignment) {
  assignmentTitle.textContent = assignment.title;
  assignmentDueDate.textContent = "Due: " + assignment.due;
  assignmentDescription.textContent = assignment.description;

  assignmentFilesList.innerHTML = "";
  assignment.files.forEach(file => {
    const li = document.createElement("li");
    const a = document.createElement("a");
    a.href = "#";
    a.textContent = file;
    li.appendChild(a);
    assignmentFilesList.appendChild(li);
  });
}

// Create comment <article>
function createCommentArticle(comment) {
  const article = document.createElement("article");
  article.classList.add("comment");

  article.innerHTML = `
    <h4>${comment.author}</h4>
    <p>${comment.text}</p>
  `;
  return article;
}

// Render comments
function renderComments() {
  commentList.innerHTML = "";
  currentComments.forEach(c => {
    const article = createCommentArticle(c);
    commentList.appendChild(article);
  });
}

// Add comment event handler
function handleAddComment(event) {
  event.preventDefault();

  const text = newCommentText.value.trim();
  if (!text) return;

  const newComment = {
    author: "Student",
    text: text,
  };

  currentComments.push(newComment);
  renderComments();
  newCommentText.value = "";
}

// Main initializer
async function initializePage() {
  currentAssignmentId = getAssignmentIdFromURL();
  if (!currentAssignmentId) {
    alert("Error: No assignment ID in URL.");
    return;
  }

  try {
    const [assignmentsRes, commentsRes] = await Promise.all([
      fetch("assignments.json"),
      fetch("comments.json"),
    ]);

    const assignments = await assignmentsRes.json();
    const commentsData = await commentsRes.json();

    const assignment = assignments.find(a => a.id == currentAssignmentId);
    currentComments = commentsData[currentAssignmentId] || [];

    if (!assignment) {
      alert("Assignment not found!");
      return;
    }

    // Render assignment + comments
    renderAssignmentDetails(assignment);
    renderComments();

    commentForm.addEventListener("submit", handleAddComment);

  } catch (error) {
    console.error(error);
    alert("Failed to load assignment data.");
  }
}

// --- Initial Load ---
initializePage();
