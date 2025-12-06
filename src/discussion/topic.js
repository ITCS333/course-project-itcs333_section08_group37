/*
  Requirement: Populate the single topic page and manage replies.

  Instructions:
  1. Link this file to `topic.html` using:
     <script src="topic.js" defer></script>

  2. In `topic.html`, add the following IDs:
     - To the <h1>: `id="topic-subject"`
     - To the <article id="original-post">:
       - Add a <p> with `id="op-message"` for the message text.
       - Add a <footer> with `id="op-footer"` for the metadata.
     - To the <div> for the list of replies: `id="reply-list-container"`
     - To the "Post a Reply" <form>: `id="reply-form"`

  3. The functions below load topic data, render replies, and manage interactions.
*/

// --- Global Data Store ---
let currentTopicId = null;
let currentReplies = []; // Will hold replies for *this* topic

// --- Element Selections ---
const topicSubject = document.querySelector('#topic-subject');
const opMessage = document.querySelector('#op-message');
const opFooter = document.querySelector('#op-footer');
const replyListContainer = document.querySelector('#reply-list-container');
const replyForm = document.querySelector('#reply-form');
const newReplyText = document.querySelector('#new-reply');
const originalPost = document.querySelector('#original-post');

// --- Functions ---

/**
 * Extract the topic id from the query string.
 */
function getTopicIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

/**
 * Populate the original post details.
 */
function renderOriginalPost(topic) {
  if (!topicSubject || !opMessage || !opFooter) return;
  const { subject, message, author, date, id } = topic;

  topicSubject.textContent = subject;
  opMessage.textContent = message;
  opFooter.textContent = `Posted by: ${author} on ${date}`;

  const opDeleteButton = originalPost?.querySelector('.delete-op-btn');
  if (opDeleteButton) {
    opDeleteButton.dataset.id = id;
  }
}

/**
 * Build a reply article element for the given reply object.
 */
function createReplyArticle(reply) {
  const { id, author, date, text } = reply;
  const article = document.createElement('article');

  const body = document.createElement('p');
  body.textContent = text;

  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${author} on ${date}`;

  const actions = document.createElement('div');
  actions.className = 'actions';

  const deleteButton = document.createElement('button');
  deleteButton.type = 'button';
  deleteButton.className = 'delete-reply-btn';
  deleteButton.dataset.id = id;
  deleteButton.textContent = 'Delete';

  actions.append(deleteButton);
  article.append(body, footer, actions);

  return article;
}

/**
 * Render the list of replies to the container.
 */
function renderReplies() {
  if (!replyListContainer) return;
  replyListContainer.innerHTML = '';

  if (!currentReplies.length) {
    const empty = document.createElement('p');
    empty.textContent = 'No replies yet. Be the first to respond.';
    replyListContainer.appendChild(empty);
    return;
  }

  currentReplies.forEach((reply) => {
    const article = createReplyArticle(reply);
    replyListContainer.appendChild(article);
  });
}

/**
 * Submit handler for posting a reply.
 */
function handleAddReply(event) {
  event.preventDefault();
  if (!newReplyText) return;

  const text = newReplyText.value.trim();
  if (!text) return;

  const newReply = {
    id: `reply_${Date.now()}`,
    author: 'Student',
    date: new Date().toISOString().split('T')[0],
    text,
  };

  currentReplies = [...currentReplies, newReply];
  renderReplies();
  newReplyText.value = '';
}

/**
 * Delegated click handler for deleting replies.
 */
function handleReplyListClick(event) {
  const target = event.target;
  if (!(target instanceof HTMLElement)) return;

  if (target.classList.contains('delete-reply-btn')) {
    const replyId = target.dataset.id;
    if (!replyId) return;

    currentReplies = currentReplies.filter((reply) => reply.id !== replyId);
    renderReplies();
  }
}

/**
 * Fetch topic/reply data, render the page, and attach event handlers.
 */
async function initializePage() {
  currentTopicId = getTopicIdFromURL();

  if (!currentTopicId) {
    if (topicSubject) {
      topicSubject.textContent = 'Topic not found.';
    }
    return;
  }

  try {
    const [topicsResponse, repliesResponse] = await Promise.all([
      fetch('topics.json'),
      fetch('replies.json'),
    ]);

    if (!topicsResponse.ok || !repliesResponse.ok) {
      throw new Error('Failed to load initial data');
    }

    const topicsData = await topicsResponse.json();
    const repliesData = await repliesResponse.json();

    const topic = topicsData.find((item) => item.id === currentTopicId);
    currentReplies = repliesData[currentTopicId] ?? [];

    if (topic) {
      renderOriginalPost(topic);
      renderReplies();

      if (replyForm) {
        replyForm.addEventListener('submit', handleAddReply);
      }

      if (replyListContainer) {
        replyListContainer.addEventListener('click', handleReplyListClick);
      }
    } else if (topicSubject) {
      topicSubject.textContent = 'Topic not found.';
    }
  } catch (error) {
    console.error('Failed to initialize topic page', error);
    if (topicSubject) {
      topicSubject.textContent = 'Topic not found.';
    }
  }
}

// --- Initial Page Load ---
initializePage();
