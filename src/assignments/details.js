/*
  Requirement: Populate the assignment detail page and discussion forum.

  Instructions:
  1. Link this file to `details.html` using:
     <script src="details.js" defer></script>

  2. In `details.html`, add the following IDs:
     - To the <h1>: `id="assignment-title"`
     - To the "Due" <p>: `id="assignment-due-date"`
     - To the "Description" <p>: `id="assignment-description"`
     - To the "Attached Files" <ul>: `id="assignment-files-list"`
     - To the <div> for comments: `id="comment-list"`
     - To the "Add a Comment" <form>: `id="comment-form"`
     - To the <textarea>: `id="new-comment-text"`

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// These will hold the data related to *this* assignment.
let currentAssignmentId = null;
let currentComments = [];

// --- Element Selections ---
// TODO: Select all the elements added IDs for
const assignmentTitle = document.getElementById("assignment-title");
const assignmentDueDate = document.getElementById("assignment-due-date");
const assignmentDescription = document.getElementById("assignment-description");
const assignmentFilesList = document.getElementById("assignment-files-list");

const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentText = document.getElementById("new-comment-text");

// --- Functions ---

/**
 * TODO: Implement the getAssignmentIdFromURL function.
 */
function getAssignmentIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

/**
 * TODO: Implement the renderAssignmentDetails function.
 */
function renderAssignmentDetails(assignment) {
  assignmentTitle.textContent = assignment.title;
  assignmentDueDate.textContent = "Due: " + assignment.dueDate;
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

/**
 * TODO: Implement the createCommentArticle function.
 */
function createCommentArticle(comment) {
  const article = document.createElement("article");
  article.innerHTML = `
    <p>${comment.text}</p>
    <footer>Posted by: ${comment.author}</footer>
  `;
  return article;
}

/**
 * TODO: Implement the renderComments function.
 */
function renderComments() {
  commentList.innerHTML = "";
  currentComments.forEach(c => {
    commentList.appendChild(createCommentArticle(c));
  });
}

/**
 * TODO: Implement the handleAddComment function.
 */
function handleAddComment(event) {
  event.preventDefault();
  const text = newCommentText.value.trim();
  if (!text) return;

  const newComment = { author: "Student", text };
  currentComments.push(newComment);
  renderComments();
  newCommentText.value = "";
}

/**
 * TODO: Implement an `initializePage` function.
 */
async function initializePage() {
  currentAssignmentId = getAssignmentIdFromURL();
  if (!currentAssignmentId) {
    alert("Error: No assignment ID in URL.");
    return;
  }

  try {
    const [assignmentsRes, commentsRes] = await Promise.all([
      fetch("assignments.json"),
      fetch("comments.json")
    ]);

    const assignments = await assignmentsRes.json();
    const commentsData = await commentsRes.json();

    const assignment = assignments.find(a => a.id === currentAssignmentId);
    currentComments = commentsData[currentAssignmentId] || [];

    if (!assignment) {
      alert("Assignment not found!");
      return;
    }

    renderAssignmentDetails(assignment);
    renderComments();
    commentForm.addEventListener("submit", handleAddComment);

  } catch (error) {
    console.error(error);
    alert("Failed to load assignment data.");
  }
}

// --- Initial Page Load ---
initializePage();
