// --- Global Data Store ---
let currentResourceId = null;
let currentComments = [];

// --- Element Selections ---
const resourceTitle = document.getElementById('resource-title');
const resourceDescription = document.getElementById('resource-description');
const resourceLink = document.getElementById('resource-link');
const commentList = document.getElementById('comment-list');
const commentForm = document.getElementById('comment-form');
const newComment = document.getElementById('new-comment');

// --- Functions ---
function getResourceIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

function renderResourceDetails(resource) {
  resourceTitle.textContent = resource.title;
  resourceDescription.textContent = resource.description;
  resourceLink.href = resource.link;
}

function createCommentArticle(comment) {
  const article = document.createElement('article');

  const p = document.createElement('p');
  p.textContent = comment.text;

  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${comment.author}`;

  article.appendChild(p);
  article.appendChild(footer);

  return article;
}

function renderComments() {
  commentList.innerHTML = '';
  currentComments.forEach(comment => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}

function handleAddComment(event) {
  event.preventDefault();
  const text = newComment.value.trim();
  if (!text) return;

  const commentObj = { author: 'Student', text };
  currentComments.push(commentObj);
  renderComments();
  newComment.value = '';
}

async function initializePage() {
  currentResourceId = getResourceIdFromURL();
  if (!currentResourceId) {
    resourceTitle.textContent = "Resource not found.";
    return;
  }

  try {
    const [resResp, comResp] = await Promise.all([
      fetch('resources.json'),
      fetch('resource-comments.json')
    ]);
    const resources = await resResp.json();
    const commentsData = await comResp.json();

    const resource = resources.find(r => r.id === currentResourceId);
    currentComments = commentsData[currentResourceId] || [];

    if (!resource) {
      resourceTitle.textContent = "Resource not found.";
      return;
    }

    renderResourceDetails(resource);
    renderComments();

    commentForm.addEventListener('submit', handleAddComment);
  } catch (error) {
    console.error("Error loading data:", error);
    resourceTitle.textContent = "Error loading resource.";
  }
}

// --- Initial Page Load ---
initializePage();
